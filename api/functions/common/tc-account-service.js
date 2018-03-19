'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')
const begin = require('any-db-transaction')
const md5 = require('md5')
const moment = require('moment-timezone')
const semver = require('semver')
const validator = require('validator')

const db = require('./tc-database')
const jwt = require('./jwt')
const uuidV4 = require('uuid/v4')

const AWS = require('aws-sdk')
// Configure the global region BEFORE instantiating anything
// else from the AWS SDK for it to work properly
AWS.config.update({region: 'us-east-1'})
const s3 = new AWS.S3()

const TCAccount = require('./tc-account')
const TCEmailVerification = require('./tc-email-verification')
const TCList = require('./tc-list')
const TCPasswordReset = require('./tc-password-reset')
const TCSubscription = require('./tc-subscription')
const TCUserSettings = require('./tc-user-settings')

const TCEmailVerificationService = require('./tc-email-verification-service')
const TCListService = require('./tc-list-service')
const TCMailerService = require('./tc-mailer-service')
const TCSmartListService = require('./tc-smart-list-service')
const TCSubscriptionService = require('./tc-subscription-service')
const TCUserSettingsService = require('./tc-user-settings-service')
const TCChangeLogService = require('./tc-changelog-service')

const constants = require('./constants')
const errors = require('./errors')

const UNIT_TEST_API_KEY = "B9FDB877-85CC-4136-8F0A-59E7CBE60C78"
const LUCKY_NUMBER = "47"

const PROFILE_IMAGE_SIZE = 50

class TCAccountService {

    static getJWT(completion) {
        db.getSetting(constants.SettingCurrentJWTKey, function(err, jwt) {
            completion(err, jwt)
        })
    }

    static saveJWT(newJWT, completion) {
        const params = {}
        params[constants.SettingCurrentJWTKey] = newJWT
        db.setSettings(params, function(err, result) {
            completion(err, result)
        })
    }

    static checkForUpdates(params, completion) {
        const clientAppVersion = params.version
        const distributionType = params.dist
        const platform = params.platform
        const architecture = params.arch
        if (!clientAppVersion) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the version param.`))))
            return
        }
        if (!distributionType) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the dist param.`))))
            return
        }
        if (!platform) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the platform param.`))))
            return
        }

        if (semver.valid(clientAppVersion) == null) {
            completion(new Error(JSON.stringify(errors.customError(errors.invalidParameters, `Invalid version specified`))))
            return
        }

        // Look for the latest version of the app that we've posted to S3.
        // If the version of the app on the server matches the client, return
        // null. Otherwise, formulate the response into something like:
        // {
        //      "url": "https://mycompany.example.com/myapp/releases/myrelease",
        //      "name": "My Release Name",
        //      "notes": "Theses are some release notes innit",
        //      "pub_date": "2013-09-18T12:29:53+01:00"
        // }
        //
        // The place we will look is:
        // Bucket: downloads.todo-cloud.com
        // 
        let prefix = `todo-cloud/${distributionType}/${platform}`
        const bucket = `downloads.todo-cloud.com`
        if (platform != "macos" && architecture) {
            prefix += `/${architecture}`
        }
        const listParams = {
            Bucket: bucket,
            MaxKeys: 10,
            Prefix: prefix
        }
        s3.listObjectsV2(listParams, function(err, result) {
            if (err) {
                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not communicate with Amazon S3: ${err.message}`))))
            } else {
                const objects = result.Contents
                let maxServerVersion = null
                let pubDate = null
                let objectKey = null
                if (objects) {
                    objects.forEach(object => {
                        // Parse out the version of the object on S3
                        const key = object.Key
                        if (key.indexOf('.') > 0) {
                            let fileName = key.substring(0, key.lastIndexOf('.')).substr(key.lastIndexOf('/') + 1)
                            let versionString = fileName.match(/\d+\..*/)[0]

                            if (maxServerVersion == null || semver.gt(versionString, maxServerVersion)) {
                                maxServerVersion = versionString
                                pubDate = object.LastModified
                                objectKey = key
                            }
                        }
                    })
                }
                if (maxServerVersion != null && maxServerVersion != clientAppVersion) {
                    const versionInfo = {
                        url: `https://s3.amazonaws.com/${bucket}/${objectKey}`,
                        name: `Todo Cloud`,
                        notes: maxServerVersion,
                        pub_date: pubDate
                    }
                    completion(null, versionInfo)
                } else {
                    // No new update posted or the versions are the same
                    completion(null, null)
                }
            }
        })
    }

    // This should be called when running inside of a local SQLite DB environment
    // to create the necessary lists and smart lists to support running the app.
    static initializeLocalAccountIfNeeded(params, completion) {
        let dbPool = null

        // Varables to be set and used later
        const userid = params.userid
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid param.`))))
            return
        }

        async.waterfall([
            function(callback) {
                // NOTE: No need to check the value of result because if we make it to here,
                // there was no error in the previous waterfall function.
                db.getPool(function(err, pool) {
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                        } else {
                            callback(null, transaction)
                        }
                    })
                })
            },
            function(transaction, callback) {
                // Check to see if the INBOX is already created locally. If it
                // is not, this is likely the first time the user has signed in
                // and we need to create it.
                const listParams = {
                    userid: userid,
                    listid: constants.LocalInboxId,
                    dbConnection: transaction
                }
                TCListService.getList(listParams, function(err, inbox) {
                    if (err) {
                        // Treat this as not having an INBOX at all
                        callback(null, transaction, null)
                    } else {
                        callback(null, transaction, inbox)
                    }
                })
            },
            function(transaction, inbox, callback) {
                if (inbox) {
                    // An inbox already exists, which indicates that a local account
                    // is already configured. Bail.
                    callback(new Error(JSON.stringify(errors.nonError)), transaction)
                } else {
                    callback(null, transaction)
                }
            },
            function(transaction, callback) {
                // Clear out any data that may be lurking in the db file.
                const params = { dbTransaction : transaction, keepLocalSettings : true }
                TCAccountService.deleteLocalAccount((err, result) => callback(err, transaction), params)
            },
            function(transaction, callback) {
                let listParams = {
                    listid: constants.LocalInboxId,
                    userid: userid,
                    name: 'Inbox', // TO-DO Figure out how to localize this!
                    dbTransaction: transaction
                }
                TCListService.addList(listParams, function(err, inbox) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error adding a new inbox list for a new user account (${userid}).`))), transaction)
                    } else {
                        // Creating the inbox was successful!
                        callback(null, transaction, inbox)
                    }
                })
            },
            function(transaction, inbox, callback) {
                if (inbox.list) {
                    inbox = inbox.list
                }
                const createParams = {
                    userid:userid,
                    userInbox:inbox.listid,
                    taskCreationEmail: "LOCAL", // bogus value, but required by the API
                    referralCode: "LOCAL", // bogus value, but required by the API
                    dbConnection:transaction
                }
                TCUserSettingsService.createUserSettings(createParams, function(err, userSettings) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error creating a user settings record for a new user account (${userid}).`))), transaction)
                    } else {
                        callback(null, transaction) // success!
                    }
                })
            },
            function(transaction, callback) {
                // Create the built-in "Everything" smart list
                let params = {
                    listid: constants.LocalEverythingSmartListId,
                    userid: userid,
                    name: "Everything", // TO-DO: Need to figure out how to provide a localized version of this
                    sort_order: 1,
                    icon_name: "menu-everything",
                    color: constants.SmartListColor.Blue,
                    json_filter: constants.SmartListJSONFilter.Everything,
                    sort_type: -1,
                    default_due_date: -1,
                    dbConnection: transaction
                }
                TCSmartListService.createSmartList(params, function(err, result) {
                    callback(err, transaction)
                })
            },
            function(transaction, callback) {
                // Create the built-in "Focus" smart list
                let params = {
                    listid: constants.LocalFocusSmartListId,
                    userid: userid,
                    name: "Focus", // TO-DO: Need to figure out how to provide a localized version of this
                    sort_order: 2,
                    icon_name: "menu-focus",
                    color: constants.SmartListColor.Orange,
                    json_filter: constants.SmartListJSONFilter.Focus,
                    sort_type: -1,
                    default_due_date: -1,
                    dbConnection: transaction
                }
                TCSmartListService.createSmartList(params, function(err, result) {
                    callback(err, transaction)
                })
            },
            function(transaction, callback) {
                // Create the built-in "Important" smart list
                let params = {
                    listid: constants.LocalImportantSmartListId,
                    userid: userid,
                    name: "Important", // TO-DO: Need to figure out how to provide a localized version of this
                    sort_order: 3,
                    icon_name: "menu-important",
                    color: constants.SmartListColor.Yellow,
                    json_filter: constants.SmartListJSONFilter.Important,
                    sort_type: -1,
                    default_due_date: -1,
                    dbConnection: transaction
                }
                TCSmartListService.createSmartList(params, function(err, result) {
                    callback(err, transaction)
                })
            },
            function(transaction, callback) {
                // Create the built-in "Someday" smart list
                let params = {
                    listid: constants.LocalSomedaySmartListId,
                    userid: userid,
                    name: "Someday", // TO-DO: Need to figure out how to provide a localized version of this
                    sort_order: 4,
                    icon_name: "menu-someday",
                    color: constants.SmartListColor.Gray,
                    json_filter: constants.SmartListJSONFilter.Someday,
                    sort_type: -1,
                    default_due_date: -1,
                    dbConnection: transaction
                }
                TCSmartListService.createSmartList(params, function(err, result) {
                    callback(err, transaction)
                })
            }
        ],
        function(err, transaction) {
            if (err) {
                let errObj = JSON.parse(err.message)
                if (errObj.errorType == errors.nonError.errorType) {
                    // This isn't actually an error and the account is already configured
                    completion(null, true)
                } else {
                    completion(new Error(JSON.stringify(errors.customError(errObj, `Could not configure a new account.`))))
                }
                if (transaction) {
                    transaction.rollback(function(transErr, result) {
                        // Ignore the result. The transaction will be closed or we'll get an error
                        // because the transaction has already been rolled back.
                        db.cleanup()
                    })
                } else {
                    db.cleanup()
                }
            } else {
                if (transaction) {
                    transaction.commit(function(err, result) {
                        if (err) {
                            completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not add a new account. Database commit failed: ${err.message}`))))
                        } else {
                            completion(null, true)
                        }
                        db.cleanup()
                    })
                } else {
                    db.cleanup()
                }
            }
        })
    }

    static deleteLocalAccount(completion, params) {
        // Safety precaution. Make sure that we're actually running locally
	    if (process.env.DB_TYPE != 'sqlite') {
            completion(null)
            return
        }

        const dbTransaction = params != undefined ? params.dbTransaction : null
        const shouldCleanupDB = dbTransaction == null
        let dbPool = null

        async.waterfall([
            function(callback) {
                if (dbTransaction) {
                    callback(null, dbTransaction)
                    return
                }

                db.getPool(function(err, pool) {
                    dbPool = pool
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                        } else {
                            callback(null, transaction)
                        }
                    })
                })
            },
            function(transaction, callback) {
                // Delete records for a number of tables that are
                // indexed by the userid.
                const tableNames = [
                    "tdo_archived_taskitos",
                    "tdo_archived_tasks",
                    "tdo_autorenew_history",
                    "tdo_bounced_emails",
                    "tdo_change_log",
                    "tdo_comments",
                    "tdo_completed_tasks",
                    "tdo_context_assignments",
                    "tdo_contexts",
                    "tdo_deleted_tasks",
                    "tdo_contexts",
                    "tdo_list_memberships",
                    "tdo_list_settings",
                    "tdo_smart_lists",
                    "tdo_lists",
                    "tdo_tag_assignments",
                    "tdo_tags",
                    "tdo_task_notifications",
                    "tdo_taskitos",
                    "tdo_tasks",
                    "tdo_user_settings",
                    "tdo_user_accounts"
                ]

                if (!params || !params.keepLocalSettings) tableNames.push("tdo_local_settings")

                async.each(tableNames, function(tableName, eachCallback) {
                    let sql = `DELETE FROM ${tableName}`
                    transaction.query(sql, null, function(err, result) {
                        if (err) {
                            eachCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))))
                        } else {
                            eachCallback(null)
                        }
                    })
                },
                function(err) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            }
        ],
        function(err, transaction) {
            if (err) {
                if (shouldCleanupDB) {
                    if (transaction) {
                        let errObj = JSON.parse(err.message)
                        completion(new Error(JSON.stringify(errors.customError(errObj, `Could not clear the local database.`))))
                        transaction.rollback(function(transErr, result) {
                            // Ignore the result. The transaction will be closed or we'll get an error
                            // because the transaction has already been rolled back.
                            db.cleanup()
                        })
                    } else {
                        let errObj = JSON.parse(err.message)
                        completion(new Error(JSON.stringify(errors.customError(errObj, `Could not clear the local db.`))))
                        db.cleanup()
                    }
                }
                else {
                    completion(new Error(JSON.stringify(errors.customError(errObj, `Could not clear the local db.`))))
                }
            } else {
                if (shouldCleanupDB) {
                    if (transaction) {
                        transaction.commit(function(err, result) {
                            if (err) {
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not clear the local database. Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, JSON.stringify({success:true}))
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                }
                else {
                    completion(null, JSON.stringify({success:true}))
                }
            }
        })
    }

    static createAccount(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let username = userInfo.username && typeof userInfo.username == 'string' ? userInfo.username.trim() : null
        let password = userInfo.password && typeof userInfo.password == 'string' ? userInfo.password.trim() : null
        let firstName = userInfo.first_name && typeof userInfo.first_name == 'string' ? userInfo.first_name.trim() : null
        let lastName = userInfo.last_name && typeof userInfo.last_name == 'string' ? userInfo.last_name.trim() : null
        let locale = userInfo.locale && typeof userInfo.locale == 'string' ? userInfo.locale.trim() : null
        let localeBestMatch = userInfo.locale_best_match && typeof userInfo.locale_best_match == 'string' ? userInfo.locale_best_match.trim() : null
        let emailOptIn = userInfo.email_opt_in !== undefined ? userInfo.email_opt_in : 0

        if (!username || username.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the username parameter.`))))
            return
        }
        if (username.length > constants.maxUsernameLength) {
            callback(new Error(JSON.stringify(errors.usernameLengthExceeded)))
            return
        }

        if (!password || password.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the password parameter.`))))
            return
        }

        // Enforce the password rules
        if (password.length > constants.maxPasswordLength) {
            callback(new Error(JSON.stringify(errors.passwordLengthExceeded)))
            return
        }

        if (password.length < constants.minPasswordLength) {
            callback(new Error(JSON.stringify(errors.passwordTooShort)))
            return
        }
        
        if (!firstName || firstName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the first_name parameter.`))))
            return
        }
        if (firstName.length > constants.maxFirstNameLength) {
            callback(new Error(JSON.stringify(errors.firstNameLengthExceeded)))
            return
        }
        if (!lastName || lastName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the last_name parameter.`))))
            return
        }
        if (lastName.length > constants.maxLastNameLength) {
            callback(new Error(JSON.stringify(errors.lastNameLengthExceeded)))
            return
        }

        if (localeBestMatch) {
            // Ensure that the specified best match locale is one that we support
            if (constants.supportedLocales.indexOf(localeBestMatch) < 0) {
                callback(new Error(JSON.stringify(errors.bestMatchLocaleInvalid)))
                return
            }
        }

        // We store all usernames (email addresses) into the database in lowercase characters
        username = username.toLowerCase()

        // Check to see if this is a valid email address.
        if (!validator.isEmail(username)) {
            callback(new Error(JSON.stringify(errors.usernameInvalid)))
            return
        }

        // Allow + character ONLY if from the @appigo.com domain.
        if (username.indexOf('+') !== -1) {
            if (username.endsWith('@appigo.com') === false) {
                callback(new Error(JSON.stringify(errors.usernameInvalidPlusCharacter)))
                return
            }
        }

        async.waterfall([
            function(callback) {
                // Ensure that the username doesn't already exist
                TCAccountService.accountForUsername({username:username}, function(err, account) {
                    if (err) {
                        // If, in this case, the error is accountNotFound, that's OK
                        // and we can continue on normally.
                        let errObj = JSON.parse(err.message)
                        if (errObj.errorType !== errors.accountNotFound.errorType) {
                            callback(new Error(JSON.stringify(errors.customError(errors.serverError, `Error determining if an account with the specified username (${username}) already exists: ${err.message}`))), null)
                            return
                        }
                    } else if (account) {
                        callback(new Error(JSON.stringify(errors.usernameAlreadyExists)), null)
                        return
                    }

                    // If we made it here, the account does not yet exist and we can go ahead and create it
                    callback(null)
                })
            },
            function(callback) {
                // NOTE: No need to check the value of result because if we make it to here,
                // there was no error in the previous waterfall function.
                db.getPool(function(err, pool) {
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                        } else {
                            callback(null, transaction)
                        }
                    })
                })
            },
            function(transaction, callback) {
                let newAccount = new TCAccount()
                newAccount.configureWithProperties({
                    username: username,
                    password: password,
                    email_opt_out: !emailOptIn,
                    first_name: firstName,
                    last_name: lastName,
                    locale: locale,
                    best_match_locale: localeBestMatch
                })
                newAccount.add(transaction, function(err, account) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding a new user account (${username}): ${err.message}`))), transaction)
                    } else {
                        // Account added successfully!
                        callback(null, transaction, account)
                    }
                })
            },
            function(transaction, account, callback) {
                // Use the subscription service to create a new subscription.
                // Pass in our transaction so it will be used instead of a new
                // db connection.

                TCSubscriptionService.createSubscription({userid: account.userid, dbConnection:transaction}, function (subErr, subscription) {
                    if (subErr) {
                        callback(new Error(JSON.stringify(errors.customError(subErr, `Error adding a new subscription record for a new user account (${username}).`))), transaction)
                    } else {
                        // Subscription added successfully! No need to pass the subscription object on.
                        callback(null, transaction, account)
                    }
                })
            },
            function(transaction, account, callback) {
                let listParams = {
                    userid: account.userid,
                    name: 'Inbox', // TO-DO Figure out how to localize this!
                    dbTransaction: transaction
                }
                TCListService.addList(listParams, function(err, inbox) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error adding a new inbox list for a new user account (${username}).`))), transaction)
                    } else {
                        // Creating the inbox was successful!
                        callback(null, transaction, account, inbox.list)
                    }
                })
            },
            function(transaction, account, inbox, callback) {
                // Determine a suitable task creation email for the user
                TCUserSettingsService.newTaskCreationEmailForUsername({username:username, dbConnection:transaction}, function(err, taskCreationEmail) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error determining a new task creation email for a new user account (${username}).`))), transaction)
                    } else {
                        // We have a task creation email we can use to set up a user settings record
                        callback(null, transaction, account, inbox, taskCreationEmail)
                    }
                })
            },
            function(transaction, account, inbox, taskCreationEmail, callback) {
                // Determine a referral code for the user
                TCUserSettingsService.newReferralCode({dbConnection:transaction}, function(err, referralCode) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error determining a new referral code for a new user account (${username}).`))), transaction)
                    } else {
                        // We have a new referral code we can use to set up a user settings record
                        callback(null, transaction, account, inbox, taskCreationEmail, referralCode)
                    }
                })
            },
            function(transaction, account, inbox, taskCreationEmail, referralCode, callback) {
                TCUserSettingsService.createUserSettings({userid:account.userid, userInbox:inbox.listid, taskCreationEmail:taskCreationEmail, referralCode:referralCode, dbConnection:transaction}, function(err, userSettings) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error creating a user settings record for a new user account (${username}).`))), transaction)
                    } else {
                        callback(null, transaction, account, taskCreationEmail) // success!
                    }
                })
            },
            function(transaction, account, taskCreationEmail, callback) {
                // Create a verify email record
                TCEmailVerificationService.createEmailVerification({userid:account.userid, username:account.username, dbConnection:transaction}, function(err, emailVerification) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error creating an email verification record for a new user account (${username}).`))), transaction)
                    } else {
                        // Create the URL for the verification email
                        let verifyEmailURL = `${process.env.WEBAPP_BASE_URL}/verify-email/${emailVerification.verificationid}`
                        callback(null, transaction, account, taskCreationEmail, verifyEmailURL)
                    }
                })
            },
            function(transaction, account, taskCreationEmail, verifyEmailURL, callback) {
                const createDefaultSmartListParams = {
                    userid : account.userid,
                    dbConnection : transaction
                }
                TCSmartListService.createDefaultSmartLists(createDefaultSmartListParams, (err, result) => {
                    if (err) {
                        callback(err, transaction)
                        return
                    }
                    callback(null, transaction, account, taskCreationEmail, verifyEmailURL)
                })
            }
        ],
        function(err, transaction, account, taskCreationEmail, verifyEmailURL) {
            if (err) {
                if (transaction) {
                    let errObj = JSON.parse(err.message)
                    callback(new Error(JSON.stringify(errors.customError(errObj, `Could not create new account.`))))
                    transaction.rollback(function(transErr, result) {
                        // Ignore the result. The transaction will be closed or we'll get an error
                        // because the transaction has already been rolled back.
                        db.cleanup()
                    })
                } else {
                    let errObj = JSON.parse(err.message)
                    callback(new Error(JSON.stringify(errors.customError(errObj, `Could not create new account.`))))
                    db.cleanup()
                }
            } else {
                if (transaction) {
                    transaction.commit(function(err, result) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not add a new account. Database commit failed: ${err.message}`))))
                        } else {
                            // Send out the welcome email to the new user!
                            let displayName = account.first_name
                            if (displayName === undefined) {
                                displayName = account.username
                            } else if (account.last_name !== undefined) {
                                displayName += ` ${account.last_name}`
                            }
                            
                            TCMailerService.sendWelcomeEmail({email:account.username, displayName:displayName, verifyEmailURL:verifyEmailURL, taskCreationEmail:taskCreationEmail}, function(mailErr, mailResult) {
                                if (mailErr) {
                                    logger.debug(`Error sending welcome email (${account.username}): ${mailErr.message}`)
                                } else {
                                    if (!mailResult) {
                                        logger.debug(`Welcome email was not sent to: ${account.username}`)
                                    }
                                }

                                // Send back the newly-created account regardless
                                // of the result of sending the welcome email.
                                callback(null, account)
                            })
                        }
                        db.cleanup()
                    })
                } else {
                    db.cleanup()
                }
            }
        })
    }

    static accountExport(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        // This method can only succeed if the caller is a root-level administrator.

        let userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        let customerUsername = params.customerUsername && typeof params.customerUsername == 'string' ? params.customerUsername.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!customerUsername || customerUsername.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the customerUsername parameter.`))))
            return
        }

        let userFolderName = customerUsername.replace('@', '_').replace('+', '_')
        let s3UrlPrefix = `${process.env.DATA_EXTRACTION_S3_URL_PREFIX}/${userFolderName}`
        let commaSeparatedListIDs = null

        // This function won't actually do the exporting, but just build the queries
        // needed to export the user's data. The export queries would be too time
        // intensive to run inside a single Lambda query.
        let exportStatements = []
        
        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {
                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                TCAccountService.getUserAdminLevel({userid: userid, dbConnection: connection}, function(err, adminLevel) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (adminLevel >= constants.AdminLevel.Root) {
                            callback(null, connection)
                        } else {
                            callback(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                        }
                    }
                })
            },
            function(connection, callback) {
                TCAccountService.accountForUsername({username: customerUsername, dbConnection: connection}, function(err, anAccount) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, anAccount)
                    }
                })
            },
            function(connection, customerAccount, callback) {

                // First, take care of all tables that can be indexed by the userid
                const simpleTables = [
                    `tdo_user_accounts`,
                    `tdo_user_settings`,
                    `tdo_user_devices`,
                    `tdo_user_account_log`,
                    `tdo_subscriptions`,
                    `tdo_smart_lists`,
                    `tdo_list_settings`,
                    `tdo_list_memberships`
                ]
                simpleTables.forEach(tableName => {
                    const s3FileUrl = `${s3UrlPrefix}/${tableName}`
                    const sql = `SELECT * FROM ${tableName} WHERE userid='${customerAccount.userid}' INTO OUTFILE S3 '${s3FileUrl}' OVERWRITE ON;`
                    exportStatements.push(sql)
                })
                callback(null, connection, customerAccount)
            },
            function(connection, customerAccount, callback) {
                // Get the listIDs for the user
                const getListParams = {
                    userid: customerAccount.userid,
                    includeFiltered: true,
                    dbConnection: connection
                }
                TCListService.listIDsForUser(getListParams, function(err, listIDs) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, customerAccount, listIDs)
                    }
                })
            },
            function(connection, customerAccount, listIDs, callback) {
                // Write out the customer's lists
                // Convert the lists of lists into a comma-separated list, encapsulated with quotes
                commaSeparatedListIDs = listIDs.map(listID => `'${listID}'`).join(',')
                const s3FileUrl = `${s3UrlPrefix}/tdo_lists`
                const sql = `SELECT * FROM tdo_lists WHERE listid IN (${commaSeparatedListIDs}) INTO OUTFILE S3 '${s3FileUrl}' OVERWRITE ON;`
                exportStatements.push(sql)
                callback(null, connection, customerAccount, listIDs)
            },
            function(connection, customerAccount, listIDs, callback) {
                // Write out all of the tasks assigned to the user's lists
                const tableNames = [
                    `tdo_tasks`,
                    `tdo_completed_tasks`,
                    `tdo_deleted_tasks`,
                    // `tdo_archived_tasks`
                ]
                tableNames.forEach(tableName => {
                    const s3FileUrl = `${s3UrlPrefix}/${tableName}`
                    const sql = `SELECT * FROM ${tableName} WHERE listid IN (${commaSeparatedListIDs}) INTO OUTFILE S3 '${s3FileUrl}' OVERWRITE ON;`
                    exportStatements.push(sql)
                })
                callback(null, connection, customerAccount, listIDs)
            },
            function(connection, customerAccount, listIDs, callback) {

                // Get taskitos per list
                listIDs.forEach(listID => {
                    const s3FileUrl = `${s3UrlPrefix}/tdo_taskitos_${listID}`
                    let sql = `SELECT tdo_taskitos.* FROM tdo_taskitos JOIN tdo_tasks ON (tdo_taskitos.parentid = tdo_tasks.taskid) WHERE listid='${listID}'`
                    sql += ` UNION SELECT tdo_taskitos.* FROM tdo_taskitos JOIN tdo_completed_tasks ON (tdo_taskitos.parentid = tdo_completed_tasks.taskid) WHERE listid='${listID}'`
                    sql += ` UNION SELECT tdo_taskitos.* FROM tdo_taskitos JOIN tdo_deleted_tasks ON (tdo_taskitos.parentid = tdo_deleted_tasks.taskid) WHERE listid='${listID}' INTO OUTFILE S3 '${s3FileUrl}' OVERWRITE ON;`
                    exportStatements.push(sql)
                })
                callback(null, connection, customerAccount, listIDs)
            }
            // function(connection, customerAccount, listIDs, callback) {
            //     // Data that needs to be exported:

            //     // tdo_tag_assignments
            //     // tdo_tags
            //     // tdo_task_notifications
                
            // },
        ],
        function(err, connection) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not build export statements for customer.`))))
            } else {
                completion(null, {
                    success: true,
                    statements: exportStatements
                })
            }
        })
    }

    // userInfo parameters:
    //  username: string
    //  password: string (clear text and not hashed)
    static authenticate(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let username = userInfo.username && typeof userInfo.username == 'string' ? userInfo.username.trim() : null
        let password = userInfo.password && typeof userInfo.password == 'string' ? userInfo.password.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!username || username.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the username parameter.`))))
            return
        }

        if (!password || password.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the password parameter.`))))
            return
        }

        // We store all usernames (email addresses) in the database in lowercase
        // characters, so make sure that the username query with is also lower case
        username = username.toLowerCase()

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {
                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                TCAccountService.userIdForUsername({username:username, dbConnection:connection}, function(err, userid) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userid)
                    }
                })
            },
            function(connection, userid, callback) {
                TCAccountService.isUserInMaintenance({userid:userid, dbConnection:connection}, function(err, userInMaintenance) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (userInMaintenance) {
                            callback(new Error(JSON.stringify(errors.accountInMaintenance)), connection)
                        } else {
                            callback(null, connection, userid)
                        }
                    }
                })
            },
            function(connection, userid, callback) {
                TCAccountService.matchPassword({username:username, password:password, dbConnection:connection}, function(err, passwordMatched) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userid, passwordMatched)
                    }
                })
            }
        ],
        function(err, connection, userid, passwordMatched) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not authenticate the user (${username}).`))))
            } else {
                if (!passwordMatched) {
                    callback(new Error(JSON.stringify(errors.passwordInvalid)))
                } else {
                    // Successfully validated the password!
                    let sessionTimeout = process.env.SESSION_TIMEOUT || 300 

                    let token = jwt.createToken(userid, username, sessionTimeout)
                    if (!token) {
                        callback(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not generate a JSON Web Token for authentication.`))))
                        return
                    }

                    let result = {
                        'userid':userid,
                        'token':token
                    }

                    callback(null, result)
                }
            }
        })
    }

    static refreshAuthentication(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        // This method will only be called if called with a valid JWT, so if the
        // process reaches here, we know we can create a new JWT just fine.

        let userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        
        // We'll look up the username from the userid
        let username = null

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {
                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                TCAccountService.usernameForUserId({userid: userid, dbConnection: connection}, function(err, theUsername) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        username = theUsername
                        callback(null, connection)
                    }
                })
            }
        ],
        function(err, connection) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not refresh authentication for userid (${userid}).`))))
            } else {
                // Successfully validated the password!
                let sessionTimeout = process.env.SESSION_TIMEOUT || 300 

                let token = jwt.createToken(userid, username, sessionTimeout)
                if (!token) {
                    completion(new Error(JSON.stringify(errors.customError(errors.serverError, `TCAccountService.refreshAuthentication() Could not generate a JSON Web Token for authentication..`))))
                    return
                }

                let result = {
                    'userid':userid,
                    'token':token
                }

                completion(null, result)
            }
        })
    }

    static impersonate(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        // This method can only succeed if the caller is a root-level administrator.

        let userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        let customerUsername = params.customerUsername && typeof params.customerUsername == 'string' ? params.customerUsername.trim() : null
        let reason = params.reason && typeof params.reason == 'string' ? params.reason.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!customerUsername || customerUsername.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the customerUsername parameter.`))))
            return
        }
        if (!reason || reason.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the reason parameter.`))))
            return
        }
        
        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {
                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                TCAccountService.getUserAdminLevel({userid: userid, dbConnection: connection}, function(err, adminLevel) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (adminLevel >= constants.AdminLevel.Root) {
                            callback(null, connection)
                        } else {
                            callback(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                        }
                    }
                })
            },
            function(connection, callback) {
                TCAccountService.accountForUsername({username: customerUsername, dbConnection: connection}, function(err, anAccount) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, anAccount)
                    }
                })
            },
            function(connection, impersonatedAccount, callback) {
                // Log an account action
                let params = {
                    userid: impersonatedAccount.userid,
                    ownerUserid: userid,
                    changeType: constants.UserAccountAction.Impersonation,
                    description: reason,
                    dbConnection: connection
                }
                TCAccountService.logUserAccountAction(params, function(err, result) {
                    if (err) {
                        // Ignore an error because it's not fatal.
                        logger.debug(`Error logging an impersonation by admin user (${userid}) for ${impersonatedUserID} - ${reason}: ${err}`)
                    }
                    callback(null, connection, impersonatedAccount)
                })
            },
            function(connection, impersonatedAccount, callback) {
                // Create a temporary JWT that the admin user can use
                const sessionTimeout = 3600 // 1 hour for an admin session
                const token = jwt.createToken(impersonatedAccount.userid, impersonatedAccount.username, sessionTimeout)
                if (!token) {
                    callback(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not generate a JSON Web Token for impersonation.`))), connection)
                } else {
                    callback(null, connection, {
                        userid: impersonatedAccount.userid,
                        token: token
                    })
                }
            }
        ],
        function(err, connection, result) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not create JWT for impersonation.`))))
            } else {
                completion(null, result)
            }
        })
    }

    static getUserAdminLevel(params, completion) {
        if (!params) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                let sql = `SELECT admin_level AS adminLevel FROM tdo_user_accounts WHERE userid = ?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error looking for admin_level for userid (${userid}): ${err.message}`))), connection)
                    } else {
                        if (results && results.rows && results.rows.length > 0) {
                            let adminLevel = results.rows[0].adminLevel
                            callback(null, connection, adminLevel)
                        } else {
                            callback(new Error(JSON.stringify(errors.customError(errors.accountNotFound, `No matching userid (${userid}) found in system.`))), connection)
                        }
                    }
                })
            }
        ],
        function(err, connection, adminLevel) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not determine admin level for userid (${userid}).`))))
            } else {
                completion(null, adminLevel)
            }
        })
    }

    static verifyEmail(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let verificationid = userInfo.verificationid && typeof userInfo.verificationid == 'string' ? userInfo.verificationid.trim() : null

        if (!verificationid || verificationid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the verificationid parameter.`))))
            return
        }
                
        async.waterfall([
            function(callback) {
                db.getPool(function(err, pool) {
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                        } else {
                            callback(null, transaction)
                        }
                    })
                })
            },
            function(transaction, callback) {
                let anEmailVerification = new TCEmailVerification({
                    verificationid: verificationid
                })
                anEmailVerification.read(transaction, function(err, emailVerification) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), transaction)
                    } else {
                        if (!emailVerification) {
                            callback(new Error(JSON.stringify(errors.emailVerificationNotFound)), transaction)
                        } else {
                            callback(null, transaction, emailVerification)
                        }
                    }
                })
            },
            function(transaction, emailVerification, callback) {
                // Set the user's email as verified in the user record
                let anAccount = new TCAccount({
                    userid:emailVerification.userid,
                    email_verified:1
                })
                anAccount.update(transaction, function(err, account) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), transaction)
                    } else {
                        callback(null, transaction, emailVerification)
                    }
                })
            },
            function(transaction, emailVerification, callback) {
                emailVerification.delete(transaction, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Failed to delete the email verification record (userid: ${emailVerification.userid}): ${err.message}`))), transaction)
                    } else {
                        callback(null, transaction, emailVerification)
                    }
                })
            },
            function(transaction, emailVerification, callback) {
                const vipParams = {
                    userid: emailVerification.userid,
                    username: emailVerification.username,
                    dbConnection: transaction
                }
                TCAccountService.extendVIPAccountIfNeeded(vipParams, function(err, newExpirationTimestamp) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, newExpirationTimestamp)
                    }
                })
            }
        ],
        function(err, transaction, newExpirationTimestamp) {
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not verify a user's email address (verificationid: ${verificationid}).`))))
                if (transaction) {
                    transaction.rollback(function(transErr, result) {
                        // Ignore the result. The transaction will be closed or we'll get an error
                        // because the transaction has already been rolled back.
                        db.cleanup()
                    })
                } else {
                    db.cleanup()
                }
            } else {
                if (transaction) {
                    transaction.commit(function(err, result) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not verify the email address (verificationid: ${verificationid}). Database commit failed: ${err.message}`))))
                        } else {
                            if (newExpirationTimestamp) {
                                // A VIP account was renewed, so include that information
                                callback(null, {success: true, new_expiration_date: newExpirationTimestamp})
                            } else {
                                callback(null, {success: true})
                            }
                        }
                        db.cleanup()
                    })
                } else {
                    db.cleanup()
                }
            }
        })
    }

    static extendVIPAccountIfNeeded(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        let username = params.username && typeof params.username == 'string' ? params.username.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCAccountService.extendVIPAccountIfNeeded() Missing the userid parameter.`))))
            return
        }
        if (!username || username.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCAccountService.extendVIPAccountIfNeeded() Missing the username parameter.`))))
            return
        }

        // Check to see if the user's email address is in the VIP domains
        // and if it's not, just silently fail (return with no error).
        let userDomain = username.substring(username.lastIndexOf("@") + 1)
        if (constants.VIPDomains.indexOf(userDomain) < 0) {
            completion(null, false)
            return
        }
                
        async.waterfall([
            function(callback) {
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                const getSubscriptionParams = {
                    userid: userid,
                    dbConnection: connection                    
                }
                TCSubscriptionService.subscriptionForUserId(getSubscriptionParams, function(err, subscriptionInfo) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, connection, subscriptionInfo)
                    }
                })
            },
            function(connection, subscriptionInfo, callback) {
                const currentExpiration = subscriptionInfo.subscription.expiration_date
                const newExpiration = moment.unix(currentExpiration)
                newExpiration.add(constants.VIPExtensionIntervalInMonths, "month")
                const newExpirationTimestamp = newExpiration.unix()

                const updateParams = {
                    subscriptionID: subscriptionInfo.subscription.subscriptionid,
                    newExpirationDate: newExpirationTimestamp,
                    subscriptionType: subscriptionInfo.subscription.type,
                    subscriptionLevel: subscriptionInfo.subscription.level,
                    dbConnection: connection
                }
                TCSubscriptionService.updateSubscriptionWithNewExpirationDate(updateParams, function(err, result) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, newExpirationTimestamp)
                    }
                })
                

            }
        ],
        function(err, connection, newExpirationTimestamp) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not extend the premium account for ${username} (${userid}).`))))
            } else {
                completion(null, newExpirationTimestamp)
            }
        })
    }

    static resendVerificationEmail(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        
        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {
                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                // Delete any existing verification email
                TCEmailVerificationService.deleteExistingEmailVerification({userid:userid, dbConnection:connection}, function(err, result) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                TCAccountService.usernameForUserId({userid:userid, dbConnection:connection}, function(err, username) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, username)
                    }
                })
            },
            function(connection, username, callback) {
                // Create a new verify email record
                TCEmailVerificationService.createEmailVerification({userid:userid, username:username, dbConnection:connection}, function(err, emailVerification) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        // Create the URL for the verification email
                        let verifyEmailURL = `${process.env.WEBAPP_BASE_URL}/verify-email/${emailVerification.verificationid}`
                        callback(null, connection, username, verifyEmailURL)
                    }
                })
            },
            function(connection, username, verifyEmailURL, callback) {
                TCAccountService.displayNameForUserId({userid:userid, dbConnection:connection}, function(err, displayName) {
                    if (err) {
                        // We'll consider this an error, because it _should_ be possible to get a display name
                        callback(err, connection)
                    } else {
                        callback(null, connection, username, verifyEmailURL, displayName)
                    }
                })
            },
            function(connection, username, verifyEmailURL, displayName, callback) {
                TCMailerService.sendEmailVerificationEmail({email:username, displayName:displayName, verifyEmailURL:verifyEmailURL}, function(err, mailResult) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (!mailResult) {
                            callback(new Error(JSON.stringify(errors.emailServiceError)), connection)
                        } else {
                            callback(null, connection)
                        }
                    }
                })
                
            }
        ],
        function(err, connection) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not send an email verification for user (userid: ${userid}).`))))
            } else {
                // If the code makes it here, we can assume success!
                callback(null, {'success':true})
            }
        })
    }

    // userInfo parameters:
    //  username: string
    static requestResetPassword(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let username = userInfo.username && typeof userInfo.username == 'string' ? userInfo.username.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!username || username.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the username parameter.`))))
            return
        }

        // We store all usernames (email addresses) in the database in lowercase
        // characters, so make sure that the username query with is also lower case
        username = username.toLowerCase()

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                TCAccountService.userIdForUsername({username:username, dbConnection:connection}, function(err, userid) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userid)
                    }
                })
            },
            function(connection, userid, callback) {
                TCAccountService.deleteExistingPasswordResetForUserID({userid:userid, dbConnection:connection}, function(err, result) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userid)
                    }
                })
            },
            function(connection, userid, callback) {
                let aReset = new TCPasswordReset({userid:userid, username:username})
                aReset.add(connection, function(err, passwordReset) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.databaseError)), connection)
                    } else {
                        callback(null, connection, userid, passwordReset)
                    }
                })
            },
            function(connection, userid, passwordReset, callback) {
                let params = {
                    userid:userid,
                    dbConnection:connection
                }
                TCAccountService.displayNameForUserId(params, function(err, displayName) {
                    if (err) {
                        // We'll consider this an error, because it _should_ be possible to get a display name
                        callback(err, connection)
                    } else {
                        callback(null, connection, userid, passwordReset, displayName)
                    }
                })
            },
            function(connection, userid, passwordReset, displayName, callback) {
                let passwordResetURL = `${process.env.WEBAPP_BASE_URL}/password-reset/${passwordReset.resetid}`
                TCMailerService.sendPasswordResetEmail({email:username, displayName:displayName, resetURL:passwordResetURL}, function(err, result) {
                    if (err) {
                        // This is considered an error if we weren't able to send an email
                        callback(err, connection)
                    } else {
                        callback(null, connection, userid)
                    }
                })
            },
            function(connection, userid, callback) {
                let params = {
                    userid: userid,
                    ownerUserid: userid,
                    changeType: constants.UserAccountAction.MailPasswordReset,
                    description: 'Reset password link sent.',
                    dbConnection: connection
                }
                TCAccountService.logUserAccountAction(params, function(err, result) {
                    if (err) {
                        // If the previous function succeeded and we actually sent
                        // a password reset, we're gonna still continue on, but go
                        // ahead and log an error into the console.
                        logger.debug(`Error logging a password reset to the user's action log: ${err}`)
                    }
                    callback(null, connection)
                })
            }
        ],
        function(err, connection) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not reset the password (username: ${username}).`))))
            } else {
                // If the code makes it here, we can assume success!
                callback(null, {'success':true})
            }
        })
    }

    // userInfo parameters:
    //  resetid: string
    //  password: string (new password)
    static resetPassword(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let resetid = userInfo.resetid && typeof userInfo.resetid == 'string' ? userInfo.resetid.trim() : null
        let password = userInfo.password && typeof userInfo.password == 'string' ? userInfo.password.trim() : null

        if (!resetid || resetid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the resetid parameter.`))))
            return
        }

        if (!password || password.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the password parameter.`))))
            return
        }

        // Enforce the password rules
        if (password.length > constants.maxPasswordLength) {
            callback(new Error(JSON.stringify(errors.passwordLengthExceeded)))
            return
        }

        if (password.length < constants.minPasswordLength) {
            callback(new Error(JSON.stringify(errors.passwordTooShort)))
            return
        }
                
        async.waterfall([
            function(callback) {
                db.getPool(function(err, pool) {
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                        } else {
                            callback(null, transaction)
                        }
                    })
                })
            },
            function(transaction, callback) {
                // Get the password reset record
                let aPasswordReset = new TCPasswordReset({resetid:resetid})
                aPasswordReset.read(transaction, function(err, passwordReset) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading a password reset record (resetid: ${resetid}): ${err.message}`))), transaction)
                    } else {
                        if (!passwordReset) {
                            callback(new Error(JSON.stringify(errors.passwordResetNotFound)), transaction)
                        } else {
                            callback(null, transaction, passwordReset)
                        }
                    }
                })
            },
            function(transaction, passwordReset, callback) {
                // Check to see if the passwordReset record is still valid
                let now = Math.floor(Date.now() / 1000)
                if (passwordReset.timestamp < (now - constants.passwordResetTimeoutInSeconds)) {
                    callback(new Error(JSON.stringify(errors.passwordResetExpired)), transaction)
                } else {
                    callback(null, transaction, passwordReset)
                }
            },
            function(transaction, passwordReset, callback) {
                let anAccount = new TCAccount({
                    userid: passwordReset.userid,
                    password: password
                })
                anAccount.update(transaction, function(err, account) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error resetting a user's password (userid: ${passwordReset.userid}): ${err.message}`))), transaction)
                    } else {
                        // User's password reset correctly
                        callback(null, transaction, passwordReset, account.userid)
                    }
                })
            },
            function(transaction, passwordReset, userid, callback) {
                // Delete the password reset record
                passwordReset.delete(transaction, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error deleting a password reset record (recordid: ${recordid}): ${err.message}`))), transaction)
                    } else {
                        callback(null, transaction, userid)
                    }
                })
            },
            function(transaction, userid, callback) {
                // Log the password reset on the user's account
                let params = {
                    userid: userid,
                    ownerUserid: userid,
                    changeType: constants.UserAccountAction.PasswordReset,
                    description: 'User reset their password with reset link.',
                    dbConnection: transaction
                }
                TCAccountService.logUserAccountAction(params, function(err, result) {
                    if (err) {
                        // If the previous functions succeeded and we actually performed
                        // a password reset, we're gonna still continue on, but go
                        // ahead and log an error into the console.
                        logger.debug(`Error logging a password reset to the user's action log: ${err}`)
                    }
                    callback(null, transaction)
                })
            }
        ],
        function(err, transaction) {
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not reset the password.`))))
                if (transaction) {
                    transaction.rollback(function(transErr, result) {
                        // Ignore the result. The transaction will be closed or we'll get an error
                        // because the transaction has already been rolled back.
                        db.cleanup()
                    })
                } else {
                    db.cleanup()
                }
            } else {
                if (transaction) {
                    transaction.commit(function(err, result) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not reset the password. Database commit failed: ${err.message}`))))
                        } else {
                            callback(null, {success: true})
                        }
                        db.cleanup()
                    })
                } else {
                    db.cleanup()
                }
            }
        })
    }
    

    static deleteExistingPasswordResetForUserID(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        
        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                let sql = `DELETE FROM tdo_password_reset WHERE userid = ?`
                connection.query(sql, [userid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(error.databaseError)), connection)
                    } else {
                        callback(null, connection, true)
                    }
                })
            }
        ],
        function(err, connection, result) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not delete existing password reset information for the userid (${userid}).`))))
            } else {
                callback(null, result)
            }
        })
    }

    static updatePassword(params, callback) { 
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const currentPassword = params.current && typeof params.current == 'string' ? params.current.trim() : null
        const newPassword = params.new_password && typeof params.new_password == 'string' ? params.new_password.trim() : null
        const reenteredPassword = params.reentered_password && typeof params.reentered_password == 'string' ? params.reentered_password.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        if (!currentPassword || currentPassword.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the current password.`))))
            return
        }

        if (!newPassword || newPassword.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the new password.`))))
            return
        }

        if (!reenteredPassword || reenteredPassword.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the reenteredPassword password.`))))
            return
        }

        if (reenteredPassword != newPassword) {
            callback(new Error(JSON.stringify(errors.customError(errors.passwordsNotSameError, `The new_password and reentered_password parameters do not match.`))))
            return
        }

        // Enforce the password rules
        if (newPassword.length > constants.maxPasswordLength) {
            callback(new Error(JSON.stringify(errors.passwordLengthExceeded)))
            return
        }

        if (newPassword.length < constants.minPasswordLength) {
            callback(new Error(JSON.stringify(errors.passwordTooShort)))
            return
        }


        async.waterfall([
            function (callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                const account = new TCAccount()
                account.userid = userid
                account.read(connection, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    }
                    else {
                        callback(null, connection, account)
                    }
                })
            },
            function(connection, account, callback) {
                const matchParams = { 
                    username : account.username, 
                    password : currentPassword, 
                    dbConnection: connection 
                }
                TCAccountService.matchPassword(matchParams, (err, result) => {
                    if (err) {
                        callback(err, connection)
                    }
                    else {
                        if (!result) {
                            callback(new Error(JSON.stringify(errors.passwordInvalid)), connection)
                        }
                        else {
                            callback(null, connection, account)
                        }
                    }
                })
            },
            function(connection, account, callback) {
                const updateParams = {
                    userid : account.userid,
                    dbConnection : connection,
                    properties : {
                        first_name : account.first_name,
                        last_name : account.last_name,
                        password : newPassword
                    }
                }
                TCAccountService.updateAccount(updateParams, (err, updated) => {
                    if (err) {
                        callback(err, connection)
                    }
                    else {
                        callback(null, connection, updated)
                    }
                })
            }
        ],
        function(err, connection, account) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not update the password for the userid (${userid}).`))))
            } else {
                callback(null, { success : true })
            }
        })
    }

    static updateAccount(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null
        let password = userInfo.properties && userInfo.properties.password && typeof userInfo.properties.password == 'string' ? userInfo.properties.password.trim() : null
        let firstName = userInfo.properties && userInfo.properties.first_name && typeof userInfo.properties.first_name == 'string' ? userInfo.properties.first_name.trim() : null
        let lastName = userInfo.properties && userInfo.properties.last_name && typeof userInfo.properties.last_name == 'string' ? userInfo.properties.last_name.trim() : null
        let username = userInfo.properties && userInfo.properties.username && typeof userInfo.properties.username == 'string' ? userInfo.properties.username.trim() : null
        let locale = userInfo.properties && userInfo.properties.locale && typeof userInfo.properties.locale == 'string' ? userInfo.properties.locale.trim() : null
        let localeBestMatch = userInfo.properties && userInfo.properties.locale_best_match && typeof userInfo.properties.locale_best_match == 'string' ? userInfo.properties.locale_best_match.trim() : null
        let emailOptIn = userInfo.properties && userInfo.properties.email_opt_in !== undefined ? userInfo.properties.email_opt_in : 0
        let selectedLocale = userInfo.properties && userInfo.properties.selected_locale && typeof userInfo.properties.selected_locale == 'string' ? userInfo.properties.selected_locale.trim() : null
        const imageGuid = userInfo.properties && userInfo.properties.image_guid !== undefined ? userInfo.properties.image_guid : undefined

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        if (!firstName || firstName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the first_name parameter.`))))
            return
        }
        if (firstName.length > constants.maxFirstNameLength) {
            callback(new Error(JSON.stringify(errors.firstNameLengthExceeded)))
            return
        }
        if (!lastName || lastName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the last_name parameter.`))))
            return
        }
        if (lastName.length > constants.maxLastNameLength) {
            callback(new Error(JSON.stringify(errors.lastNameLengthExceeded)))
            return
        }

        if (locale) {
            if (constants.supportedLocales.indexOf(locale) < 0) {
                callback(new Error(JSON.stringify(errors.localeInvalid)))
                return
            }
        }

        if (localeBestMatch) {
            // Ensure that the specified best match locale is one that we support
            if (constants.supportedLocales.indexOf(localeBestMatch) < 0) {
                callback(new Error(JSON.stringify(errors.bestMatchLocaleInvalid)))
                return
            }
        }

        if (selectedLocale) {
            if (constants.supportedLocales.indexOf(selectedLocale) < 0) {
                callback(new Error(JSON.stringify(errors.localeInvalid)))
                return
            }
        }

        if(username) {
            if (username.length > constants.maxUsernameLength) {
                callback(new Error(JSON.stringify(errors.usernameLengthExceeded)))
                return
            }

            // We store all usernames (email addresses) into the database in lowercase characters
            username = username.toLowerCase()

            // Check to see if this is a valid email address.
            if (!validator.isEmail(username)) {
                callback(new Error(JSON.stringify(errors.usernameInvalid)))
                return
            }

            // Allow + character ONLY if from the @appigo.com domain.
            if (username.indexOf('+') !== -1) {
                if (username.endsWith('@appigo.com') === false) {
                    callback(new Error(JSON.stringify(errors.usernameInvalidPlusCharacter)))
                    return
                }
            }
        }

        // Enforce the password rules
        if (password) {
            if (password.length > constants.maxPasswordLength) {
                callback(new Error(JSON.stringify(errors.passwordLengthExceeded)))
                return
            }

            if (password.length < constants.minPasswordLength) {
                callback(new Error(JSON.stringify(errors.passwordTooShort)))
                return
            }
        }

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                // Use a blank TCAccount object and fill in only the properties
                // that were passed to us from the API client. But, only allow
                // user-changeable properties.
                let anAccount = new TCAccount()

                const changesToLog = []
                anAccount.userid = userid
                if (password) {
                    anAccount.password = password
                    changesToLog.push({
                        userid: userid,
                        ownerID: userid,
                        changeType: constants.AccountChangeLogItemType.Password,
                        description: `User updated password.`,
                        dbConnection: connection
                    })
                }
                if (firstName) {
                    anAccount.first_name = firstName
                    changesToLog.push({
                        userid: userid,
                        ownerID: userid,
                        changeType: constants.AccountChangeLogItemType.Name,
                        description: `User updated first name.`,
                        dbConnection: connection
                    })
                }
                if (lastName) {
                    anAccount.last_name = lastName
                    if (!changesToLog.reduce((accum, curr) => accum || curr.changeType == constants.AccountChangeLogItemType.Name, false)) {
                        changesToLog.push({
                            userid: userid,
                            ownerID: userid,
                            changeType: constants.AccountChangeLogItemType.Name,
                            description: `User updated last name.`,
                            dbConnection: connection
                        })
                    }
                }
                if (locale) {anAccount.locale = locale}
                if (selectedLocale) {anAccount.selected_locale = selectedLocale}
                if (localeBestMatch) {anAccount.locale_best_match = localeBestMatch}
                if (emailOptIn !== null && emailOptIn !== undefined) {
                    anAccount.email_opt_out = !emailOptIn
                    changesToLog.push({
                        userid: userid,
                        ownerID: userid,
                        changeType: constants.AccountChangeLogItemType.EmailOptOut,
                        description: `User opted ${emailOptIn > 0 || emailOptIn ? 'in' : 'out'} of email.`,
                        dbConnection: connection
                    })
                }
                if (username) { 
                    anAccount.username = username 
                    anAccount.email_verified = 0
                    changesToLog.push({
                        userid: userid,
                        ownerID: userid,
                        changeType: constants.AccountChangeLogItemType.Username,
                        description: `User updated username.`,
                        dbConnection: connection
                    })
                }
                if (imageGuid !== undefined /* Image guids can be set back to null */) {
                    anAccount.image_guid = imageGuid
                }

                anAccount.update(connection, function(err, account) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (!account) {
                            callback(new Error(JSON.stringify(errors.accountNotFound)), connection)
                        } else {
                            callback(null, connection, account, changesToLog)
                        }
                    }
                })
            },
            function(connection, account, changes, callback) {
                // When running on the sqlite sync clients, the accounts are used for holding
                // information on synced users, and we don't keep a changelog for that.
                if (process.env.DB_TYPE == 'sqlite') {
                    callback(null, connection, account)
                    return
                }

                async.each(changes, (change, eachCallback) => {
                    TCChangeLogService.addUserAccountLogEntry(change, function(err, result) {
                        // If an error occurs, don't abandon the whole process, but log it to the console
                        if (err) {
                            console.error(`Error recording a change to changelog during TCAccountService.updateAccount() for user (${userid}): ${err}`)
                        }
                        eachCallback()
                    })
                },
                (err) => {
                    callback(null, connection, account)
                })
            },
            function(connection, account, callback) {
                // When running on the sqlite sync clients, the accounts are used for holding
                // information on synced users, and we don't need to send email verifications.
                if (process.env.DB_TYPE == 'sqlite') {
                    callback(null, connection, account)
                    return
                }

                if (username) {
                    const verificationParams = {
                        userid : userid,
                        dbConnection : connection
                    }
                    TCAccountService.resendVerificationEmail(verificationParams, (err, result) => {
                        if (err) {
                            callback(err, connection)
                        }
                        else {
                            callback(null, connection, account)
                        }
                    })
                }
                else {
                    callback(null, connection, account)
                }
            }
        ],
        function(err, connection, account) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not update the account for the userid (${userid}).`))))
            } else {
                callback(null, account)
            }
        })
    }

    static accountForUserId(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                let anAccount = new TCAccount()
                anAccount.userid = userid
                anAccount.read(connection, function(err, account) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (!account) {
                            callback(new Error(JSON.stringify(errors.accountNotFound)), connection)
                        } else {
                            if (!account) {
                                callback(new Error(JSON.stringify(errors.accountNotFound)), connection)
                            } else {
                                callback(null, connection, account)
                            }
                        }
                    }
                })
            }
        ],
        function(err, connection, account) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not find an account for the userid (${userid}).`))))
            } else {
                callback(null, account)
            }
        })
    }

    static accountForUsername(userInfo, completion) {
        if (!userInfo) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let username = userInfo.username && typeof userInfo.username == 'string' ? userInfo.username.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!username || username.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the username parameter.`))))
            return
        }

        // We store all usernames (email addresses) in the database in lowercase
        // characters, so make sure that the username query with is also lower case
        username = username.toLowerCase()

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                // Get the userid for the username and then let the TCObject do the work of reading the object
                let sql = `SELECT userid FROM tdo_user_accounts WHERE username = ?`
                connection.query(sql, [username], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (result && result.rows.length > 0) {
                            let userid = result.rows[0].userid
                            callback(null, connection, userid)
                        } else {
                            callback(new Error(JSON.stringify(errors.accountNotFound)), connection)
                        }
                    }
                })
            },
            function(connection, userid, callback) {
                TCAccountService.accountForUserId({userid:userid, dbConnection:connection}, function(err, account) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, account)
                    }
                })
            }
        ],
        function(err, connection, account) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not find an account for the username (${username}).`))))
            } else {
                completion(null, account)
            }
        })
    }

    static userIdForUsername(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let username = userInfo.username && typeof userInfo.username == 'string' ? userInfo.username.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!username || username.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the username parameter.`))))
            return
        }

        // We store all usernames (email addresses) in the database in lowercase
        // characters, so make sure that the username query with is also lower case
        username = username.toLowerCase()

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                let sql = `SELECT userid FROM tdo_user_accounts WHERE username = ?`
                connection.query(sql, [username], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error looking for a userid for username (${username}): ${err.message}`))), connection)
                    } else {
                        if (results && results.rows && results.rows.length > 0) {
                            let userid = results.rows[0].userid
                            callback(null, connection, userid)
                        } else {
                            callback(new Error(JSON.stringify(errors.customError(errors.accountNotFound, `No matching username (${username}) found in system.`))), connection)
                        }
                    }
                })
            }
        ],
        function(err, connection, userid) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not determine a userid for username (${username}).`))))
            } else {
                callback(null, userid)
            }
        })
    }

    static matchPassword(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let username = userInfo.username && typeof userInfo.username == 'string' ? userInfo.username.trim() : null
        let password = userInfo.password && typeof userInfo.password == 'string' ? userInfo.password.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!username || username.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the username parameter.`))))
            return
        }

        if (!password || password.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the password parameter.`))))
            return
        }
        
        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                let sql = `SELECT password FROM tdo_user_accounts WHERE username = ?`
                connection.query(sql, [username], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error finding username in database (${username}): ${err.message}`))), connection)
                    } else {
                        let isMatch = false
                        if (results && results.rows && results.rows.length > 0) {
                            let storedPassword = results.rows[0].password
                            let hashedPotentialMatch = md5(password)
                            isMatch = (storedPassword === hashedPotentialMatch)
                        }

                        callback(null, connection, isMatch)
                    }
                })
            }
        ],
        function(err, connection, isMatch) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not match password for username ${username}.`))))
            } else {
                callback(null, isMatch)
            }
        })
    }

    static isUserInMaintenance(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                let sql = `SELECT COUNT(*) AS maintenanceCount FROM tdo_user_maintenance WHERE userid = ?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error finding userid in database (${userid}): ${err.message}`))), connection)
                    } else {
                        let userInMaintenance = false
                        if (results && results.rows && results.rows.length > 0) {
                            userInMaintenance = results.rows[0].maintenanceCount > 0
                        }

                        callback(null, connection, userInMaintenance)
                    }
                })
            }
        ],
        function(err, connection, userInMaintenance) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not determine if userid ${userid} is in maintenance mode.`))))
            } else {
                callback(null, userInMaintenance)
            }
        })
    }

    static usernameForUserId(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                let sql = `SELECT username FROM tdo_user_accounts WHERE userid = ?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error looking for a username for userid (${userid}): ${err.message}`))), connection)
                    } else {
                        if (results && results.rows && results.rows.length > 0) {
                            let username = results.rows[0].username
                            callback(null, connection, username)
                        } else {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `No matching userid (${userid}) found in database.`))), connection)
                        }
                    }
                })
            }
        ],
        function(err, connection, username) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not determine a username for userid (${userid}).`))))
            } else {
                callback(null, username)
            }
        })
    }

    static displayNameForUserId(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                let sql = `SELECT username,first_name,last_name FROM tdo_user_accounts WHERE userid = ?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error looking for a username for userid (${userid}): ${err.message}`))), connection)
                    } else {
                        if (results && results.rows && results.rows.length > 0) {
                            let username = results.rows[0].username
                            let firstName = results.rows[0]['first_name']
                            let lastName = results.rows[0]['last_name']

                            let displayName = firstName
                            if (!displayName) {
                                displayName = username
                            } else if (lastName) {
                                displayName += ` ${lastName}`
                            }

                            callback(null, connection, displayName)
                        } else {
                            callback(new Error(JSON.stringify(errors.accountNotFound)))
                        }
                    }
                })
            }
        ],
        function(err, connection, displayName) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not determine a displayName for userid (${userid}).`))))
            } else {
                callback(null, displayName)
            }
        })
    }

    static logUserAccountAction(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null
        let ownerid = userInfo.ownerid && typeof userInfo.ownerid == 'string' ? userInfo.ownerid.trim() : null
        let changeType = userInfo.changeType !== undefined ? userInfo.changeType : 0
        let description = userInfo.description && typeof userInfo.description == 'string' ? userInfo.description.trim() : null
        let timestamp = Math.floor(Date.now() / 1000)

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!ownerid || ownerid.length == 0) {
            ownerid = userid
        }
        if (!changeType || changeType.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the changeType parameter.`))))
            return
        }
        if (!description || description.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the description parameter.`))))
            return
        }

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
        		let sql = `INSERT INTO tdo_user_account_log (userid, owner_userid, change_type, description, timestamp) VALUES (?, ?, ?, ?, ?)`
                let userAccountAction = [
                    userid,
                    ownerid,
                    changeType,
                    description,
                    timestamp
                ]
                connection.query(sql, userAccountAction, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        callback(null, connection, userAccountAction)
                    }
                })
            }
        ],
        function(err, connection, userAccountAction) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not log a user account action for userid (${userid}).`))))
            } else {
                callback(null, userAccountAction)
            }
        })
    }

    static deleteAccountData(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null
        let shouldCreateInbox = userInfo.createInbox !== undefined ? userInfo.createInbox : false

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        async.waterfall([
            function(callback) {
                // Pass on the dbConnection we were passed or get and
                // start a new database transaction.
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        begin(pool, {autoRollback: false}, function(err, transaction) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                            } else {
                                callback(null, transaction)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                // First, delete all lists the user belongs to
                TCListService.getAllListsAndMembersForUser({userid:userid, dbConnection:connection}, function(err, listsAndMembers) {
                    if (err) {
                        callback(err)
                    } else {
                        async.each(listsAndMembers, function(listInfo, eachCallback) {
                            let listid = listInfo.listid
                            let members = listInfo.members

                            let userIsOwner = members.find((memberInfo) => {
                                // Look for the userid and see if it's an owner of this list
                                return (memberInfo.userid == userid && memberInfo.membership_type == constants.ListMembershipType.Owner)
                            }) ? true : false

                            let otherOwners = members.find((memberInfo) => {
                                return (memberInfo.userid != userid && memberInfo.membership_type == constants.ListMembershipType.Owner)
                            }) ? true : false
                            
                            let otherMembers = members.length > 1 ? true : false

                            let canDeleteList = (userIsOwner && !otherMembers)
                            let canLeaveList = otherOwners

                            if (canDeleteList) {
                                TCListService.permanentlyDeleteList({listid:listid, dbConnection:connection}, function(err, result) {
                                    if (err) {
                                        eachCallback(err)
                                    } else {
                                        eachCallback()
                                    }
                                })
                            } else if (canLeaveList) {
                                TCListService.removeUserFromList({listid:listid, userid:userid, dbConnection:connection}, function(err, result) {
                                    if (err) {
                                        eachCallback(err)
                                    } else {
                                        eachCallback()
                                    }
                                })
                            } else {
                                // If the user is the only owner in a shared list,
                                // we cannot delete their data. They will have to
                                // remove all members of the shared list or make at
                                // least one other member an owner.
                                eachCallback(new Error(JSON.stringify(errors.listMembershipNotEmpty)))
                            }
                        },
                        function(err) {
                            if (err) {
                                callback(err, connection)
                            } else {
                                callback(null, connection)
                            }
                        })
                    }
                })
            },
            function(connection, callback) {
                // Delete records for a number of tables that are
                // indexed by the userid.
                let tableNames = [
                    "tdo_contexts",
                    "tdo_context_assignments",
                    "tdo_user_devices",
                    "tdo_smart_lists"
                ]
                async.each(tableNames, function(tableName, eachCallback) {
                    let sql = `DELETE FROM ${tableName} WHERE userid=?`
                    connection.query(sql, [userid], function(err, result) {
                        if (err) {
                            eachCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))))
                        } else {
                            eachCallback(null)
                        }
                    })
                },
                function(err) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                if (shouldCreateInbox) {
                    let listParams = {
                        userid: userid,
                        name: "Inbox", // TO-DO Figure out how to localize this!
                        dbTransaction: connection
                    }
                    TCListService.addList(listParams, function(err, inbox) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(err, `Error adding a new inbox list while wiping an account's data (${userid}).`))), connection)
                        } else {
                            // Creating the inbox was successful!
                            callback(null, connection, inbox.list)
                        }
                    })
                } else {
                    callback(null, connection, null)
                }
            },
            function(connection, inbox, callback) {
                // If inbox is not null, we need to save it as the user's new inbox
                if (inbox) {
                    let params = {
                        userid: userid,
                        inboxId: inbox.listid,
                        dbConnection: connection
                    }
                    TCUserSettingsService.updateUserInbox(params, function(err, result) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection)
                        }
                    })
                } else {
                    callback(null, connection)
                }
            },
            function(connection, callback) {
                let nowTimestamp = Math.floor(Date.now() / 1000)
                let anAccount = new TCAccount({
                    userid: userid,
                    last_reset_timestamp: nowTimestamp
                })
                anAccount.update(connection, function(err, account) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(error.databaseError, err.message))), connection)
                    } else {
                        callback(null, connection)
                    }
                })
            }
        ],
        function(err, connection) {
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not delete account data.`))))
                if (shouldCleanupDB) {
                    if (transaction) {
                        transaction.rollback(function(transErr, result) {
                            // Ignore the result. The transaction will be closed or we'll get an error
                            // because the transaction has already been rolled back.
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                }
            } else {
                if (shouldCleanupDB) {
                    if (transaction) {
                        transaction.commit(function(err, result) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not delete account data. Database commit failed: ${err.message}`))))
                            } else {
                                callback(null, true)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    callback(null, true)
                }
            }
        })
    }

    static deleteAccount(userInfo, completion) {
        if (!userInfo) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null
        let apikey = userInfo.apikey && typeof userInfo.apikey == 'string' ? userInfo.apikey.trim() : null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        if (process.env.DB_TYPE != 'sqlite') {
            if (!apikey || apikey.length == 0) {
                completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the apikey parameter.`))))
                return
            }
    
            // Validate the apikey
            let prehash = `${UNIT_TEST_API_KEY}-${userid}-${LUCKY_NUMBER}-${UNIT_TEST_API_KEY}`
            let hashedApiKey = md5(prehash)
            if (hashedApiKey != apikey) {
                // Invalid apikey specified - unauthorized for delete operation
                completion(new Error(JSON.stringify(errors.unauthorizedError)))
                return
            }
        }

        // TO-DO: Check to see if this user is an administrator of a team.
        // A team administrator cannot be deleted. A new administrator must
        // be set up first and then this user must be removed as a team
        // administrator before allowing the account to be deleted.

        // TO-DO: Check to see if this user is a member of a team. A participant
        // in a team must first be removed from a team before the account can
        // be deleted.

        // TO-DO: Prevent deleting any account that has root-level admin access

        async.waterfall([
            function(callback) {
                // Get the user account
                TCAccountService.accountForUserId({userid:userid}, function(err, account) {
                    if (err) {
                        callback(err, null)
                    } else {
                        callback(null, account)
                    }
                })
            },
            function(account, callback) {
                // TO-DO: Prevent deleting an account that has root-level admin access
                callback(null)
            },
            function(callback) {
                db.getPool(function(err, pool) {
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                        } else {
                            callback(null, transaction)
                        }
                    })
                })
            },
            function(transaction, callback) {
                TCAccountService.deleteAccountData({userid:userid, dbConnection:transaction}, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error deleting account data for userid ${userid}.`))), transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                // Delete records for a number of tables that are
                // indexed by the userid.
                let tableNames = [
                    "tdo_user_accounts",
                    "tdo_user_settings",
                    "tdo_user_sessions",
                    "tdo_user_migrations",
                    "tdo_email_verifications",
                    "tdo_password_reset",
                    "tdo_user_account_log",
                    "tdo_promo_codes",
                    "tdo_promo_code_history",
                    "tdo_subscriptions",
                    "tdo_user_payment_system",
                    "tdo_stripe_user_info",
                    "tdo_stripe_payment_history",
                    "tdo_iap_payment_history"
                ]
                async.each(tableNames, function(tableName, eachCallback) {
                    let sql = `DELETE FROM ${tableName} WHERE userid=?`
                    transaction.query(sql, [userid], function(err, result) {
                        if (err) {
                            eachCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))))
                        } else {
                            eachCallback(null)
                        }
                    })
                },
                function(err) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                let sql = `DELETE FROM tdo_autorenew_history WHERE subscriptionid IN (SELECT subscriptionid FROM tdo_subscriptions WHERE userid=?)`
                transaction.query(sql, [userid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            }
        ],
        function(err, transaction) {
            if (err) {
                if (transaction) {
                    let errObj = JSON.parse(err.message)
                    completion(new Error(JSON.stringify(errors.customError(errObj, `Could not delete an account (${userid}).`))))
                    transaction.rollback(function(transErr, result) {
                        // Ignore the result. The transaction will be closed or we'll get an error
                        // because the transaction has already been rolled back.
                        db.cleanup()
                    })
                } else {
                    let errObj = JSON.parse(err.message)
                    completion(new Error(JSON.stringify(errors.customError(errObj, `Could not delete an account (${userid}).`))))
                    db.cleanup()
                }
            } else {
                if (transaction) {
                    transaction.commit(function(err, result) {
                        if (err) {
                            completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not delete an account. Database commit failed: ${err.message}`))))
                        } else {
                            completion(null, JSON.stringify({success:true}))
                        }
                        db.cleanup()
                    })
                } else {
                    db.cleanup()
                }
            }
        })
    }

    static getProfileImageUploadURLs(params, completion) {
        // This function returns to the caller a pre-authorized URL that they
        // can use to upload a new user profile image.

        if (!params) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
            return
        }
        
        const fileGuid = uuidV4()

        async.times(2,
        function(idx, next) {
            const dstFolder = idx == 0 ? `user-images/profile-images` : `user-images/profile-images-large`
            const presignedParams = {
                Bucket: process.env.PROFILE_IMAGES_S3_BUCKET,
                Expires: 360, // Only valid for 5 minutes
                Fields: {
                    "Key": `${dstFolder}/${fileGuid}`,
                    "acl": `public-read`,
                    "Content-Type": "image/*",
                    "x-amz-storage-class": "REDUCED_REDUNDANCY"
                },
                Conditions: [
                    ["content-length-range", 1, constants.profileImageSizeLimitInBytes] // Instructs S3 to limit the size of the upload
                ]
            }
            s3.createPresignedPost(presignedParams, function(err, data) {
                if (err) {
                    completion(new Error(JSON.stringify(errors.customError(errors.serverError, err))))
                } else {
                    const urlType = idx == 0 ? `smallUrlInfo` : `largeUrlInfo`
                    next(null, {
                        type: urlType,
                        data: data
                    })
                }
            })
        },
        function(err, presignedURLs) {
            if (err) {
                completion(err)
            } else {
                const result = {}
                presignedURLs.forEach((urlInfo) => {
                    result[urlInfo.type] = urlInfo.data
                })
                completion(null, JSON.stringify(result))
            }
        })
    }

    static saveUploadedProfileImages(params, completion) {
        if (!params) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const bucket = params.bucket && typeof params.bucket == 'string' ? params.bucket.trim() : null
        const largeSrcKey = params.largeSrcKey && typeof params.largeSrcKey == 'string' ? params.largeSrcKey.trim() : null
        const smallSrcKey = params.smallSrcKey && typeof params.smallSrcKey == 'string' ? params.smallSrcKey.trim() : null

        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
            return
        }
        if(!bucket) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing bucket.'))))
            return
        }
        if(!largeSrcKey) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing largeSrcKey.'))))
            return
        }
        if(!smallSrcKey) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing smallSrcKey.'))))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        //  USERID.GUID.TIMESTAMP
        const components = largeSrcKey.split("/").reverse()
        if (!components || components.length < 1) {
            callback(new Error(JSON.stringify(errors.customError(errors.serverError, `Invalid image name uploaded.`))))
            return
        }
        const dstGuid = components[0]
        const timestamp = Math.floor(Date.now() / 1000)

        // Look specifically for each uploaded image by listing
        // the contents of their folders with a prefix specified.
        async.waterfall([
            function verify(callback) {
                const keys = [largeSrcKey, smallSrcKey]
                async.each(keys, function(key, eachCallback) {
                    const listParams = {
                        Bucket: bucket,
                        MaxKeys: 1,
                        Prefix: key
                    }
                    s3.listObjectsV2(listParams, function(err, listData) {
                        if (err) {
                            eachCallback(err)
                        } else {
                            // Make sure there's ONE result in the listing.
                            if (!listData || listData.KeyCount != 1) {
                                eachCallback(new Error(`Key not found on S3: ${key}`))
                            } else {
                                eachCallback(null)
                            }
                        }
                    })
                },
                function(err) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.imageNotFound, err.message))))
                    } else {
                        callback(null)
                    }
                })
            },
            function(callback) {
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                callback(null, connection)
                            }
                        })
                    })
                } else {
                    callback(null, dbConnection)
                }
            },
            function(connection, callback) {
                // Read the current user account and see if we need to delete a
                // previous user profile image.
                const accountParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCAccountService.accountForUserId(accountParams, function(err, accountInfo) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, accountInfo)
                    }
                })
            },
            function(connection, accountInfo, callback) {
                if (accountInfo.image_guid != undefined) {
                    async.times(2, function(idx, next) {
                        const oldFolder = idx == 0 ? `user-images/profile-images` : `user-images/profile-images-large`
                        const oldKey = `${oldFolder}/${accountInfo.image_guid}`

                        // Delete the old user profile image from S3
                        const deleteParams = {
                            Bucket: bucket,
                            Key: oldKey
                        }
                        s3.deleteObject(deleteParams, function(err, data) {
                            if (err) {
                                next(err)
                            } else {
                                next(null, true)
                            }
                        })
                    },
                    function(err, results) {
                        if (err) {
                            // This is a soft fail. We'll end up with orphaned old
                            // profile images, but it's not necessarily the worst
                            // thing in the world.
                            logger.debug(`Soft Fail: Unable to delete old user profile image from Amazon S3 Location: ${bucket}/${oldKey}`)
                        }

                        callback(null, connection)
                    })
                } else {
                    callback(null, connection)
                }
            },
            function(connection, callback) {
                // Update the user's account record with the new image guid
                const sql = `UPDATE tdo_user_accounts SET image_guid=?,image_update_timestamp=? WHERE userid=?`
                connection.query(sql, [dstGuid, timestamp, userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating the user's (${userid}) profile image GUID: ${err.message}`))), connection)
                    } else {
                        callback(null, connection)
                    }
                })
            }],
            function (err, connection) {
                if (shouldCleanupDB) {
                    if (connection) {
                        dbPool.releaseConnection(connection)
                    }
                    db.cleanup()
                }
                if (err) {
                    completion(new Error(JSON.stringify(errors.customError(err, `Unable to process uploaded user profile image for user: ${userid}`))))
                } else {
                    completion(null, JSON.stringify({success:true}))
                }
            }
        );
    }

    // for use only with the sqlite local db
    static getAllUsers(params, completion) {
        if (process.env.DB_TYPE != 'sqlite') {
            completion(errors.create(errors.syncError, `getAllUsers can only be called during sync by sync clients!`))
            return
        }

        if (!params) {
            completion(errors.create(errors.missingParameters))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        async.waterfall([
            function(next) {
                if (dbConnection) {
                    next(null, dbConnection)
                    return
                }

                db.getPool(function(err, pool) {
                    dbPool = pool
                    dbPool.getConnection(function(err, connection) {
                        if (err) {
                            next(errors.create(errors.databaseError, `Error getting a database connection: ${err.message}`), null)
                            return
                        } 
                        next(null, connection)
                    })
                })
            },
            function(connection, next) {
                const sql = `
                    SELECT userid, first_name, last_name, username, image_guid FROM tdo_user_accounts ORDER BY first_name
                `

                connection.query(sql, null, function(err, results) {
                    if (err) {
                        next(errors.create(errors.syncError, `Retrieving users failed: ${err.message}`))
                        return
                    }

                    const users = results.rows ? results.rows.map(row => new TCAccount(row)) : []
                    next(null, connection, users)
                })
            }
        ],
        function(err, connection, users) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(errors.create(errObj, `Could not get all users.`))
                return
            } 

            completion(null, users)
        }) 
    }

    static deleteSyncUser(params, completion) {
        if (process.env.DB_TYPE != 'sqlite') {
            completion(errors.create(errors.syncError, `deleteSyncUser can only be called during sync by sync clients!`))
            return
        }

        if (!params) {
            completion(errors.create(errors.missingParameters))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        async.waterfall([
            function(next) {
                if (dbConnection) {
                    next(null, dbConnection)
                    return
                }

                db.getPool(function(err, pool) {
                    dbPool = pool
                    dbPool.getConnection(function(err, connection) {
                        if (err) {
                            next(errors.create(errors.databaseError, `Error getting a database connection: ${err.message}`), null)
                            return
                        } 
                        next(null, connection)
                    })
                })
            },
            function(connection, next) {
                // Remove the account info
                const sql = `
                    DELETE FROM tdo_user_accounts WHERE userid = ?
                `
                connection.query(sql, [userid], function(err) {
                    if (err) {
                        next(errors.create(errors.databaseError, `Unable to remove synced user from user accounts table: ${err.message}`))
                        return
                    }

                    next(null, connection)
                })
            },
            function(connection, next) {
                // Remove the list membership info
                const sql = `
                    DELETE FROM tdo_list_memberships WHERE userid = ?
                `
                connection.query(sql, [userid], function(err) {
                    if (err) {
                        next(errors.create(errors.databaseError, `Unable to remove synced user from list memberships table: ${err.message}`))
                        return
                    }

                    next(null, connection)
                })
            }
        ],
        function(err, connection) {
            if (shouldCleanupDB) {
                if (connection) dbPool.releaseConnection(connection)
                db.cleanup()
            }

            if (err) {
                const errObj = JSON.parse(err.message)
                const message = `Failed to deleted synced user.`
                completion(errors.create(errObj, message))
                return
            }

            completion(null, true)
        })
    }
}

module.exports = TCAccountService
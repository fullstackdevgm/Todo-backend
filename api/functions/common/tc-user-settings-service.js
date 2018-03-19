'use strict'

var async = require('async')
var moment = require('moment-timezone')
var shortid = require('shortid')

const db = require('./tc-database')

const TCUserSettings = require('./tc-user-settings')

const constants = require('./constants')
const errors = require('./errors')

class TCUserSettingsService {
    static createUserSettings(settingsInfo, callback) {
        if (!settingsInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = settingsInfo.userid && typeof settingsInfo.userid == 'string' ? settingsInfo.userid.trim() : null
        let userInbox = settingsInfo.userInbox && typeof settingsInfo.userInbox == 'string' ? settingsInfo.userInbox.trim() : null
        let taskCreationEmail = settingsInfo.taskCreationEmail && typeof settingsInfo.taskCreationEmail == 'string' ? settingsInfo.taskCreationEmail.trim() : null
        let referralCode = settingsInfo.referralCode && typeof settingsInfo.referralCode == 'string' ? settingsInfo.referralCode.trim() : null

        const dbConnection = settingsInfo.dbConnection
        const shouldCleanupDB = !dbConnection

        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!userInbox || userInbox.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userInbox parameter.`))))
            return
        }
        if (!taskCreationEmail || taskCreationEmail.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the taskCreationEmail parameter.`))))
            return
        }
        if (!referralCode || referralCode.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the referralCode parameter.`))))
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
                let newUserSettings = new TCUserSettings()
                newUserSettings.configureWithProperties({
                    userid: userid,
                    user_inbox: userInbox,
                    task_creation_email: taskCreationEmail,
                    referral_code: referralCode
                })
                newUserSettings.add(connection, function(addErr, userSettings) {
                    if (addErr) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding a new user settings record into the database: ${addErr.message}`))), connection)
                    } else {
                        callback(null, connection, userSettings)
                    }
                })
            }
        ],
        function(err, connection, userSettings) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not create a new user settings record for userid: ${userid}`))))
            } else {
                callback(null, userSettings)
            }
        })
    }

    static userSettingsForUserId(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        var userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        let dbPool = null

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
                let aSettings = new TCUserSettings()
                aSettings.userid = userid
                aSettings.read(connection, function(err, userSettings) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (!userSettings) {
                            callback(new Error(JSON.stringify(errors.accountSettingsNotFound)), connection)
                        } else {
                            callback(null, connection, userSettings)
                        }
                    }
                })
            }
        ],
        function(err, connection, userSettings) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not find account settings for the userid (${userid}).`))))
            } else {
                callback(null, userSettings)
            }
        })
    }

    static updateUserSettings(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        // The API does not allow the user to change every property on the
        // user settings record and the code here only looks for properties
        // that can be modified by an API client.

        var userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null
        var timezone = userInfo.properties.timezone && typeof userInfo.properties.timezone == 'string'  ? userInfo.properties.timezone.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        // Validate all parameters for proper format

        if (timezone) {
            let aValidTimezone = moment.tz.zone(timezone)
            if (aValidTimezone == null) {
                // The specified timezone is invalid!
                callback(new Error(JSON.stringify(errors.timezoneInvalid)))
                return
            }
        }

        let dbPool = null

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
                // Use a blank TCUserSettings object and fill in only the
                // properties that are allowed and passed from the API client.
                let aSettings = new TCUserSettings()
                aSettings.userid = userid
                if (timezone) {aSettings.timezone = timezone}
                if (userInfo.properties.tag_filter_with_and != undefined) {aSettings.tag_filter_with_and = userInfo.properties.tag_filter_with_and}
                if (userInfo.properties.tag_sort_order != undefined) {aSettings.tag_sort_order = userInfo.properties.tag_sort_order}
                if (userInfo.properties.task_sort_order != undefined) {aSettings.task_sort_order = userInfo.properties.task_sort_order}
                if (userInfo.properties.start_date_filter != undefined) {aSettings.start_date_filter = userInfo.properties.start_date_filter}
                if (userInfo.properties.all_list_filter_string !== undefined && 
                    typeof userInfo.properties.all_list_filter_string == 'string' &&
                    userInfo.properties.all_list_filter_string.length >= 0) {
                        aSettings.all_list_filter_string = userInfo.properties.all_list_filter_string.trim()
                    }
                if (userInfo.properties.default_duedate != undefined) {aSettings.default_duedate = userInfo.properties.default_duedate}
                if (userInfo.properties.show_overdue_section != undefined) {aSettings.show_overdue_section = userInfo.properties.show_overdue_section}
                if (userInfo.properties.skip_task_date_parsing != undefined) {aSettings.skip_task_date_parsing = userInfo.properties.skip_task_date_parsing}
                if (userInfo.properties.skip_task_priority_parsing != undefined) {aSettings.skip_task_priority_parsing = userInfo.properties.skip_task_priority_parsing}
                if (userInfo.properties.skip_task_list_parsing != undefined) {aSettings.skip_task_list_parsing = userInfo.properties.skip_task_list_parsing}
                if (userInfo.properties.skip_task_tag_parsing != undefined) {aSettings.skip_task_tag_parsing = userInfo.properties.skip_task_tag_parsing}
                if (userInfo.properties.skip_task_checklist_parsing != undefined) {aSettings.skip_task_checklist_parsing = userInfo.properties.skip_task_checklist_parsing}
                if (userInfo.properties.skip_task_project_parsing != undefined) {aSettings.skip_task_project_parsing = userInfo.properties.skip_task_project_parsing}
                if (userInfo.properties.skip_task_startdate_parsing != undefined) {aSettings.skip_task_startdate_parsing = userInfo.properties.skip_task_startdate_parsing}
                if (userInfo.properties.new_feature_flags != undefined) {aSettings.new_feature_flags = userInfo.properties.new_feature_flags}
                if (userInfo.properties.email_notification_defaults != undefined) {aSettings.email_notification_defaults = userInfo.properties.email_notification_defaults}
                if (userInfo.properties.enable_google_analytics_tracking != undefined) {aSettings.enable_google_analytics_tracking = userInfo.properties.enable_google_analytics_tracking}

                aSettings.update(connection, function(err, userSettings) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (!userSettings) {
                            callback(new Error(JSON.stringify(errors.accountNotFound)), connection)
                        } else {
                            callback(null, connection, userSettings)
                        }
                    }
                })
            }
        ],
        function(err, connection, userSettings) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not update the user settings for the userid (${userid}).`))))
            } else {
                callback(null, userSettings)
            }
        })
    }

    // Offers a fast way to get the ID of the user's inbox list
    static getUserInboxID(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCUserSettingsService.getUserInboxID() missing the userid.`))))
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
                const sql = `SELECT user_inbox AS inboxid FROM tdo_user_settings WHERE userid=?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error querying the database to get a user's inbox id: ${err.message}`))), connection)
                    } else {
                        let inboxid = null
                        if (results && results.rows && results.rows.length > 0) {
                            inboxid = results.rows[0].inboxid
                        }
                        callback(null, connection, inboxid)
                    }
                })
            }
        ],
        function(err, connection, inboxid) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not read a user's (${userid}) inbox id.`))))
            } else {
                completion(null, inboxid)
            }
        })
    }

    static updateUserInbox(params, callback) {
        if (!params) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        // The API does not allow the user to change every property on the
        // user settings record and the code here only looks for properties
        // that can be modified by an API client.

        var userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        var inboxId = params.inboxId && typeof params.inboxId == 'string' ? params.inboxId.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        if (!inboxId || inboxId.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the inboxId parameter.`))))
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
                let aSettings = new TCUserSettings()
                aSettings.userid = userid
                aSettings.user_inbox = inboxId

                aSettings.update(connection, function(err, userSettings) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (!userSettings) {
                            callback(new Error(JSON.stringify(errors.accountNotFound)), connection)
                        } else {
                            callback(null, connection, userSettings)
                        }
                    }
                })
            }
        ],
        function(err, connection, userSettings) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not update the user settings for the userid (${userid}).`))))
            } else {
                callback(null, userSettings)
            }
        })
    }

    static newTaskCreationEmailForUsername(usernameInfo, callback) {
        if (!usernameInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let username = usernameInfo.username && typeof usernameInfo.username == 'string' ? usernameInfo.username.trim() : null

        const dbConnection = usernameInfo.dbConnection
        const shouldCleanupDB = !dbConnection

        if (!username || username.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the username parameter.`))))
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
                // Use only the left-hand side of an email address
                let emailPrefix = username.substring(0, username.lastIndexOf('@'))
                var taskCreationEmail = null
                var attemptsLeft = 20

                // Try to come up with a unique email address and keep looking to find
                // a unique value until we have one, with a limit of 20 times (which
                // should be PLENTY).
                async.doUntil(function(callback){
                    attemptsLeft--

                    var uniquePart = shortid.generate()
                    var possibleTaskCreationEmail = `${emailPrefix}-${uniquePart}`

                    TCUserSettingsService.isTaskCreationEmailUnique({email:possibleTaskCreationEmail, dbConnection:connection}, function(err, isUnique) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error determining a unique task creation email (${possibleTaskCreationEmail}): ${err.message}`))))
                        } else {
                            if (isUnique) {
                                taskCreationEmail = possibleTaskCreationEmail
                            }
                            callback(null, isUnique) // let doUntil know we're done
                        }
                    })
                },
                function() { // The test of when to know to be done with the doUntil
                    return taskCreationEmail != null || attemptsLeft > 0
                },
                function(err, result) {
                    if (err) {
                        callback(err, connection)
                        return
                    }

                    if (taskCreationEmail) {
                        callback(null, connection, taskCreationEmail)
                    } else {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Unable to determine a task creation email fro username: ${username}`))), connection)
                    }
                })
            }
        ],
        function(err, connection, taskCreationEmail) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not determine a new task creation email for username: ${username}`))))
            } else {
                callback(null, taskCreationEmail)
            }
        })
    }

    static isTaskCreationEmailUnique(emailInfo, callback) {
        if (!emailInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let email = emailInfo.email && typeof emailInfo.email == 'string' ? emailInfo.email.trim() : null

        const dbConnection = emailInfo.dbConnection
        const shouldCleanupDB = !dbConnection

        if (!email || email.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the email parameter.`))))
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
                let sql = `SELECT COUNT(*) AS emailCount FROM tdo_user_settings WHERE task_creation_email = ?`
                connection.query(sql, [email], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error querying the database to determine if a task creation email is unique: ${err.message}`))), connection)
                    } else {
                        if (result && result.rows && result.rows.length > 0) {
                            let emailCount = result.rows[0].emailCount
                            if (emailCount > 0) {
                                callback(null, connection, false) // not unique (a result found)
                                return
                            }
                        }
                        callback(null, connection, true) // unique (no result)
                    }
                })
            }
        ],
        function(err, connection, isEmailUnique) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not determine if a potential task creation email would be unique (${email}).`))))
            } else {
                callback(null, isEmailUnique)
            }
        })

    }

    static newReferralCode(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection

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
                // Generate a new referral code
                var referralCode = null
                var attemptsLeft = 20

                // Try to come up with a unique referral code and keep looking to find
                // a unique value until we have one, with a limit of 20 times (which
                // should be PLENTY).
                async.doUntil(function(callback){
                    attemptsLeft--

                    var possibleReferralCode = shortid.generate()

                    TCUserSettingsService.isReferralCodeUnique({referralCode:possibleReferralCode, dbConnection:connection}, function(err, isUnique) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error determining a unique referral code (${possibleReferralCode}): ${err.message}`))))
                        } else {
                            if (isUnique) {
                                referralCode = possibleReferralCode
                            }
                            callback(null, isUnique) // let doUntil know we're done
                        }
                    })
                },
                function() { // The test of when to know to be done with the doUntil
                    return referralCode != null || attemptsLeft > 0
                },
                function(err, result) {
                    if (err) {
                        callback(err, connection)
                        return
                    }

                    if (referralCode) {
                        callback(null, connection, referralCode)
                    } else {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Unable to determine a referral code.`))), connection)
                    }
                })
            }
        ],
        function(err, connection, referralCode) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                callback(err)
            } else {
                callback(null, referralCode)
            }
        })
    }

    static isReferralCodeUnique(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let referralCode = userInfo.referralCode && typeof userInfo.referralCode == 'string' ? userInfo.referralCode.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection

        if (!referralCode || referralCode.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the referralCode parameter.`))))
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
                let sql = `SELECT COUNT(*) AS referralCodeCount FROM tdo_user_settings WHERE referral_code = ?`
                connection.query(sql, [referralCode], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error querying the database to determine if a referral code is unique: ${err.message}`))), connection)
                    } else {
                        if (result && result.rows && result.rows.length > 0) {
                            let referralCodeCount = result.rows[0].referralCodeCount
                            if (referralCodeCount > 0) {
                                callback(null, connection, false) // not unique (a result found)
                                return
                            }
                        }
                        callback(null, connection, true) // unique (no result)
                    }
                })
            }
        ],
        function(err, connection, isCodeUnique) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not determine if a potential referral code would be unique (${referralCode}).`))))
            } else {
                callback(null, isCodeUnique)
            }
        })

    }

    static getUserTimeZone(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCUserSettingsService.getUserTimeZone() missing the userid.`))))
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
                const sql = `SELECT timezone AS timeZone FROM tdo_user_settings WHERE userid=?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error querying the database to get a user's time zone: ${err.message}`))), connection)
                    } else {
                        let timeZone = null
                        if (results && results.rows && results.rows.length > 0) {
                            timeZone = results.rows[0].timeZone
                        }
                        if (!timeZone) {
                            timeZone = constants.defaultTimeZone
                        }
                        callback(null, connection, timeZone)
                    }
                })
            }
        ],
        function(err, connection, timeZone) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not read a user's (${userid}) time zone.`))))
            } else {
                completion(null, timeZone)
            }
        })
    }

    static getSortOrder(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCUserSettingsService.getSortOrder() missing the userid.`))))
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
                const sql = `SELECT task_sort_order AS sortorder FROM tdo_user_settings WHERE userid=?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error querying the database to get a user's preferred sort order: ${err.message}`))), connection)
                    } else {
                        let sortOrder = null
                        if (results && results.rows && results.rows.length > 0) {
                            sortOrder = results.rows[0].sortorder
                        }
                        callback(null, connection, sortOrder)
                    }
                })
            }
        ],
        function(err, connection, sortOrder) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not read a user's (${userid}) default sort order setting.`))))
            } else {
                completion(null, sortOrder)
            }
        })
    }

    static getFilteredLists(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCUserSettingsService.getFilteredLists() missing the userid.`))))
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
                const sql = `SELECT all_list_filter_string AS filter FROM tdo_user_settings WHERE userid=?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error querying the database to get a user's filtered lists: ${err.message}`))), connection)
                    } else {
                        let filteredLists = null
                        if (results && results.rows && results.rows.length > 0) {
                            filteredLists = results.rows[0].filter
                        }
                        callback(null, connection, filteredLists)
                    }
                })
            }
        ],
        function(err, connection, filteredLists) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not read a user's (${userid}) filtered lists setting.`))))
            } else {
                completion(null, filteredLists)
            }
        })
    }
}

module.exports = TCUserSettingsService

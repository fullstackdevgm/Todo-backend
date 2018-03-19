'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')
const begin = require('any-db-transaction')
const db = require('./tc-database')

const TCChangeLogService = require('./tc-changelog-service')
const TCIAPService = require('./tc-iap-service')
const TCMailerService = require('./tc-mailer-service')
const TCPaymentSystemInfo = require('./tc-payment-system-info')
const TCSubscription = require('./tc-subscription')
const TCSystemSettings = require('./tc-system-settings')
const TCTeamService = require('./tc-team-service')

const constants = require('./constants')
const errors = require('./errors')

const moment = require('moment-timezone')
const stripe = require('stripe')(
    process.env.STRIPE_SECRET_KEY
)

class TCSubscriptionService {
    static createSubscription(subscriptionInfo, callback) {
        if (!subscriptionInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = subscriptionInfo.userid && typeof subscriptionInfo.userid == 'string' ? subscriptionInfo.userid.trim() : null
        let expirationDate = subscriptionInfo.expiration_date !== undefined ? subscriptionInfo.expiration_date : null
        let subscriptionType = subscriptionInfo.type !== undefined ? subscriptionInfo.type : constants.SubscriptionType.Unknown
        let subscriptionLevel = subscriptionInfo.level !== undefined ? subscriptionInfo.level : constants.SubscriptionLevel.Trial
        let teamId = subscriptionInfo.teamid && typeof subscriptionInfo.teamid == 'string' ? subscriptionInfo.teamid.trim() : null

        const dbConnection = subscriptionInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (subscriptionType) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the type parameter.`))))
            return
        }
        if (!subscriptionLevel) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the level parameter.`))))
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
                // Make sure the expiration date is set up properly
                if (expirationDate) {
                    callback(null, connection) // good to go
                } else {
                    const settingsParams = {
                        settingName: constants.SystemSettingName.SubscriptionTrialDurationInSeconds,
                        defaultValue: constants.SystemSettingDefault.TrialDurationInSeconds,
                        dbConnection: connection
                    }
                    TCSystemSettings.getSetting(settingsParams, function(err, settingValue) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error retrieving a system setting: ${constants.systemSettingSubscriptionTrialDurationInSeconds}`))), connection)
                        } else {
                            let now = Math.floor(Date.now() / 1000)
                            expirationDate = now + parseInt(settingValue)
                            callback(null, connection)
                        }
                    })
                }
            },
            function(connection, callback) {
                // If we make it to this point, we have a valid db connection
                let newSubscription = new TCSubscription()
                newSubscription.configureWithProperties({
                    userid: userid,
                    expiration_date: expirationDate,
                    type: subscriptionType,
                    level: subscriptionLevel,
                    teamid: teamId
                })
                newSubscription.add(connection, function(err, subscription) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding a new subscription record into the database: ${err.message}`))), connection)
                    } else {
                        callback(null, connection, subscription)
                    }
                })
            }
        ],
        function(err, connection, subscription) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not create a new subscription record for userid: ${userid}`))))
            } else {
                callback(null, subscription)
            }
        })
    }

    static subscriptionForUserId(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        var userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null
        const includeCreditCardInfo = userInfo.include_cc_info !== undefined ? userInfo.include_cc_info : false

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        // Set these variables as the waterfall progresses instead of passing them
        // along all the time.
        let theSubscription = null
        let thePaymentSystem = null
        let thePricingInfo = {}
        const theBillingInfo = {
            iap_autorenewing_account_type: "none"
        }
        const theTeamInfo = {}

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
                TCSubscriptionService.subscriptionIdForUserId({userid:userid, dbConnection:connection}, function(err, subscriptionid) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, subscriptionid)
                    }
                })
            },
            function(connection, subscriptionid, callback) {
                let aSubscription = new TCSubscription()
                aSubscription.subscriptionid = subscriptionid
                aSubscription.read(connection, function(err, subscription) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (!subscription) {
                            callback(new Error(JSON.stringify(errors.subscriptionNotFound)), connection)
                        } else {
                            theSubscription = subscription
                            callback(null, connection)
                        }
                    }
                })
            },
            function(connection, callback) {
                // Read the current payment system for the user
                const paymentParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCSubscriptionService.paymentSystemInfoForUserID(paymentParams, function(err, paymentSystemInfo) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (paymentSystemInfo) {
                            // Convert this into something that will make sense to
                            // API consumers (instead of just a number).
                            switch(paymentSystemInfo.payment_system_type) {
                                case constants.PaymentSystemType.Stripe: {
                                    thePaymentSystem = "stripe"
                                    break
                                }
                                case constants.PaymentSystemType.AppleIAP:
                                case constants.PaymentSystemType.AppleIAPAutorenew: {
                                    thePaymentSystem = "apple_iap"
                                    break
                                }
                                case constants.PaymentSystemType.GooglePlayAutorenew: {
                                    thePaymentSystem = "googleplay"
                                    break
                                }
                                case constants.PaymentSystemType.Team: {
                                    thePaymentSystem = "team"
                                    break
                                }
                                case constants.PaymentSystemType.Whitelisted: {
                                    thePaymentSystem = "vip"
                                    break
                                }
                                default: {
                                    thePaymentSystem = "unknown"
                                    break
                                }
                            }
                        } else {
                            thePaymentSystem = "unknown"
                        }

                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Return the pricing information with the request
                TCSubscriptionService.getPersonalSubscriptionPricingTable({dbConnection:connection}, function(err, pricingInfo) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        thePricingInfo = pricingInfo
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Determine what new expiration dates would be for a
                // monthly subscription if a purchase was made today
                const expParams = {
                    currentExpiration: theSubscription.expiration_date,
                    subscriptionType: constants.SubscriptionType.Month,
                    dbConnection: connection
                }
                TCSubscriptionService.getSubscriptionExpirationDateForType(expParams, function(err, newMonthExpirationDate) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        theBillingInfo["new_month_expiration_date"] = newMonthExpirationDate
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Determine what new expiration dates would be for a
                // yearly subscription if a purchase was made today
                const expParams = {
                    currentExpiration: theSubscription.expiration_date,
                    subscriptionType: constants.SubscriptionType.Year,
                    dbConnection: connection
                }
                TCSubscriptionService.getSubscriptionExpirationDateForType(expParams, function(err, newYearExpirationDate) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        theBillingInfo["new_year_expiration_date"] = newYearExpirationDate
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const iapParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCIAPService.isAppleIAPUser(iapParams, function(err, isAppleIAPUser) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (isAppleIAPUser) {
                            theBillingInfo["iap_autorenewing_account_type"] = "Apple IAP"
                            callback(null, connection)
                        } else {
                            TCIAPService.isGooglePlayUser(iapParams, function(err, isGooglePlayUser) {
                                if (err) {
                                    callback(err, connection)
                                } else {
                                    if (isGooglePlayUser) {
                                        theBillingInfo["iap_autorenewing_account_type"] = "GooglePlay"
                                    }
                                    callback(null, connection)
                                }
                            })
                        }
                    }
                })
            },
            function(connection, callback) {
                // Check to see if we need to set the "can_switch_to_monthly" option by
                // seeing if the current expiration date is farther out than one
                // month.  If so, change the newMonthExpirationDate to just be set
                // to the current expiration date.
                theBillingInfo["can_switch_to_monthly"] = false

                if (theSubscription.type == constants.SubscriptionType.Year) {
                    // Only offer this as an option (to switch to monthly billing)
                    // if the user is on a yearly billing cycle AND the user is
                    // currently a normal billed user.
                    const settingParams = {
                        settingName: constants.SystemSettingName.SubscriptionMonthlyDurationInSeconds,
                        defaultValue: constants.SystemSettingDefault.MonthlyDurationInSeconds,
                        dbConnection: connection
                    }
                    TCSystemSettings.getSetting(settingParams, function(err, monthlyDurationInSeconds) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            let oneMonthFromToday = Math.floor((Date.now() / 1000) + parseInt(monthlyDurationInSeconds))
                            if (theSubscription.expiration_date > oneMonthFromToday) {
                                theBillingInfo["can_switch_to_monthly"] = false
                                theBillingInfo["new_month_expiration_date"] = theSubscription.expiration_date
                            }
                            callback(null, connection)
                        }
                    })
                } else {
                    callback(null, connection)
                }
            },
            function(connection, callback) {
                if (!includeCreditCardInfo) {
                    callback(null, connection)
                } else {
                    const ccInfoParams = {
                        userid: userid,
                        dbConnection: connection
                    }
                    TCSubscriptionService.getCreditCardInfoForUser(ccInfoParams, function(err, ccInfo) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            if (ccInfo) {
                                theBillingInfo["cc_info"] = ccInfo
                            }
                            callback(null, connection)
                        }
                    })
                }
            },
            function(connection, callback) {
                if (theSubscription.teamid == undefined || theSubscription.teamid.length == 0) {
                    // Don't include any information about teaming
                    callback(null, connection)
                } else {
                    const teamParams = {
                        teamid: theSubscription.teamid,
                        dbConnection: connection
                    }
                    TCTeamService.teamNameForTeamID(teamParams, function(err, teamName) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            theTeamInfo["teamID"] = theSubscription.teamid
                            theTeamInfo["teamName"] = teamName
                            callback(null, connection)
                        }
                    })
                }
            },
            function(connection, callback) {
                // Determine if the user is a billing admin for any team
                const teamParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCTeamService.isBillingAdminForAnyTeam(teamParams, function(err, isBillingAdmin) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        theTeamInfo["is_team_billing_admin"] = isBillingAdmin ? true : false
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
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not find a subscription for the userid (${userid}).`))))
            } else {
                const result = {
                    subscription: theSubscription,
                    payment_system: thePaymentSystem,
                    pricing: thePricingInfo,
                    billing: theBillingInfo,
                    teaming: theTeamInfo
                }
                callback(null, result)
            }
        })
    }

    static subscriptionIdForUserId(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        var userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null

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
                let sql = `SELECT subscriptionid FROM tdo_subscriptions WHERE userid = ?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (results && results.rows && results.rows.length > 0) {
                            let subscriptionid = results.rows[0].subscriptionid
                            callback(null, connection, subscriptionid)
                        } else {
                            callback(new Error(JSON.stringify(errors.subscriptionNotFound)), connection)
                        }
                    }
                })
            }
        ],
        function(err, connection, subscriptionid) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not find a subscription for userid (${userid}).`))))
            } else {
                callback(null, subscriptionid)
            }
        })
    }

    // Parameters:
    //  subscription_id     required - the subscription the purchase will be applied to (the API will verify)
    //  subscription_type   required - either "month" or "year" to indicate the duration of the purchase
    //  total_charge        required - the full price in USD of the purchase (e.g.: 1.99)
    //  stripe_token        optional - either stripe_token OR last4 is required, but not both
    //  last4               optional - either last4 OR stripe_token is required, but not both
    static purchaseSubscription(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const subscriptionID = params.subscription_id && typeof params.subscription_id == 'string' ? params.subscription_id.trim() : null
        const subscriptionTypeString = params.subscription_type && typeof params.subscription_type == 'string' ? params.subscription_type.trim() : null
        const totalCharge = params.total_charge != undefined ? params.total_charge : null
        const stripeToken = params.stripe_token && typeof params.stripe_token == 'string' ? params.stripe_token.trim() : null
        const last4 = params.last4 && typeof params.last4 == 'string' ? params.last4.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        const now = Math.floor(Date.now() / 1000)

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!subscriptionID || subscriptionID.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the subscription_id parameter.`))))
            return
        }
        if (!subscriptionTypeString || subscriptionTypeString.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the subscription_type parameter.`))))
            return
        }
        if (!totalCharge) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the total_charge parameter.`))))
            return
        }

        if ((!stripeToken || stripeToken.length == 0) && (!last4 || last4.length == 0)) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `stripe_token or last4 required.`))))
            return
        }

        // Validate certain parameters
        if (subscriptionTypeString != constants.SubscriptionTypeString.Month && subscriptionTypeString != constants.SubscriptionTypeString.Year) {
            completion(new Error(JSON.stringify(errors.customError(errors.invalidParameters, `subscription_type invalid (specify "month" or "year").`))))
            return
        }

        let subscriptionType = constants.SubscriptionType.Month
        let chargeDescription = `Todo® Cloud Premium Account (1 month)`
        if (subscriptionTypeString == constants.SubscriptionTypeString.Year) {
            subscriptionType = constants.SubscriptionType.Year
            chargeDescription = `Todo® Cloud Premium Account (1 year)`
        }

        // These variables will be filled in as async.waterfall() progresses
        // and will be easier to track this way than passing them along to
        // every single function.
        let theSubscription = null
        let newExpirationDate = Math.floor(Date.now() / 1000)
        let authoritativePricing = null
        let authoritativePriceInCents = 0
        let theStripeCharge = null

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
                // If the user has any form of auto-renewing IAP set up already, fail
                // this method. It should not get called in the first place because the
                // client will check before allowing this to go through, but just in case...
                const iapParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCIAPService.userHasNonCanceledAutoRenewingIAP(iapParams, function(err, hasNonCanceledIAP) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (hasNonCanceledIAP) {
                            callback(new Error(JSON.stringify(paymentHasIAPError)), connection)
                        } else {
                            callback(null, connection)
                        }
                    }
                })
            },
            function(connection, callback) {
                // Validate the user's subscription
                //  1. It must be owned by them (subscriptionID must match)
                //  2. It must be eligible for upgrading (expiration must be
                //     within six months of right now)
                const subParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCSubscriptionService.subscriptionForUserId(subParams, function(err, subscriptionInfo) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        const subscription = subscriptionInfo.subscription
                        if (!subscription || subscription.subscriptionid != subscriptionID) {
                            // The user is not authorized to access the subscription they specified
                            callback(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                        } else {
                            // Check to see if the user is eligible to make a purchase
                            const sixMonthsTimestamp = moment().add(6, "month").unix()
                            if (subscription.expiration_date > sixMonthsTimestamp) {
                                callback(new Error(JSON.stringify(errors.subscriptionPurchaseNotEligible)), connection)
                            } else {
                                theSubscription = subscription
                                callback(null, connection)
                            }
                        }
                    }
                })
            },
            function(connection, callback) {
                // Determine the new expiration date
                const expParams = {
                    currentExpiration: theSubscription.expiration_date,
                    subscriptionType: subscriptionType,
                    dbConnection: connection
                }
                TCSubscriptionService.getSubscriptionExpirationDateForType(expParams, function(err, anExpirationDate) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        newExpirationDate = anExpirationDate
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // PRICE CHECK: Make sure that the price we showed to the user matches
                // the authoritative price (the price calculated by the server).
                TCSubscriptionService.getPersonalSubscriptionPricingTable({dbConnection:connection}, function(err, pricingInfo) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        authoritativePricing = pricingInfo

                        if (totalCharge != pricingInfo[subscriptionTypeString]) {
                            callback(new Error(JSON.stringify(errors.paymentInvalidCharge)), connection)
                        } else {
                            callback(null, connection)
                        }
                    }
                })
            },
            function(connection, callback) {
                // Stripe requires charges to be specified in number of USD cents
                authoritativePriceInCents = totalCharge * 100

                const stripeChargeParams = {
                    userid: userid,
                    stripeToken: stripeToken,
                    last4: last4,
                    unitPriceInCents: authoritativePriceInCents,
                    unitCombinedPriceInCents: authoritativePriceInCents,
                    subtotalInCents: authoritativePriceInCents,
                    authoritativePriceInCents: authoritativePriceInCents,
                    chargeDescription: chargeDescription,
                    subscriptionType: subscriptionType,
                    newExpirationDate: newExpirationDate,
                    numOfSubscriptions: 1,
                    dbConnection: connection
                }
                TCSubscriptionService.makeStripeCharge(stripeChargeParams, function(err, stripeCharge) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        theStripeCharge = stripeCharge
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const subUpdateParams = {
                    subscriptionID: subscriptionID,
                    newExpirationDate: newExpirationDate,
                    subscriptionType: subscriptionType,
                    subscriptionLevel: constants.SubscriptionLevel.Paid,
                    dbConnection: connection
                }
                TCSubscriptionService.updateSubscriptionWithNewExpirationDate(subUpdateParams, function(err, updateResults) {
                    if (err) {
                        // CRITIAL PROBLEM - A personal subscription was paid for but not
                        // updated, so send a mail to support so they can make sure to
                        // fix this.
                        const errParams = {
                            subscriptionid: subscriptionID,
                            subscriptionType: subscriptionTypeString,
                            newExpirationDate: newExpirationDate
                        }
                        TCMailerService.sendSubscriptionUpdateErrorNotification(errParams, function(mailErr, mailResult) {
                            if (mailErr) {
                                logger.debug(`Error sending an email notification to our admin about a subscription purchase failing to be applied: Subscription ID: ${subscriptionID}, Subscription Type: ${subscriptionTypeString}`)
                            }

                            callback(err, connection)
                        })
                    } else {
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Bug 7405 (from Bugzilla) - We need to clear out the user's auto-renew
                // history entry if there is one, in case they were having problems with
                // their old card. This way if their old card was failing to auto-renew,
                // they will still be able to auto-renew with a new card.
                const removeParams = {
                    subscriptionID: subscriptionID,
                    dbConnection:connection
                }
                TCSubscriptionService.removeSubscriptionFromAutorenewQueue(removeParams, function(err, removeResult) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (!removeResult) {
                            logger.debug(`Unable to remove a user's old info from the auto-renew queue (userid: ${userid}).`)
                        }
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Keep a record of the charge
                if (theStripeCharge.customer) {
                    callback(null, connection, theStripeCharge.customer)
                } else {
                    const getCustomerIDParams = {
                        userid: userid,
                        dbConnection: connection
                    }
                    TCSubscriptionService.getStripeCustomerID(getCustomerIDParams, function(err, customerID) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection, customerID)
                        }
                    })
                }
            },
            function(connection, stripeCustomerID, callback) {
                // Log the Stripe payment
                const cardType = theStripeCharge.source.brand ? theStripeCharge.source.brand : "N/A"
                const last4 = theStripeCharge.source.last4 ? theStripeCharge.source.last4 : "xxxx"
                const logParams = {
                    userid: userid,
                    numberOfUsers: 1,
                    stripeCustomerID: stripeCustomerID,
                    stripeChargeID: theStripeCharge.id,
                    cardType: cardType,
                    last4: last4,
                    subscriptionType: subscriptionType,
                    amount: theStripeCharge.amount,
                    timestamp: now,
                    chargeDescription: chargeDescription,
                    dbConnection: connection
                }
                TCSubscriptionService.logStripePayment(logParams, function(err, logResult) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, stripeCustomerID)
                    }
                })
            },
            function(connection, stripeCustomerID, callback) {
                const paymentInfoParams = {
                    userid: userid,
                    paymentSystemType: constants.PaymentSystemType.Stripe,
                    paymentSystemUserID: stripeCustomerID,
                    dbConnection: connection
                }
                TCSubscriptionService.addOrUpdateUserPaymentSystemInfo(paymentInfoParams, function(err, addResult) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection)
                    }
                })
            },
            // function(connection, callback) {
            //     // Record the purchase in the referral system so the original referrer
            //     // receives credit for this purchase (potentially).
            //     //
            //     // Note: In xPlat 10.x we are making the decision to drop support
            //     // for the referral system and we will no longer worry about it, nor
            //     // add any code for it.
            // }
        ],
        function(err, connection, subscriptionid) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not complete the purchase request for userid (${userid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static downgradeSubscription(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        // Variables filled in later and used throughout async.waterfall()
        let stripeCustomerID = null

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
                // Get the Stripe Customer ID
                const getCustomerIDParams = {
                    userid: userid,
                    dbConnection: transaction
                }
                TCSubscriptionService.getStripeCustomerID(getCustomerIDParams, function(err, customerID) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        stripeCustomerID = customerID
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                const deleteParams = {
                    userid: userid,
                    dbTransaction: transaction
                }
                TCSubscriptionService.deleteStripeCustomerInfoForUserID(deleteParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                // Delete the Stripe customer from the Stripe system
                stripe.customers.del(stripeCustomerID, function(err, confirmation) {
                    if (err) {
                        const errMsg = `Error deleting Stripe customer (${stripeCustomerID}) for user (${userid}): ${err.type}`
                        callback(new Error(JSON.stringify(errors.customError(errors.paymentProcessingError, errMsg))), transaction)
                    } else {
                        // If no error was returned by Stripe, assume the customer was deleted (since Stripe is not our system)
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                // Log the action into the change log
                const changeParams = {
                    userid: userid,
                    ownerID: userid,
                    changeType: constants.AccountChangeLogItemType.DowngradeToFreeAccount,
                    description: `User-initiated downgrade to free account.`,
                    dbConnection: transaction
                }
                TCChangeLogService.addUserAccountLogEntry(changeParams, function(err, result) {
                    // If an error occurs, don't abandon the whole process, but log it to the console
                    if (err) {
                        console.error(`Error recording a change to changelog during TCSubscriptionService.downgradeSubscription() for user (${userid}): ${err}`)
                    }
                    callback(null, transaction)
                })
            }
        ],
        function(err, transaction) {
            if (err) {
                if (transaction) {
                    let errObj = JSON.parse(err.message)
                    callback(new Error(JSON.stringify(errors.customError(errObj, `Could not downgrade subscription for user (${userid}).`))))
                    transaction.rollback(function(transErr, result) {
                        // Ignore the result. The transaction will be closed or we'll get an error
                        // because the transaction has already been rolled back.
                        db.cleanup()
                    })
                } else {
                    let errObj = JSON.parse(err.message)
                    callback(new Error(JSON.stringify(errors.customError(errObj, `Could not downgrade subscription for user (${userid}).`))))
                    db.cleanup()
                }
            } else {
                if (transaction) {
                    transaction.commit(function(err, result) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not downgrade subscription for user (${userid}). Database commit failed: ${err.message}`))))
                        } else {
                            completion(null, true) // Subscription downgraded successfully
                        }
                        db.cleanup()
                    })
                } else {
                    db.cleanup()
                }
            }
        })
    }

    static resendReceipt(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const timestamp = params.timestamp ? params.timestamp : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!timestamp) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the timestamp parameter.`))))
            return
        }

        // Load this here so we don't have problems with a circular reference at load time
        const TCAccountService = require('./tc-account-service')

        // Variables filled along the way in async.waterfall()
        let purchaseInfo = null
        let username = null
        let displayName = null

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
                // We're only interested in Stripe purchases since that's the only payment
                // system we can generate a receipt for (the other payment systems aren't ours).
                const paymentParams = {
                    userid: userid,
                    timestamp: timestamp,
                    dbConnection: connection
                }
                TCSubscriptionService.getStripePurchaseInfoForUserID(paymentParams, function(err, aPurchaseInfo) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (!aPurchaseInfo) {
                            callback(new Error(JSON.stringify(errors.paymentNotFound)), connection)
                        } else {
                            purchaseInfo = aPurchaseInfo
                            callback(null, connection)
                        }
                    }
                })
            },
            function(connection, callback) {
                TCAccountService.usernameForUserId({userid: userid, dbConnection:connection}, function(err, aUsername) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        username = aUsername
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                TCAccountService.displayNameForUserId({userid: userid, dbConnection: connection}, function(err, aDisplayName) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        displayName = aDisplayName
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const mailParams = {
                    email: username,
                    displayName: displayName,
                    purchaseDate: timestamp,
                    cardType: purchaseInfo.card_type ? purchaseInfo.card_type : "XXXX",
                    last4: purchaseInfo.last4 ? purchaseInfo.last4 : "xxxx",
                    subscriptionType: purchaseInfo.type,
                    purchaseAmount: purchaseInfo.amount,
                }
                TCMailerService.sendPremierAccountPurchaseReceipt(mailParams, function(err, result) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Log this action to the user account log
                const logParams = {
                    userid: userid,
                    ownerID: userid,
                    changeType: constants.AccountChangeLogItemType.PurchaseReceipt,
                    description: `User-initiated resend purchase receipt.`,
                    dbConnection: connection
                }
                TCChangeLogService.addUserAccountLogEntry(logParams, function(err, result) {
                    if (err) {
                        // Do not treat this as a fatal error, but log it to the console
                        console.error(`Error adding an entry to the user's account log that a receipt was sent for user (${userid}).`)
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
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not resend a purchase receipt for user (${userid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static getStripePurchaseInfoForUserID(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const timestamp = params.timestamp ? params.timestamp : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!timestamp) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the timestamp parameter.`))))
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
                const sql = `SELECT card_type,last4,type,amount FROM tdo_stripe_payment_history WHERE userid=? AND timestamp=?`
                connection.query(sql, [userid, timestamp], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (results && results.rows && results.rows.length > 0) {
                            const row = results.rows[0]
                            const amountInUSD = (row['amount'] / 100).toFixed(2)

                            const purchaseInfo = {
                                card_type: row['card_type'],
                                last4: row['last4'],
                                type: row['type'],
                                amount: amountInUSD
                            }
                            callback(null, connection, purchaseInfo)
                        } else {
                            // Don't treat missing information as an error
                            callback(null, connection, null)
                        }
                    }
                })
            }
        ],
        function(err, connection, purchaseInfo) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not retrieve purchase info for userid (${userid}).`))))
            } else {
                completion(null, purchaseInfo)
            }
        })
    }

    /**
     * userid (required)
     *      The user that will have the credit card associated with it.
     * authoritativePriceInCents (required)
     *      Amount in USD CENTS. For example, $19.99 should be
     *      specified as 1999.
     * chargeDescription (required)
     *      The description that will show up to the end user as the
     *      reason for the charge.
     * stripeToken (required)
     *      tripeToken or last4 must be specified, but NOT BOTH
     * last4 (required if stripeToken is empty)
     *      last4 or stripeToken must be specified, but NOT BOTH
     * teamid (required if a team purchase)
     *      The team, if specified, that the charge will be attributed to.
     * unitPriceInCents (required)
     *      How much does each subscription cost?
     * unitCombinedPriceInCents (required)
     *      unixPrice x numOfSubscriptions
     * subtotalInCents (required)
     *      Amount in USD CENTS. For example, $19.99 would be specified
     *      as 1999.
     * subscriptionType
     *      The type of subscription (use a constants.SubscriptionType value)
     * discountPercentage (required, can be 0)
     *      Individual purchases should pass in 0. Team purchases
     *      with actual discounts should be passed in as an integer.
     *      For example, 5 would represent a 5% discount.
     * discountInCents (required, can be 0)
     *      The actual dollar discount in USD CENTS. For example,
     *      a discount of $3.99 would be specified as 399.
     * teamCredits (optional)
     *      An array of team credits that turn into a discount for
     *      the team. The array elements contain:
     *          userid                  - donor of the credits
     *          numOfMonthsRemaining    - informational
     *          numOfMonthsHarvested    - the number of donation month credits being used in this transaction
     * teamCreditsPriceDiscountInCents (optional)
     *      The discount amount that the credits equal.
     * numOfSubscriptions (required to be > 0)
     *      If this is a team purchase, this will specify how many
     *      subscriptions are purchased.
     */
    static makeStripeCharge(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        // The user can be one of two types of Stripe customers:
        //		1. They've NEVER paid us before
        //		2. They've paid us in the past
        //			2a. Their previous card is still valid and they chose to continue paying with that (last4)
        //			2b. They provided new card information (stripeToken)
        //
        //			UNNATURAL PIG POSITION (IMPOSSIBLE CONDITION):
        //			2c. The user's previous card is expired.  We don't give the
        //				user this option when making a payment.  If the previous
        //				card has expired, we don't return that as a viable
        //				option.

        const TCAccountService = require('./tc-account-service')

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const authoritativePriceInCents = params.authoritativePriceInCents != undefined ? params.authoritativePriceInCents.toFixed() : 0
        const chargeDescription = params.chargeDescription && typeof params.chargeDescription == 'string' ? params.chargeDescription.trim() : null
        const subscriptionType = params.subscriptionType != undefined ? params.subscriptionType : null
        const newExpirationDate = params.newExpirationDate != undefined ? params.newExpirationDate : null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!authoritativePriceInCents) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the authoritativePriceInCents parameter.`))))
            return
        }
        if (!chargeDescription || chargeDescription.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the chargeDescription parameter.`))))
            return
        }
        if (!subscriptionType) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the subscriptionType parameter.`))))
            return
        }
        if (!newExpirationDate) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the newExpirationDate parameter.`))))
            return
        }

        const stripeToken = params.stripeToken && typeof params.stripeToken == 'string' ? params.stripeToken.trim() : null
        const last4 = params.last4 && typeof params.last4 == 'string' ? params.last4.trim() : null
        
        if ((!stripeToken || stripeToken.length == 0) && (!last4 || last4.length == 0)) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `One of 'stripeToken' OR 'last4' required.`))))
            return
        }

        const teamid = params.teamid && typeof params.teamid == 'string' ? params.teamid.trim() : null
        const unitPriceInCents = params.unitPriceInCents != undefined ? params.unitPriceInCents : 0
        const unitCombinedPriceInCents = params.unitCombinedPriceInCents != undefined ? params.unitCombinedPriceInCents : 0
        const subtotalInCents = params.subtotalInCents != undefined ? params.subtotalInCents : 0
        const discountPercentage = params.discountPercentage != undefined ? params.discountPercentage : 0
        const discountInCents = params.discountInCents != undefined ? params.discountInCents : 0
        const teamCredits = params.teamCredits != undefined ? params.teamCredits : 0
        const teamCreditsPriceDiscountInCents = params.teamCreditsPriceDiscountInCents != undefined ? params.teamCreditsPriceDiscountInCents : 0
        const numOfSubscriptions = params.numOfSubscriptions != undefined ? params.numOfSubscriptions : 0

        if (!unitPriceInCents) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the unitPriceInCents parameter.`))))
            return
        }
        if (!unitCombinedPriceInCents) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the unitCombinedPriceInCents parameter.`))))
            return
        }
        if (!subtotalInCents) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the subtotalInCents parameter.`))))
            return
        }
        if (!numOfSubscriptions) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the numOfSubscriptions parameter.`))))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        let emailAddress = null
        let userDisplayName = null
        let isNewCard = false

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
                // Get the user email address
                TCAccountService.usernameForUserId({userid:userid, dbConnection:connection}, function(err, username) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        emailAddress = username
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Get the user's display name
                TCAccountService.displayNameForUserId({userid:userid, dbConnection:connection}, function(err, displayName) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        userDisplayName = displayName
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const customerParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCSubscriptionService.getStripeCustomerID(customerParams, function(err, stripeCustomerID) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, stripeCustomerID)
                    }
                })
            },
            function(connection, stripeCustomerID, callback) {
                if (stripeCustomerID) {
                    // The user has previously paid us using Stripe, so we either need
                    // to attempt a new payment OR update their card information and
                    // THEN make a new payment.

                    // Attempt to get the existing customer from Stripe
                    stripe.customers.retrieve(stripeCustomerID, function(err, stripeCustomer) {
                        if (err) {
                            const errMsg = `Error retrieving Stripe customer (${stripeCustomerID}) for user (${userid}): ${err.type}`
                            callback(new Error(JSON.stringify(errors.customError(errors.paymentProcessingError, errMsg))), connection)
                        } else {
                            if (stripeToken) {
                                // The user has provided NEW card information (we have a new
                                // one-time stripeToken).  We need to update their card
                                // information on their Stripe Customer ID.
                                const updateParams = {
                                    source: stripeToken,
                                    email: emailAddress
                                }
                                stripe.customers.update(stripeCustomerID, updateParams, function(err, updatedStripeCustomer) {
                                    if (err) {
                                        const errMsg = `Error updating Stripe customer (${stripeCustomerID}) for user (${userid}): ${err.type}`
                                        callback(new Error(JSON.stringify(errors.customError(errors.paymentProcessingError, errMsg))), connection)
                                    } else {
                                        callback(null, connection, updatedStripeCustomer.id)
                                    }
                                })

                            } else {
                                callback(null, connection, stripeCustomer.id)
                            }
                        }
                    })
                } else {
                    // This is a new customer or an existing Stripe Customer that's provided new credit card info
                    isNewCard = true
                    const createCustomerParams = {
                        description: userid,
                        email: emailAddress,
                        card: stripeToken
                    }
                    stripe.customers.create(createCustomerParams, function(err, newStripeCustomer) {
                        if (err) {
                            const errMsg = `Error creating a new Stripe customer for user (${userid}): ${err.type}`
                            callback(new Error(JSON.stringify(errors.customError(errors.paymentProcessingError, errMsg))), connection)
                        } else {
                            // Store this new Stripe customer
                            const storeParams = {
                                userid: userid,
                                stripeCustomerID: newStripeCustomer.id,
                                dbConnection: connection
                            }
                            TCSubscriptionService.storeStripeCustomer(storeParams, function(err, result) {
                                if (err) {
                                    callback(err, connection)
                                } else {
                                    callback(null, connection, newStripeCustomer.id)
                                }
                            })
                        }
                    })
                }
            },
            function(connection, stripeCustomerID, callback) {
                // If we make it this far, we have a valid Stripe Customer and
                // we can use it to make a charge!
                let stripeCharge = null

                const chargeParams = {
                    amount: authoritativePriceInCents,
                    currency: "usd",
                    description: chargeDescription,
                    customer: stripeCustomerID,
                }
                stripe.charges.create(chargeParams, function(err, stripeCharge) {
                    if (err) {
                        const errMsg = `Error creating a Stripe charge for user (${userid}): ${err.type}`
                        callback(new Error(JSON.stringify(errors.customError(errors.paymentProcessingError, errMsg))), connection)
                    } else {
                        callback(null, connection, stripeCharge)
                    }
                })
            },
            function(connection, stripeCharge, callback) {

                const isTeamPurchase = teamid && teamid.length > 0

                const mailParams = {
                    email: emailAddress,
                    displayName: userDisplayName,
                    purchaseDate: stripeCharge.created,
                    cardType: stripeCharge.source.brand ? stripeCharge.source.brand : "N/A",
                    last4: stripeCharge.source.last4 ? stripeCharge.source.last4 : "xxxx",
                    subscriptionType: subscriptionType,
                    unitPrice: (unitPriceInCents / 100).toFixed(2),
                    unitCombinedPrice: (unitCombinedPriceInCents / 100).toFixed(2),
                    discountAmount: (discountInCents / 100).toFixed(2),
                    teamCreditsDiscountAmount: teamCreditsPriceDiscountInCents ? teamCreditsPriceDiscountInCents : 0,
                    subtotalAmount: (subtotalInCents / 100).toFixed(2),
                    purchaseAmount: (authoritativePriceInCents / 100).toFixed(2),
                    newExpirationDate: newExpirationDate
                }
                if (isTeamPurchase) {
                    TCMailerService.sendTeamPurchaseReceipt(mailParams, function(err, result) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection, stripeCharge)
                        }
                    })
                } else {
                    TCMailerService.sendPremierAccountPurchaseReceipt(mailParams, function(err, result) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection, stripeCharge)
                        }
                    })
                }
            }
        ],
        function(err, connection, stripeCharge) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not make a Stripe charge for user (${userid}).`))))
            } else {
                completion(null, stripeCharge)
            }
        })
    }

    static storeStripeCustomer(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const stripeCustomerID = params.stripeCustomerID != undefined ? params.stripeCustomerID : 0

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!stripeCustomerID || stripeCustomerID.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the stripeCustomerID parameter.`))))
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
                const sql = `INSERT INTO tdo_stripe_user_info (userid, stripe_userid) VALUES (?,?)`
                connection.query(sql, [userid, stripeCustomerID], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
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
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not update a subscription (${subscriptionID}) with a new expiration date (${newExpirationDate}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static updateSubscriptionWithNewExpirationDate(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const subscriptionID = params.subscriptionID && typeof params.subscriptionID == 'string' ? params.subscriptionID.trim() : null
        const newExpirationDate = params.newExpirationDate != undefined ? params.newExpirationDate : 0
        const subscriptionType = params.subscriptionType != undefined ? params.subscriptionType : constants.SubscriptionType.Unknown
        const subscriptionLevel = params.subscriptionLevel != undefined ? params.subscriptionLevel : constants.SubscriptionLevel.Unknown
        const teamid = params.teamid && typeof params.teamid == 'string' ? params.teamid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!subscriptionID || subscriptionID.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the subscriptionID parameter.`))))
            return
        }
        if (!newExpirationDate) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the newExpirationDate parameter.`))))
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
                const aSubscription = new TCSubscription()
                aSubscription.subscriptionid = subscriptionID
                aSubscription.expiration_date = newExpirationDate
                if (subscriptionType != constants.SubscriptionType.Unknown) { aSubscription.type = subscriptionType }
                if (subscriptionLevel != constants.SubscriptionLevel.Unknown) { aSubscription.level = subscriptionLevel }
                aSubscription.teamid = teamid
                aSubscription.update(connection, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
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
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not update a subscription (${subscriptionID}) with a new expiration date (${newExpirationDate}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static removeSubscriptionFromAutorenewQueue(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const subscriptionID = params.subscriptionID && typeof params.subscriptionID == 'string' ? params.subscriptionID.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!subscriptionID || subscriptionID.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the subscriptionID parameter.`))))
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
                const sql = `DELETE FROM tdo_autorenew_history WHERE subscriptionid=?`
                connection.query(sql, [subscriptionID], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        callback(null, connection)
                    }
                })
            }
        ],
        function(err, connection, subscriptionid) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not delete a subscription (${subscriptionID}) from the autorenewal queue.`))))
            } else {
                completion(null, true)
            }
        })
    }

    static logStripePayment(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const teamid = params.teamid && typeof params.teamid == 'string' ? params.teamid.trim() : null
        const numberOfUsers = params.numberOfUsers != undefined ? params.numberOfUsers : 1
        const stripeCustomerID = params.stripeCustomerID && typeof params.stripeCustomerID == 'string' ? params.stripeCustomerID.trim() : null
        const stripeChargeID = params.stripeChargeID && typeof params.stripeChargeID == 'string' ? params.stripeChargeID.trim() : null
        const cardType = params.cardType && typeof params.cardType == 'string' ? params.cardType.trim() : null
        const last4 = params.last4 && typeof params.last4 == 'string' ? params.last4.trim() : null
        const subscriptionType = params.subscriptionType != undefined ? params.subscriptionType : constants.SubscriptionType.Unknown
        const amount = params.amount != undefined ? params.amount : 0
        const timestamp = params.timestamp != undefined ? params.timestamp : Math.floor(Date.now() / 1000)
        const chargeDescription = params.chargeDescription && typeof params.chargeDescription == 'string' ? params.chargeDescription.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!stripeCustomerID) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the stripeCustomerID parameter.`))))
            return
        }
        if (!stripeChargeID) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the stripeChargeID parameter.`))))
            return
        }
        if (!cardType) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the cardType parameter.`))))
            return
        }
        if (!last4) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the last4 parameter.`))))
            return
        }
        if (!chargeDescription) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the chargeDescription parameter.`))))
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
                const sqlParams = [
                    userid,
                    stripeCustomerID, 
                    stripeChargeID,
                    cardType,
                    last4,
                    subscriptionType,
                    amount,
                    chargeDescription,
                    timestamp,
                    teamid,
                    numberOfUsers
                ]
                const sql = `INSERT INTO tdo_stripe_payment_history (userid, stripe_userid, stripe_chargeid, card_type, last4, type, amount, charge_description, timestamp, teamid, license_count) VALUES (?,?,?,?,?,?,?,?,?,?,?)`
                connection.query(sql, sqlParams, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        callback(null, connection)
                    }
                })
            }
        ],
        function(err, connection, subscriptionid) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not log a Stripe payment userid (${userid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static paymentSystemInfoForUserID(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

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
                const aPaymentSystemInfo = new TCPaymentSystemInfo()
                aPaymentSystemInfo.userid = userid
                aPaymentSystemInfo.read(connection, function(err, paymentSystemInfo) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (paymentSystemInfo) {
                            callback(null, connection, paymentSystemInfo)
                        } else {
                            callback(null, connection, false)
                        }
                    }
                })
            }
        ],
        function(err, connection, paymentSystemInfo) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not read payment system info for userid (${userid}).`))))
            } else {
                completion(null, paymentSystemInfo)
            }
        })
    }

    static addOrUpdateUserPaymentSystemInfo(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const paymentSystemType = params.paymentSystemType ? params.paymentSystemType : null
        const paymentSystemUserID = params.paymentSystemUserID && typeof params.paymentSystemUserID == 'string' ? params.paymentSystemUserID.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!paymentSystemType) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the paymentSystemType parameter.`))))
            return
        }
        if (!paymentSystemUserID || paymentSystemUserID.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the paymentSystemUserID parameter.`))))
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
                const aPaymentSystemInfo = new TCPaymentSystemInfo()
                aPaymentSystemInfo.userid = userid
                aPaymentSystemInfo.read(connection, function(err, paymentSystemInfo) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        const paymentParams = {
                            userid: userid,
                            paymentSystemType: paymentSystemType,
                            paymentSystemUserID: paymentSystemUserID
                        }
                        if (paymentSystemInfo) {
                            // Update the existing user payment system info
                            paymentSystemInfo.payment_system_type = paymentSystemType
                            paymentSystemInfo.payment_system_userid = paymentSystemUserID
                            paymentSystemInfo.update(connection, function(err, updatedInfo) {
                                if (err) {
                                    callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                                } else {
                                    callback(null, connection)
                                }
                            })
                        } else {
                            // Add a new user payment system info record
                            aPaymentSystemInfo.payment_system_type = paymentSystemType
                            aPaymentSystemInfo.payment_system_userid = paymentSystemUserID
                            aPaymentSystemInfo.add(connection, function(err, newInfo) {
                                if (err) {
                                    callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                                } else {
                                    callback(null, connection)
                                }
                            })
                        }
                    }
                })
            }
        ],
        function(err, connection, subscriptionid) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not update payment system info for userid (${userid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static getPaymentHistoryForUserID(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        // variables used later so we don't have to pass them down the waterfall
        let stripePurchases = []
        let appleIapPurchases = []
        let googlePlayPurchases = []

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
                // Get Stripe purchases
                const purchaseParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCSubscriptionService.getStripePurchaseHistoryForUserID(purchaseParams, function(err, purchaseHistory) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (purchaseHistory) {
                            stripePurchases = purchaseHistory
                        }
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Get Apple IAP purchases
                const purchaseParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCIAPService.getIAPPurchaseHistoryForUserID(purchaseParams, function(err, purchaseHistory) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (purchaseHistory) {
                            appleIapPurchases = purchaseHistory
                        }
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Get GooglePlay purchases
                const purchaseParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCIAPService.getGooglePlayPurchaseHistoryForUserID(purchaseParams, function(err, purchaseHistory) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        if (purchaseHistory) {
                            googlePlayPurchases = purchaseHistory
                        }
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Merge all the purchases together
                let allPayments = stripePurchases.concat(appleIapPurchases, googlePlayPurchases)

                // Sort the purchases by purchase date
                allPayments.sort((a, b) => {
                    return a.timestamp - b.timestamp
                })

                callback(null, connection, allPayments)
            }
        ],
        function(err, connection, paymentHistory) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not retrieve payment history for userid (${userid}).`))))
            } else {
                completion(null, paymentHistory)
            }
        })
    }

    static getStripePurchaseHistoryForUserID(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

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
                const sql = `SELECT timestamp,type,amount,stripe_chargeid FROM tdo_stripe_payment_history WHERE userid=? AND (teamid IS NULL OR teamid='') ORDER BY timestamp DESC`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (results && results.rows) {
                            const purchases = []
                            results.rows.forEach((row) => {
                                const timestamp = row['timestamp']
                                const subscriptionType = row['type'] == constants.SubscriptionType.Year ? "year" : "month"
                                const amount = (row['amount'] / 100).toFixed(2)
                                const description = `Payment of $${amount} USD`
                                purchases.push({
                                    service: `stripe`,
                                    timestamp: timestamp,
                                    subscription_type: subscriptionType,
                                    description: description
                                })
                            })

                            callback(null, connection, purchases)
                        } else {
                            // Don't treat missing information as an error
                            callback(null, connection, null)
                        }
                    }
                })
            }
        ],
        function(err, connection, purchaseHistory) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not retrieve purchase history for userid (${userid}).`))))
            } else {
                completion(null, purchaseHistory)
            }
        })
    }

    static getPersonalSubscriptionPricingTable(params, completion) {
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
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
                const settingsParams = {
                    settingName: constants.SystemSettingName.MonthlyPricePerUser,
                    defaultValue: constants.SystemSettingDefault.PremiumMonthlyPriceInUSD,
                    dbConnection: connection
                }
                TCSystemSettings.getSetting(settingsParams, function(err, monthlyPrice) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, parseFloat(monthlyPrice))
                    }
                })
            },
            function(connection, monthlyPrice, callback) {
                const settingsParams = {
                    settingName: constants.SystemSettingName.YearlyPricePerUser,
                    defaultValue: constants.SystemSettingDefault.PremiumYearlyPriceInUSD,
                    dbConnection: connection
                }
                TCSystemSettings.getSetting(settingsParams, function(err, yearlyPrice) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, monthlyPrice, parseFloat(yearlyPrice))
                    }
                })
            }
        ],
        function(err, connection, monthlyPrice, yearlyPrice) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not determine monthly and yearly price for a premium subscription.`))))
            } else {
                const pricingInfo = {
                    month: monthlyPrice,
                    year: yearlyPrice
                }
                completion(null, pricingInfo)
            }
        })
    }

    static getSubscriptionExpirationDateForType(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const currentExpiration = params.currentExpiration != undefined ? params.currentExpiration : null
        const subscriptionType = params.subscriptionType != undefined ? params.subscriptionType : null

        const dbConnection = params.dbConnection

        const now = Math.floor(Date.now() / 1000)

        if (!currentExpiration) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the currentExpiration parameter.`))))
            return
        }
        if (!subscriptionType) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the subscriptionType parameter.`))))
            return
        }

        const baseExpiration = currentExpiration < now ? now : currentExpiration

        if (subscriptionType == constants.SubscriptionType.Month) {
            const settingParams = {
                settingName: constants.SystemSettingName.SubscriptionMonthlyDurationInSeconds,
                defaultValue: constants.SystemSettingDefault.MonthlyDurationInSeconds,
                dbConnection: dbConnection
            }
            TCSystemSettings.getSetting(settingParams, function(err, monthPeriodInSeconds) {
                if (err) {
                    completion(err)
                } else {
                    const newExpiration = baseExpiration + parseInt(monthPeriodInSeconds)
                    completion(null, newExpiration)
                }
            })
        } else if (subscriptionType == constants.SubscriptionType.Year) {
            const settingParams = {
                settingName: constants.SystemSettingName.SubscriptionYearlyDurationInSeconds,
                defaultValue: constants.SystemSettingDefault.YearlyDurationInSeconds,
                dbConnection: dbConnection
            }
            TCSystemSettings.getSetting(settingParams, function(err, yearPeriodInSeconds) {
                if (err) {
                    completion(err)
                } else {
                    const newExpiration = baseExpiration + parseInt(yearPeriodInSeconds)
                    completion(null, newExpiration)
                }
            })
        } else {
            completion(new Error(JSON.stringify(errors.subscriptionTypeInvalid)))
        }
    }

    static getCreditCardInfoForUser(params, completion) {
        // Communicate with Stripe to get information about the user's stored credit card
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        var userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const dbConnection = params.dbConnection

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        async.waterfall([
            function(callback) {
                const customerIDParams = {
                    userid: userid,
                    dbConnection: dbConnection
                }
                TCSubscriptionService.getStripeCustomerID(customerIDParams, function(err, stripeCustomerID) {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, stripeCustomerID)
                    }
                })
            },
            function(stripeCustomerID, callback) {
                if (stripeCustomerID) {
                    stripe.customers.retrieve(stripeCustomerID, function(err, customer) {
                        if (err) {
                            const errMsg = `Error retrieving Stripe customer (${stripeCustomerID}) for user (${userid}): ${err.type}`
                            callback(new Error(JSON.stringify(errors.customError(errors.paymentProcessingError, errMsg))))
                        } else {
                            if (!customer.default_source || !customer.sources || !customer.sources.data || customer.sources.data.length <= 0) {
                                callback(null, null) // no credit card info
                            } else {
                                const defaultCardID = customer.default_source
                                const activeCard = customer.sources.data.find((cardInfo) => {
                                    return cardInfo.id == defaultCardID
                                })
                                if (!activeCard) {
                                    callback(null, null)
                                } else {
                                    const ccInfo = {
                                        type: activeCard.brand ? activeCard.brand : "****",
                                        last4: activeCard.last4 ? activeCard.last4 : "****",
                                        exp_month: activeCard.exp_month ? activeCard.exp_month : "**",
                                        exp_year: activeCard.exp_year ? activeCard.exp_year : "**",
                                    }
                                    // Also add the name on the card if it exists
                                    if (activeCard.name) {
                                        ccInfo["name"] = activeCard.name
                                    }
                                    callback(null, ccInfo)
                                }
                            }
                        }
                    })
                } else {
                    callback(null, null)
                }
            }
        ],
        function(err, creditCardInfo) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not retrieve credit card information for userid (${userid}).`))))
            } else {
                if (creditCardInfo) {
                    completion(null, creditCardInfo)
                } else {
                    // Don't treat missing information as an error, but just
                    // return null. It could be that the specified user has
                    // never purchased through Stripe (so we won't have any
                    // information about their credit card).
                    completion(null, null)
                }
            }
        })
    }

    static getStripeCustomerID(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        var userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

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
                let sql = `SELECT stripe_userid FROM tdo_stripe_user_info WHERE userid=?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (results && results.rows && results.rows.length > 0) {
                            let stripeUserID = results.rows[0].stripe_userid
                            callback(null, connection, stripeUserID)
                        } else {
                            // Don't treat missing Stripe information as an error
                            callback(null, connection, null)
                        }
                    }
                })
            }
        ],
        function(err, connection, stripeCustomerID) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Error retrieving a user's Stripe Customer ID (${userid}).`))))
            } else {
                completion(null, stripeCustomerID)
            }
        })
    }

    static deleteStripeCustomerInfoForUserID(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const dbTransaction = params.dbTransaction !== undefined ? params.dbTransaction : null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!dbTransaction) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the dbTransaction parameter.`))))
            return
        }

        async.waterfall([
            function(callback) {
                const sql = `DELETE FROM tdo_stripe_user_info WHERE userid=?`
                dbTransaction.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))))
                    } else {
                        callback(null)
                    }
                })
            },
            function(callback) {
                // Set the subscription type (monthly|yearly) to UNKNOWN so that if
                // the user becomes a team admin or anything else like that, they
                // won't get an erroneous update by the autorenew daemon. If the
                // autorenew daemon encounters an UNKNOWN subscription type, it will
                // not process an autorenewal for a standard (non-team) premium
                // account.
                const sql = `UPDATE tdo_subscriptions SET type=0 WHERE userid=?`
                dbTransaction.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))))
                    } else {
                        callback(null)
                    }
                })
            },
            function(callback) {
                const sql = `DELETE FROM tdo_user_payment_system WHERE userid=?`
                dbTransaction.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))))
                    } else {
                        callback(null)
                    }
                })
            }
        ],
        function(err) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Error deleting a user's Stripe info (${userid}).`))))
            } else {
                completion(null, true)
            }
        })
    }
}

module.exports = TCSubscriptionService
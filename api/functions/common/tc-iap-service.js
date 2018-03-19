'use strict'

const async = require('async')

const db = require('./tc-database')

const TCIAPAutorenewReceipt = require('./tc-iap-autorenew-receipt')
const TCGooglePlayAutorenewToken = require('./tc-googleplay-autorenew-token')

const constants = require('./constants')
const errors = require('./errors')

class TCIAPService {

    static userHasNonCanceledAutoRenewingIAP(params, completion) {
        TCIAPService.isAppleIAPUser(params, function(err, isAppleIAPUser) {
            if (err) {
                completion(err)
            } else if (isAppleIAPUser) {
                completion(null, true)
            } else {
                TCIAPService.isGooglePlayUser(params, function(err, isGooglePlayUser) {
                    if (err) {
                        completion(err)
                    } else {
                        completion(null, isGooglePlayUser)
                    }
                })
            }
        })
    }

    static isAppleIAPUser(params, completion) {
        TCIAPService.iapAutorenewReceiptForUser(params, function(err, iapReceipt) {
            if (err) {
                completion(err)
            } else {
                const isAppleIAPUser = iapReceipt && (iapReceipt.autorenewal_canceled == undefined || iapReceipt.autorenewal_canceled == 0)
                completion(null, isAppleIAPUser)
            }
        })
    }

    static isGooglePlayUser(params, completion) {
        TCIAPService.googlePlayTokenForUser(params, function(err, token) {
            if (err) {
                completion(err)
            } else {
                const isGooglePlayUser = token && (token.autorenewal_canceled == undefined || token.autorenewal_canceled == 0)
                completion(null, isGooglePlayUser)
            }
        })
    }

    static iapAutorenewReceiptForUser(params, completion) {
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
                let aReceipt = new TCIAPAutorenewReceipt()
                aReceipt.userid = userid
                aReceipt.read(connection, function(err, iapReceipt) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        callback(null, iapReceipt)
                    }
                })
            }
        ],
        function(err, connection, iapReceipt) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Error getting IAP Receipt for userid (${userid}).`))))
            } else {
                completion(null, iapReceipt)
            }
        })
    }

    static googlePlayTokenForUser(params, completion) {
        if (!params) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        var userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
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
                let aToken = new TCGooglePlayAutorenewToken()
                aToken.userid = userid
                aToken.read(connection, function(err, token) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        callback(null, token)
                    }
                })
            }
        ],
        function(err, connection, token) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Error getting GooglePlay autorenewal token for userid (${userid}).`))))
            } else {
                completion(null, token)
            }
        })
    }

    static getIAPPurchaseHistoryForUserID(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection

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
                const sql = `SELECT UNIX_TIMESTAMP(purchase_date) AS timestamp,product_id,bid FROM tdo_iap_payment_history WHERE userid=? ORDER BY timestamp DESC`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (results && results.rows) {
                            const purchases = []
                            results.rows.forEach((row) => {
                                const timestamp = row['timestamp']
                                const productID = row['product_id']
                                const bundleID = row['bid']

                                let subscriptionType = `month`
                                if (productID.indexOf(`year`) > 0) {
                                    subscriptionType = `year`
                                }

                                // Determine what iOS app this was purchased from
                                let productName = `Todo`
                                if (bundleID.indexOf(`todoipad`) > 0) {
                                    productName = `Todo for iPad`
                                } else if (bundleID.indexOf(`todolite`) > 0) {
                                    productName = `Todo Lite`
                                } else if (bundleID.indexOf(`todopro`) > 0) {
                                    productName = `Todo Cloud`
                                }

                                const description = `In-App Purchase from ${productName}`
                                purchases.push({
                                    service: `apple_iap`,
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
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not retrieve Apple In-App Purchase history for userid (${userid}).`))))
            } else {
                completion(null, purchaseHistory)
            }
        })
    }

    static getGooglePlayPurchaseHistoryForUserID(params, completion) {
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
                const sql = `SELECT product_id,purchase_timestamp FROM tdo_googleplay_payment_history WHERE userid=? ORDER BY purchase_timestamp DESC`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (results && results.rows) {
                            const purchases = []
                            results.rows.forEach((row) => {
                                const timestamp = row['purchase_timestamp']
                                const productID = row['product_id']

                                let subscriptionType = `month`
                                if (productID.indexOf(`year`) > 0) {
                                    subscriptionType = `year`
                                }

                                const description = `In-App Purchase from Todo Cloud for Android`
                                purchases.push({
                                    service: `googleplay`,
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
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not retrieve GooglePlay Purchase history for userid (${userid}).`))))
            } else {
                completion(null, purchaseHistory)
            }
        })
    }

}

module.exports = TCIAPService
'use strict'

const async = require('async')

const db = require('./tc-database')

const TCEmailVerification = require('./tc-email-verification')

const constants = require('./constants')
const errors = require('./errors')

class TCEmailVerificationService {
    static createEmailVerification(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = userInfo.userid && typeof userInfo.userid == 'string' ? userInfo.userid.trim() : null
        let username = userInfo.username && typeof userInfo.username == 'string' ? userInfo.username.trim() : null

        const dbConnection = userInfo.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!username) {
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
                // If we make it to this point, we have a valid db connection
                let newEmailVerification = new TCEmailVerification()
                newEmailVerification.configureWithProperties({
                    userid: userid,
                    username: username
                })
                newEmailVerification.add(connection, function(err, emailVerification) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        callback(null, connection, emailVerification)
                    }
                })
            }
        ],
        function(err, connection, emailVerification) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not create an email verification record for user: ${username}`))))
            } else {
                callback(null, emailVerification)
            }
        })
    }

    static deleteExistingEmailVerification(userInfo, callback) {
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
                let sql = `DELETE FROM tdo_email_verifications WHERE userid = ?`
                connection.query(sql, [userid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(error.databaseError)), connection)
                    } else {
                        callback(null, true)
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
                callback(new Error(JSON.stringify(errors.customError(errObj, `Could not delete existing email verification userid (${userid}).`))))
            } else {
                callback(null, result)
            }
        })
    }
}

module.exports = TCEmailVerificationService
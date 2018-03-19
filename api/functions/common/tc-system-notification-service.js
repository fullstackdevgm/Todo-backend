'use strict'

const async = require('async')

const db = require('./tc-database')
const begin = require('any-db-transaction')

const constants = require('./constants')
const errors = require('./errors')

const TCSystemNotification = require('./tc-system-notification')

class TCSystemNotificationService {
    static getLatestSystemNotification(params, completion) {
        
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

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
                const sql = `
                    SELECT *
                    FROM tdo_system_notifications
                    WHERE (deleted IS NULL OR deleted = 0)
                `

                connection.query(sql, [], (err, results) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        let systemNotification = {}
                        for (let row of results.rows) {
                            systemNotification = new TCSystemNotification(row)
                        }
                        callback(null, connection, systemNotification)
                    }
                })
            }
        ],
        function(err, connection, systemNotification) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get system message.`))))
            } else {
                completion(null, systemNotification)
            }
        })

    }
}

module.exports = TCSystemNotificationService
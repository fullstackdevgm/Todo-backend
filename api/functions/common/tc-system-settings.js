'use strict'

const async = require('async')

const db = require('./tc-database')

const constants = require('./constants')
const errors = require('./errors')

class TCSystemSettings {
    static getSetting(params, completion) {
        if (!params) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const settingName = params.settingName && typeof params.settingName == 'string' ? params.settingName.trim() : null
        const defaultValue = params.defaultValue != undefined ? params.defaultValue : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!settingName || settingName.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the settingName parameter.`))))
            return
        }
        if (!defaultValue) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the defaultValue parameter.`))))
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

                const sql = `SELECT setting_value FROM tdo_system_settings WHERE setting_id = ?`

                connection.query(sql, [settingName], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `${err.message}`))), connection)
                    } else {
                        var settingValue = defaultValue
                        if (result && result.rows && result.rows.length > 0) {
                            settingValue = result.rows[0].setting_value
                        }
                        callback(null, connection, settingValue)
                    }
                })
            }
        ],
        function(err, connection, settingValue) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not read a system setting value: (${settingName}).`))))
            } else {
                completion(null, settingValue)
            }
        })
    }
}

module.exports = TCSystemSettings
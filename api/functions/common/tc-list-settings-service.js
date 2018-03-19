'use strict'

const async = require('async')

const db = require('./tc-database')
const begin = require('any-db-transaction')

const constants = require('./constants')
const errors = require('./errors')

const TCListSettings = require('./tc-list-settings')

class TCListSettingsService {
    static createListSettings(params, completion) {
        const userId = params.userid != undefined ? params.userid : null
        const listId = params.listid != undefined ? params.listid : null

        if (userId == null || listId == null) {
            completion(new Error(errors.customError(errors.missingParameters, 'Missing parameters when creating list settings')))
        }

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
                const settings = new TCListSettings(params)

                settings.add(connection, function(err, settings) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding a new list settings record into the database: ${err.message}`))), connection)
                    } else {
                        callback(null, connection, settings)
                    }
                })
            }
        ],
        function(err, connection, settings) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not create a list settings object for listId ${listId}.`))))
            } else {
                completion(null, settings)
            }
        })
    }

    static getListSettings(params, completion) {
        const listid = params.listid != null ? params.listid : null
        const userid = params.userid != null ? params.userid : null

        if (!listid) {
            completion(new Error(errors.customError(errors.missingParameters, 'TCListSettingsService.getListSettings() missing parameter: listid')))
            return
        }
        if (!userid) {
            completion(new Error(errors.customError(errors.missingParameters, 'TCListSettingsService.getListSettings() missing parameter: userid')))
        }

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
                const aListSettings = new TCListSettings(params)
                aListSettings.read(connection, function(err, listSettings) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading list settings record: ${err.message}`))), connection)
                    } else {
                        callback(null, connection, listSettings)
                    }
                })
            }
        ],
        function(err, connection, settings) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not update list settings for listId ${listid}.`))))
            } else {
                completion(null, settings)
            }
        })
    }

    static updateListSettings(params, completion) {
        const listid = params.listid != null ? params.listid : null
        const userid = params.userid != null ? params.userid : null

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
                const settings = new TCListSettings(params)

                settings.update(connection, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating list settings record: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, result)
                    }
                })
            }
        ],
        function(err, connection, settings) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not update list settings for listId ${listid}.`))))
            } else {
                completion(null, settings)
            }
        })
    }

    // This is an optimized query that ONLY gets the sort order setting so that
    // task queries can remain fast (as opposed to reading ALL of the columns in
    // a list settings record).
    static sortTypeForList(params, completion) {
        const listid = params.listid != null ? params.listid : null
        const userid = params.userid != null ? params.userid : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCListSettingsService.sortTypeForList() missing the listid.`))))
            return
        }
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCListSettingsService.sortTypeForList() missing the userid.`))))
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
                const sql = `SELECT sort_type AS sortType FROM tdo_list_settings WHERE listid=? AND userid=?`
                connection.query(sql, [listid, userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error querying the database to get the sort type for a list: ${err.message}`))), connection)
                    } else {
                        if (results && results.rows && results.rows.length > 0) {
                            let sortType = results.rows[0].sortType
                            callback(null, connection, sortType)
                        } else {
                            callback(null, connection, constants.SortType.DatePriorityAlpha)
                        }
                    }
                })
            }
        ],
        function(err, connection, sortType) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not read a list's (${listid}) sort type.`))))
            } else {
                completion(null, sortType)
            }
        })
    }
}

module.exports = TCListSettingsService

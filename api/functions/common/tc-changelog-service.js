'use strict'

const async = require('async')
const uuidV4 = require('uuid/v4')

const db = require('./tc-database')
const begin = require('any-db-transaction')
const moment = require('moment-timezone')
require('datejs')

const constants = require('./constants')
const errors = require('./errors')

const TCUtils = require('./tc-utils')

const ITEM_NAME_MAX_LENGTH = 72

class TCChangeLogService {

    static addChangeLogEntry(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        // Required params
        const listid = params.listid
        const userid = params.userid
        const itemid = params.itemid
        const itemType = params.itemType
        const changeType = params.changeType
        const changeLocation = params.changeLocation

        // Optional params
        const targetid = params.targetid
        const targetType = params.targetType
        const changeData = params.changeData

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCChangeLogService.addChangeLogEntry() missing the listid.`))))
            return
        }
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCChangeLogService.addChangeLogEntry() missing the userid.`))))
            return
        }
        if (!itemid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCChangeLogService.addChangeLogEntry() missing the itemid.`))))
            return
        }
        if (!itemType) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCChangeLogService.addChangeLogEntry() missing the itemType.`))))
            return
        }
        if (!changeType) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCChangeLogService.addChangeLogEntry() missing the changeType.`))))
            return
        }
        if (!changeLocation) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCChangeLogService.addChangeLogEntry() missing the changeLocation.`))))
            return
        }

        let itemName = params.itemName
        if (!itemName) {
            itemName = TCChangeLogService.getDefaultNameForItemType(itemType)
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        const timestamp = Math.floor(Date.now() / 1000)
        const changeid = uuidV4()

        if (itemName.length > ITEM_NAME_MAX_LENGTH) {
            itemName = itemName.substr(0, ITEM_NAME_MAX_LENGTH)
        }

        let nameString = 'changeid, listid, userid, itemid, item_name, item_type, change_type, mod_date, change_location'
        const values = [changeid, listid, userid, itemid, itemName, itemType, changeType, timestamp, changeLocation]

        if (targetid) {
            nameString += ", targetid"
            values.push(targetid)
        }
        if (targetType) {
            nameString += ", target_type"
            values.push(targetType)
        }
        if (changeData) {
            nameString += ", change_data"
            values.push(changeData)
        }

        let sql = `INSERT INTO tdo_change_log (${nameString}) VALUES (`
        for (var index = 0; index < values.length; index++) {
            if (index > 0) {
                sql += `, `
            }
            sql += `?`
        }
        sql += ")"

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
            function (connection, callback) {
                connection.query(sql, values, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection)
                    }
                })
            }
        ], 
        function(err, connection, tasks) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not add a change log entry.`))))
            } else {
                completion(null, true)
            }
        })
    }

    static addUserAccountLogEntry(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        // Required params
        const userid = params.userid
        const ownerID = params.ownerID
        const changeType = params.changeType
        const description = params.description

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCChangeLogService.addUserAccountLogEntry() missing the userid.`))))
            return
        }
        if (!ownerID) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCChangeLogService.addUserAccountLogEntry() missing the ownerID.`))))
            return
        }
        if (!changeType) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCChangeLogService.addUserAccountLogEntry() missing the changeType.`))))
            return
        }
        if (!description) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCChangeLogService.addUserAccountLogEntry() missing the description.`))))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        const timestamp = Math.floor(Date.now() / 1000)

        let nameString = 'userid, owner_userid, change_type, description, timestamp'
        const values = [userid, ownerID, changeType, description, timestamp]


        let sql = `INSERT INTO tdo_user_account_log (${nameString}) VALUES (`
        for (var index = 0; index < values.length; index++) {
            if (index > 0) {
                sql += `, `
            }
            sql += `?`
        }
        sql += ")"

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
            function (connection, callback) {
                connection.query(sql, values, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection)
                    }
                })
            }
        ], 
        function(err, connection, tasks) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not add a user change log entry.`))))
            } else {
                completion(null, true)
            }
        })
    }

    static getDefaultNameForItemType(itemType) {
        let name = 'Unnamed Item'
        switch (itemType) {
            case constants.ChangeLogItemType.List:
                name = 'New List'
                break;
            case constants.ChangeLogItemType.User:
                name = 'New User'
                break;
            case constants.ChangeLogItemType.Event:
                name = 'New Event'
                break;
            case constants.ChangeLogItemType.Comment:
                name = 'New Comment'
                break;
            case constants.ChangeLogItemType.Invitation:
                name = 'New Invitation'
                break;
            case constants.ChangeLogItemType.Task:
                name = 'New Task'
                break;
        }

        return name
    }

}

module.exports = TCChangeLogService

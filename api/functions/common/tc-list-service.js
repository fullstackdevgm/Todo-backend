'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')

const db = require('./tc-database')
const begin = require('any-db-transaction')
const moment = require('moment-timezone')

const constants = require('./constants')
const errors = require('./errors')

const TCChangeLogService = require('./tc-changelog-service')
const TCList = require('./tc-list')
const TCListMembership = require('./tc-list-membership')
const TCListMembershipService = require('./tc-list-membership-service')
const TCListSettings = require('./tc-list-settings')
const TCListSettingsService = require('./tc-list-settings-service')

const TCTaskService = require('./tc-task-service')

const TCUserSettingsService = require('./tc-user-settings-service')
const TCUserSettings = require('./tc-user-settings')

class TCListService {
    static listsForUserId(params, completion) {
        if (!params || !params.userid) {
            completion(new Error(JSON.stringify(errors.cusomtError(errors.missingParameters, 'Missing userid.'))))
            return
        }

        const userId          = params.userid
        const includeDeleted  = params.includeDeleted  ? params.includeDeleted  == 'true' || params.includeDeleted === true  : false
        const includeFiltered = params.includeFiltered ? params.includeFiltered == 'true' || params.includeFiltered === true : false

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
                if (!includeFiltered) {
                    TCUserSettingsService.userSettingsForUserId({ userid: userId, dbConnection: connection }, function(err, userSettings) {
                        if (err) { 
                            callback(new Error(JSON.stringify(errors.userSettingsNotFound)), connection)
                        }
                        else { 
                            callback(null, connection, userSettings) 
                        }
                    })
                }
                else {
                    callback(null, connection, null)
                }
            },
            function(connection, userSettings, callback) {
                const filteredListIds = []
                if (userSettings && userSettings.all_list_filter_string) {
                    filteredListIds.concat(userSettings.all_list_filter_string.split(','))
                }

                let sql = `
                    SELECT lists.*, settings.*
                    FROM tdo_lists lists
                        INNER JOIN tdo_list_memberships memberships ON memberships.listid = lists.listid
                        INNER JOIN tdo_list_settings settings 
                            ON settings.listid = lists.listid AND settings.userid = memberships.userid
                    WHERE
                        memberships.userid = ?
                `

                for (const listId in filteredListIds) {
                    sql += `AND lists.listid != ?\n`
                }

                if (!includeDeleted) {
                    sql += `AND (lists.deleted IS NULL OR lists.deleted = 0)\n`
                }

                connection.query(sql, [userId].concat(filteredListIds), function(err, results, fields) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        const lists = []
                        if (results.rows) {
                            for (const row of results.rows) {
                                lists.push({
                                    list: new TCList(row),
                                    settings : new TCListSettings(row)
                                })
                            }
                        }
                        callback(null, connection, lists)
                    }
                })
            }
        ], 
        function(err, connection, lists) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not find any lists for the userid (${userId}).`))))
            } else {
                completion(null, lists)
            }
        })
    }

    static listIDsForUser(params, completion) {
        if (!params || !params.userid) {
            completion(new Error(JSON.stringify(errors.cusomtError(errors.missingParameters, 'Missing userid.'))))
            return
        }

        const userid          = params.userid
        const includeDeleted  = params.includeDeleted  != null ? params.includeDeleted  == 'true' : false
        const includeFiltered = params.includeFiltered != null ? params.includeFiltered == 'true' : false

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
                if (!includeFiltered) {
                    TCUserSettingsService.userSettingsForUserId({ userid: userid, dbConnection: connection }, function(err, userSettings) {
                        if (err) { 
                            callback(new Error(JSON.stringify(errors.userSettingsNotFound)), connection)
                        }
                        else { 
                            callback(null, connection, userSettings) 
                        }
                    })
                }
                else {
                    callback(null, connection, null)
                }
            },
            function(connection, userSettings, callback) {
                const filteredListIds = []
                if (userSettings && userSettings.all_list_filter_string) {
                    filteredListIds.concat(userSettings.all_list_filter_string.split(','))
                }

                let sql = `
                    SELECT lists.listid
                    FROM tdo_lists lists
                        INNER JOIN tdo_list_memberships memberships ON memberships.listid = lists.listid
                    WHERE
                        memberships.userid = ?
                `

                for (const listId in filteredListIds) {
                    sql += `AND lists.listid != ?\n`
                }

                if (!includeDeleted) {
                    sql += `AND (lists.deleted IS NULL OR lists.deleted = 0)\n`
                }

                connection.query(sql, [userid].concat(filteredListIds), function(err, results, fields) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        const listids = []
                        if (results.rows) {
                            for (const row of results.rows) {
                                listids.push(row.listid)
                            }
                        }
                        callback(null, connection, listids)
                    }
                })
            }
        ], 
        function(err, connection, listids) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not find any lists for the userid (${userid}).`))))
            } else {
                completion(null, listids)
            }
        })
    }

    static addList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userId = params.userid != undefined ? params.userid : null
        const creator = userId
        const name = params.name && typeof params.name == 'string' ? params.name.trim() : null

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction
        
        if (!creator || creator.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userId.'))))
            return
        }

        if (!name || name.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing list name.'))))
            return
        }

        async.waterfall([
            function(callback) {
                if (!dbTransaction) {
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
                    callback(null, dbTransaction)
                }
            },
            function(transaction, callback) {
                const list = new TCList()
                const listProperties = {
                    creator: creator,
                    name: name,
                    dirty: params.dirty !== undefined ? params.dirty : true,
                    isSyncService: params.isSyncService
                }
                if (params.listid) {
                    listProperties["listid"] = params.listid
                }
                if (params.sync_id) {
                    listProperties["sync_id"] = params.sync_id
                }
                list.configureWithProperties(listProperties)

                list.add(transaction, function(err, list) {
                    if(err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding a new list (${name}): ${err.message}`))), transaction)
                    }
                    else {
                        callback(null, transaction, list)
                    }
                })
            },
            function(transaction, list, callback) {
                TCListMembershipService.createListMembership({
                    userid : userId,
                    listid : list.listid,
                    dbConnection : transaction
                }, function(err, membership){
                    if(err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding a new list membership (${name}): ${err.message}`))), transaction)
                    }
                    else {
                        // No need to pass the membership along
                        callback(null, transaction, list)
                    }
                })
            },
            function(transaction, list, callback) {
                const settingsParams = {
                    userid : userId,
                    listid : list.listid,
                    dbConnection : transaction
                }
                if (params.settings) {
                    Object.assign(settingsParams, params.settings)
                }

                TCListSettingsService.createListSettings(settingsParams, function(err, settings){
                    if(err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding new list settings (${name}): ${err.message}`))), transaction)
                    }
                    else {
                        callback(null, transaction, list, settings)
                    }
                })
            },
            function(transaction, list, settings, callback) {
                // Log the addition to the ChangeLog
                const changeParams = {
                    listid: list.listid,
                    userid: userId,
                    itemid: list.listid,
                    itemName: list.name,
                    itemType: constants.ChangeLogItemType.List,
                    changeType: constants.ChangeLogType.Add,
                    changeLocation: constants.ChangeLogLocation.API,
                    dbConnection: transaction
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, changeLogResult) {
                    if (err) {
                        logger.debug(`Error recording change to changelog during addList(): ${err}`)
                    }
                    callback(null, transaction, list, settings)
                })
            }
        ], 
        function(err, transaction, list, settings) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not create list.`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not create list. Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, {
                                    list : list,
                                    settings: settings
                                })
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, {
                        list : list,
                        settings: settings
                    })
                }
            }
        })
    }

    static getList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const listid = params.listid != null ? params.listid : null
        const userid = params.userid != null ? params.userid : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing listid.'))))
            return
        }

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
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
            function (connection, callback) {
                // Check that the user is authorized to access this list
                const authorizationParams = {
                    listid: listid,
                    userid: userid,
                    membershipType: constants.ListMembershipType.Member,
                    dbConnection: connection
                }
                TCListMembershipService.isAuthorizedForMembershipType(authorizationParams, function(err, isAuthorized) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error confirming list (${listid}) membership authorization for user (${userid}).`))), connection)
                    }
                    else {
                        if (!isAuthorized) {
                            callback(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                        } else {
                            callback(null, connection)
                        }
                    }
                })
            },
            function(connection, callback) {
                const aList = new TCList({
                    listid: listid
                })
                aList.read(connection, function(err, theList) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading the list (${listid}): ${err.message}`))), connection)
                    } else {
                        callback(null, connection, theList)
                    }
                })
            },
            function(connection, list, callback) {
                // Get the list settings
                const settingsParams = {
                    listid: listid,
                    userid: userid,
                    dbConnection: connection
                }
                TCListSettingsService.getListSettings(settingsParams, function(err, settings) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, list, settings)
                    }
                })
            }
        ], 
        function(err, connection, list, settings) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not read list (${listid}).`))))
            } else {
                if (list && settings) {
                    completion(null, { 
                            list: list,
                            settings: settings
                        })
                } else {
                    completion(new Error(JSON.stringify(errors.listNotFound)))
                }
            }
        })
    }

    static updateList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const name = params.name && typeof params.name == 'string' ? params.name.trim() : null
        const deleted = params.deleted != null ? params.deleted : null
        const listid = params.listid != null ? params.listid : null
        const userid = params.userid != null ? params.userid : null

        let shouldUpdateSettings = params.settings != null

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing listid.'))))
            return
        }

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
            return
        }

        async.waterfall([function(callback) {
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
                // Read the original list so we can verify membership authorization and
                // also have a copy of the original values for logging properly to the
                // change log.
                const getListParams = {
                    listid: listid,
                    userid: userid,
                    dbConnection: transaction
                }
                TCListService.getList(getListParams, function(err, originalList) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, originalList)
                    }
                })
            },
            function(transaction, originalList, callback) {
                if(shouldUpdateSettings) {
                    const settingsParams = Object.assign(params.settings, { 
                        dbConnection : transaction, 
                        listid: listid,
                        userid: userid
                    })
                    TCListSettingsService.updateListSettings(settingsParams, function(err, settings) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(err, `Error updating list settings (${name}).`))), transaction)
                        }
                        else {
                            callback(null, transaction, originalList, settings)
                        }
                    })
                }
                else {
                    callback(null, transaction, originalList, {})
                }
            },
            function(transaction, originalList, settings, callback) {
                const list = new TCList({
                    listid : listid,
                    name : name,
                    dirty: params.dirty !== undefined ? params.dirty : true,
                    sync_id : params.sync_id ? params.sync_id : undefined,
                    isSyncService : params.isSyncService
                })
                if (originalList) {
                    list.update(transaction, function(err, list) {
                        if(err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding a new list (${name}): ${err.message}`))), transaction)
                        }
                        else {
                            const changeData = []
                            if (originalList.name != list.name) {
                                changeData['old-listName'] = originalList.name ? originalList.name : ''
                                changeData['listName'] = list.name ? list.name : ''
                            }
                            if (originalList.description != list.description) {
                                changeData['old-description'] = originalList.description ? originalList.description : ''
                                changeData['description'] = list.description ? list.description : ''
                            }

                            const changeParams = {
                                listid: listid,
                                userid: userid,
                                itemid: listid,
                                itemName: name,
                                itemType: constants.ChangeLogItemType.List,
                                changeType: constants.ChangeLogType.Modify,
                                changeLocation: constants.ChangeLogLocation.API,
                                changeData: JSON.stringify(changeData),
                                dbConnection: transaction
                            }
                            TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                                // Since the change log isn't critical to the overall system (in a sense),
                                // do not fail the updateList() if there's a problem adding a change log entry.
                                if (err) {
                                    logger.debug(`Error recording change to changelog during updateList(): ${err}`)
                                }
                                callback(null, transaction, list, settings)
                            })
                        }
                    })
                }
                else {
                    callback(null, transaction, {}, settings)
                }
            },
        ],
        function(err, transaction, list, settings) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not update list.`))))
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
                            completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not update list. Database commit failed: ${err.message}`))))
                        } else {
                            completion(null, { 
                                list: list,
                                settings: settings
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

    static deleteList(params, completion) {
        const userid = params.userid
        const listid = params.listid

        if(!(userid && listid)) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const dbTransaction = params.dbTransaction
        let closeTransaction = false

        async.waterfall([
            function(callback) {
                if (dbTransaction) {
                    callback(null, dbTransaction)
                } else {
                    db.getPool(function(err, pool) {
                        begin(pool, {autoRollback: false}, function(err, transaction) {
                            if (err) {
                                callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                            } else {
                                closeTransaction = true
                                callback(null, transaction)
                            }
                        })
                    })
                }
            },
            function(transaction, callback) {
                TCListService.canDeleteList({
                    userid : userid,
                    listid : listid,
                    dbConnection : transaction
                }, function(err, canDelete) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error rolling back after failing to delete a list (${listid}).`))), transaction)
                    }
                    else {
                        callback(null, transaction, canDelete)
                    }
                })
            },
            function(transaction, canDelete, callback) {
                if (canDelete) {
                    let page = 0
                    let returnedTasks = 0
                    async.doWhilst(function(whilstCallback) {
                        const getTaskParams = {
                            userid: userid,
                            listid: listid,
                            page: page,
                            page_size: constants.maxPagedTasks,
                            preauthorized: true,
                            dbConnection: transaction
                        }
                        TCTaskService.tasksForList(getTaskParams, function(getTaskErr, tasksInfo) {
                            if (getTaskErr) {
                                whilstCallback(getTaskErr)
                            } else {
                                if (tasksInfo && tasksInfo.tasks && tasksInfo.tasks.length > 0) {
                                    returnedTasks = tasksInfo.tasks.length

                                    async.eachSeries(tasksInfo.tasks,
                                    function(aTask, eachCallback) {
                                        // Delete each task one by one. If this ends up taking
                                        // too long, the DB transaction should fail and the user
                                        // will get an error message.
                                        const delParams = {
                                            taskid: aTask.taskid,
                                            userid: userid,
                                            preauthorized: true,
                                            dbTransaction: transaction
                                        }
                                        TCTaskService.deleteTask(delParams, function(delErr, delResult) {
                                            if (delErr) {
                                                eachCallback(delErr)
                                            } else {
                                                eachCallback(null)
                                            }
                                        })
                                    },
                                    function(eachErr) {
                                        whilstCallback(eachErr)
                                    })
                                } else {
                                    returnedTasks = 0
                                    whilstCallback(null)
                                }
                            }
                        })
                    },
                    function() {
                        return returnedTasks > 0
                    },
                    function(whilstErr) {
                        if (whilstErr) {
                            callback(whilstErr, transaction, canDelete)
                        } else {
                            callback(null, transaction, canDelete)
                        }
                    })
                } else {
                    callback(null, transaction, canDelete)
                }
            },
            function(transaction, canDelete, callback) {
                if (canDelete) {
                    const list = new TCList({
                        userid : userid,
                        listid : listid,
                        deleted: 1
                    })

                    list.update(transaction, function(err, result) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete list. Database query failed: ${err.message}`))), transaction)
                        }
                        else {
                            // Read the list name to log it
                            const listParams = {
                                listid: listid,
                                dbConnection: transaction
                            }
                            TCListService.getListName(listParams, function(err, listName) {
                                if (err) {
                                    callback(err, transaction, false, result)
                                } else {
                                    const changeParams = {
                                        listid: listid,
                                        userid: userid,
                                        itemid: listid,
                                        itemName: listName,
                                        itemType: constants.ChangeLogItemType.List,
                                        changeType: constants.ChangeLogType.Delete,
                                        changeLocation: constants.ChangeLogLocation.API,
                                        dbConnection: transaction
                                    }
                                    TCChangeLogService.addChangeLogEntry(changeParams, function(err, changeLogResult) {
                                        if (err) {
                                            logger.debug(`Error recording change to changelog during deleteList(): ${err}`)
                                        }
                                        callback(null, transaction, result, null)
                                    })
                                }
                            })
                        }
                    })
                }
                else {
                    const canDeleteMembershipParams = {
                        userid: userid,
                        listid: listid,
                        dbConnection: transaction
                    }
                    TCListMembershipService.canDeleteMembership(canDeleteMembershipParams, function(canDeleteErr, canDelete) {
                        if (canDeleteErr) {
                            callback(canDeleteErr, transaction)
                        } else {
                            if (canDelete) {
                                const deleteMembershipParams = {
                                    userid : userid,
                                    listid : listid,
                                    dbConnection : transaction
                                }
                                TCListMembershipService.deleteMembership(deleteMembershipParams, function(err, result) {
                                    if (err) {
                                        callback(new Error(JSON.stringify(errors.customError(err, `Could not delete list membership.`))), transaction)
                                    }
                                    else {
                                        callback(null, transaction, null, result)
                                    }
                                })
                            } else {
                                // Respond with an error because the delete should not happen
                                callback(new Error(JSON.stringify(errors.listMembershipNotEmpty)), transaction, null, null)
                            }
                        }
                    })
                }
            }
        ], 
        function(err, transaction, deletedList, deletedMembership) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not delete list.`))))
                if (transaction && closeTransaction) {
                    transaction.rollback(function(transErr, result) {
                        // Ignore the result. The transaction will be closed or we'll get an error
                        // because the transaction has already been rolled back.
                        db.cleanup()
                    })
                } else if (closeTransaction) {
                    db.cleanup()
                }
            } else {
                if (transaction && closeTransaction) {
                    transaction.commit(function(err, result) {
                        if (err) {
                            completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not delete list. Database commit failed: ${err.message}`))))
                        } else {
                            completion(null, {
                                list : deletedList,
                                deletedMembership : deletedMembership
                            })
                        }
                        db.cleanup()
                    })
                } else if (closeTransaction) {
                    db.cleanup()
                }
            }
        })
    }

    static canDeleteList(params, completion) {
        async.waterfall([
            function(callback) {
                TCListMembershipService.getMembershipCountForList(params, function(err, count) {
                    callback(err, count == 1)
                })
            },
            function (canDelete, callback) {
                params.membershipType = constants.ListMembershipType.Owner
                TCListMembershipService.isAuthorizedForMembershipType(params, function(err, authorized) {
                    callback(err, canDelete, authorized)
                })
            }
        ],
        function(err, canDelete, authorized) {
            if (err) { 
                completion(err)
            }
            else {
                completion(null, canDelete && authorized)
            }
        })
    }

    static getAllListsAndMembersForUser(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
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
                let params = {
                    userid: userid,
                    includeDeleted: true,
                    dbConnection: connection
                }
                TCListService.listsForUserId(params, function(err, lists) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, lists)
                    }
                })
            },
            function(connection, lists, callback) {
                var listsAndMembers = []

                async.each(lists, function(listInfo, eachCallback) {
                    let list = listInfo.list
                    let sql = `SELECT userid,membership_type FROM tdo_list_memberships WHERE listid=?`
                    connection.query(sql, [list.listid], function(err, results) {
                        if (err) {
                            eachCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting members of a list (${list.listid}): ${err.message}`))))
                        } else {
                            const members = []
                            if (results.rows) {
                                for (const row of results.rows) {
                                    members.push(row)
                                }
                                listsAndMembers.push({
                                    listid: list.listid,
                                    members: members
                                })
                            }
                            eachCallback()
                        }
                    })
                },
                function(err) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, listsAndMembers)
                    }
                })
            }
        ],
        function(err, connection, listsAndMembers) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not retrieve lists and members for userid (${userid}).`))))
            } else {
                completion(null, listsAndMembers)
            }
        })
    }

    static getListName(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }
        
        const listid = params.listid

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCListService.getListName() : Missing listid.'))))
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
                let sql = `SELECT name from tdo_lists WHERE listid=?`
                connection.query(sql, [listid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    } else {
                        let listName = null
                        if (results.rows && results.rows.length > 0) {
                            listName = results.rows[0].name
                        }

                        if (listName) {
                            callback(null, connection, listName)
                        } else {
                            callback(new Error(JSON.stringify(errors.listNotFound)), connection)
                        }
                    }
                })
            }
        ],
        function(err, connection, listName) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get a list name for listid (${listid}).`))))
            } else {
                completion(null, listName)
            }
        })
    }

    static getListSyncId(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }
        
        const listid = params.listid

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCListService.getListSyncId() : Missing listid.'))))
            return
        }

        if (listid == constants.LocalInboxId) {
            completion(null, constants.ServerInboxId)
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
                let sql = `SELECT sync_id from tdo_lists WHERE listid=?`
                connection.query(sql, [listid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    } else {
                        let syncid = null
                        if (results.rows && results.rows.length > 0) {
                            syncid = results.rows[0].sync_id
                        }

                        callback(null, connection, syncid ? syncid : "0")
                    }
                })
            }
        ],
        function(err, connection, syncid) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get a sync id name for listid (${listid}).`))))
            } else {
                completion(null, syncid)
            }
        })
    }

    static permanentlyDeleteList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let listid = params.listid && typeof params.listid == 'string' ? params.listid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection

        if (!listid || listid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the listid parameter.`))))
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
            function(transaction, callback) {
                // Go through each taskID for each task table and issue
                // permanent deletes for each task.
                let tableNames = constants.TasksTableNames
                async.eachLimit(tableNames, 1, function(tableName, eachTableCallback) {
                    let taskIDs = []
                    let sql = `SELECT taskid FROM ${tableName} WHERE listid=?`
                    transaction.query(sql, [listid], function(err, result) {
                        if (err) {
                            eachTableCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))))
                        } else {
                            if (result && result.rows) {
                                result.rows.forEach(function(row) {
                                    taskIDs.push(row.taskid)
                                })

                                async.eachLimit(taskIDs, 1, function(taskid, eachTaskCallback) {
                                    let taskParams = {
                                        taskid: taskid,
                                        tableName: tableName,
                                        dbTransaction: transaction
                                    }
                                    TCTaskService.permanentlyDeleteTask(taskParams, function(deleteTaskErr, result) {
                                        if (deleteTaskErr) {
                                            eachTaskCallback(deleteTaskErr)
                                        } else {
                                            eachTaskCallback()
                                        }
                                    })
                                },
                                function(eachTaskErr) {
                                    if (eachTaskErr) {
                                        eachTableCallback(err)
                                    } else {
                                        eachTableCallback()
                                    }
                                })
                            }
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
                let tableNames = [
                    "tdo_list_settings",
                    "tdo_list_memberships",
                    "tdo_lists",
                    "tdo_change_log" // should delete anything tied to this list (including task change log entries)
                ]
                async.each(tableNames, function(tableName, eachTableCallback) {
                    let sql = `DELETE FROM ${tableName} WHERE listid=?`
                    transaction.query(sql, [listid], function(err, result) {
                        if (err) {
                            eachTableCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete list (${listid}) from table (${tableName}): ${err.message}.`))))
                        } else {
                            eachTableCallback()
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
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not permanently delete a list (${listid}).`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not permanently delete a list (${listid}). Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, true)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, true)
                }
            }
        })
    }

    static removeUserFromList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        let listid = params.listid && typeof params.listid == 'string' ? params.listid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        if (!listid || listid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the listid parameter.`))))
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
            function(transaction, callback) {
                // Remove the user from list memberships and remove the
                // corresponding list settings record(s).
                let tableNames = [
                    "tdo_list_memberships",
                    "tdo_list_settings"
                ]
                async.each(tableNames, function(tableName, eachTableCallback) {
                    let sql = `DELETE FROM ${tableName} WHERE listid=? AND userid=?`
                    transaction.query(sql, [listid, userid], function(err, result) {
                        if (err) {
                            eachTableCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete list (${listid}) and user (${userid}) from table (${tableName}): ${err.message}.`))))
                        } else {
                            eachTableCallback()
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
                // Unassign any tasks from this user
                let tableNames = [
                    "tdo_tasks",
                    "tdo_completed_tasks",
                    "tdo_deleted_tasks"
                ]
                let nowTimestamp = Math.floor(Date.now() / 1000)
                async.each(tableNames, function(tableName, eachTableCallback) {
                    let sql = `UPDATE ${tableName} SET assigned_userid=NULL, timestamp=${nowTimestamp} WHERE assigned_userid=? AND listid=?`
                    transaction.query(sql, [userid, listid], function(err, result) {
                        if (err) {
                            eachTableCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not unassign user user (${userid}) from tasks in list (${listid}), table (${tableName}): ${err.message}.`))))
                        } else {
                            eachTableCallback()
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
                // TO-DO: Add a call to notify Slack that a user was removed (see TDOList.php:removeUserFromList() for more information)
                logger.debug(`TO-DO: tc-list-service.js:removeUserFromList() Add a call to notify Slack that a user was removed (see TDOList.php:removeUserFromList() for more information)`)
                callback(null, transaction)
            }
        ],
        function(err, transaction) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not remove a user (${userid}) from a list (${listid}).`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not remove a user (${userid}) from a list (${listid}). Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, true)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, true)
                }
            }
        })
    }

    static taskCountForList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const listid = params.listid != null ? params.listid : null
        const userid = params.userid != null ? params.userid : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing listid.'))))
            return
        }

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
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
            function (connection, callback) {
                // Check that the user is authorized to access this list
                const authorizationParams = {
                    listid: listid,
                    userid: userid,
                    membershipType: constants.ListMembershipType.Member,
                    dbConnection: connection
                }
                TCListMembershipService.isAuthorizedForMembershipType(authorizationParams, function(err, isAuthorized) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error confirming list (${listid}) membership authorization for user (${userid}).`))), connection)
                    }
                    else {
                        if (!isAuthorized) {
                            callback(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                        } else {
                            callback(null, connection)
                        }
                    }
                })
            },
            function(connection, callback) {
                let sql = `
                    SELECT COUNT(taskid) as count FROM tdo_tasks WHERE listid = ? 
                `

                connection.query(sql, [listid], (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        let count = 0
                        for (let row of result.rows) {
                            count = row.count
                        }

                        callback(null, connection, count)
                    }
                })
            },
            function(connection, count, callback) {
                const settings = new TCUserSettings({userid : userid})
                settings.read(connection, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error reading user settings: ${err.message}`))))
                    }
                    else {
                        callback(null, connection, count, result.timezone)
                    }
                })
            },
            function(connection, count, timezone, callback) {
                const timezoneNow = moment.tz(timezone).unix()
                const regularNow = Date.now() / 1000
                let sql = `
                    SELECT COUNT(taskid) as overdue
                    FROM tdo_tasks
                    WHERE listid = ? AND
                        (((duedate != 0) AND (CASE 1 WHEN (due_date_has_time = 1) THEN duedate < ? ELSE duedate < ? END)) OR
                        ((project_duedate != 0) AND (CASE 1 WHEN (project_duedate_has_time = 1) THEN project_duedate < ? ELSE project_duedate < ? END)))
                `

                connection.query(sql, [listid, timezoneNow, regularNow, timezoneNow, timezoneNow], (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        let overdue = 0
                        for (let row of result.rows) {
                            overdue = row.overdue
                        }
                        callback(null, connection, { count: count, overdue: overdue })
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
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get list task count (${listid}).`))))
            } else {
                completion(null, result)
            }
        })
    }

    // This should only be used by the code that runs locally in the SQLite environment (sync service)
    static findUnsyncedListByName(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const name = params.name ? params.name : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing name.`))))
            return
        }

        const sql = `
            SELECT lists.*, settings.*
            FROM tdo_lists lists
                INNER JOIN tdo_list_memberships memberships ON memberships.listid = lists.listid
                INNER JOIN tdo_list_settings settings ON settings.listid = lists.listid
            WHERE
                memberships.userid = ? AND 
                (lists.sync_id IS NULL OR lists.sync_id='') AND 
                (lists.deleted IS NULL OR lists.deleted=0) AND 
                lists.name=?
        `

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
                connection.query(sql, [userid, name], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        let list = null
                        if (results.rows && results.rows.length > 0) {
                            const row = results.rows[0]
                            list = {
                                list: new TCList(row),
                                settings: new TCListSettings(row)
                            }
                        }
                        callback(null, connection, list)
                    }
                })
            }
        ], 
        function(err, connection, list) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Error searching for a list matching a name: (${name}).`))))
            } else {
                completion(null, list)
            }
        })
    }

    static syncIdForListId(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }
        
        const listid = params.listid

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCListService.syncIdForListId() : Missing listid.'))))
            return
        }

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
                            next(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error getting a database connection: ${err.message}`))), null)
                            return
                        } 
                        next(null, connection)
                    })
                })
            },
            function(connection, callback) {
                let sql = `SELECT sync_id from tdo_lists WHERE listid=?`
                connection.query(sql, [listid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                        return
                    }

                    let syncid = null
                    if (results.rows && results.rows.length > 0) {
                        syncid = results.rows[0].sync_id
                    }

                    if (!syncid) {
                        callback(new Error(JSON.stringify(errors.listNotFound)), connection)
                        return
                    }

                    callback(null, connection, syncid)
                })
            }
        ],
        function(err, connection, syncid) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get the syncid for listid (${listid}).`))))
                return
            } 

            completion(null, syncid)
        })
    }

    static listIdForSyncId(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }
        
        const syncid = params.syncid

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!syncid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCListService.listIdForSyncId() : Missing listid.'))))
            return
        }

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
                            next(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error getting a database connection: ${err.message}`))), null)
                            return
                        } 
                        next(null, connection)
                    })
                })
            },
            function(connection, callback) {
                let sql = `SELECT listid from tdo_lists WHERE sync_id=?`
                connection.query(sql, [syncid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                        return
                    }

                    let listid = null
                    if (results.rows && results.rows.length > 0) {
                        listid = results.rows[0].listid
                    }

                    if (!listid) {
                        callback(new Error(JSON.stringify(errors.listNotFound)), connection)
                        return
                    }

                    callback(null, connection, listid)
                })
            }
        ],
        function(err, connection, listid) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get the listid for syncid (${syncid}).`))))
                return
            } 

            completion(null, listid)
        })
    }

    static getListForSyncId(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }
        
        params.userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        params.syncid = params.syncid && typeof params.syncid == 'string' ? params.syncid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!params.userid || params.userid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCListService.getListForSyncId() : Missing userid.'))))
            return
        }
        if (!params.syncid || params.syncid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCListService.getListForSyncId() : Missing syncid.'))))
            return
        }
logger.debug(`getListForSyncId() 0 - ${params.syncid}`)

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
                let listid = null
                const sql = `SELECT listid FROM tdo_lists WHERE sync_id = ?`
logger.debug(`getListForSyncId() 1 - ${sql}`)
                connection.query(sql, [params.syncid], function(err, result) {
                    if (err) {
logger.debug(`getListForSyncId() 2`)
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                        `Error running query: ${err.message}`))), connection)
                    } else {
logger.debug(`getListForSyncId() 3`)
                        if (result.rows && result.rows.length > 0) {
logger.debug(`getListForSyncId() 4`)
                            listid = result.rows[0].listid
                        }
                        callback(null, connection, listid)
                    }
                })
            },
            function(connection, listid, callback) {
logger.debug(`getListForSyncId() 5`)
                if (listid) {
                    // Now just use our normal getList() method
                    const getParams = {
                        userid: params.userid,
                        listid: listid,
                        dbConnection: connection,
                        preauthorized: true
                    }
                    TCListService.getList(getParams, function(err, list) {
                        if (err) {
logger.debug(`getListForSyncId() 6`)
                            callback(err, connection, null)
                        } else {
logger.debug(`getListForSyncId() 6.1 - ${list ? JSON.stringify(list) : 'list is null'}`)
                            callback(null, connection, list)
                        }
                    })
                } else {
logger.debug(`getListForSyncId() 7`)
                    callback(null, connection, null)
                }
            }
        ], 
        function(err, connection, list) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
logger.debug(`getListForSyncId() 8`)
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not find list for syncid (${params.syncid}).`))))
            } else {
logger.debug(`getListForSyncId() 9`)
                completion(null, list) // Note, list may be null (which means we don't have a list with the requested syncid)
            }
        })
    }
    
}

module.exports = TCListService

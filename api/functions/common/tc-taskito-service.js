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
const TCListMembershipService = require('./tc-list-membership-service')
const TCTaskito = require('./tc-taskito')
const TCTaskUtils = require('./tc-task-utils')

class TCTaskitoService {
    static getTaskitosForChecklist(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        let parentid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!parentid || parentid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.getTaskitosForChecklist() missing parentid.'))))
            return
        }

        // Load this here so we don't get a circular dependency issue at load time
        const TCTaskService = require('./tc-task-service')

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
                // Read in the whole parent task (checklist) so we can verify
                // that it's indeed a checklist. If a task is returned, we also
                // know that the user is authorized to add a task. If they aren't
                // authorized, an unauthorized error will be returned.
                const taskParams = {
                    userid: userid,
                    taskid: parentid,
                    dbConnection: connection
                }
                TCTaskService.getTask(taskParams, function(err, checklist) {
                    if (err) {
                        callback(err, connection)
                    } else if (checklist) {
                        if (!checklist.isChecklist()) {
                            callback(new Error(JSON.stringify(errors.invalidParent)), connection)
                        } else {
                            callback(null, connection)
                        }
                    } else {
                        callback(new Error(JSON.stringify(errors.taskNotFound)), connection)
                    }
                })
            },
            function(connection, callback) {
                let sql = `
                    SELECT * 
                    FROM tdo_taskitos
                    WHERE parentid = ? AND
                        (deleted IS NULL OR deleted = 0)
                    ORDER BY sort_order, taskitoid
                `

                connection.query(sql, [parentid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting taskitos from database: ${err.message}`))))
                        return 
                    }
                    else {
                        const rows = [].concat(result.rows)
                        callback(null, connection, rows.map(row => new TCTaskito(row)))
                    }
                })
            }
        ], 
        function(err, connection, taskitos){
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not find any taskitos for the checklist (${parentid}).`))))
            } else {
                completion(null, taskitos)
            }
        })
    }

    static getTaskito(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskitoService.getTaskito().`))))
            return
        }

        let userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        let taskitoid = params.taskitoid && typeof params.taskitoid == 'string' ? params.taskitoid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!taskitoid || taskitoid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.getTaskito() missing taskitoid.'))))
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
                let sql = `
                    SELECT * 
                    FROM tdo_taskitos
                    WHERE taskitoid = ?
                `
                connection.query(sql, [taskitoid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting a taskito from database: ${err.message}`))))
                        return 
                    }
                    else {
                        if (result.rows && result.rows.length > 0) {
                            callback(null, connection, new TCTaskito(result.rows[0]))
                        } else {
                            callback(null, connection, null)
                        }
                    }
                })
            }
        ], 
        function(err, connection, taskito){
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Error getting taskito for id (${taskitoid}).`))))
            } else {
                completion(null, taskito)
            }
        })
    }

    static getTaskitoForSyncId(params, completion) {
logger.debug(`getTaskitoForSyncId() 0`)
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskitoService.getTaskitoForSyncId().`))))
            return
        }
        
        params.userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        params.syncid = params.syncid && typeof params.syncid == 'string' ? params.syncid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!params.userid || params.userid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.getTaskitoForSyncId() : Missing userid.'))))
            return
        }
        if (!params.syncid || params.syncid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.getTaskitoForSyncId() : Missing syncid.'))))
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
logger.debug(`getTaskitoForSyncId() 1`)
                let taskitoid = null
                const sql = `SELECT taskitoid FROM tdo_taskitos WHERE sync_id = ?`
                connection.query(sql, [params.syncid], function(err, result) {
                    if (err) {
logger.debug(`getTaskitoForSyncId() 3`)
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                        `Error running query: ${err.message}`))), connection)
                    } else {
logger.debug(`getTaskitoForSyncId() 4`)
                        if (result.rows && result.rows.length > 0) {
logger.debug(`getTaskitoForSyncId() 5`)
                            taskitoid = result.rows[0].taskitoid
                        }
                        callback(null, connection, taskitoid)
                    }
                })
            },
            function(connection, taskitoid, callback) {
                if (taskitoid) {
logger.debug(`getTaskitoForSyncId() 8`)
                    // Now just use our normal getTaskito() method
                    const getTaskParams = {
                        userid: params.userid,
                        taskitoid: taskitoid,
                        dbConnection: connection,
                        preauthorized: true
                    }
logger.debug(`getTaskitoForSyncId() 8.1: ${JSON.stringify(getTaskParams)}`)
                    TCTaskitoService.getTaskito(getTaskParams, function(err, taskito) {
logger.debug(`getTaskitoForSyncId() 9`)
                        callback(err, connection, taskito)
                    })
                } else {
logger.debug(`getTaskitoForSyncId() 9.1`)
                    callback(null, connection, null)
                }
            }
        ], 
        function(err, connection, taskito) {
logger.debug(`getTaskitoForSyncId() 10`)
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
logger.debug(`getTaskitoForSyncId() 11`)
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Error finding taskito for syncid (${params.syncid}).`))))
            } else {
logger.debug(`getTaskitoForSyncId() 12`)
                completion(null, taskito) // Note, taskito may be null (which means we don't have a taskito with the requested syncid)
            }
        })
    }

    static getUnsyncedTaskitoMatchingProperties(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskitoService.getUnsyncedTaskitoMatchingProperties().`))))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        if (!userid) {
            completion(new Erro(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid parameter.`))))
            return
        }

        const task = params.taskito != undefined ? params.taskito : null
        if (!task) {
            completion(new Erro(JSON.stringify(errors.customError(errors.missingParameters, `Missing taskito parameter.`))))
            return
        }

        const values = []

        let whereStatement = `(sync_id IS NULL OR sync_id = '') AND (deleted IS NULL OR deleted=0) `

        if (task.parentid != undefined && task.parentid.length > 0) {
            whereStatement += ` AND parentid = ?`
            values.push(task.parentid)
        }

        if (task.name != undefined) {
            whereStatement += ` AND name = ?`
            values.push(task.name)
        }

        whereStatement += ` AND completiondate = ?`
        if (task.completiondate != undefined && task.completiondate != 0) {
            values.push(task.completionDate)
        } else {
            values.push(0)
        }

        const sql = `SELECT taskitoid FROM tdo_taskitos WHERE ${whereStatement}`

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
                connection.query(sql, values, function(err, result) {
                    let taskitoid = null
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                        `Error running query: ${err.message}`))), connection)
                    } else {
                        if (result.rows && result.rows.length > 0) {
                            taskitoid = result.rows[0].taskitoid
                        }
                        callback(null, connection, taskitoid)
                    }
                })
            },
            function(connection, taskitoid, callback) {
                if (taskitoid) {
                    // Now just use our normal getTaskito() method
                    const getTaskParams = {
                        userid: params.userid,
                        taskitod: taskitoid,
                        dbConnection: connection,
                        preauthorized: true
                    }
                    TCTaskitoService.getTaskito(getTaskParams, function(err, existingTaskito) {
                        callback(err, connection, existingTaskito)
                    })
                } else {
                    callback(null, connection, null)
                }
            }
        ],
        function(err, connection, existingTaskito) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Error while looking for a matching existing taskito.`))))
            } else {
                completion(null, existingTaskito)
            }
        })
    }

    static getAllDirtyTaskitos(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskitoService.getAllDirtyTaskitos().`))))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskitoService.getAllDirtyTaskitos() Missing userid parameter.`))))
            return
        }
logger.debug(`getAllDirtyTaskitos() 0`)

        const modifiedAfterDate = params.modifiedAfterDate != undefined ? params.modifiedAfterDate : null

        let sql = `SELECT * FROM tdo_taskitos WHERE dirty > 0`

        if (modifiedAfterDate) {
logger.debug(`getAllDirtyTaskitos() 3`)
            sql += ` AND timestamp > ${modifiedAfterDate}`
        }
     
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        const tasks = []

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
logger.debug(`getAllDirtyTaskitos() 4: ${sql}`)
                connection.query(sql, [], function(err, results) {
                    if (err) {
logger.debug(`getAllDirtyTaskitos() 5`)
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
logger.debug(`getAllDirtyTaskitos() 6: ${JSON.stringify(results)}`)
                        if (results.rows) {
                            for (const row of results.rows) {
                                tasks.push(new TCTaskito(row))
                            }
                        }
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
logger.debug(`getAllDirtyTaskitos() 7`)
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Error while looking for a dirty taskitos.`))))
            } else {
logger.debug(`getAllDirtyTaskitos() 8`)
                completion(null, tasks)
            }
        })
    }

    static addTaskito(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        let parentid = params.parentid && typeof params.parentid == 'string' ? params.parentid.trim() : null
        let name = params.name && typeof params.name == 'string' ? params.name.trim() : null

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction

        let listid = null
        
        if (!userid || name.userid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.addTaskito() missing userid.'))))
            return
        }
        if (!parentid || parentid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.addTaskito() missing parentid.'))))
            return
        }
        if (!name || name.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.addTaskito() missing name.'))))
            return
        }

        // Load this here so we don't get a circular dependency issue at load time
        const TCTaskService = require('./tc-task-service')

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
                // Read in the whole parent task (checklist) so we can verify
                // that it's indeed a checklist. If a task is returned, we also
                // know that the user is authorized to add a task. If they aren't
                // authorized, an unauthorized error will be returned.
                const taskParams = {
                    userid: userid,
                    taskid: parentid,
                    dbConnection: transaction
                }
                TCTaskService.getTask(taskParams, function(err, checklist) {
                    if (err) {
                        callback(err, transaction)
                    } else if (checklist) {
                        if (!checklist.isChecklist()) {
                            callback(new Error(JSON.stringify(errors.invalidParent)), transaction)
                        } else {
                            listid = checklist.listid
                            callback(null, transaction)
                        }
                    } else {
                        callback(new Error(JSON.stringify(errors.taskNotFound)), transaction)
                    }
                })
            },
            function(transaction, callback) {
                const sortOrderParams = {
                    taskid: parentid,
                    dbConnection: transaction
                }
                TCTaskitoService.highestSortOrderForTaskitosOfTask(sortOrderParams, function(err, highestSortOrder) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, highestSortOrder)
                    }
                })
            },
            function(transaction, highestSortOrder, callback) {
                const newTaskito = new TCTaskito(params)
                newTaskito['sort_order'] = highestSortOrder + 10 // copying the PHP implementation (which adds 10)
                newTaskito.add(transaction, function(err, taskito) {
                    if(err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding new checklist item (${name}): ${err.message}`))), transaction)
                    }
                    else {
                        callback(null, transaction, taskito)
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Update the taskito timestamp on the list so that sync clients
                // know that there's been a change.
                const listParams = {
                    listid: listid,
                    timestamp: Math.floor(Date.now() / 1000),
                    isTaskito: true,
                    dbConnection: transaction
                }
                TCTaskService.updateTaskTimestampForList(listParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, taskito)
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Add a change log entry indicating that a taskito has been created.
                const changeParams = {
                    listid: listid,
                    userid: userid,
                    itemid: taskito.taskitoid,
                    itemName: taskito.name,
                    itemType: constants.ChangeLogItemType.Taskito,
                    changeType: constants.ChangeLogType.Add,
                    changeLocation: constants.ChangeLogLocation.API,
                    dbConnection: transaction
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    // Since the change log isn't critical to the overall system (in a sense),
                    // do not fail the addTask() if there's a problem adding a change log entry.
                    if (err) {
                        logger.debug(`Error recording change to changelog during addTaskito(): ${err}`)
                    }
                    callback(null, transaction, taskito)
                })
            }
        ], 
        function(err, transaction, taskito) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not create a taskito (${name}) in checklist (${parentid}).`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not create a taskito (${name}) in checklist (${parentid}).`))))
                            } else {
                                completion(null, taskito)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, taskito)
                }
            }
        })
    }
    
    static updateTaskito(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const taskitoid = params.taskitoid && typeof params.taskitoid == 'string' ? params.taskitoid.trim() : null
        const name = params.name && typeof params.name == 'string' ? params.name.trim() : null
        const sortOrder = params.sort_order != undefined ? isNaN(Number(params.sort_order)) ? null : Math.abs(Number(params.sort_order)) : null

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction

        let listid = null
        const changeData = {}
        
        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.updateTaskito() missing userid.'))))
            return
        }
        if (!taskitoid || taskitoid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.updateTaskito() missing taskitoid.'))))
            return
        }

        if (name == null && sortOrder == null) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskitoService.updateTaskito() one of 'name' or 'sort_order' must be specififed.`))))
            return
        }

        // Load this here so we don't get a circular dependency issue at load time
        const TCTaskService = require('./tc-task-service')

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
                const aTaskito = new TCTaskito({taskitoid: taskitoid})
                aTaskito.read(transaction, function(err, taskito) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading taskito (${taskitoid}): ${err.message}`))), transaction)
                    } else {
                        callback(null, transaction, taskito)
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Read in the whole parent task (checklist) so we can verify
                // that it's indeed a checklist. If a task is returned, we also
                // know that the user is authorized for making changes. If they aren't
                // authorized, an unauthorized error will be returned.
                const taskParams = {
                    userid: userid,
                    taskid: taskito.parentid,
                    dbConnection: transaction
                }
                TCTaskService.getTask(taskParams, function(err, checklist) {
                    if (err) {
                        callback(err, transaction)
                    } else if (checklist) {
                        if (!checklist.isChecklist()) {
                            callback(new Error(JSON.stringify(errors.invalidParent)), transaction)
                        } else {
                            listid = checklist.listid // save off the listid for the ChangeLog entry later
                            callback(null, transaction, taskito)
                        }
                    } else {
                        callback(new Error(JSON.stringify(errors.taskNotFound)), transaction)
                    }
                })
            },
            function(transaction, taskito, callback) {
                taskito.configureWithProperties(params)
                if (name != null) {
                    changeData['old-taskName'] = taskito.name
                    changeData['taskName'] = name
                    taskito.name = name
                }
                if (sortOrder != null) {
                    changeData['old-sortOrder'] = taskito.sort_order
                    changeData['sortOrder'] = sortOrder
                    taskito.sort_order = sortOrder
                }
                taskito.update(transaction, function(err, updatedTaskito) {
                    if(err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating a checklist item (${taskitoid}): ${err.message}`))), transaction)
                    }
                    else {
                        callback(null, transaction, updatedTaskito)
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Update the taskito timestamp on the list so that sync clients
                // know that there's been a change.
                const listParams = {
                    listid: listid,
                    timestamp: Math.floor(Date.now() / 1000),
                    isTaskito: true,
                    dbConnection: transaction
                }
                TCTaskService.updateTaskTimestampForList(listParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, taskito)
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Add a change log entry indicating that a taskito has been created.
                const changeParams = {
                    listid: listid,
                    userid: userid,
                    itemid: taskito.taskitoid,
                    itemName: taskito.name,
                    itemType: constants.ChangeLogItemType.Taskito,
                    changeType: constants.ChangeLogType.Modify,
                    changeLocation: constants.ChangeLogLocation.API,
                    changeData: JSON.stringify(changeData),
                    dbConnection: transaction
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    // Since the change log isn't critical to the overall system (in a sense),
                    // do not fail updateTaskito() if there's a problem adding a change log entry.
                    if (err) {
                        logger.debug(`Error recording change to changelog during updateTaskito(): ${err}`)
                    }
                    callback(null, transaction, taskito)
                })
            }
        ], 
        function(err, transaction, taskito) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not update a taskito (${taskitoid}).`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not update a taskito (${taskitoid}).`))))
                            } else {
                                completion(null, taskito)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, taskito)
                }
            }
        })
    }
    
    static deleteTaskito(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const taskitoid = params.taskitoid && typeof params.taskitoid == 'string' ? params.taskitoid.trim() : null

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction

        let listid = null // used later to add a change log entry
        let taskitoAlreadyDeleted = false

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.updateTaskito() missing userid.'))))
            return
        }
        if (!taskitoid || taskitoid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.updateTaskito() missing taskitoid.'))))
            return
        }

        // Load this here so we don't get a circular dependency issue at load time
        const TCTaskService = require('./tc-task-service')

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
                const aTaskito = new TCTaskito({taskitoid: taskitoid})
                aTaskito.read(transaction, function(err, taskito) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading taskito (${taskitoid}): ${err.message}`))), transaction)
                    } else {
                        if (taskito.deleted) {
                            taskitoAlreadyDeleted = true
                            callback(new Error(`Taskito is already deleted. Nothing to do.`), transaction)
                        } else {
                            callback(null, transaction, taskito)
                        }
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Read in the whole parent task (checklist) so we can verify
                // that it's indeed a checklist. If a task is returned, we also
                // know that the user is authorized for making changes. If they aren't
                // authorized, an unauthorized error will be returned.
                const taskParams = {
                    userid: userid,
                    taskid: taskito.parentid,
                    dbConnection: transaction
                }
                TCTaskService.getTask(taskParams, function(err, checklist) {
                    if (err) {
                        callback(err, transaction)
                    } else if (checklist) {
                        if (!checklist.isChecklist()) {
                            callback(new Error(JSON.stringify(errors.invalidParent)), transaction)
                        } else {
                            listid = checklist.listid // save off the listid for the ChangeLog entry later
                            callback(null, transaction, taskito)
                        }
                    } else {
                        callback(new Error(JSON.stringify(errors.taskNotFound)), transaction)
                    }
                })
            },
            function(transaction, taskito, callback) {
                taskito.delete(transaction, function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a taskito (${taskitoid}): ${err.message}.`))), transaction)
                    } else {
                        callback(null, transaction, taskito)
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Update the taskito timestamp on the list so that sync clients
                // know that there's been a change.
                const listParams = {
                    listid: listid,
                    timestamp: Math.floor(Date.now() / 1000),
                    isTaskito: true,
                    dbConnection: transaction
                }
                TCTaskService.updateTaskTimestampForList(listParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, taskito)
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Add a change log entry indicating that a taskito has been deleted.
                const changeParams = {
                    listid: listid,
                    userid: userid,
                    itemid: taskito.taskitoid,
                    itemName: taskito.name,
                    itemType: constants.ChangeLogItemType.Taskito,
                    changeType: constants.ChangeLogType.Delete,
                    changeLocation: constants.ChangeLogLocation.API,
                    dbConnection: transaction
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    // Since the change log isn't critical to the overall system (in a sense),
                    // do not fail deleteTaskito() if there's a problem adding a change log entry.
                    if (err) {
                        logger.debug(`Error recording change to changelog during deleteTaskito(): ${err}`)
                    }
                    callback(null, transaction, taskito)
                })
            }
        ], 
        function(err, transaction) {
            if (err) {
                if (taskitoAlreadyDeleted) {
                    // This isn't really an error and we really just need to
                    // return to the caller as if everything worked.
                    completion(null, true)
                } else {
                    let errObj = JSON.parse(err.message)
                    completion(new Error(JSON.stringify(errors.customError(errObj, `Could not delete a taskito (${taskitoid}).`))))
                }
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not mark a taskito as deleted. Database commit failed: ${err.message}`))))
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
    
    static completeTaskitos(params, completion) {
        params.completiondate = Math.floor(Date.now() / 1000)
        TCTaskitoService.updateTaskitosCompletion(params, function(err, result) {
            if (err) {
                completion(err)
            } else {
                completion(null, result)
            }
        })
    }

    static uncompleteTaskitos(params, completion) {
        params.completiondate = 0
        TCTaskitoService.updateTaskitosCompletion(params, function(err, result) {
            if (err) {
                completion(err)
            } else {
                completion(null, result)
            }
        })
    }
    
    static updateTaskitosCompletion(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }
        
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const taskIDs = params.items != undefined ? params.items : []
        const completionDate = params.completiondate != undefined ? params.completiondate : 0

        const dbTransaction = params.dbConnection
        const shouldCleanupDB = !dbTransaction
        
        if (!userid || userid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.updateTaskitosCompletion() : Missing userid.'))))
            return
        }
        if (!taskIDs || taskIDs.constructor !== Array) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.updateTaskitosCompletion() : Missing items.'))))
            return
        }
        if (taskIDs.length == 0) {
            completion(null, {items : []})
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
                let index = 0
                let allCompletedTaskIDs = []
                async.whilst(
                    function() {
                        // Return true to keep going
                        return index < taskIDs.length
                    },
                    function(doWhilstCallback) {
                        const taskid = taskIDs[index]
                        index++
                        const completeTaskitoParams = {
                            userid: userid,
                            taskitoid: taskid,
                            completiondate: completionDate,
                            dbTransaction: transaction
                        }
                        TCTaskitoService.updateTaskitoCompletion(completeTaskitoParams, function(err, completedTaskIDs) {
                            if (err) {
                                doWhilstCallback(err)
                            } else {
                                if (completedTaskIDs.length > 0) {
                                    allCompletedTaskIDs = allCompletedTaskIDs.concat(completedTaskIDs)
                                }
                                doWhilstCallback(null)
                            }
                        })
                    },
                    function(err) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            callback(null, transaction, allCompletedTaskIDs)
                        }
                    }
                )
            }
        ], 
        function(err, transaction, allCompletedTaskIDs) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Error updating completion value of checklist items.`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not update completion value of checklist item(s). Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, {items: allCompletedTaskIDs})
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, {items: allCompletedTaskIDs})
                }
            }
        })
    }
    
    static updateTaskitoCompletion(params, completion) {
        const taskitoid = params.taskitoid && typeof params.taskitoid == 'string' ? params.taskitoid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const completionDate = params.completiondate ? params.completiondate : 0

        // // We have a gatekeeper of API Gateway that wouldn't pass this along,
        // // but when we call this recursively, we can pass in preauthorization
        // // so that we don't have to check to see if the user is authorized to
        // // delete the specified taskid.
        // let isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction

        let listid = null // used to add an entry into the change log
        const allCompletedTaskIDs = [taskitoid]
        
        if(!taskitoid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.updateTaskitoCompletion() missing taskitoid.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.updateTaskitoCompletion() missing userid.'))))
            return
        }

        // Load this here so we don't get a circular dependency issue at load time
        const TCTaskService = require('./tc-task-service')

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
                const aTaskito = new TCTaskito({taskitoid: taskitoid})
                aTaskito.read(transaction, function(err, taskito) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading taskito (${taskitoid}): ${err.message}`))), transaction)
                    } else {
                        if (taskito.deleted) {
                            taskitoAlreadyDeleted = true
                            callback(new Error(`Taskito is already deleted. Nothing to do.`), transaction)
                        } else {
                            callback(null, transaction, taskito)
                        }
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Read in the whole parent task (checklist) so we can verify
                // that it's indeed a checklist. If a task is returned, we also
                // know that the user is authorized for making changes. If they aren't
                // authorized, an unauthorized error will be returned.
                const taskParams = {
                    userid: userid,
                    taskid: taskito.parentid,
                    dbConnection: transaction
                }
                TCTaskService.getTask(taskParams, function(err, checklist) {
                    if (err) {
                        callback(err, transaction)
                    } else if (checklist) {
                        if (!checklist.isChecklist()) {
                            callback(new Error(JSON.stringify(errors.invalidParent)), transaction)
                        } else {
                            listid = checklist.listid // save off the listid for the ChangeLog entry later
                            callback(null, transaction, taskito)
                        }
                    } else {
                        callback(new Error(JSON.stringify(errors.taskNotFound)), transaction)
                    }
                })
            },
            function(transaction, taskito, callback) {
                taskito.completiondate = completionDate
                taskito.update(transaction, function(err, updatedTaskito) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not update a completiondate value for a taskito (${taskitoid}): ${err.message}.`))), transaction)
                    } else {
                        callback(null, transaction, taskito)
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Update the taskito timestamp on the list so that sync clients
                // know that there's been a change.
                const listParams = {
                    listid: listid,
                    timestamp: Math.floor(Date.now() / 1000),
                    isTaskito: true,
                    dbConnection: transaction
                }
                TCTaskService.updateTaskTimestampForList(listParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, taskito)
                    }
                })
            },
            function(transaction, taskito, callback) {
                // Add a change log entry indicating that a taskito has been created.
                const changeParams = {
                    listid: listid,
                    userid: userid,
                    itemid: taskito.taskitoid,
                    itemName: taskito.name,
                    itemType: constants.ChangeLogItemType.Taskito,
                    changeType: constants.ChangeLogType.Modify,
                    changeLocation: constants.ChangeLogLocation.API,
                    dbConnection: transaction
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    // Since the change log isn't critical to the overall system (in a sense),
                    // do not fail deleteTaskito() if there's a problem adding a change log entry.
                    if (err) {
                        logger.debug(`Error recording change to changelog during updateTaskitoCompletion(): ${err}`)
                    }
                    callback(null, transaction, taskito)
                })
            }
        ], 
        function(err, transaction) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not update the completiondate value of a taskito (${taskitoid}).`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not update the completiondate value of a taskito (${taskitoid}). Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, allCompletedTaskIDs)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, allCompletedTaskIDs)
                }
            }
        })
    }

    // static moveTaskito(params, completion) {
    //         completion(new Error(JSON.stringify(errors.customError(errors.serverError, 'TCTaskitoService.moveTaskito() is not implemented.'))))
    // }
    
    // static updateSortOrders(params, completion) {
    //         completion(new Error(JSON.stringify(errors.customError(errors.serverError, 'TCTaskitoService.updateSortOrders() is not implemented.'))))
    // }

    static highestSortOrderForTaskitosOfTask(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let highestSortOrder = 0
        let taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!taskid || taskid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.addTaskito() missing taskid.'))))
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
                const sql = `SELECT MAX(sort_order) FROM tdo_taskitos WHERE (deleted IS NULL OR deleted=0) AND parentid=?`
                connection.query(sql, [taskid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        if (results.rows && results.rows.length > 0) {
                            highestSortOrder = Number(results.rows[0][results.fields[0]['name']])
                        }
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
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not determine the max sort order for a checklist (${taskid}).`))))
            } else {
                completion(null, highestSortOrder)
            }
        })
    }
    
    static completeChildrenOfChecklist(params, completion) {
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        // We have a gatekeeper of API Gateway that wouldn't pass this along,
        // but when we call this recursively, we can pass in preauthorization
        // so that we don't have to check to see if the user is authorized to
        // delete the specified taskid.
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if(!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.completeChildrenOfChecklist() Missing taskid.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.completeChildrenOfChecklist() Missing userid.'))))
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
                // Read the listid of the checklist
                const listParams = {
                    taskid: params.taskid,
                    dbConnection: connection
                }
                TCTaskUtils.listIDForTask(listParams, function(err, listid) {
                    if (err) {
                        callback(err, connection)
                    } else if (listid) {
                        callback(null, connection, listid)
                    } else {
                        callback(new Error(JSON.stringify(errors.taskNotFound)), connection)
                    }
                })
            },
            function (connection, listid, callback) {
                if (isPreauthorized) {
                    callback(null, connection, listid)
                } else {
                    // Check that the user is authorized to access this task
                    const authorizationParams = {
                        listid: listid,
                        userid: params.userid,
                        membershipType: constants.ListMembershipType.Member,
                        dbConnection: connection
                    }
                    TCListMembershipService.isAuthorizedForMembershipType(authorizationParams, function(err, isAuthorized) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(err, `Error confirming list (${listid}) membership authorization for user (${params.userid}).`))), connection)
                        }
                        else {
                            if (!isAuthorized) {
                                callback(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                            } else {
                                callback(null, connection, listid)
                            }
                        }
                    })
                }
            },
            function(connection, listid, callback) {
                const timestamp = Math.floor(Date.now() / 1000)
                let sql = `UPDATE tdo_taskitos SET completiondate=?, timestamp=? WHERE parentid=?`
                connection.query(sql, [timestamp, timestamp, taskid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    } else {
                        // Send results.rowCount so we know in the next waterfall function
                        // if there were any affected taskitos. It appears that there's also
                        // the following properties on results: "affectedRows", "changedRows",
                        // but the only documented one is "rowCount", which also seems to get
                        // set on a successful update.
                        callback(null, connection, listid, timestamp, results.rowCount)
                    }
                })
            },
            function(connection, listid, timestamp, numOfUpdatedTaskitos, callback) {
                if (numOfUpdatedTaskitos > 0) {
                    // Update the taskito timestamp on the list so that sync clients
                    // know that there's been a change.
                    const listParams = {
                        listid: listid,
                        timestamp: timestamp,
                        dbConnection: connection
                    }
                    TCTaskitoService.updateTaskitoTimestampForList(listParams, function(err, result) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection)
                        }
                    })
                } else {
                    callback(null, connection)
                }
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
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not complete taskitos for checklist (${taskid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static updateTaskitoTimestampForList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const listid = params.listid && typeof params.listid == 'string' ? params.listid.trim() : null
        const timestamp = params.timestamp !== undefined ? params.timestamp : Math.floor(Date.now() / 1000)

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!listid || listid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskitoService.updateTaskitoTimestampForList() missing the listid parameter.`))))
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
                let sql = `UPDATE tdo_lists SET taskito_timestamp=? WHERE listid=?`
                connection.query(sql, [timestamp, listid], function(err, result) {
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
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not update the taskito_timestamp for list (${listid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static deleteChildrenOfChecklist(params, completion) {
        const taskid = params.checklistid && typeof params.checklistid == 'string' ? params.checklistid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        // We have a gatekeeper of API Gateway that wouldn't pass this along,
        // but when we call this recursively, we can pass in preauthorization
        // so that we don't have to check to see if the user is authorized to
        // delete the specified taskid.
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if(!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.deleteChildrenOfChecklist() Missing taskid.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskitoService.deleteChildrenOfChecklist() Missing userid.'))))
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
                if (isPreauthorized) {
                    callback(null, connection, null)
                } else {
                    // Read the listid of the task so we can make sure the
                    // user is authorized to access the task.
                    const listParams = {
                        taskid: params.taskid,
                        dbConnection: connection
                    }
                    TCTaskUtils.listIDForTask(listParams, function(err, listid) {
                        if (err) {
                            callback(err, connection)
                        } else if (listid) {
                            callback(null, connection, listid)
                        } else {
                            callback(new Error(JSON.stringify(errors.taskNotFound)), connection)
                        }
                    })
                }
            },
            function (connection, listid, callback) {
                if (isPreauthorized) {
                    callback(null, connection)
                } else {
                    // Check that the user is authorized to access this task
                    const authorizationParams = {
                        listid: listid,
                        userid: params.userid,
                        membershipType: constants.ListMembershipType.Member,
                        dbConnection: connection
                    }
                    TCListMembershipService.isAuthorizedForMembershipType(authorizationParams, function(err, isAuthorized) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(err, `Error confirming list (${listid}) membership authorization for user (${params.userid}).`))), connection)
                        }
                        else {
                            if (!isAuthorized) {
                                callback(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                            } else {
                                callback(null, connection)
                            }
                        }
                    })
                }
            },
            function(connection, callback) {
                const timestamp = Math.floor(Date.now() / 1000)
                let sql = `UPDATE tdo_taskitos SET deleted=1, timestamp=? WHERE parentid=?`
                connection.query(sql, [timestamp, taskid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    } else {
                        callback(null, connection)
                    }
                })
            },
        ], 
        function(err, connection, subtaskIDs) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not delete taskitos for checklist (${taskid}).`))))
            } else {
                completion(null, true)
            }
        })
    }
}

module.exports = TCTaskitoService

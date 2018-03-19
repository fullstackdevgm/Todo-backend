// @ts-check 
// ^ Visual Studio Code now supports checking types in Javascript even
// if the file is not a *.ts file, but you have to tell it to do so
// with this special comment at the top of the file.

'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')

const db = require('./tc-database')
const begin = require('any-db-transaction')
const moment = require('moment-timezone')

const constants = require('./constants')
const errors = require('./errors')

const TCSmartList = require('./tc-smart-list')
const TCTask = require('./tc-task')
const TCUserSettingsService = require('./tc-user-settings-service')
const TCUtils = require('./tc-utils')

const NO_DATE_SORT_VALUE = 64092211200

class TCSmartListService {
    static getSmartLists(params, completion) {
        const userid = params.userid != null ? params.userid : null
        const includeDeleted = params.includeDeleted != null ? params.includeDeleted : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        async.waterfall([
            function(next) {
                if (dbConnection) {
                    next(null, dbConnection)
                    return
                }

                db.getPool(function(err, pool) {
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            const message = `Error beginning a database transaction: ${err.message}`
                            next(errors.create(errors.databaseError, message))
                            return
                        }

                        next(null, transaction)
                    })
                })
            },
            function(transaction, callback) {
                let sql = `
                    SELECT *
                    FROM tdo_smart_lists
                    WHERE
                        userid = ?`

                if (!includeDeleted) {
                    sql += ' AND (deleted IS NULL OR deleted = 0)'
                }

                transaction.query(sql, [userid], function(err, results, fields) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `SQL error retrieving smart lists: ${err.message}`))), connection)
                    }
                    else {
                        const smartLists = []
                        if (results.rows) {
                            for (const row of results.rows) {
                                smartLists.push(new TCSmartList(row))
                            }
                        }
                        callback(null, transaction, smartLists)
                    }
                })
            },
            function (transaction, smartLists, callback) {
                const hasEverythingSmartList = smartLists.reduce((accum, smartList) => {
                    return accum || smartList.icon_name == "menu-everything"
                }, false)

                if (hasEverythingSmartList) {
                    callback(null, transaction, smartLists)
                    return
                }

                const createParams = {
                    userid : userid,
                    dbConnection : transaction
                }
                TCSmartListService.createDefaultSmartLists(createParams, (err, result) => {
                    callback(err, transaction, result)
                })
            }
        ], 
        function(err, transaction, smartLists) {
            if (err) {
                let errObj = JSON.parse(err.message)
                const message = `Could not find any smart lists for the userid (${userid}).`
                completion(errors.create(errObj, message))
                if (shouldCleanupDB) {
                    if (transaction) {
                        transaction.rollback((transErr, result) => db.cleanup())
                    } else {
                        db.cleanup()
                    }
                }
                return
            } 

            const resultLists = smartLists.sort((a, b) => {
                let aName = a.name.toLowerCase()
                let bName = b.name.toLowerCase()

                if(aName > bName) return -1
                if(aName < bName) return 1
                return 0
            })

            if (shouldCleanupDB) {
                if (transaction) {
                    transaction.commit(function(err, result) {
                        if (err) {
                            const message = `Could not get smart lists. Database commit failed: ${err.message}`
                            completion(errors.create(errors.serverError, message))
                            return
                        } 

                        completion(null, resultLists)
                        db.cleanup()
                    })
                    return
                } else {
                    db.cleanup()
                }
            } 

            completion(null, resultLists)
        })
    }

    static getSmartList(params, completion) {
        const listid = params.listid != null ? params.listid : null
        const userid = params.userid != null ? params.userid : null

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing smart list listid.'))))
            return
        }
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
            return
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
                const smartList = new TCSmartList({
                    listid : listid
                })

                smartList.read(connection, function(err, resultSmartList) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting a smart list: ${err.message}`))), connection)
                    }
                    else {
                        // Verify that the userid matches the user requesting the smart list
                        if (resultSmartList.userid != userid) {
                            callback(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                        } else {
                            callback(null, connection, resultSmartList)
                        }
                    }
                })
            }
        ], 
        function(err, connection, smartList) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not find a smart list for listid (${listid}).`))))
            } else {
                completion(null, smartList)
            }
        })
    }

    static findUnsyncedSmartListByName(params, completion) {
        if (process.env.DB_TYPE != 'sqlite') {
            completion()
            return
        }
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
            SELECT smart_lists.*
            FROM tdo_smart_lists smart_lists
            WHERE
                smart_lists.userid = ? AND 
                (smart_lists.sync_id IS NULL OR smart_lists.sync_id='') AND 
                (smart_lists.deleted IS NULL OR smart_lists.deleted=0) AND 
                smart_lists.name=?
        `

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
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
            function(connection, next) {
                connection.query(sql, [userid, name], function(err, results) {
                    if (err) {
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                        return
                    }
                    let smartList = null
                    if (results.rows && results.rows.length > 0) {
                        const row = results.rows[0]
                        smartList = new TCSmartList(row)
                    }
                    next(null, connection, smartList)
                })
            }
        ], 
        function(err, connection, smartList) {
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
                completion(null, smartList)
            }
        })
    }

    static createSmartList(params, completion) {
        const userid = params.userid != null ? params.userid : null
        const name   = params.name   != null ? params.name   : null
        const color  = params.color  != null ? params.color  : null
        const iconName = params.icon_name != null ? params.icon_name : null
        const jsonFilter = params.json_filter != null ? params.json_filter : null
        const sortOrder  = params.sort_order  != null ? params.sort_order  : null
        const sortType   = params.sort_type   != null ? params.sort_type   : null
        const defaultList = params.default_list != null ? params.default_list : null
        const defaultDueDate  = params.default_due_date  != null ? params.default_due_date  : null
        const excludedListIds = params.excluded_list_ids != null ? params.excluded_list_ids : null
        const completedTasksFilters = params.completed_tasks_filter != null ? params.completed_tasks_filter : null
        const syncId = params.sync_id ? params.sync_id : undefined
        const dirty = params.dirty !== undefined ? params.dirty : true
        const isSyncService = params.isSyncService

        if (!name) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing smart list name.'))))
            return
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
                const smartList = new TCSmartList({
                    userid : userid,
                    name   : name,
                    color  : color,
                    icon_name   : iconName,
                    json_filter : jsonFilter,
                    sort_order  : sortOrder,
                    sort_type   : sortType,
                    default_list: defaultList,
                    default_due_date  : defaultDueDate,
                    excluded_list_ids : excludedListIds,
                    completed_tasks_filter : completedTasksFilters,
                    sync_id : syncId,
                    dirty : dirty,
                    isSyncService: isSyncService
                })

                if (params.listid) {
                    smartList["listid"] = params.listid
                }

                smartList.add(connection, function(err, resultSmartList) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error creating a smart list: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, resultSmartList)
                    }
                })
            }
        ], 
        function(err, connection, smartList) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not add a smart list with name (${name}).`))))
            } else {
                completion(null, smartList)
            }
        })
    }

    static createDefaultSmartLists(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid ? params.userid : null

        if (!userid) {
            const message = `Missing the userid when creating default smart lists.`
            completion(errors.create(errors.missingParameters, message))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        async.waterfall([
            function(next) {
                if (dbConnection) {
                    next(null, dbConnection)
                    return
                }

                db.getPool(function(err, pool) {
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            const message = `Error beginning a database transaction: ${err.message}`
                            next(errors.create(errors.databaseError, message))
                            return
                        }

                        next(null, transaction)
                    })
                })
            },
            function(transaction, next) {
                // Create the built-in "Everything" smart list
                let params = {
                    userid: userid,
                    name: "Everything", // TO-DO: Need to figure out how to provide a localized version of this
                    sort_order: 1,
                    icon_name: "menu-everything",
                    color: constants.SmartListColor.Blue,
                    json_filter: constants.SmartListJSONFilter.Everything,
                    sort_type: -1,
                    default_due_date: -1
                }
                TCSmartListService.createSmartList(params, function(err, result) {
                    if (err) {
                        next(err, transaction)
                        return
                    } 

                    next(null, transaction, [result])
                })
            },
            function(transaction, smartLists, callback) {
                // Create the built-in "Focus" smart list
                let params = {
                    userid: userid,
                    name: "Focus", // TO-DO: Need to figure out how to provide a localized version of this
                    sort_order: 2,
                    icon_name: "menu-focus",
                    color: constants.SmartListColor.Orange,
                    json_filter: constants.SmartListJSONFilter.Focus,
                    sort_type: -1,
                    default_due_date: -1
                }
                TCSmartListService.createSmartList(params, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                        return
                    } 
                    smartLists.push(result)
                    callback(null, transaction, smartLists)
                })
            },
            function(transaction, smartLists, callback) {
                // Create the built-in "Important" smart list
                let params = {
                    userid: userid,
                    name: "Important", // TO-DO: Need to figure out how to provide a localized version of this
                    sort_order: 3,
                    icon_name: "menu-important",
                    color: constants.SmartListColor.Yellow,
                    json_filter: constants.SmartListJSONFilter.Important,
                    sort_type: -1,
                    default_due_date: -1
                }
                TCSmartListService.createSmartList(params, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                        return
                    }
                    smartLists.push(result)
                    callback(null, transaction, smartLists)
                })
            },
            function(transaction, smartLists, callback) {
                // Create the built-in "Someday" smart list
                let params = {
                    userid: userid,
                    name: "Someday", // TO-DO: Need to figure out how to provide a localized version of this
                    sort_order: 4,
                    icon_name: "menu-someday",
                    color: constants.SmartListColor.Gray,
                    json_filter: constants.SmartListJSONFilter.Someday,
                    sort_type: -1,
                    default_due_date: -1
                }
                TCSmartListService.createSmartList(params, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                        return
                    } 
                    smartLists.push(result)
                    callback(null, transaction, smartLists)
                })
            }
        ],  
        function(err, transaction, smartLists) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(errors.create(errObj, `Could not create default smart lists.`))
                if (shouldCleanupDB) {
                    if (transaction) {
                        transaction.rollback((transErr, result) => db.cleanup())
                    } else {
                        db.cleanup()
                    }
                }
                return
            } 

            if (shouldCleanupDB) {
                if (transaction) {
                    transaction.commit(function(err, result) {
                        if (err) {
                            const message = `Could not create default smart lists. Database commit failed: ${err.message}`
                            completion(errors.create(errors.serverError, message))
                            return
                        } 

                        completion(null, smartLists)
                        db.cleanup()
                    })
                    return
                } else {
                    db.cleanup()
                }
            } 

            completion(null, smartLists)
        })
    }

    static updateSmartList(params, completion) {
        const listid = params.listid != null ? params.listid : null
        const name   = params.name   != null ? params.name   : null
        const color  = params.color  != null ? params.color  : null
        const iconName = params.icon_name != null ? params.icon_name : null
        const jsonFilter = params.json_filter != null ? params.json_filter : null
        const sortOrder  = params.sort_order  != null ? params.sort_order  : null
        const sortType   = params.sort_type   != null ? params.sort_type   : null
        const defaultList = params.default_list != null ? params.default_list : null
        const defaultDueDate  = params.default_due_date  != null ? params.default_due_date  : null
        const excludedListIds = params.excluded_list_ids != null || typeof params.excluded_list_ids == 'string' ? params.excluded_list_ids : null
        const completedTasksFilters = params.completed_tasks_filter != null ? params.completed_tasks_filter : null
        const userid = params.userid != null ? params.userid : null
        const syncId = params.sync_id ? params.sync_id : undefined
        const dirty = params.dirty !== undefined ? params.dirty : true
        const isSyncService = params.isSyncService

        if (!(name && listid)) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing smart list name or listid.'))))
            return
        }
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
            return
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
                // Verify that the user owns this smart list
                const authParams = {
                    listid: listid,
                    userid: userid,
                    dbConnection: connection
                }
                TCSmartListService.isUserAuthorizedForSmartList(authParams, function(err, isAuthorized) {
                    if (err) {
                        callback(err)
                    } else {
                        if (!isAuthorized) {
                            callback(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                        } else {
                            callback(null, connection)
                        }
                    }
                })
            },
            function(connection, callback) {
                const smartList = new TCSmartList({
                    listid : listid,
                    name   : name,
                    color  : color,
                    icon_name   : iconName,
                    json_filter : jsonFilter,
                    sort_order  : sortOrder,
                    sort_type   : sortType,
                    default_list: defaultList,
                    default_due_date  : defaultDueDate,
                    excluded_list_ids : excludedListIds,
                    completed_tasks_filter : completedTasksFilters,
                    sync_id : syncId,
                    dirty : dirty,
                    isSyncService : isSyncService
                })

                smartList.update(connection, function(err, resultSmartList) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating a smart list: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, resultSmartList)
                    }
                })
            }
        ], 
        function(err, connection, smartList) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not update smart lists with listid (${listid}).`))))
            } else {
                completion(null, smartList)
            }
        })
    }

    static deleteSmartList(params, completion) {
        const listid = params.listid != null ? params.listid : null

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing smart list listid.'))))
            return
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
                const smartList = new TCSmartList({
                    listid : listid,
                    deleted: 1
                })

                smartList.update(connection, function(err, resultSmartList) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                            `Error deleting a smart list: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, resultSmartList)
                    }
                })
            }
        ], 
        function(err, connection, smartList) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not delete smart lists with listid (${listid}).`))))
            } else {
                completion(null, smartList)
            }
        })
    }

    static permanentlyDeleteSmartList(params, completion) {
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
            function(next) {
                // Pass on the dbConnection we were passed or get and
                // start a new database transaction.
                if (dbConnection) {
                    next(null, dbConnection)
                    return
                } 

                db.getPool(function(err, pool) {
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            next(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                            return
                        }

                        next(null, transaction)
                    })
                })
            },
            function(transaction, next) {
                let tableNames = [
                    "tdo_smart_lists"
                ]
                async.each(tableNames, function(tableName, eachTableCallback) {
                    let sql = `DELETE FROM ${tableName} WHERE listid=?`
                    transaction.query(sql, [listid], function(err, result) {
                        if (err) {
                            const message = `Could not delete smart list (${listid}) from table (${tableName}): ${err.message}.`
                            eachTableCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, message))))
                            return
                        } 
                        eachTableCallback()
                    })
                },
                function(err) {
                    next(err ? err : null, transaction)
                })
            }
        ],
        function(err, transaction) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not permanently delete a smart list (${listid}).`))))
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
                return
            } 

            if (shouldCleanupDB) {
                if (transaction) {
                    transaction.commit(function(err, result) {
                        if (err) {
                            const message = `Could not permanently delete a smart list (${listid}). Database commit failed: ${err.message}`
                            completion(new Error(JSON.stringify(errors.customError(errors.serverError, message))))
                            return
                        } 

                        completion(null, true)
                        db.cleanup()
                    })
                    return
                } else {
                    db.cleanup()
                }
            } 

            completion(null, true)
        })
    }

    static isUserAuthorizedForSmartList(params, completion) {
        const listid = params.listid != null ? params.listid : null
        const userid = params.userid != null ? params.userid : null

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing smart list listid.'))))
            return
        }
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
            return
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
                const smartList = new TCSmartList({
                    listid : listid
                })

                smartList.read(connection, function(err, resultSmartList) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting a smart list: ${err.message}`))), connection)
                    }
                    else {
                        // Verify that the userid matches the user requesting the smart list
                        const isAuthorized = resultSmartList.userid == userid
                        callback(null, connection, isAuthorized)
                    }
                })
            }
        ], 
        function(err, connection, isAuthorized) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not find a smart list for listid (${listid}).`))))
            } else {
                completion(null, isAuthorized)
            }
        })
    }

    static sqlForCompletedTaskPeriod(completedTasksPeriod, userTimeZone) {
        let daysOffset = 0
        switch(completedTasksPeriod) {
            case constants.SmartListCompletedTasksPeriod.None:
                daysOffset = 0
                break;
            case constants.SmartListCompletedTasksPeriod.OneDay:
                daysOffset = 1
                break;
            case constants.SmartListCompletedTasksPeriod.TwoDays:
                daysOffset = 2
                break;
            case constants.SmartListCompletedTasksPeriod.ThreeDays:
                daysOffset = 3
                break;
            case constants.SmartListCompletedTasksPeriod.OneWeek:
                daysOffset = 7
                break;
            case constants.SmartListCompletedTasksPeriod.TwoWeeks:
            default:
                daysOffset = 14
                break;
        }

        const dateInterval = moment.tz(userTimeZone).subtract(daysOffset, 'day').startOf('day').unix()
        const sql = `completiondate > ${dateInterval}`
        return sql
    }

    static sqlForCompletedTasksSettingForSmartList(smartList, userTimeZone) {
        if (!smartList) { return null }

        const distantPastInterval = 0

        if (smartList.jsonFilter() == undefined || smartList.jsonFilter().completedTasks == undefined || smartList.jsonFilter().completedTasks.period == undefined) {
            return null
        }
        const periodSQL = TCSmartListService.sqlForCompletedTaskPeriod(smartList.jsonFilter().completedTasks.period, userTimeZone)
        return `(completiondate IS NOT NULL AND completiondate != ${distantPastInterval} AND (${periodSQL}))`
    }

    static sqlWhereStatementForSmartList(smartList, userTimeZone) {
        if (!smartList) {
            return null
        }

        const jsonFilter = smartList.jsonFilter()
        if (!jsonFilter) {
            return null
        }

        let sql = ''

        if (jsonFilter.filterGroups && jsonFilter.filterGroups.length > 0) {
            let needToCloseSegment = false
            if (sql.length > 0) {
                sql += ` AND (`
                needToCloseSegment = true
            }

            jsonFilter.filterGroups.forEach((filterGroup, idx) => {
                const filterGroupSQL = TCSmartListService.sqlForFilterGroup(filterGroup, smartList, userTimeZone)

                if (idx > 0) {
                    sql += ` OR `
                }

                if (filterGroupSQL && filterGroupSQL.length > 0) {
                    sql += `(${filterGroupSQL})`
                }

            })

            if (needToCloseSegment) {
                sql += `)`
            }
        }

        return sql
    }

    static sqlForFilterGroup(filterGroup, smartList, userTimeZone) {
        if (!filterGroup) {
            return ''
        }

        let sql = ''

        const filterNames = Object.keys(filterGroup)
        filterNames.forEach((filterName, idx) => {
            const filterValue = filterGroup[filterName]

            if (idx > 0) {
                sql += ` AND `
            }

            const filterSQL = TCSmartListService.sqlForFilter(filterName, filterValue, smartList, userTimeZone)
            if (filterSQL) {
                sql += `(${filterSQL})`
            }
        })

        return sql
    }

    static sqlForFilter(filterName, filterValue, smartList, userTimeZone) {
        switch(filterName) {
            case constants.SmartListFilterType.Action: {
                return TCSmartListService.sqlForActionFilter(filterValue)
            }
            case constants.SmartListFilterType.Assignment: {
                return TCSmartListService.sqlForAssignmentFilter(filterValue, smartList.userid)
            }
            case constants.SmartListFilterType.CompletedDate: {
                return TCSmartListService.sqlForDateFilter('completiondate', filterValue, false, userTimeZone)
            }
            case constants.SmartListFilterType.DueDate: {
                const jsonFilter = smartList.jsonFilter()
                const useStartDates = !(jsonFilter && jsonFilter.excludeStartDates != undefined && jsonFilter.excludeStartDates == true)

                return TCSmartListService.sqlForDateFilter('duedate', filterValue, useStartDates, userTimeZone)
            }
            case constants.SmartListFilterType.Location: {
                return TCSmartListService.sqlForLocationFilter(filterValue)
            }
            case constants.SmartListFilterType.ModifiedDate: {
                return TCSmartListService.sqlForDateFilter('timestamp', filterValue, false, userTimeZone)
            }
            case constants.SmartListFilterType.Name: {
                return TCSmartListService.sqlForTextFilter('name', filterValue)
            }
            case constants.SmartListFilterType.Note: {
                return TCSmartListService.sqlForTextFilter('note', filterValue)
            }
            case constants.SmartListFilterType.Priority: {
                return TCSmartListService.sqlForPriorityFilter(filterValue)
            }
            case constants.SmartListFilterType.Recurrence: {
                return TCSmartListService.sqlForRecurrenceFilter(filterValue)
            }
            case constants.SmartListFilterType.Starred: {
                return TCSmartListService.sqlForStarredFilter(filterValue)
            }
            case constants.SmartListFilterType.StartDate: {
                return TCSmartListService.sqlForDateFilter('startdate', filterValue, true, userTimeZone)
            }
            case constants.SmartListFilterType.TaskType: {
                return TCSmartListService.sqlForTaskTypeFilter(filterValue)
            }
            case constants.SmartListFilterType.Tags: {
                return TCSmartListService.sqlForTagFilter(filterValue)
            }
        }

        return ''
    }

    static sqlForActionFilter(filterValue) {
        if (filterValue.indexOf(constants.SmartListActionFilterType.None) < 0
                && filterValue.indexOf(constants.SmartListActionFilterType.Contact) < 0
                && filterValue.indexOf(constants.SmartListActionFilterType.Location) < 0
                && filterValue.indexOf(constants.SmartListActionFilterType.Url) < 0) {
            return ''
        }

        let sql = '('

        if (filterValue.indexOf(constants.SmartListActionFilterType.None) >= 0) {
            sql += `task_type != ${constants.TaskType.CallContact} AND task_type != ${constants.TaskType.SMSContact} AND task_type != ${constants.TaskType.EmailContact} AND task_type != ${constants.TaskType.VisitLocation} AND task_type != ${constants.TaskType.URL}`
            // For parent tasks, we have to inspect their task type data,
            // because their task_type field is dedicated to marking them as 
            // projects and checklists.
            sql += ` AND NOT (
                            (task_type = ${constants.TaskType.Project} OR task_type = ${constants.TaskType.Checklist}) 
                            AND (
                                type_data LIKE '%contact%' OR 
                                type_data LIKE '%url%' OR 
                                type_data LIKE '%location%' OR 
                                type_data LIKE '%other%'
                            )
                        )`
        }
        
        if (filterValue.indexOf(constants.SmartListActionFilterType.Contact) >= 0) {
            if (sql.length > 1) {
                sql += ` OR `
            }
            sql += `task_type = ${constants.TaskType.CallContact} OR task_type = ${constants.TaskType.SMSContact} OR task_type = ${constants.TaskType.EmailContact}`
        }
        
        if (filterValue.indexOf(constants.SmartListActionFilterType.Location) >= 0) {
            if (sql.length > 1) {
                sql += ` OR `
            }
            sql += `task_type = ${constants.TaskType.VisitLocation}`
        }
        
        if (filterValue.indexOf(constants.SmartListActionFilterType.Url) >= 0) {
            if (sql.length > 1) {
                sql += ` OR `
            }
            sql += `task_type = ${constants.TaskType.URL}`
        }
        
        sql += `)`
        return sql;
    }

    static sqlForAssignmentFilter(filterValue, userid) {
        let sql = ''

        if (filterValue && filterValue.length > 0) {
            sql += `(`

            filterValue.forEach((aUserID, idx) => {
                if (idx > 0) {
                    sql += ` OR `
                }

                if (aUserID == constants.SystemUserID.AllUser) {
                    // Assigned to anyone (not null and not empty string)
                    sql += `assigned_userid IS NOT NULL AND assigned_userid <> '' `
                } else if (aUserID == constants.SystemUserID.UnassignedUser) {
                    // No one is assigned (s null or empty string)
                    sql += `assigned_userid IS NULL OR assigned_userid = '' `
                } else if (aUserID == constants.SystemUserID.MeUser) {
                    sql += `assigned_userid = '${userid}'`
                } else {
                    sql += `assigned_userid = '${aUserID}'`
                }
            })
            
            sql += `)`
        }
        
        return sql;
    }

    static sqlForDateFilter(columnName, filterValue, usingStartDates, userTimeZone) {
        if (!columnName || !filterValue) {
            return ''
        }

        let zeroInterval = 0
        let distantPastInterval = 0
        let isDueDate = false
        let isStartDate = false
        let isCompletionDate = false
        switch(columnName) {
            case "duedate": {
                isDueDate = true
                zeroInterval = 0
                break;
            }
            case "completiondate": {
                isCompletionDate = true
                zeroInterval = 0
                break;
            }
            case "startdate": {
                isStartDate = true
                zeroInterval = 0
                break;
            }
            case "timestamp": {
                break;
            }
        }

        const projectColumnName = `project_${columnName}`
        let sql = `(`

        switch (filterValue.type) {
            case constants.SmartListDateFilterType.None: {
                if (isDueDate || isStartDate) {
                    sql += `(`
                    sql += `(task_type != 1 AND (${columnName} IS NULL OR ${columnName} = ${zeroInterval}))`
                    sql += ` OR `
                    sql += `(task_type = 1 AND (${projectColumnName} IS NULL OR ${projectColumnName} = ${zeroInterval}))`
                    sql += `)`
                } else {
                    sql += `(`
                    sql += `${columnName} IS NULL`
                    if (zeroInterval != 0 || isCompletionDate) {
                        sql += ` OR ${columnName} = ${zeroInterval}`
                    }
                    sql += `)`
                }
                break;
            }
            case constants.SmartListDateFilterType.Any: {
                if (isDueDate || isStartDate) {
                    sql += `(`
                    sql += `(task_type != 1 AND (${columnName} IS NOT NULL AND ${columnName} != ${zeroInterval}))`
                    sql += ` OR `
                    sql += `(task_type = 1 AND (${projectColumnName} IS NOT NULL AND ${projectColumnName} != ${zeroInterval}))`
                    sql += `)`
                } else {
                    sql += `(`
                    sql += `${columnName} IS NOT NULL`
                    if (zeroInterval != 0 || isCompletionDate) {
                        sql += ` AND ${columnName} != ${zeroInterval}`
                    }
                    sql += `)`
                }
                break;
            }
            case constants.SmartListDateFilterType.After: {
                let dateInterval = 0
                if (filterValue.relation == constants.SmartListDateRelationType.Exact) {
                    if (filterValue.date == undefined) {
                        return ''
                    }
                    // In the PHP, we rely on the session having the right time zone
                    // set and so anything that's done with the DateTime object already
                    // adjusts for the user's time zone. In Javascript, we don't have
                    // that same mechanism, so we need to make sure to account for the
                    // user's time zone.

                    const theDate = moment.tz(filterValue.date, userTimeZone)
                    theDate.startOf('day')
                    theDate.subtract(1, 'second') // One second before midnight
                    dateInterval = theDate.unix()
                } else if (filterValue.relation == constants.SmartListDateRelationType.Relative) {
                    const periodDate = TCSmartListService.dateForPeriodAndValue(filterValue.period, filterValue.value, userTimeZone)
                    if (!periodDate) {
                        return ''
                    }
                    dateInterval = moment.tz(periodDate, userTimeZone).startOf('day').subtract(1, 'second').unix()
                }

                // This is needed in order to pay close attention to "Today" when
                // evaluating tasks with a due time set. If the date interval is
                // not today, ignore looking at the due time.
                const nowInterval = moment().unix()

                const normalizedNow = TCUtils.normalizedDateFromGMT(moment().unix())
                const normalizedIntervalDate = TCUtils.normalizedDateFromGMT(dateInterval)
                let isToday = normalizedNow == normalizedIntervalDate

                if (isDueDate && isToday) {
                    if (usingStartDates) {
                        sql += `(`
                        sql += `(task_type != 1 AND ((((due_date_has_time = 0) AND (duedate > ${dateInterval}) AND (duedate != ${zeroInterval})) OR ((due_date_has_time = 1) AND (duedate > ${nowInterval}) AND (duedate != ${zeroInterval}))) OR (startdate IS NOT NULL AND startdate > ${dateInterval})))`
                        sql += ` OR `
                        sql += `(task_type = 1 AND ((((project_duedate_has_time = 0) AND (project_duedate > ${dateInterval}) AND (project_duedate != ${zeroInterval})) OR ((project_duedate_has_time = 1) AND (project_duedate > ${nowInterval}) AND (project_duedate != ${zeroInterval}))) OR (project_startdate IS NOT NULL AND project_startdate > ${dateInterval})))`
                        sql += `)`
                    } else {
                        sql += `(`
                        sql += `(task_type!=1 AND (((due_date_has_time = 0) AND (duedate > ${dateInterval}) AND (duedate != ${zeroInterval})) OR ((due_date_has_time = 1) AND (duedate > ${nowInterval}) AND (duedate != ${zeroInterval}))))`
                        sql += ` OR `
                        sql += `(task_type=1 AND (((project_duedate_has_time = 0) AND (project_duedate > ${dateInterval}) AND (project_duedate != ${zeroInterval})) OR ((project_duedate_has_time = 0) AND (project_duedate > ${nowInterval}) AND (project_duedate != ${zeroInterval}))))`
                        sql += `)`

                    }
                } else {
                    if (isDueDate && usingStartDates) {
                        sql += `(`
                        sql += `(task_type!=1 AND (((duedate > ${dateInterval}) AND (duedate != ${zeroInterval})) OR (startdate > ${dateInterval} AND startdate IS NOT NULL)))`
                        sql += ` OR `
                        sql += `(task_type=1 AND (((project_duedate > ${dateInterval}) AND (project_duedate != ${zeroInterval})) OR (project_startdate > ${dateInterval} AND project_startdate IS NOT NULL)))`
                        sql += `)`
                    } else {
                        if (isStartDate) {
                            sql += `(`
                            sql += `(task_type!=1 AND `
                        }
                        sql += `((${columnName} > ${dateInterval}) AND (${columnName} != ${zeroInterval}))`
                        if (isStartDate) {
                            sql += `)`
                            
                            sql += ` OR `
                            sql += `(task_type=1 AND ((${projectColumnName} > ${dateInterval}) AND (${projectColumnName} != ${zeroInterval})))`
                            sql += `)`
                        }
                    }
                }
                break;
            }
            case constants.SmartListDateFilterType.Before: {
                let dateInterval = 0
                if (filterValue.relation == constants.SmartListDateRelationType.Exact) {
                    if (filterValue.date == undefined) {
                        return ''
                    }
                    const theDate = moment.tz(filterValue.date, userTimeZone)
                    theDate.startOf('day')
                    theDate.subtract(1, 'second') // One second before midnight
                    dateInterval = theDate.unix()
                } else if (filterValue.relation == constants.SmartListDateRelationType.Relative) {
                    const periodDate = TCSmartListService.dateForPeriodAndValue(filterValue.period, filterValue.value, userTimeZone)
                    if (!periodDate) {
                        return ''
                    }
                    dateInterval = moment.tz(periodDate, userTimeZone).startOf('day').unix()
                }
                
                // This is needed in order to pay close attention to "Today" when
                // evaluating tasks with a due time set. If the date interval is
                // not today, ignore looking at the due time.
                const nowInterval = moment().unix()

                const normalizedNow = TCUtils.normalizedDateFromGMT(nowInterval, userTimeZone)
                const normalizedIntervalDate = TCUtils.normalizedDateFromGMT(dateInterval, userTimeZone)
                let isToday = normalizedNow == normalizedIntervalDate
                
                if (isDueDate && isToday) {
                    if (usingStartDates) {
                        sql += `(`
                        sql += `(task_type!=1 AND ((((due_date_has_time = 0) AND (duedate < ${dateInterval}) AND (duedate != ${zeroInterval})) OR ((due_date_has_time = 1) AND (duedate < ${nowInterval}) AND (duedate != ${zeroInterval}))) OR (startdate IS NOT NULL AND startdate < ${dateInterval} AND startdate != 0)))`
                        sql += ` OR `
                        sql += `(task_type=1 AND ((((project_duedate_has_time = 0) AND (project_duedate < ${dateInterval}) AND (project_duedate != ${zeroInterval})) OR ((project_duedate_has_time = 1) AND (project_duedate < ${nowInterval}) AND (project_duedate != ${zeroInterval}))) OR (project_startdate IS NOT NULL AND project_startdate < ${dateInterval} AND project_startdate != ${distantPastInterval})))`
                        sql += `)`
                    } else {
                        sql += `(`
                        sql += `(task_type!=1 AND (((due_date_has_time = 0) AND (duedate < ${dateInterval}) AND (duedate != ${zeroInterval})) OR ((due_date_has_time = 1) AND (duedate < ${nowInterval}) AND (duedate != ${zeroInterval}))))`
                        sql += ` OR `
                        sql += `(task_type=1 AND (((project_duedate_has_time = 0) AND (project_duedate < ${dateInterval}) AND (project_duedate != ${zeroInterval})) OR ((project_duedate_has_time = 1) AND (project_duedate < ${nowInterval}) AND (project_duedate != ${zeroInterval}))))`
                        sql += `)`
                    }
                } else {
                    if (isDueDate && usingStartDates) {
                        sql += `(`
                        sql += `(task_type!=1 AND (((duedate < ${dateInterval}) AND (duedate != ${zeroInterval})) OR (startdate IS NOT NULL AND startdate < ${dateInterval} AND startdate != ${distantPastInterval})))`
                        sql += ` OR `
                        sql += `(task_type=1 AND (((project_duedate < ${dateInterval}) AND (project_duedate != ${zeroInterval})) OR (project_startdate IS NOT NULL AND project_startdate < ${dateInterval} AND project_startdate != ${distantPastInterval})))`
                        sql += `)`
                    } else {
                        if (isStartDate) {
                            sql += `(`
                            sql += `(task_type!=1 AND `
                        }
                        sql += `((${columnName} < ${dateInterval}) AND (${columnName} != ${zeroInterval}))`
                        if (isStartDate) {
                            sql += `)`
                            
                            sql += ` OR `
                            sql += `(task_type=1 AND ((${projectColumnName} < ${dateInterval}) AND (${projectColumnName} != ${zeroInterval})))`
                            sql += `)`
                        }
                    }
                }
                break;
            }
            case constants.SmartListDateFilterType.Is:
            case constants.SmartListDateFilterType.Not:
            {
                let startInterval = 0
                let endInterval = 0
                if (filterValue.relation == constants.SmartListDateRelationType.Exact) {
                    if (filterValue.date == undefined && filterValue.dateRange == undefined) {
                        return ''
                    }

                    if (filterValue.date != undefined) {
                        const theDate = moment.tz(filterValue.date, userTimeZone) // Parses the ISO 8601 into a moment and accounts for the time zone
                        startInterval = theDate.startOf('day').unix()
                        endInterval = theDate.endOf('day').unix()
                    } else {
                        if (filterValue.dateRange.start == undefined || filterValue.dateRange.end == undefined) {
                            return ''
                        }

                        const startDate = moment.tz(filterValue.dateRange.start, userTimeZone)
                        const endDate = moment.tz(filterValue.dateRange.end, userTimeZone)
                        startInterval = startDate.startOf('day').unix()
                        endInterval = endDate.endOf('day').unix()
                    }
                } else if (filterValue.relation == constants.SmartListDateRelationType.Relative) {
                    if (filterValue.intervalRangeStart != undefined && filterValue.intervalRangeEnd != undefined) {
                        const startDate = TCSmartListService.dateForPeriodAndValue(filterValue.intervalRangeStart.period, filterValue.intervalRangeStart.start, userTimeZone)
                        const endDate = TCSmartListService.dateForPeriodAndValue(filterValue.intervalRangeEnd.period, filterValue.intervalRangeStart.end, userTimeZone)

                        startInterval = moment.tz(startDate, userTimeZone).startOf('day').unix()
                        endInterval = moment.tz(endDate, userTimeZone).endOf('day').unix()
                    } else {
                        const intervalDate = TCSmartListService.dateForPeriodAndValue(filterValue.period, filterValue.value, userTimeZone)
                        startInterval = moment.tz(intervalDate, userTimeZone).startOf('day').unix()
                        endInterval = moment.tz(intervalDate, userTimeZone).endOf('day').unix()
                    }
                }

                if (filterValue.type == constants.SmartListDateFilterType.Is) {
                    if (isDueDate) {
                        if (usingStartDates) {
                            sql += `(`
                            sql += `(task_type !=1 AND ((duedate > ${startInterval} AND duedate < ${endInterval}) OR (startdate IS NOT NULL AND startdate < ${endInterval} AND startdate != ${distantPastInterval})))`
                            sql += ` OR `
                            sql += `(task_type = 1 AND ((project_duedate > ${startInterval} AND project_duedate < ${endInterval}) OR (project_startdate IS NOT NULL AND project_startdate < ${endInterval} AND project_startdate != ${distantPastInterval})))`
                            sql += `)`
                        } else {
                            sql += `(`
                            sql += `(task_type !=1 AND (duedate > ${startInterval} AND duedate < ${endInterval}))`
                            sql += ` OR `
                            sql += `(task_type = 1 AND (project_duedate > ${startInterval} AND project_duedate < ${endInterval}))`
                            sql += `)`
                        }
                    } else {
                        if (isStartDate) {
                            sql += `(`
                            sql += `(task_type != 1 AND `
                        }
                        sql += `${columnName} > ${startInterval} AND ${columnName} < ${endInterval}`
                        if (isStartDate) {
                            sql += `)`
                            
                            sql += ` OR `
                            sql += `(task_type = 1 AND (${projectColumnName} > ${startInterval} AND ${projectColumnName} < ${endInterval}))`
                            sql += `)`
                        }
                    }
                } else {
                    // NOT
                    if (isDueDate || isStartDate) {
                        sql += `(`
                        sql += `(task_type != 1 AND `
                    } else if (isCompletionDate) {
                        sql += `((${columnName} IS NOT NULL AND ${columnName} != 0) AND (`
                    }
                    sql += `${columnName} < ${startInterval} OR ${columnName} > ${endInterval}`
                    if (isDueDate || isStartDate) {
                        sql += `)`
                        
                        sql += ` OR `
                        sql += `(task_type=1 AND (${projectColumnName} < ${startInterval} OR ${projectColumnName} > ${endInterval}))`
                        sql += `)`
                    } else if (isCompletionDate) {
                        sql += `))`
                    }
                }
                break;
            }
        }

        sql += `)`
        return sql
    }

    static sqlForLocationFilter(hasLocation) {
        if (hasLocation) {
            return `(LENGTH(location_alert) > 0)`
        } else {
            return `((location_alert IS NULL) OR (LENGTH(location_alert) = 0))`
        }
    }

    static sqlForTextFilter(columnName, filterValue) {
        if (!columnName || !filterValue || !filterValue.comparator || !filterValue.searchTerms || filterValue.searchTerms.length == 0) {
            return ''
        }

        let sql = `(`
        filterValue.searchTerms.forEach((searchTerm, idx) => {
            if (idx > 0) {
                if (filterValue.comparator == constants.SmartListComparatorType.Or) {
                    sql += ` OR `
                } else {
                    sql += ` AND `
                }
            }

            // NOTE: For Todo 9.0, we are intentionally ignoring the "contains" BOOL
            // on a Smart List Search Term
            sql += `${columnName} LIKE '%${searchTerm.text}%'`
        })

        sql += `)`
        return sql
    }

    static sqlForPriorityFilter(filterValue) {
        if (!filterValue || filterValue.length == 0) {
            return ''
        }

        let sql = ''
        
        if (filterValue.indexOf(constants.SmartListPriorityFilterType.None) >= 0) {
            sql += `((task_type!=1 AND priority = ${constants.TaskPriority.None}) OR 
                (task_type=1 AND project_priority = ${constants.TaskPriority.None}) OR 
                (task_type!=1 AND priority > ${constants.TaskPriority.Low}) OR 
                (task_type=1 AND project_priority > ${constants.TaskPriority.Low}))`
        }
        
        if (filterValue.indexOf(constants.SmartListPriorityFilterType.Low) >= 0) {
            if (sql.length > 0) {
                sql += ` OR `
            }
            sql += `((task_type!=1 AND priority = ${constants.TaskPriority.Low}) OR 
                (task_type=1 AND project_priority = ${constants.TaskPriority.Low}))`
        }
        
        if (filterValue.indexOf(constants.SmartListPriorityFilterType.Medium) >= 0) {
            if (sql.length > 0) {
                sql += ` OR `
            }
            sql += `((task_type!=1 AND priority = ${constants.TaskPriority.Medium}) OR 
                (task_type=1 AND project_priority = ${constants.TaskPriority.Medium}))`
        }
        
        if (filterValue.indexOf(constants.SmartListPriorityFilterType.High) >= 0) {
            if (sql.length > 0) {
                sql += ` OR `
            }
            sql += `((task_type!=1 AND priority = ${constants.TaskPriority.High}) OR 
                (task_type=1 AND project_priority = ${constants.TaskPriority.High}))`
        }

        return `(${sql})`
    }

    static sqlForRecurrenceFilter(hasRecurrence) {
        if (hasRecurrence) {
            return `(recurrence_type > 0)`
        } else {
            return `(recurrence_type = 0)`
        }
    }

    static sqlForStarredFilter(starred) {
        if (starred) {
            return `((task_type!=1 AND starred != 0) OR (task_type=1 AND project_starred != 0))`
        } else {
            return `((task_type!=1 AND (starred IS NULL OR starred = 0)) OR (task_type=1 AND (project_starred IS NULL OR project_starred = 0)))`
        }
    }

    static sqlForTaskTypeFilter(filterValue) {
        const hasNormal = filterValue.find(val => val == constants.SmartListTaskTypeFilterType.Normal) != null
        const hasProject = filterValue.find(val => val == constants.SmartListTaskTypeFilterType.Project) != null
        const hasChecklist = filterValue.find(val => val == constants.SmartListTaskTypeFilterType.Checklist) != null

        if (!hasNormal && !hasChecklist && !hasProject) {
            return ''
        }

        let sql = ``
        if (hasNormal) {
             sql += `((task_type != ${constants.TaskType.Project}) AND (task_type != ${constants.TaskType.Checklist}))`
        }

        if (hasProject) {
            const projectSql = `(task_type = ${constants.TaskType.Project})`
            sql += sql.length > 0 ? ` OR ${projectSql}` : projectSql
        }

        if (hasChecklist) {
            const checklistSql = `(task_type = ${constants.TaskType.Checklist})`
            sql += sql.length > 0 ? ` OR ${checklistSql}` : checklistSql
        }

        return `(${sql})`
    }

    static sqlForTagFilter(filterValue) {
        if (!filterValue.tags || filterValue.tags.length == 0) {
            return ''
        }

        const firstTag = filterValue.tags[0]
        let sql = ``
        if (firstTag && firstTag == 'Any Tag') {
            sql += `EXISTS (SELECT * FROM tdo_tag_assignments AS assignments WHERE assignments.taskid = tasks.taskid)`
        }

        else if (firstTag && firstTag == 'No Tag') {
            sql += `NOT EXISTS (
                SELECT * 
                FROM tdo_tag_assignments AS assignments 
                WHERE assignments.taskid = tasks.taskid
            )`
        }

        else {
            const comparator = filterValue.comparator == "and" ? 'AND' : 'OR'
            const tagsFilter = filterValue.tags.reduce((accum, tagName) => {
                const existsClause = `EXISTS (
                    SELECT * 
                    FROM tdo_tag_assignments AS assignments 
                        JOIN tdo_tags AS tags ON assignments.tagid = tags.tagid
                    WHERE assignments.taskid = tasks.taskid 
                        AND tags.name = '${tagName}'
                )`
                return accum ? `${accum} ${comparator} (${existsClause})` : `(${existsClause})`
            }, null)

            sql += `(${tagsFilter})`
        }

        return `(${sql})`
    }

    static dateForPeriodAndValue(period, value, userTimeZone) {
        if (!userTimeZone) {
            userTimeZone = "Etc/GMT"
        }
        const theDate = moment.tz(Date.now(), userTimeZone)
        if (value > 0) {
            return theDate.add(value, period).toDate()
        } else {
            return theDate.subtract(Math.abs(value), period).toDate()
        }
    }

    static tasksForSmartList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const smartList = params.smartList != undefined ? params.smartList : null
        const selectedDates = params.selectedDates ? params.selectedDates : []
        const completedOnly = params.completedOnly != undefined ? params.completedOnly : false
        const ignoreCompletedFilter = params.ignoreCompletedFilter != undefined ? params.ignoreCompletedFilter : false
        const pageSize = params.pageSize != undefined ? params.pageSize : constants.defaultPagedTasks
        const offset = params.offset != undefined ? params.offset : 0
        const userTimeZone = params.userTimeZone != undefined ? params.userTimeZone : constants.defaultTimeZone
        const sortType = params.sortType != undefined ? params.sortType : constants.SortType.DatePriorityAlpha

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!smartList) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing smartList.'))))
            return
        }

        // Check the completedOnly query param vs. what the actual smart list setting
        // is to enforce the rules. If there's a mismatch, return an empty result set.
        if (smartList.completed_tasks_filter != undefined && smartList.completed_tasks_filter.length > 0) {
            try {
                const filterValue = JSON.parse(smartList.completed_tasks_filter)
                const filterType = filterValue.type

                if ((filterType && filterType == constants.SmartListCompletedTasksFilterType.Active && completedOnly == true)
                    || (filterType && filterType == constants.SmartListCompletedTasksFilterType.Completed && completedOnly == false)) {
                    // There's a mismatch and we should return no tasks
                    completion(null, [])
                    return
                }
            } catch (error) {
                // Ignore this error, but log it
                logger.debug(`Error parsing a smart list's (${smartList.listid}) completed_tasks_filter: ${error}`)
            }
        }

        const usingStartDates = !smartList.excludesStartDates()
        
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
                // Read the user's INBOX ID so we can replace the hard-coded
                // version with the real inbox ID when filtering.
                const inboxParams = {
                    userid: smartList.userid,
                    dbConnection: connection
                }
                TCUserSettingsService.getUserInboxID(inboxParams, function(err, userInboxID) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userInboxID)
                    }
                })
            },
            function(connection, userInboxID, callback) {
                // Read the user's lists so they can be used to retrieve tasks
                const listParams = {
                    userid: smartList.userid,
                    dbConnection: connection
                }
                const TCListService = require('./tc-list-service')
                TCListService.listIDsForUser(listParams, function(err, listids) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userInboxID, listids)
                    }
                })
            },
            function(connection, userInboxID, listids, callback) {
                // Read the global filtered lists so it can be passed into the method
                // that filters tasks later.
                const filterParams = {
                    userid: smartList.userid,
                    dbConnection: connection
                }
                TCUserSettingsService.getFilteredLists(filterParams, function(err, filteredLists) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        // Since we're on the server, we need to just remove
                        // any filtered lists from the listids.
                        const jsonFilter = smartList.jsonFilter()

                        let excludedListsFromFilter = []
                        if (smartList.excluded_list_ids && smartList.excluded_list_ids.length > 0) {
                            const excludedListIDs = smartList.excluded_list_ids.replace(constants.ServerInboxId, userInboxID)
                            excludedListsFromFilter = excludedListIDs.split(/\s*,\s*/)
                        }

                        let excludedListsFromSettings = []
                        if (filteredLists && filteredLists.length > 0) {
                            const excludedListIDs = filteredLists.replace(constants.ServerInboxId, userInboxID)
                            excludedListsFromSettings = excludedListIDs.split(/\s*,\s*/)
                        }

                        const listsToUse = listids.filter(listid => {
                            return excludedListsFromFilter.find(aListId => aListId == listid) == undefined
                                    && excludedListsFromSettings.find(aListId => aListId == listid) == undefined
                        })

                        callback(null, connection, listsToUse)
                    }
                })
            },
            function(connection, listids, callback) {
                let whereSQL = ''

                // Search for tasks only from the specified listids
                whereSQL += ` (`
                listids.forEach((listid, idx) => {
                    if (idx > 0) {
                        whereSQL += ` OR `
                    }
                    whereSQL += `listid = '${listid}'`
                })
                whereSQL += `)`

                // Prevent deleted tasks from ever appearing in a smart list
                whereSQL += ' AND ((deleted IS NULL) OR (deleted = 0))'

                if (completedOnly) {
                    if (ignoreCompletedFilter) {
                        // This is used if we are fetching additional completed tasks when
                        // the user taps the "More completed tasks" button that don't auto-
                        // matically come back from the first view of completed tasks
                        // because of a potential setting. For example, if there are
                        // completed tasks that are within 2 weeks, but the completed
                        // setting for the smart list is only for 3 days.
                        const completedSQL = TCSmartListService.sqlForCompletedTaskPeriod(constants.SmartListCompletedTasksPeriod.TwoWeeks, userTimeZone)
                        whereSQL += ` AND completiondate IS NOT NULL AND completiondate != 0 and (${completedSQL})`
                    } else {
                        const completedSQL = TCSmartListService.sqlForCompletedTasksSettingForSmartList(smartList, userTimeZone)
                        if (completedSQL) {
                            whereSQL += ` AND (${completedSQL})`
                        }
                    }
                } else {
                    const completedTasksFilterType = smartList.jsonFilter() && smartList.jsonFilter().completedTasks && smartList.jsonFilter().completedTasks.type ? smartList.jsonFilter().completedTasks.type : constants.SmartListCompletedTasksFilterType.All
                    if (completedTasksFilterType == constants.SmartListCompletedTasksFilterType.Completed) {
                        // The user ONLY wants to see completed tasks, so don't return any
                        // tasks from this call.
                        callback(null, connection, null)
                        return
                    }

                    // whereSQL += ` AND (completiondate IS NULL OR completiondate = 0)`;
                }

                const smartListSQLFilter = TCSmartListService.sqlWhereStatementForSmartList(smartList, userTimeZone)
                if (smartListSQLFilter && smartListSQLFilter.length > 0) {
                    whereSQL += ` AND (${smartListSQLFilter})`
                }

                let sql = ''
                if (!smartList.showSubtasks()) {
                    // When not exposing subtasks at the top level, the following code
                    // ensures that the project appears even though it may not itself have
                    // something that matches, but one of its subtasks does. This breaks
                    // the SQL down and hopefully improves performance by doing it this way.
                    // Todo 8 did something similar. This is an attempt to do the same.
                    // let childSQL = `SELECT parentid FROM ${tableName} WHERE ${whereSQL} AND (parentid IS NOT NULL AND parentid != '')`
                    let childSQL = `
                        SELECT parentid 
                        FROM ${completedOnly ? 'tdo_completed_tasks' : 'tdo_tasks'} AS tasks 
                        WHERE ${whereSQL} 
                        AND (parentid IS NOT NULL AND parentid != '')
                    `
                    let parentIdList = ''

                    connection.query(childSQL, [], function(err, results) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                        } else {
                            // Step through the results and build tasks
                            if (results && results.rows && results.rows.length > 0) {
                                results.rows.forEach((row) => {
                                    const parentId = row.parentid
                                    if (parentIdList.length > 0) {
                                        parentIdList += ', '
                                    }
                                    parentIdList += `\"${parentId}\"`
                                })
                            }

                            if (parentIdList.length > 0) {
                                whereSQL += ` OR (task_type=1 AND taskid IN (${parentIdList}))`
                            }
                            sql += ` (parentid IS NULL OR parentid = '') AND ${whereSQL}`
                            
                            callback(null, connection, sql)
                        }
                    })
                } else {
                    sql += whereSQL
                    callback(null, connection, sql)
                }
            },
            function(connection, sql, callback) {
                if (sql == null) {
                    // Previous function indicating we shouldn't return any tasks
                    callback(null, connection, [])
                } else {
                    if (completedOnly) {
                        // Since we're only after completed tasks, just use the tdo_completed_tasks table
                        sql = `SELECT * FROM tdo_completed_tasks AS tasks WHERE ${sql}`
                        sql += ` ORDER BY completiondate DESC,sort_order,priority,name`
                    } else {
                        // We're only after active tasks, so don't look in the tdo_completed_tasks table at all
                        sql = `SELECT * FROM tdo_tasks AS tasks WHERE ${sql}`
                        const sortOrderString = TCSmartListService.sortOrderStringForSortType(sortType, userTimeZone)
                        sql += sortOrderString
                    }

                    sql += ` LIMIT ${pageSize} OFFSET ${offset}`

                    connection.query(sql, [], function(err, results) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                        } else {
                            const tasks = []
                            if (results && results.rows && results.rows.length > 0) {
                                results.rows.forEach((row) => {
                                    const aTask = new TCTask()
                                    aTask.configureWithProperties(row)
                                    tasks.push(aTask)
                                })
                            }
                            callback(null, connection, tasks)
                        }
                    })
                }
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
                        `Could not get tasks for smart list with due date sort type (${smartList.listid}).`))))
            } else {
                completion(null, tasks)
            }
        })
    }

    static sortOrderStringForSortType(sortType, userTimeZone) {
        let sortString = ` ORDER BY `

        // getTimezoneOffset returns in # of minutes, so multiply by 60 to convert to seconds
        const localTimeZoneOffset = moment.tz(Date.now(), userTimeZone).utcOffset() * 60
        const endTodayInterval = moment.tz(Date.now(), userTimeZone).endOf('day').unix()

        //We add 40371 here instead of 40370 to make tasks that are showing by start date sort after tasks showing by due date
        const dueDateSort = ` CASE WHEN duedate=0 THEN ${NO_DATE_SORT_VALUE} ELSE (duedate + (CASE 1 WHEN due_date_has_time THEN 0 ELSE (${localTimeZoneOffset} + 43170) END)) END`
        const startDateExpression = `CASE WHEN (startdate > 0 AND startdate IS NOT NULL AND ((startdate - ${localTimeZoneOffset} + 43170) < duedate) AND (duedate > ${endTodayInterval})) THEN (CASE WHEN (startdate > ${endTodayInterval}) THEN (startdate - ${localTimeZoneOffset} + 43171) ELSE (${endTodayInterval} + 1) END) ELSE ${dueDateSort} END`
        
        //If the task is sorting by start date, add a secondary sort by due date in case the start dates are equal
        const secondaryDateSort = `CASE WHEN (startdate > 0 AND startdate IS NOT NULL AND ((startdate - ${localTimeZoneOffset} + 43170) < duedate) AND (duedate > ${endTodayInterval})) THEN ${dueDateSort} ELSE 0 END`
                
        switch (sortType) {
            case constants.SortType.PriorityDateAlpha: {
                sortString += ` priority,${secondaryDateSort},${dueDateSort},sort_order,name`
                break;
            }
            case constants.SortType.Alphabetical: {
                sortString += ` name`
                break;
            }
            case constants.SortType.DatePriorityAlpha:
            default: {
                sortString += ` ${startDateExpression},${secondaryDateSort},priority,sort_order,name`
                break;
            }
        }

        return sortString
    }
}

module.exports = TCSmartListService
'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')

const db = require('./tc-database')
const begin = require('any-db-transaction')

const constants = require('./constants')
const errors = require('./errors')

const TCTag = require('./tc-tag').TCTag
const TCTagAssignment = require('./tc-tag').TCTagAssignment
const TCTask = require('./tc-task')

const TCListMembershipService = require('./tc-list-membership-service')
const TCTaskUtils = require('./tc-task-utils')

class TCTagService {
    static getTagsForTask(params, completion) {
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
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTagService.getTagsForTask() Missing taskid.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTagService.getTagsForTask() Missing userid.'))))
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
                let sql = `
                    SELECT tags.* 
                    FROM tdo_tags tags
                        INNER JOIN tdo_tag_assignments assignments ON assignments.tagid = tags.tagid
                    WHERE
                        assignments.taskid = ?
                `
                connection.query(sql, [taskid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        const tags = []
                        for (const row of results.rows) {
                            tags.push(new TCTag(row))
                        }
                        callback(null, connection, tags)
                    }
                })
            },
        ], 
        function(err, connection, tags) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get tags for task (${taskid}).`))))
            } else {
                completion(null, tags)
            }
        })
    }

    static getAllTags(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if(!userid) {
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
            function(connection, callback) {
                const getUserTasksTableSQL = `(
                        SELECT taskid 
                        FROM tdo_tasks 
                            INNER JOIN tdo_list_memberships ON tdo_tasks.listid = tdo_list_memberships.listid 
                        WHERE 
                            userid = ?
                    UNION ALL
                        SELECT taskid 
                        FROM tdo_completed_tasks 
                            INNER JOIN tdo_list_memberships ON tdo_completed_tasks.listid = tdo_list_memberships.listid 
                        WHERE 
                            userid = ?
                ) AS usertasks`

                const getUserTagsTableSQL = `
                    tdo_tag_assignments 
                    INNER JOIN ${getUserTasksTableSQL} ON tdo_tag_assignments.taskid = usertasks.taskid`

                const sql = `
                    SELECT DISTINCT tdo_tags.tagid as tagid, tdo_tags.name as name, COUNT(tdo_tag_assignments.tagid) as count
                    FROM (tdo_tags 
                        INNER JOIN (${getUserTagsTableSQL}) ON tdo_tags.tagid = tdo_tag_assignments.tagid)
                    GROUP BY tdo_tags.tagid
                    ORDER BY tdo_tags.name
                    `

                connection.query(sql, [userid, userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        const tags = []
                        for (const row of results.rows) {
                            tags.push(new TCTag(row))
                        }
                        callback(null, connection, tags)
                    }
                })
            },
        ], 
        function(err, connection, tags) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get tags for userid (${userid}).`))))
            } else {
                completion(null, tags)
            }
        })
    }

    static createTag(params, completion) {
        const name = params.name && typeof params.name == 'string' ? params.name.trim() : null
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if(!name) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing tag name.'))))
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
                const tag = new TCTag({
                    name : name
                })
                tag.add(connection, (err, newTag) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding new tag (${name}): ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, newTag)
                    }
                })
            }
        ], 
        function(err, connection, tag) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not create tag.`))))
            } else {
                completion(null, tag)
            }
        })
    }

    static getTag(params, completion) {
        const tagid = params.tagid ? params.tagid : null
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if(!tagid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing tagid.'))))
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
                const tag = new TCTag({
                    tagid : tagid
                })
                tag.read(connection, (err, newTag) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting tag (${tagid}): ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, newTag)
                    }
                })
            }
        ], 
        function(err, connection, tag) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get tag ${tagid}.`))))
            } else {
                completion(null, tag)
            }
        })
    }

    static updateTag(params, completion) {
        const tagid = params.tagid ? params.tagid : null
        const name = params.name ? params.name : null
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if(!tagid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing tagid.'))))
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
                const tag = new TCTag({
                    tagid : tagid,
                    name  : name
                })
                tag.update(connection, (err, newTag) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating tag (${tagid}): ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, newTag)
                    }
                })
            }
        ], 
        function(err, connection, tag) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not update tag ${tagid}.`))))
            } else {
                completion(null, tag)
            }
        })
    }

    static getTaskIDsForTag(params, completion) {
        const tagid = params.tagid ? params.tagid : null
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if(!tagid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Unable to get taskids for tag: missing tagid.'))))
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
                const sql = `
                    SELECT taskid
                    FROM tdo_tag_assignments
                    WHERE tagid = ?
                `

                connection.query(sql, [tagid], (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting taskids for tag (${tagid}): ${err.message}`))), transaction)
                    }
                    else {
                        const rows = result.rows ? result.rows : []
                        callback(null, connection, rows.map(row => row.taskid))
                    }
                })
            }
        ],
        function(err, connection, taskids){
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get taskids for tag (${tagid}).`))))
            } else {
                completion(null, taskids)
            }
        })
    }

    static deleteTag(params, completion) {
        const tagid = params.tagid ? params.tagid : null
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection

        if(!tagid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing tagid.'))))
            return
        }

        async.waterfall([
            function(callback) {
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
                TCTagService.getTaskIDsForTag({ tagid: tagid, dbConnection: transaction }, (err, taskids) => {
                    if (err) {
                        callback(err)
                    }
                    else {
                        callback(null, transaction, taskids)
                    }
                })
            },
            function(transaction, taskids, callback) {

                if (taskids.length == 0) {
                    callback(null, transaction)
                    return
                }

                // We need to update all the task timestamps that had this tag
                const timestamp = Math.floor(Date.now() / 1000)
                const sqlParams = [timestamp].concat(taskids)
                let sql = `UPDATE tdo_tasks SET timestamp = ? WHERE `

                taskids.forEach((taskid, index) => {
                    if (index > 0) {
                        sql += ` OR `
                    }
                    sql += `taskid = ?`
                })

                logger.debug(sql)
                logger.debug(sqlParams)

                transaction.query(sql, sqlParams, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating task timestamps when deleting tag (${tagid}): ${err.message}`))), transaction)
                    }
                    else {
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                const sql = `
                    DELETE FROM tdo_tag_assignments WHERE tagid = ?
                `

                transaction.query(sql, [tagid], (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error deleting tag (${tagid}): ${err.message}`))), transaction)
                    }
                    else {
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                const tag = new TCTag({
                    tagid : tagid
                })
                tag.delete(transaction, (err, newTag) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error deleting tag (${tagid}): ${err.message}`))), transaction)
                    }
                    else {
                        callback(null, transaction, newTag)
                    }
                })
            }
        ], 
        function(err, transaction, tag) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not delete tag.`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not delete tag. Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, tag)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                }
                else {
                    completion(null, tag)
                }
            }
        })
    }

    static assignTag(params, completion) {
        const tagid  = params.tagid ? params.tagid : null
        const taskid = params.taskid ? params.taskid : null
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if(!tagid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing tagid.'))))
            return
        }

        if(!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing taskid.'))))
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
                const tagAssignment = new TCTagAssignment({
                    tagid : tagid,
                    taskid : taskid
                })
                tagAssignment.add(connection, (err, newTagAssignment) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error assigning tag (${tagid}) to task (${taskid}): ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, newTagAssignment)
                    }
                })
            },
            function(connection, tagAssignment, callback) {
                const task = new TCTask({ taskid : taskid })

                // Updates the task timestamp
                task.update(connection, (err, updatedTask) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating task timestamp (${taskid}): ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, tagAssignment)
                    }
                })
            }
        ], 
        function(err, connection, tagAssignment) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not assign tag (${tagid}) to task (${taskid}).`))))
            } else {
                completion(null, tagAssignment)
            }
        })
    }

    static removeTagAssignment(params, completion) {
        const tagid  = params.tagid ? params.tagid : null
        const taskid = params.taskid ? params.taskid : null
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if(!tagid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing tagid.'))))
            return
        }

        if(!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing taskid.'))))
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
                const tagAssignment = new TCTagAssignment({
                    tagid : tagid,
                    taskid : taskid
                })
                tagAssignment.delete(connection, (err, newTagAssignment) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error deleting tag assignment (${tagid}) to task (${taskid}): ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, newTagAssignment)
                    }
                })
            },
            function(connection, tagAssignment, callback) {
                const task = new TCTask({ taskid : taskid })

                // Updates the task timestamp
                task.update(connection, (err, updatedTask) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating task timestamp (${taskid}): ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, tagAssignment)
                    }
                })
            }
        ], 
        function(err, connection, tagAssignment) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not delete tag assignment (${tagid}) to task (${taskid}).`))))
            } else {
                completion(null, tagAssignment)
            }
        })
    }

    static getTagWithName(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const tagName = params.tagName && typeof params.tagName == 'string' ? params.tagName.trim() : null

        if (!tagName) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing tagName.'))))
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
                const sql = `SELECT * FROM tdo_tags WHERE name = ?`
                connection.query(sql, [tagName], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `DB Error: ${err}`))), connection)
                    } else {
                        if (result.rows && result.rows.length > 0) {
                            const tag = new TCTag(result.rows[0])
                            callback(null, connection, tag)
                        } else {
                            callback(null, connection, null)
                        }
                    }
                })
            }
        ], 
        function(err, connection, tag) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get tag by name ${tagName}.`))))
            } else {
                completion(null, tag)
            }
        })
    }

    static removeAllTagAssignmentsFromTask(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
            return
        }
        if (!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing tagid.'))))
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
                const getParams = {
                    userid: userid,
                    taskid: taskid,
                    dbConnection: connection
                }
                TCTagService.getTagsForTask(getParams, function(err, tags) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, tags)
                    }
                })
            },
            function(connection, tags, callback) {
                async.eachSeries(tags,
                    function(tag, eachCallback) {
                        const removeParams = {
                            tagid: tag.tagid,
                            taskid: taskid,
                            dbConnection: connection
                        }
                        TCTagService.removeTagAssignment(removeParams, function(removeErr, result) {
                            eachCallback(removeErr)
                        })
                    },
                    function(eachErr) {
                        callback(eachErr, connection)
                    }
                )
            }
        ], 
        function(err, connection) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            completion(null, true)
        })
    }
}

module.exports = TCTagService
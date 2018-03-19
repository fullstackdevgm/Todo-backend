'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')

const db = require('./tc-database')
const begin = require('any-db-transaction')
const moment = require('moment-timezone')

const constants = require('./constants')
const errors = require('./errors')

const TCAccount = require('./tc-account')
const TCComment = require('./tc-comment')
const TCChangeLogService = require('./tc-changelog-service')
const TCListMembershipService = require('./tc-list-membership-service')
const TCTaskUtils = require('./tc-task-utils')

class TCCommentService {
    static deleteAllCommentsForTask(params, completion) {
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
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCCommentService.deleteAllCommentsForTask() Missing taskid.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCCommentService.deleteAllCommentsForTask() Missing userid.'))))
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
                let sql = `UPDATE tdo_comments SET deleted=1, timestamp=? WHERE itemid=?`
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
                        `Could not delete comments for task (${taskid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static getCommentsForTask(params, completion) {
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
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing taskid.'))))
            return
        }
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
            function (connection, callback) {
                const sql = `
                    SELECT comments.*, users.first_name, users.last_name
                    FROM tdo_comments comments
                        INNER JOIN tdo_user_accounts users ON users.userid = comments.userid
                    WHERE
                        comments.itemid = ? AND (comments.deleted IS NULL OR comments.deleted = 0)
                `

                connection.query(sql, [taskid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        const commentsAndUsers = []
                        if (result) {
                            for (const row of result.rows) {
                                commentsAndUsers.push({
                                    comment : new TCComment(row),
                                    user : new TCAccount(row)
                                })
                            }
                        }
                        callback(null, connection, commentsAndUsers)
                    }
                })
            }
        ], 
        function(err, connection, results) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not get comments for task (${taskid}) for the user (${userid}).`))))
            } else {
                completion(null, results)
            }
        })
    }

    static getComment(params, completion) {
        const commentid = params.commentid && typeof params.commentid == 'string' ? params.commentid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if(!commentid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing commentid.'))))
            return
        }
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
            function (connection, callback) {
                const aComment = new TCComment({commentid : commentid})
                aComment.read(connection, (err, comment) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error retrieving comment: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, comment)
                    }
                })
            },
            function(connection, comment, callback) {
                // Get the list ID that belongs to the task
                const listParams = {
                    taskid: comment.itemid,
                    dbConnection: connection
                }
                TCTaskUtils.listIDForTask(listParams, function(err, listid) {
                    if (err) {
                        callback(err, connection)
                    } else if (listid) {
                        callback(null, connection, comment, listid)
                    } else {
                        callback(new Error(JSON.stringify(errors.taskNotFound)), connection)
                    }
                })
                
            },
            function(connection, comment, listid, callback) {
                // Verify that the user has access to this list
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
                            callback(null, connection, comment)
                        }
                    }
                })
            },
        ], 
        function(err, connection, comment) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not read a comment (${commentid}).`))))
            } else {
                completion(null, comment)
            }
        })
    }

    static createComment(params, completion) {
        const itemid = params.itemid && typeof params.itemid == 'string' ? params.itemid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if(!itemid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing itemid.'))))
            return
        }
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
                // Get the list ID that belongs to the task
                const listParams = {
                    taskid: itemid,
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
            function(connection, listid, callback) {
                // Verify that the user has access to this list
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
                            callback(null, connection, listid)
                        }
                    }
                })
            },
            function (connection, listid, callback) {
                const comment = new TCComment(params)
                comment.add(connection, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error creating comment: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, listid, comment, result)
                    }
                })
            },
            function(connection, listid, comment, result, callback) {
                const changeParams = {
                    listid: listid,
                    userid: userid,
                    itemid: comment.commentid,
                    itemName: comment.item_name,
                    itemType: constants.ChangeLogItemType.Comment,
                    changeType: constants.ChangeLogType.Add,
                    changeLocation: constants.ChangeLogLocation.API,
                    dbConnection: connection
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    if (err) {
                        logger.debug(`Error recording change to changelog during createComment(): ${err}`)
                    }
                    callback(null, connection, comment)
                })
            }
        ], 
        function(err, connection, comment) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not create the comment for (${itemid}).`))))
            } else {
                completion(null, comment)
            }
        })
    }

    static updateComment(params, completion) {
        const commentid = params.commentid && typeof params.commentid == 'string' ? params.commentid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        delete params.deleted // prevent setting the deleted flag from the update function

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if(!commentid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing commentid.'))))
            return
        }
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
                // Read the comment. If the comment returns, we know that the user
                // has access to update the comment.
                const getParams = {
                    commentid: commentid,
                    userid: userid,
                    dbConnection: connection
                }
                TCCommentService.getComment(getParams, function(err, comment) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.commentNotFound)), connection)
                    } else {
                        callback(null, connection, comment)
                    }
                })
            },
            function (connection, comment, callback) {
                const aComment = new TCComment(params)
                aComment.update(connection, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error updating comment: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, comment, result)
                    }
                })
            },
            function(connection, comment, result, callback) {
                // Read the listid of the task so we can update the changelog.
                const listParams = {
                    taskid: comment.itemid,
                    dbConnection: connection
                }
                TCTaskUtils.listIDForTask(listParams, function(err, listid) {
                    if (err) {
                        callback(err, connection)
                    } else if (listid) {
                        callback(null, connection, comment, listid, result)
                    } else {
                        callback(new Error(JSON.stringify(errors.taskNotFound)), connection)
                    }
                })
            },
            function(connection, comment, listid, result, callback) {
                const changeParams = {
                    listid: listid,
                    userid: userid,
                    itemid: comment.commentid,
                    itemName: comment.item_name,
                    itemType: constants.ChangeLogItemType.Comment,
                    changeType: constants.ChangeLogType.Modify,
                    changeLocation: constants.ChangeLogLocation.API,
                    dbConnection: connection
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, changeLogResult) {
                    if (err) {
                        logger.debug(`Error recording change to changelog during updateComment(): ${err}`)
                    }
                    callback(null, connection, result)
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
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not authenticate the user (${username}).`))))
            } else {
                completion(null, result)
            }
        })
    }

    static deleteComment(params, completion) {
        const commentid = params.commentid && typeof params.commentid == 'string' ? params.commentid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if(!commentid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing commentid.'))))
            return
        }
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
                // Read the comment. If the comment returns, we know that the user
                // has access to the comment and we can delete it accordingly.
                const getParams = {
                    commentid: commentid,
                    userid: userid,
                    dbConnection: connection
                }
                TCCommentService.getComment(getParams, function(err, comment) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.commentNotFound)), connection)
                    } else {
                        callback(null, connection, comment)
                    }
                })
            },
            function (connection, comment, callback) {
                const aComment = new TCComment({ commentid : commentid, deleted : 1 })
                aComment.update(connection, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error deleting comment: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, comment, result.deleted == 1)
                    }
                })
            },
            function(connection, comment, result, callback) {
                // Read the listid of the task so we can update the changelog.
                const listParams = {
                    taskid: comment.itemid,
                    dbConnection: connection
                }
                TCTaskUtils.listIDForTask(listParams, function(err, listid) {
                    if (err) {
                        callback(err, connection)
                    } else if (listid) {
                        callback(null, connection, comment, listid, result)
                    } else {
                        callback(new Error(JSON.stringify(errors.taskNotFound)), connection)
                    }
                })
            },
            function(connection, comment, listid, result, callback) {
                const changeParams = {
                    listid: listid,
                    userid: userid,
                    itemid: comment.commentid,
                    itemName: comment.item_name,
                    itemType: constants.ChangeLogItemType.Comment,
                    changeType: constants.ChangeLogType.Delete,
                    changeLocation: constants.ChangeLogLocation.API,
                    dbConnection: connection
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    if (err) {
                        logger.debug(`Error recording change to changelog during deleteComment(): ${err}`)
                    }
                    callback(null, connection, result)
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
                completion(new Error(JSON.stringify(errors.customError(errObj, `Error deleting a comment (${commentid}).`))))
            } else {
                completion(null, result)
            }
        })
    }
}

module.exports = TCCommentService

'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')

const db = require('./tc-database')
const begin = require('any-db-transaction')
const moment = require('moment-timezone')

const constants = require('./constants')
const errors = require('./errors')

const TCTaskUtils = require('./tc-task-utils')
const TCTaskNotification = require('./tc-task-notification')
const TCListMembershipService = require('./tc-list-membership-service')
const TCUserSettings = require('./tc-user-settings')

class TCTaskNotificationService {
    static deleteAllTaskNotificationsForTask(params, completion) {
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
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskNotificationService.deleteAllTaskNotificationsForTask() Missing taskid.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskNotificationService.deleteAllTaskNotificationsForTask() Missing userid.'))))
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
                let sql = `UPDATE tdo_task_notifications SET deleted=1, timestamp=? WHERE taskid=?`
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
                        `Could not delete task notifications for task (${taskid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static createNotificationsForRecurringTask(params, completion) {
        const originalTaskId = params.originalTaskId && typeof params.originalTaskId == 'string' ? params.originalTaskId.trim() : null
        const completedTaskId = params.completedTaskId && typeof params.completedTaskId == 'string' ? params.completedTaskId.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const offset = params.offset ? params.offset : 0

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if(!originalTaskId) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing originalTaskId.'))))
            return
        }
        if(!completedTaskId) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing completedTaskId.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
            return
        }
        if(!offset && !(offset === 0)) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing offset.'))))
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
                const notificationParams = {
                    taskid: originalTaskId,
                    userid: userid,
                    dbConnection: connection
                }
                TCTaskNotificationService.getNotificationsForTask(notificationParams, function(err, notifications) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        async.eachSeries(notifications,
                        function(notification, eachCallback) {
                            const newNotificationParams = new TCTaskNotification()
                            newNotificationParams.sound_name = notification.sound_name
                            newNotificationParams.triggerdate = notification.triggerdate
                            newNotificationParams.triggeroffset = notification.triggeroffset
                            newNotificationParams.taskid = completedTaskId
                            newNotificationParams.deleted = 1
                            newNotificationParams.userid = userid
                            newNotificationParams.dbConnection = connection
                            newNotificationParams.isPreauthorized = true

                            TCTaskNotificationService.createNotification(newNotificationParams, function(createErr, newNotification) {
                                if (createErr) {
                                    eachCallback(createErr)
                                } else {
                                    // Update the notification
                                    newNotification.triggerdate = notification.triggerdate + offset
                                    newNotification.dbConnection = connection
                                    newNotification.isPreauthorized = true
                                    newNotification.userid = userid
                                    TCTaskNotificationService.updateNotification(newNotification, function(updateErr, updatedNotification) {
                                        if (updateErr) {
                                            eachCallback(updateErr)
                                        } else {
                                            eachCallback()
                                        }
                                    })
                                }
                            })
                        },
                        function(err) {
                            if (err) {
                                callback(err, connection)
                            } else {
                                callback(null, connection)
                            }
                        })
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
                        `Could not create task notifications for task (${originalTaskId}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static userIsAuthorizedForNotification(params, completion) {
        let taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        // We have a gatekeeper of API Gateway that wouldn't pass this along,
        // but when we call this recursively, we can pass in preauthorization
        // so that we don't have to check to see if the user is authorized to
        // delete the specified taskid.
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

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
                if (!taskid && !isPreauthorized) {
                    const notification = new TCTaskNotification(params)
                    notification.read(connection, (err, result) => {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading notification: ${err.message}`))), connection)
                        }
                        else {
                            taskid = result.taskid
                            callback(null, connection)
                        }
                    })
                }
                else {
                    callback(null, connection)
                }
            },
            function(connection, callback) {
                if (isPreauthorized) {
                    callback(null, connection, null)
                } else {
                    // Read the listid of the task so we can make sure the
                    // user is authorized to access the task.
                    const listParams = {
                        taskid: taskid,
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
                    callback(null, connection, isPreauthorized)
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
                                callback(null, connection, isAuthorized)
                            }
                        }
                    })
                }
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
                        `Could not determine task notification authorization for task (${taskid}).`))))
            } else {
                completion(null, isAuthorized)
            }
        })
    }

    static updateListNotificationTimestamp(params, completion) {
        let taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        // We have a gatekeeper of API Gateway that wouldn't pass this along,
        // but when we call this recursively, we can pass in preauthorization
        // so that we don't have to check to see if the user is authorized to
        // delete the specified taskid.
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Update list notification timestamp: Missing userid.'))))
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
                if (!taskid) {
                    const notification = new TCTaskNotification(params)
                    notification.read(connection, (err, result) => {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading notification: ${err.message}`))), connection)
                        }
                        else {
                            taskid = result.taskid
                            callback(null, connection)
                        }
                    })
                }
                else {
                    callback(null, connection)
                }
            },
            function(connection, callback) {
                const listParams = {
                        taskid: taskid,
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
                const sql = `UPDATE tdo_lists SET notification_timestamp = ? WHERE listid = ?`

                connection.query(sql, [Math.floor(Date.now() / 1000), listid], (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error updating list notification timestamp: ${err.message}`))))
                    }
                    else {
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
                        `Could not determine task notification authorization for task (${taskid}).`))))
            } else {
                completion(null)
            }
        })
    }

    static getNotificationsForTask(params, completion) {
        const taskid = params.taskid ? params.taskid : null

        if(!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing taskid.'))))
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
                const authParams = {
                    dbConnection : connection,
                    userid : params.userid,
                    taskid : params.taskid,
                    isPreauthorized : params.isPreauthorized
                }
                TCTaskNotificationService.userIsAuthorizedForNotification(authParams, function(err, isAuthorized) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `2-Error confirming task notification for task (${taskid}) authorization for user (${params.userid}).`))), connection)
                    }
                    else {
                        // Don't bother with checking whether not isAuthorized is true. When false, it throws an error.
                        // If there is no error, isAuthorized will be true.

                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const sql = `
                    SELECT *
                    FROM tdo_task_notifications
                    WHERE 
                        taskid = ?
                    AND
                        (deleted IS NULL OR deleted = 0)
                `

                connection.query(sql, [taskid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        const rows = result.rows ? result.rows : []
                        callback(null, connection, rows.map((row) => new TCTaskNotification(row) ))
                    }
                })
            }
        ],
        function(err, connection, notifications) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not determine task notification authorization for task (${taskid}).`))))
            } else {
                completion(null, notifications)
            }
        })
    }

    static getNotificationsForUser(params, completion) {
        const userid = params.userid

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
                const settings = new TCUserSettings({userid : userid})
                settings.read(connection, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error reading user settings: ${err.message}`))))
                    }
                    else {
                        callback(null, connection, result.timezone)
                    }
                })
            },
            function(connection, timezone, callback) {
                if (!timezone) {
                    timezone = constants.defaultTimeZone
                }

                const now = moment.tz(timezone).unix()
                const timeWindow = moment.duration(2, 'hours').asSeconds() + now
                const sql = `
                    SELECT notifications.*
                    FROM tdo_task_notifications notifications
                        JOIN tdo_tasks tasks ON tasks.taskid = notifications.taskid
                        JOIN tdo_list_memberships memberships ON tasks.listid = memberships.listid
                    WHERE 
                        memberships.userid = ? AND
                        notifications.triggerdate - notifications.triggeroffset > ? AND
                        (notifications.triggerdate - notifications.triggeroffset) < ? AND
                        (notifications.deleted IS NULL OR notifications.deleted = 0)
                `

                connection.query(sql, [userid, now, timeWindow], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        const rows = result.rows ? result.rows : []
                        callback(null, connection, rows.map((row) => new TCTaskNotification(row) ))
                    }
                })
            }
        ],
        function(err, connection, notifications) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not retrive task notification for user (${userid}).`))))
            } else {
                completion(null, notifications)
            }
        })
    }

    static getNotification(params, completion) {
        const notificationid = params.notificationid ? params.notificationid : null

        // Only need to check for this one param here, other parameters will be checked in the authorization call.
        if (notificationid == null || !notificationid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing notificationid.'))))
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
                // Get the notification first, so that we can use its task id to check authorization.
                // If authorization fails, an error occurs and the notification is not returned, regardless.
                
                const notification = new TCTaskNotification(params)
                notification.read(connection, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error reading notification (${notificationid})`))), connection)
                    }
                    else {
                        callback(null, connection, result)
                    }
                })
            },
            function(connection, notification, callback) {
                const authParams = {
                    dbConnection : connection,
                    userid : params.userid,
                    taskid : notification.taskid,
                    isPreauthorized : params.isPreauthorized,
                    notificationid : notificationid
                }
                TCTaskNotificationService.userIsAuthorizedForNotification(authParams, function(err, isAuthorized) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error confirming task notification (${notificationid}) authorization for user (${params.userid}).`))), connection)
                    }
                    else {
                        // Don't bother with checking whether not isAuthorized is true. When false, it throws an error.
                        // If there is no error, isAuthorized will be true.

                        callback(null, connection, notification)
                    }
                })
            }
        ],
        function(err, connection, notification){
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not determine task notification authorization (${notificationid}).`))))
            } else {
                completion(null, notification)
            }
        })
    }
    
    static createNotification(params, completion) {
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

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
                const authParams = {
                    dbConnection : connection,
                    userid : userid,
                    taskid : params.taskid,
                    isPreauthorized : params.isPreauthorized
                }
                TCTaskNotificationService.userIsAuthorizedForNotification(authParams, function(err, isAuthorized) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error confirming task notification for task (${taskid}) authorization for user (${params.userid}).`))), connection)
                    }
                    else {
                        // Don't bother with checking whether not isAuthorized is true. When false, it throws an error.
                        // If there is no error, isAuthorized will be true.

                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const notification = new TCTaskNotification(params)
                notification.add(connection, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error creating notification for task (${taskid})`))), connection)
                    }
                    else {
                        callback(null, connection, result)
                    }
                })
            },
            function(connection, notification, callback) {
                params.dbConnection = connection
                TCTaskNotificationService.updateListNotificationTimestamp(params, (err) => {
                    err ? callback(err, connection) : callback(null, connection, notification)
                })
            }
        ],
        function(err, connection, notification){
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not create task notification for task (${taskid}).`))))
            } else {
                completion(null, notification)
            }
        })
    }

    static updateNotification(params, completion) {
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const notificationid = params.notificationid && typeof params.notificationid == 'string' ? params.notificationid.trim() : null

        // prevent the deleted field from being changed in the update call
        delete params.deleted

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
                const authParams = {
                    dbConnection : connection,
                    userid : params.userid,
                    taskid : params.taskid,
                    notificationid : notificationid,
                    isPreauthorized : params.isPreauthorized
                }
                TCTaskNotificationService.userIsAuthorizedForNotification(authParams, function(err, isAuthorized) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error confirming task notification (${notificationid}) authorization for user (${params.userid}).`))), connection)
                    }
                    else {
                        // Don't bother with checking whether not isAuthorized is true. When false, it throws an error.
                        // If there is no error, isAuthorized will be true.

                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const notification = new TCTaskNotification(params)
                notification.update(connection, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error creating notification (${notificationid})`))), connection)
                    }
                    else {
                        callback(null, connection, result)
                    }
                })
            },
            function(connection, notification, callback) {
                params.dbConnection = connection
                TCTaskNotificationService.updateListNotificationTimestamp(params, (err) => {
                    err ? callback(err, connection) : callback(null, connection, notification)
                })
            }
        ],
        function(err, connection, notification){
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not update task notification (${notificationid}).`))))
            } else {
                completion(null, notification)
            }
        })
    }

    static updateNotificationsForTask(params, completion) {
        // const notificationParams = {
        //     taskid: subtask.taskid,
        //     duedate: originalSubtaskDueDate,
        //     dbConnection: transaction
        // }

        if (!params) {
            completion(errors.create(errors.missingParameters, 'Missing all parameters in updateNotificationsFortask'))
            return
        }

        const userid = params.userid ? params.userid : null
        const taskid = params.taskid ? params.taskid : null

        if (!userid) {
            completion(errors.create(errors.missingParameters, 'Missing userid in updateNotificationsFortask'))
            return
        }

        if (!taskid) {
            completion(errors.create(errors.missingParameters, 'Missing taskid in updateNotificationsFortask'))
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
                            next(errors.create(errors.databaseError, `Error beginning a database transaction: ${err.message}`))
                            return
                        } 
                        next(null, transaction)
                    })
                })
            },
            function(transaction, next) {
                const getParams = {
                    userid : userid,
                    taskid : taskid,
                    dbConnection : transaction
                }
                TCTaskNotificationService.getNotificationsForTask(getParams, (err, notifications) => {
                    next(err, transaction, notifications)
                })
            },
            function(transaction, notifications, next) {
                async.eachSeries(notifications, (notification, nextEach) => {
                    const updateParams = Object.assign({}, notification, params, { dbConnection: transaction })
                    TCTaskNotificationService.updateNotification(updateParams, (err, result) => {
                        next(err)
                    })
                },
                (err) => {
                    next(err)
                })
            }
        ],
        function(err, transaction) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not process recurrence for subtasks of project (${originalProject.taskid}).`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not process recurrence for subtasks of project (${originalProject.taskid}). Database commit failed: ${err.message}`))))
                            } else {
                                completion(null)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null)
                }
            }
        })
    }

    static deleteNotification(params, completion) {
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const notificationid = params.notificationid && typeof params.notificationid == 'string' ? params.notificationid.trim() : null

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
                const authParams = {
                    dbConnection : connection,
                    userid : params.userid,
                    taskid : params.taskid,
                    notificationid : notificationid,
                    isPreauthorized : params.isPreauthorized
                }
                TCTaskNotificationService.userIsAuthorizedForNotification(authParams, function(err, isAuthorized) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error confirming task notification (${notificationid}) authorization for user (${params.userid}).`))), connection)
                    }
                    else {
                        // Don't bother with checking whether not isAuthorized is true. When false, it throws an error.
                        // If there is no error, isAuthorized will be true.

                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                params.deleted = 1
                const notification = new TCTaskNotification(params)
                notification.update(connection, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error creating notification (${notificationid})`))), connection)
                    }
                    else {
                        callback(null, connection, result)
                    }
                })
            },
            function(connection, notification, callback) {
                params.dbConnection = connection
                TCTaskNotificationService.updateListNotificationTimestamp(params, (err) => {
                    err ? callback(err, connection) : callback(null, connection, notification)
                })
            }
        ],
        function(err, connection, notification){
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not delete task notification (${notificationid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static getAllDirtyTaskNotifications(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskNotificationService.getAllDirtyTaskNotifications().`))))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskNotificationService.getAllDirtyTaskNotifications() Missing userid parameter.`))))
            return
        }
logger.debug(`getAllDirtyTaskNotifications() 0`)

        const modifiedAfterDate = params.modifiedAfterDate != undefined ? params.modifiedAfterDate : null

        let sql = `SELECT * FROM tdo_task_notifications WHERE dirty > 0`

        if (modifiedAfterDate) {
logger.debug(`getAllDirtyTaskNotifications() 3`)
            sql += ` AND timestamp > ${modifiedAfterDate}`
        }
     
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        const notifications = []

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
logger.debug(`getAllDirtyTaskNotifications() 4: ${sql}`)
                connection.query(sql, [], function(err, results) {
                    if (err) {
logger.debug(`getAllDirtyTaskNotifications() 5`)
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
logger.debug(`getAllDirtyTaskNotifications() 6: ${JSON.stringify(results)}`)
                        if (results.rows) {
                            for (const row of results.rows) {
                                notifications.push(new TCTaskNotification(row))
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
logger.debug(`getAllDirtyTaskNotifications() 7`)
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Error while looking for a dirty task notifications.`))))
            } else {
logger.debug(`getAllDirtyTaskNotifications() 8`)
                completion(null, notifications)
            }
        })
    }

    static getNotificationForSyncId(params, completion) {
logger.debug(`getNotificationForSyncId() 0`)
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskNotificationService.getNotificationForSyncId().`))))
            return
        }
        
        params.userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        params.syncid = params.syncid && typeof params.syncid == 'string' ? params.syncid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!params.userid || params.userid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskNotificationService.getNotificationForSyncId() : Missing userid.'))))
            return
        }
        if (!params.syncid || params.syncid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskNotificationService.getNotificationForSyncId() : Missing syncid.'))))
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
logger.debug(`getNotificationForSyncId() 1`)
                let notificationid = null
                const sql = `SELECT notificationid FROM tdo_task_notifications WHERE sync_id = ?`
                connection.query(sql, [params.syncid], function(err, result) {
                    if (err) {
logger.debug(`getNotificationForSyncId() 3`)
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                        `Error running query: ${err.message}`))), connection)
                    } else {
logger.debug(`getNotificationForSyncId() 4`)
                        if (result.rows && result.rows.length > 0) {
logger.debug(`getNotificationForSyncId() 5`)
                            notificationid = result.rows[0].notificationid
                        }
                        callback(null, connection, notificationid)
                    }
                })
            },
            function(connection, notificationid, callback) {
                if (notificationid) {
logger.debug(`getNotificationForSyncId() 8`)
                    // Now just use our normal getNotification() method
                    const getParams = {
                        userid: params.userid,
                        notificationid: notificationid,
                        dbConnection: connection,
                        preauthorized: true
                    }
logger.debug(`getNotificationForSyncId() 8.1: ${JSON.stringify(getParams)}`)
                    TCTaskNotificationService.getNotification(getParams, function(err, notification) {
logger.debug(`getNotificationForSyncId() 9`)
                        callback(err, connection, notification)
                    })
                } else {
logger.debug(`getNotificationForSyncId() 9.1`)
                    callback(null, connection, null)
                }
            }
        ], 
        function(err, connection, notification) {
logger.debug(`getNotificationForSyncId() 10`)
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
logger.debug(`getNotificationForSyncId() 11`)
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Error finding notification for syncid (${params.syncid}).`))))
            } else {
logger.debug(`getNotificationForSyncId() 12`)
                completion(null, notification) // Note, notification may be null (which means we don't have a notification with the requested syncid)
            }
        })
    }

/*
                                                        const searchParams = {
                                                            userid: userid,
                                                            taskid: taskForNotification.taskid,
                                                            triggerDate: triggerDate
                                                        }
                                                        TCTaskNotificationService.searchForNotification(searchParams, function(searchErr, foundNotification) {
                                                            if (searchErr) {
                                                                innerWaterfallCallback(searchErr)
                                                            } else {
                                                                innerWaterfallCallback(null, foundNotification)
                                                            }
                                                        })
*/    

    static searchForNotification(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskNotificationService.searchForNotification().`))))
            return
        }
        
        params.userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        params.taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!params.userid || params.userid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskNotificationService.searchForNotification() : Missing userid.'))))
            return
        }
        if (!params.taskid || params.taskid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskNotificationService.searchForNotification() : Missing taskid.'))))
            return
        }
        if (!params.triggerDate) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskNotificationService.searchForNotification() : Missing triggerDate.'))))
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
                const sql = `SELECT * FROM tdo_task_notifications WHERE taskid = ?`
                connection.query(sql, [params.syncid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                        `Error running query: ${err.message}`))), connection)
                    } else {
                        let notification = null
                        if (result.rows && result.rows.length > 0) {
                            notification = new TCTaskNotification(result.rows[0])
                        }
                        callback(null, connection, notification)
                    }
                })
            }
        ],
        function(err, connection, notification){
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not determine task notification authorization (${notificationid}).`))))
            } else {
                completion(null, notification)
            }
        })
    }
}

module.exports = TCTaskNotificationService

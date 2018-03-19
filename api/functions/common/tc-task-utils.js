'use strict'

const async = require('async')

const db = require('./tc-database')

const constants = require('./constants')
const errors = require('./errors')

const TCChangeLogService = require('./tc-changelog-service')

class TCTaskUtils {
    // The purpose of this function is to properly add entries into the
    // tdo_changelog table when making changes to tasks. The "oldTask" is
    // compared against the "newTask" so that the actual property changes
    // are logged into the ChangeLog.
    //
    // If a task changes lists, a "delete" entry is added for the old
    // list and an "add" for the new list.
    static updateChangeLog(params, completion) {
        const oldTask = params.oldTask
        const newTask = params.newTask
        const userid = params.userid

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if(!oldTask) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskUtils.updateChangeLog() Missing oldTask.'))))
            return
        }
        if(!newTask) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskUtils.updateChangeLog() Missing newTask.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskUtils.updateChangeLog() Missing userid.'))))
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

                if (oldTask.listid && newTask.listid && oldTask.listid != newTask.listid) {
                    // The task is moving to a different list and so we need to add a "delete" entry
                    // for the old list and an "add" entry for the new list.
                    const deleteChangeParams = {
                        listid: oldTask.listid,
                        userid: userid,
                        itemid: oldTask.taskid,
                        itemName: oldTask.name,
                        itemType: constants.ChangeLogItemType.Task,
                        changeType: constants.ChangeLogType.Delete,
                        changeLocation: constants.ChangeLogLocation.API,
                        dbConnection: connection
                    }
                    TCChangeLogService.addChangeLogEntry(deleteChangeParams, function(err, result) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection, true)
                        }
                    })
                } else {
                    callback(null, connection, false)   
                }
            },
            function(connection, shouldAddEntryForNewTask, callback) {
                // If shouldAddEntryForNewTask is true, continue the work from the previous function
                if (shouldAddEntryForNewTask) {
                    const addChangeParams = {
                        listid: newTask.listid,
                        userid: userid,
                        itemid: newTask.taskid,
                        itemName: newTask.name,
                        itemType: constants.ChangeLogItemType.Task,
                        changeType: constants.ChangeLogType.Add,
                        changeLocation: constants.ChangeLogLocation.API,
                        dbConnection: connection
                    }
                    TCChangeLogService.addChangeLogEntry(addChangeParams, function(err, result) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection, false)
                        }
                    })
                } else {
                    callback(null, connection, true)
                }
            },
            function(connection, shouldDetermineChanges, callback) {
                // If shouldDetermineChanges is true, we haven't done anything yet and
                // now it's time to compare the oldTask with the newTask and then make
                // an entry into the changelog with the differences.
                if (shouldDetermineChanges) {
                    const changeData = TCTaskUtils.buildJSONChangesForTask(oldTask, newTask)
                    const changeParams = {
                        listid: newTask.listid,
                        userid: userid,
                        itemid: newTask.taskid,
                        itemName: newTask.name,
                        itemType: constants.ChangeLogItemType.Task,
                        changeType: constants.ChangeLogType.Add,
                        changeLocation: constants.ChangeLogLocation.API,
                        changeData: changeData,
                        dbConnection: connection
                    }
                    TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection)
                        }
                    })
                } else {
                    // The work was already done with a move to a different list, so
                    // just continue on.
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
                        `Could not update the changelog for a task (${newTask.taskid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static buildJSONChangesForTask(oldTask, newTask) {
        // Build a JSON string that describes the property differences between
        // the old a new task that will be used to add a correct entry into
        // the ChangeLog.
        const properties = {}

        let oldValue = oldTask.completionDate ? oldTask.completionDate : 0
        let newValue = newTask.completionDate ? newTask.completionDate : 0
        if (oldValue != newValue) {
            properties['old-completionDate'] = oldValue
            properties['completionDate'] = newValue
        }

        oldValue = oldTask.parentid ? oldTask.parentid : ''
        newValue = newTask.parentid ? newTask.parentid : ''
        if (oldValue != newValue) {
            properties['old-parentid'] = oldValue
            properties['parentid'] = newValue
        }

        oldValue = oldTask.name ? oldTask.name : ''
        newValue = newTask.name ? newTask.name : ''
        if (oldValue != newValue) {
            properties['old-taskName'] = oldValue
            properties['taskName'] = newValue
        }

        oldValue = oldTask.note ? oldTask.note : ''
        newValue = newTask.note ? newTask.note : ''
        if (oldValue != newValue) {
            properties['old-taskNote'] = oldValue
            properties['taskNote'] = newValue
        }

        oldValue = oldTask.startdate ? oldTask.startdate : 0
        newValue = newTask.startdate ? newTask.startdate : 0
        if (oldValue != newValue) {
            properties['old-taskStartDate'] = oldValue
            properties['taskStartDate'] = newValue
        }

        oldValue = oldTask.duedate ? oldTask.duedate : 0
        newValue = newTask.duedate ? newTask.duedate : 0
        if (oldValue != newValue) {
            properties['old-taskDueDate'] = oldValue
            properties['taskDueDate'] = newValue
        }

        oldValue = oldTask.sort_order ? oldTask.sort_order : 0
        newValue = newTask.sort_order ? newTask.sort_order : 0
        if (oldValue != newValue) {
            properties['old-sortOrder'] = oldValue
            properties['sortOrder'] = newValue
        }

        oldValue = oldTask.starred ? oldTask.starred : 0
        newValue = newTask.starred ? newTask.starred : 0
        if (oldValue != newValue) {
            properties['old-starred'] = oldValue
            properties['starred'] = newValue
        }

        oldValue = oldTask.priority ? oldTask.priority : constants.TaskPriority.None
        newValue = newTask.priority ? newTask.priority : constants.TaskPriority.None
        if (oldValue != newValue) {
            properties['old-priority'] = oldValue
            properties['priority'] = newValue
        }

        oldValue = oldTask.recurrence_type ? oldTask.recurrence_type : constants.TaskRecurrenceType.None
        newValue = newTask.recurrence_type ? newTask.recurrence_type : constants.TaskRecurrenceType.None
        if (oldValue != newValue) {
            properties['old-recurrenceType'] = oldValue
            properties['recurrenceType'] = newValue
        }

        oldValue = oldTask.advanced_recurrence_string ? oldTask.advanced_recurrence_string : ''
        newValue = newTask.advanced_recurrence_string ? newTask.advanced_recurrence_string : ''
        if (oldValue != newValue) {
            properties['old-advancedRecurrenceString'] = oldValue
            properties['advancedRecurrenceString'] = newValue
        }

        oldValue = oldTask.location_alert ? TCTaskUtils.parseLocationAlertType(oldTask.location_alert) : constants.TaskLocationAlertType.None
        newValue = newTask.location_alert ? TCTaskUtils.parseLocationAlertType(newTask.location_alert) : constants.TaskLocationAlertType.None
        if (oldValue != newValue) {
            properties['old-locationAlertType'] = oldValue
            properties['locationAlertType'] = newValue
        }

        oldValue = oldTask.location_alert ? TCTaskUtils.parseLocationAlertAddress(oldTask.location_alert) : ''
        newValue = newTask.location_alert ? TCTaskUtils.parseLocationAlertAddress(newTask.location_alert) : ''
        if (oldValue != newValue) {
            properties['old-locationAlertAddress'] = oldValue
            properties['locationAlertAddress'] = newValue
        }

        oldValue = oldTask.assigned_userid ? oldTask.assigned_userid : ''
        newValue = newTask.assigned_userid ? newTask.assigned_userid : ''
        if (oldValue != newValue) {
            properties['old-assignedUserId'] = oldValue
            properties['assignedUserId'] = newValue
        }

        oldValue = oldTask.task_type ? oldTask.task_type : constants.TaskType.Normal
        newValue = newTask.task_type ? newTask.task_type : constants.TaskType.Normal
        if (oldValue != newValue) {
            properties['old-taskType'] = oldValue
            properties['taskType'] = newValue
        }

        oldValue = oldTask.type_data ? oldTask.type_data : ''
        newValue = newTask.type_data ? newTask.type_data : ''
        if (oldValue != newValue) {
            properties['old-typeData'] = oldValue
            properties['typeData'] = newValue
        }

        return JSON.stringify(properties)
    }

    static parseLocationAlertType(locationString) {
        if (!locationString) {
            return constants.TaskLocationAlertType.None
        }

        if (locationString.startsWith('<')) {
            return constants.TaskLocationAlertType.Leaving
        } else if (locationString.startsWith('>')) {
            return constants.TaskLocationAlertType.Arriving
        }

        return constants.TaskLocationAlertType.None
    }

    static parseLocationAlertAddress(locationString) {
        if (!locationString) {
            return ''
        }

        let tmpString = locationString.substr(2)
        const locationComponents = tmpString.split(':')
        if (!locationComponents || locationComponents.length < 2) {
            return ''
        }

        return locationComponents[1]
    }
    
    static listIDForTask(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }
        
        params.taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!params.taskid || params.taskid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskUtils.listIDForTask() : Missing taskid.'))))
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
                let sql = `SELECT listid FROM tdo_tasks WHERE taskid=?
                            UNION SELECT listid FROM tdo_completed_tasks WHERE taskid=?
                            UNION SELECT listid FROM tdo_deleted_tasks WHERE taskid=?`
                connection.query(sql, [params.taskid, params.taskid, params.taskid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    } else {
                        let listid = null
                        if (results.rows && results.rows.length > 0) {
                            listid = results.rows[0].listid
                        }

                        if (listid) {
                            callback(null, connection, listid)
                        } else {
                            callback(new Error(JSON.stringify(errors.taskNotFound)), connection)
                        }
                    }
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
                        `Could not get a listid for task (${params.taskid}).`))))
            } else {
                completion(null, listid)
            }
        })
    }
}

module.exports = TCTaskUtils

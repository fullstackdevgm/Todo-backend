'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')

const db = require('./tc-database')
const begin = require('any-db-transaction')
const moment = require('moment-timezone')
require('datejs')

const constants = require('./constants')
const errors = require('./errors')

const TCChangeLogService = require('./tc-changelog-service')
const TCCommentService = require('./tc-comment-service')
const TCList = require('./tc-list')
const TCTask = require('./tc-task')
const TCListMembershipService = require('./tc-list-membership-service')
const TCListSettingsService = require('./tc-list-settings-service')
const TCSmartListService = require('./tc-smart-list-service')
const TCTagService = require('./tc-tag-service')
const TCTaskitoService = require('./tc-taskito-service')
const TCTaskNotificationService = require('./tc-task-notification-service')
const TCTaskUtils = require('./tc-task-utils')
const TCUserSettingsService = require('./tc-user-settings-service')
const TCUtils = require('./tc-utils')

//This is used when sorting tasks with no due date, to ensure they
// come after tasks with a due date.
const NO_DATE_SORT_VALUE = 64092211200
const PRIORITY_ORDER_BY_STATEMENT = "priority=0,priority ASC"

const MON_SELECTION = 0x0001
const TUE_SELECTION	= 0x0002
const WED_SELECTION	= 0x0004
const THU_SELECTION	= 0x0008
const FRI_SELECTION	= 0x0010
const SAT_SELECTION	= 0x0020
const SUN_SELECTION	= 0x0040
const WEEKDAY_SELECTION = 0x001F
const WEEKEND_SELECTION = 0x0060

class TCTaskService {
    static tasksForList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid
        const listid = params.listid

        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false
        
        let page = params.page ? Number(params.page) : 0
        let pageSize = params.page_size ? Number(params.page_size) : constants.defaultPagedTasks
        if (pageSize > constants.maxPagedTasks) { pageSize = constants.maxPagedTasks }

        let offset = page * pageSize

        const completedOnly = params.completed_only != undefined ? Boolean(params.completed_only) : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.tasksForList() missing the listid.`))))
            return
        }
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.tasksForList() missing the userid.`))))
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
                if (isPreauthorized) {
                    callback(null, connection)
                } else {
                    const authParams = {
                        listid : listid,
                        userid : userid,
                        membershipType: constants.ListMembershipType.Viewer,
                        dbConnection : connection
                    }
                    TCListMembershipService.isAuthorizedForMembershipType(authParams, function(err, isAuthorized) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(err, `Error confirming list (${listid}) membership authorization for user (${userid}).`))), connection)
                        } else {
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
                const sortTypeParams = {
                    listid: listid,
                    userid: userid,
                    dbConnection: connection
                }
                TCListSettingsService.sortTypeForList(sortTypeParams, function(err, sortType) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, sortType)
                    }
                })
            },
            function(connection, sortType, callback) {
                const orderByParams = {
                    sortType: sortType,
                    userid: userid,
                    dbConnection: connection
                }
                TCTaskService.orderByStatementForSortType(orderByParams, function(err, orderByStatement) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, orderByStatement)
                    }
                })
            },
            function(connection, orderByStatement, callback) {
                let tableName = completedOnly ? "tdo_completed_tasks" : "tdo_tasks"

                let sql = `
                    SELECT *
                    FROM ${tableName}
                    WHERE
                        listid = ? AND
                        (deleted IS NULL OR deleted = 0) AND 
                        (parentid = '' OR parentid IS NULL)
                    ${orderByStatement}
                    LIMIT ? OFFSET ?
                `

                connection.query(sql, [listid, pageSize, offset], function(err, results, fields) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        const tasks = []
                        if (results.rows) {
                            for (const row of results.rows) {
                                tasks.push(new TCTask(row))
                            }
                        }
                        let tasksInfo = {
                            tasks: tasks
                        }
                        callback(null, connection, tasksInfo)
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
                        `Could not find any tasks for the listid (${listid}).`))))
            } else {
                completion(null, tasks)
            }
        })
    }

    static getTaskCounts(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.tasksForList() missing the userid.`))))
            return
        }

        const listTaskCounts = []
        const smartListTaskCounts = []

        let nowTimestamp = moment().unix()
        let startOfDayTimestamp = moment().unix()
        let dateSQL = null
        let selectedDatesSQL = ''
        let completedCutoffSQL = ''

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
                const timeZoneParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCUserSettingsService.getUserTimeZone(timeZoneParams, function(err, userTimeZone) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        nowTimestamp = moment().tz(userTimeZone).unix()
                        const startOfToday = moment().tz(userTimeZone)
                        startOfToday.startOf('day')
                        startOfToday.subtract(1, 'second') // One second before midnight
                        startOfDayTimestamp = startOfToday.unix()

                        // Figure out the dateSQL for gathering overdue tasks
                        dateSQL = `(`
                        dateSQL += `(task_type!=1 AND (((due_date_has_time = 0) AND (duedate < ${startOfDayTimestamp}) AND (duedate != 0)) OR ((due_date_has_time = 1) AND (duedate < ${nowTimestamp}) AND (duedate != 0))))`
                        dateSQL += ` OR `
                        dateSQL += `(task_type=1 AND (((project_duedate_has_time = 0) AND (project_duedate < ${startOfDayTimestamp}) AND (project_duedate != 0)) OR ((project_duedate_has_time = 1) AND (project_duedate < ${nowTimestamp}) AND (project_duedate != 0))))`
                        dateSQL += `)`

                        // Figure out date SQL for counting tasks that match selected dates
                        if (params.selected_dates) {
                            const selectedDates = params.selected_dates.split(',')
                            if (selectedDates) {
                                let dateQueries = Array()
                                selectedDates.forEach(selectedDate => {
                                    const theDate = moment.tz(selectedDate, userTimeZone)
                                    const beginTimestamp = theDate.startOf('day').unix()
                                    const endTimestamp = theDate.endOf('day').unix()
                                    let sql = `(`
                                    sql += `(task_type != 1 AND (((due_date_has_time = 0) AND (duedate > ${beginTimestamp}) AND (duedate < ${endTimestamp}) AND (duedate != 0)) OR ((due_date_has_time = 1) AND (duedate > ${beginTimestamp}) AND (duedate < ${endTimestamp}) AND (duedate != 0))))`
                                    sql += ` OR `
                                    sql += `(task_type = 1 AND (((project_duedate_has_time = 0) AND (project_duedate > ${beginTimestamp}) AND (project_duedate < ${endTimestamp}) AND (project_duedate != 0)) OR ((project_duedate_has_time = 1) AND (project_duedate > ${beginTimestamp} AND project_duedate < ${endTimestamp}) AND (project_duedate != 0))))`
                                    sql += `)`
                                    dateQueries.push(sql)
                                })

                                if (dateQueries.length > 0) {
                                    selectedDatesSQL = ` AND (${dateQueries.join(' OR ')})`
                                }
                            }
                        }

                        // Figure out the SQL for cutting off completed tasks
                        if (params.completion_cutoff_date) {
                            const cutoffDate = moment.tz(params.completion_cutoff_date, userTimeZone)
                            if (cutoffDate) {
                                const beginTimestamp = cutoffDate.startOf('day').unix()
                                completedCutoffSQL = ` AND (completiondate > ${beginTimestamp})`
                            }
                        }

                        callback(null, connection, userTimeZone)
                    }
                })
            },
            function(connection, userTimeZone, callback) {
                const listParams = {
                    userid: userid,
                    dbConnection: connection
                }
                const TCListService = require('./tc-list-service')
                TCListService.listIDsForUser(listParams, function(err, listIDs) {
                    if (err) {
                        callback(err, connection, userTimeZone)
                    } else {
                        callback(null, connection, userTimeZone, listIDs)
                    }
                })
            },
            function(connection, userTimeZone, listIDs, callback) {
                async.eachSeries(listIDs,
                function(listid, eachCallback) {
                    let activeTaskCount = 0
                    let overdueTaskCount = 0
                    let completedTaskCount = 0

                    async.waterfall([
                        function(innerWaterfallCallback) {
                            const sql = `SELECT COUNT(*) AS count FROM tdo_tasks WHERE listid=? ${selectedDatesSQL}`
                            connection.query(sql, [listid], function(err, results) {
                                if (err) {
                                    innerWaterfallCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error running query: ${err}`))))
                                } else {
                                    if (results.rows) {
                                        for (const row of results.rows) {
                                            activeTaskCount = row.count
                                        }
                                    }
                                    innerWaterfallCallback(null)
                                }
                            })
                        },
                        function(innerWaterfallCallback) {
                            const completedSQL = `SELECT COUNT(*) AS count FROM tdo_completed_tasks WHERE listid=? ${selectedDatesSQL} ${completedCutoffSQL}`
                            connection.query(completedSQL, [listid], function(err, results) {
                                if (err) {
                                    innerWaterfallCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error running query: ${err}`))))
                                } else {
                                    if (results.rows) {
                                        for (const row of results.rows) {
                                            completedTaskCount = row.count
                                        }
                                    }
                                    innerWaterfallCallback(null)
                                }
                            })
                        },
                        function(innerWaterfallCallback) {
                            const overdueSQL = `SELECT COUNT(*) AS count FROM tdo_tasks WHERE listid=? AND ${dateSQL} ${selectedDatesSQL}`
                            connection.query(overdueSQL, [listid], function(err, results) {
                                if (err) {
                                    innerWaterfallCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error running query: ${err}`))))
                                } else {
                                    if (results.rows) {
                                        for (const row of results.rows) {
                                            overdueTaskCount = row.count
                                        }
                                    }
                                    innerWaterfallCallback(null)
                                }
                            })
                        }
                    ], function(innerWaterfallErr) {
                        if (innerWaterfallErr) {
                            eachCallback(innerWaterfallErr)
                        } else {
                            listTaskCounts.push({
                                listid: listid,
                                active: activeTaskCount,
                                overdue: overdueTaskCount,
                                completed: completedTaskCount
                            })
                            eachCallback(null)
                        }
                    })

                },
                function(err) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userTimeZone, listIDs)
                    }
                })
            },
            function(connection, userTimeZone, listIDs, callback) {
                // Read the user's INBOX ID so we can replace the hard-coded
                // version witht he real inbox ID when filtering.
                const inboxParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCUserSettingsService.getUserInboxID(inboxParams, function(err, userInboxID) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userTimeZone, listIDs, userInboxID)
                    }
                })
            },
            function(connection, userTimeZone, listIDs, userInboxID, callback) {
                // Read a user's global filtered lists so we can combine it
                // with smart lists to filter later.
                const filterParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCUserSettingsService.getFilteredLists(filterParams, function(err, filteredLists) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        // Remove any filtered lists from the listIDs before
                        // passing on to the next function.
                        let excludedListsFromSettings = []
                        if (filteredLists && filteredLists.length > 0) {
                            const excludedListIDs = filteredLists.replace(constants.ServerInboxId, userInboxID)
                            excludedListsFromSettings = excludedListIDs.split(/\s*,\s*/)
                        }

                        const listsToUse = listIDs.filter(listid => {
                            return excludedListsFromSettings.find(aListId => aListId == listid) == undefined
                        })

                        callback(null, connection, userTimeZone, listsToUse, userInboxID)
                    }
                })
            },
            function(connection, userTimeZone, listIDs, userInboxID, callback) {
                // Read a user's smart lists so we can have the info
                // needed to build the SQL WHERE query needed to return
                // count information
                const smartListParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCSmartListService.getSmartLists(smartListParams, function(err, smartLists) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userTimeZone, listIDs, userInboxID, smartLists)
                    }
                })
            },
            function(connection, userTimeZone, listIDs, userInboxID, smartLists, callback) {
                async.eachSeries(smartLists,
                function(smartList, eachCallback) {
                    const smartListSQL = TCSmartListService.sqlWhereStatementForSmartList(smartList, userTimeZone)
                    let activeTaskCount = 0
                    let overdueTaskCount = 0
                    let completedTaskCount = 0

                    // Check the smart list's list filter to see if any lists should be excluded
                    // from using.
                    const jsonFilter = smartList.jsonFilter()
                    let excludedListsFromFilter = []
                    if (smartList.excluded_list_ids && smartList.excluded_list_ids.length > 0) {
                        const excludedListIDs = smartList.excluded_list_ids.replace(constants.ServerInboxId, userInboxID)
                        excludedListsFromFilter = excludedListIDs.split(/\s*,\s*/)
                    }

                    const listsToUse = listIDs.filter(listid => {
                        return excludedListsFromFilter.find(aListId => aListId == listid) == undefined
                    })

                    let whereSQL = '('
                    listsToUse.forEach((listid, idx) => {
                        if (idx > 0) {
                            whereSQL += ` OR `
                        }
                        whereSQL += `listid = '${listid}'`
                    })
                    whereSQL += ')'

                    if (smartListSQL && smartListSQL.length > 0) {
                        whereSQL += ` AND ${smartListSQL}`
                    }

                    async.waterfall([
                        function(innerWaterfallCallback) {
                            let sql = `SELECT COUNT(*) AS count FROM tdo_tasks AS tasks WHERE ${whereSQL} ${selectedDatesSQL}`
                            connection.query(sql, [], function(err, results) {
                                if (err) {
                                    innerWaterfallCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error running query: ${err}`))))
                                } else {
                                    if (results.rows) {
                                        for (const row of results.rows) {
                                            activeTaskCount = row.count
                                        }
                                    }
                                    innerWaterfallCallback(null)
                                }
                            })
                        },
                        function(innerWaterfallCallback) {
                            const completedSQL = `SELECT COUNT(*) AS count FROM tdo_completed_tasks AS tasks WHERE ${whereSQL} ${selectedDatesSQL} ${completedCutoffSQL}`
                            connection.query(completedSQL, [], function(err, results) {
                                if (err) {
                                    innerWaterfallCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error running query: ${err}`))))
                                } else {
                                    if (results.rows) {
                                        for (const row of results.rows) {
                                            completedTaskCount = row.count
                                        }
                                    }
                                    innerWaterfallCallback(null)
                                }
                            })
                        },
                        function(innerWaterfallCallback) {
                            const overdueSQL = `SELECT COUNT(*) AS count FROM tdo_tasks AS tasks WHERE ${whereSQL} AND ${dateSQL} ${selectedDatesSQL}`
                            connection.query(overdueSQL, [], function(err, results) {
                                if (err) {
                                    innerWaterfallCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error running query: ${err}`))))
                                } else {
                                    if (results.rows) {
                                        for (const row of results.rows) {
                                            overdueTaskCount = row.count
                                        }
                                    }
                                    innerWaterfallCallback(null)
                                }
                            })
                            
                        }
                    ], function(innerWaterfallErr) {
                        if (innerWaterfallErr) {
                            eachCallback(innerWaterfallErr)
                        } else {
                            smartListTaskCounts.push({
                                listid: smartList.listid,
                                active: activeTaskCount,
                                overdue: overdueTaskCount,
                                completed: completedTaskCount
                            })
                            eachCallback(null)
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
                        `Could not determine task counts for user (${userid}).`))))
            } else {
                completion(null, {
                    lists: listTaskCounts,
                    smart_lists: smartListTaskCounts
                })
            }
        })
    }

    // The general approach to implementing this method is that we only want to query
    // the database once for a count of all due tasks in the specified date ranage.
    // After getting results, the code will split them up into a "daily" array. It's
    // important that the days are returned in the user's time zone because otherwise
    // the counts will be off.
    static getTaskCountByDateRange(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid

        const beginDateString = params.begin_date
        const endDateString = params.end_date

        let beginTimestamp = null
        let endTimestamp = null

        const currentSmartListId = params.smart_listid
        const currentListId = params.listid

        let userTimeZone = null
        let listFilterSQL = null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.getTaskCountByDateRange() missing the userid.`))))
            return
        }
        if (!beginDateString) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.getTaskCountByDateRange() missing the begin_date.`))))
            return
        }
        if (!endDateString) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.getTaskCountByDateRange() missing the end_date.`))))
            return
        }

        // Must have one of smart_listid OR listid
        if ((!currentSmartListId && !currentListId) || (currentSmartListId && currentListId)) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.getTaskCountByDateRange() must specify either smart_listid OR listid, but not both.`))))
            return
        }

        const dateCounts = {}
        let rawResults = []

        // let nowTimestamp = moment().unix()
        // let startOfDayTimestamp = moment().unix()
        let dateSQL = null

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
                const timeZoneParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCUserSettingsService.getUserTimeZone(timeZoneParams, function(err, aTimeZone) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        userTimeZone = aTimeZone
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                if (currentListId) {
                    // Build the list filter based on ONE listid
                    listFilterSQL = `listid='${currentListId}'`
                    callback(null, connection)
                } else {
                    // Build the list filter based on the Smart List's filter and
                    // also account for user list filters
                    async.waterfall([
                        function(innerWaterfallCallback) {
                            const getSmartListParams = {
                                userid: userid,
                                listid: currentSmartListId,
                                dbConnection: connection
                            }
                            TCSmartListService.getSmartList(getSmartListParams, function(err, smartList) {
                                if (err) {
                                    innerWaterfallCallback(err)
                                } else {
                                    listFilterSQL = TCSmartListService.sqlWhereStatementForSmartList(smartList)
                                    innerWaterfallCallback(null, smartList)
                                }
                            })
                        },
                        function(smartList, innerWaterfallCallback) {
                            // Make sure that we *only* look at tasks from lists that
                            // belong to the user.
                            const listParams = {
                                userid: userid,
                                dbConnection: connection
                            }
                            const TCListService = require('./tc-list-service')
                            TCListService.listIDsForUser(listParams, function(err, listIDs) {
                                if (err) {
                                    innerWaterfallCallback(err)
                                } else {
                                    innerWaterfallCallback(null, smartList, listIDs)
                                }
                            })
                        },
                        function(smartList, listIDs, innerWaterfallCallback) {
                            // Read the user's INBOX ID so we can replace the hard-coded
                            // version witht he real inbox ID when filtering.
                            const inboxParams = {
                                userid: userid,
                                dbConnection: connection
                            }
                            TCUserSettingsService.getUserInboxID(inboxParams, function(err, userInboxID) {
                                if (err) {
                                    innerWaterfallCallback(err)
                                } else {
                                    innerWaterfallCallback(null, smartList, listIDs, userInboxID)
                                }
                            })
                        },
                        function(smartList, listIDs, userInboxID, innerWaterfallCallback) {
                            // Read the global filtered lists so we don't include them when
                            // querying for task counts.
                            const filterParams = {
                                userid: userid,
                                dbConnection: connection
                            }
                            TCUserSettingsService.getFilteredLists(filterParams, function(err, filteredLists) {
                                if (err) {
                                    innerWaterfallCallback(err)
                                } else {
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

                                    const listsToUse = listIDs.filter(listid => {
                                        return excludedListsFromFilter.find(aListId => aListId == listid) == undefined
                                                && excludedListsFromSettings.find(aListId => aListId == listid) == undefined
                                    })

                                    innerWaterfallCallback(null, listsToUse)
                                }
                            })
                        },
                        function(listsToUse, innerWaterfallCallback) {
                            if (listsToUse && listsToUse.length > 0) {
                                // Augment the listFilterSQL to scope the query to user-owned lists
                                if (!listFilterSQL || listFilterSQL.length == 0) {
                                    listFilterSQL = ``
                                } else {
                                    listFilterSQL += ` AND `
                                }

                                listFilterSQL += `listid IN (`
                                listsToUse.forEach((listid, index) => {
                                    if (index > 0) {
                                        listFilterSQL += `,`
                                    }
                                    listFilterSQL += `"${listid}"`
                                })
                                listFilterSQL += `)`
                            }
                            innerWaterfallCallback(null)
                        }
                    ], function(err) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection)
                        }
                    })
                }
            },
            function(connection, callback) {
                // Now that we have the user's time zone, validate that the dates we've received are valid.
                // It's not possible to specify a begin_date after an end_date, for example. The begin_date
                // and end_date ARE allowed to be the same, which means that we will only be checking ONE
                // date.
                // let beginDate = moment(beginDateString).tz(userTimeZone)
                // let endDate = moment(endDateString).tz(userTimeZone)

                beginTimestamp = moment.tz(beginDateString, userTimeZone).startOf('day').unix()
                endTimestamp = moment.tz(endDateString, userTimeZone).endOf('day').unix()

                if (beginTimestamp > endTimestamp) {
                    callback(new Error(JSON.stringify(errors.customError(errors.invalidParameters, `TCTaskService.getTaskCountByDateRange() begin_date must be before end_date.`))), connection)
                } else {
                    callback(null, connection)
                }
            },
            function(connection, callback) {
                // Query the database for tasks (not projects) that fall within the given range
                if (!listFilterSQL || listFilterSQL.length == 0) {
                    listFilterSQL = ``
                } else {
                    listFilterSQL += ` AND `
                }
                const queries = [
                    `SELECT duedate AS timestamp,COUNT(*) AS count FROM tdo_tasks AS tasks WHERE ${listFilterSQL} task_type!=1 AND duedate IS NOT NULL AND duedate >= ? AND duedate < ? AND ((deleted IS NULL) OR (deleted = 0)) GROUP BY duedate ORDER BY duedate`,
                    `SELECT project_duedate AS timestamp,COUNT(*) AS count FROM tdo_tasks AS tasks WHERE ${listFilterSQL} task_type=1 AND project_duedate IS NOT NULL AND project_duedate >= ? AND project_duedate < ? AND ((deleted IS NULL) OR (deleted = 0)) GROUP BY project_duedate ORDER BY project_duedate`
                ]
                async.eachSeries(queries,
                function(sql, eachCallback) {
                    connection.query(sql, [beginTimestamp, endTimestamp], function(err, results) {
                        if (err) {
                            eachCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error running query: ${err}`))))
                        } else {
                            if (results.rows) {
                                rawResults = rawResults.concat(results.rows)
                            }
                            eachCallback(null)
                        }
                    })
                },
                function(eachErr) {
                    if (eachErr) {
                        callback(eachErr, connection)
                    } else {
                        // Tidy up the array so the dates are sorted by timestamp
                        rawResults.sort(function(a, b) {
                            return a.timestamp - b.timestamp
                        })
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Now build the final array, combining dates together as needed
                // and incrementing the counts.
                rawResults.forEach((countResult) => {
                    // Convert the timestamp into a simple ISO 8601 date string
                    // (which is just the date info and no time) and make sure it's
                    // in the user's time zone.
                    const dateString = moment.tz(countResult.timestamp * 1000, userTimeZone).format(`YYYY-MM-DD`)
                    const taskCount = countResult.count
                    
                    const existingCount = dateCounts[dateString]
                    if (existingCount) {
                        // Add on to the count
                        dateCounts[dateString] = existingCount + taskCount
                    } else {
                        // Add a new count
                        dateCounts[dateString] = taskCount
                    }
                })

                callback(null, connection)
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
                        `Could not determine task counts with date ranges for user (${userid}).`))))
            } else {
                completion(null, {
                    dates: dateCounts
                })
            }
        })
    }

    static getTasksForSearchText(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid
        const searchText = params.search_text
        
        let page = params.page ? Number(params.page) : 0
        let pageSize = params.page_size ? Number(params.page_size) : constants.defaultPagedTasks
        if (pageSize > constants.maxPagedTasks) { pageSize = constants.maxPagedTasks }

        let offset = page * pageSize

        const completedOnly = params.completed_only != undefined ? Boolean(params.completed_only) : false
        
        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.getTasksForSearchText() missing the userid parameter.`))))
            return
        }
        if (!searchText) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.getTasksForSearchText() missing search_text parameter.`))))
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
                // Read the user's INBOX ID so we can replace the hard-coded
                // version with the real inbox ID when filtering.
                const inboxParams = {
                    userid: userid,
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
                    userid: userid,
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
                    userid: userid,
                    dbConnection: connection
                }
                TCUserSettingsService.getFilteredLists(filterParams, function(err, filteredLists) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        // Since we're on the server, we need to just remove
                        // any filtered lists from the listids.
                        let excludedListsFromSettings = []
                        if (filteredLists && filteredLists.length > 0) {
                            const excludedListIDs = filteredLists.replace(constants.ServerInboxId, userInboxID)
                            excludedListsFromSettings = excludedListIDs.split(/\s*,\s*/)
                        }

                        const listsToUse = listids.filter(listid => {
                            return excludedListsFromSettings.find(aListId => aListId == listid) == undefined
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

                // Prevent deleted tasks from ever appearing in search results
                whereSQL += ' AND ((deleted IS NULL) OR (deleted = 0))'


                const searchSQL = TCTaskService.searchSQLForSearchString(searchText)
                const taskitoSearchSQL = TCTaskService.searchSQLForSearchString(searchText, false)

                let sql = ``
                let queryValues = []

                if (completedOnly) {
                    sql = `SELECT * FROM tdo_completed_tasks WHERE ${whereSQL} AND ((${searchSQL.sql}) OR (taskid IN (SELECT parentid FROM tdo_taskitos WHERE deleted = 0 AND ${taskitoSearchSQL.sql})))`
                } else {
                    sql = `SELECT * FROM tdo_tasks WHERE ${whereSQL} AND ((${searchSQL.sql}) OR (taskid IN (SELECT parentid FROM tdo_taskitos WHERE deleted = 0 AND ${taskitoSearchSQL.sql})))`
                }

                sql += ` LIMIT ? OFFSET ?`

                queryValues = queryValues.concat( searchSQL.values, taskitoSearchSQL.values, [pageSize, offset])

                connection.query(sql, queryValues, function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        const tasks = []
                        if (results.rows) {
                            for (const row of results.rows) {
                                tasks.push(new TCTask(row))
                            }
                        }
                        let tasksInfo = {
                            tasks: tasks
                        }
                        callback(null, connection, tasksInfo)
                    }
                })
            },
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
                        `Could not get tasks for search.`))))
            } else {
                completion(null, tasks)
            }
        })
    }

    static searchSQLForSearchString(searchText, includeNote) {
        if (includeNote !== true || includeNote !== false) includeNote = true
        let sql = ""
        let values = []

        const searchArray = searchText.split(/\s+/)
        searchArray.forEach((searchItem) => {
            if (searchItem.length > 0) {
                if (sql.length > 0) {
                    sql += " AND"
                }

                // // This removes magic on LIKE wildchars
                // $searchItem = preg_replace('#(%|_)#', '\\$1', $searchItem);
                // $searchItem = mysql_real_escape_string($searchItem);

                searchItem = searchItem.replace(/%|_|"|'/g, function(x){return '\\' + x})
                values.push('%'+searchItem+'%')

                sql += ` (name LIKE ?`
                if (includeNote) {
                    sql += ` OR note LIKE ?`
                    values.push('%'+searchItem+'%')
                }
                sql += `)`
            }
        })

        return {sql : sql, values : values}
    }

    static orderByStatementForSortType(params, completion) {
        if (params.completedOnly) {
            let sortStatement = ` ORDER BY completiondate DESC,sort_order, ${PRIORITY_ORDER_BY_STATEMENT}, name ASC, taskid ASC`
            completion(null, sortStatement)
            return
        }

        const sortType = params.sortType != undefined ? params.sortType : constants.SortType.DatePriorityAlpha
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.orderByStatementForSortType() missing the userid.`))))
            return
        }

        // Each sort type uses the dueDate/Priority/Alpha sort,
        // so we have to figure it out always.
        // Because of start dates, we need to know 23:59:59 of
        // in the user's timezone as well.
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
                const timeZoneParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCUserSettingsService.getUserTimeZone(timeZoneParams, function(err, userTimeZone) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userTimeZone)
                    }
                })
            },
            function(connection, userTimeZone, callback) {
                let timezoneOffset = 43170 // some magic number of seconds representing 11 hrs 59 seconds
                let endOfTodayMoment = moment()

                if (userTimeZone) {
                    // Adjust for the user's timezone
                    endOfTodayMoment = moment.tz(Date.now(), userTimeZone)

                    // getTimezoneOffset returns in # of minutes, so multiply by 60 to convert to seconds
                    let utcOffsetInSeconds = moment.tz(Date.now(), userTimeZone).utcOffset() * 60
                    timezoneOffset = utcOffsetInSeconds * -1 + 43170
                }

                endOfTodayMoment.endOf("day")
                let endTodayInterval = endOfTodayMoment.unix()

                let dueDateSort = `(CASE WHEN duedate=0 THEN ${NO_DATE_SORT_VALUE} ELSE duedate + (CASE WHEN (due_date_has_time=1) THEN 0 ELSE (${timezoneOffset}) END) END)`
                let startDateSubSort = `(CASE WHEN startdate > ${endTodayInterval} THEN startdate + (${timezoneOffset} + 1) ELSE (${endTodayInterval} + 1) END)`
                let startDateSort = `CASE WHEN (startdate != 0 AND (duedate = 0 OR ((startdate + ${timezoneOffset}) < duedate AND duedate > ${endTodayInterval}))) THEN ${startDateSubSort} ELSE ${dueDateSort} END`

                // If the task is sorting by start date, add a secondary sort by due date in case the start dates are equal
                let secondaryDateSort = `CASE WHEN (startdate != 0 AND (duedate = 0 OR ((startdate + ${timezoneOffset}) < duedate AND duedate > ${endTodayInterval}))) THEN ${dueDateSort} ELSE 0 END`

                let dueDateSortStatement = `${startDateSort},${secondaryDateSort}`
                callback(null, connection, dueDateSortStatement)
            },
            function(connection, dueDateSortStatement, callback) {
                let sortStatement = ""
                switch(sortType) {
                    case constants.SortType.PriorityDateAlpha: {
                        sortStatement = ` ORDER BY ${PRIORITY_ORDER_BY_STATEMENT},${dueDateSortStatement}, sort_order, name ASC`
                        break;
                    }
                    case constants.SortType.Alphabetical: {
                        sortStatement = ` ORDER BY name ASC, ${dueDateSortStatement},${PRIORITY_ORDER_BY_STATEMENT}`
                        break;
                    }
                    case constants.SortType.DatePriorityAlpha:
                    default: {
                        sortStatement = ` ORDER BY ${dueDateSortStatement},${PRIORITY_ORDER_BY_STATEMENT}, sort_order, name ASC`
                        break;
                    }
                }

                callback(null, connection, sortStatement)
            }
        ],
        function(err, connection, sortStatement) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not build a sort order statement for a user (${userid}) for sortType = ${sortType}.`))))
            } else {
                completion(null, sortStatement)
            }
        })
    }

    static tasksForSmartList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid
        const listid = params.listid
        const selectedDates = params.selectedDates != undefined ? params.selectedDates : []
        const completedOnly = params.completed_only != undefined ? Boolean(params.completed_only) : false

        let page = params.page ? Number(params.page) : 0
        let pageSize = params.page_size ? Number(params.page_size) : constants.defaultPagedTasks
        if (pageSize > constants.maxPagedTasks) { pageSize = constants.maxPagedTasks }

        let offset = page * pageSize

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        let userTimeZone = null

        // Variables that will be set so they don't have to continuously be
        // passed during every async.waterfall() function.
        let smartList = null

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.tasksForSmartList() missing the listid.`))))
            return
        }
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.tasksForSmartList() missing the userid.`))))
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
                // Read the smart list. If the user is not authorized for the smart
                // list, an unauthorized error will be returned, so there's no need
                // to do any additional authorization checks here.
                const readParams = {
                    listid: listid,
                    userid: userid,
                    dbConnection: connection
                }
                TCSmartListService.getSmartList(readParams, function(err, theSmartList) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        smartList = theSmartList
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Read the user's timezone
                const timeZoneParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCUserSettingsService.getUserTimeZone(timeZoneParams, function(err, theTimeZone) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        userTimeZone = theTimeZone
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Determine the sort order to use. If the sort_order value is < 0,
                // we need to use the default sort order on the user's settings.
                if (smartList.sort_order == undefined || smartList.sort_order < 0) {
                    const sortOrderParams = {
                        userid: userid,
                        dbConnection: connection
                    }
                    TCUserSettingsService.getSortOrder(params, function(err, defaultSortOrder) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection, defaultSortOrder)
                        }
                    })
                } else {
                    callback(null, connection, smartList.sort_order)
                }
            },
            function(connection, sortOrder, callback) {
                const smartListParams = {
                    smartList: smartList,
                    selectedDates: selectedDates,
                    completedOnly: completedOnly,
                    pageSize: pageSize,
                    offset: offset,
                    userTimeZone: userTimeZone,
                    sortOrder: sortOrder,
                    dbConnection: connection
                }
                TCSmartListService.tasksForSmartList(smartListParams, function(err, tasks) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, tasks)
                    }
                })
            },
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
                        `Could not find any tasks for the smart list (${listid}).`))))
            } else {
                completion(null, {tasks: tasks})
            }
        })
    }

    /**
     * Create a single or multiple tasks
     * @param {*} params 
     * @param {*} completion 
     */
    static addTasks(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const tasks = params.tasks != undefined ? params.tasks : null
        if (!tasks) {
            TCTaskService.addTask(params, function(err, result) {
                if (err) {
                    completion(err)
                } else {
                    completion(null, result)
                }
            })
            return
        }

        var parsedTasks = null
        try {
            parsedTasks = JSON.parse(tasks)
        } catch(e) {
            completion(new Error(JSON.stringify(errors.invalidParameters)))
            return
        }

        // Loop through the different tasks, creating them one by one, and
        // build a results array that can be returned to the client.
        if (parsedTasks.length > constants.maxCreateBulkTasks) {
            completion(new Error(JSON.stringify(errors.maxBulkTasksExceeded)))
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
                var createdTasks = Array()
                async.eachSeries(parsedTasks,
                    function(newTaskParams, eachCallback) {
                        let clientTaskID = newTaskParams.client_taskid
                        newTaskParams.userid = params.userid
                        newTaskParams.dbConnection = connection
                        TCTaskService.addTask(newTaskParams, function(err, newTask) {
                            if (err) {
                                createdTasks.push({
                                    client_taskid: clientTaskID,
                                    error: JSON.parse(err.message)
                                })
                            } else {
                                createdTasks.push({
                                    client_taskid: clientTaskID,
                                    task: newTask
                                })
                            }
                            async.nextTick(eachCallback)
                        })
                    }, function(err) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection, createdTasks)
                        }
                    }
                )
            }
        ], 
        function(err, connection, createdTasks) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not create tasks for the listid (${params.listid}).`))))
            } else {
                completion(null, {tasks: createdTasks})
            }
        })
        
    }

    static addTask(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        params.listid = params.listid  != undefined ? params.listid : null
        params.parentid = params.parentid != undefined ? params.parentid : null
        params.name = params.name && typeof params.name == 'string' ? params.name.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!params.name || params.name.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.addTask : Missing task name.'))))
            return
        }

        // Prevent an invalid advanced recurrence state from happening by ensuring
        // that if an advanced recurrence type is specified, an accompanying advanced
        // recurrence string is also specified. https://github.com/Appigo/todo-issues/issues/3477
        const recurrenceType = params.recurrence_type ? params.recurrence_type : null
        const advancedRecurrenceString = params.advanced_recurrence_string ? params.advanced_recurrence_string : null

        if (recurrenceType != undefined && (recurrenceType == constants.TaskRecurrenceType.Advanced || recurrenceType == constants.TaskRecurrenceType.Advanced + 100)) {
            if (!advancedRecurrenceString) {
                // This is a NO-NO. Invalid state. Instead of bailing out and
                // returning an error (which can cause sync to die), change
                // the recurrence type to none (0). https://github.com/Appigo/todo-issues/issues/4219
                logger.debug(`addTask() found advanced recurrence type specified with no recurrence string. Repairing by setting recurrence to none. Task name: ${params.name}`)
                params.recurrence_type = constants.TaskRecurrenceType.None
            }
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
                if (!params.listid || params.listid.length == 0) {
                    const inboxParams = {
                        userid: params.userid,
                        dbConnection: connection
                    }
                    TCUserSettingsService.getUserInboxID(inboxParams, function(err, inboxid) {
                        if (err) {
                            callback(err, connection)
                        } else if (inboxid) {
                            params.listid = inboxid
                            callback(null, connection)
                        } else {
                            callback(new Error(JSON.stringify(errors.customError(errors.accountSettingsNotFound, `No inbox is specified in the user account settings.`))), connection)
                        }
                    })
                } else {
                    callback(null, connection)
                }
            },
            function(connection, callback) {
                if (!params.parentid) {
                    callback(null, connection)
                    return
                }

                // If creating a subtask, make sure that the listid is the same as the parent list id.
                const parentReadParams = {
                    userid : params.userid,
                    taskid : params.parentid,
                    dbConnection : connection
                }
                TCTaskService.getTask(parentReadParams, (err, parentTask) => {
                    if (err) {
                        callback(errors.create(err, 'Error reading parent task for new subtask.'), connection)
                        return
                    }

                    if (parentTask && parentTask.listid) {
                        params.listid = parentTask.listid
                        params.recurrence_type = constants.TaskRecurrenceType.WithParent
                    }
                    
                    callback(null, connection)
                })
            },
            function (connection, callback) {
                const authorizationParams = {
                    listid : params.listid,
                    userid : params.userid,
                    membershipType: constants.ListMembershipType.Member,
                    dbConnection : connection
                }
                TCListMembershipService.isAuthorizedForMembershipType(authorizationParams, function(err, isAuthorized) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error confirming list (${params.listid}) membership authorization for user (${params.userid}).`))), connection)
                        return
                    }
                    if (!isAuthorized) {
                        callback(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                        return
                    }

                    callback(null, connection, isAuthorized)
                })
            },
            function(connection, isAuthorized, callback) {
                // Determine which table the task should be added to depending on
                // the completiondate and deleted parameters.
                let tableName = constants.TasksTable.Normal
                if (params.deleted != undefined && params.deleted > 0) {
                    tableName = constants.TasksTable.Deleted
                } else if (params.completiondate != undefined && params.completiondate > 0) {
                    tableName = constants.TasksTable.Completed
                }

                const newTask = new TCTask(params, tableName)
                newTask.add(connection, function(err, task) {
                    if(err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding new task (${params.name}): ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, task)
                    }
                })
            },
            function(connection, task, callback) {
                // Update the task timestamp on the list so that sync clients
                // know that there's been a change.
                const listParams = {
                    listid: task.listid,
                    timestamp: Math.floor(Date.now() / 1000),
                    dbConnection: connection
                }
                TCTaskService.updateTaskTimestampForList(listParams, function(err, result) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, task)
                    }
                })
            },
            function(connection, task, callback) {
                // Add a change log entry indicating that a task has been created.
                const changeParams = {
                    listid: task.listid,
                    userid: params.userid,
                    itemid: task.taskid,
                    itemName: task.name,
                    itemType: constants.ChangeLogItemType.Task,
                    changeType: constants.ChangeLogType.Add,
                    changeLocation: constants.ChangeLogLocation.API,
                    dbConnection: connection
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    // Since the change log isn't critical to the overall system (in a sense),
                    // do not fail the addTask() if there's a problem adding a change log entry.
                    if (err) {
                        logger.debug(`Error recording change to changelog during addTask(): ${err}`)
                    }
                    callback(null, connection, task)
                })
            },

            // In order to support Intelligent Task Parsing (https://github.com/Appigo/todo-issues/issues/3499)
            // Look for parsed_tags, parsed_taskitos, and parsed_subtasks and process them
            // accordingly.

            function(connection, task, callback) {
                // Process any tags
                if (params.parsed_tags) {
                    const tagParams = {
                        userid: params.userid,
                        taskid: task.taskid,
                        parsedTags: params.parsed_tags,
                        dbConnection: connection
                    }
                    TCTaskService.processParsedTags(tagParams, function(err, result) {
                        callback(err, connection, task)
                    })
                } else {
                    callback(null, connection, task) // continue on w/o doing anything
                }
            },
            function(connection, task, callback) {
                if (task.isProject() && params.parsed_subtasks) {
                    const projectParams = {
                        userid : params.userid,
                        project: task,
                        parsedSubtasks: params.parsed_subtasks,
                        dbConnection: connection
                    }
                    TCTaskService.processParsedSubtasks(projectParams, function(err, result) {
                        callback(err, connection, task)
                    })
                } else if (task.isChecklist() && params.parsed_taskitos) {
                    const checklistParams = {
                        userid : params.userid,
                        checklist: task,
                        parsedTaskitos: params.parsed_taskitos,
                        dbConnection: connection
                    }
                    TCTaskService.processParsedTaskitos(checklistParams, function(err, result) {
                        callback(err, connection, task)
                    })
                } else {
                    callback(null, connection, task) // continue on w/o doing anything
                }
            }
        ], 
        function(err, connection, task) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not create task for the listid (${params.listid}).`))))
            } else {
                completion(null, task)
            }
        })
    }

    static completeTasks(params, completion) {
        params.completiondate = Math.floor(Date.now() / 1000)
        TCTaskService.updateTasksCompletion(params, function(err, result) {
            if (err) {
                completion(err)
            } else {
                completion(null, result)
            }
        })
    }

    static uncompleteTasks(params, completion) {
        params.completiondate = 0
        TCTaskService.updateTasksCompletion(params, function(err, result) {
            if (err) {
                completion(err)
            } else {
                completion(null, result)
            }
        })
    }

    static updateTasksCompletion(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const taskIDs = params.tasks != undefined ? params.tasks : []
        const completionDate = params.completiondate != undefined ? params.completiondate : 0

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction
        
        if (!userid || userid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.updateTasksCompletion() : Missing userid.'))))
            return
        }
        if (!taskIDs || taskIDs.constructor !== Array || taskIDs.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.updateTasksCompletion() : Missing tasks.'))))
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
                let allRepeatedTasks = []
                let allNewTasks = []
                async.whilst(
                    function() {
                        // Return true to keep going
                        return index < taskIDs.length
                    },
                    function(doWhilstCallback) {
                        const taskid = taskIDs[index]
                        index++
                        const completeTaskParams = {
                            userid: userid,
                            taskid: taskid,
                            dbTransaction: transaction
                        }
                        if (completionDate > 0) {
                            TCTaskService.completeTask(completeTaskParams, function(err, results) {
                                if (err) {
                                    doWhilstCallback(err)
                                } else {
                                    if (results.completedTaskIDs && results.completedTaskIDs.length > 0) {
                                        allCompletedTaskIDs = allCompletedTaskIDs.concat(results.completedTaskIDs)
                                    }
                                    if (results.repeatedTasks && results.repeatedTasks.length > 0) {
                                        allRepeatedTasks = allRepeatedTasks.concat(results.repeatedTasks)
                                    }
                                    if (results.newTasks && results.newTasks.length > 0) {
                                        allNewTasks = allNewTasks.concat(results.newTasks)
                                    }
                                    doWhilstCallback(null)
                                }
                            })
                        } else {
                            TCTaskService.uncompleteTask(completeTaskParams, function(err, completedTaskIDs) {
                                if (err) {
                                    doWhilstCallback(err)
                                } else {
                                    if (completedTaskIDs.length > 0) {
                                        allCompletedTaskIDs = allCompletedTaskIDs.concat(completedTaskIDs)
                                    }
                                    doWhilstCallback(null)
                                }
                            })
                        }
                    },
                    function(err) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            const tasks = {
                                completedTaskIDs: allCompletedTaskIDs,
                                repeatedTasks: allRepeatedTasks,
                                newTasks: allNewTasks
                            }
                            callback(null, transaction, tasks)
                        }
                    }
                )
            }
        ], 
        function(err, transaction, tasks) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Error updating the completiondate for task(s).`))))
                if (shouldCleanupDB) {
                    // if (transaction && transaction.state() !== 'closed') {
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not update the completiondate of a task(s). Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, tasks)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, tasks)
                }
            }
        })
    }

    // Should return a list of task IDs that were completed (including subtasks)
    static completeTask(params, completion) {
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        // We have a gatekeeper of API Gateway that wouldn't pass this along,
        // but when we call this recursively, we can pass in preauthorization
        // so that we don't have to check to see if the user is authorized to
        // delete the specified taskid.
        let isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction

        let allCompletedTaskIDs = []
        let allRepeatedTasks = []
        let allNewTasks = []

        let userTimeZone = null
        
        if(!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.completeTask() missing taskid.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.completeTask() missing userid.'))))
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
                const getTaskParams = {
                    taskid: taskid,
                    userid: userid,
                    preauthorized: isPreauthorized,
                    dbConnection: transaction
                }
                TCTaskService.getTask(getTaskParams, function(err, task) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        // If the code makes it here, we've read the task and the
                        // userid is authorized to view it. This method gets called
                        // recursively for any subtasks so it's not really necessary
                        // to keep checking for authorization. Keep track of that
                        // here by setting isPreauthorized.
                        isPreauthorized = true

                        // If the task is completed return an error
                        if (task.isCompleted()) {
                            callback(new Error(JSON.stringify(errors.taskAlreadyCompleted)), transaction)
                        } else {
                            callback(null, transaction, task)
                        }
                    }
                })
            },
            function(transaction, task, callback) {
                // Read the user's timezone
                const timeZoneParams = {
                    userid: userid,
                    dbConnection: transaction
                }
                TCUserSettingsService.getUserTimeZone(timeZoneParams, function(err, aTimeZone) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        userTimeZone = aTimeZone
                        callback(null, transaction, task)
                    }
                })
            },
            function(transaction, task, callback) {
                if (task.isProject()) {
                    const subtaskParams = {
                        taskid: taskid,
                        userid: userid,
                        activeOnly: true,
                        preauthorized: isPreauthorized,
                        dbConnection: transaction
                    }
                    TCTaskService.getSubtasksForProject(subtaskParams, function(err, subtaskIDs) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            async.each(subtaskIDs,
                            function(subtaskID, eachCallback) {
                                const subtaskCompleteParams = {
                                    taskid: subtaskID,
                                    userid: userid,
                                    dbTransaction: transaction,
                                    preauthorized: true // skip the user authorization step to verify that the user has access to delete the task
                                }
                                TCTaskService.completeTask(subtaskCompleteParams, function(err, result) {
                                    if (err) {
                                        eachCallback(err)
                                    } else {
                                        allCompletedTaskIDs.push(subtaskID)
                                        eachCallback()
                                    }
                                })
                            },
                            function(err) {
                                if (err) {
                                    // One of the subtasks had an error completing
                                    callback(err, transaction)
                                } else {
                                    // All active subtasks were completed successfully
                                    callback(null, transaction, task)
                                }
                            })
                        }
                    })
                } else if (task.isChecklist()) {
                    const taskitoParams = {
                        taskid: taskid,
                        userid: userid,
                        preauthorized: isPreauthorized,
                        dbConnection: transaction
                    }
                    TCTaskitoService.completeChildrenOfChecklist(taskitoParams, function(err, result) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            callback(null, transaction, task)
                        }
                    })
                } else {
                    callback(null, transaction, task)
                }
            },
            function(transaction, task, callback) {
                // Set a completion date for the task so that recurrence processing
                // will function the right way.
                task.completiondate = Math.floor(Date.now() / 1000)
                const recurrenceParams = {
                    task: task,
                    userid: userid,
                    userTimeZone: userTimeZone,
                    preauthorized: isPreauthorized,
                    dbTransaction: transaction
                }
                // TCTaskService.processRecurrenceForTask(recurrenceParams, function(err, processedTask, completedTask) {
                TCTaskService.processRecurrenceForTask(recurrenceParams, function(err, recurrenceResults) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        allCompletedTaskIDs.push(task.taskid)
                        if (recurrenceResults.repeatedTasks && recurrenceResults.repeatedTasks.length > 0) {
                            allRepeatedTasks = allRepeatedTasks.concat(recurrenceResults.repeatedTasks)
                        }
                        if (recurrenceResults.newTasks && recurrenceResults.newTasks.length > 0) {
                            allNewTasks = allNewTasks.concat(recurrenceResults.newTasks)
                        }
                        callback(null, transaction, task, recurrenceResults.completedTask)
                    }
                })
            },
            function(transaction, task, completedTask, callback) {
                // If task.completiondate == 0, the recurrence processing updated the
                // task and we should just update the task now.
                if (task.completiondate == undefined || task.completiondate == 0) {
                    task.update(transaction, function(err, updatedTask) {
                        if(err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating task (${task.taskid}): ${err.message}`))), transaction)
                        }
                        else {
                            allRepeatedTasks.push(updatedTask)
                            callback(null, transaction, updatedTask, completedTask)
                        }
                    })
                } else {
                    // Call addTask() to add the completed task and then delete the
                    // task from the current table.
                    const newTaskParams = {
                        userid: userid,
                        dbConnection: transaction
                    }
                    TCTaskService.populateParamsFromTask(newTaskParams, task)
                    TCTaskService.addTask(newTaskParams, function(err, newTask) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            // Delete the task from the tdo_tasks table
                            task.setTableName(constants.TasksTable.Normal)
                            task.delete(transaction, function(err, result) {
                                if (err) {
                                    callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a task: ${err.message}.`))), transaction)
                                } else {
                                    callback(null, transaction, task, completedTask)
                                }
                            })
                        }
                    })
                }
            },
            function(transaction, task, completedTask, callback) {
                if (!task.isSubtask()) {
                    callback(null, transaction, task, completedTask)
                    return
                }

                const getParentFunction = (task) => (innerNext) => {
                    const getParams = {
                        userid : params.userid,
                        taskid : task.parentid,
                        isPreauthorized : true,
                        dbConnection : transaction
                    }
                    
                    TCTaskService.getTask(getParams, (err, parent) => {
                        if (err) {
                            innerNext(err)
                            return
                        }

                        innerNext(null, parent)
                    })
                }

                const updateParentFunction = (parent, innerNext) => {
                    delete parent.completiondate
                    delete parent.deleted
                    const updateParams = Object.assign(parent, {
                        userid : params.userid,
                        dbTransaction : transaction
                    })
                    TCTaskService.updateTask(updateParams, (err, result) => {
                        innerNext(err)
                    })
                }

                const waterfallFunctions = [
                    getParentFunction(task),
                    updateParentFunction
                ]

                async.waterfall(waterfallFunctions, function(err) {
                    callback(err, transaction, task, completedTask)
                })
            },
            function(transaction, task, completedTask, callback) {
                // Update the task timestamp on the list so that sync clients
                // know that there's been a change.
                const listParams = {
                    listid: task.listid,
                    timestamp: Math.floor(Date.now() / 1000),
                    dbConnection: transaction
                }
                TCTaskService.updateTaskTimestampForList(listParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, task, completedTask)
                    }
                })
            },
            function(transaction, task, completedTask, callback) {
                const changeData = {
                    'old-completiondate': task.completiondate,
                    'completiondate': completedTask.completiondate
                }

                const changeParams = {
                    listid: task.listid,
                    userid: params.userid,
                    itemid: task.taskid,
                    itemName: task.name,
                    itemType: constants.ChangeLogItemType.Task,
                    changeType: constants.ChangeLogType.Modify,
                    changeLocation: constants.ChangeLogLocation.API,
                    changeData: JSON.stringify(changeData),
                    dbConnection: transaction
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    // Since the change log isn't critical to the overall system (in a sense),
                    // do not fail the addTask() if there's a problem adding a change log entry.
                    if (err) {
                        logger.debug(`Error recording change to changelog during completeTask(): ${err}`)
                    }
                    callback(null, transaction, task)
                })
            }
        ], 
        function(err, transaction) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not complete a task (${taskid}).`))))
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
                const result = {
                    completedTaskIDs: allCompletedTaskIDs,
                    repeatedTasks: allRepeatedTasks,
                    newTasks: allNewTasks
                }
                if (shouldCleanupDB) {
                    if (transaction) {
                        transaction.commit(function(err, result) {
                            if (err) {
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not complete a task (${taskid}). Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, result)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, result)
                }
            }
        })
    }

    // Should return a list of task IDs that were completed (including subtasks)
    static uncompleteTask(params, completion) {

        // 1. Read the task
        // 2. Verify permissions (automatically happens by virtue of reading the task in the first place)
        // 3. Delete from tdo_completed_tasks
        // 4. Set a new timestamp
        // 5. Set a completiondate of 0
        // 6. Add to normal tasks table

        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction

        let allUncompletedTaskIDs = []
        const changeData = {} // Used later to log to tdo_change_log
        
        if(!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.uncompleteTask() missing taskid.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.uncompleteTask() missing userid.'))))
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
                const getTaskParams = {
                    taskid: taskid,
                    userid: userid,
                    dbConnection: transaction
                }
                TCTaskService.getTask(getTaskParams, function(err, task) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        // If the code makes it here, we've read the task and the
                        // userid is authorized to view it.

                        // If the task is not completed return an error
                        if (!task.isCompleted()) {
                            callback(new Error(JSON.stringify(errors.taskNotCompleted)), transaction)
                        } else {
                            callback(null, transaction, task)
                        }
                    }
                })
            },
            function(transaction, task, callback) {
                if (task._tableName != "tdo_completed_tasks") {
                    // This is not a completed task, so we should
                    // err out with an invalid params.
                    callback(new Error(JSON.stringify(errors.customError(errors.invalidParameters, `uncompleteTask() called with a task (${taskid}) that is not a completed task.`))), transaction)
                } else {
                    changeData['old-completiondate'] = task.completiondate
                    changeData['completiondate'] = task.completiondate
                    // Add the task to the normal tasks
                    task.timestamp = Math.floor(Date.now() / 1000)
                    task.completiondate = 0
                    const newTaskParams = {
                        userid: userid,
                        dbConnection: transaction
                    }
                    TCTaskService.populateParamsFromTask(newTaskParams, task)
                    TCTaskService.addTask(newTaskParams, function(err, newTask) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            allUncompletedTaskIDs.push(newTask.taskid)

                            // Delete the task from the tdo_completed_tasks table
                            task.setTableName(constants.TasksTable.Completed)
                            task.delete(transaction, function(err, result) {
                                if (err) {
                                    callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a task: ${err.message}.`))), transaction)
                                } else {
                                    callback(null, transaction, newTask)
                                }
                            })
                        }
                    })
                }
            },
            function(transaction, task, callback) {
                // Update the task timestamp on the list so that sync clients
                // know that there's been a change.
                const listParams = {
                    listid: task.listid,
                    timestamp: Math.floor(Date.now() / 1000),
                    dbConnection: transaction
                }
                TCTaskService.updateTaskTimestampForList(listParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, task)
                    }
                })
            },
            function(transaction, task, callback) {
                const changeParams = {
                    listid: task.listid,
                    userid: userid,
                    itemid: task.taskid,
                    itemName: task.name,
                    itemType: constants.ChangeLogItemType.Task,
                    changeType: constants.ChangeLogType.Modify,
                    changeLocation: constants.ChangeLogLocation.API,
                    changeData: JSON.stringify(changeData),
                    dbConnection: transaction
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    // Since the change log isn't critical to the overall system (in a sense),
                    // do not fail the addTask() if there's a problem adding a change log entry.
                    if (err) {
                        logger.debug(`Error recording change to changelog during uncompleteTask(): ${err}`)
                    }
                    callback(null, transaction, task)
                })
            },
            function(transaction, task, callback) {
                if (task.parentid && task.parentid.length > 0) {
                    // Read the parent and send it into fixupChildPropertiesForTask()
                    const parentIdParams = {
                        taskid: task.parentid,
                        userid: userid,
                        isPreauthorized: true,
                        dbConnection: transaction
                    }
                    TCTaskService.getTask(parentIdParams, function(err, parentTask) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            const fixupParams = {
                                task: parentTask,
                                userid: userid, // Needed later to read the user's timezone setting
                                dbConnection: transaction
                            }
                            TCTaskService.fixupChildPropertiesForTask(fixupParams, function(err, result) {
                                if (err) {
                                    callback(err, transaction)
                                } else {
                                    callback(null, transaction, task)
                                }
                            })
                        }
                    })
                } else {
                    callback(null, transaction, task)
                }
            },
            function(transaction, task, callback) {
                if (task.isProject()) {
                    const fixupParams = {
                        task: task,
                        userid: userid,
                        dbConnection: transaction
                    }
                    TCTaskService.fixupChildPropertiesForTask(fixupParams, function(err, result) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            callback(null, transaction, task)
                        }
                    })
                } else {
                    callback(null, transaction, task)
                }
            },
            function(transaction, task, callback) {
                if (!task.isSubtask()) {
                    callback(null, transaction, task)
                    return
                }

                const getParentFunction = (task) => (innerNext) => {
                    const getParams = {
                        userid : params.userid,
                        taskid : task.parentid,
                        isPreauthorized : true,
                        dbConnection : transaction
                    }
                    
                    TCTaskService.getTask(getParams, (err, parent) => {
                        if (err) {
                            innerNext(err)
                            return
                        }

                        innerNext(null, parent)
                    })
                }

                const updateParentFunction = (parent, innerNext) => {
                    delete parent.completiondate
                    delete parent.deleted
                    const updateParams = Object.assign(parent, {
                        userid : params.userid,
                        dbTransaction : transaction
                    })
                    TCTaskService.updateTask(updateParams, (err, result) => {
                        innerNext(err)
                    })
                }

                const waterfallFunctions = [
                    getParentFunction(task),
                    updateParentFunction
                ]

                async.waterfall(waterfallFunctions, function(err) {
                    callback(err, transaction, task)
                })
            }
        ], 
        function(err, transaction) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not uncomplete a task (${taskid}).`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not uncomplete a task (${taskid}). Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, allUncompletedTaskIDs)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, allUncompletedTaskIDs)
                }
            }
        })
    }

    static getTask(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }
        
        params.userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        params.taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!params.userid || params.userid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.getTask() : Missing userid.'))))
            return
        }
        if (!params.taskid || params.taskid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.getTask() : Missing taskid.'))))
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
                // Use async.whilst() to read each task table until we find the task
                // we are looking for. async.whilst() allows us to bail as soon as we've
                // successfully read a task.
                let task = null
                let taskTableNames = constants.TasksTableNames
                let index = 0
                async.whilst(
                    function() {
                        // Test to see if the whilst should stop
                        let result = index < taskTableNames.length && !task
                        return result
                    },
                    function(whilstCallback) {
                        const aTask = new TCTask({
                            taskid: params.taskid
                        }, taskTableNames[index])
                        index++
                        aTask.read(connection, function(err, theTask) {
                            if (err) {
                                whilstCallback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading task (${aTask.taskid}) from table (${aTask.tableName}): ${err.message}.`))))
                            } else {
                                if (theTask) {
                                    task = theTask
                                    whilstCallback(null, theTask)
                                } else {
                                    whilstCallback(null)
                                }
                            }
                        })
                    },
                    function(err) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection, task)
                        }
                    }
                )
            }
        ], 
        function(err, connection, task) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not read task (${params.taskid}).`))))
            } else {
                if (task) {
                    completion(null, task)
                } else {
                    completion(new Error(JSON.stringify(errors.taskNotFound)))
                }
            }
        })
    }

    static getTaskForSyncId(params, completion) {
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
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.getTaskForSyncId() : Missing userid.'))))
            return
        }
        if (!params.syncid || params.syncid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.getTaskForSyncId() : Missing syncid.'))))
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
                // Use async.whilst() to read from each task table until we find the taskid
                // we are looking for. async.whilst() allows us to bail as soon as we've
                // successfully read a taskid.
                let taskid = null
                let taskTableNames = constants.TasksTableNames
                let index = 0
                async.whilst(
                    function() {
                        // Test to see if the whilst should stop
                        let result = index < taskTableNames.length && !taskid
                        return result
                    },
                    function(whilstCallback) {
                        const sql = `SELECT taskid FROM ${taskTableNames[index]} WHERE sync_id = ?`
                        index++
                        connection.query(sql, [params.syncid], function(err, result) {
                            if (err) {
                                whilstCallback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                `Error running query: ${err.message}`))), connection)
                            } else {
                                if (result.rows && result.rows.length > 0) {
                                    taskid = result.rows[0].taskid
                                }
                                whilstCallback(null)
                            }
                        })
                    },
                    function(err) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            callback(null, connection, taskid)
                        }
                    }
                )
            },
            function(connection, taskid, callback) {
                if (taskid) {
                    // Now just use our normal getTask() method
                    const getTaskParams = {
                        userid: params.userid,
                        taskid: taskid,
                        dbConnection: connection,
                        preauthorized: true
                    }
                    TCTaskService.getTask(getTaskParams, function(err, task) {
                        callback(err, connection, task)
                    })
                } else {
                    callback(null, connection, null)
                }
            }
        ], 
        function(err, connection, task) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not find task for syncid (${params.syncid}).`))))
            } else {
                completion(null, task) // Note, task may be null (which means we don't have a task with the requested syncid)
            }
        })
    }

    static updateTask(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }
        
        params.userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        params.taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        params.listid = params.listid && typeof params.listid == 'string' ? params.listid.trim() : null

        // In order to keep the notification system working properly, we have to
        // log all changes to the ChangeLog. In order to do this, the values of
        // the original task must be known. We'll populate the oldTask variable
        // as part of updateTask()
        let oldTask = null
        let changingLists = false

        if (params.isSyncService == undefined ) {
            // completiondate and delete parameters cannot be specified as part of
            // an updateTask() call. Since there is so much that needs to happen
            // during the deleteTask() and completeTask() operations, those must
            // occur individually.
            if (params.completiondate != undefined) {
                completion(new Error(JSON.stringify(errors.customError(errors.invalidParameters, `TCTaskService.updateTask() : completiondate parameter is not allowed.`))))
                return
            }
            if (params.deleted != undefined) {
                completion(new Error(JSON.stringify(errors.customError(errors.invalidParameters, `TCTaskService.updateTask() : deleted parameter is not allowed.`))))
                return
            }
        }

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction
        
        if (!params.userid || params.userid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.updateTask() : Missing userid.'))))
            return
        }
        if (!params.taskid || params.taskid == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.updateTask() : Missing taskid.'))))
            return
        }

        // Prevent an invalid advanced recurrence state from happening by ensuring
        // that if an advanced recurrence type is specified, an accompanying advanced
        // recurrence string is also specified. https://github.com/Appigo/todo-issues/issues/3477
        const recurrenceType = params.recurrence_type ? params.recurrence_type : null
        const advancedRecurrenceString = params.advanced_recurrence_string ? params.advanced_recurrence_string : null

        if (recurrenceType != undefined && (recurrenceType == constants.TaskRecurrenceType.Advanced || recurrenceType == constants.TaskRecurrenceType.Advanced + 100)) {
            if (!advancedRecurrenceString) {
                // This is a NO-NO. Invalid state. Instead of bailing out and
                // returning an error (which can cause sync to die), change
                // the recurrence type to none (0). https://github.com/Appigo/todo-issues/issues/4219
                logger.debug(`updateTask() found advanced recurrence type specified with no recurrence string. Repairing by setting recurrence to none. Task ID: ${params.taskid}`)
                params.recurrence_type = constants.TaskRecurrenceType.None
            }
        }
        
        async.waterfall([
            // Get transaction
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
            // List Authorizations
            function(transaction, callback) {
                // Read the listid of the existing task so we can make sure the
                // user is authorized to update the task.
                const listParams = {
                    taskid: params.taskid,
                    dbConnection: transaction
                }
                TCTaskUtils.listIDForTask(listParams, function(err, originalListid) {
                    if (err) {
                        callback(err, transaction)
                    } else if (originalListid) {
                        callback(null, transaction, originalListid)
                    } else {
                        callback(new Error(JSON.stringify(errors.taskNotFound)), transaction)
                    }
                })
            },
            function (transaction, originalListid, callback) {
                // Check that the user is authorized to modify tasks in the list
                // where the task currently belongs
                const authorizationParams = {
                    listid: originalListid,
                    userid: params.userid,
                    membershipType: constants.ListMembershipType.Member,
                    dbConnection: transaction
                }
                TCListMembershipService.isAuthorizedForMembershipType(authorizationParams, function(err, isAuthorized) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(err, `Error confirming list (${originalListid}) membership authorization for user (${params.userid}).`))), transaction)
                    }
                    else {
                        if (!isAuthorized) {
                            callback(new Error(JSON.stringify(errors.unauthorizedError)), transaction)
                        } else {
                            callback(null, transaction, originalListid)
                        }
                    }
                })
            },
            function(transaction, originalListid, callback) {
                // If the params specify a different listid than the original listid,
                // the user is trying to move the task to a different list. Make sure
                // that the user is authorized to move the task to the new list.
                changingLists = params.listid != originalListid
                if (params.listid && changingLists) {
                    const authorizationParams = {
                        listid: params.listid,
                        userid: params.userid,
                        membershipType: constants.ListMembershipType.Member,
                        dbConnection: transaction
                    }
                    TCListMembershipService.isAuthorizedForMembershipType(authorizationParams, function(err, isAuthorized) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(err, `Error confirming list membership authorization for new list (${params.listid})`))), transaction)
                        } else {
                            if (!isAuthorized) {
                                callback(new Error(JSON.stringify(errors.unauthorizedError)), transaction)
                            } else {
                                callback(null, transaction)
                            }
                        }
                    })
                } else {
                    callback(null, transaction)
                }
            },
            // Read in original task
            function(transaction, callback) {
                // Before making any changes, read the unchanged original task
                const origParams = {
                    userid: params.userid,
                    taskid: params.taskid,
                    dbConnection : transaction,
                    isPreauthorized : true
                }

                TCTaskService.getTask(origParams, function(err, theOriginalTask) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading original task during updateTask(): ${err.message}`))), transaction)
                    } else {
                        // Save off the original task for reference later
                        oldTask = theOriginalTask
                        callback(null, transaction)
                    }
                })
            },
            // Figure out which table the task should be in
            function(transaction, callback) {
                if (params.isSyncService == undefined) {
                    // Skip this step
                    callback(null, transaction)
                    return
                } 

                // We need to make sure that the task is in the correct table before
                // actually updating it.
                let destinationTableName = constants.TasksTable.Normal
                if (params.deleted != undefined && params.deleted) {
                    destinationTableName = constants.TasksTable.Deleted
                } else if (params.completiondate != undefined && params.completiondate) {
                    destinationTableName = constants.TasksTable.Completed
                }

                const oldTable = oldTask ? oldTask.tableName() : null
                if (oldTable == destinationTableName) {
                    // No need to move the task into a different table
                    callback(null, transaction)
                    return
                } 

                // Time for a little moving around
                logger.debug(`Sync received an updated task and determined it should move from ${oldTable} to ${destinationTableName}.`)
                const moveParams = {
                    userid: params.userid,
                    taskProperties: params,
                    dbTransaction: transaction,
                    sourceTable: oldTable,
                    destinationTable: destinationTableName
                }
                TCTaskService.moveTaskIntoTable(moveParams, function(moveErr, moveResult) {
                    if (moveErr) {
                        callback(moveErr, transaction)
                        return
                    }
                    callback(null, transaction)
                })
            },
            // Convert task types
            function (transaction, callback) {
                const next = (err, result) => {
                    if (err) {
                        callback(err, transaction)
                        return
                    }

                    callback(null, transaction)
                }

                const convertParams = {
                    userid : params.userid,
                    taskid : params.taskid,
                    parentTask : oldTask,
                    dbConnection : transaction
                }

                if (!oldTask || !oldTask.isProject || !oldTask.isChecklist) {
                    next (null, {})
                    return
                }

                if (oldTask.isProject() && params.task_type == constants.TaskType.Checklist) {
                    logger.debug('Converting from project to checklist')
                    TCTaskService.convertProjectToChecklist(convertParams, next)
                }
                else if (oldTask.isProject() && params.task_type == constants.TaskType.Normal) {
                    logger.debug('Converting from project to normal')
                    TCTaskService.convertToNormalTask(convertParams, next)
                }
                else if (oldTask.isChecklist() && params.task_type == constants.TaskType.Project) { 
                    logger.debug('Converting from checklist to project')
                    TCTaskService.convertChecklistToProject(convertParams, next)
                }
                else if (oldTask.isChecklist() && params.task_type == constants.TaskType.Normal) { 
                    logger.debug('Converting from checklist to normal')
                    TCTaskService.convertToNormalTask(convertParams, next)
                }
                else {
                    next (null, {})
                }
            },
            // Update project's "subtask" fields
            function(transaction, next) {
                const task = new TCTask(params)
                if (oldTask.isProject && oldTask.isProject() && !task.isProject()) {
                    task.duedate = oldTask.project_duedate
                    task.duedate_has_time = oldTask.project_duedate_has_time
                    task.startdate = oldTask.project_startdate
                    task.priority = oldTask.project_priority
                    task.starred = oldTask.project_starred
                }

                if (task.isProject()) {
                    if (oldTask.isProject && !oldTask.isProject()) {
                        task.project_duedate = oldTask.duedate
                        task.project_duedate_has_time = oldTask.duedate_has_time
                        task.project_startdate = oldTask.startdate
                        task.project_priority = oldTask.priority
                        task.project_starred = oldTask.starred
                    }

                    async.waterfall([
                        function(innerNext) {
                            const starredSQL = `
                                SELECT COUNT(taskid) as count
                                FROM tdo_tasks
                                WHERE (
                                    completiondate = 0 OR completiondate IS NULL
                                )
                                AND (
                                    deleted = 0 OR deleted IS NULL
                                )
                                AND (
                                    parentid = ?
                                )
                                AND (
                                    starred != 0
                                )
                            `

                            transaction.query(starredSQL, [task.taskid], (err, result) => {
                                if (err) {
                                    const message = `Error determining whether there is a starred subtask: ${err.message}`
                                    innerNext(errors.create(errors.databaseError, message))
                                    return
                                }

                                task.starred = result.rows.reduce((accum, r) => accum + r.count, 0) > 0
                                innerNext()
                            })
                        },
                        function (innerNext) {
                            const highestPrioritySQL = `
                                SELECT MIN(priority) as high_task_priority
                                FROM tdo_tasks
                                WHERE (
                                    completiondate = 0 OR completiondate IS NULL
                                )
                                AND (
                                    deleted = 0 OR deleted IS NULL
                                )
                                AND (
                                    parentid = ?
                                )
                                AND (
                                    priority != 0
                                ) 
                            `

                            transaction.query(highestPrioritySQL, [task.taskid], (err, result) => {
                                if (err) {
                                    const message = `Error determining highest subtask priority: ${err.message}`
                                    innerNext(errors.create(errors.databaseError, message))
                                    return
                                }

                                task.priority = result.rows.reduce((accum, r) => {
                                    if (accum == constants.TaskPriority.None) {
                                        return r.high_task_priority
                                    }
                                    return r.high_priority < accum ? r.high_task_priority : accum
                                }, constants.TaskPriority.None)
                                innerNext()
                            })
                        },
                        function(innerNext) {
                            // Read the user's timezone
                            const timeZoneParams = {
                                userid: params.userid,
                                dbConnection: transaction
                            }
                            TCUserSettingsService.getUserTimeZone(timeZoneParams, function(err, userTimeZone) {
                                if (err) {
                                    innerNext(err)
                                } else {
                                    const utcOffsetInSeconds = moment.tz(Date.now(), userTimeZone).utcOffset() * 60
                                    const timezoneOffset = utcOffsetInSeconds * -1 + 43170 
                                    innerNext(null, timezoneOffset)
                                }
                            })
                        },
                        function(timezoneOffset, innerNext) {
                            const earliestTaskSQL =`
                                SELECT duedate, due_date_has_time
                                FROM tdo_tasks
                                WHERE (
                                    completiondate = 0 OR completiondate IS NULL
                                )
                                AND (
                                    duedate IS NOT NULL AND duedate != 0
                                )
                                AND (
                                    deleted = 0 OR deleted IS NULL
                                )
                                AND (
                                    parentid = ?
                                )
                                ORDER BY (
                                    duedate + (CASE 1 WHEN due_date_has_time OR duedate IS NULL THEN 0 ELSE ? END)
                                )
                                LIMIT 1
                            `

                            transaction.query(earliestTaskSQL, [task.taskid, timezoneOffset], (err, result) => {
                                if (err) {
                                    const message = `Error determining soonest subtask due date: ${err.message}`
                                    innerNext(errors.create(errors.databaseError, message))
                                    return
                                }

                                if (result.rows.length > 0) {
                                    result.rows.forEach(row => {
                                        task.duedate = row.duedate
                                        task.due_date_has_time = row.due_date_has_time
                                    })
                                }
                                else {
                                    task.duedate = 0
                                    task.due_date_has_time = false
                                }

                                innerNext(null)
                            })
                        },
                        function(innerNext) {
                            const earliestTaskSQL =`
                                SELECT startdate
                                FROM tdo_tasks
                                WHERE (
                                    completiondate = 0 OR completiondate IS NULL
                                )
                                AND (
                                    deleted = 0 OR deleted IS NULL
                                )
                                AND (
                                    parentid = ?
                                )
                                AND (
                                    startdate IS NOT NULL AND startdate > 0
                                )
                                ORDER BY (
                                    startdate
                                )
                                LIMIT 1
                            `

                            transaction.query(earliestTaskSQL, [task.taskid], (err, result) => {
                                if (err) {
                                    const message = `Error determining highest subtask priority: ${err.message}`
                                    innerNext(errors.create(errors.databaseError, message))
                                    return
                                }

                                result.rows.forEach(row => {
                                    task.startdate = row.startdate
                                })

                                innerNext(null)
                            })
                        }
                    ],
                    function(err) {
                        next(err, transaction, task)
                    })

                    return
                }
                else if(task.isChecklist()) {
                    task.project_duedate = null
                    task.project_startdate = null
                    task.project_priority = constants.TaskPriority.None
                    task.project_starred = false
                    task.project_duedate_has_time = false
                }

                next(null, transaction, task)
            },
            // Perform update
            function(transaction, task, callback) {
                if (params.parentid === null || params.parentid === '') task.parentid = params.parentid
                if (params.assigned_userid === null || params.assigned_userid === '') task.assigned_userid = params.assigned_userid
                if (oldTask && oldTask.isCompleted()) task.setTableName('tdo_completed_tasks')
                task.update(transaction, function(err, task) {
                    if(err) {
                        callback(errors.create(errors.databaseError, `Error updating task (${params.taskid}): ${err.message}`), transaction)
                        return
                    }

                    // When changing the list of a project, the subtasks must also be updated.
                    if (changingLists && oldTask.isProject()) {
                        async.waterfall([
                            function(projectNext) {
                                const getParams = {
                                    userid : params.userid,
                                    taskid : oldTask.taskid,
                                    readFullTask : true,
                                    dbConnection : transaction
                                }
                                TCTaskService.getSubtasksForProject(getParams, (err, subtasks) => {
                                    if (err) {
                                        const message = `Unable to get subtasks when updating project list.`
                                        projectNext(errors.create(errors.invalidParameters, message))
                                        return
                                    }
                                    projectNext(null, subtasks)
                                })
                            },
                            function(subtasks, projectNext) {  
                                async.eachSeries(subtasks, function(subtask, eachNext) {
                                    subtask.listid = task.listid
                                    if(subtask.isCompleted()) subtask.setTableName('tdo_completed_tasks')
                                    subtask.update(transaction, (err, result) => {
                                        if (err) {
                                            const message = `Error updating subtask list when project changed list: ${err.message}`
                                            eachNext(errors.create(errors.databaseError, message))
                                            return
                                        }

                                        eachNext()
                                    })
                                },
                                function(err) {
                                    projectNext(err)
                                })
                            }
                        ],
                        function(err) {
                            callback(err, transaction, task)
                        })
                        return
                    }

                    callback(null, transaction, task)
                })
            },
            // If the changed task is a subtask, the parent task needs to be updated too
            // in order to update the "subtask" fields on the parent.
            function(transaction, task, callback) {
                if (!task.isSubtask() && !(oldTask && oldTask.isSubtask())) {
                    callback(null, transaction, task)
                    return
                }

                const parentId = task.parentid ? task.parentid : 
                    oldTask ? oldTask.parentid : null
                if (!parentId) {
                    callback(null, transaction, task)
                    return
                }
                const getParentFunction = (task) => (innerNext) => {
                    const getParams = {
                        userid : params.userid,
                        taskid : parentId,
                        isPreauthorized : true,
                        dbConnection : transaction
                    }
                    
                    TCTaskService.getTask(getParams, (err, parent) => {
                        if (err) {
                            innerNext(err)
                            return
                        }

                        innerNext(null, parent)
                    })
                }

                const updateParentFunction = (parent, innerNext) => {
                    delete parent.completiondate
                    delete parent.deleted
                    const updateParams = Object.assign(parent, {
                        userid : params.userid,
                        dbTransaction : transaction
                    })
                    TCTaskService.updateTask(updateParams, (err, result) => {
                        innerNext(err)
                    })
                }

                const sameParent = task.parentid == oldTask.parentid
                const movedParent = !sameParent && task.parentid != null && oldTask.parentid != null
                const removedParent = !task.isSubtask() && oldTask.isSubtask()
                const addedParent = task.isSubtask() && !oldTask.isSubtask()

                const waterfallFunctions = () =>{
                    if (sameParent || addedParent) {
                        return [
                            getParentFunction(task),
                            updateParentFunction
                        ]
                    }

                    if (movedParent) {
                        return [
                            getParentFunction(task),
                            updateParentFunction,
                            getParentFunction(oldTask),
                            updateParentFunction
                        ]
                    }

                    if (removedParent) {
                        return [
                            getParentFunction(oldTask),
                            updateParentFunction
                        ]
                    }
                } 

                async.waterfall(waterfallFunctions(), function(err) {
                    callback(err, transaction, task)
                })
            },
            // Update list timestamp
            function(transaction, task, callback) {
                // Update the task timestamp on the list so that sync clients
                // know that there's been a change.
                const listParams = {
                    listid: task.listid,
                    timestamp: Math.floor(Date.now() / 1000),
                    dbConnection: transaction
                }
                TCTaskService.updateTaskTimestampForList(listParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, task)
                    }
                })
            },
            // Change log
            function(transaction, task, callback) {
                const changeParams = {
                    oldTask: oldTask,
                    newTask: task,
                    userid: params.userid,
                    dbConnection: transaction
                }
                TCTaskUtils.updateChangeLog(changeParams, function(err, result) {
                    // Since the change log isn't critical to the overall system (in a sense),
                    // do not fail the addTask() if there's a problem adding a change log entry.
                    if (err) {
                        logger.debug(`Error recording change to changelog during updateTask(): ${err}`)
                    }
                    callback(null, transaction, task)
                })
            }
        ], 
        function(err, transaction, task) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Problem updating a task (${params.taskid}).`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not update a task (${params.taskid}). Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, task)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, task)
                }
            }
        })
    }

    static deleteTask(params, completion) {
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        // We have a gatekeeper of API Gateway that wouldn't pass this along,
        // but when we call this recursively, we can pass in preauthorization
        // so that we don't have to check to see if the user is authorized to
        // delete the specified taskid.
        let isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        let taskAlreadyDeleted = false
        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction
        
        if(!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.deleteTask() Missing taskid.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.deleteTask() Missing userid.'))))
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
                const getTaskParams = {
                    taskid: taskid,
                    userid: userid,
                    preauthorized: isPreauthorized,
                    dbConnection: transaction
                }
                TCTaskService.getTask(getTaskParams, function(err, task) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        // If the code makes it here, we've read the task and the
                        // userid is authorized to view it. This method gets called
                        // recursively for any subtasks so it's not really necessary
                        // to keep checking for authorization. Keep track of that
                        // here by setting isPreauthorized.
                        isPreauthorized = true
                        if (task.deleted) {
                            // The task is already deleted, so there's nothing
                            // really to do. Just bail out, but we don't actually
                            // want to return an error to the API caller.
                            taskAlreadyDeleted = true
                            callback(new Error(`Task is already deleted.`), transaction)
                        } else {
                            callback(null, transaction, task)
                        }
                    }
                })
            },
            function(transaction, task, callback) {
                if (task.isProject()) {
                    const subtaskParams = {
                        taskid: taskid,
                        userid: userid,
                        nonDeleted: true,
                        preauthorized: isPreauthorized,
                        dbConnection: transaction
                    }
                    TCTaskService.getSubtasksForProject(subtaskParams, function(err, subtaskIDs) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            async.each(subtaskIDs,
                            function(subtaskID, eachCallback) {
                                const subtaskDeleteParams = {
                                    taskid: subtaskID,
                                    userid: userid,
                                    dbTransaction: transaction,
                                    preauthorized: true // skip the user authorization step to verify that the user has access to delete the task
                                }
                                TCTaskService.deleteTask(subtaskDeleteParams, function(err, result) {
                                    if (err) {
                                        eachCallback(err)
                                    } else {
                                        eachCallback()
                                    }
                                })
                            },
                            function(err) {
                                if (err) {
                                    // One of the subtasks were not deleted
                                    callback(err, transaction)
                                } else {
                                    // All subtasks were deleted successfully
                                    callback(null, transaction, task)
                                }
                            })
                        }
                    })
                } else if (task.isChecklist()) {
                    const taskitoParams = {
                        checklistid: taskid,
                        userid: userid,
                        preauthorized: isPreauthorized,
                        dbConnection: transaction
                    }
                    TCTaskitoService.deleteChildrenOfChecklist(taskitoParams, function(err, result) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            callback(null, transaction, task)
                        }
                    })
                } else {
                    callback(null, transaction, task)
                }
            },
            function(transaction, task, callback) {
                const notificationParams = {
                    taskid: taskid,
                    userid: userid,
                    preauthorized: true,
                    dbConnection: transaction
                }
                TCTaskNotificationService.deleteAllTaskNotificationsForTask(notificationParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, task)
                    }
                })
            },
            function(transaction, task, callback) {
                const commentParams = {
                    taskid: taskid,
                    userid: userid,
                    preauthorized: true,
                    dbConnection: transaction
                }
                TCCommentService.deleteAllCommentsForTask(commentParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, task)
                    }
                })
            },
            function(transaction, task, callback) {
                // Set the task as deleted and re-add it, causing it to be added
                // to the deleted tasks table (tdo_deleted_tasks). Use the addTask()
                // call because it handles a bunch of other things up (as opposed to
                // just calling task.delete()).
                const newTaskParams = {
                    userid: userid,
                    dbConnection: transaction,
                }
                TCTaskService.populateParamsFromTask(newTaskParams, task)
                newTaskParams.deleted = true
                TCTaskService.addTask(newTaskParams, function(err, newTask) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, task)
                    }
                })
            },
            function(transaction, task, callback) {
                // Delete the task from its current table
                let tableName = constants.TasksTable.Normal
                if (task.isCompleted()) {
                    tableName = constants.TasksTable.Completed
                }
                task.setTableName(tableName)
                task.delete(transaction, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a task: ${err.message}.`))), transaction)
                        return
                    } 

                    if (task.isSubtask()) {
                        const getParentFunction = (next) => {
                            const params = {
                                userid : userid,
                                taskid : task.parentid,
                                preauthorization : isPreauthorized,
                                dbConnection : transaction
                            }
                            TCTaskService.getTask(params, (err, result) => {
                                if (err) {
                                    next(errors.create(err, `Could not read parent of deleted subtask.`))
                                    return
                                }
                                next(null, result)
                            })
                        }
    
                        const updateChildPropertiesFunction = (parent, next) => {
                            const params = {
                                userid : userid,
                                task : parent,
                                dbConnection : transaction
                            }
                            TCTaskService.fixupChildPropertiesForTask(params, (err, result) => {
                                if (err) {
                                    next(errors.create(err, `Could not update project child properties`))
                                    return
                                }
                                next(null)
                            })
                        }
    
                        const finish = (err) => {
                            callback(null, transaction, task)
                        }
    
                        async.waterfall([getParentFunction, updateChildPropertiesFunction], finish)
                        return
                    }

                    callback(null, transaction, task)
                })
            },
            function(transaction, task, callback) {
                // Update the task timestamp on the list so that sync clients
                // know that there's been a change.
                const listParams = {
                    listid: task.listid,
                    timestamp: Math.floor(Date.now() / 1000),
                    dbConnection: transaction
                }
                TCTaskService.updateTaskTimestampForList(listParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, task)
                    }
                })
            },
            function(transaction, task, callback) {
                const changeParams = {
                    listid: task.listid,
                    userid: params.userid,
                    itemid: task.taskid,
                    itemName: task.name,
                    itemType: constants.ChangeLogItemType.Task,
                    changeType: constants.ChangeLogType.Delete,
                    changeLocation: constants.ChangeLogLocation.API,
                    dbConnection: transaction
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    // Since the change log isn't critical to the overall system (in a sense),
                    // do not fail the addTask() if there's a problem adding a change log entry.
                    if (err) {
                        logger.debug(`Error recording change to changelog during deleteTask(): ${err}`)
                    }
                    callback(null, transaction)
                })
            }
        ], 
        function(err, transaction) {
            if (err) {
                if (taskAlreadyDeleted) {
                    // This isn't really an error and we really just need to
                    // return to the caller as if everything worked.
                    completion(null, true)
                } else {
                    let errObj = JSON.parse(err.message)
                    completion(new Error(JSON.stringify(errors.customError(errObj, `Could not delete a task (${taskid}).`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not mark a task as deleted. Database commit failed: ${err.message}`))))
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

    static getSubtasks(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        let page = params.page ? Number(params.page) : 0
        let pageSize = params.page_size ? Number(params.page_size) : constants.defaultPagedTasks
        if (pageSize > constants.maxPagedTasks) { pageSize = constants.maxPagedTasks }

        let offset = page * pageSize

        const completedOnly = params.completed_only != undefined ? Boolean(params.completed_only) : false

        // We have a gatekeeper of API Gateway that wouldn't pass this along,
        // but when we call this recursively, we can pass in preauthorization
        // so that we don't have to check to see if the user is authorized to
        // delete the specified taskid.
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.getSubtasks() missing the taskid.`))))
            return
        }
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.getSubtasks() missing the userid.`))))
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
                                callback(null, connection, listid)
                            }
                        }
                    })
                }
            },
            function(connection, listid, callback) {
                const sortTypeParams = {
                    listid: listid,
                    userid: userid,
                    dbConnection: connection
                }
                TCListSettingsService.sortTypeForList(sortTypeParams, function(err, sortType) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, sortType)
                    }
                })
            },
            function(connection, sortType, callback) {
                const orderByParams = {
                    sortType: sortType,
                    userid: userid,
                    dbConnection: connection
                }
                TCTaskService.orderByStatementForSortType(orderByParams, function(err, orderByStatement) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, orderByStatement)
                    }
                })
            },
            function(connection, orderByStatement, callback) {
                let tableName = completedOnly ? "tdo_completed_tasks" : "tdo_tasks"

                let sql = `
                    SELECT *
                    FROM ${tableName}
                    WHERE
                        (deleted IS NULL OR deleted = 0) AND
                        parentid = ?
                    ${orderByStatement}
                    LIMIT ? OFFSET ?
                `

                connection.query(sql, [taskid, pageSize, offset], function(err, results, fields) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        const tasks = []
                        if (results.rows) {
                            for (const row of results.rows) {
                                tasks.push(new TCTask(row))
                            }
                        }
                        let tasksInfo = {
                            tasks: tasks
                        }
                        callback(null, connection, tasksInfo)
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
                        `Could not find any subtasks for the project (${taskid}).`))))
            } else {
                completion(null, tasks)
            }
        })
    }

    static getSubtasksForProject(params, completion) {
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        // We have a gatekeeper of API Gateway that wouldn't pass this along,
        // but when we call this recursively, we can pass in preauthorization
        // so that we don't have to check to see if the user is authorized to
        // delete the specified taskid.
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const nonDeleted = params.nonDeleted != undefined ? params.nonDeleted : false
        const activeOnly = params.activeOnly != undefined ? params.activeOnly : false
        const readFullTask = params.readFullTask != undefined ? params.readFullTask : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if(!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.getSubtasksForProject() Missing taskid.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.getSubtasksForProject() Missing userid.'))))
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
                let selection = `taskid`
                if (readFullTask) { selection = `*` }
                let sql = ''
                let queryParams = [taskid]
                if (nonDeleted) {
                    sql = `SELECT ${selection} FROM tdo_tasks WHERE parentid=?
                            UNION SELECT ${selection} FROM tdo_completed_tasks WHERE parentid=?`
                    queryParams = [taskid, taskid]
                } else if (activeOnly) {
                    sql = `SELECT ${selection} FROM tdo_tasks WHERE parentid=?`
                } else {
                    sql = `SELECT ${selection} FROM tdo_tasks WHERE parentid=?
                            UNION SELECT ${selection} FROM tdo_completed_tasks WHERE parentid=?
                            UNION SELECT ${selection} FROM tdo_deleted_tasks WHERE parentid=?`
                    queryParams = [taskid, taskid, taskid]
                }
                connection.query(sql, queryParams, function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    } else {
                        let subtasks = []
                        if (results.rows) {
                            results.rows.forEach(function(row, index) {
                                if (readFullTask) {
                                    let aSubtask = new TCTask(row)
                                    if (aSubtask) {
                                        subtasks.push(aSubtask)
                                    }
                                } else {
                                    subtasks.push(row.taskid)
                                }
                            })
                        }

                        callback(null, connection, subtasks)
                    }
                })
            },
        ], 
        function(err, connection, subtasks) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get subtasks for project (${taskid}).`))))
            } else {
                completion(null, subtasks)
            }
        })
    }

    static getSubtaskCount(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const taskid = params.taskid != null ? params.taskid : null
        const userid = params.userid != null ? params.userid : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.getSubtaskCount() Missing taskid.'))))
            return
        }

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.getSubtaskCount() Missing userid.'))))
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
                const task = new TCTask({taskid : taskid})
                task.read(connection, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error retrieving task: ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, result)
                    }
                })
            },
            function(connection, task, callback) {
                const isChecklist = task.task_type == constants.TaskType.Checklist  
                const tableName  = isChecklist ? 'tdo_taskitos' : 'tdo_tasks'
                const columnName = isChecklist ? 'taskitoid' : 'taskid'
                let sql = `
                    SELECT COUNT(${columnName}) as count FROM ${tableName} WHERE parentid = ? AND (deleted IS NULL OR deleted = 0)
                `

                connection.query(sql, [taskid], (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        let count = 0
                        for (let row of result.rows) {
                            count = row.count
                        }
                        callback(null, connection, { count: count })
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
                        `Could not get list task count (${taskid}).`))))
            } else {
                completion(null, result)
            }
        })
    }

    static processRecurrenceForTask(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const task = params.task !== undefined ? params.task : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const userTimeZone = params.userTimeZone ? params.userTimeZone : null

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction

        let allRepeatedTasks = []
        let allNewTasks = []

        if (!task || task.constructor !== TCTask) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the task parameter.`))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid.'))))
            return
        }
        if(!userTimeZone) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userTimeZone.'))))
            return
        }

        if (task.completiondate == undefined || task.completiondate == 0
                || task.recurrence_type == undefined
                || task.recurrence_type == constants.TaskRecurrenceType.None
                || task.recurrence_type == constants.TaskRecurrenceType.None + 100
                || task.recurrence_type == constants.TaskRecurrenceType.WithParent
                || task.recurrence_type == constants.TaskRecurrenceType.WithParent + 100) {
            // Don't do anything if the task object hasn't been marked as completed
            // or isn't a recurring task.
            completion(null, {completedTask: task})
            return
        }

        const recurrenceType = task.recurrence_type
        const recurrenceString = task.advanced_recurrence_string

        if (recurrenceType == constants.TaskRecurrenceType.Advanced
                || recurrenceType == constants.TaskRecurrenceType.Advanced + 100) {
            const advancedType = TCTaskService.advancedRecurrenceTypeForString(recurrenceString)
            if (advancedType == constants.TaskAdvancedRecurrenceType.Unknown) {
                completion(null, {completedTask: task})
                return
            }            
        }

        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

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
                // First, add a non-repeating completed task. Use the built-in
                // addTask() so that all the good stuff happens that we expect
                // to have happen. Since the task has a completiondate, it should
                // be added to tdo_completed_tasks.
                const completedTaskParams = {
                    userid: userid,
                    dbConnection: transaction
                }
                TCTaskService.populateParamsFromTask(completedTaskParams, task)
                completedTaskParams.recurrence_type = constants.TaskRecurrenceType.None
                completedTaskParams.advanced_recurrence_string = undefined
                completedTaskParams.taskid = undefined
                completedTaskParams.timestamp = Math.floor(Date.now() / 1000)
                delete completedTaskParams.sync_id
                TCTaskService.addTask(completedTaskParams, function(err, completedTask) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        allNewTasks.push(completedTask)
                        callback(null, transaction, completedTask)
                    }
                })
            },
            function(transaction, completedTask, callback) {
                if (task.isProject()) {
                    const subtaskParams = {
                        taskid: task.taskid,
                        userid: userid,
                        nonDeleted: true,   // Get active and completed subtasks
                        readFullTask: true, // Read the full task info
                        preauthorized: isPreauthorized,
                        dbConnection: transaction
                    }
                    TCTaskService.getSubtasksForProject(subtaskParams, function(err, subtasks) {
                        if (err) {
                            callback(err, transaction)
                        } else {
                            const allSubtaskParams = {
                                originalProject: task,
                                completedProject: completedTask,
                                subtasks: subtasks,
                                userid: userid,
                                userTimeZone: userTimeZone,
                                dbTransaction: transaction
                            }
                            TCTaskService.processRecurrenceForSubtasks(allSubtaskParams, function(err, result) {
                                if (err) {
                                    callback(err, transaction)
                                } else {
                                    if (result.repeatedTasks && result.repeatedTasks.length > 0) {
                                        allRepeatedTasks = allRepeatedTasks.concat(result.repeatedTasks)
                                    }
                                    if (result.newTasks && result.newTasks.length > 0) {
                                        allNewTasks = allNewTasks.concat(result.newTasks)
                                    }
                                    callback(null, transaction, completedTask)
                                }
                            })
                        }
                    })
                } else if (task.isChecklist()) {
                    const taskitoParams = {
                        taskid: task.taskid,
                        userid: userid,
                        preauthorized: isPreauthorized,
                        dbConnection: transaction
                    }

                    TCTaskitoService.getTaskitosForChecklist(taskitoParams, (err, taskitos) => {
                        if (err) {
                            callback(err, transaction)
                            return
                        }

                        //add Taskitos For New Task
                        async.eachSeries(taskitos,
                            function(taskito, eachCallback) {
                                async.waterfall([
                                    function(innerWaterfallCallback) {
                                        const newTaskitoParams = {
                                            userid: userid,
                                            parentid: completedTask.taskid,
                                            name: taskito.name,
                                            dbTransaction: transaction
                                        }
                                        TCTaskitoService.addTaskito(newTaskitoParams, function(err, newTaskito) {
                                            if (err) {
                                                innerWaterfallCallback(err, transaction)
                                                return
                                            } else {
                                                innerWaterfallCallback(null, transaction, newTaskito)
                                            }
                                        })
                                    },
                                    function(transaction, newTaskito, innerWaterfallCallback) {
                                        const completeTaskitoParams = {
                                            userid: userid,
                                            taskitoid: newTaskito.taskitoid,
                                            completiondate: Date.now(),
                                            dbTransaction: transaction
                                        }
                                        TCTaskitoService.updateTaskitoCompletion(completeTaskitoParams, function(err, result) {
                                            if (err) {
                                                innerWaterfallCallback(err, transaction)
                                                return
                                            }
                                            innerWaterfallCallback(null, transaction)
                                        })
                                    }
                                ], function (innerWaterfallErr) {
                                    if( innerWaterfallErr ) {
                                        eachCallback(innerWaterfallErr, transaction)
                                    } else {
                                        eachCallback(null, transaction)
                                    }
                                })
                        }, function(err) {
                            if( err ) {
                                callback(err, transaction)
                                return
                            }

                            taskitoParams.items = taskitos.map(t => t.taskitoid)
                            TCTaskitoService.uncompleteTaskitos(taskitoParams, function(err, result) {
                            	if (err) {
                            		callback(err, transaction)
                            		return
                            	}

                            	callback(null, transaction, completedTask)
                            })
                        })
                    })
                } else {
                    callback(null, transaction, completedTask)
                }
            },
            function(transaction, completedTask, callback) {
                const offset = TCTaskService.fixupTaskRecurrenceData(task, userTimeZone)

                const notificationParams = {
                    originalTaskId: task.taskid,
                    completedTaskId: completedTask.taskid,
                    userid: userid,
                    offset: offset,
                    dbConnection: transaction
                }
                TCTaskNotificationService.createNotificationsForRecurringTask(notificationParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, completedTask)
                    }
                })
            },
            function(transaction, completedTask, callback) {
                if(!task.isProject()) {
                    callback(null, transaction, completedTask)
                    return
                }
                
                const fixupParams = {
                    task: task,
                    userid: userid,
                    dbConnection: transaction
                }
                TCTaskService.fixupChildPropertiesForTask(fixupParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                    } else {
                        callback(null, transaction, completedTask)
                    }
                })
            }
        ],
        function(err, transaction, completedTask) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Error processing recurrence.`))))
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
                const result = {
                    completedTask: completedTask,
                    repeatedTasks: allRepeatedTasks,
                    newTasks: allNewTasks
                }
                if (shouldCleanupDB) {
                    if (transaction) {
                        transaction.commit(function(err, result) {
                            if (err) {
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not process recurrence for task. Database commit failed: ${err.message}`))))
                            } else {
                                completion(null, result)
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, result)
                }
            }
        })
    }

    static processRecurrenceForSubtasks(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }
        const originalProject = params.originalProject != undefined ? params.originalProject : null
        const completedProject = params.completedProject != undefined ? params.completedProject : null
        const subtasks = params.subtasks != undefined ? params.subtasks : null
        const userid = params.userid != undefined ? params.userid : null
        const userTimeZone = params.userTimeZone != undefined ? params.userTimeZone : null

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction

        let allRepeatedTasks = []
        let allNewTasks = []
        
        if(!originalProject) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processRecurrenceForSubtasks() missing originalProject.'))))
            return
        }
        if(!completedProject) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processRecurrenceForSubtasks() missing completedProject.'))))
            return
        }
        if(!subtasks) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processRecurrenceForSubtasks() missing subtasks.'))))
            return
        }
        if(!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processRecurrenceForSubtasks() missing userid.'))))
            return
        }
        if(!userTimeZone) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processRecurrenceForSubtasks() missing userTimeZone.'))))
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
                async.each(subtasks,
                function(subtask, eachCallback) {
                    const originalSubtaskDueDate = subtask.duedate

                    // For subtasks that repeat, process this way
                    if (subtask.recurrence_type != undefined
                            && subtask.recurrence_type != constants.TaskRecurrenceType.None
                            && subtask.recurrence_type != constants.TaskRecurrenceType.None + 100) {
                        async.waterfall([
                            function(callback) {
                                if (subtask.completiondate != undefined && subtask.completiondate != 0) {
                                    // The subtask is completed. A new completed subtasks needs to be
                                    // created and attached to the newly-created parent project that is
                                    // also marked completed.
                                    const completedSubtaskParams = {
                                        userid: userid,
                                        dbConnection: transaction
                                    }
                                    for (let propertyName of subtask.columnNames()) {
                                        if (subtask[propertyName] != undefined) {
                                            completedSubtaskParams[propertyName] = subtask[propertyName]
                                        }
                                    }
                                    completedSubtaskParams.parentid = completedProject.taskid
                                    completedSubtaskParams.recurrence_type = constants.TaskRecurrenceType.None
                                    completedSubtaskParams.advanced_recurrence_string = undefined
                                    completedSubtaskParams.taskid = undefined
                                    completedSubtaskParams.timestamp = Math.floor(Date.now() / 1000)
                                    delete completedSubtaskParams.sync_id
                                    TCTaskService.addTask(completedSubtaskParams, function(err, completedSubtask) {
                                        if (err) {
                                            callback(err)
                                        } else {
                                            allNewTasks.push(completedSubtask)
                                            callback(null) // continue on
                                        }
                                    })
                                } else {
                                    // The subtask is not completed and we can skip this step
                                    callback(null)
                                }
                            },
                            function(callback) {
                                subtask.parentid = originalProject.taskid // thought this would already be set, but this is what our original PHP code does
                                if (subtask.recurrence_type == constants.TaskRecurrenceType.WithParent || subtask.recurrence_type == constants.TaskRecurrenceType.WithParent + 100) {
                                    // Only process the due date if it had a due date
                                    // to begin with, otherwise leave it alone.
                                    if (subtask.duedate != undefined && subtask.duedate != 0) {
                                        subtask.recurrence_type = originalProject.recurrence_type
                                        subtask.advanced_recurrence_string = originalProject.advanced_recurrence_string != undefined ? originalProject.advanced_recurrence_string : undefined
                                        TCTaskService.fixupTaskRecurrenceData(subtask, userTimeZone) // don't need to pay attention to the return value or errors
                                    }

                                    subtask.recurrence_type = constants.TaskRecurrenceType.WithParent
                                    subtask.advanced_recurrence_string = undefined
                                }

                                subtask.completiondate = 0
                                
                                if (subtask.isChecklist()) {
                                    const uncompleteParams = {
                                        taskid: subtask.taskid,
                                        dbConnection: transaction
                                    }
                                    TCTaskService.uncompleteChecklistItems(uncompleteParams, function(err, result) {
                                        if (err) {
                                            callback(err)
                                        } else {
                                            callback(null)
                                        }
                                    })
                                } else {
                                    callback(null)
                                }
                            },
                            function(callback) {
                                // This task is in the completed table, so move it back so it will be active
                                const moveParams = {
                                    task: subtask,
                                    userid: userid,
                                    dbConnection: transaction
                                }
                                TCTaskService.moveFromCompletedTable(moveParams, function(err, movedTask) {
                                    if (err) {
                                        callback(err)
                                    } else {
                                        allRepeatedTasks.push(movedTask)
                                        callback(null)
                                    }
                                })
                            },
                            function(callback) {
                                const notificationParams = {
                                    userid: userid,
                                    taskid: subtask.taskid,
                                    duedate: originalSubtaskDueDate,
                                    dbConnection: transaction
                                }
                                TCTaskNotificationService.updateNotificationsForTask(notificationParams, function(err, result) {
                                    if (err) {
                                        callback(err)
                                    } else {
                                        callback(null)
                                    }
                                })
                            }
                        ],
                        function(err) {
                            if (err) {
                                eachCallback(err)
                            } else {
                                eachCallback(null)
                            }
                        })
                    } else {
                        // This isn't a repeating subtask
                        // If it's not complete, and it doesn't recur with the parent, delete it
                        if (subtask.completiondate == undefined || subtask.completiondate == 0) {
                            const deleteTaskParams = {
                                taskid: subtask.taskid,
                                userid: userid,
                                preauthorized: true,
                                dbTransaction: transaction
                            }
                            TCTaskService.deleteTask(deleteTaskParams, function(err, result) {
                                if (err) {
                                    eachCallback(err)
                                } else {
                                    eachCallback(null)
                                }
                            })
                        } else {
                            // If it's complete and doesn't recur, set it to be joined
                            // with the completed parent
                            subtask.parentid = completedProject.taskid
                            subtask.setTableName(constants.TasksTable.Completed)

                            // Update the subtask without modifying the parent.  The parent will be
                            // modifed at the end of this function so skip modifying here because
                            // that would just waste time.
                            subtask.update(transaction, function(err, resultTask) {
                                if (err) {
                                    eachCallback(err)
                                } else {
                                    eachCallback(null)
                                }
                            })
                        }
                    }
                },
                function(err) {
                    if (err) {
                        // One of the subtasks had an error completing
                        callback(err, transaction)
                    } else {
                        // All active subtasks were completed successfully
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                if (!originalProject.isChecklist()) {
                    callback(null, transaction)
                    return
                }

                const uncompleteParams = {
                    taskid: originalProject.taskid,
                    dbConnection: transaction
                }
                TCTaskService.uncompleteChecklistItems(uncompleteParams, function(err, result) {
                    if (err) {
                        callback(err, transaction)
                        return
                    } 

                    callback(null, transaction)
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
                                completion(null, {repeatedTasks: allRepeatedTasks, newTasks: allNewTasks})
                            }
                            db.cleanup()
                        })
                    } else {
                        db.cleanup()
                    }
                } else {
                    completion(null, {repeatedTasks: allRepeatedTasks, newTasks: allNewTasks})
                }
            }
        })
    }

    static fixupTaskRecurrenceData(task, userTimeZone) {
        if (!task) {
            return 0 // no offset
        }

        let advancedType = null

        if (task.recurrence_type == constants.TaskRecurrenceType.Advanced || task.recurrence_type == constants.TaskRecurrenceType.Advanced + 100) {
            advancedType = TCTaskService.advancedRecurrenceTypeForString(task.advanced_recurrence_string)
            if (advancedType == constants.TaskRecurrenceType.Unknown) {
                return 0 // no offset
            }
        }

        let offset = 0

        if (task.recurrence_type == constants.TaskRecurrenceType.Advanced || task.recurrence_type == constants.TaskRecurrenceType.Advanced + 100) {
            switch(advancedType) {
                case constants.TaskAdvancedRecurrenceType.EveryXDaysWeeksMonths: {
                    offset = TCTaskService.processRecurrenceForTaskAdvancedEveryXDaysWeeksMonths(task, userTimeZone)
                    break;
                }
                case constants.TaskAdvancedRecurrenceType.TheXOfEachMonth: {
                    offset = TCTaskService.processRecurrenceForTaskAdvancedTheXOfEachMonth(task, userTimeZone)
                    break;
                }
                case constants.TaskAdvancedRecurrenceType.EveryMonTueEtc: {
                    offset = TCTaskService.processRecurrenceForTaskAdvancedEveryMonTueEtc(task, userTimeZone)
                    break;
                }

            }
        } else {
            offset = TCTaskService.processRecurrenceSimple(task, userTimeZone)
        }

        task.completiondate = undefined
        const startDateField = task.isProject() ? 'project_startdate' : 'startdate'
        if (task[startDateField] != undefined && task[startDateField] > 0) {
            task[startDateField] += offset
        }

        return offset
    }

    static processRecurrenceForTaskAdvancedEveryXDaysWeeksMonths(task, userTimeZone) {
        const recurrenceType = task.recurrence_type
        let baseDateTimestamp = 0
        let offsetTimestamp = 0
        const taskDate = task.isProject() ? task.project_duedate : task.duedate
        const taskDateHasTime = task.isProject() ? task.project_duedate_has_time : task.due_date_has_time

        if (taskDate == undefined || taskDate == 0) {
            offsetTimestamp = Math.floor(Date.now() / 1000)
        } else {
            offsetTimestamp = taskDate
        }

        if (recurrenceType < 100) {
            baseDateTimestamp = offsetTimestamp
        } else {
            baseDateTimestamp = Math.floor(Date.now() / 1000)

            if (taskDate != undefined && taskDate > 0 && taskDateHasTime != undefined && taskDateHasTime > 0) {
                baseDateTimestamp = TCUtils.dateWithTimeFromDate(baseDateTimestamp, taskDate)
            }
        }

        const baseDate = moment.unix(baseDateTimestamp)

        const components = task.advanced_recurrence_string.split(/\s+/)
        if (!components || components.length == 0) {
            return 0
        }

        // Do all of this in a trycatch block in case there are problems
        // with array index references we expect but for whatever reason
        // aren't met.
        try {
            if (components[0] == "Every") {
                const interval = Number(components[1])
                const unit = components[2].toLowerCase()

                baseDate.add(interval, unit)
            } else {
                logger.debug(`Not able to match up the advanced recurrence type for task (${task.taskid})`)
                return 0
            }
        } catch (error) {
            logger.debug(`An error occurred processing advanced recurrence rules for task (${task.taskid}).`)
            return 0
        }

        let newTimestamp = baseDate.unix()
        if (taskDateHasTime == undefined || taskDateHasTime == 0) {
            newTimestamp = TCUtils.normalizedDateFromGMT(newTimestamp, userTimeZone)
        } else if (taskDate != undefined && taskDate != 0) {
            newTimestamp = TCUtils.dateWithTimeFromDate(newTimestamp, taskDate)
        }

        if(task.isProject())
            task.project_duedate = newTimestamp
        else
            task.duedate = newTimestamp

        return newTimestamp - offsetTimestamp
    }

    static processRecurrenceForTaskAdvancedTheXOfEachMonth(task, userTimeZone) {
        const recurrenceType = task.recurrence_type
        let baseDateTimestamp = 0
        let offsetTimestamp = 0
        const taskDate = task.isProject() ? task.project_duedate : task.duedate
        const taskDateHasTime = task.isProject() ? task.project_duedate_has_time : task.due_date_has_time

        if (taskDate == undefined || taskDate == 0) {
            offsetTimestamp = Math.floor(Date.now() / 1000)
        } else {
            offsetTimestamp = taskDate
        }

        if (recurrenceType < 100) {
            baseDateTimestamp = offsetTimestamp
        } else {
            baseDateTimestamp = Math.floor(Date.now() / 1000)

            if (taskDate != undefined && taskDate > 0 && taskDateHasTime != undefined && taskDateHasTime > 0) {
                baseDateTimestamp = TCUtils.dateWithTimeFromDate(baseDateTimestamp, taskDate)
            }
        }

        const baseDate = moment.unix(baseDateTimestamp)

        const components = task.advanced_recurrence_string.split(/\s+/)
        if (!components || components.length == 0) {
            return 0
        }

        let newTimestamp = null

        // Do all of this in a trycatch block in case there are problems
        // with array index references we expect but for whatever reason
        // aren't met.
        try {
            if (components[0].toLowerCase() == "the") {
                let week = components[1]
                let weekday = components[2]

                if (week == "1st" || week.toLowerCase() == "first") {
                    week = 1
                } else if (week == "2nd" || week.toLowerCase() == "second") {
                    week = 2
                } else if (week == "3rd" || week.toLowerCase() == "third") {
                    week = 3
                } else if (week == "4th" || week.toLowerCase() == "fourth") {
                    week = 4
                } else if (week == "5th" || week.toLowerCase() == "fifth") {
                    week = 5
                } else if (week.toLowerCase() == "last" || week.toLowerCase() == "final") {
                    week = -1
                } else {
                    logger.debug(`Not able to match up the advanced recurrence type for task (${task.taskid})`)
                    return 0
                }

                if (weekday.toLowerCase() == "monday" || weekday.toLowerCase() == "mon") {
                    weekday = 1
                } else if (weekday.toLowerCase() == "tuesday" || weekday.toLowerCase() == "tues" || weekday.toLowerCase() == "tue") {
                    weekday = 2
                } else if (weekday.toLowerCase() == "wednesday" || weekday.toLowerCase() == "wed") {
                    weekday = 3
                } else if (weekday.toLowerCase() == "thursday" || weekday.toLowerCase() == "thur" || weekday.toLowerCase() == "thu" || weekday.toLowerCase() == "thurs") {
                    weekday = 4
                } else if (weekday.toLowerCase() == "friday" || weekday.toLowerCase() == "fri") {
                    weekday = 5
                } else if (weekday.toLowerCase() == "saturday" || weekday.toLowerCase() == "sat") {
                    weekday = 6
                } else if (weekday.toLowerCase() == "sunday" || weekday.toLowerCase() == "sun") {
                    weekday = 0
                } else {
                    logger.debug(`Not able to match up the advanced recurrence type for task (${task.taskid})`)
                    return 0
                }

                // Date.js: moveToNthOccurrence()
                // Date.today().moveToNthOccurrence(0, 1);// First Sunday of the month
                // Date.today().moveToNthOccurrence(0, 3); // Third Sunday of the month
                // Date.today().moveToNthOccurrence(0, -1); // Last Sunday of the month
                
                let newDate = baseDate.toDate()
                while (!newTimestamp) {
                    newDate.addMonths(1)
                    if (TCUtils.hasDateOccurrence(newDate, weekday, week)) {
                        newDate.moveToNthOccurrence(weekday, week)
                        newTimestamp = Math.floor(newDate.getTime() / 1000)
                    }
                }
            } else {
                logger.debug(`Not able to match up the advanced recurrence type for task (${task.taskid})`)
                return 0
            }
        } catch (error) {
            logger.debug(`An error occurred processing advanced recurrence rules for task (${task.taskid}).`)
            return 0
        }

        if (taskDateHasTime == undefined || taskDateHasTime == 0) {
            newTimestamp = TCUtils.normalizedDateFromGMT(newTimestamp, userTimeZone)
        } else if (taskDate != undefined && taskDate != 0) {
            newTimestamp = TCUtils.dateWithTimeFromDate(newTimestamp, taskDate)
        }

        if(task.isProject())
            task.project_duedate = newTimestamp
        else
            task.duedate = newTimestamp

        return newTimestamp - offsetTimestamp
    }

    static processRecurrenceForTaskAdvancedEveryMonTueEtc(task, userTimeZone) {
        const recurrenceType = task.recurrence_type
        let baseDateTimestamp = 0
        let offsetTimestamp = 0
        const taskDate = task.isProject() ? task.project_duedate : task.duedate
        const taskDateHasTime = task.isProject() ? task.project_duedate_has_time : task.due_date_has_time

        if (taskDate == undefined || taskDate == 0) {
            offsetTimestamp = Math.floor(Date.now() / 1000)
        } else {
            offsetTimestamp = taskDate
        }

        if (recurrenceType < 100) {
            baseDateTimestamp = offsetTimestamp
        } else {
            baseDateTimestamp = Math.floor(Date.now() / 1000)

            if (taskDate != undefined && taskDate > 0 && taskDateHasTime != undefined && taskDateHasTime > 0) {
                baseDateTimestamp = TCUtils.dateWithTimeFromDate(baseDateTimestamp, taskDate)
            }
        }

        const baseDate = moment.unix(baseDateTimestamp)

        let selectedDays = 0
        const advancedString = task.advanced_recurrence_string

        // First record which occurrences of the weekdays are present in the advanced recurrence string
        if (advancedString.match(/mon/i)) {
            selectedDays |= MON_SELECTION
        }
        if (advancedString.match(/tue/i)) {
            selectedDays |= TUE_SELECTION
        }
        if (advancedString.match(/wed/i) || advancedString.match(/wendsday/i)) {
            selectedDays |= WED_SELECTION
        }
        if (advancedString.match(/thu/i)) {
            selectedDays |= THU_SELECTION
        }
        if (advancedString.match(/fri/i) || advancedString.match(/fryday/i)) {
            selectedDays |= FRI_SELECTION
        }
        if (advancedString.match(/sat/i)) {
            selectedDays |= SAT_SELECTION
        }
        if (advancedString.match(/sun/i)) {
            selectedDays |= SUN_SELECTION
        }
        if (advancedString.match(/weekday/i)) {
            selectedDays |= WEEKDAY_SELECTION
        }
        if (advancedString.match(/weekend/i)) {
            selectedDays |= WEEKEND_SELECTION
        }
        if (advancedString.match(/every day/i)) {
            selectedDays |= (WEEKDAY_SELECTION | WEEKEND_SELECTION)
        }

        if (selectedDays == 0) {
            logger.debug(`Invalid recurrence of type EveryMonTueEtc: ${advancedString} for task (${task.taskid})`)
            return 0
        }

        let dayCount = 1
        let tmpDate = moment(baseDate).add(1, 'day')
        let tmpDayOfWeek = tmpDate.isoWeekday() % 7

        let nextLoop = true
        while (nextLoop) {
            switch (tmpDayOfWeek) {
                case 0:
                    if (selectedDays & SUN_SELECTION) {
                        nextLoop = false
                    }
                    break;
                case 1:
                    if (selectedDays & MON_SELECTION) {
                        nextLoop = false
                    }
                    break;
                case 2:
                    if (selectedDays & TUE_SELECTION) {
                        nextLoop = false
                    }
                    break;
                case 3:
                    if (selectedDays & WED_SELECTION) {
                        nextLoop = false
                    }
                    break;
                case 4:
                    if (selectedDays & THU_SELECTION) {
                        nextLoop = false
                    }
                    break;
                case 5:
                    if (selectedDays & FRI_SELECTION) {
                        nextLoop = false
                    }
                    break;
                case 6:
                    if (selectedDays & SAT_SELECTION) {
                        nextLoop = false
                    }
                    break;
            }
            if (nextLoop) {
                dayCount++
            }

            tmpDate = moment(baseDate).add(dayCount, 'day')
            tmpDayOfWeek = tmpDate.isoWeekday() % 7
        }

        let newTimestamp = moment(baseDate).add(dayCount, 'day').unix()
        if (taskDateHasTime == undefined || taskDateHasTime == 0) {
            newTimestamp = TCUtils.normalizedDateFromGMT(newTimestamp, userTimeZone)
        } else if (taskDate != undefined && taskDate != 0) {
            newTimestamp = TCUtils.dateWithTimeFromDate(newTimestamp, taskDate)
        }

        if(task.isProject())
            task.project_duedate = newTimestamp
        else
            task.duedate = newTimestamp

        return newTimestamp - offsetTimestamp
    }

    static processRecurrenceSimple(task, userTimeZone) {
        const recurrenceType = task.recurrence_type
        let baseDateTimestamp = 0
        let offsetTimestamp = 0
        let actualRecurrenceType = constants.TaskRecurrenceType.None
        const taskDate = task.isProject() ? task.project_duedate : task.duedate
        const taskDateHasTime = task.isProject() ? task.project_duedate_has_time : task.due_date_has_time

        if (taskDate == undefined || taskDate== 0) {
            offsetTimestamp = Math.floor(Date.now() / 1000)
        } else {
            offsetTimestamp = taskDate
        }

        if (recurrenceType < 100) {
            // Repeat from due date
            actualRecurrenceType = recurrenceType
            baseDateTimestamp = offsetTimestamp
        } else {
            // Repeat from completion date
            actualRecurrenceType = recurrenceType - 100
            baseDateTimestamp = Math.floor(Date.now() / 1000)

            if (taskDate != undefined && taskDate > 0 && taskDateHasTime != undefined && taskDateHasTime > 0) {
                baseDateTimestamp = TCUtils.dateWithTimeFromDate(baseDateTimestamp, taskDate)
            }
        }

        // NOTE: Moment.js already handles adding a month properly (from the doc):
        //  "If the day of the month on the original date is greater than the
        //  number of days in the final month, the day of the month will change
        //  to the last day in the final month."

        const baseDate = moment.unix(baseDateTimestamp)
        switch(actualRecurrenceType) {
            case constants.TaskRecurrenceType.Weekly: {
                baseDate.add(1, 'weeks')
                break;
            }
            case constants.TaskRecurrenceType.Yearly: {
                baseDate.add(1, 'years')
                break;
            }
            case constants.TaskRecurrenceType.Daily: {
                baseDate.add(1, 'days')
                break;
            }
            case constants.TaskRecurrenceType.Biweekly: {
                baseDate.add(2, 'weeks')
                break;
            }
            case constants.TaskRecurrenceType.Monthly: {
                baseDate.add(1, 'months')
                break;
            }
            case constants.TaskRecurrenceType.Bimonthly: {
                baseDate.add(2, 'months')
                break;
            }
            case constants.TaskRecurrenceType.Semiannually: {
                baseDate.add(6, 'months')
                break;
            }
            case constants.TaskRecurrenceType.Quarterly: {
                baseDate.add(3, 'months')
                break;
            }
            case constants.TaskRecurrenceType.WithParent:
            case constants.TaskRecurrenceType.Advanced:
            case constants.TaskRecurrenceType.None: {
                // This shouldn't happen, but don't change the due date
                logger.debug(`An invalid option was found while calculating recurrence data for task (${task.taskid}).`)
                return 0
            }
        }

        let newTimestamp = baseDate.unix()
        if (taskDateHasTime == undefined || taskDateHasTime == 0) {
            // Normalize the date to noon GMT
            newTimestamp = TCUtils.normalizedDateFromGMT(newTimestamp, userTimeZone)
        } else {
            newTimestamp = TCUtils.dateWithTimeFromDate(newTimestamp, taskDate)
        }

        if(task.isProject())
            task.project_duedate = newTimestamp
        else
            task.duedate = newTimestamp
        
        return newTimestamp - offsetTimestamp
    }

    static moveFromCompletedTable(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const task = params.task !== undefined ? params.task : null
        const userid = params.userid !== undefined ? params.userid : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!task) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.moveFromCompletedTable() missing the task parameter.`))))
            return
        }
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.moveFromCompletedTable() missing the userid parameter.`))))
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
                const addTaskParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCTaskService.populateParamsFromTask(addTaskParams, task)
                addTaskParams.timestamp = Math.floor(Date.now() / 1000)
                addTaskParams.completiondate = 0
                TCTaskService.addTask(addTaskParams, function(err, addedTask) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, addedTask)
                    }
                })
            },
            function(connection, addedTask, callback) {
                const taskToDelete = new TCTask({taskid: task.taskid}, constants.TasksTable.Completed)
                taskToDelete.delete(connection, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a task: ${err.message}`))), connection)
                    } else {
                        callback(null, connection, addedTask)
                    }
                })
            }
        ],
        function(err, connection, addedTask) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not move a task from completed to normal tasks table (${task.taskid}).`))))
            } else {
                completion(null, addedTask)
            }
        })
    }

    // As far as I can tell, this method fixes up a project so that it has the proper
    // comparison values that are used to help sort and display it correctly in result lists.
    static fixupChildPropertiesForTask(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const task = params.task !== undefined ? params.task : null
        const userid = params.userid !== undefined ? params.userid : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!task) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.fixupChildPropertiesForTask() missing the task parameter.`))))
            return
        }
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.fixupChildPropertiesForTask() missing the userid parameter.`))))
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
                let newCompPriority = task.project_priority != undefined ? task.project_priority : 0
                const sql = "SELECT priority FROM tdo_tasks WHERE priority != 0 AND parentid=? ORDER BY priority ASC LIMIT 1"
                connection.query(sql, [task.taskid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    } else {
                        let bestChildPriority = 0
                        if (results.rows) {
                            for (const row of results.rows) {
                                if (row.priority != undefined) {
                                    bestChildPriority = row.priority
                                }
                            }
                        }
                        if (newCompPriority == 0 || (bestChildPriority != 0 && bestChildPriority < newCompPriority)) {
                            newCompPriority = bestChildPriority
                        }
                        task.priority = newCompPriority
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // Read the user's timezone
                const timeZoneParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCUserSettingsService.getUserTimeZone(timeZoneParams, function(err, userTimeZone) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, userTimeZone)
                    }
                })
            },
            function(connection, userTimeZone, callback) {
                let newCompDueDate = task.project_duedate != undefined ? task.project_duedate : 0
                let newCompDueDateHasTime = task.project_duedate_has_time != undefined ? task.project_duedate_has_time : 0

                let timezoneOffset = 43170 // represents 11 hrs 59 seconds
                if (userTimeZone) {
                    const utcOffsetInSeconds = moment.tz(Date.now(), userTimeZone).utcOffset() * 60
                    timezoneOffset = utcOffsetInSeconds * -1 + 43170
                }

                const sql = `SELECT duedate, due_date_has_time FROM tdo_tasks WHERE duedate != 0 AND parentid=? ORDER BY (duedate + (CASE 1 WHEN (due_date_has_time=1) THEN 0 ELSE (${timezoneOffset}) END)) LIMIT 1`
                connection.query(sql, [task.taskid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    } else {
                        if (results.rows) {
                            for (const row of results.rows) {
                                if (row.duedate != undefined) {
                                    const bestChildDueDate = row.duedate
                                    if (newCompDueDate == 0 || (bestChildDueDate != 0 && bestChildDueDate < newCompDueDate)) {
                                        newCompDueDate = bestChildDueDate
                                        newCompDueDateHasTime = Boolean(row.due_date_has_time)
                                    }
                                }
                            }
                        }
                        task.duedate = newCompDueDate
                        task.duedate_has_time = newCompDueDateHasTime
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                let newCompStartDate = task.project_startdate != undefined ? task.project_startdate : 0
                const sql = `SELECT startdate FROM tdo_tasks WHERE startdate != 0 AND parentid=? ORDER BY startdate LIMIT 1`
                connection.query(sql, [task.taskid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                    `Error running query: ${err.message}`))), connection)
                    } else {
                        if (results.rows) {
                            for (const row of results.rows) {
                                if (row.startdate != undefined) {
                                    const bestChildStartDate = row.startdate
                                    if (newCompStartDate == 0 || (bestChildStartDate != 0 && bestChildStartDate < newCompDueDate)) {
                                        newCompStartDate = bestChildStartDate
                                    }
                                }
                            }
                        }
                        task.startdate = newCompStartDate
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                let compStarred = task.project_starred != undefined ? task.project_starred : 0
                if (!compStarred) {
                    const sql = `SELECT COUNT(taskid) AS starcount FROM tdo_tasks WHERE starred!=0 AND parentid=?`
                    connection.query(sql, [task.taskid], function(err, results) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                                        `Error running query: ${err.message}`))), connection)
                        } else {
                            if (results.rows) {
                                for (const row of results.rows) {
                                    if (row.starcount != undefined && row.startcount > 0) {
                                        compStarred = 1
                                    }
                                }
                            }
                            task.starred = compStarred
                            callback(null, connection)
                        }
                    })
                } else {
                    callback(null, connection)
                }
            },
            function(connection, callback) {
                // Update the task without updating the timestamp
                task.updateWithoutTimestamp(connection, function(err, updatedTask) {
                    if(err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating task (${task.taskid}): ${err.message}`))), connection)
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
                        `Could not fix up a project during uncompleteTask() operation (${task.taskid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static populateParamsFromTask(params, task) {
        if (!params || !task) { return }

        for (let propertyName of task.columnNames()) {
            if (task[propertyName] != undefined) {
                params[propertyName] = task[propertyName]
            }
        }
    }

    static updateTaskTimestampForList(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const listid = params.listid && typeof params.listid == 'string' ? params.listid.trim() : null
        const timestamp = params.timestamp !== undefined ? params.timestamp : Math.floor(Date.now() / 1000)
        const isTaskito = params.isTaskito !== undefined ? Boolean(params.isTaskito) : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!listid || listid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the listid parameter.`))))
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
                const columnName = isTaskito ? 'taskito_timestamp' : 'task_timestamp'
                let sql = `UPDATE tdo_lists SET ${columnName}=? WHERE listid=?`
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
                        `Could not update the task_timestamp for list (${listid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    // This should be called with an existing DB Transaction (dbTransaction).
    // If not called with an open transaction, this method will create a
    // transaction to work with.
    static permanentlyDeleteTask(params, completion) {
        const taskid = params.taskid
        const tableName = params.tableName ? params.tableName : constants.TasksTable.Normal

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction
        
        if(!taskid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.permanentlyDeleteTask() Missing taskid.'))))
            return
        }
        if(!tableName) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.permanentlyDeleteTask() Missing tableName.'))))
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
                // Permanently delete taskitos. We don't know if this is a checklist,
                // but we shouldn't be penalized too badly by just attempting a delete
                // anyway.
                let sql = `DELETE FROM tdo_taskitos WHERE parentid=?`
                transaction.query(sql, [taskid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a taskito for task (${taskid}): ${err.message}.`))), transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                // Permanently delete task notifications for task
                let sql = `DELETE FROM tdo_task_notifications WHERE taskid=?`
                transaction.query(sql, [taskid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a notification for task (${taskid}): ${err.message}.`))), transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                // Permanently delete comments for task
                const sql = `DELETE FROM tdo_comments WHERE itemid=?`
                transaction.query(sql, [taskid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a commment for task (${taskid}): ${err.message}.`))), transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                // Delete the task from the specified table name
                let sql = `DELETE FROM ${tableName} WHERE taskid=?`
                transaction.query(sql, [taskid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a task (${taskid}) from ${tableName}: ${err.message}.`))), transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                // Delete the task from any tag assignments
                let sql = `DELETE FROM tdo_tag_assignments WHERE taskid=?`
                transaction.query(sql, [taskid], function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a tag assignment for task (${taskid}): ${err.message}.`))), transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            }
        ], 
        function(err, transaction, list, settings) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not permanently delete a task (${taskid}).`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not permanently delete a task. Database commit failed: ${err.message}`))))
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

    static advancedRecurrenceTypeForString(recurrenceString) {
        if (recurrenceString == undefined || typeof recurrenceString !== 'string' || recurrenceString.length == 0) {
            return constants.TaskAdvancedRecurrenceType.Unknown
        }

        const components = recurrenceString.split(/\s+/)
        if (!components || components.length == 0) {
            return constants.TaskAdvancedRecurrenceType.Unknown
        }

        const firstWord = components[0]
        if (firstWord.startsWith("Every")) {
            if (components.length < 2) {
                return constants.TaskAdvancedRecurrenceType.Unknown
            }

            const secondWord = components[1]
            if (secondWord.startsWith("0")) {
                return constants.TaskAdvancedRecurrenceType.Unknown
            }

            if (secondWord.match(/^[0-9]/)) {
                return constants.TaskAdvancedRecurrenceType.EveryXDaysWeeksMonths
            }

            if (secondWord.length > 0) {
                const char = secondWord.substr(0, 1)
                if (/^[a-z]/.test(char.toLowerCase()) == false) {
                    return constants.TaskAdvancedRecurrenceType.Unknown
                }
            }

            return constants.TaskAdvancedRecurrenceType.EveryMonTueEtc
        }

        if (firstWord.toLowerCase().startsWith("on") || firstWord.toLowerCase().startsWith("the")) {
            return constants.TaskAdvancedRecurrenceType.TheXOfEachMonth
        }

        return constants.TaskAdvancedRecurrenceType.Unknown
    }

    static processParsedTags(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid != undefined ? params.userid : null
        const taskid = params.taskid != undefined ? params.taskid : null
        const parsedTags = params.parsedTags != undefined ? params.parsedTags : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processParsedTags : Missing userid.'))))
            return
        }
        if (!taskid || taskid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processParsedTags : Missing taskid.'))))
            return
        }
        if (!parsedTags || parsedTags.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processParsedTags : Missing parsedTags.'))))
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
                // Read the list of user tags available first
                const allTagParams = {
                    userid: userid,
                    dbConnection: connection
                }
                TCTagService.getAllTags(allTagParams, function(err, allTags) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        callback(null, connection, allTags)
                    }
                })
            },
            function(connection, allTags, callback) {
                let tagsToAssign = []
                let tagsToCreate = []
                parsedTags.forEach(parsedTag => {
                    let existingTag = allTags.find(tag => tag.name.toLowerCase() == parsedTag)
                    if (existingTag) {
                        tagsToAssign.push(existingTag)
                    } else {
                        tagsToCreate.push(parsedTag)
                    }
                })

                // Create all the new tags first
                async.eachSeries(tagsToCreate,
                function(tagName, eachCallback) {
                    const tagParams = {
                        name: tagName,
                        dbConnection: connection
                    }
                    TCTagService.createTag(tagParams, function(err, newTag) {
                        if (err) {
                            eachCallback(err)
                        } else {
                            tagsToAssign.push(newTag)
                            eachCallback(null)
                        }
                    })
                },
                function(eachErr) {
                    if (eachErr) {
                        callback(eachErr, connection)
                    } else {
                        callback(null, connection, tagsToAssign)
                    }
                })
            },
            function(connection, tagsToAssign, callback) {
                async.eachSeries(tagsToAssign,
                function(tag, eachCallback) {
                    const tagParams = {
                        tagid: tag.tagid,
                        taskid: taskid,
                        dbConnection: connection
                    }
                    TCTagService.assignTag(tagParams, function(err, result) {
                        eachCallback(err)
                    })
                },
                function(eachErr) {
                    if (eachErr) {
                        callback(eachErr, connection)
                    } else {
                        callback(null, connection)
                    }
                })
            },
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
                        `Could not process parsed tags for task (${taskid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static processParsedSubtasks(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid != undefined ? params.userid : null
        const project = params.project != undefined ? params.project : null
        const parsedSubtasks = params.parsedSubtasks != undefined ? params.parsedSubtasks : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processParsedTags : Missing userid.'))))
            return
        }
        if (!project) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processParsedTags : Missing project.'))))
            return
        }
        if (!parsedSubtasks || parsedSubtasks.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processParsedTags : Missing parsedSubtasks.'))))
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
                async.eachSeries(parsedSubtasks,
                function(subtaskName, eachCallback) {
                    const addTaskParams = {
                        userid: userid,
                        listid: project.listid,
                        parentid: project.taskid,
                        name: subtaskName,
                        task_type: constants.TaskType.Normal,
                        dbConnection: connection
                    }
                    TCTaskService.addTask(addTaskParams, function(err, newSubtask) {
                        eachCallback(err)
                    })
                },
                function(eachErr) {
                    if (eachErr) {
                        callback(eachErr, connection)
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
                        `Could not add parsed subtasks for task (${project.taskid}).`))))
            } else {
                completion(null, true)
            }
        })
    }
    
    static processParsedTaskitos(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid != undefined ? params.userid : null
        const checklist = params.checklist != undefined ? params.checklist : null
        const parsedTaskitos = params.parsedTaskitos != undefined ? params.parsedTaskitos : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processParsedTags : Missing userid.'))))
            return
        }
        if (!checklist) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processParsedTags : Missing checklist.'))))
            return
        }
        if (!parsedTaskitos || parsedTaskitos.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'TCTaskService.processParsedTags : Missing parsedTaskitos.'))))
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
                async.eachSeries(parsedTaskitos,
                function(taskitoName, eachCallback) {
                    const addParams = {
                        userid: userid,
                        parentid: checklist.taskid,
                        name: taskitoName,
                        dbTransaction: connection
                    }
                    TCTaskitoService.addTaskito(addParams, function(err, newTaskito) {
                        eachCallback(err)
                    })
                },
                function(eachErr) {
                    if (eachErr) {
                        callback(eachErr, connection)
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
                        `Could not add parsed items for checklist (${checklist.taskid}).`))))
            } else {
                completion(null, true)
            }
        })
    }

    static getUnsyncedTaskMatchingProperties(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        if (!userid) {
            completion(new Erro(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid parameter.`))))
            return
        }

        const task = params.task != undefined ? params.task : null
        if (!task) {
            completion(new Erro(JSON.stringify(errors.customError(errors.missingParameters, `Missing task parameter.`))))
            return
        }

        const values = []
        let tableName = constants.TasksTable.Normal

        let whereStatement = `(sync_id IS NULL OR sync_id = '')`

        whereStatement += ` AND parentid = ?`
        if (task.parentid != undefined && task.parentid.length > 0) {
            values.push(task.parentid)
        } else {
            values.push(constants.LocalInboxId)
        }

        if (task.name != undefined) {
            whereStatement += ` AND name = ?`
            values.push(task.name)
        }

        whereStatement += ` AND completiondate = ?`
        if (task.completiondate != undefined && task.completiondate != 0) {
            tableName = constants.TasksTable.Completed
            values.push(task.completionDate)
        } else {
            values.push(0)
        }

        if (task.isProject()) {
            whereStatement += ` AND project_priority = ? AND project_duedate = ? AND project_startdate = ?`
            values.push(task.project_priority != undefined ? task.project_priority : constants.TaskPriority.None)
            values.push(task.project_duedate != undefined ? task.project_duedate : 0)
            values.push(task.project_startdate != undefined ? task.project_startdate : 0)
        } else {
            whereStatement += ` AND priority = ? AND duedate = ? AND completiondate = ? AND startdate = ?`
            values.push(task.priority != undefined ? task.priority : constants.TaskPriority.None)
            values.push(task.duedate != undefined ? task.duedate : 0)
            values.push(task.startdate != undefined ? task.startdate : 0)
        }

        whereStatement += ` AND recurrence_type = ?`
        values.push(task.recurrence_type != undefined ? task.recurrence_type : constants.TaskRecurrenceType.None)

        whereStatement += ` AND task_type = ?`
        values.push(task.task_type != undefined ? task.task_type : constants.TaskType.Normal)

        whereStatement += ` AND location_alert = ?`
        values.push(task.location_alert != undefined ? task.location_alert : null)

        const sql = `SELECT taskid FROM ${tableName} WHERE ${whereStatement}`

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
                    let taskid = null
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError,
                        `Error running query: ${err.message}`))), connection)
                    } else {
                        if (result.rows && result.rows.length > 0) {
                            taskid = result.rows[0].taskid
                        }
                        callback(null, connection, taskid)
                    }
                })
            },
            function(connection, taskid, callback) {
                if (taskid) {
                    // Now just use our normal getTask() method
                    const getTaskParams = {
                        userid: params.userid,
                        taskid: taskid,
                        dbConnection: connection,
                        preauthorized: true
                    }
                    TCTaskService.getTask(getTaskParams, function(err, existingTask) {
                        callback(err, connection, existingTask)
                    })
                } else {
                    callback(null, connection, null)
                }
            }
        ],
        function(err, connection, existingTask) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Error while looking for a matching existing task.`))))
            } else {
                completion(null, existingTask)
            }
        })
    }

    static getAllDirtyTasks(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid parameter.`))))
            return
        }
logger.debug(`getAllDirtyTasks() 0`)

        const syncSubtasks = params.syncSubtasks != undefined ? params.syncSubtasks : false

        const modifiedAfterDate = params.modifiedAfterDate != undefined ? params.modifiedAfterDate : null

        let sql = `SELECT * FROM all_tasks_view WHERE dirty > 0`
        if (syncSubtasks) {
logger.debug(`getAllDirtyTasks() 1`)
            sql += ` AND (parentid IS NOT NULL AND parentid !='')`
        } else {
logger.debug(`getAllDirtyTasks() 2`)
            sql += ` AND (parentid IS NULL OR parentid = '')`
        }

        if (modifiedAfterDate) {
logger.debug(`getAllDirtyTasks() 3`)
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
logger.debug(`getAllDirtyTasks() 4: ${sql}`)
                connection.query(sql, [], function(err, results) {
                    if (err) {
logger.debug(`getAllDirtyTasks() 5`)
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
logger.debug(`getAllDirtyTasks() 6: ${JSON.stringify(results)}`)
                        if (results.rows) {
                            for (const row of results.rows) {
                                tasks.push(new TCTask(row))
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
logger.debug(`getAllDirtyTasks() 7`)
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Error while looking for a dirty tasks.`))))
            } else {
logger.debug(`getAllDirtyTasks() 8`)
                completion(null, tasks)
            }
        })
    }

    static moveTaskIntoTable(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        if (!userid) {
            completion(new Erro(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid parameter.`))))
            return
        }

        const taskProperties = params.taskProperties != undefined ? params.taskProperties : null
        if (!taskProperties) {
            completion(new Erro(JSON.stringify(errors.customError(errors.missingParameters, `Missing taskProperties parameter.`))))
            return
        }

        const sourceTable = params.sourceTable != undefined ? params.sourceTable : null
        if (!sourceTable) {
            completion(new Erro(JSON.stringify(errors.customError(errors.missingParameters, `Missing sourceTable parameter.`))))
            return
        }

        const destinationTable = params.destinationTable != undefined ? params.destinationTable : null
        if (!destinationTable) {
            completion(new Erro(JSON.stringify(errors.customError(errors.missingParameters, `Missing destinationTable parameter.`))))
            return
        }

        if (sourceTable == destinationTable) {
            completion(null, true)
            return
        }

        const dbTransaction = params.dbTransaction
        const shouldCleanupDB = !dbTransaction


        async.waterfall([
            function(callback) {
                if (dbTransaction) {
                    callback(null, dbTransaction)
                    return
                }

                db.getPool(function(err, pool) {
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                            return
                        } 
                        callback(null, transaction)
                    })
                })
            },
            function(transaction, callback) {
                // Delete the task from its old table
                const oldTask = new TCTask(taskProperties, sourceTable)
                TCTaskService.populateParamsFromTask(oldTask, taskProperties)
                oldTask.delete(transaction, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not delete a task from ${sourceTable}: ${err.message}`))), transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            },
            function(transaction, callback) {
                const newTask = new TCTask(taskProperties, destinationTable)
                TCTaskService.populateParamsFromTask(newTask, taskProperties)
                newTask.add(transaction, function(err, result) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Could not add a task to ${destinationTable}: ${err.message}`))), transaction)
                    } else {
                        callback(null, transaction)
                    }
                })
            }
        ],
        function(err, transaction) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Problem moving a task from ${sourceTable} to ${destinationTable}.`))))
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
                                completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not move ta task from ${sourceTable} to ${destinationTable}. Database commit failed: ${err.message}`))))
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

    // TODO: WIP, needs more work
    static convertProjectToChecklist(params, completion) {

        if (!params) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertToNormalTask() : Missing params.'))
            return
        }
        
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const parentTask = params.parentTask != undefined ? params.parentTask : null
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!userid) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertToNormalTask() : Missing userid.'))
            return
        }
        if (!taskid) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertToNormalTask() : Missing taskid.'))
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
                            const message = `Error getting a database connection: ${err.message}`
                            next(errors.create(errors.databaseError, message), null)
                            return
                        } 
                        next(null, connection)
                    })
                })
            },
            function(connection, next) {
                // Get the task
                if (parentTask) {
                    next (null, connection, parentTask)
                    return
                }

                TCTaskService.getTask(params, (err, task) => {
                    if (err) {
                        next(errors.create(errors.taskNotFound, `Error getting task when converting to normal task.`), connection)
                        return
                    }

                    if (!task.isProject) {
                        next(errors.create(errors.invalidParent, `Expecting task to be project to  convert to checklist.`), connection)
                        return
                    }

                    next(null, connection, task)
                })
            },
            function(connection, parentTask, next) {
                const getParams = {
                    userid : userid,
                    taskid : taskid,
                    readFullTask : true,
                    nonDeleted : true,
                    dbConnection : connection
                }
                TCTaskService.getSubtasksForProject(getParams, (err, subtasks) => {
                    if (err) {
                        const message = `Error getting project subtasks when converting to checklist.`
                        next(errors.create(errors.taskNotFound, message))
                        return
                    }

                    next(null, connection, parentTask, subtasks)
                })
            },
            function(connection, parentTask, subtasks, next) {
                parentTask.task_type = constants.TaskType.Project
                async.eachSeries(subtasks,
                    function(subtask, nextEach) {
                        if (subtask.isChecklist()) {
                            const getParams = {
                                userid : userid,
                                taskid : subtask.taskid,
                                dbConnection : connection
                            }
                            TCTaskitoService.getTaskitosForChecklist(getParams, (err, taskitos) => {
                                if (err) {
                                    const message = `Error getting taskitos for subtask checklist when converting to checklist.`
                                    nextEach(errors.create(errors.taskNotFound, message))
                                    return
                                }

                                async.eachSeries(taskitos, function(taskito, nextEachTaskito) {
                                    taskito.parentid = parentTask.taskid
                                    taskito.update(connection, (err, taskito) => {
                                        if (err) {
                                            const message = `Error updating taskito when merging with parent during conversion to checklist ${err.message}`
                                            nextEachTaskito(errors.create(errors.databaseError, message))
                                            return
                                        }

                                        nextEachTaskito()
                                    })
                                },
                                function(err) {
                                    if (err) {
                                        nextEach(err)
                                        return
                                    }

                                    const taskito = subtask.toTaskito()
                                    taskito.parentid = parentTask.taskid
                                    taskito.add(connection, (err, taskito) => {
                                        if (err) {
                                            const message = `Error converting subtask to taskito during conversion to checklist ${err.message}`
                                            nextEach(errors.create(errors.databaseError, message))
                                            return
                                        }

                                        const deleteParams = {
                                            userid : userid,
                                            taskid : subtask.taskid,
                                            dbTransaction : connection
                                        }
                                        TCTaskService.deleteTask(deleteParams, (err, result) => {
                                            if (err) {
                                                const message = `Error deleting subtask after converting to taskito during conversion to checklist ${err.message}`
                                                nextEach(errors.create(errors.databaseError, message))
                                                return
                                            }

                                            nextEach()
                                        })
                                    })
                                })
                            })

                            return
                        }
                        else {
                            const taskito = subtask.toTaskito()
                            taskito.parentid = parentTask.taskid
                            taskito.add(connection, (err, taskito) => {
                                if (err) {
                                    const message = `Error converting subtask to taskito during conversion to checklist ${err.message}`
                                    nextEach(errors.create(errors.databaseError, message))
                                    return
                                }

                                const deleteParams = {
                                    userid : userid,
                                    taskid : subtask.taskid,
                                    dbTransaction : connection
                                }
                                TCTaskService.deleteTask(deleteParams, (err, result) => {
                                    if (err) {
                                        const message = `Error deleting subtask after converting to taskito during conversion to checklist ${err.message}`
                                        nextEach(errors.create(errors.databaseError, message))
                                        return
                                    }

                                    nextEach()
                                })
                            })
                        }    
                    },
                    function(err) {
                        next(err)
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
                completion(errors.create(errObj, `Error while converting project to checklist.`))
                return
            } 

            completion(null, result)
        })
    }

    // TODO: WIP, needs more work
    static convertChecklistToProject(params, completion) {
        if (!params) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertChecklistToProject() : Missing params.'))
            return
        }
        
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const parentTask = params.parentTask != undefined ? params.parentTask : null
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!userid) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertChecklistToProject() : Missing userid.'))
            return
        }
        if (!taskid) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertChecklistToProject() : Missing taskid.'))
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
                            const message = `Error getting a database connection: ${err.message}`
                            next(errors.create(errors.databaseError, message), null)
                            return
                        } 
                        next(null, connection)
                    })
                })
            },
            function(connection, next) {
                // Get the task
                if (parentTask) {
                    next (null, connection, parentTask)
                    return
                }

                TCTaskService.getTask(params, (err, task) => {
                    if (err) {
                        next(errors.create(errors.taskNotFound, `Error getting task when converting checklist to project.`), connection)
                        return
                    }

                    if (!task.isChecklist) {
                        next(errors.create(errors.invalidParent, `Expecting task to be checklist to convert to project.`), connection)
                        return
                    }

                    if (task.parentid != null) {
                        next(errors.create(errors.invalidParent, `Subtask checklists cannot be converted to projects.`), connection)
                        return
                    }

                    next(null, connection, task)
                })
            },
            function(connection, parentTask, next) {
                const convertParams = {
                    userid : userid,
                    taskid : taskid,
                    listid : parentTask.listid,
                    removeFromParent : false,
                    dbConnection : connection
                }
                TCTaskService.convertChecklistItemsToTasks(convertParams, (err, result) => {
                    if (err) {
                        nextEach(err)
                        return
                    }

                    next(null, connection)
                })
            },
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
                completion(errors.create(errObj, `Error while converting checklist to project.`))
                return
            } 

            completion(null, result)
        })
    }

    // TODO: WIP, needs more work
    static convertToNormalTask(params, completion) {
        if (!params) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertToNormalTask() : Missing params.'))
            return
        }
        
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const parentTask = params.parentTask != undefined ? params.parentTask : null
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!userid) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertToNormalTask() : Missing userid.'))
            return
        }
        if (!taskid) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertToNormalTask() : Missing taskid.'))
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
                            const message = `Error getting a database connection: ${err.message}`
                            next(errors.create(errors.databaseError, message), null)
                            return
                        } 
                        next(null, connection)
                    })
                })
            },
            function(connection, next) {
                // Get the task
                if (parentTask) {
                    next (null, connection, parentTask)
                    return
                }

                TCTaskService.getTask(params, (err, task) => {
                    if (err) {
                        next(errors.create(errors.taskNotFound, `Error getting task when converting to normal task.`), connection)
                        return
                    }

                    next(null, connection, task)
                })
            },
            function(connection, parentTask, next) {
                // This function is for handling project subtasks.
                if(parentTask.isChecklist()) {
                    next(null, connection, parentTask)
                    return
                }

                async.waterfall([
                    function(nextProjectFunction) {
                        const getParams = {
                            userid : userid,
                            taskid : taskid,
                            readFullTask : true,
                            nonDeleted : true,
                            dbConnection: connection
                        }

                        TCTaskService.getSubtasksForProject(getParams, (err, subtasks) => {
                            if (err) {
                                const message = `Error getting project subtasks when converting to normal task.`
                                nextProjectFunction(errors.create(errors.taskNotFound, message))
                                return
                            }

                            nextProjectFunction(null, subtasks)
                        })
                    },
                    function(subtasks, nextProjectFunction) {
                        async.eachSeries(subtasks,
                            function(subtask, nextEach) {
                                if (subtask.recurrence_type == constants.TaskRecurrenceType.WithParent) {
                                    subtask.recurrence_type = constants.TaskRecurrenceType.None
                                }

                                async.waterfall([
                                    function(nextSub) {
                                        if(subtask.isCompleted()) subtask.setTableName('tdo_completed_tasks')
                                        subtask.parentid = ''
                                        if (!subtask.isChecklist()) {
                                            nextSub()
                                            return
                                        }

                                        const convertParams = {
                                            userid : userid,
                                            taskid : subtask.taskid,
                                            listid : parentTask.listid,
                                            removeFromParent : true,
                                            dbConnection : connection
                                        }
                                        TCTaskService.convertChecklistItemsToTasks(convertParams, (err, result) => {
                                            if (err) {
                                                nextSub(err)
                                                return
                                            }
                                            subtask.task_type = constants.TaskType.Normal
                                            nextSub(null)
                                        })
                                    },
                                    function(nextSub) {
                                        subtask.update(connection, (err, result) => {
                                            if (err) {
                                                const message = `Error updating subtask to normal task.`
                                                nextSub(errors.create(JSON.parse(err.message), message))
                                                return
                                            }
                                            nextSub()
                                        })
                                    }
                                ],
                                function(err) {
                                    nextEach(err)
                                })
                            },
                            function(err) {
                                nextProjectFunction(err)
                            })
                    }
                ],
                function(err) {
                    next(err, connection, parentTask)
                })
            },
            function(connection, parentTask, next) {
                // Handle checklists.
                if (!parentTask.isChecklist()) {
                    next(null, connection)
                    return
                }

                const convertParams = {
                    userid : userid,
                    taskid : taskid,
                    listid : parentTask.listid,
                    removeFromParent : true,
                    dbConnection : connection
                }
                TCTaskService.convertChecklistItemsToTasks(convertParams, (err, result) => {
                    if (err) {
                        next(err)
                        return
                    }

                    next(null, connection)
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
                completion(errors.create(errObj, `Error while converting to normal task.`))
                return
            } 

            completion(null, result)
        })
    }

    // TODO: WIP, needs more work
    static convertChecklistItemsToTasks(params, completion) {
        if (!params) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertToNormalTask() : Missing params.'))
            return
        }
        
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const taskid = params.taskid && typeof params.taskid == 'string' ? params.taskid.trim() : null
        const listid = params.listid && typeof params.listid == 'string' ? params.listid.trim() : null
        const removeFromParent = params.removeFromParent ? true : false
        const isPreauthorized = params.preauthorized != undefined ? params.preauthorized : false

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null
        
        if (!userid) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertChecklistItemsToTasks() : Missing userid.'))
            return
        }
        if (!taskid) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertChecklistItemsToTasks() : Missing taskid.'))
            return
        }
        if (!listid) {
            completion(errors.create(errors.missingParameters, 'TCTaskService.convertChecklistItemsToTasks() : Missing listid.'))
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
                            const message = `Error getting a database connection: ${err.message}`
                            next(errors.create(errors.databaseError, message), null)
                            return
                        } 
                        next(null, connection)
                    })
                })
            },
            function(connection, next) {
                const getParams = {
                    userid : userid,
                    taskid : taskid,
                    dbConnection : connection
                }
                TCTaskitoService.getTaskitosForChecklist(getParams, (err, taskitos) => {
                    if (err) {
                        next(errors.create(errors.taskNotFound, `Error getting checklist items when converting taskitos to tasks.`), connection)
                        return
                    }

                    next(null, connection, taskitos)
                })
            },
            function(connection, taskitos, next) {
                async.eachSeries(taskitos, 
                    function(taskito, nextEach) {
                        if (taskito.deleted) {
                            nextEach()
                            return
                        }
                        const task = taskito.toTask()
                        task.listid = listid
                        if (removeFromParent) {
                            task.parentid = null
                            task.recurrence_type = constants.TaskRecurrenceType.None
                        }
                        else {
                            task.recurrence_type = constants.TaskRecurrenceType.WithParent
                        }

                        const addParams = {
                            userid : userid,
                            dbConnection : connection
                        }

                        TCTaskService.addTask(Object.assign(addParams, task), (err, addedTask) => {
                            if (err) {
                                nextEach(err)
                                return
                            }

                            taskito.delete(connection, (err, result) => {
                                logger.debug(result)
                                if (err) {
                                    nextEach(err)
                                    return
                                }

                                nextEach()
                            })
                        })
                    },
                    function(err) {
                        next(err, connection)
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
                completion(errors.create(errObj, `Could not convert checklist items to tasks.`))
                return
            } 

            completion(null, {success : true})
        })
    }
}

module.exports = TCTaskService

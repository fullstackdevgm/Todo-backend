'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')
const begin = require('any-db-transaction')
const rp = require('request-promise')

const db = require('./tc-database')
const jwt = require('./jwt')
const moment = require('moment-timezone')

const TCAccountService = require('./tc-account-service')
const TCListService = require('./tc-list-service')
const TCSmartListService = require('./tc-smart-list-service')
const TCTask = require('./tc-task')
const TCTaskNotification = require('./tc-task-notification')
const TCTaskNotificationService = require('./tc-task-notification-service')
const TCTaskito = require('./tc-taskito')
const TCTaskitoService = require('./tc-taskito-service')
const TCTaskService = require('./tc-task-service')
const TCListMembershipService = require('./tc-list-membership-service')
const TCAccount = require('./tc-account')
const TCTagService = require('./tc-tag-service')
const TCUserSettingsService = require('./tc-user-settings-service')
const TCUtils = require('./tc-utils')

const constants = require('./constants')
const errors = require('./errors')

const io = require('socket.io-client')

let isSyncing                       = false

let _currentSyncType                = constants.CurrentSyncType.None
let _lastSyncTime                   = null

// Variables used during an active sync
let _serverListHash                 = null
let _serverSmartListHash            = null
let _serverUserHash                 = null
let _allTaskTimeStamps              = {}
let _listMembershipHashes           = {}
let _allTaskitoTimeStamps           = {}
let _allNotificationTimeStamps      = {}
let _contextTimeStamp               = null
let _lastResetDataTimestamp         = null

let _subscriptionLevel              = null
let _subscriptionExpiration         = null
let _subscriptionPaymentService     = null

let _teamName                       = null
let _teamAdminName                  = null
let _teamAdminEmail                 = null
let _subscriptionUserDisplayName    = null



class TCSyncService {
    static performSync(params, completion) {

        let userid = null

        // const now = Math.floor(Date.now() / 1000)
        let storedResetTimestamp = null
        let dataWasReset = false
        let storedListHash = null
        let requestServerLists = false

        let storedSmartListHash = null
        let requestServerSmartLists = false

        let storedUserHash = null
        let requestServerUsers = false

        let isFirstSync = false
        let jwtToken = null // Used during initial sync to query for task counts
        let taskCounts = {} // Used to send sync events with

        let storedTaskTimeStamps = null
        let syncTasks = false

        let storedTaskitoTimeStamps = null
        let syncTaskitos = false

        let storedNotificationTimeStamps = null
        let syncNotifications = false

        let syncEventSocket = io.connect(`http://127.0.0.1:${process.env.PORT}`, {'forceNew': true})

        async.waterfall([
            function(callback) {
                // Get the user's ID from the stored JWT token so it
                // can be used later on.
                TCAccountService.getJWT(function(err, token) {
                    if (err) {
                        callback(err)
                    } else {
                        jwtToken = token
                        userid = jwt.userIDFromToken(token)
                        callback(null)
                    }
                })
            },
            function(callback) {
                TCSyncService.getSyncInformation({}, function(err, syncInformation) {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null)
                    }
                })
            },
            function(callback) {
                db.getSetting(constants.SettingLastResetDataTimeStampKey, function(err, timeStamp) {
                    if (err) {
                        callback(err)
                    } else {
                        storedResetTimestamp = timeStamp
                        if (!storedResetTimestamp && !_lastResetDataTimestamp) {
                            dataWasReset = false;
                        } else {
                            if (!storedResetTimestamp || storedResetTimestamp != _lastResetDataTimestamp) {
                                dataWasReset = true;
                            }
                        }
                        callback(null)
                    }
                })
            },
            function(callback) {
                // If the server data has been reset since the last time we synced, bail out of the sync and make the user do a reset or perform full sync
                if (dataWasReset) {
                    // If we're in a reset sync or perform full sync already, then save off the last reset data timestamp
                    if (_currentSyncType == constants.CurrentSyncType.Full || _currentSyncType == constants.CurrentSyncType.Reset || _lastSyncTime == null) {
                        const newSettings = {}
                        if (_lastResetDataTimestamp) {
                            newSettings[constants.SettingLastResetDataTimeStampKey] = _lastResetDataTimestamp
                        } else {
                            newSettings[constants.SettingLastResetDataTimeStampKey] = null
                        }
                        db.setSettings(newSettings, function(err, setResults) {
                            callback(err)
                        })
                    } else {
                        // If we detected a reset on the server, get rid of all our sync ids so if the user chooses to perform a full sync,
                        // we'll push everything up successfully
                        TCSyncService.resetSyncState({removeSyncIds: true}, function(err, resetResult) {
                            if (err) {
                                callback(err)
                            } else {
                                // Reset sync state was successful, but indicate to the xPlat client that
                                // a reset was performed.
                                callback(new Error(JSON.stringify(errors.syncServerDataReset)))
                            }
                        })
                    }
                } else {
                    // Continue on
                    callback(null)
                }
            },

            //
            // Sync Lists
            //
            function(callback) {
                db.getSetting(constants.SettingListHashKey, function(err, value) {
                    if (err) {
                        callback(err)
                    } else {
                        storedListHash = value
                        callback(null)
                    }
                })
            },
            function(callback) {
                syncEventSocket.emit('sync-event', {
                    message: 'Synchronizing lists'
                })

                // Determine whether we need to synchronize Task Lists
                requestServerLists = (!storedListHash || storedListHash != _serverListHash)
                TCSyncService.hasDirtyRecords({tableName: "tdo_lists"}, function(err, hasDirtyLists) {
                    if (err) {
                        callback(err)
                    } else {
                        if (requestServerLists || hasDirtyLists) {
                            const syncListsParams = {
                                userid: userid,
                                requestServerLists: requestServerLists
                            }
                            TCSyncService.syncLists(syncListsParams, function(err, syncResult) {
                                callback(err)
                            })
                        } else {
                            // Skip task list sync
                            callback(null)
                        }
                    }
                })
            },

            //
            // Sync Smart Lists
            //
            function(callback) {
                    syncEventSocket.emit('sync-event', {
                        message: 'Synchronizing smart lists'
                    })
                db.getSetting(constants.SettingSmartListHashKey, function(err, value) {
                    if (err) {
                        callback(err)
                    } else {
                        storedSmartListHash = value
                        callback(null)
                    }
                })
            },
            function(callback) {
                // Determine whether we need to synchronize Task Lists
                requestServerSmartLists = (!storedSmartListHash || storedSmartListHash != _serverSmartListHash)
                TCSyncService.hasDirtyRecords({tableName: "tdo_smart_lists"}, function(err, hasDirtySmartLists) {
                    if (err) {
                        callback(err)
                    } else {
                        if (requestServerSmartLists || hasDirtySmartLists) {
                            const params = {
                                userid: userid, 
                                requestServerSmartLists: requestServerSmartLists   
                            }
                            TCSyncService.syncSmartLists(params, function(err, syncResult) {
                                callback(err)   
                            })
                        } else {
                            // Skip synchronizing smart lists
                            callback(null)
                        }
                    }
                })
            },

            //
            // Request Shared List Users from the Server
            // Note: This isn't really a two-way "sync" of users. We're basically checking the
            // server's user hash key. If it's different, we're gonna request all the users the
            // server thinks we should know about and then we'll store them locally.
            //
            function(callback) {
                db.getSetting(constants.SettingUserHashKey, function(err, value) {
                    if (err) {
                        callback(err)
                    } else {
                        storedUserHash = value
                        callback(null)
                    }
                })
            },
            function(callback) {
                requestServerUsers = (!storedUserHash || storedUserHash != _serverUserHash)
                if (requestServerUsers) {
                    const params = {
                        requestServerUsers: requestServerUsers,
                        userid: userid
                    }
                    TCSyncService.syncUsers(params, function(err, syncResult) {
                        callback(err)
                    })
                } else {
                    // Skip synchronizing smart lists
                    callback(null)
                }
            },

            //
            // TO-DO: Sync Contexts
            // Note: We no longer even use contexts. For now, skip them entirely.
            //
            
            //
            // Sync Tasks
            //
            function(callback) {
                syncEventSocket.emit('sync-event', {
                    message: 'Synchronizing tasks'
                })                
                isFirstSync = false

                db.getSetting(constants.SettingAllTaskTimeStampsKey, function(err, value) {
                    if (err) {
                        callback(err)
                    } else {
                        storedTaskTimeStamps = value
                        callback(null)
                    }
                })
            },
            function(callback) {
                if (!storedTaskTimeStamps) {
                    syncTasks = true
                    isFirstSync = true
                    // Get the task counts so we can give progress on the sync back
                    // to the UI via socket.io events.

                    // Only request completed tasks that were completed in the last 14 days
                    const now = moment()
                    const cutoffDateString = now.subtract(14, 'day').format('YYYY-MM-DD')

                    var rp = require('request-promise')

                    const options = {
                        uri: `${process.env.TC_API_URL}/tasks/count`,
                        method: `GET`,
                        json: true,
                        headers: {
                            Authorization: `Bearer ${jwtToken}`,
                            "x-api-key": process.env.TC_API_KEY
                        },
                        qs: {
                            "completion_cutoff_date": cutoffDateString
                        }
                    }

                    let err = null

                    rp(options)
                    .then((jsonResponse) => {

                        if (jsonResponse.lists) {
                            let activeTasks = jsonResponse.lists.reduce((activeTaskCount, listInfo) => {
                                return activeTaskCount + listInfo.active
                            }, 0)
                            let completedTasks = jsonResponse.lists.reduce((completedTaskCount, listInfo) => {
                                return completedTaskCount + listInfo.completed
                            }, 0)
                            
                            taskCounts['activeTasks'] = activeTasks
                            taskCounts['completedTasks'] = completedTasks
                        }
                    })
                    .catch((e) => {
                        err = e
                    })
                    .finally(() => {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.syncError, `Problem accessint task counts from service: ${err.message}`)))
                        } else {
                            callback(null)
                        }
                    })
                } else {
                    // Do some more digging to determine if tasks need to be synchronized
                    db.getSetting(constants.SettingCompletedTaskSyncResetKey, function(err, value) {
                        if (err) {
                            callback(err)
                        } else {
                            const completedTaskReset = value
                            let newSettings = {}
                            newSettings[constants.SettingCompletedTaskSyncResetKey] = null
                            db.setSettings(newSettings, function(err, setResult) {
                                if (err) {
                                    callback(err)
                                } else {
                                    if (completedTaskReset) {
                                        syncTasks = true
                                        storedTaskTimeStamps = null
                                        newSettings = {}
                                        newSettings[constants.SettingAllTaskTimeStampsKey] = null
                                        db.setSettings(newSettings, function(err, removeResult) {
                                            if (err) {
                                                callback(err)
                                            } else {
                                                callback(null)
                                            }
                                        })
                                    } else if (_allTaskTimeStamps && Object.keys(storedTaskTimeStamps).length != _allTaskTimeStamps ? Object.keys(_allTaskTimeStamps).length : 0) {
                                        syncTasks = true
                                        callback(null)
                                    } else {
                                        // Loop through all of the server time stamps and make sure they
                                        // match up with the time stamps we have. If they don't we need to
                                        // sync the tasks.
                                        Object.keys(storedTaskTimeStamps).forEach((serverListId) => {
                                            const storedTimestamp = storedTaskTimeStamps[serverListId]
                                            const timestamp = _allTaskTimeStamps && _allTaskTimeStamps[serverListId] ? _allTaskTimeStamps[serverListId] : 0

                                            if (!storedTimestamp || !timestamp || storedTimestamp != timestamp) {
                                                syncTasks = true
                                            }
                                        })

                                        callback(null)
                                    }
                                }
                            })
                        }
                    })
                }
            },
            function(callback) {
                // Determine if there are dirty tasks
                const dirtyParams = {
                    modifiedBeforeDate: null,
                    distinguishSubtasks: true,
                    checkSubtasks: false
                }
                TCSyncService.hasDirtyTasks(dirtyParams, function(err, hasDirtyTasks) {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, hasDirtyTasks)
                    }
                })
            },
            function(hasDirtyTasks, callback) {
                if (syncTasks || hasDirtyTasks) {
                    // SYNC TASKS!
                    logger.debug(`Synchronizing tasks...`)
                    const syncParams = {
                        timestamps: storedTaskTimeStamps,
                        syncSubtasks: false,
                        isFirstSync: isFirstSync,
                        userid: userid,
                        syncEventSocket: syncEventSocket,
                        taskCounts: taskCounts
                    }
                    TCSyncService.syncTasks(syncParams, function(err, syncResult) {
                        if (err) {
                            callback(err)
                        } else {
                            callback(null)
                        }
                    })
                } else {
                    callback(null) // don't sync tasks
                }
            },


            //
            // Sync Subtasks
            //
            function(callback) {
                syncEventSocket.emit('sync-event', {
                    message: 'Synchronizing subtasks'
                })                
                // Determine if there are dirty tasks
                const dirtyParams = {
                    modifiedBeforeDate: null,
                    distinguishSubtasks: true,
                    checkSubtasks: true
                }
                TCSyncService.hasDirtyTasks(dirtyParams, function(err, hasDirtySubtasks) {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, hasDirtySubtasks)
                    }
                })
            },
            function(hasDirtySubtasks, callback) {
                if (hasDirtySubtasks) {
                    // SYNC SUBTASKS!
                    logger.debug(`Synchronizing subtasks...`)
                    const syncParams = {
                        timestamps: storedTaskTimeStamps,
                        syncSubtasks: true,
                        // We don't need to get all tasks because the previous task
                        // sync actually picks up subtasks as well. https://github.com/Appigo/todo-issues/issues/4183
                        isFirstSync: false,
                        userid: userid
                    }
                    TCSyncService.syncTasks(syncParams, function(err, syncResult) {
                        if (err) {
                            callback(err)
                        } else {
                            callback(null)
                        }
                    })
                } else {
                    callback(null) // don't sync subtasks
                }
            },


            //
            // Convert Contexts to Tags
            //


            //
            // Sync Taskitos
            //
            function(callback) {
                syncEventSocket.emit('sync-event', {
                    message: 'Synchronizing checklists'
                })                
                isFirstSync = false

                db.getSetting(constants.SettingsAllTaskitoTimeStamps, function(err, value) {
                    if (err) {
                        callback(err)
                    } else {
                        storedTaskitoTimeStamps = value
                        callback(null)
                    }
                })
            },
            function(callback) {
                if (!storedTaskitoTimeStamps) {
                    isFirstSync = true
                }

                if (!storedTaskitoTimeStamps && (!_allTaskitoTimeStamps || Object.keys(_allTaskitoTimeStamps).length == 0)) {
                    syncTaskitos = false
                    callback(null) // continue on
                    return
                } 
                
                if (!storedTaskitoTimeStamps) {
                    syncTaskitos = true
                    callback(null) // continue on
                    return
                }

                if (Object.keys(storedTaskitoTimeStamps).length != (_allTaskitoTimeStamps ? Object.keys(_allTaskitoTimeStamps).length : 0)) {
                    syncTaskitos = true
                    callback(null) // continue on
                    return
                }

                // Loop through all of the server time stamps and make sure that
                // they match up with the time stamps we have. If they don't, we need
                // to sync the taskitos.
                Object.keys(storedTaskitoTimeStamps).forEach((serverListId) => {
                    const storedTimestamp = storedTaskitoTimeStamps[serverListId]
                    const timestamp = _allTaskitoTimeStamps && _allTaskitoTimeStamps[serverListId] ? _allTaskitoTimeStamps[serverListId] : 0

                    if (!storedTimestamp || !timestamp || storedTimestamp != timestamp) {
                        syncTaskitos = true
                    }
                })

                callback(null)
            },
            function(callback) {
                if (syncTaskitos) {
                    callback(null, null)
                    return
                }

                // Determine if there are dirty taskitos
                const dirtyParams = {
                    modifiedBeforeDate: null,
                    tableName: "tdo_taskitos"
                }
                TCSyncService.hasDirtyRecords(dirtyParams, function(err, hasDirtyTaskitos) {
                    if (err) {
                        callback(err)
                        return
                    } 
                    callback(null, hasDirtyTaskitos)
                })
            },
            function(hasDirtyTaskitos, callback) {
                if (!(syncTaskitos || hasDirtyTaskitos)) {
logger.debug(`st.3`)
                    callback(null) // don't sync taskitos
                    return
                }

                // SYNC TASKITOS!
                logger.debug(`Synchronizing checklist items...`)
                const syncParams = {
                    timestamps: storedTaskitoTimeStamps,
                    isFirstSync: isFirstSync,
                    userid: userid
                }
                TCSyncService.syncTaskitos(syncParams, function(err, syncResult) {
                    if (err) {
logger.debug(`st.1`)
                        callback(err)
                    } else {
logger.debug(`st.2`)
                        callback(null)
                    }
                })
            },

            //
            // Sync Notifications
            //
            function(callback) {
                isFirstSync = false

                db.getSetting(constants.SettingsAllNotificationTimeStamps, function(err, value) {
                    if (err) {
                        callback(err)
                    } else {
                        storedNotificationTimeStamps = value
                        callback(null)
                    }
                })
            },
            function(callback) {
                if (!storedNotificationTimeStamps) {
                    isFirstSync = true
                }

                if (!storedNotificationTimeStamps && (!_allNotificationTimeStamps || Object.keys(_allNotificationTimeStamps).length == 0)) {
                    syncNotifications = false
                    callback(null) // continue on
                } else if (!storedNotificationTimeStamps) {
                    syncNotifications = true
                    callback(null) // continue on
                } else {
                    if (Object.keys(storedNotificationTimeStamps).length != (_allNotificationTimeStamps ? Object.keys(_allNotificationTimeStamps).length : 0)) {
                        syncNotifications = true
                        callback(null) // continue on
                    } else {
                        // Loop through all of the server time stamps and make sure that
                        // they match up with the time stamps we have. If they don't, we need
                        // to sync the notifications.
                        Object.keys(storedNotificationTimeStamps).forEach((serverListId) => {
                            const storedTimestamp = storedNotificationTimeStamps[serverListId]
                            const timestamp = _allNotificationTimeStamps && _allNotificationTimeStamps[serverListId] ? _allNotificationTimeStamps[serverListId] : 0

                            if (!storedTimestamp || !timestamp || storedTimestamp != timestamp) {
                                syncNotifications = true
                            }
                        })

                        callback(null)
                    }
                }
            },
            function(callback) {
                if (syncNotifications) {
                    callback(null, null)
                } else {
                    // Determine if there are dirty notifications
                    const dirtyParams = {
                        modifiedBeforeDate: null,
                        tableName: `tdo_task_notifications`
                    }
                    TCSyncService.hasDirtyRecords(dirtyParams, function(err, hasDirtyNotifications) {
                        if (err) {
                            callback(err)
                        } else {
                            callback(null, hasDirtyNotifications)
                        }
                    })
                }
            },
            function(hasDirtyNotifications, callback) {
                if (syncNotifications || hasDirtyNotifications) {
                    // SYNC NOTIFICATIONS!
                    logger.debug(`Synchronizing task notifications...`)
                    const syncParams = {
                        timestamps: storedNotificationTimeStamps,
                        isFirstSync: isFirstSync,
                        userid: userid
                    }
                    TCSyncService.syncNotifications(syncParams, function(err, syncResult) {
                        if (err) {
logger.debug(`sn.1`)
                            callback(err)
                        } else {
logger.debug(`sn.2`)
                            callback(null)
                        }
                    })
                } else {
logger.debug(`sn.3`)
                    callback(null) // don't sync notifications
                }
            },

            //
            // Sync Change Log
            //
            // NOTE: Syncing the Change Log is commented out in iOS, so we don't actually have to do that here either! :)
        ],
        function(err) {
            syncEventSocket.disconnect()
            
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Synchronization failed.`))))
            } else {
                completion(null, true)
            }
        })

    }

    static syncLists(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.syncLists().`))))
            return
        }

        const userid = params.userid ? params.userid : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.syncLists() Missing userid.`))))
            return
        }

        let hasDirtyLists = false
        // let hasSpecialListChanges = false

        const requestServerLists = params.requestServerLists ? params.requestServerLists : false

        if (requestServerLists) {
            logger.debug(`Requesting task lists from the Todo Cloud service.`)
        }

        async.waterfall([
            function(callback) {
                if (requestServerLists) {
                    TCSyncService.requestServerLists({userid: userid}, function(err, result) {
                        if (err) {
                            callback(err)
                        } else {
                            callback(null)
                        }
                    })
                } else {
                    callback(null) // continue on
                }
            },
            function(callback) {
                // Check to see if we have local lists that are dirty that should sync
                TCSyncService.hasDirtyRecords({tableName: "tdo_lists"}, function(err, dirtyLists) {
                    if (err) {
                        callback(err)
                    } else {
                        hasDirtyLists = true
                        callback(null)
                    }
                })
            },
            // function(callback) {
            //     TCSyncService.hasModifiedSpecialListSettings(function(err, hasModifications) {
            //         if (err) {
            //             callback(err)
            //         } else {
            //             hasSpecialListChanges = hasModifications
            //             callback(null)
            //         }
            //     })
            // },
            function(callback) {
                // if (hasDirtyLists || hasSpecialListChanges) {
                if (hasDirtyLists) {
                    TCSyncService.sendLocalLists({userid: userid}, function(err, result) {
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
                if (_serverListHash) {
                    // Save off the new value
                    const settingParams = {}
                    settingParams[constants.SettingListHashKey] = _serverListHash
                    db.setSettings(settingParams, function(err, settingResult) {
                        if (err) {
                            callback(err)
                        } else {
                            callback(null)
                        }
                    })
                } else {
                    callback(null)
                }
            }
        ],
        function(err) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not sync lists.`))))
            } else {
                completion(null, true)
            }
        })
    }

    static requestServerLists(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.requestServerLists().`))))
            return
        }

        const userid = params.userid ? params.userid : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        let syncHadServerChanges = true
        let syncHadErrors = false

        let localLists = []

        const allLists = {}

        async.waterfall([
            function(callback) {
                // Get all the user's lists
                const getListsParams = {
                    userid: userid,
                    includeDeleted: true,
                    includeFiltered: true
                }
                TCListService.listsForUserId(getListsParams, function(err, allLists) {
                    if (err) {
logger.debug(`requestServerLists() 0`)
                        callback(err)
                    } else {
                        localLists = allLists
logger.debug(`requestServerLists() 1 - ${JSON.stringify(localLists)}`)
                        callback(null)
                    }
                })
            },
            function(callback) {
                // Build a dictionary of all of our lists so we can make sure
                // our lists match those on the server.
                localLists.forEach((localList) => {
                    // Skip over the INBOX list
                    if (localList.list.listid == constants.LocalInboxId) {
                        return
                    }

                    if (localList.list.sync_id && localList.list.sync_id.length > 0) {
logger.debug(`requestServerLists() 2`)
                        allLists[localList.list.sync_id] = localList
                    }
                })
                callback(null)
            },
            function(callback) {
                // Now fetch all lists from the server and go through them
                TCSyncService.makeSyncRequest({method: 'getLists'}, function(err, result) {
                    if (err) {
logger.debug(`requestServerLists() 3`)
                        callback(err)
                    } else {
                        if (!result || !result.lists) {
logger.debug(`requestServerLists() 4`)
                            callback(new Error(JSON.stringify(errors.customError(errors.syncError, `getLists returned no data.`))))
                        } else {
logger.debug(`requestServerLists() 5`)
                            callback(null, result.lists, result.speciallists)
                        }
                    }
                })
            },
            function(serverLists, specialLists, callback) {
                const listsToUpdate = []
                const unfoundServerLists = [] // Keep track of lists received by the server that we don't find locally
                serverLists.forEach((serverList) => {
                    const serverListId = serverList.listid
                    const serverListName = serverList.name
                    const serverListColor = serverList.color
                    const serverListIconName = serverList.iconName
                    const serverListSortOrder = serverList.sortOrder
                    const serverListSortType = serverList.sortType
                    const serverListDefaultDueDate = serverList.defaultDueDate
logger.debug(`requestServerLists() 6 - ${JSON.stringify(serverList)}`)

                    if (!serverListId || !serverListName) {
                        console.error(`A server list was received that didn't contain a valid identifier or name.`)
                        syncHadErrors = true
                        return // this breaks out of the .forEach() loop
                    }

                    const aList = allLists[serverListId]
                    if (aList) {
logger.debug(`requestServerLists() 7`)
                        // This is a list that exists locally
                        if (!aList.list.dirty) {
logger.debug(`requestServerLists() 8`)
                            let hasChanges = false
                            let hasSettingsChanges = false
                            let listSettings = {}
                            if (aList.list.name != serverListName) {
logger.debug(`requestServerLists() 9`)
                                aList.list.name = serverListName
                                hasChanges = true
                            }

                            if (serverListColor && serverListColor != aList.settings.color) {
logger.debug(`requestServerLists() 10`)
                                listSettings['color'] = serverListColor
                                hasSettingsChanges = true
                            }

                            if (serverListIconName && serverListIconName != aList.settings.icon_name) {
logger.debug(`requestServerLists() 11`)
                                listSettings['icon_name'] = serverListIconName
                                hasSettingsChanges = true;
                            }
                            
                            if (serverListSortOrder && parseInt(serverListSortOrder) != aList.settings.sort_order) {
logger.debug(`requestServerLists() 12`)
                                listSettings['sort_order'] = parseInt(serverListSortOrder)
                                hasSettingsChanges = true;
                            }
                            
                            if (serverListSortType && parseInt(serverListSortType) != aList.settings.sort_type) {
logger.debug(`requestServerLists() 13`)
                                listSettings['sort_type'] = parseInt(serverListSortType)
                                hasSettingsChanges = true;
                            }
                            
                            if (serverListDefaultDueDate && parseInt(serverListDefaultDueDate) != aList.settings.default_due_date) {
logger.debug(`requestServerLists() 14`)
                                listSttings['default_due_date'] = parseInt(serverListDefaultDueDate)
                                hasSettingsChanges = true;
                            }

                            if (hasChanges || hasSettingsChanges) {
logger.debug(`requestServerLists() 15`)
                                if (hasSettingsChanges) {
logger.debug(`requestServerLists() 16`)
                                    aList.settings = listSettings
                                }
                                listsToUpdate.push(aList)
                            }
                        } else {
logger.debug(`requestServerLists() 17`)
                            // If the local list is dirty, we'll push the changes later on.
                            // For now, do nothing.
                        }

                        delete allLists[serverListId]
                    } else {
logger.debug(`requestServerLists() 18`)
                        // We didn't find the same list locally
                        unfoundServerLists.push(serverList)
                    }
                })

                if (syncHadErrors) {
                    callback(new Error(JSON.stringify(errors.customError(errors.syncError, `Error retrieving server lists.`))))
                } else {
logger.debug(`requestServerLists() 19`)
                    callback(null, listsToUpdate, unfoundServerLists, specialLists)
                }
            },
            function(listsToUpdate, unfoundServerLists, specialLists, callback) {
                // Update the lists that were updated by server values
                async.eachSeries(listsToUpdate,
                    function(aList, eachCallback) {
                        aList.userid = userid
                        aList.listid = aList.list.listid
                        aList.name = aList.list.name
                        aList.dirty = false
                        aList.isSyncService = true // allow dirty to be set to false
                        TCListService.updateList(aList, function(err, updateResult) {
                            if (err) {
logger.debug(`requestServerLists() 20`)
                                eachCallback(err)
                            } else {
logger.debug(`requestServerLists() 21`)
                                eachCallback(null)
                            }
                        })
                    },
                    function(err) {
                        if (err) {
logger.debug(`requestServerLists() 22`)
                            callback(err)
                        } else {
logger.debug(`requestServerLists() 23`)
                            callback(null, unfoundServerLists, specialLists)
                        }
                    }
                )
            },
            function(unfoundServerLists, specialLists, callback) {
                // The 'unfoundServerLists' array contains lists that came from the server
                // that we didn't find a matching sync_id locally. First, we'll try
                // to look local lists that match the name. If we don't find a local
                // list that matches the name, we'll create a new local list.
                const listsToCreate = []
                async.eachSeries(unfoundServerLists,
                    function(serverList, eachCallback) {
logger.debug(`requestServerLists() 24 - ${JSON.stringify(serverList)}`)
                        const findParams = {
                            userid: userid,
                            name: serverList.name
                        }
                        TCListService.findUnsyncedListByName(findParams, function(err, localList) {
                            if (err) {
logger.debug(`requestServerLists() 25`)
                                eachCallback(err)
                            } else {
                                if (localList) {
logger.debug(`requestServerLists() 26`)
                                    localList.userid = userid
                                    localList.listid = localList.list.listid
                                    localList.dirty = false
                                    localList.name = serverList.name
                                    localList.sync_id = serverList.listid
                                    localList.settings.color = serverList.color
                                    localList.settings.icon_name = serverList.iconName
                                    localList.settings.sort_order = serverList.sortOrder
                                    localList.settings.sort_type = serverList.sortType
                                    localList.settings.default_due_date = serverList.defaultDueDate
                                    localList.isSyncService = true // allow dirty to be set to false

                                    // Now update the list locally
                                    TCListService.updateList(localList, function(updateErr, updateResult) {
                                        if (updateErr) {
logger.debug(`requestServerLists() 27`)
                                            eachCallback(err)
                                        } else {
logger.debug(`requestServerLists() 28`)
                                            eachCallback(null)
                                        }
                                    })
                                } else {
logger.debug(`requestServerLists() 29`)
                                    // Create a new local list
                                    const newListParams = {
                                        userid: userid,
                                        sync_id: serverList.listid,
                                        dirty: false,
                                        name: serverList.name,
                                        settings: {
                                            color: serverList.color,
                                            icon_name: serverList.iconName,
                                            sort_order: serverList.sortOrder,
                                            sort_type: serverList.sortType,
                                            default_due_date: serverList.defaultDueDate
                                        },
                                        isSyncService: true // allow dirty to be set to false
                                    }
                                    TCListService.addList(newListParams, function(addErr, addResult) {
                                        if (addErr) {
logger.debug(`requestServerLists() 30`)
                                            eachCallback(err)
                                        } else {
logger.debug(`requestServerLists() 31`)
                                            eachCallback(null)
                                        }
                                    })
                                }
                            }
                        })
                    },
                    function(err) {
                        if (err) {
logger.debug(`requestServerLists() 32`)
                            callback(err)
                        } else {
logger.debug(`requestServerLists() 33`)
                            callback(null, specialLists)
                        }
                    }
                )
            },
            function(specialLists, callback) {
                if (specialLists && Array.isArray(specialLists)) {
                    async.eachSeries(specialLists,
                        function(specialList, eachCallback) {
logger.debug(`requestServerLists() 34 - ${JSON.stringify(specialList)}`)
                            // For now, we're only going to care about INBOX
                            const serverListId = specialList.listid
                            if (!serverListId || serverListId != constants.ServerInboxId) {
                                eachCallback(null)
                                return
                            }

                            const getParams = {
                                userid: userid,
                                listid: constants.LocalInboxId
                            }
                            TCListService.getList(getParams, function(err, inbox) {
                                if (err) {
logger.debug(`requestServerLists() 35`)
                                    eachCallback(err)
                                } else if (inbox.dirty || inbox.settings.dirty) {
logger.debug(`requestServerLists() 36`)
                                    // Do nothing because we have local changes that
                                    // will be pushed up
                                    eachCallback(null)
                                } else {
logger.debug(`requestServerLists() 37`)
                                    if (specialList.color) {
logger.debug(`requestServerLists() 38`)
                                        inbox.settings.color = specialList.color
                                    }
                                    inbox.settings.sort_order = specialList.sortOrder
                                    inbox.settings.sort_type = specialList.sortType
                                    inbox.settings.default_due_date = specialList.defaultDueDate
                                    inbox.listid = inbox.list.listid
                                    inbox.userid = userid
                                    inbox.sync_id = serverListId
                                    inbox.dirty = false
                                    inbox.isSyncService = true // allow dirty to be set to false
                                    TCListService.updateList(inbox, function(updateErr, updateResult) {
                                        if (updateErr) {
logger.debug(`requestServerLists() 39`)
                                            eachCallback(updateErr)
                                        } else {
logger.debug(`requestServerLists() 40`)
                                            eachCallback(null)
                                        }
                                    })
                                }
                            })
                        },
                        function(err) {
                            if (err) {
logger.debug(`requestServerLists() 41`)
                                callback(err)
                            } else {
logger.debug(`requestServerLists() 42`)
                                callback(null)
                            }
                        }
                    )
                } else {
logger.debug(`requestServerLists() 43`)
                    callback(null) // do nothing
                }
            },
            function(callback) {
                // Go through the lists that no longer exist on the server
                // and make sure we delete them from the local database,
                // including all associated tasks, taskitos, notifications, etc.
                async.eachSeries(allLists,
                    function(listToDelete, eachCallback) {
logger.debug(`requestServerLists() 44`)
                        if (listToDelete.listid == constants.LocalInboxId) {
logger.debug(`requestServerLists() 45`)
                            // Skip over the INBOX
                            eachCallback(null)
                        } else {
logger.debug(`requestServerLists() 46`)
                            const deleteParams = {
                                listid: listToDelete.list.listid
                            }
                            TCListService.permanentlyDeleteList(deleteParams, function(err, result) {
logger.debug(`requestServerLists() 47`)
                                eachCallback(err)
                            })
                        }
                    },
                    function(err) {
                        if (err) {
logger.debug(`requestServerLists() 48`)
                            callback(err)
                        } else {
logger.debug(`requestServerLists() 49`)
                            callback(null)
                        }
                    }
                )
            }
        ],
        function(err) {
            if (err) {
logger.debug(`requestServerLists() 50`)
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not sync lists from server.`))))
            } else {
logger.debug(`requestServerLists() 51`)
                completion(null, true)
            }
        })
    }

    static sendLocalLists(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.sendLocalLists().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }
        
        const addLists = []
        const modifyLists = []
        const deleteLists = []
        const modifySpecialLists = []

        let addListString = null
        let modListString = null
        let delListString = null
        let modSpecialListString = null

        let dirtyLists = null

        const listsToDeleteLocally = []

        // When we're looking at dirty lists, we'll save off the inbox
        // if we find it.
        let dirtyInbox = null

        async.waterfall([
            function(callback) {
                // Get all the user's lists
                const getListsParams = {
                    userid: userid,
                    includeDeleted: true,
                    includeFiltered: true
                }
                TCListService.listsForUserId(getListsParams, function(err, allLists) {
                    if (err) {
                        callback(err)
                    } else {
                        // Save off only lists that are dirty
                        dirtyLists = allLists.filter((aList) => {
                            if (aList.list && aList.list.dirty) {
                                // Filter out the inbox if it's dirty,
                                // but save it off so we can sync it.
                                if (aList.list.listid == constants.LocalInboxId) {
                                    dirtyInbox = aList
                                } else {
                                    return true
                                }
                            }

                            return false
                        })
                        callback(null)
                    }
                })
            },
            function(callback) {
                async.eachSeries(dirtyLists,
                    function(aList, eachCallback) {
                        if (aList.list.listid == constants.LocalInboxId) {
                            eachCallback(null) // skip processing the local inbox
                        } else {
                            if (aList.list.deleted) {
                                if (aList.list.sync_id && aList.list.sync_id.length > 0) {
                                    deleteLists.push({
                                        listId: aList.list.sync_id
                                    })
                                } else {
                                    listsToDeleteLocally.push(aList)
                                }
                            } else {
                                if (aList.list.sync_id && aList.list.sync_id.length > 0) {
                                    // List needs to be modified on the server
                                    modifyLists.push({
                                        listId: aList.list.sync_id,
                                        listName: aList.list.name,
                                        color: aList.settings.color,
                                        iconName: aList.settings.icon_name,
                                        sortOrder: aList.settings.sort_order,
                                        sortType: aList.settings.sort_type,
                                        defaultDueDate: aList.settings.default_due_date
                                    })
                                } else {
                                    // List needs to be added to the server
                                    addLists.push({
                                        tmpListId: aList.list.listid,
                                        listName: aList.list.name,
                                        color: aList.settings.color,
                                        iconName: aList.settings.icon_name,
                                        sortOrder: aList.settings.sort_order,
                                        sortType: aList.settings.sort_type,
                                        defaultDueDate: aList.settings.default_due_date
                                    })
                                }
                            }
                            eachCallback(null)
                        }
                    },
                    function(err) {
                        callback(err)
                    }
                )
            },
            function(callback) {
                // Delete local lists permanantly that have never been synchronized
                // and are marked as deleted (listsToDeleteLocally).
                async.eachSeries(listsToDeleteLocally,
                    function(aList, eachCallback) {
                        const deleteParams = {
                            listid: aList.list.listid
                        }
                        TCListService.permanentlyDeleteList(deleteParams, function(err, result) {
                            eachCallback(err)
                        })
                    },
                    function(err) {
                        if (err) {
                            callback(err)
                        } else {
                            callback(null)
                        }
                    }
                )
            },
            function(callback) {
                // Determine if there were any "special" list modifications.
                // In practice, this is only referring to the Inbox. If the inbox
                // was found to be dirty, we'll already have it stored off:
                if (dirtyInbox && dirtyInbox.list.dirty) {
                    modifySpecialLists.push({
                        listId: constants.ServerInboxId,
                        color: dirtyInbox.settings.color
                    })
                }

                // Prep the add, modify, and delete strings
                if (addLists.length > 0) {
                    addListString = JSON.stringify(addLists)
                }
                if (modifyLists.length > 0) {
                    modListString = JSON.stringify(modifyLists)
                }
                if (deleteLists.length > 0) {
                    delListString = JSON.stringify(deleteLists)
                }
                if (modifySpecialLists.length > 0) {
                    modSpecialListString = JSON.stringify(modifySpecialLists)
                }

                callback(null)
            },
            function(callback) {
                if (!addListString && !modListString && !delListString && !modSpecialListString) {
                    // There's nothing to push
                    callback(null, null)
                } else {
                    const syncParams = {
                        method: 'changeLists',
                    }
                    if (addListString) {
                        syncParams['addLists'] = addListString
                    }
                    if (modListString) {
                        syncParams['updateLists'] = modListString
                    }
                    if (delListString) {
                        syncParams['deleteLists'] = delListString
                    }
                    if (modSpecialListString) {
                        syncParams['updateSpecialLists'] = modSpecialListString
                    }
                    TCSyncService.makeSyncRequest(syncParams, function(err, result) {
                        if (err) {
                            callback(err)
                        } else {
                            if (!result || !result.results) {
                                callback(new Error(JSON.stringify(errors.customError(errors.syncError, `Sync method 'changeLists' returned no data.${result.errorCode ? ' ' + result.errorCode + ': ' + result.errorDesc : ''}`))))
                            } else {
                                callback(null, result)
                            }
                        }
                    })
                }
            },
            function(syncResult, callback) {
                // Process added lists result
                if (syncResult && syncResult.results.added) {
                    async.eachSeries(syncResult.results.added,
                        function(addedList, eachCallback) {
                            if (addedList.errorCode) {
                                logger.debug(`Error synchronizing new list with ID: ${addedList.tmpListId}`)
                            } else {
                                // Don't access the local database again to get the
                                // local list object, but pull it from the `dirtyLists`
                                const syncid = addedList.listId
                                const tmpListId = addedList.tmpListId
                                if (tmpListId) {
                                    const localList = dirtyLists.find((aList) => {
                                        return (aList.list.listid == tmpListId)
                                    })
                                    localList.list.sync_id = syncid
                                    localList.list.userid = userid
                                    localList.list.dirty = false
                                    localList.list.isSyncService = true // allow dirty to be set to false
                                    TCListService.updateList(localList.list, function(err, result) {
                                        eachCallback(err, syncResult)
                                    })
                                } else {
                                    eachCallback(null, syncResult)                                    
                                }
                            }
                        },
                        function(err) {
                            callback(err, syncResult)
                        }
                    )
                } else {
                    callback(null, syncResult)
                }
            },
            function(syncResult, callback) {
                // Process updated lists result
                if (syncResult && syncResult.results.updated) {
                    async.eachSeries(syncResult.results.updated,
                        function(updatedList, eachCallback) {
                            if (updatedList.errorCode) {
                                logger.debug(`Error synchronizing changes for list with ID: ${updatedList.tmpListId}`)
                            } else {
                                // Don't access the local database again to get the
                                // local list object, but pull it from the `dirtyLists`
                                const syncid = updatedList.listId
                                if (syncid) {
                                    const localList = dirtyLists.find((aList) => {
                                        return (aList.list.sync_id == syncid)
                                    })
                                    localList.list.userid = userid
                                    localList.list.dirty = false
                                    localList.isSyncService = true // allow dirty to be set to false
                                    TCListService.updateList(localList.list, function(err, result) {
                                        eachCallback(err, syncResult)
                                    })
                                } else {
                                    eachCallback(null, syncResult)
                                }
                            }
                        },
                        function(err) {
                            callback(err, syncResult)
                        }
                    )
                } else {
                    callback(null, syncResult)
                }
            },
            function(syncResult, callback) {
                // Process deleted lists result
                if (syncResult && syncResult.results.deleted) {
                    async.eachSeries(syncResult.results.deleted,
                        function(deletedList, eachCallback) {
                            if (deletedList.errorCode) {
                                logger.debug(`Error synchronizing deleted list (ID: ${deletedList.tmpListId})`)
                            } else {
                                // Don't access the local database again to get the
                                // local list object, but pull it from the `dirtyLists`
                                const syncid = deletedList.listId
                                if (syncid) {
                                    const localList = dirtyLists.find((aList) => {
                                        return (aList.list.sync_id == syncid)
                                    })
                                    TCListService.permanentlyDeleteList(localList.list, function(err, result) {
                                        eachCallback(err, syncResult)
                                    })
                                } else {
                                    eachCallback(null, syncResult)                                    
                                }
                            }
                        },
                        function(err) {
                            callback(err, syncResult)
                        }
                    )
                } else {
                    callback(null, syncResult)
                }
            },
            function(syncResult, callback) {
                // Process "special" lists result
                if (syncResult && syncResult.results.updatedSpecialLists) {
                    async.eachSeries(syncResult.results.updatedSpecialLists,
                        function(specialList, eachCallback) {
                            if (specialList.errorCode) {
                                logger.debug(`Error synchronizing special list (ID: ${specialList.tmpListId})`)
                            } else {
                                // Don't access the local database again to get the
                                // local list object, but pull it from the `dirtyLists`
                                const syncid = specialList.listId
                                if (syncid && syncid == constants.ServerInboxId) {
                                    dirtyInbox.list.sync_id = syncid
                                    dirtyInbox.list.userid = userid
                                    dirtyInbox.list.dirty = false
                                    dirtyInbox.isSyncService = true // allow dirty to be set to false
                                    TCListService.updateList(dirtyInbox.list, function(err, result) {
                                        eachCallback(err, syncResult)
                                    })
                                } else {
                                    eachCallback(null, syncResult)
                                }
                            }
                        },
                        function(err) {
                            callback(err, syncResult)
                        }
                    )
                } else {
                    callback(null, syncResult)
                }
            },
            function(syncResult, callback) {
                // Record the list hash in memory
                if (syncResult) {
                    _serverListHash = syncResult.listHash
                }
                callback(null)
            }
        ],
        function(err) {
            if (err) {
                // Clear out the server list hash so that the client doesn't
                // think that it has successfully synchronized.
                _serverListHash = null
                completion(err)
            } else {
                completion(null, true)
            }
        })
    }

    static syncSmartLists(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.syncSmartLists().`))))
            return
        }

        const userid = params.userid ? params.userid : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        let hasDirtyLists = false
        let hasSpecialListChanges = false

        const requestServerSmartLists = params.requestServerSmartLists ? params.requestServerSmartLists : false     

        async.waterfall([
            function(next) {
                if (!requestServerSmartLists) {
                    next() // continue on
                    return
                } 
                    
                TCSyncService.requestServerSmartLists({userid: userid}, function(err, result) {
                    next(err ? err : null)
                })
            },
            function(next) {
                TCSyncService.hasDirtyRecords({userid: userid, tableName: "tdo_smart_lists"}, function(err, dirtyLists) {
                    if (err) {
                        next(err)
                        return
                    } 
                
                    hasDirtyLists = dirtyLists
                    next()
                })
            },
            function(next) {
                if (!hasDirtyLists) {
                    next()
                    return
                }

                TCSyncService.sendLocalSmartLists({userid: userid}, function(err, result) {
                    next(err ? err : null)
                })
            },
            function(next) {
                if (!_serverSmartListHash) {
                    next()
                    return
                }
                
                // Save off the new value
                const settingParams = {}
                settingParams[constants.SettingSmartListHashKey] = _serverSmartListHash
                db.setSettings(settingParams, function(err, settingResult) {
                    next(err ? err : null)
                })
            }
        ],
        function(err) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not sync smart lists.`))))
                return
            } 

            completion(null, true)
        })
    }

    static requestServerSmartLists(params, completion) {
         if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.requestServerSmartLists().`))))
            return
        }

        const userid = params.userid ? params.userid : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        let localSmartLists = []
        const allSmartLists = {}

        async.waterfall([
            function(next) {
                // Get all the user's lists
                const getListsParams = {
                    userid: userid
                }
                TCSmartListService.getSmartLists(getListsParams, function(err, smartLists) {
                    if (err) {
                        next(err)
                        return
                    } 

                    localSmartLists = smartLists
                    next(null)
                })
            },
            function(next) {
                // Build a dictionary of all of our lists so we can make sure
                // our lists match those on the server.
                localSmartLists.forEach((localSmartList) => {
                    if (localSmartList.sync_id && localSmartList.sync_id.length > 0) {
                        allSmartLists[localSmartList.sync_id] = localSmartList
                    }
                })

                next(null)
            },
            function(next) {
                // Now fetch all smart lists from the server and go through them
                TCSyncService.makeSyncRequest({method: 'getSmartLists'}, function(err, result) {
                    if (err) {
                        next(err)
                        return
                    } 
                    if (!result) {
                        next(new Error(JSON.stringify(errors.customError(errors.syncError, `getSmartLists returned no data.`))))
                        return
                    } 

                    next(null, result.smartLists)
                })
            },
            function(serverSmartLists, next) {
                const smartListsToUpdate = []
                const unfoundServerSmartLists = [] // Keep track of lists received by the server that we don't find locally
                async.eachSeries(serverSmartLists, (serverSmartList, eachCallback) => {
                    const serverSmartListId = serverSmartList.listId
                    const serverSmartListName = serverSmartList.name
                    const serverSmartListColor = serverSmartList.color
                    const serverSmartListIconName = serverSmartList.iconName
                    const serverSmartListSortOrder = serverSmartList.sortOrder
                    const serverSmartListJSON = serverSmartList.jsonFilter 
                    const serverSmartListSortType = serverSmartList.sortType
                    const serverSmartListDefaultDueDate = serverSmartList.defaultDueDate
                    let serverSmartListDefaultList = serverSmartList.defaultList // re-assignable if error detected
                    const serverSmartListExcludedListIDs = serverSmartList.excludedListIDs 
                    const serverSmartListCompletedTasksFilter = serverSmartList.completedTasksFilter

                    if (!serverSmartListId || !serverSmartListName) {
                        eachCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `A server list was received that didn't contain a valid identifier or name.`))))
                        return
                    }

                    async.waterfall([
                        (next) => {
                            if (!serverSmartListDefaultList) {
                                next(null, null)
                                return
                            }

                            logger.debug(`Server Smart list (${serverSmartListName}) default list id: ${serverSmartListDefaultList}`)

                            const isInbox = (
                                serverSmartListDefaultList.endsWith("UNFILED") || 
                                serverSmartListDefaultList.toLowerCase() == constants.LocalInboxId.toLocaleLowerCase()
                            )
                            if (serverSmartListDefaultList && isInbox) {
                                next(null, constants.LocalInboxId)
                                return
                            }

                            TCListService.listIdForSyncId({ syncid: serverSmartListDefaultList }, (err, result) => {
                                if (err) {
                                    try {
                                        let errObj = JSON.parse(err.message)
                                        if (errObj.errorType == errors.listNotFound.errorType) {
                                            // Prevent sync from failing nad let's just set this to be the server INBOX
                                            serverSmartListDefaultList = constants.ServerInboxId
                                            next(null, constants.LocalInboxId)
                                        } else {
                                            next(err)
                                        }
                                    } catch (e) {
                                        logger.debug(`Error parsing error message: ${e}`)
                                        next(err)
                                    }
                                } else {
                                    next(null, result)
                                }
                            })
                        },
                        (defaultList, next) => {
                            if (!serverSmartListExcludedListIDs) {
                                next(null, null, defaultList)
                                return
                            }

                            async.concatSeries(serverSmartListExcludedListIDs.split(','), (id, nextConcat) => {
                                if (id.endsWith("UNFILED")) {
                                    nextConcat(null, constants.LocalInboxId)
                                    return
                                }

                                TCListService.listIdForSyncId({ syncid: id }, (err, result) => {
                                    if (err) {
                                        try {
                                            let errObj = JSON.parse(err.message)
                                            if (errObj.errorType == errors.listNotFound.errorType) {
                                                // Ignore this list
                                                nextConcat(null, null)
                                            } else {
                                                nextConcat(err)
                                            }
                                        } catch (e) {
                                            logger.debug(`Error parsing error message: ${e}`)
                                            nextConcat(err)
                                        }
                                    } else {
                                        nextConcat(null, result)
                                    }
                                })
                            },
                            (err, results) => {
                                if (err) logger.debug(`Error finding a local list for a sync id in a smart list's (${serverSmartListName}) excluded list ids.`)
                                err ? next(err) : next(null, new Set(results), defaultList)
                            })
                        },
                        (excludedListIds, defaultList, next) => {
                            const filterObject = JSON.parse(serverSmartListJSON)
                            if (!defaultList) {
                                delete filterObject.defaultList
                            }
                            else {
                                filterObject.defaultList = defaultList
                            }

                            if (excludedListIds && excludedListIds.size > 0) {
                                filterObject.excludeLists = Array.from(excludedListIds)
                            }
                            else {
                                delete filterObject.excludeLists
                            }

                            next(null, JSON.stringify(filterObject), excludedListIds, defaultList)
                        },
                        (localJSON, excludedListIds, defaultList, next) => {
                            const aSmartList = allSmartLists[serverSmartListId]
                            if (aSmartList) {
                                // This is a list that exists locally
                                if (!aSmartList.dirty) {
                                    let hasChanges = false
                                    let smartListSettings = {}
                                    if (aSmartList.name != serverSmartListName) {
                                        aSmartList.name = serverSmartListName
                                        hasChanges = true
                                    }

                                    if (serverSmartListColor && serverSmartListColor != aSmartList.color) {
                                        aSmartList.color = serverSmartListColor
                                        hasChanges = true
                                    }

                                    if (serverSmartListIconName && serverSmartListIconName != aSmartList.icon_name) {
                                        aSmartList.icon_name = serverSmartListIconName
                                        hasChanges = true
                                    }
                                    
                                    if (serverSmartListSortOrder && parseInt(serverSmartListSortOrder) != aSmartList.sort_order) {
                                        aSmartList.sort_order = parseInt(serverSmartListSortOrder)
                                        hasChanges = true
                                    }

                                    if (localJSON && localJSON != aSmartList.json_filter) {
                                        aSmartList.json_filter = localJSON
                                        hasChanges = true
                                    }
                                    
                                    if (serverSmartListSortType && parseInt(serverSmartListSortType) != aSmartList.sort_type) {
                                        aSmartList.sort_type = parseInt(serverSmartListSortType)
                                        hasChanges = true;
                                    }
                                    
                                    if (serverSmartListDefaultDueDate && parseInt(serverSmartListDefaultDueDate) != aSmartList.default_due_date) {
                                        aSmartList.default_due_date = parseInt(serverSmartListDefaultDueDate)
                                        hasChanges = true;
                                    }

                                    if (defaultList && defaultList != aSmartList.default_list) {
                                        aSmartList.default_list = defaultList
                                        hasChanges = true
                                    }

                                    if (excludedListIds) {
                                        const excludedListsString = Array.from(excludedListIds).join(',')
                                        if (excludedListsString && excludedListsString != aSmartList.excluded_list_ids) {
                                            aSmartList.excluded_list_ids = excludedListsString
                                            hasChanges = true
                                        }
                                    } else if (aSmartList.excluded_list_ids) {
                                        aSmartList.excluded_list_ids = null
                                    }

                                    if (serverSmartListCompletedTasksFilter && serverSmartListCompletedTasksFilter != aSmartList.completed_tasks_filter) {
                                        aSmartList.completed_tasks_filter = serverSmartListCompletedTasksFilter
                                        hasChanges = true
                                    }

                                    if (hasChanges) {
                                        smartListsToUpdate.push(aSmartList)
                                    }
                                } 

                                delete allSmartLists[serverSmartListId]
                            } else {
                                // We didn't find the same list locally
                                // Store the local reference list ids on the
                                // server smart list object so they can be used 
                                // to create a new list.
                                
                                const excludedListsString = excludedListIds ? Array.from(excludedListIds).join(',') : null
                                serverSmartList.defaultList = defaultList
                                serverSmartList.jsonFilter = localJSON
                                serverSmartList.excludedListIDs = excludedListsString

                                unfoundServerSmartLists.push(serverSmartList)
                            }

                            next(null)
                        }
                    ],
                    (err) => {
                        eachCallback(err ? err : null)
                    })    
                },
                (err) => {
                    if (err) {
                        next(new Error(JSON.stringify(errors.customError(errors.syncError, `Error retrieving server smart lists.`))))
                        return
                    } 

                    next(null, smartListsToUpdate, unfoundServerSmartLists)
                })
            },
            function(smartListsToUpdate, unfoundServerLists, next) {
                // Update the lists that were updated by server values
                async.eachSeries(smartListsToUpdate,
                    function(aSmartList, eachCallback) {
                        aSmartList.userid = userid
                        aSmartList.dirty = false
                        aSmartList.isSyncService = true // allow dirty bit to be set to false
                        TCSmartListService.updateSmartList(aSmartList, function(err, updateResult) {
                            if (err) {
                                eachCallback(err)
                                return
                            } 
                            eachCallback(null)
                        })
                    },
                    function(err) {
                        if (err) {
                            next(err)
                            return
                        } 
                        next(null, unfoundServerLists)
                    }
                )
            },
            function(unfoundServerSmartLists, next) {
                // The 'unfoundServerSmartLists' array contains smart lists that came 
                // from the server that we didn't find a matching sync_id locally. First, we'll try
                // to look local lists that match the name. If we don't find a local
                // smart list that matches the name, we'll create a new local smart list.
                async.eachSeries(unfoundServerSmartLists,
                    function(serverSmartList, eachCallback) {
                        const findParams = {
                            userid: userid,
                            name: serverSmartList.name
                        }
                        TCSmartListService.findUnsyncedSmartListByName(findParams, function(err, localSmartList) {
                            if (err) {
                                eachCallback(err)
                                return
                            } 
                            if (localSmartList) {
                                localSmartList.userid = userid
                                localSmartList.dirty = false
                                localSmartList.name = serverSmartList.name
                                localSmartList.sync_id = serverSmartList.listId
                                localSmartList.color = serverSmartList.color
                                localSmartList.icon_name = serverSmartList.iconName
                                localSmartList.sort_order = serverSmartList.sortOrder
                                localSmartList.sort_type = serverSmartList.sortType
                                localSmartList.default_due_date = serverSmartList.defaultDueDate

                                // These local list id dependent values were calculated and stored 
                                // to the serverSmartList object earlier
                                localSmartList.default_list = serverSmartList.defaultList
                                localSmartList.excluded_list_ids = serverSmartList.excludedListIDs
                                localSmartList.json_filter = serverSmartList.jsonFilter
                                localSmartList.isSyncService = true // allow the dirty bit to be set to false

                                // Now update the list locally
                                TCSmartListService.updateSmartList(localSmartList, function(updateErr, updateResult) {
                                    if (updateErr) {
                                        eachCallback(err)
                                        return
                                    } 

                                    eachCallback(null)
                                })
                            } else {
                                // Create a new local list
                                const newListParams = {
                                    userid: userid,
                                    sync_id: serverSmartList.listId,
                                    dirty: false,
                                    name: serverSmartList.name,
                                    color: serverSmartList.color,
                                    icon_name: serverSmartList.iconName,
                                    sort_order: serverSmartList.sortOrder,
                                    sort_type: serverSmartList.sortType,
                                    default_due_date: serverSmartList.defaultDueDate,

                                    // These local list id dependent values were calculated and stored 
                                    // to the serverSmartList object earlier
                                    default_list: serverSmartList.defaultList,
                                    exclude_list_ids: serverSmartList.excludedListIDs,
                                    json_filter: serverSmartList.jsonFilter,

                                    isSyncService: true // allow dirty to be false

                                }
                                TCSmartListService.createSmartList(newListParams, function(addErr, addResult) {
                                    if (addErr) {
                                        eachCallback(err)
                                        return
                                    }

                                    eachCallback(null)
                                })
                            }
                        })
                    },
                    function(err) {
                        next(err ? err : null)
                    }
                )
            },
            function(next) {
                // Go through the smart lists that no longer exist on the server
                // and make sure we delete them from the local database.
                async.eachSeries(allSmartLists,
                    function(serverSmartList, eachCallback) {
                        const deleteParams = {
                            listid: serverSmartList.listid,
                            userid: userid
                        }
                        TCSmartListService.permanentlyDeleteSmartList(deleteParams, function(err, result) {
                            eachCallback(err)
                        })
                    },
                    function(err) {
                        next(err ? err : null)
                    }
                )
            }
        ],
        function(err) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not sync smart lists from server.`))))
                return
            } 
            completion(null, true)
        })
    }

    static sendLocalSmartLists(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.sendLocalSmartLists().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }
        
        const addSmartLists = []
        const modifySmartLists = []
        const deleteSmartLists = []

        let addSmartListString = null
        let modSmartListString = null
        let delSmartListString = null

        let dirtySmartLists = null

        const smartListsToDeleteLocally = []

        async.waterfall([
            function(next) {
                // Get all the user's smart lists
                const getListsParams = {
                    userid: userid,
                    includeDeleted : true
                }
                TCSmartListService.getSmartLists(getListsParams, function(err, allSmartLists) {
                    if (err) {
                        next(err)
                        return
                    } 
                    // Save off only smart lists that are dirty
                    dirtySmartLists = allSmartLists.filter((aSmartList) => aSmartList.dirty)
                    next(null)
                })
            },
            function(next) {
                const sortDeletedSmartList = (aSmartList) => {
                    if (aSmartList.sync_id && aSmartList.sync_id.length > 0) {
                        deleteSmartLists.push({ listId: aSmartList.sync_id })
                    } else {
                        smartListsToDeleteLocally.push(aSmartList)
                    }
                }

                async.eachSeries(dirtySmartLists,
                    function(aSmartList, eachCallback) {
                        if (aSmartList.deleted) {
                            sortDeletedSmartList(aSmartList)
                            eachCallback(null)
                            return
                        }
                        
                        async.waterfall([
                            function(next) {
                                if (!aSmartList.default_list) {
                                    next(null, null)
                                    return
                                }
                                if (aSmartList.default_list && aSmartList.default_list.endsWith("INBOX")) {
                                    next(null, constants.ServerInboxId)
                                    return
                                }

                                TCListService.syncIdForListId({ listid: aSmartList.default_list }, (err, result) => {
                                    err ? next(err) : next(null, result)
                                })
                            },
                            function(defaultList, next) {
                                if (!aSmartList.excluded_list_ids) {
                                    next(null, null, defaultList)
                                    return
                                }

                                async.concatSeries(aSmartList.excluded_list_ids.split(','), (id, nextConcat) => {
                                    if (id.endsWith("INBOX")) {
                                        nextConcat(null, constants.ServerInboxId)
                                        return
                                    }

                                    TCListService.syncIdForListId({ listid: id }, (err, result) => {
                                        err ? nextConcat(err) : nextConcat(null, result)
                                    })
                                },
                                (err, results) => {
                                    err ? next(err) : next(null, new Set(results), defaultList)
                                })
                            },
                            function(excludedListIds, defaultList, next) {
                                const filterObject = JSON.parse(aSmartList.json_filter)
                                if (!defaultList) {
                                    delete filterObject.defaultList
                                }
                                else {
                                    filterObject.defaultList = defaultList
                                }

                                if (excludedListIds && excludedListIds.size > 0) {
                                    filterObject.excludeLists = Array.from(excludedListIds)
                                }
                                else {
                                    delete filterObject.excludeLists
                                }

                                next(null, JSON.stringify(filterObject), excludedListIds, defaultList)
                            },
                            function(serverJSON, excludedListIds, defaultList, next) {
                                const smartListInfo = {
                                    listName: aSmartList.name,
                                    color: aSmartList.color,
                                    iconName: aSmartList.icon_name,
                                    sortOrder: aSmartList.sort_order,
                                    jsonFilter: serverJSON, 
                                    sortType: aSmartList.sort_type,
                                    defaultDueDate: aSmartList.default_due_date,
                                    defaultList: defaultList, 
                                    excludedListIDs: excludedListIds ? Array.from(excludedListIds).join(',') : null, 
                                    completedTasksFilter: aSmartList.completed_tasks_filter
                                }

                                if (aSmartList.sync_id && aSmartList.sync_id.length > 0) {
                                    // List needs to be modified on the server
                                    smartListInfo.listId = aSmartList.sync_id
                                    modifySmartLists.push(smartListInfo)
                                } else {
                                    // List needs to be added to the server
                                    smartListInfo.tmpListId = aSmartList.listid
                                    addSmartLists.push(smartListInfo)
                                }
                                next()
                            }
                        ],
                        function(err)  {
                            eachCallback(err ? err : null)
                        })
                    },
                    function(err) {
                        next(err)
                    }
                )
            },
            function(next) {
                // Delete local lists permanantly that have never been synchronized
                // and are marked as deleted (listsToDeleteLocally).
                async.eachSeries(smartListsToDeleteLocally,
                    function(aSmartList, eachCallback) {
                        const deleteParams = {
                            listid: aSmartList.listid
                        }
                        TCSmartListService.permanentlyDeleteSmartList(deleteParams, function(err, result) {
                            eachCallback(err)
                        })
                    },
                    function(err) {
                        next(err ? err : null)
                    }
                )
            },
            function(callback) {
                // Prep the add, modify, and delete strings
                if (addSmartLists.length > 0) {
                    addSmartListString = JSON.stringify(addSmartLists)
                }
                if (modifySmartLists.length > 0) {
                    modSmartListString = JSON.stringify(modifySmartLists)
                }
                if (deleteSmartLists.length > 0) {
                    delSmartListString = JSON.stringify(deleteSmartLists)
                }

                callback(null)
            },
            function(next) {
                if (!addSmartListString && !modSmartListString && !delSmartListString) {
                    // There's nothing to push
                    next(null, null)
                    return
                } 
                const syncParams = {
                    method: 'changeSmartLists',
                    addSmartLists: addSmartListString ? addSmartListString : undefined,
                    updateSmartLists: modSmartListString ? modSmartListString : undefined,
                    deleteSmartLists: delSmartListString ? delSmartListString : undefined
                }
                TCSyncService.makeSyncRequest(syncParams, function(err, result) {
                    if (err) {
                        next(err)
                        return
                    }
                    if (!result || !result.results) {
                        next(new Error(JSON.stringify(errors.customError(errors.syncError, `Sync method 'changeLists' returned no data.${result.errorCode ? ' ' + result.errorCode + ': ' + result.errorDesc : ''}`))))
                        return
                    }

                    next(null, result)
                })
            },
            function(syncResult, next) {
                // Process added lists result
                if (!syncResult || !syncResult.results.added) {
                    next(null, syncResult)
                    return
                } 

                async.eachSeries(syncResult.results.added.filter((added) => added && added.tmpListId != null),
                    function(addedSmartList, eachCallback) {
                        if (addedSmartList.errorCode) {
                            logger.debug(`Error synchronizing new smart list with ID: ${addedSmartList.tmpListId}`)
                            eachCallback(null, syncResult)
                            return
                        } 

                        // Don't access the local database again to get the
                        // local list object, but pull it from the `dirtyLists`
                        const syncid = addedSmartList.listId
                        const tmpListId = addedSmartList.tmpListId  
                        const localSmartList = dirtySmartLists.find((aSmartList) => aSmartList.listid == tmpListId)
                        localSmartList.sync_id = syncid
                        localSmartList.userid = userid
                        localSmartList.dirty = false
                        localSmartList.isSyncService = true // allow the dirty bit to be set to false
                        TCSmartListService.updateSmartList(localSmartList, function(err, result) {
                            eachCallback(err, syncResult)
                        })
                    },
                    function(err) {
                        next(err, syncResult)
                    })
            },
            function(syncResult, next) {
                // Process updated lists result
                if (!syncResult || !syncResult.results.updated) {
                    next(null, syncResult)
                    return
                }

                async.eachSeries(syncResult.results.updated.filter((updated) => updated && updated.listId != null),
                    function(updatedList, eachCallback) {
                        if (updatedList.errorCode) {
                            logger.debug(`Error synchronizing changes for smart list with ID: ${updatedList.tmpListId}`)
                            eachCallback(null, syncResult)
                            return
                        } 

                        // Don't access the local database again to get the
                        // local list object, but pull it from the `dirtyLists`
                        const syncid = updatedList.listId
                        const localSmartList = dirtySmartLists.find((aSmartList) => aSmartList.sync_id == syncid)
                        localSmartList.userid = userid
                        localSmartList.dirty = false
                        localSmartList.isSyncService = true // allow the dirty bit to be set to false
                        TCSmartListService.updateSmartList(localSmartList, function(err, result) {
                            eachCallback(err, syncResult)
                        })
                    },
                    function(err) {
                        next(err, syncResult)
                    })
            },
            function(syncResult, next) {
                // Process deleted lists result
                if (!syncResult || !syncResult.results.deleted) {
                    next(null, syncResult)
                    return
                }

                async.eachSeries(syncResult.results.deleted.filter((del) => del && del.listId != null),
                    function(deletedList, eachCallback) {
                        if (deletedList.errorCode) {
                            logger.debug(`Error synchronizing deleted list (ID: ${deletedList.tmpListId})`)
                            eachCallback(null, syncResult)
                            return
                        } 
                        // Don't access the local database again to get the
                        // local list object, but pull it from the `dirtyLists`
                        const syncid = deletedList.listId
                        const localSmartList = dirtySmartLists.find((aSmartList) => aSmartList.sync_id == syncid)
                        TCSmartListService.permanentlyDeleteSmartList(localSmartList, function(err, result) {
                            eachCallback(err, syncResult)
                        })
                    },
                    function(err) {
                        next(err, syncResult)
                    })
            },
            function(syncResult, callback) {
                // Record the list hash in memory
                if (syncResult) {
                    _serverSmartListHash = syncResult.listHash
                }
                callback(null)
            }
        ],
        function(err) {
            if (err) {
                // Clear out the server list hash so that the client doesn't
                // think that it has successfully synchronized.
                _serverSmartListHash = null
                completion(err)
            } else {
                completion(null, true)
            }
        })
    }

    static syncUsers(params, completion) {
        logger.debug(`Running user sync`)
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.syncUsers().`))))
            return
        }

        const userid = params.userid ? params.userid : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const requestServerUsers = params.requestServerUsers ? params.requestServerUsers : false     

        async.waterfall([
            function(next) {
                if (!requestServerUsers) {
                    next() // continue on
                    return
                } 
                    
                TCSyncService.requestServerUsers({userid: userid}, function(err, result) {
                    next(err ? err : null)
                })
            },
            function(next) {
                if (!_serverUserHash) {
                    next()
                    return
                }
                
                // Save off the new value
                const settingParams = {}
                settingParams[constants.SettingUserHashKey] = _serverUserHash
                db.setSettings(settingParams, function(err, settingResult) {
                    next(err ? err : null)
                })
            }
        ],
        function(err) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not sync users.`))))
                return
            } 

            completion(null, true)
        })
    }

    static requestServerUsers(params, completion) {
         if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const userid = params.userid ? params.userid : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        let localUsers = []
        const allUsers = {}

        async.waterfall([
            function(next) {
                // Get all the stored users
                const getUsersParams = {}
                TCAccountService.getAllUsers(getUsersParams, function(err, users) {
                    if (err) {
                        next(err)
                        return
                    } 

                    localUsers = users
                    next(null)
                })
            },
            function(next) {
                localUsers.forEach(user => {
                    allUsers[user.userid] = user
                })

                next(null)
            },
            function(next) {
                const syncUserRequestParams = {
                    method : 'getUsers'
                }
                TCSyncService.makeSyncRequest(syncUserRequestParams, (err, result) => {
                    if (err) {
                        next(errors.create(errors.syncError, `Get users failed: ${err.message}`))
                        return
                    }

                    if (!result || !result.users || !result.me || !result.me.id) {
                        next(errors.create(errors.syncError, `Get users did not return valid users!`))
                        return
                    }

                    result.users[result.me.id] = result.me
                    next(null, result)
                })
            },
            function(serverUsers, next) {
                async.eachSeries(Object.values(serverUsers.users), function(serverUser, eachNext) {
                    const serverUserId = serverUser.id
                    const serverUserName = serverUser.name
                    const serverUserImageUrl = serverUser.imgurl_2x ? serverUser.imgurl_2x : serverUser.imgurl
                    const serverUserEmail = serverUser.email

                    const serverUserLists = serverUser.lists

                    if (!serverUserId || !serverUserName) {
                        eachNext(errors.create(errors.syncError, `Get users returned users with invalid information.`))
                        return
                    }

                    const user = allUsers[serverUserId]

                    const updateSyncedUser = function(user, continuation) {
                        let hasChanges = false

                        if (`${user.first_name} ${user.last_name}` != serverUserName) {
                            
                            const names = serverUserName.split(' ')
                            user.first_name = names[0]
                            user.last_name = names[1]

                            logger.debug(`setting user names: ${user.first_name} ${user.last_name}`)
                            hasChanges = true
                        }

                        if (!serverUserImageUrl && !user.image_guid) {
                            user.image_guid = null
                            hasChanges = true
                        }
                        else if(serverUserImageUrl && !serverUserImageUrl.endsWith(user.image_guid)) {
                            const components = serverUserImageUrl.split('/') 
                            user.image_guid = components[components.length - 1]

                            logger.debug(`changing image guid: ${user.image_guid} from server url: ${serverUserImageUrl}`)
                            hasChanges = true
                        }

                        if (!user.username || serverUserEmail != user.username) {
                            user.username = serverUserEmail
                            hasChanges = true
                            logger.debug(`changing email/username: ${serverUserEmail}`)
                        }

                        if (hasChanges) {
                            const params = {
                                userid : user.userid,
                                properties : user
                            }
                            TCAccountService.updateAccount(params, (err, result) => {
                                if (err) {
                                    continuation(errors.create(errors.syncError, `Unable to update user: ${err.message}`))
                                    return 
                                }

                                delete allUsers[serverUserId]
                                continuation(null, user)
                            })
                        }
                    }

                    const createSyncedUser = function(continuation) {
                        const user = new TCAccount()
                        user.userid = serverUserId

                        const names = serverUserName.split(' ')
                        user.first_name = names[0]
                        user.last_name = names[1]


                        const components = serverUserImageUrl ? serverUserImageUrl.split('/') : null
                        user.image_guid = components ? components[components.length - 1] : null

                        user.username = serverUserEmail
                        let dbPool = null

                        async.waterfall([
                            function(next) {
                                db.getPool(function(err, pool) {
                                    dbPool = pool
                                    dbPool.getConnection(function(err, connection) {
                                        if (err) {
                                            next(errors.create(errors.databaseError, `Error getting a database connection: ${err.message}`))
                                            return
                                        } 

                                        next(null, connection)
                                    })
                                })
                            },
                            function(connection, next) {
                                user.add(connection, function(err, result) {
                                    if (err) {
                                        next(errors.create(errors.databaseError, `Error creating synced user account: ${err.message}`), connection)
                                        return
                                    }

                                    next(null, connection, result)
                                })
                            }
                        ],
                        function(err, connection, newUser) {
                            if (connection) dbPool.releaseConnection(connection)
                            db.cleanup()

                            if (err) {
                                const errObj = JSON.parse(err.message)
                                const message = `Unable to create synced user account: ${userid}`
                                continuation(errors.create(errObj, message))
                                return
                            }

                            continuation(null, newUser)
                        })
                    }

                    const finish = function(err, user) {
                        if (err) {
                            eachNext(err)
                            return
                        }
                        // Fix up list memberships:
                        async.waterfall([
                            function(next) {
                                if (user.userid == serverUsers.me.id) {
                                    // Don't mess with the logged in user membership objects. These
                                    // get taken care of in list sync.
                                    next(null, [])
                                    return
                                }

                                const params = { userid : user.userid }
                                TCListMembershipService.getAllMembershipsForUser(params, (err, memberships) => {
                                    if (err) {
                                        next(errors.create(errors.syncError, `Unable to get user memberships: ${err.message}`))
                                        return
                                    }

                                    next(null, memberships)
                                })
                            },
                            function(memberships, next) {
                                let filteredMemberships = Array.from(memberships)
                                async.eachSeries(serverUserLists, (severSyncId, eachNext) => {
                                    const params = {
                                        syncid : severSyncId
                                    }
                                    TCListService.listIdForSyncId(params, function(err, listid) {
                                        if (err || listid == null) {
                                            logger.debug(`No list id found for membership sync id: ${membership.listid}`)
                                            eachNext(null)
                                            return
                                        }

                                        if (filteredMemberships.find(membership => membership.listid == listid)) {
                                            filteredMemberships = filteredMemberships.filter(membership => membership.listid != listid)
                                            eachNext(null)
                                        }
                                        else {
                                            const params = {
                                                userid : user.userid,
                                                listid : listid
                                            }
                                            TCListMembershipService.createListMembership(params, function(err, membership) {
                                                if (err) {
                                                    eachNext(errors.create(errors.syncError, `Failed to create new list membership: ${err.message}`))
                                                    return
                                                }

                                                eachNext(null)
                                            })
                                        }
                                    })
                                },
                                (err) => {
                                    next(err, filteredMemberships)
                                })
                            },
                            function(memberships, next) {
                                // Delete the memberships that are left in the list after removing the ones
                                // that synced with the server.
                                async.eachSeries(memberships, (membership, eachNext) => {
                                    TCListMembershipService.deleteMembership(membership, (err, result) => {
                                        eachNext(err)
                                    })
                                },
                                function(err) {
                                    if (err) {
                                        const errObj = JSON.parse(err.message)
                                        next(errors.create(errObj,`Error deleting synced user membership for deleted user`))
                                        return
                                    }
                                    next(null)
                                })
                            }
                        ],
                        function(err) {
                            eachNext(err)
                        })
                    }

                    user ? updateSyncedUser(user, finish) : createSyncedUser(finish)
                },
                function(err ){
                    next(err)
                })
            },
            function(next) {
                // Remove the users that are removed from the server.
                async.eachSeries(Object.entries(allUsers), (user, eachNext) => {
                    TCAccountService.deleteSyncUser({ userid : user[0] }, (err, result) => {
                        if (err) {
                            eachNext(errors.create(errors.syncError, `Unable to delete remotely removed synced user: ${err.message}`))
                            return
                        }

                        eachNext(null)
                    })
                },
                function(err) {
                    next(err)
                })
            }
        ],
        function(err) {
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not sync users from server.`))))
                return
            } 
            completion(null, true)
        })
    }

    static syncTasks(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.syncTasks().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const timestamps = params.timestamps ? params.timestamps : null
        const syncSubtasks = params.syncSubtasks ? params.syncSubtasks : false
        const isFirstSync = params.isFirstSync ? params.isFirstSync : false        
        const syncEventSocket = params.syncEventSocket
        const taskCounts = params.taskCounts
        if (taskCounts) {
            logger.debug(`Task Counts inside syncTasks(): ${JSON.stringify(taskCounts)}`)
        }

        let syncUpAgain = false

        const addTasks = []
        const modifyTasks = []
        const deleteTasks = []
        
        let addTaskString = null
        let modTaskString = null
        let delTaskString = null

        let allDirtyTasks = null

        let response = null
        
        const tasksToPermanentlyDelete = []

        async.waterfall([
            function(callback) {
                if (!isFirstSync) {
                    const getTaskParams = {
                        syncSubtasks: syncSubtasks,
                        userid: userid
                    }
logger.debug(`st: 0`)
                    TCTaskService.getAllDirtyTasks(getTaskParams, function(err, dirtyTasks) {
                        if (err) {
logger.debug(`st: 1`)
                            callback(err)
                        } else {
logger.debug(`st: 2`)
                            allDirtyTasks = dirtyTasks;
                            if (syncSubtasks) {
                                logger.debug(`Todo Cloud found ${allDirtyTasks.length} subtasks needing sync.`)
                            } else {
                                logger.debug(`Todo Cloud found ${allDirtyTasks.length} tasks needing sync.`)
                            }
                            callback(null)
                        }
                    })
                } else {
logger.debug(`st: 3`)
                    callback(null)
                }
            },
            function(callback) {
logger.debug(`st: 4`)
                if (allDirtyTasks) {
logger.debug(`st: 5`)
                    async.eachSeries(allDirtyTasks,
                        function(curTask, eachCallback) {
                            if (curTask.deleted) {
logger.debug(`st: 6`)
                                // The task is marked as deleted
                                if (curTask.sync_id && curTask.sync_id.length > 0) {
logger.debug(`st: 7`)
                                    // We need to delete this list from the server
                                    const jsonParams = {
                                        task: curTask,
                                        isDelete: true,
                                        isAdd: false,
                                        userid: userid
                                    }
                                    TCSyncService.jsonForTask(jsonParams, function(jsonErr, taskJSON) {
                                        if (jsonErr) {
                                            eachCallback(jsonErr)
                                        } else {
                                            deleteTasks.push(taskJSON)
                                            eachCallback(null)
                                        }
                                    })
                                } else {
logger.debug(`st: 8`)
                                    // Delete the list locally
                                    tasksToPermanentlyDelete.push(curTask)
                                    eachCallback(null)
                                }
                            } else {
logger.debug(`st: 9`)
                                // The task is NOT marked as deleted (active)
                                if (curTask.sync_id && curTask.sync_id.length > 0) {
logger.debug(`st: 10`)
                                    // Update the list on the server
                                    const jsonParams = {
                                        task: curTask,
                                        isDelete: false,
                                        isAdd: false,
                                        userid: userid
                                    }
                                    TCSyncService.jsonForTask(jsonParams, function(jsonErr, taskJSON) {
                                        if (jsonErr) {
logger.debug(`st: 10.1`)
                                            eachCallback(jsonErr)
                                        } else {
logger.debug(`st: 10.2`)
                                            modifyTasks.push(taskJSON)
                                            eachCallback(null)
                                        }
                                    })
                                } else {
logger.debug(`st: 11`)
                                    // Create the task on the server
                                    const jsonParams = {
                                        task: curTask,
                                        isDelete: false,
                                        isAdd: true,
                                        userid: userid
                                    }
                                    TCSyncService.jsonForTask(jsonParams, function(jsonErr, taskJSON) {
                                        if (jsonErr) {
logger.debug(`st: 11.1`)
                                            eachCallback(jsonErr)
                                        } else {
logger.debug(`st: 12`)
                                            addTasks.push(taskJSON)
                                            eachCallback(null)
                                        }
                                    })
                                }
                            }
                        },
                        function(err) {
logger.debug(`st: 13`)
                            callback(err)
                        }
                    )
                } else {
logger.debug(`st: 14`)
                    logger.debug(`Todo Cloud detected a first-time sync. Requesting ALL tasks from server...`)
                    callback(null)
                }
            },
            function(callback) {
                // Delete tasks that may have been marked for local deletion
                if (tasksToPermanentlyDelete.length > 0) {
logger.debug(`st: 15`)
                    async.eachSeries(tasksToPermanentlyDelete,
                        function(curTask, eachCallback) {
                            const deleteParams = {
                                taskid: curTask.taskid,
                                tableName: constants.TasksTable.Deleted // I'm assuming this is where the task will be?
                            }
logger.debug(`st: 16`)
                            TCTaskService.permanentlyDeleteTask(deleteParams, function(err, result) {
logger.debug(`st: 17`)
                                eachCallback(err)
                            })
                        },
                        function(err) {
logger.debug(`st: 18`)
                            callback(err)
                        }
                    )
                } else {
logger.debug(`st: 19`)
                    callback(null)
                }
            },
            function(callback) {
logger.debug(`st: 20`)
                // Full "paged" sync inside the do-while loop so that no one request
                // to the server pushes or pulls too many tasks at once. This will help
                // us have higher sync success rates by making us less dependant on
                // longer network requests and should also help the server keep its
                // processing time lower because the separate requests can be load-
                // balanced. Customers with a large task data set should be able to
                // sync any number of tasks to/from the service.

                const bulkSyncPageSize = constants.SyncServiceBulkSyncPageTaskCount

                // The call to sync tasks is done on a list by list basis and so a
                // global page offset won't work. Instead, we track the page offset
                // separately for each list during the doWhilst sync loop. The client
                // maintains an object of list offsets which are provided by the server.
                // In subsequent calls to the server, the server uses this to know where
                // to pick up from based on the previous request.
                const listOffsets = {}

                // In each loop we keep track of how many tasks we've received from the
                // server. If the number is ever 0, we'll know that we're done with tasks
                // on the server and can consider the sync done for that list.
                let taskActionsReceivedFromServer = 0

                async.doWhilst(function(doWhilstCallback) {
logger.debug(`st: 21`)
                    let tasksToPushCount = 0 // reset to 0 each time we loop

                    if (addTasks.length > 0 && tasksToPushCount < bulkSyncPageSize) {
logger.debug(`st: 22`)
                        // Respect the page size allowed to push to the server
                        const pushSlotsAvailable = bulkSyncPageSize - tasksToPushCount
                        const numOfTasksToPush = Math.min(pushSlotsAvailable, addTasks.length)

                        // Remove the paged tasks from the main array of tasks so they
                        // won't be sent again. The removed items are returned into
                        // pagedTasks.
                        const pagedTasks = addTasks.splice(0, numOfTasksToPush)
                        tasksToPushCount = tasksToPushCount + pagedTasks.length

                        addTaskString = JSON.stringify(pagedTasks)
                    } else {
logger.debug(`st: 23`)
                        addTaskString = null
                    }
                    
                    if (modifyTasks.length > 0 && tasksToPushCount < bulkSyncPageSize) {
logger.debug(`st: 24: ${JSON.stringify(modifyTasks)}`)
                        // Respect the page size allowed to push to the server
                        const pushSlotsAvailable = bulkSyncPageSize - tasksToPushCount
                        const numOfTasksToPush = Math.min(pushSlotsAvailable, modifyTasks.length)

                        // Remove the paged tasks from the main array of tasks so they
                        // won't be sent again. The removed items are returned into
                        // pagedTasks.
                        const pagedTasks = modifyTasks.splice(0, numOfTasksToPush)
                        tasksToPushCount = tasksToPushCount + pagedTasks.length

logger.debug(`st: 25: ${JSON.stringify(pagedTasks)}`)
                        modTaskString = JSON.stringify(pagedTasks)
logger.debug(`st: 26: ${modTaskString}`)
                    } else {
logger.debug(`st: 27: ${modTaskString}`)
                        modTaskString = null
                    }
                    
                    if (deleteTasks.length > 0 && tasksToPushCount < bulkSyncPageSize) {
logger.debug(`st: 28`)
                        // Respect the page size allowed to push to the server
                        const pushSlotsAvailable = bulkSyncPageSize - tasksToPushCount
                        const numOfTasksToPush = Math.min(pushSlotsAvailable, deleteTasks.length)

                        // Remove the paged tasks from the main array of tasks so they
                        // won't be sent again. The removed items are returned into
                        // pagedTasks.
                        const pagedTasks = deleteTasks.splice(0, numOfTasksToPush)
                        tasksToPushCount = tasksToPushCount + pagedTasks.length

                        delTaskString = JSON.stringify(pagedTasks)
                    } else {
logger.debug(`st: 29`)
                        delTaskString = null
                    }

                    const syncParams = {
                        method: 'syncTasks',
                        numOfTasks: bulkSyncPageSize
                    }
                    if (addTaskString) {
logger.debug(`st: 30: ${addTaskString}`)
                        syncParams['addTasks'] = addTaskString
                    }
                    if (modTaskString) {
logger.debug(`st: 31: ${modTaskString}`)
                        syncParams['updateTasks'] = modTaskString
                    }
                    if (delTaskString) {
logger.debug(`st: 32: ${delTaskString}`)
                        syncParams['deleteTasks'] = delTaskString
                    }
                    if (timestamps) {
logger.debug(`st: 33`)
                        Object.keys(timestamps).forEach((serverListId) => {
logger.debug(`st: 34: ${serverListId}`)
                            syncParams[serverListId] = timestamps[serverListId]
                        })
                    }
                    if (Object.keys(listOffsets).length > 0) {
logger.debug(`st: 35`)
                        Object.keys(listOffsets).forEach((listOffsetKey) => {
                            const offset = listOffsets[listOffsetKey]
                            const offsetString = parseInt(offset)
logger.debug(`st: 36: ${listOffsetKey} = ${offsetString}`)
                            syncParams[listOffsetKey] = offsetString
                        })
                    }
logger.debug(`Calling 'syncTasks' ${syncSubtasks ? '(subtasks) ' : ''}with:\n${JSON.stringify(syncParams)}`)

                    TCSyncService.makeSyncRequest(syncParams, function(err, syncResponse) {
                        if (err) {
                            doWhilstCallback(err)
                        } else {
                            response = syncResponse
                            // There's a LOT to do. Time for another async.waterfall
                            const addedResults = response.results && Object.keys(response.results).length > 0 ? response.results.added : null
                            const updatedResults = response.results && Object.keys(response.results).length > 0 ? response.results.updated : null
                            const deletedResults = response.results && Object.keys(response.results).length > 0 ? response.results.deleted : null
                            const offsetResults = response.listOffsets && Object.keys(response.listOffsets).length > 0 ? response.listOffsets : null

                            async.waterfall([
                                function(waterfallCallback) {
                                    if (addedResults) {
                                        const addedParams = {
                                            userid: userid,
                                            addedTasks: addedResults
                                        }
                                        TCSyncService.processAddedTasks(addedParams, function(addedErr, addedResult) {
                                            if (addedErr) {
                                                waterfallCallback(addedErr)
                                            } else {
                                                if (addedResult.syncUpAgain != undefined) {
                                                    syncUpAgain = addedResult.syncUpAgain
                                                }
                                                waterfallCallback(null)
                                            }
                                        })
                                    } else {
                                        // Skip added tasks
                                        waterfallCallback(null)
                                    }
                                },
                                function(waterfallCallback) {
                                    if (updatedResults) {
                                        const updatedParams = {
                                            userid: userid,
                                            updatedTasks: updatedResults
                                        }
                                        TCSyncService.processUpdatedTasks(updatedParams, function(updatedErr, updatedResult) {
                                            if (updatedErr) {
                                                waterfallCallback(updatedErr)
                                            } else {
                                                if (updatedResult.syncUpAgain != undefined) {
                                                    syncUpAgain = updatedResult.syncUpAgain
                                                }
                                                waterfallCallback(null)
                                            }
                                        })
                                    } else {
                                        // Skip updated tasks
                                        waterfallCallback(null)
                                    }
                                },
                                function(waterfallCallback) {
                                    if (deletedResults) {
                                        const deletedParams = {
                                            userid: userid,
                                            deletedTasks: deletedResults
                                        }
                                        TCSyncService.processDeletedTasks(deletedParams, function(deletedErr, deletedResult) {
                                            if (deletedErr) {
                                                waterfallCallback(deletedErr)
                                            } else {
                                                waterfallCallback(null)
                                            }
                                        })
                                    } else {
                                        // Skip deleted tasks
                                        waterfallCallback(null)
                                    }
                                },
                                function(waterfallCallback) {
                                    // Reset this now that we're in the loop
                                    taskActionsReceivedFromServer = 0
                                    if (response.actions && Object.keys(response.actions).length > 0) {
                                        const actionParams = {
                                            userid: userid,
                                            taskActions: response.actions,
                                            syncEventSocket: syncEventSocket,
                                            taskCounts: taskCounts
                                        }
                                        TCSyncService.processTaskActions(actionParams, function(actionErr, result) {
                                            if (actionErr) {
                                                waterfallCallback(actionErr)
                                            } else {
                                                taskActionsReceivedFromServer = result.taskActionsReceivedFromServer
                                                waterfallCallback(null)
                                            }
                                        })
                                    } else {
                                        logger.debug(`No additional task changes found on the Todo Cloud server.`)
                                        waterfallCallback(null)
                                    }
                                },
                                function(waterfallCallback) {
                                    // Update the task offsets with the values set by the server so
                                    // the next time in the doWhilst loop we'll ask for the right set
                                    // of tasks.
                                    if (offsetResults) {
                                        Object.keys(offsetResults).forEach((key) => {
                                            listOffsets[key] = offsetResults[key]
                                        })
                                    }

                                    waterfallCallback(null)
                                }
                            ],
                            function(err) {
                                if (err) {
                                    doWhilstCallback(err)
                                } else {
                                    async.nextTick(doWhilstCallback)
                                }
                            })
                        }
                    })
                },
                function() {
                    // Test function to know when to bail out of the doWhilst
                    return (addTasks.length > 0 || modifyTasks.length > 0 || deleteTasks.length > 0 || taskActionsReceivedFromServer > 0)
                },
                function(err) {
                    // Called after the doWhilst test function returns false
                    // or if there is an error in the main work function.                  
                    callback(err)
                })
            },
            function(callback) {
                if (!response) {
                    callback(null)
                } else {
                    const settingParams = {}
                    const taskTimeStamps = response.alltasktimestamps != undefined && Object.keys(response.alltasktimestamps).length > 0 ? response.alltasktimestamps : null
                    _allTaskTimeStamps = taskTimeStamps ? taskTimeStamps : {}
                    settingParams[constants.SettingAllTaskTimeStampsKey] = _allTaskTimeStamps

                    const listMembershipHashes = response.listMembershipHashes != undefined && Object.keys(response.listMembershipHashes).length > 0 ? response.listMembershipHashes : null
                    _listMembershipHashes = listMembershipHashes ? listMembershipHashes : {}
                    settingParams[constants.SettingListMembershipHashes] = _listMembershipHashes

                    const taskitoTimeStamps = response.alltaskitotimestamps != undefined && Object.keys(response.alltaskitotimestamps).length > 0 ? response.alltaskitotimestamps : null
                    if (taskitoTimeStamps) {
                        // Following the convetion we used in iOS, we only update this if we get some back
                        _allTaskitoTimeStamps = taskitoTimeStamps ? taskitoTimeStamps : {}
                    }

                    const notificationTimeStamps = response.allnotificationtimestamps != undefined && Object.keys(response.allnotificationtimestamps).length > 0 ? response.allnotificationtimestamps : null
                    if (notificationTimeStamps) {
                        _allNotificationTimeStamps = notificationTimeStamps ? notificationTimeStamps : {}
                    }

                    db.setSettings(settingParams, function(err, settingResult) {
                        callback(err)
                    })
                }
            },
        ],
        function(err) {
            if (err) {
                completion(err)
            } else {
                if (isFirstSync) {
                    // Now that we've synced the first time, we need to sync tasks again
                    // to push any potential changes back up to the server that happened
                    // during the original sync.
                    const newSyncParams = {
                        timestamps: _allTaskTimeStamps,
                        syncSubtasks: syncSubtasks,
                        isFirstSync: false,
                        userid: userid
                    }
                    TCSyncService.syncTasks(newSyncParams, function(newSyncErr, newSyncResult) {
                        if (newSyncErr) {
                            completion(newSyncErr)
                        } else {
                            completion(null, true)
                        }
                    })
                } else if (syncUpAgain) {
                    const newSyncParams = {
                        timestamps: _allTaskTimeStamps,
                        syncSubtasks: false,
                        isFirstSync: false,
                        userid: userid
                    }
                    TCSyncService.syncTasks(newSyncParams, function(newSyncErr, newSyncResult) {
                        if (newSyncErr) {
                            completion(newSyncErr)
                        } else {
                            completion(null, true)
                        }
                    })
                } else {
                    completion(null, true)
                }
            }
        })
    }

    static processAddedTasks(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processAddedTasks().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const addedTasks = params.addedTasks ? params.addedTasks : null
        if (!addedTasks) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing addedTasks.`))))
            return
        }

        let syncUpAgain = false

        async.eachSeries(addedTasks,
            function(addedTask, eachCallback) {
                const errorCode = addedTask.errorcode
                if (errorCode) {
                    let updatedTask = null
                    let taskName = null
                    let taskId = addedTask.tmptaskid
                    let showSyncError = true
                    async.waterfall([
                        function(waterfallCallback) {
                            if (taskId) {
                                const getTaskParams = {
                                    userid: userid,
                                    taskid: taskId,
                                    preauthorized: true
                                }
                                TCTaskService.getTask(getTaskParams, function(getTaskErr, aTask) {
                                    if (getTaskErr) {
                                        waterfallCallback(getTaskErr)
                                    } else {
                                        updatedTask = aTask
                                        if (updatedTask) {
                                            taskName = updatedTask.name
                                        }
                                        waterfallCallback(null)
                                    }
                                })
                            } else {
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            // Bug 7401 (from the old Bugzilla DB) - If we try to sync a subtask and
                            // its parent is no longer valid on the server, switch it to a normal
                            // task and sync it up again.
                            if (updatedTask && updatedTask.isSubtask() && (errorCode == constants.SyncErrorCodeParentTaskNotProject || errorCode == constants.SyncErrorCodeParentTaskNotFound)) {
                                updatedTask.parentid = null
                                if (updatedTask.recurrence_type != undefined) {
                                    if (updatedTask.recurrence_type == constants.TaskRecurrenceType.WithParent || updatedTask.recurrence_type == constants.TaskRecurrenceType.WithParent + 100) {
                                        updatedTask.recurrence_type = constants.TaskRecurrenceType.None
                                    }
                                }
                                updatedTask.userid = userid
                                updatedTask.dirty = true
                                updatedTask.syncService = true
                                updatedTask.isSyncService = true
                                TCTaskService.updateTask(updatedTask, function(updateErr, aTask) {
                                    if (updateErr) {
                                        waterfallCallback(updateErr)
                                    } else {
                                        showSyncError = false
                                        syncUpAgain = true
                                        waterfallCallback(null)
                                    }
                                })
                            } else {
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            if (showSyncError) {
                                if (taskName) {
                                    logger.debug(`Adding task ${taskName} failed during sync with error code: ${errorCode}`)
                                    waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Adding task ${taskName} failed during sync with error code: ${errorCode}`))))
                                } else {
                                    logger.debug(`Adding a task failed during sync with error code: ${errorCode}`)
                                    waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Adding a task failed during sync with error code: ${errorCode}`))))
                                }
                            } else {
                                waterfallCallback(null)
                            }
                        }
                    ],
                    function(waterfallErr) {
                        eachCallback(waterfallErr)
                    })
                } else {
                    const syncid = addedTask.taskid
                    const tmpTaskId = addedTask.tmptaskid
                    if (tmpTaskId) {
                        const getTaskParams = {
                            userid: userid,
                            taskid: tmpTaskId,
                            preauthorized: true
                        }
                        TCTaskService.getTask(getTaskParams, function(getTaskErr, aTask) {
                            if (getTaskErr) {
                                eachCallback(getTaskErr)
                            } else {
                                aTask.sync_id = syncid
                                aTask.userid = userid
                                aTask.dirty = false
                                aTask.isSyncService = true
                                TCTaskService.updateTask(aTask, function(updateErr, updateResult) {
                                    eachCallback(updateErr)
                                })
                            }
                        })
                    } else {
                        eachCallback(null)
                    }
                }
            },
            function(err) {
                if (err) {
                    completion(err)
                } else {
                    const result = {}
                    if (syncUpAgain) {
                        result["syncUpAgain"] = true
                    }
                    completion(null, result)
                }
            }
        )
    }

    static processUpdatedTasks(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processUpdatedTasks().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const updatedTasks = params.updatedTasks ? params.updatedTasks : null
        if (!updatedTasks) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing updatedTasks.`))))
            return
        }

        let syncUpAgain = false

        async.eachSeries(updatedTasks,
            function(aTask, eachCallback) {
                const errorCode = aTask.errorcode
                if (errorCode) {
                    let updatedTask = null
                    let taskName = null
                    let serverId = aTask.taskid
                    let showSyncError = true
                    async.waterfall([
                        function(waterfallCallback) {
                            if (serverId) {
                                const getTaskParams = {
                                    userid: userid,
                                    syncid: serverId
                                }
                                TCTaskService.getTaskForSyncId(getTaskParams, function(getTaskErr, localTask) {
                                    if (getTaskErr) {
                                        waterfallCallback(getTaskErr)
                                    } else {
                                        updatedTask = localTask
                                        if (updatedTask) {
                                            taskName = updatedTask.name
                                        }
                                        waterfallCallback(null)
                                    }
                                })
                            } else {
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            // Bug 7401 (from the old Bugzilla DB) - If we try to sync a subtask and
                            // its parent is no longer valid on the server, switch it to a normal
                            // task and sync it up again.
                            if (updatedTask && updatedTask.isSubtask() && (errorCode == constants.SyncErrorCodeParentTaskNotProject || errorCode == constants.SyncErrorCodeParentTaskNotFound)) {
                                updatedTask.parentid = null
                                if (updatedTask.recurrence_type != undefined) {
                                    if (updatedTask.recurrence_type == constants.TaskRecurrenceType.WithParent || updatedTask.recurrence_type == constants.TaskRecurrenceType.WithParent + 100) {
                                        updatedTask.recurrence_type = constants.TaskRecurrenceType.None
                                    }
                                }
                                updatedTask.userid = userid
                                updatedTask.dirty = true
                                updatedTask.isSyncService = true
                                TCTaskService.updateTask(updatedTask, function(updateErr, aTask) {
                                    if (updateErr) {
                                        waterfallCallback(updateErr)
                                    } else {
                                        showSyncError = false
                                        syncUpAgain = true
                                        waterfallCallback(null)
                                    }
                                })
                            } else {
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            if (showSyncError) {
                                if (taskName) {
                                    logger.debug(`Updating task ${taskName} failed during sync with error code: ${errorCode}`)
                                    waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Updating task ${taskName} failed during sync with error code: ${errorCode}`))))
                                } else {
                                    logger.debug(`Updating a task failed during sync with error code: ${errorCode}`)
                                    waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Updating a task failed during sync with error code: ${errorCode}`))))
                                }
                            } else {
                                waterfallCallback(null)
                            }
                        }
                    ],
                    function(waterfallErr) {
                        eachCallback(waterfallErr)
                    })
                } else {
                    const syncId = aTask.taskid
                    if (syncId) {
                        const getTaskParams = {
                            userid: userid,
                            syncid: syncId
                        }
                        TCTaskService.getTaskForSyncId(getTaskParams, function(getTaskErr, localTask) {
                            if (getTaskErr) {
                                eachCallback(getTaskErr)
                            } else {
                                if (localTask) {
                                    // If the task switched sync ids, change out the sync id
                                    const newSyncId = aTask['new-taskid']
                                    if (newSyncId) {
                                        localTask.sync_id = newSyncId
                                    }
                                    localTask.userid = userid
                                    localTask.dirty = false
                                    localTask.isSyncService = true
                                    TCTaskService.updateTask(localTask, function(updateErr, updateResult) {
                                        eachCallback(updateErr)
                                    })
                                } else {
                                    // Not sure how this would ever happen
                                    eachCallback(null)
                                }
                            }
                        })
                    } else {
                        eachCallback(null)
                    }
                }
            },
            function(err) {
                if (err) {
                    completion(err)
                } else {
                    const result = {}
                    if (syncUpAgain) {
                        result["syncUpAgain"] = true
                    }
                    completion(null, result)
                }
            }
        )
    }

    static processDeletedTasks(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processDeletedTasks().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const deletedTasks = params.deletedTasks ? params.deletedTasks : null
        if (!deletedTasks) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing deletedTasks.`))))
            return
        }

        async.eachSeries(deletedTasks,
            function(aTask, eachCallback) {
                const errorCode = aTask.errorcode
                if (errorCode) {
                    let taskName = null
                    let serverId = aTask.taskid
                    async.waterfall([
                        function(waterfallCallback) {
                            if (serverId) {
                                const getTaskParams = {
                                    userid: userid,
                                    syncid: serverId
                                }
                                TCTaskService.getTaskForSyncId(getTaskParams, function(getTaskErr, localTask) {
                                    if (getTaskErr) {
                                        waterfallCallback(getTaskErr)
                                    } else {
                                        if (localTask) {
                                            taskName = localTask.name
                                        }
                                        waterfallCallback(null)
                                    }
                                })
                            } else {
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            if (taskName) {
                                logger.debug(`Deleting task ${taskName} failed during sync with error code: ${errorCode}`)
                                waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Deleting task ${taskName} failed during sync with error code: ${errorCode}`))))
                            } else {
                                logger.debug(`Deleting a task failed during sync with error code: ${errorCode}`)
                                waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Deleting a task failed during sync with error code: ${errorCode}`))))
                            }
                        }
                    ],
                    function(waterfallErr) {
                        eachCallback(waterfallErr)
                    })
                } else {
                    const syncid = aTask.taskid
                    if (syncid) {
                        const getTaskParams = {
                            userid: userid,
                            syncid: syncid
                        }
                        TCTaskService.getTaskForSyncId(getTaskParams, function(getTaskErr, localTask) {
                            if (getTaskErr) {
                                eachCallback(getTaskErr)
                            } else {
                                if (localTask) {
                                    const updateParams = {
                                        userid: userid,
                                        listid: localTask.listid,
                                        taskid: localTask.taskid,
                                        deleted: true,
                                        dirty: false,
                                        isSyncService: true
                                    }
                                    TCTaskService.updateTask(updateParams, function(updateErr, result) {
                                        eachCallback(updateErr)
                                    })
                                } else {
                                    eachCallback(null)
                                }
                            }
                        })
                    } else {
                        eachCallback(null)
                    }
                }
            },
            function(err) {
                if (err) {
                    completion(err)
                } else {
                    completion(null, true)
                }
            }
        )
    }

    static processTaskActions(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processTaskActions().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const actions = params.taskActions ? params.taskActions : null
        if (!actions) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing taskActions.`))))
            return
        }

        const syncEventSocket = params.syncEventSocket
        const taskCounts = params.taskCounts
        if (taskCounts) {
            logger.debug(`Task counts inside processTaskActions: ${JSON.stringify(taskCounts)}`)
        }

        let taskActionsReceived = 0
        let syncHadServerChanges = false
        let syncUpAgain = false

        const updateActions = actions.update != undefined && actions.update.length > 0 ? actions.update : null
        const deleteActions = actions.delete != undefined && actions.delete.length > 0 ? actions.delete : null

        async.waterfall([
            function(callback) {
                if (updateActions) {
                    taskActionsReceived = taskActionsReceived + updateActions.length
                    syncHadServerChanges = true
                    logger.debug(`Processing ${updateActions.length} task changes that were added/modified on Todo Cloud...`)
                    async.eachSeries(updateActions,
                        function(updatedTask, eachCallback) {
logger.debug(`processTaskActions() 0`)
                            let oldParent = null
                            const syncid = updatedTask.taskid
                            if (syncid) {
                                async.waterfall([
                                    function(waterfallCallback) {
                                        const getTaskParams = {
                                            userid: userid,
                                            syncid: syncid
                                        }
logger.debug(`processTaskActions() 1`, updatedTask.name, getTaskParams)
                                        TCTaskService.getTaskForSyncId(getTaskParams, function(err, taskToUpdate) {
                                            if (err) {
logger.debug(`processTaskActions() 2`)
                                                waterfallCallback(err)
                                            } else {
logger.debug(`processTaskActions() 3`)
                                                waterfallCallback(null, taskToUpdate)
                                            }
                                        })
                                    },
                                    function(taskToUpdate, waterfallCallback) {
logger.debug(`processTaskActions() 4`)
                                        if (taskToUpdate) {
                                            if (taskToUpdate.dirty == undefined || !taskToUpdate.dirty) {
logger.debug(`processTaskActions() 5`)
                                                async.waterfall([
                                                    function(innerWaterfallCallback) {
logger.debug(`processTaskActions() 6`)
                                                        // Bug 7430 (Bugzilla) - When a task is moved out of its parent,
                                                        // we need to update the old parent
                                                        if (taskToUpdate.parentid != undefined && taskToUpdate.parentid.length > 0) {
                                                            const newParentId = updatedTask.parentid
                                                            if (newParentId == undefined || newParentId.length == 0 || newParentId != taskToUpdate.parentid) {
                                                                const oldParentParams = {
                                                                    userid: userid,
                                                                    taskid: taskToUpdate.parentid,
                                                                    preauthorized: true
                                                                }
                                                                TCTaskService.getTask(oldParentParams, function(getTaskErr, parentTask) {
logger.debug(`processTaskActions() 7`)
                                                                    if (getTaskErr) {
logger.debug(`processTaskActions() 8`)
                                                                        innerWaterfallCallback(getTaskErr)
                                                                    } else {
logger.debug(`processTaskActions() 9`)
                                                                        oldParent = parentTask
                                                                        innerWaterfallCallback(null)
                                                                    }
                                                                })
                                                            } else {
logger.debug(`processTaskActions() 10`)
                                                                innerWaterfallCallback(null)
                                                            }
                                                        } else {
logger.debug(`processTaskActions() 11`)
                                                            innerWaterfallCallback(null)
                                                        }
                                                    },
                                                    function(innerWaterfallCallback) {
logger.debug(`processTaskActions() 12`)
                                                        // Bug 7401 (Bugzilla) - If the server sends us a subtask and its parent
                                                        // is no longer valid locally, switch it to a normal task and sync that
                                                        // change up
                                                        const updateParams = {
                                                            userid: userid,
                                                            task: taskToUpdate,
                                                            values: updatedTask
                                                        }
                                                        TCSyncService.updateTaskWithValues(updateParams, function(updateErr, updateResult) {
                                                            if (updateErr) {
logger.debug(`processTaskActions() 13`)
                                                                innerWaterfallCallback(updateErr)
                                                            } else {
logger.debug(`processTaskActions() 14`)
                                                                taskToUpdate.userid = userid
                                                                if (updateResult.changesMade) {
logger.debug(`processTaskActions() 15`)
                                                                    taskToUpdate.dirty = true
                                                                    syncUpAgain = true
                                                                } else {
logger.debug(`processTaskActions() 16`)
                                                                    taskToUpdate.dirty = false
                                                                }
                                                                taskToUpdate.isSyncService = true
                                                                TCTaskService.updateTask(taskToUpdate, function(updateErr, updateResult) {
logger.debug(`processTaskActions() 17`)
                                                                    innerWaterfallCallback(updateErr)
                                                                })
                                                            }
                                                        })
                                                    }
                                                ], function(innerWaterfallErr) {
logger.debug(`processTaskActions() 18`)
                                                    waterfallCallback(innerWaterfallErr)
                                                })
                                            } else {
                                                logger.debug(`An update from the server was ignored because it was modified locally on task: ${taskToUpdate.name}`)
                                                waterfallCallback(null)
                                            }
                                        } else {
logger.debug(`processTaskActions() 19`)
                                            // Search for an existing task
                                            async.waterfall([
                                                function(innerWaterfallCallback) {
logger.debug(`processTaskActions() 20`)
                                                    const searchTask = new TCTask()
                                                    const updateParams = {
                                                        userid: userid,
                                                        task: searchTask,
                                                        values: updatedTask
                                                    }
                                                    TCSyncService.updateTaskWithValues(updateParams, function(updateErr, updateResults) {
                                                        if (updateErr) {
logger.debug(`processTaskActions() 21`)
                                                            innerWaterfallCallback(updateErr)
                                                        } else {
logger.debug(`processTaskActions() 22`)
                                                            const getTaskParams = {
                                                                userid: userid,
                                                                task: searchTask
                                                            }
                                                            TCTaskService.getUnsyncedTaskMatchingProperties(getTaskParams, function(getErr, existingTask) {
                                                                if (getErr) {
logger.debug(`processTaskActions() 23`)
                                                                    innerWaterfallCallback(getErr)
                                                                } else {
logger.debug(`processTaskActions() 24`)
                                                                    innerWaterfallCallback(null, existingTask)
                                                                }
                                                            })
                                                        }
                                                    })
                                                },
                                                function(existingTask, innerWaterfallCallback) {
logger.debug(`processTaskActions() 25`)
                                                    if (existingTask) {
                                                        logger.debug(`Found a local non-synced task that matches a server task (${existingTask.name})`)

                                                        // Bug 7401 (Bugzilla) - If the server sends us a subtask and its parent is no longer
                                                        // valid locally, switch it to a normal task and sync that change up.
                                                        const updateParams = {
                                                            userid: userid,
                                                            task: existingTask,
                                                            values: updatedTask
                                                        }
                                                        TCSyncService.updateTaskWithValues(updateParams, function(updateErr, updateResults) {
                                                            if (updateErr) {
logger.debug(`processTaskActions() 26`)
                                                                innerWaterfallCallback(updateErr)
                                                            } else {
logger.debug(`processTaskActions() 27`)
                                                                let dirty = updateResults.changesMade != undefined && updateResults.changesMade
                                                                syncUpAgain = dirty
                                                                existingTask.dirty = dirty
                                                                existingTask.userid = userid
                                                                existingTask.isSyncService = true
                                                                TCTaskService.updateTask(existingTask, function(updateTaskErr, updateTaskResults) {
                                                                    if (updateTaskErr) {
logger.debug(`processTaskActions() 28`)
                                                                        innerWaterfallCallback(updateTaskErr)
                                                                    } else {
logger.debug(`processTaskActions() 29`)
                                                                        innerWaterfallCallback(null)
                                                                    }
                                                                })
                                                            }
                                                        })
                                                    } else {
logger.debug(`processTaskActions() 30 - ${JSON.stringify(updatedTask)}`)
                                                        const taskToAdd = new TCTask()
                                                        const updateParams = {
                                                            userid: userid,
                                                            task: taskToAdd,
                                                            values: updatedTask
                                                        }
                                                        TCSyncService.updateTaskWithValues(updateParams, function(updateErr, updateResults) {
                                                            if (updateErr) {
logger.debug(`processTaskActions() 31`)
                                                                innerWaterfallCallback(updateErr)
                                                            } else {
                                                                let dirty = updateResults.changesMade != undefined && updateResults.changesMade
                                                                syncUpAgain = dirty
                                                                taskToAdd.dirty = dirty
                                                                taskToAdd.userid = userid
                                                                taskToAdd.isSyncService = true
logger.debug(`processTaskActions() 32: ${JSON.stringify(taskToAdd)}`)
                                                                TCTaskService.addTask(taskToAdd, function(addErr, addResult) {
                                                                    if (addErr) {
logger.debug(`processTaskActions() 33`)
                                                                        innerWaterfallCallback(addErr)
                                                                        return
                                                                    }
logger.debug(`processTaskActions() 34 ${JSON.stringify(addResult)}`)

                                                                    TCSyncService.assignTags({
                                                                        task : addResult,
                                                                        userid : userid,
                                                                        tagString : updatedTask.tags
                                                                    }, innerWaterfallCallback)
                                                                    
                                                                })
                                                            }
                                                        })
                                                    }
                                                }
                                            ],
                                            function(innerWaterfallErr) {
logger.debug(`processTaskActions() 34.1`)
                                                waterfallCallback(innerWaterfallErr)
                                            })
                                        }
                                    }
                                ],
                                function(waterfallErr) {
logger.debug(`processTaskActions() 35`)
                                    if (waterfallErr) {
logger.debug(`processTaskActions() 36`)
                                        eachCallback(waterfallErr)
                                    } else {
logger.debug(`processTaskActions() 37`)
                                        if (oldParent) {
logger.debug(`processTaskActions() 38`)
                                            // Bug 7430 (Bugzilla) - When a task is moved out of its parent,
                                            // we need to update the old parent
                                            oldParent.userid = userid
                                            oldParent.dirty = false
                                            oldParent.isSyncService = true
                                            TCTaskService.updateTask(oldParent, function(updateErr, result) {
logger.debug(`processTaskActions() 39`)
                                                eachCallback(updateErr)
                                            })
                                        } else {
logger.debug(`processTaskActions() 40`)
                                            eachCallback(null)
                                        }
                                    }
                                })
                            } else {
logger.debug(`processTaskActions() 41`)
                                eachCallback(null)
                            }

                        },
                        function(eachErr) {
                            if (eachErr) {
logger.debug(`processTaskActions() 42.1`)
                                callback(eachErr)
                            } else {
logger.debug(`processTaskActions() 42.2`)
                                if (syncEventSocket) {
logger.debug(`processTaskActions() 42.3`)
                                    let totalProcessedTasks = taskCounts.processedTasks ? taskCounts.processedTasks : 0
                                    taskCounts.processedTasks = totalProcessedTasks + updateActions.length
                                    syncEventSocket.emit('sync-event', {
                                        message: `Synchronized ${updateActions.length} tasks.`,
                                        taskCounts: taskCounts
                                    })
                                }
                                callback(null)
                            }
                        })
                } else {
logger.debug(`processTaskActions() 43`)
                    callback(null)
                }
            },
            function(callback) {
logger.debug(`processTaskActions() 44`)
                if (!deleteActions) {
logger.debug(`processTaskActions() 45`)
                    callback(null)
                    return
                }
                taskActionsReceived = taskActionsReceived + deleteActions.length

                logger.debug(`Deleting ${deleteActions.length} tasks that were deleted from Todo Cloud.`)
                async.eachSeries(deleteActions,
                    function(deletedTask, eachCallback) {
logger.debug(`processTaskActions() 46`)
                        syncHadServerChanges = true

                        const serverTaskId = deletedTask.taskid
                        if (!serverTaskId) {
logger.debug(`processTaskActions() 47`)
                            eachCallback(null)
                            return
                        }
                        const getParams = {
                            userid: userid,
                            syncid: serverTaskId
                        }

                        TCTaskService.getTaskForSyncId(getParams, function(getErr, existingTask) {
                            if (getErr) {
logger.debug(`processTaskActions() 48`)
                                eachCallback(getErr)
                                return
                            } 

logger.debug(`processTaskActions() 49`)
                            if (!existingTask) {
                                // How would this ever happen?
                                logger.debug(`Todo Cloud sent a task to delete that was not found locally: ${serverTaskId}`)
                                eachCallback(null)
                                return
                            }

                            const delParams = {
                                userid: userid,
                                taskid: existingTask.taskid,
                                preauthorized: true,
                                dirty: false
                            }
                            TCTaskService.deleteTask(delParams, function(delErr, delResult) {
logger.debug(`processTaskActions() 50`)
                                eachCallback(delErr)
                            })
                        })
logger.debug(`processTaskActions() 51`)
                    },
                    function(eachErr) {
logger.debug(`processTaskActions() 52`)
                        callback(eachErr)
                    })
            }
        ],
        function(err) {
logger.debug(`processTaskActions() 54`)
            if (err) {
logger.debug(`processTaskActions() 55`)
                completion(err)
            } else {
logger.debug(`processTaskActions() 56`)
                const results = {
                    taskActionsReceivedFromServer: taskActionsReceived,
                    syncHadServerChanges: syncHadServerChanges
                }
                if (syncUpAgain) {
logger.debug(`processTaskActions() 57`)
                    results["syncUpAgain"] = true
                }

                completion(null, results)
            }
        })
    }

    // Purpose of this is to update the values in the task and return
    // whether any changes were made
    static updateTaskWithValues(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.updateTaskWithValues().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        const task = params.task ? params.task : null
        const values = params.values ? params.values : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }
        if (!task) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing task.`))))
            return
        }
        if (!values) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing values.`))))
            return
        }

        let changedFromOriginal = false

        task.sync_id = values.taskid

        // If this method is being called, the task is NOT deleted on the server
        // and we need to restore the task if needed from the deleted state.
        if (task.deleted != undefined && task.deleted) {
            task.deleted = false
            changedFromOriginal = true
        }
        
        const name = values.name
        if (name != undefined && name.length > 0) {
            task.name = name
        } else {
            task.name = `Unknown` // TO-DO: Localize this
        }

        const serverTaskType = values['task_type']
        if (serverTaskType != undefined) {
            task.task_type = parseInt(serverTaskType)
            const serverTaskTypeData = values['type_data']
            if (serverTaskTypeData) {
                task.type_data = serverTaskTypeData
            }
        } else {
            task.task_type = constants.TaskType.Normal
            task.type_data = null
        }

        const isProject = task.task_type && task.task_type == constants.TaskType.Project

        const duedate = values['duedate']
        if (duedate != undefined && parseInt(duedate) > 0) {
            if (isProject) {
                task.project_duedate = parseInt(duedate)
            } else {
                task.duedate = parseInt(duedate)
            }

            const hasTimeValue = values['duedatehastime']
            if (hasTimeValue != undefined && (hasTimeValue == "true" || parseInt(hasTimeValue) > 0)) {
                if (isProject) {
                    task.project_duedate_has_time = true
                } else {
                    task.due_date_has_time = true
                }
            } else {
                if (isProject) {
                    task.project_duedate = TCUtils.normalizedDateFromGMT(task.project_duedate)
                    task.project_duedate_has_time = false
                } else {
                    task.duedate = TCUtils.normalizedDateFromGMT(task.duedate)
                    task.due_date_has_time = false
                }
            }
        } else {
            if (isProject) {
                task.project_duedate = 0
                task.project_duedate_has_time = false
            } else {
                task.duedate = 0
                task.due_date_has_time = false
            }
        }

        const startdate = values['startdate']
        if (startdate != undefined && parseInt(startdate) > 0) {
            if (isProject) {
                task.project_startdate = TCUtils.normalizedDateFromGMT(parseInt(startdate))
            } else {
                task.startdate = TCUtils.normalizedDateFromGMT(parseInt(startdate))
            }
        } else {
            if (isProject) {
                task.project_startdate = 0
            } else {
                task.startdate = 0
            }
        }

        const completionDate = values['completiondate']
        if (completionDate != undefined && parseInt(completionDate) > 0) {
            task.completiondate = TCUtils.normalizedDateFromGMT(parseInt(completionDate))
        } else {
            task.completiondate = 0
        }

        const recurrenceType = values['recurrence_type']
        if (recurrenceType != undefined) {
            task.recurrence_type = parseInt(recurrenceType)
        } else {
            task.recurrence_type = constants.TaskRecurrenceType.None
        }

        // Bug 7401 (Bugzilla) - If the task is set to recure with a parent but doesn't have a parent id,
        // set it to none.
        if (!task.parentid && (task.recurrence_type == constants.TaskRecurrenceType.WithParent || task.recurrence_type == constants.TaskRecurrenceType.WithParent + 100)) {
            task.recurrence_type = constants.TaskRecurrenceType.None
        }

        const advRecVal = values['advanced_recurrence_string']
        if (advRecVal != undefined && advRecVal.length > 0) {
            task.advanced_recurrence_string = advRecVal
        } else {
            task.advanced_recurrence_string = null
        }

        const note = values['note']
        if (note != undefined && note.length > 0) {
            task.note = note
        } else {
            task.note = null
        }

        const priority = values['priority']
        let priorityValue = constants.TaskPriority.None
        if (priority != undefined) {
            switch (parseInt(priority)) {
                case 1: { // High
                    priorityValue = constants.TaskPriority.High
                    break;
                }
                case 5: { // Medium
                    priorityValue = constants.TaskPriority.Medium
                    break;
                }
                case 9: { // Low
                    priorityValue = constants.TaskPriority.Low
                    break;
                }
                default:
                case 0: { // None
                    priorityValue = constants.TaskPriority.None
                    break;
                }
            }
        }
        if (isProject) {
            task.project_priority = priorityValue
        } else {
            task.priority = priorityValue
        }


        const starredValue = values['starred']
        if (isProject) {
            task.project_starred = starredValue != undefined && parseInt(starredValue) > 0
        } else {
            task.starred = starredValue != undefined && parseInt(starredValue) > 0
        }

        const sortValue = values['sort_order']
        if (sortValue != undefined && parseInt(sortValue) > 0) {
            task.sort_order = parseInt(sortValue)
        } else {
            task.sort_order = 0
        }

        const assignedUserId = values['assigneduserid']
        if (assignedUserId != undefined) {
            task.assigned_userid = assignedUserId
        } else {
            task.assigned_userid = null
        }

        // Note: In the iOS Sync code, there's a spot to sync a comment
        // count, but there's not really such a thing in this db.

        const locationAlert = values['location_alert']
        if (locationAlert != undefined) {
            task.location_alert = locationAlert
        } else {
            task.location_alert = null
        }

        async.waterfall([
            function(callback) {
                const listid = values.listid
logger.debug(`updateTaskWithValues() 40 - ${listid}`)
                if (listid != undefined && listid.length > 0) {
                    const listParams = {
                        userid: userid,
                        syncid: listid
                    }
                    TCListService.getListForSyncId(listParams, function(err, list) {
                        if (err) {
logger.debug(`updateTaskWithValues() 41`)
                            callback(err)
                        } else {
logger.debug(`updateTaskWithValues() 42 - ${list ? JSON.stringify(list) : 'list is null'}`)
                            // NOTE: The list object is sent with 'list' and 'settings' objects inside of it
                            if (list && list.list.listid) {
logger.debug(`updateTaskWithValues() 43`)
                                task.listid = list.list.listid
                            } else {
logger.debug(`updateTaskWithValues() 44`)
                                task.listid = constants.LocalInboxId
                            }
logger.debug(`updateTaskWithValues() 45`)
                            callback(null)
                        }
                    })
                } else {
logger.debug(`updateTaskWithValues() 46`)
                    task.listid = constants.LocalInboxId
                    callback(null)
                }
            },
            function(callback) {
logger.debug(`updateTaskWithValues() 47`)
                const parentid = values.parentid
                if (parentid != undefined && parentid.length > 0) {
logger.debug(`updateTaskWithValues() 48`)
                    const getTaskParams = {
                        userid: userid,
                        preauthorized: true,
                        syncid: parentid
                    }
                    TCTaskService.getTaskForSyncId(getTaskParams, function(err, parentTask) {
                        if (err) {
logger.debug(`updateTaskWithValues() 49`)
                            callback(err)
                        } else {
logger.debug(`updateTaskWithValues() 50`)
                            // Bug 7401 (Bugzilla) - If the server sends us a subtask and its
                            // parent is no longer valid locally, switch it to a normal task
                            // and return that we made changes so that we'll sync that change
                            // up to the server.
                            if (!parentTask || !parentTask.isProject()) {
logger.debug(`updateTaskWithValues() 51`)
                                task.parentid = null
                                changedFromOriginal = true
                            } else {
logger.debug(`updateTaskWithValues() 52`)
                                task.parentid = parentTask.taskid
                            }
                            callback(null)
                        }
                    })
                } else {
logger.debug(`updateTaskWithValues() 53`)
                    task.parentid = null
                    callback(null)
                }
            },
            function(callback) {
                TCSyncService.assignTags({
                    task : task,
                    userid : userid,
                    tagString : values.tags
                }, callback)
            }
        ],
        function(err) {
            if (err) {
                completion(err)
            } else {
                const result = {}
                if (changedFromOriginal) {
                    result['changesMade'] = true
                }
                completion(null, result)
            }
        })
    }

    static assignTags(params, completion) {
        // logger.debug(`Assigning Tags 1`, params)
        if (!params) {
            completion(errors.create(errors.missingParameters, `TCSyncService.assignTags().`))
            return
        }

        const userid = params.userid ? params.userid : null
        const task = params.task ? params.task : null
        const tagString = params.tagString ? params.tagString : null

        if (!userid) {
            completion(errors.create(errors.missingParameters, `assignTags() Missing userid.`))
            return
        }
        if (!task) {
            completion(errors.create(errors.missingParameters, `assignTags() Missing task.`))
            return
        }

        const waterfallFunctions = [function(next) {
            // logger.debug(`Assigning Tags 2`)
            if (!task.taskid) {
                // This must be a new task and there won't be any tags yet
                next(null, [])
                return
            }

            // Get all the tags for the task so we can know how to properly
            // process it.
            const getTagParams = {
                taskid: task.taskid,
                userid: userid
            }
            TCTagService.getTagsForTask(getTagParams, (err, tags) => {
                next(err, tags)
            })
        },
        function(tags, next) {
            // logger.debug(`Assigning Tags 3`)
            // logger.debug(`TAG STRING: `, tagString)
            const removeTags = tagString == undefined || tagString.length == 0
            const noActionNeeded = removeTags && tags.length == 0
            const skip = !task.taskid || noActionNeeded
            if (skip) {
                // logger.debug(`NO TAGS, SKIPPING`)
                // This must be a new task and there won't be any tags yet
                next(null, [], [])
                return
            }

            // Let's hope that everything else succeeds because we're gonna assign
            // tags to the task if they're different.            
            if (removeTags) {
                // logger.debug(`REMOVING ALL TAGS FROM ${task.name}`)
                const removeParams = {
                    userid: userid,
                    taskid: task.taskid
                }
                TCTagService.removeAllTagAssignmentsFromTask(removeParams, function(removeErr, removeResult) {
                    next(null, [], [])
                })
                return
            } 

            const serverTagsArray = tagString.split(`,`)
            // Go through the tags array and determine which ones to
            // add and which ones to remove.
            const tagsToRemove = tags.filter(tag => !serverTagsArray.find(tagName => tag.name == tagName))

            // Look to see if we have this tag already assigned.
            // If not, we need to add it as a new tag and assign
            // it to the task.
            const tagNamesToAdd = serverTagsArray.filter(name => !tags.find(tag => name == tag.name))

            // logger.debug(`TAGS (${task.name}, ${task.taskid}): `, tagsToRemove, tagNamesToAdd)
            next(null, tagsToRemove, tagNamesToAdd)
        },
        function(tagsToRemove, tagNamesToAdd, next) {
            // logger.debug(`Assigning Tags 4`)
            if (!tagsToRemove || tagsToRemove.length == 0) {
                next(null, tagNamesToAdd)
                return
            }

            // logger.debug(`REMOVE TAGS: `, tagsToRemove)
            async.eachSeries(tagsToRemove,
                function(tagToRemove, eachCallback) {
                    const removeParams = {
                        tagid: tagToRemove.tagid,
                        taskid: task.taskid
                    }
                    TCTagService.removeTagAssignment(removeParams, function(removeErr, removeResult) {
                        eachCallback(removeErr)
                    })
                },
                function(err) {
                    next(err, tagNamesToAdd)
                }
            )
        },
        function(tagNamesToAdd, next) {
            // logger.debug(`Assigning Tags 5`)
            if (!tagNamesToAdd || tagNamesToAdd.length == 0) {
                next(null, null)
                return
            } 

            const tagsToAssign = []
            async.eachSeries(tagNamesToAdd,
                function(tagName, eachNext) {
                    // Check to see if the tag already exists and if it
                    // does, assign it. Otherwise, create a new tag.
                    const getTagParams = {
                        userid: userid,
                        tagName: tagName
                    }
                    TCTagService.getTagWithName(getTagParams, function(getErr, existingTag) {
                        if (getErr) {
                            eachNext(getErr)
                            return
                        } 

                        if (existingTag) {
                            tagsToAssign.push(existingTag)
                            eachNext(null)
                            return
                        } 

                        // Create a new tag to be assigned
                        const createParams = {
                            name: tagName
                        }
                        // logger.debug(`CREATING NEW TAG: `, tagName)
                        TCTagService.createTag(createParams, function(createErr, newTag) {
                            if (createErr) {
                                eachNext(createErr)
                                return
                            } 

                            if (newTag) {
                                tagsToAssign.push(newTag)
                            }

                            eachNext(null)
                        })
                    })
                },
                function(eachErr) {
                    next(eachErr, tagsToAssign)
                }
            )
        },
        function(tagsToAssign, next) {
            // logger.debug(`Assigning Tags 6`)
            // logger.debug(`ASSIGN TAGS: `, tagsToAssign, task.taskid)
            if (!task.taskid || !tagsToAssign || tagsToAssign.length == 0) {
                next(null)
                return
            } 

            const iteratorFunction = (tag, eachNext) => {
                const assignParams = {
                    tagid: tag.tagid,
                    taskid: task.taskid
                }
                TCTagService.assignTag(assignParams, function(assignErr, result) {
                    if (assignErr) {
                        logger.error(`Error assigning tag during sync`, assignErr)
                    }
                    eachNext(assignErr)
                })
            }

            async.eachSeries(tagsToAssign, iteratorFunction, next)
        }]

        async.waterfall(waterfallFunctions, completion)
    }

    static syncTaskitos(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.syncTaskitos().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const timestamps = params.timestamps ? params.timestamps : null
        const isFirstSync = params.isFirstSync ? params.isFirstSync : false        

        let syncUpAgain = false

        const addTasks = []
        const modifyTasks = []
        const deleteTasks = []
        
        let addTaskString = null
        let modTaskString = null
        let delTaskString = null

        let allDirtyTaskitos = null

        let response = null
        
        const tasksToPermanentlyDelete = []

        async.waterfall([
            function(callback) {
                if (!isFirstSync) {
                    const getTaskParams = {
                        userid: userid
                    }
                    TCTaskitoService.getAllDirtyTaskitos(getTaskParams, function(err, dirtyTaskitos) {
                        if (err) {
                            callback(err)
                        } else {
                            allDirtyTaskitos = dirtyTaskitos;
                            logger.debug(`Todo Cloud found ${allDirtyTaskitos.length} checklist items needing sync.`)
                            callback(null)
                        }
                    })
                } else {
                    callback(null)
                }
            },
            function(callback) {
                if (allDirtyTaskitos) {
                    async.eachSeries(allDirtyTaskitos,
                        function(curTask, eachCallback) {
                            if (curTask.deleted) {
                                // The task is marked as deleted
                                if (curTask.sync_id && curTask.sync_id.length > 0) {
                                    // We need to delete this taskito from the server
                                    const jsonParams = {
                                        task: curTask,
                                        isDelete: true,
                                        isAdd: false,
                                        userid: userid
                                    }
                                    TCSyncService.jsonForTaskito(jsonParams, function(jsonErr, taskJSON) {
                                        if (jsonErr) {
                                            eachCallback(jsonErr)
                                        } else {
                                            deleteTasks.push(taskJSON)
                                            eachCallback(null)
                                        }
                                    })
                                } else {
                                    // Delete the list locally
                                    tasksToPermanentlyDelete.push(curTask)
                                    eachCallback(null)
                                }
                            } else {
                                // The task is NOT marked as deleted (active)
                                if (curTask.sync_id && curTask.sync_id.length > 0) {
                                    // Update the taskito on the server
                                    const jsonParams = {
                                        task: curTask,
                                        isDelete: false,
                                        isAdd: false,
                                        userid: userid
                                    }
                                    TCSyncService.jsonForTaskito(jsonParams, function(jsonErr, taskJSON) {
                                        if (jsonErr) {
                                            eachCallback(jsonErr)
                                        } else {
                                            modifyTasks.push(taskJSON)
                                            eachCallback(null)
                                        }
                                    })
                                } else {
                                    // Create the taskito on the server
                                    const jsonParams = {
                                        task: curTask,
                                        isDelete: false,
                                        isAdd: true,
                                        userid: userid
                                    }
                                    TCSyncService.jsonForTaskito(jsonParams, function(jsonErr, taskJSON) {
                                        if (jsonErr) {
                                            eachCallback(jsonErr)
                                        } else {
                                            addTasks.push(taskJSON)
                                            eachCallback(null)
                                        }
                                    })
                                }
                            }
                        },
                        function(err) {
                            callback(err)
                        }
                    )
                } else {
                    logger.debug(`Todo Cloud detected a first-time sync. Requesting ALL taskitos from server...`)
                    callback(null)
                }
            },
            function(callback) {
                // Delete tasks that may have been marked for local deletion
                if (tasksToPermanentlyDelete.length > 0) {
                    async.eachSeries(tasksToPermanentlyDelete,
                        function(curTask, eachCallback) {
                            const deleteParams = {
                                taskitoid: curTask.taskid,
                                userid: userid
                            }
                            TCTaskitoService.deleteTaskito(deleteParams, function(err, result) {
                                eachCallback(err)
                            })
                        },
                        function(err) {
                            callback(err)
                        }
                    )
                } else {
                    callback(null)
                }
            },
            function(callback) {
                // Full "paged" sync inside the do-while loop so that no one request
                // to the server pushes or pulls too many tasks at once. This will help
                // us have higher sync success rates by making us less dependant on
                // longer network requests and should also help the server keep its
                // processing time lower because the separate requests can be load-
                // balanced. Customers with a large task data set should be able to
                // sync any number of tasks to/from the service.

                const bulkSyncPageSize = constants.SyncServiceBulkSyncPageTaskCount

                // The call to sync tasks is done on a list by list basis and so a
                // global page offset won't work. Instead, we track the page offset
                // separately for each list during the doWhilst sync loop. The client
                // maintains an object of list offsets which are provided by the server.
                // In subsequent calls to the server, the server uses this to know where
                // to pick up from based on the previous request.
                const listOffsets = {}

                // In each loop we keep track of how many tasks we've received from the
                // server. If the number is ever 0, we'll know that we're done with tasks
                // on the server and can consider the sync done for that list.
                let taskActionsReceivedFromServer = 0

                async.doWhilst(function(doWhilstCallback) {
                    let tasksToPushCount = 0 // reset to 0 each time we loop

                    if (addTasks.length > 0 && tasksToPushCount < bulkSyncPageSize) {
                        // Respect the page size allowed to push to the server
                        const pushSlotsAvailable = bulkSyncPageSize - tasksToPushCount
                        const numOfTasksToPush = Math.min(pushSlotsAvailable, addTasks.length)

                        // Remove the paged tasks from the main array of tasks so they
                        // won't be sent again. The removed items are returned into
                        // pagedTasks.
                        const pagedTasks = addTasks.splice(0, numOfTasksToPush)
                        tasksToPushCount = tasksToPushCount + pagedTasks.length

                        addTaskString = JSON.stringify(pagedTasks)
                    } else {
                        addTaskString = null
                    }
                    
                    if (modifyTasks.length > 0 && tasksToPushCount < bulkSyncPageSize) {
                        // Respect the page size allowed to push to the server
                        const pushSlotsAvailable = bulkSyncPageSize - tasksToPushCount
                        const numOfTasksToPush = Math.min(pushSlotsAvailable, modifyTasks.length)

                        // Remove the paged tasks from the main array of tasks so they
                        // won't be sent again. The removed items are returned into
                        // pagedTasks.
                        const pagedTasks = modifyTasks.splice(0, numOfTasksToPush)
                        tasksToPushCount = tasksToPushCount + pagedTasks.length

                        modTaskString = JSON.stringify(pagedTasks)
                    } else {
                        modTaskString = null
                    }
                    
                    if (deleteTasks.length > 0 && tasksToPushCount < bulkSyncPageSize) {
                        // Respect the page size allowed to push to the server
                        const pushSlotsAvailable = bulkSyncPageSize - tasksToPushCount
                        const numOfTasksToPush = Math.min(pushSlotsAvailable, deleteTasks.length)

                        // Remove the paged tasks from the main array of tasks so they
                        // won't be sent again. The removed items are returned into
                        // pagedTasks.
                        const pagedTasks = deleteTasks.splice(0, numOfTasksToPush)
                        tasksToPushCount = tasksToPushCount + pagedTasks.length

                        delTaskString = JSON.stringify(pagedTasks)
                    } else {
                        delTaskString = null
                    }

                    const syncParams = {
                        method: 'syncTaskitos',
                        numOfTasks: bulkSyncPageSize
                    }
                    if (addTaskString) {
logger.debug(`syncTaskitos: 30: ${addTaskString}`)
                        syncParams['addTaskitos'] = addTaskString
                    }
                    if (modTaskString) {
logger.debug(`syncTaskitos: 31: ${modTaskString}`)
                        syncParams['updateTaskitos'] = modTaskString
                    }
                    if (delTaskString) {
logger.debug(`syncTaskitos: 32: ${delTaskString}`)
                        syncParams['deleteTaskitos'] = delTaskString
                    }
                    if (timestamps) {
                        Object.keys(timestamps).forEach((serverListId) => {
logger.debug(`syncTaskitos: 34: ${serverListId}`)
                            syncParams[serverListId] = timestamps[serverListId]
                        })
                    }
                    if (Object.keys(listOffsets).length > 0) {
logger.debug(`syncTaskitos: 35`)
                        Object.keys(listOffsets).forEach((listOffsetKey) => {
                            const offset = listOffsets[listOffsetKey]
                            const offsetString = parseInt(offset)
logger.debug(`syncTaskitos: 36: ${listOffsetKey} = ${offsetString}`)
                            syncParams[listOffsetKey] = offsetString
                        })
                    }
logger.debug(`Calling 'syncTaskitos' with:\n${JSON.stringify(syncParams)}`)

                    TCSyncService.makeSyncRequest(syncParams, function(err, syncResponse) {
                        if (err) {
logger.debug(`syncTaskitos: 37`)
                            doWhilstCallback(err)
                        } else {
                            response = syncResponse
                            // There's a LOT to do. Time for another async.waterfall
                            const addedResults = response.results && Object.keys(response.results).length > 0 ? response.results.added : null
                            const updatedResults = response.results && Object.keys(response.results).length > 0 ? response.results.updated : null
                            const deletedResults = response.results && Object.keys(response.results).length > 0 ? response.results.deleted : null
                            const offsetResults = response.listOffsets && Object.keys(response.listOffsets).length > 0 ? response.listOffsets : null

                            async.waterfall([
                                function(waterfallCallback) {
                                    if (addedResults) {
logger.debug(`syncTaskitos: 37.1`)
                                        const addedParams = {
                                            userid: userid,
                                            addedTaskitos: addedResults
                                        }
                                        TCSyncService.processAddedTaskitos(addedParams, function(addedErr, addedResult) {
                                            if (addedErr) {
logger.debug(`syncTaskitos: 37.2`)
                                                waterfallCallback(addedErr)
                                            } else {
logger.debug(`syncTaskitos: 37.3`)
                                                // if (addedResult.syncUpAgain != undefined) {
                                                //     syncUpAgain = addedResult.syncUpAgain
                                                // }
                                                waterfallCallback(null)
                                            }
                                        })
                                    } else {
logger.debug(`syncTaskitos: 37.4`)
                                        // Skip added tasks
                                        waterfallCallback(null)
                                    }
                                },
                                function(waterfallCallback) {
                                    if (updatedResults) {
logger.debug(`syncTaskitos: 37.5`)
                                        const updatedParams = {
                                            userid: userid,
                                            updatedTaskitos: updatedResults
                                        }
                                        TCSyncService.processUpdatedTaskitos(updatedParams, function(updatedErr, updatedResult) {
                                            if (updatedErr) {
logger.debug(`syncTaskitos: 37.6`)
                                                waterfallCallback(updatedErr)
                                            } else {
logger.debug(`syncTaskitos: 37.7`)
                                                // if (updatedResult.syncUpAgain != undefined) {
                                                //     syncUpAgain = updatedResult.syncUpAgain
                                                // }
                                                waterfallCallback(null)
                                            }
                                        })
                                    } else {
logger.debug(`syncTaskitos: 37.8`)
                                        // Skip updated tasks
                                        waterfallCallback(null)
                                    }
                                },
                                function(waterfallCallback) {
                                    if (deletedResults) {
logger.debug(`syncTaskitos: 37.9`)
                                        const deletedParams = {
                                            userid: userid,
                                            deletedTaskitos: deletedResults
                                        }
                                        TCSyncService.processDeletedTaskitos(deletedParams, function(deletedErr, deletedResult) {
                                            if (deletedErr) {
logger.debug(`syncTaskitos: 37.10`)
                                                waterfallCallback(deletedErr)
                                            } else {
logger.debug(`syncTaskitos: 37.11`)
                                                waterfallCallback(null)
                                            }
                                        })
                                    } else {
logger.debug(`syncTaskitos: 37.12`)
                                        // Skip deleted tasks
                                        waterfallCallback(null)
                                    }
                                },
                                function(waterfallCallback) {
                                    // Reset this now that we're in the loop
logger.debug(`syncTaskitos: 37.13`)
                                    taskActionsReceivedFromServer = 0
                                    if (response.actions && Object.keys(response.actions).length > 0) {
logger.debug(`syncTaskitos: 37.14`)
                                        const actionParams = {
                                            userid: userid,
                                            taskActions: response.actions
                                        }
                                        TCSyncService.processTaskitoActions(actionParams, function(actionErr, result) {
                                            if (actionErr) {
logger.debug(`syncTaskitos: 37.15`)
                                                waterfallCallback(actionErr)
                                            } else {
logger.debug(`syncTaskitos: 37.16`, result)
                                                taskActionsReceivedFromServer = result.taskActionsReceivedFromServer
                                                waterfallCallback(null)
                                            }
                                        })
                                    } else {
logger.debug(`syncTaskitos: 37.17`)
                                        logger.debug(`No additional taskito changes found on the Todo Cloud server.`)
                                        waterfallCallback(null)
                                    }
                                },
                                function(waterfallCallback) {
logger.debug(`syncTaskitos: 38`)
                                    // Update the task offsets with the values set by the server so
                                    // the next time in the doWhilst loop we'll ask for the right set
                                    // of tasks.
                                    if (offsetResults) {
logger.debug(`syncTaskitos: 38.1`)
                                        Object.keys(offsetResults).forEach((key) => {
                                            listOffsets[key] = offsetResults[key]
                                        })
                                    }

                                    waterfallCallback(null)
                                }
                            ],
                            function(err) {
                                if (err) {
logger.debug(`syncTaskitos: 39`)
                                    doWhilstCallback(err)
                                } else {
logger.debug(`syncTaskitos: 40`)
                                    doWhilstCallback(null)
                                }
                            })
                        }
                    })
                },
                function() {
logger.debug(`syncTaskitos: 41`)
                    // Test function to know when to bail out of the doWhilst
                    return (addTasks.length > 0 || modifyTasks.length > 0 || deleteTasks.length > 0 || taskActionsReceivedFromServer > 0)
                },
                function(err) {
logger.debug(`syncTaskitos: 42`)
                    // Called after the doWhilst test function returns false
                    // or if there is an error in the main work function.                  
                    callback(err)
                })
            },
            function(callback) {
                if (!response) {
logger.debug(`syncTaskitos: 43`)
                    callback(null)
                } else {
logger.debug(`syncTaskitos: 44`)
                    const taskitoTimeStamps = (response.alltaskitotimestamps != undefined && Object.keys(response.alltaskitotimestamps).length > 0) ? response.alltaskitotimestamps : null
                    if (taskitoTimeStamps) {
logger.debug(`syncTaskitos: 45`)
                        // Following the convetion we used in iOS, we only update this if we get some back
                        _allTaskitoTimeStamps = taskitoTimeStamps ? taskitoTimeStamps : {}
                        const settingParams = {}
                        settingParams[constants.SettingsAllTaskitoTimeStamps] = _allTaskitoTimeStamps
                        db.setSettings(settingParams, function(err, settingResult) {
logger.debug(`syncTaskitos: 46`)
                            callback(err)
                        })
                    } else {
logger.debug(`syncTaskitos: 46.1`)
                        callback(null)
                    }

                }
            },
        ],
        function(err) {
            if (err) {
logger.debug(`syncTaskitos: 47`)
                completion(err)
            } else {
logger.debug(`syncTaskitos: 48`)
                if (isFirstSync || syncUpAgain) {
logger.debug(`syncTaskitos: 49`)
                    // Now that we've synced the first time, we need to sync tasks again
                    // to push any potential changes back up to the server that happened
                    // during the original sync.
                    const newSyncParams = {
                        timestamps: _allTaskitoTimeStamps,
                        isFirstSync: false,
                        userid: userid
                    }
                    TCSyncService.syncTaskitos(newSyncParams, function(newSyncErr, newSyncResult) {
                        if (newSyncErr) {
logger.debug(`syncTaskitos: 50`)
                            completion(newSyncErr)
                        } else {
logger.debug(`syncTaskitos: 51`)
                            completion(null, true)
                        }
                    })
                // } else if (syncUpAgain) {
                //     const newSyncParams = {
                //         timestamps: _allTaskitoTimeStamps,
                //         isFirstSync: false,
                //         userid: userid
                //     }
                //     TCSyncService.syncTaskitos(newSyncParams, function(newSyncErr, newSyncResult) {
                //         if (newSyncErr) {
                //             completion(newSyncErr)
                //         } else {
                //             completion(null, true)
                //         }
                //     })
                } else {
logger.debug(`syncTaskitos: 52`)
                    completion(null, true)
                }
            }
        })
    }

    static processAddedTaskitos(params, completion) {
logger.debug(`processAddedTaskitos: 1`)
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processAddedTaskitos().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const addedTasks = params.addedTaskitos ? params.addedTaskitos : null
        if (!addedTasks) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing addedTaskitos.`))))
            return
        }

        let syncUpAgain = false

        // Keeping track of taskitos that have lost a connection with their parent
        // checklist: In some cases when restoring task databases (has happend at
        // least twice that we know of), there were one or more taskitos that failed
        // to be added because the parent task was missing. This fixes the problem
        // by creating a "Recovered Checklist Items" checklist and setting the
        // parent to the new checklist item.
        const taskitosToAdd = []
        const taskitosToDelete = []

        async.eachSeries(addedTasks,
            function(addedTask, eachCallback) {
                const errorCode = addedTask.errorcode
                if (errorCode) {
logger.debug(`processAddedTaskitos: 2`)
                    let resolvedError = false
                    let taskitoName = null
                    let taskitoid = addedTask.tmptaskitoid
                    let updatedTaskito = null
                    // let showSyncError = true
                    async.waterfall([
                        function(waterfallCallback) {
logger.debug(`processAddedTaskitos: 3`)
                            if (taskitoid) {
logger.debug(`processAddedTaskitos: 4`)
                                const getTaskitoParams = {
                                    userid: userid,
                                    taskitoid: taskitoid,
                                    preauthorized: true
                                }
                                TCTaskitoService.getTaskito(getTaskitoParams, function(getTaskitoErr, aTaskito) {
                                    if (getTaskitoErr) {
logger.debug(`processAddedTaskitos: 5`)
                                        waterfallCallback(getTaskitoErr)
                                    } else {
logger.debug(`processAddedTaskitos: 6`)
                                        updatedTaskito = aTaskito
                                        if (updatedTaskito) {
logger.debug(`processAddedTaskitos: 7`)
                                            taskitoName = updatedTaskito.name
                                            if (updatedTaskito.parentid) {
logger.debug(`processAddedTaskitos: 8`)
                                                const getTaskParams = {
                                                    userid: userid,
                                                    taskid: updatedTaskito.parentid,
                                                    preauthorized: true
                                                }
                                                TCTaskService.getTask(getTaskParams, function(getTaskErr, parentChecklist) {
                                                    if (getTaskErr) {
logger.debug(`processAddedTaskitos: 9`)
                                                        waterfallCallback(getTaskErr)
                                                    } else {
logger.debug(`processAddedTaskitos: 10`)
                                                        if (parentChecklist) {
logger.debug(`processAddedTaskitos: 11`)
                                                            // Everything good, no need to repair
                                                            waterfallCallback(null)
                                                        } else {
logger.debug(`processAddedTaskitos: 12`)
                                                            // No checklist found. We'll resolve the problem
                                                            // automatically, so we don't have to signal a sync err.
                                                            resolvedError = true
                                                            if (updatedTaskito.completiondate == 0) {
logger.debug(`processAddedTaskitos: 13`)
                                                                taskitosToAdd.push(updatedTaskito)
                                                            } else {
logger.debug(`processAddedTaskitos: 14`)
                                                                taskitosToDelete.push(updatedTaskito)
                                                            }
                                                            waterfallCallback(null)
                                                        }
                                                    }
                                                })
                                            } else {
logger.debug(`processAddedTaskitos: 15`)
                                                waterfallCallback(null)
                                            }
                                        } else {
logger.debug(`processAddedTaskitos: 16`)
                                            waterfallCallback(null)
                                        }
                                    }
                                })
                            } else {
logger.debug(`processAddedTaskitos: 17`)
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            if (taskitoName) {
logger.debug(`processAddedTaskitos: 18`)
                                logger.debug(`Adding checklist subtask '${taskitoName}' failed with error code: ${errorCode}`)
                            } else {
logger.debug(`processAddedTaskitos: 19`)
                                logger.debug(`Adding checklist subtask failed with error code: ${errorCode}`)
                            }

                            if (!resolvedError) {
logger.debug(`processAddedTaskitos: 20`)
                                waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Error processing added checklist items.`))))
                            } else {
logger.debug(`processAddedTaskitos: 21`)
                                waterfallCallback(null)
                            }
                        }
                    ],
                    function(waterfallErr) {
logger.debug(`processAddedTaskitos: 22`)
                        eachCallback(waterfallErr)
                    })
                } else {
logger.debug(`processAddedTaskitos: 23 - ${JSON.stringify(addedTask)}`)
                    const syncid = addedTask.taskitoid
                    const tmpTaskId = addedTask.tmptaskitoid
                    if (tmpTaskId) {
logger.debug(`processAddedTaskitos: 24`)
                        const getTaskitoParams = {
                            userid: userid,
                            taskitoid: tmpTaskId,
                            preauthorized: true
                        }
                        TCTaskitoService.getTaskito(getTaskitoParams, function(getTaskitoErr, aTask) {
                            if (getTaskitoErr) {
logger.debug(`processAddedTaskitos: 25`)
                                eachCallback(getTaskitoErr)
                            } else {
logger.debug(`processAddedTaskitos: 26`)
                                aTask.sync_id = syncid
                                aTask.userid = userid
                                aTask.dirty = false
                                aTask.isSyncService = true
                                TCTaskitoService.updateTaskito(aTask, function(updateErr, updateResult) {
                                    if (updateErr) {
logger.debug(`processAddedTaskitos: 27.1: ${updateErr}`)
                                        eachCallback(updateErr)
                                    } else {
logger.debug(`processAddedTaskitos: 27.2: ${JSON.stringify(updateResult)}`)
                                        eachCallback(null)
                                    }
                                })
                            }
                        })
                    } else {
logger.debug(`processAddedTaskitos: 28`)
                        eachCallback(null)
                    }
                }
            },
            function(err) {
                if (err) {
logger.debug(`processAddedTaskitos: 29`)
                    completion(err)
                } else {
logger.debug(`processAddedTaskitos: 30`)
                    async.waterfall([
                        function(waterfallCallback) {
logger.debug(`processAddedTaskitos: 31`)
                            // Add in any orphaned taskitos
                            if (taskitosToAdd.length > 0) {
logger.debug(`processAddedTaskitos: 32`)
                                const addTaskParams = {
                                    userid: userid,
                                    name: "Recovered Checklist Items", // TO-DO: figure out how to localize this
                                    task_type: constants.TaskType.Checklist,
                                    listid: constants.LocalInboxId,
                                    dirty: true,
                                    isSyncService: true
                                }
                                TCTaskService.addTask(addTaskParams, function(addErr, addedChecklist) {
                                    if (addErr) {
logger.debug(`processAddedTaskitos: 33`)
                                        waterfallCallback(addErr)
                                    } else {
logger.debug(`processAddedTaskitos: 34`)
                                        syncUpAgain = true
                                        async.eachSeries(taskitosToAdd,
                                        function(taskitoToAdd, addEachCallback) {
logger.debug(`processAddedTaskitos: 35`)
                                            taskitoToAdd.userid = userid
                                            taskitoToAdd.parentid = addedChecklist.taskid
                                            taskitoToAdd.dirty = true
                                            taskitoToAdd.isSyncService = true
                                            TCTaskitoService.addTaskito(taskitoToAdd, function(addTaskitoErr, addedTaskito) {
                                                if (addTaskitoErr) {
logger.debug(`processAddedTaskitos: 36`)
                                                    addEachCallback(addTaskitoErr)
                                                } else {
logger.debug(`processAddedTaskitos: 37`)
                                                    addEachCallback(null)
                                                }
                                            })
                                        },
                                        function(addEachErr) {
                                            if (addEachErr) {
logger.debug(`processAddedTaskitos: 38`)
                                                waterfallCallback(addEachErr)
                                            } else {
logger.debug(`processAddedTaskitos: 39`)
                                                waterfallCallback(null)
                                            }
                                        })
                                    }
                                })
                            } else {
logger.debug(`processAddedTaskitos: 40`)
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            // Delete any orphaned completed taskitos
logger.debug(`processAddedTaskitos: 41`)
                            if (taskitosToDelete.length > 0) {
logger.debug(`processAddedTaskitos: 42`)
                                async.eachSeries(taskitosToDelete,
                                function(taskitoToDelete, eachDeleteCallback) {
                                    taskitoToDelete.deleted = true
                                    taskitoToDelete.dirty = false
                                    taskitoToDelete.userid = userid
                                    taskitoToDelete.isSyncService = true
                                    TCTaskitoService.updateTaskito(taskitoToDelete, function(updateErr, updateResult) {
                                        if (updateErr) {
logger.debug(`processAddedTaskitos: 43`)
                                            eachDeleteCallback(updateErr)
                                        } else {
logger.debug(`processAddedTaskitos: 44`)
                                            eachDeleteCallback(null)
                                        }
                                    })
                                },
                                function(eachDeleteErr) {
                                    if (eachDeleteErr) {
logger.debug(`processAddedTaskitos: 45`)
                                        waterfallCallback(eachDeleteErr)
                                    } else {
logger.debug(`processAddedTaskitos: 46`)
                                        waterfallCallback(null)
                                    }
                                })
                            } else {
logger.debug(`processAddedTaskitos: 47`)
                                waterfallCallback(null)
                            }
                        }
                    ],
                    function(waterfallErr) {
                        if (waterfallErr) {
logger.debug(`processAddedTaskitos: 48`)
                            completion(waterfallErr)
                        } else {
logger.debug(`processAddedTaskitos: 49`)
                            const result = {}
                            if (syncUpAgain) {
logger.debug(`processAddedTaskitos: 50`)
                                result["syncUpAgain"] = true
                            }
logger.debug(`processAddedTaskitos: 51`)
                            completion(null, result)
                        }
                    })
                }
            }
        )
    }

    static processUpdatedTaskitos(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processUpdatedTaskitos().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const updatedTasks = params.updatedTaskitos ? params.updatedTaskitos : null
        if (!updatedTasks) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing updatedTaskitos.`))))
            return
        }

        async.eachSeries(updatedTasks,
            function(aTask, eachCallback) {
                const errorCode = aTask.errorcode
                if (errorCode) {
                    let updatedTaskito = null
                    let taskitoName = null
                    let serverId = aTask.taskitoid
                    let showSyncError = true
                    async.waterfall([
                        function(waterfallCallback) {
                            if (serverId) {
                                const getTaskitoParams = {
                                    userid: userid,
                                    syncid: serverId
                                }
                                TCTaskitoService.getTaskitoForSyncId(getTaskitoParams, function(getTaskitoErr, localTaskito) {
                                    if (getTaskitoErr) {
                                        waterfallCallback(getTaskitoErr)
                                    } else {
                                        updatedTaskito = localTaskito
                                        if (updatedTaskito) {
                                            taskitoName = updatedTaskito.name
                                        }
                                        waterfallCallback(null)
                                    }
                                })
                            } else {
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            if (showSyncError) {
                                if (taskitoName) {
                                    logger.debug(`Updating taskito ${taskitoName} failed during sync with error code: ${errorCode}`)
                                    waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Updating taskito ${taskitoName} failed during sync with error code: ${errorCode}`))))
                                } else {
                                    logger.debug(`Updating a taskito failed during sync with error code: ${errorCode}`)
                                    waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Updating a taskito failed during sync with error code: ${errorCode}`))))
                                }
                            } else {
                                waterfallCallback(null)
                            }
                        }
                    ],
                    function(waterfallErr) {
                        eachCallback(waterfallErr)
                    })
                } else {
                    const syncId = aTask.taskitoid
                    if (syncId) {
                        const getTaskitoParams = {
                            userid: userid,
                            syncid: syncId
                        }
                        TCTaskitoService.getTaskitoForSyncId(getTaskitoParams, function(getTaskErr, localTask) {
                            if (getTaskErr) {
                                eachCallback(getTaskErr)
                            } else {
                                if (localTask) {
                                    localTask.userid = userid
                                    localTask.dirty = false
                                    localTask.isSyncService = true
                                    TCTaskitoService.updateTaskito(localTask, function(updateErr, updateResult) {
                                        eachCallback(updateErr)
                                    })
                                } else {
                                    // Not sure how this would ever happen
                                    eachCallback(null)
                                }
                            }
                        })
                    } else {
                        eachCallback(null)
                    }
                }
            },
            function(err) {
                if (err) {
                    completion(err)
                } else {
                    completion(null, {})
                }
            }
        )
    }

    static processDeletedTaskitos(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processDeletedTaskitos().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const deletedTasks = params.deletedTaskitos ? params.deletedTaskitos : null
        if (!deletedTasks) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing deletedTaskitos.`))))
            return
        }

        async.eachSeries(deletedTasks,
            function(aTask, eachCallback) {
                const errorCode = aTask.errorcode
                if (errorCode) {
                    let taskName = null
                    let serverId = aTask.taskitoid
                    async.waterfall([
                        function(waterfallCallback) {
                            if (serverId) {
                                const getTaskitoParams = {
                                    userid: userid,
                                    syncid: serverId
                                }
                                TCTaskitoService.getTaskitoForSyncId(getTaskitoParams, function(getTaskErr, localTaskito) {
                                    if (getTaskErr) {
                                        waterfallCallback(getTaskErr)
                                    } else {
                                        if (localTask) {
                                            taskName = localTaskito.name
                                        }
                                        waterfallCallback(null)
                                    }
                                })
                            } else {
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            if (taskName) {
                                logger.debug(`Deleting taskito ${taskName} failed during sync with error code: ${errorCode}`)
                                waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Deleting taskito ${taskName} failed during sync with error code: ${errorCode}`))))
                            } else {
                                logger.debug(`Deleting a taskito failed during sync with error code: ${errorCode}`)
                                waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Deleting a taskito failed during sync with error code: ${errorCode}`))))
                            }
                        }
                    ],
                    function(waterfallErr) {
                        eachCallback(waterfallErr)
                    })
                } else {
                    const syncid = aTask.taskitoid
                    if (syncid) {
                        const getTaskitoParams = {
                            userid: userid,
                            syncid: syncid
                        }
                        TCTaskitoService.getTaskitoForSyncId(getTaskitoParams, function(getTaskErr, localTaskito) {
                            if (getTaskErr) {
                                eachCallback(getTaskErr)
                            } else {
                                if (localTaskito) {
                                    const updateParams = {
                                        userid: userid,
                                        taskitoid: localTaskito.taskitoid,
                                        deleted: true,
                                        dirty: false,
                                        isSyncService: true
                                    }
                                    TCTaskitoService.updateTaskito(updateParams, function(updateErr, result) {
                                        eachCallback(updateErr)
                                    })
                                } else {
                                    eachCallback(null)
                                }
                            }
                        })
                    } else {
                        eachCallback(null)
                    }
                }
            },
            function(err) {
                if (err) {
                    completion(err)
                } else {
                    completion(null, true)
                }
            }
        )
    }

    static processTaskitoActions(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processTaskitoActions().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.processTaskitoActions() Missing userid.`))))
            return
        }

        const actions = params.taskActions ? params.taskActions : null
        if (!actions) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.processTaskitoActions() Missing taskActions.`))))
            return
        }

        let taskActionsReceived = 0
        let syncHadServerChanges = false
        let syncUpAgain = false

        const updateActions = actions.update != undefined && actions.update.length > 0 ? actions.update : null
        const deleteActions = actions.delete != undefined && actions.delete.length > 0 ? actions.delete : null

        async.waterfall([
            function(callback) {
                if (updateActions) {
                    taskActionsReceived = taskActionsReceived + updateActions.length
                    syncHadServerChanges = true
                    logger.debug(`Processing ${updateActions.length} checklist items that were added/modified on Todo Cloud...`)
                    async.eachSeries(updateActions,
                        function(updatedTask, eachCallback) {
logger.debug(`processTaskitoActions() 0`)
                            let oldParent = null
                            const syncid = updatedTask.taskitoid
                            if (syncid) {
                                async.waterfall([
                                    function(waterfallCallback) {
logger.debug(`processTaskitoActions() 1`)
                                        const getTaskParams = {
                                            userid: userid,
                                            syncid: syncid
                                        }
                                        TCTaskitoService.getTaskitoForSyncId(getTaskParams, function(err, taskToUpdate) {
                                            if (err) {
logger.debug(`processTaskitoActions() 2`)
                                                waterfallCallback(err)
                                            } else {
logger.debug(`processTaskitoActions() 3`)
                                                waterfallCallback(null, taskToUpdate)
                                            }
                                        })
                                    },
                                    function(taskToUpdate, waterfallCallback) {
logger.debug(`processTaskitoActions() 4`)
                                        if (taskToUpdate) {
                                            if (taskToUpdate.dirty == undefined || !taskToUpdate.dirty) {
logger.debug(`processTaskitoActions() 5`)
                                                async.waterfall([
                                                    function(innerWaterfallCallback) {
logger.debug(`processTaskitoActions() 6`)
                                                        // Bug 7430 (Bugzilla) - When a task is moved out of its checklist,
                                                        // we need to update the old parent
                                                        if (taskToUpdate.parentid != undefined && taskToUpdate.parentid.length > 0) {
                                                            const newParentId = updatedTask.parentid
                                                            if (newParentId == undefined || newParentId.length == 0 || newParentId != taskToUpdate.parentid) {
                                                                const oldParentParams = {
                                                                    userid: userid,
                                                                    taskid: taskToUpdate.parentid,
                                                                    preauthorized: true
                                                                }
                                                                TCTaskService.getTask(oldParentParams, function(getTaskErr, parentTask) {
logger.debug(`processTaskitoActions() 7`)
                                                                    if (getTaskErr) {
logger.debug(`processTaskitoActions() 8`)
                                                                        innerWaterfallCallback(getTaskErr)
                                                                    } else {
logger.debug(`processTaskitoActions() 9`)
                                                                        oldParent = parentTask
                                                                        innerWaterfallCallback(null)
                                                                    }
                                                                })
                                                            } else {
logger.debug(`processTaskitoActions() 10`)
                                                                innerWaterfallCallback(null)
                                                            }
                                                        } else {
logger.debug(`processTaskitoActions() 11`)
                                                            innerWaterfallCallback(null)
                                                        }
                                                    },
                                                    function(innerWaterfallCallback) {
logger.debug(`processTaskitoActions() 12`)
                                                        // Make sure the updated taskito is not marked as deleted if it was locally
                                                        taskToUpdate.deleted = false

                                                        // Bug 7401 (Bugzilla) - If the server sends us a subtask and its parent
                                                        // is no longer valid locally, switch it to a normal task and sync that
                                                        // change up
                                                        const updateParams = {
                                                            userid: userid,
                                                            taskito: taskToUpdate,
                                                            values: updatedTask
                                                        }
                                                        TCSyncService.updateTaskitoWithValues(updateParams, function(updateErr, updateResult) {
                                                            if (updateErr) {
logger.debug(`processTaskitoActions() 13`)
                                                                innerWaterfallCallback(updateErr)
                                                            } else {
logger.debug(`processTaskitoActions() 14`)
                                                                taskToUpdate.userid = userid
                                                                taskToUpdate.dirty = false
                                                                taskToUpdate.isSyncService = true
                                                                TCTaskitoService.updateTaskito(taskToUpdate, function(updateErr, updateResult) {
logger.debug(`processTaskitoActions() 17`)
                                                                    innerWaterfallCallback(updateErr)
                                                                })
                                                            }
                                                        })
                                                    }
                                                ], function(innerWaterfallErr) {
logger.debug(`processTaskitoActions() 18`)
                                                    waterfallCallback(innerWaterfallErr)
                                                })
                                            } else {
                                                logger.debug(`A taskito update from the server was ignored because it was modified locally on taskito: ${taskToUpdate.name}`)
                                                waterfallCallback(null)
                                            }
                                        } else {
logger.debug(`processTaskitoActions() 19`)
                                            // Search for an existing taskito
                                            async.waterfall([
                                                function(innerWaterfallCallback) {
logger.debug(`processTaskitoActions() 20`)
                                                    const searchTask = new TCTaskito()
                                                    const updateParams = {
                                                        userid: userid,
                                                        taskito: searchTask,
                                                        values: updatedTask
                                                    }
                                                    TCSyncService.updateTaskitoWithValues(updateParams, function(updateErr, updateResults) {
                                                        if (updateErr) {
logger.debug(`processTaskitoActions() 21`)
                                                            innerWaterfallCallback(updateErr)
                                                        } else {
logger.debug(`processTaskitoActions() 22`)
                                                            const getTaskParams = {
                                                                userid: userid,
                                                                taskito: searchTask
                                                            }
                                                            TCTaskitoService.getUnsyncedTaskitoMatchingProperties(getTaskParams, function(getErr, existingTask) {
                                                                if (getErr) {
logger.debug(`processTaskitoActions() 23`)
                                                                    innerWaterfallCallback(getErr)
                                                                } else {
logger.debug(`processTaskitoActions() 24`)
                                                                    innerWaterfallCallback(null, existingTask)
                                                                }
                                                            })
                                                        }
                                                    })
                                                },
                                                function(existingTask, innerWaterfallCallback) {
                                                    if (existingTask && taskToUpdate.parentid && taskToUpdate.parentid.length > 0 && updatedTask.parentid && taskToUpdate.parentid != updatedTask.parentid) {
                                                        // Bug 7430 (Bugzilla) - When a task is moved out of its parent, we need to update the old parent
                                                        const getTaskParams = {
                                                            userid: userid,
                                                            taskid: taskToUpdate.parentid,
                                                            preauthorized: true
                                                        }
                                                        TCTaskService.getTask(getTaskParams, function(getErr, aParentTask) {
                                                            if (getErr) {
                                                                innerWaterfallCallback(getErr)
                                                            } else {
                                                                oldParent = aParentTask
                                                                innerWaterfallCallback(null, existingTask)
                                                            }
                                                        })
                                                    } else {
                                                        innerWaterfallCallback(null, null)
                                                    }
                                                },
                                                function(existingTask, innerWaterfallCallback) {
logger.debug(`processTaskitoActions() 25`)
                                                    if (existingTask) {

                                                        logger.debug(`Found a local non-synced taskito that matches a server taskito (${existingTask.name})`)
                                                        const updateParams = {
                                                            userid: userid,
                                                            taskito: existingTask,
                                                            values: updatedTask
                                                        }
                                                        TCSyncService.updateTaskitoWithValues(updateParams, function(updateErr, updateResults) {
                                                            if (updateErr) {
logger.debug(`processTaskitoActions() 26`)
                                                                innerWaterfallCallback(updateErr)
                                                            } else {
logger.debug(`processTaskitoActions() 27`)
                                                                existingTask.dirty = false
                                                                existingTask.userid = userid
                                                                existingTask.isSyncService = true
                                                                TCTaskitoService.updateTaskito(existingTask, function(updateTaskErr, updateTaskResults) {
                                                                    if (updateTaskErr) {
logger.debug(`processTaskitoActions() 28`)
                                                                        innerWaterfallCallback(updateTaskErr)
                                                                    } else {
logger.debug(`processTaskitoActions() 29`)
                                                                        innerWaterfallCallback(null)
                                                                    }
                                                                })
                                                            }
                                                        })
                                                    } else {
logger.debug(`processTaskitoActions() 30 - ${JSON.stringify(updatedTask)}`)
                                                        const taskToAdd = new TCTaskito()
                                                        // Bug 7401 (Bugzilla) - If the server sends us a subtask and its parent is no longer valid
                                                        // locally, switch it to a normal task and sync that change up.
                                                        const updateParams = {
                                                            userid: userid,
                                                            taskito: taskToAdd,
                                                            values: updatedTask
                                                        }
                                                        TCSyncService.updateTaskitoWithValues(updateParams, function(updateErr, updateResults) {
                                                            if (updateErr) {
logger.debug(`processTaskitoActions() 31`)
                                                                innerWaterfallCallback(updateErr)
                                                            } else {
                                                                if (!taskToAdd.parentid) {
                                                                    innerWaterfallCallback(null)
                                                                    return
                                                                }

                                                                taskToAdd.dirty = false
                                                                taskToAdd.userid = userid
                                                                taskToAdd.isSyncService = true
logger.debug(`processTaskitoActions() 32: ${JSON.stringify(taskToAdd)}`)
                                                                TCTaskitoService.addTaskito(taskToAdd, function(addErr, addResult) {
                                                                    if (addErr) {
logger.debug(`processTaskitoActions() 33`)
                                                                        innerWaterfallCallback(addErr)
                                                                    } else {
logger.debug(`processTaskitoActions() 34 ${JSON.stringify(addResult)}`)
                                                                        innerWaterfallCallback(null)
                                                                    }
                                                                })
                                                            }
                                                        })
                                                    }
                                                }
                                            ],
                                            function(innerWaterfallErr) {
logger.debug(`processTaskitoActions() 34.1`)
                                                waterfallCallback(innerWaterfallErr)
                                            })
                                        }
                                    }
                                ],
                                function(waterfallErr) {
logger.debug(`processTaskitoActions() 35`)
                                    if (waterfallErr) {
logger.debug(`processTaskitoActions() 36`)
                                        eachCallback(waterfallErr)
                                    } else {
logger.debug(`processTaskitoActions() 37`)
                                        if (oldParent) {
logger.debug(`processTaskitoActions() 38`)
                                            // Bug 7430 (Bugzilla) - When a task is moved out of its parent,
                                            // we need to update the old parent
                                            oldParent.userid = userid
                                            oldParent.dirty = false
                                            oldParent.isSyncService = true
                                            TCTaskService.updateTask(oldParent, function(updateErr, result) {
logger.debug(`processTaskitoActions() 39`)
                                                eachCallback(updateErr)
                                            })
                                        } else {
logger.debug(`processTaskitoActions() 40`)
                                            eachCallback(null)
                                        }
                                    }
                                })
                            } else {
logger.debug(`processTaskitoActions() 41`)
                                eachCallback(null)
                            }

                        },
                        function(eachErr) {
logger.debug(`processTaskitoActions() 42`)
                            callback(eachErr)
                        })
                } else {
logger.debug(`processTaskitoActions() 43`)
                    callback(null)
                }
            },
            function(callback) {
logger.debug(`processTaskitoActions() 44`)
                if (deleteActions) {
logger.debug(`processTaskitoActions() 45`)
                    taskActionsReceived = taskActionsReceived + deleteActions.length

                    logger.debug(`Deleting ${deleteActions.length} checklist items that were deleted from Todo Cloud.`)
                    async.eachSeries(deleteActions,
                        function(deletedTask, eachCallback) {
logger.debug(`processTaskitoActions() 46`)
                            syncHadServerChanges = true

                            const serverTaskId = deletedTask.taskitoid
                            if (serverTaskId) {
logger.debug(`processTaskitoActions() 47`)
                                const getParams = {
                                    userid: userid,
                                    syncid: serverTaskId
                                }
                                TCTaskitoService.getTaskitoForSyncId(getParams, function(getErr, existingTask) {
                                    if (getErr) {
logger.debug(`processTaskitoActions() 48`)
                                        callback(getErr)
                                    } else {
logger.debug(`processTaskitoActions() 49`)
                                        if (existingTask) {
                                            const delParams = {
                                                userid: userid,
                                                taskitoid: existingTask.taskitoid,
                                                preauthorized: true,
                                                dirty: false,
                                                isSyncService: true
                                            }
                                            TCTaskitoService.deleteTaskito(delParams, function(delErr, delResult) {
logger.debug(`processTaskitoActions() 50`)
                                                callback(delErr)
                                            })
                                        } else {
                                            // How would this ever happen? In any case, since it's a deleted
                                            // taskito and the client doesn't know about it, we can safely
                                            // ignore it.
                                            logger.debug(`Todo Cloud sent a taskito to delete that was not found locally: ${serverTaskId}`)
                                            callback(null)
                                        }
                                    }
                                })
                            } else {
logger.debug(`processTaskitoActions() 51`)
                                callback(null)
                            }
                        },
                        function(eachErr) {
logger.debug(`processTaskitoActions() 52`)
                            callback(eachErr)
                        }
                    )
                } else {
logger.debug(`processTaskitoActions() 53`)
                    callback(null)
                }
            }
        ],
        function(err) {
logger.debug(`processTaskitoActions() 54`)
            if (err) {
logger.debug(`processTaskitoActions() 55`)
                completion(err)
            } else {
logger.debug(`processTaskitoActions() 56`)
                const results = {
                    taskActionsReceivedFromServer: taskActionsReceived,
                    syncHadServerChanges: syncHadServerChanges
                }
                if (syncUpAgain) {
logger.debug(`processTaskitoActions() 57`)
                    results["syncUpAgain"] = true
                }

                completion(null, results)
            }
        })
    }

    // Purpose of this is to update the values in the taskito and return
    // whether any changes were made
    static updateTaskitoWithValues(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.updateTaskitoWithValues().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        const task = params.taskito ? params.taskito : null
        const values = params.values ? params.values : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.updateTaskitoWithValues() Missing userid.`))))
            return
        }
        if (!task) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.updateTaskitoWithValues() Missing task.`))))
            return
        }
        if (!values) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.updateTaskitoWithValues() Missing values.`))))
            return
        }

        let changedFromOriginal = false

        task.sync_id = values.taskitoid
        
        const name = values.name
        if (name != undefined && name.length > 0) {
            task.name = name
        } else {
            task.name = `Unknown` // TO-DO: Localize this
        }

        const completionDate = values.completiondate
        if (completionDate && parseInt(completionDate) > 0) {
            task.completiondate = completionDate
        } else {
            task.completiondate = 0
        }

        const sortValue = values['sort_order']
        if (sortValue != undefined && parseInt(sortValue) > 0) {
            task.sort_order = parseInt(sortValue)
        } else {
            task.sort_order = 0
        }

        const parentid = values.parentid
        if (parentid != undefined && parentid.length > 0) {
logger.debug(`updateTaskitoWithValues() 48`)
            const getTaskParams = {
                userid: userid,
                preauthorized: true,
                syncid: parentid
            }
            TCTaskService.getTaskForSyncId(getTaskParams, function(err, parentTask) {
                if (err) {
logger.debug(`updateTaskitoWithValues() 49`)
                    completion(err)
                } else {
                    if (parentTask) {
logger.debug(`updateTaskitoWithValues() 50`)
                        task.parentid = parentTask.taskid
                    } else {
                        task.parentid = null
                    }
                    completion(null, true)
                }
            })
        } else {
logger.debug(`updateTaskitoWithValues() 53`)
            task.parentid = null
            completion(null, true)
        }
    }

    static resetSyncState(params, completion) {
        logger.debug(`TO-DO: Implement TCSyncService.resetSyncState()!`)
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.resetSyncState().`))))
            return
        }

        const removeSyncIds = params.removeSyncIds === true ? true : false
        completion(null, true)
    }

    static getSyncInformation(params, completion) {
        logger.debug(`TO-DO: Send LOCALE information as part of this. Look at TDOConnection.m Line 300 in the Todo for iOS code for reference.`)
        TCSyncService.makeSyncRequest({method: 'getSyncInformation'}, function(err, result) {
            if (err) {
                completion(err)
            } else {
                const syncProtocolVersion = result.protocolVersion
                if (syncProtocolVersion > constants.SyncMaxSupportedProtocolVersion) {
                    completion(new Error(JSON.stringify(errors.syncProtocolVersionUnsupported)))
                    return
                }

                async.waterfall([
                    function(callback) {
                        // Save off the session token
                        const params = {}
                        params[constants.SettingSessionTokenKey] = result.sessionToken
                        db.setSettings(params, function(err, settingResult) {
                            callback(err)
                        })
                    },
                    function(callback) {
                        // Store off (temporarily into variables) a bunch of the timestamps
                        _serverListHash = result.listHash ? result.listHash : null
                        _serverSmartListHash = result.smartListHash ? result.smartListHash : null
                        _serverUserHash = result.userHash ? result.userHash : null
                        _allTaskTimeStamps = result.alltasktimestamps && Object.keys(result.alltasktimestamps).length > 0 ? result.alltasktimestamps : {}
                        _listMembershipHashes = result.listMembershipHashes && Object.keys(result.listMembershipHashes).length > 0 ? result.listMembershipHashes : {}
                        _allTaskitoTimeStamps = result.alltaskitotimestamps && Object.keys(result.alltaskitotimestamps).length > 0 ? result.alltaskitotimestamps : {}
                        _allNotificationTimeStamps = result.allnotificationtimestamps && Object.keys(result.allnotificationtimestamps).length > 0 ? result.allnotificationtimestamps : {}
                        _contextTimeStamp = result.contexttimestamp ? result.contexttimestamp : null
                        _lastResetDataTimestamp = result.lastresetdatatimestamp ? result.lastresetdatatimestamp : null
                        callback(null)
                    },
                    function(callback) {
                        TCSyncService.updateSystemNotificationInfo(result, function(err, updateResult) {
                            callback(err)
                        })
                    },
                    function(callback) {
                        TCSyncService.updateSubscriptionInfo(result, function(err, updateResult) {
                            callback(err)
                        })
                    },
                    function(callback) {
                        // Update the number of invitations in the settings DB
                        const numOfInvitationsString = result.pendinginvitations
                        const params = {}
                        params[constants.SettingTodoCloudPendingInvitationsCountKey] = null
                        db.setSettings(params, function(err, updateResult) {
                            callback(err)
                        })
                    },
                    function(callback) {
                        const params = {}
                        params[constants.SettingEmailValidatedKey] = true
                        db.setSettings(params, function(err, updateResult) {
                            callback(err)
                        }) 
                    }
                ],
                function(err) {
                    completion(err)
                })
            }
        })
    }

    static updateSystemNotificationInfo(params, completion) {
        // Variables set inside waterfall...
        let currentNotificationId   = null
        let notificationId          = null
        let notificationMessage     = null
        let notificationTimestamp   = null

        async.waterfall([
            function(callback) {
                db.getSetting(constants.SettingSystemNotificationIdKey, function(err, systemNotificationId) {
                    if (err) {
                        callback(err)
                    } else {
                        currentNotificationId = systemNotificationId
                        callback(null)
                    }
                })
            },
            function(callback) {
                const newSettings = {}

                notificationId = params.systemNotificationId
                notificationMessage = params.systemNotificationMessage
                notificationTimestamp = params.systemNotificationTimestamp

                if (!notificationId || !notificationMessage || !notificationTimestamp) {
                    if (currentNotificationId) {
                        newSettings[constants.SettingSystemNotificationMessageKey] = null
                        newSettings[constants.SettingSystemNotificationTimestampKey] = null
                        newSettings[constants.SettingSystemNotificationUrlKey] = null
                        newSettings[constants.SettingSystemNotificationIdKey] = null
                    }
                } else {
                    if (currentNotificationId == null || currentNotificationId != notificationId) {
                        newSettings[constants.SettingSystemNotificationMessageKey] = notificationMessage
                        newSettings[constants.SettingSystemNotificationTimestampKey] = notificationTimestamp

                        const url = params.systemNotificationLearnMoreUrl
                        newSettings[constants.SettingSystemNotificationUrlKey] = url ? url : null
                        newSettings[constants.SettingSystemNotificationIdKey] = notificationId

                    }
                }

                // Note: In iOS, we'd post a notification so that the UI could respond
                // appropriately to any change in system notification. For xPlat, we can
                // probably just do a check after sync for any change in system notification
                // message and call it good. In fact, I think we already have something
                // built-in that checks for it.
                if (Object.keys(newSettings).length > 0) {
                    // There's something to save
                    db.setSettings(newSettings, function(err, setResults) {
                        callback(err)
                    })
                } else {
                    // There's nothing to save, so just continue on
                    callback(null)
                }
            }
        ],
        function(err) {
            completion(err)
        })
    }

    static updateSubscriptionInfo(params, completion) {
        const subscriptionLevelNumber = params.subscriptionLevel
        const secondsToExpirationNumber = params.subscriptionExpirationSecondsFromNow
        const subscriptionPaymentServiceNumber = params.subscriptionPaymentServiceV2

        const teamSecondsToExpirationNumber = params.subscriptionTeamExpirationSecondsFromNow

        const teamName = params.subscriptionTeamName
        const teamAdminName = params.subscriptionTeamAdminName
        const teamAdminEmail = params.subscriptionTeamBillingAdminEmail
        const userDisplayName = params.subscriptionUserDisplayName
        
        if (!secondsToExpirationNumber  && !teamSecondsToExpirationNumber)
        {
            // The information is not present in the userInfo dictionary so don't
            // change the cached information we may already have.
            logger.debug(`updateSubscriptionInfo(): No subscription expiration information available.`)
            completion(null)
            return
        }

        let subscriptionLevel = constants.SubscriptionLevel.Expired
        if (subscriptionLevelNumber) {
            subscriptionLevel = parseInt(subscriptionLevelNumber)
        }
        
        let secondsToExpiration = 0
        if (teamSecondsToExpirationNumber && parseFloat(teamSecondsToExpirationNumber) != -1) {
            secondsToExpiration = parseFloat(teamSecondsToExpirationNumber) - constants.SyncSubscriptionExpirationFudgeFactor;
        } else {
            secondsToExpiration = parseFloat(secondsToExpirationNumber) - constants.SyncSubscriptionExpirationFudgeFactor;
        }

        const now = Math.floor(Date.now() / 1000)
        const expTimestamp = now + secondsToExpiration
        const subPaymentService = subscriptionPaymentServiceNumber ? parseInt(subscriptionPaymentServiceNumber) : constants.PaymentSystemType.Unknown

        _subscriptionLevel = subscriptionLevel
        _subscriptionExpiration = expTimestamp
        _subscriptionPaymentService = subPaymentService

        _teamName = teamName && teamName.length > 0 ? teamName : null
        _teamAdminName = teamAdminName && teamAdminName.length > 0 ? teamAdminName : null
        _teamAdminEmail = teamAdminEmail && teamAdminEmail.length > 0 ? teamAdminEmail : null
        _subscriptionUserDisplayName = userDisplayName && userDisplayName.length > 0 ? userDisplayName : null

        completion(null)
    }

    static makeSyncRequest(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.makeSyncRequest().`))))
            return
        }

        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                completion(new Error(JSON.stringify(errors.customError(errors.unauthorizedError, `Missing JWT.`))))
                return
            } else {
                const method = params.method
                if (!method) {
                    completion(new Error(JSON.stringify(errors.missingParameters, `Missing method parameter.`)))
                    return
                }
logger.debug(`makeSyncRequest: ${JSON.stringify(params)}`)

                const options = {
                    uri: `${process.env.SYNC_API_URL}`,
                    method: `POST`,
                    body: params,
                    headers: {
                        Authorization: `Bearer ${jwt}`,
                        Accept: 'application/json',
                        'Content-Type': 'application/json'
                    },
                    json: true
                }

                var hasResponded = false
                var response = null
                var error = null

                rp(options)
                    .then(function(jsonResponse) {
                        response = jsonResponse
                    })
                    .catch(function(e) {
                        error = e
                    })
                    .finally(function() {
                        if (error) {
                            completion(new Error(JSON.stringify(errors.customError(errors.serverError, error))))
                        } else {
                            logger.debug(`makeSyncRequest (response): ${JSON.stringify(response)}`)
                            completion(null, response)
                        }
                    })
            }
        })
    }

    static hasDirtyTasks(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.hasDirtyTasks().`))))
            return
        }

        const modifiedBeforeDate = params.modifiedBeforeDate ? params.modifiedBeforeDate : null
        const distinguishSubtasks = params.distinguishSubtasks ? params.distinguishSubtasks : false
        const checkSubtasks = params.checkSubtasks ? params.checkSubtasks : false

        let sql = `SELECT COUNT(*) AS count FROM all_tasks_view WHERE dirty=1`

        const queryParams = []

        if (modifiedBeforeDate) {
            sql += ` AND timestamp < ?`
            queryParams.push(modifiedBeforeDate)
        }

        if (distinguishSubtasks) {
            if (checkSubtasks) {
                sql += ` AND (parentid IS NOT NULL AND parentid != '')`
            } else {
                sql += ` AND (parentid IS NULL OR parentid = '')`
            }
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
                connection.query(sql, queryParams, function(err, results, fields) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        let count = 0
                        if (results.rows) {
                            for (const row of results.rows) {
                                count = row.count
                            }
                        }
                        callback(null, connection, count > 0)
                    }
                })
            }
        ],
        function(err, connection, dirtyTasksExist) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not determine if there are dirty tasks.`))))
            } else {
                completion(null, dirtyTasksExist)
            }
        })
    }

    static jsonForTask(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.jsonForTask().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        const task = params.task ? params.task : null
        const isDelete = params.isDelete != undefined ? params.isDelete : false
        const isAdd = params.isAdd != undefined ? params.isAdd : false

logger.debug(`jsonForTask: ${JSON.stringify(task)}`)

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }
        if (!task) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing task.`))))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        let userTimeZone = null
        let taskTagsString = null
        let parentTask = null
        let listSyncId = "0"

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
                TCUserSettingsService.getUserTimeZone(timeZoneParams, function(err, timeZone) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        userTimeZone = timeZone
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const getTagParams = {
                    userid: userid,
                    taskid: task.taskid,
                    dbConnection: connection
                }
                TCTagService.getTagsForTask(getTagParams, function(err, tags) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        taskTagsString = tags.reduce(function(tagsString, tag) {
                            if (tagsString.length > 0) {
                                tagsString += ","
                            }
                            return tagsString + tag.name
                        }, "")
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                // If the task is a subtask, get the parent task, which we'll need later
                if (task.isSubtask()) {
                    const getParams = {
                        userid: userid,
                        taskid: task.parentid,
                        dbConnection: connection
                    }
                    TCTaskService.getTask(getParams, function(err, foundTask) {
                        if (err) {
                            callback(err, connection)
                        } else {
                            parentTask = foundTask
                            callback(null, connection)
                        }
                    })
                } else {
                    callback(null, connection)
                }
            },
            function(connection, callback) {
                // Read the sync_id of the task's list
                const getParams = {
                    userid: userid,
                    listid: task.listid,
                    dbConnection: connection
                }
                TCListService.getListSyncId(getParams, function(err, syncId) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        listSyncId = syncId == constants.ServerInboxId ? null : syncId
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const taskDictionary = {}

                if (isDelete) {
                    if (task.sync_id && task.sync_id.length > 0) {
                        taskDictionary["taskid"] = task.sync_id
                    }
                } else {
                    if (isAdd) {
                        taskDictionary["tmptaskid"] = task.taskid
                    } else {
                        taskDictionary["taskid"] = task.sync_id
                    }

                    if (task.name) {
                        taskDictionary["name"] = task.name
                    }

                    const isProject = task.task_type && task.task_type == constants.TaskType.Project
                    const priorityValue = isProject ? task.project_priority : task.priority
                    switch(priorityValue) {
                        default:
                        case constants.TaskPriority.None: {
                            taskDictionary["priority"] = "0"
                            break;
                        }
                        case constants.TaskPriority.Low: {
                            taskDictionary["priority"] = "9"
                            break;
                        }
                        case constants.TaskPriority.Medium: {
                            taskDictionary["priority"] = "5"
                            break;
                        }
                        case constants.TaskPriority.High: {
                            taskDictionary["priority"] = "1"
                            break;
                        }
                    }

                    let dueDate = 0
                    let dueDateHasTime = 0
                    if (isProject) {
                        dueDate = task.project_duedate
                        dueDateHasTime = task.project_duedate_has_time
                    } else {
                        dueDate = task.duedate
                        dueDateHasTime = task.due_date_has_time
                    }
                    if (dueDate == 0) {
                        taskDictionary["duedate"] = "0"
                        taskDictionary["duedatehastime"] = "0"
                    } else {
                        if (dueDateHasTime > 0) {
                            taskDictionary["duedatehastime"] = "1"
                            taskDictionary["duedate"] = dueDate.toString()
                        } else {
                            taskDictionary["duedatehastime"] = "0"
                            taskDictionary["duedate"] = TCUtils.denormalizeDateToMidnightGMT(dueDate, userTimeZone).toString()
                        }
                    }

                    taskDictionary["completiondate"] = task.completiondate != 0 ? task.completiondate.toString() : "0"

                    taskDictionary["note"] = task.note ? task.note : ""

                    taskDictionary["listid"] = listSyncId

                    taskDictionary["contextid"] = "0"
                    const starredValue = isProject ? task.project_starred : task.starred
                    taskDictionary["starred"] = starredValue ? "1" : "0"
                    taskDictionary["tags"] = taskTagsString && taskTagsString.length > 0 ? taskTagsString : ""
                    taskDictionary["sortorder"] = task.sort_order != undefined ? task.sort_order.toString() : "0"
                    if (task.isSubtask() && parentTask && parentTask.sync_id) {
                        taskDictionary["parentid"] = parentTask.sync_id
                    } else {
                        taskDictionary["parentid"] = "0"
                    }

                    taskDictionary["tasktype"] = task.task_type.toString()

                    // We were trying to simplify this for the website but it breaks
                    // on the clients so we need to go back to sending the full task
                    // type data.
                    taskDictionary["tasktypedata"] = task.type_data ? task.type_data : ""

                    taskDictionary["repeattype"] = task.recurrence_type.toString()
                    taskDictionary["advancedrepeat"] = (task.recurrence_type == constants.TaskRecurrenceType.Advanced || task.recurrence_type == constants.TaskRecurrenceType.Advanced + 100) ? task.advanced_recurrence_string : ""

                    const startDateValue = isProject ? task.project_startdate : task.startdate
                    if (startDateValue) {
                        taskDictionary["startdate"] = TCUtils.denormalizeDateToMidnightGMT(startDateValue, userTimeZone).toString()
                    } else {
                        taskDictionary["startdate"] = "0"
                    }

                    taskDictionary["locationalert"] = task.location_alert ? task.location_alert : ""

                    taskDictionary["assigneduserid"] = task.assigned_userid ? task.assigned_userid : ""

                }
                
                callback(null, connection, taskDictionary)
            }
        ],
        function(err, connection, taskDictionary) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not construct a JSON representation of a task (${task.name - task.taskid}).`))))
            } else {
                completion(null, taskDictionary)
            }
        })
    }

    static jsonForTaskito(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.jsonForTaskito().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        const task = params.task ? params.task : null
        const isDelete = params.isDelete != undefined ? params.isDelete : false
        const isAdd = params.isAdd != undefined ? params.isAdd : false

logger.debug(`jsonForTaskito: ${JSON.stringify(task)}`)

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.jsonForTaskito() Missing userid.`))))
            return
        }
        if (!task) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.jsonForTaskito() Missing task.`))))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        let parentTask = null

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
                // Get the parent checklist, which we'll need later
                const getParams = {
                    userid: userid,
                    taskid: task.parentid,
                    dbConnection: connection
                }
                TCTaskService.getTask(getParams, function(err, foundTask) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        parentTask = foundTask
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const taskDictionary = {}

                if (isDelete) {
                    if (task.sync_id && task.sync_id.length > 0) {
                        taskDictionary["taskitoid"] = task.sync_id
                    }
                } else {
                    if (isAdd) {
                        taskDictionary["tmptaskitoid"] = task.taskitoid
                    } else {
                        taskDictionary["taskitoid"] = task.sync_id
                    }

                    if (task.name) {
                        taskDictionary["name"] = task.name
                    }

                    taskDictionary["completiondate"] = task.completiondate != 0 ? task.completiondate.toString() : "0"

                    taskDictionary["sortorder"] = task.sort_order != undefined ? task.sort_order.toString() : "0"
                    if (parentTask && parentTask.sync_id) {
                        taskDictionary["parentid"] = parentTask.sync_id
                    } else {
                        taskDictionary["parentid"] = "0"
                    }
                }
                
                callback(null, connection, taskDictionary)
            }
        ],
        function(err, connection, taskDictionary) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not construct a JSON representation of a taskito (${task.name - task.taskid}).`))))
            } else {
                completion(null, taskDictionary)
            }
        })
    }

    static hasDirtyRecords(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.hasDirtyRecords().`))))
            return
        }

        const tableName = params.tableName ? params.tableName : null
        const modifiedBeforeDate = params.modifiedBeforeDate ? params.modifiedBeforeDate : null

        if (!tableName) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.hasDirtyRecords() missing tableName parameter.`))))
            return
        }

        let sql = `SELECT COUNT(*) AS count FROM ${tableName} WHERE dirty=1`

        const queryParams = []

        if (modifiedBeforeDate) {
            sql += ` AND timestamp < ?`
            queryParams.push(modifiedBeforeDate)
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
                connection.query(sql, queryParams, function(err, results, fields) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error running query: ${err.message}`))), connection)
                    }
                    else {
                        let count = 0
                        if (results.rows) {
                            for (const row of results.rows) {
                                count = row.count
                            }
                        }
                        callback(null, connection, count > 0)
                    }
                })
            }
        ],
        function(err, connection, dirtyRecordsExist) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not determine if there are dirty records for table: ${tableName}.`))))
            } else {
                completion(null, dirtyRecordsExist)
            }
        })
    }

    static syncNotifications(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.syncNotifications().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.syncNotifications() missing userid.`))))
            return
        }

        const timestamps = params.timestamps ? params.timestamps : null
        const isFirstSync = params.isFirstSync ? params.isFirstSync : false        

        let syncUpAgain = false

        const addObjects = []
        const modifyObjects = []
        const deleteObjects = []
        
        let addString = null
        let modString = null
        let delString = null

        let allDirtyObjects = null

        let response = null
        
        const objectsToPermanentlyDelete = []

        // In each loop we keep track of how many tasks we've received from the
        // server. If the number is ever 0, we'll know that we're done with tasks
        // on the server and can consider the sync done for that list.
        let actionsReceivedFromServer = 0

        async.waterfall([
            function(callback) {
                if (!isFirstSync) {
                    const getParams = {
                        userid: userid
                    }
                    TCTaskNotificationService.getAllDirtyTaskNotifications(getParams, function(err, dirtyObjects) {
                        if (err) {
                            callback(err)
                        } else {
                            allDirtyObjects = dirtyObjects;
                            logger.debug(`Todo Cloud found ${allDirtyObjects.length} task notifications needing sync.`)
                            callback(null)
                        }
                    })
                } else {
                    callback(null)
                }
            },
            function(callback) {
                if (allDirtyObjects) {
                    async.eachSeries(allDirtyObjects,
                        function(curObj, eachCallback) {
                            if (curObj.deleted) {
                                // The task is marked as deleted
                                if (curObj.sync_id && curObj.sync_id.length > 0) {
                                    // We need to delete this notification from the server
                                    const jsonParams = {
                                        notification: curObj,
                                        isDelete: true,
                                        isAdd: false,
                                        userid: userid
                                    }
                                    TCSyncService.jsonForTaskNotification(jsonParams, function(jsonErr, objJSON) {
                                        if (jsonErr) {
                                            eachCallback(jsonErr)
                                        } else {
                                            deleteObjects.push(objJSON)
                                            eachCallback(null)
                                        }
                                    })
                                } else {
                                    // Delete the notification locally
                                    objectsToPermanentlyDelete.push(curObj)
                                    eachCallback(null)
                                }
                            } else {
                                // Look for the notification's task in our local database.
                                // This will help clean up notifications that should simply
                                // be removed.
                                const getTaskParams = {
                                    userid: userid,
                                    taskid: curObj.taskid
                                }
                                TCTaskService.getTask(getTaskParams, function(getTaskErr, notifyTask) {
                                    if (getTaskErr) {
                                        eachCallback(getTaskErr)
                                    } else {
                                        if (curObj.sync_id && curObj.sync_id.length > 0) {
                                            if (notifyTask && !notifyTask.deleted) {
                                                // Upate the notification on the server
                                                const jsonParams = {
                                                    notification: curObj,
                                                    isDelete: false,
                                                    isAdd: false,
                                                    userid: userid
                                                }
                                                TCSyncService.jsonForTaskNotification(jsonParams, function(jsonErr, objJSON) {
                                                    if (jsonErr) {
                                                        eachCallback(jsonErr)
                                                    } else {
                                                        modifyObjects.push(objJSON)
                                                        eachCallback(null)
                                                    }
                                                })
                                            } else {
                                                objectsToPermanentlyDelete.push(curObj)
                                                eachCallback(null)
                                            }
                                        } else {
                                            if (notifyTask && !notifyTask.deleted) {
                                                // Create the taskito on the server
                                                const jsonParams = {
                                                    notification: curObj,
                                                    isDelete: false,
                                                    isAdd: true,
                                                    userid: userid
                                                }
                                                TCSyncService.jsonForTaskNotification(jsonParams, function(jsonErr, objJSON) {
                                                    if (jsonErr) {
                                                        eachCallback(jsonErr)
                                                    } else {
                                                        addObjects.push(objJSON)
                                                        eachCallback(null)
                                                    }
                                                })
                                            } else {
                                                objectsToPermanentlyDelete.push(curObj)
                                                eachCallback(null)
                                            }
                                        }
                                    }
                                })
                            }
                        },
                        function(err) {
                            callback(err)
                        }
                    )
                } else {
                    logger.debug(`Todo Cloud detected a first-time sync. Requesting ALL task notifications from server...`)
                    callback(null)
                }
            },
            function(callback) {
                // Delete tasks that may have been marked for local deletion
                if (objectsToPermanentlyDelete.length > 0) {
                    async.eachSeries(objectsToPermanentlyDelete,
                        function(curObj, eachCallback) {
                            const deleteParams = {
                                notificationid: curObj.notificationid,
                                userid: userid
                            }
                            TCTaskNotificationService.deleteNotification(deleteParams, function(err, result) {
                                eachCallback(err)
                            })
                        },
                        function(err) {
                            callback(err)
                        }
                    )
                } else {
                    callback(null)
                }
            },
            function(callback) {
                if (addObjects.length > 0) {
                    addString = JSON.stringify(addObjects)
                }

                if (modifyObjects.length > 0) {
                    modString = JSON.stringify(modifyObjects)
                }

                if (deleteObjects.length > 0) {
                    delString = JSON.stringify(deleteObjects)
                }

                const syncParams = {
                    method: 'syncNotifications'
                }
                if (addString) {
logger.debug(`syncNotifications: 30: ${addString}`)
                    syncParams['addNotifications'] = addString
                }
                if (modString) {
logger.debug(`syncNotifications: 31: ${modString}`)
                    syncParams['updateNotifications'] = modString
                }
                if (delString) {
logger.debug(`syncNotifications: 32: ${delString}`)
                    syncParams['deleteNotifications'] = delString
                }
                if (timestamps) {
                    Object.keys(timestamps).forEach((serverListId) => {
logger.debug(`syncNotifications: 34: ${serverListId}`)
                        syncParams[serverListId] = timestamps[serverListId]
                    })
                }
logger.debug(`Calling 'syncNotifications' with:\n${JSON.stringify(syncParams)}`)

                TCSyncService.makeSyncRequest(syncParams, function(err, syncResponse) {
                    if (err) {
logger.debug(`syncNotifications: 37`)
                        callback(err)
                    } else {
                        response = syncResponse
                        // There's a LOT to do. Time for another async.waterfall
                        const addedResults = response.results && Object.keys(response.results).length > 0 ? response.results.added : null
                        const updatedResults = response.results && Object.keys(response.results).length > 0 ? response.results.updated : null
                        const deletedResults = response.results && Object.keys(response.results).length > 0 ? response.results.deleted : null

                        async.waterfall([
                            function(waterfallCallback) {
                                if (addedResults) {
logger.debug(`syncNotifications: 37.1`)
                                    const addedParams = {
                                        userid: userid,
                                        addedNotifications: addedResults
                                    }
                                    TCSyncService.processAddedNotifications(addedParams, function(addedErr, addedResult) {
                                        if (addedErr) {
logger.debug(`syncNotifications: 37.2`)
                                            waterfallCallback(addedErr)
                                        } else {
logger.debug(`syncNotifications: 37.3`)
                                            // if (addedResult.syncUpAgain != undefined) {
                                            //     syncUpAgain = addedResult.syncUpAgain
                                            // }
                                            waterfallCallback(null)
                                        }
                                    })
                                } else {
logger.debug(`syncNotifications: 37.4`)
                                    // Skip added notifications
                                    waterfallCallback(null)
                                }
                            },
                            function(waterfallCallback) {
                                if (updatedResults) {
logger.debug(`syncNotifications: 37.5`)
                                    const updatedParams = {
                                        userid: userid,
                                        updatedNotifications: updatedResults
                                    }
                                    TCSyncService.processUpdatedNotifications(updatedParams, function(updatedErr, updatedResult) {
                                        if (updatedErr) {
logger.debug(`syncNotifications: 37.6`)
                                            waterfallCallback(updatedErr)
                                        } else {
logger.debug(`syncNotifications: 37.7`)
                                            // if (updatedResult.syncUpAgain != undefined) {
                                            //     syncUpAgain = updatedResult.syncUpAgain
                                            // }
                                            waterfallCallback(null)
                                        }
                                    })
                                } else {
logger.debug(`syncNotifications: 37.8`)
                                    // Skip updated notifications
                                    waterfallCallback(null)
                                }
                            },
                            function(waterfallCallback) {
                                if (deletedResults) {
logger.debug(`syncNotifications: 37.9`)
                                    const deletedParams = {
                                        userid: userid,
                                        deletedNotifications: deletedResults
                                    }
                                    TCSyncService.processDeletedNotifications(deletedParams, function(deletedErr, deletedResult) {
                                        if (deletedErr) {
logger.debug(`syncNotifications: 37.10`)
                                            waterfallCallback(deletedErr)
                                        } else {
logger.debug(`syncNotifications: 37.11`)
                                            waterfallCallback(null)
                                        }
                                    })
                                } else {
logger.debug(`syncNotifications: 37.12`)
                                    // Skip deleted notifications
                                    waterfallCallback(null)
                                }
                            },
                            function(waterfallCallback) {
                                // Reset this now that we're in the loop
logger.debug(`syncNotifications: 37.13`)
                                actionsReceivedFromServer = 0
                                if (response.actions && Object.keys(response.actions).length > 0) {
logger.debug(`syncNotifications: 37.14`)
                                    const actionParams = {
                                        userid: userid,
                                        actions: response.actions
                                    }
                                    TCSyncService.processNotificationActions(actionParams, function(actionErr, result) {
                                        if (actionErr) {
logger.debug(`syncNotifications: 37.15`)
                                            waterfallCallback(actionErr)
                                        } else {
logger.debug(`syncNotifications: 37.16`)
                                            actionsReceivedFromServer = result.actionsReceivedFromServer
                                            waterfallCallback(null)
                                        }
                                    })
                                } else {
logger.debug(`syncNotifications: 37.17`)
                                    logger.debug(`No additional notification changes found on the Todo Cloud server.`)
                                    waterfallCallback(null)
                                }
                            }
                        ],
                        function(err) {
                            if (err) {
logger.debug(`syncNotifications: 39`)
                                callback(err)
                            } else {
logger.debug(`syncNotifications: 40`)
                                callback(null)
                            }
                        })
                    }
                })
            },
            function(callback) {
                if (!response) {
logger.debug(`syncNotifications: 43`)
                    callback(null)
                } else {
logger.debug(`syncNotifications: 44`)
                    const notificationTimeStamps = (response.allnotificationtimestamps != undefined && Object.keys(response.allnotificationtimestamps).length > 0) ? response.allnotificationtimestamps : null
                    if (notificationTimeStamps) {
logger.debug(`syncNotifications: 45`)
                        // Following the convetion we used in iOS, we only update this if we get some back
                        _allNotificationTimeStamps = notificationTimeStamps ? notificationTimeStamps : {}
                        const settingParams = {}
                        settingParams[constants.SettingsAllNotificationTimeStamps] = _allNotificationTimeStamps
                        db.setSettings(settingParams, function(err, settingResult) {
logger.debug(`syncNotifications: 46`)
                            callback(err)
                        })
                    } else {
logger.debug(`syncNotifications: 46.1`)
                        callback(null)
                    }

                }
            },
        ],
        function(err) {
            if (err) {
logger.debug(`syncNotifications: 47`)
                completion(err)
            } else {
logger.debug(`syncNotifications: 48`)
                if (isFirstSync) {
logger.debug(`syncNotifications: 49`)
                    // Now that we've synced the first time, we need to sync tasks again
                    // to push any potential changes back up to the server that happened
                    // during the original sync.
                    const newSyncParams = {
                        timestamps: _allNotificationTimeStamps,
                        isFirstSync: false,
                        userid: userid
                    }
                    TCSyncService.syncNotifications(newSyncParams, function(newSyncErr, newSyncResult) {
                        if (newSyncErr) {
logger.debug(`syncNotifications: 50`)
                            completion(newSyncErr)
                        } else {
logger.debug(`syncNotifications: 51`)
                            completion(null, true)
                        }
                    })
                } else {
logger.debug(`syncNotifications: 52`)
                    completion(null, true)
                }
            }
        })
    }

    static processAddedNotifications(params, completion) {
logger.debug(`processAddedNotifications: 1`)
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processAddedNotifications().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing userid.`))))
            return
        }

        const addedNotifications = params.addedNotifications ? params.addedNotifications : null
        if (!addedNotifications) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing addedNotifications.`))))
            return
        }

        async.eachSeries(addedNotifications,
            function(addedObj, eachCallback) {
                const errorCode = addedObj.errorcode
                if (errorCode) {
logger.debug(`processAddedNotifications: 2`)
                    let resolvedError = false
                    let taskName = null
                    let objid = addedObj.tmpnotificationid
                    let updatedObj = null
                    // let showSyncError = true
                    async.waterfall([
                        function(waterfallCallback) {
logger.debug(`processAddedNotifications: 3`)
                            if (objid) {
logger.debug(`processAddedNotifications: 4`)
                                const getParams = {
                                    userid: userid,
                                    notificationid: objid,
                                    preauthorized: true
                                }
                                TCTaskNotificationService.getNotification(getParams, function(getErr, aNotification) {
                                    if (getErr) {
logger.debug(`processAddedNotifications: 5`)
                                        waterfallCallback(getErr)
                                    } else {
logger.debug(`processAddedNotifications: 6`)
                                        updatedObj = aNotification
                                        if (updatedObj && updatedObj.taskid) {
logger.debug(`processAddedNotifications: 7`)
                                            const getTaskParams = {
                                                userid: userid,
                                                taskid: updatedObj.taskid,
                                                preauthorized: true
                                            }
                                            TCTaskService.getTask(getTaskParams, function(getTaskErr, task) {
                                                if (getTaskErr) {
logger.debug(`processAddedNotifications: 9`)
                                                    waterfallCallback(getTaskErr)
                                                } else {
logger.debug(`processAddedNotifications: 10`)
                                                    if (task) {
logger.debug(`processAddedNotifications: 11`)
                                                        taskName = task.name
                                                        // Everything good, no need to repair
                                                        waterfallCallback(null)
                                                    } else {
logger.debug(`processAddedNotifications: 12`)
                                                        // No task found.
                                                        waterfallCallback(null)
                                                    }
                                                }
                                            })
                                        } else {
logger.debug(`processAddedNotifications: 16`)
                                            waterfallCallback(null)
                                        }
                                    }
                                })
                            } else {
logger.debug(`processAddedNotifications: 17`)
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            if (taskName) {
logger.debug(`processAddedNotifications: 18`)
                                logger.debug(`Adding notification for task '${taskName}' failed with error code: ${errorCode}`)
                            } else {
logger.debug(`processAddedNotifications: 19`)
                                logger.debug(`Adding notification for task failed with error code: ${errorCode}`)
                            }

                            waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Error processing added notification items.`))))
                        }
                    ],
                    function(waterfallErr) {
logger.debug(`processAddedNotifications: 22`)
                        eachCallback(waterfallErr)
                    })
                } else {
logger.debug(`processAddedNotifications: 23 - ${JSON.stringify(addedObj)}`)
                    const syncid = addedObj.notificationid
                    const tmpObjId = addedObj.tmpnotificationid
                    if (tmpObjId) {
logger.debug(`processAddedNotifications: 24`)
                        const getParams = {
                            userid: userid,
                            notificationid: tmpObjId,
                            preauthorized: true
                        }
                        TCTaskNotificationService.getNotification(getParams, function(getErr, aNotification) {
                            if (getErr) {
logger.debug(`processAddedNotifications: 25`)
                                eachCallback(getErr)
                            } else if (aNotification) {
logger.debug(`processAddedNotifications: 26`)
                                aNotification.sync_id = syncid
                                aNotification.userid = userid
                                aNotification.dirty = false
                                aNotification.isSyncService = true
                                TCTaskNotificationService.updateNotification(aNotification, function(updateErr, updateResult) {
                                    if (updateErr) {
logger.debug(`processAddedNotifications: 27.1: ${updateErr}`)
                                        eachCallback(updateErr)
                                    } else {
logger.debug(`processAddedNotifications: 27.2: ${JSON.stringify(updateResult)}`)
                                        eachCallback(null)
                                    }
                                })
                            }
                        })
                    } else {
logger.debug(`processAddedNotifications: 28`)
                        eachCallback(null)
                    }
                }
            },
            function(err) {
                if (err) {
logger.debug(`processAddedNotifications: 29`)
                    completion(err)
                } else {
                    completion(null, {})
                }
            }
        )
    }

    static processUpdatedNotifications(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processUpdatedNotifications().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processUpdatedNotifications() missing userid.`))))
            return
        }

        const updatedNotifications = params.updatedNotifications ? params.updatedNotifications : null
        if (!updatedNotifications) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processUpdatedNotifications() missing updatedNotifications.`))))
            return
        }

        async.eachSeries(updatedNotifications,
            function(updatedObj, eachCallback) {
                const errorCode = updatedObj.errorcode
                if (errorCode) {
                    let updatedNotification = null
                    let taskName = null
                    let serverId = updatedObj.notificationid
                    async.waterfall([
                        function(waterfallCallback) {
                            if (serverId) {
                                const getParams = {
                                    userid: userid,
                                    syncid: serverId
                                }
                                TCTaskNotificationService.getNotificationForSyncId(getParams, function(getErr, localNotification) {
                                    if (getErr) {
                                        waterfallCallback(getErr)
                                    } else {
                                        updatedNotification = localNotification
                                        const getTaskParams = {
                                            userid: userid,
                                            taskid: localNotification.taskid,
                                            preauthorized: true
                                        }
                                        TCTaskService.getTask(getTaskParams, function(getTaskErr, localTask) {
                                            if (getTaskErr) {
                                                waterfallCallback(getTaskErr)
                                            } else {
                                                taskName = localTask.name
                                                waterfallCallback(null)
                                            }
                                        })
                                    }
                                })
                            } else {
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            if (taskName) {
                                logger.debug(`Updating notification for task '${taskName}' failed during sync with error code: ${errorCode}`)
                                waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Updating notification for task '${taskName}' failed during sync with error code: ${errorCode}`))))
                            } else {
                                logger.debug(`Updating a notification failed during sync with error code: ${errorCode}`)
                                waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Updating a notification failed during sync with error code: ${errorCode}`))))
                            }
                        }
                    ],
                    function(waterfallErr) {
                        eachCallback(waterfallErr)
                    })
                } else {
                    const syncId = updatedObj.notificationid
                    if (syncId) {
                        const getParams = {
                            userid: userid,
                            syncid: syncId
                        }
                        TCTaskNotificationService.getNotificationForSyncId(getParams, function(getErr, localNotification) {
                            if (getErr) {
                                eachCallback(getErr)
                            } else {
                                if (localNotification) {
                                    localNotification.userid = userid
                                    localNotification.dirty = false
                                    localNotification.isSyncService = true
                                    TCTaskNotificationService.updateNotification(localNotification, function(updateErr, updateResult) {
                                        eachCallback(updateErr)
                                    })
                                } else {
                                    // Not sure how this would ever happen because the only
                                    // way that we are processing in here is because we sent
                                    // a notification to the server in the first place.
                                    eachCallback(null)
                                }
                            }
                        })
                    } else {
                        eachCallback(null)
                    }
                }
            },
            function(err) {
                if (err) {
                    completion(err)
                } else {
                    completion(null, {})
                }
            }
        )
    }

    static processDeletedNotifications(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processDeletedNotifications().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processDeletedNotifications() missing userid.`))))
            return
        }

        const deletedNotifications = params.deletedNotifications ? params.deletedNotifications : null
        if (!deletedNotifications) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processDeletedNotifications() missing deletedNotifications.`))))
            return
        }

        async.eachSeries(deletedNotifications,
            function(deletedObj, eachCallback) {
                const errorCode = deletedObj.errorcode
                if (errorCode) {
                    let taskName = null
                    let serverId = deletedObj.notificationid
                    async.waterfall([
                        function(waterfallCallback) {
                            if (serverId) {
                                const getParams = {
                                    userid: userid,
                                    syncid: serverId
                                }
                                TCTaskNotificationService.getNotificationForSyncId(getParams, function(getErr, localNotification) {
                                    if (getErr) {
                                        waterfallCallback(getErr)
                                    } else {
                                        const getTaskParams = {
                                            userid: userid,
                                            taskid: localNotification.taskid,
                                            preauthorized: true
                                        }
                                        TCTaskService.getTask(getTaskParams, function(getTaskErr, localTask) {
                                            if (getTaskErr) {
                                                waterfallCallback(getTaskErr)
                                            } else {
                                                taskName = localTask.name
                                                waterfallCallback(null)
                                            }
                                        })
                                    }
                                })
                            } else {
                                waterfallCallback(null)
                            }
                        },
                        function(waterfallCallback) {
                            if (taskName) {
                                logger.debug(`Deleting notification for task '${taskName}' failed during sync with error code: ${errorCode}`)
                                waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Deleting notification for task '${taskName}' failed during sync with error code: ${errorCode}`))))
                            } else {
                                logger.debug(`Deleting a task notification failed during sync with error code: ${errorCode}`)
                                waterfallCallback(new Error(JSON.stringify(errors.customError(errors.syncError, `Deleting a task notification failed during sync with error code: ${errorCode}`))))
                            }
                        }
                    ],
                    function(waterfallErr) {
                        eachCallback(waterfallErr)
                    })
                } else {
                    const syncid = deletedObj.notificationid
                    if (syncid) {
                        const getParams = {
                            userid: userid,
                            syncid: syncid
                        }
                        TCTaskNotificationService.getNotificationForSyncId(getParams, function(getErr, localNotification) {
                            if (getErr) {
                                eachCallback(getErr)
                            } else {
                                if (localNotification) {
                                    const updateParams = {
                                        userid: userid,
                                        notificationid: localNotification.notificationid,
                                        deleted: true,
                                        dirty: false,
                                        isSyncService: true
                                    }
                                    TCTaskNotificationService.updateNotification(updateParams, function(updateErr, result) {
                                        eachCallback(updateErr)
                                    })
                                } else {
                                    eachCallback(null)
                                }
                            }
                        })
                    } else {
                        eachCallback(null)
                    }
                }
            },
            function(err) {
                if (err) {
                    completion(err)
                } else {
                    completion(null, true)
                }
            }
        )
    }

    static processNotificationActions(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.processNotificationActions().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.processNotificationActions() Missing userid.`))))
            return
        }

        const actions = params.actions ? params.actions : null
        if (!actions) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCTaskService.processNotificationActions() Missing actions.`))))
            return
        }
logger.debug(`processNotificationActions() ${JSON.stringify(params)}`)

        let actionsReceived = 0
        let syncHadServerChanges = false
        let syncUpAgain = false

        const updateActions = actions.update != undefined && actions.update.length > 0 ? actions.update : null
        const deleteActions = actions.delete != undefined && actions.delete.length > 0 ? actions.delete : null

        async.waterfall([
            function(callback) {
                if (updateActions) {
                    actionsReceived = actionsReceived + updateActions.length
                    syncHadServerChanges = true
                    logger.debug(`Processing ${updateActions.length} notifications that were added/modified on Todo Cloud...`)
                    async.eachSeries(updateActions,
                        function(updatedNotification, eachCallback) {
logger.debug(`processNotificationActions() 0`)
                            let oldParent = null
                            const syncid = updatedNotification.notificationid
                            const serverTaskId = updatedNotification.taskid
                            let taskForNotification = null
                            if (syncid && serverTaskId) {
                                async.waterfall([
                                    function(waterfallCallback) {
                                        // Make sure we have a local task
                                        const getParams = {
                                            userid: userid,
                                            syncid: serverTaskId,
                                            preauthorized: true
                                        }
                                        TCTaskService.getTaskForSyncId(getParams, function(getErr, localTask) {
                                            if (getErr) {
                                                waterfallCallback(getErr)
                                            } else {
                                                if (localTask) {
                                                    taskForNotification = localTask
                                                } else {
                                                    logger.debug(`Task for notification could not be found.`)
                                                }
                                                waterfallCallback(null)
                                            }
                                        })
                                    },
                                    function(waterfallCallback) {
logger.debug(`processNotificationActions() 1`)
                                        const getParams = {
                                            userid: userid,
                                            syncid: syncid
                                        }
                                        TCTaskNotificationService.getNotificationForSyncId(getParams, function(err, notificationToUpdate) {
                                            if (err) {
logger.debug(`processNotificationActions() 2`)
                                                waterfallCallback(err)
                                            } else {
logger.debug(`processNotificationActions() 3`)
                                                waterfallCallback(null, notificationToUpdate)
                                            }
                                        })
                                    },
                                    function(notificationToUpdate, waterfallCallback) {
logger.debug(`processNotificationActions() 4`)
                                        if (notificationToUpdate) {
                                            const updateParams = {
                                                userid: userid,
                                                notification: notificationToUpdate,
                                                values: updatedNotification
                                            }
                                            TCSyncService.updateNotificationWithValues(updateParams, function(updateErr, updateResult) {
                                                if (updateErr) {
                                                    waterfallCallback(updateErr)
                                                } else {
                                                    notificationToUpdate.userid = userid
                                                    notificationToUpdate.dirty = false
                                                    notificationToUpdate.isSyncService = true
                                                    TCTaskNotificationService.updateNotification(notificationToUpdate, function(notificationUpdateErr, notificationUpdateResult) {
                                                        if (notificationUpdateErr) {
                                                            waterfallCallback(notificationUpdateErr)
                                                        } else {
                                                            waterfallCallback(null)
                                                        }
                                                    })
                                                }
                                            })
                                        } else {
logger.debug(`processNotificationActions() 19`)
                                            // If we didn't find a notification, go and see if there is a non-synced
                                            // notification on this task that matches the trigger date.
                                            let foundNotification = null

                                            const triggerDate = updatedNotification.triggerdate
                                            if (triggerDate && parseInt(triggerDate) > 0 && taskForNotification) {
                                                async.waterfall([
                                                    function(innerWaterfallCallback) {
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
                                                    },
                                                    function(foundNotification, innerWaterfallCallback) {
                                                        if (foundNotification) {
                                                            logger.debug(`Todo Cloud found a non-synced task notification that matches a new server notification on task: ${taskForNotification.name}`)
                                                            const updateParams = {
                                                                userid: userid,
                                                                notification: notificationToUpdate,
                                                                values: updatedNotification
                                                            }
                                                            TCSyncService.updateNotificationWithValues(updateParams, function(updateErr, updateResult) {
                                                                if (updateErr) {
                                                                    innerWaterfallCallback(updateErr)
                                                                } else {
                                                                    foundNotification.userid = userid
                                                                    foundNotification.dirty = false
                                                                    foundNotification.isSyncService = true
                                                                    TCTaskNotificationService.updateNotification(foundNotification, function(anErr, aResult) {
                                                                        innerWaterfallCallback(anErr)
                                                                    })
                                                                }
                                                            })
                                                        } else {
                                                            const notificationToAdd = new TCTaskNotification()
                                                            const updateParams = {
                                                                userid: userid,
                                                                notification: notificationToAdd,
                                                                values: updatedNotification
                                                            }
                                                            TCSyncService.updateNotificationWithValues(updateParams, function(updateErr, updateResult) {
                                                                if (updateErr) {
                                                                    innerWaterfallCallback(updateErr)
                                                                } else {
                                                                    notificationToAdd.userid = userid
                                                                    notificationToAdd.dirty = false
                                                                    notificationToAdd.isSyncService = true
                                                                    notificationToAdd.taskid = taskForNotification.taskid
                                                                    TCTaskNotificationService.createNotification(notificationToAdd, function(createErr, createResult) {
                                                                        innerWaterfallCallback(createErr)
                                                                    })
                                                                }
                                                            })
                                                        }
                                                    }
                                                ],
                                                function(innerWaterfallErr) {
                                                    waterfallCallback(innerWaterfallErr)
                                                })
                                            } else {
                                                // Nothing to do since there's no trigger date anyway (shouldn't ever get here)
                                                waterfallCallback(null)
                                            }
                                        }
                                    }
                                ],
                                function(waterfallErr) {
logger.debug(`processNotificationActions() 35`)
                                    eachCallback(waterfallErr)
                                })
                            } else {
logger.debug(`processNotificationActions() 41`)
                                eachCallback(null)
                            }
                        },
                        function(eachErr) {
logger.debug(`processNotificationActions() 42`)
                            callback(eachErr)
                        })
                } else {
logger.debug(`processNotificationActions() 43`)
                    callback(null)
                }
            },
            function(callback) {
logger.debug(`processNotificationActions() 44`)
                if (deleteActions) {
logger.debug(`processNotificationActions() 45`)
                    actionsReceived = actionsReceived + deleteActions.length

                    logger.debug(`Deleting ${deleteActions.length} notifications that were deleted on Todo Cloud.`)
                    async.eachSeries(deleteActions,
                        function(deletedNotification, eachCallback) {
logger.debug(`processNotificationActions() 46`)
                            syncHadServerChanges = true

                            const serverTaskId = deletedNotification.notificationid
                            if (serverTaskId) {
logger.debug(`processNotificationActions() 47`)
                                const getParams = {
                                    userid: userid,
                                    syncid: serverTaskId
                                }
                                TCTaskNotificationService.getNotificationForSyncId(getParams, function(getErr, existingNotification) {
                                    if (getErr) {
logger.debug(`processNotificationActions() 48`)
                                        callback(getErr)
                                    } else {
logger.debug(`processNotificationActions() 49`)
                                        if (existingNotification) {
                                            const delParams = {
                                                userid: userid,
                                                notificationid: existingNotification.notificationid,
                                                preauthorized: true,
                                                dirty: false,
                                                isSyncService: true
                                            }
                                            TCTaskNotificationService.deleteNotification(delParams, function(delErr, delResult) {
logger.debug(`processNotificationActions() 50`)
                                                callback(delErr)
                                            })
                                        } else {
                                            // How would this ever happen? In any case, since it's a deleted
                                            // taskito and the client doesn't know about it, we can safely
                                            // ignore it.
                                            logger.debug(`Todo Cloud sent a notification to delete that was not found locally: ${serverTaskId}`)
                                            callback(null)
                                        }
                                    }
                                })
                            } else {
logger.debug(`processNotificationActions() 51`)
                                callback(null)
                            }
                        },
                        function(eachErr) {
logger.debug(`processNotificationActions() 52`)
                            callback(eachErr)
                        }
                    )
                } else {
logger.debug(`processNotificationActions() 53`)
                    callback(null)
                }
            }
        ],
        function(err) {
logger.debug(`processNotificationActions() 54`)
            if (err) {
logger.debug(`processNotificationActions() 55`)
                completion(err)
            } else {
logger.debug(`processNotificationActions() 56`)
                const results = {
                    actionsReceivedFromServer: actionsReceived,
                    syncHadServerChanges: syncHadServerChanges
                }
                if (syncUpAgain) {
logger.debug(`processNotificationActions() 57`)
                    results["syncUpAgain"] = true
                }

                completion(null, results)
            }
        })
    }

    // Purpose of this is to update the values in the taskito and return
    // whether any changes were made
    static updateNotificationWithValues(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.updateNotificationWithValues().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        const notification = params.notification ? params.notification : null
        const values = params.values ? params.values : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.updateNotificationWithValues() Missing userid.`))))
            return
        }
        if (!notification) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.updateNotificationWithValues() Missing notification.`))))
            return
        }
        if (!values) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.updateNotificationWithValues() Missing values.`))))
            return
        }

        notification.sync_id = values["notificationid"] ? values["notificationid"] : null
        notification.sound_name = values["sound_name"] ? values["sound_name"] : "none"
        notification.triggerdate = values.triggerdate ? values.triggerdate : 0
        notification.triggeroffset = values.triggeroffset ? values.triggeroffset : 0

        const taskSyncId = values.taskid
        if (taskSyncId) {
            const getParams = {
                userid: userid,
                syncid: taskSyncId,
                preauthorized: true
            }
            TCTaskService.getTaskForSyncId(getParams, function(getErr, localTask) {
                if (getErr) {
                    completion(getErr)
                } else {
                    notification.taskid = localTask.taskid
                    completion(null, true)
                }
            })
        } else {
            completion(null, true)
        }
    }

    static jsonForTaskNotification(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.jsonForTaskNotification().`))))
            return
        }

        const userid = params.userid ? params.userid : null
        const notification = params.notification ? params.notification : null
        const isDelete = params.isDelete != undefined ? params.isDelete : false
        const isAdd = params.isAdd != undefined ? params.isAdd : false

logger.debug(`jsonForTaskNotification: ${JSON.stringify(notification)}`)

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.jsonForTaskNotification() Missing userid.`))))
            return
        }
        if (!notification) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCSyncService.jsonForTaskNotification() Missing notification.`))))
            return
        }

        if (isDelete && notification.sync_id && notification.sync_id.length > 0) {
            completion(null, {notificationid: notification.sync_id})
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        let notifyTask = null

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
                // Get the task associated with the notification
                const getParams = {
                    userid: userid,
                    taskid: notification.taskid,
                    dbConnection: connection
                }
                TCTaskService.getTask(getParams, function(err, foundTask) {
                    if (err) {
                        callback(err, connection)
                    } else {
                        notifyTask = foundTask
                        callback(null, connection)
                    }
                })
            },
            function(connection, callback) {
                const notificationDictionary = {}

                if (isAdd) {
                    notificationDictionary["tmpnotificationid"] = notification.notificationid
                } else if (notification.sync_id && notification.sync_id.length > 0){
                    notificationDictionary["notificationid"] = notification.sync_id
                }

                notificationDictionary["taskid"] ="0"

                if (notifyTask && notifyTask.sync_id) {
                    notificationDictionary["taskid"] = notifyTask.sync_id
                }

                notificationDictionary["sound_name"] = notification.sound_name ? notification.sound_name : "none"
                notificationDictionary["triggerdate"] = notification.triggerdate ? notification.triggerdate.toString() : ""
                notificationDictionary["triggeroffset"] = notification.triggeroffset ? notification.triggeroffset.toString() : ""
                
                callback(null, connection, notificationDictionary)
            }
        ],
        function(err, connection, notificationDictionary) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not construct a JSON representation of a task notification (${notification.name - notification.notificationid}).`))))
            } else {
                completion(null, notificationDictionary)
            }
        })
    }
}

module.exports = TCSyncService

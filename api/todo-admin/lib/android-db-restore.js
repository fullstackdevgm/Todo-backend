

// Third party libraries
const async         = require('async')
const chalk         = require('chalk')
const CLI           = require('clui')
const Database      = require('better-sqlite3')
const inquirer      = require('inquirer')
const Spinner       = CLI.Spinner
// const Progress      = CLI.Progress;

// Built-in Node.js libraries
const fs            = require('fs')
const os            = require('os')
const path          = require('path')

// Rudimentary way of mapping local identifiers with
// server identifiers (sync_id).
var serverIdMap     = {}

const InboxListId   = 'INBOX'
const MaxTasksToCreateSimultaneously = 20

module.exports = {
    dbRestoreAction : function(todoApi, completion) {
        var isImpersonating = false
        var db = null
        async.waterfall([
            function(callback) {
                promptForDatabaseFile(function(err, dbFilePath) {
                    if (err) {
                        callback(err)
                    } else {
                        console.log(
                            chalk.default.yellowBright(
                                `Selected file: ${JSON.stringify(dbFilePath)}`
                            )
                        )
                        if (dbFilePath == 'exit') {
                            callback(new Error('exit'))
                        } else {
                            callback(null, dbFilePath)
                        }
                    }
                })
            },
            function(dbFile, callback) {
                promptForCustomerUsername(function(err, customerUsername) {
                    if (err) {
                        callback(err)
                    } else {
                        console.log(
                            chalk.default.yellowBright(
                                `Customer username: ${customerUsername}`
                            )
                        )
                        callback(null, dbFile, customerUsername)
                    }
                })
            },
            function(dbFile, customerUsername, callback) {
                // Attempt to impersonate the customer
                const spinner = new Spinner(`Setting up impersonation: ${customerUsername}`)
                spinner.start()
                todoApi.beginImpersonation(customerUsername, `Restoring an Android Database`, function(err, result) {
                    spinner.stop()
                    if (err) {
                        callback(err)
                    } else {
                        isImpersonating = true
                        console.log(chalk.default.yellowBright(`Impersonation began: ${customerUsername}`))
                        callback(null, dbFile)
                    }
                })
            },
            function(dbFile, callback) {
                // Open up the databse
                db = Database(dbFile, {readonly: true, fileMustExist: true})
                if (!db) {
                    callback(new Error(`Could not open the database: ${dbFile}`))
                } else {
                    callback(null)
                }
            },
            function(callback) {
                // If we reach here, the database has been opened and we are ready to rock and roll!
                restoreDatabase(db, todoApi, function(err, result) {
                    callback(err, result)
                })
            }
//             function(dbFile, callback) {
//                 todoApi.getLists(function(err, lists) {
//                     if (err) {
//                         callback(err)
//                     } else {
// console.log(`LISTS: ${JSON.stringify(lists)}`)
//                         callback(null, true)
//                     }
//                 })
//             }
        ], function(err, success) {
            // Regardless of the success/failure of the function, if we are impersonating a
            // customer account, make sure to forget the impersonation token.
            if (isImpersonating) {
                todoApi.endImpersonation()
            }

            if (db) {
                db.close()
            }

            if (err) {
                if (err.message == 'exit') {
                    completion(null, false)
                } else {
                    completion(err)
                }
            } else {
                completion(null, success)
            }
        })
    }
}

function promptForDatabaseFile(completion) {
    getDatabaseFiles(function(err, dbFiles) {
        if (err) {
            completion(err)
            return
        }

        console.log(`DB Files: ${JSON.stringify(dbFiles)}`)

        const choices = dbFiles.map(filePath => {
            const fileName = path.basename(filePath)
            return { name: fileName, value: filePath }
        })

        choices.push(new inquirer.Separator())
        choices.push({ name: 'Exit', value: 'exit' })

        const questions = [
            {
                name: 'databaseFile',
                type: 'list',
                message: 'Select the databse file (from ~/Downloads):',
                choices: choices,
            }
        ]

        inquirer.prompt(questions)
        .then(answer => {
            completion(null, answer.databaseFile)
        })
        .catch(reason => {
            console.log(`Error selecting database file: ${reason}`)
            completion(new Error(reason))
        })
    })
}

// Returns an array of database files found in ~/Downloads
function getDatabaseFiles(completion) {
    const homeDir = os.homedir()
    const downloadsDir = path.join(homeDir, "Downloads")

    if (fs.existsSync(downloadsDir) == false) {
        completion(new Error(`Downloads directory does not exist: ${downloadsDir}`))
        return
    }

    fs.readdir(downloadsDir, function(err, files) {
        if (err) {
            completion(err)
        } else {
            // Only return files that are *.db or *.sqlitedb files
            const dbFiles = files.filter(filePath => {
                const lowerCaseFilePath = filePath.toLowerCase()
                return lowerCaseFilePath.endsWith('db') || lowerCaseFilePath.endsWith('sqlitedb')
            }).map(fileName => {
                return path.join(downloadsDir, fileName)
            })
            completion(null, dbFiles)
        }
    })
}

function promptForCustomerUsername(completion) {
    const questions = [
        {
            name: 'username',
            type: 'input',
            message: 'Customer username (email address):',
            validate: function(value) {
                if (value.length) {
                    return true
                } else {
                    return 'Please enter a customer username.'
                }
            }
        }
    ]
    inquirer.prompt(questions)
    .then(answer => {
        completion(null, answer.username)
    })
    .catch(reason => {
        completion(new Error(`Error getting a customer username: ${reason}`))
    })
}

function restoreDatabase(db, todoApi, completion) {
    if (!db || !todoApi) {
        completion(new Error(`restoreDatabase() called with an empty db or todoApi parameter(s).`))
        return
    }

    // let result = taskHasTagAssignment('111db211-a754-4fc6-9a7b-e2fdaef33e13', db)
    // console.log(`has assignment: ${result}`)
    // completion(null, {success: true})
    // return

    async.waterfall([
        function(callback) {
            // Upload Lists
            uploadLists(db, todoApi, function(err, result) {
                callback(err)
            })
        },
        function(callback) {
            // Upload Tasks
            uploadTasks(db, todoApi, function(err, result) {
                callback(err)
            })
        },
        function(callback) {
            // Upload Taskitos
            uploadTaskitos(db, todoApi, function(err, result) {
                callback(err)
            })
        },
        function(callback) {
            // Upload Tags
            uploadTags(db, todoApi, function(err, result) {
                callback(err)
            })
        }
    ], function(err) {
        serverIdMap = {} // allow this to be garbage collected by wiping out what it was before
        if (err) {
            completion(err)
        } else {
            completion(null, {success: true})
        }
    })
}

function uploadLists(db, todoApi, completion) {
    // Basic idea:
    // 1. Read in local lists
    // 2. Create the lists on the server and track their remote ID

    async.waterfall([
        function(callback) {
            // Read in the local lists
            var localLists = db.prepare(`SELECT * FROM lists WHERE deleted = 0`).all()
            // Create a new server list for the local list
            if (!localLists) {
                localLists = Array()
            }
            // Add in an INBOX
            localLists.push({
                list_id: InboxListId,
                name: 'Inbox'
            })
            async.eachSeries(localLists,
            function(localList, eachCallback) {
                const listParams = {
                    name : `RECOVERED-${localList.name}`,
                }
                if (localList.color) {
                    listParams.settings = {
                        color : localList.color
                    }
                }
                const spinner = new Spinner(`Uploading list: ${listParams.name}`)
                spinner.start()
                todoApi.addList(listParams, function(err, serverList) {
                    spinner.stop()
                    if (err) {
                        eachCallback(err)
                    } else {
                        console.log(`List uploaded: ${serverList.list.name}`)
                        // Store the server id
                        serverIdMap[localList.list_id] = serverList.list.listid
                        eachCallback(null)
                    }
                })
            },
            function(err) {
                callback(err)
            })
        }
    ], function(err) {
        if (err) {
            completion(err)
        } else {
            completion(null, {success: true})
        }
    })
}

function uploadTasks(db, todoApi, completion) {
    // Upload tasks per local list
    async.waterfall([
        function(callback) {
            // Read in the local lists
            localLists = db.prepare(`SELECT * FROM lists WHERE deleted = 0`).all()
            async.eachSeries(localLists,
            function(localList, eachCallback) {
                uploadTasksForLocalList(localList.list_id, db, todoApi, function(err, result) {
                    eachCallback(err)
                })
            }, function(err) {
                callback(err)
            })
        },
        function(callback) {
            // Upload tasks for the local inbox (tasks not assigned to any list)
            uploadTasksForLocalList(null, db, todoApi, function(err, result) {
                callback(err)
            })
        }
    ], function(err) {
        if (err) {
            completion(err)
        } else {
            completion(null, {success: true})
        }
    })
}

function uploadTaskitos(db, todoApi, completion) {
    // Upload taskitos
    let localTaskitos = db.prepare(`SELECT * FROM taskitos WHERE deleted = 0 AND parent_id IS NOT NULL`).all()
    async.eachSeries(localTaskitos,
    function(taskito, eachCallback) {
        let serverParentID = serverIdMap[taskito.parent_id]
        if (serverParentID) {
            const params = {
                name: taskito.name,
                parentid: serverParentID,
                sort_order: taskito.sort_order
            }
            const spinner = new Spinner(`Uploading taskito: ${taskito.name}`)
            spinner.start()
            todoApi.addTaskito(params, function(err, serverTaskito) {
                spinner.stop()
                if (err) {
                    eachCallback(err)
                } else {
                    console.log(`Taskito uploaded: ${serverTaskito.name}`)
                    eachCallback(null)
                }
            })
        } else {
            // Couldn't find a previously-uploaded parent, so skip this taskito
            eachCallback(null)
        }
    }, function(err) {
        if (err) {
            completion(err)
        } else {
            completion(null, {success: true})
        }
    })
}

function uploadTags(db, todoApi, completion) {
    var skipLocalTags = false
    async.waterfall([
        function(callback) {
            // Read all our local tags
            let localTags = db.prepare(`SELECT * FROM tags`).all()
            if (!localTags || localTags.length == 0) {
                skipLocalTags = true
                callback(skipLocalTags) // psuedo error (bail out of processing tags)
            } else {
                callback(null, localTags)
            }
        },
        function(localTags, callback) {
            // Read all the existing server tags
            const spinner = new Spinner(`Reading existing server tags...`)
            spinner.start()
            todoApi.getTags(function(err, serverTags) {
                spinner.stop()
                if (err) {
                    callback(err)
                } else {
                    if (!serverTags) {
                        // prevent a crash in the next function by using an empty array
                        serverTags = Array()
                        console.log(`No server tags.`)
                    } else {
                        console.log(`Server tags: ${JSON.stringify(serverTags)}`)
                    }
                    callback(null, localTags, serverTags)
                }
            })
        },
        function(localTags, serverTags, callback) {
            // Match up local tags with server tags and keep
            // track of which tags the server didn't have so 
            // we can create them on the server.
            let tagsToAdd = Array()
            localTags.forEach(localTag => {
                let matchingServerTag = serverTags.find(serverTag => {
                    return localTag.name == serverTag.name
                })
                if (matchingServerTag) {
                    // Save the server ID of the tag so that when we process
                    // tag assignments later, we will use the right tag
                    serverIdMap[localTag.tag_id] = matchingServerTag.tagid
                } else {
                    tagsToAdd.push(localTag)
                }
            })

            async.eachSeries(tagsToAdd,
            function(localTag, eachCallback) {
                const tagParams = {
                    name: localTag.name
                }
                const spinner = new Spinner(`Uploading tag: ${localTag.name}`)
                spinner.start()
                todoApi.addTag(tagParams, function(err, serverTag) {
                    spinner.stop()
                    if (err) {
                        eachCallback(err)
                    } else {
                        console.log(`Tag uploaded: ${serverTag.name}`)
                        serverIdMap[localTag.tag_id] = serverTag.tagid
                        eachCallback(null)
                    }
                })
            }, function(err) {
                callback(err)
            })
        },
        function(callback) {
            // Now that all of the local tags are present on the server,
            // assign the corresponding tasks with the tags.
            let tagAssignments = db.prepare(`SELECT * FROM tag_associations`).all()
            if (!tagAssignments) {
                callback(null) // nothing to do
            } else {
                async.eachSeries(tagAssignments,
                function(tagAssignment, eachCallback) {
                    let serverTagID = serverIdMap[tagAssignment.tag_id]
                    let serverTaskID = serverIdMap[tagAssignment.task_id]
                    if (serverTagID && serverTaskID) {
                        const spinner = new Spinner(`Assigning tag...`)
                        spinner.start()
                        todoApi.assignTag(serverTagID, serverTaskID, function(err, result) {
                            spinner.stop()
                            console.log(`Tag assigned: ${JSON.stringify(result)}`)
                            spinner.stop()
                            eachCallback(err)
                        })
                    } else {
                        eachCallback(null)
                    }
                }, function(err) {
                    callback(err)
                })
            }
        },
    ], function(err) {
        if (err) {
            if (skipLocalTags) {
                // We didn't have local tags to process, so we skipped them
                completion(null, {success: true})
            } else {
                completion(err)
            }
        } else {
            completion(null, {success: true})
        }
    })
}

function taskHasTagAssignment(taskid, db) {
    if (!taskid || !db) {
        return false
    }
    let assignmentCount = db.prepare(`SELECT COUNT(*) FROM tag_associations WHERE task_id = ?`).pluck().get(taskid)
    return assignmentCount > 0
}

function uploadTasksForLocalList(localListId, db, todoApi, completion) {
    var serverTasks = Array()

    var listid = localListId ? localListId : InboxListId
    let listSQL = localListId ? `list_id = '${localListId}'` : `list_id IS NULL`
    async.waterfall([
        function(callback) {
            var offset = 0
            var localTasks = null
            async.doWhilst(function(whilstCallback) {
                // Upload projects
                localTasks = db.prepare(`SELECT * FROM tasks WHERE ${listSQL} AND deleted = 0 AND type = 1 LIMIT ? OFFSET ?`).all([MaxTasksToCreateSimultaneously, offset])
                // var progressBar = new Progress(localTasks.length);
                // var progress = 0
                var tasksToUpload = Array()
                localTasks.forEach(task => {
                    var params = serverParamsForLocalTask(task)
                    params['listid'] = serverIdMap[listid]
                    tasksToUpload.push(params)
                })
                if (tasksToUpload.length > 0) {
                    const spinner = new Spinner(`Uploading ${tasksToUpload.length} projects...`)
                    spinner.start()
                    todoApi.addTasks(tasksToUpload, function(err, results) {
                        spinner.stop()
                        if (err) {
                            whilstCallback(err)
                        } else {
                            // Make sure that we didn't get any errors
                            var hadErrors = false
                            if (results && results.tasks) {
                                results.tasks.forEach(createdTask => {
                                    if (createdTask.error) {
                                        console.log(`Error uploading project: ${createdTask.client_taskid}`)
                                        hadErrors = true
                                    } else {
                                        console.log(`Project uploaded: ${createdTask.task.name}`)
                                        serverIdMap[createdTask.client_taskid] = createdTask.task.taskid
                                    }
                                })
                            }
                            if (hadErrors) {
                                whilstCallback(new Error(`Error while uploading projects.`))
                            } else {
                                whilstCallback(null)
                            }
                        }
                    })
                } else {
                    whilstCallback(null)
                }
            }, function() {
                // Continue on again if the database returned tasks
                if (!localTasks || localTasks.length == 0) {
                    return false
                }

                offset = offset + localTasks.length
                return true
            }, function(err) {
                callback(err)
            })
        },
        function(callback) {
            var offset = 0
            var localTasks = null
            async.doWhilst(function(whilstCallback) {
                // Upload tasks & checklists
                localTasks = db.prepare(`SELECT * FROM tasks WHERE ${listSQL} AND deleted = 0 AND type != 1 LIMIT ? OFFSET ?`).all([MaxTasksToCreateSimultaneously, offset])
                var tasksToUpload = Array()
                localTasks.forEach(task => {
                    var params = serverParamsForLocalTask(task)
                    params['listid'] = serverIdMap[listid]
                    tasksToUpload.push(params)
                })
                if (tasksToUpload.length > 0) {
                    const spinner = new Spinner(`Uploading ${tasksToUpload.length} tasks...`)
                    spinner.start()
                    todoApi.addTasks(tasksToUpload, function(err, results) {
                        spinner.stop()
                        if (err) {
                            whilstCallback(err)
                        } else {
                            // Make sure that we didn't get any errors
                            var hadErrors = false
                            if (results && results.tasks) {
                                results.tasks.forEach(createdTask => {
                                    if (createdTask.error) {
                                        console.log(`Error uploading task: ${createdTask.client_taskid}`)
                                        hadErrors = true
                                    } else {
                                        console.log(`Task uploaded: ${createdTask.task.name}`)
                                        serverIdMap[createdTask.client_taskid] = createdTask.task.taskid
                                    }
                                })
                            }
                            if (hadErrors) {
                                whilstCallback(new Error(`Error while uploading tasks.`))
                            } else {
                                whilstCallback(null)
                            }
                        }
                    })
                } else {
                    whilstCallback(null)
                }
            }, function() {
                // Continue on again if the database returned tasks
                if (!localTasks || localTasks.length == 0) {
                    return false
                }

                offset = offset + localTasks.length
                return true
            }, function(err) {
                callback(err)
            })
        }
    ], function(err) {
        if (err) {
            completion(err)
        } else {
            completion(null, {success: true})
        }
    })
}

function serverParamsForLocalTask(localTask) {
    /*
        parent_id TEXT,
    */
    var params = {}
    if (localTask.name) { params['name'] = localTask.name }
    if (localTask.note) { params['note'] = localTask.note }
    if (localTask.due_date) { params['duedate'] = localTask.due_date }
    if (localTask.completion_date) { params['completiondate'] = localTask.completion_date }
    if (localTask.priority) { params['priority'] = localTask.priority }
    if (localTask.mod_date) { params['timestamp'] = localTask.mod_date }
    if (localTask.start_date) { params['startdate'] = localTask.start_date }
    if (localTask.recurrence) { params['recurrence_type'] = localTask.recurrence }
    if (localTask.advanced_recurrence) { params['advanced_recurrence_string'] = localTask.advanced_recurrence }
    if (localTask.type) { params['task_type'] = localTask.type }
    if (localTask.type_data) { params['type_data'] = localTask.type_data }
    if (localTask.sort_order) { params['sort_order'] = localTask.sort_order }
    if (localTask.project_due_date) { params['project_duedate'] = localTask.project_due_date }
    if (localTask.project_start_duedate) { params['project_startdate'] = localTask.project_start_duedate }
    if (localTask.project_priority) { params['project_priority'] = localTask.project_priority }
    if (localTask.project_starred) { params['project_starred'] = localTask.project_starred }
    if (localTask.starred) { params['starred'] = localTask.starred }
    if (localTask.location_alert) { params['location_alert'] = localTask.location_alert }
    if (localTask.assigned_user_id) { params['assigned_userid'] = localTask.assigned_user_id }

    // Used when bulk creating/uploading tasks
    if (localTask.task_id) { params['client_taskid'] = localTask.task_id }
    
    return params
}
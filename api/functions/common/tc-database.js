'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const anyDB = require('any-db')
const async = require('async')
const fs = require('fs')
const path = require('path')
const sem = require('semaphore')(1)
const errors = require('./errors')

let myPool = null

const createStatements = [
    `CREATE TABLE IF NOT EXISTS tdo_archived_taskitos (`
    + `taskitoid TEXT PRIMARY KEY,`
    + `parentid TEXT,`
    + `name TEXT,`
    + `completiondate DOUBLE DEFAULT 0,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `deleted INTEGER DEFAULT 0,`
    + `sort_order INTEGER DEFAULT 0,`
    + `sync_id TEXT,`
    + `dirty INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_archived_tasks (`
    + `taskid TEXT PRIMARY KEY,`
    + `listid TEXT,`
    + `name TEXT,`
    + `parentid TEXT,`
    + `note TEXT,`
    + `startdate DOUBLE DEFAULT 0,`
    + `duedate DOUBLE DEFAULT 0,`
    + `due_date_has_time INTEGER DEFAULT 0,`
    + `completiondate DOUBLE DEFAULT 0,`
    + `priority INTEGER DEFAULT 0,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `caldavuri TEXT,`
    + `caldavdata BLOB,`
    + `deleted INTEGER DEFAULT 0,`
    + `task_type INTEGER DEFAULT 0,`
    + `type_data TEXT,`
    + `starred INTEGER DEFAULT 0,`
    + `assigned_userid TEXT,`
    + `recurrence_type INTEGER DEFAULT 0,`
    + `advanced_recurrence_string TEXT,`
    + `project_startdate DOUBLE,`
    + `project_duedate DOUBLE,`
    + `project_duedate_has_time INTEGER,`
    + `project_priority INTEGER,`
    + `project_starred INTEGER,`
    + `location_alert TEXT,`
    + `sort_order INTEGER DEFAULT 0,`
    + `sync_id TEXT,`
    + `dirty INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_autorenew_history (`
    + `subscriptionid TEXT PRIMARY KEY,`
    + `renewal_attempts INTEGER DEFAULT 0,`
    + `attempted_time DOUBLE DEFAULT 0,`
    + `failure_reason TEXT)`,

    `CREATE TABLE IF NOT EXISTS tdo_bounced_emails (`
    + `email TEXT PRIMARY KEY,`
    + `bounce_type INTEGER DEFAULT 0,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `bounce_count INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_change_log (`
    + `changeid TEXT PRIMARY KEY,`
    + `listid TEXT,`
    + `userid TEXT,`
    + `itemid TEXT,`
    + `item_name TEXT,`
    + `item_type INTEGER DEFAULT 0,`
    + `change_type INTEGER DEFAULT 0,`
    + `targetid TEXT,`
    + `target_type INTEGER DEFAULT 0,`
    + `mod_date DOUBLE DEFAULT 0,`
    + `serializeid TEXT,`
    + `deleted INTEGER DEFAULT 0,`
    + `change_location INTEGER DEFAULT 0,`
    + `change_data TEXT)`,

    `CREATE TABLE IF NOT EXISTS tdo_comments (`
    + `commentid TEXT PRIMARY KEY,`
    + `userid TEXT,`
    + `itemid TEXT,`
    + `item_type INTEGER DEFAULT 0,`
    + `item_name TEXT,`
    + `text TEXT,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `deleted INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_completed_tasks (`
    + `taskid TEXT PRIMARY KEY,`
    + `listid TEXT,`
    + `name TEXT,`
    + `parentid TEXT,`
    + `note TEXT,`
    + `startdate DOUBLE DEFAULT 0,`
    + `duedate DOUBLE DEFAULT 0,`
    + `due_date_has_time INTEGER DEFAULT 0,`
    + `completiondate DOUBLE DEFAULT 0,`
    + `priority INTEGER DEFAULT 0,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `caldavuri TEXT,`
    + `caldavdata BLOB,`
    + `deleted INTEGER DEFAULT 0,`
    + `task_type INTEGER DEFAULT 0,`
    + `type_data TEXT,`
    + `starred INTEGER DEFAULT 0,`
    + `assigned_userid TEXT,`
    + `recurrence_type INTEGER DEFAULT 0,`
    + `advanced_recurrence_string TEXT,`
    + `project_startdate DOUBLE,`
    + `project_duedate DOUBLE,`
    + `project_duedate_has_time INTEGER,`
    + `project_priority INTEGER,`
    + `project_starred INTEGER,`
    + `location_alert TEXT,`
    + `sort_order INTEGER DEFAULT 0,`
    + `sync_id TEXT,`
    + `dirty INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_context_assignments (`
    + `taskid TEXT PRIMARY KEY,`
    + `userid TEXT,`
    + `contextid TEXT,`
    + `context_assignment_timestamp DOUBLE DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_contexts (`
    + `contextid TEXT PRIMARY KEY,`
    + `userid TEXT,`
    + `name TEXT,`
    + `deleted INTEGER DEFAULT 0,`
    + `context_timestamp DOUBLE DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_deleted_tasks (`
    + `taskid TEXT PRIMARY KEY,`
    + `listid TEXT,`
    + `name TEXT,`
    + `parentid TEXT,`
    + `note TEXT,`
    + `startdate DOUBLE DEFAULT 0,`
    + `duedate DOUBLE DEFAULT 0,`
    + `due_date_has_time INTEGER DEFAULT 0,`
    + `completiondate DOUBLE DEFAULT 0,`
    + `priority INTEGER DEFAULT 0,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `caldavuri TEXT,`
    + `caldavdata BLOB,`
    + `deleted INTEGER DEFAULT 0,`
    + `task_type INTEGER DEFAULT 0,`
    + `type_data TEXT,`
    + `starred INTEGER DEFAULT 0,`
    + `assigned_userid TEXT,`
    + `recurrence_type INTEGER DEFAULT 0,`
    + `advanced_recurrence_string TEXT,`
    + `project_startdate DOUBLE,`
    + `project_duedate DOUBLE,`
    + `project_duedate_has_time INTEGER,`
    + `project_priority INTEGER,`
    + `project_starred INTEGER,`
    + `location_alert TEXT,`
    + `sort_order INTEGER DEFAULT 0,`
    + `sync_id TEXT,`
    + `dirty INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_list_memberships (`
    + `listid TEXT,`
    + `userid TEXT,`
    + `membership_type INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_list_settings (`
    + `listid TEXT PRIMARY KEY,`
    + `userid TEXT,`
    + `color TEXT,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `cdavOrder TEXT,`
    + `cdavColor TEXT,`
    + `sync_filter_tasks INTEGER DEFAULT 0,`
    + `task_notifications INTEGER DEFAULT 0,`
    + `user_notifications INTEGER DEFAULT 0,`
    + `comment_notifications INTEGER DEFAULT 0,`
    + `notify_assigned_only INTEGER,`
    + `hide_dashboard INTEGER,`
    + `icon_name TEXT,`
    + `sort_order INTEGER DEFAULT 0,`
    + `sort_type INTEGER DEFAULT 0,`
    + `default_due_date INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_smart_lists (`
    + `listid TEXT PRIMARY KEY,`
    + `name TEXT,`
    + `userid TEXT,`
    + `color TEXT,`
    + `icon_name TEXT,`
    + `sort_order INTEGER DEFAULT 0,`
    + `json_filter TEXT,`
    + `sort_type INTEGER DEFAULT 0,`
    + `default_due_date INTEGER DEFAULT 0,`
    + `default_list TEXT,`
    + `excluded_list_ids TEXT,`
    + `completed_tasks_filter TEXT,`
    + `deleted INTEGER DEFAULT 0,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `sync_id TEXT,`
    + `dirty INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_lists (`
    + `listid TEXT PRIMARY KEY,`
    + `name TEXT,`
    + `description TEXT,`
    + `creator TEXT,`
    + `cdavUri TEXT,`
    + `cdavTimeZone TEXT,`
    + `deleted INTEGER DEFAULT 0,`
    + `timestamp INTEGER DEFAULT 0,`
    + `task_timestamp INTEGER DEFAULT 0,`
    + `notification_timestamp INTEGER DEFAULT 0,`
    + `taskito_timestamp INTEGER DEFAULT 0,`
    + `sync_id TEXT,`
    + `dirty INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_tag_assignments (`
    + `tagid TEXT,`
    + `taskid TEXT)`,

    `CREATE TABLE IF NOT EXISTS tdo_tags (`
    + `tagid TEXT PRIMARY KEY,`
    + `name TEXT)`,

    `CREATE TABLE IF NOT EXISTS tdo_task_notifications (`
    + `notificationid TEXT PRIMARY KEY,`
    + `taskid TEXT,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `sound_name TEXT,`
    + `deleted INTEGER DEFAULT 0,`
    + `triggerdate DOUBLE DEFAULT 0,`
    + `triggeroffset DOUBLE DEFAULT 0,`
    + `sync_id TEXT,`
    + `dirty INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_taskitos (`
    + `taskitoid TEXT PRIMARY KEY,`
    + `parentid TEXT,`
    + `name TEXT,`
    + `completiondate DOUBLE DEFAULT 0,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `deleted INTEGER DEFAULT 0,`
    + `sort_order INTEGER DEFAULT 0,`
    + `sync_id TEXT,`
    + `dirty INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_tasks (`
    + `taskid TEXT PRIMARY KEY,`
    + `listid TEXT,`
    + `name TEXT,`
    + `parentid TEXT,`
    + `note TEXT,`
    + `startdate DOUBLE DEFAULT 0,`
    + `duedate DOUBLE DEFAULT 0,`
    + `due_date_has_time INTEGER DEFAULT 0,`
    + `completiondate DOUBLE DEFAULT 0,`
    + `priority INTEGER DEFAULT 0,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `caldavuri TEXT,`
    + `caldavdata BLOB,`
    + `deleted INTEGER DEFAULT 0,`
    + `task_type INTEGER DEFAULT 0,`
    + `type_data TEXT,`
    + `starred INTEGER DEFAULT 0,`
    + `assigned_userid TEXT,`
    + `recurrence_type INTEGER DEFAULT 0,`
    + `advanced_recurrence_string TEXT,`
    + `project_startdate DOUBLE,`
    + `project_duedate DOUBLE,`
    + `project_duedate_has_time INTEGER,`
    + `project_priority INTEGER,`
    + `project_starred INTEGER,`
    + `location_alert TEXT,`
    + `sort_order INTEGER DEFAULT 0,`
    + `sync_id TEXT,`
    + `dirty INTEGER DEFAULT 0)`,

    `CREATE TABLE IF NOT EXISTS tdo_user_accounts (`
    + `userid TEXT PRIMARY KEY,`
    + `username TEXT,`
    + `first_name TEXT,`
    + `last_name TEXT,`
    + `creation_timestamp INTEGER DEFAULT 0,`
    + `image_guid TEXT)`,

    `CREATE TABLE IF NOT EXISTS tdo_user_settings (`
    + `userid TEXT PRIMARY KEY,`
    + `timezone TEXT,`
    + `user_inbox TEXT,`
    + `tag_filter_with_and INTEGER DEFAULT 0,`
    + `task_sort_order INTEGER DEFAULT 0,`
    + `start_date_filter INTEGER,`
    + `focus_show_undue_tasks INTEGER DEFAULT 0,`
    + `focus_show_starred_tasks INTEGER DEFAULT 0,`
    + `focus_show_completed_date INTEGER DEFAULT 0,`
    + `focus_hide_task_date INTEGER DEFAULT 2,`
    + `focus_hide_task_priority INTEGER DEFAULT 0,`
    + `focus_list_filter_string TEXT,`
    + `focus_show_subtasks INTEGER,`
    + `focus_ignore_start_dates INTEGER,`
    + `task_creation_email TEXT,`
    + `referral_code TEXT,`
    + `all_list_hide_dashboard INTEGER,`
    + `starred_list_hide_dashboard INTEGER,`
    + `focus_list_hide_dashboard INTEGER,`
    + `all_list_filter_string TEXT,`
    + `default_duedate INTEGER,`
    + `show_overdue_section INTEGER,`
    + `skip_task_date_parsing INTEGER,`
    + `skip_task_priority_parsing INTEGER,`
    + `skip_task_list_parsing INTEGER,`
    + `skip_task_context_parsing INTEGER,`
    + `skip_task_tag_parsing INTEGER,`
    + `skip_task_checklist_parsing INTEGER,`
    + `skip_task_project_parsing INTEGER,`
    + `skip_task_startdate_parsing INTEGER,`
    + `new_feature_flags INTEGER,`
    + `email_notification_defaults INTEGER,`
    + `enable_google_analytics_tracking INTEGER DEFAULT 1,`
    + `default_list TEXT)`,

    `CREATE TABLE IF NOT EXISTS tdo_local_settings (`
    + `name TEXT PRIMARY KEY,`
    + `value TEXT)`,

    `CREATE TABLE IF NOT EXISTS tdo_system_notifications (`
    + `notificationid TEXT PRIMARY KEY,`
    + `message TEXT,`
    + `timestamp DOUBLE DEFAULT 0,`
    + `deleted INTEGER DEFAULT 0,`
    + `learn_more_url TEXT)`,

    `CREATE VIEW IF NOT EXISTS all_tasks_view AS `
    + `SELECT * FROM tdo_tasks `
    + `UNION SELECT * FROM tdo_completed_tasks `
    + `UNION SELECT * FROM tdo_deleted_tasks `
]


function getPool(completion) {
    if (process.env.DB_TYPE == 'sqlite') {
        var dbFilePath = null
        const dbFileName = `TodoDesktop.sqlitedb`
        var appDataPath = process.env.TODO_DATA_DIRECTORY
        if (appDataPath) {
            // Make sure that the parent directory exists. The very first time
            // the app is run, the directory won't exist at all.
            if (fs.existsSync(appDataPath) == false) {
                fs.mkdirSync(appDataPath)
            }

            dbFilePath = path.join(appDataPath, dbFileName)

            // If we're running on Windows, we'll need to make sure that
            // all path separators are forward slashes so the DB URL is
            // formed properly.
            dbFilePath = dbFilePath.replace(/\\/g, '/')
        }

        if (!dbFilePath) {
            // Don't crash the app because the user can still mostly have things
            // work by using an in-memory database.
            dbURL = `sqlite3:///:memory`
        } else {
            // For Windows we'll add "sqlite3:///", otherwise we'll add "sqlite3://"
            dbURL = (dbFilePath.charAt(0) == '/') ? `sqlite3://${dbFilePath}` : `sqlite3:///${dbFilePath}`
        }

        // logger.debug(`DB URL: ${dbURL}`)

        sem.take(function() {
            let connection = anyDB.createConnection(dbURL)

            connection.getConnection = function(callback) {
                callback(null, connection)
            }

            connection.releaseConnection = function(aConnection) {
                aConnection.end()
            }

            completion(null, connection)
        })
    } else {
        if (myPool == null) {
            var dbHost      = process.env.DB_HOST
            var dbUsername  = process.env.DB_USERNAME
            var dbPassword  = process.env.DB_PASSWORD
            var dbName      = process.env.DB_NAME

            var dbURL = `mysql://${dbUsername}:${dbPassword}@${dbHost}/${dbName}`

            if (!dbPassword || dbPassword === undefined) {
                // Allow a proper non-password environment to work for Plano
                dbURL = `mysql://${dbUsername}@${dbHost}/${dbName}`
            }

            // any-db passes query params on to the DB adapter. We need to
            // specify the LATIN1 character set because ALL of our database
            // tables use the latin1 character set and our existing PHP
            // code relies on this. This fixes https://github.com/Appigo/todo-issues/issues/3255
            dbURL += `?charset=LATIN1`

            // logger.debug(`DB URL: ${dbURL}`)
            myPool = anyDB.createPool(dbURL, {
                min: 1,
                max: 10,
                reset: function(conn, done) {
                    // This is called when a connection is given back
                    // to the pool, so make sure that the connection is
                    // ready to use.
                    conn.query('ROLLBACK', done)
                }
            })

            myPool.getConnection = function(callback) {
                myPool.acquire(function(err, connection) {
                    callback(err, connection)
                })
            }

            myPool.releaseConnection = function(aConnection) {
                myPool.release(aConnection)
            }
        }

        completion(null, myPool)
    }
}

function populateDB(completion) {

    let dbPool = null

    async.waterfall([
        function(callback) {
            getPool(function(err, pool) {
                dbPool = pool
                dbPool.getConnection(function(err, connection) {
                    if (err) {
                        callback(err, null)
                    } else {
                        callback(null, connection)
                    }
                })
            })
        },
        function(connection, callback) {
            async.each(createStatements, function(createStatement, eachCallback) {
                connection.query(createStatement, null, function(dbErr, results) {
                    eachCallback(dbErr)
                })
            },
            function(eachErr) {
                callback(eachErr, connection)
            })
        }
    ],
    function(err, connection) {
        if (connection) {
            dbPool.releaseConnection(connection)
            cleanup()
        }

        if (err) {
            completion(err)
        } else {
            completion(null, true)
        }
    })
}

function cleanup() {
    if (process.env.DB_TYPE != 'sqlite') {
        if (!process.env.DB_PRESERVE_POOL) {
            if (myPool) {
                myPool.close()
                myPool = null
            }
        }
    } else {
        // Release the DB Semaphore so that another connection can get in
// logger.debug(`****DB RELEASING the semaphore lock****`)
        sem.leave()
    }
}

function getSetting(settingName, completion) {
    let dbPool = null

    async.waterfall([
        function(callback) {
            getPool(function(err, pool) {
                dbPool = pool
                dbPool.getConnection(function(err, connection) {
                    if (err) {
                        callback(err, null)
                    } else {
                        callback(null, connection)
                    }
                })
            })
        },
        function(connection, callback) {
            const sql = `SELECT value FROM tdo_local_settings WHERE name=?`
            connection.query(sql, [settingName], function(err, results) {
                if (err) {
                    callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `DB error looking for setting (${settingName}): ${err.message}`))), connection)
                } else {
                    if (results && results.rows && results.rows.length > 0) {
                        let value = results.rows[0].value
                        
                        // Before we send this back, let's try to convert it into JSON
                        // because some of our settings are Dictionary Objects & Arrays.
                        if (value) {
                            try {
                                value = JSON.parse(value)
                            } catch (error) {
                                // Ignore the error and allow value to remain the same
                                // as we read it from the DB (probably just a string)
                            }
                        }
                        callback(null, connection, value)
                    } else {
                        // Don't return an error, but just return null for the value
                        callback(null, connection, null)
                    }
                }
            })
        }
    ],
    function(err, connection, value) {
        if (connection) {
            dbPool.releaseConnection(connection)
            cleanup()
        }

        if (err) {
            completion(err, null)
        } else {
            completion(null, value)
        }
    })
}

function setSettings(params, completion) {
    if (!params) {
        completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCDatabase.setSettings().`))))
        return
    }
    let dbPool = null

    const settingNames = Object.keys(params)
    if (!settingNames || settingNames.length == 0) {
        completion(new Error(JSON.stringify(errors.missingParameters)))
        return
    }

    async.waterfall([
        function(callback) {
            getPool(function(err, pool) {
                dbPool = pool
                dbPool.getConnection(function(err, connection) {
                    if (err) {
                        callback(err, null)
                    } else {
                        callback(null, connection)
                    }
                })
            })
        },
        function(connection, callback) {
            let sql = `INSERT OR REPLACE INTO tdo_local_settings (name, value) VALUES `
            const queryParams = []
            settingNames.forEach((settingName, index) => {
                let settingValue = params[settingName]

                // Because we're storing all settings as text, we need to first
                // convert Objects and/or arrays into JSON.
                if (settingValue && (Array.isArray(settingValue) || (Object.keys(settingValue) && Object.keys(settingValue).length > 0))) {
                    // This is something that could be converted to JSON and is not a string
                    settingValue = JSON.stringify(settingValue)
                }

                if (index > 0) {
                    sql += `, `
                }
                sql += `(?,?)`
                queryParams.push(settingName, settingValue)
            })
            logger.debug(`setSettings()\n${sql}\n${JSON.stringify(queryParams)}`)
            connection.query(sql, queryParams, function(err, results) {
                if (err) {
                    callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `DB error saving local settings: ${err.message}`))), connection)
                } else {
                    callback(null, connection)
                }
            })
        }
    ],
    function(err, connection) {
        if (connection) {
            dbPool.releaseConnection(connection)
            cleanup()
        }

        if (err) {
            completion(err, null)
        } else {
            completion(null, true)
        }
    })
}

exports.getPool = getPool
exports.cleanup = cleanup
exports.populateDB = populateDB
exports.getSetting = getSetting
exports.setSettings = setSettings

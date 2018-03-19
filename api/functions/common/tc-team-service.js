'use strict'

const async = require('async')

const db = require('./tc-database')

const constants = require('./constants')
const errors = require('./errors')

class TCTeamService {
    static teamNameForTeamID(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        var teamid = params.teamid && typeof params.teamid == 'string' ? params.teamid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!teamid || teamid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the teamid parameter.`))))
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
                let sql = `SELECT teamname FROM tdo_team_accounts WHERE teamid=?`
                connection.query(sql, [teamid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (results && results.rows && results.rows.length > 0) {
                            let teamName = results.rows[0].teamname
                            callback(null, connection, teamName)
                        } else {
                            callback(new Error(JSON.stringify(errors.teamNotFound)), connection)
                        }
                    }
                })
            }
        ],
        function(err, connection, teamName) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not find a team name for teamid (${teamid}).`))))
            } else {
                completion(null, teamName)
            }
        })
    }

    static isBillingAdminForAnyTeam(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        var userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid || userid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
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
                let isBillingAdmin = false
                const sql = `SELECT COUNT(*) FROM tdo_team_accounts WHERE billing_userid=?`
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, err.message))), connection)
                    } else {
                        if (results.rows) {
                            for (const row of results.rows) {
                                if (row.count > 0) {
                                    isBillingAdmin = true
                                }
                            }
                        }
                        callback(null, isBillingAdmin)
                    }
                })
            }
        ],
        function(err, connection, isBillingAdmin) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, `Could not determine if a userid (${userid}) is a billing admin for any team.`))))
            } else {
                completion(null, isBillingAdmin)
            }
        })
    }
}

module.exports = TCTeamService
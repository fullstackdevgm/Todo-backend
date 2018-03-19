'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')

const db = require('./tc-database')
const begin = require('any-db-transaction')

const constants = require('./constants')
const errors = require('./errors')

const TCListMembership = require('./tc-list-membership')
const TCAccount = require('./tc-account')
const TCChangeLogService = require('./tc-changelog-service')

class TCListMembershipService {
    static createListMembership(params, completion) {
        const userId = params.userid != undefined ? params.userid : null
        const listId = params.listid != undefined ? params.listid : null
        const membershipType = params.membership_type != undefined ? params.membership_type : constants.ListMembershipType.Owner

        if (userId == null || listId == null || membershipType == null) {
            completion(new Error(errors.customError(errors.missingParameters, 'Missing parameters when creating list membership')))
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
                const membership = new TCListMembership()
                membership.configureWithProperties({
                    listid : listId,
                    userid : userId,
                    membership_type : membershipType
                })

                membership.add(connection, function(err, membership) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error adding a new list membership record into the database: ${err.message}`))), connection)
                    } else {
                        callback(null, connection, membership)
                    }
                })
            }
        ],
        function(err, connection, membership) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not create a list membership object for listId ${listId}.`))))
            } else {
                completion(null, membership)
            }
        })
    }

    static getMembership(params, completion) {
        const userId = params.userid != undefined ? params.userid : null
        const listId = params.listid != undefined ? params.listid : null

        if (userId == null || listId == null) {
            completion(new Error(errors.customError(errors.missingParameters, 'Missing parameters when retrieving list membership')))
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
                const membership = new TCListMembership()
                membership.configureWithProperties({
                    listid : listId,
                    userid : userId,
                })

                membership.read(connection, function(err, membership) {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading list membership record from the database: ${err.message}`))), connection)
                    } else {
                        if (!membership) {
                            callback(new Error(JSON.stringify(errors.listMembershipNotFound)), connection)
                        } else {
                            callback(null, connection, membership)
                        }
                    }
                })
            }
        ],
        function(err, connection, membership) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not create a list membership object for listId ${listId}.`))))
            } else {
                completion(null, membership)
            }
        })
    }

    static getMembershipCountForList(params, completion) {
        const listId = params.listid != undefined ? params.listid : null
        const userId = params.userid != undefined ? params.userid : null

        if (userId == null) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing userid when retrieving list membership'))))
        }
        if (listId == null) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, 'Missing listid when retrieving list membership'))))
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
                let sql = `
                    SELECT COUNT(*) as count
                    FROM tdo_list_memberships memberships
                    WHERE
                        memberships.listid = ?
                `

                connection.query(sql, [listId], function(err, results, fields) {
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
                        callback(null, connection, count)
                    }
                })
            }
        ],
        function(err, connection, count) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not create a list membership object for listId ${listId}.`))))
            } else {
                completion(null, count)
            }
        })
    }

    static changeRole(params, completion) {
        // Who requested the authorization
        const userid = params.userid ? params.userid : null 

        // The person whose role is being changed
        const memberid = params.memberid ? params.memberid : null 
        const listid = params.listid ? params.listid : null
        const role = (params.role != undefined && params.role != null) ? params.role : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        if (!memberid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the memberid parameter.`))))
            return
        }

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the listid parameter.`))))
            return
        }

        if (role === null || role === undefined) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the role parameter.`))))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        let oldRole = null

        async.waterfall([
            function(next) {
                if (!dbConnection) {
                    db.getPool(function(err, pool) {
                        dbPool = pool
                        dbPool.getConnection(function(err, connection) {
                            if (err) {
                                next(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                        `Error getting a database connection: ${err.message}`))), null)
                            } else {

                                next(null, connection)
                            }
                        })
                    })
                } else {
                    next(null, dbConnection)
                }
            },
            function(connection, next) {
                const authParams = {
                    listid : listid,
                    userid : userid,
                    membershipType: constants.ListMembershipType.Owner,
                    dbConnection : connection
                }
                TCListMembershipService.isAuthorizedForMembershipType(authParams, (err, authorized) => {
                    if (err ) {
                        const message = `User (${userid}) is not authorized to change roles for list (${listid}).`
                        const errObj = JSON.parse(err.message)
                        next(new Error(JSON.stringify(errors.customError(errObj, message))), connection)
                        return
                    }
                    if (!authorized) {
                        const message = `User (${userid}) is not authorized to change roles for list (${listid}).`
                        next(new Error(JSON.stringify(errors.customError(errors.unauthorizedError, message))), connection)
                        return
                    }

                    next(null, connection)
                })
            },
            function(connection, next) {
                const membership = new TCListMembership()
                membership.userid = memberid
                membership.listid = listid
                membership.read(connection, (err, result) => {
                    if (err) {
                        const message = `Error reading list membership record from the database: ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return 
                    }

                    oldRole = membership.membership_type
                    next(null, connection, result)
                })
            },
            function(connection, membership, next) {
                const params = { listid : listid, dbConnection : connection }
                TCListMembershipService.getOwnerCountForList(params, (err, ownerCount) => {
                    if (err) {
                        next(err, connection)
                        return
                    }
                    if (membership.membership_type == constants.ListMembershipType.Owner && ownerCount <= 1) {
                        next(new Error(JSON.stringify(errors.lastOwner)), connection)
                        return
                    }
                
                    next(null, connection, membership)
                })
            },
            function(connection, membership, next) {
                membership.membership_type = role
                membership.update(connection, (err, result) => {
                    if (err) {
                        const message = `Error updating list membership record: ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    next(null, connection, membership)
                })
            },
            function(connection, membership, next) {
                const TCAccountService = require('./tc-account-service')
                const params = { userid : memberid, dbConnection : connection }
                TCAccountService.displayNameForUserId(params, (err, displayName) => {
                    if (err) {
                        next(err, connection)
                        return
                    }

                    next(null, connection, membership, displayName)
                })
            },
            function(connection, membership, memberName, next) {
                const changeParams = {
                    listid: listid,
                    userid: userid,
                    itemid: memberid,
                    itemName: memberName,
                    itemType: constants.ChangeLogItemType.User,
                    changeType: constants.ChangeLogType.Modify,
                    changeLocation: constants.ChangeLogLocation.API,
                    changeData : JSON.stringify({ "old-role": oldRole, "role":role }),
                    dbConnection: connection
                }
                TCChangeLogService.addChangeLogEntry(changeParams, function(err, result) {
                    if (err) {
                        logger.debug(`Error recording change to changelog during createComment(): ${err}`)
                    }
                    next(null, connection)
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
                        `Could not update user (${memberid}) role for list (${listid}).`))))
                return
            }

            completion(null, { success : true })
        })
    }

    static getOwnerCountForList(params, completion) {
        const listid = params.listid ? params.listid : null

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `TCListService.getOwnerCountForList: Missing the listid parameter.`))))
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
                    dbPool = pool
                    dbPool.getConnection(function(err, connection) {
                        if (err) {
                            next(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error getting a database connection: ${err.message}`))), null)
                        } else {

                            next(null, connection)
                        }
                    })
                })
            },
            function(connection, next) {
                const sql = `
                    SELECT COUNT(*) as count
                    FROM tdo_list_memberships 
                    WHERE listid = ? AND 
                        membership_type = ?
                `

                connection.query(sql, [listid, constants.ListMembershipType.Owner], (err, result) => {
                    if (err) {
                        const message = `Error calculating list owner count from the database: ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    const row = result.rows[0]
                    next(null, connection, row.count)
                })
            }
        ],
        function(err, connection, count) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not get owner count for list: ${listid}.`))))
                return
            }

            completion(null, count)
        })
    }

    // Only allow a user to leave a list if the user is NOT an owner
    // or there are multiple owners of the list.
    static canDeleteMembership(params, completion) {
        const userid = params.userid != undefined ? params.userid : null
        const listid = params.listid != undefined ? params.listid : null

        if (!userid) {
            completion(errors.create(errors.missingParameters, `TCListMembershipService.isOnlyOwner() missing userid.`))
            return
        }
        if (!listid) {
            completion(errors.create(errors.missingParameters, `TCListMembershipService.isOnlyOwner() missing listid.`))
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
                    dbPool = pool
                    dbPool.getConnection(function(err, connection) {
                        if (err) {
                            next(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error getting a database connection: ${err.message}`))), null)
                        } else {

                            next(null, connection)
                        }
                    })
                })
            },
            function(connection, next) {
                const getMembershipParams = {
                    userid: userid,
                    listid: listid,
                    dbConnection: connection
                }
                TCListMembershipService.getMembership(getMembershipParams, function(err, membership) {
                    if (err) {
                        next(err, connection, false)
                    } else {
                        if (membership.membership_type != constants.ListMembershipType.Owner) {
                            next(null, connection, true) // Allow membership to be deleted
                        } else {
                            // Need to check to see if there are multiple owners
                            const ownerCountParams = {
                                listid: listid,
                                dbConnection: connection
                            }
                            TCListMembershipService.getOwnerCountForList(ownerCountParams, function(ownerErr, ownerCount) {
                                if (ownerErr) {
                                    next(ownerErr, connection, false)
                                } else {
                                    next(null, connection, ownerCount > 1)
                                }
                            })
                        }
                    }
                })
            }
        ],
        function(err, connection, canDelete) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(new Error(JSON.stringify(errors.customError(errObj, 
                        `Could not determine whether a user is allowed to delete their membership for list: ${listid}.`))))
                return
            }

            completion(null, canDelete)
        })
    }

    static deleteMembership(params, completion) {
        const userId = params.userid != undefined ? params.userid : null
        const listId = params.listid != undefined ? params.listid : null

        if (userId == null || listId == null) {
            completion(errors.create(errors.missingParameters, 'Missing parameters when deleting list membership'))
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
                                callback(errors.create(errors.databaseError,`Error getting a database connection: ${err.message}`), null)
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
                const membership = new TCListMembership()
                membership.configureWithProperties({
                    listid : listId,
                    userid : userId,
                })

                membership.delete(connection, function(err, membership) {
                    if (err) {
                        callback(errors.create(errors.databaseError, `Error deleting list membership record from the database: ${err.message}`), connection)
                    } else {
                        callback(null, connection, membership)
                    }
                })
            }
        ],
        function(err, connection, membership) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                completion(errors.create(errObj, `Could not delete a list membership object for listId ${listId}.`))
            } else {
                completion(null, membership)
            }
        })
    }

    static getMembersForList(params, completion) {
        const listid = params.listid && typeof params.listid == 'string' ? params.listid.trim() : null
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the listid parameter.`))))
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
                    dbPool = pool
                    dbPool.getConnection(function(err, connection) {
                        if (err) {
                            next(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error getting a database connection: ${err.message}`))), null)
                        } else {

                            next(null, connection)
                        }
                    })
                })
            },
            function(connection, next) {
                const authParams = {
                    listid : listid,
                    userid : userid,
                    membershipType: constants.ListMembershipType.Viewer,
                    dbConnection : connection
                }
                TCListMembershipService.isAuthorizedForMembershipType(authParams, (err, isAuthorized) => {
                    if (err) {
                        next(err, connection)
                        return
                    }
                    else if (!isAuthorized) {
                        next(new Error(JSON.stringify(errors.unauthorizedError)), connection)
                        return
                    }

                    next(null, connection)
                })
            },
            function(connection, next) {
                let sql = `
                    SELECT memberships.*, accounts.first_name, accounts.last_name, accounts.username, accounts.image_guid
                    FROM tdo_list_memberships memberships
                        INNER JOIN tdo_user_accounts accounts ON memberships.userid = accounts.userid
                    WHERE 
                        listid = ?
                    ORDER BY 
                        memberships.membership_type DESC, accounts.first_name, accounts.last_name, accounts.username
                `

                connection.query(sql, [listid], (err, result) => {
                    if (err) {
                        const message = `Error reading list members from the database: ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    next(null, connection, result.rows.map((row) => {
                        return { 
                            membership : new TCListMembership(row),
                            account : new TCAccount(row)
                        }
                    }))
                })
            }
        ],
        function(err, connection, memberships){
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }

            if (err) {
                const errObj = JSON.parse(err.message)
                const message = `Could not get memberships for list: ${listid}.`
                completion(new Error(JSON.stringify(errors.customError(errObj, message))))
                return
            }

            completion(null, memberships)
        })
    }

    static getMembersForAllLists(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
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
                    dbPool = pool
                    dbPool.getConnection(function(err, connection) {
                        if (err) {
                            next(new Error(JSON.stringify(errors.customError(errors.databaseError, 
                                    `Error getting a database connection: ${err.message}`))), null)
                        } else {

                            next(null, connection)
                        }
                    })
                })
            },
            function(connection, next) {
                let sql = `
                    SELECT DISTINCT userid, first_name, last_name, username, image_guid 
                    FROM tdo_user_accounts 
                    WHERE userid IN (
                        SELECT userid 
                        FROM tdo_list_memberships 
                        WHERE listid IN (
                            SELECT listid 
                            FROM tdo_list_memberships 
                            WHERE userid = ?
                        )
                    ) 
                    ORDER BY first_name, last_name, username
                `

                connection.query(sql, [userid], (err, result) => {
                    if (err) {
                        const message = `Error reading list members from the database: ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    next(null, connection, result.rows.map((row) => new TCAccount(row)))
                })
            }
        ],
        function(err, connection, members){
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }

            if (err) {
                const errObj = JSON.parse(err.message)
                const message = `Could not get accounts user has shared lists with for user: ${userid}.`
                completion(new Error(JSON.stringify(errors.customError(errObj, message))))
                return
            }

            completion(null, members)
        })
    }

    static getAllMembershipsForUser(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
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
                const sql = `
                    SELECT * FROM tdo_list_memberships WHERE userid = ? AND listid != ?
                `
                connection.query(sql, [userid, constants.LocalInboxId], function(err, results) {
                    if (err) {
                        next(errors.create(errors.databaseError, `Query failed: ${err.message}`), connection)
                        return
                    }
                    const memberships = results.rows ? results.rows.map(row => new TCListMembership(row)) : []
                    next(null, connection, memberships)
                })
            }
        ],
        function(err, connection, memberships) {
            if (shouldCleanupDB) {
                if (connection) dbPool.releaseConnection(connection)
                db.cleanup()
            }
            if (err) {
                const errObj = JSON.parse(err.message)
                const message = `Could not get all memberhsips for user: ${userid}.`
                completion(errors.create(errObj, message))
                return
            }

            completion(null, memberships)
        })
    }

    static isAuthorizedForMembershipType(params, completion) {
        let membershipType = params.membershipType
        if (membershipType === undefined) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the membershipType parameter.`))))
            return
        }

        TCListMembershipService.getMembership(params, function(err, membership) {
            if (err) {
                completion(err)
            } else {
                completion(null, membership.membership_type >= membershipType)
            }
        })
    }

    // For use with syncing clients
    static listMemberhsipCounts(params, completion) {
        if (!params && !params.userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }
        
        const userid = params.userid

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
                            next(errors.create(errors.databaseError, `Error getting a database connection: ${err.message}`))
                            return
                        }
                        next(null, connection)
                    })
                })
            },
            function(connection, next) {
                const sql = `
                    SELECT listid, COUNT(userid) as count
                    FROM tdo_list_memberships
                    WHERE listid IN (
                        SELECT listid 
                        FROM tdo_list_memberships 
                        WHERE userid = ?
                    )
                    GROUP BY listid
                `
                connection.query(sql, [userid], function(err, results) {
                    if (err) {
                        next(errors.create(errors.databaseError, `Query failed: ${err.message}`), connection)
                        return
                    }
                    const counts = results.rows ? results.rows.map(row => {
                        return {listid : row.listid, count : row.count }
                    }) : []
                    next(null, connection, counts)
                })
            }
        ],
        function(err, connection, counts) {
            if (shouldCleanupDB) {
                if (connection) dbPool.releaseConnection(connection)
                db.cleanup()
            }
            if (err) {
                const errObj = JSON.parse(err.message)
                const message = `Could not get all memberhsips for user: ${userid}.`
                completion(errors.create(errObj, message))
                return
            }

            completion(null, counts)
        })
    }
}

module.exports = TCListMembershipService


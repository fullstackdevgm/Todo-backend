'use strict'

const async = require('async')

const db = require('./tc-database')
const begin = require('any-db-transaction')

const constants = require('./constants')
const errors = require('./errors')

const TCInvitation = require('./tc-invitation')
const TCList = require('./tc-list')
const TCListSettings = require('./tc-list-settings')

const TCAccountService = require('./tc-account-service')
const TCAccount = require('./tc-account')
const TCListMembershipService = require('./tc-list-membership-service')
const TCListSettingsService = require('./tc-list-settings-service')
const TCChangelogService = require('./tc-changelog-service')
const TCMailerService = require('./tc-mailer-service')

class TCInvitationService {
    static sendListInvitation(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const listid = params.listid && typeof params.listid == 'string' ? params.listid.trim() : null
        const membershipType = params.membership_type ? params.membership_type : null
        const email = params.email && typeof params.email == 'string' ? params.email.trim() : null

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        if (!listid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the listid parameter.`))))
            return
        }

        if (!membershipType) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the membership type parameter.`))))
            return
        }

        if (!email) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the email parameter.`))))
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
                TCAccountService.userIdForUsername({ username : email, dbConnection : connection }, (err, result) => {
                    if (err) {
                        // No user associated with the email, pass through to next function
                        callback(null, connection, null)
                    }
                    else {
                        callback(null, connection, result)
                    }
                })
            },
            function(connection, invitedUserId, callback) {
                const invitation = new TCInvitation()
                invitation.userid = userid
                invitation.listid = listid
                invitation.email = email
                invitation.membership_type = membershipType
                if (invitedUserId) invitation.invited_userid = invitedUserId

                invitation.add(connection, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error creating a list invitation (${listid}): ${err.message}`))), connection)
                    }
                    else {
                        callback(null, connection, result)
                    }
                })
            },
            function(connection, invitation, callback) {
                const list = new TCList()
                list.listid = listid
                list.read(connection, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading list (${listid}): ${err.message}`))), connection, invitation)
                    }
                    else {
                        callback(null, connection, invitation, result)
                    }
                })
            },
            function(connection, invitation, list, callback) {
                TCAccountService.displayNameForUserId({userid : userid, dbConnection : connection}, (err, fromUserName) => {
                    if (err) {
                        callback(err, connection, invitation)
                    }
                    else {
                        callback(null, connection, invitation, list, fromUserName)
                    }
                })
            },
            function(connection, invitation, list, fromUserName, callback) {
                const emailParams = {
                    email : email,
                    from_user_name : fromUserName,
                    invitation_url : `${process.env.WEBAPP_BASE_URL}/accept-invitation/${invitation.invitationid}`,
                    list_name : list.name
                }
                TCMailerService.sendInvitationEmail(emailParams, (err, result) => {
                    if (err) {
                        callback(err, connection, invitation)
                    }
                    else {
                        callback(null, connection, invitation)
                    }
                })
            }
        ], 
        function(err, connection, invitation) {
            const finishUp = () => {
                if (shouldCleanupDB) {
                    if (connection) {
                        dbPool.releaseConnection(connection)
                    }
                    db.cleanup()
                }
                if (err) {
                    let errObj = JSON.parse(err.message)
                    completion(new Error(JSON.stringify(errors.customError(errObj, 
                            `Could not create list invitation (${listid}).`))))
                } else {
                    completion(null, invitation)
                }
            }
            
            // If there's an error after the invitation has been created,
            // delete it when the user isn't already in the system.
            if (err && invitation && !invitation.invited_userid) {
                invitation.delete(connection, (err, deletedid) => {
                    finishUp()
                })
            }
            else {
                finishUp()
            }
        })
    }

    static getInvitations(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        let shouldCleanupDB = false
        const dbConnection = params.dbConnection    
        let dbPool = null 

        async.waterfall([
            function(next) {
                if (dbConnection) {
                    next(null, dbConnection)
                    return
                }

                db.getPool(function(err, pool) {
                    if (err) {
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting a database connection: ${err.message}`))))
                        return
                    }

                    dbPool = pool
                    dbPool.getConnection(function(err, connection) {
                        if (err) {
                            next(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting a database connection: ${err.message}`))))
                            return
                        }

                        shouldCleanupDB = true
                        next(null, connection)
                    })
                })
            },
            function(connection, next) {
                const sql = `
                    SELECT * FROM tdo_invitations WHERE invited_userid = ?
                `

                connection.query(sql, [userid], (err, invitations) => {
                    if (err) {
                        const message = `Error reading invitations for user (${userid}): ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    let allInfos = []
                    let index = 0

                    async.each(invitations.rows, function(invitationRow, doWhilstCallback) {
                        async.waterfall([
                            function(next) {
                                const invitation = new TCInvitation(invitationRow)
                                index++
                                next(null, invitation)
                            },
                            function(invitation, next) {
                                const account = new TCAccount({ userid : invitation.userid })
                                account.read(connection, (err, result) => {
                                    if (err) {
                                        const message = `Error retrieving account info for invitation: ${err.message}`
                                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))))
                                        return
                                    }

                                    // We don't necessarily want to share all the account info.
                                    const simplifiedAccount = {
                                        userid : result.userid,
                                        first_name : result.first_name,
                                        last_name : result.last_name,
                                        username : result.username,
                                        image_guid : result.image_guid
                                    }

                                    next(null, invitation, simplifiedAccount)
                                })
                            },
                            function(invitation, account, next) {
                                const list = new TCList({ listid : invitation.listid })
                                list.read(connection, (err, result) => {
                                    if (err) {
                                        const message = `Error retrieving list info for invitation: ${err.message}`
                                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))))
                                        return
                                    }

                                    allInfos.push({ invitation : invitation, account : account, list : result })

                                    next(null, { invitation : invitation, account : account, list : result })
                                })
                            }
                        ],
                        function(err, result) {
                            if (err) {
                                let errObj = JSON.parse(err.message)
                                const message = `Could not get invitations.`
                                doWhilstCallback(new Error(JSON.stringify(errors.customError(errObj, message))))
                                return
                            }
                            doWhilstCallback(null, result)
                        })
                    },
                    function(err) {
                        if (err) {
                            next(err, connection)
                        } else {
                            const result = {
                                infos: allInfos,
                            }
                            next(null, connection, result)
                        }
                    })
                })
            }
        ],
        function(err, connection, result) {
            if (shouldCleanupDB) {
                dbPool.releaseConnection(connection)
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                const message = `Could not get invitations (${userid}).`
                completion(new Error(JSON.stringify(errors.customError(errObj, message))))
                return
            }
            completion(null, result)
        })   
    }

    static getInvitationsForList(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const listid = params.listid && typeof params.listid == 'string' ? params.listid.trim() : null

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
                const sql = `
                    SELECT * FROM tdo_invitations WHERE userid = ? AND listid = ?
                `

                connection.query(sql, [userid, listid], (err, invitations) => {
                    if (err) {
                        const message = `Error reading invitations for user (${userid}): ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    next(null, connection, invitations.rows.map(row => new TCInvitation(row)))
                })
            }
        ],
        function(err, connection, invitations) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                const errObj = JSON.parse(err.message)
                const message = `Could not get list invitations for userid (${userid}).`
                completion(new Error(JSON.stringify(errors.customError(errObj, message))))
                return
            }

            completion(null, invitations)
        })
    }

    static updateInvitationRole(params, completion) {
        const userid = params.userid && typeof params.userid == 'string' ? params.userid.trim() : null
        const invitationid = params.invitationid && typeof params.invitationid == 'string' ? params.invitationid.trim() : null
        

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        if (!invitationid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the invitationid parameter.`))))
            return
        }

        const membership_type = params.membership_type ? params.membership_type : null
        const memberships = [
            constants.ListMembershipType.Viewer,
            constants.ListMembershipType.Member,
            constants.ListMembershipType.Owner
        ]
        const membership = memberships[membership_type]

        if (!membership) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the membership_type parameter.`))))
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
                const invitation = new TCInvitation({ invitationid : invitationid })

                invitation.read(connection, (err, result) => {
                    if (err) {
                        const message = `Error retrieving a list invitation: ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    next(null, connection, result)
                })
            },
            function(connection, invitation, next) {
                invitation.membership_type = membership

                invitation.update(connection, (err, result) => {
                    if (err) {
                        const message = `Error updating a list invitation: ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    next(null, connection, result)
                })
            }
        ],
        function(err, connection, invitation) {
            if (shouldCleanupDB) {
                if (connection) {
                    dbPool.releaseConnection(connection)
                }
                db.cleanup()
            }
            if (err) {
                const errObj = JSON.parse(err.message)
                const message = `Could update a list invitation (${invitationid}).`
                completion(new Error(JSON.stringify(errors.customError(errObj, message))))
                return
            }

            completion(null, invitation)
        })
    }

    static resendListInvitation(params, completion) {
        const invitationid = params.invitationid ? params.invitationid : null
        const userid = params.userid ? params.userid : null

        if (!invitationid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the invitationid parameter.`))))
            return
        }

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        async.waterfall([
            function(next) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
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
                // First, read the invitation record.
                const invitation = new TCInvitation()
                invitation.invitationid = invitationid
                invitation.read(connection, (err, result) => {
                    if (err) {
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading invitation (${invitationid}): ${err.message}`))), connection)
                        return
                    }
                    
                    next(null, connection, result)
                })
            },
            function(connection, invitation, next) {
                const list = new TCList()
                list.listid = invitation.listid
                list.read(connection, (err, result) => {
                    if (err) {
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading list (${listid}): ${err.message}`))), connection)
                        return
                    }

                    next(null, connection, invitation, result)
                })
            },
            function(connection, invitation, list, next) {
                TCAccountService.displayNameForUserId({userid : userid, dbConnection : connection}, (err, fromUserName) => {
                    if (err) {
                        next(err, connection, invitation)
                        return
                    }
                    
                    next(null, connection, invitation, list, fromUserName)
                })
            },
            function(connection, invitation, list, fromUserName, next) {
                const emailParams = {
                    email : invitation.email,
                    from_user_name : fromUserName,
                    invitation_url : `${process.env.WEBAPP_BASE_URL}/accept-invitation/${invitation.invitationid}`,
                    list_name : list.name
                }
                TCMailerService.sendInvitationEmail(emailParams, (err, result) => {
                    if (err) {
                        next(err, connection)
                    }
                    else {
                        next(null, connection, invitation, list, fromUserName)
                    }
                })
            },
            function(connection, invitation, list, fromUserName, next) {
                invitation.update(connection, (err, result) => {
                    if (err) {
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error updating invitation: ${err.message}`))), connection)
                    }
                    else {
                        next(null, connection, invitation, list, fromUserName)
                    }
                })
            },
            function(connection, invitation, list, fromUserName, next) {
                const params = {
                    listid : list.listid,
                    userid : userid,
                    itemid : invitation.invitationid,
                    itemName : fromUserName,
                    itemType : constants.ChangeLogItemType.Invitation,
                    changeType : constants.ChangeLogType.Modify,
                    changeLocation : constants.ChangeLogLocation.API,
                    dbConnection : connection
                }
                TCChangelogService.addChangeLogEntry(params, (err, result) => {
                    if (err) {
                        logger.debug(`Error recording change to changelog during acceptInvitation(): ${err}`)
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
                        `Could not delete list invitation (${invitationid}).`))))
            } else {
                completion(null, {success : true})
            }
        })
    }

    static acceptInvitation(params, completion) {
        const invitationid = params.invitationid ? params.invitationid : null
        const userid = params.userid ? params.userid : null

        if (!invitationid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the invitationid parameter.`))))
            return
        }

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        async.waterfall([
            function(callback) {
                db.getPool(function(err, pool) {
                    begin(pool, {autoRollback: false}, function(err, transaction) {
                        if (err) {
                            callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error beginning a database transaction: ${err.message}`))), null)
                        } else {
                            callback(null, transaction)
                        }
                    })
                })
            },
            function(transaction, callback) {
                // First, read the invitation record.
                const invitation = new TCInvitation()
                invitation.invitationid = invitationid
                invitation.read(transaction, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading invitation (${invitationid}): ${err.message}`))), transaction)
                    }
                    else {
                        const invitedUserID = result.invited_userid
                        if (invitedUserID && invitedUserID != userid) {
                            callback(new Error(JSON.stringify(errors.customError(errors.customError, 'Invited user id does not match logged in user id.'))), transaction)
                        }
                        else {
                            callback(null, transaction, result)
                        }
                    }
                })
            },
            function(transaction, invitation, callback) {
                const list = new TCList()
                list.listid = invitation.listid
                list.read(transaction, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error reading list (${invitation.listid}): ${err.message}`))), transaction)
                    }
                    else {
                        callback(null, transaction, invitation, list)
                    }
                })
            },
            function(transaction, invitation, list, callback) {
                const role = invitation.membership_type
                if (list.deleted) {
                    callback(new Error(JSON.stringify(errors.customError(errors.customError, `Invited list (${invitation.listid}) has been deleted.`))), transaction)
                }
                else {
                    const params = { 
                        userid : userid,
                        listid : list.listid,
                        membership_type : role,
                        dbConnection : transaction
                    }
                    TCListMembershipService.createListMembership(params, (err, membership) => {
                        if (err) {
                            callback(err, transaction)
                        }
                        else {
                            callback(null, transaction, invitation, list)
                        }
                    })
                }
            },
            function(transaction, invitation, list, next) {
                // Need to determine if list settings for this list needs to be created.
                const settings = new TCListSettings({
                    userid : userid,
                    listid : list.listid
                })
                settings.read(transaction, (err, result) => {
                    if (err) {
                        next(errors.create(errors.databaseError, `Error checking whether or not list settings exists when accepting invitaiton.`), transaction)
                        return
                    }

                    next(null, transaction, invitation, list, result ? false : true)
                })
            },
            function(transaction, invitation, list, shouldCreateSettings, callback) {
                if (!shouldCreateSettings) {
                    callback(null, transaction, invitation, list)
                    return
                }

                const params = {
                    userid : userid,
                    listid : list.listid,
                    dbConnection : transaction
                }
                TCListSettingsService.createListSettings(params, (err, settings) => {
                    if (err) {
                        callback(err, transaction)
                    }
                    else {
                        callback(null, transaction, invitation, list)
                    }
                })
            },
            function (transaction, invitation, list, callback) {
                TCAccountService.displayNameForUserId({ userid : userid, dbConnection : transaction }, (err, name) => {
                    if (err) {
                        callback(err, transaction)
                    }
                    else {
                        callback(null, transaction, invitation, list, name)
                    }
                })
            },
            function(transaction, invitation, list, name, callback) {
                const invitationid = invitation.invitationid
                invitation.delete(transaction, (err, result) => {
                    if (err) {
                        callback(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error deleting invitation ${invitationid}: ${err.message}`))), transaction)
                    }
                    else {
                        callback(null, transaction, invitationid, list, name)
                    }
                })
            },
            function(transaction, invitationid, list, name, callback) {
                const params = {
                    listid : list.listid,
                    userid : userid,
                    itemid : invitationid,
                    itemName : name,
                    itemType : constants.ChangeLogItemType.User,
                    changeType : constants.ChangeLogType.Add,
                    changeLocation : constants.ChangeLogLocation.API,
                    dbConnection : transaction
                }
                TCChangelogService.addChangeLogEntry(params, (err, result) => {
                    if (err) {
                        logger.debug(`Error recording change to changelog during acceptInvitation(): ${err}`)
                    }
                    callback(null, transaction)
                })
            }
        ],
        function(err, transaction) {
            if (err) {
                if (transaction) {
                    let errObj = JSON.parse(err.message)
                    completion(new Error(JSON.stringify(errors.customError(errObj, `Could not accept list invitation.`))))
                    transaction.rollback(function(transErr, result) {
                        // Ignore the result. The transaction will be closed or we'll get an error
                        // because the transaction has already been rolled back.
                        db.cleanup()
                    })
                } else {
                    let errObj = JSON.parse(err.message)
                    completion(new Error(JSON.stringify(errors.customError(errObj, `Could not accept list invitation.`))))
                    db.cleanup()
                }
            } else {
                if (transaction) {
                    transaction.commit(function(err, result) {
                        db.cleanup()
                        if (err) {
                            completion(new Error(JSON.stringify(errors.customError(errors.serverError, `Could not accept list invitation. Database commit failed: ${err.message}`))))
                        } else {
                            completion(null, { success : true })
                        }
                    })
                } else {
                    db.cleanup()
                }
            }
        })
    }

    static deleteInvitation(params, completion) {
        const userid = params.userid ? params.userid : null
        const invitationid = params.invitationid ? params.invitationid : null

        if (!invitationid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the invitationid parameter.`))))
            return
        }

        if (!userid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the userid parameter.`))))
            return
        }

        const dbConnection = params.dbConnection
        const shouldCleanupDB = !dbConnection
        let dbPool = null

        async.waterfall([
            function(next) {
                // Pass on the dbConnection we were passed or get a new
                // connection that can be used.
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
                const invitation = new TCInvitation()
                invitation.invitationid = invitationid
                invitation.read(connection, (err, result) => {
                    if (err) {
                        const message = `Error retrieving a list invitation (${invitationid}): ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    next(null, connection, result)
                })
            },
            function(connection, invitation, next) {
                const params = {
                    userid : userid,
                    listid : invitation.listid,
                    membershipType : constants.ListMembershipType.Owner,
                    dbConnection : connection
                }
                TCListMembershipService.isAuthorizedForMembershipType(params, (err, result) => {
                    const isInvitee = invitation.invited_userid == userid
                    let isOwner = result
                    if (err) {
                        // This call will error when the userid doesn't have a membership with the list,
                        // but that's actually fine. Assume isOwner = false on error.
                        isOwner = false
                    }
                    
                    if (!isOwner && !isInvitee) {
                        const message = `The user (${userid}) is not authorized to modify the invitation (${invitationid}).`
                        next(new Error(JSON.stringify(errors.customError(errors.unauthorizedError, message))), connection)
                        return
                    }

                    next(null, connection, invitation)
                })
            },
            function(connection, invitation, next) {
                invitation.delete(connection, (err, result) => {
                    if (err) {
                        const message = `Error deleting a list invitation (${invitationid}): ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    next(null, connection, invitation)
                })
            },
            function(connection, invitation, next) {
                const params = {
                    listid : invitation.listid,
                    userid : userid,
                    itemid : invitation.invitationid,
                    itemType : constants.ChangeLogItemType.Invitation,
                    changeType : constants.ChangeLogType.Delete,
                    changeLocation : constants.ChangeLogLocation.API,
                    dbConnection : connection
                }
                TCChangelogService.addChangeLogEntry(params, (err, result) => {
                    if (err) {
                        logger.debug(`Error recording change to changelog during acceptInvitation(): ${err}`)
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
                        `Could not delete list invitation (${invitationid}).`))))
            } else {
                completion(null, {success : true})
            }
        }) 
    }

    static getInvitation(params, completion) {
        const invitationid = params.invitationid && typeof params.invitationid == 'string' ? params.invitationid.trim() : null

        if (!invitationid) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the invitationid parameter.`))))
            return
        }

        let shouldCleanupDB = false
        const dbConnection = params.dbConnection    
        let dbPool = null 

        async.waterfall([
            function(next) {
                if (dbConnection) {
                    next(null, dbConnection)
                    return
                }

                db.getPool(function(err, pool) {
                    if (err) {
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting a database connection: ${err.message}`))))
                        return
                    }

                    dbPool = pool
                    dbPool.getConnection(function(err, connection) {
                        if (err) {
                            next(new Error(JSON.stringify(errors.customError(errors.databaseError, `Error getting a database connection: ${err.message}`))))
                            return
                        }

                        shouldCleanupDB = true
                        next(null, connection)
                    })
                })
            },
            function(connection, next) {
                const invitation = new TCInvitation({ invitationid : invitationid })
                invitation.read(connection, (err, result) => {
                    if (err) {
                        const message = `Error retrieving a list invitation: ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    next(null, connection, result)
                })
            },
            function(connection, invitation, next) {
                const account = new TCAccount({ userid : invitation.userid })
                account.read(connection, (err, result) => {
                    if (err) {
                        const message = `Error retrieving account info for invitation: ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    // We don't necessarily want to share all the account info.
                    const simplifiedAccount = {
                        userid : result.userid,
                        first_name : result.first_name,
                        last_name : result.last_name,
                        username : result.username,
                        image_guid : result.image_guid
                    }

                    next(null, connection, invitation, simplifiedAccount)
                })
            },
            function(connection, invitation, account, next) {
                const list = new TCList({ listid : invitation.listid })
                list.read(connection, (err, result) => {
                    if (err) {
                        const message = `Error retrieving list info for invitation: ${err.message}`
                        next(new Error(JSON.stringify(errors.customError(errors.databaseError, message))), connection)
                        return
                    }

                    next(null, connection, { invitation : invitation, account : account, list : result })
                })
            }
        ],
        function(err, connection, result) {
            if (shouldCleanupDB) {
                dbPool.releaseConnection(connection)
                db.cleanup()
            }
            if (err) {
                let errObj = JSON.parse(err.message)
                const message = `Could get list invitation (${invitationid}).`
                completion(new Error(JSON.stringify(errors.customError(errObj, message))))
                return
            }
            completion(null, result)
        })   
    }
}

module.exports = TCInvitationService
'use strict'

const TCListMembershipService = require('./common/tc-list-membership-service')
const TCListService = require('./common/tc-list-service')
const constants = require('./common/constants')
const errors = require('./common/errors.js')
const async = require('async')

const handleResult = function(err, result, next) {
    if (err) { 
        try {
            var errObj = JSON.parse(err.message)
            if (errObj.httpStatus > 0) {
                next(err.message)
                return
            }
        } catch (e) {
            // Intentionally blank, but here to prevent problems
            // crashing if the above JSON.parse() fails.
        }

        var serverErr = errors.serverError
        serverErr.message = `${serverErr.message} - ${err.message}`
        next(JSON.stringify(serverErr))
        return
    }
    else { 
        next(null, result) 
    }
}

// Allows you to change the role of a user in a list.
exports.changeRole = function(event, context, next) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require ('./common/constants')

        utils.makeHttpRequest(
            `/list-member/${event.memberid}/role`,
            constants.httpMethods.PUT, 
            event, 
            null, 
            next
        )

        return
    }


    TCListMembershipService.changeRole(event, (err, result) => {
        handleResult(err, result, next)
    })
}

exports.getMembersForList = function(event, context, next) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require ('./common/constants')
        const TCListService = require('./common/tc-list-service')
        
        TCListService.syncIdForListId({listid : event.listid}, (err, result) => {
            if (err) {
                handleResult(err, null, next)
                return
            }
            event.listid = result
            utils.makeHttpRequest(
                `/list/${event.listid}/members`,
                constants.httpMethods.GET, 
                null, 
                null, 
                (err, result) => {
                    if (err) {
                        handleResult(err, null, next)
                        return
                    }

                    async.mapSeries(result, function(membershipInfo, nextEach) {
                        const params = { syncid : membershipInfo.membership.listid }
                        TCListService.listIdForSyncId(params, (err, syncid) => {
                            if (err) {
                                nextEach(err)
                                return
                            }

                            const membership = membershipInfo.membership
                            membership.listid = syncid
                            nextEach(null, {
                                account : membershipInfo.account,
                                membership : membership
                            })
                        })
                    },
                    function(err, result) {
                        handleResult(err, result, next)
                    })
                }
            )
        })
        return
    }

    TCListMembershipService.getMembersForList(event, (err, result) => {
        handleResult(err, result, next)
    })
}

exports.getMembersForAllLists = function(event, context, next) {
    // Info for this is synced, so we can get it from the local db.

    TCListMembershipService.getMembersForAllLists(event, (err, result) => {
        handleResult(err, result, next)
    })
}

exports.removeMembership = function(event, context, completion) {
    const userid = event.userid && typeof event.userid == 'string' ? event.userid.trim() : null
    const memberid = event.memberid && typeof event.memberid == 'string' ? event.memberid.trim() : null
    const listid = event.listid && typeof event.listid == 'string' ? event.listid.trim() : null


    const removeMembership = listid => next => {
        const deleteParams = {
            userid : memberid,
            listid : listid
        }
        TCListMembershipService.deleteMembership(deleteParams, (err, result) => {
            if (err) {
                next(err)
                return
            }

            next(null, result)
        })
    }

    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require ('./common/constants')

        async.waterfall([
            function(next) {
                TCListService.getListSyncId({ listid : listid }, (err, syncid) => {
                    next(err, syncid)
                })
            },
            function(syncid, next) {
                utils.makeHttpRequest(
                    `/list-member/${memberid}/remove/${syncid}`,
                    constants.httpMethods.DELETE, 
                    null, 
                    null, 
                    (err, result) => {
                        next(err, syncid, result)
                    }
                )
            },
            function(syncid, result, next) {
                removeMembership(syncid)(next)
            }
        ],
        function(err, result) {
            handleResult(err, result, completion)
        })
        return
    }

    async.waterfall([
        function(next) {
            const authParams = {
                userid : userid,
                listid : listid,
                membershipType : constants.ListMembershipType.Owner
            }
            TCListMembershipService.isAuthorizedForMembershipType(authParams, (err, authorized) => {
                if (err) {
                    next(err)
                    return
                }
                else if(!authorized) {
                    next(new Error(JSON.stringify(errors.unauthorizedError)))
                    return
                }

                next(null)
            })
        },
        function(next) {
            // We already checked if the user is an owner, and if they're trying to
            // remove themself, we have to make sure they aren't the LAST owner.
            if (memberid == userid) {
                TCListMembershipService.getOwnerCountForList(event, (err, count) => {
                    if (err) {
                        next(err)
                        return
                    }
                    else if(count <= 1) {
                        const message = `Cannot remove last owner from list.`
                        next(new Error(JSON.stringify(errors.customError(errors.unauthorizedError, message))))
                        return
                    }

                    next(null)
                })
                return
            }

            next(null)
        },
        removeMembership(listid)
    ], 
    function(err, result) {
        handleResult(err, result, completion)
    })
}

// Only for sync clients
exports.listMemberCounts = function(event, context, completion) {
    TCListMembershipService.listMemberhsipCounts(event, (err, result) => {
        handleResult(err, result, completion)
    })
}

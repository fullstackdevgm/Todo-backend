const TCInvitationService = require('./common/tc-invitation-service')
const errors = require('./common/errors.js')

const handleResult = function(err, result, callback) {
    if (err) { 
        try {
            var errObj = JSON.parse(err.message)
            if (errObj.httpStatus > 0) {
                callback(err.message)
                return
            }
        } catch (e) {
            // Intentionally blank, but here to prevent problems
            // crashing if the above JSON.parse() fails.
        }

        var serverErr = errors.serverError
        serverErr.message = `${serverErr.message} - ${err.message}`
        callback(JSON.stringify(serverErr))
        return
    }
    else { 
        callback(null, result) 
    }
}

// Sends an invitation to join a list
exports.sendListInvitation = function(event, context, callback) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require('./common/constants')
        const TCListService = require('./common/tc-list-service')

        const localListId = event.listid

        TCListService.syncIdForListId({ listid : event.listid }, function(err, syncid){
            if (err) {
                handleResult(err, null, callback)
                return
            }

            event.listid = syncid
            utils.makeHttpRequest(
                `/invitations`, 
                constants.httpMethods.POST, 
                event, 
                null, 
                function(err, response) {
                    if (err) {
                        callback(err)
                        return
                    }

                    response.listid = localListId
                    callback(null, response)
                })
        })
        return
	}

    TCInvitationService.sendListInvitation(event, (err, invitation) => {
        handleResult(err, invitation, callback)
    })
}

exports.getInvitations = function(event, context, next) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require('./common/constants')

        utils.makeHttpRequest(
            `/invitations`,
            constants.httpMethods.GET, 
            null, 
            null, 
            next)
        return
    }

    TCInvitationService.getInvitations(event, (err, result) => {
        handleResult(err, result, next)
    })
}

// Send a list invitation again
exports.resendInvitation = function(event, conext, callback) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require('./common/constants')

        utils.makeHttpRequest(
            `/invitations/${event.invitationid}/resend`,
            constants.httpMethods.PUT, 
            event, 
            null, 
            callback)
        return
    }
    
    TCInvitationService.resendListInvitation(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// Accepts an invitation to join a list
exports.acceptInvitation = function(event, context, callback) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require('./common/constants')

        utils.makeHttpRequest(
            `/invitations/${event.invitationid}`, 
            constants.httpMethods.POST, 
            event, 
            null, 
            callback)
        return
    }

    TCInvitationService.acceptInvitation(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// Delete a list invitation
exports.deleteInvitation = function(event, context, callback) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require('./common/constants')

        utils.makeHttpRequest(
            `/invitations/${event.invitationid}`, 
            constants.httpMethods.DELETE, 
            null, 
            null, 
            callback)
        return
    }

    TCInvitationService.deleteInvitation(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.updateInvitationRole = function(event, conext, next) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require('./common/constants')

        utils.makeHttpRequest(
            `/invitations/${event.invitationid}`, 
            constants.httpMethods.PUT, 
            event, 
            null, 
            next)
        return
    }

    TCInvitationService.updateInvitationRole(event, (err, result) => {
        handleResult(err, result, next)
    })
}

exports.getInvitationsForList = function(event, context, next) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require('./common/constants')
        const TCListService = require('./common/tc-list-service')

        TCListService.syncIdForListId({ listid : event.listid }, function(err, syncid) {
            if (err) {
                handleResult(err, null, next)
                return
            }

            utils.makeHttpRequest(
                `/lists/${syncid}/invitations`, 
                constants.httpMethods.GET, 
                null, 
                null,
                next)
        })
        return
    }

    TCInvitationService.getInvitationsForList(event, (err, result) => {
        handleResult(err, result, next)
    })
}

exports.getInvitation = function(event, context, next) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require('./common/constants')

        utils.makeHttpRequest(
            `/invitations/${event.invitationid}`, 
            constants.httpMethods.GET, 
            null, 
            null, 
            next)
        return
    }

    TCInvitationService.getInvitation(event, (err, result) => {
        handleResult(err, result, next)
    })
}
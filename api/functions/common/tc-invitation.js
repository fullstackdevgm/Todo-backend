'use strict'

const TCObject = require('./tc-object')

class TCInvitation extends TCObject {
    tableName() {
        return 'tdo_invitations'
    }

    identifierName() {
        return 'invitationid'
    }

    columnNames() {
        return super.columnNames().concat(['userid', 'listid', 'email', 'invited_userid', 'timestamp', 'membership_type', 'fb_userid', 'fb_requestid'])
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['userid', 'listid', 'timestamp'])
    }

    add(dbConnection, callback) {
        // Make sure that we set a creation timestamp before passing this on to super
        this.timestamp = Math.floor(Date.now() / 1000)
        super.add(dbConnection, callback)
    }

    update(dbConnection, callback) {
        // Make sure that we set a creation timestamp before passing this on to super
        this.timestamp = Math.floor(Date.now() / 1000)
        super.update(dbConnection, callback)
    }
}

module.exports = TCInvitation

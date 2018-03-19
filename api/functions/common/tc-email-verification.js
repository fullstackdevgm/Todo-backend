'use strict'

const TCObject = require('./tc-object')

class TCEmailVerification extends TCObject {
    tableName() {
        return 'tdo_email_verifications'
    }

    identifierName() {
        return 'verificationid'
    }

    columnNames() {
        return super.columnNames().concat(['userid', 'username', 'timestamp'])
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['userid', 'username', 'timestamp'])
    }

    add(dbConnection, callback) {
        // Make sure that we set a creation timestamp before passing this on to super
        this.timestamp = Math.floor(Date.now() / 1000)

        super.add(dbConnection, callback)
    }
}

module.exports = TCEmailVerification
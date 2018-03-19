'use strict'

const uuidV4 = require('uuid/v4')

const TCObject = require('./tc-object')

class TCPasswordReset extends TCObject {
    tableName() {
        return 'tdo_password_reset'
    }

    identifierName() {
        return 'resetid'
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

    update(dbConnection, callback) {
        this.timestamp = Math.floor(Date.now() / 1000)

        super.update(dbConnection, callback)
    }
}

module.exports = TCPasswordReset
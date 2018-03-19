'use strict'

const TCObject = require('./tc-object')

class TCSubscription extends TCObject {
    tableName() {
        return 'tdo_subscriptions'
    }

    identifierName() {
        return 'subscriptionid'
    }

    columnNames() {
        return super.columnNames().concat(['userid', 'expiration_date', 'type', 'level', 'teamid', 'timestamp'])
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['userid', 'expiration_date', 'type', 'level'])
    }

    add(dbConnection, callback) {
        // Set a timestamp before passing on to super
        this.timestamp = Math.floor(Date.now() / 1000)

        super.add(dbConnection, callback)
    }
}

module.exports = TCSubscription
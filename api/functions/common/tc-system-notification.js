'use strict'

const uuidV4 = require('uuid/v4')

const TCObject = require('./tc-object')

class TCSystemNotification extends TCObject {
    tableName() {
        return 'tdo_system_notifications'
    }

    identifierName() {
        return 'notificationid'
    }

    columnNames() {
        return super.columnNames().concat(['message', 'learn_more_url', 'timestamp', 'deleted'])
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['message'])
    }

    add(dbConnection, callback) {
        this.timestamp = Math.floor(Date.now() / 1000)

        super.add(dbConnection, callback)
    }

    update(dbConnection, callback) {
        this.timestamp = Math.floor(Date.now() / 1000)

        super.update(dbConnection, callback)
    }
}

module.exports = TCSystemNotification
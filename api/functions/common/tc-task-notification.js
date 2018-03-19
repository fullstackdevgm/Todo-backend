'use strict'

const uuidV4 = require('uuid/v4')

const TCObject = require('./tc-object')

class TCTaskNotification extends TCObject {
    tableName() {
        return 'tdo_task_notifications'
    }

    identifierName() {
        return 'notificationid'
    }

    columnNames() {
        const myColumnNames = super.columnNames().concat(['taskid', 'timestamp', 'sound_name', 'deleted', 'triggerdate', 'triggeroffset'])
        if (process.env.DB_TYPE == 'sqlite') {
            myColumnNames.push('sync_id', 'dirty')
        }
        return myColumnNames
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['taskid', 'timestamp'])
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

module.exports = TCTaskNotification
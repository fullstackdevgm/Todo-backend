'use strict'

const uuidV4 = require('uuid/v4')

const TCObject = require('./tc-object')

class TCList extends TCObject {
    tableName() {
        return 'tdo_lists'
    }

    identifierName() {
        return 'listid'
    }

    columnNames() {
        const myColumnNames = super.columnNames().concat(['name', 'description', 'creator', 'cdavUri', 'cdavTimeZone', 'deleted', 'timestamp', 'task_timestamp', 'notification_timestamp', 'taskito_timestamp'])
        if (process.env.DB_TYPE == 'sqlite') {
            myColumnNames.push('sync_id', 'dirty')
        }
        return myColumnNames
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['name', 'creator', 'timestamp', 'cdavUri'])
    }

    add(dbConnection, callback) {
        // Make sure that we set a creation timestamp before passing this on to super
        this.timestamp = Math.floor(Date.now() / 1000)

        let myInstance = this

        // Normally if the listid isn't defined, we'd let the super class define it.
        // In the case of TCList, the cdavUri needs to match the listid.
        if (myInstance['listid'] === undefined) {
            myInstance['listid'] = uuidV4()
        }

        myInstance['cdavUri'] = myInstance['listid']

        super.add(dbConnection, callback)
    }

    update(dbConnection, callback) {
        this.timestamp = Math.floor(Date.now() / 1000)

        super.update(dbConnection, callback)
    }
}

module.exports = TCList
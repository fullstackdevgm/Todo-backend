'use strict'

const uuidV4 = require('uuid/v4')

const Constants = require('./constants')
const TCObject = require('./tc-object')

class TCTaskito extends TCObject {
    tableName() {
        return 'tdo_taskitos'
    }

    identifierName() {
        return 'taskitoid'
    }

    columnNames() {
        const myColumnNames = super.columnNames().concat([
            'parentid',
            'name',
            'completiondate',
            'timestamp',     
            'deleted',
            'sort_order'
        ])
        if (process.env.DB_TYPE == 'sqlite') {
            myColumnNames.push('sync_id', 'dirty')
        }
        return myColumnNames
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['parentid', 'name', 'timestamp'])
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

    isCompleted() {
        return this.completiondate != undefined && this.completiondate != 0
    }

    toTask() {
        const TCTask = require('./tc-task')
        const task = new TCTask()

        task.recurrence_type = Constants.TaskRecurrenceType.WithParent
        task.parentid = this.parentid
        task.name = this.name
        task.sort_order = 0
        task.completiondate = this.completiondate
        task.priority = Constants.TaskPriority.None

        return task
    }
}

module.exports = TCTaskito
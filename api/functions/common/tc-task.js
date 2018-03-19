'use strict'

const uuidV4 = require('uuid/v4')

const Constants = require('./constants')
const TCObject = require('./tc-object')

class TCTask extends TCObject {
    constructor(properties, tableName) {
        super(properties)

        if (tableName) {
            this._tableName = tableName
        }
    }

    tableName() {
        if (this._tableName) {
            return this._tableName
        }

        return 'tdo_tasks'
    }

    setTableName(newTableName) {
        if (!newTableName) return

        this._tableName = newTableName
    }

    identifierName() {
        return 'taskid'
    }

    columnNames() {
        const myColumnNames = super.columnNames().concat([
            'listid',
            'name',
            'parentid',
            'note',
            'startdate',
            'duedate',
            'due_date_has_time',
            'completiondate',
            'priority',
            'timestamp',     
            'caldavuri',
            'caldavdata',
            'deleted',
            'task_type',
            'type_data',
            'starred',
            'assigned_userid',
            'recurrence_type',
            'advanced_recurrence_string',
            'project_startdate',
            'project_duedate',
            'project_duedate_has_time',
            'project_priority',
            'project_starred',
            'location_alert',
            'sort_order'
        ])
        if (process.env.DB_TYPE == 'sqlite') {
            myColumnNames.push('sync_id', 'dirty')
        }
        return myColumnNames
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['listid', 'name', 'timestamp', 'caldavuri'])
    }

    add(dbConnection, callback) {
        // Make sure that we set a creation timestamp before passing this on to super
        this.timestamp = Math.floor(Date.now() / 1000)

        // Normally if the taskid isn't defined, we'd let the super class define it.
        // In the case of TCTask, the caldavuri needs to match the taskid.
        if (this['taskid'] === undefined) {
            this['taskid'] = uuidV4()
        }

        this['caldavuri'] = this['taskid']

        super.add(dbConnection, callback)
    }

    update(dbConnection, callback) {
        this.timestamp = Math.floor(Date.now() / 1000)

        super.update(dbConnection, callback)
    }

    // There are some cases where we need to update the task without
    // causing the timestamp to be updated. This method provides that.
    updateWithoutTimestamp(dbConnection, callback) {
        super.update(dbConnection, callback)
    }

    isProject() {
        if (this.task_type == undefined) {
            // Assume that this task is NOT a project since task_type is not defined
            return false
        } else {
            return this.task_type == Constants.TaskType.Project
        }
    }
    
    isChecklist() {
        if (this.task_type == undefined) {
            // Assume that this task is NOT a checklist since task_type is not defined
            return false
        } else {
            return this.task_type == Constants.TaskType.Checklist
        }
    }

    isCompleted() {
        return this.completiondate != undefined && this.completiondate != 0
    }

    isSubtask() {
        return this.parentid != undefined && this.parentid.length > 0
    }

    createCopy() {
        let copy = new TCTask()

        this.columnNames().forEach((columnName) => {
            if (this[columnName] != undefined) {
                copy[columnName] = this[columnName]
            }
        })

        // Clear the identifiers
        if (Array.isArray(this.identifierName())) {
            for (let identifierComponent of this.identifierName()) {
                copy[identifierComponent] = undefined
            }
        } else {
            copy[this.identifierName()] = undefined
        }

        return copy
    }

    toTaskito() {
        const TCTaskito = require('./tc-taskito')
        const taskito = new TCTaskito()

        taskito.name = this.name
        taskito.sort_order = this.sort_order ? this.sort_order : 0
        taskito.completiondate = this.completiondate
        taskito.parentid = this.parentid

        return taskito
    }
}

module.exports = TCTask
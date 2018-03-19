'use strict'

const TCObject = require('./tc-object')

class TCListSettings extends TCObject {
    tableName() {
        return 'tdo_list_settings'
    }

    columnNames() {
        return ['listid', 
                'userid', 
                'color', 
                'timestamp', 
                'cdavOrder', 
                'cdavColor', 
                'sync_filter_tasks', 
                'task_notifications',
                'user_notifications',
                'comment_notifications',
                'notify_assigned_only',
                'hide_dashboard',
                'icon_name',
                'sort_order',
                'sort_type',
                'default_due_date']
    }

    identifierName() {
        return ['listid', 'userid']
    }

    requiredColumnNamesForAdding() {
        return ['listid', 'userid', 'timestamp']
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

module.exports = TCListSettings

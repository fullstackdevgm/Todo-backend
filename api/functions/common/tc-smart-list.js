'use strict'

const uuidV4 = require('uuid/v4')
const TCObject = require('./tc-object')

const SmartListSortTypeKey          = "sortType"
const SmartListDefaultDueDateKey    = "defaultDueDate"
const SmartListDefaultListKey       = "defaultList"
const SmartListShowListForTasksKey  = "showListForTasks"
const SmartListShowSubtasksKey      = "showSubtasks"
const SmartListExcludeStartDatesKey = "excludeStartDates"


class TCSmartList extends TCObject {
    tableName() {
        return 'tdo_smart_lists'
    }

    identifierName() {
        return 'listid'
    }

    columnNames() {
        const myColumnNames = super.columnNames().concat([
            'name', 
            'userid',
            'color', 
            'icon_name',
            'sort_order',
            'json_filter',
            'sort_type',
            'default_due_date',
            'default_list',
            'excluded_list_ids',
            'completed_tasks_filter',
            'deleted', 
            'timestamp'
            ])
        if (process.env.DB_TYPE == 'sqlite') {
            myColumnNames.push('sync_id', 'dirty')
        }
        return myColumnNames
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['name', 'userid', 'timestamp'])
    }

    read(dbConnection, callback) {
        super.read(dbConnection, function(err, theSmartList) {
            if (err) {
                callback(err)
            } else {
                // Parse the json_filter into a variable we can use
                try {
                    theSmartList._jsonFilter = JSON.parse(theSmartList.json_filter)
                } catch (error) {
                    logger.debug(`Error parsing JSON Filter of a Smart List (${theSmartList.listid}): ${error}`)
                }
                callback(null, theSmartList)
            }
        })
    }

    add(dbConnection, callback) {
        // Make sure that we set a creation timestamp before passing this on to super
        this.timestamp = Math.floor(Date.now() / 1000)

        super.add(dbConnection, callback)
    }

    update(dbConnection, callback) {
        this.timestamp = Math.floor(Date.now() / 1000)

        // // Convert the jsonFilter back into a string for writing to the
        // // database.
        // if (this.jsonFilter != undefined) {
        //     this.json_filter = JSON.stringify(this.jsonFilter)
        // }

        super.update(dbConnection, callback)
    }

    jsonFilter() {
        if (this._jsonFilter) {
            return this._jsonFilter
        }

        if (this.json_filter == null) {
            return {}
        }

        // Parse the json_filter into a variable
        try {
            this._jsonFilter = JSON.parse(this.json_filter)
            return this._jsonFilter
        } catch (error) {
            logger.debug(`Error parsing JSON Filter of a Smart List (${this.listid}): ${error} : RAW JSON: ${this.json_filter}`)
        }
        return {}
    }

    sortType() {
        const aJSONFilter = this.jsonFilter()
        
        if (aJSONFilter && aJSONFilter[SmartListSortTypeKey] != undefined) {
            return aJSONFilter[SmartListSortTypeKey]
        }
        return -1 // Use the system-wide default
    }

    defaultDueDate() {
        const aJSONFilter = this.jsonFilter()
        
        if (aJSONFilter && aJSONFilter[SmartListDefaultDueDateKey] != undefined) {
            return aJSONFilter[SmartListDefaultDueDateKey]
        }
        return -1 // Use the system-wide default
    }

    defaultList() {
        const aJSONFilter = this.jsonFilter()

        if (aJSONFilter && aJSONFilter[SmartListDefaultListKey] != undefined) {
            return aJSONFilter[SmartListDefaultListKey]
        }
        return null
    }

    showSubtasks() {
        const aJSONFilter = this.jsonFilter()

        if (aJSONFilter && aJSONFilter[SmartListShowSubtasksKey] != undefined) {
            const show = aJSONFilter[SmartListShowSubtasksKey]
            return show
        }
        return false
    }
    
    excludesStartDates() {
        const aJSONFilter = this.jsonFilter()
    
        if (aJSONFilter && aJSONFilter[SmartListExcludeStartDatesKey] != undefined) {
            return aJSONFilter[SmartListExcludeStartDatesKey]
        }
        return false
    }
    
    showListForTasks() {
        const aJSONFilter = this.jsonFilter()
    
        if (aJSONFilter && aJSONFilter[SmartListShowListForTasksKey] != undefined) {
            return aJSONFilter[SmartListShowListForTasksKey]
        }
        return false
    }
}

module.exports = TCSmartList
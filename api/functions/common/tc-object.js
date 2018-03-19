'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const async = require('async')
const uuidV4 = require('uuid/v4')

class TCObject {
    constructor(properties) {
        if (properties) {
            this.configureWithProperties(properties)
        }
    }

    tableName() {
        throw new Error(`Attempted a database operation on a TCObject with an undefined tableName() function. Please define tableName() on your extended TCObject class.`)
    }

    identifierName() {
        throw new Error(`Attempted a database operation on a TCObject with an undefined identifierName() function. Please define identifierName() on your extended TCObject class.`)
    }

    columnNames() {
        const id = this.identifierName()
        return Array.isArray(id) ? id : [id]
    }

    requiredColumnNamesForAdding() {
        const id = this.identifierName()
        return Array.isArray(id) ? id : [id]
    }

    read(dbConnection, callback) {
        if (!dbConnection) {
            callback(new Error(`TCObject.read() was passed a null dbConnection.`))
            return
        }

        // Get "this" into a variable. Javascript doesn't like this['identifier'].
        let myInstance = this

        if(!this.areIdentifiersSet()) {
            callback(new Error(`TCObject.read() was called with an undefined identifier (${this.identifierName()}).`))
            return
        }

        var myTableName = null
            try {
                myTableName = this.tableName()
            } catch (e) {
                callback(e)
                return
            }
        
        const whereInfo = this.createWhereInformation()
        const sql = `SELECT * FROM ${myTableName} ${whereInfo.sqlString}`

        // Read the record from the database and fill out its values
        dbConnection.query(sql, whereInfo.values, function(err, result) {
            if (err) {
                callback(err)
            } else {
                if (result && result.rows && result.rows.length > 0) {
                    let row = result.rows[0]
                    for (let propertyName of myInstance.columnNames()) {
                        let propertyValue = row[propertyName]
                        if (row != undefined) {
                            myInstance[propertyName] = propertyValue
                        }
                    }
                    callback(null, myInstance)
                } else {
                    // Record does not exist. Let's not throw an error, but
                    // just return a 'false' as the result.
                    callback(null, false)
                }
            }
        })
    }

    add(dbConnection, callback) {
        if (!dbConnection) {
            callback(new Error(`TCObject.add() was passed a null dbConnection.`))
            return
        }

        // Get "this" into a variable. Javascript doesn't like this['identifier'].
        let myInstance = this

        // Make sure the new object to add has an identifier
        if (!this.areIdentifiersSet()) {
            if (this.identifierName().isArray) {
                callback(new Error(`TCObject.add() has insufficient identifiers to create a record.`))
                return
            }
            else {
                this[this.identifierName()] = uuidV4()
            }
        }

        // Verify that all the required properties are specified
        for (let propertyName of this.requiredColumnNamesForAdding()) {
            if (myInstance[propertyName] == undefined) {
                callback(new Error(`A required property (${propertyName}) is undefined.`))
                return
            }
        }

        var propertiesString = ''
        var valuePlaceholders = ''
        var propertyValues = []

        if (this.supportsDirty()) {
            myInstance['dirty'] = this.shouldSetDirty() ? 1 : 0
        }

        // Get the list of values that are set on this instance
        for (let propertyName of this.columnNames()) {
            if (myInstance[propertyName] != undefined) {
                if (propertiesString.length > 0) {
                    propertiesString += ','
                    valuePlaceholders += ','
                }
                propertiesString += propertyName
                valuePlaceholders += '?'
                propertyValues.push(myInstance[propertyName])
            }
        }

        var myTableName = null
        try {
            myTableName = this.tableName()
        } catch (e) {
            callback(e)
            return
        }
        let sql = `INSERT INTO ${myTableName} (${propertiesString}) VALUES (${valuePlaceholders})`

        // logger.debug(`SQL: ${sql}`)
        // logger.debug(`Property values: ${propertyValues}`)

        dbConnection.query(sql, propertyValues, (err, result) => {
            if (err) {
                logger.debug(`Error adding a new record into the database: ${err.message}`)
                callback(err)
            } else {
                callback(null, myInstance)
            }
        })
    }

    update(dbConnection, callback) {
        if (!dbConnection) {
            callback(new Error(`TCObject.update() was passed a null dbConnection.`))
            return
        }

        // Get "this" into a variable. Javascript doesn't like this['identifier'].
        var myInstance = this

        if (!this.areIdentifiersSet()) {
            // An 'identifier' is required for an update to work
            callback(new Error(`TCObject.update() was called with an undefined identifier (${this.identifierName()}).`))
            return
        }
        
        var setPropertiesString = ''
        var propertyValues = []

        // Get the list of values that are set on this instance
        for (let propertyName of this.columnNames()) {
            if (myInstance[propertyName] !== undefined) {
                if (setPropertiesString.length > 0) {
                    setPropertiesString += ','
                }
                setPropertiesString += `${propertyName} = ?`
                propertyValues.push(myInstance[propertyName])
            }
        }

        // If no property values are present, there's really nothing
        // to do.
        if (propertyValues.length == 0) {
            callback(null, myInstance)
            return
        }

        if (this.supportsDirty()) {
            setPropertiesString += `,dirty=`

            setPropertiesString += this.shouldSetDirty() ? `1` : `0`
        }

        // Add on the identifier to the end of the propertyValues so that the update statement
        // knows what record to update.
        

        var myTableName = null
        try {
            myTableName = this.tableName()
        } catch (e) {
            callback(e)
            return
        }
        const whereInfo = this.createWhereInformation()
        const sql = `UPDATE ${myTableName} SET ${setPropertiesString} ${whereInfo.sqlString}`

        // logger.debug(`SQL: ${sql}`)
        // logger.debug(`Property values: ` + JSON.stringify(propertyValues))

        dbConnection.query(sql, propertyValues.concat(whereInfo.values), (err, result) => {
            if (err) {
                logger.debug(`Error updating an object in the database: ${err.message}`)
                callback(err)
            } else {
                callback(null, myInstance)
            }
        })
    }

    delete(dbConnection, callback) {
        if (!dbConnection) {
            callback(new Error(`TCObject.delete() was passed a null dbConnection.`))
            return
        }

        if (!this.areIdentifiersSet()) {
            // An 'identifier' is required for an delete to work
            callback(new Error(`TCObject.delete() was called with an undefined identifier (${this.identifierName()}).`))
            return
        }

        var myTableName = null
        try {
            myTableName = this.tableName()
        } catch (e) {
            callback(e)
            return
        }
        const whereInfo = this.createWhereInformation()
        const sql = `DELETE FROM ${myTableName} ${whereInfo.sqlString}`

        // logger.debug(`DELETE Object SQL: ${sql}: ${JSON.stringify(whereInfo.values)}`)

        dbConnection.query(sql, whereInfo.values, (err, result) => {
            if (err) {
                logger.debug(`Error deleting a record from the database: ${err.message}`)
                callback(err)
            } else {
                if (Array.isArray(this.identifierName())) {
                    let identifierValues = []
                    for(let identifierComponent of this.identifierName()) {
                        identifierValues.push(this[identifierComponent])
                    }
                    callback(null, identifierValues)
                } else {
                    callback(null, this[this.identifierName()])
                }
            }
        })
    }

    configureWithProperties(properties) {
        if (!properties) return

        // Get "this" into a variable. Javascript doesn't seem to like this['identifier'].
        // Correction: in functions with dynamic context, 'this' may be a different 'this'
        // than originally thought.
        let myInstance = this

        // Get the list of values that are set on this instance
        for (let propertyName of myInstance.columnNames()) {
            let propertyValue = properties[propertyName]
            if (propertyValue !== undefined) {
                myInstance[propertyName] = propertyValue
            }
        }

        if (properties['isSyncService'] !== undefined) {
            myInstance['isSyncService'] = properties['isSyncService']
        }
    }

    areIdentifiersSet() {
        const myInstance = this
        if (Array.isArray(this.identifierName())) { 
            for (let identifierComponent of this.identifierName()) {
                if (myInstance[identifierComponent] == undefined) {
                    return false
                }
            }
        }
        else { // For when only a single field is needed to identify a record
            if (myInstance[this.identifierName()] == undefined) {
                return false
            }
        }

        return true
    }

    createWhereInformation() {
        const myInstance = this
        const identifierValues = []
        let whereString = 'WHERE '
        // For when multiple fields are required to identify a record
        if (Array.isArray(this.identifierName())) { 
            let isFirstComponent = true
            for (let identifierComponent of this.identifierName()) {
                if (!isFirstComponent) {
                    whereString += 'AND '
                }
                isFirstComponent = false
                whereString += `${identifierComponent} = ? `

                identifierValues.push(myInstance[identifierComponent])
            }
        }
        else { // For when only a single field is needed to identify a record
            whereString += `${this.identifierName()} = ?`
            identifierValues.push(myInstance[this.identifierName()])
        }

        return { sqlString : whereString, values : identifierValues }
    }

    supportsDirty() {
        const hasDirtyColumn = this.columnNames().indexOf('dirty') >= 0
        return hasDirtyColumn
    }

    shouldSetDirty() {
        // Only pay attention to the dirty flag if isSyncService is set.
        // Otherwise, assume that any other call is NOT being called by
        // the sync service and we should mark the object dirty.
        return (this.supportsDirty() && (this['isSyncService'] == undefined || this['isSyncService'] == false || (this['dirty'] !== undefined && this['dirty'] === true)))
    }
}

module.exports = TCObject

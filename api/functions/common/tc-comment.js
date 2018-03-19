'use strict'

const uuidV4 = require('uuid/v4')

const TCObject = require('./tc-object')

class TCComment extends TCObject {
    tableName() {
        return 'tdo_comments'
    }

    identifierName() {
        return 'commentid'
    }

    columnNames() {
        return super.columnNames().concat(['userid', 'itemid', 'item_type', 'item_name', 'text', 'timestamp', 'deleted'])
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['userid', 'itemid'])
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

module.exports = TCComment
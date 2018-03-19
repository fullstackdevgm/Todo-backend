'use strict'

const uuidV4 = require('uuid/v4')

const TCObject = require('./tc-object')

class TCTag extends TCObject {
    tableName() {
        return 'tdo_tags'
    }

    identifierName() {
        return 'tagid'
    }

    columnNames() {
        return super.columnNames().concat(['name', 'count'])
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['name'])
    }
}

class TCTagAssignment extends TCObject {
    tableName() {
        return 'tdo_tag_assignments'
    }

    identifierName() {
        return ['tagid', 'taskid']
    }
}

module.exports = { 
    TCTag : TCTag,
    TCTagAssignment : TCTagAssignment
}
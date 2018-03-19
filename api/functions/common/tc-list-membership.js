'use strict'

const TCObject = require('./tc-object')

class TCListMembership extends TCObject {
    tableName() {
        return 'tdo_list_memberships'
    }

    identifierName() {
        // I don't know what to do do about this guy because it
        // needs multiple fields to uniquely identify a record.
        // (listid and userid)
        return ['listid', 'userid']
    }

    columnNames() {
        return ['listid', 'userid', 'membership_type']
    }

    requiredColumnNamesForAdding() {
        return ['listid', 'userid']
    }
}

module.exports = TCListMembership
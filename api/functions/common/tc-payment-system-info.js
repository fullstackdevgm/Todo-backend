'use strict'

const TCObject = require('./tc-object')

class TCPaymentSystemInfo extends TCObject {
    tableName() {
        return 'tdo_user_payment_system'
    }

    identifierName() {
        return 'userid'
    }

    columnNames() {
        return super.columnNames().concat(['payment_system_type', 'payment_system_userid'])
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['payment_system_type', 'payment_system_userid'])
    }
}

module.exports = TCPaymentSystemInfo
'use strict'

const uuidV4 = require('uuid/v4')

const TCObject = require('./tc-object')

class TCIAPAutorenewReceipt extends TCObject {
    tableName() {
        return 'tdo_iap_autorenew_receipts'
    }

    identifierName() {
        return 'userid'
    }

    columnNames() {
        return super.columnNames().concat(['latest_receipt_data', 'expiration_date', 'transaction_id', 'autorenewal_canceled'])
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['latest_receipt_data', 'expiration_date', 'transaction_id', 'autorenewal_canceled'])
    }
}

module.exports = TCIAPAutorenewReceipt
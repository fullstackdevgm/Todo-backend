'use strict'

const uuidV4 = require('uuid/v4')

const TCObject = require('./tc-object')

class TCGooglePlayAutorenewToken extends TCObject {
    tableName() {
        return 'tdo_googleplay_autorenew_tokens'
    }

    identifierName() {
        return 'userid'
    }

    columnNames() {
        return super.columnNames().concat(['product_id', 'token', 'expiration_date', 'autorenewal_canceled'])
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['product_id', 'token', 'expiration_date', 'autorenewal_canceled'])
    }
}

module.exports = TCGooglePlayAutorenewToken
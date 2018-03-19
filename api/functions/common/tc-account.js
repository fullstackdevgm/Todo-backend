'use strict'

const TCObject = require('./tc-object')
const md5 = require('md5')

class TCAccount extends TCObject {
    tableName() {
        return 'tdo_user_accounts'
    }

    identifierName() {
        return 'userid'
    }

    columnNames() {
        if (process.env.DB_TYPE != 'sqlite') {
            return super.columnNames().concat(['username', 'password', 'email_verified', 'email_opt_out', 'first_name', 'last_name', 'creation_timestamp', 'locale', 'best_match_locale', 'selected_locale', 'last_reset_timestamp', 'image_guid', 'image_update_timestamp'])
        } else {
            return super.columnNames().concat(['username', 'first_name', 'last_name', 'creation_timestamp', 'image_guid'])
        }
    }

    requiredColumnNamesForAdding() {
        if (process.env.DB_TYPE != 'sqlite') {
            return super.requiredColumnNamesForAdding().concat(['username', 'password', 'first_name', 'last_name', 'creation_timestamp'])
        } else {
            return super.requiredColumnNamesForAdding().concat(['username', 'first_name', 'last_name', 'creation_timestamp'])
        }
    }

    add(dbConnection, callback) {
        // Make sure that we set a creation timestamp before passing this on to super
        this.creation_timestamp = Math.floor(Date.now() / 1000)

        // Also hash the password before storing it into the database
        var myInstance = this
        if (myInstance['password'] !== undefined) {
            this.password = md5(this.password)
        }

        super.add(dbConnection, function(err, account) {
            // Normally on a TCObject update, we respond with
            // all of the properties of the object. In this
            // case, we want to make sure that we don't send
            // the hashed password back.
            if (err) {
                callback(err)
            } else {
                if (account.password !== undefined) {
                    account.password = true
                }
                callback(null, account)
            }
        })
    }

    update(dbConnection, callback) {
        // If the user specified a password, make sure to hash it
        // before updating it into the database.
        var myInstance = this
        if (myInstance['password'] && typeof myInstance['password'] == 'string') {
            this.password = md5(this.password.trim())
        }

        super.update(dbConnection, function(err, account) {
            // Normally on a TCObject update, we respond with
            // all of the properties that were changed, but in
            // the case of when we're changing the password,
            // we shouldn't send the hashed password back to
            // the client. Instead, let's just set it to true
            // to at least indicate that the server recognizes
            // that the password was changed successfully.
            if (err) {
                callback(err)
            } else {
                if (account.password !== undefined) {
                    account.password = true
                }
                callback(null, account)
            }
        })
    }
}

module.exports = TCAccount
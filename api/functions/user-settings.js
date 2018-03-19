const async = require('async')

const TCUserSettingsService = require('./common/tc-user-settings-service')

exports.getUserSettings = function(event, context, callback) {
    var userid = event.userid

    TCUserSettingsService.userSettingsForUserId({userid:userid}, function(err, userSettings) {
        if (err) {
            try {
                var errObj = JSON.parse(err.message)
                if (errObj.httpStatus > 0) {
                    callback(err.message)
                    return
                }
            } catch (e) {
                // Intentionally blank, but here to prevent problems
                // crashing if the above JSON.parse() fails.
            }

            var serverErr = errors.serverError
            serverErr.message = `${serverErr.message} - ${err.message}`
            callback(serverErr)
            return
        } else {
            var accountInfo = {user_settings: userSettings}
            callback(null, accountInfo)
        }
    })
}

exports.updateUserSettings = function(event, context, callback) {
    var userid = event.userid // comes from the Authorization Bearer Header (JWT)

    TCUserSettingsService.updateUserSettings({userid:userid, properties:event}, function(err, userSettings) {
        if (err) {
            try {
                var errObj = JSON.parse(err.message)
                if (errObj.httpStatus > 0) {
                    callback(err.message)
                    return
                }
            } catch (e) {
                // Intentionally blank, but here to prevent problems
                // crashing if the above JSON.parse() fails.
            }

            var serverErr = errors.serverError
            serverErr.message = `${serverErr.message} - ${err.message}`
            callback(serverErr)
            return
        } else {
            var accountInfo = {user_settings: userSettings}
            callback(null, accountInfo)
        }
    })
}

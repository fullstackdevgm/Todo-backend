const TCTaskNotificationService = require('./common/tc-task-notification-service')
const errors = require('./common/errors.js')

const handleResult = function(err, result, callback) {
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
        callback(JSON.stringify(serverErr))
        return
    }
    else { 
        callback(null, result) 
    }
}

exports.createNotification = function(event, context, callback) {
    TCTaskNotificationService.createNotification(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.getNotification = function(event, context, callback) {
    TCTaskNotificationService.getNotification(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.updateNotification = function(event, context, callback) {
    TCTaskNotificationService.updateNotification(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.deleteNotification = function(event, context, callback) {
    TCTaskNotificationService.deleteNotification(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.getNotificationsForTask = function(event, context, callback) {
    TCTaskNotificationService.getNotificationsForTask(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.getNotificationsForUser = function(event, context, callback) {
    TCTaskNotificationService.getNotificationsForUser(event, (err, result) => {
        handleResult(err, result, callback)
    })
}
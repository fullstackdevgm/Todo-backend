const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const TCSystemNotificationService = require('./common/tc-system-notification-service')
const TCAccountService = require('./common/tc-account-service')
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

exports.getLatestSystemNotification = function(event, context, callback) {
    if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
                return
            }

            var rp = require('request-promise')
            logger.debug("/system/notification requesting")
            
            const options = {
                uri: `${process.env.TC_API_URL}/system/notification`,
                method: `GET`,
                qs : event,
                json: true,
                headers: {
                    Authorization: `Bearer ${jwt}`,
                    "x-api-key": process.env.TC_API_KEY
                }
            }

            rp(options)
                .then(function(jsonResponse) {
                    logger.debug("/system/notification got response")
                    handleResult(null, jsonResponse, callback)
                })
                .catch(function(err) {
                    logger.debug("/system/notification got error")
                    // logger.debug(err)
                    handleResult(err, null, callback)
                })
        })
	} else {
        TCSystemNotificationService.getLatestSystemNotification(event, (err, result) => {
            handleResult(err, result, callback)
        })
    }
}
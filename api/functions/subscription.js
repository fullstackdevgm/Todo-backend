const async = require('async')

const TCAccountService = require('./common/tc-account-service')
const TCSubscriptionService = require('./common/tc-subscription-service')
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

exports.getSubscription = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
				var rp = require('request-promise')

				const options = {
					uri: `${process.env.TC_API_URL}/subscription`,
					method: `GET`,
					qs: event,
					headers: {
						Authorization: `Bearer ${jwt}`,
						"x-api-key": process.env.TC_API_KEY
					},
					json: true
				}

				rp(options)
					.then(function(jsonResponse) {
						callback(null, jsonResponse)
					})
					.catch(function(err) {
						// An error occurred
						callback(errors.customError(errors.serverError, `Error communicating with the service: ${err}`))
					})
			}
		})
	} else {
        TCSubscriptionService.subscriptionForUserId(event, (err, result) => {
            handleResult(err, result, callback)
        })
    }
}

exports.purchaseSubscription = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
				var rp = require('request-promise')

				const options = {
					uri: `${process.env.TC_API_URL}/subscription/purchase`,
					method: `POST`,
					body: event,
					headers: {
						Authorization: `Bearer ${jwt}`,
						"x-api-key": process.env.TC_API_KEY
					},
					json: true
				}

				rp(options)
					.then(function(jsonResponse) {
						callback(null, jsonResponse)
					})
					.catch(function(err) {
						// An error occurred
						callback(errors.customError(errors.serverError, `Error communicating with the service: ${err}`))
					})
			}
		})
	} else {
        TCSubscriptionService.purchaseSubscription(event, (err, result) => {
            handleResult(err, result, callback)
        })
    }
}

exports.getPaymentHistory = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
				var rp = require('request-promise')

				const options = {
					uri: `${process.env.TC_API_URL}/subscription/purchases`,
					method: `GET`,
					qs: event,
					headers: {
						Authorization: `Bearer ${jwt}`,
						"x-api-key": process.env.TC_API_KEY
					},
					json: true
				}

				rp(options)
					.then(function(jsonResponse) {
						callback(null, jsonResponse)
					})
					.catch(function(err) {
						// An error occurred
						callback(errors.customError(errors.serverError, `Error communicating with the service: ${err}`))
					})
			}
		})
	} else {
        TCSubscriptionService.getPaymentHistoryForUserID(event, (err, result) => {
            handleResult(err, result, callback)
        })
    }
}

exports.downgradeSubscription = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
				var rp = require('request-promise')

				const options = {
					uri: `${process.env.TC_API_URL}/subscription/downgrade`,
					method: `POST`,
					data: event,
					headers: {
						Authorization: `Bearer ${jwt}`,
						"x-api-key": process.env.TC_API_KEY
					},
					json: true
				}

				rp(options)
					.then(function(jsonResponse) {
						callback(null, jsonResponse)
					})
					.catch(function(err) {
						// An error occurred
						callback(errors.customError(errors.serverError, `Error communicating with the service: ${err}`))
					})
			}
		})
	} else {
        TCSubscriptionService.downgradeSubscription(event, (err, result) => {
            handleResult(err, result, callback)
        })
    }
}

exports.resendReceipt = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
				var rp = require('request-promise')

				const options = {
					uri: `${process.env.TC_API_URL}/subscription/purchases/${event.timestamp}/resend_receipt`,
					method: `POST`,
					data: event,
					headers: {
						Authorization: `Bearer ${jwt}`,
						"x-api-key": process.env.TC_API_KEY
					},
					json: true
				}

				rp(options)
					.then(function(jsonResponse) {
						callback(null, jsonResponse)
					})
					.catch(function(err) {
						// An error occurred
						callback(errors.customError(errors.serverError, `Error communicating with the service: ${err}`))
					})
			}
		})
	} else {
        TCSubscriptionService.resendReceipt(event, (err, result) => {
            handleResult(err, result, callback)
        })
    }
}

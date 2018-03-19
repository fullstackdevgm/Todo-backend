var jwt = require('./common/jwt')
var constants = require('./common/constants')
var errors = require('./common/errors')
const db = require('./common/tc-database')
const async = require('async')

var TCAccountService = require('./common/tc-account-service')

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

exports.checkForUpdates = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
		var rp = require('request-promise')
		
		const options = {
			uri: `${process.env.TC_API_URL}/app/latest`,
			method: `GET`,
			qs: event,
			headers: {
				"x-api-key": process.env.TC_API_KEY
			},
			json: true,
			resolveWithFullResponse: true // needed in order to determine the HTTP response code
		}

		rp(options)
			.then(function(response) {
				if (response.statusCode == 204) {
					callback(`{"httpStatus":204}`)
				} else if (response.statusCode == 200) {
					// Figure out the JSON
					console.log(`RESPONSE: ${JSON.stringify(response)}`)
					callback(null, {})
				} else {
					// Some sort of error happened
					callback(errors.serverError)
				}
			})
			.catch(function(err) {
				errors.handleLocalError(err, function(result) {
					callback(result)
				})
			})
	} else {
		TCAccountService.checkForUpdates(event, function(err, result) {
            if (err) {
                try {
                    var errObj = JSON.parse(err.message)
                    if (errObj.httpStatus > 0) {
                        callback(err.message)
                        return
                    }
                } catch (e) {
                    // Intentinally blank, but here to prevent problems
                    // crashing if the above JSON.parse() fails.
                }

                var serverErr = errors.serverError
                serverErr.message = `${serverErr.message} - ${err.message}`
                callback(serverErr)
            } else {
				if (result == null) {
					// When there's no update available, the server responds with
					// nothing, but when running inside of API Gateway, we have
					// to respond with an error and HTTP Status of 204 so it will
					// return the correct information to the client.
					callback(`{"httpStatus":204}`)
				} else {
					callback(null, result)
				}
            }
		})
	}
}

exports.authenticate = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
		var rp = require('request-promise')

		const options = {
			uri: `${process.env.TC_API_URL}/authenticate`,
			method: `POST`,
			body: event,
			json: true,
			headers: {
				"x-api-key": process.env.TC_API_KEY
			}
		}

		rp(options)
			.then(function(jsonResponse) {
				TCAccountService.saveJWT(jsonResponse.token, function(err, saveResult) {
					if (err) {
						callback(err.message)
						return
					} 
					const initParams = {
						userid: jwt.userIDFromToken(jsonResponse.token)
					}
					TCAccountService.initializeLocalAccountIfNeeded(initParams, function(err, result) {
						if (err) {
							callback(err.message)
						} else {
							callback(null, jsonResponse)
						}
					})
				})
			})
			.catch(function(err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
			})
	} else {
		TCAccountService.authenticate(event, function(err, result) {
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
			}

			callback(null, result)
		})
	}
}

exports.refreshAuthentication = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
				var rp = require('request-promise')

				const options = {
					uri: `${process.env.TC_API_URL}/authenticate/refresh`,
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
						TCAccountService.saveJWT(jsonResponse.token, function(err, saveResult) {
							if (err) {
								callback(err.message)
							} else {
								callback(null, jsonResponse)
							}
						})
					})
					.catch(function(err) {
						errors.handleLocalError(err, function(result) {
							callback(result)
						})
					})
			}
		})
	} else {
		TCAccountService.refreshAuthentication(event, function(err, result) {
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
			}

			callback(null, result)
		})
	}
}

exports.createAccount = function(event, context, callback) {
	const sessionTimeout = process.env.SESSION_TIMEOUT || 300 

	if (process.env.DB_TYPE == 'sqlite') {
		var rp = require('request-promise')

		const options = {
			uri: `${process.env.TC_API_URL}/account`,
			method: `POST`,
			body: event,
			json: true,
			headers: {
				"x-api-key": process.env.TC_API_KEY
			}
		}

		rp(options)
			.then(function(account) {
				// Post succeeded
				var token = jwt.createToken(account.userid, event.username, sessionTimeout)
				if (!token) {
					callback(errors.customError(errors.serverError, `Could not generate a JSON Web Token for authentication.`))
				} else {
					var result = {
						userid: account.userid,
						token: account.token
					}

					TCAccountService.saveJWT(account.token, function(err, saveResult) {
						if (err) {
							callback(err)
						} else {
							const initParams = {
								userid: jwt.userIDFromToken(account.token)
							}
							TCAccountService.initializeLocalAccountIfNeeded(initParams, function(err, initialized) {
								if (err) {
									callback(err)
								} else {
									callback(null, result)
								}
							})
						}
					})
				}
			})
			.catch(function(err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
			})
	} else {
		TCAccountService.createAccount(event, function(err, account) {
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
			}

			var token = jwt.createToken(account.userid, account.username, sessionTimeout)
			if (!token) {
				callback(errors.customError(errors.serverError, `Could not generate a JSON Web Token for authentication.`))
			} else {
				var result = {
					userid: account.userid,
					token: token
				}

				callback(null, result)
			}
		})
	}
}

exports.impersonate = function(event, context, completion) {
	TCAccountService.impersonate(event, function(err, result) {
		if (err) {
			try {
				var errObj = JSON.parse(err.message)
				if (errObj.httpStatus > 0) {
					completion(err.message)
					return
				}
			} catch (e) {
				// Intentionally blank, but here to prevent problems
				// crashing if the above JSON.parse() fails.
			}

			var serverErr = errors.serverError
			serverErr.message = `${serverErr.message} - ${err.message}`
			completion(serverErr)
			return
		}

		completion(null, result)
	})
}

// NUKE IT!!!!
exports.removeLocalData = function(event, context, next) {
	async.waterfall([
		function(next) {
			TCAccountService.deleteLocalAccount(function(err, result) {
				next(err, result)
			})
		},
		function(result, next) {
			TCAccountService.saveJWT('', (err, jwtResult) => {
				next(err, result)
			})
		}
	],
	function(err, result) {
		handleResult(err, result, next)
	})
}

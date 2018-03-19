const async = require('async')

const TCAccountService = require('./common/tc-account-service')
const TCSubscriptionService = require('./common/tc-subscription-service')
const TCUserSettingsService = require('./common/tc-user-settings-service')

var errors = require('./common/errors')

exports.getAccountInfo = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                callback.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
                var rp = require('request-promise')

                const options = {
                    uri: `${process.env.TC_API_URL}/account`,
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
                        errors.handleLocalError(err, function(result) {
                            callback(result)
                        })
                    })
            }
        })
	} else {
        var userid = event.userid

        var getExtendedInfo = event.type && event.type === "extended" ? true : false

        // Return account, subscription, and user settings information
        async.waterfall([
            function(callback) {
                // Get the user's account
                TCAccountService.accountForUserId({userid:userid}, function(err, account) {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, account)
                    }
                })
            },
            function(account, callback) {
                if (getExtendedInfo) {
                    TCSubscriptionService.subscriptionForUserId({userid:userid}, function(err, subscription) {
                        if (err) {
                            callback(err)
                        } else {
                            callback(null, account, subscription)
                        }
                    })
                } else {
                    callback(null, account, null)
                }
            },
            function(account, subscription, callback) {
                if (getExtendedInfo) {
                    TCUserSettingsService.userSettingsForUserId({userid:userid}, function(err, userSettings) {
                        if (err) {
                            callback(err)
                        } else {
                            callback(null, account, subscription, userSettings)
                        }
                    })
                } else {
                    callback(null, account, null, null)
                }
            }
        ],
        function(err, account, subscription, userSettings) {
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
                var accountInfo = {account:account}
                if (getExtendedInfo) {
                    accountInfo["subscription"] = subscription
                    accountInfo["user_settings"] = userSettings
                }
                callback(null, accountInfo)
            }
        })
    }
}

exports.updateAccount = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                callback.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
            var rp = require('request-promise')

            const options = {
                uri: `${process.env.TC_API_URL}/account`,
                method: `PUT`,
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
                    errors.handleLocalError(err, function(result) {
                        callback(result)
                    })
                })
            }
        })
	} else {
        var userid = event.userid // comes from the Authorization Bearer Header (JWT)

        // TCSubscriptionService will decide which attributes are allowed to
        // be changed by the user. Good news is that we don't have to sift
        // through any of that here. Just pass on the "event" object, which
        // contains everything sent by the API client.
        TCAccountService.updateAccount({userid:userid, properties:event}, function(err, account) {
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
                var accountInfo = {account: account}
                callback(null, accountInfo)
            }
        })
    }
}

exports.verifyEmail = function(event, context, callback) {
    TCAccountService.verifyEmail(event, function(err, result) {
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
            callback(null, result)
        }
    })
}

exports.resendVerificationEmail = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                callback.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
            var rp = require('request-promise')

            const options = {
                uri: `${process.env.TC_API_URL}/account/email/verify/resend`,
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
                    errors.handleLocalError(err, function(result) {
                        callback(result)
                    })
                })
            }
        })
	} else {
        TCAccountService.resendVerificationEmail(event, function(err, result) {
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
                callback(null, result)
            }
        })
    }
}

exports.requestResetPassword = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
		var rp = require('request-promise')

		const options = {
			uri: `${process.env.TC_API_URL}/account/password/reset`,
			method: `POST`,
			body: event,
			json: true,
			headers: {
				"x-api-key": process.env.TC_API_KEY
			}
		}

		rp(options)
			.then(function(jsonResponse) {
				callback(null, jsonResponse)
			})
			.catch(function(err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
			})
	} else {
        TCAccountService.requestResetPassword(event, function(err, result) {
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
                callback(null, result)
            }
        })
    }
}

exports.passwordReset = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
		var rp = require('request-promise')

		const options = {
			uri: `${process.env.TC_API_URL}/account/password/reset`,
			method: `PUT`,
			body: event,
			json: true,
			headers: {
				"x-api-key": process.env.TC_API_KEY
			}
		}

		rp(options)
			.then(function(jsonResponse) {
				callback(null, jsonResponse)
			})
			.catch(function(err) {
                errors.handleLocalError(err, function(result) {
                    callback(result)
                })
			})
	} else {
        TCAccountService.resetPassword(event, function(err, result) {
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
                callback(null, result)
            }
        })
    }
}

exports.updatePassword = function(event, context, callback) {
    TCAccountService.updatePassword(event, function(err, result) {
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
        else {
            callback(null, result)
        }
    })
}

exports.deleteAccount = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                callback.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
                var rp = require('request-promise')

                const options = {
                    uri: `${process.env.TC_API_URL}/account/${event.userid}`,
                    method: `DELETE`,
                    body: event,
                    headers: {
                        Authorization: `Bearer ${jwt}`,
                        "x-api-key": process.env.TC_API_KEY
                    },
                    json: true
                }

                rp(options)
                    .then(function(jsonResponse) {
                        TCAccountService.deleteLocalAccount(function(err, result) {
                            callback(err, jsonResponse)
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
        TCAccountService.deleteAccount(event, function(err, result) {
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
                callback(null, result)
            }
        })
    }
}

// This function returns two pre-signed URLs where two different
// sizes of profile images can be uploaded (100x100px and 50x50px).
exports.getProfileImageUploadURLs = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                callback.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
                var rp = require('request-promise')

                const options = {
                    uri: `${process.env.TC_API_URL}/account/profile-image/upload-urls`,
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
                        errors.handleLocalError(err, function(result) {
                            callback(result)
                        })
                    })
            }
        })
	} else {
        TCAccountService.getProfileImageUploadURLs(event, function(err, result) {
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
                callback(null, result)
            }
        })
    }
}

// This function should be called after uploading new profile images.
exports.saveUploadedProfileImages = function(event, context, callback) {
	if (process.env.DB_TYPE == 'sqlite') {
        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                callback.handleLocalError(err, function(result) {
                    callback(result)
                })
            } else {
                var rp = require('request-promise')

                const options = {
                    uri: `${process.env.TC_API_URL}/account/profile-image/save`,
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
                        errors.handleLocalError(err, function(result) {
                            callback(result)
                        })
                    })
            }
        })
	} else {
        TCAccountService.saveUploadedProfileImages(event, function(err, result) {
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
                callback(null, result)
            }
        })
    }
}

exports.accountExport = function(event, context, callback) {
	TCAccountService.accountExport(event, function(err, result) {
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


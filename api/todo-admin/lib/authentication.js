const async         = require('async')
const chalk         = require('chalk')
const inquirer      = require('inquirer')
const CLI           = require('clui')
const rp            = require('request-promise')
const Spinner       = CLI.Spinner

const Configstore   = require('configstore')
const pkg           = require('../package.json')
const jwt           = require('jwt-simple')

const conf = new Configstore(pkg.name)

const ConfKey = {
    jwtToken: 'jwtToken'
}


module.exports = {
    getJWTToken : function(deploymentType, completion) {
        async.waterfall([
            function(callback) {
                var jwtToken = conf.get(`${deploymentType}.${ConfKey.jwtToken}`)
                if (jwtToken) {
                    // Make sure the token hasn't expired
                    if (isJWTTokenValid(jwtToken) == false) {
                        // Force the user to reauthenticate
                        jwtToken = null
                    }
                }
                callback(null, jwtToken)
            },
            function(jwtToken, callback) {
                if (!jwtToken) {
                    promptForCredentials(function(credentials) {
                        callback(null, credentials, null)
                    })
                } else {
                    callback(null, null, jwtToken)
                }
            },
            function(credentials, jwtToken, callback) {
                if (credentials) {
                    const spinner = new Spinner('Authenticating, please wait...')
                    spinner.start()
                    authenticate(credentials, function(err, token) {
                        spinner.stop()
                        if (err) {
                            callback(err)
                        } else {
                            // Save the token
                            conf.set(`${deploymentType}.${ConfKey.jwtToken}`, token)

                            callback(null, token)
                        }
                    })
                } else {
                    callback(null, jwtToken)
                }
            }
        ],
        function(err, jwtToken) {
            if (err) {
                console.log(`Could not get a JWT token: ${err}`)
                completion(err)
            } else {
                completion(null, jwtToken)
            }
        })
    },

    currentUsername : function() {
        let jwtToken = conf.get(`${process.env.TC_DEPLOYMENT_TYPE}.${ConfKey.jwtToken}`)
        return usernameFromJWTToken(jwtToken)
    },

    currentUserId : function() {
        let jwtToken = conf.get(`${process.env.TC_DEPLOYMENT_TYPE}.${ConfKey.jwtToken}`)
        return useridFromJWTToken(jwtToken)
    }
}

function promptForCredentials(completion) {
    var questions = [
        {
            name: 'username',
            type: 'input',
            message: 'Enter your Todo Cloud username (email address):',
            validate: function(value) {
                if (value.length) {
                    return true
                } else {
                    return 'Please enter your username.'
                }
            }
        },
        {
            name: 'password',
            type: 'password',
            message: 'Enter your password:',
            validate: function(value) {
                if (value.length) {
                    return true
                } else {
                    return 'Please enter your password.'
                }
            }
        }
    ]

    inquirer.prompt(questions)
        .then(completion)
        .catch(reason => {
            console.log(`Error prompting for credentials: ${reason}`)
            process.exit(1)
        })
}

function authenticate(credentials, completion) {
    if (!credentials || !credentials.username || !credentials.password) {
        completion(new Error(`Missing credentials for authentication.`))
        return
    }

    const options = {
        uri: `${process.env.TC_API_URL}/authenticate`,
        method: `POST`,
        body: {
            username: credentials.username,
            password: credentials.password
        },
        json: true,
        headers: {
            'x-api-key': process.env.TC_API_KEY
        }
    }
    rp(options)
    .then(jsonResponse => {
        if (!jsonResponse || !jsonResponse.token) {
            completion(new Error(`No JWT in authentication response.`))
        } else {
            completion(null, jsonResponse.token)
        }
    })
    .catch(error => {
        completion(error)
    })
}

function usernameFromJWTToken(token) {
    if (!token) {
        return null
    }

    const payload = jwt.decode(token, null, true)
    if (!payload || !payload.data || !payload.data.username) {
        return null
    }

    return payload.data.username
}

function useridFromJWTToken(token) {
    if (!token) {
        return null
    }
    
    const payload = jwt.decode(token, null, true)
    if (!payload || !payload.data || !payload.data.userid) {
        return null
    }

    return payload.data.userid
}

function isJWTTokenValid(token) {
    // Checks to see if the date on the token is valid
    if (!token) {
        return false
    }
    
    const payload = jwt.decode(token, null, true)
    if (!payload || !payload.exp) {
        return false
    }

    const now = Date.now() / 1000

    return now < payload.exp
}
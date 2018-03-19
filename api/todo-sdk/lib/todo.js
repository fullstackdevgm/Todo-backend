'use strict';

const async         = require('async')
const Configstore   = require('configstore')
const pkg           = require('../package.json')
const jwt           = require('jwt-simple')
const rp            = require('request-promise')
const log4js        = require('@log4js-node/log4js-api')
const logger        = log4js.getLogger('todo-api')

Todo.DEFAULT_API_URL = 'https://api.todo-cloud.com/v1'

Todo.PACKAGE_VERSION = require('../package.json').version;

Todo.USER_AGENT = {
    bindings_version: Todo.PACKAGE_VERSION,
    lang: 'node',
    lang_version: process.version,
    platform: process.platform,
    publisher: 'appigo',
    uname: null
};

Todo.USER_AGENT_SERIALIZED = null

var exec = require('child_process').exec;

const conf = new Configstore(pkg.name)

const ConfKey = {
    jwtToken: 'jwtToken'
}

function Todo(key, promptForCredentialsFunction) {
    if (!(this instanceof Todo)) {
        return new Todo(key, promptForCredentialsFunction);
    }

    this._api = {
        jwt: null,
        apiUrl: Todo.DEFAULT_HOST,
        timeout: Todo.DEFUALT_TIMEOUT,
        agent: null,
        dev: false
    };

    this.setApiKey(key);

    this._promptForCredentialsFunction = promptForCredentialsFunction;
}

Todo.prototype = {

    setApiUrl: function(url) {
        if (url) {
            this._setApiField('apiUrl', url);
        }
    },
            
    setApiKey: function(key) {
        if (key) {
          this._setApiField('apiKey', key);
        }
    },
    
    setHttpAgent: function(agent) {
        this._setApiField('agent', agent);
    },
    
    _setApiField: function(key, value) {
        this._api[key] = value;
    },
    
    getApiField: function(key) {
        return this._api[key];
    },
    
    getConstant: function(c) {
        return Todo[c];
    },

    configure: function(completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                // Set up the User Agent string
                if (Todo.USER_AGENT_SERIALIZED) {
                    return callback(null)
                }
                myInstance.getClientUserAgentSeeded(Todo.USER_AGENT, function(cua) {
                    Todo.USER_AGENT_SERIALIZED = cua;
                    callback(null)
                })
            },
            function(callback) {
                // Make sure we have a valid JWT
                myInstance.getJWT(function(err, token) {
                    callback(err)
                })
            }
        ], function(err) {
            if (err) {
                completion(err)
            } else {
                completion(null, true)
            }
        })
    },
        
    // Gets a JSON version of a User-Agent and uses a cached version for a slight
    // speed advantage.
    getClientUserAgent: function(cb) {
        if (Todo.USER_AGENT_SERIALIZED) {
            return cb(Todo.USER_AGENT_SERIALIZED);
        }
        this.getClientUserAgentSeeded(Todo.USER_AGENT, function(cua) {
            Todo.USER_AGENT_SERIALIZED = cua;
            cb(Todo.USER_AGENT_SERIALIZED);
        });
    },
    
    // Gets a JSON version of a User-Agent by encoding a seeded object and
    // fetching a uname from the system.
    getClientUserAgentSeeded: function(seed, cb) {
        var self = this;

        exec('uname -a', function(err, uname) {
            var userAgent = {};
            for (var field in seed) {
                userAgent[field] = encodeURIComponent(seed[field]);
            }

            // URI-encode in case there are unusual characters in the system's uname.
            userAgent.uname = encodeURIComponent(uname) || 'UNKNOWN';

            if (self._appInfo) {
                userAgent.application = self._appInfo;
            }

            cb(JSON.stringify(userAgent));
        });
    },

    getLists: function(completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var lists = null

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/lists`,
                    method: `GET`,
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /lists (GET).`)
                    } else {
                        lists = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, lists)
                })
            }
        ], function(err, lists) {
            if (err) {
                completion(err)
            } else {
                completion(null, lists)
            }
        })
    },

    addList: function(listParams, completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var list = null

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/lists`,
                    method: `POST`,
                    body: listParams,
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /lists (POST).`)
                    } else {
                        list = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, list)
                })
            }
        ], function(err, task) {
            if (err) {
                completion(err)
            } else {
                completion(null, task)
            }
        })
    },

    addTask: function(taskParams, completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var task = null

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/tasks`,
                    method: `POST`,
                    body: taskParams,
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /tasks (POST).`)
                    } else {
                        task = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, task)
                })
            }
        ], function(err, task) {
            if (err) {
                completion(err)
            } else {
                completion(null, task)
            }
        })
    },

    addTasks: function(tasksToAdd, completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var tasks = null

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/tasks`,
                    method: `POST`,
                    body: {tasks : tasksToAdd},
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /tasks (POST).`)
                    } else {
                        tasks = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, tasks)
                })
            }
        ], function(err, tasks) {
            if (err) {
                completion(err)
            } else {
                completion(null, tasks)
            }
        })
    },

    getTask: function(taskid, completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var task = null

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/tasks/${taskid}`,
                    method: `GET`,
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /tasks/${taskid} (GET).`)
                    } else {
                        task = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, task)
                })
            }
        ], function(err, task) {
            if (err) {
                completion(err)
            } else {
                completion(null, task)
            }
        })
    },

    getTasksForList: function(listid, pageOffset, pageSize, completedOnly, completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var tasks = null

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/lists/${listid}/tasks?page=${pageOffset}&page_size=${pageSize}&completed_only=${completedOnly}`,
                    method: `GET`,
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /lists/${listid}/tasks (GET).`)
                    } else {
                        tasks = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, tasks)
                })
            }
        ], function(err, tasks) {
            if (err) {
                completion(err)
            } else {
                completion(null, tasks)
            }
        })
    },

    addTaskito: function(taskitoParams, completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var task = null

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/checklist/items`,
                    method: `POST`,
                    body: taskitoParams,
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /tasks (POST).`)
                    } else {
                        task = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, task)
                })
            }
        ], function(err, task) {
            if (err) {
                completion(err)
            } else {
                completion(null, task)
            }
        })
    },

    getTags: function(completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var tags = null

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/tags`,
                    method: `GET`,
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /tags (GET).`)
                    } else {
                        tags = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, tags)
                })
            }
        ], function(err, tags) {
            if (err) {
                completion(err)
            } else {
                completion(null, tags)
            }
        })
    },

    addTag: function(tagParams, completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var tag = null

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/tags`,
                    method: `POST`,
                    body: tagParams,
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /tags (POST).`)
                    } else {
                        tag = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, tag)
                })
            }
        ], function(err, tag) {
            if (err) {
                completion(err)
            } else {
                completion(null, tag)
            }
        })
    },

    assignTag: function(tagID, taskID, completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var assignment = null

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/tasks/${taskID}/tags/${tagID}`,
                    method: `POST`,
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /tasks/{taskid}/tags/{tagid} (POST).`)
                    } else {
                        assignment = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, assignment)
                })
            }
        ], function(err, assignment) {
            if (err) {
                completion(err)
            } else {
                completion(null, assignment)
            }
        })
    },

    getAccountInfo: function(readUserSettings, completion) {
        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                var error = null
                var accountInfo = null

                var path = `/account`
                if (readUserSettings) {
                    path = `/account?type=extended`
                }

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}${path}`,
                    method: `GET`,
                    json: true,
                    headers: headers
                }
                rp(options)
                .then(jsonResponse => {
                    if (!jsonResponse) {
                        error = new Error(`No response from /account (GET).`)
                    } else {
                        accountInfo = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                })
                .done(() => {
                    callback(error, accountInfo)
                })
            }
        ], function(err, accountInfo) {
            if (err) {
                completion(err)
            } else {
                completion(null, accountInfo)
            }
        })
    },

    authenticate: function(username, password, completion) {
        if (!username || !password) {
            completion(new Error(`Missing username or password for authentication.`))
            return
        }

        const headers = {'x-api-key': this.getApiField('apiKey')}
        if (Todo.USER_AGENT_SERIALIZED) {
            headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
        }

        var error = null
        var token = null

        const options = {
            uri: `${this.getApiField('apiUrl')}/authenticate`,
            method: `POST`,
            body: {
                username: username,
                password: password
            },
            json: true,
            headers: headers
        }
        rp(options)
        .then(jsonResponse => {
            if (!jsonResponse || !jsonResponse.token) {
                error = new Error(`No JWT in authentication response.`)
            } else {
                token = jsonResponse.token
            }
        })
        .catch(e => {
            error = e
        })
        .done(() => {
            completion(error, token)
        })
    },

    beginImpersonation: function(username, reason, completion) {
        if (!username || !reason) {
            completion(new Error(`Missing username or reason parameter.`))
            return
        }

        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/authenticate/impersonate`,
                    method: `GET`,
                    qs: {
                        customerUsername: username,
                        reason: reason
                    },
                    json: true,
                    headers: headers
                }
                var error = null
                var token = null
                rp(options)
                .then(jsonResponse => {
                    logger.debug(`impersonation response: ${JSON.stringify(jsonResponse)}`)
                    if (!jsonResponse || !jsonResponse.token) {
                        error = new Error(`No JWT in authentication response.`)
                    } else {
                        myInstance['_impersonationToken'] = jsonResponse.token
                        token = jsonResponse.token
                    }
                })
                .catch(e => {
                    error = e
                    callback(error)
                })
                .done(function() {
                    callback(error, token)
                })
            }
        ], function(err, impersonationToken) {
            if (err) {
                completion(err)
            } else {
                completion(null, impersonationToken)
            }
        })
    },

    endImpersonation: function() {
        this._impersonationToken = null
    },

    getJWT: function(completion) {
        // If impersonation is currently enabled, return the impersonationToken
        if (this._impersonationToken) {
            completion(null, this._impersonationToken)
            return
        }

        const myInstance = this
        async.waterfall([
            function(callback) {
                var jwtToken = conf.get(`${myInstance.getApiField('apiUrl')}.${ConfKey.jwtToken}`)
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
                    if (!myInstance._promptForCredentialsFunction) {
                        callback(new Error('No function specified to collect credentials!'))
                    } else {
                        myInstance._promptForCredentialsFunction(function(err, credentials) {
                            if (err) {
                                callback(err)
                            } else {
                                callback(null, credentials, null)
                            }
                        })
                    }
                } else {
                    callback(null, null, jwtToken)
                }
            },
            function(credentials, jwtToken, callback) {
                if (credentials) {
                    myInstance.authenticate(credentials.username, credentials.password, function(err, token) {
                        if (err) {
                            callback(err)
                        } else {
                            // Save the token
                            myInstance.saveJWT(token)
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
                logger.debug(`Could not get a JWT token: ${err}`)
                completion(err)
            } else {
                completion(null, jwtToken)
            }
        })
    },

    saveJWT: function(token) {
        conf.set(`${this.getApiField('apiUrl')}.${ConfKey.jwtToken}`, token)
    },

    accountExport: function(customerUsername, completion) {
        if (!customerUsername) {
            completion(new Error(`Missing customerUsername.`))
            return
        }

        const myInstance = this
        async.waterfall([
            function(callback) {
                myInstance.getJWT((err, token) => {
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, token)
                    }
                })
            },
            function(jwt, callback) {
                const headers = {
                    'x-api-key': myInstance.getApiField('apiKey'),
                    'Authorization': `Bearer ${jwt}`
                }
                if (Todo.USER_AGENT_SERIALIZED) {
                    headers['User-Agent'] = Todo.USER_AGENT_SERIALIZED
                }

                const options = {
                    uri: `${myInstance.getApiField('apiUrl')}/account/export`,
                    method: `GET`,
                    qs: {
                        customerUsername: customerUsername
                    },
                    json: true,
                    headers: headers
                }
                let error = null
                let response = []
                rp(options)
                .then(jsonResponse => {
                    logger.debug(`account export response: ${JSON.stringify(jsonResponse)}`)
                    if (!jsonResponse || !jsonResponse.success) {
                        error = new Error(`No response from server.`)
                    } else {
                        response = jsonResponse
                    }
                })
                .catch(e => {
                    error = e
                    callback(error)
                })
                .done(function() {
                    callback(error, response)
                })
            }
        ], function(err, result) {
            if (err) {
                completion(err)
            } else {
                completion(null, result)
            }
        })
    },
    
};

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

module.exports = Todo;

// Expose constructor as a named property to enable mocking with Sinon.JS
module.exports.Todo = Todo;
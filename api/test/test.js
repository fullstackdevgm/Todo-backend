let Constants = require('../functions/common/constants')
let Errors = require('../functions/common/errors')

let async = require('async')
let chai = require('chai')
let chaiHttp = require('chai-http')
let jwt = require('jwt-simple')
let md5 = require('md5')
let moment = require('moment')
require('datejs') // extend the Date object to have moveToNthOccurrence

let assert = chai.assert
let should = chai.should()

let stripe = require('stripe')(
    "DUtytkg84Q5C0KhnhDgxuyqwa5NyVO64" // our test key
)

// This is an API Key specifically for unit testing and only
// enabled for the TEST deployment stage in API Gateway.
const todoCloudAPIKey = "MhRX7MkrNr8UNe1EWENjt749SE6Fhnpv7sOx0C9a"

chai.use(chaiHttp)

let assuming = require('mocha-assume').assuming

// Set this to true in order to see return values from HTTP calls
const shouldOutputDebugMessages = true

// const baseApiUrl = "https://api.todo-cloud.com/test-v1" // TEST
const baseApiUrl = "http://localhost:8989/api/v1"    // DEV 
// const baseApiUrl = "http://localhost:8080/api/v1" // Embedded

// const syncApiUrl = "http://localhost:8989"

const UNIT_TEST_API_KEY = "B9FDB877-85CC-4136-8F0A-59E7CBE60C78"
const LUCKY_NUMBER = "47"

// Run all tests:
const UnitTests = {
    "Unauthenticated": true,
    "Accounts": true,
    "TaskLists": true,
    "SmartLists": true,
    "SmartListTasks": true,
    "Tasks": true,
    "CompletedTasks": true,
    "RepeatingTasks": true,
    "Checklists": true,
    "Tags": true,
    "Comments": true,
    "Notifications": true,
    "TaskCounts": true,
    "Purchases": true,
    "Sharing": true, // The Sharing tests are not supported in the local SQLite Express.js environment
    "Sync": true,
    "AppUpdate": true
}

// const UnitTests = {
//     "Unauthenticated": false,
//     "Accounts": false,
//     "TaskLists": false,
//     "SmartLists": false,
//     "SmartListTasks": false,
//     "Tasks": false,
//     "CompletedTasks": false,
//     "RepeatingTasks": false,
//     "Checklists": false,
//     "Tags": false,
//     "Comments": false,
//     "Notifications": false,
//     "TaskCounts": false,
//     "Purchases": false,
//     "Sharing": false, // The Sharing tests are not supported in the local SQLite Express.js environment
//     "Sync": false,
//     "AppUpdate": true
// }

function hashedAPIKeyWithUserId(userid) {
    var prehash = `${UNIT_TEST_API_KEY}-${userid}-${LUCKY_NUMBER}-${UNIT_TEST_API_KEY}`
    return md5(prehash)
}

const HttpMethod = {
    GET: "GET",
    POST: "POST",
    PUT: "PUT",
    DELETE: "DELETE"
}

// Below, these methods are tested with a non-authenticated
// call to make sure that they respond properly with 401.
const authenticatedRoutes = [
    {
        route: "/account",
        method: HttpMethod.GET
    },
    {
        route: "/account",
        method: HttpMethod.PUT
    },
    {
        route: "/account/1234",
        method: HttpMethod.DELETE
    },
    {
        route: "/account/email/verify/resend",
        method: HttpMethod.POST
    },
    {
        route: "/subscription",
        method: HttpMethod.GET
    },
    {
        route: "/user-settings",
        method: HttpMethod.GET
    },
    {
        route: "/user-settings",
        method: HttpMethod.PUT
    },
    {
        route: "/lists",
        method: HttpMethod.GET
    },
    {
        route: "/lists",
        method: HttpMethod.POST
    },
    {
        route: "/account",
        method: HttpMethod.PUT
    },
    {
        route: "/lists/1234",
        method: HttpMethod.GET
    },
    {
        route: "/lists/1234",
        method: HttpMethod.PUT
    },
    {
        route: "/lists/1234",
        method: HttpMethod.DELETE
    },
    {
        route: "/smart-lists",
        method: HttpMethod.GET
    },
    {
        route: "/smart-lists",
        method: HttpMethod.POST
    },
    {
        route: "/smart-lists/1234",
        method: HttpMethod.GET
    },
    {
        route: "/smart-lists/1234",
        method: HttpMethod.PUT
    },
    {
        route: "/smart-lists/1234",
        method: HttpMethod.DELETE
    },
    {
        route: "/tasks",
        method: HttpMethod.POST
    },
    {
        route: "/tasks/1234",
        method: HttpMethod.GET
    },
    {
        route: "/tasks/1234/tags",
        method: HttpMethod.GET
    },
    {
        route: "/tasks/1234/tags/1234",
        method: HttpMethod.POST
    },
    {
        route: "/tasks/1234/tags/1234",
        method: HttpMethod.DELETE
    },
    {
        route: "/tags",
        method: HttpMethod.GET
    },
    {
        route: "/tags",
        method: HttpMethod.POST
    },
    {
        route: "/tags/1234",
        method: HttpMethod.GET
    },
    {
        route: "/tags/1234",
        method: HttpMethod.PUT
    },
    {
        route: "/tags/1234",
        method: HttpMethod.DELETE
    }
]

// Variables used to set up a test account across multiple test suites
let testUserid = null
let jwtToken = null
let username = "unittester@appigo.com"
let password = "testing"
let firstName = "Unit"
let lastName = "Tester"

function debug(message) {
    if (!shouldOutputDebugMessages) { return }
    if (typeof message === 'string') {
        console.log(message)
    } else {
        if (message["body"] && message["status"]) {
            console.log(`${message.status}: ${JSON.stringify(message.body)}`)
        } else {
            console.log(JSON.stringify(message))
        }
    }
}

function hasDateOccurrence(aDate, dayOfWeek, occurrence) {
    const daysInMonth = Date.getDaysInMonth(aDate.getYear(), aDate.getMonth())
    const movingDate = moment(aDate).startOf('month')
    let dayOccurrences = 0
    for (let dayOfMonth = 0; dayOfMonth <= daysInMonth; dayOfMonth++) {
        const weekdayNumber = movingDate.isoWeekday() % 7
        if (weekdayNumber == dayOfWeek) {
            dayOccurrences++
        }
        movingDate.add(1, 'day')
    }

    return (dayOccurrences >= occurrence)
}

function setUpAccount(credentials, done) {
    // Before hook that will run before all tests in this suite
    debug(`before(${baseApiUrl})`)

    // debug(`Mon:` + moment().isoWeekday("Monday").isoWeekday() % 7)
    // debug(`Tue:` + moment().isoWeekday("Tuesday").isoWeekday() % 7)
    // debug(`Wed:` + moment().isoWeekday("Wednesday").isoWeekday() % 7)
    // debug(`Thu:` + moment().isoWeekday("Thursday").isoWeekday() % 7)
    // debug(`Fri:` + moment().isoWeekday("Friday").isoWeekday() % 7)
    // debug(`Sat:` + moment().isoWeekday("Saturday").isoWeekday() % 7)
    // debug(`Sun:` + moment().isoWeekday("Sunday").isoWeekday() % 7)

    // let weekday = moment().isoWeekday("Sunday").isoWeekday() % 7
    // let week = 5

    // let test = new Date(2017, 1, 29)
    // let occurrenceFound = false
    // while (!occurrenceFound) {
    //     test.addMonths(1)
    //     if (hasDateOccurrence(test, weekday, week)) {
    //         test.moveToNthOccurrence(weekday, week)
    //         occurrenceFound = true
    //     }
    // }
    // debug(`Test Result: ` + test.toISOString())

    // assert(0, `Intentional stop!`)

    // Make sure that the test user account is not present
    //  1. Check to see if the test user exists on the service
    //  2. If the test user exists, delete it and all associated smart lists, lists, tasks, etc.

    // Determine if the account exists by attempting to authenticate. If the
    // authentication succeeds, the account exists and we'll know to delete it.
    async.waterfall([
        function(callback) {
            chai
                .request(baseApiUrl)
                .post("/authenticate")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(credentials)
                .end(function(err, res) {
                    debug(res)
                    if (res.status == 200) {
                        // console.log(`Existing account found: ${username}`)
                        res.body.should.be.a("object")
                        res.body.should.have.property("userid")
                        res.body.should.have.property("token")
                        callback(null, res.body.userid, res.body.token)
                    } else {
                        callback(null, null, null)
                    }
                })
        },
        function(userid, authToken, callback) {
            // console.log(`   userid: ${userid}`)
            // console.log(`authToken: ${authToken}`)
            // If userid & authToken are present, the account DOES exist and
            // we need to issue the deleteAccount() call.
            if (userid && authToken) {
                let hashedAPIKey = {
                    apikey: hashedAPIKeyWithUserId(userid)
                }

                chai
                    .request(baseApiUrl)
                    .del(`/account/${userid}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${authToken}`)
                    .send(hashedAPIKey)
                    .end(function(err, res) {
                        debug(res)
                        if (err) {
                            callback(err)
                        } else if (res.status != 204) {
                            callback(new Error(`Got an invalid response from /account/{userid} (DELETE): ${(res && res.status) ? res.status : "Unknown"}`))
                        } else {
                            // Account deleted successfully
                            callback(null)
                        }
                    })
            } else {
                // The account doesn't exist and we can just continue on.
                callback(null)
            }
        }
    ],
    function(err) {
        assert(!err, `An error occurred setting up for the testing: ${err}`)
        done()
    })
}

function setUpAuthenticatedAccount(done) {
    setUpAccount({ "username": username, "password": password }, function(accountSetupDone) {
        let newUser = {
            "username": username,
            "password": password,
            "first_name": firstName,
            "last_name": lastName
        }
        chai
            .request(baseApiUrl)
            .post("/account")
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .send(newUser)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("userid")
                res.body.should.have.property("token")

                // Save off the userid so tests can use it
                testUserid = res.body.userid

                // Save the JWT token for authenticated testing
                jwtToken = res.body.token
                done()
            })
        })
}

function createAuthenticatedAccount(params, done) {
    setUpAccount({ "username": params.username, "password": params.password }, function(accountSetupDone) {
        chai
        .request(baseApiUrl)
        .post("/account")
        .set("content-type", "application/json")
        .set("x-api-key", todoCloudAPIKey)
        .send(params)
        .end(function(err, res) {
            debug(res)
            res.should.have.status(200)
            res.body.should.be.a("object")
            res.body.should.have.property("userid")
            res.body.should.have.property("token")
            done(res.body.userid, res.body.token)
        })
    })
}

function deleteAuthenticatedAccount(params, done) {
    debug(`after()`)
    debug(`    userid: ${params.userid}`)
    debug(`  jwtToken: ${params.token}`)

    // Delete the test account from the server
    if (params.userid && params.token) {
        let hashedAPIKey = {
            apikey: hashedAPIKeyWithUserId(params.userid)
        }
        chai
            .request(baseApiUrl)
            .del(`/account/${params.userid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${params.token}`)
            .send(hashedAPIKey)
            .end(function(err, res) {
                debug(res)
                assert(err == null, `Received an error deleting the test user account: ${err}`)
                res.should.have.status(204)
                done()
            })
    }
}

function deleteAccount(done) {
    // After hook that will run after all tests in this suite have finished
    debug(`after()`)
    debug(`    userid: ${testUserid}`)
    debug(`  jwtToken: ${jwtToken}`)

    // Delete the test account from the server
    if (testUserid && jwtToken) {
        let hashedAPIKey = {
            apikey: hashedAPIKeyWithUserId(testUserid)
        }
        chai
            .request(baseApiUrl)
            .del(`/account/${testUserid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(hashedAPIKey)
            .end(function(err, res) {
                debug(res)
                assert(err == null, `Received an error deleting the test user account: ${err}`)
                res.should.have.status(204)
                done()
            })
    }
}

describe("Todo Cloud API - Unauthenticated Tests", function() {
    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)
    
    // before(function() {
    //     // runs before all tests in this block
    // })
    // after(function() {
    //     // runs after all tests in this block
    // })
    // beforeEach(function() {
    //     // runs before each test in this block
    // })
    // afterEach(function() {
    //     // runs after each test in this block
    // })
    describe("/account (POST) - Username too long", function() {
        assuming(UnitTests.Unauthenticated).it("Should return 400", function(done) {
            let badUsername = ''
            for (var i = 0; i < Constants.maxUsernameLength; i++) {
                badUsername += 'a'
            }
            badUsername += "@appigo.com"
            let newUser = {
                "username": badUsername,
                "password": "testing",
                "first_name": "Unit",
                "last_name": "Tester"
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.usernameLengthExceeded.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/account (POST) - First name exceeded", function() {
        assuming(UnitTests.Unauthenticated).it("Should return 400", function(done) {
            let name = ''
            for (var i = 0; i < Constants.maxFirstNameLength + 1; i++) {
                name += 'a'
            }
            let newUser = {
                "username": "boyd+unit1@appigo.com",
                "password": "testing",
                "first_name": name,
                "last_name": "Tester"
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.firstNameLengthExceeded.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/account (POST) - Last name exceeded", function() {
        assuming(UnitTests.Unauthenticated).it("Should return 400", function(done) {
            let name = ''
            for (var i = 0; i < Constants.maxFirstNameLength + 1; i++) {
                name += 'a'
            }
            let newUser = {
                "username": "boyd+unit1@appigo.com",
                "password": "testing",
                "first_name": "Unit",
                "last_name": name
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.lastNameLengthExceeded.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/account (POST) - Invalid username (email)", function() {
        assuming(UnitTests.Unauthenticated).it("Should return 400", function(done) {
            let newUser = {
                "username": "boyd+unit1appigo.com",
                "password": "testing",
                "first_name": "Unit",
                "last_name": "Tester"
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.usernameInvalid.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/account (POST) - Invalid username (email contains plus character and not '@appigo.com')", function() {
        assuming(UnitTests.Unauthenticated).it("Should return 400", function(done) {
            let newUser = {
                "username": "boyd+unit1@example.com",
                "password": "testing",
                "first_name": "Unit",
                "last_name": "Tester"
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.usernameInvalidPlusCharacter.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/account (POST) - Password too short", function() {
        assuming(UnitTests.Unauthenticated).it("Should return 400", function(done) {
            let newUser = {
                "username": "boyd+unit1@appigo.com",
                "password": "test",
                "first_name": "Unit",
                "last_name": "Tester"
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.passwordTooShort.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/account (POST) - Missing username", function() {
        assuming(UnitTests.Unauthenticated).it("Should return 400 (MissingParameters)", function(done) {
            let newUser = {
                "password": "testing",
                "first_name": "Unit",
                "last_name": "Tester"
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.missingParameters.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/account (POST) - Missing password", function() {
        assuming(UnitTests.Unauthenticated).it("Should return 400 (MissingParameters)", function(done) {
            let newUser = {
                "username": "boyd+unit1@appigo.com",
                "first_name": "Unit",
                "last_name": "Tester"
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.missingParameters.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/account (POST) - Missing first_name", function() {
        assuming(UnitTests.Unauthenticated).it("Should return 400 (MissingParameters)", function(done) {
            let newUser = {
                "username": "boyd+unit1@appigo.com",
                "password": "testing",
                "last_name": "Tester"
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.missingParameters.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/account (POST) - Missing last_name", function() {
        assuming(UnitTests.Unauthenticated).it("Should return 400 (MissingParameters)", function(done) {
            let newUser = {
                "username": "boyd+unit1@appigo.com",
                "password": "testing",
                "first_name": "Unit"
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.missingParameters.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/lists (GET) Missing JWT Token Test", function() {
        assuming(UnitTests.Unauthenticated).it("Should get Unauthorized (401)", function(done) {
            chai
                .request(baseApiUrl)
                .get("/lists")
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(401)
                    done()
                })
        })
    })

    describe("Routes Requiring Authentication", function() {
        async.each(authenticatedRoutes, function(routeInfo, callback) {
            describe(`${routeInfo.route} (${routeInfo.method})`, function() {
                assuming(UnitTests.Unauthenticated).it("Should return 401 (Unauthorized)", function(done) {
                    if (routeInfo.method == HttpMethod.GET) {
                        chai
                            .request(baseApiUrl)
                            .get(routeInfo.route)
                            .end(function(err, res) {
                                debug(res)
                                res.should.have.status(401)
                                done()
                            })
                    } else if (routeInfo.method == HttpMethod.POST) {
                        chai
                            .request(baseApiUrl)
                            .post(routeInfo.route)
                            .end(function(err, res) {
                                debug(res)
                                res.should.have.status(401)
                                done()
                            })
                    } else if (routeInfo.method == HttpMethod.PUT) {
                        chai
                            .request(baseApiUrl)
                            .put(routeInfo.route)
                            .end(function(err, res) {
                                debug(res)
                                res.should.have.status(401)
                                done()
                            })
                    } else if (routeInfo.method == HttpMethod.DELETE) {
                        chai
                            .request(baseApiUrl)
                            .delete(routeInfo.route)
                            .end(function(err, res) {
                                debug(res)
                                res.should.have.status(401)
                                done()
                            })
                    }
                })
            })
        },
        function(err) {
            if (err) {
                assert(!err, `An error occurred setting up for the testing: ${err}`)
            }
        })
    })
})

describe("Todo Cloud API - Account Tests", function() {

    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    before(function(done) {
        if (!UnitTests.Accounts) {
            done()
            return
        }
        setUpAccount({ "username": username, "password": password }, done)
    })

    after(function(done) {
        if (!UnitTests.Accounts) {
            done()
            return
        }
        deleteAccount(done)
    })

    describe("/account (POST) - Create new user", function() {
        assuming(UnitTests.Accounts).it("Should return a new JWT token (don't store the JWT token)", function(done) {
            let newUser = {
                "username": username,
                "password": password,
                "first_name": firstName,
                "last_name": lastName
            }
            chai
                .request(baseApiUrl)
                .post("/account")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(newUser)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("userid")
                    res.body.should.have.property("token")

                    // Save off the userid so the test user can
                    // be used to test for invalid JWT token.
                    testUserid = res.body.userid

                    // Save a bogus JWT token to make it fail
                    // authorization in the next test in this
                    // suite.
                    jwtToken = `${res.body.token}-bogus`
                    done()
                })
        })
    })

    describe("Bogus JWT Token Test", function() {
        assuming(UnitTests.Accounts).it("Should get Forbidden (403)", function(done) {
            chai
                .request(baseApiUrl)
                .get("/lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(403) // Forbidden
                    done()
                })
        })
    })

    describe("/authenticate (POST) - Authenticate new user", function() {
        assuming(UnitTests.Accounts).it("Should return a new JWT token (save it for test use)", function(done) {
            let credentials = {
                "username": username,
                "password": password
            }
            chai
                .request(baseApiUrl)
                .post("/authenticate")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .send(credentials)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("userid")
                    res.body.should.have.property("token")

                    // Save off the userid and token so that the test user
                    // can be deleted at the end of this test suite.
                    testUserid = res.body.userid
                    jwtToken = res.body.token
                    done()
                })
        })
    })

    describe("/subscription (GET) - Get the user's subscription information", function() {
        assuming(UnitTests.Accounts).it("Should return information about a user's subscription", function(done) {
            chai
                .request(baseApiUrl)
                .get("/subscription")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("subscription")
                    done()
                })
        })
    })

    describe("/authenticate/refresh (GET) - Refresh the user's JWT token", function() {
        assuming(UnitTests.Accounts).it("Should return a new JWT with an updated expiration date", function(done) {
            setTimeout(function() {
                chai
                .request(baseApiUrl)
                .get("/authenticate/refresh")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("userid")
                    res.body.should.have.property("token")
    
                    // Validate that the newly-returned jwtToken has
                    // a newer expiration date than the one we just
                    // used to make this call. Pass in a null secret
                    // key and tell the decode method to NOT verify,
                    // because this isn't the server and we don't
                    // need to verify. We just want the info in the
                    // payload.
                    const newJWTPayload = jwt.decode(res.body.token, null, true)
                    assert(newJWTPayload, `Could not read new JWT payload!`)
    
                    const oldJWTPayload = jwt.decode(jwtToken, null, true)
                    assert(oldJWTPayload, `Could not read old JWT payload!`)
    
                    assert(newJWTPayload.exp > oldJWTPayload.exp, `The new JWT expiration date (${newJWTPayload.exp}) is not newer than the old JWT expiration date (${oldJWTPayload.exp})!`)
    
                    // Save off the new JWT token
                    jwtToken = res.body.token
                    done()
                })
            }, 1000) // wait for 1 second so that the new expiration date will work
        })
    })
})

describe("Todo Cloud API - Task List Tests", function() {

    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    // Variables used across tests
    let listid = null

    before(function(done) {
        if (!UnitTests.TaskLists) {
            done()
            return
        }
        setUpAuthenticatedAccount(done)
    })

    after(function(done) {
        if (!UnitTests.TaskLists) {
            done()
            return
        }
        deleteAccount(done)
    })

    describe("/lists (GET) - Get user lists", function() {
        assuming(UnitTests.TaskLists).it("Should return at least one list", function(done) {
            chai
                .request(baseApiUrl)
                .get("/lists?includeDeleted=false&includeFiltered=true")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    res.body.length.should.be.above(0)
                    done()
                })
        })
    })

    describe("/lists (POST) - Missing list name", function() {
        assuming(UnitTests.TaskLists).it("Should get MissingParameters (400)", function(done) {
            chai
                .request(baseApiUrl)
                .post("/lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.missingParameters.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/lists (POST) - Create a standard list", function() {
        assuming(UnitTests.TaskLists).it("Should succeed (200)", function(done) {
            let newList = {
                name: "Test List"
            }
            chai
                .request(baseApiUrl)
                .post("/lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newList)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("list")
                    res.body.list.should.have.property("listid")
                    res.body.should.have.property("settings")
                    listid = res.body.list.listid
                    done()
                })
        })
    })

    describe("/lists (GET) - Get user lists", function() {
        assuming(UnitTests.TaskLists).it("Should return two lists (Inbox and the one we just created)", function(done) {
            chai
                .request(baseApiUrl)
                .get("/lists?includeDeleted=false&includeFiltered=true")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    res.body.length.should.be.eql(2)
                    done()
                })
        })
    })

    describe("/lists (PUT) - Modify a list", function() {
        assuming(UnitTests.TaskLists).it("Should succeed (200) - Modified list should be sent back", function(done) {
            let params = {
                name: "Changed List Name",
                settings: {
                    icon_name: "4001-guitar",
                    color: "244, 67, 54",
                    sort_order: 47,
                    sort_type: 2,
                    default_due_date: 2
                    // TO-DO: Add in the other properties that we support changing
                }
            }
            chai
                .request(baseApiUrl)
                .put(`/lists/${listid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(params)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("list")
                    res.body.list.should.have.property("listid")
                    res.body.list.should.have.property("name")
                    res.body.list.name.should.be.eql("Changed List Name")
                    res.body.should.have.property("settings")
                    res.body.settings.should.have.property("icon_name")
                    res.body.settings.icon_name.should.be.eql("4001-guitar")
                    res.body.settings.should.have.property("color")
                    res.body.settings.color.should.be.eql("244, 67, 54")
                    res.body.settings.should.have.property("sort_order")
                    res.body.settings.sort_order.should.be.eql(47)
                    res.body.settings.should.have.property("sort_type")
                    res.body.settings.sort_type.should.be.eql(2)
                    res.body.settings.should.have.property("default_due_date")
                    res.body.settings.default_due_date.should.be.eql(2)
                    done()
                })
        })
    })

    describe("/lists/{listid} (GET) - Get the list we created and modified", function() {
        assuming(UnitTests.TaskLists).it("Should see the properties we specified earlier", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("list")
                    res.body.list.should.have.property("listid")
                    res.body.list.should.have.property("name")
                    res.body.list.name.should.be.eql("Changed List Name")
                    res.body.should.have.property("settings")
                    res.body.settings.should.have.property("icon_name")
                    res.body.settings.icon_name.should.be.eql("4001-guitar")
                    done()
                })
        })
    })

    describe("/lists/{listid} (DELETE) - Send bogus list id", function() {
        assuming(UnitTests.TaskLists).it("Should get unauthorized (403)", function(done) {
            chai
                .request(baseApiUrl)
                .delete(`/lists/${listid}-bogus-list-id`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(403)
                    res.body.should.have.property("code").eql(Errors.listMembershipNotFound.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    // This one is kind of a bogus test! On Amazon API Gateway,
    // I don't think it's passing it along to the right method. For now,
    // I'm going to comment this test out because it's not essential or
    // valid for what we need.
    // describe("/lists/{listid} (DELETE) - Missing list id", function() {
    //     assuming(UnitTests.TaskLists).it("Should get not found (404)", function(done) {
    //         chai
    //             .request(baseApiUrl)
    //             .delete(`/lists/`)
    //             .set("content-type", "application/json")
            // .set("x-api-key", todoCloudAPIKey)
    //             .set("Authorization", `Bearer ${jwtToken}`)
    //             .end(function(err, res) {
    //                 res.should.have.status(404)
    //                 done()
    //             })
    //     })
    // })

    describe("/lists/{listid} (DELETE) - Delete the test list", function() {
        assuming(UnitTests.TaskLists).it("Should succeed (204)", function(done) {
            chai
                .request(baseApiUrl)
                .delete(`/lists/${listid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    listid = null
                    done()
                })
        })
    })

    describe("/lists (GET) - Get user lists", function() {
        assuming(UnitTests.TaskLists).it("Should return ONE list (Inbox)", function(done) {
            chai
                .request(baseApiUrl)
                .get("/lists?includeDeleted=false&includeFiltered=true")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    res.body.length.should.be.eql(1)
                    done()
                })
        })
    })

    // TO-DO: Create, modify, get, and delete a list
})

describe("Todo Cloud API - Smart List Tests", function() {

    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    // Variables used across tests
    let listid = null
    let listTimestamp = 0

    before(function(done) {
        if (!UnitTests.SmartLists) {
            done()
            return
        }
        setUpAuthenticatedAccount(done)
    })

    after(function(done) {
        if (!UnitTests.SmartLists) {
            done()
            return
        }
        deleteAccount(done)
    })

    describe("/smart-lists (GET) - Get smart lists", function() {
        assuming(UnitTests.SmartLists).it("Should return built-in smart lists", function(done) {
            chai
                .request(baseApiUrl)
                .get("/smart-lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    res.body.length.should.be.eql(4)
                    done()
                })
        })
    })

    describe("/smart-lists (POST) - Create smart list with missing parameters", function() {
        assuming(UnitTests.SmartLists).it("Should return MissingParameters (400)", function(done) {
            let params = {
                color: "244, 67, 54"
            }
            chai
                .request(baseApiUrl)
                .post("/smart-lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(params)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.missingParameters.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("/smart-lists (POST) - Create smart list", function() {
        assuming(UnitTests.SmartLists).it("Should succeed (200)", function(done) {
            let params = {
                name: "Test Smart List",
                color: "244, 67, 54"
            }
            chai
                .request(baseApiUrl)
                .post("/smart-lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(params)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("listid")
                    res.body.should.have.property("timestamp")
                    res.body.should.have.property("userid").eql(testUserid)
                    res.body.should.have.property("name").eql("Test Smart List")
                    res.body.should.have.property("color").eql("244, 67, 54")
                    listid = res.body.listid
                    done()
                })
        })
    })

    describe("/smart-lists (GET) - Get smart lists", function() {
        assuming(UnitTests.SmartLists).it("Should return built-in smart lists + newly-created list", function(done) {
            chai
                .request(baseApiUrl)
                .get("/smart-lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    res.body.length.should.be.eql(5)
                    done()
                })
        })
    })

    describe("/smart-lists/{listid} (PUT) - Modify smart list", function() {
        assuming(UnitTests.SmartLists).it("Should succeed (200)", function(done) {
            let params = {
                name: "Changed Smart List",
                color: "33, 150, 243"
                // TO-DO: Fill out different things that can be changed in a smart list
            }
            chai
                .request(baseApiUrl)
                .put(`/smart-lists/${listid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(params)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("listid").eql(listid)
                    res.body.should.have.property("timestamp")
                    res.body.should.have.property("name").eql("Changed Smart List")
                    res.body.should.have.property("color").eql("33, 150, 243")
                    listTimestamp = res.body.timestamp
                    done()
                })
        })
    })

    describe("/smart-lists/{listid} (GET) - Get a specific smart list", function() {
        assuming(UnitTests.SmartLists).it("Should succeed (200)", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${listid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("listid").eql(listid)
                    res.body.should.have.property("userid").eql(testUserid)
                    res.body.should.have.property("timestamp").eql(listTimestamp)
                    res.body.should.have.property("name").eql("Changed Smart List")
                    res.body.should.have.property("color").eql("33, 150, 243")
                    // TO-DO: Check more expected properties on /smart-lists/{listid} (GET)
                    done()
                })
        })
    })

    describe("/smart-lists/{listid} (DELETE) - Delete a bogus smart list", function() {
        assuming(UnitTests.SmartLists).it("Should get success (204)", function(done) {
            chai
                .request(baseApiUrl)
                .delete(`/smart-lists/${listid}-bogus-id`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    done()
                })
        })
    })

    describe("/smart-lists/{listid} (DELETE) - Delete a smart list", function() {
        assuming(UnitTests.SmartLists).it("Should get success (204)", function(done) {
            chai
                .request(baseApiUrl)
                .delete(`/smart-lists/${listid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    done()
                })
        })
    })

    describe("/smart-lists (GET) - Get smart lists", function() {
        assuming(UnitTests.SmartLists).it("Should return built-in smart lists (again - after the delete of the previous test)", function(done) {
            chai
                .request(baseApiUrl)
                .get("/smart-lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    res.body.length.should.be.eql(4)
                    done()
                })
        })
    })
})

describe("Todo Cloud API - Task Tests", function() {

    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    // Variables used across tests
    let listid = null
    let inboxid = null
    let smartListid = null
    let taskid = null
    let projectid = null
    let subtaskids = []
    let subtaskIdToDelete = null
    let checklistid = null

    before(function(done) {
        if (!UnitTests.Tasks && !UnitTests.CompletedTasks && !UnitTests.RepeatingTasks) {
            done()
            return
        }
        async.waterfall([
            function(callback) {
                setUpAuthenticatedAccount(function(done) {
                    callback(null)
                })
            },
            function(callback) {
                // Set up a list that we can use to test tasks
                let newList = {
                    name: "Test List"
                }
                chai
                    .request(baseApiUrl)
                    .post("/lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newList)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("list")
                        res.body.list.should.have.property("listid")
                        res.body.should.have.property("settings")
                        listid = res.body.list.listid
                        callback(null)
                    })
            },
            function(callback) {
                let params = {
                    name: "Test Smart List"
                }
                chai
                    .request(baseApiUrl)
                    .post("/smart-lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(params)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("listid")
                        res.body.should.have.property("timestamp")
                        res.body.should.have.property("userid").eql(testUserid)
                        res.body.should.have.property("name").eql("Test Smart List")
                        smartListid = res.body.listid
                        callback(null)
                    })
            }
        ],
        function(err) {
            assert(!err, `An error occurred setting up for the task tests: ${err}`)
            done()
        })
    })

    after(function(done) {
        if (!UnitTests.Tasks && !UnitTests.CompletedTasks && !UnitTests.RepeatingTasks) {
            done()
            return
        }
        deleteAccount(done)
    })

    describe("/lists/{listid}/tasks (GET) - Get tasks from a list", function() {
        assuming(UnitTests.Tasks).it("Should return an empty list of tasks", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks from a smart list", function() {
        assuming(UnitTests.Tasks).it("Should return an empty list of tasks", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${smartListid}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    res.body.tasks.length.should.be.eql(0)
                    done()
                })
        })
    })

    describe("/tasks (POST) - Create a task in the custom list", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            let todayStartTimestamp = moment().startOf("day").unix()
            let todayEndTimestamp = moment().endOf("day").unix()
            let nowTimestamp = moment().unix()
            let newTask = {
                name: "Test Task",
                listid: listid,
                duedate: nowTimestamp,
                priority: Constants.TaskPriority.Medium
            }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTask)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid")
                    res.body.should.have.property("duedate")
                    res.body.duedate.should.be.above(todayStartTimestamp)
                    res.body.duedate.should.be.below(todayEndTimestamp)
                    res.body.should.have.property("priority").eql(Constants.TaskPriority.Medium)
                    taskid = res.body.taskid
                    done()
                })
        })
    })

    describe("/lists/{listid}/tasks (GET) - Get tasks from a list", function() {
        assuming(UnitTests.Tasks).it("Should return an array of tasks with 1 task", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    res.body.tasks.length.should.be.eql(1)
                    res.body.tasks[0].should.have.property("taskid")
                    res.body.tasks[0].should.have.property("name").eql("Test Task")
                    done()
                })
        })
    })

    describe("/tasks (POST) - Create a task in the inbox list", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            // Do not specify a listid so that the system will have to
            // read the inboxid and file the task there.
            let newTask = {
                name: "Inbox Task"
            }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTask)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid")
                    res.body.should.have.property("name").eql("Inbox Task")
                    res.body.should.have.property("listid")
                    inboxid = res.body.listid
                    done()
                })
        })
    })

    describe("/lists/{listid}/tasks (GET) - Get tasks from the inbox", function() {
        assuming(UnitTests.Tasks).it("Should return an array of tasks with 1 task", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/lists/${inboxid}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    res.body.tasks.length.should.be.eql(1)
                    res.body.tasks[0].should.have.property("taskid")
                    res.body.tasks[0].should.have.property("name").eql("Inbox Task")
                    done()
                })
        })
    })

    describe("/tasks (POST) - Create a task due tomorrow in the custom list", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            let tomorrowTimestamp = moment().add(1, "day").unix()
            let newTask = {
                name: "Tomorrow Task",
                listid: listid,
                duedate: tomorrowTimestamp,
                priority: Constants.TaskPriority.High
            }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTask)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    done()
                })
        })
    })

    describe("/tasks (POST) - Create a task due yesterday in the custom list", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            let yesterdayTimestamp = moment().subtract(1, "day").unix()
            let newTask = {
                name: "Yesterday Task",
                listid: listid,
                duedate: yesterdayTimestamp,
                priority: Constants.TaskPriority.Low
            }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTask)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    done()
                })
        })
    })

    describe("/lists/{listid}/tasks (GET) - Check order of tasks in list", function() {
        assuming(UnitTests.Tasks).it("Tomorrow Task should be at end", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    res.body.tasks.length.should.be.eql(3)
                    res.body.tasks[res.body.tasks.length - 1].should.have.property("taskid")
                    res.body.tasks[res.body.tasks.length - 1].should.have.property("name").eql("Tomorrow Task")
                    done()
                })
        })
    })

    describe("/lists/{listid}/tasks (GET) - Get page size of 2 tasks", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200) with 2 tasks", function(done) {
            const page = 0
            const pageSize = 2
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}/tasks?page=${page}&page_size=${pageSize}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    res.body.tasks.length.should.be.eql(2)
                    done()
                })
        })
    })

    describe("/lists/{listid}/tasks (GET) - Get page size of 2 tasks, page 1", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200) with 1 task", function(done) {
            const page = 1
            const pageSize = 2
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}/tasks?page=${page}&page_size=${pageSize}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    res.body.tasks.length.should.be.eql(1)
                    done()
                })
        })
    })

    describe("/lists/{listid}/tasks (GET) - Get page size of 2 tasks, page 2", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200) with 0 tasks", function(done) {
            const page = 2
            const pageSize = 2
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}/tasks?page=${page}&page_size=${pageSize}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    res.body.tasks.length.should.be.eql(0)
                    done()
                })
        })
    })

    // Change the sort type of the custom list to be alphabetical
    // and then ask for tasks. The "Yesterday Task" should be at the
    // end and the "Test Task" at the beginning.
    describe("/lists (PUT) - Modify a list", function() {
        assuming(UnitTests.Tasks).it("Tasks should be returned in alphabetical order", function(done) {
            let params = {
                settings: {
                    sort_type: Constants.SortType.Alphabetical
                }
            }
            chai
                .request(baseApiUrl)
                .put(`/lists/${listid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(params)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("list")
                    res.body.list.should.have.property("listid")
                    res.body.should.have.property("settings")
                    res.body.settings.should.have.property("sort_type").eql(Constants.SortType.Alphabetical)
                    done()
                })
        })
    })

    describe("/lists/{listid}/tasks (GET) - Check order of tasks in list", function() {
        assuming(UnitTests.Tasks).it("Yesterday Task should be at end", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    res.body.tasks.length.should.be.eql(3)
                    res.body.tasks[0].should.have.property("taskid")
                    res.body.tasks[0].should.have.property("name").eql("Test Task")
                    res.body.tasks[res.body.tasks.length - 1].should.have.property("taskid")
                    res.body.tasks[res.body.tasks.length - 1].should.have.property("name").eql("Yesterday Task")
                    done()
                })
        })
    })

    // Change the sort type of the custom list to be priority based
    // and then ask for tasks. The order should be: Tomorrow Task,
    // Test Task, and Yesterday Task
    describe("/lists (PUT) - Modify a list", function() {
        assuming(UnitTests.Tasks).it("Tasks should be returned in priority order", function(done) {
            let params = {
                settings: {
                    sort_type: Constants.SortType.PriorityDateAlpha
                }
            }
            chai
                .request(baseApiUrl)
                .put(`/lists/${listid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(params)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("list")
                    res.body.list.should.have.property("listid")
                    res.body.should.have.property("settings")
                    res.body.settings.should.have.property("sort_type").eql(Constants.SortType.PriorityDateAlpha)
                    done()
                })
        })
    })

    describe("/lists/{listid}/tasks (GET) - Check order of tasks in list", function() {
        assuming(UnitTests.Tasks).it("Tomorrow Task should be at start", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    res.body.tasks.length.should.be.eql(3)
                    res.body.tasks[0].should.have.property("taskid")
                    res.body.tasks[0].should.have.property("name").eql("Tomorrow Task")
                    res.body.tasks[res.body.tasks.length - 1].should.have.property("taskid")
                    res.body.tasks[res.body.tasks.length - 1].should.have.property("name").eql("Yesterday Task")
                    done()
                })
        })
    })

    describe("/tasks/{taskid} (PUT) - Update a task", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            let params = {
                name: "Changed Test Task",
                listid: inboxid, // move to the inbox
                note: "Test note",
                startdate: moment().subtract(1, "day").unix(),
                duedate: moment().unix(),
                priority: Constants.TaskPriority.Low,
                starred: true
            }
            chai
            .request(baseApiUrl)
            .put(`/tasks/${taskid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("taskid").eql(taskid)
                res.body.should.have.property("name").eql("Changed Test Task")
                res.body.should.have.property("listid").eql(inboxid)
                res.body.should.have.property("note").eql("Test note")
                res.body.should.have.property("startdate").below(moment().startOf("day").unix())
                res.body.should.have.property("duedate").above(moment().startOf("day").unix())
                res.body.should.have.property("duedate").below(moment().endOf("day").unix())
                res.body.should.have.property("priority").eql(Constants.TaskPriority.Low)
                res.body.should.have.property("starred").above(0)
                done()
            })
        })
    })

    describe("/tasks/{taskid} (GET) - Read a task", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            chai
            .request(baseApiUrl)
            .get(`/tasks/${taskid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("taskid").eql(taskid)
                res.body.should.have.property("name").eql("Changed Test Task")
                res.body.should.have.property("listid").eql(inboxid)
                res.body.should.have.property("note").eql("Test note")
                res.body.should.have.property("startdate").below(moment().startOf("day").unix())
                res.body.should.have.property("duedate").above(moment().startOf("day").unix())
                res.body.should.have.property("duedate").below(moment().endOf("day").unix())
                res.body.should.have.property("priority").eql(Constants.TaskPriority.Low)
                res.body.should.have.property("starred").above(0)
                done()
            })
        })
    })

    describe("/lists/{listid}/tasks (GET) - Test task should not be in custom list", function() {
        assuming(UnitTests.Tasks).it("Custom list should have 2 tasks", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    res.body.tasks.length.should.be.eql(2)
                    done()
                })
        })
    })

    describe("/tasks/{taskid} (PUT) - Attempt to update a task's completion date", function() {
        assuming(UnitTests.Tasks).it("Should fail (400)", function(done) {
            let params = {
                completiondate: moment().unix()
            }
            chai
            .request(baseApiUrl)
            .put(`/tasks/${taskid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(400)
                res.body.should.be.a("object")
                res.body.should.have.property("code").eql(Errors.invalidParameters.errorType)
                done()
            })
        })
    })

    describe("/tasks/{taskid} (PUT) - Attempt to update a task's deleted property", function() {
        assuming(UnitTests.Tasks).it("Should fail (400)", function(done) {
            let params = {
                deleted: true
            }
            chai
            .request(baseApiUrl)
            .put(`/tasks/${taskid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(400)
                res.body.should.be.a("object")
                res.body.should.have.property("code").eql(Errors.invalidParameters.errorType)
                done()
            })
        })
    })

    describe("/tasks (POST) - Create a deleted task", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            let newTask = {
                name: "Deleted Task",
                listid: listid,
                deleted: true
            }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTask)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid")
                    res.body.should.have.property("deleted").above(0)
                    taskid = res.body.taskid
                    done()
                })
        })
    })

    describe("/tasks/{taskid} (GET) - Read a deleted task", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            chai
            .request(baseApiUrl)
            .get(`/tasks/${taskid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("taskid").eql(taskid)
                res.body.should.have.property("name").eql("Deleted Task")
                res.body.should.have.property("listid").eql(listid)
                res.body.should.have.property("deleted").above(0)
                res.body.should.have.property("_tableName").eql(Constants.TasksTable.Deleted)
                done()
            })
        })
    })

    describe("/tasks (POST) - Create a completed task", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            let todayStartTimestamp = moment().startOf("day").unix()
            let todayEndTimestamp = moment().endOf("day").unix()
            let nowTimestamp = moment().unix()
            let newTask = {
                name: "Completed Task",
                listid: listid,
                completiondate: nowTimestamp
            }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTask)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid")
                    res.body.should.have.property("completiondate")
                    res.body.completiondate.should.be.above(todayStartTimestamp)
                    res.body.completiondate.should.be.below(todayEndTimestamp)
                    taskid = res.body.taskid
                    done()
                })
        })
    })

    describe("/tasks/{taskid} (GET) - Read a completed task", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            let todayStartTimestamp = moment().startOf("day").unix()
            let todayEndTimestamp = moment().endOf("day").unix()
            chai
            .request(baseApiUrl)
            .get(`/tasks/${taskid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("taskid").eql(taskid)
                res.body.should.have.property("name").eql("Completed Task")
                res.body.should.have.property("listid").eql(listid)
                res.body.should.have.property("completiondate").above(moment().startOf("day").unix())
                res.body.should.have.property("completiondate").below(moment().endOf("day").unix())
                res.body.should.have.property("_tableName").eql(Constants.TasksTable.Completed)
                done()
            })
        })
    })

    describe("/tasks/{taskid} (DELETE) - Delete a completed task", function() {
        assuming(UnitTests.Tasks).it("Should succeed (204)", function(done) {
            chai
                .request(baseApiUrl)
                .delete(`/tasks/${taskid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    done()
                })
        })
    })

    describe("/tasks/{taskid} (GET) - Read a deleted, completed task", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            chai
            .request(baseApiUrl)
            .get(`/tasks/${taskid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("taskid").eql(taskid)
                res.body.should.have.property("name").eql("Completed Task")
                res.body.should.have.property("listid").eql(listid)
                res.body.should.have.property("deleted").above(0)
                res.body.should.have.property("_tableName").eql("tdo_deleted_tasks")
                done()
            })
        })
    })

    describe("/tasks (POST) - Create a normal task to be deleted", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            let newTask = {
                name: "Task to Delete",
                listid: listid
            }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTask)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid")
                    res.body.should.have.property("name").eql("Task to Delete")
                    taskid = res.body.taskid
                    done()
                })
        })
    })

    describe("/tasks/{taskid} (DELETE) - Delete a normal task", function() {
        assuming(UnitTests.Tasks).it("Should succeed (204)", function(done) {
            chai
                .request(baseApiUrl)
                .delete(`/tasks/${taskid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    done()
                })
        })
    })

    describe("/tasks/{taskid} (GET) - Read a deleted task", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            chai
            .request(baseApiUrl)
            .get(`/tasks/${taskid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("taskid").eql(taskid)
                res.body.should.have.property("name").eql("Task to Delete")
                res.body.should.have.property("listid").eql(listid)
                res.body.should.have.property("_tableName").eql(Constants.TasksTable.Deleted)
                done()
            })
        })
    })

    describe("/tasks (POST) - Create a project in the custom list", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            let todayStartTimestamp = moment().startOf("day").unix()
            let todayEndTimestamp = moment().endOf("day").unix()
            let nowTimestamp = moment().unix()
            let newTask = {
                name: "Test Project",
                listid: listid,
                duedate: nowTimestamp,
                priority: Constants.TaskPriority.Medium,
                task_type: Constants.TaskType.Project
            }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTask)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid")
                    res.body.should.have.property("duedate")
                    res.body.duedate.should.be.above(todayStartTimestamp)
                    res.body.duedate.should.be.below(todayEndTimestamp)
                    res.body.should.have.property("priority").eql(Constants.TaskPriority.Medium)
                    res.body.should.have.property("task_type").eql(Constants.TaskType.Project)
                    projectid = res.body.taskid
                    done()
                })
        })
    })

    describe("/lists/{listid}/tasks (GET) - Project Task should appear in the custom list", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/lists/${listid}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    let tasks = res.body.tasks
                    let projectTask = tasks.find(function(task, index) {
                        return task.taskid == projectid
                    })
                    assert(projectTask != null && projectTask.taskid == projectid, `Could not find project task in returned tasks from the custom list.`)
                    done()
                })
        })
    })

    describe("/tasks (POST) - Create 10 subtasks", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            async.times(10, function(index, next) {
                let newTask = {
                    name: `Subtask ${index}`,
                    listid: listid,
                    parentid: projectid
                }
                chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("parentid").eql(projectid)
                        let subtaskid = res.body.taskid
                        subtaskids.push(subtaskid)
                        next(null, subtaskid)
                    })

            },
            function(err, subtaskIDs) {
                assert(!err, `Error occurred creating subtasks: ${err}`)
                debug(`Subtask IDs:\n${JSON.stringify(subtaskids)}`)
                done()
            })
        })
    })

    describe("/tasks/{taskid}/subtasks (GET) - Get the 10 subtasks", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/tasks/${projectid}/subtasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    let tasks = res.body.tasks
                    tasks.length.should.be.eql(subtaskids.length)
                    done()
                })
        })
    })

    describe("/tasks/{taskid}/subtasks (GET) - Get 5 subtasks at a time", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            const page = 0
            const pageSize = 5
            chai
                .request(baseApiUrl)
                .get(`/tasks/${projectid}/subtasks?page=${page}&page_size=${pageSize}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    let tasks = res.body.tasks
                    tasks.length.should.be.eql(pageSize)
                    done()
                })
        })
    })

    describe("/tasks/{taskid}/subtasks (GET) - Get 2nd set of 5 subtasks", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            const page = 1
            const pageSize = 5
            chai
                .request(baseApiUrl)
                .get(`/tasks/${projectid}/subtasks?page=${page}&page_size=${pageSize}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    let tasks = res.body.tasks
                    tasks.length.should.be.eql(pageSize)
                    done()
                })
        })
    })

    describe("/tasks/{taskid}/subtasks (GET) - Get 3rd set of 5 subtasks", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200) - and return 0 tasks", function(done) {
            const page = 2
            const pageSize = 5
            chai
                .request(baseApiUrl)
                .get(`/tasks/${projectid}/subtasks?page=${page}&page_size=${pageSize}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    let tasks = res.body.tasks
                    tasks.length.should.be.eql(0)
                    done()
                })
        })
    })

    describe("/tasks/{taskid}/count (GET) - Get subtask count", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200) - and return a count of 10", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/tasks/${projectid}/count`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property('count')
                    res.body.count.should.equal(10)
                    done()
                })
        })
    })
    
    describe("/tasks/{taskid} (DELETE) - Delete a subtask", function() {
        assuming(UnitTests.Tasks).it("Should succeed (204)", function(done) {
            subtaskIdToDelete = subtaskids[0]
            chai
                .request(baseApiUrl)
                .delete(`/tasks/${subtaskIdToDelete}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    subtaskids.splice(0, 1) // delete the subtask id from our local storage
                    done()
                })
        })
    })

    describe("/tasks/{taskid}/subtasks (GET) - Get subtasks after deletion", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200) - only 9 subtasks", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/tasks/${projectid}/subtasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("tasks").be.a("array")
                    let tasks = res.body.tasks
                    let deletedSubtask = tasks.find(function(task, index) {
                        return task.taskid == subtaskIdToDelete
                    })
                    assert(!deletedSubtask, `Deleted subtask was found in the results!`)
                    tasks.length.should.be.eql(subtaskids.length)
                    done()
                })
        })
    })

    describe("/tasks/{taskid} (DELETE) - Delete a project", function() {
        assuming(UnitTests.Tasks).it("Should succeed (204)", function(done) {
            chai
                .request(baseApiUrl)
                .delete(`/tasks/${projectid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    done()
                })
        })
    })

    describe("/tasks/{taskid} (GET) - Read a deleted project", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            chai
            .request(baseApiUrl)
            .get(`/tasks/${projectid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("taskid").eql(projectid)
                res.body.should.have.property("name").eql("Test Project")
                res.body.should.have.property("listid").eql(listid)
                res.body.should.have.property("deleted").above(0)
                res.body.should.have.property("_tableName").eql(Constants.TasksTable.Deleted)
                done()
            })
        })
    })

    describe("/tasks/{taskid} (GET) - Read a subtask after deleting parent project", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200) - subtask should be deleted", function(done) {
            let subtaskid = subtaskids[0]
            chai
            .request(baseApiUrl)
            .get(`/tasks/${subtaskid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("taskid").eql(subtaskid)
                res.body.should.have.property("listid").eql(listid)
                res.body.should.have.property("parentid").eql(projectid)
                res.body.should.have.property("deleted").above(0)
                res.body.should.have.property("_tableName").eql(Constants.TasksTable.Deleted)
                done()
            })
        })
    })

    describe("/tasks (POST) - Create multiple tasks", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200)", function(done) {
            let newTasks = {
                    tasks: [
                        {
                            name: "Bulk Task A",
                            listid: listid,
                            client_taskid: "A"
                        },
                        {
                            name: "Bulk Task B",
                            listid: listid,
                            client_taskid: "B"
                        },
                        {
                            name: "Bulk Task C",
                            listid: listid,
                            client_taskid: "C"
                        },
                        {
                            name: "Bulk Task D",
                            listid: listid,
                            client_taskid: "D"
                        },
                        {
                            name: "Bulk Task E",
                            listid: listid,
                            client_taskid: "E"
                        },
                        {
                            name: "Bulk Task F",
                            listid: listid,
                            client_taskid: "F"
                        },
                        {
                            name: "Bulk Task G",
                            listid: listid,
                            client_taskid: "G"
                        },
                        {
                            name: "Bulk Task H",
                            listid: listid,
                            client_taskid: "H"
                        },
                        {
                            name: "Bulk Task I",
                            listid: listid,
                            client_taskid: "I"
                        },
                        {
                            name: "Bulk Task J",
                            listid: listid,
                            client_taskid: "J"
                        }
                    ]
                }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTasks)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.tasks.should.be.an("array")
                    res.body.tasks.should.not.be.empty

                    res.body.tasks.forEach((aTask) => {
                        aTask.should.have.property("client_taskid")
                        aTask.should.have.property("task")
                        aTask.task.should.have.property("taskid")
                    })
                    done()
                })
        })
    })

    describe("/tasks (POST) - Create multiple tasks with missing task name on one of the tasks.", function() {
        assuming(UnitTests.Tasks).it("Should succeed (200) with an error for one of the tasks.", function(done) {
            let newTasks = {
                    tasks: [
                        {
                            name: "Bulk Task A",
                            listid: listid,
                            client_taskid: "A"
                        },
                        {
                            listid: listid,
                            client_taskid: "B"
                        },
                        {
                            name: "Bulk Task C",
                            listid: listid,
                            client_taskid: "C"
                        }
                    ]
                }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTasks)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.tasks.should.be.an("array")
                    res.body.tasks.should.not.be.empty

                    var numOfErrors = 0

                    res.body.tasks.forEach((aTask) => {
                        aTask.should.have.property("client_taskid")
                        if (aTask.error) {
                            numOfErrors++
                        } else {
                            aTask.should.have.property("task")
                            aTask.task.should.have.property("taskid")
                        }
                    })
                    assert(numOfErrors == 1, `Expected one task to get an error about a missing name parameter.`)
                    
                    done()
                })
        })
    })

    describe("/tasks (POST) - Attempt to create too many multiple tasks", function() {
        assuming(UnitTests.Tasks).it("Should fail (403)", function(done) {
            var tasks = Array()
            for (var i = 0; i < Constants.maxCreateBulkTasks + 1; i++) {
                tasks.push({
                    name: `Bulk Task ${i}`,
                    listid: listid,
                    client_taskid: `clientid_${i}`
                })
            }

            let newTasks = {
                tasks: tasks
            }
            chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTasks)
                .end(function(err, res) {
                    debug(res)
                    res.body.should.have.property("code").eql(Errors.maxBulkTasksExceeded.errorType)
                    res.body.should.have.property("message").eql(Errors.maxBulkTasksExceeded.message)
                    done()
                })
        })
    })

    describe("TASK COMPLETION TESTS", function() {

        let taskIdToUncomplete = null
        let repeatingSubtaskIDs = []

        before(function(done) {
            if (!UnitTests.CompletedTasks) {
                done()
                return
            }
            debug(`        before() for Task Completion Tests...`)
            async.waterfall([
                // Create a basic task to complete
                function(callback) {
                    let newTask = {
                        name: "Task to Complete",
                        listid: listid
                    }
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        taskid = res.body.taskid
                        callback(null)
                    })
                },
                function(callback) {
                    let newTask = {
                        name: "Task to Uncomplete",
                        listid: listid
                    }
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        taskIdToUncomplete = res.body.taskid
                        callback(null)
                    })
                },
                function(callback) {
                    // Create a simple project to complete
                    let newTask = {
                        name: "Project to Complete",
                        listid: listid,
                        task_type: Constants.TaskType.Project
                    }
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("task_type").eql(Constants.TaskType.Project)
                        projectid = res.body.taskid
                        callback(null)
                    })
                },
                function(callback) {
                    // Add 10 subtasks to the project that can be completed
                    subtaskids = []
                    async.times(10, function(index, next) {
                        let newTask = {
                            name: `Subtask ${index}`,
                            listid: listid,
                            parentid: projectid
                        }
                        chai
                            .request(baseApiUrl)
                            .post("/tasks")
                            .set("content-type", "application/json")
                            .set("x-api-key", todoCloudAPIKey)
                            .set("Authorization", `Bearer ${jwtToken}`)
                            .send(newTask)
                            .end(function(err, res) {
                                debug(res)
                                res.should.have.status(200)
                                res.body.should.be.a("object")
                                res.body.should.have.property("taskid")
                                res.body.should.have.property("parentid").eql(projectid)
                                let subtaskid = res.body.taskid
                                subtaskids.push(subtaskid)
                                next(null)
                            })
                    },
                    function(err) {
                        callback(err)
                    })
                },
                function(callback) {
                    // Add 5 repeating subtasks to the project that can be completed
                    async.times(5, function(index, next) {
                        let newTask = {
                            name: `Repeating Subtask ${index}`,
                            listid: listid,
                            parentid: projectid,
                            duedate: Math.floor(Date.now() / 1000),
                            recurrence_type: Constants.TaskRecurrenceType.Weekly
                        }
                        chai
                            .request(baseApiUrl)
                            .post("/tasks")
                            .set("content-type", "application/json")
                            .set("x-api-key", todoCloudAPIKey)
                            .set("Authorization", `Bearer ${jwtToken}`)
                            .send(newTask)
                            .end(function(err, res) {
                                debug(res)
                                res.should.have.status(200)
                                res.body.should.be.a("object")
                                res.body.should.have.property("taskid")
                                res.body.should.have.property("name").eql(newTask.name)
                                res.body.should.have.property("parentid").eql(projectid)
                                res.body.should.have.property("duedate").eql(newTask.duedate)
                                res.body.should.have.property("recurrence_type").eql(newTask.recurrence_type)
                                let subtaskid = res.body.taskid
                                repeatingSubtaskIDs.push(subtaskid)
                                next(null)
                            })
                    },
                    function(err) {
                        callback(err)
                    })
                },
                function(callback) {
                    // Create a simple checklist to complete
                    let newTask = {
                        name: "Checklist to Complete",
                        listid: listid,
                        task_type: Constants.TaskType.Checklist
                    }
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("task_type").eql(newTask.task_type)
                        checklistid = res.body.taskid
                        callback(null)
                    })
                }
            ],
            function(err) {
                assert(!err, `Setup for the task completion tests failed: ${err}`)
                done()
            })
        })

        describe("/tasks/complete (POST) - Complete two tasks", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200) - tasks should be returned as completed", function(done) {
                let tasksToComplete = {
                    "tasks": [
                        taskid,
                        taskIdToUncomplete
                    ]
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/complete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToComplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == 2, `Expected TWO completed tasks.`)
                    tasksToComplete.tasks.forEach((aTaskId, index) => {
                        assert(completedTasks.find(function(completedTaskId, index) {
                            return completedTaskId == aTaskId
                        }) != undefined, `Expected ${aTaskId} as one of the completed tasks.`)
                    })
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (GET) - Read completed tasks", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200)", function(done) {
                const completedTasks = [
                    { "taskid": taskid, "name": "Task to Complete" },
                    { "taskid": taskIdToUncomplete, "name": "Task to Uncomplete" }
                ]
                async.each(completedTasks, function(taskInfo, callback) {
                    chai
                    .request(baseApiUrl)
                    .get(`/tasks/${taskInfo.taskid}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid").eql(taskInfo.taskid)
                        res.body.should.have.property("name").eql(taskInfo.name)
                        res.body.should.have.property("listid").eql(listid)
                        res.body.should.have.property("_tableName").eql(Constants.TasksTable.Completed)
                        callback()
                    })
                },
                function(err) {
                    assert(!err, `Error reading completed tasks: ${err}`)
                    done()
                })
            })
        })

        describe("/lists/{listid}/tasks?completed_only=true (GET) - Read completed tasks", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200)", function(done) {
                const completedTasks = [
                    { "taskid": taskid, "name": "Task to Complete" },
                    { "taskid": taskIdToUncomplete, "name": "Task to Uncomplete" }
                ]
                // The API should work for each of these ways we ask for
                // completed_only.
                const urls = [
                    `/lists/${listid}/tasks?completed_only=true`
                ]
                async.each(urls, function(urlPath, callback) {
                    chai
                    .request(baseApiUrl)
                    .get(urlPath)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.tasks.should.be.an("array")
                        res.body.tasks.should.not.be.empty

                        res.body.tasks.forEach((aTask) => {
                            aTask.should.have.property("taskid")
                            aTask.should.have.property("listid").eql(listid)
                        })

                        completedTasks.forEach((aTask) => {
                            assert(res.body.tasks.find(e => e.taskid == aTask.taskid) != undefined, `Expected to find ${aTask.taskid} in results.`)
                        })
                        callback()
                    })
                },
                function(err) {
                    assert(!err, `Error reading completed only tasks: ${err}`)
                    done()
                })
            })
        })

        describe("/lists/{listid}/tasks?completed_only=false|0 (GET) - Read active tasks", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200)", function(done) {
                const completedTasks = [
                    { "taskid": taskid, "name": "Task to Complete" },
                    { "taskid": taskIdToUncomplete, "name": "Task to Uncomplete" }
                ]
                // Test each of these URLs and make sure that they do not return
                // any completed tasks.
                const urls = [
                    `/lists/${listid}/tasks?completed_only=false`,
                    `/lists/${listid}/tasks`
                ]
                async.each(urls, function(urlPath, callback) {
                    chai
                    .request(baseApiUrl)
                    .get(urlPath)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.tasks.should.be.an("array")
                        res.body.tasks.should.not.be.empty

                        res.body.tasks.forEach((aTask) => {
                            aTask.should.have.property("taskid")
                            aTask.should.have.property("listid").eql(listid)
                        })

                        completedTasks.forEach((aTask) => {
                            assert(res.body.tasks.find(e => e.taskid == aTask.taskid) == undefined, `Expected to NOT find ${aTask.taskid} in results.`)
                        })
                        callback()
                    })
                },
                function(err) {
                    assert(!err, `Error reading active only tasks: ${err}`)
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (DELETE) - Delete a completed task", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (204)", function(done) {
                chai
                    .request(baseApiUrl)
                    .delete(`/tasks/${taskid}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(204)
                        done()
                    })
            })
        })

        describe("/tasks/{taskid} (GET) - Read a deleted completed task", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200)", function(done) {
                chai
                .request(baseApiUrl)
                .get(`/tasks/${taskid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid").eql(taskid)
                    res.body.should.have.property("listid").eql(listid)
                    res.body.should.have.property("_tableName").eql(Constants.TasksTable.Deleted)
                    res.body.should.have.property("deleted").eql(1)
                    done()
                })
            })
        })

        describe("/tasks/uncomplete (POST) - Uncomplete a task", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200) - tasks should be returned as uncompleted", function(done) {
                let tasksToComplete = {
                    "tasks": [
                        taskIdToUncomplete
                    ]
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/uncomplete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToComplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == 1, `Expected ONE uncompleted task.`)
                    tasksToComplete.tasks.forEach((aTaskId, index) => {
                        assert(completedTasks.find(function(completedTaskId, index) {
                            return completedTaskId == aTaskId
                        }) != undefined, `Expected ${aTaskId} as one of the uncompleted tasks.`)
                    })
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (GET) - Read an uncompleted task", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200)", function(done) {
                chai
                .request(baseApiUrl)
                .get(`/tasks/${taskIdToUncomplete}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid").eql(taskIdToUncomplete)
                    res.body.should.have.property("name").eql("Task to Uncomplete")
                    res.body.should.have.property("listid").eql(listid)
                    res.body.should.have.property("_tableName").eql(Constants.TasksTable.Normal)
                    res.body.should.have.property("completiondate").eql(0)
                    done()
                })
            })
        })

        describe("/tasks/complete (POST) - Complete repeating subtasks", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200) - repeating subtasks should be rescheduled", function(done) {
                let tasksToComplete = {
                    "tasks": repeatingSubtaskIDs
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/complete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToComplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == repeatingSubtaskIDs.length, `Expected # of completed tasks to be ${repeatingSubtaskIDs.length}.`)
                    repeatingSubtaskIDs.forEach((subtaskid) => {
                        assert(completedTasks.find((completedTaskId) => {
                            return completedTaskId == subtaskid
                        }), `Expected to find completed repeating subtask (${subtaskid}) as one of the completed tasks.`)
                    })

                    let endOfToday = moment().endOf('day').unix()
                    
                    res.body.should.have.property("repeatedTasks")
                    let repeatedTasks = res.body.repeatedTasks
                    assert(repeatedTasks.length == repeatingSubtaskIDs.length, `Expected ${repeatingSubtaskIDs.length} repeated tasks.`)
                    repeatedTasks.forEach((aTask) => {
                        aTask.should.have.property("_tableName").eql(Constants.TasksTable.Normal)
                        aTask.should.have.property("duedate").above(endOfToday)
                        aTask.should.not.have.property("completiondate")
                        aTask.should.have.property("parentid").eql(projectid)
                        aTask.should.have.property("listid").eql(listid)
                        aTask.should.have.property("taskid")
                        assert(repeatingSubtaskIDs.find((aSubtaskId) => {
                            return aSubtaskId == aTask.taskid
                        }) != undefined, `Didn't find the repeating subtask ids in the repeated tasks.`)
                    })

                    res.body.should.have.property("newTasks")
                    let newTasks = res.body.newTasks
                    assert(newTasks.length == repeatingSubtaskIDs.length, `Expected ${repeatingSubtaskIDs.length} repeated tasks.`)
                    newTasks.forEach((aTask) => {
                        aTask.should.have.property("_tableName").eql(Constants.TasksTable.Completed)
                        aTask.should.have.property("duedate").below(endOfToday)
                        aTask.should.have.property("completiondate").below(endOfToday)
                        aTask.should.have.property("parentid").eql(projectid)
                        aTask.should.have.property("listid").eql(listid)
                    })
                    
                    done()
                })
            })
        })
        
        describe("/tasks/complete (POST) - Basic complete a project", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200) - project should be returned as completed", function(done) {
                let tasksToComplete = {
                    "tasks": [
                        projectid
                    ]
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/complete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToComplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == 1 + subtaskids.length + repeatingSubtaskIDs.length, `Expected # of completed tasks to be project + subtasks + repeating subtasks.`)
                    assert(completedTasks.find(function(completedTaskId, index) {
                        return completedTaskId == projectid
                    }) != undefined, `Expected ${projectid} as one of the completed tasks.`)
                    subtaskids.forEach(function(subtaskid) {
                        assert(completedTasks.find(function(completedTaskId, index) {
                            return completedTaskId == subtaskid
                        }), `Expected to find a completed subtask (${subtaskid}) as one of the completed tasks.`)
                    })
                    repeatingSubtaskIDs.forEach((subtaskid) => {
                        assert(completedTasks.find((completedTaskId) => {
                            return completedTaskId == subtaskid
                        }), `Expected to find completed repeating subtask (${subtaskid}) as one of the completed tasks.`)
                    })
                    done()
                })
            })
        })
        
        describe("/tasks/{taskid} (GET) - Read a completed project", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200)", function(done) {
                chai
                .request(baseApiUrl)
                .get(`/tasks/${projectid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid").eql(projectid)
                    res.body.should.have.property("name").eql("Project to Complete")
                    res.body.should.have.property("listid").eql(listid)
                    res.body.should.have.property("completiondate").above(0)
                    res.body.should.have.property("_tableName").eql(Constants.TasksTable.Completed)
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (GET) - Read completed subtasks", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200) - subtasks should be completed", function(done) {
                async.each(subtaskids, function(subtaskid, callback) {
                    chai
                    .request(baseApiUrl)
                    .get(`/tasks/${subtaskid}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid").eql(subtaskid)
                        res.body.should.have.property("listid").eql(listid)
                        res.body.should.have.property("parentid").eql(projectid)
                        res.body.should.have.property("completiondate").above(0)
                        res.body.should.have.property("_tableName").eql(Constants.TasksTable.Completed)
                        callback()
                    })
                },
                function(err) {
                    assert(!err, `Error reading completed subtasks: ${err}`)
                    done()
                })
            })
        })

        describe("/tasks/uncomplete (POST) - Uncomplete a project", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200) - project should be returned as uncompleted", function(done) {
                let tasksToUncomplete = {
                    "tasks": [
                        projectid
                    ]
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/uncomplete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToUncomplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == 1, `Expected ONE uncompleted task.`)
                    tasksToUncomplete.tasks.forEach((aTaskId, index) => {
                        assert(completedTasks.find(function(completedTaskId, index) {
                            return completedTaskId == aTaskId
                        }) != undefined, `Expected ${aTaskId} as one of the uncompleted tasks.`)
                    })
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (GET) - Read an uncompleted project", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200)", function(done) {
                chai
                .request(baseApiUrl)
                .get(`/tasks/${projectid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid").eql(projectid)
                    res.body.should.have.property("name").eql("Project to Complete")
                    res.body.should.have.property("listid").eql(listid)
                    res.body.should.have.property("completiondate").eql(0)
                    res.body.should.have.property("_tableName").eql(Constants.TasksTable.Normal)
                    done()
                })
            })
        })

        describe("/tasks/uncomplete (POST) - Uncomplete subtasks", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200) - subtasks should be returned as uncompleted", function(done) {
                let tasksToUncomplete = {
                    "tasks": subtaskids
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/uncomplete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToUncomplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == subtaskids.length, `Expected uncompleted task count to match what was sent.`)
                    tasksToUncomplete.tasks.forEach((aTaskId, index) => {
                        assert(completedTasks.find(function(completedTaskId, index) {
                            return completedTaskId == aTaskId
                        }) != undefined, `Expected ${aTaskId} as one of the uncompleted tasks.`)
                    })
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (GET) - Read uncompleted subtasks", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200) - subtasks should be uncompleted", function(done) {
                async.each(subtaskids, function(subtaskid, callback) {
                    chai
                    .request(baseApiUrl)
                    .get(`/tasks/${subtaskid}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid").eql(subtaskid)
                        res.body.should.have.property("listid").eql(listid)
                        res.body.should.have.property("parentid").eql(projectid)
                        res.body.should.have.property("completiondate").eql(0)
                        res.body.should.have.property("_tableName").eql(Constants.TasksTable.Normal)
                        callback()
                    })
                },
                function(err) {
                    assert(!err, `Error reading completed subtasks: ${err}`)
                    done()
                })
            })
        })

        describe("/tasks/complete (POST) - Basic complete a checklist", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200) - checklist should be returned as completed", function(done) {
                let tasksToComplete = {
                    "tasks": [
                        checklistid
                    ]
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/complete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToComplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == 1, `Expected 1 of completed task (checklist).`)
                    assert(completedTasks.find(function(completedTaskId, index) {
                        return completedTaskId == checklistid
                    }) != undefined, `Expected ${checklistid} as the completed tasks.`)
                    done()
                })
            })
        })
        
        describe("/tasks/{taskid} (GET) - Read a completed checklist", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200)", function(done) {
                chai
                .request(baseApiUrl)
                .get(`/tasks/${checklistid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid").eql(checklistid)
                    res.body.should.have.property("name").eql("Checklist to Complete")
                    res.body.should.have.property("listid").eql(listid)
                    res.body.should.have.property("completiondate").above(0)
                    res.body.should.have.property("_tableName").eql(Constants.TasksTable.Completed)
                    done()
                })
            })
        })

        describe("/tasks/uncomplete (POST) - Uncomplete a checklist", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200) - checklist should be returned as uncompleted", function(done) {
                let tasksToUncomplete = {
                    "tasks": [
                        checklistid
                    ]
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/uncomplete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToUncomplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == 1, `Expected ONE uncompleted task.`)
                    tasksToUncomplete.tasks.forEach((aTaskId, index) => {
                        assert(completedTasks.find(function(completedTaskId, index) {
                            return completedTaskId == checklistid
                        }) != undefined, `Expected ${checklistid} as one of the uncompleted tasks.`)
                    })
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (GET) - Read an uncompleted checklist", function() {
            assuming(UnitTests.CompletedTasks).it("Should succeed (200)", function(done) {
                chai
                .request(baseApiUrl)
                .get(`/tasks/${checklistid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid").eql(checklistid)
                    res.body.should.have.property("name").eql("Checklist to Complete")
                    res.body.should.have.property("listid").eql(listid)
                    res.body.should.have.property("completiondate").eql(0)
                    res.body.should.have.property("_tableName").eql(Constants.TasksTable.Normal)
                    done()
                })
            })
        })

    })

    describe("TASK RECURRENCE TESTS", function() {
        const recurringTasks = []
        const advancedEveryXTasks = []
        const advancedXOfMonthTasks = []
        const advancedEveryMonTuesTasks = []

        let completedRecurringTasks = []
        let completedAdvancedEveryXTasks = []
        let completedAdvancedXOfMonthTasks = []
        let completedAdvancedEveryMonTuesTasks = []

        before(function(done) {
            if (!UnitTests.RepeatingTasks) {
                done()
                return
            }
            debug(`        before() for Task Recurrence Tests...`)
            async.waterfall([
                function(callback) {
                    async.each(Object.keys(Constants.TaskRecurrenceType), function(recurrenceTypeKey, eachCallback) {
                        if (Constants.TaskRecurrenceType[recurrenceTypeKey] == Constants.TaskRecurrenceType.None
                                || Constants.TaskRecurrenceType[recurrenceTypeKey] == Constants.TaskRecurrenceType.WithParent
                                || Constants.TaskRecurrenceType[recurrenceTypeKey] == Constants.TaskRecurrenceType.Advanced) {
                            eachCallback(null)
                        } else {
                            // console.log(`Recurrence Key: ${recurrenceTypeKey} = ${Constants.TaskRecurrenceType[recurrenceTypeKey]}`)
                            const taskName = `Recurring ${recurrenceTypeKey} Task`
                            const duedate = Math.floor(Date.now() / 1000)
                            let newTask = {
                                name: taskName,
                                listid: listid,
                                duedate: duedate,
                                recurrence_type: Constants.TaskRecurrenceType[recurrenceTypeKey]
                            }
                            chai
                            .request(baseApiUrl)
                            .post("/tasks")
                            .set("content-type", "application/json")
                            .set("x-api-key", todoCloudAPIKey)
                            .set("Authorization", `Bearer ${jwtToken}`)
                            .send(newTask)
                            .end(function(err, res) {
                                debug(res)
                                res.should.have.status(200)
                                res.body.should.be.a("object")
                                res.body.should.have.property("taskid")
                                res.body.should.have.property("name").eql(newTask.name)
                                res.body.should.have.property("duedate").eql(duedate)
                                res.body.should.have.property("recurrence_type").eql(Constants.TaskRecurrenceType[recurrenceTypeKey])
                                recurringTasks.push({
                                    taskid: res.body.taskid,
                                    name: res.body.name,
                                    recurrence_type: res.body.recurrence_type
                                })
                                eachCallback(null)
                            })
                        }
                    },
                    function(err) {
                        assert(!err, `An error occurred creating repeating tasks: ${err}`)
                        callback(null)
                    })
                },
                function(callback) {
                    const everyXRules = [
                        {
                            name: "Repeat Every 1 year",
                            text: "Every   1 year",
                            interval: 1,
                            unit: "years"
                        },
                        {
                            name: "Repeat Every 1 years",
                            text: "Every 1 years",
                            interval: 1,
                            unit: "years"
                        },
                        {
                            name: "Repeat Every 4 Week",
                            text: "Every 4 Week",
                            interval: 4,
                            unit: "weeks"
                        },
                        {
                            name: "Repeat Every 4 Weeks",
                            text: "Every 4 Weeks",
                            interval: 4,
                            unit: "weeks"
                        },
                        {
                            name: "Repeat Every 1 Month",
                            text: "Every 1 month",
                            interval: 1,
                            unit: "months"
                        },
                        {
                            name: "Repeat Every 1 Month",
                            text: "Every 1 month",
                            interval: 1,
                            unit: "months"
                        },
                        {
                            name: "Repeat Every 6 Days",
                            text: "Every 6 days",
                            interval: 6,
                            unit: "days"
                        },
                        {
                            name: "Repeat Every 3 Days",
                            text: "Every 3 Days",
                            interval: 3,
                            unit: "days"
                        },
                        {
                            name: "Repeat Every 10 Months",
                            text: "Every 10 Months",
                            interval: 10,
                            unit: "months"
                        },
                        {
                            name: "Repeat Every 7 Years",
                            text: "Every 7 Years",
                            interval: 7,
                            unit: "years"
                        }
                    ]
                    async.each(everyXRules, function(xRule, eachCallback) {
                        // console.log(`Recurrence Key: ${recurrenceTypeKey} = ${Constants.TaskRecurrenceType[recurrenceTypeKey]}`)
                        const taskName = xRule.name
                        const duedate = Math.floor(Date.now() / 1000)
                        let newTask = {
                            name: taskName,
                            listid: listid,
                            duedate: duedate,
                            recurrence_type: Constants.TaskRecurrenceType.Advanced,
                            advanced_recurrence_string: xRule.text
                        }
                        chai
                        .request(baseApiUrl)
                        .post("/tasks")
                        .set("content-type", "application/json")
                        .set("x-api-key", todoCloudAPIKey)
                        .set("Authorization", `Bearer ${jwtToken}`)
                        .send(newTask)
                        .end(function(err, res) {
                            debug(res)
                            res.should.have.status(200)
                            res.body.should.be.a("object")
                            res.body.should.have.property("taskid")
                            res.body.should.have.property("name").eql(newTask.name)
                            res.body.should.have.property("duedate").eql(duedate)
                            res.body.should.have.property("recurrence_type").eql(Constants.TaskRecurrenceType.Advanced)
                            res.body.should.have.property("advanced_recurrence_string").eql(xRule.text)
                            advancedEveryXTasks.push({
                                taskid: res.body.taskid,
                                name: res.body.name,
                                ruleInfo: xRule
                            })
                            eachCallback(null)
                        })
                    },
                    function(err) {
                        assert(!err, `An error occurred creating advanced Every X repeating tasks: ${err}`)
                        callback(null)
                    })
                },
                function(callback) {
                    const xMonthRules = [
                        {
                            name: "Repeat 3rd Sunday",
                            text: "The 3rd Sunday",
                            weekday: 0,
                            week: 3
                        },
                        {
                            name: "Repeat 4th Tuesday",
                            text: "The 4th Tuesday",
                            weekday: 2,
                            week: 4
                        },
                        {
                            name: "Repeat Last Friday",
                            text: "The Last Friday",
                            weekday: 5,
                            week: -1
                        },
                        {
                            name: "Repeat 5th Sunday",
                            text: "The 5th Sunday",
                            weekday: 0,
                            week: 5
                        }
                    ]
                    async.each(xMonthRules, function(xRule, eachCallback) {
                        // console.log(`Recurrence Key: ${recurrenceTypeKey} = ${Constants.TaskRecurrenceType[recurrenceTypeKey]}`)
                        const taskName = xRule.name
                        const duedate = Math.floor(Date.now() / 1000)
                        let newTask = {
                            name: taskName,
                            listid: listid,
                            duedate: duedate,
                            recurrence_type: Constants.TaskRecurrenceType.Advanced,
                            advanced_recurrence_string: xRule.text
                        }
                        chai
                        .request(baseApiUrl)
                        .post("/tasks")
                        .set("content-type", "application/json")
                        .set("x-api-key", todoCloudAPIKey)
                        .set("Authorization", `Bearer ${jwtToken}`)
                        .send(newTask)
                        .end(function(err, res) {
                            debug(res)
                            res.should.have.status(200)
                            res.body.should.be.a("object")
                            res.body.should.have.property("taskid")
                            res.body.should.have.property("name").eql(newTask.name)
                            res.body.should.have.property("duedate").eql(duedate)
                            res.body.should.have.property("recurrence_type").eql(Constants.TaskRecurrenceType.Advanced)
                            res.body.should.have.property("advanced_recurrence_string").eql(xRule.text)
                            advancedXOfMonthTasks.push({
                                taskid: res.body.taskid,
                                name: res.body.name,
                                ruleInfo: xRule
                            })
                            eachCallback(null)
                        })
                    },
                    function(err) {
                        assert(!err, `An error occurred creating advanced Every X repeating tasks: ${err}`)
                        callback(null)
                    })
                },
                function(callback) {
                    const monTuesRules = [
                        {
                            name: "Repeat every Sunday",
                            text: "Every Sunday",
                            weekdays: [0]
                        },
                        {
                            name: "Repeat every Monday",
                            text: "Every Monday",
                            weekdays: [1]
                        },
                        {
                            name: "Repeat every Tuesday",
                            text: "Every Tuesday",
                            weekdays: [2]
                        },
                        {
                            name: "Repeat every Wednesday",
                            text: "Every Wednesday",
                            weekdays: [3]
                        },
                        {
                            name: "Repeat every Thursday",
                            text: "Every Thursday",
                            weekdays: [4]
                        },
                        {
                            name: "Repeat every Friday",
                            text: "Every Friday",
                            weekdays: [5]
                        },
                        {
                            name: "Repeat every Saturday",
                            text: "Every Saturday",
                            weekdays: [6]
                        },
                        {
                            name: "Repeat every Weekday",
                            text: "Every Weekday",
                            weekdays: [1,2,3,4,5]
                        },
                        {
                            name: "Repeat every Weekend Day",
                            text: "Every Weekend",
                            weekdays: [0,6]
                        },
                        {
                            name: "Repeat every day",
                            text: "Every day",
                            weekdays: [0,1,2,3,4,5,6]
                        }
                    ]
                    async.each(monTuesRules, function(xRule, eachCallback) {
                        const taskName = xRule.name
                        const duedate = Math.floor(Date.now() / 1000)
                        let newTask = {
                            name: taskName,
                            listid: listid,
                            duedate: duedate,
                            recurrence_type: Constants.TaskRecurrenceType.Advanced,
                            advanced_recurrence_string: xRule.text
                        }
                        chai
                        .request(baseApiUrl)
                        .post("/tasks")
                        .set("content-type", "application/json")
                        .set("x-api-key", todoCloudAPIKey)
                        .set("Authorization", `Bearer ${jwtToken}`)
                        .send(newTask)
                        .end(function(err, res) {
                            debug(res)
                            res.should.have.status(200)
                            res.body.should.be.a("object")
                            res.body.should.have.property("taskid")
                            res.body.should.have.property("name").eql(newTask.name)
                            res.body.should.have.property("duedate").eql(duedate)
                            res.body.should.have.property("recurrence_type").eql(Constants.TaskRecurrenceType.Advanced)
                            res.body.should.have.property("advanced_recurrence_string").eql(xRule.text)
                            advancedEveryMonTuesTasks.push({
                                taskid: res.body.taskid,
                                name: res.body.name,
                                ruleInfo: xRule
                            })
                            eachCallback(null)
                        })
                    },
                    function(err) {
                        assert(!err, `An error occurred creating advanced Every Mon/Tues/etc repeating tasks: ${err}`)
                        callback(null)
                    })
                },
                function(callback) {
                    // Create a simple recurring project to complete
                    let newTask = {
                        name: "Recurring Project",
                        listid: listid,
                        task_type: Constants.TaskType.Project,
                        recurrence_type: Constants.TaskRecurrenceType.Weekly
                    }
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("task_type").eql(Constants.TaskType.Project)
                        projectid = res.body.taskid
                        callback(null)
                    })
                },
                function(callback) {
                    // Add 10 subtasks to the project that can be completed
                    subtaskids = []
                    async.times(10, function(index, next) {
                        let newTask = {
                            name: `Subtask ${index}`,
                            listid: listid,
                            parentid: projectid
                        }
                        chai
                            .request(baseApiUrl)
                            .post("/tasks")
                            .set("content-type", "application/json")
                            .set("x-api-key", todoCloudAPIKey)
                            .set("Authorization", `Bearer ${jwtToken}`)
                            .send(newTask)
                            .end(function(err, res) {
                                debug(res)
                                res.should.have.status(200)
                                res.body.should.be.a("object")
                                res.body.should.have.property("taskid")
                                res.body.should.have.property("name").eql(newTask.name)
                                res.body.should.have.property("parentid").eql(projectid)
                                let subtaskid = res.body.taskid
                                subtaskids.push(subtaskid)
                                next(null)
                            })
                    },
                    function(err) {
                        callback(err)
                    })
                }
            ],
            function(err) {
                assert(!err, `Setup for the task completion tests failed: ${err}`)
                done()
            })
        })

        describe("Complete Repeating Tasks", function() {
            assuming(UnitTests.RepeatingTasks).it("Should complete all the basic repeating tasks", function(done) {
                const repeatingTaskIDs = recurringTasks.map(function(obj) {
                    return obj.taskid
                })

                let tasksToComplete = {
                    "tasks": repeatingTaskIDs
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/complete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToComplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == repeatingTaskIDs.length, `Number of completed tasks doesn't match number of requested tasks to complete.`)

                    // Make sure all of the task IDs we expect to have been marked completed
                    // are actually marked as completed.
                    repeatingTaskIDs.forEach((repeatingTaskId, index) => {
                        assert(completedTasks.find((completedTaskId, completedIndex) => {
                            return completedTaskId == repeatingTaskId
                        }) != undefined, `Expected ${repeatingTaskId} as one of the completed tasks.`)
                    })

                    // Every task should have been repeated with a new due date and no completion date
                    res.body.should.have.property("repeatedTasks")
                    let repeatedTasks = res.body.repeatedTasks
                    repeatingTaskIDs.forEach((repeatingTaskId) => {
                        assert(repeatedTasks.find((repeatedTask) => {
                            return repeatedTask.taskid == repeatingTaskId
                        }) != undefined, `Exptected ${repeatingTaskId} as one of the repeated tasks.`)
                    })
                    assert(repeatedTasks.length == repeatingTaskIDs.length, `Number of repeated tasks should match the number requested to be completed.`)
                    repeatedTasks.forEach((repeatedTask) => {
                        assert(repeatedTask.completiondate == undefined || repeatedTask.completionDate == 0,
                                `Expected a repeated task to NOT have a completiondate property defined.`)
                    })

                    // A new task should have been created that is completed, for the
                    // original repeating task. This new task should not match any of
                    // the original task ids and should have a completiondate.
                    res.body.should.have.property("newTasks")
                    let newTasks = res.body.newTasks
                    assert(newTasks.length == repeatingTaskIDs.length, `Number of completed tasks should match the number requested.`)
                    newTasks.forEach((newTask) => {
                        assert(newTask.completiondate > 0, `Expected a newly-created completed task to have a completion date.`)
                        assert(newTask._tableName == Constants.TasksTable.Completed, `Expected newly-completed task to be in the completed tasks table.`)
                    })
                    repeatingTaskIDs.forEach((repeatingTaskId) => {
                        assert(newTasks.find((newTask) => {
                            return newTask.taskid == repeatingTaskId
                        }) == undefined, `Should not see original task id in newly-created completed task.`)
                    })
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (GET) - Read repeating tasks after completion", function() {
            assuming(UnitTests.RepeatingTasks).it("Should read rescheduled tasks", function(done) {
                async.each(recurringTasks, function(recurringTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .get(`/tasks/${recurringTask.taskid}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        let duedateStart = moment().utc()
                        let duedateEnd = moment().utc()
                        switch(recurringTask.recurrence_type) {
                            case Constants.TaskRecurrenceType.Weekly: {
                                duedateStart.add(1, 'weeks').startOf('day').unix()
                                duedateEnd.add(1, 'weeks').endOf('day').unix()
                                break;
                            }
                            case Constants.TaskRecurrenceType.Monthly: {
                                duedateStart.add(1, 'months').startOf('day').unix()
                                duedateEnd.add(1, 'months').endOf('day').unix()
                                break;
                            }
                            case Constants.TaskRecurrenceType.Yearly: {
                                duedateStart.add(1, 'years').startOf('day').unix()
                                duedateEnd.add(1, 'years').endOf('day').unix()
                                break;
                            }
                            case Constants.TaskRecurrenceType.Daily: {
                                duedateStart.add(1, 'days').startOf('day').unix()
                                duedateEnd.add(1, 'days').endOf('day').unix()
                                break;
                            }
                            case Constants.TaskRecurrenceType.Biweekly: {
                                duedateStart.add(2, 'weeks').startOf('day').unix()
                                duedateEnd.add(2, 'weeks').endOf('day').unix()
                                break;
                            }
                            case Constants.TaskRecurrenceType.Bimonthly: {
                                duedateStart.add(2, 'months').startOf('day').unix()
                                duedateEnd.add(2, 'months').endOf('day').unix()
                                break;
                            }
                            case Constants.TaskRecurrenceType.Semiannually: {
                                duedateStart.add(6, 'months').startOf('day').unix()
                                duedateEnd.add(6, 'months').endOf('day').unix()
                                break;
                            }
                            case Constants.TaskRecurrenceType.Quarterly: {
                                duedateStart.add(3, 'months').startOf('day').unix()
                                duedateEnd.add(3, 'months').endOf('day').unix()
                                break;
                            }
                        }
                        duedateStart = duedateStart.unix()
                        duedateEnd = duedateEnd.unix()

                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid").eql(recurringTask.taskid)
                        res.body.should.have.property("name").eql(recurringTask.name)
                        res.body.should.have.property("listid").eql(listid)

                        // Should show up in the tdo_tasks table without having a completiondate (because it should get reset)
                        res.body.should.have.property("_tableName").eql(Constants.TasksTable.Normal)

                        res.body.should.have.property("completiondate").eql(0)
                        res.body.should.have.property("recurrence_type").eql(recurringTask.recurrence_type)

                        res.body.should.have.property("duedate").above(duedateStart)
                        res.body.should.have.property("duedate").below(duedateEnd)
                        eachCallback()
                    })
                },
                function(err) {
                    assert(!err, `An error occurred reading a repeated task: ${err}`)
                    done()
                })
            })
        })

        describe("/lists/{listid}/tasks (GET) - Find a completed recurring task in the completed tasks", function() {

            after(function(done) {
                debug(`**** Deleting the basic recurring tasks from the database. ****`)
                // Delete all  the recurringTasks from the database so they no longer
                // get returned by the getTasks() call and prevent other tests from
                // funtioning correctly (in case we grow a large group of tasks and
                // paging starts kicking in).
                async.each(completedRecurringTasks, function(recurringTaskId, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .delete(`/tasks/${recurringTaskId}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(204)
                        eachCallback()
                    })
                },
                function(err) {
                    assert(!err, `An error occurred deleting a basic recurring task: ${err}`)
                    done()
                })
            })

            assuming(UnitTests.RepeatingTasks).it("Basic recurring tasks should be found in the completed tasks", function(done) {
                chai
                    .request(baseApiUrl)
                    .get(`/lists/${listid}/tasks?completed_only=true`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("tasks").be.a("array")
                        const tasks = res.body.tasks
                        recurringTasks.forEach((recurringTask, index) => {
                            const completedTask = tasks.find((task, taskIndex) => {
                                debug(`Task: ${JSON.stringify(task)}`)
                                return task.name == recurringTask.name && task.recurrence_type == 0 && task.completiondate > 0
                            })
                            assert(completedTask != null, `Could not find \"${recurringTask.name}\" in the completed tasks.`)
                        })
                        // Save the taskids off so that we can delete them later so they
                        // don't affect other tests.
                        completedRecurringTasks = tasks.map((task, index) => {
                            return task.taskid
                        })
                        done()
                    })
            })
        })

        describe("Complete Advanced Every X Repeating Tasks", function() {
            assuming(UnitTests.RepeatingTasks).it("Should complete all the advanced (Every X) repeating tasks", function(done) {
                const repeatingTaskIDs = advancedEveryXTasks.map(function(obj) {
                    return obj.taskid
                })

                let tasksToComplete = {
                    "tasks": repeatingTaskIDs
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/complete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToComplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == repeatingTaskIDs.length, `Number of completed tasks doesn't match number of requested tasks to complete.`)

                    // Make sure all of the task IDs we expect to have been marked completed
                    // are actually marked as completed.
                    repeatingTaskIDs.forEach((repeatingTaskId, index) => {
                        assert(completedTasks.find((completedTaskId, completedIndex) => {
                            return completedTaskId == repeatingTaskId
                        }) != undefined, `Expected ${repeatingTaskId} as one of the completed tasks.`)
                    })
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (GET) - Read advanced repeating tasks (Every X) after completion", function() {
            assuming(UnitTests.RepeatingTasks).it("Should read rescheduled tasks", function(done) {
                async.each(advancedEveryXTasks, function(recurringTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .get(`/tasks/${recurringTask.taskid}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        const interval = recurringTask.ruleInfo.interval
                        const unit = recurringTask.ruleInfo.unit
                        const duedateStart = moment().utc().add(interval, unit).startOf('day').unix()
                        const duedateEnd = moment().utc().add(interval, unit).endOf('day').unix()

                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid").eql(recurringTask.taskid)
                        res.body.should.have.property("name").eql(recurringTask.name)
                        res.body.should.have.property("listid").eql(listid)

                        // Should show up in the tdo_tasks table without having a completiondate (because it should get reset)
                        res.body.should.have.property("_tableName").eql(Constants.TasksTable.Normal)

                        res.body.should.have.property("completiondate").eql(0)
                        res.body.should.have.property("recurrence_type").eql(Constants.TaskRecurrenceType.Advanced)
                        res.body.should.have.property("advanced_recurrence_string").eql(recurringTask.ruleInfo.text)

                        res.body.should.have.property("duedate").above(duedateStart)
                        res.body.should.have.property("duedate").below(duedateEnd)
                        eachCallback()
                    })
                },
                function(err) {
                    assert(!err, `An error occurred reading a repeated task: ${err}`)
                    done()
                })
            })
        })

        describe("/lists/{listid}/tasks (GET) - Find a completed advanced recurring task (Every X) in the completed tasks", function() {

            after(function(done) {
                debug(`**** Deleting the advanced recurring (Every X) tasks from the database. ****`)
                // Delete all  the recurringTasks from the database so they no longer
                // get returned by the getTasks() call and prevent other tests from
                // funtioning correctly (in case we grow a large group of tasks and
                // paging starts kicking in).
                async.each(completedAdvancedEveryXTasks, function(recurringTaskId, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .delete(`/tasks/${recurringTaskId}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(204)
                        eachCallback()
                    })
                },
                function(err) {
                    assert(!err, `An error occurred deleting a basic recurring task: ${err}`)
                    done()
                })
            })
            
            assuming(UnitTests.RepeatingTasks).it("Advanced completed tasks should be found in the completed tasks", function(done) {
                chai
                    .request(baseApiUrl)
                    .get(`/lists/${listid}/tasks?completed_only=true`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("tasks").be.a("array")
                        const tasks = res.body.tasks
                        advancedEveryXTasks.forEach((recurringTask, index) => {
                            const completedTask = tasks.find((task, taskIndex) => {
                                return task.name == recurringTask.name && task.recurrence_type == 0 && task.completiondate > 0
                            })
                            assert(completedTask != null, `Could not find \"${recurringTask.name}\" in the completed tasks.`)
                        })
                        // Save the taskids off so that we can delete them later so they
                        // don't affect other tests.
                        completedAdvancedEveryXTasks = tasks.map((task, index) => {
                            return task.taskid
                        })
                        done()
                    })
            })
        })

        describe("Complete Advanced X of Month Repeating Tasks", function() {
            assuming(UnitTests.RepeatingTasks).it("Should complete all the advanced (X of Month) repeating tasks", function(done) {
                const repeatingTaskIDs = advancedXOfMonthTasks.map(function(obj) {
                    return obj.taskid
                })

                let tasksToComplete = {
                    "tasks": repeatingTaskIDs
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/complete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToComplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == repeatingTaskIDs.length, `Number of completed tasks doesn't match number of requested tasks to complete.`)

                    // Make sure all of the task IDs we expect to have been marked completed
                    // are actually marked as completed.
                    repeatingTaskIDs.forEach((repeatingTaskId, index) => {
                        assert(completedTasks.find((completedTaskId, completedIndex) => {
                            return completedTaskId == repeatingTaskId
                        }) != undefined, `Expected ${repeatingTaskId} as one of the completed tasks.`)
                    })
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (GET) - Read advanced repeating tasks (X of Month) after completion", function() {
            assuming(UnitTests.RepeatingTasks).it("Should read rescheduled tasks", function(done) {
                async.each(advancedXOfMonthTasks, function(recurringTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .get(`/tasks/${recurringTask.taskid}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        const weekday = recurringTask.ruleInfo.weekday
                        const week = recurringTask.ruleInfo.week
                        // const interval = recurringTask.ruleInfo.interval
                        // const unit = recurringTask.ruleInfo.unit
                        // const duedateStart = moment().utc().add(interval, unit).startOf('day').unix()
                        // const duedateEnd = moment().utc().add(interval, unit).endOf('day').unix()

                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid").eql(recurringTask.taskid)
                        res.body.should.have.property("name").eql(recurringTask.name)
                        res.body.should.have.property("listid").eql(listid)

                        // Should show up in the tdo_tasks table without having a completiondate (because it should get reset)
                        res.body.should.have.property("_tableName").eql(Constants.TasksTable.Normal)

                        res.body.should.have.property("completiondate").eql(0)
                        res.body.should.have.property("recurrence_type").eql(Constants.TaskRecurrenceType.Advanced)
                        res.body.should.have.property("advanced_recurrence_string").eql(recurringTask.ruleInfo.text)

                        res.body.should.have.property("duedate").above(0)

                        const duedateTimestamp = res.body.duedate

                        const dueDate = moment(duedateTimestamp * 1000)
                        const weekdayNumber = dueDate.isoWeekday() % 7

                        const nowDate = moment()
                        assert(nowDate.month() < dueDate.month(), `Expecting the new due date to be in the future and not the same month as now.`)

                        assert(weekdayNumber == weekday, `Task recurred to non-expected weekday.`)

                        eachCallback()
                    })
                },
                function(err) {
                    assert(!err, `An error occurred reading a repeated task: ${err}`)
                    done()
                })
            })
        })

        describe("/lists/{listid}/tasks (GET) - Find a completed advanced recurring task (X of Month) in the completed tasks", function() {

            after(function(done) {
                debug(`**** Deleting the advanced recurring (X of Month) tasks from the database. ****`)
                // Delete all  the recurringTasks from the database so they no longer
                // get returned by the getTasks() call and prevent other tests from
                // funtioning correctly (in case we grow a large group of tasks and
                // paging starts kicking in).
                async.each(completedAdvancedXOfMonthTasks, function(recurringTaskId, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .delete(`/tasks/${recurringTaskId}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(204)
                        eachCallback()
                    })
                },
                function(err) {
                    assert(!err, `An error occurred deleting a basic recurring task: ${err}`)
                    done()
                })
            })
            
            assuming(UnitTests.RepeatingTasks).it("Advanced completed tasks should be found in the completed tasks", function(done) {
                chai
                    .request(baseApiUrl)
                    .get(`/lists/${listid}/tasks?completed_only=true`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("tasks").be.a("array")
                        const tasks = res.body.tasks
                        advancedXOfMonthTasks.forEach((recurringTask, index) => {
                            const completedTask = tasks.find((task, taskIndex) => {
                                return task.name == recurringTask.name && task.recurrence_type == 0 && task.completiondate > 0
                            })
                            assert(completedTask != null, `Could not find \"${recurringTask.name}\" in the completed tasks.`)
                        })
                        // Save the taskids off so that we can delete them later so they
                        // don't affect other tests.
                        completedAdvancedXOfMonthTasks = tasks.map((task, index) => {
                            return task.taskid
                        })
                        done()
                    })
            })
        })
        

        ///////
        describe("Complete Advanced Mon/Tue Repeating Tasks", function() {
            assuming(UnitTests.RepeatingTasks).it("Should complete all the advanced (Mon/Tue) repeating tasks", function(done) {
                const repeatingTaskIDs = advancedEveryMonTuesTasks.map(function(obj) {
                    return obj.taskid
                })

                let tasksToComplete = {
                    "tasks": repeatingTaskIDs
                }
                chai
                .request(baseApiUrl)
                .post(`/tasks/complete`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(tasksToComplete)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("completedTaskIDs")
                    let completedTasks = res.body.completedTaskIDs
                    assert(completedTasks.length == repeatingTaskIDs.length, `Number of completed tasks doesn't match number of requested tasks to complete.`)

                    // Make sure all of the task IDs we expect to have been marked completed
                    // are actually marked as completed.
                    repeatingTaskIDs.forEach((repeatingTaskId, index) => {
                        assert(completedTasks.find((completedTaskId, completedIndex) => {
                            return completedTaskId == repeatingTaskId
                        }) != undefined, `Expected ${repeatingTaskId} as one of the completed tasks.`)
                    })
                    done()
                })
            })
        })

        describe("/tasks/{taskid} (GET) - Read advanced repeating tasks (Mon/Tue) after completion", function() {
            assuming(UnitTests.RepeatingTasks).it("Should read rescheduled tasks", function(done) {
                async.each(advancedEveryMonTuesTasks, function(recurringTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .get(`/tasks/${recurringTask.taskid}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        const weekdays = recurringTask.ruleInfo.weekdays

                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid").eql(recurringTask.taskid)
                        res.body.should.have.property("name").eql(recurringTask.name)
                        res.body.should.have.property("listid").eql(listid)

                        // Should show up in the tdo_tasks table without having a completiondate (because it should get reset)
                        res.body.should.have.property("_tableName").eql(Constants.TasksTable.Normal)

                        res.body.should.have.property("completiondate").eql(0)
                        res.body.should.have.property("recurrence_type").eql(Constants.TaskRecurrenceType.Advanced)
                        res.body.should.have.property("advanced_recurrence_string").eql(recurringTask.ruleInfo.text)

                        res.body.should.have.property("duedate").above(0)

                        const duedateTimestamp = res.body.duedate

                        const dueDate = moment(duedateTimestamp * 1000)
// debug(`=====  task duedate: ${dueDate.toISOString()}`)
                        const weekdayNumber = dueDate.isoWeekday() % 7

                        // Make sure that the new weekdayNumber is found in an expected weekday
                        const weekdayFound = weekdays.find((value) => {
                            return weekdayNumber == value
                        })

                        assert(weekdayFound == weekdayNumber, `Expecting to find the new due date's weekday (${weekdayNumber}) in one of ${JSON.stringify(weekdays)}`)
                        const now = moment().endOf('day').unix()
                        assert(now < duedateTimestamp, `Expecting the new due date to be greater than today.`)

                        eachCallback()
                    })
                },
                function(err) {
                    assert(!err, `An error occurred reading a repeated task: ${err}`)
                    done()
                })
            })
        })

        describe("/lists/{listid}/tasks (GET) - Find a completed advanced recurring task (Mon/Tue) in the completed tasks", function() {

            after(function(done) {
                debug(`**** Deleting the advanced recurring (Mon/Tue) tasks from the database. ****`)
                // Delete all  the recurringTasks from the database so they no longer
                // get returned by the getTasks() call and prevent other tests from
                // funtioning correctly (in case we grow a large group of tasks and
                // paging starts kicking in).
                async.each(completedAdvancedEveryMonTuesTasks, function(recurringTaskId, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .delete(`/tasks/${recurringTaskId}`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(204)
                        eachCallback()
                    })
                },
                function(err) {
                    assert(!err, `An error occurred deleting a basic recurring task: ${err}`)
                    done()
                })
            })
            
            assuming(UnitTests.RepeatingTasks).it("Advanced completed tasks should be found in the completed tasks", function(done) {
                chai
                    .request(baseApiUrl)
                    .get(`/lists/${listid}/tasks?completed_only=true`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("tasks").be.a("array")
                        const tasks = res.body.tasks
                        advancedEveryMonTuesTasks.forEach((recurringTask, index) => {
                            const completedTask = tasks.find((task, taskIndex) => {
                                return task.name == recurringTask.name && task.recurrence_type == 0 && task.completiondate > 0
                            })
                            assert(completedTask != null, `Could not find \"${recurringTask.name}\" in the completed tasks.`)
                        })
                        // Save the taskids off so that we can delete them later so they
                        // don't affect other tests.
                        completedAdvancedEveryMonTuesTasks = tasks.map((task, index) => {
                            return task.taskid
                        })
                        done()
                    })
            })
        })
        
    })
})

describe("Todo Cloud API - Smart List Tasks Tests", function() {

    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    // Variables used across tests
    let inboxid = null
    let allSmartListId = null

    let testSmartListId = null

    before(function(done) {
        if (!UnitTests.SmartListTasks) {
            done()
            return
        }
        async.waterfall([
            function(callback) {
                setUpAuthenticatedAccount(function(done) {
                    callback(null)
                })
            },
            function(callback) {
                // Read ALL the smart lists and record the
                // listid of the ALL smart list.
                chai
                .request(baseApiUrl)
                .get(`/smart-lists`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    allSmartList = res.body.find((smartList) => {
                        return (smartList.icon_name && smartList.icon_name == "menu-everything")
                    })
                    if (allSmartList) { allSmartListId = allSmartList.listid }
                    callback(null)
                })
            },
            function(callback) {
                // Read the user lists and figure out which one is the inbox.
                chai
                .request(baseApiUrl)
                .get("/lists?includeDeleted=false")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    res.body.length.should.be.above(0)
                    const inbox = res.body.map(item => item.list).find((list) => {
                        return list.name == "Inbox"
                    })
                    assert(inbox, `Could not find an Inbox list.`)
                    inbox.should.have.property("listid")
                    inboxid = inbox.listid
                    callback(null)
                })
            },
            function(callback) {
                // Create a few tasks that should be returned by the call
                // to get tasks from smart lists.
                async.times(5, function(index, next) {
                    let newTask = {
                        name: `Test Task ${index}`,
                        listid: inboxid,
                        duedate: Math.floor(Date.now() / 1000)
                    }
                    chai
                        .request(baseApiUrl)
                        .post("/tasks")
                        .set("content-type", "application/json")
                        .set("x-api-key", todoCloudAPIKey)
                        .set("Authorization", `Bearer ${jwtToken}`)
                        .send(newTask)
                        .end(function(err, res) {
                            debug(res)
                            res.should.have.status(200)
                            res.body.should.be.a("object")
                            res.body.should.have.property("taskid")
                            res.body.should.have.property("name").eql(newTask.name)
                            res.body.should.have.property("listid").eql(newTask.listid)
                            res.body.should.have.property("duedate").eql(newTask.duedate)
                            next(null)
                        })
                },
                function(err) {
                    callback(err)
                })
            },
            function(callback) {
                // Create a smart list that can be used for testing
                // task action filters.
                let newSmartList = {
                    name: "Actions Smart List",
                    json_filter: `{"filterGroups":[{"actionType":["contact"]}]} `
                }
                chai
                .request(baseApiUrl)
                .post("/smart-lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newSmartList)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("listid")
                    res.body.should.have.property("timestamp")
                    res.body.should.have.property("name").eql(newSmartList.name)
                    testSmartListId = res.body.listid
                    callback()
                })
            },
            function(callback) {
                // Create Task Action Tasks
                const newTasks = [
                    {
                        name: `Task Action: Call Contact`,
                        listid: inboxid,
                        task_type: Constants.TaskType.CallContact
                    },
                    {
                        name: `Task Action: SMS Contact`,
                        listid: inboxid,
                        task_type: Constants.TaskType.SMSContact
                    },
                    {
                        name: `Task Action: Email Contact`,
                        listid: inboxid,
                        task_type: Constants.TaskType.EmailContact
                    },
                    {
                        name: `Task Action: Visit Location`,
                        listid: inboxid,
                        task_type: Constants.TaskType.VisitLocation
                    },
                    {
                        name: `Task Action: URL`,
                        listid: inboxid,
                        task_type: Constants.TaskType.URL
                    },
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        res.body.should.have.property("task_type").eql(newTask.task_type)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating task action tasks: ${err}`)
                    callback()
                })
            },
            function(callback) {
                // Create User Assigned Tasks
                const newTasks = [
                    {
                        name: `Assigned to Me`,
                        listid: inboxid,
                        assigned_userid: testUserid
                    }
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        res.body.should.have.property("assigned_userid").eql(newTask.assigned_userid)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating assigned tasks: ${err}`)
                    callback()
                })
            },
            function(callback) {
                // Create Completed Tasks for Date Filter Tests
                const newTasks = [
                    {
                        name: `Completed now task`,
                        listid: inboxid,
                        completiondate: moment().unix()
                    },
                    {
                        name: `Completed yesterday task`,
                        listid: inboxid,
                        completiondate: moment().subtract(1, 'day').unix()
                    },
                    {
                        name: `Completed a week ago task`,
                        listid: inboxid,
                        completiondate: moment().subtract(1, 'week').unix()
                    },
                    {
                        name: `Completed 4 months ago task`,
                        listid: inboxid,
                        completiondate: moment().subtract(4, 'month').unix()
                    },
                    {
                        name: `Completed a year ago task`,
                        listid: inboxid,
                        completiondate: moment().subtract(1, 'year').unix()
                    },
                    {
                        name: `Completed two years ago task`,
                        listid: inboxid,
                        completiondate: moment().subtract(2, 'year').unix()
                    },
                    {
                        name: `Completed 9 months ago task`,
                        listid: inboxid,
                        completiondate: moment().subtract(9, 'month').unix()
                    },
                    {
                        name: `Completed 14 months ago task`,
                        listid: inboxid,
                        completiondate: moment().subtract(14, 'month').unix()
                    },
                    {
                        name: `Completed 3 weeks ago task`,
                        listid: inboxid,
                        completiondate: moment().subtract(3, 'week').unix()
                    }
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        res.body.should.have.property("completiondate").eql(newTask.completiondate)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating completed tasks: ${err}`)
                    callback()
                })
            },
            function(callback) {
                // Create Tasks with Due Dates for Date Filter Tests
                const newTasks = [
                    {
                        name: `Due now task`,
                        listid: inboxid,
                        duedate: moment().unix()
                    },
                    {
                        name: `Due tomorrow task`,
                        listid: inboxid,
                        duedate: moment().add(1, 'day').unix()
                    },
                    {
                        name: `Due in a week task`,
                        listid: inboxid,
                        duedate: moment().add(1, 'week').unix()
                    },
                    {
                        name: `Due in 3 weeks task`,
                        listid: inboxid,
                        duedate: moment().add(3, 'weeks').unix()
                    },
                    {
                        name: `Due in 4 months task`,
                        listid: inboxid,
                        duedate: moment().add(4, 'month').unix()
                    },
                    {
                        name: `Due in a year task`,
                        listid: inboxid,
                        duedate: moment().add(1, 'year').unix()
                    },
                    {
                        name: `Due in two years task`,
                        listid: inboxid,
                        duedate: moment().add(2, 'year').unix()
                    },
                    {
                        name: `Due in 9 months task`,
                        listid: inboxid,
                        duedate: moment().add(9, 'month').unix()
                    },
                    {
                        name: `Due in 14 months task`,
                        listid: inboxid,
                        duedate: moment().add(14, 'month').unix()
                    },
                    {
                        name: `Due in 3 weeks task`,
                        listid: inboxid,
                        duedate: moment().add(3, 'week').unix()
                    },
                    {
                        name: `Overdue by 3 weeks task`,
                        listid: inboxid,
                        duedate: moment().subtract(3, 'week').unix()
                    },
                    {
                        name: `Overdue by 4 months task`,
                        listid: inboxid,
                        duedate: moment().subtract(4, 'month').unix()
                    },
                    {
                        name: `Overdue by 1 year task`,
                        listid: inboxid,
                        duedate: moment().subtract(1, 'year').unix()
                    },
                    {
                        name: `Overdue by 16 months task`,
                        listid: inboxid,
                        duedate: moment().subtract(16, 'month').unix()
                    }
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        res.body.should.have.property("duedate").eql(newTask.duedate)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating due tasks: ${err}`)
                    callback()
                })
            },
            function(callback) {
                // Create Tasks with Start Dates for Date Filter Tests
                const newTasks = [
                    {
                        name: `Start yesterday task`,
                        listid: inboxid,
                        startdate: moment().subtract(1, 'day').unix(),
                        duedate: moment().unix()
                    },
                    {
                        name: `Start today task`,
                        listid: inboxid,
                        startdate: moment().unix(),
                        duedate: moment().add(1, 'day').unix()
                    },
                    {
                        name: `Start tomorrow task`,
                        listid: inboxid,
                        startdate: moment().add(1, 'day').unix(),
                        duedate: moment().add(1, 'week').unix()
                    },
                    {
                        name: `Start in one week`,
                        listid: inboxid,
                        startdate: moment().add(1, 'week').unix(),
                        duedate: moment().add(3, 'weeks').unix()
                    },
                    {
                        name: `Start in one month`,
                        listid: inboxid,
                        startdate: moment().add(1, 'month').unix(),
                        duedate: moment().add(4, 'month').unix()
                    },
                    {
                        name: `Start in seven months`,
                        listid: inboxid,
                        startdate: moment().add(7, 'month').unix(),
                        duedate: moment().add(1, 'year').unix()
                    },
                    {
                        name: `Start in one year`,
                        listid: inboxid,
                        startdate: moment().add(1, 'year').unix(),
                        duedate: moment().add(2, 'year').unix()
                    },
                    {
                        name: `Start in 3 weeks`,
                        listid: inboxid,
                        startdate: moment().add(3, 'week').unix(),
                        duedate: moment().add(9, 'month').unix()
                    },
                    {
                        name: `Start in 13 months`,
                        listid: inboxid,
                        startdate: moment().add(13, 'month').unix(),
                        duedate: moment().add(14, 'month').unix()
                    },
                    {
                        name: `Start in two weeks`,
                        listid: inboxid,
                        startdate: moment().add(2, 'week').unix(),
                        duedate: moment().add(3, 'week').unix()
                    },
                    {
                        name: `Start 4 weeks ago`,
                        listid: inboxid,
                        startdate: moment().subtract(4, 'week').unix(),
                        duedate: moment().subtract(3, 'week').unix()
                    },
                    {
                        name: `Start 5 months ago`,
                        listid: inboxid,
                        startdate: moment().subtract(5, 'month').unix(),
                        duedate: moment().subtract(4, 'month').unix()
                    },
                    {
                        name: `Start 13 months ago`,
                        listid: inboxid,
                        startdate: moment().subtract(13, 'month').unix(),
                        duedate: moment().subtract(1, 'year').unix()
                    },
                    {
                        name: `Start 17 months ago`,
                        listid: inboxid,
                        startdate: moment().subtract(17, 'month').unix(),
                        duedate: moment().subtract(16, 'month').unix()
                    }
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        res.body.should.have.property("duedate").eql(newTask.duedate)
                        res.body.should.have.property("startdate").eql(newTask.startdate)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating tasks with start dates: ${err}`)
                    callback()
                })
            },
            function(callback) {
                // Create Tasks with a location alert
                const newTasks = [
                    {
                        name: `Arriving 77 E 42nd Street in New York`,
                        listid: inboxid,
                        location_alert: ">:40.752980, -73.977067:77 E 42nd St New York City New York 10017 United States"
                    },
                    {
                        name: `Leaving Jacksonville`,
                        listid: inboxid,
                        location_alert: "<:30.480615, -81.594045:1326 Starratt Rd Jacksonville Florida 32218 United States"
                    }
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        res.body.should.have.property("location_alert").eql(newTask.location_alert)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating tasks with a location alert: ${err}`)
                    callback()
                })
            },
            function(callback) {
                // Create tasks used for name search tests
                const newTasks = [
                    {
                        name: `Chocolate Milk`,
                        listid: inboxid
                    },
                    {
                        name: `Milk Chocolate`,
                        listid: inboxid
                    },
                    {
                        name: `Cookies and Milk`,
                        listid: inboxid
                    },
                    {
                        name: `Dark chocolate`,
                        listid: inboxid
                    }
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating tasks for filtering on the task name: ${err}`)
                    callback()
                })
            },
            function(callback) {
                // Create tasks used for note search tests
                const newTasks = [
                    {
                        name: `Note Test 1`,
                        listid: inboxid,
                        note: `In the jungle, the mighty jungle.`
                    },
                    {
                        name: `Note Test 2`,
                        listid: inboxid,
                        note: `The lion sleeps tonight and eats goats for breakfast over a campfire.`
                    },
                    {
                        name: `Note Test 3`,
                        listid: inboxid,
                        note: `There are many campfire songs that exist, but which one is your favorite?`
                    },
                    {
                        name: `Note Test 4`,
                        listid: inboxid,
                        note: `When I am at a campfire, I like to roast marshmallows and eat smores.`
                    }
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("note").eql(newTask.note)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating tasks for filtering on the task note: ${err}`)
                    callback()
                })
            },
            function(callback) {
                // Create tasks used for priority filter tests
                const newTasks = [
                    {
                        name: `No priority`,
                        listid: inboxid,
                        priority: Constants.TaskPriority.None
                    },
                    {
                        name: `Low priority`,
                        listid: inboxid,
                        priority: Constants.TaskPriority.Low
                    },
                    {
                        name: `Medium priority`,
                        listid: inboxid,
                        priority: Constants.TaskPriority.Medium
                    },
                    {
                        name: `High priority`,
                        listid: inboxid,
                        priority: Constants.TaskPriority.High
                    }
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("priority").eql(newTask.priority)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating tasks for filtering on the task priority: ${err}`)
                    callback()
                })
            },
            function(callback) {
                // Create tasks used for testing the recurrence filter
                const newTasks = [
                    {
                        name: `Repeat weekly`,
                        listid: inboxid,
                        recurrence_type: Constants.TaskRecurrenceType.Weekly
                    },
                    {
                        name: `Repeat monthly`,
                        listid: inboxid,
                        recurrence_type: Constants.TaskRecurrenceType.Monthly
                    },
                    {
                        name: `Repeat yearly`,
                        listid: inboxid,
                        recurrence_type: Constants.TaskRecurrenceType.Yearly
                    }
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("recurrence_type").eql(newTask.recurrence_type)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating tasks for filtering on the recurrence type: ${err}`)
                    callback()
                })
            },
            function(callback) {
                // Create tasks used for testing the starred tasks filter
                const newTasks = [
                    {
                        name: `Starred task`,
                        listid: inboxid,
                        starred: true
                    },
                    {
                        name: `Not starred task`,
                        listid: inboxid,
                        starred: false
                    },
                    {
                        name: `Starred project`,
                        listid: inboxid,
                        project_starred: true,
                        task_type: Constants.TaskType.Project
                    },
                    {
                        name: `Starred checklist`,
                        listid: inboxid,
                        starred: true,
                        task_type: Constants.TaskType.Checklist
                    }
                ]
                async.each(newTasks, function(newTask, eachCallback) {
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("name").eql(newTask.name)
                        res.body.should.have.property("listid").eql(newTask.listid)
                        eachCallback(null)
                    })
                },
                function(err) {
                    assert(!err, `Error creating tasks for filtering on the recurrence type: ${err}`)
                    callback()
                })
            }
        ],
        function(err) {
            assert(!err, `Setup for the smart list tasks tests failed: ${err}`)
            done()
        })
    })

    after(function(done) {
        if (!UnitTests.SmartListTasks) {
            done()
            return
        }
        // deleteAccount(done)
        done()
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks from the ALL smart list", function() {
        assuming(UnitTests.SmartListTasks).it("Should return tasks", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${allSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    // const tasks = res.body.tasks
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get contact tasks from the Action Smart List", function() {
        assuming(UnitTests.SmartListTasks).it("Should return only contact tasks", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 3, `Excpected only 3 contact tasks`)
                    // const tasks = res.body.tasks
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get location tasks from the Action Smart List", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Location Actions`,
                json_filter: `{"filterGroups":[{"actionType":["location"]}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should return only location tasks", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 1, `Excpected only 1 location task`)
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get URL tasks from the Action Smart List", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `URL Actions`,
                json_filter: `{"filterGroups":[{"actionType":["url"]}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should return only URL tasks", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 1, `Excpected only 1 URL task`)
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get URL & Location tasks from the Action Smart List", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `URL & Location Actions`,
                json_filter: `{"filterGroups":[{"actionType":["url", "location"]}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should return only URL & Location tasks", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 2, `Excpected only 2 action tasks`)
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks with NO task action", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `NO Task Actions`,
                json_filter: `{"filterGroups":[{"actionType":["none"]}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should return tasks with NO task action", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    tasks.forEach(task => {
                        // Make sure that none of the tasks has a task_type parameter
                        assert(task.task_type != Constants.TaskType.CallContact
                            && task.task_type != Constants.TaskType.SMSContact
                            && task.task_type != Constants.TaskType.EmailContact
                            && task.task_type != Constants.TaskType.VisitLocation
                            && task.task_type != Constants.TaskType.URL,
                            `Expected to find NO tasks with a task_type property.`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks assigned to anyone", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks Assigned to Anyone`,
                json_filter: `{"filterGroups":[{"assignment":["${Constants.SystemUserID.AllUser}"]}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should return many tasks that are assigned to someone", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks

                    // Check that ALL tasks returned have an assigned user
                    tasks.forEach(task => {
                        assert(task.assigned_userid != undefined && task.assigned_userid.length > 0,
                        `Found a task that was returned that was not assigned to a user.`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks assigned to nobody", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks Assigned to Nobody`,
                json_filter: `{"filterGroups":[{"assignment":["${Constants.SystemUserID.UnassignedUser}"]}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are not assigned", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Check to see that we can find at least one task
                    // that is assigned.
                    assert(tasks.find(task => {
                        return task.assigned_userid != undefined && task.assigned_userid.length > 0
                    }) == undefined, `Found a task that had an assignment, but expected to find no tasks assigned to anyone.`)
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks assigned to \"ME\"", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks Assigned to ME`,
                json_filter: `{"filterGroups":[{"assignment":["${Constants.SystemUserID.MeUser}"]}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are assigned to \"ME\"", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Check that ALL tasks returned are assigned to "ME"
                    tasks.forEach(task => {
                        assert(task.assigned_userid != undefined
                                && task.assigned_userid.length > 0
                                && task.assigned_userid == testUserid,
                        `Found a task that was returned that was not assigned to "ME".`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks assigned to a specific user", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks Assigned to Specific User`,
                json_filter: `{"filterGroups":[{"assignment":["${testUserid}"]}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are assigned to a specific user", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Check that ALL tasks returned are assigned to testUserid
                    tasks.forEach(task => {
                        assert(task.assigned_userid != undefined
                                && task.assigned_userid.length > 0
                                && task.assigned_userid == testUserid,
                        `Found a task that was returned that was not assigned to a specific user.`)
                    })
                    done()
                })
        })
    })

    //
    // completiondate filter tests
    //

    describe("/smart-lists/{listid}/tasks (GET) - Get non-completed tasks", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Non-completed tasks`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"none"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are NOT completed", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure NO tasks are returned that are completed
                    tasks.forEach(task => {
                        assert(task.completiondate == undefined
                                || task.completiondate == 0,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get ANY completed tasks", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Any completed tasks`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"any"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that ARE completed", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate != 0,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks completed after 2 months ago only (EXACT DATE)", function() {
        const twoMonthsAgo = moment().subtract(2, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            const twoMonthsAgoString = twoMonthsAgo.toISOString()

            let params = {
                name: `Tasks after 2 months ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"after","relation":"exact","date":"${twoMonthsAgoString}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are completed after two months ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const twoMonthsAgoTimestamp = twoMonthsAgo.unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate >= twoMonthsAgoTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks completed after 4 days ago only (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(4, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks after 4 days ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"after","relation":"relative","period":"day","value":"-4"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are completed after 4 days ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks completed after 2 weeks ago only (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(2, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks after 2 weeks ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"after","relation":"relative","period":"week","value":"-2"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are completed after 2 weeks ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks completed after 2 months ago only (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(2, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks after 2 months ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"after","relation":"relative","period":"month","value":"-2"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are completed after 2 months ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks completed after 1 year ago only (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks after 1 year ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"after","relation":"relative","period":"year","value":"-1"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are completed after 1 year ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks completed BEFORE 2 months ago only (EXACT DATE)", function() {
        const twoMonthsAgo = moment().subtract(2, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            const twoMonthsAgoString = twoMonthsAgo.toISOString()

            let params = {
                name: `Tasks BEFORE 2 months ago (EXACT DATE)`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"before","relation":"exact","date":"${twoMonthsAgoString}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are completed BEFORE two months ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const twoMonthsAgoTimestamp = twoMonthsAgo.unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate <= twoMonthsAgoTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks completed before 4 days ago only (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(4, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks BEFORE 4 days ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"before","relation":"relative","period":"day","value":"-4"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are completed BEFORE 4 days ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks completed before 2 weeks ago only (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(2, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks BEFORE 2 weeks ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"before","relation":"relative","period":"week","value":"-2"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are completed BEFORE 2 weeks ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks completed before 5 months ago only (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(5, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks BEFORE 5 months ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"before","relation":"relative","period":"month","value":"-5"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are completed BEFORE 5 months ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks completed before 1 year ago only (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks BEFORE 1 year ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"before","relation":"relative","period":"year","value":"-1"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are completed BEFORE 1 year ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    // completedDate, "type":"is"
    describe("/smart-lists/{listid}/tasks (GET) - Get completed tasks on a specific date", function() {
        const exactDate = moment().subtract(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed tasks on ${exactDate.toISOString()}`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"is","relation":"exact","date":"${exactDate.toISOString()}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are completed on ${exactDate.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate >= exactDateStartTimestamp
                                && task.completiondate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get completed tasks in a specific range", function() {
        const rangeStart = moment().subtract(9, 'month')
        const rangeEnd = moment().subtract(2, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed tasks between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"is","relation":"exact","dateRange":{"start":"${rangeStart.toISOString()}","end":"${rangeEnd.toISOString()}"}}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are completed between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate >= exactDateStartTimestamp
                                && task.completiondate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get completed tasks in a relative INTERVAL range", function() {
        const rangeStart = moment().subtract(9, 'month')
        const rangeEnd = moment().subtract(1, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed tasks between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"is","relation":"relative","intervalRange":{"period":"month","start":"-9","end":"-1"}}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are completed between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate >= exactDateStartTimestamp
                                && task.completiondate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get completed tasks on a relative day (3 weeks ago)", function() {
        const exactDate = moment().subtract(3, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed tasks from 3 weeks ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"is","relation":"relative","period":"week","value":"-3"}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are completed 3 weeks ago from now.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && task.completiondate >= exactDateStartTimestamp
                                && task.completiondate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    // completedDate, "type":"not"
    describe("/smart-lists/{listid}/tasks (GET) - Get completed tasks that are NOT on a specific date", function() {
        const exactDate = moment().subtract(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed tasks NOT on ${exactDate.toISOString()}`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"not","relation":"exact","date":"${exactDate.toISOString()}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT completed on ${exactDate.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && (task.completiondate < exactDateStartTimestamp
                                || task.completiondate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get completed tasks NOT in a specific range", function() {
        const rangeStart = moment().subtract(9, 'month')
        const rangeEnd = moment().subtract(2, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed tasks NOT between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"not","relation":"exact","dateRange":{"start":"${rangeStart.toISOString()}","end":"${rangeEnd.toISOString()}"}}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are completed NOT between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && (task.completiondate < exactDateStartTimestamp
                                || task.completiondate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get completed tasks NOT in a relative INTERVAL range", function() {
        const rangeStart = moment().subtract(9, 'month')
        const rangeEnd = moment().subtract(1, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed tasks NOT between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"not","relation":"relative","intervalRange":{"period":"month","start":"-9","end":"-1"}}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are completed between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && (task.completiondate < exactDateStartTimestamp
                                || task.completiondate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get completed tasks NOT on a relative day (1 week ago)", function() {
        const exactDate = moment().subtract(1, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed tasks from 1 week ago`,
                json_filter: `{"filterGroups":[{"completedDate":{"type":"not","relation":"relative","period":"week","value":"-1"}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are completed NOT 1 week ago from now.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && (task.completiondate < exactDateStartTimestamp
                                || task.completiondate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    //
    // duedate filter tests
    //

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks that do NOT have a due date", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks with NO due date`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"none"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that do NOT have a due date", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure NO tasks are returned that are completed
                    tasks.forEach(task => {
                        assert(task.duedate == undefined
                                || task.duedate == 0,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get ANY due tasks (must have a due date)", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks with a due date`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"any"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that have a due date", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    tasks.forEach(task => {
                        assert((task.completiondate == undefined || task.completiondate == 0)
                            && (task.duedate != undefined && task.duedate != 0),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due after 1 week (EXACT DATE)", function() {
        const exactDate = moment().add(1, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            const exactDateString = exactDate.toISOString()

            let params = {
                name: `Tasks due after one week`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"after","relation":"exact","date":"${exactDateString}"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due after 1 week.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateTimestamp = exactDate.unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate >= exactDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due after 4 days from now (RELATIVE DATE)", function() {
        const relativeDate = moment().add(4, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due after 4 days from now`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"after","relation":"relative","period":"day","value":"4"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due after 4 days from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due after 4 days from now (RELATIVE DATE) - INCLUDING START DATES", function() {
        const relativeDate = moment().add(4, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due after 4 days from now`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"after","relation":"relative","period":"day","value":"4"}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due after 4 days from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert((task.duedate != undefined && task.duedate >= relativeDateTimestamp)
                            || (task.startdate != undefined && task.startdate >= relativeDateTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due after 2 weeks from now (RELATIVE DATE)", function() {
        const relativeDate = moment().add(2, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due after 2 weeks`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"after","relation":"relative","period":"week","value":"2"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due after 2 weeks from today.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due after 2 months (RELATIVE DATE)", function() {
        const relativeDate = moment().add(2, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due after 2 months`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"after","relation":"relative","period":"month","value":"2"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due after 2 months.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due after 1 year (RELATIVE DATE)", function() {
        const relativeDate = moment().add(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due 1 year from today`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"after","relation":"relative","period":"year","value":"1"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due after 1 year.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due BEFORE 2 months from now (EXACT DATE)", function() {
        const exactDate = moment().add(2, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            const exactDateString = exactDate.toISOString()

            let params = {
                name: `Tasks due BEFORE 2 months from now (EXACT DATE)`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"before","relation":"exact","date":"${exactDateString}"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due BEFORE two months from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateTimestamp = exactDate.unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate <= exactDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due before 4 days from now (RELATIVE DATE)", function() {
        const relativeDate = moment().add(4, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due BEFORE 4 days from now`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"before","relation":"relative","period":"day","value":"4"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due BEFORE 4 days from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due before 4 days from now (RELATIVE DATE) - INCLUDE START DATES", function() {
        const relativeDate = moment().add(4, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due BEFORE 4 days from now`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"before","relation":"relative","period":"day","value":"4"}}], "excludeStartDates":false}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due BEFORE 4 days from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert((task.duedate != undefined && task.duedate <= relativeDateTimestamp)
                            || (task.startdate != undefined && task.startdate <= relativeDateTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due before 2 weeks from now only (RELATIVE DATE)", function() {
        const relativeDate = moment().add(2, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due BEFORE 2 weeks from now`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"before","relation":"relative","period":"week","value":"2"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due BEFORE 2 weeks from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due before 5 months from now (RELATIVE DATE)", function() {
        const relativeDate = moment().add(5, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due BEFORE 5 months from now`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"before","relation":"relative","period":"month","value":"5"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due BEFORE 5 months from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due before 1 year from now only (RELATIVE DATE)", function() {
        const relativeDate = moment().add(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due BEFORE 1 year from now`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"before","relation":"relative","period":"year","value":"1"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due BEFORE 1 year from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    // dueDate, "type":"is"
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due on a specific date", function() {
        const exactDate = moment().add(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due on ${exactDate.toISOString()}`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"is","relation":"exact","date":"${exactDate.toISOString()}"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are due on ${exactDate.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate >= exactDateStartTimestamp
                                && task.duedate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due in a specific range", function() {
        const rangeStart = moment().add(2, 'day')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"is","relation":"exact","dateRange":{"start":"${rangeStart.toISOString()}","end":"${rangeEnd.toISOString()}"}}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are due between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate >= exactDateStartTimestamp
                                && task.duedate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due in a relative INTERVAL range", function() {
        const rangeStart = moment().add(1, 'month')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"is","relation":"relative","intervalRange":{"period":"month","start":"1","end":"9"}}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are due between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate >= exactDateStartTimestamp
                                && task.duedate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks due on a relative day (3 weeks from now)", function() {
        const exactDate = moment().add(3, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due 3 weeks from now`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"is","relation":"relative","period":"week","value":"3"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are due in 3 weeks.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && task.duedate >= exactDateStartTimestamp
                                && task.duedate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    // dueDate, "type":"not"
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks that are NOT due on a specific date", function() {
        const exactDate = moment().add(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT due on ${exactDate.toISOString()}`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"not","relation":"exact","date":"${exactDate.toISOString()}"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT due on ${exactDate.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && (task.completiondate < exactDateStartTimestamp
                                || task.completiondate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks NOT due in a specific range", function() {
        const rangeStart = moment().add(2, 'day')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT due between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"not","relation":"exact","dateRange":{"start":"${rangeStart.toISOString()}","end":"${rangeEnd.toISOString()}"}}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT due between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.duedate != undefined
                                && (task.duedate < exactDateStartTimestamp
                                || task.duedate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks NOT due in a relative INTERVAL range", function() {
        const rangeStart = moment().add(1, 'month')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT due between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"not","relation":"relative","intervalRange":{"period":"month","start":"1","end":"9"}}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks not due between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && (task.completiondate < exactDateStartTimestamp
                                || task.completiondate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks NOT due on a relative day (in 1 week)", function() {
        const exactDate = moment().add(1, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT due in 1 week`,
                json_filter: `{"filterGroups":[{"dueDate":{"type":"not","relation":"relative","period":"week","value":"1"}}], "excludeStartDates":true}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT due in 1 week.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.completiondate != undefined
                                && (task.completiondate < exactDateStartTimestamp
                                || task.completiondate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    //
    // modified date (timestamp) filter tests
    //
    // NOTE: Unless we tweak the API to allow us to fake modification
    // dates (timestamp), there's really no way for us to do thorough
    // testing of this particular column, but we'll have these tests
    // in here anyway so that the code paths will get exercised.
    //

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified after 1 week ago (EXACT DATE)", function() {
        const exactDate = moment().subtract(1, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            const exactDateString = exactDate.toISOString()

            let params = {
                name: `Tasks modified after one week`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"after","relation":"exact","date":"${exactDateString}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that were modified after 1 week ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateTimestamp = exactDate.unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp >= exactDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified after 4 days ago (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(4, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified after 4 days ago`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"after","relation":"relative","period":"day","value":"-4"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that were modified after 4 days ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified after 2 weeks ago (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(2, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks due after 2 weeks ago`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"after","relation":"relative","period":"week","value":"-2"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are modified after 2 weeks ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified after 2 months ago (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(2, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified after 2 months ago`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"after","relation":"relative","period":"month","value":"-2"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are modified after 2 months ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified after 1 year ago (RELATIVE DATE)", function() {
        const relativeDate = moment().subtract(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified after 1 year ago`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"after","relation":"relative","period":"year","value":"-1"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are modified after 1 year ago.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified BEFORE 2 months from now (EXACT DATE)", function() {
        const exactDate = moment().add(2, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            const exactDateString = exactDate.toISOString()

            let params = {
                name: `Tasks modified BEFORE 2 months from now (EXACT DATE)`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"before","relation":"exact","date":"${exactDateString}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are modified BEFORE two months from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateTimestamp = exactDate.unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp <= exactDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified before 4 days from now (RELATIVE DATE)", function() {
        const relativeDate = moment().add(4, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified BEFORE 4 days from now`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"before","relation":"relative","period":"day","value":"4"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are due BEFORE 4 days from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified before 2 weeks from now only (RELATIVE DATE)", function() {
        const relativeDate = moment().add(2, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified BEFORE 2 weeks from now`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"before","relation":"relative","period":"week","value":"2"}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are modified BEFORE 2 weeks from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified before 5 months from now (RELATIVE DATE)", function() {
        const relativeDate = moment().add(5, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified BEFORE 5 months from now`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"before","relation":"relative","period":"month","value":"5"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are modified BEFORE 5 months from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified before 1 year from now only (RELATIVE DATE)", function() {
        const relativeDate = moment().add(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified BEFORE 1 year from now`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"before","relation":"relative","period":"year","value":"1"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that are modified BEFORE 1 year from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    // modifiedDate, "type":"is"
    describe("/smart-lists/{listid}/tasks (GET) - Get modified tasks on a specific date", function() {
        const exactDate = moment().subtract(0, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Modified tasks on ${exactDate.toISOString()}`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"is","relation":"exact","date":"${exactDate.toISOString()}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are modified on ${exactDate.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp >= exactDateStartTimestamp
                                && task.timestamp <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified in a specific range", function() {
        const rangeStart = moment().subtract(2, 'day')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"is","relation":"exact","dateRange":{"start":"${rangeStart.toISOString()}","end":"${rangeEnd.toISOString()}"}}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are modified between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp >= exactDateStartTimestamp
                                && task.timestamp <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified in a relative INTERVAL range", function() {
        const rangeStart = moment().subtract(1, 'month')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"is","relation":"relative","intervalRange":{"period":"month","start":"-1","end":"9"}}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are modified between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && task.timestamp >= exactDateStartTimestamp
                                && task.timestamp <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    // quick test to see that everything is working by specifing
    // a range that will return NO tasks
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified in a relative INTERVAL range (not in range)", function() {
        const rangeStart = moment().add(1, 'month')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"is","relation":"relative","intervalRange":{"period":"month","start":"1","end":"9"}}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should return NO tasks that are modified between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.below(1)
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks modified on a relative day (3 weeks from now)", function() {
        const exactDate = moment().add(3, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks modified 3 weeks from now`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"is","relation":"relative","period":"week","value":"3"}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should return NO tasks that are modified in 3 weeks.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.below(1)
                    done()
                })
        })
    })

    // modifiedDate, "type":"not"
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks that are NOT modified on a specific date", function() {
        const exactDate = moment().add(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT due on ${exactDate.toISOString()}`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"not","relation":"exact","date":"${exactDate.toISOString()}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT modified on ${exactDate.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && (task.timestamp < exactDateStartTimestamp
                                || task.timestamp > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks NOT modified in a specific range", function() {
        const rangeStart = moment().add(2, 'day')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT modified between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"not","relation":"exact","dateRange":{"start":"${rangeStart.toISOString()}","end":"${rangeEnd.toISOString()}"}}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT modified between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && (task.timestamp < exactDateStartTimestamp
                                || task.timestamp > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks NOT modified in a relative INTERVAL range", function() {
        const rangeStart = moment().add(1, 'month')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT modified between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"not","relation":"relative","intervalRange":{"period":"month","start":"1","end":"9"}}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks not modified between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && (task.timestamp < exactDateStartTimestamp
                                || task.timestamp > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks NOT modified on a relative day (in 1 week)", function() {
        const exactDate = moment().add(1, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT modified in 1 week`,
                json_filter: `{"filterGroups":[{"modifiedDate":{"type":"not","relation":"relative","period":"week","value":"1"}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT modified in 1 week.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.timestamp != undefined
                                && (task.timestamp < exactDateStartTimestamp
                                || task.timestamp > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    //
    // startDate filter tests
    //

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks that do NOT have a start date", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks with NO start date`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"none"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that do NOT have a start date", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure NO tasks are returned that are completed
                    tasks.forEach(task => {
                        assert(task.startdate == undefined
                                || task.startdate == 0,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get ANY tasks (must have a start date)", function() {
        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks with a start date`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"any"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that have a start date", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    tasks.forEach(task => {
                        assert((task.startdate != undefined && task.startdate != 0),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks that start after 1 week (EXACT DATE)", function() {
        const exactDate = moment().add(1, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            const exactDateString = exactDate.toISOString()

            let params = {
                name: `Tasks starting after one week`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"after","relation":"exact","date":"${exactDateString}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that start after 1 week.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateTimestamp = exactDate.unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate >= exactDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting after 4 days from now (RELATIVE DATE)", function() {
        const relativeDate = moment().add(4, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting after 4 days from now`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"after","relation":"relative","period":"day","value":"4"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that start after 4 days from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting after 2 weeks from now (RELATIVE DATE)", function() {
        const relativeDate = moment().add(2, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting after 2 weeks`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"after","relation":"relative","period":"week","value":"2"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that start after 2 weeks from today.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting after 2 months (RELATIVE DATE)", function() {
        const relativeDate = moment().add(2, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting after 2 months`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"after","relation":"relative","period":"month","value":"2"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that start after 2 months.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting after 1 year (RELATIVE DATE)", function() {
        const relativeDate = moment().add(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting 1 year from today`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"after","relation":"relative","period":"year","value":"1"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that start after 1 year.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate >= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting BEFORE 2 months from now (EXACT DATE)", function() {
        const exactDate = moment().add(2, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            const exactDateString = exactDate.toISOString()

            let params = {
                name: `Tasks starting BEFORE 2 months from now (EXACT DATE)`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"before","relation":"exact","date":"${exactDateString}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that start BEFORE two months from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateTimestamp = exactDate.unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate <= exactDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting before 4 days from now (RELATIVE DATE)", function() {
        const relativeDate = moment().add(4, 'day')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting BEFORE 4 days from now`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"before","relation":"relative","period":"day","value":"4"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that start BEFORE 4 days from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting before 2 weeks from now only (RELATIVE DATE)", function() {
        const relativeDate = moment().add(2, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting BEFORE 2 weeks from now`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"before","relation":"relative","period":"week","value":"2"}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that start BEFORE 2 weeks from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting before 5 months from now (RELATIVE DATE)", function() {
        const relativeDate = moment().add(5, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting BEFORE 5 months from now`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"before","relation":"relative","period":"month","value":"5"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that start BEFORE 5 months from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    // Make sure ALL returned tasks are completed
                    // and completed after two months ago.
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting before 1 year from now only (RELATIVE DATE)", function() {
        const relativeDate = moment().add(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting BEFORE 1 year from now`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"before","relation":"relative","period":"year","value":"1"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it("Should only return tasks that start BEFORE 1 year from now.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const relativeDateTimestamp = relativeDate.unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate <= relativeDateTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    // // dueDate, "type":"is"
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting on a specific date", function() {
        const exactDate = moment().add(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting on ${exactDate.toISOString()}`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"is","relation":"exact","date":"${exactDate.toISOString()}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are due on ${exactDate.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate >= exactDateStartTimestamp
                                && task.startdate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting in a specific range", function() {
        const rangeStart = moment().add(2, 'day')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"is","relation":"exact","dateRange":{"start":"${rangeStart.toISOString()}","end":"${rangeEnd.toISOString()}"}}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that start between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate >= exactDateStartTimestamp
                                && task.startdate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting in a relative INTERVAL range", function() {
        const rangeStart = moment().add(1, 'month')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"is","relation":"relative","intervalRange":{"period":"month","start":"1","end":"9"}}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that start between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate >= exactDateStartTimestamp
                                && task.startdate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks starting on a relative day (3 weeks from now)", function() {
        const exactDate = moment().add(3, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks starting 3 weeks from now`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"is","relation":"relative","period":"week","value":"3"}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that start in 3 weeks.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && task.startdate >= exactDateStartTimestamp
                                && task.startdate <= exactDateEndTimestamp,
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    // // dueDate, "type":"not"
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks that are NOT starting on a specific date", function() {
        const exactDate = moment().add(1, 'year')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT starting on ${exactDate.toISOString()}`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"not","relation":"exact","date":"${exactDate.toISOString()}"}}]} `
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT starting on ${exactDate.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && (task.startdate < exactDateStartTimestamp
                                || task.startdate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks NOT starting in a specific range", function() {
        const rangeStart = moment().add(2, 'day')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT starting between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"not","relation":"exact","dateRange":{"start":"${rangeStart.toISOString()}","end":"${rangeEnd.toISOString()}"}}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT starting between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && (task.startdate < exactDateStartTimestamp
                                || task.startdate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks NOT starting in a relative INTERVAL range", function() {
        const rangeStart = moment().add(1, 'month')
        const rangeEnd = moment().add(9, 'month')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT starting between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"not","relation":"relative","intervalRange":{"period":"month","start":"1","end":"9"}}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks not starting between ${rangeStart.toISOString()} and ${rangeEnd.toISOString()}.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = rangeStart.startOf('day').unix()
                    const exactDateEndTimestamp = rangeEnd.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && (task.startdate < exactDateStartTimestamp
                                || task.startdate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks NOT starting on a relative day (in 1 week)", function() {
        const exactDate = moment().add(1, 'week')

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks NOT due in 1 week`,
                json_filter: `{"filterGroups":[{"startDate":{"type":"not","relation":"relative","period":"week","value":"1"}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT starting in 1 week.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    const exactDateStartTimestamp = exactDate.startOf('day').unix()
                    const exactDateEndTimestamp = exactDate.endOf('day').unix()
                    tasks.forEach(task => {
                        assert(task.startdate != undefined
                                && (task.startdate < exactDateStartTimestamp
                                || task.startdate > exactDateEndTimestamp),
                        `Found a task out of range: ${task.name}`)
                    })
                    done()
                })
        })
    })

    //
    // Location Filter Tests
    //
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks that do NOT have a location alert", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks without a location alert`,
                json_filter: `{"filterGroups":[{"hasLocation":false}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that do not have a location alert.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    tasks.forEach(task => {
                        assert(task.location_alert == undefined
                                || task.lcoation_alert.length == 0,
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })
    
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks that have a location alert", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Tasks WITH a location alert`,
                json_filter: `{"filterGroups":[{"hasLocation":true}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that have a location alert.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    
                    tasks.forEach(task => {
                        assert(task.location_alert != undefined
                                && task.location_alert.length > 0,
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    //
    // Smart List "name" filter tests
    //
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks whose name matches search (OR)", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"name":{"comparator":"or","searchTerms":[{"contains":true,"text":"milk"},{"contains":true,"text":"chocolate"}]}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that match search terms.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 4, `Expected to receive 4 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.name != undefined
                                && (task.name.toLowerCase().indexOf('milk') >= 0
                                    || task.name.toLowerCase().indexOf('chocolate') >= 0),
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks whose name matches search (AND)", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"name":{"comparator":"AND","searchTerms":[{"contains":true,"text":"milk"},{"contains":true,"text":"chocolate"}]}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that match search terms.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 2, `Expected to receive 2 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.name != undefined
                                && task.name.toLowerCase().indexOf('milk') >= 0
                                && task.name.toLowerCase().indexOf('chocolate') >= 0,
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Attempt to get tasks whose name matches search with bogus search term (OR)", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"name":{"comparator":"OR","searchTerms":[{"contains":true,"text":"jukka"},{"contains":true,"text":"pekka"}]}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should return NO tasks.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 0, `Expected to receive NO matching tasks but got: ${tasks.length}`)
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Attempt to get tasks whose name matches search with bogus search term (AND)", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"name":{"comparator":"AND","searchTerms":[{"contains":true,"text":"jukka"},{"contains":true,"text":"pekka"}]}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should return NO tasks.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 0, `Expected to receive NO matching tasks but got: ${tasks.length}`)
                    done()
                })
        })
    })

    //
    // Smart List "note" filter tests
    //
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks whose note matches search (OR)", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"note":{"comparator":"or","searchTerms":[{"contains":true,"text":"jungle"},{"contains":true,"text":"campfire"}]}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that match search terms.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 4, `Expected to receive 4 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.note != undefined
                                && (task.note.toLowerCase().indexOf('jungle') >= 0
                                    || task.note.toLowerCase().indexOf('campfire') >= 0),
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks whose note matches search (AND)", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"note":{"comparator":"AND","searchTerms":[{"contains":true,"text":"campfire"},{"contains":true,"text":"eat"}]}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that match search terms.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 2, `Expected to receive 2 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.note != undefined
                                && task.note.toLowerCase().indexOf('campfire') >= 0
                                && task.note.toLowerCase().indexOf('eat') >= 0,
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Attempt to get tasks whose note matches search with bogus search term (OR)", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"note":{"comparator":"OR","searchTerms":[{"contains":true,"text":"jukka"},{"contains":true,"text":"pekka"}]}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should return NO tasks.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 0, `Expected to receive NO matching tasks but got: ${tasks.length}`)
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Attempt to get tasks whose note matches search with bogus search term (AND)", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"note":{"comparator":"AND","searchTerms":[{"contains":true,"text":"jukka"},{"contains":true,"text":"pekka"}]}}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should return NO tasks.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 0, `Expected to receive NO matching tasks but got: ${tasks.length}`)
                    done()
                })
        })
    })

    //
    // Smart List priority filter tests
    //
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks with no priority", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"priority":["none"]}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that match priority filter.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(1)
                    const tasks = res.body.tasks
                    assert(tasks.length > 1, `Expected to receive more than one 1 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.priority != undefined
                                && (task.priority == 0 || task.priority == Constants.TaskPriority.None),
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks with a higher priority than NONE", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"priority":["high", "med", "low"]}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that match priority filter.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(1)
                    const tasks = res.body.tasks
                    assert(tasks.length > 1, `Expected to receive more than one 1 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.priority != undefined
                                && task.priority != Constants.TaskPriority.None,
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks with HIGH priority", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"priority":["high"]}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that match priority filter.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.below(2)
                    const tasks = res.body.tasks
                    assert(tasks.length == 1, `Expected to receive 1 matching task but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.priority != undefined
                                && task.priority == Constants.TaskPriority.High,
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks with MEDIUM priority", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"priority":["med"]}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that match priority filter.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.below(2)
                    const tasks = res.body.tasks
                    assert(tasks.length == 1, `Expected to receive 1 matching task but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.priority != undefined
                                && task.priority == Constants.TaskPriority.Medium,
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks with LOW priority", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"priority":["low"]}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that match priority filter.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.below(2)
                    const tasks = res.body.tasks
                    assert(tasks.length == 1, `Expected to receive 1 matching task but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.priority != undefined
                                && task.priority == Constants.TaskPriority.Low,
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get tasks with NO priority OR HIGH priority", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"priority":["high", "none"]}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that match priority filter.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(1)
                    const tasks = res.body.tasks
                    assert(tasks.length > 1, `Expected to receive more than one 1 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.priority != undefined
                                && (task.priority == Constants.TaskPriority.None
                                    || task.priority == Constants.TaskPriority.High),
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    //
    // Smart List recurrence filter tests
    //
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks with no recurrence", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"hasRecurrence":false}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that have no recurrence field.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(1)
                    const tasks = res.body.tasks
                    assert(tasks.length > 1, `Expected to receive more than one 1 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.recurrence_type == undefined
                                || task.recurrence_type == 0,
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get ONLY recurring tasks", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Some smart list name`,
                json_filter: `{"filterGroups":[{"hasRecurrence":true}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are recurring.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(1)
                    const tasks = res.body.tasks
                    assert(tasks.length > 1, `Expected to receive more than one 1 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(task.recurrence_type != undefined
                                && task.recurrence_type >= 0,
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    //
    // Smart List starred filter tests
    //
    describe("/smart-lists/{listid}/tasks (GET) - Get tasks with no star", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Unstarred tasks`,
                json_filter: `{"filterGroups":[{"starred":false}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return tasks that are NOT starred.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(1)
                    const tasks = res.body.tasks
                    assert(tasks.length > 1, `Expected to receive more than one 1 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert((task.task_type != Constants.TaskType.Project && (task.starred == undefined || task.starred == 0))
                            || (task.task_type == Constants.TaskType.Project && (task.project_starred == undefined || task.project_starred == 0)),
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Get ONLY starred tasks", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Starred tasks`,
                json_filter: `{"filterGroups":[{"starred":true}]}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should only return starred tasks.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(1)
                    const tasks = res.body.tasks
                    assert(tasks.length > 1, `Expected to receive more than one 1 matching tasks but got: ${tasks.length}`)
                    
                    tasks.forEach(task => {
                        assert(((task.task_type == Constants.TaskType.Normal || task.task_type == Constants.TaskType.Checklist) && (task.starred != undefined && task.starred > 0))
                            || (task.task_type == Constants.TaskType.Project && (task.project_starred != undefined && task.project_starred > 0)),
                        `Found an invalid task: ${task.name}`)
                    })
                    done()
                })
        })
    })

    //
    // Smart List Completed Tasks Filter Tests
    //
    describe("/smart-lists/{listid}/tasks (GET) - Mismatch of query completed_only=false (inferred) with conflicting completed_tasks_filter setting", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed only filter`,
                json_filter: `{"completedTasks":{"type":"completed","period":"2weeks"},"filterGroups":[{"starred":false}]}`,
                completed_tasks_filter: `{"type":"completed","period":"2weeks"}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                res.body.should.have.property("completed_tasks_filter").eql(params.completed_tasks_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should return NO tasks because of mismatch of completed_tasks param.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 0, `Expected to receive NO matching tasks but got: ${tasks.length}`)
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Mismatch of query completed_only=false (implicit) with conflicting completed_tasks_filter setting", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed only filter`,
                json_filter: `{"completedTasks":{"type":"completed","period":"2weeks"},"filterGroups":[{"starred":false}]}`,
                completed_tasks_filter: `{"type":"completed","period":"2weeks"}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}?completed_only=false`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                res.body.should.have.property("completed_tasks_filter").eql(params.completed_tasks_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should return NO tasks because of mismatch of completed_tasks param.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 0, `Expected to receive NO matching tasks but got: ${tasks.length}`)
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - Mismatch of query completed_only=true (implicit) with conflicting completed_tasks_filter setting", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed only filter`,
                json_filter: `{"completedTasks":{"type":"active","period":"none"},"filterGroups":[{"starred":false}]}`,
                completed_tasks_filter: `{"type":"active","period":"none"}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                res.body.should.have.property("completed_tasks_filter").eql(params.completed_tasks_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should return NO tasks because of mismatch of completed_tasks param.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks")
                    const tasks = res.body.tasks
                    assert(tasks.length == 0, `Expected to receive NO matching tasks but got: ${tasks.length}`)
                    done()
                })
        })
    })

    describe("/smart-lists/{listid}/tasks (GET) - completed_only=true with 'all' as completed tasks filter type", function() {

        before(function(done) {
            if (!UnitTests.SmartListTasks) {
                done()
                return
            }

            let params = {
                name: `Completed only filter`,
                json_filter: `{"completedTasks":{"type":"all","period":"1month"},"filterGroups":[{"starred":false}]}`,
                completed_tasks_filter: `{"type":"all","period":"1month"}`
            }
            chai
            .request(baseApiUrl)
            .put(`/smart-lists/${testSmartListId}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(params)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("json_filter").eql(params.json_filter)
                res.body.should.have.property("completed_tasks_filter").eql(params.completed_tasks_filter)
                done()
            })
        })

        assuming(UnitTests.SmartListTasks).it(`Should return completed tasks.`, function(done) {
            chai
                .request(baseApiUrl)
                .get(`/smart-lists/${testSmartListId}/tasks?completed_only=true`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("tasks").length.above(0)
                    const tasks = res.body.tasks
                    assert(tasks.length > 0, `Expected to receive matching tasks but got: ${tasks.length}`)
                    done()
                })
        })
    })

})

describe("Checklist Testing", function() {

    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    let listid = null
    let parentid = null
    let taskitoid = null
    let taskitoid2 = null
    let taskito = null

    before(function(done) {
        if (!UnitTests.Checklists) {
            done()
            return
        }
        debug(`        before() for Checklist Tests...`)
        async.waterfall([
            function(callback) {
                setUpAuthenticatedAccount(function(done) {
                    callback(null)
                })
            },
            function(callback) {
                // Set up a list that we can use to test tasks
                let newList = {
                    name: "Test List"
                }
                chai
                    .request(baseApiUrl)
                    .post("/lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newList)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("list")
                        res.body.list.should.have.property("listid")
                        res.body.should.have.property("settings")
                        listid = res.body.list.listid
                        callback(null)
                    })
            },
            function(callback) {
                const checklistName = `Checklist Task`
                const duedate = Math.floor(Date.now() / 1000)
                let newTask = {
                    name: checklistName,
                    listid: listid,
                    duedate: duedate,
                    task_type: Constants.TaskType.Checklist
                }
                chai
                .request(baseApiUrl)
                .post("/tasks")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTask)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskid")
                    parentid = res.body.taskid
                    res.body.should.have.property("name").eql(newTask.name)
                    res.body.should.have.property("duedate").eql(duedate)
                    res.body.should.have.property("task_type").eql(Constants.TaskType.Checklist)
                    callback(null)
                })
            },
        ],
        function(err) {
            assert(!err, `Setup for the checklist tests failed: ${err}`)
            done()
        })
    })

    after(function(done) {
        if (!UnitTests.Checklists) {
            done()
            return
        }
        deleteAccount(done)
    })

        
    describe("Create a taskito", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200)", function(done) {
            let newTaskito = {
                name: "Test Taskito",
                parentid: parentid,
            }
            chai
                .request(baseApiUrl)
                .post("/checklist/items")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTaskito)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskitoid")
                    res.body.should.have.property("parentid").eql(newTaskito.parentid)
                    res.body.should.have.property("name").eql(newTaskito.name)
                    taskitoid = res.body.taskitoid
                    taskito = res.body
                    done()
                })
        })
    })

    describe("Create a second taskito", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200) and have a higher sort order than the first taskito", function(done) {
            let newTaskito = {
                name: "Test Taskito 2",
                parentid: parentid,
            }
            chai
                .request(baseApiUrl)
                .post("/checklist/items")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newTaskito)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskitoid").not.equal(taskito.taskitoid)
                    res.body.should.have.property("parentid").eql(newTaskito.parentid)
                    res.body.should.have.property("name").eql(newTaskito.name)
                    res.body.should.have.property("sort_order").above(taskito.sort_order)
                    taskitoid2 = res.body.taskitoid
                    done()
                })
        })
    })

    describe("Attempt to update a taskito without sending required params", function() {
        assuming(UnitTests.Checklists).it("Should fail (400)", function(done) {
            chai
                .request(baseApiUrl)
                .put(`/checklist/items/${taskitoid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    res.body.should.be.a("object")
                    res.body.should.have.property("code").eql(Errors.missingParameters.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe("Rename a taskito", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200)", function(done) {
            let updateParams = {
                name: "Renamed Taskito"
            }
            chai
                .request(baseApiUrl)
                .put(`/checklist/items/${taskitoid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(updateParams)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskitoid").eql(taskito.taskitoid)
                    res.body.should.have.property("name").eql(updateParams.name)
                    done()
                })
        })
    })

    describe("Change taskito sort order", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200)", function(done) {
            let updateParams = {
                sort_order: 247
            }
            chai
                .request(baseApiUrl)
                .put(`/checklist/items/${taskitoid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(updateParams)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskitoid").eql(taskito.taskitoid)
                    res.body.should.have.property("sort_order").eql(updateParams.sort_order)
                    done()
                })
        })
    })

    describe("Change taskito name and sort order simultaneously", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200)", function(done) {
            let updateParams = {
                name: "Original Taskito",
                sort_order: 147
            }
            chai
                .request(baseApiUrl)
                .put(`/checklist/items/${taskitoid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(updateParams)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("taskitoid").eql(taskito.taskitoid)
                    res.body.should.have.property("name").eql(updateParams.name)
                    res.body.should.have.property("sort_order").eql(updateParams.sort_order)
                    done()
                })
        })
    })

    describe("Read taskitos", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200)")
    })

    describe("Read taskitos (check paging features)", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200)")
    })

    describe("/tasks/{taskid}/count (GET) - Get taskito count", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200) - and return a count of 2", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/tasks/${parentid}/count`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property('count')
                    res.body.count.should.equal(2)
                    done()
                })
        })
    })

    describe("Complete checklist items", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200)", function(done) {
            let tasksToComplete = {
                "items": [
                    taskitoid,
                    taskitoid2
                ]
            }
            chai
            .request(baseApiUrl)
            .post(`/checklist/items/complete`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(tasksToComplete)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("items")
                let completedTasks = res.body.items
                assert(completedTasks.length == 2, `Expected TWO completed tasks.`)
                tasksToComplete.items.forEach((itemid) => {
                    assert(completedTasks.find(function(aTaskitoId, index) {
                        return itemid == aTaskitoId
                    }) != undefined, `Expected ${itemid} as one of the completed tasks.`)
                })
                done()
            })
        })
    })

    describe("Uncomplete checklist items", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200)", function(done) {
            let tasksToUncomplete = {
                "items": [
                    taskitoid,
                    taskitoid2
                ]
            }
            chai
            .request(baseApiUrl)
            .post(`/checklist/items/uncomplete`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(tasksToUncomplete)
            .end(function(err, res) {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a("object")
                res.body.should.have.property("items")
                let uncompletedTasks = res.body.items
                assert(uncompletedTasks.length == 2, `Expected TWO uncompleted tasks.`)
                tasksToUncomplete.items.forEach((itemid) => {
                    assert(uncompletedTasks.find(function(aTaskitoId, index) {
                        return itemid == aTaskitoId
                    }) != undefined, `Expected ${itemid} as one of the uncompleted tasks.`)
                })
                done()
            })
            
        })
    })

    describe("Delete a taskito", function() {
        assuming(UnitTests.Checklists).it("Should succeed (204)", function(done) {
            chai
                .request(baseApiUrl)
                .delete(`/checklist/items/${taskitoid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    done()
                })
        })
    })

    describe("Read checklist items to make sure deleted taskito is not present", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200)")
    })

    describe("Delete a checklist", function() {
        assuming(UnitTests.Checklists).it("Should succeed (204)")
    })

    describe("Read a completed checklist", function() {
        assuming(UnitTests.Checklists).it("Should succeed (200)")
    })
})

describe("Todo Cloud API - Tag Tests", function() {
    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    // Variables used across tests
    let listid = null
    let taskid = null
    let tagid  = null

    before(function(done) {
        if (!UnitTests.Tags) {
            done()
            return
        }
        async.waterfall([
            function(callback) {
                setUpAuthenticatedAccount(function(done) {
                    callback(null)
                })
            },
            function(callback) {
                // Set up a list that we can use to test tasks
                let newList = {
                    name: "Test List"
                }
                chai
                    .request(baseApiUrl)
                    .post("/lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newList)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("list")
                        res.body.list.should.have.property("listid")
                        res.body.should.have.property("settings")
                        listid = res.body.list.listid
                        callback(null)
                    })
            },
            function(callback) {
                let params = {
                    name: "Test Task",
                    listid: listid
                }
                chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(params)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("timestamp")
                        res.body.should.have.property("listid").eql(listid)
                        res.body.should.have.property("name").eql("Test Task")
                        taskid = res.body.taskid
                        callback(null)
                    })
            }
        ],
        function(err) {
            assert(!err, `An error occurred setting up for the task tests: ${err}`)
            done()
        })
    })

    after(function(done) {
        if (!UnitTests.Tags) {
            done()
            return
        }
        deleteAccount(done)
    })

    describe('/tags (POST) - Create a tag', () => {
        assuming(UnitTests.Tags).it('Should succeed (200) - tag should be created', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/tags`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ name : 'Test Tag' })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('tagid')
                res.body.should.have.property('name').eql('Test Tag')
                tagid = res.body.tagid
                done()
            })
        })
    })

    describe('/tasks/:taskid/tags/:tagid (POST) - Assign a tag to a task', () => {
        assuming(UnitTests.Tags).it('Should succeed (200) - a tag should be assigned to the task', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/tasks/${taskid}/tags/${tagid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({})
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('tagid').eql(tagid)
                res.body.should.have.property('taskid').eql(taskid)
                done()
            })
        })
    })

    describe('/tags (GET) - Get all tags associated with an account', () => {
        assuming(UnitTests.Tags).it("Should succeed (200) - Should get all tags associated with a user's tasks", (done) => {
            chai
            .request(baseApiUrl)
            .get(`/tags`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')
                res.body.should.not.be.empty
                res.body[0].should.have.property('tagid').eql(tagid)
                done()
            })
        })
    })

    describe('/tasks/:taskid/tags (GET) - Get all tags associated with a task', () => {
        assuming(UnitTests.Tags).it("Should succeed (200) - Should get all tags associated a task", (done) => {
            chai
            .request(baseApiUrl)
            .get(`/tasks/${taskid}/tags`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')
                res.body.should.not.be.empty
                res.body[0].should.have.property('tagid').eql(tagid)
                done()
            })
        })
    })

    describe('/tags/:tagid (PUT) - Updates a specific tag', () => {
        assuming(UnitTests.Tags).it("Should succeed (200) - Should Updates a single specific tag", (done) => {
            chai
            .request(baseApiUrl)
            .put(`/tags/${tagid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ name : 'Updated Test Tag' })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('object')
                res.body.should.have.property('tagid').eql(tagid)
                res.body.should.have.property('name').eql('Updated Test Tag')
                done()
            })
        })
    })

    describe('/tasks/:taskid/tags/:tagid (DELETE) - Remove tag assignment from a task', () => {
        assuming(UnitTests.Tags).it('Should succeed (204) - A tag assignment should be removed from the task', (done) => {
            chai
            .request(baseApiUrl)
            .delete(`/tasks/${taskid}/tags/${tagid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(204)
                done()
            })
        })
    })

    describe('/tags/:tagid (GET) - Gets a specific tag', () => {
        assuming(UnitTests.Tags).it("Should succeed (200) - Should get a single specific tag", (done) => {
            chai
            .request(baseApiUrl)
            .get(`/tags/${tagid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('object')
                res.body.should.have.property('tagid').eql(tagid)
                res.body.should.have.property('name').eql('Updated Test Tag')
                done()
            })
        })
    })

    describe('/tasks/:taskid/tags (GET) - Get all tags associated with a task', () => {
        assuming(UnitTests.Tags).it("Should succeed (200) - Should no tags associated with this task", (done) => {
            chai
            .request(baseApiUrl)
            .get(`/tasks/${taskid}/tags`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')
                res.body.should.be.empty
                done()
            })
        })
    })

    describe('/tags (GET) - Get all tags associated with an account', () => {
        assuming(UnitTests.Tags).it("Should succeed (200) - Should get all tags associated with a user's tasks", (done) => {
            chai
            .request(baseApiUrl)
            .get(`/tags`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')
                res.body.should.be.empty
                done()
            })
        })
    })

    describe('/tags/:tagid (DELETE) - Delete a tag', () => {
        assuming(UnitTests.Tags).it('Should succeed (204) - a tag should be deleted', (done) => {
            chai
            .request(baseApiUrl)
            .delete(`/tags/${tagid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(204)
                done()
            })
        })
    })
})

describe("Todo Cloud API - Comment Tests", function() {
    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    // Variables used across tests
    let listid = null
    let taskid = null
    let commentid1  = null
    let commentid2  = null

    before(function(done) {
        if (!UnitTests.Comments) {
            done()
            return
        }
        async.waterfall([
            function(callback) {
                setUpAuthenticatedAccount(function(done) {
                    callback(null)
                })
            },
            function(callback) {
                // Set up a list that we can use to test tasks
                let newList = {
                    name: "Test List"
                }
                chai
                    .request(baseApiUrl)
                    .post("/lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newList)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("list")
                        res.body.list.should.have.property("listid")
                        res.body.should.have.property("settings")
                        listid = res.body.list.listid
                        callback(null)
                    })
            },
            function(callback) {
                let params = {
                    name: "Test Task",
                    listid: listid
                }
                chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(params)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("timestamp")
                        res.body.should.have.property("listid").eql(listid)
                        res.body.should.have.property("name").eql("Test Task")
                        taskid = res.body.taskid
                        callback(null)
                    })
            }
        ],
        function(err) {
            assert(!err, `An error occurred setting up for the task tests: ${err}`)
            done()
        })
    })

    after(function(done) {
        if (!UnitTests.Comments) {
            done()
            return
        }
        deleteAccount(done)
    })

    describe('/comments (POST) - Create a comment', () => {
        assuming(UnitTests.Comments).it('Should succeed (200) - comment should be created', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/comments`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ text : 'Test Comment One', itemid : taskid })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('commentid')
                res.body.should.have.property('text').eql('Test Comment One')
                res.body.should.have.property('userid').eql(testUserid)
                commentid1 = res.body.commentid
                done()
            })
        })

        assuming(UnitTests.Comments).it('Should succeed (200) - comment should be created', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/comments`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ text : 'Test Comment Two', itemid : taskid })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('commentid')
                res.body.should.have.property('text').eql('Test Comment Two')
                res.body.should.have.property('userid').eql(testUserid)
                commentid2 = res.body.commentid
                done()
            })
        })
    })
    
    describe('/tasks/:taskid/comments (GET) - Get all comments associated with a task', () => {
        assuming(UnitTests.Comments).it("Should succeed (200) - Should get all comments associated a task", (done) => {
            chai
            .request(baseApiUrl)
            .get(`/tasks/${taskid}/comments`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')
                res.body.should.not.be.empty
                
                const obj1 = res.body[0]
                const obj2 = res.body[1]
                obj1.should.be.an('object')
                obj2.should.be.an('object')

                obj1.should.have.property('comment')
                obj1.comment.should.have.property('userid').eql(testUserid)
                obj1.should.have.property('user')
                obj1.user.should.have.property('userid').eql(testUserid)
                obj2.should.have.property('comment')
                obj2.comment.should.have.property('userid').eql(testUserid)
                obj2.should.have.property('user')
                obj2.user.should.have.property('userid').eql(testUserid)
                done()
            })
        })
    })

    describe('/comments/:commentid (GET) - Get all a specific comment', () => {
        assuming(UnitTests.Comments).it("Should succeed (200) - Should get a specific comment", (done) => {
            chai
            .request(baseApiUrl)
            .get(`/comments/${commentid1}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('text').eql('Test Comment One')
                res.body.should.have.property('userid').eql(testUserid)
                res.body.should.have.property('commentid').eql(commentid1)
                done()
            })
        })

        assuming(UnitTests.Comments).it("Should succeed (200) - Should get a specific comment", (done) => {
            chai
            .request(baseApiUrl)
            .get(`/comments/${commentid2}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('text').eql('Test Comment Two')
                res.body.should.have.property('userid').eql(testUserid)
                res.body.should.have.property('commentid').eql(commentid2)
                done()
            })
        })
    })

    describe('/comments/:commentid (PUT) - Updates a specific comment', () => {
        assuming(UnitTests.Comments).it("Should succeed (200) - Should update a single specific comment", (done) => {
            chai
            .request(baseApiUrl)
            .put(`/comments/${commentid1}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ text : 'Updated Test Comment' })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('object')
                res.body.should.have.property('commentid').eql(commentid1)
                res.body.should.have.property('userid').eql(testUserid)
                res.body.should.have.property('text').eql('Updated Test Comment')
                done()
            })
        })
    })

    describe('/comments/:commentid (DELETE) - Delete a comment', () => {
        assuming(UnitTests.Comments).it('Should succeed (204) - a comment should be deleted', (done) => {
            chai
            .request(baseApiUrl)
            .delete(`/comments/${commentid1}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(204)
                done()
            })
        })

        assuming(UnitTests.Comments).it('Should succeed (204) - a comment should be deleted', (done) => {
            chai
            .request(baseApiUrl)
            .delete(`/comments/${commentid2}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(204)
                done()
            })
        })
    })

    describe('/tasks/:taskid/comments (GET) - Get all comments associated with a task', () => {
        assuming(UnitTests.Comments).it("Should succeed (200) - Should get all comments associated a task", (done) => {
            chai
            .request(baseApiUrl)
            .get(`/tasks/${taskid}/comments`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')
                res.body.should.be.empty
                done()
            })
        })
    })  
})

describe("Todo Cloud API - Notification Tests", function() {
    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    // Variables used across tests
    let listid = null
    let taskid = null
    let notificationid1  = null
    let notificationid2  = null
    const now = Math.floor(Date.now() / 1000)

    before(function(done) {
        if (!UnitTests.Notifications) {
            done()
            return
        }
        async.waterfall([
            function(callback) {
                setUpAuthenticatedAccount(function(done) {
                    callback(null)
                })
            },
            function(callback) {
                // Set up a list that we can use to test tasks
                let newList = {
                    name: "Test List"
                }
                chai
                    .request(baseApiUrl)
                    .post("/lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newList)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("list")
                        res.body.list.should.have.property("listid")
                        res.body.should.have.property("settings")
                        listid = res.body.list.listid
                        callback(null)
                    })
            },
            function(callback) {
                let params = {
                    name: "Test Task",
                    listid: listid
                }
                chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(params)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("timestamp")
                        res.body.should.have.property("listid").eql(listid)
                        res.body.should.have.property("name").eql("Test Task")
                        taskid = res.body.taskid
                        callback(null)
                    })
            }
        ],
        function(err) {
            assert(!err, `An error occurred setting up for the notifications tests: ${err}`)
            done()
        })
    })

    after(function(done) {
        if (!UnitTests.Notifications) {
            done()
            return
        }
        deleteAccount(done)
    })

    describe('/notifications (POST) - Create a notification', () => {
        assuming(UnitTests.Notifications).it('Should succeed (200) - notification should be created', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/notifications`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ taskid: taskid, triggerdate: now, triggeroffset: 0 })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('notificationid')
                notificationid1 = res.body.notificationid
                res.body.should.have.property('taskid').eql(taskid)
                res.body.should.have.property('triggerdate').eql(now)
                res.body.should.have.property('triggeroffset').eql(0)
                done()
            })
        })

        assuming(UnitTests.Notifications).it('Should succeed (200) - notification should be created', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/notifications`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ taskid: taskid, triggerdate: 0, triggeroffset: 300 })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('notificationid')
                notificationid2 = res.body.notificationid
                res.body.should.have.property('taskid').eql(taskid)
                res.body.should.have.property('triggerdate').eql(0)
                res.body.should.have.property('triggeroffset').eql(300)
                done()
            })
        })
    })

    describe('/notifications (GET) - Get a notification', () => {
        assuming(UnitTests.Notifications).it('Should succeed (200) - should get a notification', (done) => {
            chai
            .request(baseApiUrl)
            .get(`/notifications/${notificationid1}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('notificationid')
                res.body.should.have.property('taskid').eql(taskid)
                res.body.should.have.property('triggerdate').eql(now)
                res.body.should.have.property('triggeroffset').eql(0)
                done()
            })
        })

        assuming(UnitTests.Notifications).it('Should succeed (200) - should get a notification', (done) => {
            chai
            .request(baseApiUrl)
            .get(`/notifications/${notificationid2}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('notificationid')
                res.body.should.have.property('taskid').eql(taskid)
                res.body.should.have.property('triggerdate').eql(0)
                res.body.should.have.property('triggeroffset').eql(300)
                done()
            })
        })
    })

    describe('/tasks/{taskid}/notifications (GET) - Get all notifications for a task', () => {
        assuming(UnitTests.Notifications).it('Should succeed (200)- Should get all notifications for a given task', (done) => {
            chai
            .request(baseApiUrl)
            .get(`/tasks/${taskid}/notifications`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')
                res.body.should.not.be.empty

                const obj1 = res.body[0]
                const obj2 = res.body[1]
                obj1.should.be.an('object')
                obj2.should.be.an('object')

                obj1.should.have.property('notificationid')
                obj1.should.have.property('taskid').eql(taskid)
                obj1.should.have.property('triggerdate').eql(now)
                obj1.should.have.property('triggeroffset').eql(0)
                
                obj2.should.have.property('notificationid')
                obj2.should.have.property('taskid').eql(taskid)
                obj2.should.have.property('triggerdate').eql(0)
                obj2.should.have.property('triggeroffset').eql(300)

                done()
            })
        })
    })

    describe('/notifications (UPDATE) - Update a notification', () => {
        const newNow = Math.floor((Date.now() + 60) / 1000)
        assuming(UnitTests.Notifications).it('Should succeed (200) - should update a notification', (done) => {
            chai
            .request(baseApiUrl)
            .put(`/notifications/${notificationid1}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ triggerdate: newNow })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('notificationid')
                res.body.should.have.property('triggerdate').eql(newNow)
                done()
            })
        })
    })

    describe('/notifications (DELETE) - Delete a notification', () => {
        assuming(UnitTests.Notifications).it('Should succeed (204) - should delete notification', (done) => {
            chai
            .request(baseApiUrl)
            .delete(`/notifications/${notificationid1}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(204)
                done()
            })
        })

        assuming(UnitTests.Notifications).it('Should succeed (204) - should delete notification', (done) => {
            chai
            .request(baseApiUrl)
            .delete(`/notifications/${notificationid2}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(204)
                done()
            })
        })
    })
})

describe("Todo Cloud API - Task Count Tests", function() {
    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)
    
    // Variables used across tests
    let inboxid = null
    let list1 = null
    let list2 = null

    let allSmartListId = null
    let smartList1 = null
    let smartList2 = null

    before(function(done) {
        if (!UnitTests.TaskCounts) {
            done()
            return
        }
        async.waterfall([
            function(callback) {
                setUpAuthenticatedAccount(function(done) {
                    callback(null)
                })
            },
            function(callback) {
                // Read ALL the smart lists and record the
                // listid of the ALL smart list.
                chai
                .request(baseApiUrl)
                .get(`/smart-lists`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    allSmartList = res.body.find((smartList) => {
                        return (smartList.icon_name && smartList.icon_name == "menu-everything")
                    })
                    if (allSmartList) { allSmartListId = allSmartList.listid }
                    callback(null)
                })
            },
            function(callback) {
                // Read the user lists and figure out which one is the inbox.
                chai
                .request(baseApiUrl)
                .get("/lists?includeDeleted=false")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    res.body.length.should.be.above(0)
                    const inbox = res.body.map(item => item.list).find((list) => {
                        return list.name == "Inbox"
                    })
                    assert(inbox, `Could not find an Inbox list.`)
                    inbox.should.have.property("listid")
                    inboxid = inbox.listid
                    callback(null)
                })
            },
            function(callback) {
                // Set up Test List 1
                let newList = {
                    name: "Test List 1"
                }
                chai
                    .request(baseApiUrl)
                    .post("/lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newList)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("list")
                        res.body.list.should.have.property("listid")
                        res.body.should.have.property("settings")
                        list1 = res.body.list.listid
                        callback(null)
                    })
            },
            function(callback) {
                // Set up Test List 2
                let newList = {
                    name: "Test List 2"
                }
                chai
                    .request(baseApiUrl)
                    .post("/lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newList)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("list")
                        res.body.list.should.have.property("listid")
                        res.body.should.have.property("settings")
                        list2 = res.body.list.listid
                        callback(null)
                    })
            },
            function(callback) {
                // Create Smart List 1
                let newSmartList = {
                    name: "Smart List 1",
                    json_filter: `{"filterGroups":[{"starred":true}]} `
                }
                chai
                .request(baseApiUrl)
                .post("/smart-lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newSmartList)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("listid")
                    res.body.should.have.property("timestamp")
                    res.body.should.have.property("name").eql(newSmartList.name)
                    smartList1 = res.body.listid
                    callback()
                })
            },
            function(callback) {
                // Create Smart List 2
                let newSmartList = {
                    name: "Smart List 1",
                    json_filter: `{"filterGroups":[{"priority":["high"]}]} `
                }
                chai
                .request(baseApiUrl)
                .post("/smart-lists")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(newSmartList)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("listid")
                    res.body.should.have.property("timestamp")
                    res.body.should.have.property("name").eql(newSmartList.name)
                    smartList2 = res.body.listid
                    callback()
                })
            },
            function(callback) {
                const tasks = [
                    {
                        listid: inboxid,
                        starred: true
                    },
                    {
                        listid: list1,
                        starred: true,
                        priority: Constants.TaskPriority.High
                    },
                    {
                        listid: list1,
                        priority: Constants.TaskPriority.High
                    },
                    {
                        listid: list2,
                        starred: true,
                        duedate: moment().subtract(2, 'day').unix()
                        
                    },
                    {
                        listid: inboxid,
                        starred: true,
                        completiondate: moment().subtract(2, 'day').unix()
                    },
                    {
                        listid: list2,
                        priority: Constants.TaskPriority.High,
                        completiondate: moment().subtract(4, 'month').unix()
                    }
                ]
                async.eachOf(tasks,
                function(taskParams, idx, eachCallback) {
                    taskParams.name = `Task ${idx + 1}`
                    chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(taskParams)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        res.body.should.have.property("timestamp")
                        res.body.should.have.property("listid").eql(taskParams.listid)
                        res.body.should.have.property("name").eql(taskParams.name)
                        eachCallback(null)
                    })
                },
                function(err) {
                    callback(err)
                })
            }
        ],
        function(err) {
            assert(!err, `An error occurred setting up for the task tests: ${err}`)
            done()
        })
    })

    after(function(done) {
        if (!UnitTests.TaskCounts) {
            done()
            return
        }
        deleteAccount(done)
    })

    describe('/tasks/count (GET) - Get task counts', () => {
        assuming(UnitTests.TaskCounts).it('Should succeed (200) - should return task counts for lists and smart lists', (done) => {
            chai
            .request(baseApiUrl)
            .get(`/tasks/count`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('lists')
                res.body.should.have.property('smart_lists')

                const lists = res.body.lists
                const smartLists = res.body.smart_lists

                assert(lists.length == 3, `Expected to receive 3 lists`)
                assert(smartLists.length == 6, `Expected to receive 6 smart lists`)

                const allSmartListInfo = smartLists.find((listInfo) => {
                    return listInfo.listid == allSmartListId
                })
                assert(allSmartListInfo.active == 4, `Expected 4 active tasks in the "Everything" smart list.`)
                assert(allSmartListInfo.completed == 2, `Expected 2 completed tasks in the "Everything" smart list.`)

                // Check starred tasks (with the starred smart list)
                const starredSmartListInfo = smartLists.find((listInfo) => {
                    return listInfo.listid == smartList1
                })
                assert(starredSmartListInfo.active == 3, `Expected 3 active starred tasks.`)
                assert(starredSmartListInfo.completed == 1, `Expected 1 completed starred task.`)

                // Check high priority tasks (with the high priority smart list)
                const highPriorirtySmartListInfo = smartLists.find((listInfo) => {
                    return listInfo.listid == smartList2
                })
                assert(highPriorirtySmartListInfo.active == 2, `Expected 3 active high priority tasks.`)
                assert(highPriorirtySmartListInfo.completed == 1, `Expected 1 completed high priority task.`)
                
                // Check inbox task counts
                const inboxInfo = lists.find((listInfo) => {
                    return listInfo.listid == inboxid
                })
                assert(inboxInfo.active == 1, `Expected 1 active task in the inbox.`)
                assert(inboxInfo.completed == 1, `Expected 1 completed task in the inbox.`)
                
                // Check List 1 task counts
                const list1Info = lists.find((listInfo) => {
                    return listInfo.listid == list1
                })
                assert(list1Info.active == 2, `Expected 2 active tasks in list 1 (${list1}).`)
                assert(list1Info.completed == 0, `Expected 0 completed tasks in list 1 (${list1}).`)
                
                // Check List 2 task counts
                const list2Info = lists.find((listInfo) => {
                    return listInfo.listid == list2
                })
                assert(list2Info.active == 1, `Expected 1 active task in list 1 (${list2}).`)
                assert(list2Info.completed == 1, `Expected 1 completed task in list 1 (${list2}).`)
                
                done()
            })
        })
    })

    describe('/tasks/count (GET) - Get task counts with a specified date', () => {
        assuming(UnitTests.TaskCounts).it('Should succeed (200) - should return task counts for lists and smart lists', (done) => {

            const selectedDateString = moment().subtract(2, 'day').format(`YYYY-MM-DD`)

            chai
            .request(baseApiUrl)
            .get(`/tasks/count?selected_dates=${selectedDateString}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('lists')
                res.body.should.have.property('smart_lists')

                const lists = res.body.lists
                const smartLists = res.body.smart_lists

                assert(lists.length == 3, `Expected to receive 3 lists`)
                assert(smartLists.length == 6, `Expected to receive 6 smart lists`)

                const allSmartListInfo = smartLists.find((listInfo) => {
                    return listInfo.listid == allSmartListId
                })
                assert(allSmartListInfo.active == 1, `Expected 1 active task in the "Everything" smart list.`)
                assert(allSmartListInfo.overdue == 1, `Expected 1 overdue task in the "Everything" smart list.`)

                // Check List 2 task counts
                const list2Info = lists.find((listInfo) => {
                    return listInfo.listid == list2
                })
                assert(list2Info.active == 1, `Expected 1 active task in list 1 (${list2}).`)
                assert(list2Info.overdue == 1, `Expected 1 overdue task in list 1 (${list2}).`)
                
                done()
            })
        })
    })

    describe('/tasks/count (GET) - Get task counts with two specified dates', () => {
        assuming(UnitTests.TaskCounts).it('Should succeed (200) - should return task counts for lists and smart lists', (done) => {

            const selectedDateString = moment().subtract(2, 'day').format(`YYYY-MM-DD`) + ',' + moment().add(2, 'day').format('YYYY-MM-DD')

            chai
            .request(baseApiUrl)
            .get(`/tasks/count?selected_dates=${selectedDateString}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('lists')
                res.body.should.have.property('smart_lists')

                const lists = res.body.lists
                const smartLists = res.body.smart_lists

                assert(lists.length == 3, `Expected to receive 3 lists`)
                assert(smartLists.length == 6, `Expected to receive 6 smart lists`)

                const allSmartListInfo = smartLists.find((listInfo) => {
                    return listInfo.listid == allSmartListId
                })
                assert(allSmartListInfo.active == 1, `Expected 1 active task in the "Everything" smart list.`)
                assert(allSmartListInfo.overdue == 1, `Expected 1 overdue task in the "Everything" smart list.`)

                // Check List 2 task counts
                const list2Info = lists.find((listInfo) => {
                    return listInfo.listid == list2
                })
                assert(list2Info.active == 1, `Expected 1 active task in list 1 (${list2}).`)
                assert(list2Info.overdue == 1, `Expected 1 overdue task in list 1 (${list2}).`)
                
                done()
            })
        })
    })

    describe('/tasks/count (GET) - Get task counts with specified dates that result in no tasks', () => {
        assuming(UnitTests.TaskCounts).it('Should succeed (200) - should return task counts for lists and smart lists', (done) => {

            const selectedDateString = moment().subtract(24, 'day').format(`YYYY-MM-DD`) + ',' + moment().add(47, 'day').format('YYYY-MM-DD')

            chai
            .request(baseApiUrl)
            .get(`/tasks/count?selected_dates=${selectedDateString}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('lists')
                res.body.should.have.property('smart_lists')

                const lists = res.body.lists
                const smartLists = res.body.smart_lists

                assert(lists.length == 3, `Expected to receive 3 lists`)
                assert(smartLists.length == 6, `Expected to receive 6 smart lists`)

                const allSmartListInfo = smartLists.find((listInfo) => {
                    return listInfo.listid == allSmartListId
                })
                assert(allSmartListInfo.active == 0, `Expected 1 active task in the "Everything" smart list.`)
                assert(allSmartListInfo.overdue == 0, `Expected 1 overdue task in the "Everything" smart list.`)
                
                done()
            })
        })
    })

    describe('/tasks/count (GET) - Get task counts based on a date range', () => {
        assuming(UnitTests.TaskCounts).it('Should succeed (200) - should return task counts for the Everything smart list', (done) => {
            const beginDate = moment().subtract(1, 'day').format(`YYYY-MM-DD`)
            const endDate = moment().add(1, 'month').format(`YYYY-MM-DD`)
            chai
            .request(baseApiUrl)
            .get(`/tasks/date_count?smart_listid=${allSmartListId}&begin_date=${beginDate}&end_date=${endDate}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('dates')

                const dates = res.body.dates

                // assert(lists.length == 3, `Expected to receive 3 lists`)
                // assert(smartLists.length == 6, `Expected to receive 6 smart lists`)

                // const allSmartListInfo = smartLists.find((listInfo) => {
                //     return listInfo.listid == allSmartListId
                // })
                // assert(allSmartListInfo.active == 4, `Expected 4 active tasks in the "Everything" smart list.`)
                // assert(allSmartListInfo.completed == 2, `Expected 2 completed tasks in the "Everything" smart list.`)

                // // Check starred tasks (with the starred smart list)
                // const starredSmartListInfo = smartLists.find((listInfo) => {
                //     return listInfo.listid == smartList1
                // })
                // assert(starredSmartListInfo.active == 3, `Expected 3 active starred tasks.`)
                // assert(starredSmartListInfo.completed == 1, `Expected 1 completed starred task.`)

                // // Check high priority tasks (with the high priority smart list)
                // const highPriorirtySmartListInfo = smartLists.find((listInfo) => {
                //     return listInfo.listid == smartList2
                // })
                // assert(highPriorirtySmartListInfo.active == 2, `Expected 3 active high priority tasks.`)
                // assert(highPriorirtySmartListInfo.completed == 1, `Expected 1 completed high priority task.`)
                
                // // Check inbox task counts
                // const inboxInfo = lists.find((listInfo) => {
                //     return listInfo.listid == inboxid
                // })
                // assert(inboxInfo.active == 1, `Expected 1 active task in the inbox.`)
                // assert(inboxInfo.completed == 1, `Expected 1 completed task in the inbox.`)
                
                // // Check List 1 task counts
                // const list1Info = lists.find((listInfo) => {
                //     return listInfo.listid == list1
                // })
                // assert(list1Info.active == 2, `Expected 2 active tasks in list 1 (${list1}).`)
                // assert(list1Info.completed == 0, `Expected 0 completed tasks in list 1 (${list1}).`)
                
                // // Check List 2 task counts
                // const list2Info = lists.find((listInfo) => {
                //     return listInfo.listid == list2
                // })
                // assert(list2Info.active == 1, `Expected 1 active task in list 1 (${list2}).`)
                // assert(list2Info.completed == 1, `Expected 1 completed task in list 1 (${list2}).`)
                
                done()
            })
        })
    })

    describe('/lists/{listid}/count (GET) - Get specific list task counts', () => {
        assuming(UnitTests.TaskCounts).it('Should succeed (200) - Should return task counts for a specific list', (done) => {
            chai
            .request(baseApiUrl)
            .get(`/lists/${list1}/count`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('count')
                res.body.should.have.property('overdue')
                res.body.count.should.equal(2)
                res.body.overdue.should.equal(0)
                done()
            })
        })
    })

    describe('/lists/{listid}/count (GET) - Get specific list task counts', () => {
        assuming(UnitTests.TaskCounts).it('Should succeed (200) - Should return task counts for a specific list', (done) => {
            chai
            .request(baseApiUrl)
            .get(`/lists/${list2}/count`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('count')
                res.body.should.have.property('overdue')
                res.body.count.should.equal(1)
                res.body.overdue.should.equal(1)
                done()
            })
        })
    })


})

describe("Todo Cloud API - Premium Subscription Purchase Tests", function() {
    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)
    
    // Variables used across tests
    let subscriptionID = null
    let stripeToken = null
    let last4 = "4242"
    let purchaseTimestamp = null

    before(function(done) {
        if (!UnitTests.Purchases) {
            done()
            return
        }
        async.waterfall([
            function(callback) {
                setUpAuthenticatedAccount(function(done) {
                    callback(null)
                })
            },
            function(callback) {
                // Communicate with Stripe to get a stripeToken that can be used
                // to purchase a monthly account
                stripe.tokens.create({
                    card: {
                        "number": "4242424242424242",
                        "exp_month": 12,
                        "exp_year": 2047,
                        "cvc": "474"
                    }
                }, function(err, token) {
                    assert(!err, `Had an error getting a stripe token: ${err}`)
                    stripeToken = token.id
                    callback(null)
                })
            },
            function(callback) {
                // Get the user's subscription record so we can use it in the test
                chai
                    .request(baseApiUrl)
                    .get(`/subscription`)
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.have.property("subscription")
                        subscriptionID = res.body.subscription.subscriptionid
                        assert(subscriptionID, `Not able to retrieve a subscription ID.`)
                        done()
                    })
            }
        ],
        function(err) {
            assert(!err, `An error occurred setting up for the purchase tests: ${err}`)
            done()
        })
    })

    after(function(done) {
        if (!UnitTests.Purchases) {
            done()
            return
        }
        deleteAccount(done)
    })

    describe('/subscription/purchase (POST) - Attempt to puchase a monthly subscription with a bogus Stripe token', () => {
        assuming(UnitTests.Purchases).it("Should fail", function(done) {
            let purchaseParams = {
                "subscription_id": subscriptionID,
                "subscription_type": "month",
                "total_charge": 1.99,
                "stripe_token": "bogus_token"
            }
            chai
                .request(baseApiUrl)
                .post(`/subscription/purchase`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(purchaseParams)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(500)
                    done()
                })
        })
    })

    describe('/subscription/purchase (POST) - Attempt to puchase a monthly subscription with last4 and no saved cc info', () => {
        assuming(UnitTests.Purchases).it("Should fail", function(done) {
            let purchaseParams = {
                "subscription_id": subscriptionID,
                "subscription_type": "month",
                "total_charge": 1.99,
                "last4": "1234"
            }
            chai
                .request(baseApiUrl)
                .post(`/subscription/purchase`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(purchaseParams)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(500)
                    done()
                })
        })
    })

    describe('/subscription/purchase (POST) - Purchase a monthly subscription', () => {
        assuming(UnitTests.Purchases).it("Should purchase a monthly account", function(done) {
            let purchaseParams = {
                "subscription_id": subscriptionID,
                "subscription_type": "month",
                "total_charge": 1.99,
                "stripe_token": stripeToken
            }
            chai
                .request(baseApiUrl)
                .post(`/subscription/purchase`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(purchaseParams)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    done()
                })
        })
    })

    describe('/subscription (GET) - Account should show payment system as Stripe', () => {
        assuming(UnitTests.Purchases).it("Should succeed", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/subscription`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("subscription")
                    subscriptionID = res.body.subscription.subscriptionid
                    assert(subscriptionID, `Not able to retrieve a subscription ID.`)
                    res.body.should.have.property("payment_system").eql("stripe")
                    done()
                })
            
        })
    })

    describe('/subscription/purchase (POST) - Purchase a yearly subscription', () => {
        assuming(UnitTests.Purchases).it("Should purchase a yearly account", function(done) {
            let purchaseParams = {
                "subscription_id": subscriptionID,
                "subscription_type": "year",
                "total_charge": 19.99,
                "last4": last4
            }
            chai
                .request(baseApiUrl)
                .post(`/subscription/purchase`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(purchaseParams)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    done()
                })
        })
    })

    describe('/subscription/purchase (POST) - Attempt to purchase another yearly subscription', () => {
        assuming(UnitTests.Purchases).it("Should fail with ineligible error", function(done) {
            let purchaseParams = {
                "subscription_id": subscriptionID,
                "subscription_type": "year",
                "total_charge": 19.99,
                "last4": last4
            }
            chai
                .request(baseApiUrl)
                .post(`/subscription/purchase`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(purchaseParams)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(403)
                    res.body.should.have.property("code").eql(Errors.subscriptionPurchaseNotEligible.errorType)
                    res.body.should.have.property("message")
                    done()
                })
        })
    })

    describe('/subscription/purchases (GET) - Read all purchases for a user', () => {
        assuming(UnitTests.Purchases).it("Should succeed", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/subscription/purchases`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    res.body.length.should.be.above(0)

                    // Save off the purchase timestamp of the first purchase for use in a later test
                    purchaseTimestamp = res.body[0].timestamp
                    done()
                })
        })
    })    

    describe('/subscription/purchases/{timestamp}/resend_receipt (GET) - Resend a purchase receipt', () => {
        assuming(UnitTests.Purchases).it("Should succeed", function(done) {
            chai
                .request(baseApiUrl)
                .post(`/subscription/purchases/${purchaseTimestamp}/resend_receipt`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    done()
                })
        })
    })

    describe('/subscription/downgrade (POST) - Downgrade to free account', () => {
        assuming(UnitTests.Purchases).it("Should succeed", function(done) {
            chai
                .request(baseApiUrl)
                .post(`/subscription/downgrade`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    done()
                })
        })
    })

    describe('/subscription (GET) - Make sure the account was downgraded properly', () => {
        assuming(UnitTests.Purchases).it("Should succeed", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/subscription`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.have.property("subscription")
                    subscriptionID = res.body.subscription.subscriptionid
                    assert(subscriptionID, `Not able to retrieve a subscription ID.`)
                    res.body.should.have.property("payment_system").eql("unknown")
                    done()
                })
            
        })
    })

})

describe("Todo Cloud API - Share List Tests", function() {
    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)
    
    // Variables used across tests
    let inboxid = null
    let list1 = null
    let list2 = null
    let invitationid = null
    let invitationid2 = null
    let invitationid3 = null

    let secondAccountId = null
    let secondAccountToken = null
    const secondAccountEmail = 'unittester+dev01@appigo.com'

    before(function(done) {
        if (!UnitTests.Sharing) {
            done()
            return
        }
        async.waterfall([
            function(callback) {
                const accountParams = {
                    "username": secondAccountEmail,
                    "password": 'tester',
                    "first_name": 'Unit01',
                    "last_name": 'Tester01'
                }
                createAuthenticatedAccount(accountParams, (userid, token) => {
                    secondAccountId = userid
                    secondAccountToken = token
                    callback(null)
                })

            },
            function(callback) {
                setUpAuthenticatedAccount(function(done) {
                    callback(null)
                })
            },
            function(callback) {
                // Read the user lists and figure out which one is the inbox.
                chai
                .request(baseApiUrl)
                .get("/lists?includeDeleted=false")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("array")
                    res.body.length.should.be.above(0)
                    const inbox = res.body.map(item => item.list).find((list) => {
                        return list.name == "Inbox"
                    })
                    assert(inbox, `Could not find an Inbox list.`)
                    inbox.should.have.property("listid")
                    inboxid = inbox.listid
                    callback(null)
                })
            },
            function(callback) {
                // Set up Test List 1
                let newList = {
                    name: "Test List 1"
                }
                chai
                    .request(baseApiUrl)
                    .post("/lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newList)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("list")
                        res.body.list.should.have.property("listid")
                        res.body.should.have.property("settings")
                        list1 = res.body.list.listid
                        callback(null)
                    })
            },
            function(callback) {
                // Set up Test List 2
                let newList = {
                    name: "Test List 2"
                }
                chai
                    .request(baseApiUrl)
                    .post("/lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newList)
                    .end(function(err, res) {
                        if (err) {
                            callback(err)
                            return
                        }
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("list")
                        res.body.list.should.have.property("listid")
                        res.body.should.have.property("settings")
                        list2 = res.body.list.listid
                        callback(null)
                    })
            }
        ],
        function(err) {
            assert(!err, `An error occurred setting up for the invite tests: ${err}`)
            done()
        })
    })

    after(function(done) {
        if (!UnitTests.Sharing) {
            done()
            return
        }

        async.waterfall([
            function(callback) {
                // The second account must be deleted first, or else the other account will be prevented from being deleted
                // when it comes time to delete the other account's lists.
                deleteAuthenticatedAccount({ userid : secondAccountId, token : secondAccountToken }, callback)
            },
            function(callback) {
                deleteAccount(callback)
            }
        ],
        function(err) {
            assert(!err, `An error occurred tearing down the invite tests: ${err}`)
            done()
        })
    })

    describe('/invitations (POST) - Create a new list invitation', function() {
        assuming(UnitTests.Sharing).it('Should succeed (200) - should create and return a new list invitation, and send an invitation email', (done) => {
            const invitationRequest = {
                userid : testUserid,
                listid : list1,
                membership_type : Constants.ListMembershipType.Member,
                email : secondAccountEmail
            }
            chai
            .request(baseApiUrl)
            .post(`/invitations`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(invitationRequest)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('userid')
                res.body.should.have.property('listid')
                res.body.should.have.property('email')
                res.body.should.have.property('membership_type')
                res.body.should.have.property('invitationid')
                res.body.should.have.property('invited_userid')
                res.body.invitationid.should.be.a('string')
                res.body.invitationid.should.not.be.empty

                res.body.userid.should.equal(testUserid)
                res.body.listid.should.equal(list1)
                res.body.email.should.equal(secondAccountEmail)
                res.body.invited_userid.should.equal(secondAccountId)
                assert.equal(res.body.membership_type, Constants.ListMembershipType.Member, `Membership type: ${res.body.membership_type} should equal ${1}`)

                invitationid = res.body.invitationid

                done()
            })
        })
    })

    describe('/invitations/:invitationid/resend (PUT) - Resend a list invitation email', function() {
        assuming(UnitTests.Sharing).it('Should succeed (200) - Should update invitation timestamp and resend the invitation email', (done) => {
            chai
            .request(baseApiUrl)
            .put(`/invitations/${invitationid}/resend`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({})
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('success')
                res.body.success.should.equal(true)
                done()
            })
        })
    })

    describe('/invitations (POST) - Create another new list invitation', function() {
        assuming(UnitTests.Sharing).it('Should succeed (200) - should create and return a new list invitation, and send an invitation email', (done) => {
            const invitationRequest = {
                userid : testUserid,
                listid : list2,
                membership_type : Constants.ListMembershipType.Member,
                email : 'unittester+dev02@appigo.com'
            }
            chai
            .request(baseApiUrl)
            .post(`/invitations`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(invitationRequest)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('userid')
                res.body.should.have.property('listid')
                res.body.should.have.property('email')
                res.body.should.have.property('membership_type')
                res.body.should.have.property('invitationid')
                res.body.should.not.have.property('invited_userid')
                res.body.invitationid.should.be.a('string')
                res.body.invitationid.should.not.be.empty

                res.body.userid.should.equal(testUserid)
                res.body.listid.should.equal(list2)
                res.body.email.should.equal('unittester+dev02@appigo.com')
                assert.equal(res.body.membership_type, Constants.ListMembershipType.Member, `Membership type: ${res.body.membership_type} should equal ${1}`)

                invitationid2 = res.body.invitationid

                done()
            })
        })
    })

    describe('/invitations (POST) - Create another new list invitation', function() {
        assuming(UnitTests.Sharing).it('Should succeed (200) - should create and return a new list invitation, and send an invitation email', (done) => {
            const invitationRequest = {
                userid : testUserid,
                listid : list2,
                membership_type : Constants.ListMembershipType.Member,
                email : secondAccountEmail
            }
            chai
            .request(baseApiUrl)
            .post(`/invitations`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send(invitationRequest)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('userid')
                res.body.should.have.property('listid')
                res.body.should.have.property('email')
                res.body.should.have.property('membership_type')
                res.body.should.have.property('invitationid')
                res.body.should.have.property('invited_userid')
                res.body.invitationid.should.be.a('string')
                res.body.invitationid.should.not.be.empty

                res.body.userid.should.equal(testUserid)
                res.body.listid.should.equal(list2)
                res.body.email.should.equal(secondAccountEmail)
                res.body.invited_userid.should.equal(secondAccountId)
                assert.equal(res.body.membership_type, Constants.ListMembershipType.Member, `Membership type: ${res.body.membership_type} should equal ${1}`)

                invitationid3 = res.body.invitationid

                done()
            })
        })
    })

    describe('/invitations (GET) - Get all invitations from a user.', function() {
        assuming(UnitTests.Sharing).it('Should succeed (200) - Should get all the invitations from this user.', (done) => {
            chai
            .request(baseApiUrl)
            .get(`/invitations`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')
                assert.isNotNull(res.body.find(inv => inv.invitationid == invitationid2), `Invitation2 was not returned.`)
                assert.isNotNull(res.body.find(inv => inv.invitationid == invitationid3), `Invitation3 was not returned.`)

                done()
            })
        })
    })

    describe('invitation/:invitationid (GET) - Get a specific invitation and related info', function() {
        assuming(UnitTests.Sharing).it(`Should succeed (200) - Returns the memberships for a list.`, (done) => {
            // This call should work even without authorization. Don't provide an authorization token.
            chai
            .request(baseApiUrl)
            .get(`/invitations/${invitationid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('object')
                res.body.should.have.property('invitation')
                res.body.should.have.property('account')
                res.body.should.have.property('list')

                res.body.invitation.should.have.property('invitationid')
                res.body.invitation.should.have.property('userid')
                res.body.invitation.should.have.property('listid')
                res.body.invitation.should.have.property('email')

                res.body.invitation.invitationid.should.equal(invitationid)
                res.body.invitation.userid.should.equal(testUserid)
                res.body.invitation.listid.should.equal(list1)
                res.body.invitation.email.should.equal(secondAccountEmail)

                res.body.account.should.have.property('first_name')
                res.body.account.should.have.property('last_name')
                res.body.account.should.have.property('username')

                res.body.account.first_name.should.equal(firstName)
                res.body.account.last_name.should.equal(lastName)
                res.body.account.username.should.equal(username)

                res.body.list.should.have.property('listid')
                res.body.list.should.have.property('name')

                res.body.list.listid.should.equal(list1)
                res.body.list.name.should.equal('Test List 1')

                done()
            })
        })
    })

    describe('/lists/:listid/invitations (GET) - Get all invitations for a list from a user.', function() {
        assuming(UnitTests.Sharing).it('Should succeed (200) - Should get all the invitations for a specific list from this user.', (done) => {
            chai
            .request(baseApiUrl)
            .get(`/lists/${list2}/invitations`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')
                assert.isNotNull(res.body.find(inv => inv.invitationid == invitationid2), `Invitation2 was not returned.`)
                assert.isNotNull(res.body.find(inv => inv.invitationid == invitationid3), `Invitation3 was not returned.`)

                done()
            })
        })
    })

    describe('/invitations/:invitationid (POST) - Accept a list invitation', function() {
        assuming(UnitTests.Sharing).it('Should succeed (200) - Authenticated user should be added to list membership.', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/invitations/${invitationid}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${secondAccountToken}`)
            .send({})
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('success')
                res.body.success.should.equal(true)

                done()
            })
        })
    })

    describe(`/list-member/:memberid/role (PUT) - Change a list member's role`, function() {
        assuming(UnitTests.Sharing).it(`Should succeed (200) - A list member's role should change.`, (done) => {
            chai
            .request(baseApiUrl)
            .put(`/list-member/${secondAccountId}/role`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ listid : list1, role : Constants.ListMembershipType.Owner })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('success')
                res.body.success.should.equal(true)

                done()
            })
        })
    })

    describe(`/list-member/:memberid/role (PUT) - Change a list member's role`, function() {
        assuming(UnitTests.Sharing).it(`Should succeed (200) - A list member's role should change.`, (done) => {
            chai
            .request(baseApiUrl)
            .put(`/list-member/${secondAccountId}/role`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${secondAccountToken}`)
            .send({ listid : list1, role : Constants.ListMembershipType.Viewer })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('success')
                res.body.success.should.equal(true)

                done()
            })
        })
    })

    describe(`/list-member/:memberid/role (PUT) - Change a list member's role`, function() {
        assuming(UnitTests.Sharing).it(`Should fail (403) - A list member's role should not change.`, (done) => {
            chai
            .request(baseApiUrl)
            .put(`/list-member/${testUserid}/role`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ listid : list1, role : Constants.ListMembershipType.Member })
            .end((err, res) => {
                debug(res)
                res.should.have.status(403)
                res.body.should.be.a('object')

                done()
            })
        })
    })

    describe(`/list/:listid/members (GET) - Get the members for a list`, function() {
        assuming(UnitTests.Sharing).it(`Should succeed (200) - Returns the memberships for a list.`, (done) => {
            chai
            .request(baseApiUrl)
            .get(`/list/${list1}/members`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')

                const testerMember = res.body.find((member) => member.membership.userid == testUserid)
                const secondMember = res.body.find((member) => member.membership.userid == secondAccountId)

                testerMember.should.be.an('object')
                testerMember.should.have.property('membership')
                testerMember.should.have.property('account')
                testerMember.membership.should.have.property('membership_type')
                testerMember.membership.should.have.property('listid')
                testerMember.account.should.have.property('first_name')
                testerMember.account.should.have.property('last_name')
                testerMember.account.should.have.property('username')

                testerMember.membership.membership_type.should.equal(Constants.ListMembershipType.Owner)
                testerMember.membership.listid.should.equal(list1)
                testerMember.membership.userid.should.equal(testUserid)

                secondMember.should.be.an('object')
                secondMember.should.have.property('membership')
                secondMember.should.have.property('account')
                secondMember.membership.should.have.property('membership_type')
                secondMember.membership.should.have.property('listid')
                secondMember.account.should.have.property('first_name')
                secondMember.account.should.have.property('last_name')
                secondMember.account.should.have.property('username')

                secondMember.membership.membership_type.should.equal(Constants.ListMembershipType.Viewer)
                secondMember.membership.listid.should.equal(list1)
                secondMember.membership.userid.should.equal(secondAccountId)

                done()
            })
        })
    })

    describe('/list-member (GET) - Get user info on all users that share lists with the current user.', function() {
        assuming(UnitTests.Sharing).it(`Should succeed (200) - Returns user info for lists shared with the user.`, (done) => {
            chai
            .request(baseApiUrl)
            .get(`/list-member`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${secondAccountToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.an('array')

                const testerUser = res.body.find((user) => user.userid == testUserid)
                const secondUser = res.body.find((user) => user.userid == secondAccountId)

                testerUser.should.be.an('object')
                testerUser.should.have.property('first_name')
                testerUser.should.have.property('last_name')
                testerUser.should.have.property('username')

                testerUser.first_name.should.equal(firstName)
                testerUser.last_name.should.equal(lastName)
                testerUser.username.should.equal(username)

                secondUser.should.be.an('object')
                secondUser.should.have.property('first_name')
                secondUser.should.have.property('last_name')
                secondUser.should.have.property('username')

                secondUser.first_name.should.equal('Unit01')
                secondUser.last_name.should.equal('Tester01')
                secondUser.username.should.equal(secondAccountEmail)

                done()
            })
        })
    }) 
  
    describe(`/invitations/:invitationid (PUT) - Update an invitation's role.`, function() {
        assuming(UnitTests.Sharing).it('Should succeed (200) - Should update the list invitation\'s role', (done) => {
            chai
            .request(baseApiUrl)
            .put(`/invitations/${invitationid2}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .send({ membership_type : Constants.ListMembershipType.Owner })
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                res.body.should.be.a('object')
                res.body.should.have.property('membership_type')
                res.body.membership_type.should.equal(Constants.ListMembershipType.Owner)
                done()
            })
        })
    })

    describe('/invitations/:invitationid (DELETE) - Invitee deletes a list invitation', function() {
        assuming(UnitTests.Sharing).it('Should succeed (204) - Should delete the list invitation', (done) => {
            chai
            .request(baseApiUrl)
            .del(`/invitations/${invitationid3}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${secondAccountToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(204)

                done()
            })
        })
    })

    describe('/invitations/:invitationid (DELETE) - 3rd party tries to delete a list invitation', function() {
        assuming(UnitTests.Sharing).it('Should fail (403) - Should not be authroized to delete the list invitation', (done) => {
            chai
            .request(baseApiUrl)
            .del(`/invitations/${invitationid2}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${secondAccountToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(403)

                done()
            })
        })
    })

    describe('/invitations/:invitationid (DELETE) - Invitation creator deletes a list invitation', function() {
        assuming(UnitTests.Sharing).it('Should succeed (204) - Should delete the list invitation', (done) => {
            chai
            .request(baseApiUrl)
            .del(`/invitations/${invitationid2}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(204)

                done()
            })
        })
    })

    describe('/list-member/:memberid/remove/:listid (DELETE) - Remove list member from list.', function() {
        assuming(UnitTests.Sharing).it('Should succeed (204) - Should remove member from list.', (done) => {
            chai
            .request(baseApiUrl)
            .del(`/list-member/${secondAccountId}/remove/${list1}`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(204)

                done()
            })
        })
    })
})

describe("Todo Cloud API - Sync Tests", function() {
    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    // Variables used across tests
    let listid = null

    const taskids = []
    let checklistid = null

    before(function(done) {
        if (!UnitTests.Sync) {
            done()
            return
        }
        async.waterfall([
            function(callback) {
                setUpAuthenticatedAccount(function(done) {
                    callback(null)
                })
            },
            function(callback) {
                // Add a test list that should be pushed to the server
                let newList = {
                    name: "Test List"
                }
                chai
                    .request(baseApiUrl)
                    .post("/lists")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newList)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("list")
                        res.body.list.should.have.property("listid")
                        res.body.should.have.property("settings")
                        listid = res.body.list.listid
                        callback()
                    })
            },
            function(callback) {
                // Add 10 tasks that will be synchronized to the server
                subtaskids = []
                async.times(10, function(index, next) {
                    let newTask = {
                        name: `Task ${index}`,
                        listid: listid
                    }
                    chai
                        .request(baseApiUrl)
                        .post("/tasks")
                        .set("content-type", "application/json")
                        .set("x-api-key", todoCloudAPIKey)
                        .set("Authorization", `Bearer ${jwtToken}`)
                        .send(newTask)
                        .end(function(err, res) {
                            debug(res)
                            res.should.have.status(200)
                            res.body.should.be.a("object")
                            res.body.should.have.property("taskid")
                            let taskid = res.body.taskid
                            taskids.push(taskid)
                            next(null)
                        })
                },
                function(err) {
                    callback(err)
                })
            },
            function(callback) {
                // Add a checklist
                const newTask = {
                    name: `Sample Checklist`,
                    listid: listid,
                    task_type: Constants.TaskType.Checklist
                }
                chai
                    .request(baseApiUrl)
                    .post("/tasks")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTask)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskid")
                        let taskid = res.body.taskid
                        checklistid = taskid
                        callback(null)
                    })
            },
            function(callback) {
                // Add 10 taskitos
                const taskitoids = []
                async.times(10, function(index, next) {
                    let newTaskito = {
                        name: `Taskito ${index}`,
                        parentid: checklistid
                    }
                    chai
                    .request(baseApiUrl)
                    .post("/checklist/items")
                    .set("content-type", "application/json")
                    .set("x-api-key", todoCloudAPIKey)
                    .set("Authorization", `Bearer ${jwtToken}`)
                    .send(newTaskito)
                    .end(function(err, res) {
                        debug(res)
                        res.should.have.status(200)
                        res.body.should.be.a("object")
                        res.body.should.have.property("taskitoid")
                        res.body.should.have.property("parentid").eql(newTaskito.parentid)
                        res.body.should.have.property("name").eql(newTaskito.name)
                        let taskitoid = res.body.taskitoid
                        taskitoids.push(taskitoid)
                        next(null)
                    })
                },
                function(err) {
                    callback(err)
                })
            }
        ],
        function(err) {
            assert(!err, `An error occurred setting up for the sync tests: ${err}`)
            done()
        })
    })

    after(function(done) {
        if (!UnitTests.Sync) {
            done()
            return
        }
        deleteAccount(done)
    })

    describe('/sync (POST)', () => {
        assuming(UnitTests.Sync).it('Should succeed (200) - Should perform a sync', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/sync`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                done()
            })
        })
    })

    describe('Modify a list', () => {
        assuming(UnitTests.Sync).it('Should succeed (200)', (done) => {
            let params = {
                name: "Changed List Name",
                settings: {
                    icon_name: "4001-guitar",
                    color: "244, 67, 54",
                    sort_order: 47,
                    sort_type: 2,
                    default_due_date: 2
                }
            }
            chai
                .request(baseApiUrl)
                .put(`/lists/${listid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .send(params)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.a("object")
                    res.body.should.have.property("list")
                    res.body.list.should.have.property("listid")
                    res.body.list.should.have.property("name")
                    res.body.list.name.should.be.eql("Changed List Name")
                    res.body.should.have.property("settings")
                    res.body.settings.should.have.property("icon_name")
                    res.body.settings.icon_name.should.be.eql("4001-guitar")
                    res.body.settings.should.have.property("color")
                    res.body.settings.color.should.be.eql("244, 67, 54")
                    res.body.settings.should.have.property("sort_order")
                    res.body.settings.sort_order.should.be.eql(47)
                    res.body.settings.should.have.property("sort_type")
                    res.body.settings.sort_type.should.be.eql(2)
                    res.body.settings.should.have.property("default_due_date")
                    res.body.settings.default_due_date.should.be.eql(2)
                    done()
                })
        })
    })

    describe('/sync (POST) after modifying a list', () => {
        assuming(UnitTests.Sync).it('Should succeed (200) - should sync an updated list', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/sync`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                done()
            })
        })
    })

    describe("Delete the test list", function() {
        assuming(UnitTests.Sync).it("Should succeed (204)", function(done) {
            chai
                .request(baseApiUrl)
                .delete(`/lists/${listid}`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .set("Authorization", `Bearer ${jwtToken}`)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    listid = null
                    done()
                })
        })
    })

    describe('/sync (POST) after deleting a list', () => {
        assuming(UnitTests.Sync).it('Should succeed (200) - should sync a deleted list', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/sync`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                done()
            })
        })
    })

    describe('/sync (POST) after no changes', () => {
        assuming(UnitTests.Sync).it('Should succeed (200) - should sync without really doing anything', (done) => {
            chai
            .request(baseApiUrl)
            .post(`/sync`)
            .set("content-type", "application/json")
            .set("x-api-key", todoCloudAPIKey)
            .set("Authorization", `Bearer ${jwtToken}`)
            .end((err, res) => {
                debug(res)
                res.should.have.status(200)
                done()
            })
        })
    })
})

describe("Todo Cloud API - App Update Tests", function() {
    // Disable timeouts so that the API is given as much time as needed.
    // Amazon Lambda has its own timeouts, so no function should run
    // longer than those defined timeouts anyway.
    this.timeout(0)

    // In one of the tests, track the latest version so we can use it to
    // try out specifying the latest version later.
    let latestServerVersion = null

    describe('/app/latest (GET) with nothing specified in the query string', () => {
        assuming(UnitTests.AppUpdate).it("Should return an error because parameters are missing", function(done) {
            chai
                .request(baseApiUrl)
                .get("/app/latest")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    done()
                })
        })
    })

    describe('/app/latest (GET) with version of 0', () => {
        assuming(UnitTests.AppUpdate).it("Should return error because version is invalid format", function(done) {
            chai
                .request(baseApiUrl)
                .get("/app/latest?version=0&dist=test&platform=macos&arch=x64")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(400)
                    done()
                })
        })
    })

    describe('/app/latest (GET) with version of 0.0.0', () => {
        assuming(UnitTests.AppUpdate).it("Should return _something_ back from the server", function(done) {
            chai
                .request(baseApiUrl)
                .get("/app/latest?version=0.0.0&dist=test&platform=macos&arch=x64")
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(200)
                    res.body.should.be.an('object')
                    res.body.should.have.property('url')
                    res.body.should.have.property('name')
                    res.body.should.have.property('notes')
                    res.body.should.have.property('pub_date')

                    // Save off the latest version so we can use it in the next test
                    latestServerVersion = res.body.notes
                    done()
                })    
        })        
    })    

    describe(`/app/latest (GET) with the latest version available`, () => {
        assuming(UnitTests.AppUpdate).it("Should return 204, denoting that there's no new update.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/app/latest?version=${latestServerVersion}&dist=test&platform=macos&arch=x64`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    done()
                })    
        })        
    })    

    describe(`/app/latest (GET) with bogus platform`, () => {
        assuming(UnitTests.AppUpdate).it("Should return 204, denoting that there's no new update.", function(done) {
            chai
                .request(baseApiUrl)
                .get(`/app/latest?version=0.0.0&dist=test&platform=bogus&arch=x64`)
                .set("content-type", "application/json")
                .set("x-api-key", todoCloudAPIKey)
                .end(function(err, res) {
                    debug(res)
                    res.should.have.status(204)
                    done()
                })    
        })        
    })    

})

// server.js

// BASE SETUP
// =============================================================================

// call the packages we need
'use strict'

var express    = require('express')        // call express
var app        = express()                 // define our app using express
var bodyParser = require('body-parser')

var jwt			= require('./functions/common/jwt')
var errors		= require('./functions/common/errors')
const db		= require('./functions/common/tc-database')

// Socket.IO is used to provide task synchronization information to the
// desktop versions of the Todo app.
const http		= require('http').Server(app)
const io		= require('socket.io')(http)

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

// In development, we are simulating the AWS environment. We can spoof
// environment variables by setting process.env.VARIABLE_NAME = XYZ. We're doing
// that here to specify information needed to connect to the database.

logger.info(`Express.js starting up!`)

process.env.DB_HOST	= 'localhost'
process.env.DB_USERNAME = 'root'
process.env.DB_PASSWORD = ''
process.env.DB_NAME	= 'todoonline_db'

// TO-DO: These THREE environment variables will need to change and
// be specified on-the-fly or at build time to run in the local
// Express.js service (in Electron for Windows & Mac).
// process.env.DB_TYPE = 'sqlite'
// process.env.TC_API_URL = 'https://api.todo-cloud.com/test-v1'
// process.env.SYNC_API_URL = 'https://plano.todo-cloud.com/sync2/'

// This specifies which Amazon S3 bucket will be used for user profile images
process.env.PROFILE_IMAGES_S3_BUCKET = 'dev.todopro.com'

// When running in development mode, we need to use the TEST Stripe keys
process.env.STRIPE_SECRET_KEY = 'DUtytkg84Q5C0KhnhDgxuyqwa5NyVO64'
process.env.STRIPE_PUBLIC_KEY = 'pk_PAFUEjoj7cCw2Bb7w5ZP3i4QpHjWI'

// When running inside Express.js, any db connection pools should not be
// cleaned up automatically. Setting this prevents that (and indicates that
// we are running inside Express).
process.env.DB_PRESERVE_POOL = true

// Development session length is 1 day
process.env.SESSION_TIMEOUT = 86400

process.env.WEBAPP_BASE_URL = 'http://localhost:4200/#'

// Required for development
process.env.JWT_TOKEN_SECRET = 'development-jwt-secret'

// configure app to use bodyParser()
// this will let us get the data from a POST
app.use(bodyParser.urlencoded({ extended: true }))
app.use(bodyParser.json())
app.use(function(req, res, next) {
    res.header('Access-Control-Allow-Origin', "*")
    res.header('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE')
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization, X-Api-Key')
    next()
})

app.disable('etag')

var port = process.env.PORT || 8080        // set our port

// ROUTES FOR OUR API
// =============================================================================
var router = express.Router()              // get an instance of the express Router

// test route to make sure everything is working (accessed at GET http://localhost:8080/api)
router.get('/', function(req, res) {
    res.json({ message: 'hooray! welcome to our api!' })
});

// more routes for our API will happen here

var Authenticate = require('./functions/authenticate')

router.route('/*')
    .options(function(req, res, next) {
		res.json()
	})

router.route('/app/latest')
	.get((req, res, next) => {
		req.body.version = req.query.version
		req.body.dist = req.query.dist
		req.body.platform = req.query.platform
		req.body.arch = req.query.arch
		Authenticate.checkForUpdates(req.body, null, (err, responseData) => {
			if (err) {
				try {
					var errObj = JSON.parse(err)
					if (errObj.httpStatus == 204) {
						res.status(204)
						res.json({})
					} else {
						next(err)
					}
				} catch (error) {
					next(err)
				}
			} else {
				res.json(responseData)
			}
		})
	})

router.route('/authenticate')
	.post(function(req, res, next) {
		Authenticate.authenticate(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

router.route('/local-clear')
	.delete(function(req, res, next) {
		Authenticate.removeLocalData(req.body, null, function(err, response) {
			err ? next(err) : res.json(response)
		})
	})

// This is the createAccount() call and is NOT protected by JWT authentication
router.route('/account')
	.post(function(req, res, next) {
		Authenticate.createAccount(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

var Account = require('./functions/account')

router.route('/account/email/verify')
	.put(function(req, res, next) {
		Account.verifyEmail(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

// This is the resetPassword() call and does NOT need protection by JWT authentication
router.route('/account/password/reset')
	.post(function(req, res, next) {
		Account.requestResetPassword(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})
	.put(function(req, res, next) {
		Account.resetPassword(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

const Invitation = require('./functions/invitations')

router.route('/invitations/:invitationid')
	.get((req, res, next) => {
		req.body.invitationid = req.params.invitationid
		Invitation.getInvitation(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})

// ALL routes below this line require authorization with a JWT token

// Here's the special function that performs the authorization (decodes
// the JWT).
router.use(function(req, res, next) {
	// Get the JWT from the "Authorization: Bearer <token>" header,
	// decode it, get the userid from the token, add userid to req.body,
	// and pass the request on to the router. If JWT verification fails,
	// return 403 (Unauthorized).
	var authHeader = req.get('Authorization')
	if (authHeader && authHeader.length > 0) {
		var parts = authHeader.split(' ')
		if (parts.length == 2) {
			var token = parts[1]
			var userid = jwt.userIDFromToken(token)
			if (userid) {
				// Everything looks good. Pass the userid as a
				// body variable.
				req.body.userid = userid
				next()
				return
			} else {
				res.status(403)
				res.json(JSON.stringify({code:'Unauthorized', message:'JWT token is not valid.'}))
				return
			}
		}
	}
	res.status(401) // Unauthorized
	res.json(JSON.stringify({code:'Unauthorized', message:'JWT token not present or validation failed.'}))
})

router.route('/authenticate/refresh')
	.get(function(req, res, next) {
		Authenticate.refreshAuthentication(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

router.route('/authenticate/impersonate')
	.get(function(req, res, next) {
		req.body.customerUsername = req.query.customerUsername
		req.body.reason = req.query.reason

		Authenticate.impersonate(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

router.route('/account')
	.get(function(req, res, next) {
		if (req.query && req.query.type) {
			// Look for ?type=extended in the query string but pass it along as
			// part of the body element so that this will also work in Lambda.
			req.body.type = req.query.type
		}
		Account.getAccountInfo(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})
	.put(function(req, res, next) {
		Account.updateAccount(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})


router.route('/account/:userid')
	.delete(function(req, res, next) {
		req.body.userid = req.params.userid
		Account.deleteAccount(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})	

router.route('/account/email/verify/resend')
	.post(function(req, res, next) {
		Account.resendVerificationEmail(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

router.route('/account/profile-image/upload-urls')
	.get(function(req, res, next) {
        req.body.fileType = req.query.fileType

		Account.getProfileImageUploadURLs(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

router.route('/account/profile-image/save')
	.post(function(req, res, next) {
		Account.saveUploadedProfileImages(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

router.route('/account/password/update')
	.put(function(req, res, next) {
		Account.updatePassword(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})

var UserSettings = require('./functions/user-settings')

router.route('/user-settings')
	.get(function(req, res, next) {
		UserSettings.getUserSettings(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})
	.put(function(req, res, next) {
		UserSettings.updateUserSettings(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

var Subscription = require('./functions/subscription')

router.route('/subscription')
	.get(function(req, res, next) {
		req.body.include_cc_info = (req.query.include_cc_info != null) ? req.query.include_cc_info : false
		
		Subscription.getSubscription(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

router.route('/subscription/downgrade')
	.post(function(req, res, next) {
		Subscription.downgradeSubscription(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

router.route('/subscription/purchase')
	.post(function(req, res, next) {
		Subscription.purchaseSubscription(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

router.route('/subscription/purchases')
	.get(function(req, res, next) {
		Subscription.getPaymentHistory(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

router.route('/subscription/purchases/:timestamp/resend_receipt')
	.post(function(req, res, next) {
		req.body.timestamp = req.params.timestamp
		Subscription.resendReceipt(req.body, null, function(err, responseData) {
			if (!err) {
				res.json(responseData)
			} else {
				next(err)
			}
		})
	})

var Lists = require('./functions/lists')

router.route('/lists')
    .get(function(req, res, next) {
        req.body.includeDeleted = (req.query.includeDeleted != null)  ? req.query.includeDeleted  : false
        req.body.includeFiltered = (req.query.includeFiltered != null) ? req.query.includeFiltered : false

        Lists.getLists(req.body, null, (err, responseData) => {
            err == null ? res.json(responseData) : next(err)
        })
    })
	.post(function(req, res, next){
		Lists.createList(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
router.route('/lists/:listid')
	.get(function(req, res, next) {
		req.body.listid = req.params.listid
		Lists.getList(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.put(function(req, res, next) {
		req.body.listid = req.params.listid
		Lists.updateList(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.delete(function(req, res, next) {
		req.body.listid = req.params.listid
		Lists.deleteList(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})

router.route('/lists/:listid/count')
	.get(function(req, res, next) {
		req.body.listid = req.params.listid
		Lists.taskCountForList(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

const SmartLists = require('./functions/smart-lists')

router.route('/smart-lists')
	.get(function(req, res, next) {
		SmartLists.getSmartLists(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		}) 
	})
	.post(function(req, res, next) {
		SmartLists.createSmartList(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
router.route('/smart-lists/:listid')
	.get(function(req, res, next) {
		req.body.listid = req.params.listid
		SmartLists.getSmartList(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.put(function(req, res, next) {
		req.body.listid = req.params.listid
		SmartLists.updateSmartList(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.delete(function(req, res, next) {
		req.body.listid = req.params.listid
		SmartLists.deleteSmartList(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})

const Tasks = require('./functions/tasks')

router.route('/lists/:listid/tasks')
	.get(function(req, res, next) {
		req.body.listid = req.params.listid
		if (req.query.page != undefined) {req.body.page = req.query.page}
		if (req.query.page_size != undefined) {req.body.page_size = req.query.page_size}
		req.body.completed_only = req.query.completed_only != undefined && req.query.completed_only == "true"
		Tasks.getTasksForList(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/smart-lists/:listid/tasks')
	.get(function(req, res, next) {
		req.body.listid = req.params.listid
		if (req.query.page != undefined) {req.body.page = req.query.page}
		if (req.query.page_size != undefined) {req.body.page_size = req.query.page_size}
		req.body.completed_only = req.query.completed_only != undefined && req.query.completed_only == "true"
		Tasks.getTasksForSmartList(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/tasks')
	.post(function(req, res, next) {
		if (req.body.tasks) {
			// We're doing this because it makes it MUCH easier
			// to deal with sending multiple tasks via API Gateway.
			let escapedTasks = JSON.stringify(req.body.tasks)
			req.body.tasks = escapedTasks
		}
		Tasks.createTask(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/tasks/count')
	.get(function(req, res, next) {
		if (req.query.selected_dates != undefined) {req.body.selected_dates = req.query.selected_dates}
		if (req.query.completion_cutoff_date != undefined) {req.body.completion_cutoff_date = req.query.completion_cutoff_date}
		Tasks.getTaskCounts(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/tasks/date_count')
	.get(function(req, res, next) {
		if (req.query.smart_listid != undefined) {req.body.smart_listid = req.query.smart_listid}
		if (req.query.listid != undefined) {req.body.listid = req.query.listid}
		if (req.query.begin_date != undefined) {req.body.begin_date = req.query.begin_date}
		if (req.query.end_date != undefined) {req.body.end_date = req.query.end_date}
		Tasks.getTaskCountByDateRange(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/tasks/search')
	.get(function(req, res, next) {
		if (req.query.search_text != undefined) {req.body.search_text = req.query.search_text}
		if (req.query.page != undefined) {req.body.page = req.query.page}
		if (req.query.page_size != undefined) {req.body.page_size = req.query.page_size}
		req.body.completed_only = req.query.completed_only != undefined && req.query.completed_only == "true"
		Tasks.getTasksForSearchText(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/tasks/:taskid')
	.get(function(req, res, next) {
		req.body.taskid = req.params.taskid
		Tasks.getTask(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.put(function(req, res, next) {
		req.body.taskid = req.params.taskid
		Tasks.updateTask(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.delete(function(req, res, next) {
		req.body.taskid = req.params.taskid
		Tasks.deleteTask(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})

router.route('/tasks/:taskid/count')
	.get(function(req, res, next) {
		req.body.taskid = req.params.taskid
		Tasks.getSubtaskCount(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/tasks/complete')
	.post(function(req, res, next) {
		Tasks.completeTasks(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/tasks/uncomplete')
	.post(function(req, res, next) {
		Tasks.uncompleteTasks(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/tasks/:taskid/subtasks')
	.get(function(req, res, next) {
		req.body.taskid = req.params.taskid
		if (req.query.page != undefined) {req.body.page = req.query.page}
		if (req.query.page_size != undefined) {req.body.page_size = req.query.page_size}
		req.body.completed_only = req.query.completed_only != undefined && req.query.completed_only == "true"
		Tasks.getSubtasks(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

const Taskitos = require('./functions/taskitos')

router.route('/checklist/:taskid/items')
	.get(function(req, res, next) {
		req.body.taskid = req.params.taskid
		Taskitos.getTaskitosForChecklist(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/checklist/items')
	.post(function(req, res, next) {
		Taskitos.createTaskito(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/checklist/items/:taskitoid')
	.put(function(req, res, next) {
		req.body.taskitoid = req.params.taskitoid
		Taskitos.updateTaskito(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.delete(function(req, res, next) {
		req.body.taskitoid = req.params.taskitoid
		Taskitos.deleteTaskito(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})

router.route('/checklist/items/complete')
	.post(function(req, res, next) {
		Taskitos.completeTaskitos(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/checklist/items/uncomplete')
	.post(function(req, res, next) {
		Taskitos.uncompleteTaskitos(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

const Tags = require('./functions/tags')

router.route('/tags')
	.get((req, res, next) => {
		Tags.getAllTags(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.post((req, res, next) => {
		Tags.createTag(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/tags/:tagid')
	.get((req, res, next) => {
		req.body.tagid = req.params.tagid
		Tags.getTag(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.put((req, res, next) => {
		req.body.tagid = req.params.tagid
		Tags.updateTag(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.delete((req, res, next) => {
		req.body.tagid = req.params.tagid
		Tags.deleteTag(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})

router.route('/tasks/:taskid/tags')
	.get((req, res, next) => {
		req.body.taskid = req.params.taskid
		Tags.getTagsForTask(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/tasks/:taskid/tags/:tagid')
	.post((req, res, next) => {
		req.body.taskid = req.params.taskid
		req.body.tagid  = req.params.tagid
		Tags.assignTag(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.delete((req, res, next) => {
		req.body.taskid = req.params.taskid
		req.body.tagid  = req.params.tagid
		Tags.removeTagAssignment(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})

const Comments = require('./functions/comments')

router.route('/tasks/:taskid/comments')
	.get((req, res, next) => {
		req.body.taskid = req.params.taskid
		Comments.getCommentsForTask(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/comments')
	.post((req, res, next) => {
		Comments.createComment(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/comments/:commentid')
	.get((req, res, next) => {
		req.body.commentid = req.params.commentid
		Comments.getComment(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.put((req, res, next) => {
		req.body.commentid = req.params.commentid
		Comments.updateComment(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.delete((req, res, next) => {
		req.body.commentid = req.params.commentid
		Comments.deleteComment(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})

const TaskNotifications = require('./functions/task-notifications')

router.route('/notifications')
	.get((req, res, next) => {
		TaskNotifications.getNotificationsForUser(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.post((req, res, next) => {
		TaskNotifications.createNotification(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/notifications/:notificationid')
	.get((req, res, next) => {
		req.body.notificationid = req.params.notificationid
		TaskNotifications.getNotification(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.put((req, res, next) => {
		req.body.notificationid = req.params.notificationid
		TaskNotifications.updateNotification(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})
	.delete((req, res, next) => {
		req.body.notificationid = req.params.notificationid
		TaskNotifications.deleteNotification(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})

router.route('/tasks/:taskid/notifications')
	.get((req, res, next) => {
		req.body.taskid = req.params.taskid
		TaskNotifications.getNotificationsForTask(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

const SystemNotification = require('./functions/system-notification')

router.route('/system/notification')
	.get((req, res, next) => {
		SystemNotification.getLatestSystemNotification(req.body, null, (err, responseData) => {
			err == null ? res.json(responseData) : next(err)
		})
	})

router.route('/invitations')
	.post((req, res, next) => {
		Invitation.sendListInvitation(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})
	.get((req, res, next) => {
		Invitation.getInvitations(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})

router.route('/invitations/:invitationid')
	.post((req, res, next) => {
		req.body.invitationid = req.params.invitationid
		Invitation.acceptInvitation(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})
	.put((req, res, next) => {
		req.body.invitationid = req.params.invitationid
		Invitation.updateInvitationRole(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})
	.delete((req, res, next) => {
		req.body.invitationid = req.params.invitationid
		Invitation.deleteInvitation(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})

router.route('/invitations/:invitationid/resend')
	.put((req, res, next) => {
		req.body.invitationid = req.params.invitationid
		Invitation.resendInvitation(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})

router.route('/lists/:listid/invitations')
	.get((req, res, next) => {
		req.body.listid = req.params.listid
		Invitation.getInvitationsForList(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})

const ListMember = require('./functions/list-member')

router.route('/list-member')
	.get((req, res, next) => {
		ListMember.getMembersForAllLists(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})

router.route('/list-member/:memberid/role')
	.put((req, res, next) => {
		req.body.memberid = req.params.memberid
		ListMember.changeRole(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})

router.route('/list-member/:memberid/remove/:listid')
	.delete((req, res, next) => {
		req.body.memberid = req.params.memberid
		req.body.listid = req.params.listid
		ListMember.removeMembership(req.body, null, (err, responseData) => {
			if (err) {
				next(err)
			} else {
				res.status(204)
				res.json(responseData)
			}
		})
	})

router.route('/list/:listid/members')
	.get((req, res, next) => {
		req.body.listid = req.params.listid
		ListMember.getMembersForList(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})

// Only for sync clients
router.route('/list-member/counts')
	.get((req, res, next) => {
		ListMember.listMemberCounts(req.body, null, (err, responseData) => {
			err ? next(err) : res.json(responseData)
		})
	})

const Sync = require('./functions/sync')

if (process.env.DB_TYPE == 'sqlite') {
	logger.debug('Sync route open')
	router.route('/sync')
		.post((req, res, next) => {
			Sync.performSync(req.body, null, (err, responseData) => {
				err ? next(err) : res.json(responseData)
			})
		})

	router.route('/sync/id/list/:listid')
		.get((req, res, next) => {
			req.body.listid = req.params.listid
			Sync.syncIdForList(req.body, null, (err, responseData) => {
				err ? next(err) : res.json(responseData)
			})
		})
}


router.use(logErrorsHandler)
router.use(apiErrorHandler)
router.use(defaultErrorHandler)

// REGISTER OUR ROUTES -------------------------------
// all of our routes will be prefixed with /api
app.use(process.env.DB_TYPE == 'sqlite' ? '/api/v1' : '/', router);

function logErrorsHandler(err, req, res, next) {
	if (err) {
		logger.error('todo-api error: ' + err)
	}
	next(err)
}

function apiErrorHandler(err, req, res, next) {
	// Parse the error code from the message and send it back to the
	// client in the way that's expected according to the API doc.
	try {
		var errObj = JSON.parse(err)
		if (errObj && errObj.errorType && errObj.httpStatus && errObj.message) {
			res.status(errObj.httpStatus)
			res.json({code:errObj.errorType, message:errObj.message})
			return
		}
	} catch (e) {
		res.status(500)
		res.json(`{"code":"${errors.serverError.errorType}", "message":"Error parsing error message: ${JSON.stringify(e)}"}`)
		logger.trace(`Exception parsing error message: ${e}`)
		return
	}

	next(err)
}

function defaultErrorHandler(err, req, res, next) {
	logger.error(err)
	// Delegate to the default error handling mechanisms in Express if a response
	// was already started.
	if (res.headersSent) {
		return next(err)
	}
	res.status(500)
	res.json({error:err.stack})
}

io.on('connection', function(socket) {
	logger.debug('SYNC Event Socket: connected')
	socket.on('disconnect', function() {
		logger.debug('SYNC Event Socket: disconnected')
	})

	socket.on('sync-event', function(data) {
		// Pass this on to the client
		logger.debug(`Saw 'sync-event': ${JSON.stringify(data)}`)
		io.emit('sync-message', data)
	})
})

// START THE SERVER
// =============================================================================
// app.listen(port);
// app.listen(port, function() {
http.listen(port, function() {
	logger.info('Todo Cloud API running on port ' + port);

	// If we're running with an SQLite environment, make sure the DB is
	// prepared properly.
	if (process.env.DB_TYPE == 'sqlite') {
		db.populateDB(function(err, result) {
			if (err) {
				logger.error(`Error preparing the SQLite DB: ${err}`)
				exit(1)
			} else {
				logger.info(`Todo DB ready`)
			}
		})
	}
})

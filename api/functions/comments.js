const TCCommentService = require('./common/tc-comment-service')
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

const getSyncedTaskSyncId = function(params, next) {
    const TCTaskService = require('./common/tc-task-service')
    const TCSyncService = require('./common/tc-sync-service')
    const async = require('async')

    const getTask = (next) => {
        TCTaskService.getTask({ userid : params.userid, taskid : params.taskid }, next)
    }

    const syncWhenNeeded = (task, next) => {
        if(task && task.sync_id) {
            next(null, task)
            return
        }

        TCSyncService.performSync({}, next)
    }

    const reloadTaskWhenNeeded = (task, next) => {
        if(task && task.sync_id) {
            next(null, task)
            return
        }

        getTask(next)
    }

    async.waterfall([
        getTask,
        syncWhenNeeded,
        reloadTaskWhenNeeded
    ],
    (err, task) => {
        if (err) {
            next(err, null)
            return
        }

        next(null, task.sync_id)
    })
}

// Returns all comments for a task
exports.getCommentsForTask = function(event, context, callback) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require ('./common/constants')

        getSyncedTaskSyncId(event, (err, syncid) => {
            if (err) {
                handleResult(err, null, callback)
                return
            }

            utils.makeHttpRequest(
                `/tasks/${syncid}/comments`,
                constants.httpMethods.GET, 
                null, 
                null, 
                callback)
        })
        return
    }

    TCCommentService.getCommentsForTask(event, (err, tasks) => {
        handleResult(err, tasks, callback)
    })
}

// Creates a new comment
exports.createComment = function(event, context, callback) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require ('./common/constants')

        getSyncedTaskSyncId({ taskid : event.itemid, userid : event.userid }, (err, syncid) => {
            if (err) {
                handleResult(err, null, callback)
                return
            }

            event.itemid = syncid
            utils.makeHttpRequest(
                `/comments`,
                constants.httpMethods.POST, 
                event, 
                null, 
                callback
            )
        })
        return
    }

    TCCommentService.createComment(event, (err, task) => {
        handleResult(err, task, callback)
    })
}

// Returns a single comment
exports.getComment = function(event, context, callback) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require ('./common/constants')

        utils.makeHttpRequest(
            `/comments/${event.commentid}`,
            constants.httpMethods.GET, 
            null, 
            null, 
            callback
        )

        return
    }

    TCCommentService.getComment(event, (err, task) => {
        handleResult(err, task, callback)
    })
}

// Updates a comment
exports.updateComment = function(event, context, callback) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require ('./common/constants')

        utils.makeHttpRequest(
            `/comments/${event.commentid}`,
            constants.httpMethods.PUT, 
            event, 
            null, 
            callback
        )

        return
    }

    TCCommentService.updateComment(event, (err, task) => {
        handleResult(err, task, callback)
    })
}

// Deletes a comment
exports.deleteComment = function(event, context, callback) {
    if (process.env.DB_TYPE == 'sqlite') {
        const utils = require('./common/tc-utils')
        const constants = require ('./common/constants')

        utils.makeHttpRequest(
            `/comments/${event.commentid}`,
            constants.httpMethods.DELETE, 
            null, 
            null, 
            callback
        )

        return
    }

    TCCommentService.deleteComment(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

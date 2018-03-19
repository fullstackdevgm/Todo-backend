const TCTaskService = require('./common/tc-task-service')
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

// Returns tasks for a specific list
exports.getTasksForList = function(event, context, callback) {
    TCTaskService.tasksForList(event, (err, tasks) => {
        handleResult(err, tasks, callback)
    })
}

// Return task counts for all lists and smart lists
exports.getTaskCounts = function(event, context, callback) {
    TCTaskService.getTaskCounts(event, (err, taskCounts) => {
        handleResult(err, taskCounts, callback)
    })
}

// Return task counts by dates for a specific smart list or list
exports.getTaskCountByDateRange = function(event, context, callback) {
    TCTaskService.getTaskCountByDateRange(event, (err, taskCounts) => {
        handleResult(err, taskCounts, callback)
    })
}

// Returns tasks for a specific smart list
exports.getTasksForSmartList = function(event, context, callback) {
    TCTaskService.tasksForSmartList(event, (err, tasks) => {
        handleResult(err, tasks, callback)
    })
}

// Completes tasks specified in the tasks array
exports.completeTasks = function(event, context, callback) {
    TCTaskService.completeTasks(event, (err, completedTasks) => {
        handleResult(err, completedTasks, callback)
    })
}

// Marks tasks specified in the tasks array as uncompleted
exports.uncompleteTasks = function(event, context, callback) {
    TCTaskService.uncompleteTasks(event, (err, uncompletedTasks) => {
        handleResult(err, uncompletedTasks, callback)
    })
}

// Creates new task(s) for the given list id
exports.createTask = function(event, context, callback) {
    TCTaskService.addTasks(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// Returns a single task
exports.getTask = function(event, context, callback) {
    TCTaskService.getTask(event, (err, task) => {
        handleResult(err, task, callback)
    })
}

// Updates a task
exports.updateTask = function(event, context, callback) {
    TCTaskService.updateTask(event, (err, task) => {
        handleResult(err, task, callback)
    })
}

// Deletes a task
exports.deleteTask = function(event, context, callback) {
    TCTaskService.deleteTask(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// Gets subtasks for the specified project
exports.getSubtasks = function(event, context, callback) {
    TCTaskService.getSubtasks(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.getSubtaskCount = function (event, context, callback) {
    TCTaskService.getSubtaskCount(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.getTasksForSearchText = function(event, context, next) {
    TCTaskService.getTasksForSearchText(event, (err, result) => {
        handleResult(err, result, next)
    })
}

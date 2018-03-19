const TCAccountService = require('./common/tc-account-service')
const TCListService = require('./common/tc-list-service')
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

// Returns all the lists for a logged-in user
exports.getLists = function(event, context, callback) {
    TCListService.listsForUserId(event, (err, lists) => {
        handleResult(err, lists, callback)
    })
}

// Creates a new list for the logged-in user
exports.createList = function(event, context, callback) {
    TCListService.addList(event, (err, list) => {
        handleResult(err, list, callback)
    })
}

// Returns a single list
exports.getList = function(event, context, callback) {
    TCListService.getList(event, (err, list) => {
        handleResult(err, list, callback)
    })
}

// Updates a list
exports.updateList = function(event, context, callback) {
    TCListService.updateList(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// Deletes a list
exports.deleteList = function(event, context, callback) {
    TCListService.deleteList(event, (err, result)=> {
        handleResult(err, result, callback)
    })
}

exports.taskCountForList = function(event, context, callback) {
    TCListService.taskCountForList(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

const TCSmartListService = require('./common/tc-smart-list-service')

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
        callback(serverErr)
        return
    }
    else { 
        callback(null, result) 
    }
}

// Returns all the smart lists for a logged-in user
exports.getSmartLists = function(event, context, callback) {
    TCSmartListService.getSmartLists(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// Creates a new smart list for the logged-in user
exports.createSmartList = function(event, context, callback) {
    TCSmartListService.createSmartList(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// Returns a single smart list
exports.getSmartList = function(event, context, callback) {
    TCSmartListService.getSmartList(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// Updates a smart list
exports.updateSmartList = function(event, context, callback) {
    TCSmartListService.updateSmartList(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// Deletes a smart list
exports.deleteSmartList = function(event, context, callback) {
    TCSmartListService.deleteSmartList(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

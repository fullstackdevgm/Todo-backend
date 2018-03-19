const TCTaskitoService = require('./common/tc-taskito-service')
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

exports.getTaskitosForChecklist = function(event, context, callback) {
    TCTaskitoService.getTaskitosForChecklist(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.createTaskito = function(event, context, callback) {
    TCTaskitoService.addTaskito(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.updateTaskito = function(event, context, callback) {
    TCTaskitoService.updateTaskito(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.deleteTaskito = function(event, context, callback) {
    TCTaskitoService.deleteTaskito(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// exports.moveTaskito = function(event, context, callback) {
//     TCTaskitoService.moveTaskito(event, (err, result) => {
//         handleResult(err, result, callback)
//     })
// }

exports.completeTaskitos = function(event, context, callback) {
    TCTaskitoService.completeTaskitos(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.uncompleteTaskitos = function(event, context, callback) {
    TCTaskitoService.uncompleteTaskitos(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

// exports.updateSortOrders = function(event, context, callback) {
//     TCTaskitoService.updateSortOrders(event, (err, result) => {
//         handleResult(err, result, callback)
//     })
// }

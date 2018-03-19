const TCSyncService = require('./common/tc-sync-service')
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

// Performs a synchronization with the remote sync API
exports.performSync = function(event, context, callback) {
    TCSyncService.performSync(event, function(err, result) {
        handleResult(err, result, callback)
    })
}

exports.syncIdForList = function(event, context, next) {
    TCListService.syncIdForListId(event, (err, result) => {
        if (err) {
            next(null, {
                hasSyncId : false,
                syncId : ''
            })
            return
        }

        next(null, {
            hasSyncId : true,
            syncId : result
        })
    })
}

const TCTagService = require('./common/tc-tag-service')
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

// Returns all tags for a user
exports.getAllTags = function(event, context, callback) {
    TCTagService.getAllTags(event, (err, tags) => {
        handleResult(err, tags, callback)
    })
}

// Returns tags assigned to a task
exports.getTagsForTask = function(event, context, callback) {
    TCTagService.getTagsForTask(event, (err, tags) => {
        handleResult(err, tags, callback)
    })
}

// Creates a new tag
exports.createTag = function(event, context, callback) {
    TCTagService.createTag(event, (err, tag) => {
        handleResult(err, tag, callback)
    })
}

// Returns a single tag
exports.getTag = function(event, context, callback) {
    TCTagService.getTag(event, (err, tag) => {
        handleResult(err, tag, callback)
    })
}

// Updates a tag
exports.updateTag = function(event, context, callback) {
    TCTagService.updateTag(event, (err, tag) => {
        handleResult(err, tag, callback)
    })
}

// Deletes a tag
exports.deleteTag = function(event, context, callback) {
    TCTagService.deleteTag(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.assignTag = function(event, context, callback) {
    TCTagService.assignTag(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

exports.removeTagAssignment = function(event, context, callback) {
    TCTagService.removeTagAssignment(event, (err, result) => {
        handleResult(err, result, callback)
    })
}

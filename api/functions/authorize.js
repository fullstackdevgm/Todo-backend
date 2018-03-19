// This function is ONLY for running our REST API in the Amazon API Gateway
// and Lambda environment. This is the function that our custom authorizer in
// API Gateway uses to decode a JWT and extract a userid to pass on to Lambda
// functions requiring authorization.

var jwt = require('./common/jwt')

exports.handler = function(event, context, callback) {
    var authHeader = event.authorizationToken
    if (authHeader && authHeader.length > 0) {
        var parts = authHeader.split(' ')
        if (parts.length == 2) {
            var token = parts[1]
            var userid = jwt.userIDFromToken(token)
            if (userid && userid.length > 0) {
                // userIDFromToken() never returns a userid if the
                // token is expired. So if our code gets this far,
                // everything's good to go.
                callback(null, generatePolicy(userid, 'Allow')) // 200
            } else {
                callback(null, generatePolicy(userid, 'Deny')) // 403
            }
        }
    } else {
        callback("Unauthorized") // 401
    }
}

function generatePolicy(userid, effect) {
    if (!userid) {
        userid = 'unknown'
    }
    var authResponse = {
        principalId: 'user',
        policyDocument: {
            Version: '2012-10-17', // default version
            Statement: [
                {
                    Action: "execute-api:Invoke",
                    Effect: effect,
                    Resource: 'arn:aws:execute-api:*:*:*'
                }
            ]
        },
        context: {
            userid: userid
        }
    }

    return authResponse
}
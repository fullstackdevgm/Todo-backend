const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const uuidV4 = require('uuid/v4')
const jwt = require('jwt-simple')

function _payloadForToken(token) {
  try {
    const secret = process.env.JWT_TOKEN_SECRET
    var payload = jwt.decode(token, secret, process.env.DB_TYPE == 'sqlite')
    return payload
  } catch (e) {
    // Intentionally blank
    logger.debug('Exception decoding JWT token: ' + e)
  }

  return null
}

function _isPayloadValid(payload) {
  if (!payload) {
    return false
  }

  if (payload.exp <= 0) {
    return false
  }

  // Check to see if the expiration date of the token has passed
  var now = Math.floor(Date.now() / 1000)
  if (payload.exp < now) {
    return false
  }

  return true
}

function createToken(userid, username, sessionTimeout) {
  var tokenId = new Buffer(uuidV4()).toString('base64')
	var issuedAt = Math.floor(Date.now() / 1000) // UNIX timestamp
  var expire = issuedAt + parseInt(sessionTimeout)
  var serverName = 'api.todo-cloud.com-1.0.0' // TO-DO Read the server version from an environment variable or config file

  var data = {
    'jti':tokenId,
    'iat':issuedAt,
    'iss':serverName,
    'exp':expire,
    'data':{
      'userid':userid,
      'username':username
    }
  }

  try {
    const secret = process.env.JWT_TOKEN_SECRET
    var token = jwt.encode(data, secret)
    return token
  } catch (e) {
    logger.debug('Exception trying to JWT encode (' + JSON.stringify(data) + '): ' + e)
  }

  return null
}

function isValid(token) {
  var payload = _payloadForToken(token)
  if (!payload) {
    return false
  }

  return _isPayloadValid(payload)
}

function userIDFromToken(token) {
  var payload = _payloadForToken(token)
  if (!_isPayloadValid(payload)) {
    return null
  }

  return payload.data.userid
}

exports.createToken = createToken
exports.isValid = isValid
exports.userIDFromToken = userIDFromToken
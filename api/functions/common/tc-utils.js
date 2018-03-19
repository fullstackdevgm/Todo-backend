'use strict'

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const moment = require('moment-timezone')
require('datejs')

const constants = require('./constants')
const errors = require('./errors')

class TCUtils {
    static dateWithTimeFromDate(originalDate, timeDate) {
        // Extract the time portion from timeDate and
        // add it on to the originalDate.
        const originalDateObj = moment.unix(originalDate)

        const timeDateObj = moment.unix(timeDate)

        originalDateObj.hour(timeDateObj.hour())
        originalDateObj.minute(timeDateObj.minute())
        originalDateObj.second(timeDateObj.second())
        originalDateObj.millisecond(timeDateObj.millisecond())

        return originalDateObj.unix()
    }

    static normalizedDateFromGMT(gmttimestamp, userTimeZone) {
        if (!userTimeZone) {
            userTimeZone = "Etc/GMT"
        }
        const newDate = moment.tz(gmttimestamp * 1000, userTimeZone)
        newDate.hour(12)
        newDate.minute(0)
        newDate.second(0)
        newDate.millisecond(0)

        return newDate.unix()
    }

    static denormalizeDateToMidnightGMT(localTimestamp, userTimeZone) {
        if (!userTimeZone) {
            userTimeZone = "Etc/GMT"
        }
        const localDate = moment.tz(localTimestamp * 1000, userTimeZone)
        const gmtDate = localDate.clone().tz("Etc/GMT")
        gmtDate.hour(0)
        gmtDate.minute(0)
        gmtDate.second(0)
        gmtDate.millisecond(0)

        return gmtDate.unix()
    }

    static hasDateOccurrence(aDate, dayOfWeek, occurrence) {
        if (occurrence <= 0) {
            return true
        }
        
        const daysInMonth = Date.getDaysInMonth(aDate.getYear(), aDate.getMonth())
        const movingDate = moment(aDate).startOf('month')
        let dayOccurrences = 0
        for (let dayOfMonth = 0; dayOfMonth <= daysInMonth; dayOfMonth++) {
            const weekdayNumber = movingDate.isoWeekday() % 7
            if (weekdayNumber == dayOfWeek) {
                dayOccurrences++
            }
            movingDate.add(1, 'day')
        }

        return (dayOccurrences >= occurrence)
    }

    static makeHttpRequest(path, method, body, query, next) {
        const TCAccountService = require('./tc-account-service')
        const rp = require('request-promise')

        TCAccountService.getJWT(function(err, jwt) {
            if (err) {
                errors.handleLocalError(err, function(result) {
                    next(result)
                })
                return
            }

            const cleanedPath = path.charAt(0) == '/' ? path.substring(1) : path

            const options = {
                uri: `${process.env.TC_API_URL}${path}`,
                method: method,
                body: body,
                qs : query ? query : { nothing: 'non-value'},
                headers: {
                    Authorization: `Bearer ${jwt}`,
                    "x-api-key": process.env.TC_API_KEY
                },
                json: true
            }

            if (!body)  delete options.body
            if (!query) delete options.qs

            rp(options)
                .then(function(jsonResponse) {
                    // logger.debug('http call success')
                    // logger.debug(options)
                    next(null, jsonResponse)
                })
                .catch(function(err) {
                    logger.debug('http call error')
                    logger.debug(`Status: ${err.statusCode}: ${err.statusMessage}`)
                    logger.debug(err.message)
                    logger.debug(err.options)
                    // An error occurred
                    next(errors.customError(errors.serverError, `Error communicating with the service: ${err}`))
                })
        })
    }
}

module.exports = TCUtils

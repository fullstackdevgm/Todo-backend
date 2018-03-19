'use strict'

const constants = require('./constants')

exports.invalidParameters = {errorType: 'InvalidParameters', httpStatus: 400, message: 'Invalid parameters specified.'}
exports.missingParameters = {errorType: 'MissingParameters', httpStatus: 400, message: 'Missing parameters.'}
exports.usernameLengthExceeded = {errorType: 'UsernameLengthExceeded', httpStatus: 400, message: `The username provided is too long. (MAX = ${constants.maxUsernameLength})`}
exports.passwordLengthExceeded = {errorType: 'PasswordLengthExceeded', httpStatus: 400, message: `The password provided is too long. (MAX = ${constants.maxPasswordLength})`}
exports.passwordTooShort = {errorType: 'PasswordTooShort', httpStatus: 400, message: `The password provided is too short. (MIN = ${constants.minPasswordLength})`}
exports.passwordInvalid = {errorType: 'PasswordInvalid', httpStatus: 403, message: `Invalid password specified.`}
exports.passwordResetExpired = {errorType: 'PasswordResetExpired', httpStatus: 403, message: `The password reset time has expired. Please try to reset the password again.`}
exports.passwordResetNotFound = {errorType: 'PasswordResetNotFound', httpStatus: 403, message: `Password reset was not enabled for this account. Please try to reset the password again.`}
exports.passwordsNotSameError = {errorType: 'PasswordsNotSame', httpStatus: 400, message: `The passwords entered do not match.`}
exports.firstNameLengthExceeded = {errorType: 'FirstNameLengthExceeded', httpStatus: 400, message: `The first name provided is too long. (MAX = ${constants.maxFirstNameLength})`}
exports.lastNameLengthExceeded = {errorType: 'LastNameLengthExceeded', httpStatus: 400, message: `The last name provided is too long. (MAX = ${constants.maxLastNameLength})`}

exports.localeInvalid = {errorType: 'LocaleInvalid', httpStatus: 400, message: `Invalid locale. Valid locales include: ${constants.supportedLocales.toString()}`}
exports.bestMatchLocaleInvalid = {errorType: 'BestMatchLocaleInvalid', httpStatus: 400, message: `Invalid locale. Valid locales include: ${constants.supportedLocales.toString()}`}

exports.usernameInvalid = {errorType: 'UsernameInvalid', httpStatus: 400, message: `Invalid email specified for username.`}
exports.usernameInvalidPlusCharacter = {errorType: 'UsernameInvalidWithPlusCharacter', httpStatus: 400, message: `Invalid email specified for username. Cannot use + character.`}
exports.usernameAlreadyExists = {errorType: 'UsernameUnavailable', httpStatus: 400, message: `The specified username is not available.`}
exports.accountInMaintenance = {errorType: 'AccountMaintenance', httpStatus: 503, message: `The specified account is currently under maintenance.`}
exports.accountNotFound = {errorType: 'AccountNotFound', httpStatus: 403, message: `The request could not be completed because the associated account could not be found.`}
exports.accountSettingsNotFound = {errorType: 'AccountSettingsNotFound', httpStatus: 403, message: `The request could not be completed because the associated account settings could not be found.`}
exports.emailVerificationNotFound = {errorType: 'EmailVerificationNotFound', httpStatus: 403, message: `An email verification record was not found. Please repeat the verify email process.`}

exports.commentNotFound = {errorType: 'CommentNotFound', httpStatus: 403, message: `The request could not be completed because the comment could not be found.`}

exports.timezoneInvalid = {errorType: 'TimezoneInvalid', httpStatus: 400, message: `An invalid time zone was specified.`}

exports.userSettingsNotFound = {errorType: 'UserSettingsNotFound', httpStatus: 403, message: `The request could not be completed because the associated user settings could not be found.`}

exports.subscriptionNotFound = {errorType: 'SubscriptionNotFound', httpStatus: 403, message: `An account subscription could not be found.`}
exports.subscriptionTypeInvalid = {errorType: 'SubscriptionTypeInvalid', httpStatus: 403, message: `An invalid subscription type was specified.`}
exports.subscriptionPurchaseNotEligible = {errorType: 'SubscriptionPurchaseNotEligible', httpStatus: 403, message: `The subscription is not yet eligible for extension.`}

exports.listNotFound = {errorType: 'ListNotFound', httpStatus: 403, message: `The specified list could not be found.`}
exports.listMembershipNotFound = {errorType: 'ListMembershipNotFound', httpStatus: 403, message: `The request could not be completed because the list membership could not be found.`}
exports.listMembershipNotEmpty = {errorType: 'ListMembershipNotEmpty', httpStatus: 400, message: `The list could not be deleted because it is shared with other members.`}

exports.databaseError = {errorType: 'DatabaseError', httpStatus: 500, message: `A database error occurred.`}

exports.maxBulkTasksExceeded = {errorType: 'TaskCreateLimitExceeded', httpStatus: 403, message: `The number of tasks that can be created at one time is limited to ${constants.maxCreateBulkTasks}.`}
exports.taskNotFound = {errorType: 'TaskNotFound', httpStatus: 403, message: `The specified task could not be found.`}
exports.taskAlreadyCompleted = {errorType: 'TaskAlreadyCompleted', httpStatus: 403, message: `The specified task is already completed.`}
exports.taskNotCompleted = {errorType: 'TaskNotCompleted', httpStatus: 403, message: `The specified task is not completed.`}
exports.invalidParent = {errorType: 'InvalidParent', httpStatus: 403, message: `The specified task is not a project or checklist.`}

exports.imageTypeUnkown = {errorType: 'ImageTypeUnknown', httpStatus: 403, message: `The image type could not be determined.`}
exports.imageTypeUnsupported = {errorType: 'ImageTypeUnsupported', httpStatus: 403, message: `Supported image types are *.jpg or *.png.`}
exports.imageNotFound = {errorType: 'ImageNotFound', httpStatus: 403, message: `The image could not be found.`}

exports.mandrillError = {errorType: 'MandrillEmailServiceError', httpStatus: 500, message: `An error occurred sending an email.`}
exports.emailTemplateError = {errorType: 'EmailTemplateError', httpStatus: 500, message: `An error occurred processing an email template.`}
exports.emailServiceError = {errorType: 'EmailServiceError', httpStatus: 500, message: `An error occurred sending an email.`}

exports.teamNotFound = {errorType: 'TeamNotFound', httpStatus: 403, message: `The specified team could not be found.`}

exports.unauthorizedError = {errorType: 'Unauthorized', httpStatus: 403, message: `You are not authorized to access this resource or operation.`}

exports.serverError = {errorType: 'ServerError', httpStatus: 500, message: `An internal server error occurred.`}
exports.unknownError = {errorType: 'UnknownError', httpStatus: 500, message: `An unknown error occurred.`}

exports.paymentProcessingError = {errorType: 'PaymentProcessingError', httpStatus: 500, message: `An error occurred communicating with the payment processing service.`}
exports.paymentHasIAPError = {errorType: 'PaymentHasIAPError', httpStatus: 403, message: `The specified account has a recurring existing in-app purchase subscription configured.`}
exports.paymentInvalidCharge = {errorType: 'PaymentInvalidCharge', httpStatus: 403, message: `The total charge specified does not match the subscription type.`}
exports.paymentNotFound = {errorType: 'PaymentNotFound', httpStatus: 403, message: `The specified payment could not be found.`}

exports.lastOwner = {errorType: 'LastOwner', httpStatus: 403, message: `Attempted to remove the last list owner membership.`}

///
/// Sync Errors
///
exports.syncProtocolVersionUnsupported = {errorType: 'SyncProtocolVersionUnsupported', httpStatus: 403, message: `Unsupported sync protocol version. Please upgrade your app to a new version.`}
exports.syncServerDataReset = {errotType: 'SyncServerDataReset', httpStatus: 403, message: `Task data was reset on the server. Prompt the customer with a choice of what to do next.`}
exports.syncError = {errorType: 'SyncError', httpStatus: 500, message: 'A synchronization error occurred. Please try again.'}

const customError = function(baseError, customMessage) {
    if (!baseError.errorType && baseError.message) {
        // Handle the case when this is called without first transforming
        // to the kind of error we're expecting (less work for the developer).
        try {
            baseError = JSON.parse(baseError.message)
        } catch (error) {
            baseError = exports.serverError
        }
    }
    let err = {
        errorType: baseError.errorType,
        httpStatus: baseError.httpStatus,
        message: `${baseError.message} ${customMessage}`
    }
    return err
}
exports.customError = customError

exports.create = function(error, customMessage) {
    const errorString = JSON.stringify(customMessage ? customError(error, customMessage) : error)
    return new Error(errorString)
}

exports.nonError = {errorType: 'NonError', httpStatus: 200, message: `This is not an error that should be passed on from the API and is only used internally to signal an early bailout.`}

// This method is used to help parse local errors and send them on
// correctly to the local API caller.
exports.handleLocalError = function(err, callback) {
    if (err == null || err.message == null) {
        callback(err)
    }

    const firstCurlyIndex = err.message.indexOf('{')
    if (firstCurlyIndex < 0) {
        callback(err)
    }

    const possibleJSON = err.message.substring(firstCurlyIndex)
    if (!possibleJSON) {
        callback(err)
    }

    try {
        const parsedJSON = JSON.parse(possibleJSON)
        parsedJSON.errorType = parsedJSON.code
        parsedJSON.httpStatus = Number.parseInt(err.message.substring(0, err.message.indexOf('-')).trim())
        const jsonErrString = JSON.stringify(parsedJSON)
        callback(jsonErrString)
    } catch(error) {
        console.error(`Exception parsing local JSON Error: ${error}`)
        callback(err)
    }
}

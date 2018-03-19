'use strict'

// I'm commenting out the AWS stuff for now unless we decide
// to use Amazon SES. I don't want to just blow all the code
// away because it'll be easier to bring back if we just keep
// it here.
//
// Also, we would need to add back in the following packages
// to the functions/package.json file:
//      aws-sdk             (allows us to communicate with SES)
//      email-templates     (lets us use email templates in functions/email-templates/)
//      handlebars          (the template language/library of the email-templates)
//
// const AWS = require('aws-sdk')
// // Configure the global region BEFORE instantiating anything
// // else from the AWS SDK for it to work properly.
// AWS.config.update({region: 'us-east-1'})
// const EmailTemplate = require('email-templates').EmailTemplate
// const path = require('path')
// const ses = new AWS.SES()
// 
// const welcomeEmailTemplateName = 'todo-cloud-welcome-email'
// const templatesDir = path.resolve(__dirname, '../email-templates')

const log4js = require('@log4js-node/log4js-api')
const logger = log4js.getLogger('todo-api')

const mandrill = require('mandrill-api/mandrill')
const moment = require('moment-timezone')

const constants = require('./constants')
const errors = require('./errors')

const mandrillClient = new mandrill.Mandrill(constants.mandrillAPIKey)
const mandrillTemplate = {
    welcomeEmail: "todo-cloud-welcome-email",
    resetPassword: "todo-cloud-reset-password",
    emailVerification: "todo-cloud-email-verification",
    subscriptionPurchaseReceipt: "todo-cloud-subscription-purchase-receipt",
    sharedListInivtation: "todo-cloud-shared-list-invitation"
}

class TCMailerService {
    //
    // Params:
    //  email
    //  displayName
    //  verifyEmailURL
    //  taskCreationEmail
    static sendWelcomeEmail(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let email = userInfo.email && typeof userInfo.email == 'string' ? userInfo.email.trim() : null
        let displayName = userInfo.displayName && typeof userInfo.displayName == 'string' ? userInfo.displayName.trim() : null
        let verifyEmailURL = userInfo.verifyEmailURL && typeof userInfo.verifyEmailURL == 'string' ? userInfo.verifyEmailURL.trim() : null
        let taskCreationEmail = userInfo.taskCreationEmail && typeof userInfo.taskCreationEmail == 'string' ? userInfo.taskCreationEmail.trim() : null

        if (!email || email.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the email parameter.`))))
            return
        }
        if (!displayName || displayName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the displayName parameter.`))))
            return
        }
        if (!verifyEmailURL || verifyEmailURL.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the verifyEmailURL parameter.`))))
            return
        }
        if (!taskCreationEmail || taskCreationEmail.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the taskCreationEmail parameter.`))))
            return
        }

        // The following commented-out lines are what we'd use if we decide
        // to use Amazon SES for transactional emails.
        // let mergeTags = {
        //     USER_DISPLAY_NAME: displayName,
        //     VERIFY_EMAIL_URL: verifyEmailURL,
        //     TASK_CREATION_EMAIL: taskCreationEmail
        // }

        // TCMailerService.sendEmailTemplate(
        //     {
        //         templateName:welcomeEmailTemplateName,
        //         email:email,
        //         displayName:displayName,
        //         mergeTags:mergeTags
        //     },
        //     function(err, result) {
        //         if (err) {
        //             callback(err)
        //         } else {
        //             callback(null, result)
        //         }
        //     }
        // )

        let mergeTags = [
            {name:'USER_DISPLAY_NAME', content: displayName},
            {name:'VERIFY_EMAIL_URL', content: verifyEmailURL},
            {name:'TASK_CREATION_EMAIL', content: taskCreationEmail}
        ]

        TCMailerService.sendMandrillEmailTemplate(
            {
                templateName:mandrillTemplate.welcomeEmail,
                email:email,
                displayName:displayName,
                mergeTags:mergeTags
            },
            function(err, result) {
                if (err) {
                    callback(err)
                } else {
                    callback(null, result)
                }
            }
        )        
    }

    // Uncomment this function if we decide to use Amazon SES for transactional emails
    // static sendEmailTemplate(userInfo, callback) {
    //     if (!userInfo) {
    //         callback(new Error(JSON.stringify(errors.missingParameters)))
    //         return
    //     }

    //     let templateName = userInfo.templateName && typeof userInfo.templateName == 'string' ? userInfo.templateName.trim() : null
    //     let email = userInfo.email && typeof userInfo.email == 'string' ? userInfo.email.trim() : null
    //     let displayName = userInfo.displayName && typeof userInfo.displayName == 'string' ? userInfo.displayName.trim() : null
    //     let subject = userInfo.subject && typeof userInfo.subject == 'string' ? userInfo.subject.trim() : null
    //     let fromEmail = userInfo.fromEmail && typeof userInfo.fromEmail == 'string' ? userInfo.fromEmail.trim() : null
    //     let fromName = userInfo.fromName && typeof userInfo.fromName == 'string' ? userInfo.fromName.trim() : null
    //     let replyToAddress = userInfo.replyToAddress && typeof userInfo.replyToAddress == 'string' ? userInfo.replyToAddress.trim() : null
    //     let mergeTags = userInfo.mergeTags ? userInfo.mergeTags : null

    //     if (!templateName || templateName.length == 0) {
    //         callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the templateName parameter.`))))
    //         return
    //     }
    //     if (!email || email.length == 0) {
    //         callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the email parameter.`))))
    //         return
    //     }
    //     if (!displayName || displayName.length == 0) {
    //         callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the displayName parameter.`))))
    //         return
    //     }

    //     if (!fromEmail) {
    //         fromEmail = constants.defaultEmailFromAddress
    //     }

    //     if (!subject) {
    //         subject = constants.defaultEmailSubject
    //     }

    //     if (replyToAddress) {
    //         params['ReplyToAddresses'] = [replyToAddress]
    //     } else {
    //         replyToAddress = constants.defaultEmailFromAddress
    //     }

    //     let params = {
    //         Destination: {
    //             ToAddresses: [email]
    //         },
    //         Message: {
    //             Subject: {
    //                 Data: subject
    //             }
    //         },
    //         Source: fromEmail,
    //         ReplyToAddresses: [replyToAddress]
    //     }

    //     if (mergeTags) {
    //         // Always add in the CURRENT_YEAR merge variable
    //         let currentYear = new Date().getFullYear()
    //         mergeTags['CURRENT_YEAR'] = currentYear
    //     }

    //     let template = new EmailTemplate(path.join(templatesDir, templateName))
    //     template.render(mergeTags, function(err, results) {
    //         if (err) {
    //             console.error(`An error occurred rendering the email template (${templateName}): ${err}`)
    //             callback(new Error(JSON.stringify(errors.customError(errors.emailTemplateError, `Error rendering template (${templateName}): ${err.message}`))))
    //         } else {
    //             if (results.html) {
    //                 params.Message['Body'] = {
    //                     Html: {Data: results.html}
    //                 }
    //             }
    //             if (results.text) {
    //                 params.Message.Body['Text'] = {Data: results.text}
    //             }
    //             if (results.subject) {
    //                 params.Message.Subject = {Data: results.subject}
    //             }
                
    //             try {
    //                 ses.sendEmail(params, function(err, data) {
    //                     if (err) {
    //                         console.error(`An error occurred sending the email template (${templateName}) to ${email}: ${err}`)
    //                         callback(new Error(JSON.stringify(errors.customError(errors.emailError), `Error occurred sending email template (${templateName}) to ${email}: ${err}`)))
    //                     } else {
    //                         callback(null, true)
    //                     }
    //                 })
    //             } catch (e) {
    //                 console.err(`An exception occurred trying to send an email template (${templateName}) to ${email}: ${e}`)
    //                 callback(null, false)
    //             }
    //         }
    //     })
    // }

    static sendEmailVerificationEmail(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let email = userInfo.email && typeof userInfo.email == 'string' ? userInfo.email.trim() : null
        let displayName = userInfo.displayName && typeof userInfo.displayName == 'string' ? userInfo.displayName.trim() : null
        let verifyEmailURL = userInfo.verifyEmailURL && typeof userInfo.verifyEmailURL == 'string' ? userInfo.verifyEmailURL.trim() : null
        let taskCreationEmail = userInfo.taskCreationEmail && typeof userInfo.taskCreationEmail == 'string' ? userInfo.taskCreationEmail.trim() : null

        if (!email || email.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the email parameter.`))))
            return
        }
        if (!displayName || displayName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the displayName parameter.`))))
            return
        }
        if (!verifyEmailURL || verifyEmailURL.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the verifyEmailURL parameter.`))))
            return
        }

        let mergeTags = [
            {name:'USER_DISPLAY_NAME', content: displayName},
            {name:'VERIFY_EMAIL_URL', content: verifyEmailURL}
        ]

        TCMailerService.sendMandrillEmailTemplate(
            {
                templateName:mandrillTemplate.emailVerification,
                email:email,
                displayName:displayName,
                mergeTags:mergeTags
            },
            function(err, result) {
                if (err) {
                    callback(err)
                } else {
                    callback(null, result)
                }
            }
        )        
    }

    static sendInvitationEmail(params, completion) {
        if (!params) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const email = params.email && typeof params.email == 'string' ? params.email.trim() : null
        const fromUserName = params.from_user_name && typeof params.from_user_name == 'string' ? params.from_user_name.trim() : null
        const invitationURL = params.invitation_url && typeof params.invitation_url == 'string' ? params.invitation_url.trim() : null
        const listName = params.list_name && typeof params.list_name == 'string' ? params.list_name.trim() : null

        if (!email || email.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the email parameter.`))))
            return
        }

        if (!fromUserName || fromUserName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the fromUserName parameter.`))))
            return
        }

        if (!invitationURL || invitationURL.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the invitationURL parameter.`))))
            return
        }

        if (!listName || listName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the listName parameter.`))))
            return
        }

        const mergeTags = [
            {name:'FROM_USER_NAME', content: fromUserName},
            {name:'INVITATION_URL', content: invitationURL},
            {name:'SHARED_LIST_NAME', content: listName}
        ]

        const mandrillParams = {
            email : email,
            templateName : mandrillTemplate.sharedListInivtation,
            displayName : 'Recipient',
            mergeTags : mergeTags
        }

        TCMailerService.sendMandrillEmailTemplate(mandrillParams, (err, result) => {
            if (err) {
                completion(err)
            }
            else {
                completion(null, result)
            }
        })
    }


    //
    // Params:
    //  email
    //  displayName
    //  resetURL
    static sendPasswordResetEmail(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let email = userInfo.email && typeof userInfo.email == 'string' ? userInfo.email.trim() : null
        let displayName = userInfo.displayName && typeof userInfo.displayName == 'string' ? userInfo.displayName.trim() : null
        let resetURL = userInfo.resetURL && typeof userInfo.resetURL == 'string' ? userInfo.resetURL.trim() : null

        if (!email || email.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the email parameter.`))))
            return
        }
        if (!displayName || displayName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the displayName parameter.`))))
            return
        }
        if (!resetURL || resetURL.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the resetURL parameter.`))))
            return
        }

        let mergeTags = [
            {name:'USER_DISPLAY_NAME', content: displayName},
            {name:'RESET_URL', content: resetURL}
        ]

        TCMailerService.sendMandrillEmailTemplate(
            {
                templateName:mandrillTemplate.resetPassword,
                email:email,
                displayName:displayName,
                mergeTags:mergeTags
            },
            function(err, result) {
                if (err) {
                    callback(err)
                } else {
                    callback(null, result)
                }
            }
        )        
    }

	//	email
	//		Who the email will be sent to
	//	displayName
	//		The display name of the user who the email will be sent to
	//	purchaseDate
	//		A Unix timestamp of the purchase date
	//	cardType
	//		The card brand, e.g., Visa, MasterCard, AMEX, etc.
	//	last4
	//		The last four digits of the credit card used to make the purchase
	//	subscriptionType
	//		One of constants.SubscriptionType values
	//	purchaseAmount
	//		The amount of hard-earned cash that they graciously just spent on
    //      Todo Cloud specified in USD.
    //  newExpirationDate
    //      A Unix timestamp of the next expiration date
    static sendPremierAccountPurchaseReceipt(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const email = params.email && typeof params.email == 'string' ? params.email.trim() : null
        const displayName = params.displayName && typeof params.displayName == 'string' ? params.displayName.trim() : null
        const purchaseDate = params.purchaseDate ? params.purchaseDate : null
        const cardType = params.cardType && typeof params.cardType == 'string' ? params.cardType.trim() : null
        const last4 = params.last4 && typeof params.last4 == 'string' ? params.last4.trim() : null
        const subscriptionType = params.subscriptionType != undefined ? params.subscriptionType : null
        const purchaseAmount = params.purchaseAmount != undefined ? params.purchaseAmount : null
        const newExpirationDate = params.newExpirationDate != undefined ? params.newExpirationDate : null
        
        if (!email || email.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the email parameter.`))))
            return
        }
        if (!displayName || displayName.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the displayName parameter.`))))
            return
        }
        if (!purchaseDate) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the purchaseDate parameter.`))))
            return
        }
        if (!cardType || cardType.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the cardType parameter.`))))
            return
        }
        if (!last4 || last4.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the last4 parameter.`))))
            return
        }
        if (!subscriptionType) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the subscriptionType parameter.`))))
            return
        }
        if (!purchaseAmount) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the purchaseAmount parameter.`))))
            return
        }

        if ((subscriptionType != constants.SubscriptionType.Month) && (subscriptionType != constants.SubscriptionType.Year)) {
            completion(new Error(JSON.stringify(errors.customError(errors.invalidParameters, `Invalid subscriptionType specified.`))))
            return
        }

        let accountType = `Monthly` // TO-DO: Needs localization
        if (subscriptionType == constants.SubscriptionType.Year) {
            accountType = `Yearly`  // TO-DO: Needs localization
        }

        // Convert to a human-readable payment date
        const paymentDate = moment.unix(purchaseDate).format(`LL`)
        const paymentMethod = `${cardType} XXXX-XXXX-XXXX-${last4}`
        const newExpirationString = newExpirationDate ? moment.unix(newExpirationDate).format(`LL`) : "-"
        const termsURL = `${process.env.WEBAPP_BASE_URL}/terms`

        // Show the purchase amount in USD
        const purchaseAmountString = `$ ${purchaseAmount} USD`

        let mergeTags = [
            {name:'USER_DISPLAY_NAME', content: displayName},
            {name:'USER_EMAIL_ADDRESS', content: email},
            {name:'ACCOUNT_TYPE', content: accountType},
            {name:'PAYMENT_DATE', content: paymentDate},
            {name:'PAYMENT_METHOD', content: paymentMethod},
            {name:'NEW_EXPIRATION_DATE', content: newExpirationString},
            {name:'PURCHASE_AMOUNT', content: purchaseAmountString},
            {name:'TERMS_URL', content: termsURL}
        ]

        TCMailerService.sendMandrillEmailTemplate(
            {
                templateName:mandrillTemplate.subscriptionPurchaseReceipt,
                email:email,
                displayName:displayName,
                mergeTags:mergeTags
            },
            function(err, result) {
                if (err) {
                    completion(err)
                } else {
                    completion(null, result)
                }
            }
        )
    }

    static sendTeamPurchaseReceipt(params, completion) {
        logger.debug(`TO-DO: Implement TCMailerService.sendTeamPurchaseReceipt()`)
        completion(null, true)
    }

    static sendSubscriptionUpdateErrorNotification(params, completion) {
        if (!params) {
            completion(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        const subscriptionType = params.subscriptionType && typeof params.subscriptionType == 'string' ? params.subscriptionType.trim() : null
        const subscriptionid = params.subscriptionid && typeof params.subscriptionid == 'string' ? params.subscriptionid.trim() : null
        const newExpirationDate = params.newExpirationDate

        if (!subscriptionType || subscriptionType.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the subscriptionType parameter.`))))
            return
        }
        if (!subscriptionid || subscriptionid.length == 0) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the subscriptionid parameter.`))))
            return
        }
        if (!newExpirationDate) {
            completion(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the newExpirationDate parameter.`))))
            return
        }

        const message = {
            html: `<p>The system (${process.env.WEB_BASE_URL}) just detected that a user purchased a subscription (${subscriptionType}) but the subscription table could not be updated. The user's subscription (${subscriptionid}) expiration date should be set to: ${newExpirationDate}</p>`,
            subject: `[URGENT: ${process.env.WEB_BASE_URL}] Todo Cloud Subscription Purchase Error`,
            from_email: `no-reply@todo-cloud.com`,
            from_name: `Todo Cloud`,
            to: [{
                email: `support@appigo.com`,
                name: `Todo Cloud Support`,
                type: `to`
            }],
            headers: {
                "Reply-To": `support@appigo.com`
            },
            important: true,        // deliver ahead of non-important emails
            track_opens: false,
            track_clicks: false,
            auto_text: true         // automatically generate a text version
        }
        mandrillClient.messages.send(
            {
                message: message,
                async: true    // Return from the call nearly immediately to reduce the amount of time used in Amazon Lambda
            },
            function(result) {
                let status = result[0].status
                if (status && status === 'sent') {
                    // Success!
                    completion(null, true)
                } else if (status && status === 'queued') {
                    logger.debug(`Service email queued.`)
                    completion(null, true)
                } else if (status) {
                    logger.debug(`Service email failed to send via Mandrill with status: ${status}`)
                    completion(null, false)
                } else {
                    logger.debug(`Failed to send service email for an unknown reason.`)
                }
            },
            function(err) {
                logger.debug(`A Mandrill error occurred: ${err.name} - ${err.message}`)
                completion(new Error(JSON.stringify(errors.customError(errors.mandrillError, `${err.name}: ${err.message}`))))
            }
        )
    }

    static sendMandrillEmailTemplate(userInfo, callback) {
        if (!userInfo) {
            callback(new Error(JSON.stringify(errors.missingParameters)))
            return
        }

        let templateName = userInfo.templateName && typeof userInfo.templateName == 'string' ? userInfo.templateName.trim() : null
        let email = userInfo.email && typeof userInfo.email == 'string' ? userInfo.email.trim() : null
        let displayName = userInfo.displayName && typeof userInfo.displayName == 'string' ? userInfo.displayName.trim() : null
        let subject = userInfo.subject && typeof userInfo.subject == 'string' ? userInfo.subject.trim() : null
        let fromEmail = userInfo.fromEmail && typeof userInfo.fromEmail == 'string' ? userInfo.fromEmail.trim() : null
        let fromName = userInfo.fromName && typeof userInfo.fromName == 'string' ? userInfo.fromName.trim() : null
        let replyToAddress = userInfo.replyToAddress && typeof userInfo.replyToAddress == 'string' ? userInfo.replyToAddress.trim() : null
        let mergeTags = userInfo.mergeTags !== undefined ? userInfo.mergeTags : null
        let sendAsync = userInfo.async !== undefined ? userInfo.async : true // Set the default to true to consume less time in Lambda functions (cost us less)

        if (!templateName || templateName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the templateName parameter.`))))
            return
        }
        if (!email || email.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the email parameter.`))))
            return
        }
        if (!displayName || displayName.length == 0) {
            callback(new Error(JSON.stringify(errors.customError(errors.missingParameters, `Missing the displayName parameter.`))))
            return
        }

        let message = {
            to: [{
                email:email,
                name:displayName
            }]
        }

        if (subject) {
            message['subject'] = subject
        }

        if (fromEmail) {
            message['from_email'] = fromEmail
        }

        if (fromName) {
            message['from_name'] = fromName
        }

        if (replyToAddress) {
            message['headers'] = {'Reply-To': replyToAddress}
        }

        if (mergeTags) {
            message['merge_vars'] = [{
                rcpt: email,
                vars: mergeTags
            }]
        }

        let templateContent = ''

        mandrillClient.messages.sendTemplate(
            {
                template_name: userInfo.templateName,
                template_content: templateContent,
                message: message,
                async: sendAsync
            },
            function(result) {
                let status = result[0].status
                if (status && status === 'sent') {
                    // Success!
                    callback(null, true)
                } else if (status && status === 'queued') {
                    logger.debug(`Email queued to ${email}.`)
                    callback(null, true)
                } else if (status) {
                    logger.debug(`Email failed to send via Mandrill to ${email} with status: ${status}`)
                    callback(null, false)
                } else {
                    logger.debug(`Failed to send email to ${email} for unknown reason.`)
                }
            },
            function(err) {
                logger.debug(`A Mandrill error occurred: ${err.name} - ${err.message}`)
                callback(new Error(JSON.stringify(errors.customError(errors.mandrillError, `${err.name}: ${err.message}`))))
            }
        )
    }    
}

module.exports = TCMailerService

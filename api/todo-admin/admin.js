#!/usr/bin/env node

// Libraries needed
const async         = require('async')
const chalk         = require('chalk')
const clear         = require('clear')
const CLI           = require('clui')
const Configstore   = require('configstore')
const figlet        = require('figlet')
const fs            = require('fs')
const inquirer      = require('inquirer')
const path          = require('path')
const pkg           = require('./package.json')

// Our Libraries
const auth          = require('./lib/authentication')
const androidDB     = require('./lib/android-db-restore')
const accountAdmin  = require('./lib/account-admin')

const conf          = new Configstore(pkg.name)
const Spinner       = CLI.Spinner

process.env.TC_API_KEY = 'QRtZwFjB9patdBaafpWdq1678UohLyfM3rFl2CND'

const DeploymentType = {
    test:       'test',
    beta:       'beta',
    production: 'prod'
}

const AdminAction = {
    restoreAndroidDB:   'restoreAndroidDB',
    exportUserData:     'exportUserData',
    downloadExportData: 'downloadExportData'
}

// var todo          = require('todo')(process.env.TC_API_KEY, promptForCredentials)
var todo = require('../todo-sdk/lib/todo')(process.env.TC_API_KEY, promptForCredentials)

// Main script begins here

// clear()
console.log(
    chalk.blueBright(
        figlet.textSync('Todo Admin', { horizontalLayout: 'full'})
    )
)

async.waterfall([
    function(callback) {
        // First find out what environment the admin wants to work in
        selectDeployment(function(deploymentSelection) {
            switch(deploymentSelection.deployment) {
                case DeploymentType.test: {
                    process.env.TC_API_URL = `https://api.todo-cloud.com/test-v1`
                    break
                }
                case DeploymentType.beta: {
                    process.env.TC_API_URL = `https://api.todo-cloud.com/beta-v1`
                    break
                }
                case DeploymentType.production: {
                    process.env.TC_API_URL = `https://api.todo-cloud.com/v1`
                    break
                }
                
                default: {
                    process.exit(1)
                    break
                }
            }
        
            todo.setApiUrl(process.env.TC_API_URL)
            process.env.TC_DEPLOYMENT_TYPE = deploymentSelection.deployment
            callback(null, deploymentSelection.deployment)
        })
    },
    function(deploymentType, callback) {
        // Make sure the Todo API is configured
        // const spinner = new Spinner('Configuring Todo, please wait...')
        // spinner.start()
        todo.configure(function(err, result) {
            // spinner.stop()
            if (err) {
                callback(err)
            } else {
                callback(null, deploymentType)
            }
        })
    },
    function(jwtToken, callback) {
        // Present main menu
        var showMainMenu = true
        async.whilst(function() {
            return showMainMenu
        }, function(whilstCallback) {
            runMainMenu(function(err, shouldExit) {
                if (err) {
                    whilstCallback(err)
                } else {
                    showMainMenu = !shouldExit
                    whilstCallback(null)
                }
            })
        }, function(err) {
            callback(err)
        })
    },
    function(callback) {
        // All done!
        process.exit(0)
    }
],
function(err) {
    if (err) {
        console.log(`An error occurred: ${err}`)
        process.exit(1)
    }
})

function selectDeployment(completion) {
    var questions = [
        {
            name: 'deployment',
            type: 'list',
            message: 'Select the target environment:',
            default: 'test',
            choices: [
                { name: 'Test (pori.todo-cloud.com)', value: DeploymentType.test },
                { name: 'Beta (beta.todo-cloud.com)', value: DeploymentType.beta },
                { name: 'Production (www.todo-cloud.com)', value: DeploymentType.production },
                new inquirer.Separator(),
                { name: 'Exit', value: 'exit' }
            ]
        }
    ]

    inquirer.prompt(questions)
        .then(completion)
        .catch(reason => {
            console.log(`Error determining stage: ${reason}`)
            process.abort()
        })
}

function runMainMenu(completion) {
    const options = [
        {
            type: 'rawlist',
            name: 'menuSelection',
            message: 'What do you want to do?',
            choices: [
                { name: 'Restore an Android Database', value: AdminAction.restoreAndroidDB },
                { name: 'Export User Data to S3', value: AdminAction.exportUserData },
                { name: 'Download Exported User Data from S3', value: AdminAction.downloadExportData },
                new inquirer.Separator(),
                { name: 'Exit', value: 'exit' }
            ]
        }
    ]
    inquirer.prompt(options)
    .then(answer => {
        var shouldExitApp = answer.menuSelection == 'exit'
        if (shouldExitApp) {
            completion(null, shouldExitApp)
            return
        }
        runAction(answer.menuSelection, function(err, success) {
            if (err) {
                completion(err)
            } else {
                completion(null, false) // Don't exit, but return to the main menu
            }
        })
    })
    .catch(reason => {
        console.log(`Error selecting main menu item: ${reason}`)
        process.exit(1)
    })
}

function runAction(actionName, completion) {
    switch(actionName) {
        case AdminAction.restoreAndroidDB: {
            androidDB.dbRestoreAction(todo, function(err, result) {
                completion(err, result)
            })
            break
        }
        case AdminAction.exportUserData: {
            accountAdmin.exportUserData(todo, function(err, result) {
                completion(err, result)
            })
            break
        }
        case AdminAction.downloadExportData: {
            accountAdmin.downloadExportData(todo, function(err, result) {
                completion(err, result)
            })
            break
        }
        default: {
            console.log(`TODO: Implement the action for: ${actionName}`)
            completion(null, true)
        }
    }
}

function promptForCredentials(completion) {
    var questions = [
        {
            name: 'username',
            type: 'input',
            message: 'Enter your Todo Cloud username (email address):',
            validate: function(value) {
                if (value.length) {
                    return true
                } else {
                    return 'Please enter your username.'
                }
            }
        },
        {
            name: 'password',
            type: 'password',
            message: 'Enter your password:',
            validate: function(value) {
                if (value.length) {
                    return true
                } else {
                    return 'Please enter your password.'
                }
            }
        }
    ]

    inquirer.prompt(questions)
        .then(credentials => {
            completion(null, credentials)
        })
        .catch(reason => {
            completion(new Error(`Error prompting for credentials: ${reason}`))
        })
}
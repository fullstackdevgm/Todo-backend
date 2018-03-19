

// Third party libraries
const async         = require('async')
const chalk         = require('chalk')
const CLI           = require('clui')
const Database      = require('better-sqlite3')
const inquirer      = require('inquirer')
const Spinner       = CLI.Spinner
// const Progress      = CLI.Progress;

// Built-in Node.js libraries
const fs            = require('fs')
const os            = require('os')
const path          = require('path')

const AWS = require('aws-sdk')
// Use the "appigo" profile from ~/.aws/credentials
var credentials = new AWS.SharedIniFileCredentials({profile: 'appigo'})
AWS.config.credentials = credentials

// Configure the global region BEFORE instantiating anything
// else from the AWS SDK for it to work properly
AWS.config.update({region: 'us-east-1'})
const s3 = new AWS.S3()

module.exports = {
    exportUserData : function(todoApi, completion) {
        async.waterfall([
            function(callback) {
                promptForCustomerUsername(function(err, customerUsername) {
                    if (err) {
                        callback(err)
                    } else {
                        console.log(
                            chalk.default.yellowBright(
                                `Customer username: ${customerUsername}`
                            )
                        )
                        callback(null, customerUsername)
                    }
                })
            },
            function(customerUsername, callback) {
                // Export the user's database
                const spinner = new Spinner(`Exporting the customer data: ${customerUsername}`)
                spinner.start()
                todoApi.accountExport(customerUsername, function(err, result) {
                    spinner.stop()
                    if (err) {
                        callback(err)
                    } else {
                        console.log(chalk.default.yellowBright(`Export completed: ${customerUsername}`))
                        if (result.success) {
                            result.statements.forEach(statement => {
                                console.log(statement)
                            })
                        }

                        callback(null, result.success)
                    }
                })
            }
        ], function(err, result) {
            if (err) {
                if (err.message == 'exit') {
                    completion(null, false)
                } else {
                    completion(err)
                }
            } else {
                console.log(
                    chalk.default.yellowBright(
                        `NOTE: The next step is a manual one. Copy the statements above into your clipboard, shell in to the production Aurora RDS database, and execute these statements to export the customer's data.`
                    )
                )
        
                completion(null, result)
            }
        })
    },

    downloadExportData : function(todoApi, completion) {
        // 1. Show the list of customer data available on S3
        // 2. Prompt the user to select which customer data to prepare
        const bucket = `private.appigo.com`
        const prefix = `beta/`

        async.waterfall([
            function(callback) {
                let continuationToken = null
                let folderNames = []

                const spinner = new Spinner(`Getting list of exported folders...`)
                spinner.start()

                async.doWhilst(function(whilstCallback) {
                    let listParams = {
                        Bucket: bucket,
                        Prefix: prefix
                    }
                    if (continuationToken) {
                        listParams["ContinuationToken"] = continuationToken
                    }
                    s3.listObjectsV2(listParams, function(err, result) {
                        if (err) {
                            callback(err)
                        } else {
                            if (result.Contents) {
                                result.Contents.forEach(item => {
                                    const key = item.Key
                                    const prefixRemoved = key.substr(prefix.length)
                                    const folderName = prefixRemoved.split('/')[0]
                                    if (folderNames.indexOf(folderName) == -1) {
                                        folderNames.push(folderName)
                                    }
                                })
                                continuationToken = result.IsTruncated ? result.NextContinuationToken : null
                            } else {
                                continuationToken = null
                            }
                            whilstCallback(null)
                        }
                    })
                }, function() {
                    return continuationToken != null
                }, function(err) {
                    spinner.stop()
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, folderNames)
                    }
                })
            },
            function(folderNames, callback) {
                let choices = folderNames.map(folderName => {
                    return { name: folderName, value: folderName }
                })
                choices.push(new inquirer.Separator())
                choices.push({ name: 'Exit', value: 'exit' })
                const questions = [
                    {
                        name: 'folderName',
                        type: 'rawlist',
                        message: 'Select the customer folder to download:',
                        choices: choices
                    }
                ]
                inquirer.prompt(questions)
                .then(answer => {
                    if (answer.folderName == 'exit') {
                        callback(new Error('exit'))
                    } else {
                        callback(null, answer.folderName)
                    }
                })
                .catch(reason => {
                    console.log(`Error selecting customer folder name: ${reason}`)
                    callback(new Error(reason))
                })
            },
            function(folderName, callback) {
                // Now get a list of all the files
                console.log(`Selected folder: ${folderName}`)

                // If an existing local directory already exist with the same name,
                // delete it.
                deleteFolderRecursive(`./${folderName}`)
                fs.mkdirSync(`./${folderName}`)

                // Get a list of objects to download
                const folderPrefix = `${prefix}${folderName}`

                let continuationToken = null
                let keysToDownload = []

                const spinner = new Spinner(`Getting list of files to download...`)
                spinner.start()
                async.doWhilst(function(whilstCallback) {
                    let listParams = {
                        Bucket: bucket,
                        Prefix: folderPrefix
                    }
                    if (continuationToken) {
                        listParams["ContinuationToken"] = continuationToken
                    }
                    s3.listObjectsV2(listParams, function(err, result) {
                        if (err) {
                            callback(err)
                        } else {
                            if (result.Contents) {
                                result.Contents.forEach(item => {
                                    const key = item.Key
                                    if (item.Size > 0) {
                                        keysToDownload.push(key)
                                    }
                                })
                                continuationToken = result.IsTruncated ? result.NextContinuationToken : null
                            } else {
                                continuationToken = null
                            }
                            whilstCallback(null)
                        }
                    })
                }, function() {
                    return continuationToken != null
                }, function(err) {
                    spinner.stop()
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, folderName, keysToDownload)
                    }
                })
            },
            function(folderName, keysToDownload, callback) {
                // const spinner = new Spinner(`Downloading the customer MySQL data into: ./${folderName}`)
                // spinner.start()
                let importFile = fs.createWriteStream(`./${folderName}/input_sql.txt`)
                async.eachSeries(keysToDownload,
                function(keyToDownload, eachCallback) {
                    const getParams = {
                        Bucket: bucket,
                        Key: keyToDownload
                    }
                    let fileName = keyToDownload.substr(keyToDownload.lastIndexOf("/") + 1)
                    let outFile = fs.createWriteStream(`./${folderName}/${fileName}`)

                    // Figure out what table this file should be importing to
                    let tableName = fileName.substring(0, fileName.indexOf('.'))
                    if (tableName.startsWith("tdo_taskitos")) {
                        tableName = "tdo_taskitos" // needed to remove the list id from the name
                    }

                    const spinner = new Spinner(`Downloading: ./${folderName}/${fileName}`)
                    spinner.start()
                    s3.getObject(getParams)
                    .on('httpData', function(chunk) {
                        outFile.write(chunk)
                    })
                    .on('httpDone', function() {
                        spinner.stop()
                        outFile.end()

                        // Add an import statement
                        importFile.write(`LOAD DATA INFILE '/var/lib/mysql-files/${fileName}' INTO TABLE ${tableName};\n`)

                        eachCallback(null)
                    })
                    .send()
                }, function(err) {
                    importFile.end()
                    if (err) {
                        callback(err)
                    } else {
                        callback(null, folderName)
                    }
                })
            }
        ], function(err, folderName) {
            if (err) {
                if (err.message == 'exit') {
                    completion(null, false)
                } else {
                    completion(err)
                }
            } else {
                console.log(
                    chalk.default.yellowBright(
                        `Successfully downloaded files to: ./${folderName}/`
                    )
                )
                completion(null, true)
            }
        })
    }
}

function promptForCustomerUsername(completion) {
    const questions = [
        {
            name: 'username',
            type: 'input',
            message: 'Customer username (email address):',
            validate: function(value) {
                if (value.length) {
                    return true
                } else {
                    return 'Please enter a customer username.'
                }
            }
        }
    ]
    inquirer.prompt(questions)
    .then(answer => {
        completion(null, answer.username)
    })
    .catch(reason => {
        completion(new Error(`Error getting a customer username: ${reason}`))
    })
}

function deleteFolderRecursive(path) {
    if (fs.existsSync(path)) {
      fs.readdirSync(path).forEach(function(file, index) {
        var curPath = path + "/" + file
        if (fs.lstatSync(curPath).isDirectory()) { // recurse
          deleteFolderRecursive(curPath)
        } else { // delete file
          fs.unlinkSync(curPath)
        }
      });
      fs.rmdirSync(path)
    }
  }
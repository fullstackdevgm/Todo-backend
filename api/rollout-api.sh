#!/bin/bash

# Some day we either need to figure out how to get bash to work with JSON,
# or we need to convert this script to a Node.js script so we can put the
# function definitions in JSON:
#testFunctions=$(cat <<EOF
#[
#    {
#        "name": "assignTag",
#        "handler": "tags.assignTag",
#        "description": "Assigns a tag to a task."
#    },
#    {
#        "name": "authenticate",
#        "handler": "authenticate.authenticate",
#        "description": "Returns a JWT for authorization."
#    }
#]
#EOF
#)

# NOTE: Please keep the function names alphabetized
functionNames=(
    'acceptInvitation'
    'accountExport'
    'assignTag'
    'authenticate'
    'authorize'
    'changeRole'
    'checkForUpdates'
    'completeChecklistItems'
    'completeTasks'
    'createAccount'
    'createChecklistItem'
    'createComment'
    'createList'
    'createNotification'
    'createSmartList'
    'createTag'
    'createTask'
    'deleteAccount'
    'deleteChecklistItem'
    'deleteComment'
    'deleteInvitation'
    'deleteList'
    'deleteNotification'
    'deleteSmartList'
    'deleteTag'
    'deleteTask'
    'downgradeSubscription'
    'impersonate'
    'getAccountInfo'
    'getAllTags'
    'getComment'
    'getCommentsForTask'
    'getInvitation'
    'getInvitations'
    'getInvitationsForList'
    'getLatestSystemNotification'
    'getList'
    'getMembersForAllLists'
    'getMembersForList'
    'getNotification'
    'getNotificationsForTask'
    'getNotificationsForUser'
    'getPaymentHistory'
    'getProfileImageUploadURLs'
    'getSmartList'
    'getSmartLists'
    'getSubscription'
    'getSubtaskCount'
    'getSubtasks'
    'getTag'
    'getTagsForTask'
    'getTask'
    'getTaskCountByDateRange'
    'getTaskCounts'
    'getTaskitosForChecklist'
    'getTasksForList'
    'getTasksForSearchText'
    'getTasksForSmartList'
    'getUserSettings'
    'getLists'
    'passwordReset'
    'purchaseSubscription'
    'refreshAuthentication'
    'removeMembership'
    'removeTagAssignment'
    'requestPasswordReset'
    'resendInvitation'
    'resendReceipt'
    'resendVerificationEmail'
    'saveUploadedProfileImages'
    'sendListInvitation'
    'taskCountForList'
    'uncompleteChecklistItems'
    'uncompleteTasks'
    'updateAccount'
    'updateChecklistItem'
    'updateComment'
    'updateInvitationRole'
    'updateList'
    'updateNotification'
    'updatePassword'
    'updateSmartList'
    'updateTag'
    'updateTask'
    'updateUserSettings'
    'verifyEmail'
)

functionHandlers=(
    'invitations.acceptInvitation'
    'account.accountExport'
    'tags.assignTag'
    'authenticate.authenticate'
    'authorize.handler'
    'list-member.changeRole'
    'authenticate.checkForUpdates'
    'taskitos.completeTaskitos'
    'tasks.completeTasks'
    'authenticate.createAccount'
    'taskitos.createTaskito'
    'comments.createComment'
    'lists.createList'
    'task-notifications.createNotification'
    'smart-lists.createSmartList'
    'tags.createTag'
    'tasks.createTask'
    'account.deleteAccount'
    'taskitos.deleteTaskito'
    'comments.deleteComment'
    'invitations.deleteInvitation'
    'lists.deleteList'
    'task-notifications.deleteNotification'
    'smart-lists.deleteSmartList'
    'tags.deleteTag'
    'tasks.deleteTask'
    'subscription.downgradeSubscription'
    'authenticate.impersonate'
    'account.getAccountInfo'
    'tags.getAllTags'
    'comments.getComment'
    'comments.getCommentsForTask'
    'invitations.getInvitation'
    'invitations.getInvitations'
    'invitations.getInvitationsForList'
    'system-notification.getLatestSystemNotification'
    'lists.getList'
    'list-member.getMembersForAllLists'
    'list-member.getMembersForList'
    'task-notifications.getNotification'
    'task-notifications.getNotificationsForTask'
    'task-notifications.getNotificationsForUser'
    'subscription.getPaymentHistory'
    'account.getProfileImageUploadURLs'
    'smart-lists.getSmartList'
    'smart-lists.getSmartLists'
    'subscription.getSubscription'
    'tasks.getSubtaskCount'
    'tasks.getSubtasks'
    'tags.getTag'
    'tags.getTagsForTask'
    'tasks.getTask'
    'tasks.getTaskCountByDateRange'
    'tasks.getTaskCounts'
    'taskitos.getTaskitosForChecklist'
    'tasks.getTasksForList'
    'tasks.getTasksForSearchText'
    'tasks.getTasksForSmartList'
    'user-settings.getUserSettings'
    'lists.getLists'
    'account.passwordReset'
    'subscription.purchaseSubscription'
    'authenticate.refreshAuthentication'
    'list-member.removeMembership'
    'tags.removeTagAssignment'
    'account.requestResetPassword'
    'invitations.resendInvitation'
    'subscription.resendReceipt'
    'account.resendVerificationEmail'
    'account.saveUploadedProfileImages'
    'invitations.sendListInvitation'
    'lists.taskCountForList'
    'taskitos.uncompleteTaskitos'
    'tasks.uncompleteTasks'
    'account.updateAccount'
    'taskitos.updateTaskito'
    'comments.updateComment'
    'invitations.updateInvitationRole'
    'lists.updateList'
    'task-notifications.updateNotification'
    'account.updatePassword'
    'smart-lists.updateSmartList'
    'tags.updateTag'
    'tasks.updateTask'
    'user-settings.updateUserSettings'
    'account.verifyEmail'
)

functionDescriptions=(
    'Accept a list invitation for an authenticated user.'
    'Export a specific customer MySQL data to Amazon S3 for developer debugging.'
    'Assigns a tag to a task.'
    'Returns a JWT for authorization.'
    'Function that handles JWT authorization.'
    'Updates the role of the specified member, if the current user is a list owner.'
    'Checks for an available app update.'
    'Complete all the specified checklist items.'
    'Completes all the specified tasks.'
    'Create a new Todo Cloud user account.'
    'Create a new checklist item.'
    'Create a new comment.'
    'Create a new task list.'
    'Create a new task notification'
    'Create a new smart list'
    'Create a new tag'
    'Create a new task'
    'Delete an account.'
    'Delete a checklist item.'
    'Delete a comment.'
    'Deletes a list invitation.'
    'Deletes a task list.'
    'Deletes a task notification.'
    'Deletes a smart list.'
    'Deletes a tag.'
    'Delete a task.'
    'Downgrade a premium subscription to a free account.'
    'Impersonate an account (available to root-level administrators only)'
    'Return info about the authenticated account.'
    'Returns all tags associated with a user account.'
    'Get the specified comment.'
    'Returns all the comments associated with a task.'
    'Get a list invitation with related information.'
    'Get all invitations for a user.'
    'Get all the invitations from a user for a specific list.'
    'Get the latest system notification.'
    'Reads a specific list.'
    'Get info about all the users that share a list with the current user.'
    'Get the members for a list with their roles.'
    'Get a specified task notification.'
    'Get all notifications for a task.'
    'Get the soon upcoming notifications for a user.'
    'Returns the purchase history for a user.'
    'Request an upload URL for a profile image.'
    'Returns a single smart list based on listid.'
    'Returns all  smart lists for a userid.'
    'Get authenticated user subscription information.'
    'Get the subtask count for a parent task.'
    'Get the subtasks for a project.'
    'Get the specified tag.'
    'Get tags for a task.'
    'Get the specified task.'
    'Returns the number of tasks per day from the specified date range.'
    'Computes task counts for smart lists and lists that belong to a user.'
    'Get all taskitos for the specified checklist.'
    'Get the tasks for a list.'
    'Get tasks based on a search term.'
    'Get the tasks for a smart list.'
    'Read user settings for an authenticated user.'
    'Return all the lists for the authenticated user.'
    'Resets a password on a user account.'
    'Process a purchase of a premium account.'
    'Refreshes a connections authentication token.'
    'Removes a list membership after checking that it is allowed.'
    'Removes a tag assignment.'
    'Request a password reset email.'
    'Resend the email notification for specified list invitation.'
    'Resend a specific purchase receipt.'
    'Request a new email verification email.'
    'Processes an uploaded profile image and saves it to a user account.'
    'Create and send an invitation to join a list.'
    'Gets the task and overdue counts for a list.'
    'Mark all the specified checklist items as uncomplete.'
    'Mark all the specified tasks as uncomplete.'
    'Update account information for a Todo Cloud user.'
    'Update a checklist item.'
    'Update a comment for the specified id.'
    'Update the role of the specified invitation.'
    'Update list and list settings for list with specified id.'
    'Update a task notification.'
    'Update password.'
    'Update a smart list for specified listid.'
    'Update a tag with the given id.'
    'Update a task.'
    'Update user settings for an authenticated user.'
    'Verify an email address.'
)

zipS3Bucket="uploads.todo-cloud.com"
zipS3Folder="lambda-functions"
zipFileName="lambda.zip"
apiJSONFileName="todo-cloud-api-v1.json"

lambdaFunctionRole="arn:aws:iam::398938165940:role/service-role/todo-api-lambda-function-role"
vpcConfig="{\"SubnetIds\": [\"subnet-48266765\",\"subnet-8a92a5c3\"], \"SecurityGroupIds\": [\"sg-901424f6\", \"sg-4d5f8831\"]}"

# Environment variables are now stored in a file that is not committed
# to our GitHub project.
source ~/.appigo/todo-cloud-env-vars.sh

# Specify custom timeouts for functions by creating variables with "Timeout"
# appended to their name.
authenticate=10
createAccountTimeout=10

# Setting all of the functions that have to call out to a 3rd party service
# to send a transactional email to have more time.
requestPasswordResetTimeout=10
resendVerificationEmailTimeout=10

# Since the purchase process has to communicate with Stripe AND Mandrill,
# make sure there is plenty of time for the process to run.
purchaseSubscriptionTimeout=10

# The deleteTask() operation may affect a LARGE number of subtasks if the
# main task is a project/checklist. Give it a full 60 seconds.
#deleteTaskTimeout=60

# The completeTask() operation may affect a LARGE number of subtasks if
# any of the specified tasks are a project/checklist. Give this function
# a full 60 seconds to complete.
#completeTasksTimeout=60

# The sendListInvitation and resendInvitation functions have to reach out to a 3rd party API which may 
# take additional time.
accountExport=10
createTaskTimeout=10
sendListInvitationTimeout=10
resendInvitationTimeout=10

getPaymentHistoryTimeout=10
getTaskCountsTimeout=5
completeChecklistItems=10

# Specify custom memory sizes to use. The larger the memory size, the faster
# the CPU that will be allocated to the function.
#updateTaskMemorySize=256
#getTaskMemorySize=256
accountExport=1024
createTaskMemorySize=1024
completeTasksMemorySize=1024
uncompleteTasksMemorySize=1024
completeChecklistItems=1024

authenticateMemorySize=256
changeRoleMemorySize=1024
deleteAccountMemorySize=1024
deleteListMemorySize=1024
deleteTaskMemorySize=1024
downgradeSubscriptionMemorySize=1024
getAccountInfoMemorySize=1024
getListsMemorySize=1024
getPaymentHistoryMemorySize=1024
getSmartListsMemorySize=1024
getTaskCountByDateRangeMemorySize=1024
getTaskCountsMemorySize=2048
getTasksForListMemorySize=1024
getTasksForSmartListMemorySize=1024
purchaseSubscriptionMemorySize=1024
resendReceiptMemorySize=512
saveUploadedProfileImagesMemorySize=1024

function chooseDeploymentType {
    echo ""
    echo "1. Internal Testing (api.todo-cloud.com/test-v1)"
    echo "2. Beta (api.todo-cloud.com/beta-v1)"
    echo "3. Production (api.todo-cloud.com/v1)"
    echo ""
    read -p "Select your deployment type (<Enter> to return to main menu): " -n 1 -s selectedOption
    echo ""

    if [ -z "$selectedOption" ]
    then
        deploymentType=""
        envVars=""
        return
    fi
    case $selectedOption in
    1)
        deploymentType="TEST"
        envVars="$testEnvironmentVariables"
        ;;
    2)
        deploymentType="BETA"
        envVars="$betaEnvironmentVariables"
        ;;
    3)
        deploymentType="PROD"
        envVars="$prodEnvironmentVariables"
        ;;
    *)
        deploymentType=""
        envVars=""
        ;;
    esac

    echo ""
    echo "Selected deployment type: $deploymentType"
}

function packageFunctions {
    echo ""
    echo "Packaging up the Lambda functions..."
    pushd functions/
    rm -rfv "../$zipFileName"
    zip -ur9 "../$zipFileName" *
    popd
    echo "Node.js functions packaged into: $zipFileName"

    # Now upload the ZIP file to Amazon S3 so that it's ready for use
    echo "Uploading $zipFileName to s3://$zipS3Bucket/$zipS3Folder/..."
    aws s3 cp $zipFileName s3://$zipS3Bucket/$zipS3Folder/$zipFileName --profile appigo
    if [ $? -eq 0 ]
    then
        echo "Sucessfully uploaded $zipFileName to: s3://$zipS3Bucket/$zipS3Folder/$zipFileName"
    else
        echo "Failed to upload $zipFileName to s3://$zipS3Bucket/$zipS3Folder/$zipFileName"
        exit 1
    fi
}

function createAlias {
    local functionName=$1
    local aliasName=$2

    echo "createAlias: $functionName ($aliasName) ..."

    aws lambda create-alias \
    --function-name "$functionName" \
    --function-version "\$LATEST" \
    --name "$aliasName" \
    --profile appigo
}

function updateAlias {
    local functionName=$1
    local aliasName=$2
    local functionVersion=$3

    echo "updateAlias: $functionName ($aliasName) Version: $functionVersion ..."

    aws lambda update-alias \
    --function-name "$functionName" \
    --function-version "$functionVersion" \
    --name "$aliasName" \
    --profile appigo
}

function grantAPIAccess {
    local functionName=$1
    local aliasName=$2

    echo "grantAPIAccess: $functionName:$aliasName ..."

    aws lambda add-permission \
    --function-name "$functionName" \
    --qualifier "$aliasName" \
    --statement-id api-gateway-access \
    --action "lambda:InvokeFunction" \
    --principal "apigateway.amazonaws.com" \
    --profile appigo
}

function createFunction {
    local functionName=$1
    local handlerName=$2
    local description=$3
    local envVars=$4    

    echo "createFunction: $functionName [$handlerName]: $description ..."

    aws lambda create-function \
    --function-name "$functionName" \
    --runtime "nodejs6.10" \
    --role "$lambdaFunctionRole" \
    --handler "$handlerName" \
    --description "$description" \
    --vpc-config "$vpcConfig" \
    --environment "$envVars" \
    --code "S3Bucket=$zipS3Bucket,S3Key=$zipS3Folder/$zipFileName" \
    --profile appigo

# This is the old way we used to do things when we didn't
# upload to S3 first. Leaving this in here just in case we
# need to quickly reference it.
#    --zip-file "fileb://$zipFileName"

    createAlias "$functionName" "TEST"
    createAlias "$functionName" "BETA"
    createAlias "$functionName" "PROD"

    grantAPIAccess "$functionName" "TEST"
    grantAPIAccess "$functionName" "BETA"
    grantAPIAccess "$functionName" "PROD"
}

function createFunctionWithPrompts {
    echo ""
    # Prompt for a new function name
    read -p "New function name (<Enter> to abort): " functionName
    if [ -z "$functionName" ]
    then
        return
    fi

    # Prompt for a new function handler
    read -p "Function handler (e.g., \"authenticate.createAccount\"): " handlerName
    if [ -z "$handlerName" ]
    then
        return
    fi

    # Prompt for a description
    read -p "Description: " description
    if [ -z "$description" ]
    then
        return
    fi

    envVars="$testEnvironmentVariables"

    echo ""
    echo "Summary:"
    echo ""
    echo "   Function name: $functionName"
    echo "Function handler: $handlerName"
    echo "     Description: $description"
    echo "VPC Config: $vpcConfig"
    echo "Role: $lambdaFunctionRole"
    echo "Environment: $envVars"
    echo ""

    read -p "Does everything look correct? (type \"yes\" to continue): " response

    if [ -z "$response" ]
    then
        return
    fi

    if [ "$response" != "yes" ]
    then
        return
    fi

    packageFunctions

    createFunction "$functionName" "$handlerName" "$description" "$envVars"
}

function createFunctionFromList {
    # Select a function
    for i in "${!functionNames[@]}"; do
        echo "$i. ${functionNames[$i]}"
    done

    read -p "Select function (<Enter> to return to main menu): " -s selectedFunction
    echo ""

    if [ -z "$selectedFunction" ]
    then
        return
    fi

    functionName="${functionNames[$selectedFunction]}"
    if [ -z "$functionName" ]
    then
        selectedFunction=""
        return
    fi

    functionHandler=${functionHandlers[$selectedFunction]}
    functionDescription=${functionDescriptions[$selectedFunction]}
    
    echo ""
    echo "Selected function: $functionName"

    chooseDeploymentType
    if [ -z "$deploymentType" ]
    then
        return
    fi
    
    packageFunctions

    # Create the new function
    createFunction "$functionName" "$functionHandler" "$functionDescription" "$testEnvironmentVariables"

    # Update Lambda function code
    updateFunction "$functionName"

    # Update the function configuration
    updateFunctionConfiguration "$functionName" "$envVars"

    publishFunction "$functionName"

    # Set the alias (version: TEST, BETA, PROD)
    updateAlias "$functionName" "$deploymentType" "$functionVersion"
}

function publishFunction {
    local functionName=$1

    echo "publishFunction: $functionName ..."

    aws lambda publish-version \
    --function-name "$functionName" \
    --profile appigo > /tmp/$functionName.publish.log

    # Parse the new function version
    functionVersion=`grep "Version" /tmp/$functionName.publish.log | cut -d "\"" -f4`

    echo "$functionName is now Version: $functionVersion"
}

function updateFunction {
    local functionName=$1

    echo "updateFunction: $functionName ..."

    aws lambda update-function-code \
    --function-name "$functionName" \
    --s3-bucket "$zipS3Bucket" \
    --s3-key "$zipS3Folder/$zipFileName" \
    --profile appigo

    # This is the old way we used to do things when we didn't
    # upload to S3 first. Leaving this in here just in case we
    # need to quickly reference it.
    #--zip-file "fileb://$zipFileName" 
}

function updateFunctionConfiguration {
    local functionName=$1
    local environmentVariables=$2

    # Update the function configuration
    local timeout=${functionName}Timeout
    local memorySize=${functionName}MemorySize

    timeout=${!timeout}
    memorySize=${!memorySize}

    if [ -z "$timeout" ]
    then
        timeout=3
    fi

    if [ -z "$memorySize" ]
    then
        memorySize=256
    fi

    echo "updateFunctionConfiguration: $functionName (Timeout: $timeout, Memory Size: $memorySize) ..."

    aws lambda update-function-configuration \
    --function-name "$functionName" \
    --runtime "nodejs6.10" \
    --timeout $timeout \
    --memory-size $memorySize \
    --role "$lambdaFunctionRole" \
    --vpc-config "$vpcConfig" \
    --environment "$environmentVariables" \
    --profile appigo

}

function updateFunctions {
    packageFunctions

    for functionName in ${functionNames[@]}; do
        # Update Lambda function code
       updateFunction "${functionName}"

        updateFunctionConfiguration "${functionName}" "$envVars"

        publishFunction "${functionName}"

        # Set the alias (version: TEST, BETA, PROD)
        updateAlias "${functionName}" "$deploymentType" "$functionVersion"
    done
}

function updateSingleFunction {
    # Select a function
    for i in "${!functionNames[@]}"; do
        echo "$i. ${functionNames[$i]}"
    done

    read -p "Select function (<Enter> to return to main menu): " -s selectedFunction
    echo ""

    if [ -z "$selectedFunction" ]
    then
        return
    fi

    functionName="${functionNames[$selectedFunction]}"
    if [ -z "$functionName" ]
    then
        selectedFunction=""
        return
    fi
        
    echo ""
    echo "Selected function: $functionName"

    chooseDeploymentType
    if [ -z "$deploymentType" ]
    then
        return
    fi
    
    packageFunctions

    # Update Lambda function code
    updateFunction "$functionName"

    updateFunctionConfiguration "$functionName" "$envVars"

    publishFunction "$functionName"

    # Set the alias (version: TEST, BETA, PROD)
    updateAlias "$functionName" "$deploymentType" "$functionVersion"
}

function deleteOldVersionsOfFunction {
    local functionName=$1
    local versions=`aws lambda list-versions-by-function --function-name "$functionName" --max-items 500 --profile appigo |grep "\"Version\"" |cut -d "\"" -f4`
    for version in $versions
    do
        if [[ $version =~ [[:digit:]] ]]    # version must be a digit
        then
            echo "Deleting '$functionName' version $version..."
           aws lambda delete-function \
           --function-name "$functionName" \
           --qualifier $version \
           --profile appigo
        fi
    done
}

function deleteOldFunctionVersions {
    element_count=${#functionNames[*]}
    index=0
    while [ "$index" -lt "$element_count" ]
    do
        functionName=${functionNames[$index]}

        deleteOldVersionsOfFunction "$functionName"

        ((index++))
    done
}

function preloadFunctions {

    packageFunctions

    element_count=${#functionNames[*]}
    index=0
    while [ "$index" -lt "$element_count" ]
    do
        functionName=${functionNames[$index]}
        functionHandler=${functionHandlers[$index]}
        functionDescription=${functionDescriptions[$index]}

        createFunction "$functionName" "$functionHandler" "$functionDescription" "$testEnvironmentVariables"

        ((index++))
    done
}

function updateAPI {

    # First, prepare a new API.json file for the selected deployment type
    # by replacing FUNCTION_ALIAS in the API definition file with the
    # proper deployment alias.
    sed "s/FUNCTION_ALIAS/$deploymentType/g" "$apiJSONFileName" > "/tmp/$apiJSONFileName"

    echo "Uploading API for $deploymentType ..."
    aws apigateway put-rest-api \
    --rest-api-id "55jmtuf9lk" \
    --mode merge \
    --fail-on-warnings \
    --body "file:///tmp/$apiJSONFileName" \
    --profile appigo

    case $deploymentType in
    TEST)
        stageName="test"
        stageDescription="Testing stage used with database available on plano.todo-cloud.com."
        ;;
    BETA)
        stageName="beta"
        stageDescription="Beta stage used with production database and beta clients."
        ;;
    PROD)
        stageName="production"
        stageDescription="Production stage used with production database and production apps."
        ;;
    *)
        echo "Invalid deployment type: $deploymentType"
        exit 1
        ;;
    esac

    local gitTagDescription=`git describe --tags`
    local gitCommitHash=`git rev-parse HEAD`
    local deploymentDescription="$stageName - $gitTagDescription ($gitCommitHash)"
    
    echo "Creating API Gateway Deployment Stage: $deploymentDescription ..."
    aws apigateway create-deployment \
    --rest-api-id "55jmtuf9lk" \
    --stage-name "$stageName" \
    --stage-description "$stageDescription" \
    --description "$deploymentDescription" \
    --profile appigo
}

function mainMenu {
    # echo $testFunctions
    echo ""
    echo "Welcome to the Appigo Lambda Deployment Tool!"
    echo ""
    #echo "0. Pre-load Everything" # Only here for if we have to reconstruct everything from scratch
    echo "1. Create a new lambda function (MANUALLY)"
    echo "2. Create a new lambda function (FROM THE LIST OF FUNCTIONS)"
    echo "3. Update a single lambda function"
    echo "4. Update ALL lambda functions"
    echo "5. Delete ALL old lambda function versions"
    echo ""
    echo "6. Update API Gateway"
    echo ""
    read -p "Choose an option (<Enter> to quit):" -n 1 -s selectedOption
    echo ""

    if [ -z "$selectedOption" ]
    then
        echo "Goodbye!"
        exit 0
    else
        echo "You selected option #$selectedOption"
    fi

    case $selectedOption in
    # Uncomment the 0 option if we have to reconstruct everything
    # from scratch. Otherwise, let's leave it commented out so that
    # we don't actually use it.
    #0)
    #    preloadFunctions
    #    mainMenu
    #    ;;
    1)
        createFunctionWithPrompts
        mainMenu
        ;;
    2)
        createFunctionFromList
        mainMenu
        ;;
    3)
        updateSingleFunction
        mainMenu
        ;;
    4)
        chooseDeploymentType
        if [ -z "$deploymentType" ]
        then
            mainMenu
        fi
        updateFunctions
        mainMenu
        ;;
    5)
        deleteOldFunctionVersions
        mainMenu
        ;;
    6)
        chooseDeploymentType
        if [ -z "$deploymentType" ]
        then
            mainMenu
        fi
        updateAPI
        mainMenu
        ;;
    *)
        echo "Invalid option"
        mainMenu
esac
}

mainMenu


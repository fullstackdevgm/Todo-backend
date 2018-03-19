<?php
    // Sync Protocol Version
    //version 1.3 forces the user to update so that we can make
    //them use auto-renewing IAP subscriptions instead of
    //the old type of subscriptions
    define('CURRENT_SYNC_PROTOCOL_VERSION', 1.3);

    define ('EXPIRED_SUBSCRIPTION_RETRY_SYNC_INTERVAL', 1209600);

    // Error Codes
    define('ERROR_CODE_MISSING_REQUIRED_PARAMETERS', 4701);
    define('ERROR_DESC_MISSING_REQUIRED_PARAMETERS', 'The request was missing required parameters.');

    define('ERROR_CODE_BAD_USERNAME_OR_PASSWORD', 4702);
    define('ERROR_DESC_BAD_USERNAME_OR_PASSWORD', 'Bad username or password.');

    define('ERROR_CODE_CREATING_SESSION_FAILED', 4703);
    define('ERROR_DESC_CREATING_SESSION_FAILED', 'Failed to create session. The server will fail if create session is called too rapidly for the same user.');

    define('ERROR_CODE_INVALID_SESSION', 4704);
    define('ERROR_DESC_INVALID_SESSION', 'Invalid session.  Session must be requested using credentials.');

    define('ERROR_CODE_MISSING_ALL_PARAMETERS', 4705);
    define('ERROR_DESC_MISSING_ALL_PARAMETERS', 'No parameters found in request.');

    define('ERROR_CODE_USER_NOT_AUTHENTICATED', 4706);
    define('ERROR_DESC_USER_NOT_AUTHENTICATED', 'The user is not authenticated. Reauthentication is required.');

    define('ERROR_CODE_INVALID_METHOD', 4707);
    define('ERROR_DESC_INVALID_METHOD', 'Invalid method was requested.');

    define('ERROR_CODE_ERROR_ADDING_OBJECT', 4708);
    define('ERROR_DESC_ERROR_ADDING_OBJECT', 'Unable to add the object.');

    define('ERROR_CODE_ACCESS_DENIED', 4709);
    define('ERROR_DESC_ACCESS_DENIED', 'User does not have rights to the requested object.');

    define('ERROR_CODE_OBJECT_NOT_FOUND', 4710);
    define('ERROR_DESC_OBJECT_NOT_FOUND', 'Unable to locate that object on the server.');

    define('ERROR_CODE_ERROR_UPDATING_OBJECT', 4711);
    define('ERROR_DESC_ERROR_UPDATING_OBJECT', 'Unable to update the object.');

    define('ERROR_CODE_ERROR_DELETING_OBJECT', 4712);
    define('ERROR_DESC_ERROR_DELETING_OBJECT', 'Unable to delete the object.');

    define('ERROR_CODE_ERROR_READING_USER_TASKS', 4713);
    define('ERROR_DESC_ERROR_READING_USER_TASKS', 'Error reading user tasks.');

	define('ERROR_CODE_EXPIRED_SUBSCRIPTION', 4714);
	define('ERROR_DESC_EXPIRED_SUBSCRIPTION', 'User does not have a valid sync subscription.');

    define('ERROR_CODE_PARENT_TASK_NOT_PROJECT', 4715);
    define('ERROR_DESC_PARENT_TASK_NOT_PROJECT', 'Parent task specified was not a project.');

    define('ERROR_CODE_ERROR_PARSING_DATA', 4716);
    define('ERROR_DESC_ERROR_PARSING_DATA', 'Server is unable to parse the request.');

    define('ERROR_CODE_USER_BEING_MIGRATED', 4720);
    define('ERROR_DESC_USER_BEING_MIGRATED', 'The user is being migrated from the old system.');

    define('ERROR_CODE_ERROR_CREATING_USER', 4721);
    define('ERROR_DESC_ERROR_CREATING_USER', 'Error creating user, try a new username.');

    define('ERROR_CODE_ERROR_USERNAME_ALREADY_EXISTS', 4722);
    define('ERROR_DESC_ERROR_USERNAME_ALREADY_EXISTS', 'Username already exists, try a new username.');

    define ('ERROR_CODE_FACEBOOK_LOGIN_FAILED', 4723);
    define ('ERROR_DESC_FACEBOOK_LOGIN_FAILED', 'Todo Cloud Facebook login failed.');

    define ('ERROR_CODE_FACEBOOK_EMAIL_EXISTS', 4724);
    define ('ERROR_DESC_FACEBOOK_EMAIL_EXISTS', 'Your email address has already been registered with Todo Cloud. If you already have a Todo Cloud account, you may link it to this Facebook account in Settings->Account at www.todo-cloud.com.');

    define ('ERROR_CODE_LINKED_TO_OTHER_FACEBOOK', 4725);
    define ('ERROR_DESC_LINKED_TO_OTHER_FACEBOOK', 'Your Todo Cloud account is linked to a different Facebook account. Please sign in to that account.');

    define ('ERROR_CODE_FACEBOOK_LINKED_TO_OTHER_ACCOUNT', 4726);
    define ('ERROR_DESC_FACEBOOK_LINKED_TO_OTHER_ACCOUNT', 'This Facebook account is linked to a different Todo Cloud account.');

    define ('ERROR_CODE_ERROR_UPDATING_USER', 4727);
    define ('ERROR_DESC_ERROR_UPDATING_USER', 'Unable to update the user');

    define ('ERROR_CODE_FACEBOOK_USER_NOT_FOUND', 4728);
    define ('ERROR_DESC_FACEBOOK_USER_NOT_FOUND', 'There is no Todo Cloud account linked with that Facebook account. Would you like to create a new account?');

    define ('ERROR_CODE_FACEBOOK_USER_EXISTS', 4729);
    define ('ERROR_DESC_FACEBOOK_USER_EXISTS', 'This Facebook account is already tied to a Todo Cloud account. Would you like to log in to an existing account?');

    define ('ERROR_CODE_DB_LINK_FAILED', 4730);
    define ('ERROR_DESC_DB_LINK_FAILED', 'Unknown database error');

    define ('ERROR_CODE_ERROR_INVALID_UTF8_DURING_ADD', 4731);
    define ('ERROR_DESC_ERROR_INVALID_UTF8_DURING_ADD', 'The Add request contained invalid UTF8 characters');

    define ('ERROR_CODE_ERROR_INVALID_UTF8_IN_TASKS', 4732);
    define ('ERROR_DESC_ERROR_INVALID_UTF8_IN_TASKS', 'One or more tasks found contain invalid characters and could not be encoded.  Visit http://support.appigo.com for steps on how to resolve this.');

    define ('ERROR_CODE_ERROR_INVALID_UTF8_ON_TASK', 4733);
    define ('ERROR_DESC_ERROR_INVALID_UTF8_ON_TASK', 'The task contains invalid characters');

    define ('ERROR_CODE_ERROR_INVALID_UTF8_IN_CONTEXTS', 4734);
    define ('ERROR_DESC_ERROR_INVALID_UTF8_IN_CONTEXTS', 'One or more contexts found contain invalid characters and could not be encoded.  Visit http://support.appigo.com for steps on how to resolve this.');

    define ('ERROR_CODE_ERROR_INVALID_UTF8_IN_LISTS', 4735);
    define ('ERROR_DESC_ERROR_INVALID_UTF8_IN_LISTS', 'One or more lists found contain invalid characters and could not be encoded.  Visit http://support.appigo.com for steps on how to resolve this.');

    define ('ERROR_CODE_ERROR_INVALID_UTF8_IN_TASKITOS', 4736);
    define ('ERROR_DESC_ERROR_INVALID_UTF8_IN_TASKITOS', 'One or more subtasks found contain invalid characters and could not be encoded.  Visit http://support.appigo.com for steps on how to resolve this.');

    define ('ERROR_CODE_ERROR_INVALID_UTF8_IN_NOTIFICATIONS', 4737);
    define ('ERROR_DESC_ERROR_INVALID_UTF8_IN_NOTIFICATIONS', 'One or more notifications found contain invalid characters and could not be encoded.  Visit http://support.appigo.com for steps on how to resolve this.');

    define ('ERROR_CODE_USER_MAINTENANCE', 4738);
    define ('ERROR_DESC_USER_MAINTENANCE', 'The user is flagged for maintenance');

    define('ERROR_CODE_OVERSIZED_NOTE', 4739);
    define('ERROR_DESC_OVERSIZED_NOTE', 'Unable to add or update task because of oversized note. Max size is 1MB');

    define('ERROR_CODE_PARENT_TASK_NOT_FOUND', 4740);
    define('ERROR_DESC_PARENT_TASK_NOT_FOUND', 'Parent task specified was not found.');

    define('ERROR_CODE_LIST_NOT_FOUND', 4741);
    define('ERROR_DESC_LIST_NOT_FOUND', 'List for specified task not found.');

	function outputSyncError($syncErrorCode, $syncErrorMessage)
	{
        echo '{"errorCode":'.$syncErrorCode.', "errorDesc":"'.$syncErrorMessage.'"}';
	}

?>

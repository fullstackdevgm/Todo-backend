<?php
    // Message Center Sync Protocol Version
    define('CURRENT_MESSAGE_CENTER_PROTOCOL', 1.0);
    
    //If the client sends a protocol version less than this, return
    //a 470009 error forcing them to update
    define('REQUIRED_PROTOCOL_VERSION', 1.0);
    
    define ('MESSAGE_CENTER_API_KEY_SECRET', '7702EFEE-7CF3-42CF-84B6-296DD6BF8F5F');
    
    define('MC_ERROR_CODE_MISSING_PARAMETER', 470001);
    define('MC_ERROR_DESC_MISSING_PARAMETER', 'The request was missing required parameters.');
    
    define('MC_ERROR_CODE_USER_NOT_AUTHENTICATED', 470002);
    define('MC_ERROR_DESC_USER_NOT_AUTHENTICATED', 'The user is not authenticated. Reauthentication is required.');
    
    define('MC_ERROR_CODE_SEND_MESSAGES_FAILED', 470003);
    define('MC_ERROR_DESC_SEND_MESSAGES_FAILED', 'Failed to send new messages to user');
    
    define('MC_ERROR_CODE_GET_MESSAGES_FAILED', 470004);
    define('MC_ERROR_DESC_GET_MESSAGES_FAILED', 'Failed to read modified messages for user');
    
    define('MC_ERROR_CODE_MESSAGE_NOT_FOUND', 470005);
    define('MC_ERROR_DESC_MESSAGE_NOT_FOUND', 'The specified message was not found on the server');
    
    define('MC_ERROR_CODE_ERROR_UPDATING_MESSAGE', 470006);
    define('MC_ERROR_DESC_ERROR_UPDATING_MESSAGE', 'Unable to update message on server');
    
    define('MC_ERROR_CODE_GET_USER_INFO_FAILED', 470007);
    define('MC_ERROR_DESC_GET_USER_INFO_FAILED', 'Failed to get message center user info for user');
    
    define('MC_ERROR_CODE_CLIENT_UNAUTHORIZED', 470008);
    define('MC_ERROR_DESC_CLIENT_UNAUTHORIZED', 'Client is not authorized to make this call');
    
    define('MC_ERROR_CODE_PROTOCOL_VERSION_NOT_SUPPORTED', 470009);
    define('MC_ERROR_DESC_PROTOCOL_VERSION_NOT_SUPPORTED', 'The protocol version of the client is no longer supported. Please update client.');
    
	function outputMCSyncError($syncErrorCode, $syncErrorMessage)
	{
        echo '{"errorCode":'.$syncErrorCode.', "errorDesc":"'.$syncErrorMessage.'"}';
	}
	
?>
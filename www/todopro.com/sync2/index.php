<?php
	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/syncmethodhandlers/SyncConstants.php');
    
	// sync does not support Facebook

    // setIsSync will set up the cookie and session timeout to be shorter
    // for the sync protocol.
	TDOSession::setIsSync();
	
	include_once('TodoOnline/php/SessionHandler.php');

    if(isset($_POST['method']))
	{
		include_once('TodoOnline/php/SyncMethodHandlerV2.php');
	}
    else
    {
        outputSyncError(ERROR_CODE_MISSING_ALL_PARAMETERS, ERROR_DESC_MISSING_ALL_PARAMETERS);
        error_log("HandleSyncAuthentication:getSessionToken: missing all parameters");
    }
?>

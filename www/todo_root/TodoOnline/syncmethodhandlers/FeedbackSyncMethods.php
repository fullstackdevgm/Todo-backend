<?php
	
	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/syncmethodhandlers/SyncConstants.php');    
	include_once('TodoOnline/php/SessionHandler.php');	

    if(!$session->isLoggedIn())
	{
		error_log("FeedbackSyncMethods.php called without a valid session");
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
	
	$user = TDOUser::getUserForUserId($session->getUserId());

	if($user == false)
	{
		error_log("UserSyncMethods.php unable to fetch logged in user: ".$session->getUserId());
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
    

    
    if($method == "sendFeedbackAPI")
    {
        //check for valid parameters
        if(!isset($_POST['message']))
        {
            error_log("HandleSendFeedback.php called and missing a required parameter: message");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Missing argument: message'),
            ));
            return;
        }

        $feedback = $_POST['message'];

        $jsonResponse = array('success' => true);


		echo json_encode($jsonResponse);
        TDOMailer::sendUserFeedback($user, $feedback);
        TDODevice::updateDeviceForUserAndSession($session->getUserId(), $session->getSessionId());
    }

    
?>    
   
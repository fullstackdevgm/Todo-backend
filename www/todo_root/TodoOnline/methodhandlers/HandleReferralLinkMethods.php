<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');
	include_once('TodoOnline/DBConstants.php');
	
	if ($method == "sendReferralEmail")
	{
		/**
		 * Required Parameters
		 *
		 *	emails (comma separated email list)
		 *	link (the full referral URL)
		 */
		
		if(!isset($_POST['emails']))
		{
			error_log("HandleReferralLinkMethods::sendReferralEmail called and missing a required parameter: emails");
			echo '{"errcode":'.REFERRAL_ERROR_CODE_MISSING_PARAMETER.',"errdesc":"'.REFERRAL_ERROR_DESC_MISSING_PARAMETER.'"}';
			return;
		}
		if(!isset($_POST['link']))
		{
			error_log("HandleReferralLinkMethods::sendReferralEmail called and missing a required parameter: link");
			echo '{"errcode":'.REFERRAL_ERROR_CODE_MISSING_PARAMETER.',"errdesc":"'.REFERRAL_ERROR_DESC_MISSING_PARAMETER.'"}';
			return;
		}
		
		$emails = $_POST['emails'];
		$link = $_POST['link'];
		
		// Remove any extra spaces
		$emails = str_replace(' ', '', $emails);
		
		// Build an array from the email addresses that were passed in
		$emailAddresses = explode(',', $emails);
		
		$userID = $session->getUserId();
		$displayName = TDOUser::displayNameForUserID($userID);
		
		TDOMailer::sendReferralLinkInvitation($emailAddresses, $displayName, $link);
		
		echo '{"success":true}';
	}
	
?>

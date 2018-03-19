<?php

include_once('TodoOnline/base_sdk.php');


if ($method == "searchUsers")
{
    if(!isset($_POST['searchString']))
    {
        error_log("admin method searchUsers called with no search string");
        echo '{"success":false}';
        return;
    }
    
    if(isset($_POST['limit']))
        $limit = $_POST['limit'];
    else
        $limit = 50;
        
    if(isset($_POST['offset']))
        $offset = $_POST['offset'];
    else
        $offset = 0;
    
    $searchString = $_POST['searchString'];
    
    $users = TDOUser::getUsersForSearchString($searchString, $limit, $offset);
    
    if($users === false)
    {
        error_log("getUsersForSearchString failed");
        echo '{"success":false}';
        return;
    }
    
    $jsonResponseArray = array();
		
    $usersArray = array();
    
    foreach($users as $user)
    {
        $userProperties = $user->getPropertiesArray();
        $usersArray[] = $userProperties;
    }
    
    $jsonResponseArray['success'] = true;
	
	$userID = $session->getUserId();
    $jsonResponseArray['users'] = $usersArray;

    
    $jsonResponse = json_encode($jsonResponseArray);
    //error_log("jsonResponse we're sending is: ". $jsonResponse);
    echo $jsonResponse;

    
}
else if ($method == "getUserInfo")
{
    if(!isset($_POST['userid']))
    {
        error_log("admin method getUserInfo called with no userid");
        echo '{"success":false}';
        return;
    }
    
    $userid = $_POST['userid'];
    
    $user = TDOUser::getUserForUserId($userid);
    if(!$user)
    {
        error_log("getUserInfo unable to find user for userid: ".$userid);
        echo '{"success":false}';
        return;
    }
	
    $jsonResponseArray = array();
    $jsonResponseArray['success'] = true;
    
    $userProperties = $user->getPropertiesArray();
    
    $jsonResponseArray['user'] = $userProperties;
	
	$subscriptionInfo = TDOSubscription::getSubscriptionInfoForUserID($userid, false);
	if ($subscriptionInfo)
		$jsonResponseArray['subscriptionInfo'] = $subscriptionInfo;
	
	$administratorOfTeams = TDOTeamAccount::getTeamsForTeamAdmin($userid, 0, 100);
	if ($administratorOfTeams)
	{
		$administeredTeams = array();
		foreach ($administratorOfTeams as $team)
		{
			$teamInfo = array(
							  "teamid" => $team->getTeamID(),
							  "teamName" => $team->getTeamName()
			);
			$administeredTeams[] = $teamInfo;
		}
		
		if (count($administeredTeams) > 0)
			$jsonResponseArray['administeredTeams'] = $administeredTeams;
	}
    if ($administeredTeams) {
        $teamAccount = TDOTeamAccount::getTeamForTeamID($administeredTeams[0]['teamid']);
    } else {
        $teamAccount = TDOTeamAccount::getTeamForTeamMember($userid);
    }
	if ($teamAccount)
	{
		$teamInfo = $teamAccount->getPropertiesArray();
		if ($teamInfo)
			$jsonResponseArray['teamInfo'] = $teamInfo;
	}
	
	$purchaseHistory = TDOSubscription::getPurchaseHistoryForUserID($userid);
	if ($purchaseHistory)
		$jsonResponseArray['purchaseHistory'] = $purchaseHistory;
	
    $giftPurchaseHistory = TDOGiftCode::giftCodeInfoForUser($userid);
    if($giftPurchaseHistory)
        $jsonResponseArray['giftCodeHistory'] = $giftPurchaseHistory;
    
	$listCount = TDOList::getListCountForUser($userid);
	if ($listCount)
		$jsonResponseArray['listCount'] = $listCount;
	
	$activeTaskCount = TDOTask::getTaskCountForUser($userid, false);
	if ($activeTaskCount)
		$jsonResponseArray['activeTaskCount'] = $activeTaskCount;
	
	$completedTaskCount = TDOTask::getTaskCountForUser($userid, true);
	if ($completedTaskCount)
		$jsonResponseArray['completedTaskCount'] = $completedTaskCount;
	
	$ownedListCount = TDOList::getOwnedListCountForUser($userid);
	if ($ownedListCount)
		$jsonResponseArray['ownedListCount'] = $ownedListCount;
	
	$sharedListCount = TDOList::getSharedListCountForUser($userid);
	if ($sharedListCount)
		$jsonResponseArray['sharedListCount'] = $sharedListCount;
	
	$migratedUserInfo = TDOLegacy::getMigrationInfoForUser($userid);
	if ($migratedUserInfo)
		$jsonResponseArray['migrationInfo'] = $migratedUserInfo;
	
	$maintenanceUserInfo = TDOUserMaintenance::getMaintenanceInfoForUser($userid);
	if ($maintenanceUserInfo)
		$jsonResponseArray['maintenanceInfo'] = $maintenanceUserInfo;
	
	$accountLog = TDOUser::getAccountLogForUser($userid);
	if ($accountLog)
	{
		$jsonResponseArray['accountLog'] = $accountLog;
		
		// Build an associative array for the display names of the users that
		// are found in the account log.  We don't have to include the user
		// because their display name is already known.
		$adminDisplayNames = array();
		foreach ($accountLog as $logItem)
		{
			$ownerID = $logItem['owner_userid'];
			if (isset($adminDisplayNames[$ownerID]))
				continue;
			
			$adminDisplayName = TDOUser::displayNameForUserId($ownerID);
			if (!$adminDisplayName)
				continue;
			
			$adminDisplayNames[$ownerID] = $adminDisplayName;
		}
		
		if (count($adminDisplayNames) > 0)
			$jsonResponseArray['accountLogAdmins'] = $adminDisplayNames;
	}
    
    // get all user devices
    $devices = TDODevice::allDevicesForUser($userid);
    if($devices)
    {
        $userDevices = array();
        foreach( $devices as $device)
        {
            $userDevices[] = $device->getPropertiesArray();
        }
        if(count($userDevices) > 0)
			$jsonResponseArray['userDevices'] = $userDevices;
    }
	
	// Return information about whether the user's email has bounced
	$bounceRecord = TDOMailer::getBounceRecordForEmail($user->username());
	if (!empty($bounceRecord))
	{
		$jsonResponseArray['bounceRecord'] = $bounceRecord;
	}
	
    echo json_encode($jsonResponseArray);
}
else if ($method == "adjustExpirationDate")
{
    if (!isset($_POST['userid']))
    {
        error_log("admin method adjustExpirationDate called with no userid");
        echo '{"success":false,"error":"Missing parameter: userid"}';
        return;
    }
    $userid = $_POST['userid'];
	
    if (!isset($_POST['newExpirationTimestamp']))
    {
        error_log("admin method adjustExpirationDate called with no newExpirationTimestamp");
        echo '{"success":false,"error":"Missing parameter: newExpirationTimestamp"}';
        return;
    }
    $newExpirationTimestamp = $_POST['newExpirationTimestamp'];
	
    if (!isset($_POST['note']))
    {
        error_log("admin method adjustExpirationDate called with no note");
        echo '{"success":false,"error":"Missing parameter: note"}';
        return;
    }
    $note = trim($_POST['note']);
	if (strlen($note) == 0)
	{
        error_log("admin method adjustExpirationDate called with empty note");
        echo '{"success":false,"error":"Empty note"}';
        return;
	}
    
    //If the user has an autorenewing IAP subscription, don't allow their
    //date to be manually extended, because that will cause issues
    if(TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userid))
    {
        echo '{"success":false, "error":"This user has an auto-renewing IAP subscription. You may not manually alter the expiration date."}';
        return;
    }
	
	$subscription = TDOSubscription::getSubscriptionForUserID($userid);
	if (!$subscription)
	{
		error_log("admin method adjustExpirationDate unable to locate subscription for user ($userid)");
		echo '{"success":false,"error":"Cannot find premium account information for user."}';
		return;
	}
	
	$subscriptionID = $subscription->getSubscriptionID();
	$subscriptionType = $subscription->getSubscriptionType();
	$subscriptionLevel = $subscription->getSubscriptionLevel();
	
	if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $subscriptionType, $subscriptionLevel))
	{
		error_log("admin method adjustExpirationDate had an error calling TDOSubscription::updateSubscriptionWithNewExpirationDate");
		echo '{"success":false,"error":"Error updating premium account."}';
		return;
	}
	
	// The expiration date was successfully changed, log the change and also
	// send an email to the user that their expiration date was changed by
	// a member of the Todo Cloud Support Team.
	$username = TDOUser::usernameForUserId($userid);
	$displayName = TDOUser::displayNameForUserId($userid);
	if ($username)
	{
		TDOMailer::notifyUserOfExpirationChange($username, $displayName, $newExpirationTimestamp);
	}
	else
	{
		error_log("admin method adjustExpirationDate could not notify the user that their subscription expiration changed because the account does not have a username.");
	}
	
	$adminUserID = $session->getUserId();
	$changeDescription = "New Expiration Date: " . date("D d M Y", $newExpirationTimestamp) . ", Note: $note";
	
	if (!TDOUser::logUserAccountAction($userid, $adminUserID, USER_ACCOUNT_LOG_TYPE_EXP_DATE, $changeDescription))
	{
		error_log("admin method adjustExpirationDate could not log the change to the user ($userid) account by $adminUserID");
	}
	
	echo '{"success":true}';
}
else if ($method == "convertToAppleIAP")
{
	if (!isset($_POST['userid']))
	{
		error_log("admin method convertToAppleIAP called with no userid");
		echo '{"success":false,"error":"Missing parameter: userid"}';
		return;
	}
	$userid = $_POST['userid'];
	
	$receiptData = "Bogus-Receipt-Data";
	$expirationDate = new DateTime('@' . time(), new DateTimeZone("UTC"));
	$expirationDate->add(new DateInterval('P1Y'));
	$expirationTimestamp = $expirationDate->getTimestamp();
	$transactionID = TDOUtil::uuid();
	
	if (TDOInAppPurchase::saveIAPAutorenewReceipt($receiptData, $expirationTimestamp, $transactionID, $userid))
	{
		echo '{"success":true}';
		return;
	}
	else
	{
		error_log("admin method convertToAppleIAP couldn't save bogus IAP information for user: $userid");
		echo '{"success":false,"error":"Unable to mark a user as an Apple IAP user."}';
		return;
	}
}
else if ($method == "convertToGoogleIAP")
{
	if (!isset($_POST['userid']))
	{
		error_log("admin method convertToGoogleIAP called with no userid");
		echo '{"success":false,"error":"Missing parameter: userid"}';
		return;
	}
	$userid = $_POST['userid'];
	
	$receiptProductID = "com.appigo.todopro.android.subscription.oneyear.renewable";
	$googlePlayToken = "Bogus-Token";
	$expirationDate = new DateTime('@' . time(), new DateTimeZone("UTC"));
	$expirationDate->add(new DateInterval('P1Y'));
	$expirationTimestamp = $expirationDate->getTimestamp();
	
	if (TDOInAppPurchase::saveGooglePlayToken($receiptProductID, $googlePlayToken, $expirationTimestamp, $userid))
	{
		echo '{"success":true}';
		return;
	}
	else
	{
		error_log("admin method convertToGoogleIAP couldn't save bogus IAP information for user: $userid");
		echo '{"success":false,"error":"Unable to mark a user as a GooglePlay IAP user."}';
		return;
	}
}
else if ($method == "attemptIAPAutorenewal")
{
	if (!isset($_POST['userid']))
	{
		error_log("admin method attemptIAPAutorenewal called with no userid");
		echo '{"success":false,"error":"Missing parameter: userid"}';
		return;
	}
	$userid = $_POST['userid'];
	
	// Check to see if the user has any existing IAP receipt data
	$iapReceipt = TDOInAppPurchase::IAPAutorenewReceiptForUser($userid);
	$gpToken = TDOInAppPurchase::googlePlayTokenForUser($userid);
	
	if (empty($iapReceipt) && empty($gpToken))
	{
		error_log("admin method attemptIAPAutorenewal couldn't locate an Apple IAP receipt or GooglePlay Token for user: $userid");
		echo '{"success":false,"error":"No Apple IAP receipt or GooglePlay token available for this user."}';
		return;
	}
	
	$receiptInfo = $iapReceipt;
	if (empty($receiptInfo))
	{
		$receiptInfo = $gpToken;
	}
	
	$isAutorenewalCancelled = false;
	if ($receiptInfo['autorenewal_canceled'] == 1)
	{
		$isAutorenewalCancelled = true;
	}
	
	if ($isAutorenewalCancelled == true)
	{
		// Set auto renew to NOT cancelled so that the autorenewal
		// process will be attempted.
		
		$link = TDOUtil::getDBLink();
		if (empty($link))
		{
			error_log("admin method attemptIAPAutorenewal could not get a connection to the DB");
			echo '{"success":false,"error":"Could not get a connection to the database."}';
			return;
		}
		
		$escapedUserId = mysql_real_escape_string($userid, $link);
		
		$sql = "";
		if (!empty($iapReceipt))
		{
			// This is Apple IAP User. Set auto renew receipt to NOT cancelled
			$sql = "UPDATE tdo_iap_autorenew_receipts SET autorenewal_canceled=0 WHERE userid='$escapedUserId'";
		}
		else
		{
			// This is Google Play
			$sql = "UPDATE tdo_googleplay_autorenew_tokens SET autorenewal_canceled=0 WHERE userid='$escapedUserId'";
		}
		
		$response = mysql_query($sql, $link);
		if (!$response)
		{
			TDOUtil::closeDBLink($link);
			error_log("admin method attemptIAPAutorenewal could not set autorenewal status to NOT cancelled.");
			echo '{"success":false,"error":"Could not mark the account as an active IAP account."}';
			return;
		}
		
		TDOUtil::closeDBLink($link);
	}
	
	$subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userid);
	$result = TDOSubscription::processAutorenewalForSubscription($subscriptionID);
	if ($result)
	{
		// The way that processAutorenewalForSubscription() is implemented,
		// it will respond with success even if the subscription itself
		// has expired. Because of this, check to see if we should
		// mark the IAP as cancelled.
		
		$subscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
		if (empty($subscription))
		{
			error_log("admin method attemptIAPAutorenewal is unable to read the subscription record for the user.");
			echo '{"success":false,"error":"Could not read the user subscription information."}';
			return;
		}
		
		$recordedExpirationDate = $subscription->getExpirationDate();
		$now = time();
		if ($recordedExpirationDate <= $now)
		{
			TDOInAppPurchase::markIAPAutorenewalCanceledForUser($userid);
			TDOInAppPurchase::markGooglePlayAutorenewalCanceledForUser($userid);
			
			//echo "NOTE: This user's account has expired. Newest expiration date is: " . date(DateTime::ISO8601, $subscription->getExpirationDate()) . "\n";
		}
	}
//	else
//	{
//		echo "Could not successfully renew the user account\n";
//	}
	
	echo '{"success":true}';
}
else if ($method == "clearBounceEmail")
{
    if (!isset($_POST['email']))
    {
        error_log("admin method clearBounceEmail called with no email");
        echo '{"success":false,"error":"Missing parameter: email"}';
        return;
    }
	$email = trim($_POST['email']);
	
    if (!isset($_POST['note']))
    {
        error_log("admin method clearBounceEmail called with no note");
        echo '{"success":false,"error":"Missing parameter: note"}';
        return;
    }
    $note = trim($_POST['note']);
	if (strlen($note) == 0)
	{
        error_log("admin method clearBounceEmail called with empty note");
        echo '{"success":false,"error":"Empty note"}';
        return;
	}
	
	if (!TDOMailer::clearBounceEmail($email))
	{
		error_log("admin method clearBounceEmail had an error calling TDOMailer::clearBounceEmail($email)");
		echo '{"success":false,"error":"Error clearing email."}';
		return;
	}
	
	// The email call was successful. Log the change
	$userid = TDOUser::userIdForUserName($email);
	if ($userid)
	{
		$adminUserID = $session->getUserId();
		$changeDescription = "Cleared bounce email: $email, Note: $note";
		if (!TDOUser::logUserAccountAction($userid, $adminUserID, USER_ACCOUNT_LOG_TYPE_CLEAR_BOUNCE_EMAIL, $changeDescription))
		{
			error_log("admin method clearBounceEmail could not log the change ($email) to the user ($userid) account by $adminUserID");
		}
	}
	
	echo '{"success":true}';
}
else if ($method == "mailPurchaseReceipt")
{
    if (!isset($_POST['userid']))
    {
        error_log("admin method mailPurchaseReceipt called with no userid");
        echo '{"success":false,"error":"Missing parameter: userid"}';
        return;
    }
    $userid = $_POST['userid'];
	
    if (!isset($_POST['paymentTimestamp']))
    {
        error_log("admin method mailPurchaseReceipt called with no paymentTimestamp");
        echo '{"success":false,"error":"Missing parameter: paymentTimestamp"}';
        return;
    }
    $paymentTimestamp = $_POST['paymentTimestamp'];
	
    if (!isset($_POST['note']))
    {
        error_log("admin method mailPurchaseReceipt called with no note");
        echo '{"success":false,"error":"Missing parameter: note"}';
        return;
    }
    $note = trim($_POST['note']);
	if (strlen($note) == 0)
	{
        error_log("admin method mailPurchaseReceipt called with empty note");
        echo '{"success":false,"error":"Empty note"}';
        return;
	}
	
	$purchaseInfo = TDOSubscription::getStripePurchaseInfoForUserID($userid, $paymentTimestamp);
	if (!$purchaseInfo)
	{
		error_log("admin method mailPurchaseReceipt failed in the call to TDOSubscription::getStripePurchaseInfoForUserID($userid, $paymentTimestamp)");
		echo '{"success":false,"error":"Specified purchase not found"}';
		return;
	}
	
	$username = TDOUser::usernameForUserId($userid);
	$displayName = TDOUser::displayNameForUserId($userid);
	$adminUserID = $session->getUserId();
	
	if (!TDOMailer::sendPremierAccountPurchaseReceipt($username, $displayName, $paymentTimestamp, $purchaseInfo['card_type'], $purchaseInfo['last4'], $purchaseInfo['type'], $purchaseInfo['amount'], 0))
	{
		$adminDisplayName = TDOUser::displayNameForUserId($adminUserID);
		error_log("admin method mailPurchaseReceipt failed to actually mail the receipt to the user, $displayName ($username). Requested by $adminDisplayName");
		echo '{"success":false,"error":"Purchase receipt could not be sent"}';
		return;
	}
	
	if (!TDOUser::logUserAccountAction($userid, $adminUserID, USER_ACCOUNT_LOG_TYPE_PURCHASE_RECEIPT, $note))
	{
		error_log("admin method mailPurchaseReceipt could not log the change to the user ($userid) account by $adminUserID");
	}
	
	echo '{"success":true}';
}
else if ($method == "sendResetPasswordEmail")
{
    if (!isset($_POST['userid']))
    {
        error_log("admin method sendResetPasswordEmail called with no userid");
        echo '{"success":false,"error":"Missing parameter: userid"}';
        return;
    }
    $userid = $_POST['userid'];
	
    if (!isset($_POST['note']))
    {
        error_log("admin method sendResetPasswordEmail called with no note");
        echo '{"success":false,"error":"Missing parameter: note"}';
        return;
    }
    $note = trim($_POST['note']);
	if (strlen($note) == 0)
	{
        error_log("admin method sendResetPasswordEmail called with empty note");
        echo '{"success":false,"error":"Empty note"}';
        return;
	}
	
	if(TDOPasswordReset::deleteExistingPasswordResetForUser($userid) == false)
		error_log("admin method sendResetPasswordEmail unable to invalidate existing reset password request for user ($userid)");
	
	$username = TDOUser::usernameForUserId($userid);
	
	$passwordReset = new TDOPasswordReset();
	$passwordReset->setUserId($userid);
	$passwordReset->setUsername($username);
	
	if($passwordReset->addPasswordReset())
	{
		$email = TDOMailer::validate_email($username);
		if($email)
		{
			$userDisplayName = TDOUser::displayNameForUserId($userid);
			
			$resetURL = SITE_PROTOCOL . SITE_BASE_URL."?resetpassword=true&resetid=".$passwordReset->resetId()."&uid=".$userid;
			if(TDOMailer::sendResetPasswordEmail($userDisplayName, $email, $resetURL, true))
			{
				$adminUserID = $session->getUserId();
				if (!TDOUser::logUserAccountAction($userid, $adminUserID, USER_ACCOUNT_LOG_TYPE_MAIL_PASSWORD_RESET, $note))
					error_log("admin method sendResetPasswordEmail could not log the activity for ($userid) account by $adminUserID");
				
				echo '{"success":true}';
			}
			else
			{
				echo '{"success":false, "error":"failed to send email to '.$email.'"}';
			}
		}
		else
		{
			error_log("Could not validate email: ".$username);
			echo '{"success":false, "error":"could not send email to '.$username.'"}';
		}
	}
	else
	{
		echo '{"success":false}';
	}
}
else if ($method == "impersonateAccount")
{
    if (!isset($_POST['username'])) {
        error_log("admin method impersonateAccount called with no username");
        echo '{"success":false,"error":"Missing parameter: username"}';
        return;
    }
    if (!isset($_POST['userid'])) {
        error_log("admin method impersonateAccount called with no userid");
        echo '{"success":false,"error":"Missing parameter: userid"}';
        return;
    }
    $userid = $_POST['userid'];
    $username = $_POST['username'];
    $adminUserID = $session->getUserId();
    $session = $session->login($username, FALSE, $adminUserID);
    if ($session) {
        if (!TDOUser::logUserAccountAction($userid, $adminUserID, USER_ACCOUNT_LOG_TYPE_IMPERSONATION, 'Enter Impersonation mode')) {
            error_log("admin method impersonateAccount could not log the change to the user ($username) account by $adminUserID");
        }
        echo '{"success":true}';
    } else {
        echo '{"success":false}';
    }

}

?>

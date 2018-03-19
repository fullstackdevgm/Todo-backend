#!/usr/bin/php -q
<?php

// Include Classes
include_once('TodoOnline/base_sdk.php');

//	define('BOYD_REFERRAL_CODE', 'https://www.todo-cloud.com/?referralcode=tNYx4B');
	define('EXTENSION_DATE', '1 April 2013');
	
	if ($argc != 2)
	{
		echo "Missing parameters!\n";
		exit(1);
	}
	
	$email = $argv[1];
	
	// Make sure we look up usernames in lowercase
	$email = strtolower($email);
	
	$userid = TDOUser::userIdForUserName($email);
	if (!$userid)
	{
		echo "Email address not found in our system: $email\n";
		exit(2);
	}
	
	// Read the user's current expiration date and add on an extra month
	$subscription = TDOSubscription::getSubscriptionForUserID($userid);
	
	if (empty($subscription))
	{
		echo "No subscription found for userid ($email): $userid\n";
		exit(3);
	}
	
	// Determine whether we need to extend the user's premium account or not
	$newExpirationTimestamp = strtotime(EXTENSION_DATE);
	$expirationTimestamp = $subscription->getExpirationDate();
	
	if ($expirationTimestamp > $newExpirationTimestamp)
	{
		// The user already has over a month left on their premium account, so
		// there's no need to do anything at this point.
		echo "User already has sufficient time for the beta ($email): " . date("D d M Y", $expirationTimestamp) . "\n";
		exit(4);
	}
	
    if(TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userid) == true)
    {
        echo "User has auto-renewing IAP subscription set up, don't extend his account (userid: $userid, username: $email).\n";
        exit(5);
    }
    
	// Update the user's subscription
	$subscriptionID = $subscription->getSubscriptionID();
	$subscriptionType = $subscription->getSubscriptionType();
	$subscriptionLevel = $subscription->getSubscriptionLevel();
	
	echo "Updating $email account with new expiration date of " . date("D d M Y", $newExpirationTimestamp) . "\n";
	
	if (TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $subscriptionType, $subscriptionLevel) == false)
	{
		echo "Failed to extend a user's subscription (userid: $userid, username: $email)";
		exit(6);
	}
	
	// Log this as an admin action on the user's account
	$changeDescription = "New Expiration Date: " . date("D d M Y", $newExpirationTimestamp) . ", Android Beta 1 Customer";
	TDOUser::logUserAccountAction($userid, $userid, USER_ACCOUNT_LOG_TYPE_EXP_DATE, $changeDescription);
	
	echo "Successfully updated account with new expiration date: $email\n";

?>

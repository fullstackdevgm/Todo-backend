<?php

	include_once('TodoOnline/content/ContentConstants.php');
	
	if($session->isLoggedIn())
	{
		$user = TDOUser::getUserForUserId($session->getUserId());
		
		if(!empty($user))
		{
			$ownedSubscriptions = TDOSubscription::getSubscriptionsForOwnerUserID($user->userId(), true);
			
			$memberSubscription = TDOSubscription::getSubscriptionForMemberUserID($user->userId(), false, true);
			
			// This returns an associative array with the following (if there is
			// some sort of failure, it is possible that the Stripe information
			// is not returned):
			//
			//		"name"			=> "John Hawne",		(optional, may not be available)
			//		"type"			=> "American Express",	(optional - type, last4,
			//		"last4"			=> "1234",				 exp_month, and exp_year will
			//		"exp_month"		=> "02",				 either all be available or none
			//		"exp_year"		=> "2014",				 will)
			$userPaymentInfo = TDOSubscription::getSubscriptionBillingInfoForUser($user->userId());
        }
    }
    
    $subscriptions = array();
    $memberSubscriptionJson = array();
    
    foreach($ownedSubscriptions as $subscription)
	{
		$jsonArray = array();
		
		$subscriptionID = $subscription->getSubscriptionID();
		$memberUserID = $subscription->getMemberUserID();
		$subscriptionLevel = $subscription->getSubscriptionLevel();
	
		$jsonArray['subscriptionid'] = $subscriptionID ;
		
		$jsonArray['subscriptionlevel'] = $subscriptionLevel;
		
		if (empty($memberUserID))
		{
			$invitationID = $subscription->getInvitationID();

			if (empty($invitationID))
			{
				
			}
			else
			{
				$invitationEmail = $subscription->getInvitationEmail();
				$invitationTimestamp = $subscription->getInvitationTimestamp();
				
				$jsonArray['invitationid'] = $invitationID;
				$jsonArray['invitationemail'] = $invitationEmail;
				$jsonArray['invitationtimestamp'] = $invitationTimestamp;
			}
		}
		else
		{
			$jsonArray['memberuserid'] = $memberUserID;
			$jsonArray['membername'] = TDOUser::displayNameForUserId($memberUserID);
		}
		$jsonArray['susbscriptionexpiratiodate'] = $subscription->getExpirationDate();
		
			
//		$fbId = TDOUser::facebookIdForUserId($memberUserID);
//        if($fbId)
//            $userPicUrl = 'https://graph.facebook.com/'.$fbId.'/picture';
        $user = TDOUser::getUserForUserId($memberUserID);
        $imgUrl = $user->fullImageURL();
        if(!empty($imgUrl))
            $userPicUrl = $imgUrl;
        else
            $userPicUrl = SMALL_PROFILE_IMG_PLACEHOLDER;
        
        $jsonArray['memberpicurl'] = $userPicUrl;
        
		array_push($subscriptions, $jsonArray);
	}

	
	if (!empty($memberSubscription))
	{
		$memberSubscriptionJson['ownername'] = TDOUser::displayNameForUserId($memberSubscription->getOwnerUserID());
		$memberSubscriptionJson['expirationdate'] = $memberSubscription->getExpirationDate();
		
//		$fbId = TDOUser::facebookIdForUserId($memberSubscription->getOwnerUserID());
//        if($fbId)
//            $userPicUrl = 'https://graph.facebook.com/'.$fbId.'/picture';

        $user = TDOUser::getUserForUserId($memberSubscription->getOwnerUserID());
        $imgUrl = $user->fullImageURL();
        if(!empty($imgUrl))
            $userPicUrl = $imgUrl;
        else
            $userPicUrl = SMALL_PROFILE_IMG_PLACEHOLDER;
        
        $memberSubscriptionJson['onwerpicurl'] = $userPicUrl;
        
        $response['membersubscription'] = $memberSubscriptionJson;
	}
	
	$response['ownedsubscriptions'] = $subscriptions;
	$response['userpaymentinfo'] = $userPaymentInfo;
	$response['success'] = true;
	
	echo json_encode($response);
	

?>
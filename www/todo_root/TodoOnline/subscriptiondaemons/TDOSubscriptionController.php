<?php

include_once('TDODaemonConfig.php');
include_once('TodoOnline/base_sdk.php');
include_once('TDODaemonLogger.php');
include_once('TDODaemonController.php');
	

class TDOSubscriptionController extends TDODaemonController
{			

	function __construct($daemonID = '')
	{
		parent::__construct($daemonID);
	}
		
    
	public function processAutorenewableAccounts()
	{
		//	1.	Search all subscriptions and add a new row into the
		//		tdo_autorenew_history table for those subscriptions that should
		//		be processed.  Only add subscriptions in that aren't already
		//		in the table.
		//	2.	Process all subscriptions with a renewal_attempts greater than
		//		0 IF we have passed the SUBSCRIPTION_RETRY_INTERVAL.  If it
		//		fails again, increment the renewal_attempts count and record the
		//		time again.
		//	3.	Process all subscriptions with a renewal_attempts of 0 first.
		//		If a subscription fails to renew, record the time and increment
		//		the renewal_attempts count.
		//	4.	Delete all failed renewals (SUBSCRIPTION_RETRY_MAX_ATTEMPTS
		//		is reached) and log it somehow, perhaps with an email?
		
		$this->log("-----------------------------------------------");
		
		$numOfSuccessfulRenewals = 0;
		
		//	1.	Search all subscriptions and add a new row into the
		//		tdo_autorenew_history table for those subscriptions that should
		//		be processed.  Only add subscriptions in that aren't already
		//		in the table.
		$this->prepareNewSubscriptions();
		
		//	2.	Process all subscriptions with a renewal_attempts greater than
		//		0 IF we have passed the SUBSCRIPTION_RETRY_INTERVAL.  If it
		//		fails again, increment the renewal_attempts count and record the
		//		time again.
		$successfulPayments = $this->processFailedSubscriptions();
		$numOfSuccessfulRenewals += $successfulPayments;
		
		//	3.	Process all subscriptions with a renewal_attempts of 0 first.
		//		If a subscription fails to renew, record the time and increment
		//		the renewal_attempts count.
		$successfulPayments = $this->processGoodSubscriptions();
		$numOfSuccessfulRenewals += $successfulPayments;
		
//		//	4.	Delete all failed renewals (SUBSCRIPTION_RETRY_MAX_ATTEMPTS
//		//		is reached) and log it somehow, perhaps with an email?
//		$this->deleteFailedSubscriptions();
		
		$this->log("Renewed " . $numOfSuccessfulRenewals . " accounts");
		
		
		// Team Renewals
		
		$numOfSuccessfulRenewals = 0;
		
		$this->prepareNewTeamSubscriptions();
		
		$successfulPayments = $this->processFailedTeamSubscriptions();
		$numOfSuccessfulRenewals += $successfulPayments;
		
		$successfulPayments = $this->processGoodTeamSubscriptions();
		$numOfSuccessfulRenewals += $successfulPayments;
		
		$this->log("Renewed " . $numOfSuccessfulRenewals . " team accounts");
		
		//
		// Check for IAP users that we need to notify that their auto-renewing
		// subscription is about to renew ... who are members of a team. We want
		// to send them a reminder email 7 days before this event with
		// instructions on how to cancel their auto-renewing subscription so
		// their premium account can fully be paid for by the team.
		$numOfReminderEmailsSent = $this->sendTeamMemberIAPCancellationReminderEmails();
		$this->log("Sent " . $numOfReminderEmailsSent . " reminders to cancel auto-renewing IAP account.");
	}

    
	private function prepareNewSubscriptions()
	{
		// Search all existing subscriptions and add them into our history
		// table if they are now up for renewal and don't already exist in the
		// history table.
		$now = time();
		$leadTime = $now + TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS);
		
		// NOTE: getAutorenewableSubscriptionsWithinDate() does not return
		// subscriptions that are part of at team.
		$newSubscriptionIDs = TDOSubscription::getAutorenewableSubscriptionsWithinDate($leadTime);
		if ($newSubscriptionIDs)
		{
			TDOSubscription::addSubscriptionsForAutorenewal($newSubscriptionIDs);
		}
	}
	
	
	private function prepareNewTeamSubscriptions()
	{
		// Search all existing teams and add them into the history
		// table if they are now up for renewal and don't already exist in the
		// history table.
		$now = time();
		$leadTime = $now + TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS);
		
		$newTeamIDs = TDOTeamAccount::getAutorenewableTeamSubscriptionsWithinDate($leadTime);
		if ($newTeamIDs)
		{
			TDOTeamAccount::addTeamsForAutorenewal($newTeamIDs);
		}
	}
	
	
	private function processFailedSubscriptions()
	{
		$failedSubscriptionIDs = TDOSubscription::getFailedAutorenewableSubscriptions();
		return $this->processSubscriptionsForAutorenewal($failedSubscriptionIDs);
	}
	
	
	private function processFailedTeamSubscriptions()
	{
		$failedTeamIDs = TDOTeamAccount::getFailedTeamAutorenewableSubscriptions();
		return $this->processTeamsForAutorenewal($failedTeamIDs);
	}
	
	
	private function processGoodSubscriptions()
	{
		$newSubscriptionIDs = TDOSubscription::getNewAutorenewableSubscriptions();
		return $this->processSubscriptionsForAutorenewal($newSubscriptionIDs);
	}
	
	
	private function processGoodTeamSubscriptions()
	{
		$newTeamIDs = TDOTeamAccount::getNewTeamAutorenewableSubscriptions();
		return $this->processTeamsForAutorenewal($newTeamIDs);
	}
	
	
	private function processSubscriptionsForAutorenewal($subscriptionIDs)
	{
		if (!$subscriptionIDs)
		{
			$this->log("TDOSubscriptionController::processSubscriptionsForAutorenewal(): No subscriptions to process");
			return 0;
		}
		
		$numOfSuccessfulRenewals = 0;
		
		foreach ($subscriptionIDs as $subscriptionID)
		{
			$result = TDOSubscription::processAutorenewalForSubscription($subscriptionID);
			if ($result)
			{
				$this->log("Successfully processed subscription ($subscriptionID) autorenewal.");
				$numOfSuccessfulRenewals++;
			}
			
			
		}
		
		return $numOfSuccessfulRenewals;
	}
	
	
	private function processTeamsForAutorenewal($teamIDs)
	{
		if (!$teamIDs)
		{
			$this->log("TDOSubscriptionController::processTeamsForAutorenewal(): No team subscriptions to process");
			return 0;
		}
		
		$numOfSuccessfulRenewals = 0;
		
		foreach ($teamIDs as $teamID)
		{
			$result = TDOTeamAccount::processAutorenewalForTeam($teamID);
			if ($result)
			{
				$this->log("Successfully processed team subscription ($teamID) autorenewal.");
				$numOfSuccessfulRenewals++;
			}
			
			
		}
		
		return $numOfSuccessfulRenewals;
	}
	
	
	private function sendTeamMemberIAPCancellationReminderEmails()
	{
		$results = TDOSubscription::getAboutToExpireIAPTeamMembersForReminderEmail();
		if (!$results || empty($results['endDate']) || empty($results['userInfos']))
		{
			$this->log("TDOSubscription::getAboutToExpireIAPTeamMembersForRemindersEmail() returned false, so the system can't send any reminder emails.");
			return false;
		}
		
		$userInfos = $results['userInfos'];
		
		$emailsSent = 0;
		
		foreach ($userInfos as $userInfo)
		{
			$userID = $userInfo['userID'];
			$expirationDate = $userInfo['expirationDate'];
			
			// Apple or GooglePlay IAP customer?
			$iapType = NULL;
			if (TDOInAppPurchase::userIsAppleIAPUser($userID))
			{
				$iapType = "apple";
			}
			else if (TDOInAppPurchase::userIsGooglePlayUser($userID))
			{
				$iapType = "google";
			}
			
			if (empty($iapType))
			{
				$this->log("TDOSubscriptionController::sendTeamMemberIAPCancellationReminderEmails() unable to determine what type of IAP customer the user ($userID) is (apple|google).");
			}
			else
			{
				$team = TDOTeamAccount::getTeamForTeamMember($userID);
				if (empty($team))
				{
					$this->log("TDOSubscriptionController::sendTeamMemberIAPCancellationReminderEmails() unable to determine the team for a user ($userID).");
				}
				else
				{
					$teamName = $team->getTeamName();
					$sent = TDOMailer::sendTeamMemberIAPCancellationInstructions($userID, $expirationDate, $teamName, false, $iapType);
					if ($sent)
					{
						$emailsSent++;
					}
				}
			}
		}
		
		// If we sent ANY emails, we'll update the last sent setting
		if ($emailsSent > 0)
		{
			$lastReminderEmailSentDate = $results['endDate'];
			$dateString = (string)$lastReminderEmailSentDate;
			TDOUtil::setStringSystemSetting("IAP_CANCELLATION_INSTRUCTIONS_LAST_NOTIFY_DATE", $dateString);
		}
		
		return $emailsSent;
	}
	
	// SELECT tdo_subscriptions.subscriptionid,tdo_user_accounts.username FROM tdo_subscriptions INNER JOIN tdo_user_accounts ON tdo_subscriptions.userid = tdo_user_accounts.userid WHERE expiration_date < UNIX_TIMESTAMP() AND (subscriptionid IN (SELECT subscriptionid FROM tdo_iap_cancellation_emails WHERE timestamp = 0) OR subscriptionid NOT IN (SELECT subscriptionid FROM tdo_iap_cancellation_emails));

}


?>


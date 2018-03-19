<?php
    
    include_once('TodoOnline/base_sdk.php');
    
    
    function showUsage()
    {
        echo "\nUsage: renewSubscriptions\n\n";
    }

	function prepareNewSubscriptions()
	{
		// Search all existing subscriptions and add them into our history
		// table if they are now up for renewal and don't already exist in the
		// history table.
		$now = time();
		$leadTime = $now + SUBSCRIPTION_RENEW_LEAD_TIME;
		
		$newSubscriptionIDs = TDOSubscription::getAutorenewableSubscriptionsWithinDate($leadTime);
		if ($newSubscriptionIDs)
		{
			TDOSubscription::addSubscriptionsForAutorenewal($newSubscriptionIDs);
		}
	}
	
	
	function processFailedSubscriptions()
	{
		$failedSubscriptionIDs = TDOSubscription::getFailedAutorenewableSubscriptions();
		return processSubscriptionsForAutorenewal($failedSubscriptionIDs);
	}
	
	
	function processGoodSubscriptions()
	{
		$newSubscriptionIDs = TDOSubscription::getNewAutorenewableSubscriptions();
		return processSubscriptionsForAutorenewal($newSubscriptionIDs);
	}
	
	
	function processSubscriptionsForAutorenewal($subscriptionIDs)
	{
		if (!$subscriptionIDs)
		{
			echo("TDOSubscriptionController::processSubscriptionsForAutorenewal(): No subscriptions to process\n");
			return 0;
		}
		
		$numOfSuccessfulRenewals = 0;
		
		foreach ($subscriptionIDs as $subscriptionID)
		{
			$result = TDOSubscription::processAutorenewalForSubscription($subscriptionID);
			if ($result)
			{
				echo("Successfully processed subscription ($subscriptionID) autorenewal.\n");
				$numOfSuccessfulRenewals++;
			}
			
			
		}
		
		return $numOfSuccessfulRenewals;
	}
    
    
	function processAutorenewableAccounts()
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
		
		echo("-----------------------------------------------\n");
		
		$numOfSuccessfulRenewals = 0;
		
		//	1.	Search all subscriptions and add a new row into the
		//		tdo_autorenew_history table for those subscriptions that should
		//		be processed.  Only add subscriptions in that aren't already
		//		in the table.
		prepareNewSubscriptions();
		
		//	2.	Process all subscriptions with a renewal_attempts greater than
		//		0 IF we have passed the SUBSCRIPTION_RETRY_INTERVAL.  If it
		//		fails again, increment the renewal_attempts count and record the
		//		time again.
		$successfulPayments = processFailedSubscriptions();
		$numOfSuccessfulRenewals += $successfulPayments;
		
		//	3.	Process all subscriptions with a renewal_attempts of 0 first.
		//		If a subscription fails to renew, record the time and increment
		//		the renewal_attempts count.
		$successfulPayments = processGoodSubscriptions();
		$numOfSuccessfulRenewals += $successfulPayments;
		
        //		//	4.	Delete all failed renewals (SUBSCRIPTION_RETRY_MAX_ATTEMPTS
        //		//		is reached) and log it somehow, perhaps with an email?
        //		$this->deleteFailedSubscriptions();
		
		echo("Renewed " . $numOfSuccessfulRenewals . " accounts\n");
	}    
    
    
    
    processAutorenewableAccounts();
    
    ?>


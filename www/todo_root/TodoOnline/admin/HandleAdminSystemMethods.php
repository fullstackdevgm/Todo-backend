<?php

include_once('TodoOnline/base_sdk.php');


    if($method == "getSystemStats")
    {
        $responseArray = array("success" => true);
        
        // Total Number of Users
        $totalUserCount = TDOUser::getUserCount();
        $responseArray['totalUserCount'] = $totalUserCount;
		
		// Total number of teams
		$totalTeamsCount = TDOTeamAccount::getTeamsCount();
		$responseArray['totalTeamsCount'] = $totalTeamsCount;
		
		// Total number of team users purchased
		$totalTeamPurchasedUsersCount = TDOTeamAccount::getTeamPurchasedUsersCount();
		$responseArray['totalTeamPurchasedUsersCount'] = $totalTeamPurchasedUsersCount;
        
		// Total number of team users in use
		$totalTeamUsersInUseCount = TDOTeamAccount::getTeamUsersInUseCount();
		$responseArray['totalTeamUsersInUseCount'] = $totalTeamUsersInUseCount;
        
        // Daily New Users
		$todayDate = new DateTime("now", new DateTimeZone("UTC"));
		$todayString = $todayDate->format("Y-m-d");
		$mountainTimezone = new DateTimeZone("America/Denver");
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $newUsers = array();
        $maxNewUsers = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOUser::getNewuserCountWithDateRange($startTimestamp, $endTimestamp);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $newUsers[] = $dayInfo;
            
            if ($dayCount > $maxNewUsers)
                $maxNewUsers = $dayCount;
            
            $startTimestamp -= 84600; // subtract 24 hours
            $endTimestamp -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxNewUsers'] = $maxNewUsers;
        $responseArray['dailyNewUsers'] = array_reverse($newUsers);
        
    //	
    //	// New Users Today
    //	$todayStart = new DateTime();
    //	$todayStart->setTime(0,0,0);
    //	$todayEnd = new DateTime();
    //	$todayEnd->setTime(23,59,59);
    //	
    //	$startTimestamp = $todayStart->getTimestamp();
    //	$endTimestamp = $todayEnd->getTimestamp();
    //	
    //	$todayUserCount = TDOUser::getNewUserCountWithDateRange($startTimestamp, $endTimestamp);
    //	$responseArray['newUsersToday'] = $todayUserCount;
    //	
    //	// New users yesterday
    //	$yesterday = new DateTime();
    //	$yesterday->sub(new DateInterval('P1D'));
    //	$yesterdayStart = yesterday->setTime(0,0,0);
    //	$yesterdayEnd = yesterday->setTime(23,59,59);
    //	
    //	$startTimestamp = $yesterdayStart->getTimestamp();
    //	$endTimestamp = $yesterdayEnd->getTimestamp();
    //	$yesterdayUserCount = TDOUser::getNewUserCountWithDateRange($startTimestamp, $endTimestamp);
    //	$responseArray['newUsersYesterday'] = $yesterdayUserCount;
        
        // New users this week
        
        // New users last week
        // New users this month
        // New users last month
        // New users this year
        // New users last year
        // Average new users per day
        
        // Total number of users who have connected with Facebook
        
        // Total number of users who have migrated from Todo Online
        
        // Total Number of Tasks
    //	$totalTaskCount = TDOTask::getTaskCount();
    //	$responseArray['totalTaskCount'] = $totalTaskCount;
        
        // New tasks today
        // New tasks yesterday
        // New tasks this week
        // New tasks last week
        // New tasks this month
        // New tasks last month
        // New tasks this year
        // New tasks last year
        // Average new tasks per day
        
        // Total Number of Comments
        // New comments today
        // New comments yesterday
        // New comments this week
        // New comments last week
        // New comments this month
        // New comments last month
        // New comments this year
        // New comments last year
        // Average new comments per day
        
        // Total Number of Lists
    //	$totalListCount = TDOList::getListCount();
    //	$responseArray['totalListCount'] = $totalListCount;
        
        // Total Number of Shared Lists
        
        
        //
        // Stripe Purchases
        //
        
        // Stripe Month Purchases
        
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $stripePurchases = array();
        $maxPurchases = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOSubscription::getStripePurchaseCountInRange(SUBSCRIPTION_TYPE_MONTH, $startTimestamp, $endTimestamp);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $stripePurchases[] = $dayInfo;
            
            if ($dayCount > $maxPurchases)
                $maxPurchases = $dayCount;
            
            $startTimestamp -= 84600; // subtract 24 hours
            $endTimestamp -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxMonthStripePurchases'] = $maxPurchases;
        $responseArray['dailyMonthStripePurchases'] = array_reverse($stripePurchases);
        
        // Stripe Year Purchases
        
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $stripePurchases = array();
        $maxPurchases = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOSubscription::getStripePurchaseCountInRange(SUBSCRIPTION_TYPE_YEAR, $startTimestamp, $endTimestamp);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $stripePurchases[] = $dayInfo;
            
            if ($dayCount > $maxPurchases)
                $maxPurchases = $dayCount;
            
            $startTimestamp -= 86400;
            $endTimestamp -= 86400;
        }
        
        $responseArray['dailyMaxYearStripePurchases'] = $maxPurchases;
        $responseArray['dailyYearStripePurchases'] = array_reverse($stripePurchases);
        
        
        // Total Stripe Purchases
        // New Stripe Purchases Today
        // New Stripe Purchases Yesterday
        // New Stripe Purchases This Week
        // New Stripe Purchases Last Week
        // New Stripe Purchases This Month
        // New Stripe Purchases Last Month
        // New Stripe Purchases This Year
        // New Stripe Purchases Last Year
        // Average Stripe Purchases Per Day
        
        
        // Total IAP Purchases
        // New IAP Purchases Today
        // New IAP Purchases Yesterday
        // New IAP Purchases This Week
        // New IAP Purchases Last Week
        // New IAP Purchases This Month
        // New IAP Purchases Last Month
        // New IAP Purchases This Year
        // New IAP Purchases Last Year
        // Average IAP Purchases Per Day
        
		//
		// Renewing In-App Purchases
		//
		
		// Renewing IAP Month Purchases
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $iapPurchases = array();
        $maxPurchases = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOInAppPurchase::getIAPPurchaseCountInRange(SUBSCRIPTION_TYPE_MONTH, $startTimestamp, $endTimestamp, true);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $iapPurchases[] = $dayInfo;
            
            if ($dayCount > $maxPurchases)
                $maxPurchases = $dayCount;
            
            $startTimestamp -= 84600; // subtract 24 hours
            $endTimestamp -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxRenewingMonthIAPPurchases'] = $maxPurchases;
        $responseArray['dailyRenewingMonthIAPPurchases'] = array_reverse($iapPurchases);
		
        // Renewing IAP Year Purchases
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $iapPurchases = array();
        $maxPurchases = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOInAppPurchase::getIAPPurchaseCountInRange(SUBSCRIPTION_TYPE_YEAR, $startTimestamp, $endTimestamp, true);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $iapPurchases[] = $dayInfo;
            
            if ($dayCount > $maxPurchases)
                $maxPurchases = $dayCount;
            
            $startTimestamp -= 84600; // subtract 24 hours
            $endTimestamp -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxRenewingYearIAPPurchases'] = $maxPurchases;
        $responseArray['dailyRenewingYearIAPPurchases'] = array_reverse($iapPurchases);
		
		
		//
		// Renewing Google Play Purchases
		//
		
		// Renewing Google Play Monthly Purchases
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $iapPurchases = array();
        $maxPurchases = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOInAppPurchase::getGooglePlayPurchaseCountInRange(SUBSCRIPTION_TYPE_MONTH, $startTimestamp, $endTimestamp);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $iapPurchases[] = $dayInfo;
            
            if ($dayCount > $maxPurchases)
                $maxPurchases = $dayCount;
            
            $startTimestamp -= 84600; // subtract 24 hours
            $endTimestamp -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxRenewingMonthGooglePlayPurchases'] = $maxPurchases;
        $responseArray['dailyRenewingMonthGooglePlayPurchases'] = array_reverse($iapPurchases);
		
		// Renewing Google Play Yearly Purchases
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $iapPurchases = array();
        $maxPurchases = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOInAppPurchase::getGooglePlayPurchaseCountInRange(SUBSCRIPTION_TYPE_YEAR, $startTimestamp, $endTimestamp);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $iapPurchases[] = $dayInfo;
            
            if ($dayCount > $maxPurchases)
                $maxPurchases = $dayCount;
            
            $startTimestamp -= 84600; // subtract 24 hours
            $endTimestamp -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxRenewingYearGooglePlayPurchases'] = $maxPurchases;
        $responseArray['dailyRenewingYearGooglePlayPurchases'] = array_reverse($iapPurchases);
		
		
        //
        // In-App Purchases
        //
        
		// IAP Month Purchases
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $stripePurchases = array();
        $maxPurchases = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOInAppPurchase::getIAPPurchaseCountInRange(SUBSCRIPTION_TYPE_MONTH, $startTimestamp, $endTimestamp);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $stripePurchases[] = $dayInfo;
            
            if ($dayCount > $maxPurchases)
                $maxPurchases = $dayCount;
            
            $startTimestamp -= 84600; // subtract 24 hours
            $endTimestamp -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxMonthIAPPurchases'] = $maxPurchases;
        $responseArray['dailyMonthIAPPurchases'] = array_reverse($stripePurchases);
        
        // IAP Year Purchases
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $stripePurchases = array();
        $maxPurchases = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOInAppPurchase::getIAPPurchaseCountInRange(SUBSCRIPTION_TYPE_YEAR, $startTimestamp, $endTimestamp);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $stripePurchases[] = $dayInfo;
            
            if ($dayCount > $maxPurchases)
                $maxPurchases = $dayCount;
            
            $startTimestamp -= 84600; // subtract 24 hours
            $endTimestamp -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxYearIAPPurchases'] = $maxPurchases;
        $responseArray['dailyYearIAPPurchases'] = array_reverse($stripePurchases);
        
        
        // Number of Unique User Synchronizations in past 24 hours
		
		
		//
		// Team Purchases
		//
		
		// Team Month Purchases
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $stripePurchases = array();
        $maxPurchases = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOTeamAccount::getTeamLicensesPurchaseCountInRange(SUBSCRIPTION_TYPE_MONTH, $startTimestamp, $endTimestamp);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $stripePurchases[] = $dayInfo;
            
            if ($dayCount > $maxPurchases)
                $maxPurchases = $dayCount;
            
            $startTimestamp -= 84600; // subtract 24 hours
            $endTimestamp -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxMonthTeamPurchases'] = $maxPurchases;
        $responseArray['dailyMonthTeamPurchases'] = array_reverse($stripePurchases);
        
        // Team Year Purchases
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $stripePurchases = array();
        $maxPurchases = 0;
        for ($i = 0; $i < 14; $i++)
        {
            $dayCount = TDOTeamAccount::getTeamLicensesPurchaseCountInRange(SUBSCRIPTION_TYPE_YEAR, $startTimestamp, $endTimestamp);
            
            $dayInfo = array(
                             "timestamp" => $startTimestamp,
                             "count" => $dayCount
                             );
            
            $stripePurchases[] = $dayInfo;
            
            if ($dayCount > $maxPurchases)
                $maxPurchases = $dayCount;
            
            $startTimestamp -= 84600; // subtract 24 hours
            $endTimestamp -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxYearTeamPurchases'] = $maxPurchases;
        $responseArray['dailyYearTeamPurchases'] = array_reverse($stripePurchases);
        
        $jsonResponse = json_encode($responseArray);
        echo $jsonResponse;
    }
    else if($method == "getSystemTotals")
    {
        $adminLevel = TDOUser::adminLevel($session->getUserId());
        if ( $adminLevel < ADMIN_LEVEL_ROOT )
        {
            $user = TDOUser::getUserForUserId($session->getUserId());
            error_log("Admin - getSystemTotals: User is not root level admin.  Username: " . $user->displayName());
            echo '{"success":false}';
            return;
        }
        
        $responseArray = array("success" => true);
        
        // Total Number of Users
        $totalUserCount = TDOUser::getUserCount();
        $responseArray['totalUserCount'] = $totalUserCount;
        
        
        // New Users Today
        $todayStart = new DateTime();
        $todayStart->setTime(0,0,0);
        $todayEnd = new DateTime();
        $todayEnd->setTime(23,59,59);
        
        $startTimestamp = $todayStart->getTimestamp();
        $endTimestamp = $todayEnd->getTimestamp();
        
        $todayUserCount = TDOUser::getNewUserCountWithDateRange($startTimestamp, $endTimestamp);
        $responseArray['newUsersToday'] = $todayUserCount;
        

        // Total number of users who have connected with Facebook
        
        // Total number of users who have migrated from Todo Online
        
        // Total Number of Tasks
        $totalTaskCount = TDOTask::getTaskCount();
        $responseArray['totalTaskCount'] = $totalTaskCount;

        
        $totalCompletedTaskCount = TDOTask::getCompletedTaskCount();
        $responseArray['totalCompletedTaskCount'] = $totalCompletedTaskCount;

        
        // Total Number of Lists
        $totalListCount = TDOList::getListCount();
        $responseArray['totalListCount'] = $totalListCount;
        
        // Total Number of Shared Lists
        
        
        //
        // Stripe Purchases
        //
        
        // Stripe Month Purchases
        $todayDate = new DateTime();
        $todayString = $todayDate->format("Y-m-d");
		$mountainTimezone = new DateTimeZone("America/Denver");
        
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $dayCount = TDOSubscription::getStripePurchaseCountInRange(SUBSCRIPTION_TYPE_MONTH, $startTimestamp, $endTimestamp);
        $responseArray['stripeMonthPurchasesToday'] = $dayCount;
        
        // Stripe Year Purchases
        
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $dayCount = TDOSubscription::getStripePurchaseCountInRange(SUBSCRIPTION_TYPE_YEAR, $startTimestamp, $endTimestamp);
        $responseArray['stripeYearPurchasesToday'] = $dayCount;
            
        //
        // In-App Purchases
        //
        
        // In-App Month Purchases
        
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $dayCount = TDOInAppPurchase::getIAPPurchaseCountInRange(SUBSCRIPTION_TYPE_MONTH, $startTimestamp, $endTimestamp);
        $responseArray['iapMonthPurchasesToday'] = $dayCount;
        
        // In-App Year Purchases
        
        $dayStart = new DateTime($todayString, $mountainTimezone);
        $dayStart->setTime(0,0,0);
        $dayEnd = new DateTime($todayString, $mountainTimezone);
        $dayEnd->setTime(23,59,59);
        $startTimestamp = $dayStart->getTimestamp();
        $endTimestamp = $dayEnd->getTimestamp();
        
        $dayCount = TDOInAppPurchase::getIAPPurchaseCountInRange(SUBSCRIPTION_TYPE_YEAR, $startTimestamp, $endTimestamp);
        $responseArray['iapYearPurchasesToday'] = $dayCount;
            

        
        $jsonResponse = json_encode($responseArray);
        echo $jsonResponse;
    }
    
    
    

?>

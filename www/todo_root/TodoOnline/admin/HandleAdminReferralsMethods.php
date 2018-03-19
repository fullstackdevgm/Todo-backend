<?php

include_once('TodoOnline/base_sdk.php');


    if($method == "getReferralStats")
    {
        $responseArray = array("success" => true);
        
        //Get totals

        $responseArray['totalReferralAccounts'] = TDOReferral::getTotalNumberOfNewAccountsFromReferrals();
        $responseArray['totalExtensions'] = TDOReferral::getTotalNumberOfExtensionsGivenToReferrers();
        $responseArray['totalUniqueCodes'] = TDOReferral::getTotalUniqueReferralCodesUsed();
        $responseArray['totalUniqueCodesPurchased'] = TDOReferral::getTotalUniqueReferralCodesResultingInAccountExtension();
        
        $responseArray['topReferrers'] = TDOReferral::getTopReferrers();
        $responseArray['topPaidReferrers'] = TDOReferral::getTopPaidReferrers();
        
        date_default_timezone_set("America/Denver");
        $startDate = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
        $endDate = mktime(0, 0, -1, date("n"), (date("j")+1), date("Y"));
        

        $accountsCreatedInfo = array();
        $maxAccountsCreated = 0;
        
        $accountsExtendedInfo = array();
        $maxAccountsExtended = 0;
        
        $uniqueCodesUsedInfo = array();
        $maxUniqueCodesUsed = 0;

        for ($i = 0; $i < 14; $i++)
        {
            //Get info on how many accounts per day have been created from referral links
            $accountsCreatedCount = TDOReferral::getNumberOfAccountsCreatedFromReferralLinksForDateRange($startDate, $endDate);
            $accountsCreated = array(
                             "timestamp" => $startDate,
                             "count" => $accountsCreatedCount
                             );
            
            $accountsCreatedInfo[] = $accountsCreated;
            
            if ($accountsCreatedCount > $maxAccountsCreated)
                $maxAccountsCreated = $accountsCreatedCount;
            
            
            //Get info on how many month increases have been given to referrers
            $accountsExtendedCount = TDOReferral::getNumberOfAccountExtensionsForReferrersForDateRange($startDate, $endDate);
            $accountsExtended = array(
                                "timestamp" => $startDate,
                                "count" => $accountsExtendedCount
                                );
            $accountsExtendedInfo[] = $accountsExtended;
            
            if($accountsExtendedCount > $maxAccountsExtended)
                $maxAccountsExtended = $accountsExtendedCount;
            
            
            //Get info on how many unique referrals are being used per day
            $uniqueCodesCount = TDOReferral::getNumberOfUniqueReferralsUsedInDateRange($startDate, $endDate);
            $uniqueCodes = array(
                            "timestamp" => $startDate,
                            "count" => $uniqueCodesCount
                            );
            $uniqueCodesUsedInfo[] = $uniqueCodes;
            
            if($uniqueCodesCount > $maxUniqueCodesUsed)
                $maxUniqueCodesUsed = $uniqueCodesCount;
            
            
            $startDate -= 84600; // subtract 24 hours
            $endDate -= 84600; // subtract 24 hours
        }
        
        $responseArray['dailyMaxAccountsCreated'] = $maxAccountsCreated;
        $responseArray['dailyAccountsCreated'] = array_reverse($accountsCreatedInfo);
        
        $responseArray['dailyMaxAccountsExtended'] = $maxAccountsExtended;
        $responseArray['dailyAccountsExtended'] = array_reverse($accountsExtendedInfo);
        
        $responseArray['dailyMaxUniqueCodesUsed'] = $maxUniqueCodesUsed;
        $responseArray['dailyUniqueCodesUsed'] = array_reverse($uniqueCodesUsedInfo);
        
        
        $jsonResponse = json_encode($responseArray);
        echo $jsonResponse;
    }
    

?>

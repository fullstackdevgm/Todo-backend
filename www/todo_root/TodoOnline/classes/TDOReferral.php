<?php
	//      TDOSubscription
	//      Used to manage and access subscription information
	
	// include files
	include_once('AWS/sdk.class.php');
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/DBConstants.php');
	
	
	define('REFERRAL_CODE_LENGTH', 6);
	
//	define('REFERRAL_CODE_MAX_ACCRUE_PERIOD', 'P1Y');
	
	define('REFERRAL_CODE_MAX_ACCRUE_COUNT', 12);
	
	define('REFERRAL_CODE_REFERRER_REWARD_DAYS', 31);
	define('REFERRAL_CODE_PURCHASER_REWARD_DAYS', 17);
	
	//
	// Referral Link Error Codes
	//
	define('REFERRAL_CODE_SUCCESS', 0);
	
	define ('REFERRAL_CODE_ERROR_CODE_DB_LINK', 49004);
	define ('REFERRAL_CODE_ERROR_DESC_DB_LINK', _('Could not get a connection to the database.'));
	
	define ('REFERRAL_CODE_ERROR_CODE_NO_RESULT', 49005);
	define ('REFERRAL_CODE_ERROR_DESC_NO_RESULT', _('No result.'));
	
	
	
	
	class TDOReferral
	{
		/** DB Changes Needed
		 
		 
		 tdo_user_referrals
			consumer_userid						KEY
			referral_code						KEY
			purchase_timestamp
		 
		 
		 Redeeming Referral Codes:
		 
		 - When someone signs up for a new account (through the web) with a
		   referral code, we record their userid and the referral code in the
		   tdo_user_referrals table.
		 
		 - When someone purchases ANYTHING (month/year), we go look them up in
		   tdo_user_referrals. If the purchase_timestamp is blank, we then look
		   up the referrer's account and attempt to credit their account. If the
		   referrer's account hasn't already reached the limit on referrals, we
		   credit the referrer's account. We also record the purchase_timestamp
		   so we can track how effective a particular referral code is and how
		   many people are signing up with referral codes.
		 
		 Determining Referral Limits
		 
		 - 
		 
		 */
		
		
//		public static function referralCodeInfoForUserID($userid)
//		{
//            
//        
//			if (empty($userid))
//			{
//				return false;
//			}
//			
//			$referralCode = TDOUserSettings::referralCodeForUserID($userid);
//			if (empty($referralCode))
//			{
//				return false;
//			}
//			
//			$resultArray = array(
//								 "referral_code" => $referralCode,
//								 "max_accrue_count" => REFERRAL_CODE_MAX_ACCRUE_COUNT
//								 );
//			
//			
//			
//			$referralPurchasesInPeriod = TDOReferral::referralPurchaseCountForLatestPeriod($referralCode, REFERRAL_CODE_MAX_ACCRUE_PERIOD);
//			if (!empty($referralPurchasesInPeriod))
//			{
//				$resultArray['referrals_in_period'] = $referralPurchasesInPeriod;
//			}
//			
//			$totalReferrals = TDOReferral::totalReferralsForCode($referralCode);
//			if (!empty($totalReferrals))
//			{
//				$resultArray['total_referrals'] = $totalReferrals;
//			}
//			
//			$subscriptionInfo = TDOSubscription::getSubscriptionInfoForUserID($userid);
//			if (!empty($subscriptionInfo) && !empty($subscriptionInfo['expiration_date']))
//				$resultArray['expiration_date'] = $subscriptionInfo['expiration_date'];
//			
//			return $resultArray;
//		}
		
		
		public static function isValidReferralCode($possibleReferralCode)
		{
			if (empty($possibleReferralCode))
			{
				return false;
			}
			
            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOReferral::isValidReferralCode() failed to get dblink");
				return false;
			}
			
			$totalCount = 0;
			$possibleReferralCode = mysql_real_escape_string($possibleReferralCode, $link);
			$sql = "SELECT COUNT(*) FROM tdo_user_settings WHERE referral_code='$possibleReferralCode'";
			$result = mysql_query($sql, $link);
			if ($result)
			{
				if ($row = mysql_fetch_array($result))
				{
					if (isset($row['0']))
					{
						$totalCount = $row['0'];
					}
				}
			}
			
			TDOUtil::closeDBLink($link);
			
			if ($totalCount > 0)
				return true;
			
			return false;
		}

		
		public static function generatePossibleReferralCode()
		{
			$referralCodeLength = 6;
			$allowedCharacters = "bcdfghjkmnpqrstvwxyz123456789BCDFGHJKLMNPQRSTVWXYZ";
			$length = strlen($allowedCharacters);
			
			$code = "";
			
			for ($i = 0; $i < $referralCodeLength; $i++)
			{
				$characterIsTaken = true;
				$newChar = '';
				
				while ($characterIsTaken == true)
				{
					$newChar = $allowedCharacters[rand() % $length];
					
					if (strpos($code, $newChar) === false)
						$characterIsTaken = false;
					else
						$characterIsTaken = true;
				}
				
				$code .= $newChar;
			}
			
			return $code;
		}
		
		
//		public static function referralPurchaseCountForLatestPeriod($referralCode, $referralPeriod)
//		{
//            $link = TDOUtil::getDBLink();
//            if(!$link)
//			{
//				error_log("TDOReferral::referralPurchaseCountForLatestPeriod() failed to get dblink");
//				return array(
//							 "errcode" => REFERRAL_CODE_ERROR_CODE_DB_LINK,
//							 "errdesc" => REFERRAL_CODE_ERROR_DESC_DB_LINK
//							 );
//			}
//			
//			
//			
//			$referralCode = mysql_real_escape_string($referralCode, $link);
//			
//			$sql = "SELECT COUNT(*) FROM tdo_user_referrals WHERE referral_code='$referralCode' AND purchase_timestamp > 0";
//			if (!empty($referralPeriod))
//			{
//				$nowDate = new DateTime();
//				$pastDate = $nowDate->sub(new DateInterval($referralPeriod));
//				$pastDateTimestamp = $pastDate->getTimestamp();
//				
//				$sql .= " AND purchase_timestamp >= $pastDateTimestamp";
//			}
//			
//			$result = mysql_query($sql, $link);
//			
//			if ($result)
//			{
//				if ($row = mysql_fetch_array($result))
//				{
//					if (isset($row['0']))
//					{
//						$totalCount = $row['0'];
//						if ($totalCount > REFERRAL_CODE_MAX_ACCRUE_COUNT)
//							$totalCount = REFERRAL_CODE_MAX_ACCRUE_COUNT;
//						
//						$resultArray = array(
//											 "count" => $totalCount
//											 );
//						TDOUtil::closeDBLink($link);
//						return $resultArray;
//					}
//				}
//			}
//			
//			TDOUtil::closeDBLink($link);
//			error_log("TDOReferral::referralPurchaseCountForLatestPeriod('$referralCode', '$referralPeriod') unable to get count.");
//			
//			return array(
//						 "errcode" => REFERRAL_CODE_ERROR_CODE_NO_RESULT,
//						 "errdesc" => REFERRAL_CODE_ERROR_DESC_NO_RESULT
//						 );
//		}
		
		
//		public static function totalPurchasedReferralsForCode($referralCode)
//		{
//            $link = TDOUtil::getDBLink();
//            if(!$link)
//			{
//				error_log("TDOReferral::totalPurchasedReferralsForCode failed to get dblink");
//                return false;
//			}
//			
//			$referralCode = mysql_real_escape_string($referralCode, $link);
//			
//			$sql = "SELECT COUNT(*) FROM tdo_user_referrals WHERE referral_code='$referralCode' AND purchase_timestamp > 0";
//			$result = mysql_query($sql, $link);
//			
//			if ($result)
//			{
//				if ($row = mysql_fetch_array($result))
//				{
//					if (isset($row['0']))
//					{
//                        $count = $row['0'];
//						TDOUtil::closeDBLink($link);
//						return $count;
//					}
//				}
//			}
//            else
//                error_log("TDOReferral::totalPurchasedReferralsForCode failed with error: ".mysql_error());
//			
//            
//            TDOUtil::closeDBLink($link);
//            return false;
//		}
		
		
		public static function referralRecordForUserID($userid, $link = NULL)
		{
			if (empty($userid))
			{
				error_log("TDOReferral::referralRecordForUserID() called with empty userid");
				return false;
			}
			
			$closeDBLink = false;
			if ($link == NULL)
			{
				$closeDBLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOReferral::referralRecordForUserID() could not get a link to the DB");
					return false;
				}
			}
			
			$userid = mysql_real_escape_string($userid, $link);
			$sql = "SELECT referral_code, purchase_timestamp FROM tdo_user_referrals WHERE consumer_userid='$userid'";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				if ($closeDBLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$row = mysql_fetch_array($result);
			if (!$row)
			{
				if ($closeDBLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$referralRecord = array (
									 "userid" => $userid,
									 "referral_code" => $row['referral_code']
									 );
			
			if (isset($row['purchase_timestamp']))
				$referralRecord['purchase_timestamp'] = $row['purchase_timestamp'];
			
			if ($closeDBLink)
				TDOUtil::closeDBLink($link);
			
			return $referralRecord;
		}
		
		
		// This should be called on account creation when an account was created
		// from a referral code. We'll also give the new user extra time on
		// their account.
		public static function recordNewReferral($newUserid, $referralCode, $link = NULL)
		{
			if (empty($newUserid))
			{
				error_log("TDOReferral::recordNewReferral() called with empty userid");
				return false;
			}
			
			if (empty($referralCode))
			{
				error_log("TDOReferral::recordNewReferral() called with empty $referralCode");
				return false;
			}
			
			$referralRecord = TDOReferral::referralRecordForUserID($newUserid, $link);
			if (!empty($referralRecord))
			{
				// We've already recorded a referral for this user
				return true;
			}
			
			$closeDBLink = false;
			if ($link == NULL)
			{
				$closeDBLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOReferral::recordNewReferral() could not get a link to the DB");
					return false;
				}
			}
			
			$newUserid = mysql_real_escape_string($newUserid, $link);
			$referralCode = mysql_real_escape_string($referralCode, $link);
			
			$sql = "INSERT INTO tdo_user_referrals (consumer_userid, referral_code) VALUES ('$newUserid', '$referralCode')";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("Failed to record a new referral signup for userid ($newUserid): " . mysql_error());
				if ($closeDBLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			//
			// Extend the purchaser's account by the referral bonus time
			//
			$purchaserSubscription = TDOSubscription::getSubscriptionInfoForUserID($newUserid);
			if (!empty($purchaserSubscription))
			{
				$nowDateTimestamp = time();
				$oldExpirationTimestamp = $purchaserSubscription['expiration_date'];
				
				// Bring the old expiration timestamp at least up to right NOW
				// if it's already expired (this should never happen, but just
				// in case...
				if ($oldExpirationTimestamp < $nowDateTimestamp)
					$oldExpirationTimestamp = $nowDateTimestamp;
				
				$oldExpirationDate = new DateTime('@' . $oldExpirationTimestamp, new DateTimeZone("UTC"));
				
				$extensionInterval = new DateInterval('P' . REFERRAL_CODE_PURCHASER_REWARD_DAYS . 'D');
				$newExpirationDate = $oldExpirationDate->add($extensionInterval);
				
				$newExpirationTimestamp = $newExpirationDate->getTimestamp();
				
				$subscriptionID = $purchaserSubscription['subscription_id'];
				$subscriptionLevel = $purchaserSubscription['subscription_level'];
				$subscriptionTypeString = $purchaserSubscription['subscription_type'];
				$subscriptionType = SUBSCRIPTION_TYPE_UNKNOWN;
				if ($subscriptionTypeString == "month")
					$subscriptionType = SUBSCRIPTION_TYPE_MONTH;
				else if ($subscriptionTypeString == "year")
					$subscriptionType = SUBSCRIPTION_TYPE_YEAR;
				
				if (TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $subscriptionType, $subscriptionLevel, NULL, $link))
				{
					// Log the credit to the user's account
					
					// The extension amount is the number of days that the
					// expiration date is extended because of the referral.
					$extensionDays = REFERRAL_CODE_PURCHASER_REWARD_DAYS;
					
					if (!TDOReferral::logReferralCredit($newUserid, "SIGNUP", $extensionDays, $nowDateTimestamp, $link))
					{
						// This is a soft fail.
						error_log("TDOReferral::recordNewReferral() unable to log the referral credit to the purchaser ($newUserid) (the subscription extension succeeded, we just couldn't add stuff into our db to track it.");
					}
				}
				else
				{
					error_log("TDOReferral::recordNewReferral() unable to extend the purchaser's account ($newUserid)");
				}
			}
			
			if ($closeDBLink)
				TDOUtil::closeDBLink($link);
			
			return true;
		}
		
		
		public static function addReferralEmailUnsubscriber($email)
		{
			// Check first to see if the email is already added
			if (TDOReferral::isReferralEmailAddressUnsubscribed($email))
			{
				// Do nothing. We already have that email
				return;
			}
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOReferral::addReferralEmailUnsubscriber() could not get a link to the DB");
				return false;
			}
			
			$email = mysql_real_escape_string($email, $link);
			$sql = "INSERT INTO tdo_referral_unsubscribers (email) VALUES ('$email')";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("Failed to add a new email address to the referral unsubscribers ($email): " . mysql_error());
				TDOUtil::closeDBLink($link);
				return;
			}
			
			TDOUtil::closeDBLink($link);
			return true;
		}
		
		
		public static function isReferralEmailAddressUnsubscribed($email)
		{
			if (empty($email))
			{
				return false;
			}
			
            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOReferral::isReferralEmailAddressUnsubscribed() failed to get dblink");
				return false;
			}
			
			$totalCount = 0;
			$email = mysql_real_escape_string($email, $link);
			$sql = "SELECT COUNT(*) FROM tdo_referral_unsubscribers WHERE email='$email'";
			$result = mysql_query($sql, $link);
			if ($result)
			{
				if ($row = mysql_fetch_array($result))
				{
					if (isset($row['0']))
					{
						$totalCount = $row['0'];
					}
				}
			}
			
			TDOUtil::closeDBLink($link);
			
			if ($totalCount > 0)
				return true;
			
			return false;
		}
		
		
		// A user just purchased a premium account, so credit the referrer if
		// one exists.
		public static function recordPurchaseForUser($purchaserUserid, $purchaseTimestamp, $link = NULL)
		{
			if (empty($purchaserUserid))
			{
				error_log("TDOReferral::recordPurchaseUserPurchase() called with empty purchaserUserid");
				return false;
			}
			
			if (empty($purchaseTimestamp))
			{
				error_log("TDOReferral::recordPurchaseUserPurchase() called with empty purchaseTimestamp");
				return false;
			}
			
			$referralRecord = TDOReferral::referralRecordForUserID($purchaserUserid);
			if (empty($referralRecord))
			{
				// This user did not sign up with a referral. Do nothing.
				return false;
			}
			
			// If there is already a purchase_timestamp on the purchaser's
			// referral record, they have already credited the original
			// referrer. Do nothing.
			if (isset($referralRecord['purchase_timestamp']) && ($referralRecord['purchase_timestamp'] > 0) )
			{
				// Sure enough, we've already credited the referrer and don't
				// need to do anything in this case.
				return false;
			}
			
			// If we make it this far, we should credit the referrer if needed.
			
			$referralCode = $referralRecord['referral_code'];
			$referrerUserid = TDOUserSettings::getUserIDForReferralCode($referralCode);
			
			if (empty($referrerUserid))
			{
				error_log("TDOReferral::recordPurchaseForUser() could not determine original referrer for referral code ($referralCode)");
				return false;
			}
			
			$referrerInfo = TDOReferral::accountExtensionInfoForUserId($referrerUserid);
			if (empty($referrerInfo))
			{
				error_log("TDOReferral::recordPurchaseForUser() unable to read referrer info for userid ($referrerUserid)");
				return false;
			}
			
			
			//--------------- THIS IS WHERE ALL THE ACTION HAPPENS -----------//
			
			//
			// 1. Record the purchase_timestamp on the purchaser's referral record
			//
			
			TDOReferral::recordPurchaserTimestamp($purchaserUserid, $purchaseTimestamp, $link);
			
			//
			// 2. Extend the referrer's account by the referral bonus time if
			//    they are eligible.
			//
			
			$extendedReferrerAccount = false;
            $autorenewingIAPDetected = false;
			
			// Check to see if the referrer is eligible to receive a reward for
			// the referral.
			$eligibleExtensionCount = $referrerInfo['eligible_extensions'];
			if ($eligibleExtensionCount > 0)
			{
                if(TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($referrerUserid) == false)
                {
                    // The referrer should be credited the amount allowed by the
                    // referral.
                    $extendedReferrerAccount = true;
                    
                    $referrerSubscription = TDOSubscription::getSubscriptionInfoForUserID($referrerUserid);
                    if (!empty($referrerSubscription))
                    {
                        $nowDateTimestamp = $purchaseTimestamp;
                        $oldExpirationTimestamp = $referrerSubscription['expiration_date'];
                        
                        // Bring the old expiration timestamp at least up to right NOW
                        // if it's already expired.
                        if ($oldExpirationTimestamp < $nowDateTimestamp)
                            $oldExpirationTimestamp = $nowDateTimestamp;
                        
                        $oldExpirationDate = new DateTime('@' . $oldExpirationTimestamp, new DateTimeZone("UTC"));
                        
                        $extensionInterval = new DateInterval('P' . REFERRAL_CODE_REFERRER_REWARD_DAYS . 'D');
                        $newExpirationDate = $oldExpirationDate->add($extensionInterval);
                        
                        $newExpirationTimestamp = $newExpirationDate->getTimestamp();
                        
                        $subscriptionID = $referrerSubscription['subscription_id'];
                        $subscriptionLevel = $referrerSubscription['subscription_level'];
                        $subscriptionTypeString = $referrerSubscription['subscription_type'];
						$subscriptionType = SUBSCRIPTION_TYPE_UNKNOWN;
						if ($subscriptionTypeString == "month")
							$subscriptionType = SUBSCRIPTION_TYPE_MONTH;
						else if ($subscriptionType == "year")
							$subscriptionType = SUBSCRIPTION_TYPE_YEAR;
                        
                        if (TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $subscriptionType, $subscriptionLevel, NULL, $link))
                        {
                            // Log the credit to the user's account
                            
                            // The extension amount is the number of days that the
                            // expiration date is extended because of the referral.
                            $extensionDays = REFERRAL_CODE_REFERRER_REWARD_DAYS;
                            
                            if (!TDOReferral::logReferralCredit($referrerUserid, $purchaserUserid, $extensionDays, $nowDateTimestamp, $link))
                            {
                                // This is a soft fail.
                                error_log("TDOReferral::recordPurchaseForUser() unable to log the referral credit to the referrer ($referrerUserid) (the subscription extension succeeded, we just couldn't add stuff into our db to track it.");
                            }
                        }
                        else
                        {
                            error_log("TDOReferral::recordPurchaseForUser() unable to extend the referrer's account ($referrerUserid) with user purchase ($purchaserUserid)");
                        }
                    }
                }
                else
                    $autorenewingIAPDetected = true;
			}
			
			// 3. Email the referrer
			$referrerEmailAddress = TDOUser::usernameForUserId($referrerUserid);
			if (!empty($referrerEmailAddress))
			{
				$firstName = TDOUser::firstNameForUserId($referrerUserid);
				if (empty($firstName))
					$firstName = TDOUser::displayNameForUserId($referrerUserid);
				
				TDOMailer::sendReferralLinkPurchaseNotification($referrerEmailAddress, $firstName, $extendedReferrerAccount, $autorenewingIAPDetected);
			}
			
			return true;
		}
		
        public static function accountExtensionInfoForUserId($userid)
        {
            //From boyd - the user should be able to extend their account out a year using referral codes. E.g.
            //if they had 12 extensions from referral codes in January, they should be eligible for one extension
            //in February so their account is still out one year.
            
            $returnData = array();
            
            $extensionHistory = TDOReferral::getExtensionHistoryForReferrer($userid);
            if($extensionHistory === false)
            {
                error_log("accountExtensionInfoForUserId unable to get extension history for user: $userid");
                return false;
            }
            
            $returnData['extensions_received'] = count($extensionHistory);
            
            //If the user has not had any extensions, they are eligible for the max amount
            if(empty($extensionHistory))
            {
                $returnData['eligible_extensions'] = REFERRAL_CODE_MAX_ACCRUE_COUNT;
            }
            else
            {
                //ncb - Iterate through all extensions a user has received and calculate what their expiration date would be
                //based on the referral extensions. Then make sure this can't be pushed farther than 1 year out from the
                //current date
                
            
                //The maximum amt we should extend a user's account from today will be
                //the max_accrue_count times the referrer_reward_days (should come out to about a year)
                $maxExtensionDays = REFERRAL_CODE_MAX_ACCRUE_COUNT * REFERRAL_CODE_REFERRER_REWARD_DAYS;
                $maxExtensionExpirationDate = mktime(23, 23, 59, date("n"), date("j") + $maxExtensionDays, date("Y"));
//                error_log("Max extension expiration date: ".date("m-d-Y", $maxExtensionExpirationDate));
            
                $currentExtensionExpirationDate = 0;
                foreach($extensionHistory as $accountExtension)
                {
//                    error_log(" ***extension history***");
                    $accountExtensionDate = $accountExtension['timestamp'];
//                    error_log("    Extended on: ".date("m-d-Y", $accountExtensionDate));
                    if($currentExtensionExpirationDate < $accountExtensionDate)
                    {
                        $currentExtensionExpirationDate = $accountExtensionDate;
//                        error_log("    Setting new current expiration date");
                    }
                    $accountExtensionDays = $accountExtension['extension_days'];
//                    error_log("    Extension days: ".$accountExtensionDays);
                    $currentExtensionExpirationDate += ($accountExtensionDays * 86400);
//                    error_log("    New current expiration date: ".date("m-d-Y", $currentExtensionExpirationDate));
                }
                
                //$currentExtensionExpirationDate now holds the expiration date the user would be at based solely on
                //their referral extensions
                $currentTime = time();
                if($currentExtensionExpirationDate < $currentTime)
                    $currentExtensionExpirationDate = $currentTime;
                
                $eligibleExtensions = 0;
                if($currentExtensionExpirationDate < $maxExtensionExpirationDate)
                {
                    //Calculate the number of extensions a user is eligible for right now
                    $eligibleExtensions = (int)(($maxExtensionExpirationDate - $currentExtensionExpirationDate) / (REFERRAL_CODE_REFERRER_REWARD_DAYS * 86400));
                }
                $returnData['eligible_extensions'] = $eligibleExtensions;
                
                if($eligibleExtensions == 0)
                {
                    //Caluclate the date at which the user will be eligible for another account extension
                    $newDateAfterNextExtension = $currentExtensionExpirationDate + (REFERRAL_CODE_REFERRER_REWARD_DAYS * 86400);
                    $dateEligible = $newDateAfterNextExtension - ($maxExtensionDays * 86400);
                    $returnData['eligible_extension_date'] = $dateEligible;
                }
                
            }
            
            $referralCode = TDOUserSettings::referralCodeForUserID($userid);
            
            if (empty($referralCode))
            {
                error_log("accountExtensionInfoForUserId unable to get referral code for user: $userid");
                return false;
            }
            
            $returnData['referral_code'] = $referralCode;
            
            //Also return the total number of referrals resulting in new accounts
			$totalReferrals = TDOReferral::getAllReferralCountForReferralCode($referralCode);
            $returnData['total_referrals'] = intval($totalReferrals);
            
            $subscriptionInfo = TDOSubscription::getSubscriptionInfoForUserID($userid);
            if (!empty($subscriptionInfo) && !empty($subscriptionInfo['expiration_date']))
                $returnData['expiration_date'] = $subscriptionInfo['expiration_date'];
            
            
            return $returnData;
        }
        
        //Returns info about all the times a user's account has been extended because somebody
        //used their referral link
        public static function getExtensionHistoryForReferrer($userId, $link=NULL)
        {
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                    return false;
            }
            else
                $closeDBLink = false;
            
            $sql = "SELECT * FROM tdo_referral_credit_history WHERE userid='".mysql_real_escape_string($userId, $link)."' AND consumer_userid IS NOT NULL AND consumer_userid != 'SIGNUP' ORDER BY timestamp ASC";
            $result = mysql_query($sql, $link);
            if($result)
            {
                $history = array();
                while($row = mysql_fetch_array($result))
                {
                    $history[] = $row;
                }
                
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return $history;
            }
            else
                error_log("getExtensionHistoryForReferrer failed with error: ".mysql_error());
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }

		
		public static function logReferralCredit($userid, $consumerUserid, $extensionAmount, $timestamp, $link = NULL)
		{
			if (empty($userid))
			{
				error_log("TDOReferral::logReferralCredit() called with empty userid");
				return false;
			}
			
			if (empty($consumerUserid))
			{
				error_log("TDOReferral::logReferralCredit() called with empty consumerUserid");
				return false;
			}
			
			$closeDBLink = false;
			if ($link == NULL)
			{
				$closeDBLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOReferral::logReferralCredit() could not get a link to the DB");
					return false;
				}
			}
			
			$userid = mysql_real_escape_string($userid, $link);
			$consumerUserid = mysql_real_escape_string($consumerUserid, $link);
			
			$sql = "INSERT INTO tdo_referral_credit_history (userid, consumer_userid, extension_days, timestamp) VALUES ('$userid', '$consumerUserid', $extensionAmount, $timestamp)";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOReferral::logReferralCredit() Failed to add a record of a referral credit for user ($userid): " . mysql_error());
				if ($closeDBLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if ($closeDBLink)
				TDOUtil::closeDBLink($link);
			
			return true;
		}
		
		
		public static function getReferralCreditHistoryForUserId($userid, $link = NULL)
		{
            if(empty($userid))
            {
                error_log("TDOReferral::getReferralCreditHistoryForUserId() failed because userid is empty");
                return false;
            }
            
            $history = array();
			
			$closeDBLink = false;
			if ($link == NULL)
			{
				$closeDBLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOReferral::getReferralCreditHistoryForUserId() could not get a link to the DB");
					return false;
				}
			}
			
			$userid = mysql_real_escape_string($userid, $link);
			$sql = "SELECT extension_days,timestamp FROM tdo_referral_credit_history WHERE userid='$userid'";
			
			$result = mysql_query($sql, $link);
			if($result)
			{
				while($row = mysql_fetch_array($result))
				{
					$extensionDays = $row['extension_days'];
					$timestamp = $row['timestamp'];
					$subscriptionType = "referral";
					
					$description = "Extended $extensionDays days";
					
					$creditRecord = array(
										  "timestamp" => $timestamp,
										  "subscriptionType" => $subscriptionType,
										  "description" => $description
										  );
					$history[] = $creditRecord;
				}
			}
			else
			{
				error_log("TDOReferral::getReferralCreditHistoryForUserId($userid) failed with error: ".mysql_error());
			}
			
			if ($closeDBLink)
				TDOUtil::closeDBLink($link);
            
            return $history;
		}
		
		
		public static function recordPurchaserTimestamp($userid, $purchaseTimestamp, $link = NULL)
		{
			if (empty($userid))
			{
				error_log("TDOReferral::recordPurchaserTimestamp() called with empty purchaserUserid");
				return false;
			}
			
			if (empty($purchaseTimestamp))
			{
				error_log("TDOReferral::recordPurchaserTimestamp() called with empty purchaseTimestamp");
				return false;
			}
			
			$closeDBLink = false;
			if ($link == NULL)
			{
				$closeDBLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOReferral::recordPurchaserTimestamp() could not get a link to the DB");
					return false;
				}
			}
			
			$userid = mysql_real_escape_string($userid, $link);
			$sql = "UPDATE tdo_user_referrals SET purchase_timestamp = $purchaseTimestamp WHERE consumer_userid='$userid'";
			$response = mysql_query($sql, $link);
			if($response)
            {
                TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOSubscription::updateUserPaymentSystemInfo() failed: ".mysql_error());
			}
            
            TDOUtil::closeDBLink($link);
            return false;
		}
		
        //ADMIN METHODS
        
        //This will return the number of accounts created from referral links between startDate and endDate (including the endpoints)
        public static function getNumberOfAccountsCreatedFromReferralLinksForDateRange($startDate, $endDate)
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
                return false;
            
            $startDate = intval($startDate);
            $endDate = intval($endDate);
            
            $sql = "SELECT COUNT(*) FROM tdo_referral_credit_history WHERE consumer_userid='SIGNUP' AND timestamp >= ".$startDate." AND timestamp <= ".$endDate;
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result)) 
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        TDOUtil::closeDBLink($link);
                        return $count;
                    }
                    
                }
            }
            else
                error_log("getNumberOfAccountsCreatedFromReferralLinksForDateRange failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
            
        }
		
        //This will return the number of 30-day increases we have given to referrers between startDate and endDate (including the endpoints)
        public static function getNumberOfAccountExtensionsForReferrersForDateRange($startDate, $endDate)
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
                return false;
            
            $startDate = intval($startDate);
            $endDate = intval($endDate);
            
            $sql = "SELECT COUNT(*) FROM tdo_referral_credit_history WHERE consumer_userid IS NOT NULL AND consumer_userid != 'SIGNUP' AND timestamp >= ".$startDate." AND timestamp <= ".$endDate;
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        TDOUtil::closeDBLink($link);
                        return $count;
                    }
                    
                }
            }
            else
                error_log("getNumberOfAccountExtensionsForReferrersForDateRange failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        //This will return the number of unique referrals being used to sign up between startDate and endDate (including the endpoints)
        public static function getNumberOfUniqueReferralsUsedInDateRange($startDate, $endDate)
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
                return false;
            
            $startDate = intval($startDate);
            $endDate = intval($endDate);
            
            $sql = "SELECT COUNT(DISTINCT referral_code) FROM tdo_user_referrals INNER JOIN tdo_referral_credit_history ON tdo_user_referrals.consumer_userid = tdo_referral_credit_history.userid WHERE tdo_referral_credit_history.consumer_userid='SIGNUP' AND timestamp >= ".$startDate." AND timestamp <= ".$endDate;
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        TDOUtil::closeDBLink($link);
                        return $count;
                    }
                    
                }
            }
            else
                error_log("getNumberOfUniqueReferralsUsedInDateRange failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        public static function getTotalNumberOfNewAccountsFromReferrals()
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
                return false;
            
            $sql = "SELECT COUNT(*) FROM tdo_referral_credit_history WHERE consumer_userid='SIGNUP'";
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        TDOUtil::closeDBLink($link);
                        return $count;
                    }
                    
                }
            }
            else
                error_log("getTotalNumberOfNewAccountsFromReferrals failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        public static function getTotalNumberOfExtensionsGivenToReferrers()
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
                return false;
            
            $sql = "SELECT COUNT(*) FROM tdo_referral_credit_history WHERE consumer_userid IS NOT NULL AND consumer_userid != 'SIGNUP'";
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        TDOUtil::closeDBLink($link);
                        return $count;
                    }
                    
                }
            }
            else
                error_log("getTotalNumberOfExtensionsGivenToReferrers failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        
        public static function getTotalUniqueReferralCodesUsed()
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
                return false;
            
            $sql = "SELECT COUNT(DISTINCT referral_code) FROM tdo_user_referrals INNER JOIN tdo_referral_credit_history ON tdo_user_referrals.consumer_userid = tdo_referral_credit_history.userid WHERE tdo_referral_credit_history.consumer_userid='SIGNUP'";
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        TDOUtil::closeDBLink($link);
                        return $count;
                    }
                    
                }
            }
            else
                error_log("getTotalUniqueReferralCodesUsed failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        
        public static function getTotalUniqueReferralCodesResultingInAccountExtension()
        {
            //For this one we'll want to find referral codes with consumers who are also consumers in the referral history table
            $link = TDOUtil::getDBLink();
            if(empty($link))
                return false;

            
            $sql = "SELECT COUNT(DISTINCT referral_code) FROM tdo_user_referrals INNER JOIN tdo_referral_credit_history ON tdo_user_referrals.consumer_userid = tdo_referral_credit_history.consumer_userid";
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        TDOUtil::closeDBLink($link);
                        return $count;
                    }
                    
                }
            }
            else
                error_log("getTotalUniqueReferralCodesResultingInAccountExtension failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
            
        }
        
        //This will return usernames of the people whose referral codes have resulted in the most new accounts
        public static function getTopReferrers($limit=10)
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
                return false;
            
            $sql = "SELECT referral_code, COUNT(referral_code) AS referralcount FROM tdo_user_referrals GROUP BY referral_code ORDER BY referralcount DESC LIMIT ".intval($limit);
            
            $result = mysql_query($sql, $link);
            $users = array();
            if($result)
            {
                while($row = mysql_fetch_array($result))
                {
                    if(isset($row['referral_code']) && isset($row['referralcount']))
                    {
                        $referralCode = $row['referral_code'];
                        $referrerUserid = TDOUserSettings::getUserIDForReferralCode($referralCode);
                        if(!empty($referrerUserid))
                        {
                            $extensionCount = TDOReferral::getExtensionCountForReferrer($referrerUserid, $link);
                            
                            $username = TDOUser::usernameForUserId($referrerUserid);
                            $userInfo = array("username"=>$username, "userid"=>$referrerUserid, "referralcount"=>$row['referralcount'], "extensioncount"=>$extensionCount);
                            $users[] = $userInfo;
                        }
                    }
                    
                }
                
                TDOUtil::closeDBLink($link);
                return $users;
            }
            else
                error_log("getTopReferrers failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        //This will return usernames of the people who have gotten the most extensions on their accounts
        public static function getTopPaidReferrers($limit=10)
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
                return false;
            
            $sql = "SELECT userid, COUNT(userid) AS extensioncount FROM tdo_referral_credit_history WHERE consumer_userid IS NOT NULL AND consumer_userid != 'SIGNUP' GROUP BY userid ORDER BY extensioncount DESC LIMIT ".intval($limit);
            
            $result = mysql_query($sql, $link);
            $users = array();
            if($result)
            {
                while($row = mysql_fetch_array($result))
                {
                    if(isset($row['userid']) && isset($row['extensioncount']))
                    {
                        $referrerUserid = $row['userid'];
                        if(!empty($referrerUserid))
                        {
                            $referralCode = TDOUserSettings::referralCodeForUserID($referrerUserid);
                            $referralCount = TDOReferral::getAllReferralCountForReferralCode($referralCode, $link);
                        
                            $username = TDOUser::usernameForUserId($referrerUserid);
                            $userInfo = array("username"=>$username, "userid"=>$referrerUserid, "referralcount"=>$referralCount, "extensioncount"=>$row['extensioncount']);
                            $users[] = $userInfo;
                        }
                    }
                    
                }
                
                TDOUtil::closeDBLink($link);
                return $users;
            }
            else
                error_log("getTopPaidReferrers failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        //Returns the total of all referrals for a given code, regardless of whether a purchase has been made on them
        public static function getAllReferralCountForReferralCode($referralCode, $link=NULL)
        {
            if(empty($link))

            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                    return false;
            }
            else
                $closeDBLink = false;
            
            $sql = "SELECT COUNT(consumer_userid) FROM tdo_user_referrals WHERE referral_code='".mysql_real_escape_string($referralCode, $link)."'";
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
                        return $count;
                    }
                    
                }
            }
            else
                error_log("getAllReferralCountForReferralCode failed with error: ".mysql_error());
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
        
        //Returns the number of times a user's account has been extended because they sent a referral
        public static function getExtensionCountForReferrer($userId, $link=NULL)
        {
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                    return false;
            }
            else
                $closeDBLink = false;
            
            $sql = "SELECT COUNT(*) FROM tdo_referral_credit_history WHERE userid='".mysql_real_escape_string($userId, $link)."' AND consumer_userid IS NOT NULL AND consumer_userid != 'SIGNUP'";
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
                        return $count;
                    }
                    
                }
            }
            else
                error_log("getExtensionCountForReferrer failed with error: ".mysql_error());
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
	}

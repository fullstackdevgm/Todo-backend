<?php
	//      TDOInAppPurchase
	
	// include files
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/DBConstants.php');
	
	define ('IAP_ITUNES_SECRET_KEY', '9436d03fa9e34156b63be966b4f8b748');
    define ('SUBSCRIPTION_IAP_SERVER_URL_PRODUCTION', 'https://buy.itunes.apple.com/verifyReceipt');
	define ('SUBSCRIPTION_IAP_SERVER_URL_SANDBOX', 'https://sandbox.itunes.apple.com/verifyReceipt');
		
	class TDOInAppPurchase
	{
                    
		public static function logIAPPayment($userID, $transactionID, $productID, $purchaseDate, $appItemID, $versionExternalID, $bundleID, $appVersion)
		{
			if(empty($userID))
			{
				error_log("TDOInAppPurchase::logIAPPayment() failed because userID is empty");
				return false;
			}
			if(empty($transactionID))
			{
				error_log("TDOInAppPurchase::logIAPPayment() failed because transactionID is empty");
				return false;
			}
			if(empty($productID))
			{
				error_log("TDOInAppPurchase::logIAPPayment() failed because productID is empty");
				return false;
			}
			if(empty($purchaseDate))
			{
				error_log("TDOInAppPurchase::logIAPPayment() failed because purchaseDate is empty");
				return false;
			}
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOInAppPurchase::logIAPPayment() failed to get dblink");
				return false;
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			$transactionID = mysql_real_escape_string($transactionID, $link);
			$productID = mysql_real_escape_string($productID, $link);
			$purchaseDate = mysql_real_escape_string($purchaseDate, $link);
			
			if (!empty($appItemID))
				$appItemID = mysql_real_escape_string($appItemID, $link);
			if (!empty($versionExternalID))
				$versionExternalID = mysql_real_escape_string($versionExternalID, $link);
			if (!empty($bundleID))
				$bundleID = mysql_real_escape_string($bundleID, $link);
			if (!empty($appVersion))
				$appVersion = mysql_real_escape_string($appVersion, $link);
			
			$sql = "INSERT INTO tdo_iap_payment_history (userid, product_id, transaction_id, purchase_date, app_item_id, version_external_identifier, bid, bvrs) VALUES "
					. "('$userID', '$productID', '$transactionID', '$purchaseDate', ";
			if (isset($appItemID))
				$sql .= "'$appItemID', ";
			else
				$sql .= "NULL, ";
			
			if (isset($versionExternalID))
				$sql .= "'$versionExternalID', ";
			else
				$sql .= "NULL, ";

			if (isset($bundleID))
				$sql .= "'$bundleID', ";
			else
				$sql .= "NULL, ";

			if (isset($appVersion))
				$sql .= "'$appVersion')";
			else
				$sql .= "NULL)";
			
//			error_log("SQL LOG ----------- " . $sql);
			
			$response = mysql_query($sql, $link);
			if($response)
            {
                TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOInAppPurchase::logIAPPayment() failed: ".mysql_error());
			}
            
            TDOUtil::closeDBLink($link);
            return false;
		}
		
		
		public static function logGooglePlayPayment($userID, $productID, $purchaseDate, $expirationDate)
		{
			if(empty($userID))
			{
				error_log("TDOInAppPurchase::logGooglePlayPayment() failed because userID is empty");
				return false;
			}
			if(empty($productID))
			{
				error_log("TDOInAppPurchase::logGooglePlayPayment() failed because $productID is empty");
				return false;
			}
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOInAppPurchase::logGooglePlayPayment() failed to get dblink");
				return false;
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			$productID = mysql_real_escape_string($productID, $link);
			$purchaseDate = intval($purchaseDate);
			$expirationDate = intval($expirationDate);
			
			
			$sql = "INSERT INTO tdo_googleplay_payment_history (userid, product_id, purchase_timestamp, expiration_timestamp) VALUES "
			. "('$userID', '$productID', $purchaseDate, $expirationDate)";
			
			$response = mysql_query($sql, $link);
			if($response)
            {
                TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOInAppPurchase::logGooglePlayPayment() failed: ".mysql_error());
			}
            
            TDOUtil::closeDBLink($link);
            return false;
		}
		
		
        public static function validateIAPReceipt($receiptData, $userID, $password=NULL)
        {
            if (empty($receiptData))
                return array("error" => SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER,  "error_desc" => SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER);
            
            // Ensure that this is a valid receipt by validating the receiptData
            // with the app store server.

            $arrayToSend = array('receipt-data' => $receiptData);
//            error_log("Setting receipt data: $receiptData");
            if(!empty($password))
            {
                $arrayToSend['password'] = $password;
            }
            $payload = json_encode($arrayToSend);
            
            $serverURL = SUBSCRIPTION_IAP_SERVER_URL_PRODUCTION;
            
        RETRY_WITH_SANDBOX:
            
            $response = NULL;
            try
            {
                $response = TDOUtil::postRequest($serverURL, $payload);
            }
            catch (Exception $e)
            {
                error_log("Exception calling iTunes Store (" . SUBSCRIPTION_IAP_SERVER_URL_PRODUCTION . "): " . $e->getMessage());
                return array("error" => SUBSCRIPTION_ERROR_CODE_APP_STORE_BAD_RESPONSE, "error_desc" => SUBSCRIPTION_ERROR_DESC_APP_STORE_BAD_RESPONSE);
            }
            
            if (!$response)
            {
                error_log("Tried to validate the receipt with iTunes server and got no response (userid: $userID).");
                return array("error" => SUBSCRIPTION_ERROR_CODE_APP_STORE_BAD_RESPONSE, "error_desc" => SUBSCRIPTION_ERROR_DESC_APP_STORE_BAD_RESPONSE);
            }
            
            // Check to see if the return code
            $status = $response['status'];
            if ($status != 0)
            {
                // A status other than 0 indicates that the receipt is invalid.
                // From the API doc, "If the value of the status key is 0, this is a
                // valid receipt. If the value is anything other than 0, this
                // receipt is invalid.
                
                if ($status == 21007)
                {
                    // If we get this error, it means we could be testing this on
                    // the sandbox server. Having this code here lets our server
                    // work with the IAP Sandbox server and the IAP production
                    // server. We try the real iTunes server first and then the
                    // sandbox server.
                    
                    // We need to retry everything using the sandbox server
                    $serverURL = SUBSCRIPTION_IAP_SERVER_URL_SANDBOX;
                    goto RETRY_WITH_SANDBOX;
                }
                else
                {
                    error_log("$$$$$ ------ iTunes has reported that the IAP receipt is BAD for user (userid: $userID) and received status code : " . $status);
                    return array("status_code" => $status);
                }
            }
            
            $receipt = $response['receipt'];
            if (!$receipt)
            {
                error_log("Response from IAP iTunes server did not return a receipt for userid: $userID.");
                return array("error" => SUBSCRIPTION_ERROR_CODE_APP_STORE_MISSING_RECEIPT, "error_desc" => SUBSCRIPTION_ERROR_DESC_APP_STORE_MISSING_RECEIPT);
            }
			
			error_log("TDOInAppPurchase::validateIAPReceipt(" . $userID . "): " . json_encode($response));
			
			// Look for the newest expires_date. If expires_date doesn't exist,
			// we shouldn't save it or process it because it's likely an old
			// subscription type that we no longer support (the non-renewable
			// kind).
			if (!empty($receipt['in_app']))
			{
				$latestIAPReceipt = NULL;
				$inAppPurchaseReceipts = $receipt['in_app'];
//				error_log("+++++ Detected " . count($inAppPurchaseReceipts) . " IAP receipts.");
				foreach ($inAppPurchaseReceipts as $iapReceipt)
				{
					// Skip over every receipt that is old (it won't have an
					// expires_date).
					if (empty($iapReceipt['expires_date']))
					{
						continue;
					}
					
					if ($latestIAPReceipt == NULL)
					{
						$latestIAPReceipt = $iapReceipt;
						continue;
					}
					
					// Check to see if the current iapReceipt is newer
					$expiresDate = $latestIAPReceipt['expires_date'];
					if (!empty($expiresDate))
					{
						$currentExpiresDate = $iapReceipt['expires_date'];
						if (!empty($currentExpiresDate))
						{
							if (strcmp($expiresDate, $currentExpiresDate) < 0)
							{
								// The current receipt in the for loop must be
								// newer than the one we've previously saved, so
								// set that as the latest IAP receipt
								$latestIAPReceipt = $iapReceipt;
//								error_log("+++++ Found newer IAP receipt: " . $currentPurchaseDate);
							}
						}
					}
				}
				
				if ($latestIAPReceipt != NULL)
				{
					$receipt = $latestIAPReceipt;
//					error_log("+++++ Using IAP receipt with purchase date of: " . $receipt['purchase_date']);
//					error_log("+++++ EXPIRATION DATE: " . $receipt['expires_date']);
				}
			}
			
            $returnArray = array("receipt" => $receipt);
            
            if(isset($response['latest_receipt']))
                $returnArray['latest_receipt'] = $response['latest_receipt'];
			
            if(isset($response['latest_receipt_info']))
                $returnArray['latest_receipt_info'] = $response['latest_receipt_info'];
			
            return $returnArray;
        }
		
		
		// On success, this function returns an array with the key:
		//		expiration_date_in_seconds
		public static function readExpirationDateFromGooglePlayToken($token, $productId, $userid)
		{
            if (empty($token) || empty($productId) || empty($userid))
                return array("error" => SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER,  "error_desc" => SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER);
			
			// Use the OAuth Access Token to make this authenticated call
			$accessToken = TDOUtil::getStringSystemSetting(SYSTEM_SETTING_GOOGLE_PLAY_ACCESS_TOKEN, "bGoa+V7g/yqDXvKRqq+JTFn4uQZbPiQJo4pf9RzJ");
//			if (!$accessToken)
//			{
//                // put in a bogus accessToken so one will get populated
//                $accessToken = "bGoa+V7g/yqDXvKRqq+JTFn4uQZbPiQJo4pf9RzJ";
//			}
			
			$fullURL = "https://www.googleapis.com/androidpublisher/v1/applications/com.appigo.todopro/subscriptions/" . $productId . "/purchases/" . $token . "?access_token=" . $accessToken;
			//$fullURL = "https://www.googleapis.com/androidpublisher/v1/applications/com.appigo.todopro/subscriptions/" . $productId . "/purchases/sryrhkpohhtbjmrbqgtzleta.AO-J1OzmKoPJyYfEmUn0GPcVyRMu1fJz76C7WOsrXBRHdGyzrJYmAYIHU5X1WdKB7CGZK4tEhGnp85tN0wHlQMi6KisPMeb2-o2e5NedQOce1KEiP9SHe2WaBnLrFMK80l_qiIbK8ZMHRy02c5MH7SAMmYidQX9v-_RGkd9NMfzSoiiOYD95q7Vi8imyBQPjlV--TmcJRjDR?access_token=" . $accessToken;
			//$fullURL = "https://www.googleapis.com/androidpublisher/v1/applications/com.appigo.todopro/subscriptions/" . $productId . "/purchases/PcVyRMu1fJz76C7WOsrXBRHdGyzrJYmAYIHU5X1WdKB7CGZK4tEhGnp85tN0wHlQMi6KisPMeb2-o2e5NedQOce1KEiP9SHe2WaBnLrFMK80l_qiIbK8ZMHRy02c5M?access_token=" . $accessToken;
			
			$retryAttempts = 0;
			
		RETRY_WITH_NEW_ACCESS_TOKEN:
			
			$response = NULL;
            try
            {
                $response = TDOUtil::getRequest($fullURL);
            }
            catch (Exception $e)
            {
                $errorCode = $e->getCode();
                
				switch ($errorCode)
				{
                    // ERROR CODE 401 - INVALID ACCESS CODE
                    // ERROR CODE 404 - INVALID TOKEN                        
                        
					case 401:	// Invalid Access Code
						if ($retryAttempts >= 3)
						{
							// We've tried too many times to get a valid token.
							error_log("TDOInAppPurchase::readExpirationDateFromGooglePlayToken() Failed to get a valid access_token via Google OAuth after $retryAttempts times (userid: $userid).");
							return array("error" => SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_REFRESH_ACCESS_TOKEN_FAILED, "error_desc" => SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_REFRESH_ACCESS_TOKEN_FAILED);
						}
						
						$retryAttempts++;
						TDOUtil::requestNewGooglePlayAccessToken();
						goto RETRY_WITH_NEW_ACCESS_TOKEN;
						break;
					case 404:	// Invalid Google Play Subscription Purchase Token
						// In this case, Google is telling us that the token we
						// passed to them is invalid, so we can positively
						// respond with an error.
						error_log("TDOInAppPurchase::readExpirationDateFromGooglePlayToken() We were passed an invalid Google Play token (userid: $userid).");
						return array("error" => SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_INVALID_TOKEN, "error_desc" => SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_INVALID_TOKEN);
						break;
					default:	// Unknown, so we'll try up to thee more times before failing completely
						if ($retryAttempts < 3)
						{
							// Maybe a glitch happened. Retry.
							$retryAttempts++;
							goto RETRY_WITH_NEW_ACCESS_TOKEN;
						}
						break;
				}
                
				// If the code makes it to this point, that means we've retried
				// three times with an unknown error, so return an error at
				// this point.
				error_log("TDOInAppPurchase::readExpirationDateFromGooglePlayToken() Failed to properly communicate with Google Play to validate a purchase token.");
				return array("error" => SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_TOKEN_VERIFY_RETRY_FAILED, "error_desc" => SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_TOKEN_VERIFY_RETRY_FAILED);
            }
            
            if (!$response)
            {
                error_log("TDOInAppPurchase::readExpirationDateFromGooglePlayToken() Tried to validate a token with Google Play and got no response (userid: $userid).");
                return array("error" => SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_BAD_RESPONSE, "error_desc" => SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_BAD_RESPONSE);
            }
			
			// If we get to this point, we have info about the purchase token.
			
			/* VALID RESPONSE
			 
			 {
				 "kind": "androidpublisher#subscriptionPurchase",
				 "initiationTimestampMsec": long,
				 "validUntilTimestampMsec": long,
				 "autoRenewing": boolean
			 }
			 
			 */
			
			if (!isset($response['validUntilTimestampMsec']))
			{
				// We didn't get a valid timestamp
				error_log("Google Play token verification is missing a validUntilTimestampMsec.");
				return array("error" => SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_MISSING_VALID_UNTIL_FIELD, "error_desc" => SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_MISSING_VALID_UNTIL_FIELD);
			}
			
			$validUntilMsec = $response['validUntilTimestampMsec'];
			$purchaseDateMsec = $response['initiationTimestampMsec'];
			// convert to just seconds (because that's what we store in the db)
			$subscriptionExpiration = $validUntilMsec / 1000;
            $subscriptionPurchase = $purchaseDateMsec / 1000;
			
			$returnArray = array('expiration_date_in_seconds' => $subscriptionExpiration, 'purchase_date_in_seconds' => $subscriptionPurchase);
			
			return $returnArray;
		}
        
        public static function saveIAPAutorenewReceipt($receiptData, $expirationDate, $transactionId, $userId)
        {
            //If the user already has an entry in the table, update it. Otherwise, add a new one.
            //tdo_iap_autorenew_receipts (userid VARCHAR(36) NOT NULL, latest_receipt_data BLOB NOT NULL, expiration_date INT NOT NULL DEFAULT 0, transaction_id VARCHAR(255) NOT NULL, autorenewal_canceled TINYINT(1) NOT NULL DEFAULT 0, INDEX tdo_iap_autorenew_receipts_userid (userid)
            if(empty($receiptData) || empty($userId) || empty($transactionId))
            {
                error_log("TDOInAppPurchase::saveIAPAutorenewReceipt called with missing parameter");
                return false;
            }
            
            $userRow = TDOInAppPurchase::IAPAutorenewReceiptForUser($userId);
            if($userRow === false)
            {
                error_log("TDOInAppPurchase::saveIAPAutorenewReceipt failed trying to get latest receipt for user");
                return false;
            }
            
            $userRowExists = !empty($userRow);
            
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOInAppPurchase failed to get DB link");
                return false;
            }
            
            $escapedUserId = mysql_real_escape_string($userId, $link);
            $escapedReceiptData = mysql_real_escape_string($receiptData, $link);
            $expirationDate = intval($expirationDate);
            $escapedTransactionId = mysql_real_escape_string($transactionId, $link);

            if($userRowExists)
                $sql = "UPDATE tdo_iap_autorenew_receipts SET latest_receipt_data='$escapedReceiptData', expiration_date=$expirationDate, transaction_id='$escapedTransactionId', autorenewal_canceled=0 WHERE userid='$escapedUserId'";
            else
                $sql = "INSERT INTO tdo_iap_autorenew_receipts (userid, latest_receipt_data, expiration_date, transaction_id, autorenewal_canceled) VALUES ('$escapedUserId', '$escapedReceiptData', $expirationDate, '$escapedTransactionId', 0)";
            
            $response = mysql_query($sql, $link);
            if($response)
            {
                TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("TDOInAppPurchase::saveIAPAutorenewReceipt failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
            
        }
		
		
		public static function saveGooglePlayToken($receiptProductID, $googlePlayToken, $receiptExpirationDate, $userId)
		{
            // If the user already has an entry in the table, update it,
			// otherwise, add a new one.
            if(empty($receiptProductID) || empty($googlePlayToken) || empty($receiptExpirationDate) || empty($userId))
            {
                error_log("TDOInAppPurchase::saveGooglePlayToken() called with missing parameter");
                return false;
            }
            
			$userRow = TDOInAppPurchase::googlePlayTokenForUser($userId);
            if($userRow === false)
            {
                error_log("TDOInAppPurchase::saveGooglePlayToken() failed trying to get latest token for the user (userid: $userId).");
                return false;
            }
            
            $userRowExists = !empty($userRow);
            
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOInAppPurchase::saveGooglePlayToken() failed to get DB link");
                return false;
            }
            
			$escapedProductID = mysql_real_escape_string($receiptProductID, $link);
			$escapedToken = mysql_real_escape_string($googlePlayToken, $link);
			$expirationDate = intval($receiptExpirationDate);
            $escapedUserId = mysql_real_escape_string($userId, $link);
			
            if($userRowExists)
                $sql = "UPDATE tdo_googleplay_autorenew_tokens SET token='$escapedToken', expiration_date=$expirationDate, autorenewal_canceled=0, product_id='$escapedProductID' WHERE userid='$escapedUserId'";
            else
                $sql = "INSERT INTO tdo_googleplay_autorenew_tokens (userid, token, expiration_date, autorenewal_canceled, product_id) VALUES ('$escapedUserId', '$escapedToken', $expirationDate, 0, '$escapedProductID')";
            
            $response = mysql_query($sql, $link);
            if($response)
            {
                TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("TDOInAppPurchase::saveGooglePlayToken() failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
		}
			
        
        public static function IAPAutorenewReceiptForUser($userId)
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOInAppPurchase failed to get DB link");
                return false;
            }
            
            $sql = "SELECT * FROM tdo_iap_autorenew_receipts WHERE userid='".mysql_real_escape_string($userId, $link)."'";
            $response = mysql_query($sql, $link);
            if($response)
            {
                $history = NULL;
                if($row = mysql_fetch_array($response))
                {
                    $history = $row;
                }
                TDOUtil::closeDBLink($link);
                return $history;
            }
            else
                error_log("IAPAutorenewReceiptForUser failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
            
        }
		
		
		public static function googlePlayTokenForUser($userId)
		{
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOInAppPurchase::googlePlayTokenForUser() failed to get DB link");
                return false;
            }
            
            $sql = "SELECT * FROM tdo_googleplay_autorenew_tokens WHERE userid='".mysql_real_escape_string($userId, $link)."'";
            $response = mysql_query($sql, $link);
            if($response)
            {
                $history = NULL;
                if($row = mysql_fetch_array($response))
                {
                    $history = $row;
                }
                TDOUtil::closeDBLink($link);
                return $history;
            }
            else
                error_log("TDOInAppPurchase::googlePlayTokenForUser() failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
		}
		
        
        public static function userHasNonCanceledAutoRenewingIAP($userId)
        {
			// Check both Apple Auto-Renew IAP and Google Play Auto-Renew
			
			// Apple IAP
			if (TDOInAppPurchase::userIsAppleIAPUser($userId))
			{
                return true;
			}
			
			// Google Play
			if (TDOInAppPurchase::userIsGooglePlayUser($userId))
			{
				return true;
			}
            
            return false;
        }
		
		public static function userIsAppleIAPUser($userId)
		{
			$previousReceipt = TDOInAppPurchase::IAPAutorenewReceiptForUser($userId);
			if($previousReceipt && $previousReceipt['autorenewal_canceled'] == 0)
			{
				return true;
			}
			
			return false;
		}
		
		public static function userIsGooglePlayUser($userId)
		{
			$previousToken = TDOInAppPurchase::googlePlayTokenForUser($userId);
			if ($previousToken && $previousToken['autorenewal_canceled'] == 0)
			{
				return true;
			}
			
			return false;
		}
		
        public static function markIAPAutorenewalCanceledForUser($userId)
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOInAppPurchase failed to get DB link");
                return false;
            }
            
            if(!mysql_query("START TRANSACTION", $link))
            {
                error_log("TDOInAppPurchase failed to start transaction ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            $escapedUserId = mysql_real_escape_string($userId, $link);
            
            $sql = "UPDATE tdo_iap_autorenew_receipts SET autorenewal_canceled=1 WHERE userid='$escapedUserId'";
            if(!mysql_query($sql, $link))
            {
                error_log("markIAPAutorenewalCanceledForUser failed with error ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            $sql = "DELETE FROM tdo_user_payment_system WHERE userid='$escapedUserId'";
            if(!mysql_query($sql, $link))
            {
                error_log("markIAPAutorenewalCanceledForUser failed with error ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
			
            if(!mysql_query("COMMIT", $link))
            {
                error_log("TDOInAppPurchase failed to commit transaction ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
			
			// Check to see if the account is part of a team and if so, set the
			// expiration date accordingly.
			// https://github.com/Appigo/todo-issues/issues/2292
			$teamAccount = TDOTeamAccount::getTeamForTeamMember($userId, $link);
			if (!empty($teamAccount))
			{
				$subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userId, $link);
				$teamID = $teamAccount->getTeamID();
				$newExpirationTimestamp = $teamAccount->getExpirationDate();
				$billingFrequency = $teamAccount->getBillingFrequency();
				if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $billingFrequency, SUBSCRIPTION_LEVEL_TEAM, $teamID, $link))
				{
					$username = TDOUser::usernameForUserId($userId);
					$teamName = TDOTeamAccount::getTeamName();
					error_log("TDOInAppPurchase::markIAPAutorenewalCanceledForUser() unabled to update a subscription ($subscriptionID) to mark a user ($username: $userId) as a member of a team ($teamName: $teamID).");
				}
			}
			
            TDOUtil::closeDBLink($link);
            return true;
        }
        
        
        public static function markGooglePlayAutorenewalCanceledForUser($userId)
        {
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("markGooglePlayAutorenewalCanceledForUser failed to get DB link");
                return false;
            }
            
            if(!mysql_query("START TRANSACTION", $link))
            {
                error_log("markGooglePlayAutorenewalCanceledForUser failed to start transaction ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            $escapedUserId = mysql_real_escape_string($userId, $link);
            
            $sql = "UPDATE tdo_googleplay_autorenew_tokens SET autorenewal_canceled=1 WHERE userid='$escapedUserId'";
            if(!mysql_query($sql, $link))
            {
                error_log("markGooglePlayAutorenewalCanceledForUser failed with error ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            $sql = "DELETE FROM tdo_user_payment_system WHERE userid='$escapedUserId'";
            if(!mysql_query($sql, $link))
            {
                error_log("markGooglePlayAutorenewalCanceledForUser failed with error ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            if(!mysql_query("COMMIT", $link))
            {
                error_log("markGooglePlayAutorenewalCanceledForUser failed to commit transaction ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
			
			// Check to see if the account is part of a team and if so, set the
			// expiration date accordingly.
			// https://github.com/Appigo/todo-issues/issues/2292
			$teamAccount = TDOTeamAccount::getTeamForTeamMember($userId, $link);
			if (!empty($teamAccount))
			{
				$subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userId, $link);
				$teamID = $teamAccount->getTeamID();
				$newExpirationTimestamp = $teamAccount->getExpirationDate();
				$billingFrequency = $teamAccount->getBillingFrequency();
				if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $billingFrequency, SUBSCRIPTION_LEVEL_TEAM, $teamID, $link))
				{
					$username = TDOUser::usernameForUserId($userId);
					$teamName = TDOTeamAccount::getTeamName();
					error_log("TDOInAppPurchase::markGooglePlayAutorenewalCanceledForUser() unabled to update a subscription ($subscriptionID) to mark a user ($username: $userId) as a member of a team ($teamName: $teamID).");
				}
			}
			
            TDOUtil::closeDBLink($link);
            return true;
        }
        
		
			
        public static function processIAPAutorenewalForUser($userID, $subscription)
        {
            $resultArray = array();
        
            $previousIAPPurchase = TDOInAppPurchase::IAPAutorenewReceiptForUser($userID);
            if($previousIAPPurchase && $previousIAPPurchase['autorenewal_canceled'] == 0)
            {
                $previousReceipt = $previousIAPPurchase['latest_receipt_data'];
                $actualReceipt = TDOInAppPurchase::validateIAPReceipt($previousReceipt, $userID, IAP_ITUNES_SECRET_KEY);

                if($actualReceipt && isset($actualReceipt['status_code']) && ($actualReceipt['status_code'] == 21002 || $actualReceipt['status_code'] == 21003 || $actualReceipt['status_code'] == 21006))
                {
                    //These codes indicate that the receipt is definitely invalid or expired, so return that no
                    //autorenewal was found and mark the db entry as canceled so we don't try it again.
                    error_log("TDOInAppPurchase::processIAPAutorenewalForUser received iap error code: ".$actualReceipt['status_code']);
                    TDOInAppPurchase::markIAPAutorenewalCanceledForUser($userID);
                    $resultArray['account_renewed'] = false;
                    $resultArray['iap_autorenewing_account'] = false;
					
					if (!empty($subscription))
					{
						// Clear out any existing entry in the autorenew history
						$subscriptionID = $subscription->getSubscriptionID();
						TDOSubscription::removeSubscriptionFromAutorenewQueue($subscriptionID);
					}
					
                    return $resultArray;
                }
                else if ($actualReceipt && isset($actualReceipt['latest_receipt']) && isset($actualReceipt['latest_receipt_info']))
                {
                    $updateResults = TDOInAppPurchase::applyIAPReceiptToSubscription($actualReceipt['latest_receipt'], $actualReceipt['latest_receipt_info'], $subscription, $userID, false);
                    if(isset($updateResults['success']) && $updateResults['success'] == true)
                    {
                        TDOSubscription::removeSubscriptionFromAutorenewQueue($subscription->getSubscriptionID());
                        $resultArray['account_renewed'] = true;
                    }
                    else
                    {
                        error_log("TDOInAppPurchase::processIAPAutorenewalForUser encountered error applying latest subscription. Incrementing failure count.");
                        TDOSubscription::updateFailureCountsForSubscriptionID($subscription->getSubscriptionID(), "Error applying IAP subscription.");
                        $resultArray['account_renewed'] = false;
                    }

                    $resultArray['iap_autorenewing_account'] = true;
                    return $resultArray;
                }
                else
                {
                    //If we get in this case, there was some unknown error with validating the receipt.
                    //Return that an autorenewal was found so that we'll try again later and we won't
                    //try to process a Stripe purchase.
                    error_log("TDOInAppPurchase::processIAPAutorenewalForUser encountered error validating latest receipt. Incrementing failure count.");
                    TDOSubscription::updateFailureCountsForSubscriptionID($subscription->getSubscriptionID(), "Error validating IAP receipt.");
                    
                    $resultArray['account_renewed'] = false;
                    $resultArray['iap_autorenewing_account'] = true;
                    return $resultArray;
                }
            }
            else
            {
                error_log("TDOInAppPurchase::processIAPAutorenewalForUser detected non-IAP user (" . $userID . "). This is normal for a Stripe account.");
                $resultArray['account_renewed'] = false;
                $resultArray['iap_autorenewing_account'] = false;
                return $resultArray;
            }
            
        }
        
        
        public static function processGooglePlayAutorenewalForUser($userID, $subscription)
        {
            $resultArray = array();
            
            $previousToken = TDOInAppPurchase::googlePlayTokenForUser($userID);
			if ($previousToken && $previousToken['autorenewal_canceled'] == 0)            
            {
                $token = $previousToken['token'];
                $productId = $previousToken['product_id'];
                
                //error_log("Making call to read Expiration Date for Google Play Token with: " . $token . " and " . $productId . " and " . $userID);
                
                $tokenExpirationDate = TDOInAppPurchase::readExpirationDateFromGooglePlayToken($token, $productId, $userID);
                if (!$tokenExpirationDate || empty($tokenExpirationDate['expiration_date_in_seconds']))
                {
                    $resultArray['account_renewed'] = false;
                    $resultArray['gp_autorenewing_account'] = true;

                    if($tokenExpirationDate && !empty($tokenExpirationDate['error']))
                    {
                        $error = $tokenExpirationDate['error'];
                        error_log("TDOInAppPurchase::processGooglePlayAutorenewalForUser received error code: ".$tokenExpirationDate['error']);

                        // this is the only case in which we should mark the account as cancelled or non-renewing because we know the token was no longer valid
                        if($error == SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_INVALID_TOKEN)
                        {
                            error_log("TDOInAppPurchase::processGooglePlayAutorenewalForUser marking account as non-renewable.");
                            $resultArray['gp_autorenewing_account'] = false;
                            TDOInAppPurchase::markGooglePlayAutorenewalCanceledForUser($userID);
                        }
                    }
                    else
                    {
                        error_log("TDOInAppPurchase::processGooglePlayAutorenewalForUser got an error from readExpirationDateFromGooglePlayToken");
                    }
                    
                    return $resultArray;
                }                

                $newExpirationDate = $tokenExpirationDate['expiration_date_in_seconds'];
                $newPurchaseDate = $tokenExpirationDate['purchase_date_in_seconds'];

                $subscription = TDOSubscription::getSubscriptionForUserID($userID);
                if (!$subscription)
                {
                    //If we get in this case, there was some unknown error getting the subscription
                    //Return that an autorenewal was found so that we'll try again later and we won't
                    //try to process a Stripe purchase.
                    error_log("TDOInAppPurchase::processGooglePlayAutorenewalForUser encountered error validating latest receipt. Incrementing failure count.");
                    TDOSubscription::updateFailureCountsForSubscriptionID($subscription->getSubscriptionID(), "Error validating IAP receipt.");
                    
                    $resultArray['account_renewed'] = false;
                    $resultArray['gp_autorenewing_account'] = true;
                    return $resultArray;
                }
                
                // apply to the user's account
                $updateResults = TDOInAppPurchase::applyGooglePlayPurchaseToSubscription($productId, $token, $newPurchaseDate, $newExpirationDate, $subscription, $userID, false);
                if(isset($updateResults['success']) && $updateResults['success'] == true)
                {
                    TDOSubscription::removeSubscriptionFromAutorenewQueue($subscription->getSubscriptionID());
                    $resultArray['account_renewed'] = true;
                }
                else
                {
                    error_log("TDOInAppPurchase::processIAPAutorenewalForUser encountered error applying latest subscription. Incrementing failure count.");
                    TDOSubscription::updateFailureCountsForSubscriptionID($subscription->getSubscriptionID(), "Error applying IAP subscription.");
                    $resultArray['account_renewed'] = false;
                }
                
                $resultArray['gp_autorenewing_account'] = true;
                return $resultArray;
            }
            else
            {
                error_log("TDOInAppPurchase::processGooglePlayAutorenewalForUser detected non-IAP user (" . $userID . "). This is normal for a Stripe account.");
                $resultArray['account_renewed'] = false;
                $resultArray['gp_autorenewing_account'] = false;
                return $resultArray;
            }
        }        
        
        
        
        //This method should only be called after verifying that the given iap receipt can legally
        //be applied to the specified subscription
        public static function applyIAPReceiptToSubscription($encodedReceipt, $decodedReceipt, $subscription, $userID, $deleteStripeInfo=true)
        {
            $responseArray = array();
			
			if (!isset($decodedReceipt['product_id']))
			{
				// Look for the newest expires_date. If expires_date doesn't
				// exist, we shouldn't save it or process it because it's likely
				// an old subscription type that we no longer support (the
				// non-renewable kind).
				if (is_array($decodedReceipt) && count($decodedReceipt) > 0 && !empty($decodedReceipt[0]['product_id']))
				{
					$mostRecent = NULL;
					// This must be from the newer-style App Store receipts and we
					// need to look for the most recent purchase.
					foreach ($decodedReceipt as $receipt)
					{
						// Skip over every receipt that is old (it won't have an
						// expires_date).
						if (empty($receipt['expires_date']))
						{
							continue;
						}
						
						if ($mostRecent == NULL)
						{
							$mostRecent = $receipt;
							continue;
						}
						
						$mostRecentExpiresDate = $mostRecent['expires_date'];
						$receiptExpiresDate = $receipt['expires_date'];
						if (!empty($mostRecentExpiresDate) && !empty($receiptExpiresDate))
						{
							if (strcmp($mostRecentExpiresDate, $receiptExpiresDate) < 0)
							{
								$mostRecent = $receipt;
							}
						}
					}
					
					if ($mostRecent != NULL)
						$decodedReceipt = $mostRecent;
				}
			}
			
            // Determine what product the user just purchased
            if (!isset($decodedReceipt['product_id']))
            {
                error_log("TDOInAppPurchase::applyIAPReceiptToSubscription unable to decode product ID from receipt for user ($userID)");
                $responseArray['errcode'] = SUBSCRIPTION_ERROR_CODE_MISSING_PRODUCTID;
                $responseArray['errdesc'] = SUBSCRIPTION_ERROR_DESC_MISSING_PRODUCTID;
                return $responseArray;
            }
            
            $receiptProductID = $decodedReceipt['product_id'];
            
            if(!isset($decodedReceipt['expires_date']))
            {
                error_log("TDOInAppPurchase::applyIAPReceiptToSubscription unable to decode expiration date from receipt for user ($userID)");
                $responseArray['errcode'] = SUBSCRIPTION_ERROR_CODE_MISSING_EXPIRATION_DATE;
                $responseArray['errdesc'] = SUBSCRIPTION_ERROR_DESC_MISSING_EXPIRATION_DATE;
                return $responseArray;

            }
            
            if (isset($decodedReceipt['transaction_id']))
                $transactionID = $decodedReceipt['transaction_id'];
            else
                $transactionID = $userID;
			
			$receiptExpirationDate = TDOUtil::unixTimestampFromDateStringOrMilliseconds($decodedReceipt['expires_date']);
			
            //Extend the user's account to be due 24 hours after the expiration date given by apple. We do this to give ourselves
            //a buffer when processing renewals, so that if we check an account within 24 hours of its expiration, we know that
            //it has already been renewed or canceled
            
            $newExpirationDate = $receiptExpirationDate + 86400;
            $subscriptionType = SUBSCRIPTION_TYPE_UNKNOWN;
			
            if(strpos($receiptProductID, "onemonth") !== false)
            {
				$subscriptionType = SUBSCRIPTION_TYPE_MONTH;
                $expectedExpirationDate = mktime(date("H"), date("i"), date("s"), date("n") + 1, date("j"), date("Y"));
            }
            else if(strpos($receiptProductID, "oneyear") !== false)
            {
				$subscriptionType = SUBSCRIPTION_TYPE_YEAR;
                $expectedExpirationDate = mktime(date("H"), date("i"), date("s"), date("n"), date("j"), date("Y") + 1);
            }
            else
            {
                // UNKNOWN Product ID
                error_log("TDOInAppPurchase::applyIAPReceiptToSubscription made with an unknown product (user: $userID).");
                $responseArray['errcode'] = SUBSCRIPTION_ERROR_CODE_UNKNOWN_PRODUCT;
                $responseArray['errdesc'] = SUBSCRIPTION_ERROR_DESC_UNKNOWN_PRODUCT;
                return $responseArray;
            }
            
            //Just for kicks, compare the expiration date with the expected expiration date based on the product id
            //and notify someone if they don't match within a day
            if(abs($receiptExpirationDate - $expectedExpirationDate) > 86400)
            {
                error_log("TDOInAppPurchase::applyIAPReceiptToSubscription(userID: $userID) expected expiration date (" . $expectedExpirationDate . ") did not match expiration date on receipt (" . $receiptExpirationDate . ")");
                //TODO: possibly email support so we're aware there was an issue
            }
			
			// If the code reaches this point, we know about the newest
			// expiration date returned by Apple. Now is the time where we check
			// to see if the expiration date is newer than what the user's
			// current subscription expiration date is. If the newest expiration
			// date from Apple is older or equal to the user's current
			// subscription expiration date, we can safely assume that the
			// actual subscription is expired.
			$currentSubscriptionExpiration = $subscription->getExpirationDate();
			if ($receiptExpirationDate <= $currentSubscriptionExpiration)
			{
				// Mark the subscription as cancelled so the auto-renew daemon
				// doesn't keep trying to renew it.
				$receiptExpirationString = date(DATE_RFC2822, $receiptExpirationDate);
				$currentExpirationString = date(DATE_RFC2822, $currentSubscriptionExpiration);
				
				error_log("TDOInAppPurchase::applyIAPReceiptToSubscription(userID: " . $userID . ", subscriptionID: " . $subscription->getSubscriptionID() . ") Marking the user's subscription (" . $currentExpirationString . ") as expired since the newly-returned receipt from Apple (" . $receiptExpirationString . ") has expired.");
				TDOInAppPurchase::markIAPAutorenewalCanceledForUser($userID);
				
				// Return success = true here so the rest of the code will
				// clear out the renewal attempts and will also keep the iOS
				// clients working properly as well.
				$responseArray['success'] = true;
				return $responseArray;
			}
			
            //We need to save off the user's IAP receipt so that we'll be able to process the auto-renewal. If this doesn't go through,
            //fail the entire call so that the client will retry.
            if(!TDOInAppPurchase::saveIAPAutorenewReceipt($encodedReceipt, $receiptExpirationDate, $transactionID, $userID))
            {
                error_log("TDOInAppPurchase::applyIAPReceiptToSubscription unable to save IAP receipt");
                $responseArray['errcode'] = SUBSCRIPTION_ERROR_CODE_EXTEND_EXPIRATION;
                $responseArray['errdesc'] = SUBSCRIPTION_ERROR_DESC_EXTEND_EXPIRATION;
                return $responseArray;
            }
            
            // Update the subscription with the new expiration date
            if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscription->getSubscriptionID(), $newExpirationDate, $subscriptionType, SUBSCRIPTION_LEVEL_PAID))
            {
                error_log("TDOInAppPurchase::applyIAPReceiptToSubscription unable to extend expiration date.");
                $responseArray['errcode'] = SUBSCRIPTION_ERROR_CODE_EXTEND_EXPIRATION;
                $responseArray['errdesc'] = SUBSCRIPTION_ERROR_DESC_EXTEND_EXPIRATION;
                return $responseArray;
            }
            
            // Record the transaction
            // userid, product_id, transaction_id,
            // purchase_date, app_item_id, version_external_identifier,
            // bid, bvrs
            $purchaseDate = NULL;
            $appItemID = NULL;
            $versionExternalID = NULL;
            $bundleID = NULL;
            $appVersion = NULL;
            
            
            if (isset($decodedReceipt['purchase_date']))
                $purchaseDate = $decodedReceipt['purchase_date'];
            
            if (isset($decodedReceipt['app_item_id']))
                $appItemID = $decodedReceipt['app_item_id'];
            
            if (isset($decodedReceipt['version_external_identifier']))
                $versionExternalID = $decodedReceipt['version_external_identifier'];
            
            if (isset($decodedReceipt['bid']))
                $bundleID = $decodedReceipt['bid'];
            
            if (isset($decodedReceipt['bvrs']))
                $appVersion = $decodedReceipt['bvrs'];
            
            if (!TDOInAppPurchase::logIAPPayment($userID, $transactionID, $receiptProductID, $purchaseDate, $appItemID, $versionExternalID, $bundleID, $appVersion))
            {
                // This is a soft fail.  Everything else worked, so let this slide.
                error_log("TDOInAppPurchase::applyIAPReceiptToSubscription had trouble calling TDOInAppPurchase::logIAPPayment (user: $userID).");
            }
            
            $stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);
            if(!empty($stripeCustomerID))
            {
                if($deleteStripeInfo)
                {
                    //The first time a user makes an autorenewing IAP purchase, wipe out their stripe user info.
                    //If this is called from the autorenew daemon and there is a stripe customer id, that's because
                    //the user tried to switch payment types, so we should notify them that they need to cancel their IAP.
                    if(!TDOSubscription::deleteStripeCustomerInfoForUserID($userID))
                    {
                        //This is a soft fail. The autorenew daemon code should make sure that we don't autorenew
                        //stripe customers with IAP_AUTORENEW payment types as a safety precaution
                        error_log("TDOInAppPurchase::applyIAPReceiptToSubscription had trouble calling TDOSubscription::deleteCustomerInfoForUserID (user: $userID).");
                    }
                }
                else
                {
                    $username = TDOUser::usernameForUserId($userID);
                    $firstName = TDOUser::firstNameForUserId($userID);
                    if (empty($firstName))
                        $firstName = TDOUser::displayNameForUserId($userID);
                    
                    //User must cancel 24 hours in advance of their IAP autorenewal
                    $cancelationDate = $receiptExpirationDate - 86400;
                    
                    TDOMailer::sendAutorenewalNoticeForUserSwitchingFromStripe($username, $firstName, $cancelationDate);
                }
            }
            
            if (!TDOSubscription::addOrUpdateUserPaymentSystemInfo($userID, PAYMENT_SYSTEM_TYPE_IAP_AUTORENEW, $transactionID))
            {
                // This is a soft fail.  Everything else worked, so let this slide.
                error_log("TDOInAppPurchase::applyIAPReceiptToSubscription had trouble calling TDOSubscription::addOrUpdateUserPaymentSystemInfo (user: $userID).");
            }
            
			$nowTimestamp = time();
            if (!TDOReferral::recordPurchaseForUser($userID, $nowTimestamp))
            {
                // This is a soft fail. Everything else worked, but the referral
                // was not processed, for whatever reason.
                error_log("TDOInAppPurchase::applyIAPReceiptToSubscription - a referral was not processed for whatever reason in TDOReferral::recordPurchaseForUser()");
            }
			
			// A payment has just been successful. Remove a possible failed
			// autorenew entry so it will be less likely to cause problems later on.
			TDOSubscription::removeSubscriptionFromAutorenewQueue($subscription->getSubscriptionID());
			
            $responseArray['success'] = true;
            return $responseArray;
        }
		
		
		public static function applyGooglePlayPurchaseToSubscription($receiptProductID, $googlePlayToken, $receiptPurchaseDate, $receiptExpirationDate, $subscription, $userID, $deleteStripeInfo=true)
		{
            $responseArray = array();
			
            //Extend the user's account to be due 24 hours after the expiration
			// date given by Google. We do this to give ourselves a buffer when
			// processing renewals, so that if we check an account within 24
			// hours of its expiration, we know that it has already been renewed
			// or canceled.
            
            $newExpirationDate = $receiptExpirationDate + 86400;
			$subscriptionType = SUBSCRIPTION_TYPE_UNKNOWN;
            
            if(strpos($receiptProductID, "onemonth") !== false)
            {
                $subscriptionType = SUBSCRIPTION_TYPE_MONTH;
                $expectedExpirationDate = mktime(date("H"), date("i"), date("s"), date("n") + 1, date("j"), date("Y"));
            }
            else if(strpos($receiptProductID, "oneyear") !== false)
            {
                $subscriptionType = SUBSCRIPTION_TYPE_YEAR;
                $expectedExpirationDate = mktime(date("H"), date("i"), date("s"), date("n"), date("j"), date("Y") + 1);
            }
            else
            {
                // UNKNOWN Product ID
                error_log("TDOInAppPurchase::applyGooglePlayPurchaseToSubscription() made with an unknown product (user: $userID).");
                $responseArray['errcode'] = SUBSCRIPTION_ERROR_CODE_UNKNOWN_PRODUCT;
                $responseArray['errdesc'] = SUBSCRIPTION_ERROR_DESC_UNKNOWN_PRODUCT;
                return $responseArray;
            }
            
            // Just for kicks, compare the expiration date with the expected
			// expiration date based on the product id and notify someone if
			// they don't match within a day.
            if(abs($receiptExpirationDate - $expectedExpirationDate) > 86400)
            {
                error_log("TDOInAppPurchase::applyGooglePlayPurchaseToSubscription() the expiration date ($expectedExpirationDate) was more than 1 day off from the receipt ($receiptExpirationDate, userid: $userID)");
                //TODO: possibly email support so we're aware there was an issue
            }
			
			// Save off the Google Play subscription purchase token so that we
			// will be able to process auto-renewal in the future. If this does
			// not work, we need to fail the entire call so the client will
			// retry sending us the token.
			
			if (!TDOInAppPurchase::saveGooglePlayToken($receiptProductID, $googlePlayToken, $receiptExpirationDate, $userID))
			{
                error_log("TDOInAppPurchase::applyGooglePlayPurchaseToSubscription() unable to save Google Play subscription purchase token (userid: $userID).");
                $responseArray['errcode'] = SUBSCRIPTION_ERROR_CODE_EXTEND_EXPIRATION;
                $responseArray['errdesc'] = SUBSCRIPTION_ERROR_DESC_EXTEND_EXPIRATION;
                return $responseArray;
            }
            
            // Update the subscription with the new expiration date
			$subscriptionID = $subscription->getSubscriptionID();
            if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationDate, $subscriptionType, SUBSCRIPTION_LEVEL_PAID))
            {
                error_log("TDOInAppPurchase::applyGooglePlayPurchaseToSubscription() unable to extend expiration date (userid: $userID).");
                $responseArray['errcode'] = SUBSCRIPTION_ERROR_CODE_EXTEND_EXPIRATION;
                $responseArray['errdesc'] = SUBSCRIPTION_ERROR_DESC_EXTEND_EXPIRATION;
                return $responseArray;
            }
            
            // Record the transaction
			// userid, product_id, expiration_timestamp
			
			if (!TDOInAppPurchase::logGooglePlayPayment($userID, $receiptProductID, $receiptPurchaseDate, $receiptExpirationDate))
			{
                // This is a soft fail.  Everything else worked, so let this slide.
                error_log("TDOInAppPurchase::applyGooglePlayPurchaseToSubscription() had trouble calling TDOInAppPurchase::logGooglePlayPayment() (user: $userID).");
            }
            
            $stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);
            if(!empty($stripeCustomerID))
            {
                if($deleteStripeInfo)
                {
                    // The first time a user makes an autorenewing Google Play
					// purchase, wipe out their stripe user info.
                    // If this is called from the autorenew daemon and there is
					// a stripe customer id, that's because the user tried to
					// switch payment types, so we should notify them that they
					// need to cancel their IAP.
                    if(!TDOSubscription::deleteStripeCustomerInfoForUserID($userID))
                    {
                        // This is a soft fail. The autorenew daemon code should
						// make sure that we don't autorenew stripe customers
						// with IAP_AUTORENEW payment types as a safety
						// precaution
                        error_log("TDOInAppPurchase::applyGooglePlayPurchaseToSubscription() had trouble calling TDOSubscription::deleteCustomerInfoForUserID() (user: $userID).");
                    }
                }
                else
                {
                    $username = TDOUser::usernameForUserId($userID);
                    $firstName = TDOUser::firstNameForUserId($userID);
                    if (empty($firstName))
                        $firstName = TDOUser::displayNameForUserId($userID);
                    
                    //User must cancel 24 hours in advance of their IAP autorenewal
                    $cancelationDate = $receiptExpirationDate - 86400;
                    
                    TDOMailer::sendAutorenewalNoticeForUserSwitchingFromStripe($username, $firstName, $cancelationDate);
                }
            }
            
			// We have no transactionID so we're gonna pass "n/a"
            if (!TDOSubscription::addOrUpdateUserPaymentSystemInfo($userID, PAYMENT_SYSTEM_TYPE_GOOGLE_PLAY_AUTORENEW, "n/a"))
            {
                // This is a soft fail.  Everything else worked, so let this slide.
                error_log("TDOInAppPurchase::applyGooglePlayPurchaseToSubscription() had trouble calling TDOSubscription::addOrUpdateUserPaymentSystemInfo (user: $userID).");
            }
            
            $nowTimestamp = time();
            if (!TDOReferral::recordPurchaseForUser($userID, $nowTimestamp))
            {
                // This is a soft fail. Everything else worked, but the referral
                // was not processed, for whatever reason.
                error_log("TDOInAppPurchase::applyGooglePlayPurchaseToSubscription() - a referral was not processed for whatever reason in TDOReferral::recordPurchaseForUser()");
            }
			
			// A payment has just been successful. Remove a possible failed
			// autorenew entry so it can't cause problems later on.
			TDOSubscription::removeSubscriptionFromAutorenewQueue($subscriptionID);
			
            $responseArray['success'] = true;
            return $responseArray;
		}
        
        
        /* ADMIN METHODS */
        
		
		// Returns false on error or an array of objects with the following keys:
		//
		//		timestamp
		//			A UNIX timestamp
		//		subscriptionType
		//			One of SUBSCRIPTION_TYPE_MONTH, SUBSCRIPTION_TYPE_YEAR
		//		description
		//			Stuff here
		public static function getIAPPurchaseHistoryForUserID($userID)
		{
			if(empty($userID))
			{
				error_log("TDOInAppPurchase::getIAPPurchaseHistoryForUserID() failed because userID is empty");
				return false;
			}
			
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOInAppPurchase::getIAPPurchaseHistoryForUserID() unable to get link");
                return false;
            }
			
			$purchases = array();
			$sql = "select UNIX_TIMESTAMP(purchase_date) AS timestamp,product_id,bid FROM tdo_iap_payment_history WHERE userid='$userID' ORDER BY timestamp DESC";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOInAppPurchase::getIAPPurchaseHistoryForUserID() failed to make the SQL call" . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			while ($row = mysql_fetch_array($result))
			{
				$timestamp = $row['timestamp'];
				$productID = $row['product_id'];
				$bundleID = $row['bid'];
				
				$subscriptionTypeString = NULL;
				if (strpos($productID, "month") > 0)
					$subscriptionTypeString = "month";
				else if (strpos($productID, "year") > 0)
					$subscriptionTypeString = "year";
				else
					continue;
				
				// Determine what iOS app this was purchased from
				$productName = "Todo";
				if (strpos($bundleID, "todoipad") > 0)
					$productName = "Todo for iPad";
				else if (strpos($bundleID, "todolite") > 0)
					$productName = "Todo Lite";
				else if (strpos($bundleID, "todopro") > 0)
					$productName = "Todo Cloud";
					
				
				$description = "In-App Purchase from $productName";
				
				$purchase = array(
								  "timestamp" => $timestamp,
								  "subscriptionType" => $subscriptionTypeString,
								  "description" => $description
								  );
				
				$purchases[] = $purchase;
			}
			
			TDOUtil::closeDBLink($link);
			return $purchases;
		}
		
		
		// Returns false on error or an array of objects with the following keys:
		//
		//		timestamp
		//			A UNIX timestamp
		//		subscriptionType
		//			One of SUBSCRIPTION_TYPE_MONTH, SUBSCRIPTION_TYPE_YEAR
		//		description
		//			Stuff here
		public static function getGooglePlayPurchaseHistoryForUserID($userID)
		{
			if(empty($userID))
			{
				error_log("TDOInAppPurchase::getGooglePlayPurchaseHistoryForUserID() failed because userID is empty");
				return false;
			}
			
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOInAppPurchase::getGooglePlayPurchaseHistoryForUserID() unable to get link");
                return false;
            }
			
			$purchases = array();
			$sql = "select product_id,purchase_timestamp FROM tdo_googleplay_payment_history WHERE userid='$userID' ORDER BY purchase_timestamp DESC";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOInAppPurchase::getGooglePlayPurchaseHistoryForUserID() failed to make the SQL call" . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			while ($row = mysql_fetch_array($result))
			{
				$timestamp = $row['purchase_timestamp'];
				$productID = $row['product_id'];
				
				$subscriptionTypeString = NULL;
				if (strpos($productID, "month") > 0)
					$subscriptionTypeString = "month";
				else if (strpos($productID, "year") > 0)
					$subscriptionTypeString = "year";
				else
					continue;
				
				$productName = "Todo Cloud for Android";
				
				$description = "Subscription Purchase from $productName";
				
				$purchase = array(
								  "timestamp" => $timestamp,
								  "subscriptionType" => $subscriptionTypeString,
								  "description" => $description
								  );
				
				$purchases[] = $purchase;
			}
			
			TDOUtil::closeDBLink($link);
			return $purchases;
		}
		
		
		public static function getIAPPurchaseCountInRange($type, $startDate, $endDate, $renewable = false)
		{
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				return false;
			}
			
			$typeQuery = '';
			if ($type == SUBSCRIPTION_TYPE_MONTH)
			{
				if ($renewable)
					$typeQuery = " product_id LIKE '%onemonth.renewable' ";
				else
					$typeQuery = " product_id LIKE '%onemonth' ";
			}
			else if ($type == SUBSCRIPTION_TYPE_YEAR)
			{
				if ($renewable)
					$typeQuery = " product_id LIKE '%oneyear.renewable' ";
				else
					$typeQuery = " product_id LIKE '%oneyear' ";
			}
			
			$sql = "SELECT COUNT(*) FROM tdo_iap_payment_history WHERE $typeQuery AND UNIX_TIMESTAMP(purchase_date) >= $startDate AND UNIX_TIMESTAMP(purchase_date) <= $endDate";
//			error_log("SQL: $sql");
			
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']))
					{
						TDOUtil::closeDBLink($link);
						return $row['0'];
					}
				}
			}
			else
			{
				error_log("TDOInAppPurchase::getPurchaseCountForUserInRange($startDate, $endDate): Unable to get user count");
			}
			
			TDOUtil::closeDBLink($link);
			return false;
		}
		
		
		public static function getGooglePlayPurchaseCountInRange($type, $startDate, $endDate)
		{
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				return false;
			}
			
			$typeQuery = '';
			if ($type == SUBSCRIPTION_TYPE_MONTH)
			{
				$typeQuery = " product_id LIKE '%onemonth%' ";
			}
			else if ($type == SUBSCRIPTION_TYPE_YEAR)
			{
				$typeQuery = " product_id LIKE '%oneyear%' ";
			}
			
			$sql = "SELECT COUNT(*) FROM tdo_googleplay_payment_history WHERE $typeQuery AND purchase_timestamp >= $startDate AND purchase_timestamp <= $endDate";
			
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']))
					{
						TDOUtil::closeDBLink($link);
						return $row['0'];
					}
				}
			}
			else
			{
				error_log("TDOInAppPurchase::getGooglePlayPurchaseCountInRange($startDate, $endDate): Unable to get user count");
			}
			
			TDOUtil::closeDBLink($link);
			return false;
		}
				
    }


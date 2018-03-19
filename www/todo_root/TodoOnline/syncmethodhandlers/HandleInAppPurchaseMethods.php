<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	include_once('TodoOnline/DBConstants.php');
	include_once('Stripe/Stripe.php');
	
	
	define ('SUBSCRIPTION_API_KEY_SECRET', '52375359-A888-4B6C-B2F8-05DDEDAE478C');

    if($method == "checkIAPAvailability")
    {
        $userID = $session->getUserId();
        
        //This method double checks that the user's account can handle an autorenewing
        //IAP subscription
		$subscription = TDOSubscription::getSubscriptionForUserID($userID);
		if (!$subscription)
		{
			error_log("HandleInAppPurchaseMethods::checkIAPAvailability unable to read user's subscription (user: $userID).");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_SUBSCRIPTION.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_SUBSCRIPTION.'"}';
			return;
		}
        
        // if the user has any auto renewing IAP subscriptions, return an error
        if(TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userID) == true)
        {
            error_log("HandleInAppPurchaseMethods::checkIAPAvailability found that the user has non expired IAP subscriptions (user: $userID).");
            echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_ACCOUNT_NOT_EXPIRED.', "errdesc":"'.SUBSCRIPTION_ERROR_DESC_ACCOUNT_NOT_EXPIRED.'"}';
			return;
        }
        
        // if the user is using stripe, check for a valid subscription
        $stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);
        if(!empty($stripeCustomerID))
        {            
            //User cannot purchase IAP renewing subscription if they are using a different payment system
            //and their account is not expired, as this will mess up their auto-renewal schedule.
            if($subscription->getExpirationDate() > time())
            {
                //We should not get in this situation because we'll check with the server before
                //the user makes a purchase, but just in case, we need to fail here.
                error_log("HandleInAppPurchaseMethods::checkIAPAvailability called on unexpired account.");
                echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_ACCOUNT_NOT_EXPIRED.', "errdesc":"'.SUBSCRIPTION_ERROR_DESC_ACCOUNT_NOT_EXPIRED.'"}';
                return;
            }
        }
        echo '{"success":true}';
        
    }
	else if ($method == "processIAPAutorenewSubscriptionPurchase")
	{
		if(!isset($_POST['receipt']))
		{
			error_log("HandleInAppPurchaseMethods::processIAPAutorenewSubscriptionPurchase called and missing a required parameter: receipt");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER.'"}';
			return;
		}
		
		$userID = $session->getUserId();
		$encodedReceipt = $_POST['receipt'];
		
		// Validate the IAP Receipt
        // Validate the IAP Receipt
        $encodedReceipt = json_decode($encodedReceipt, true);
        if(isset($encodedReceipt['receipt-data']))
        {
            $encodedReceipt = $encodedReceipt['receipt-data'];
        }
        else
        {
            error_log("HandleSubscriptionMethods::processIAPAutorenewSubscriptionPurchase called with no receipt-data in receipt object");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER.'"}';
			return;
        }
        
        $previousIAPPurchase = TDOInAppPurchase::IAPAutorenewReceiptForUser($userID);
        
		$actualReceipt = TDOInAppPurchase::validateIAPReceipt($encodedReceipt, $userID, IAP_ITUNES_SECRET_KEY);
		if (!$actualReceipt || !isset($actualReceipt['receipt']))
		{
            if(isset($actualReceipt['status_code']))
            {
                //IAP auto-renew status codes are defined here:
                //https://developer.apple.com/library/mac/#documentation/NetworkingInternet/Conceptual/StoreKitGuide/RenewableSubscriptions/RenewableSubscriptions.html#//apple_ref/doc/uid/TP40008267-CH4-SW2
                $statusCode = $actualReceipt['status_code'];
                if($statusCode == 21002 || $statusCode == 21003 || $statusCode == 21006)
                {
                    //These codes indicate that the receipt was actually bad or expired,
                    //so the client should not retry the validation
                    if($previousIAPPurchase && $previousIAPPurchase['autorenewal_canceled'] == 0)
                    {
                        error_log("HandleSubscriptionMethods::processIAPAutorenewSubscriptionPurchase got back a bad receipt, marking it as cancelled on the user subscription");
                        TDOInAppPurchase::markIAPAutorenewalCanceledForUser($userID);
						
						$subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userID);
						if (!empty($subscriptionID))
						{
							// Clear out any existing entry in the autorenew history
							TDOSubscription::removeSubscriptionFromAutorenewQueue($subscriptionID);
						}
                    }
					
                    error_log("HandleSubscriptionMethods::processIAPAutorenewSubscriptionPurchase found an invalid receipt object");
                    
                    echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_APP_STORE_RECEIPT_NOT_VALID.', "errdesc":"'.SUBSCRIPTION_ERROR_DESC_APP_STORE_RECEIPT_NOT_VALID.'"}';
                    return;
                }
                else
                {
                    error_log("HandleSubscriptionMethods::processIAPAutorenewSubscriptionPurchase received an error from the app store it didn't understand");

                    echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_APP_STORE_BAD_RESPONSE.', "errdesc":"'.SUBSCRIPTION_ERROR_DESC_APP_STORE_BAD_RESPONSE.'"}';
                    return;
                }
            
            }
            else if(isset($actualReceipt['error']) && isset($actualReceipt['error_desc']))
            {
                error_log("Error calling TDOInAppPurchase::validateIAPReceipt() ".$actualReceipt['error_desc']);
                echo '{"errcode":'.$actualReceipt['error'].',"errdesc":"'.$actualReceipt['error_desc'].'"}';
                return;
            }
            else
            {
                error_log("Error calling TDOInAppPurchase::validateIAPReceipt() ... no return value");
                echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_UNKNOWN.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_UNKNOWN.'"}';
                return;
            }
		}
		
		$decodedReceipt = $actualReceipt['receipt'];
		
        if(!isset($decodedReceipt['expires_date']))
        {
            error_log("HandleInAppPurchaseMethods::processIAPAutorenewSubscriptionPurchase unable to decode expiration date from receipt for user ($userID)");
			error_log("HandleInAppPurchaseMethods::processIAPAutorenewSubscriptionPurchase Receipt Data: " . json_encode($decodedReceipt));
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_EXPIRATION_DATE.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_EXPIRATION_DATE.'"}';
			return;
        }
        
		$receiptExpirationDate = TDOUtil::unixTimestampFromDateStringOrMilliseconds($decodedReceipt['expires_date']);
//        error_log("RECEIPT EXPIRATION DATE: ".date("d-m-Y h:i:s", $receiptExpirationDate));
//        error_log(" TRANSACTION ID: ".$decodedReceipt['transaction_id']);
		
		// Update the user's subscription
		$subscription = TDOSubscription::getSubscriptionForUserID($userID);
		if (!$subscription)
		{
			error_log("HandleInAppPurchaseMethods::processIAPAutorenewSubscriptionPurchase unable to read user's subscription (user: $userID).");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_SUBSCRIPTION.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_SUBSCRIPTION.'"}';
			return;
		}
        
        if (isset($decodedReceipt['transaction_id']))
            $transactionID = $decodedReceipt['transaction_id'];
        else
            $transactionID = $userID;
        
        //See if this is a receipt being restored to the device after an autorenew. If so,
        //see if we have already processed it in the autorenew daemon. If not, go ahead and process it.
        if($previousIAPPurchase && $previousIAPPurchase['autorenewal_canceled'] == 0)
        {
            if(($previousIAPPurchase['transaction_id'] == $transactionID && $transactionID != $userID) || $receiptExpirationDate <= $previousIAPPurchase['expiration_date'])
            {
                //We have already processed this receipt or the expiration date on it is earlier than the
                //expiration date we currently have, so just discard this receipt and return that it was successfully
                //processed so the client won't send it again.
                echo '{"success":true}';
                return;
            }
        }
        else
        {

            // if the user is using stripe, check for a valid subscription
            // if their account hasn't expired and they have a valid stripe id
            // then fail the IAP process
            $stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);
            if(!empty($stripeCustomerID))
            {            
                //User cannot purchase IAP renewing subscription if they are using a different payment system
                //and their account is not expired, as this will mess up their auto-renewal schedule.
                if($subscription->getExpirationDate() > time())
                {
                    //We should not get in this situation because we'll check with the server before
                    //the user makes a purchase, but just in case, we need to fail here.
                    error_log("HandleInAppPurchaseMethods::checkIAPAvailability called on unexpired account.");
                    echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_ACCOUNT_NOT_EXPIRED.', "errdesc":"'.SUBSCRIPTION_ERROR_DESC_ACCOUNT_NOT_EXPIRED.'"}';
                    return;
                }
            }
        }
        
        //If we got this far, the receipt is valid and can be applied to the account, so apply it now
        $response = TDOInAppPurchase::applyIAPReceiptToSubscription($encodedReceipt, $decodedReceipt, $subscription, $userID);
        
        echo json_encode($response);
        
	}
	else if ($method == "processGooglePlayAutorenewSubscriptionPurchase")
	{
		if(!isset($_POST['productId']))
		{
			error_log("HandleInAppPurchaseMethods::processGooglePlayAutorenewSubscriptionPurchase called and missing a required parameter: productId");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER.'"}';
			return;
		}
		
		if(!isset($_POST['token']))
		{
			error_log("HandleInAppPurchaseMethods::processGooglePlayAutorenewSubscriptionPurchase called and missing a required parameter: token");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER.'"}';
			return;
		}
		
		$userID = $session->getUserId();
		
		$productId = $_POST['productId'];
		$token = $_POST['token'];

        error_log("HandleInAppPurchaseMethods::processGooglePlayAutorenewSubscriptionPurchase called.");
		
        
		// TODO: Make sure the current user does NOT already have automatic IAP or
		// Stripe set up.
		
		// TODO: Validate the token to ensure the subscription was actually purchased
		// on Google Play.
		
		$tokenExpirationDate = TDOInAppPurchase::readExpirationDateFromGooglePlayToken($token, $productId, $userID);
		if (!$tokenExpirationDate || !isset($tokenExpirationDate['expiration_date_in_seconds']))
		{
			if (isset($tokenExpirationDate['error']) && isset($tokenExpirationDate['error_desc']))
			{
				echo '{"errcode":'. $tokenExpirationDate['error'] .', "errdesc":"'. $tokenExpirationDate['error_desc'] .'"}';
				return;
			}
			else
			{
				echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_UNKNOWN.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_UNKNOWN.'"}';
				return;
			}
		}
		
		$newExpirationDate = $tokenExpirationDate['expiration_date_in_seconds'];
        $newPurchaseDate = $tokenExpirationDate['purchase_date_in_seconds'];
			
		if ($newExpirationDate < time())
		{
			// We've been passed a subscription that occurs in the past, so
			// don't do anything.
			error_log("HandleInAppPurchaseMethods::processGooglePlayAutorenewSubscriptionPurchase called with an already-expired token (user: $userID).");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_TOKEN_EXPIRED.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_TOKEN_EXPIRED.'"}';
			return;
		}
		
		$subscription = TDOSubscription::getSubscriptionForUserID($userID);
		if (!$subscription)
		{
			error_log("HandleInAppPurchaseMethods::processGooglePlayAutorenewSubscriptionPurchase unable to read user's subscription (user: $userID).");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_SUBSCRIPTION.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_SUBSCRIPTION.'"}';
			return;
		}
        
		// If the Google Play expiration date expires BEFORE the existing
		// subscription, this is weird ... and an error.
		
		//User cannot purchase a renewing subscription if they are using a different payment system
		//and their account is not expired, as this will mess up their auto-renewal schedule.
		if( ($subscription->getExpirationDate() > time()) || ($subscription->getExpirationDate() > $newExpirationDate) )
		{
			//We should not get in this situation because we'll check with the server before
			//the user makes a purchase, but just in case, we need to fail here.
			error_log("HandleInAppPurchaseMethods::processGooglePlayAutorenewSubscriptionPurchase called on unexpired account (user: $userID).");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_ACCOUNT_NOT_EXPIRED.', "errdesc":"'.SUBSCRIPTION_ERROR_DESC_ACCOUNT_NOT_EXPIRED.'"}';
			return;
		}
		
		// If we get this far, the Google Play purchase is valid and we can
		// apply it to the user's account.
		$response = TDOInAppPurchase::applyGooglePlayPurchaseToSubscription($productId, $token, $newPurchaseDate, $newExpirationDate, $subscription, $userID);
        
        echo json_encode($response);
	}
	
?>

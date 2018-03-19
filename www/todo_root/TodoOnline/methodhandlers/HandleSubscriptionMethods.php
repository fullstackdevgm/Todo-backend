<?php

	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');
	include_once('TodoOnline/DBConstants.php');
	include_once('Stripe/Stripe.php');

	Stripe::setApiKey(APPIGO_STRIPE_SECRET_KEY);

	define ('SUBSCRIPTION_API_KEY_SECRET', '52375359-A888-4B6C-B2F8-05DDEDAE478C');


	//NOTE: We're leaving this in for now for legacy support with old app versions (6.0.4 and earlier), but
    //we should be safe to remove it later because we're bumping up the protocol version to force users to
    //update to 6.0.5
    if ($method == "processIAPSubscriptionPurchase")
	{
		if(!isset($_POST['userid']))
		{
			error_log("HandleSubscriptionMethods::processIAPSubscriptionPurchase called and missing a required parameter: userid");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER.'"}';
			return;
		}
		if(!isset($_POST['receipt']))
		{
			error_log("HandleSubscriptionMethods::processIAPSubscriptionPurchase called and missing a required parameter: receipt");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER.'"}';
			return;
		}
		if(!isset($_POST['apikey']))
		{
			error_log("HandleSubscriptionMethods::processIAPSubscriptionPurchase called and missing a required parameter: apikey");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER.'"}';
			return;
		}

		$userID = $_POST['userid'];
		$encodedReceipt = $_POST['receipt'];
		$apiKey = $_POST['apikey'];

		// Validate the secret API Key which is an MD5 hash of:
		// "47" + <userid> + <IAP_API_SECRET_KEY> + "47"
		$preHash = "47" . $userID . SUBSCRIPTION_API_KEY_SECRET . "47";
		$calculatedMD5 = md5($preHash);

		if ($calculatedMD5 != $apiKey)
		{
			error_log("HandleEmailTaskMethods::processIAPSubscriptionPurchase called by unauthorized service");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_UNAUTHORIZED.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_UNAUTHORIZED.'"}';
			return;
		}

        if(TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userID) == true)
        {
            error_log("HandleSubscriptionMethods::processIAPSubscriptionPurchase called on account that already has autorenewing IAP set up");
            echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_IAP_AUTORENEWAL_DETECTED.', "errdesc":"'.SUBSCRIPTION_ERROR_DESC_IAP_AUTORENEWAL_DETECTED.'"}';
            return;
        }

		// Validate the IAP Receipt
        $encodedReceipt = json_decode($encodedReceipt, true);
        if(isset($encodedReceipt['receipt-data']))
        {
            $encodedReceipt = $encodedReceipt['receipt-data'];
        }
        else
        {
            error_log("HandleSubscriptionMethods::processIAPSubscriptionPurchase called with no receipt-data in receipt object");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER.'"}';
			return;
        }
		$actualReceipt = TDOInAppPurchase::validateIAPReceipt($encodedReceipt, $userID);
		if (!$actualReceipt || empty($actualReceipt['receipt']))
		{
			error_log("Error calling TDOInAppPurchase::validateIAPReceipt() ... no return value");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_UNKNOWN.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_UNKNOWN.'"}';
			return;
		}

		$decodedReceipt = $actualReceipt['receipt'];

		// Determine what product the user just purchased
		if (!isset($decodedReceipt['product_id']))
		{
			error_log("HandleEmailTaskMethods::processIAPSubscriptionPurchase unable to decode product ID from receipt for user ($userID)");
//			$receiptString = var_export($decodedReceipt, true);
//			error_log("RECEIPT: " . $receiptString);
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_PRODUCTID.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_PRODUCTID.'"}';
			return;
		}

		$receiptProductID = $decodedReceipt['product_id'];

		// Update the user's subscription
		$subscription = TDOSubscription::getSubscriptionForUserID($userID);
		if (!$subscription)
		{
			error_log("HandleEmailTaskMethods::processIAPSubscriptionPurchase unable to read user's subscription (user: $userID).");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_MISSING_SUBSCRIPTION.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_MISSING_SUBSCRIPTION.'"}';
			return;
		}

		$subscriptionID = $subscription->getSubscriptionID();

		// Determine how much time to add on to a subscription based on what
		// was purchased.
		$expirationTimestamp = $subscription->getExpirationDate();
		$expirationDate = new DateTime("now", new DateTimeZone("UTC"));
		$expirationDate->setTimestamp($expirationTimestamp);

		$nowDate = new DateTime("now", new DateTimeZone("UTC"));

		$baseExpirationDate = NULL;
		if ($nowDate > $expirationDate)
			$baseExpirationDate = $nowDate;
		else
			$baseExpirationDate = $expirationDate;

		$extensionInterval = NULL;
		$subscriptionType = SUBSCRIPTION_TYPE_UNKNOWN;

		if (strstr($receiptProductID, "onemonth"))
		{
			$extensionInterval = new DateInterval("P1M");
			$subscriptionType = SUBSCRIPTION_TYPE_MONTH;
		}
		else if (strstr($receiptProductID, "oneyear"))
		{
			$extensionInterval = new DateInterval("P1Y");
			$subscriptionType = SUBSCRIPTION_TYPE_YEAR;
		}
		else
		{
			// UNKNOWN Product ID
			error_log("HandleEmailTaskMethods::processIAPSubscriptionPurchase made with an unknown product (user: $userID).");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_UNKNOWN_PRODUCT.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_UNKNOWN_PRODUCT.'"}';
			return;
		}

		$newExpirationDate = $baseExpirationDate->add($extensionInterval);
		$newExpirationTimestamp = $newExpirationDate->getTimestamp();

		// Update the subscription with the new expiration date
		if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $subscriptionType, SUBSCRIPTION_LEVEL_PAID))
		{
			error_log("HandleEmailTaskMethods::processIAPSubscriptionPurchase made with an unknown product (user: $userID).");
			echo '{"errcode":'.SUBSCRIPTION_ERROR_CODE_EXTEND_EXPIRATION.',"errdesc":"'.SUBSCRIPTION_ERROR_DESC_EXTEND_EXPIRATION.'"}';
			return;
		}

		// Record the transaction
		// userid, product_id, transaction_id,
		// purchase_date, app_item_id, version_external_identifier,
		// bid, bvrs
		$transactionID = NULL;
		$purchaseDate = NULL;
		$appItemID = NULL;
		$versionExternalID = NULL;
		$bundleID = NULL;
		$appVersion = NULL;

		if (isset($decodedReceipt['transaction_id']))
			$transactionID = $decodedReceipt['transaction_id'];
		else
			$transactionID = $userID;

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
			error_log("HandleSubscriptionMethods::processIAPSubscriptionPurchase had trouble calling TDOInAppPurchase::logIAPPayment (user: $userID).");
		}

		if (!TDOSubscription::addOrUpdateUserPaymentSystemInfo($userID, PAYMENT_SYSTEM_TYPE_IAP, $transactionID))
		{
			// This is a soft fail.  Everything else worked, so let this slide.
			error_log("HandleSubscriptionMethods::processIAPSubscriptionPurchase had trouble calling TDOSubscription::addOrUpdateUserPaymentSystemInfo (user: $userID).");
		}

		$nowTimestamp = time();
		if (!TDOReferral::recordPurchaseForUser($userID, $nowTimestamp))
		{
			// This is a soft fail. Everything else worked, but the referral
			// was not processed, for whatever reason.
			error_log("HandleSubscriptionMethods::processIAPSubscriptionPurchase - a referral was not processed for whatever reason in TDOReferral::recordPurchaseForUser()");
		}

		// A payment has just been successful. Remove a possible failed
		// autorenew entry so it can't cause problems later on.
		TDOSubscription::removeSubscriptionFromAutorenewQueue($subscriptionID);

		// Respond with success!
		echo '{"success":true}';
	} else if ($method == "getAccountInfo") {
		$userID = $session->getUserId();
		$subscriptionInfo = TDOSubscription::getSubscriptionInfoForUserID($userID);
		if (!$subscriptionInfo) {
			error_log("HandleSubscriptionMethods::getAccountInfo() could not get subscription info for user ($userID - " . TDOUser::usernameForUserId($userID) . ")");
			echo json_encode(array (
				'success' => FALSE,
				'error'		=> _('Failed to get subscription info for user id.')
			));
			return;
		}

		$user = TDOUser::getUserForUserId($userID);
		if (!$user) {
			error_log("HandleSubscriptionMethods::getAccountInfo() could not get user info for user ($userID - " . TDOUser::usernameForUserId($userID) . ")");
			echo json_encode(array (
				'success' => FALSE,
				'error'		=> _('Failed to get user info for user id.')
			));
			return;
		}

		$userInfo = array(
			'userid' => $userID,
			'username' => $user->username(),
			'firstname' => $user->firstName(),
			'lastname' => $user->lastName(),
			'emailverified' => $user->emailVerified(),
			'emailoptout' => $user->emailOptOut(),
			'imageguid' => $user->imageGuid(),
			'selectedlocale' => $user->selectedLocale(),
			'bestmatchlocale' => $user->bestMatchLocale(),
			'locale' => $user->locale(),
			'creationtimestamp' => $user->creationTimestamp(),
			'adminlevel' => $user->userAdminLevel(),
		);

		$result = array(
			'success' => TRUE,
			'userinfo' => $userInfo,
			'subscriptioninfo' => $subscriptionInfo
		);
		echo json_encode($result);
		return;
	} else if ($method == "getSubscriptionInfo") {
		// Returns JSON with the following:
		//
		//		subscription_id				(String)
		//		expiration_date				(UNIX Timestamp)
		//		expired						(true|false)
		//		subscription_level			(0 = expired, 1 = unknown, 2 = trial, 3 = promo, 4 = paid, 5 = migrated, 6 = pro [unused])
		//		subscription_type			(String: "month" or "year")
		//		eligibility_date			(UNIX Timestamp)
		//		eligible					(true|false)
		//		pricing_table				(Array with month & year values in USD)
		//			month
		//			year
		//		new_month_expiration_date	(UNIX Timestamp)
		//		new_year_expiration_date	(UNIX Timestamp)
        //      iap_autorenewing_account    (true|false)
		//		billing_info				(Array - missing if user is a trial user or In-App Purchase user)
		//			exp_month				(should exist)
		//			exp_year				(should exist)
		//			last4					(should exist)
		//			type					(should exist - e.g. Visa, MasterCard, etc.)
		//			name					(may exist)

		$userID = $session->getUserId();

		$subscriptionInfo = TDOSubscription::getSubscriptionInfoForUserID($userID);
		if (!$subscriptionInfo)
		{
			error_log("HandleSubscriptionMethods::getBasicSubscriptionInfo could not get subscription info for user ($userID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Failed to get subscription info for user id.'),
            ));
			return;
		}
		else
		{
			$result = array();
			$result['success'] = true;
			$result['subscriptioninfo'] = $subscriptionInfo;
		}

		echo json_encode($result);
		return;
	}
	else if ($method == "purchasePremiumAccount")
	{
		// PARAMETERS:
		//		subscriptionType	required - either "month" or "year" to indicate the duration of the purchase
		//		subscriptionID		required - the user's personal subscription (owned by them and assigned to them)
		//		totalCharge			required - the full price in USD dollars and cents of the purchase
		//		stripeToken			optional - either stripeToken or last4 is required, but not both
		//		last4				optional - either last4 or stripeToken is required, but not both

		$stripeToken = NULL;
		$last4 = NULL;

		if (!isset($_POST['subscriptionType']))
		{
			error_log("HandleSubscriptionMethods::purchasePremiumAccount called and missing a required parameter: subscriptionType");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('subscriptionType parameter is missing'),
            ));
            return;
		}
		if (!isset($_POST['subscriptionID']))
		{
			error_log("HandleSubscriptionMethods::purchasePremiumAccount called and missing a required parameter: subscriptionID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('subscriptionID parameter is missing'),
            ));
            return;
		}
		if (!isset($_POST['totalCharge']))
		{
			error_log("HandleSubscriptionMethods::purchasePremiumAccount called and missing a required parameter: totalCharge");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('totalCharge parameter is missing'),
            ));
			return;
		}
		if (!isset($_POST['stripeToken']))  // stripeToken OR last4 required
		{
			if (!isset($_POST['last4']))
			{
				error_log("HandleSubscriptionMethods::purchasePremiumAccount called and missing a required parameter: stripeToken or last4 required");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('stripeToken or last4 parameter is missing'),
                ));
				return;
			}
			else
			{
				$last4 = $_POST['last4'];
			}
		}
		else
		{
			$stripeToken = $_POST['stripeToken'];
		}

        //If the user has auto-renewing IAP set up, fail this method. It should not get called
        //in the first place because the client will check before allowing this to go through
        if(TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($session->getUserId()) == true)
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to make purchase because user has auto-renewing In-App Purchase subscription'),
            ));
            return;
        }

		$subscriptionTypeString = $_POST['subscriptionType'];
		if ( ($subscriptionTypeString != "month") && ($subscriptionTypeString != "year") )
		{
			error_log("HandleSubscriptionMethods::purchasePremiumAccount: invalid subscriptionType");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Please select either a one month or a one year account.'),
            ));
			return;
		}

		$totalCharge = $_POST['totalCharge'];

		// Validate the user's personal subscription:
		//		1. It must be owned by them
		//		2. It must be assigned to them
		//		3. It must be eligible for upgrading (expiration is within six
		//		   months of right now)
		$userID = $session->getUserId();
		$subscriptionID = $_POST['subscriptionID'];
		$subscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);

		if (!$subscription)
		{
			error_log("HandleSubscriptionMethods::purchasePremiumAccount called with non-existing subscriptionID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('subscriptionID does not exist'),
            ));
			return;
		}

		$now = new DateTime("now", new DateTimeZone("UTC"));
		$sixMonthsOut = $now->add(new DateInterval("P6M"));
		$sixMonthsTimestamp = $sixMonthsOut->getTimestamp();
		$subscriptionExpiration = $subscription->getExpirationDate();
		if ($subscriptionExpiration > $sixMonthsTimestamp)
		{
			error_log("HandleSubscriptionMethods::purchasePremiumAccount: specified subscription is not yet eligible for extension.");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('This premium account is not eligible for extension'),
            ));
			return;
		}

		$subscriptionType = SUBSCRIPTION_TYPE_UNKNOWN;
		if ($subscriptionTypeString == 'month')
		{
			$subscriptionType = SUBSCRIPTION_TYPE_MONTH;
			$chargeDescription = _('Todo® Cloud Premium Account (1 month)');
		}
		else if ($subscriptionTypeString == 'year')
		{
			$subscriptionType = SUBSCRIPTION_TYPE_YEAR;
			$chargeDescription = _('Todo® Cloud Premium Account (1 year)');
		}

		//
		// Determine the new expiration date.
		$newExpirationDate = $subscription->getSubscriptionRenewalExpirationDateForType($subscriptionType);

		if (empty($newExpirationDate))
		{
			error_log("HandleSubscriptionMethods::purchasePremiumAccount (UserID: $userID, SubscriptionType: $subscriptionType, SubscriptionID: $subscriptionID) could not determine the new expiration date.");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('A new expiration date for this premium account could not be determined'),
            ));
			return;
		}

		//
		// PRICE CHECK: Make sure that the price we showed to the user matches
		// the authoritative price (the price calculated by the server).
		$pricingTable = TDOSubscription::getPersonalSubscriptionPricingTable();
		$authoritativePrice = $pricingTable[$subscriptionTypeString];
		if (empty($authoritativePrice))
		{
			error_log("HandleSubscriptionMethods::purchasePremiumAccount: Error determining authoritive pricing.");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('error verifying authoritative pricing'),
            ));
			return;
		}

		// Use a string compare
		if ((string)$totalCharge != (string)$authoritativePrice)
		{
			error_log("HandleSubscriptionMethods::purchasePremiumAccount called with an invalid totalCharge of " . $totalCharge . " but we were expecting " . $authoritativePrice);
            echo json_encode(array(
                'success' => FALSE,
                'error' => sprintf(_('Expected total charge of %s but received %s'), $authoritativePrice, $totalCharge),
            ));
			return;
		}

		$authoritativePriceInCents = $authoritativePrice * 100;

		$stripeCharge = TDOSubscription::makeStripeCharge($userID,
														  NULL, // teamID
														  $stripeToken,
														  $last4,
														  $authoritativePriceInCents, // unitPriceInCents
														  $authoritativePriceInCents, // unitCombinedPriceInCents
														  $authoritativePriceInCents, // subtotalInCents
														  0, // discountPercentage
														  0, // discountInCents
														  0, // teamCreditMonths
														  0, // teamCreditsPriceDiscountInCents
														  $authoritativePriceInCents,
														  $chargeDescription,
														  $subscriptionType,
														  $newExpirationDate);

		if (empty($stripeCharge) || isset($stripeCharge['errcode']))
		{
			error_log("HandleSubscriptionMethods::purchasePremiumAccount failed when calling TDOSubscription::makeStripeCharge()");
			echo '{"success":false, "errcode":"' . $stripeCharge["errcode"] . '","errdesc":"' . $stripeCharge["errdesc"] . '"}';
			return;
		}

		if (TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationDate, $subscriptionType, SUBSCRIPTION_LEVEL_PAID) == false)
		{
			// CRITIAL PROBLEM - A personal subscription was paid for but not
			// updated, so send a mail to support so they can make sure to
			// fix this.
			error_log("HandleSubscriptionMethods::purchasePremiumAccount unable to extend subscription after payment for subscriptionID ($subscriptionID) and expiration date ($newExpirationDate)");
			TDOMailer::sendSubscriptionUpdateErrorNotification($subscriptionID, $newExpirationDate);
		}

        //Bug 7405 - We need to clear out the user's auto-renew history entry if there is one, in case they
        //were having problems with their old card. This way if their old card was failing to auto-renew, they
        //will still be able to auto-renew with the new card
       TDOSubscription::removeSubscriptionFromAutorenewQueue($subscriptionID);

		// Keep a record of the charge!
		$nowTimestamp = time();
		if (isset($stripeCharge['customer']))
			$stripeCustomerID = $stripeCharge['customer'];
		else
			$stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);

		if (empty($stripeCustomerID))
		{
			error_log('HandleSubscriptionMethods::purchasePremiumAccount failed to retrieve stripeCustomerID');

			// Email the support team to let them know a charge was successful
			// but for whatever reason, we weren't able to find a Stripe
			// Customer ID, which means we could not log the purchase anywhere.
			TDOMailer::sendSubscriptionLogErrorNotification($subscriptionID);
		}
		else
		{
			$cardType = 'N/A';
			$last4 = 'XXXX';
			if (isset($stripeCharge->card))
			{
				$card = $stripeCharge->card;
				if (isset($card['type']))
					$cardType = $card['type'];
				if (isset($card['last4']))
					$last4 = $card['last4'];
			}

			TDOSubscription::logStripePayment($userID, NULL, 1, $stripeCustomerID, $stripeCharge->id, $cardType, $last4, $subscriptionType, $stripeCharge->amount, $nowTimestamp, $chargeDescription);
			TDOSubscription::addOrUpdateUserPaymentSystemInfo($userID, PAYMENT_SYSTEM_TYPE_STRIPE, $stripeCustomerID);

			$nowTimestamp = time();
			if (!TDOReferral::recordPurchaseForUser($userID, $nowTimestamp))
			{
				// This is a soft fail. Everything else worked, but the referral
				// was not processed, for whatever reason.
				error_log("HandleSubscriptionMethods::purchasePremiumAccount - a referral was not processed for whatever reason in TDOReferral::recordPurchaseForUser()");
			}
		}

		echo '{"success":true}';
	}
    else if ($method == "switchBillingMethodsFromIAP")
    {
        // PARAMETERS:
        //      totalCharge         required - the total amount the user will be charged (although they won't be charged anything right now)
		//		subscriptionType	required - either "month" or "year" to indicate the duration of the purchase
		//		stripeToken			required - one time stripe token for new card

		$stripeToken = NULL;

		if (!isset($_POST['subscriptionType']) || !isset($_POST['totalCharge']) || !isset($_POST['stripeToken']))
		{
			error_log("HandleSubscriptionMethods::switchBillingMethodsFromIAP called and missing a required parameter");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('required parameter is missing'),
            ));
			return;
		}

        $stripeToken = $_POST['stripeToken'];

		$subscriptionTypeString = $_POST['subscriptionType'];
		if ( ($subscriptionTypeString != "month") && ($subscriptionTypeString != "year") )
		{
			error_log("HandleSubscriptionMethods::switchBillingMethodsFromIAP: invalid subscriptionType");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Please select either a one month or a one year account.'),
            ));
			return;
		}

		$totalCharge = $_POST['totalCharge'];

		$userID = $session->getUserId();
		$subscription = TDOSubscription::getSubscriptionForUserID($userID);

		if (!$subscription)
		{
			error_log("HandleSubscriptionMethods::switchBillingMethodsFromIAP called with non-existing subscriptionID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('subscriptionID does not exist'),
            ));
			return;
		}

        //
		// PRICE CHECK: Make sure that the price we showed to the user matches
		// the authoritative price (the price calculated by the server).
		$pricingTable = TDOSubscription::getPersonalSubscriptionPricingTable();
		$authoritativePrice = $pricingTable[$subscriptionTypeString];
		if (empty($authoritativePrice))
		{
			error_log("HandleSubscriptionMethods::switchBillingMethodsFromIAP: Error determining authoritive pricing.");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('error verifying authoritative pricing'),
            ));
			return;
		}

		// Use a string compare
		if ((string)$totalCharge != (string)$authoritativePrice)
		{
			error_log("HandleSubscriptionMethods::switchBillingMethodsFromIAP called with an invalid totalCharge of " . $totalCharge . " but we were expecting " . $authoritativePrice);
            echo json_encode(array(
                'success' => FALSE,
                'error' => sprintf(_('Expected total charge of %s but received %s'), $authoritativePrice, $totalCharge),
            ));
			return;
		}

		$authoritativePriceInCents = $authoritativePrice * 100;

		$subscriptionType = SUBSCRIPTION_TYPE_UNKNOWN;
		if ($subscriptionTypeString == 'month')
		{
			$subscriptionType = SUBSCRIPTION_TYPE_MONTH;
		}
		else if ($subscriptionTypeString == 'year')
		{
			$subscriptionType = SUBSCRIPTION_TYPE_YEAR;
		}

		$resultArray = TDOSubscription::saveStripeInfoForUser($userID,
														  $stripeToken);

		if (empty($resultArray) || !isset($resultArray['stripeid']))
		{
			error_log("HandleSubscriptionMethods::switchBillingMethodsFromIAP failed when calling TDOSubscription::saveStripeInfoForUser()");

            if(isset($resultArray['errcode']) && isset($resultArray['errdesc']))
               echo '{"success":false, "errcode":"' . $resultArray["errcode"] . '","errdesc":"' . $resultArray["errdesc"] . '"}';
            else
               echo '{"success":false}';

			return;
		}
        $stripeCustomerID = $resultArray['stripeid'];

        if(TDOSubscription::updateSubscriptionType($subscription->getSubscriptionId(), $subscriptionType) == false)
        {
            //Alert support of this critical error because the user may be charged for the wrong type of subscription
            TDOMailer::sendSubscriptionTypeUpdateErrorNotification($subscription->getSubscriptionId(), $subscriptionType);

        }
        TDOSubscription::addOrUpdateUserPaymentSystemInfo($userID, PAYMENT_SYSTEM_TYPE_STRIPE, $stripeCustomerID);

		echo '{"success":true}';
    }
	else if ($method == "switchAccountToMonthly")
	{
		// This method will ONLY work for switching from an active YEARLY
		// account to a MONTHLY account.

		// PARAMETERS:
		//		subscriptionID
		//
		// RETURNS:
		//		"success":true in JSON if successful

		if (!isset($_POST['subscriptionID']))
		{
			error_log("HandleSubscriptionMethods::switchAccountToMonthly called and missing a required parameter: subscriptionID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('subscriptionID parameter is missing'),
            ));
			return;
		}

		$subscriptionID = $_POST['subscriptionID'];

		// Ensure that the user making this call OWNS the subscription
		$userID = $session->getUserId();
		$subscription = TDOSubscription::getSubscriptionForUserID($userID);
		if (empty($subscription))
		{
			error_log("HandleSubscriptionMethods::switchAccountToMonthly could not locate subscription for userID: $userID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('could not locate user subscription'),
            ));
			return;
		}

		$subscriptionOwner = $subscription->getUserID();
		if ($subscriptionOwner != $userID)
		{
			error_log("HandleSubscriptionMethods::switchAccountToMonthly called with subscription ($subscriptionID) not owned by user ($userID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Cannot change a premium account you do not own'),
            ));
			return;
		}

		$result = TDOSubscription::switchSubscriptionToMonthly($subscriptionID);
		if (empty($result) || isset($result['errcode']))
		{
			error_log("HandleSubscriptionMethods::switchAccountToMonthly failed when calling TDOSubscription::switchSubscriptionToMonthly()");
			echo '{"success":false, "errcode":"' . $stripeCharge["errcode"] . '","errdesc":"' . $stripeCharge["errdesc"] . '"}';
			return;
		}

		echo '{"success":true}';
	}
	else if ($method == "updatePaymentCardInfo")
	{
		// Allows users to update their credit card information
		// that we have on file (actually stored on Stripe.com).

		// PARAMETERS:
		//		stripeToken			optional - either stripeToken or last4 is required, but not both

		if (!isset($_POST['stripeToken']))
		{
			error_log("HandleSubscriptionMethods::updatePaymentCardInfo called and missing a required parameter: stripeToken");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('stripeToken parameter is missing'),
            ));
			return;
		}

		$stripeToken = $_POST['stripeToken'];

		$userID = $session->getUserId();
		$username = TDOUser::usernameForUserId($userID);

		$stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);

		$stripeCustomer = NULL;

		if ($stripeCustomerID == false)
		{
			error_log("HandleSubscriptionMethods::updatePaymentCardInfo could not determine Stripe Customer ID for userID ($userID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not determine Stripe Customer ID'),
            ));
			return;
		}

		// Attempt to get the existing customer from Stripe
		try
		{
			$stripeCustomer = Stripe_Customer::retrieve($stripeCustomerID);
		}
		catch (Stripe_Error $e)
		{
			$body = $e->getJsonBody();
			$err = $body['error'];

			$jsonError = TDOSubscription::jsonErrorForStripeException($e, "Stripe_Customer::retrieve()");

			echo $jsonError;
			return;
		}
		catch (Exception $e)
		{
			// TODO: SEND AN EMAIL TO admin@appigo.com or some other email address that will be watched and will need to possibly respond to a critical error!
			error_log("HandleSubscriptionMethods::updatePaymentCardInfo received an Exception calling Stripe_Customer::retrieve($stripeCustomerID) for userid ($userID): " . $e->getMessage());
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Exception retrieving customer info from payment service. Payment service may be unavailable.'),
            ));
			return;
		}

		// The user has provided NEW card information (we have a new
		// one-time stripeToken).  We need to update their card
		// information on their Stripe Customer ID.
		$stripeCustomer->card = $stripeToken;

		// Take the opportunity to update the email address on the
		// stripe account just in case they differ right now.  It may
		// make things easier debugging/searching our Stripe account to
		// track down any problems later on.
		if ($username)
		{
			if ( (empty($stripeCustomer->email)) || ($username != $stripeCustomer->email) )
				$stripeCustomer->email = $username;
		}

		try
		{
			$stripeCustomer->save();
		}
		catch (Stripe_Error $e)
		{
			$body = $e->getJsonBody();
			$err = $body['error'];

			$jsonError = TDOSubscription::jsonErrorForStripeException($e, "$stripeCustomer->save()");
			echo $jsonError;
			return;
		}
		catch (Exception $e)
		{
			// TODO: SEND AN EMAIL TO admin@appigo.com or some other email address that will be watched and will need to possibly respond to a critical error!
			error_log("HandleSubscriptionMethods::updatePaymentCardInfo received an Exception calling Stripe_Customer::save($stripeCustomerID) for userid ($userID) when attempting to update their credit card information: " . $e->getMessage());
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Error updating credit card information. Payment service may be unavailable.'),
            ));
			return;
		}

		echo '{"success":true}';
	}
	else if ($method == "downgradeToFreeAccount")
	{
		// Allows users to remove their credit card information from Stripe.
		// Right now, Stripe does not support just removing credit card info.
		$userID = $session->getUserId();

		// First, delete the Stripe info from our database and then delete
		// the customer object from Stripe.com.

		$stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);

		$stripeCustomer = NULL;

		if ($stripeCustomerID == false)
		{
			error_log("HandleSubscriptionMethods::downgradeToFreeAccount could not determine Stripe Customer ID for userID ($userID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not determine Stripe Customer ID'),
            ));
			return;
		}

		if (TDOSubscription::deleteStripeCustomerInfoForUserID($userID) == false)
		{
			error_log("HandleSubscriptionMethods::downgradeToFreeAccount received failure from TDOSubscription::deleteStripeCustomerInfoForUserID($userID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Error removing payment card info.'),
            ));
			return;
		}

		// Attempt to get the existing customer from Stripe
		try
		{
			$stripeCustomer = Stripe_Customer::retrieve($stripeCustomerID);
		}
		catch (Stripe_Error $e)
		{
			$body = $e->getJsonBody();
			$err = $body['error'];

			$jsonError = TDOSubscription::jsonErrorForStripeException($e, "Stripe_Customer::retrieve()");

			echo $jsonError;
			return;
		}
		catch (Exception $e)
		{
			// TODO: SEND AN EMAIL TO admin@appigo.com or some other email address that will be watched and will need to possibly respond to a critical error!
			error_log("HandleSubscriptionMethods::downgradeToFreeAccount received an Exception calling Stripe_Customer::retrieve($stripeCustomerID) for userid ($userID): " . $e->getMessage());
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Exception retrieving customer info from payment service. Payment service may be unavailable.'),
            ));
			return;
		}

		try
		{
			$stripeCustomer->delete();
		}
		catch (Stripe_Error $e)
		{
			$body = $e->getJsonBody();
			$err = $body['error'];

			$jsonError = TDOSubscription::jsonErrorForStripeException($e, "$stripeCustomer->delete()");
			echo $jsonError;
			return;
		}
		catch (Exception $e)
		{
			// TODO: SEND AN EMAIL TO admin@appigo.com or some other email address that will be watched and will need to possibly respond to a critical error!
			error_log("HandleSubscriptionMethods::downgradeToFreeAccount received an Exception calling Stripe_Customer::delete() for userid ($userID) when attempting to update their credit card information: " . $e->getMessage());
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Error removing credit card information. Payment service may be unavailable.'),
            ));
			return;
		}

		// Log this action into the user's account history
		$note = "User-initiated downgrade to free account.";
		TDOUser::logUserAccountAction($userID, $userID, USER_ACCOUNT_LOG_TYPE_DOWNGRADE_TO_FREE_ACCOUNT, $note);

		echo '{"success":true}';
	}
	else if ($method == "getPurchaseHistory")
	{
		// This method will ONLY work for switching from an active YEARLY
		// account to a MONTHLY account.

		// RETURNS:
		//		JSON array of purchase history items in the following format or
		//		"success":false:
		//
		//		"success":true
		//		"purchases":
		//			"timestamp":<UNIX TIMESTAMP>
		//			"subscriptionType":"month" or "year"
		//			"description":<STRING with a little bit of explanation>

		$userID = $session->getUserId();

		$purchases = TDOSubscription::getPurchaseHistoryForUserID($userID);
		if (empty($purchases))
		{
			echo '{"success":true, "purchases": ""}';
		}
		else
		{
			$purchasesJSON = json_encode($purchases);
			echo '{"success":true, "purchases":' . $purchasesJSON . '}';
		}


	}
	else if($method == "resendPurchaseReceipt")
	{
		$userID = $session->getUserId();
		$teamID = $_POST['teamID'];
		$teamAccount = TDOTeamAccount::getTeamForTeamID($teamID);

		$username = TDOUser::usernameForUserId($userID);
		$displayName = TDOUser::displayNameForUserId($userID);
		$purchaseDate = $_POST['timestamp'];
		$teamPurchases = TDOTeamAccount::getTeamPurchaseHistory($teamID);
		$purchase = array();
		foreach ($teamPurchases as $p) {
			if ($p['timestamp'] == $purchaseDate) {
				$purchase = $p;
				break;
			}
		}
		$cardType ='';
		$last4 = '';

		$subscriptionType = $purchase['subscriptionType'];
		if ($subscriptionType == 'month') {
			$billingFrequency = 'monthly';
			$subscriptionType = 1;
		} else {
			$billingFrequency = 'yearly';
			$subscriptionType = 2;
		}
		$teamPricingInfo = TDOTeamAccount::getTeamPricingInfo($billingFrequency, $teamAccount->getLicenseCount(), false, false, $teamID);

		$unitPrice = $teamPricingInfo['unitPrice'];
		$unitCombinedPrice = $purchase['amount'];
		$discountPercentage = $teamPricingInfo['discountPercentage'];
		$discountAmount = $teamPricingInfo['discountAmount'];
		$subtotalAmount = $purchase['amount'];
		$purchaseAmount = $purchase['amount'];
		$newExpirationDate = $teamAccount->getExpirationDate();
		$numOfSubscriptions = $purchase['licenseCount'];

		$email_send = TDOMailer::sendTeamPurchaseReceipt($username, $displayName, $teamID, $purchaseDate, $cardType, $last4, $subscriptionType, $unitPrice, $unitCombinedPrice, $discountPercentage, $discountAmount, '', '', $subtotalAmount, $purchaseAmount, $newExpirationDate, $numOfSubscriptions);

		if ($email_send)
		{
			echo '{"success":true}';
		}
		else
		{
			echo '{"success":false}';
		}
		exit;
	}
	else if ($method == "generateVIPPromo")
	{
		$userID			= $session->getUserId();
		$username		= TDOUser::usernameForUserId($userID);

		if (empty($username))
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('No email address/username registered on this account.'),
            ));
			return;
		}

		// Determine the user's email domain
		$userEmailDomain = end(explode("@", $username));
		if (empty($userEmailDomain))
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Username appears to be an invalid email address.'),
            ));
			return;
		}

		$lowercaseEmailDomain = strtolower($userEmailDomain);

		// Verify that the user is on our whitelist
		$whitelistedDomains = explode(",", PROMO_CODE_WHITELISTED_DOMAINS);

		$isValidDomain = false;
		foreach ($whitelistedDomains as $whitelistedDomain)
		{
			if ($lowercaseEmailDomain == $whitelistedDomain)
			{
				$isValidDomain = true;
				break;
			}
		}

		if ($isValidDomain == false)
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('This email does not qualify as a VIP.'),
            ));
			return;
		}

		// If the code makes it this far, the user is from a whitelisted domain
		// and we need to now check to see if they are within the period of
		// being allowed to request a new promo code (subscription has to be
		// expiring within one month).
		$subscription = TDOSubscription::getSubscriptionForUserID($userID);
		if (empty($subscription))
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('This account does not have a valid premium account.'),
            ));
			return;
		}

		$expirationTimestamp = $subscription->getExpirationDate();
		$expirationDate = new DateTime('@' . $expirationTimestamp);
		$now = new DateTime("now", new DateTimeZone("UTC"));
		$oneMonthPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL);
		$oneMonthFromNow = $now->add(new DateInterval($oneMonthPeriodSetting));
		if ($expirationDate > $oneMonthFromNow)
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('This premium account has over one month remaining. Please try again later.'),
            ));
			return;
		}

		$numberOfMonths = 12;
		$displayName = TDOUser::displayNameForUserID($userID);
		$note			= "This is a VIP Promo Code requested by $displayName ($username).";

		$result = TDOPromoCode::createPromoCode($numberOfMonths, $userID, $userID, $note);

		if (empty($result))
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Failed to generate a promo code.'),
            ));
			return;
		}

		$promoLink = $result['promolink'];

		if (!TDOMailer::sendPromoCodeToUser($promoLink, $username))
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Failed to email promo code.'),
            ));
			return;
		}

		echo '{"success":true}';
		return;
	}

?>

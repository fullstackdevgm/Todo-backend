<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	include_once('TodoOnline/DBConstants.php');
	include_once('Stripe/Stripe.php');
	
	Stripe::setApiKey(APPIGO_STRIPE_SECRET_KEY);

	
	if ($method == "purchaseGiftCodes")
	{
		// PARAMETERS:
        //  giftCodes   required - a JSON array of giftCodes the user is purchasing
        //              Each gift code has the following fields:
        //                  subscription_type required - either "month" for a month subscription or "year" for a year subscription
        //                  sender_name optional - the name of the sender as they wish it to appear on the gift message. Defaults to the current user's full name.
        //                  recipient_name required - the name of the recipient of the gift.
        //                  recipient_email optional - the email address to email the gift code to. If not set, no email will be sent.
        //                  message optional - a message the user wishes to add to the email.
        //
		//	totalCharge required - the full price in USD dollars and cents of the purchase
		//	stripeToken optional - either stripeToken or last4 is required, but not both
		//	last4       optional - either last4 or stripeToken is required, but not both
		
		$stripeToken = NULL;
		$last4 = NULL;
		
		if (!isset($_POST['giftCodes']))
		{
			error_log("HandleGiftCodeMethods::purchaseGiftCodes called and missing a required parameter: giftCodes");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('giftCodes parameter is missing'),
            ));
			return;
		}
		if (!isset($_POST['totalCharge']))
		{
			error_log("HandleGiftCodeMethods::purchaseGiftCodes called and missing a required parameter: totalCharge");
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
				error_log("HandleGiftCodeMethods::purchaseGiftCodes called and missing a required parameter: stripeToken or last4 required");
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
        

        
        $giftCodeArray =  json_decode($_POST['giftCodes'], true);
        if(empty($giftCodeArray))
        {
			error_log("HandleGiftCodeMethods::purchaseGiftCodes called with bad parameter: giftCodes");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('giftCodes parameter is empty or JSON could not be parsed'),
            ));
			return;            
        }
        
        //Go through all the sent gift codes and make sure they have all required information and that the pricing matches
        //up with the total charge
        $pricingTable = TDOSubscription::getPersonalSubscriptionPricingTable();
        $expectedTotalCharge = 0;
        
        foreach($giftCodeArray as $giftCode)
        {
            if(!isset($giftCode['subscription_type']) || empty($giftCode['subscription_type']))
            {
                error_log("HandleGiftCodeMethods::purchaseGiftCodes called with invalid gift code: missing subscription_type");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('missing subscription_type for gift code'),
                ));
                return;
            }
            if(!isset($giftCode['recipient_name']) || empty($giftCode['recipient_name']))
            {
                error_log("HandleGiftCodeMethods::purchaseGiftCodes called with invalid gift code: missing recipient_name");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('missing recipient_name for gift code'),
                ));
                return;
            }
            
            $subscriptionTypeString = $giftCode['subscription_type'];
            if ( ($subscriptionTypeString != "month") && ($subscriptionTypeString != "year") )
            {
                error_log("HandleGiftCodeMethods::purchaseGiftCodes: invalid subscription type");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Please select either a one month or a one year account.'),
                ));
                return;
            }
            
            $authoritativePrice = $pricingTable[$subscriptionTypeString];
            if (empty($authoritativePrice))
            {
                error_log("HandleGiftCodeMethods::purchaseGiftCodes: Error determining authoritive pricing.");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('error verifying authoritative pricing'),
                ));
                return;
            }
            $expectedTotalCharge += $authoritativePrice;
            
        }
        
        $totalCharge = $_POST['totalCharge'];
        
        if ((string)$totalCharge != (string)$expectedTotalCharge)
		{
			error_log("HandleGiftCodeMethods::purchaseGiftCodes called with an invalid totalCharge of " . $totalCharge . " but we were expecting " . $expectedTotalCharge);
            echo json_encode(array(
                'success' => FALSE,
                'error' => sprintf(_('Expected total charge of &#39;%s&#39; but received &#39;%s&#39;'), $expectedTotalCharge, $totalCharge),
            ));
			return;
		}
        
        $count = count($giftCodeArray);
        if($count == 1)
            $chargeDescription = _('Todo® Cloud Subscription Gift Code');
        else
            $chargeDescription = sprintf(_('Todo® Cloud Subscription Gift Codes (%s)'), count($giftCodeArray));
		
		$userID = $session->getUserId();
		$authoritativePriceInCents = $expectedTotalCharge * 100;
		
		
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
														  NULL,
														  NULL,
                                                          true);
		
		if (empty($stripeCharge) || isset($stripeCharge['errcode']))
		{
			error_log("HandleGiftCodeMethods::purchaseGiftCodes failed when calling TDOSubscription::makeStripeCharge()");
			echo '{"success":false, "errcode":"' . $stripeCharge["errcode"] . '","errdesc":"' . $stripeCharge["errdesc"] . '"}';
			return;
		}
        
        //Add the charge to tdo_stripe_gift_payment_history
        $stripeGiftPaymentId = NULL;
        $nowTimestamp = time();

		if (isset($stripeCharge['customer']))
			$stripeCustomerID = $stripeCharge['customer'];
		else
			$stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);
		
		if (empty($stripeCustomerID))
		{
			error_log("HandleGiftCodeMethods::purchaseGiftCodes failed to retrieve stripeCustomerID");
			
			// Email the support team to let them know a charge was successful
			// but for whatever reason, we weren't able to find a Stripe
			// Customer ID, which means we could not log the purchase anywhere.
			TDOMailer::sendGiftCodeLogErrorNotification($userID, $nowTimestamp, count($giftCodeArray));
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
            $stripeGiftPayment = new TDOStripeGiftPayment();
            $stripeGiftPayment->setUserId($userID);
            $stripeGiftPayment->setStripeUserId($stripeCustomerID);
            $stripeGiftPayment->setStripeChargeId($stripeCharge->id);
            $stripeGiftPayment->setCardType($cardType);
            $stripeGiftPayment->setLastFour($last4);
            $stripeGiftPayment->setAmount($stripeCharge->amount);
            $stripeGiftPayment->setTimestamp($nowTimestamp);
            
            if($stripeGiftPayment->addStripeGiftPayment() == false)
            {
                error_log("HandleGiftCodeMethods::purchaseGiftCodes failed to add stripe gift payment to database");
                
                // Email the support team to let them know a charge was successful
                // but for whatever reason, we weren't able to save the log to the database.
                TDOMailer::sendGiftCodeLogErrorNotification($userID, $nowTimestamp, count($giftCodeArray));          
            }
            else
            {
                $stripeGiftPaymentId = $stripeGiftPayment->stripeGiftPaymentId();
            }

            
            //If the user already has stripe info stored with us, update it
            $paymentSystemInfo = TDOSubscription::paymentSystemInfoForUserID($userID);
			if(!empty($paymentSystemInfo) && $paymentSystemInfo['payment_system_type'] == PAYMENT_SYSTEM_TYPE_STRIPE && $paymentSystemInfo['payment_system_userid'] != $stripeCustomerID)
			{
				TDOSubscription::updateUserPaymentSystemInfo($userID, PAYMENT_SYSTEM_TYPE_STRIPE, $stripeCustomerID);
			}
		}
        
        $addedGiftCodes = array();
        
        //Now go through all the gift codes we were sent and add them to the database
        foreach($giftCodeArray as $giftCodeJson)
        {
            $subscriptionType = $giftCodeJson['subscription_type'];
            if(isset($giftCodeJson['sender_name']) && !empty($giftCodeJson['sender_name']))
                $senderName = $giftCodeJson['sender_name'];
            else
                $senderName = TDOUser::displayNameForUserId($userID);
            
            $recipientName = $giftCodeJson['recipient_name'];
            $recipientEmail = NULL;
            if(isset($giftCodeJson['recipient_email']))
                $recipientEmail = $giftCodeJson['recipient_email'];
            
            $message = NULL;
            if(isset($giftCodeJson['message']))
                $message = $giftCodeJson['message'];
            
            if($subscriptionType == "year")
                $subscriptionDuration = 12;
            else
                $subscriptionDuration = 1;
            
            $giftCode = new TDOGiftCode();
            $giftCode->setSubscriptionDuration($subscriptionDuration);
            $giftCode->setStripeGiftPaymentId($stripeGiftPaymentId);
            $giftCode->setPurchaserUserId($userID);
            $giftCode->setPurchaseTimestamp($nowTimestamp);
            $giftCode->setSenderName($senderName);
            $giftCode->setRecipientName($recipientName);
            $giftCode->setRecipientEmail($recipientEmail);
            $giftCode->setMessage($message);
            
            if($giftCode->addGiftCode() == false)
            {
                // CRITIAL PROBLEM - A personal subscription was paid for but not
                // updated, so send a mail to support so they can make sure to
                // fix this.
                error_log("HandleGiftCodeMethods::purchaseGiftCodes unable to add gift code after payment for userID ($userID)");
                TDOMailer::sendGiftCodeUpdateErrorNotification($userID, $stripeGiftPaymentId, $nowTimestamp, $recipientName, $recipientEmail, $subscriptionDuration);
            }
            else
            {
                $addedGiftCodes[] = $giftCode;
                if($giftCode->recipientEmail() != NULL)
                {
                    $email = $giftCode->recipientEmail();
                    $validatedEmail = TDOMailer::validate_email($email);
                    if(empty($validatedEmail))
                    {
                        error_log("HandleGiftCodeMethods::purchaseGiftCodes found invalid email: $email. Not attempting to send gift code link");
                    }
                    else
                    {
                        $codeLink = TDOGiftCode::giftCodeLinkForCode($giftCode->giftCode());
                        if(TDOMailer::sendGiftCodeLinkToUser($recipientName, $validatedEmail, $senderName, $message, $subscriptionType, $codeLink) == false)
                        {
                            error_log("HandleGiftCodeMethods::purchaseGiftCodes failed to send email to recipient of gift code");
                        }
                    }
                    
                }
            }
        }

        //If the user purchases more than 5 gift codes at once, send an email to support
        //so they can keep an eye out for anything fishy
        $numAdded = count($addedGiftCodes);
        if($numAdded > 5)
        {
            TDOMailer::sendGiftCodeMassPurchaseNotification($userID, $stripeGiftPaymentId, $nowTimestamp, $numAdded);
        }

        //SUCCESS! Send an email confirmation of the purchase.
        try
        {
            // Catch all exceptions here because if anything fails, we want
            // to make sure that we continue on and return success.
            $username = TDOUser::usernameForUserId($userID);
            $displayName = TDOUser::firstNameForUserId($userID);
            $purchaseDate = time();
            $cardType = NULL;
            $last4 = NULL;
            $cardOwnerName = NULL;
            if (isset($stripeCharge['card']))
            {
                $stripeCard = $stripeCharge['card'];
                
                if (isset($stripeCard['type']))
                    $cardType = $stripeCard['type'];
                
                if (isset($stripeCard['last4']))
                    $last4 = $stripeCard['last4'];
                
//					if (isset($stripeCard['name']))
//						$cardOwnerName = $stripeCard['name'];
            }
            
            $purchaseAmount = $authoritativePriceInCents / 100;

            TDOMailer::sendGiftCodePurchaseReceipt($username, $displayName, $purchaseDate, $cardType, $last4, $addedGiftCodes, $purchaseAmount);
            
        }
        catch (Exception $e)
        {
            error_log("HandleGiftCodeMethods::purchaseGiftCodes (User: $userID) unable to send notification email about a successful purchase.");
        }

		
		echo '{"success":true}';
	}
    else if($method == "resendGiftCodeEmail")
    {
        // PARAMETERS:
        // gift_code        required - the 'id' of the gift code
        // sender_name      optional - the name of the sender as they wish it to appear on the gift message. Defaults to the current user's full name.
        // recipient_name   required - the name of the recipient of the gift.
        // recipient_email  required - the email address to email the gift code to. If not set, no email will be sent.
        // message          optional - a message the user wishes to add to the email.
        
        if(!isset($_POST['gift_code']) || !isset($_POST['recipient_name']) || !isset($_POST['recipient_email']))
        {
            error_log("HandleGiftCodeMethods::resendGiftCodeEmail called missing required parameter (gift_code or recipient_name or recipient_email");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing required parameter'),
            ));
            return;
        }
        
        $giftCode = TDOGiftCode::giftCodeForCode($_POST['gift_code']);
        if(empty($giftCode))
        {
            error("HandleGiftCodeMethods::resendGiftCodeEmail unable to get gift code for code: ".$_POST['gift_code']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unable to find gift code in database'),
            ));
            return;
        }
        
        if($giftCode->purchaserUserId() != $session->getUserId())
        {
            error("HandleGiftCodeMethods::resendGiftCodeEmail called on gift code not owned by current user");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('user id does not match gift code purchaser'),
            ));
            return;
        }
        
        //If the gift code is already redeemed, don't let them resend
        if($giftCode->consumptionDate() != 0 || $giftCode->consumerUserId() != NULL)
        {
            error("HandleGiftCodeMethods::resendGiftCodeEmail called on used gift code");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('that gift code has already been used'),
            ));
            return;
        }
        
        if(isset($_POST['sender_name']) && !empty($_POST['sender_name']))
            $senderName = $_POST['sender_name'];
        else
            $senderName = TDOUser::displayNameForUserId($session->getUserId());

        $giftCode->setSenderName($senderName);
        $giftCode->setRecipientName($_POST['recipient_name']);
        $giftCode->setRecipientEmail($_POST['recipient_email']);
        if(isset($_POST['message']))
            $giftCode->setMessage($_POST['message']);
        else
            $giftCode->setMessage(NULL);
    
        $giftCodeLink = TDOGiftCode::giftCodeLinkForCode($giftCode->giftCode());
    
        if($giftCode->subscriptionDuration() == 12)
            $subscriptionType = 'year';
        else
            $subscriptionType = 'month';
    
        $email = $giftCode->recipientEmail();
        $validatedEmail = TDOMailer::validate_email($email);
        if(empty($validatedEmail))
        {
            error_log("HandleGiftCodeMethods::resendGiftCodeEmail unable to send email to: $email");
            echo json_encode(array(
                'success' => FALSE,
                'error' => sprintf(_('invalid email: %s'), $email),
            ));
            return;
        }
        
    
        if(TDOMailer::sendGiftCodeLinkToUser($giftCode->recipientName(), $validatedEmail, $senderName, $giftCode->message(), $subscriptionType, $giftCodeLink) == false)
        {
            error_log("HandleGiftCodeMethods::resendGiftCodeEmail unable to resend email");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unable to send email'),
            ));
            return;
        }
    
        if($giftCode->updateGiftCode() == false)
        {
            error_log("HandleGiftCodeMethods::resendGiftCodeEmail unable to update gift code after resending");
        }
        
        echo '{"success":true}';
        
    }
    else if($method == "getGiftCodesForCurrentUser")
    {
        //Returns a JSON array of gift codes purchased by the current user
    
        $userId = $session->getUserId();
        $giftCodes = TDOGiftCode::giftCodesForUser($userId);
        
        if($giftCodes === false)
        {
            error_log("HandleGiftCodeMethods::getGiftCodesForCurrentUser unable to get gift codes for user: $userId");
            echo '{"success":false}';
            return;
        }
        
        $giftCodeJson = array();
        foreach($giftCodes as $code)
        {
            $codeJson = $code->getPropertiesArray(true);
            $giftCodeJson[] = $codeJson;
        }
        
        $responseArray = array();
        $responseArray['success'] = true;
        $responseArray['gift_codes'] = $giftCodeJson;
        
        echo json_encode($responseArray);
        
    }
    else if($method == "getBillingInfoForCurrentUser")
    {

        $billingInfo = TDOSubscription::getSubscriptionBillingInfoForUser($session->getUserId());
        
        //If the user doesn't have any billing info, just return success but no billing info
        $responseJSON = array();
        $responseJSON['success'] = true;
        if($billingInfo)
        {
            $responseJSON['billing_info'] = $billingInfo;
        }
        
        echo json_encode($responseJSON);
    }
    else if($method == "applyGiftCodeToAccount")
    {
        $userID = $session->getUserId();
        $errorMessage = NULL;
        if(isset($_POST['giftcode']))
        {
            $giftCodeId = $_POST['giftcode'];
            $giftCode = TDOGiftCode::giftCodeForCode($giftCodeId);
            if(!empty($giftCode))
            {
                if($giftCode->consumptionDate() == 0 && $giftCode->consumerUserId() == NULL)
                {
                    //Don't allow the gift code to be applied to their account yet if they have an
                    //auto-renewing IAP subscription
                    if(TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userID) == false)
                    {
                    
                        $subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userID);
                        if ($subscriptionID)
                        {
                            $originalSubscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
                            if($originalSubscription)
                            {
                                //Prevent gift code from being applied to account that is already 2 years or more from expiring
                                $twoYearsFromNow = mktime(0, 0, 0, date("n"), date("j"), (date("Y") + 2));
                                
                                if($originalSubscription->getExpirationDate() < $twoYearsFromNow)
                                {
                                    if (TDOGiftCode::applyGiftCodeToSubscription($giftCode, $userID, $subscriptionID))
                                    {
                                        $subscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
                                        $expirationTimestamp = $subscription->getExpirationDate();
                                        
                                        echo '{"success":true, "expiration_date":'.$expirationTimestamp.'}';
                                        return;
                                    }
                                    else
                                    {
                                        $errorMessage = _("Could not apply the gift code to your account.");
                                    }
                                }
                                else
                                {
                                    $errorMessage = _("The gift code could not be applied to your account because your current subscription does not expire within the next two years.");
                                }
                            }
                            else
                            {
                                $errorMessage = _("Your account cannot accept a gift code, please contact the support team.");
                            }
                        }
                        else
                        {
                            $errorMessage = _("Your account cannot accept a gift code, please contact the support team.");
                        }
                    }
                    else
                    {
                        $errorMessage = sprintf(_("This gift code cannot be used in conjunction with your renewing In-App Purchase subscription.%sThe gift code is still valid and may be redeemed when your premium account has expired.%sFor more information, please visit the %sAppigo Help Center%s."), '<br/>', '<br/><br/>', '<a href="http://help.appigo.com/entries/23360366-Why-is-my-account-not-eligible-to-participate-in-the-Todo-Pro-Referrals-Program-" target="_blank" style="cursor:hand;text-decoration:underline;">', '</a>');
                    }
                }
                else
                {
                    $errorMessage = _("This gift code has already been used.");
                }
            }
            else
            {
                $errorMessage = _("This is not a valid gift code.");
            }
        }
        else
        {
            $errorMessage = _("No gift code found in link.");
        }

        echo json_encode(array(
            'success' => FALSE,
            'error' => $errorMessage,
        ));
    }
    
?>

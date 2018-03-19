<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	include_once('TodoOnline/DBConstants.php');
	include_once('Stripe/Stripe.php');
	
	Stripe::setApiKey(APPIGO_STRIPE_SECRET_KEY);
	
	define ('SUBSCRIPTION_API_KEY_SECRET', '52375359-A888-4B6C-B2F8-05DDEDAE478C');
	
	$userID = $session->getUserId();
	
	if ($method == "getTeamPricingInfo")
	{
		// Parameters
		//	teamID					optional - this is only required for a team
		//									   that already exists. For a team
		//									   that is just being created, this
		//									   isn't known yet.
		//	billingFrequency		required - either "monthly" or "yearly"
		//	numberOfSubscriptions	required - number value
		
		if (!isset($_POST['billingFrequency']))
		{
			error_log("HandleTeamMethods::getTeamPricingInfo called with a missing required parameter: billingFrequency");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('billingFrequency parameter is missing'),
            ));
			return;
		}
		if (!isset($_POST['numberOfSubscriptions']))
		{
			error_log("HandleTeamMethods::getTeamPricingInfo called with a missing required parameter: numberOfSubscriptions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('numberOfSubscriptions parameter is missing'),
            ));
			return;
		}
		
		$zipCode = NULL;
		if (isset($_POST['zipCode']))
		{
			$zipCode = $_POST['zipCode'];
		}
		
		$billingFrequency = $_POST['billingFrequency'];
		$numberOfSubscriptions = $_POST['numberOfSubscriptions'];
		
		$pricingInfo = TDOTeamAccount::getTeamPricingInfo($billingFrequency, $numberOfSubscriptions, false, $userID);
		if (!$pricingInfo)
		{
			error_log("HandleTeamMethods::getTeamPricingInfo error calling TDOTeamAccount::getTeamPricingInfo()");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Error getting team pricing information. Please try again later.'),
            ));
			return;
		}
		
		$result = array(
						"success" => true,
						"pricingInfo" => $pricingInfo
		);
		
		echo json_encode($result);
		return;
	}
	else if ($method == "purchaseTeamAccount")
	{
		// PARAMETERS:
		//	stripeToken			required
		//	numOfSubscriptions	required
		//	billingFrequency	required ('monthly' or 'yearly')
		//	bizCountry			required
		//	totalPrice			required
		//	teamName			required
		//
		//	bizName				optional
		//	bizPhone			optional
		//	bizAddr1			optional
		//	bizAddr2			optional
		//	bizCity				optional
		//	bizState			optional
		//	zipCode				optional
		//  discoveryAnswer     optional
		
		if (!isset($_POST['stripeToken']))
		{
			error_log("HandleTeamMethods::purchaseTeamAccount called with a missing required parameter: stripeToken");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('stripeToken parameter is missing'),
            ));
			return;
		}
		$stripeToken = $_POST['stripeToken'];
		
		if (!isset($_POST['numOfSubscriptions']))
		{
			error_log("HandleTeamMethods::purchaseTeamAccount called with a missing required parameter: numOfSubscriptions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('numOfSubscriptions parameter is missing'),
            ));
			return;
		}
		$numOfSubscriptions = $_POST['numOfSubscriptions'];
		
		if (!isset($_POST['billingFrequency']))
		{
			error_log("HandleTeamMethods::purchaseTeamAccount called with a missing required parameter: billingFrequency");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('billingFrequency parameter is missing'),
            ));
			return;
		}
		$billingFrequency = $_POST['billingFrequency'];
		
		if (!isset($_POST['bizCountry']))
		{
			error_log("HandleTeamMethods::purchaseTeamAccount called with a missing required parameter: bizCountry");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('bizCountry parameter is missing'),
            ));
			return;
		}
		$bizCountry = $_POST['bizCountry'];
		
		if (!isset($_POST['totalPrice']))
		{
			error_log("HandleTeamMethods::purchaseTeamAccount called with a missing required parameter: totalPrice");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('totalPrice parameter is missing'),
            ));
			return;
		}
		$totalPrice = $_POST['totalPrice'];
		
		if (!isset($_POST['teamName']))
		{
			error_log("HandleTeamMethods::purchaseTeamAccount called with a missing required parameter: teamName");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamName parameter is missing'),
            ));
			return;
		}
		$teamName = $_POST['teamName'];
		
		
		//bizName,bizPhone,bizAddr1,bizAddr2,bizCity,bizState
		$bizName = NULL;
		if (isset($_POST['bizName']))
			$bizName = $_POST['bizName'];
		
		$bizPhone = NULL;
		if (isset($_POST['bizPhone']))
			$bizPhone = $_POST['bizPhone'];
		
		$bizAddr1 = NULL;
		if (isset($_POST['bizAddr1']))
			$bizAddr1 = $_POST['bizAddr1'];
		
		$bizAddr2 = NULL;
		if (isset($_POST['bizAddr2']))
			$bizAddr2 = $_POST['bizAddr2'];
		
		$bizCity = NULL;
		if (isset($_POST['bizCity']))
			$bizCity = $_POST['bizCity'];
		
		$bizState = NULL;
		if (isset($_POST['bizState']))
			$bizState = $_POST['bizState'];
		
		$zipCode = NULL;
		if (isset($_POST['zipCode']))
			$zipCode = $_POST['zipCode'];
		
		$discoveryAnswer = NULL;
		if (isset($_POST['discoveryAnswer']))
			$discoveryAnswer = $_POST['discoveryAnswer'];
		
		$result = TDOTeamAccount::createAndPurchaseTeamAccount($userID,
															   $stripeToken,
															   $numOfSubscriptions,
															   $billingFrequency,
															   $bizCountry,
															   $zipCode,
															   $totalPrice,
															   $teamName,
															   $bizName,
															   $bizPhone,
															   $bizAddr1,
															   $bizAddr2,
															   $bizCity,
															   $bizState,
															   NULL,
															   $discoveryAnswer);
		if (!$result)
		{
			error_log("HandleTeamMethods::createTeam could not create a new team (userid: $userID, teamname: $teamName)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not create a new team. Please try again later.'),
            ));
			return;
		}
		else if (empty($result['teamid']))
		{
			error_log("HandleTeamMethods::createTeam could not create a new team (userid: $userID, teamname: $teamName): " . $result['error']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => $result['error'],
            ));
			return;
		}
		
		$result = array(
						"success" => true,
						"teamid" => $result['teamid']
		);
		
		// TODO: Log an administrative log
		
		echo json_encode($result);
		return;
	}
	else if ($method == "createTeamAccountWithTrial")
	{
		// PARAMETERS:
		//	stripeToken			required
		//	numOfSubscriptions	required
		//	billingFrequency	required ('monthly' or 'yearly')
		//	bizCountry			required
		//	teamName			required
		//
		//	bizName				optional
		//	bizPhone			optional
		//	bizAddr1			optional
		//	bizAddr2			optional
		//	bizCity				optional
		//	bizState			optional
		//	zipCode				optional
		//  discoveryAnswer		optional (plain text)
		
		if (!isset($_POST['stripeToken']))
		{
			error_log("HandleTeamMethods::createTeamAccountWithTrial called with a missing required parameter: stripeToken");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('stripeToken parameter is missing'),
            ));
			return;
		}
		$stripeToken = $_POST['stripeToken'];
		
		if (!isset($_POST['numOfSubscriptions']))
		{
			error_log("HandleTeamMethods::createTeamAccountWithTrial called with a missing required parameter: numOfSubscriptions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('numOfSubscriptions parameter is missing'),
            ));
			return;
		}
		$numOfSubscriptions = $_POST['numOfSubscriptions'];
		
		if (!isset($_POST['billingFrequency']))
		{
			error_log("HandleTeamMethods::createTeamAccountWithTrial called with a missing required parameter: billingFrequency");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('billingFrequency parameter is missing'),
            ));
			return;
		}
		$billingFrequency = $_POST['billingFrequency'];
		
		if (!isset($_POST['bizCountry']))
		{
			error_log("HandleTeamMethods::createTeamAccountWithTrial called with a missing required parameter: bizCountry");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('bizCountry parameter is missing'),
            ));
			return;
		}
		$bizCountry = $_POST['bizCountry'];
		
		if (!isset($_POST['teamName']))
		{
			error_log("HandleTeamMethods::createTeamAccountWithTrial called with a missing required parameter: teamName");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamName parameter is missing'),
            ));
			return;
		}
		$teamName = htmlspecialchars($_POST['teamName']);
		
		
		//bizName,bizPhone,bizAddr1,bizAddr2,bizCity,bizState
		$bizName = NULL;
		if (isset($_POST['bizName']))
			$bizName = $_POST['bizName'];
		
		$bizPhone = NULL;
		if (isset($_POST['bizPhone']))
			$bizPhone = $_POST['bizPhone'];
		
		$bizAddr1 = NULL;
		if (isset($_POST['bizAddr1']))
			$bizAddr1 = $_POST['bizAddr1'];
		
		$bizAddr2 = NULL;
		if (isset($_POST['bizAddr2']))
			$bizAddr2 = $_POST['bizAddr2'];
		
		$bizCity = NULL;
		if (isset($_POST['bizCity']))
			$bizCity = $_POST['bizCity'];
		
		$bizState = NULL;
		if (isset($_POST['bizState']))
			$bizState = $_POST['bizState'];
		
		$zipCode = NULL;
		if (isset($_POST['zipCode']))
			$zipCode = $_POST['zipCode'];
		
		$discoveryAnswer = NULL;
		if (isset($_POST['discoveryAnswer']))
			$discoveryAnswer = $_POST['discoveryAnswer'];
		
		$result = TDOTeamAccount::createTeamAccountWithTrial($userID,
															 $stripeToken,
															 $numOfSubscriptions,
															 $billingFrequency,
															 $bizCountry,
															 $zipCode,
															 $teamName,
															 $bizName,
															 $bizPhone,
															 $bizAddr1,
															 $bizAddr2,
															 $bizCity,
															 $bizState,
															 NULL,
															 $discoveryAnswer);
		if (!$result)
		{
			error_log("HandleTeamMethods::createTeamAccountWithTrial could not create a new team (userid: $userID, teamname: $teamName)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not create a new team. Please try again later.'),
            ));
			return;
		}
		else if (empty($result['teamid']))
		{
			error_log("HandleTeamMethods::createTeamAccountWithTrial could not create a new team (userid: $userID, teamname: $teamName): " . $result['error']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => $result['error'],
            ));
			return;
		}
        $user = TDOUser::getUserForUserId($userID);
        $message_key = USER_ACCOUNT_MESSAGE_CURRENT;
        if ($numOfSubscriptions == 1) {
            $message_key = USER_ACCOUNT_MESSAGE_TRIAL_ONE;
        } elseif ($numOfSubscriptions > 1) {
            $message_key = USER_ACCOUNT_MESSAGE_TRIAL_MANY;
        }

        $messages = $user->userMessages();
        if (is_array($messages)) {
            if (array_key_exists($message_key, $messages)) {
                $messages[$message_key] = 1;
            }
        } elseif (!$messages || $messages == '') {
            $messages = array($message_key => 1);
        }
        $user->setUserMessages($messages);
        $user->updateUser();

        $result = array(
						"success" => true,
						"teamid" => $result['teamid']
						);
		
		// TODO: Log an administrative log
		
		echo json_encode($result);
		return;
	}
	else if ($method == "updateTeamName")
	{
		// PARAMETERS:
		//	teamid			(required)
		//	teamName		(required)
		
		if (!isset($_POST['teamid']))
		{
			error_log("HandleTeamMethods::updateTeamName called with a missing required parameter: teamid");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamid parameter is missing'),
            ));
			return;
		}
		$teamid = $_POST['teamid'];
		
		if (!isset($_POST['teamName']))
		{
			error_log("HandleTeamMethods::updateTeamName called with a missing required parameter: teamName");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamName parameter is missing'),
            ));
			return;
		}
		$teamName = htmlspecialchars($_POST['teamName']);
		
		// Optional to-do: change the return of updateTeamName to return error
		// codes so you'd know whether the update didn't occur because of a
		// rights issue (you must be a team admin).
		if (!TDOTeamAccount::updateTeamName($userID, $teamid, $teamName))
		{
			error_log("HandleTeamMethods::updateTeamName could not update the team name (userid: $userID, teamid: $teamid, name: $teamName)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not update the team name. Please try again later.'),
            ));
			return;
		}
		
		$result = array(
						"success" => true
						);
		
		// TODO: Log an administrative log
		
		echo json_encode($result);
		return;
	}
	else if ($method == "updateTeamInfo")
	{
		// PARAMETERS:
		//	teamid			(required)
		//	bizName			(optional)
		//	bizPhone		(optional)
		//	bizAddr1		(optional)
		//	bizAddr2		(optional)
		//	bizCity			(optional)
		//	bizState		(optional)
		//	bizCountry		(optional)
		//	bizPostalCode	(optional)
		
		if (!isset($_POST['teamid']))
		{
			error_log("HandleTeamMethods::updateTeamInfo called with a missing required parameter: teamid");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamid parameter is missing'),
            ));
			return;
		}
		$teamid = $_POST['teamid'];
		
		$bizName		= NULL;
		$bizPhone		= NULL;
		$bizAddr1		= NULL;
		$bizAddr2		= NULL;
		$bizCity		= NULL;
		$bizState		= NULL;
		$bizCountry		= NULL;
		$bizPostalCode	= NULL;
		if (isset($_POST['bizName']))
			$bizName = $_POST['bizName'];
		if (isset($_POST['bizPhone']))
			$bizPhone = $_POST['bizPhone'];
		if (isset($_POST['bizAddr1']))
			$bizAddr1 = $_POST['bizAddr1'];
		if (isset($_POST['bizAddr2']))
			$bizAddr2 = $_POST['bizAddr2'];
		if (isset($_POST['bizCity']))
			$bizCity = $_POST['bizCity'];
		if (isset($_POST['bizState']))
			$bizState = $_POST['bizState'];
		if (isset($_POST['bizCountry']))
			$bizCountry = $_POST['bizCountry'];
		if (isset($_POST['bizPostalCode']))
			$bizPostalCode = $_POST['bizPostalCode'];
		
		// Optional to-do: change the return of updateTeamName to return error
		// codes so you'd know whether the update didn't occur because of a
		// rights issue (you must be a team admin).
		if (!TDOTeamAccount::updateTeamInfo($userID, $teamid, $bizName, $bizPhone, $bizAddr1, $bizAddr2, $bizCity, $bizState, $bizCountry, $bizPostalCode))
		{
			error_log("HandleTeamMethods::updateTeamInfo could not update the team info (userid: $userID, teamid: $teamid)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not update the team info. Please try again later.'),
            ));
			return;
		}
		
		$result = array(
						"success" => true
						);
		
		// TODO: Log an administrative log
		
		echo json_encode($result);
		return;
	}
	else if ($method == "inviteTeamMember")
	{
		// Parameters
		//	teamid		(required)
		//	email		(required)
		//	memberType	(required)
		
		if (!isset($_POST['teamid']))
		{
			error_log("HandleTeamMethods::inviteTeamMember called with a missing required parameter: teamid");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamid parameter is missing'),
            ));
			return;
		}
		$teamid = $_POST['teamid'];
		
		// If this is a team-owned list, make sure that the team subscription is NOT
		// expired.
		$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamid);
		if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
		{
			error_log("Method inviteTeamMember found that team subscription is expired for team: " . $teamid);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Invitations to join a team are not allowed on expired team accounts.'),
            ));
			return;
		}
		
		if (!isset($_POST['email']))
		{
			error_log("HandleTeamMethods::inviteTeamMember called with a missing required parameter: email");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('email parameter is missing'),
            ));
			return;
		}
		$emails = $_POST['email'];
		
		if (!isset($_POST['memberType']))
		{
			error_log("HandleTeamMethods::inviteTeamMember called with a missing required parameter: memberType");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('memberType parameter is missing'),
            ));
			return;
		}
        $memberType = intval($_POST['memberType']);

        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];

        $result = array(
            'success' => TRUE,
            'error' => '',
            'invitations' => array()
        );
        if (!is_array($emails)) {
            $emails = explode("\r", trim($emails));
        }
        if (is_array($emails)) {
            /**
             * if we get bunch of emails
             * starts v2.4
             */
            foreach ($emails as $index => $email) {
				$email = trim($email);
                $invitationInfo = TDOTeamAccount::inviteTeamMember($userID, $teamid, $email, $memberType);
                if (!$invitationInfo) {
                    error_log("HandleTeamMethods::inviteTeamMember could not complete the invitation (userid: $userID, teamid: $teamid, email: $email)");
                    $result['success'] = FALSE;
                    $result['error'] .= 'Could not send invitation for ' . $email;
                }
                $result['invitations'][] = array(
                    'invitationid' => $invitationInfo['invitationid'],
                    'email' => $email
                );
            }
        } else {
            /**
             * we get single email
             * v2.2.1 and older
             */
            $email = $emails;
            $memberTypeString = $_POST['memberType'];

            $memberType = TEAM_MEMBERSHIP_TYPE_MEMBER;
            if ($memberTypeString == "admin")
                $memberType = TEAM_MEMBERSHIP_TYPE_ADMIN;

            $invitationInfo = TDOTeamAccount::inviteTeamMember($userID, $teamid, $email, $memberType);
            if (!$invitationInfo) {
                error_log("HandleTeamMethods::inviteTeamMember could not complete the invitation (userid: $userID, teamid: $teamid, email: $email)");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Could not update the team info. Please try again later.'),
                ));
                return;
            }
            $result = array(
                "success" => true,
                "invitationid" => $invitationInfo['invitationid'],
                "email" => $invitationInfo['email']
            );

            // TODO: Log an administrative log
        }

		echo json_encode($result);
		return;
	}
	else if ($method == "convertAccountToGiftCode")
	{
		// Parameters
		//	invitationID	(required)
		if (!isset($_POST['invitationID']))
		{
			error_log("HandleTeamMethods::convertAccountToGiftCode called with a missing required parameter: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('invitationID parameter is missing'),
            ));
			return;
		}
		$invitationID = $_POST['invitationID'];
		
		// Check to see if the specified invitation is a valid invitation.
		if (!TDOTeamAccount::isValidTeamInvitation($invitationID))
		{
			error_log("HandleTeamMethods::convertAccountToGiftCode this does not appear to be a valid invitationID: $invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Invitation is no longer valid.'),
            ));
			return;
		}
		
		$teamID = TDOTeamAccount::teamIDForInvitationID($invitationID);
		if (!$teamID)
		{
			error_log("HandleTeamMethods::convertAccountToGiftCode could not get a team ID from the invitationID: $invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to determine the team for the invitation.'),
            ));
			return;
		}
		
		$subscription = TDOSubscription::getSubscriptionForUserID($userID);
		if (!$subscription)
		{
			error_log("HandleTeamMethods::convertAccountToGiftCode could not find a valid subscription for user: $userID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Account subscription is not valid'),
            ));
			return;
		}
		
		// Make sure the account is not currently in a paid subscription from
		// IAP or Google Play.
		if (TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userID))
		{
			error_log("HandleTeamMethods::convertAccountToGiftCode cannot convert user ($userID) subscription to gift code because it has a non-canceled autorenewing account via IAP or GooglePlay.");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('This account is not yet eligible to join a Todo Cloud team because it is paid for with an auto-renewing In-App Purchase via the App Store or Google Play.'),
            ));
			return;
		}
		
		// The gift code durations are done in # of months, so first figure out
		// how many months the existing subscription is valid for.
		
		$currentExpirationTimestamp = $subscription->getExpirationDate();
		$now = time();
		
		if ($now > $currentExpirationTimestamp)
		{
			error_log("HandleTeamMethods::convertAccountToGiftCode was called on an account that is already expired (nothing to do, userid: $userID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Account subscription has already expired'),
            ));
			return;
		}
		
		$timeLeftInSeconds = $currentExpirationTimestamp - $now;
		// Divide by 30 days and be generous in rounding up ... hopefully they
		// will give it to someone who wouldn't otherwise have used Todo Cloud
		// and that person will become a life-long customer. :)
		$monthsLeft = ceil($timeLeftInSeconds / 2592000);
		
//		error_log("CONVERTED $timeLeftInSeconds to $monthsLeft months");
		
		$senderName = TDOUser::displayNameForUserId($userID);
		
		$giftCode = new TDOGiftCode();
		$giftCode->setSubscriptionDuration($monthsLeft);
		$giftCode->setStripeGiftPaymentId("CONVERSION_TO_TEAM_ACCOUNT");
		$giftCode->setPurchaserUserId($userID);
		$giftCode->setPurchaseTimestamp($now);
		$giftCode->setSenderName($senderName);
		
		if ($giftCode->addGiftCode() == false)
		{
			// Uh oh! Something bad happened and we weren't able to create a
			// gift code. Don't go forward!
			error_log("HandleTeamMethods::convertAccountToGiftCode was unable to create a gift code for invitationID: $invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to create a gift code.'),
            ));
			return;
		}
		
		// Log that the user converted their remaining subscription to a gift
		// code in preparation to joining a team.
		$note = "Converted subscription to gift code ($monthsLeft month(s)) prior to joining a team subscription.";
		if (!TDOUser::logUserAccountAction($userID, $userID, USER_ACCOUNT_LOG_TYPE_CONVERT_SUBSCRIPTION_TO_GIFT_CODE, $note))
		{
			error_log("HandleTeamMethods::convertAccountToGiftCode unable to log to the user account ($userID) that they just converted their remaining subscription to a gift code.");
		}
		
		// Read the gift code and send it to the user in the user
		$giftCodeLink = TDOGiftCode::giftCodeLinkForCode($giftCode->giftCode());
		if (!$giftCodeLink)
		{
			error_log("HandleTeamMethods::convertAccountToGiftCode was unable to get a gift code link for the gift code: " . $giftCode->giftCode());
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to get a gift code link.'),
            ));
			return;
		}
		
		$email = TDOUser::usernameForUserId($userID);
		
		if (!TDOMailer::sendGiftCodeToTeamUser($senderName, $email, $giftCodeLink, $monthsLeft))
		{
			error_log("HandleTeamMethods::convertAccountToGiftCode was unable to send the new gift code ($$giftCodeLink) to the transitioning user for invitationID ($invitationID)");
		}
				
		// Change the user's subscription to expire today (now) in case the next
		// step does not work.
		if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscription->getSubscriptionID(), $now, SUBSCRIPTION_TYPE_UNKNOWN, SUBSCRIPTION_LEVEL_EXPIRED))
		{
			error_log("HandleTeamMethods::convertAccountToGiftCode was unable to reset the user's subscription to expire today for invitationID: $invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to adjust existing subscription expiration date.'),
            ));
			return;
		}
		
		$result = array(
						"success" => true,
						"giftcodelink" => $giftCodeLink
						);
		echo json_encode($result);
		return;
	}
	else if ($method == "acceptTeamInvitation")
	{
		// Parameters
		//	invitationID	(required)
		//	membershipType	(required, one of "member" or "admin")
		//  acceptType      (optional, one of "donateToTeam" or "convertToPromoCode")
		if (!isset($_POST['invitationID']))
		{
			error_log("HandleTeamMethods::acceptTeamInvitation called with a missing required parameter: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('invitationID parameter is missing'),
            ));
			return;
		}
		$invitationID = $_POST['invitationID'];
		if (!isset($_POST['membershipType']))
		{
			error_log("HandleTeamMethods::acceptTeamInvitation called with a missing required parameter: membershipType");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('membershipType parameter is missing'),
            ));
			return;
		}
		$membershipTypeString = $_POST['membershipType'];
		$membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER;
		if ($membershipTypeString == "admin")
			$membershipType = TEAM_MEMBERSHIP_TYPE_ADMIN;
		
		// Check to see if the specified invitation is a valid invitation.
		if (!TDOTeamAccount::isValidTeamInvitation($invitationID))
		{
			error_log("HandleTeamMethods::acceptTeamInvitation this does not appear to be a valid invitationID: $invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Invitation is no longer valid.'),
            ));
			return;
		}
		
		$teamID = TDOTeamAccount::teamIDForInvitationID($invitationID);
		if (!$teamID)
		{
			error_log("HandleTeamMethods::acceptTeamInvitation could not get a team ID from the invitationID: $invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to determine the team for the invitation.'),
            ));
			return;
		}
		
		// If this is a team-owned list, make sure that the team subscription is NOT
		// expired.
		$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
		if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
		{
			error_log("Method acceptTeamInvitation found that team subscription is expired for team: " . $teamID);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Expired team accounts cannot be joined.'),
            ));
			return;
		}
		
		$sendIAPInstructionEmail = false;
		
		if ($membershipType == TEAM_MEMBERSHIP_TYPE_MEMBER)
		{
			$subscription = TDOSubscription::getSubscriptionForUserID($userID);
			if (!$subscription)
			{
				error_log("HandleTeamMethods::acceptTeamInvitation could not find a valid subscription for user: $userID");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Account subscription is not valid.'),
                ));
				return;
			}
			
//			// Make sure that we're not going to stomp over a subscription that has
//			// longer than 14 days left in their subscription.
//			$forteenDaysFromNow = time() + 1209600;
//			if ($subscription->getExpirationDate() > $forteenDaysFromNow)
//			{
//				error_log("HandleTeamMethods::acceptTeamInvitation cannot allow user ($userID) to join team account (invitation: $invitationID) because the user subscription still has more than 14 days left in their subscription.");
//				echo '{"success":false, "error":"Cannot join team because more than 14 days remain in the current premium subscription. Please try again later."}';
//				return;
//			}
		}
		
		// Check for the "acceptType" parameter. If the user is a Stripe
		// customer and they had remaining time on their personal subscription,
		// they have been prompted to either donate their remaining time or
		// receive a promo code for their remaining time.
		// TDOTeamAccount::acceptTeamInvitation() handles the implementation,
		// but we've got to pass the user's choice along here.
		$donateSubscriptionToTeam = false;
		if (isset($_POST['acceptType']) && $_POST['acceptType'] == "donateToTeam")
		{
			$donateSubscriptionToTeam = true;
		}
		
		$teamAccount = TDOTeamAccount::acceptTeamInvitation($invitationID, $userID, $donateSubscriptionToTeam);
		
		if (!$teamAccount)
		{
			error_log("HandleTeamMethods::acceptTeamInvitation had an error calling TDOTeamAccount::acceptTeamInvitation(invitationID: $invitationID, userID: $userID).");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Error joining the team account. Please try again later.'),
            ));
			return;
		}
		
		// Log that the user joined a team
		$teamName = TDOTeamAccount::teamNameForTeamID($teamID);
		$note = "Joined a team ($teamID): $teamName";
		if (!TDOUser::logUserAccountAction($userID, $userID, USER_ACCOUNT_LOG_TYPE_JOIN_TEAM, $note))
		{
			error_log("HandleTeamMethods::acceptTeamInvitation unable to log to the user account ($userID) that they just joined a team ($teamID).");
		}
		
//		$expirationDate = $teamAccount->getExpirationDate();
//		$expirationDateString = date('d M Y', $expirationDate);
		
//		$result = array(
//						"success" => true,
//						"teamid" => $teamID,
//						"teamName" => $teamAccount->getTeamName(),
//						"expirationDate" => $expirationDate,
//						"expirationDateString" => $expirationDateString
//						);
		$result = array(
						"success" => true,
						"teamid" => $teamID,
						"teamName" => $teamAccount->getTeamName()
						);
		
		echo json_encode($result);
		return;
	}
	else if ($method == "deleteTeamInvitation")
	{
		// Parameters
		//	invitationID	(required)
		if (!isset($_POST['invitationID']))
		{
			error_log("HandleTeamMethods::deleteTeamInvitation called with a missing required parameter: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('invitationID parameter is missing'),
            ));
			return;
		}
		$invitationID = $_POST['invitationID'];
		
		// Ensure that the current user is a team administrator before allowing
		// this method to be called.
		$teamID = TDOTeamAccount::teamIDForInvitationID($invitationID);
		if (!$teamID)
		{
			error_log("HandleTeamMethods::deleteTeamInvitation could not locate the corresponding teamID for the invitationID: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Invalid team invitation.'),
            ));
			return;
		}
		
		if (!TDOTeamAccount::isAdminForTeam($userID, $teamID))
		{
			error_log("HandleTeamMethods::deleteTeamInvitation must be called by a user ($userID) that is an administer of the team ($teamID) to delete an outstanding invitation: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Insufficient rights to delete a team invitation.'),
            ));
			return;
		}
		
		// If this is a team-owned list, make sure that the team subscription is NOT
		// expired.
		$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
		if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED || $teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD)
		{
			error_log("Method deleteTeamInvitation found that team subscription is expired for team: " . $teamID);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Cannot delete invitations from an expired team account.'),
            ));
			return;
		}
		
		if (!TDOTeamAccount::deleteTeamInvitation($invitationID))
		{
			error_log("HandleTeamMethods::deleteTeamInvitation could not delete a team invitation: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Error deleting a team invitation.'),
            ));
			return;
		}
		
		$result = array("success" => true);
		echo json_encode($result);
		return;
	}
	else if ($method == "resendTeamInvitation")
	{
		// Parameters
		//	invitationID	(required)
		//	membershipType	(required, one of "member" or "admin")
		if (!isset($_POST['invitationID']))
		{
			error_log("HandleTeamMethods::resendTeamInvitation called with a missing required parameter: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('invitationID parameter is missing'),
            ));
			return;
		}
		$invitationID = $_POST['invitationID'];
		if (!isset($_POST['membershipType']))
		{
			error_log("HandleTeamMethods::resendTeamInvitation called with a missing required parameter: membershipType");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('membershipType parameter is missing'),
            ));
			return;
		}
		$membershipTypeString = $_POST['membershipType'];
		$membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER;
		if ($membershipTypeString == "admin")
			$membershipType = TEAM_MEMBERSHIP_TYPE_ADMIN;
		
		// Ensure that the current user is a team administrator before allowing
		// this method to be called.
		$teamID = TDOTeamAccount::teamIDForInvitationID($invitationID);
		if (!$teamID)
		{
			error_log("HandleTeamMethods::resendTeamInvitation could not locate the corresponding teamID for the invitationID: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Invalid team invitation.'),
            ));
			return;
		}
		
		if (!TDOTeamAccount::isAdminForTeam($userID, $teamID))
		{
			error_log("HandleTeamMethods::resendTeamInvitation must be called by a user ($userID) that is an administer of the team ($teamID) to resend an outstanding invitation: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Insufficient rights to resend a team invitation.'),
            ));
			return;
		}
		
		// If this is a team-owned list, make sure that the team subscription is NOT
		// expired.
		$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
		if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
		{
			error_log("Method resendTeamInvitation found that team subscription is expired for team: " . $teamID);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Cannot resend invitations to an expired team account.'),
            ));
			return;
		}
		
		$email = TDOTeamAccount::emailForTeamInvitationID($invitationID);
		if (!$email)
		{
			error_log("HandleTeamMethods::resendTeamInvitation could not determine the email address for the invitation: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not determine the email address to send the invitation.'),
            ));
			return;
		}
		
		if (!TDOTeamAccount::sendTeamInvitation($userID, $invitationID, $email, $membershipType))
		{
			error_log("HandleTeamMethods::resendTeamInvitation could not send a team invitation: invitationID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Error sending a team invitation.'),
            ));
			return;
		}
		
		$result = array("success" => true);
		echo json_encode($result);
		return;
	}
	else if ($method == "removeTeamMember")
	{
		// Parameters
		//	teamID			(required)
		//	userID			(required)
		//	membershipType	(required, one of "member" or "admin")
		if (!isset($_POST['teamID']))
		{
			error_log("HandleTeamMethods::removeTeamMember called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamID parameter is missing'),
            ));
			return;
		}
		$teamID = $_POST['teamID'];
		if (!isset($_POST['userID']))
		{
			error_log("HandleTeamMethods::removeTeamMember called with a missing required parameter: userID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('userID parameter is missing'),
            ));
			return;
		}
		$userIDToRemove = $_POST['userID'];
		if (!isset($_POST['membershipType']))
		{
			error_log("HandleTeamMethods::removeTeamMember called with a missing required parameter: membershipType");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('membershipType parameter is missing'),
            ));
			return;
		}
		$membershipTypeString = $_POST['membershipType'];
		$membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER;
        if ($membershipTypeString == "admin" || $membershipTypeString == TEAM_MEMBERSHIP_TYPE_ADMIN)
			$membershipType = TEAM_MEMBERSHIP_TYPE_ADMIN;
		
		// Do special checks if the user to be removed is the session user
		if ($userID == $userIDToRemove)
		{
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN)
			{
				// Make sure that there is at least ONE other team administrator
				// (we cannot let a team have ZERO administrators).
				$adminsCount = TDOTeamAccount::getCurrentTeamMemberCount($teamID, TEAM_MEMBERSHIP_TYPE_ADMIN);
				if ($adminsCount <= 1)
				{
					error_log("HandleTeamMethods::removeTeamMember cannot remove the last remaining administrator ($userID) of a team ($teamID).");
                    echo json_encode(array(
                        'success' => FALSE,
                        'error' => _('A team cannot have zero administrators.'),
                    ));
					return;
				}
			}
		}
		else
		{
			// If the session user is NOT the user to be removed, the session
			// user MUST be a team administrator.
			if (!TDOTeamAccount::isAdminForTeam($userID, $teamID))
			{
				error_log("HandleTeamMethods::removeTeamMember called by normal user ($userID) that is not an administrator of the team ($teamID). Tried to remove user ($userIDToRemove).");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('You do not have permission to remove this user from the team.'),
                ));
				return;
			}
		}

// Allow team members to be removed on an expired team so that individual users
// can pay for their own account if desired.
//		// If this is a team-owned list, make sure that the team subscription is NOT
//		// expired.
//		$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
//		if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
//		{
//			error_log("Method HandleTeamMethods::removeTeamMember found that team subscription is expired for team: " . $teamID);
//			echo '{"success":false, "error":"Cannot remove a team member from an expired team account."}';
//			return;
//		}
		
		$teamID = TDOTeamAccount::removeTeamMember($teamID, $userIDToRemove, $membershipType);
		if (!$teamID)
		{
			error_log("HandleTeamMethods::removeTeamMember could not remove a team member (session userID: $userID, userToRemove: $userIDToRemove, teamID: $teamID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not remove the specified member.'),
            ));
			return;
		}
		
		// Log something to the user's account
		$teamName = TDOTeamAccount::teamNameForTeamID($teamID);
		$note = "Removed from the team ($teamID): $teamName";
		if (!TDOUser::logUserAccountAction($userIDToRemove, $userID, USER_ACCOUNT_LOG_TYPE_LEAVE_TEAM, $note))
		{
			// Non-fatal error
			error_log("HandleTeamMethods::removeTeamMember unable to log to the user account ($userIDToRemove) that they were removed from a team ($teamID).");
		}

		$result = array(
						"success" => true,
						"teamid" => $teamID
						);
        $_SESSION['info_messages'] = array(
            'message' => 'Team member successfully removed.',
            'type' => 'success'
        );
		echo json_encode($result);
		return;
	}
    else if ($method == "addMyselfToTheTeam") {
        if (!isset($_POST['teamID'])) {
            error_log("HandleTeamMethods::addMyselfToTheTeam called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamID parameter is missing'),
            ));
            return;
        }
        $teamID = trim($_POST['teamID']);
        if (!isset($_POST['membershipType']) || $_POST['membershipType'] === '') {
            $membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER;
        } else {
            $membershipType = $_POST['membershipType'];
        }
        if($userID && $teamID) {

            if (!TDOTeamAccount::addUserToTeam($userID, $teamID, $membershipType)) {
                error_log("TDOTeamAccount::acceptTeamInvitation() couldn't add a user ($userID) to the team ($teamID), from the invitationID ($invitationID).");
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Unable to add user to the team'),
                ));
                return false;
            }
        }
        $result = array(
            "success" => true,
        );
        $_SESSION['info_messages'] = array(
            'message' => 'You joined as a member.',
            'type' => 'success'
        );
        echo json_encode($result);
        return;
    }
	else if ($method == "getTeamChangePricingInfo")
	{
		// Parameters
		//	teamID		(required)
		//	billingFrequency
		//	numOfTeamMembers
		
		// This method basically matches the logic that the javascript method
		// updateChangeDisplayInfo() in TeamFunctions.js does, except since it
		// comes from the server, it's authoritative. It's called once to show
		// the user a summary and then again during the actual purchase process
		// to get the exact amount to actually charge a user.
		
		// Returns:
		//	newExpirationDate
		//	newNumOfMembers
		//	bulkDiscount
		//	discountPercentage
		//	currentAccountCredit
		//	totalCharge
		
		if (!isset($_POST['teamID']))
		{
			error_log("HandleTeamMethods::getTeamChangePricingInfo called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamID parameter is missing'),
            ));
			return;
		}
		$teamID = $_POST['teamID'];
		if (!isset($_POST['billingFrequency']))
		{
			error_log("HandleTeamMethods::getTeamChangePricingInfo called with a missing required parameter: billingFrequency");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('billingFrequency parameter is missing'),
            ));
			return;
		}
		$billingFrequency = $_POST['billingFrequency'];
		if (!isset($_POST['numOfTeamMembers']))
		{
			error_log("HandleTeamMethods::getTeamChangePricingInfo called with a missing required parameter: numOfTeamMembers");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('numOfTeamMembers parameter is missing'),
            ));
			return;
		}
		$numOfTeamMembers = $_POST['numOfTeamMembers'];
		
		$changeInfo = TDOTeamAccount::getTeamChangePricingInfo($billingFrequency, $numOfTeamMembers, $teamID);
		if (!$changeInfo)
		{
			error_log("HandleTeamMethods::getTeamChangePricingInfo error calling TDOTeamAccount::getTeamChangePricingInfo()");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Error determining team account change information. Please try again later.'),
            ));
			return;
		}
		
		$result = array(
						"success" => true,
						"changeInfo" => $changeInfo
						);
		
		echo json_encode($result);
		return;
		
	}
	else if ($method == "changeTeamAccount")
	{
		// PARAMETERS:
		//	teamID				required
		//	numOfMembers		required
		//	billingFrequency	required (1 - monthly, or 2 - yearly)
		//	stripeToken			optional
		
		if (!isset($_POST['teamID']))
		{
			error_log("HandleTeamMethods::changeTeamAccount called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamID parameter is missing'),
            ));
			return;
		}
		$teamID = $_POST['teamID'];
//error_log("===============================changeTeamAccount teamID: $teamID");
		// If this is a team-owned list, make sure that the team subscription is NOT
		// expired.
		$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
		if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD)
		{
			error_log("Method HandleTeamMethods::changeTeamAccount found that team subscription is in grace period for team: " . $teamID);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Cannot change a team account while it in grace period. Try again later.'),
            ));
			return;
		}
		
		
		if (!isset($_POST['numOfMembers']))
		{
			error_log("HandleTeamMethods::changeTeamAccount called with a missing required parameter: numOfMembers");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('numOfMembers parameter is missing'),
            ));
			return;
		}
		$numOfMembers = $_POST['numOfMembers'];
		
		if (!isset($_POST['billingFrequency']))
		{
			error_log("HandleTeamMethods::changeTeamAccount called with a missing required parameter: billingFrequency");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('billingFrequency parameter is missing'),
            ));
			return;
		}
		$billingFrequency = $_POST['billingFrequency'];
		
		
		$stripeToken = NULL;
		if (isset($_POST['stripeToken']))
			$stripeToken = $_POST['stripeToken'];
		
		$result = TDOTeamAccount::changeTeamAccount($userID, $teamID, $numOfMembers, $billingFrequency, $stripeToken);
		if (!$result)
		{
			error_log("HandleTeamMethods::changeTeamAccount could not update the team (userid: $userID, teamID: $teamID, numOfMembers: $numOfMembers, billingFrequency: $billingFrequency)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not update the team account. Please try again later.'),
            ));
			return;
        } elseif (!isset($result['teamid']) || (isset($result['error']) && $result['error'] !== '')) {
			error_log("HandleTeamMethods::changeTeamAccount could not update the team (userid: $userID, teamID: $teamID, numOfMembers: $numOfMembers, billingFrequency: $billingFrequency): " . $result['error']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => $result['error'],
            ));
			return;
		}
		
		$result = array(
						"success" => true,
						"teamid" => $result['teamid']
						);
		
		// TODO: Log an administrative log
		
		echo json_encode($result);
		return;
	}
	else if ($method == "getTeamPurchaseHistory")
	{
		// PARAMETERS:
		//	teamID			required
		if (!isset($_POST['teamID']))
		{
			error_log("HandleTeamMethods::getTeamPurchaseHistory called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamID parameter is missing'),
            ));
			return;
		}
		$teamID = $_POST['teamID'];
		
		// Check to make sure the session user is a team admin
		if (!TDOTeamAccount::isAdminForTeam($userID, $teamID))
		{
			error_log("HandleTeamMethods::getTeamPurchaseHistory called by a user that is not a team administrator.");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Not authorized'),
            ));
			return;
		}
		
		
	}
	else if ($method == "leaveTeam")
	{
		// Parameters
		//	teamID			(required)
		if (!isset($_POST['teamID']))
		{
			error_log("HandleTeamMethods::leaveTeam called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamID parameter is missing'),
            ));
			return;
		}
		$teamID = $_POST['teamID'];
		
		// NOTE: This does not remove a team admin, so it's safe to remove an
		// admins general membership from the team here.
		$teamID = TDOTeamAccount::removeTeamMember($teamID, $userID, TEAM_MEMBERSHIP_TYPE_MEMBER);
		if (!$teamID)
		{
			error_log("HandleTeamMethods::leaveTeam could not leave the team (session userID: $userID, teamID: $teamID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not leave the team.'),
            ));
			return;
		}
		
		// Log something to the user's account
		$teamName = TDOTeamAccount::teamNameForTeamID($teamID);
		$note = "User left the team ($teamID): $teamName";
		if (!TDOUser::logUserAccountAction($userID, $userID, USER_ACCOUNT_LOG_TYPE_LEAVE_TEAM, $note))
		{
			// Non-fatal error
			error_log("HandleTeamMethods::leaveTeam unable to log to the user account ($userID) that they left the team ($teamID).");
		}
		
		$result = array(
						"success" => true,
						"teamid" => $teamID
						);
		echo json_encode($result);
		return;
	}
	else if ($method == "cancelTeamRenewal")
	{
		// Parameters
		//	teamID			(required)
		if (!isset($_POST['teamID']))
		{
			error_log("HandleTeamMethods::cancelTeamRenewal called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamID parameter is missing'),
            ));
			return;
		}
		$teamID = $_POST['teamID'];
		
		// This cannot be called by a non-team admin
		if (!TDOTeamAccount::isAdminForTeam($userID, $teamID))
		{
			error_log("HandleTeamMethods::cancelTeamRenewal must be called by a user ($userID) that is an administer of the team ($teamID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Insufficient rights to cancel team renewal.'),
            ));
			return;
		}
		
		//
		// In order to cancel the renewal, all we need to do is remove the
		// billing user from the team record. The subscription renewal daemon
		// will not be able to tie any credit card to the team.
		if (!TDOTeamAccount::removeBillingUserFromTeam($teamID))
		{
			error_log("HandleTeamMethods::cancelTeamRenewal unable to remove billing user from team: $teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not remove billing user.'),
            ));
			return false;
		}
		
		$result = array(
						"success" => true,
						"teamid" => $teamID
						);
		echo json_encode($result);
		return;
	}
	else if ($method == "updateTeamBillingInfo")
	{
		// Parameters
		//	teamID
		//	stripeToken
		
		if (!isset($_POST['teamID']))
		{
			error_log("HandleTeamMethods::updateTeamBillingInfo called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not remove billing user.'),
            ));
			return;
		}
		$teamID = $_POST['teamID'];
		
		if (!isset($_POST['stripeToken']))
		{
			error_log("HandleTeamMethods::updateTeamBillingInfo called with a missing required parameter: stripeToken");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('stripeToken parameter is missing'),
            ));
			return;
		}
		$stripeToken = $_POST['stripeToken'];
		
		
		if (!TDOTeamAccount::updateTeamBillingInfo($userID, $teamID, $stripeToken))
		{
			error_log("HandleTeamMethods::updateTeamBillingInfo could not update the team (userid: $userID, teamID: $teamID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not update the team account billing info. Please try again later.'),
            ));
			return;
		}
		
		$result = array("success" => true);
		echo json_encode($result);
		return;
	}
	else if ($method == "deleteTeamAccount")
	{
		// Parameters
		//	teamID
		
		if (!isset($_POST['teamID']))
		{
			error_log("HandleTeamMethods::deleteTeamAccount called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamID parameter is missing'),
            ));
			return;
		}
		$teamID = $_POST['teamID'];
		
		// This cannot be called by a non-team admin
		if (!TDOTeamAccount::isAdminForTeam($userID, $teamID))
		{
			error_log("HandleTeamMethods::deleteTeamAccount must be called by a user ($userID) that is an administer of the team ($teamID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You are not an administrator of this team.'),
            ));
			return;
		}
		
		$result = TDOTeamAccount::deleteTeamAccount($teamID);
		if (!$result['success'])
		{
			error_log("HandleTeamMethods::deleteTeamAccount could not delete the team (teamID: $teamID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => $result['error'],
            ));
			return;
		}
		
		$result = array("success" => true);
		echo json_encode($result);
		return;
	}
    elseif ($method == "createSharedList") {
        $list_name = $_POST['listName'];
        $team_id = $_POST['teamId'];
		
		// If this is a team-owned list, make sure that the team subscription is NOT
		// expired.
		$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($team_id);
		if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
		{
			error_log("Method HandleTeamMethods::createSharedList found that team subscription is expired for team: " . $team_id);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Cannot create a shared list in an expired team account.'),
            ));
			return;
		}
		
        $list = new TDOList();

        if (isset($list_name) && $list_name !== '')
            $list->setName($list_name);
        else
            $list->setName(_('Shared List'));

        $list->setCreator($team_id);
        if (!$list->addList($userID, $team_id)) {
            error_log("TDOList::addList() could not create a shared list for a team.");
            $result = array(
                'success' => FALSE,
            );
        } else {
            $listID = $list->listId();
            if (!TDOList::shareWithUser($listID, $userID, LIST_MEMBERSHIP_OWNER)) {
                error_log("TDOTeamAccount::createTeamAccountWithTrial() unable to add the list admin as an owner of the newly created shared list.");
            }
            $result = array(
                'success' => TRUE,
                'list_id' => $listID
            );
        }
        echo json_encode($result);
        die;
    } elseif ($method == 'addMembersToSharedList') {
        $members = array();
        $result = array();
        $list_id = '';
        if ($_POST['listid'] && trim($_POST['listid']) !== '') {
            $list_id = trim($_POST['listid']);
        }
		
		$teamID = TDOList::teamIDForList($list_id);
		// If this is a team-owned list, make sure that the team subscription is NOT
		// expired.
		$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
		if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
		{
			error_log("Method HandleTeamMethods::addMembersToSharedList found that team subscription is expired for team: " . $teamID);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Cannot add members to a shared list belonging to an expired team account.'),
            ));
			return;
		}
		
        if ($_POST['add_members']) {
            if (is_array($_POST['add_members']) && sizeof($_POST['add_members'])) {
                $members = $_POST['add_members'];
            } elseif ($_POST['add_members'] !== '' && trim($_POST['add_members']) !== '') {
                $members[] = trim($_POST['add_members']);
            } else {
                $result = array(
                    'success' => FALSE,
                    'error' => 'Unable to add users to this list',
                );
            }
        }
        if(sizeof($members)){
            foreach($members as $member){
                if (!TDOList::shareWithUser($list_id, $member, LIST_MEMBERSHIP_MEMBER)) {
                    error_log("addMembersToSharedList unable to add user to shared list.");
                }
            }
            setcookie('hide_coach', 'ready', strtotime('+1 year'), '/');
            $result = array(
                'success' => TRUE,
            );
        }

        echo json_encode($result);
        die;
    } elseif ($method == 'removeMemberFromSharedList') {
        $list_id = '';
        $user_id = '';
        $result = array();
        if ($_POST['listid'] && trim($_POST['listid']) !== '') {
            $list_id = trim($_POST['listid']);
        }
        if ($_POST['userid'] && trim($_POST['userid']) !== '') {
            $user_id = trim($_POST['userid']);
        }
        if (!$list_id || !$user_id) {
            $result = array(
                'success' => FALSE,
                'error' => 'Unable to remove user from list',
            );
        } else {
            $can_leave_list = TRUE;
			
			$teamID = TDOList::teamIDForList($list_id);
			// If this is a team-owned list, make sure that the team subscription is NOT
			// expired.
			$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
			if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
			{
				error_log("Method HandleTeamMethods::removeMembersFromSharedList found that team subscription is expired for team: " . $teamID);
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Cannot remove members from a shared list belonging to an expired team account.'),
                ));
				return;
			}
			
            if (!TDOList::removeUserFromList($list_id, $user_id)) {
                $result = array(
                    'success' => FALSE,
                    'error' => 'Error while removing usr from list',
                );
            } else {
                $result = array(
                    'success' => TRUE,
                );
            }
        }
        echo json_encode($result);
        die;
    } elseif ($method == 'changeMemberRole') {
        $list_id = '';
        $user_id = '';
        $role_id = '';
        if ($_POST['listid'] && trim($_POST['listid']) !== '') {
            $list_id = trim($_POST['listid']);
        }
        if ($_POST['userid'] && trim($_POST['userid']) !== '') {
            $user_id = trim($_POST['userid']);
        }
        if ($_POST['roleid'] && trim($_POST['roleid']) !== '') {
            $role_id = trim($_POST['roleid']);
        }
        if (!$list_id || !$user_id || !$role_id) {
            $result = array(
                'success' => FALSE,
                'error' => 'Unable to change member role',
            );
        } else {
			
			$teamID = TDOList::teamIDForList($list_id);
			// If this is a team-owned list, make sure that the team subscription is NOT
			// expired.
			$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
			if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
			{
				error_log("Method HandleTeamMethods::changeMemberRole found that team subscription is expired for team: " . $teamID);
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Cannot change members in a shared list belonging to an expired team account.'),
                ));
				return;
			}

            $can_leave_list = TRUE;
            if (!TDOList::changeUserRole($list_id, $user_id, $role_id)) {
                $result = array(
                    'success' => FALSE,
                    'error' => _('Unable to change member role'),
                );
            } else {
                $result = array(
                    'success' => TRUE,
                );
            }
        }
        echo json_encode($result);
        die;
    }
	else if ($method == "getActiveTeamCredits")
	{
		// Parameters
		//	teamID
		//	stripeToken
		
		if (!isset($_POST['teamID']))
		{
			error_log("HandleTeamMethods::getActiveTeamCredits called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamID parameter is missing'),
            ));
			return;
		}
		$teamID = $_POST['teamID'];
		
		
		// This cannot be called by a non-team admin
		if (!TDOTeamAccount::isAdminForTeam($userID, $teamID))
		{
			error_log("HandleTeamMethods::getActiveTeamCredits must be called by a user ($userID) that is an administer of the team ($teamID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You are not an administrator of this team.'),
            ));
			return;
		}
		
		$activeCredits = TDOTeamAccount::activeCreditsForTeam($teamID);
		if (!$activeCredits)
		{
			error_log("HandleTeamMethods::getActiveTeamCredits error calling TDOTeamAccount::activeCreditsForTeam()");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Error getting active team credits information. Please try again later.'),
            ));
			return;
		}
		
		// Go through and count how many months are actually available. At the
		// same time, get the display names of the donors so it'll make it
		// easier for the Admin to know who donated.
		$totalNumOfMonths = 0;
		foreach ($activeCredits as $key => $credit)
		{
			$totalNumOfMonths = $totalNumOfMonths + $credit['numOfMonths'];
			$activeCredits[$key]['donorName'] = TDOUser::displayNameForUserId($credit['userid']);
		}
		
		$result = array(
						"success" => true,
						"credits" => $activeCredits,
						"totalNumOfMonthCredits" => $totalNumOfMonths
						);
		
		echo json_encode($result);
		return;
	}
	else if ($method == "updateSlackConfig")
	{
        /**
         * string teamID
         * array listID
         * array webhookUrl
         * array channelName
         */

        if (!isset($_POST['teamID'])) {
            error_log("HandleTeamMethods::updateSlackConfig called with a missing required parameter: teamID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('teamID parameter is missing'),
            ));
            return;
        }
        $teamID = $_POST['teamID'];

        // This cannot be called by a non-team admin
        if (!TDOTeamAccount::isAdminForTeam($userID, $teamID)) {
            error_log("HandleTeamMethods::updateSlackConfig must be called by a user ($userID) that is an administer of the team ($teamID)");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You are not an administrator of this team.'),
            ));
            return;
        }

        if (!isset($_POST['listID'])) {
            error_log("HandleTeamMethods::updateSlackConfig called with a missing required parameter: listID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('listID parameter is missing'),
            ));
            return;
        }
		$listID = $_POST['listID'];
		$webhookUrl = $_POST['webhookUrl'];
		$channelName = $_POST['channelName'];
        $outToken = $_POST['token'];

        if (sizeof($listID)) {
            foreach ($listID as $k => $lid) {
                TDOTeamSlackIntegration::setWebhookURLForChannelName($teamID, $lid, $channelName[$k], $webhookUrl[$k], $outToken[$k]);
            }
        }

        echo json_encode(array('success' => TRUE, 'error' => ''));
		return;
	}
    else if ($method == "slackListener")
    {
        TDOTeamSlackIntegration::createTaskFromSlack();
    }

?>

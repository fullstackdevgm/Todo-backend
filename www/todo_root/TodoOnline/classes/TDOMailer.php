<?php
    
    include_once('AWS/sdk.class.php');
	include_once('TodoOnline/base_sdk.php');
	require_once('mandrill-api-php/src/Mandrill.php');
	define('MANDRILL_API_KEY', '702V--AmZNTrGMNsJLZlXw');

	
/** 
 *  PHP Version 5
 *
 *  @category   Site Login Objects for PHP and Amazon Web Services SimpleDB
 *  @package	SLO
 *  @copyright	Copyright (c) 2008 Bruce E. Wampler
 *  @license	http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 *  @version	2008-06-05
 */
/**
 * slo_mailer.php
 *
 * The Mailer class is meant to simplify the task of sending
 * emails to users. Note: this email system will not work
 * if your server is not setup to send mail.
 *
 */
	
define ('COPYRIGHT_STRING', 'Copyright © '.date('Y').' Appigo, Inc. All rights reserved.');
define ('APPIGO_MAILING_ADDRESS', '910 E 1100 S, Orem, UT  84097');
	
define ('BOUNCE_UNKNOWN', 0);
define ('BOUNCE_PERMANENT', 1);
define ('BOUNCE_TRANSIENT', 2);
define ('BOUNCE_COMPLAINT', 3);
define ('BOUNCE_UNDETERMINED', 4);
	
 
class TDOMailer
{

    public static function sendInvitation($fromUserName, $email, $invitationURL, $listName)
    {
		$mergeTags = array(
						   array('name' => 'FROM_USER_NAME',
								 'content' => $fromUserName),
						   array('name' => 'INVITATION_URL',
								 'content' => $invitationURL),
						   array('name' => 'SHARED_LIST_NAME',
								 'content' => $listName)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-shared-list-invitation',
													$email,
													null, // User Display Name
													$mergeTags);
    }
	
	public static function sendTeamInvitation($fromUserName, $email, $invitationURL, $teamName, $membershipType)
	{
		$mergeTags = array(
						   array('name' => 'FROM_USER_NAME',
								 'content' => $fromUserName),
						   array('name' => 'INVITATION_URL',
								 'content' => $invitationURL),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName)
						   );
		
		if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN) {
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-admin-invitation',
														$email,
														null, // User Display Name
														$mergeTags);
		} else {
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-subscription-invitation',
														$email,
														null, // User Display Name
														$mergeTags);
		}
	}
    
    public static function sendAppigoResetPasswordEmail($userDisplayName, $email, $resetURL, $isAdminRequestingReset = false)
    {
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $userDisplayName),
						   array('name' => 'RESET_URL',
								 'content' => $resetURL)
						   );
		
        if ($isAdminRequestingReset)
        {
			return TDOMailer::sendMandrillEmailTemplate('appigo-support-reset-password-by-administrator',
														$email,
														$userDisplayName,
														$mergeTags);
		}
        else
		{
			return TDOMailer::sendMandrillEmailTemplate('appigo-support-reset-password',
														$email,
														$userDisplayName,
														$mergeTags);
		}
    }
    
    public static function sendResetPasswordEmail($userDisplayName, $email, $resetURL, $isAdminRequestingReset = false)
    {
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $userDisplayName),
						   array('name' => 'RESET_URL',
								 'content' => $resetURL)
						   );
		
		if ($isAdminRequestingReset)
		{
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-reset-password-by-administrator',
														$email,
														$userDisplayName,
														$mergeTags);
		}
		else
		{
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-reset-password',
														$email,
														$userDisplayName,
														$mergeTags);
		}
    }
    
    public static function sendTodoProWelcomeEmail($userDisplayName, $email, $verifyEmailURL, $taskCreationEmail)
    {
//		$subject = "Welcome to Todo Cloud!";
		
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $userDisplayName),
						   array('name' => 'VERIFY_EMAIL_URL',
								 'content' => $verifyEmailURL),
						   array('name' => 'TASK_CREATION_EMAIL',
								 'content' => $taskCreationEmail . "@newtask.todo-cloud.com")
						   );
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-welcome-email',
													$email,
													$userDisplayName,
													$mergeTags);
		
//        $textBody = "Hello $userDisplayName,\n\n";
//        $htmlBody = "<p>Hello $userDisplayName,</p>\n";
//        
//        $textBody .= "Thank you for joining Todo Cloud.";
//        $htmlBody .= "<p>Thank you for joining Todo Cloud.</p>\n";
//
//        if(!empty($verifyEmailURL))
//        {
//            $textBody .= "Please complete your registration by clicking the link below to verify your email address.\n". $verifyEmailURL;
//            $htmlBody .= "<p>Please complete your registration by <a href=\"" . $verifyEmailURL . "\">clicking here</a> to verify your email address.</p>\n";
//        }
//
//        if(!empty($taskCreationEmail))
//        {
//            $textBody .= "\n\nYour unique Todo Cloud email address is:\n";
//            $textBody .= $taskCreationEmail."@newtask.todo-cloud.com";
//            $textBody .= "\nJust email something you need to get done to this address, and it will automatically be added to your to-do list!";
//            
//            $htmlBody .= "<p>Your unique Todo Cloud email address is ".$taskCreationEmail."@newtask.todo-cloud.com</p>\n";
//            $htmlBody .= "<p>Just email something you need to get done to this address, and it will automatically be added to your to-do list!</p>\n";
//        }
//        
//        $textBody .= "\n\nThank you from " . EMAIL_SITE_NAME ."\n";
//        $htmlBody .= "<p>Thank you from " . EMAIL_SITE_NAME ."</p>\n";
//        
//        return TDOMailer::sendHTMLAndTextEmail($email, $subject, EMAIL_FROM_NAME, EMAIL_FROM_ADDR, $htmlBody, $textBody);        
    }
    
    public static function sendTodoProMigrationEmail($userDisplayName, $email, $verifyEmailURL, $taskCreationEmail)
    {
        $subject = _('Welcome to Todo Cloud!');

        $textBody = _('Hello') . ' ' . $userDisplayName. ",\n\n";
        $htmlBody = '<p>' . _('Hello') . ' ' . $userDisplayName. ',</p>';


        $textBody .= _('Todo Online has been upgraded to Todo Cloud. What we&#39;ve added:') . "\n";
        $textBody .= ' · ' . _('The ability to share lists') . "\n";
        $textBody .= ' · ' . _('Speed and reliability improvements') . "\n";
        $textBody .= ' · ' . _('A new look and feel to the web interface') . "\n";

        $htmlBody .= '<p>' . _('Todo Online has been upgraded to Todo Cloud. What we&#39;ve added:') . '</p>';
        $htmlBody .= '<ul><li>' . _('The ability to share lists') . '</li>';
        $htmlBody .= '<li>' . _('Speed and reliability improvements') . '</li>';
        $htmlBody .= '<li>' . _('A new look and feel to the web interface') . '</li></ul>';
        

        if(!empty($verifyEmailURL))
        {
            $textBody .= _('Please complete your registration by clicking the link below to verify your email address.') . "\n" . $verifyEmailURL;
            $htmlBody .= '<p>' . sprintf(_('Please complete your registration by %sclicking here%s to verify your email address.'), '<a href="' . $verifyEmailURL . '">', '</a>') . '</p>';
        }
        
        if(!empty($taskCreationEmail))
        {
            $textBody .= "\n\n" . _('Your unique Todo Cloud email address is:') . "\n";
            $textBody .= $taskCreationEmail."@newtask.todo-cloud.com";
            $textBody .= "\n" . _('Just email something you need to get done to this address, and it will automatically be added to your to-do list!');

            $htmlBody .= '<p>' . sprintf(_('Your unique Todo Cloud email address is %s@newtask.todo-cloud.com'), $taskCreationEmail) . '</p>';
            $htmlBody .= '<p>' . _('Just email something you need to get done to this address, and it will automatically be added to your to-do list!') . '</p>';
        }

        $textBody .= "\n\n" . _('Thank you from') . EMAIL_SITE_NAME . "\n";
        $htmlBody .= '<p>' . _('Thank you from') . EMAIL_SITE_NAME . '</p>';
        
        return TDOMailer::sendHTMLAndTextEmail($email, $subject, EMAIL_FROM_NAME, EMAIL_FROM_ADDR, $htmlBody, $textBody);        
    }
    
    public static function sendEmailVerificationEmail($userDisplayName, $email, $verifyEmailURL)
    {
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $userDisplayName),
						   array('name' => 'VERIFY_EMAIL_URL',
								 'content' => $verifyEmailURL)
						   );
		
    	if (TDOUtil::isEmailAddressInWhiteList($email)) {
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-vip-email-verification',
														$email,
														$userDisplayName,
														$mergeTags);
		} else {
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-email-verification',
														$email,
														$userDisplayName,
														$mergeTags);
		}
    }
    
    public static function sendVIPEmailReverificationEmail($userDisplayName, $email, $verifyEmailURL)
    {
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $userDisplayName),
						   array('name' => 'VERIFY_EMAIL_URL',
								 'content' => $verifyEmailURL)
						   );
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-vip-reverify-email-before-expiration',
													$email,
													$userDisplayName,
													$mergeTags);
    }
	
	public static function sendTeamSubscriptionInvitation($fromUserName, $email, $invitationURL)
	{
		$mergeTags = array(
						   array('name' => 'FROM_USER_NAME',
								 'content' => $fromUserName),
						   array('name' => 'INVITATION_URL',
								 'content' => $invitationURL)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-subscription-added',
													$email,
													null, // User Display Name
													$mergeTags);
		
//		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
//		$subject = "$fromUserName has given you a Todo Cloud subscription";
//		
//		$htmlBody = "<p>Hello,\n\n";
//		$textBody = "Hello,\n\n";
//		
//		$htmlBody .= "<p>You've just been added to a Todo Cloud team subscription owned and paid for by " . $fromUserName . ".\n\n</p>\n";
//		$textBody .= "You've just been added to a Todo Cloud team subscription owned and paid for by " . $fromUserName . ".\n\n";
//		
//		$htmlBody .= "<p>Please click the following link or copy and paste the link into your web browser:\n\n</p>\n";
//		$textBody .= "Please click the following link or copy and paste the link into your web browser:\n\n";
//		
//		$htmlBody .= "<p>" . $invitationURL . "\n\n</p>\n";
//		$textBody .= $invitationURL . "\n\n";
//		
//		$htmlBody .= "<p>Enjoy!\n\n</p>\n";
//		$textBody .= "Enjoy!\n\n";
//		
//		$htmlBody .= "<p>" . EMAIL_SITE_NAME . "</p>\n";
//		$textBody .= EMAIL_SITE_NAME . "\n";
//		
//		return TDOMailer::sendHTMLAndTextEmail($email, $subject, EMAIL_FROM_ADDR, $htmlBody, $textBody);
	}
	
	public static function sendTeamAdminNewMemberNotification($teamID, $teamName, $adminEmail, $adminDisplayName, $memberEmail, $memberDisplayName, $membershipType)
	{
		$teamAdminURL = SITE_PROTOCOL . SITE_BASE_URL . "/?appSettings=show&option=teaming";
		
		$mergeTags = array(
						   array('name' => 'ADMIN_DISPLAY_NAME',
								 'content' => $adminDisplayName),
						   array('name' => 'MEMBER_DISPLAY_NAME',
								 'content' => $memberDisplayName),
						   array('name' => 'MEMBER_EMAIL',
								 'content' => $memberEmail),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'TEAM_ADMIN_URL',
								 'content' => $teamAdminURL)
						   );
		
		
		if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN)
		{
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-new-admin',
														$adminEmail,
														$memberDisplayName, // User Display Name
														$mergeTags);
		}
		else
		{
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-new-member',
														$adminEmail,
														$memberDisplayName, // User Display Name
														$mergeTags);
		}
	}
	
	public static function sendTeamAdminMemberRemovedNotification($teamID, $teamName, $adminEmail, $adminDisplayName, $memberEmail, $memberDisplayName, $membershipType)
	{
		$teamAdminURL = SITE_PROTOCOL . SITE_BASE_URL . "/?appSettings=show&option=teaming";
		
		$mergeTags = array(
						   array('name' => 'ADMIN_DISPLAY_NAME',
								 'content' => $adminDisplayName),
						   array('name' => 'MEMBER_DISPLAY_NAME',
								 'content' => $memberDisplayName),
						   array('name' => 'MEMBER_EMAIL',
								 'content' => $memberEmail),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'TEAM_ADMIN_URL',
								 'content' => $teamAdminURL)
						   );
		
		if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN)
		{
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-removed-admin',
														$adminEmail,
														$memberDisplayName, // User Display Name
														$mergeTags);
		}
		else
		{
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-removed-member',
														$adminEmail,
														$memberDisplayName, // User Display Name
														$mergeTags);
		}
	}
	
	public static function notifyTeamMemberOfRemoval($teamName, $displayName, $email, $newExpirationDate)
	{
		$expirationDate = date('d M Y', $newExpirationDate);
		$mergeTags = array(
						   array('name' => 'MEMBER_DISPLAY_NAME',
								 'content' => $displayName),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'EXPIRATION_DATE',
								 'content' => $expirationDate)
						   );
		
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-member-account-removed',
													$email,
													$displayName, // User Display Name
													$mergeTags);
	}
	
	public static function sendPromoCodeToUser($promoLink, $email)
	{
		$mergeTags = array(
						   array('name' => 'PROMO_CODE_URL',
								 'content' => $promoLink)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-promo-code',
													$email,
													null, // User Display Name
													$mergeTags);
	}
	
	
	public static function sendPromoCodeToNewTeamMember($userID, $promoLink, $numberOfMonths, $teamName)
	{
		$email = TDOUser::usernameForUserId($userID);
		$displayName = TDOUser::displayNameForUserId($userID);
		$teamURL = SITE_PROTOCOL . SITE_BASE_URL . "/?appSettings=show&option=teaming";
		
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $displayName),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'NUMBER_OF_MONTHS',
								 'content' => $numberOfMonths),
						   array('name' => 'PROMO_LINK',
								 'content' => $promoLink),
						   array('name' => 'TEAM_LINK',
								 'content' => $teamURL)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-member-promo-code',
													$email,
													$displayName, // User Display Name
													$mergeTags);
		
	}
	
	
	public static function sendPromoCodeForTeamCreditRefund($userID, $teamID, $numOfMonths, $promoCode, $promoLink)
	{
		$email = TDOUser::usernameForUserId($userID);
		$displayName = TDOUser::displayNameForUserId($userID);
		$teamName = TDOTeamAccount::teamNameForTeamID($teamID);
		
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $displayName),
						   array('name' => 'USER_EMAIL_ADDRESS',
								 'content' => $email),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'NUMBER_OF_MONTHS',
								 'content' => $numOfMonths),
						   array('name' => 'PROMO_CODE',
								 'content' => $promoCode),
						   array('name' => 'PROMO_LINK',
								 'content' => $promoLink)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-refund-team-credit-as-promo-code',
													$email,
													$displayName, // User Display Name
													$mergeTags);
		
	}
	
    
	public static function notifyUserOfExpirationChange($email, $displayName, $newExpirationTimestamp)
	{
		$newExpirationDate = _(date("D", $newExpirationTimestamp)) . ' ' . date("d", $newExpirationTimestamp) . ' ' . _(date("M", $newExpirationTimestamp)) . ' ' . date("Y", $newExpirationTimestamp);
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $displayName),
						   array('name' => 'NEW_EXPIRATION_DATE',
								 'content' => $newExpirationDate)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-account-expiration-change',
													$email,
													$displayName, // User Display Name
													$mergeTags);
	}
	
	
	public static function notifyTeamAdminOfExpirationChange($email, $displayName, $teamName, $teamID, $newExpirationTimestamp)
	{
		$newExpirationDate = _(date("D", $newExpirationTimestamp)) . ' ' . date("d", $newExpirationTimestamp) . ' ' . _(date("M", $newExpirationTimestamp)) . ' ' . date("Y", $newExpirationTimestamp);
		$teamAdminURL = SITE_PROTOCOL . SITE_BASE_URL . "/?appSettings=show&option=teaming";
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $displayName),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'NEW_EXPIRATION_DATE',
								 'content' => $newExpirationDate),
						   array('name' => 'TEAM_ADMIN_URL',
								 'content' => $teamAdminURL)
						   );
		
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-account-expiration-change',
													$email,
													$displayName, // User Display Name
													$mergeTags);
	}
	
	
    public static function sendEmailCommentErrorNotification($recipient, $errorMessage)
    {
        if(empty($recipient) || empty($errorMessage))
            return false;
		
		$mergeTags = array(
						   array('name' => 'ERROR_MESSAGE',
								 'content' => $errorMessage)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-comment-error',
													$recipient,
													null, // User Display Name
													$mergeTags);
    }
    
	//
	// PARAMETERS:
	//
	//	userEmail
	//		Who the email will be sent to
	//	displayName
	//		The display name of the user who the email will be sent to
	//	username
	//		The username of the account in question
	//	purchaseDate
	//		Scott Reeves would be pissed at this comment
	//	cardType
	//		The card brand, e.g., Visa, MasterCard, AMEX, etc.
	//	last4
	//		The last four digits of the credit card used to make the purchase
	//	subscriptionType
	//		One of SUBSCRIPTION_TYPE_MONTH, SUBSCRIPTION_TYPE_YEAR
	//	purchaseAmount
	//		The amount of hard-earned cash that they graciously just spent on
	//		this freakin' awesome service, specified in USD.
	public static function sendPremierAccountPurchaseReceipt($username, $displayName, $purchaseDate, $cardType, $last4, $subscriptionType, $purchaseAmount, $newExpirationDate)
	{
		if ( ($subscriptionType != SUBSCRIPTION_TYPE_MONTH) && ($subscriptionType != SUBSCRIPTION_TYPE_YEAR) )
		{
			error_log("TDOMailer::sendPremierAccountPurchaseReceipt() could not determine the subscription type (month|year) for $displayName ($username).");
			return false;
		}
        
		$accountType = _('Monthly');
		if ($subscriptionType == SUBSCRIPTION_TYPE_YEAR)
			$accountType = _('Yearly');
		
		$paymentDate = _(date("D", $purchaseDate)) . ' ' . date("d", $purchaseDate) . ' ' . _(date("M", $purchaseDate)) . ' ' . date("Y", $purchaseDate);
		$paymentMethod = $cardType . " XXXX-XXXX-XXXX-" . $last4;
		$newExpirationString = _(date("D", $newExpirationDate)) . ' ' . date("d", $newExpirationDate) . ' ' . _(date("M", $newExpirationDate)) . ' ' . date("Y", $newExpirationDate);
		$termsURL = SITE_PROTOCOL . SITE_BASE_URL . "/terms";
		
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $displayName),
						   array('name' => 'USER_EMAIL_ADDRESS',
								 'content' => $username),
						   array('name' => 'ACCOUNT_TYPE',
								 'content' => $accountType),
						   array('name' => 'PAYMENT_DATE',
								 'content' => $paymentDate),
						   array('name' => 'PAYMENT_METHOD',
								 'content' => $paymentMethod),
						   array('name' => 'NEW_EXPIRATION_DATE',
								 'content' => $newExpirationString),
						   array('name' => 'PURCHASE_AMOUNT',
								 'content' => money_format('$%!.2n', $purchaseAmount)),
						   array('name' => 'TERMS_URL',
								 'content' => $termsURL)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-subscription-purchase-receipt',
													$username,
													$displayName, // User Display Name
													$mergeTags);
	}
	
	//
	// PARAMETERS:
	//
	//	displayName
	//		The display name of the user who the email will be sent to
	//	username
	//		The username of the account in question
	//	purchaseDate
	//		Scott Reeves would be pissed at this comment
	//	cardType
	//		The card brand, e.g., Visa, MasterCard, AMEX, etc.
	//	last4
	//		The last four digits of the credit card used to make the purchase
	//	subscriptionType
	//		One of SUBSCRIPTION_TYPE_MONTH, SUBSCRIPTION_TYPE_YEAR
	//	purchaseAmount
	//		The amount of hard-earned cash that they graciously just spent on
	//		this freakin' awesome service, specified in USD.
	//	newExpirationDate
	//		The updated expiration date
	public static function sendTeamPurchaseReceipt($username, $displayName, $teamID,
												   $purchaseDate, $cardType, $last4,
												   $subscriptionType,
												   $unitPrice, $unitCombinedPrice,
												   $discountPercentage, $discountAmount,
												   $teamCreditMonths, $teamCreditsDiscountAmount,
												   $subtotalAmount, $purchaseAmount, $newExpirationDate,
												   $numOfSubscriptions)
	{
		$teamAccount = TDOTeamAccount::getTeamForTeamID($teamID);
		if (!$teamAccount)
		{
			error_log("TDOMailer::sendTeamPurchaseReceipt could not find the team for teamID: $teamID");
			return false;
		}
		
		if ( ($subscriptionType != SUBSCRIPTION_TYPE_MONTH) && ($subscriptionType != SUBSCRIPTION_TYPE_YEAR) )
		{
			error_log("TDOMailer::sendTeamPurchaseReceipt() could not determine the subscription type (month|year) for $displayName ($username).");
			return false;
		}
		
		$bizContactInfo = '';
		
		if ($teamAccount->getBizName())
		{
			$bizContactInfo .= $teamAccount->getBizName() . "<br/>\n";
		}
		
		if ($teamAccount->getBizPhone())
		{
			$bizContactInfo .= $teamAccount->getBizPhone() . "<br/>\n";
		}
		
		if ($teamAccount->getBizAddr1())
		{
			$bizContactInfo .= $teamAccount->getBizAddr1() . "<br/>\n";
		}
		
		if ($teamAccount->getBizAddr2())
		{
			$bizContactInfo .= $teamAccount->getBizAddr2() . "<br/>\n";
		}
		
		// Figure out what to put as the city, state, zip line
		$cityStateZip = NULL;
		if ($teamAccount->getBizCity())
		{
			$cityStateZip = $teamAccount->getBizCity();
			if ($teamAccount->getBizState())
			{
				$cityStateZip .= ", " . $teamAccount->getBizState();
			}
			
			if ($teamAccount->getBizPostalCode())
			{
				$cityStateZip .= " " . $teamAccount->getBizPostalCode();
			}
		}
		else if ($teamAccount->getBizCity())
		{
			$cityStateZip = $teamAccount->getBizCity();
			if ($teamAccount->getBizPostalCode())
			{
				$cityStateZip .= " " . $teamAccount->getBizPostalCode();
			}
		}
		else if ($teamAccount->getBizPostalCode())
		{
			$cityStateZip = $teamAccount->getBizPostalCode();
		}
		
		if ($cityStateZip)
		{
			$bizContactInfo .= $cityStateZip . "<br/>\n";
		}
		
		if ($teamAccount->getBizCountry())
		{
			$countryCode = $teamAccount->getBizCountry();
			$countryName = TDOTeamAccount::countryNameForCode($countryCode);
			
			// TODO: Write something that translates the country code to a country name
			
			$bizContactInfo .= $countryName . "<br/>\n";
		}
		
		$accountType = _("Monthly");
		if ($subscriptionType == SUBSCRIPTION_TYPE_YEAR)
			$accountType = _("Yearly");
		
		$paymentDate = _(date("D", $purchaseDate)) . ' ' . date("d", $purchaseDate) . ' ' . _(date("M", $purchaseDate)) . ' ' . date("Y", $purchaseDate);
		$paymentMethod = $cardType . " .... " . $last4;
		$newExpirationString = _(date("D", $newExpirationDate)) . ' ' . date("d", $newExpirationDate) . ' ' . _(date("M", $newExpirationDate)) . ' ' . date("Y", $newExpirationDate);
		
		$teamName = $teamAccount->getTeamName();
		$unitPriceString = money_format("$%!i", $unitPrice);
		$unitCombinedPriceString = money_format("$%!i", $unitCombinedPrice);
		$subtotalString = money_format("$%!i", $subtotalAmount);
		
		$termsURL = SITE_PROTOCOL . SITE_BASE_URL . "/terms";
		
		
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $displayName),
						   array('name' => 'USER_EMAIL_ADDRESS',
								 'content' => $username),
						   
						   array('name' => 'BIZ_CONTACT_INFO',
								 'content' => $bizContactInfo),
						   
						   array('name' => 'ACCOUNT_TYPE',
								 'content' => $accountType),
						   array('name' => 'PAYMENT_DATE',
								 'content' => $paymentDate),
						   array('name' => 'PAYMENT_METHOD',
								 'content' => $paymentMethod),
						   array('name' => 'NEW_EXPIRATION_DATE',
								 'content' => $newExpirationString),
						   array('name' => 'PURCHASE_AMOUNT',
								 'content' => money_format('$%!.2n', $purchaseAmount)),
						   
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'NUM_OF_SUBSCRIPTIONS',
								 'content' => $numOfSubscriptions),
						   array('name' => 'UNIT_PRICE',
								 'content' => $unitPriceString),
						   array('name' => 'UNIT_COMBINED_PRICE',
								 'content' => $unitCombinedPriceString),
						   
						   array('name' => 'SUBTOTAL',
								 'content' => $subtotalString),
						   
						   array('name' => 'TERMS_URL',
								 'content' => $termsURL)
						   );
		
		if ($teamCreditMonths > 0)
		{
			$mergeTags[] = array('name' => 'NUM_OF_TEAM_CREDIT_MONTHS',
								 'content' => $teamCreditMonths);
			$mergeTags[] = array('name' => 'TEAM_CREDITS_DISCOUNT_AMOUNT',
								 'content' => money_format('$%!.2n', $teamCreditsDiscountAmount));
		}
		
		$result = TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-subscription-purchase-receipt',
													   $username,
													   $displayName, // User Display Name
													   $mergeTags);
		
		// Send a copy of this receipt to business@appigo.com
		// https://github.com/Appigo/todo-issues/issues/754

		if (TDO_SERVER_TYPE == "production" || TDO_SERVER_TYPE == "beta" || TDO_SERVER_TYPE == "auth")
		{
            $teamCreationTimestamp = $teamAccount->getCreationDate();
            $teamCreationDate = _(date("D", $teamCreationTimestamp)) . ' ' . date("d", $teamCreationTimestamp) . ' ' . _(date("M", $teamCreationTimestamp)) . ' ' . date("Y", $teamCreationTimestamp);
            $subject = "Todo for Business was purchased by $teamName (receipt attached)";
            if ($paymentDate !== $teamCreationDate) {
                $subject = "Todo for Business was renewed by $teamName (receipt attached)";
            }
            $mergeTags[] = array(
                'name' => 'TEAM_CREATION_DATE',
                'content' => $teamCreationDate
            );
            TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-subscription-purchase-receipt',
												 'business@appigo.com',		// To Address
												 'Appigo Business Team',	// To Name
												 $mergeTags,
												 null,						// From Email
												 null,						// From Name
												 'business@appigo.com',		// ReplyTo Address
												 $subject);
		}
		
		return $result;
	}
	
	
	public static function sendTeamChangePurchaseReceipt($username,
														 $displayName,
														 $teamID,
														 $purchaseDate,
														 $cardType,
														 $last4,
														 $subscriptionType, $newExpirationDateString,
														 $numOfSubscriptions, $subtotal,
														 $discountAmount, $discountPercentage,
														 $teamCreditMonths, $teamCreditsDiscountAmount,
														 $accountCredit,
														 $totalCharge)
	{
		$teamAccount = TDOTeamAccount::getTeamForTeamID($teamID);
		if (!$teamAccount)
		{
			error_log("TDOMailer::sendTeamChangePurchaseReceipt could not find the team for teamID: $teamID");
			return false;
		}
		
		if ( ($subscriptionType != SUBSCRIPTION_TYPE_MONTH) && ($subscriptionType != SUBSCRIPTION_TYPE_YEAR) )
		{
			error_log("TDOMailer::sendTeamChangePurchaseReceipt() could not determine the subscription type (month|year) for $displayName ($username).");
			return false;
		}
		
		$bizContactInfo = '';
		
		if ($teamAccount->getBizName())
		{
			$bizContactInfo .= $teamAccount->getBizName() . "<br/>\n";
		}
		
		if ($teamAccount->getBizPhone())
		{
			$bizContactInfo .= $teamAccount->getBizPhone() . "<br/>\n";
		}
		
		if ($teamAccount->getBizAddr1())
		{
			$bizContactInfo .= $teamAccount->getBizAddr1() . "<br/>\n";
		}
		
		if ($teamAccount->getBizAddr2())
		{
			$bizContactInfo .= $teamAccount->getBizAddr2() . "<br/>\n";
		}
		
		// Figure out what to put as the city, state, zip line
		$cityStateZip = NULL;
		if ($teamAccount->getBizCity())
		{
			$cityStateZip = $teamAccount->getBizCity();
			if ($teamAccount->getBizState())
			{
				$cityStateZip .= ", " . $teamAccount->getBizState();
			}
			
			if ($teamAccount->getBizPostalCode())
			{
				$cityStateZip .= " " . $teamAccount->getBizPostalCode();
			}
		}
		else if ($teamAccount->getBizCity())
		{
			$cityStateZip = $teamAccount->getBizCity();
			if ($teamAccount->getBizPostalCode())
			{
				$cityStateZip .= " " . $teamAccount->getBizPostalCode();
			}
		}
		else if ($teamAccount->getBizPostalCode())
		{
			$cityStateZip = $teamAccount->getBizPostalCode();
		}
		
		if ($cityStateZip)
		{
			$bizContactInfo .= $cityStateZip . "<br/>\n";
		}
		
		if ($teamAccount->getBizCountry())
		{
			$countryCode = $teamAccount->getBizCountry();
			$countryName = TDOTeamAccount::countryNameForCode($countryCode);
			
			// TODO: Write something that translates the country code to a country name
			
			$bizContactInfo .= $countryName . "<br/>\n";
		}
		
		$accountType = _('Monthly');
		if ($subscriptionType == SUBSCRIPTION_TYPE_YEAR)
			$accountType = _('Yearly');
		
		$paymentDate = _(date("D", $purchaseDate)) . ' ' . date("d", $purchaseDate) . ' ' . _(date("M", $purchaseDate)) . ' ' . date("Y", $purchaseDate);
		$paymentMethod = $cardType . " .... " . $last4;
		
		$teamName = $teamAccount->getTeamName();
//		$unitPriceString = money_format("$%!i", $unitPrice);
//		$unitCombinedPriceString = money_format("$%!i", $unitCombinedPrice);
		$subtotalString = money_format("$%!i", $subtotal);
		$accountCreditString = money_format("$%!i", $accountCredit);
		
		$termsURL = SITE_PROTOCOL . SITE_BASE_URL . "/terms";
		
		
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $displayName),
						   array('name' => 'USER_EMAIL_ADDRESS',
								 'content' => $username),
						   
						   array('name' => 'BIZ_CONTACT_INFO',
								 'content' => $bizContactInfo),
						   
						   array('name' => 'PAYMENT_DATE',
								 'content' => $paymentDate),
						   array('name' => 'PAYMENT_METHOD',
								 'content' => $paymentMethod),
						   array('name' => 'NEW_EXPIRATION_DATE',
								 'content' => $newExpirationDateString),
						   array('name' => 'PURCHASE_AMOUNT',
								 'content' => money_format('%!.2n', $totalCharge)),
						   
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'NUM_OF_SUBSCRIPTIONS',
								 'content' => $numOfSubscriptions),
						   /*array('name' => 'UNIT_PRICE',
								 'content' => $unitPriceString),*/
						   /*array('name' => 'UNIT_COMBINED_PRICE',
								 'content' => $unitCombinedPriceString),*/
						   array('name' => 'SUBTOTAL',
								 'content' => $subtotalString),
						   array('name' => 'ACCOUNT_CREDIT',
								 'content' => $accountCreditString),
						   array('name' => 'BILLING_FREQUENCY',
								 'content' => $accountType),
						   
						   array('name' => 'TERMS_URL',
								 'content' => $termsURL)
						   );
		
		if ($teamCreditMonths > 0)
		{
			$mergeTags[] = array('name' => 'NUM_OF_TEAM_CREDIT_MONTHS',
								 'content' => $teamCreditMonths);
			$mergeTags[] = array('name' => 'TEAM_CREDITS_DISCOUNT_AMOUNT',
								 'content' => money_format('$%!.2n', $teamCreditsDiscountAmount));
		}
		
		$result = TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-subscription-change-receipt',
													$username,
													$displayName, // User Display Name
													$mergeTags);
		
		// Send a copy of this receipt to business@appigo.com
		// https://github.com/Appigo/todo-issues/issues/754
		$subject = "Todo for Business was changed (additional purchase) by $teamName (receipt attached)";
		
		if (TDO_SERVER_TYPE == "production" || TDO_SERVER_TYPE == "beta" || TDO_SERVER_TYPE == "auth")
		{
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-subscription-change-receipt',
												 'business@appigo.com',		// To Address
												 'Appigo Business Team',	// To Name
												 $mergeTags,
												 null,						// From Email
												 null,						// From Name
												 'business@appigo.com',		// ReplyTo Address
												 $subject);
		}
		
		return $result;
	}
	
	
	public static function sendTeamCreatedNotification($teamID)
	{
		$teamAccount = TDOTeamAccount::getTeamForTeamID($teamID);
		if (!$teamAccount)
		{
			error_log("TDOMailer::sendTeamCreatedNotification could not find the team for teamID: $teamID");
			return false;
		}
		
		$teamName = $teamAccount->getTeamName();
		
		$teamAdminID = $teamAccount->getBillingUserID();
		$teamAdminEmail = TDOUser::usernameForUserId($teamAdminID);
		$teamAdminDisplayName = TDOUser::displayNameForUserId($teamAdminID);
		
		$purchaseDate = $teamAccount->getCreationDate();
		
		$subscriptionType = $teamAccount->getBillingFrequency();
		$numOfSubscriptions = $teamAccount->getLicenseCount();
		
		$discoveryAnswer = $teamAccount->getDiscoveryAnswer();
		
		$bizContactInfo = '';
		
		if ($teamAccount->getBizName())
		{
			$bizContactInfo .= $teamAccount->getBizName() . "<br/>\n";
		}
		
		if ($teamAccount->getBizPhone())
		{
			$bizContactInfo .= $teamAccount->getBizPhone() . "<br/>\n";
		}
		
		if ($teamAccount->getBizAddr1())
		{
			$bizContactInfo .= $teamAccount->getBizAddr1() . "<br/>\n";
		}
		
		if ($teamAccount->getBizAddr2())
		{
			$bizContactInfo .= $teamAccount->getBizAddr2() . "<br/>\n";
		}
		
		// Figure out what to put as the city, state, zip line
		$cityStateZip = NULL;
		if ($teamAccount->getBizCity())
		{
			$cityStateZip = $teamAccount->getBizCity();
			if ($teamAccount->getBizState())
			{
				$cityStateZip .= ", " . $teamAccount->getBizState();
			}
			
			if ($teamAccount->getBizPostalCode())
			{
				$cityStateZip .= " " . $teamAccount->getBizPostalCode();
			}
		}
		else if ($teamAccount->getBizCity())
		{
			$cityStateZip = $teamAccount->getBizCity();
			if ($teamAccount->getBizPostalCode())
			{
				$cityStateZip .= " " . $teamAccount->getBizPostalCode();
			}
		}
		else if ($teamAccount->getBizPostalCode())
		{
			$cityStateZip = $teamAccount->getBizPostalCode();
		}
		
		if ($cityStateZip)
		{
			$bizContactInfo .= $cityStateZip . "<br/>\n";
		}
		
		if ($teamAccount->getBizCountry())
		{
			$countryCode = $teamAccount->getBizCountry();
			$countryName = TDOTeamAccount::countryNameForCode($countryCode);
			
			// TODO: Write something that translates the country code to a country name
			
			$bizContactInfo .= $countryName . "<br/>\n";
		}
		
		$accountType = "Monthly";
		if ($subscriptionType == SUBSCRIPTION_TYPE_YEAR)
			$accountType = "Yearly";
		$paymentDate = _(date("D", $purchaseDate)) . ' ' . date("d", $purchaseDate) . ' ' . _(date("M", $purchaseDate)) . ' ' . date("Y", $purchaseDate);
		
		$subject = "Todo for Business team created: $teamName, $accountType with $numOfSubscriptions license(s)";
		
		$mergeTags = array(
						   array('name' => 'TEAM_ADMIN_DISPLAY_NAME',
								 'content' => $teamAdminDisplayName),
						   array('name' => 'TEAM_ADMIN_EMAIL_ADDRESS',
								 'content' => $teamAdminEmail),
						   array('name' => 'BIZ_CONTACT_INFO',
								 'content' => $bizContactInfo),
						   array('name' => 'PAYMENT_DATE',
								 'content' => $paymentDate),
						   array('name' => 'NUM_OF_SUBSCRIPTIONS',
								 'content' => $numOfSubscriptions),
						   array('name' => 'BILLING_FREQUENCY',
								 'content' => $accountType),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'DISCOVERY_ANSWER',
								 'content' => $discoveryAnswer)
						   );
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-created-notification',
													'business@appigo.com',	// To Address
													'Appigo Business Team', // To Name
													$mergeTags,
													null,					// From Email (specified in Mandrill template)
													null,					// From Name (specified in Mandrill template)
													'business@appigo.com',	// ReplyTo Address
													$subject);
	}
	
	
	public static function sendTeamRemovedBillingAdminNotification($teamID, $teamName, $adminEmail, $adminDisplayName)
	{
		$teamAdminURL = SITE_PROTOCOL . SITE_BASE_URL . "/?appSettings=show&option=teaming";
		
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $adminDisplayName),
						   array('name' => 'TEAM_ADMIN_URL',
								 'content' => $teamAdminURL),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-removed-billing-admin',
													$adminEmail,
													$adminDisplayName, // User Display Name
													$mergeTags);
	}
	
	
	public static function sendTeamNewBillingAdminNotification($teamID, $teamName, $userEmail, $userDisplayName, $adminEmail, $adminDisplayName)
	{
		$teamAdminURL = SITE_PROTOCOL . SITE_BASE_URL . "/?appSettings=show&option=teaming";
		
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $adminDisplayName),
						   array('name' => 'TEAM_ADMIN_URL',
								 'content' => $teamAdminURL),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'NEW_ADMIN_DISPLAY_NAME',
								 'content' => $userDisplayName),
						   array('name' => 'NEW_ADMIN_EMAIL',
								 'content' => $userEmail)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-new-billing-admin',
													$adminEmail,
													$adminDisplayName, // User Display Name
													$mergeTags);
	}
	
	// newMember (true/false). When true, the email sent to the user will be
	// right after the member has joined the team. Specify false to send a
	// reminder email just before the IAP account is scheduled to renew.
	//
	// iapType (apple/google). This helps us customize the instructions sent to
	// the user.
	public static function sendTeamMemberIAPCancellationInstructions($userID, $nextRenewalDate, $teamName, $newMember, $iapType)
	{
		$userEmail = TDOUser::usernameForUserId($userID);
		$teamURL = SITE_PROTOCOL . SITE_BASE_URL . "/?appSettings=show&option=teaming";
		$displayName = TDOUser::displayNameForUserId($userID);
		$nextRenewalDateString = _(date("D", $nextRenewalDate)) . ' ' . date("d", $nextRenewalDate) . ' ' . _(date("M", $nextRenewalDate)) . ' ' . date("Y", $nextRenewalDate);

		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $displayName),
						   array('name' => 'NEXT_RENEWAL_DATE',
								 'content' => $nextRenewalDateString),
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'TEAM_URL',
								 'content' => $teamURL)
						   );
		
		$templateName = "";
		if ($newMember)
		{
			if ($iapType == "apple")
			{
				$templateName = "todo-cloud-team-apple-iap-cancel-instructions";
			}
			else if ($iapType == "google")
			{
				$templateName = "todo-cloud-team-googleplay-iap-cancel-instructions";
			}
		}
		else
		{
			if ($iapType == "apple")
			{
				$templateName = "todo-cloud-team-apple-iap-cancel-reminder-instructions";
			}
			else if ($iapType == "google")
			{
				$templateName = "todo-cloud-team-google-iap-cancel-reminder-instructions";
			}
		}
		
		return TDOMailer::sendMandrillEmailTemplate($templateName, $userEmail, $displayName, $mergeTags);
	}
	
    public static function sendAutorenewalNoticeForUserSwitchingFromStripe($username, $firstName, $nextExpirationDate)
    {
        if(empty($username) || empty($firstName))
        {
            error_log("TDOMailer::sendAutorenewalNoticeForUserSwitchingFromStripe called missing parameter");
            return false;
        }
		
		$expirationDateString = _(date("D", $nextExpirationDate)) . ' ' . date("d", $nextExpirationDate) . ' ' . _(date("M", $nextExpirationDate)) . ' ' . date("Y", $nextExpirationDate);
		
		$mergeTags = array(
						   array('name' => 'FIRST_NAME',
								 'content' => $firstName),
						   array('name' => 'EXPIRATION_DATE',
								 'content' => $expirationDateString)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-autorenewal-notice-switching-from-stripe',
													$username,
													NULL, // User Display Name
													$mergeTags);
    }
	
	
	public static function sendReferralLinkPurchaseNotification($username, $firstName, $extendedReferrerAccount, $autorenewingIAPDetected)
	{
		$referralStatusURL = SITE_PROTOCOL . SITE_BASE_URL . "/?appSettings=show&option=referrals";
		
		$mergeTags = array(
						   array('name' => 'FIRST_NAME',
								 'content' => $firstName),
						   array('name' => 'REFERRAL_STATUS_URL',
								 'content' => $referralStatusURL)
						   );
		
		if ($extendedReferrerAccount) {
			$mergeTags[] = array('name' => 'EXTENDED_REFERRER_ACCOUNT',
								 'content' => 'true');
		} else if ($autorenewingIAPDetected) {
			$mergeTags[] = array('name' => 'AUTORENEWING_IAP_DETECTED',
								 'content' => 'true');
		}
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-referral-purchase-extension',
													$username,
													NULL, // User Display Name
													$mergeTags);
	}
	
	
	public static function sendReferralLinkInvitation($emailAddresses, $senderDisplayName, $referralLink)
	{
		if (empty($referralLink))
		{
			error_log("TDOMailer::sendReferralLinkInvitation() called with empty referralLink");
			return false;
		}
		
		if (empty($emailAddresses))
		{
			error_log("TDOMailer::sendReferralLinkInvitation() called with empty emailAddresses");
			return false;
		}
		
		if (is_array($emailAddresses) == false)
		{
			error_log("TDOMailer::sendReferralLinkInvitation() called with invalid emailAddresses array");
			return false;
		}
		
		$atLeastOneEmailSent = false;
		
		foreach ($emailAddresses as $emailAddress)
		{
			$copyrightYear = date('Y');
			$urlEscapedEmailAddress = urlencode($emailAddress);
			$unsubscribeLink = SITE_PROTOCOL . SITE_BASE_URL . "/?referralunsubscribe=yes&email=$urlEscapedEmailAddress";
			
			$mergeTags = array(
							   array('name' => 'SENDER_DISPLAY_NAME',
									 'content' => $senderDisplayName),
							   array('name' => 'REFERRAL_LINK',
									 'content' => $referralLink),
							   array('name' => 'COPYRIGHT_YEAR',
									 'content' => $copyrightYear),
							   array('name' => 'REFERRALS_UNSUBSCRIBE_LINK',
									 'content' => $unsubscribeLink)
							   );
			
			if (TDOMailer::sendMandrillEmailTemplate('todo-cloud-referral-invitation',
													 $emailAddress,
													 NULL, // User Display Name
													 $mergeTags)) {
				$atLeastOneEmailSent = true;
				
			}
		}
		
		return $atLeastOneEmailSent;
	}
	
    
	public static function sendGiftCodePurchaseReceipt($username, $displayName, $purchaseDate, $cardType, $last4, $giftCodes, $purchaseAmount)
	{
		$paymentDate = _(date("D", $purchaseDate)) . ' ' . date("d", $purchaseDate) . ' ' . _(date("M", $purchaseDate)) . ' ' . date("Y", $purchaseDate);
		$paymentMethod = $cardType . " .... " . $last4;
		$giftCodeItems = '';
        $pricingTable = TDOSubscription::getPersonalSubscriptionPricingTable();

        foreach ($giftCodes as $giftCode) {
            $subscriptionType = 'year';
            if ($giftCode->subscriptionDuration() == 12) {
                $subscriptionType = 'year';
            } elseif ($giftCode->subscriptionDuration() == 1) {
                $subscriptionType = 'month';
            }
            $authoritativePrice = $pricingTable[$subscriptionType];

            $giftCodeItems .= '<li>Gift code for ' . $giftCode->recipientName() . ': ' . money_format('$%!.2n', $authoritativePrice) . '</li>';
        }

		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $displayName),
						   array('name' => 'PAYMENT_DATE',
								 'content' => $paymentDate),
						   array('name' => 'PAYMENT_METHOD',
								 'content' => $paymentMethod),
						   array('name' => 'GIFT_CODE_ITEMS',
								 'content' => $giftCodeItems),
						   array('name' => 'PURCHASE_AMOUNT',
								 'content' => money_format('$%!.2n', $purchaseAmount))
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-gift-code-purchase-receipt',
													$username,
													$displayName, // User Display Name
													$mergeTags);
	}
    
    public static function sendGiftCodeLinkToUser($recipientName, $recipientEmail, $senderName, $message, $subscriptionType, $giftCodeLink)
    {
		if ( ($subscriptionType != 'month') && ($subscriptionType != 'year') )
		{
			error_log("TDOMailer::sendGiftCodeLinkToUser() could not determine the subscription type (month|year) for $recipientName.");
			return false;
		}
        
        if(empty($recipientEmail))
        {
            error_log("TDOMailer::sendGiftCodeLinkToUser() called with no recipient email specified");
            return false;
        }
        if(empty($giftCodeLink))
        {
            error_log("TDOMailer::sendGiftCodeLinkToUser() called with no gift code link specified");
            return false;
        }
        
        if(empty($senderName))
            $senderName = "Unknown";
		
		$siteURL = SITE_PROTOCOL . SITE_BASE_URL . "/";
		
		$mergeTags = array(
						   array('name' => 'SENDER_DISPLAY_NAME',
								 'content' => $senderName),
						   array('name' => 'SENDER_MESSAGE',
								 'content' => $message),
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $recipientName),
						   array('name' => 'SITE_URL',
								 'content' => $siteURL),
						   array('name' => 'GIFT_CODE_LINK',
								 'content' => $giftCodeLink)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-gift-code-send-link',
													$recipientEmail,
													$recipientName, // User Display Name
													$mergeTags);
    }
    
	public static function sendGiftCodeToTeamUser($recipientName, $recipientEmail, $giftCodeLink, $giftCodeMonths)
	{
        if(empty($recipientName))
        {
            error_log("TDOMailer::sendGiftCodeToTeamUser() called with no recipientName");
            return false;
        }
        if(empty($recipientEmail))
        {
            error_log("TDOMailer::sendGiftCodeToTeamUser() called with no recipientEmail");
            return false;
        }
        if(empty($giftCodeLink))
        {
            error_log("TDOMailer::sendGiftCodeToTeamUser() called with no giftCodeLink");
            return false;
        }
		if(empty($giftCodeMonths))
		{
			error_log("TDOMailer::sendGiftCodeToTeamUser() called with no giftCodeMonths");
			return false;
		}
		
		$siteURL = SITE_PROTOCOL . SITE_BASE_URL . "/";
		
		$mergeTags = array(
						   array('name' => 'USER_DISPLAY_NAME',
								 'content' => $recipientName),
						   array('name' => 'SITE_URL',
								 'content' => $siteURL),
						   array('name' => 'GIFT_CODE_MONTHS',
								 'content' => $giftCodeMonths),
						   array('name' => 'GIFT_CODE_LINK',
								 'content' => $giftCodeLink)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-gift-code-for-team-member',
													$recipientEmail,
													$recipientName, // User Display Name
													$mergeTags);
	}
	
	
	public static function sendMandrillEmailTemplate($templateName, $toEmail, $toName, $mergeTags, $fromEmail=null, $fromName=null, $replyToAddress=null, $subject=null)
	{
		try {
			$mandrill = new Mandrill(MANDRILL_API_KEY);
            $template_name = '';
            if ($toEmail !== 'business@appigo.com') {
                $locale = self::getLocalePrefix($toEmail);
                $template_name = $locale;
            }
			$template_name .= $templateName;
			$template_content = '';
			$message = array(
							 'to' => array(array('email' => $toEmail, 'name' => $toName)),
							 );
			if (!empty($subject)) {
				$message['subject'] = $subject;
			}
			if (!empty($fromEmail)) {
				$message['from_email'] = $fromEmail;
			}
			if (!empty($fromName)) {
				$message['from_name'] = $fromName;
			}
			if (!empty($replyToAddress)) {
				$message['headers'] = array('Reply-To' => $replyToAddress);
			}
			if (!empty($mergeTags)) {
				$message['merge_vars'] = array(array(
													 'rcpt' => $toEmail,
													 'vars' => $mergeTags
											   ));
			}
			
			$response = $mandrill->messages->sendTemplate($template_name, $template_content, $message);
			if (!empty($response)) {
				$status = $response[0]['status'];
				if (!empty($status) && $status == 'sent') {
					return true;
				} else if (!empty($status) && $status == 'queued') {
					error_log("Mandrill queued an email (" . $template_name . ") to " . $toEmail);
					return true;
				}
				error_log("Mandrill response (" . $template_name . " - " . $toEmail . "): " . $status);
			} else {
				error_log("No response when attempting to send email via Mandrill (" . $template_name . " - " . $toEmail . ").");
			}
		} catch(Mandrill_Error $e) {
			error_log("TDOMailer::sendMandrillEmailTemplate(" . $templateName . ") could not send email to $toEmail: " . $e->getMessage());
		}
		
		return false;
	}
	
    public static function sendHTMLAndTextEmail($recipient, $subject, $fromName, $fromAddress, $htmlContent, $textContent, $replyToAddress=null, $htmlFooterAdditions=null)
    {
		if (empty($recipient))
		{
			error_log("TDOMailer::sendHTMLAndTextEmail() cannot send email because recipient parameter is empty");
			return false;
		}
		
		if (TDOMailer::isBouncedEmail($recipient))
		{
			error_log("TDOMailer::sendHTMLAndTextEmail() cannot send to bounced email: $recipient");
			return false;
		}
		
        $boundaryId = TDOUtil::uuid();
        
        $parameters = "-f" . $fromAddress;
        
        $header = 'MIME-Version: 1.0' . "\r\n";
        $header .= "From: ".$fromName." <".$fromAddress.">\r\n";
		if (!empty($replyToAddress))
		{
            $header .= "Reply-to: ".$replyToAddress."\r\n";
		}
        $header .= 'Content-type: multipart/alternative; boundary='.$boundaryId. "\r\n\r\n";
        

        // HTML BODY
        $htmlBody = "<body style=\"font-size:14px;font-family:'lucida grande',tahoma,verdana,arial,sans-serif;line-height:1.2rem\" bgcolor=\"#B4BBC3\">\n";
		$htmlBody .= "<table border=\"0\" align=\"center\" cellpadding=\"50\" cellspacing=\"0\" width=\"100%\" background=\"" . TP_IMG_PATH_VIEW_BACKGROUND . "\" bgcolor=\"#B4BBC3\">\n";
		$htmlBody .= "<tr>\n";
		$htmlBody .= "<td>\n";
		$htmlBody .= "<table border=\"0\" align=\"center\" bgcolor=\"#FFFFFF\" cellpadding=\"50\" width=\"550\" style=\"border:1px solid rgb(122,126,131);\">\n";
		$htmlBody .= "<tr>\n";
		$htmlBody .= "<td>\n";
		$htmlBody .= "<p><a href=\"" . SITE_PROTOCOL . SITE_BASE_URL . "/\" title=\"Todo Cloud\" target=\"_blank\">\n";
		$htmlBody .= "  <img src=\"" . TP_IMG_PATH_TP_PRO_EMAIL_LOGO2X . "\" width=\"206\" height=\"29\" border=\"0\" />\n";
		$htmlBody .= "</a></p>\n";
        
        $htmlBody .= $htmlContent;
		
		$htmlBody .= "</td>\n";
		$htmlBody .= "</tr>\n";
		$htmlBody .= "</table>\n";
		$htmlBody .= "</td>\n";
		$htmlBody .= "</tr>\n";
		
		$htmlBody .= "<tr>\n";
		$htmlBody .= "<td>\n";
        if($htmlFooterAdditions != null)
        {
            $htmlBody .= "<p style=\"color:gray;font-size:10px;text-align:center;margin-bottom:6px;\">".$htmlFooterAdditions."</p>";
        }
        
        $htmlBody .= "<p style=\"color:gray;font-size:10px;text-align:center;margin:0\">Todo Cloud is a service provided by <a href=\"http://www.appigo.com/\" style=\"color:gray;text-decoration:none\">Appigo</a> - <a href=\"".TERMS_OF_SERVICE_URL."\"  style=\"color:gray;text-decoration:none\">Terms of Service</a> - <a href=\"".PRIVACY_POLICY_URL."\" style=\"color:gray;text-decoration:none\">Privacy Policy</a></p>\n";
        $htmlBody .= "<p style=\"color:gray;font-size:10px;text-align:center;margin:6px 0\">Copyright © ".date('Y')." Appigo, Inc. - " . APPIGO_MAILING_ADDRESS . ". All Rights Reserved</p>\n";
		$htmlBody .= "<tr>\n";
		$htmlBody .= "<td>\n";
		$htmlBody .= "</table>\n";
		$htmlBody .= "</body>\n";
        
        
        // TEXT BODY
        $textBody = "";
        $textBody .= $textContent;
        
        $textBody .= "\r\n\r\nTodo Cloud is a service provided by Appigo - www.appigo.com\r\n";
        $textBody .= "Terms of Service: ".TERMS_OF_SERVICE_URL."\r\n";
        $textBody .= "Privacy Policy: ".PRIVACY_POLICY_URL."\r\n";
        
        
        // Compose the email into the multi-part message
        $emailBody = "This is a multi-part message in MIME format."; 
        $emailBody .= "\r\n--".$boundaryId."\r\n";
        $emailBody .= "Content-Type: text/plain\r\n\r\n";
        $emailBody .= $textBody;
        $emailBody .= "\r\n--".$boundaryId."\r\n";
        $emailBody .= "Content-Type: text/html\r\n\r\n";
        $emailBody .= $htmlBody;
        $emailBody .= "\r\n\r\n--".$boundaryId."--\r\n\r\n";
        
        
        return mail($recipient,$subject,$emailBody,$header,$parameters);
    } 
    
    
    public static function sendAppigoHTMLAndTextEmail($recipient, $subject, $fromName, $fromAddress, $htmlContent, $textContent, $replyToAddress=null, $htmlFooterAdditions=null)
    {
		if (empty($recipient))
		{
			error_log("TDOMailer::sendAppigoHTMLAndTextEmail() cannot send email because recipient parameter is empty");
			return false;
		}
		
		if (TDOMailer::isBouncedEmail($recipient))
		{
			error_log("TDOMailer::sendAppigoHTMLAndTextEmail() cannot send to bounced email: $recipient");
			return false;
		}
		
        $boundaryId = TDOUtil::uuid();
        
        $parameters = "-f" . $fromAddress;
        
        $header = 'MIME-Version: 1.0' . "\r\n";
        $header .= "From: ".$fromName." <".$fromAddress.">\r\n";
		if (!empty($replyToAddress))
		{
            $header .= "Reply-to: ".$replyToAddress."\r\n";
		}
        $header .= 'Content-type: multipart/alternative; boundary='.$boundaryId. "\r\n\r\n";
        
        
        // HTML BODY
        $htmlBody = "<body style=\"font-size:14px;font-family:'lucida grande',tahoma,verdana,arial,sans-serif;line-height:1.2rem\" bgcolor=\"#B4BBC3\">\n";
		$htmlBody .= "<table border=\"0\" align=\"center\" cellpadding=\"50\" cellspacing=\"0\" width=\"100%\" background=\"" . TP_IMG_PATH_VIEW_BACKGROUND . "\" bgcolor=\"#B4BBC3\">\n";
		$htmlBody .= "<tr>\n";
		$htmlBody .= "<td>\n";
		$htmlBody .= "<table border=\"0\" align=\"center\" bgcolor=\"#FFFFFF\" cellpadding=\"50\" width=\"550\" style=\"border:1px solid rgb(122,126,131);\">\n";
		$htmlBody .= "<tr>\n";
		$htmlBody .= "<td>\n";
		$htmlBody .= "<p><a href=\"https://www.appigo.com/\" title=\"Appigo, Inc.\" target=\"_blank\">\n";
		$htmlBody .= "  <img src=\"http://images.appigo.com/appigo/Appigo-Logo@2x.png\" width=\"150\" height=\"35\" border=\"0\" />\n";
		$htmlBody .= "</a></p>\n";
        
        $htmlBody .= $htmlContent;
		
		$htmlBody .= "</td>\n";
		$htmlBody .= "</tr>\n";
		$htmlBody .= "</table>\n";
		$htmlBody .= "</td>\n";
		$htmlBody .= "</tr>\n";
		
		$htmlBody .= "<tr>\n";
		$htmlBody .= "<td>\n";
        if($htmlFooterAdditions != null)
        {
            $htmlBody .= "<p style=\"color:gray;font-size:10px;text-align:center;margin-bottom:6px;\">".$htmlFooterAdditions."</p>";
        }
        
        $htmlBody .= "<p style=\"color:gray;font-size:10px;text-align:center;margin:6px 0\">Copyright © ".date('Y')." Appigo, Inc. - " . APPIGO_MAILING_ADDRESS . ". All Rights Reserved</p>\n";
		$htmlBody .= "<tr>\n";
		$htmlBody .= "<td>\n";
		$htmlBody .= "</table>\n";
		$htmlBody .= "</body>\n";
        
        
        // TEXT BODY
        $textBody = "";
        $textBody .= $textContent;
        
        $textBody .= "\r\n\r\nAppigo - www.appigo.com\r\n";
        
        // Compose the email into the multi-part message
        $emailBody = "This is a multi-part message in MIME format."; 
        $emailBody .= "\r\n--".$boundaryId."\r\n";
        $emailBody .= "Content-Type: text/plain\r\n\r\n";
        $emailBody .= $textBody;
        $emailBody .= "\r\n--".$boundaryId."\r\n";
        $emailBody .= "Content-Type: text/html\r\n\r\n";
        $emailBody .= $htmlBody;
        $emailBody .= "\r\n\r\n--".$boundaryId."--\r\n\r\n";
        
        
        return mail($recipient,$subject,$emailBody,$header,$parameters);
    }     
    
    
    
    
    public static function validate_email($email)
    {
        /* Email error checking */
		
		if (empty($email))
		{
			return false;
		}
        
       $isValid = true;
       $atIndex = strrpos($email, "@");
       if (is_bool($atIndex) && !$atIndex)
       {
          $isValid = false;
       }
       else
       {
          $domain = substr($email, $atIndex+1);
          $local = substr($email, 0, $atIndex);
          $localLen = strlen($local);
          $domainLen = strlen($domain);
          if ($localLen < 1 || $localLen > 64)
          {
             // local part length exceeded
             $isValid = false;
          }
          else if ($domainLen < 1 || $domainLen > 255)
          {
             // domain part length exceeded
             $isValid = false;
          }
          else if ($local[0] == '.' || $local[$localLen-1] == '.')
          {
             // local part starts or ends with '.'
             $isValid = false;
          }
          else if (preg_match('/\\.\\./', $local))
          {
             // local part has two consecutive dots
             $isValid = false;
          }
          else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
          {
             // character not valid in domain part
             $isValid = false;
          }
          else if (preg_match('/\\.\\./', $domain))
          {
             // domain part has two consecutive dots
             $isValid = false;
          }
          else
          {
              // CRG - Added in ability to have a + character only if in the appigo.com domain
              if(strcasecmp($domain, "appigo.com") == 0)
              {
                  $pregMatchString = '/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/';
                  $quotedPregMatchString = '/^"(\\\\"|[^"])+"$/';
              }
              else
              {
                  $pregMatchString = '/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*?^{}|~.-])+$/'; // no plus
                  $quotedPregMatchString = '/^"(\\\\"|[^"])"$/';
              }
              if (!preg_match($pregMatchString, str_replace("\\\\","",$local)))
              {
                 // character not valid in local part unless local part is quoted
                 if (!preg_match($quotedPregMatchString, str_replace("\\\\","",$local)))
                 {
                    $isValid = false;
                 }
              }
          }
          if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
          {
             // domain not found in DNS
             $isValid = false;
          }
       }
       if($isValid)
            return $email;
            
       return false;
    }
    
   
   
//   public static function sendFeedback ($fromName, $subject, $body)
//   {
//   	/**
//   	* sendFeedback - Sends user feedback to support@appigo.com
//   	*/
//   		$from = 'From: '.EMAIL_FROM_NAME.' <'.EMAIL_FROM_ADDR.'>';
//   		//$toEmail = 'betafeedback@appigo.com';
//        $toEmail = 'support+todopro@appigo.com';
//   		$subject = $subject .' ('.$fromName.')';
//   		
//	   $parameters = "-f" . EMAIL_FROM_ADDR;
//   		return mail($toEmail, $subject, $body, $from, $parameters);
//   }
	
	
    // Special emails sent only to Appigo
    
	public static function sendSubscriptionUpdateErrorNotification($subscriptionID, $newExpirationDate)
	{
		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[URGENT:" . TDO_SERVER_TYPE . "] Todo Cloud Subscription Error";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that a user purchased a subscription but the " .
		"subscription table could not be updated. Subscription ID is " .
		"$subscriptionID and expiration date should be set to: " .
		"$newExpirationDate.";
        
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
	}
    
    public static function sendSubscriptionTypeUpdateErrorNotification($subscriptionID, $subscriptionType)
    {
        $from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[URGENT:" . TDO_SERVER_TYPE . "] Todo Cloud Subscription Error";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that a user purchased a subscription but the " .
		"subscription table could not be updated. Subscription ID is " .
		"$subscriptionID and subscription type should be set to: " .
		"$subscriptionType.";
        
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
    }
    
    public static function sendGiftCodeUpdateErrorNotification($userID, $stripePurchaseId, $purchaseTimestamp, $recipientName, $recipientEmail, $subscriptionDuration)
    {
		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[URGENT:" . TDO_SERVER_TYPE . "] Todo Cloud Gift Code Error";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that a user purchased a gift code but the " .
		"gift code table could not be updated. User ID is " .
		"$userID. ";
        
        if(!empty($stripePurchaseId))
            $body .= "Stripe purchase ID is: $stripePurchaseId. ";
        else
            $body .= "The Stripe purchase was not recorded in the database. ";
        
        $body .= "Purchase timestamp is: $purchaseTimestamp. ";
        $body .= "Recipient name is: $recipientName. ";
        if(!empty($recipientEmail))
            $body .= "Recipient email is: $recipientEmail. ";
        $body .= "Subscription duration is: $subscriptionDuration. ";
        
        
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
    }
    
    public static function sendGiftCodeMassPurchaseNotification($userID, $stripePurchaseId, $purchaseTimestamp, $purchaseCount)
    {
        $from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[Attention] Large Todo Cloud Gift Code Purchase";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that a user purchased $purchaseCount number of " .
		"Todo Cloud gift codes.User ID is " .
		"$userID. ";
        
        if(!empty($stripePurchaseId))
            $body .= "Stripe purchase ID is: $stripePurchaseId. ";
        else
            $body .= "The Stripe purchase was not recorded in the database. ";
        
        $body .= "Purchase timestamp is: $purchaseTimestamp. ";
        
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
    }
    
	public static function sendSubscriptionLogErrorNotification($subscriptionID)
	{
		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[URGENT:" . TDO_SERVER_TYPE . "] Todo Cloud Subscription Error - Failed to record purchase";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that a user successfully purchased a " .
		"subscription but was unable to log the purchase. Subscription ID is " .
		"$subscriptionID";
		
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
	}
	
	public static function sendTeamSubscriptionLogErrorNotification($teamID)
	{
		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[URGENT:" . TDO_SERVER_TYPE . "] Todo Cloud Team Account Error - Failed to record purchase";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that a user successfully purchased a " .
		"team account but was unable to log the purchase. The team ID is " .
		"$teamID";
		
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
		
	}
    
    public static function sendGiftCodeLogErrorNotification($userID, $purchaseTimestamp, $purchaseCount)
    {
		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[URGENT:" . TDO_SERVER_TYPE . "] Todo Cloud Gift Code Error - Failed to record purchase";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that a user successfully purchased " . $purchaseCount .
		" gift code(s) but was unable to log the purchase. User ID is " .
		"$userID. Purchase timestamp is: $purchaseTimestamp.";
		
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
    }
    
    public static function sendSubscriptionRenewalMaxRetryAttemptsReachedNotification($subscriptionID)
    {
		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[URGENT:" . TDO_SERVER_TYPE . "] Todo Cloud Subscription Error - Max Retry Attempts Reached";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that we reached the maximum number of retry attempts " .
        "for renewing subscription id " . $subscriptionID .". An admin needs to " .
        "check on this user's account and fix it up or else the user will never " .
        "be able to auto-renew his subscription again.";
		
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
    }
	
	
	public static function sendTeamSubscriptionRenewalMaxRetryAttemptsReachedNotification($teamID)
	{
		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[URGENT:" . TDO_SERVER_TYPE . "] Todo Cloud Team Subscription Error - Max Retry Attempts Reached";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that we reached the maximum number of retry attempts " .
        "for renewing team id " . $teamID .". An admin needs to " .
        "check on this team's account and fix it up or else the team may never " .
        "be able to auto-renew its subscription again.";
		
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
		
	}
	
	
	public static function sendTeamSubscriptionExpirationErrorNotification($teamID, $newExpirationDate)
	{
		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[URGENT:" . TDO_SERVER_TYPE . "] Todo Cloud Team Subscription Expiration Date Error";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that a team admin purchased a team account but the " .
		"tdo_team_accounts table could not be updated to reflect the new expiration date. The Team ID is " .
		"$teamID and expiration date should be set to: " .
		"$newExpirationDate (" . date('d M Y', $newExpirationDate) . ").";
        
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
	}
	
	public static function sendTeamSubscriptionLicenseCountErrorNotification($teamID, $totalLicenseCount)
	{
		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$subject = "[URGENT:" . TDO_SERVER_TYPE . "] Todo Cloud Team Subscription Error";
		$body = "Hello,\n\n" .
		"The system (" . TDO_SERVER_TYPE . ") just detected that a team admin purchased licenses for a team account but the " .
		"tdo_team_accounts table could not be updated to reflect the new license count. The Team ID is " .
		"$teamID. The total number of licenses should be $totalLicenseCount.";
        
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail("support@appigo.com", $subject, $body, $from, $parameters);
	}
	
	
	public static function sendTeamExpiredNotification($teamAccount)
	{
		$teamID = $teamAccount->getTeamID();
		$teamName = $teamAccount->getTeamName();
		$expirationDate = $teamAccount->getExpirationDate();
		$expirationDateString = date('d M Y', $expirationDate);
		
		$systemSettingTeamExpirationGracePeriodDateIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL);
		$gracePeriodDateInterval = new DateInterval($systemSettingTeamExpirationGracePeriodDateIntervalSetting);
		$gracePeriodDate = new DateTime('@' . $expirationDate, new DateTimeZone("UTC"));
		$gracePeriodDate = $gracePeriodDate->add($gracePeriodDateInterval);
		$gracePeriodEndDateString = $gracePeriodDate->format('j F Y');
		
		$mergeTags = array(
						   array('name' => 'TEAM_NAME',
								 'content' => $teamName),
						   array('name' => 'TEAM_EXPIRATION_DATE',
								 'content' => $expirationDateString),
						   /*array('name' => 'TEAM_GRACE_PERIOD_IN_DAYS',
								 'content' => $systemSettingTeamExpirationGracePeriodInDays),*/
						   array('name' => 'TEAM_GRACE_PERIOD_END_DATE',
								 'content' => $gracePeriodEndDateString)
						   );
		
		// The team subscription is expired. The team now enters the
		// grace period where shared lists can still be used for 7 days,
		// but we need to send an email to the entire team.
		$teamAdminIDs = TDOTeamAccount::getAdminUserIDsForTeam($teamID);
		$teamMemberIDs = TDOTeamAccount::getUserIDsForTeam($teamID);
		
		$allTeamMemberIDs = array();
		if (!empty($teamAdminIDs))
			$allTeamMemberIDs = array_merge($allTeamMemberIDs, $teamAdminIDs);
		if (!empty($teamMemberIDs))
			$allTeamMemberIDs = array_merge($allTeamMemberIDs, $teamMemberIDs);
		
		// Remove any possible duplicates
		$allTeamMemberIDs = array_unique($allTeamMemberIDs);
		
		foreach ($allTeamMemberIDs as $userID)
		{
			$emailAddress = TDOUser::usernameForUserId($userID);
			
			return TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-expiration-notification',
														$emailAddress,
														NULL, // User Display Name
														$mergeTags);
		}
	}

	
	public static function notifyCriticalSystemError($recipient, $subject, $body)
	{
		$from = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDR . ">";
		$parameters = "-f" . EMAIL_FROM_ADDR;
		return mail($recipient, $subject, $body, $from ,$parameters);
	}
	
	
	// The TDOMailer should call this before allowing ANY emails to be sent to
	// an email address. If true is returned, do NOT send an email.
	public static function isBouncedEmail($email)
	{
		if (empty($email))
		{
			error_log("TDOMailer::isBouncedEmail() missing email parameter");
			return true;
		}
		
		$lowerCaseEmail = strtolower($email);
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOMailer::getBounceRecordForEmail() failed to get dblink");
			return false;
		}
		
		$lowerCaseEmail = mysql_real_escape_string($lowerCaseEmail, $link);
		$sql = "SELECT bounce_type FROM tdo_bounced_emails WHERE email='$lowerCaseEmail'";
		$result = mysql_query($sql, $link);
		if ($result)
		{
			if ($row = mysql_fetch_array($result))
			{
				$bounceEmail = true;
				
				// Prevent "Permanent" and "Complaint" emails from being sent
				
				$bounceType = $row['bounce_type'];
				if ( ($bounceType == BOUNCE_TRANSIENT) || ($bounceType == BOUNCE_UNDETERMINED) )
				{
					// Transient emails are those like out of the office or
					// mailbox is full which may eventually allow us to send
					// emails to.
					// Undetermined bounces are not permanent and at this point,
					// we will still send these emails out. We should probably
					// set some sort of bounce rate or limit of the number of
					// total bounces for undetermined bounces.
					$bounceEmail = false;
				}
				else
				{
					$bounceTypeString = TDOMailer::stringForBounceType($bounceType);
					error_log("TDOMailer::isBouncedEmail() returning true for $email, bounceType = $bounceTypeString");
				}
				
				TDOUtil::closeDBLink($link);
				return $bounceEmail;
			}
		}
		
		TDOUtil::closeDBLink($link);
		return false;
	}
	
	
	public static function getBounceRecordForEmail($email)
	{
		if (empty($email))
		{
			error_log("TDOMailer::getBounceRecordForEmail() missing email parameter.");
			return false;
		}
		
		$lowerCaseEmail = strtolower($email);
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOMailer::getBounceRecordForEmail() failed to get dblink");
			return false;
		}
		
		$lowerCaseEmail = mysql_real_escape_string($lowerCaseEmail, $link);
		$sql = "SELECT email,bounce_type,timestamp,bounce_count FROM tdo_bounced_emails WHERE email='$lowerCaseEmail'";
		$result = mysql_query($sql, $link);
		if ($result)
		{
			if ($row = mysql_fetch_array($result))
			{
				$bounceTypeString = TDOMailer::stringForBounceType($row['bounce_type']);
				$bounceRecord = array(
									  "email" => $row['email'],
									  "bounceTypeString" => $bounceTypeString,
									  "bounceType" => $row['bounce_type'],
									  "timestamp" => $row['timestamp'],
									  "bounceCount" => $row['bounce_count']
				);
				
				TDOUtil::closeDBLink($link);
				return $bounceRecord;
			}
		}
		
		TDOUtil::closeDBLink($link);
		return false;
	}
	
	
	public static function recordBounceEmail($email, $bounceType=BOUNCE_UNKNOWN)
	{
		if (empty($email))
		{
			error_log("TDOMailer::recordBounceEmail() missing email parameter.");
			return false;
		}
		
		// Convert the email to lowercase so that we don't ever get into a
		// situation where we don't find the user's email address.
		$lowerCaseEmail = strtolower($email);
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOMailer::recordBounceEmail() failed to get dblink");
			return false;
		}
		
		$newCount = 1;
		$timestamp = time();
		$bounceRecord = TDOMailer::getBounceRecordForEmail($lowerCaseEmail);
		if ($bounceRecord)
		{
			// A record for this email already exists and now we'll update it
			// with the latest information.
			$existingType = $bounceRecord['bounceType'];
			error_log("Existing type for email ($email): $existingType");
			$existingCount = $bounceRecord['bounceCount'];
			error_log("Existing count for email ($email): $existingCount");
			$newType = $existingType;
			
			if ($existingType == $bounceType)
				$newCount = $existingCount + 1;
			else
				$newType = $bounceType;
			
			$sql = "UPDATE tdo_bounced_emails SET bounce_type=$newType,timestamp=$timestamp,bounce_count=$newCount WHERE email='$lowerCaseEmail'";
			$response = mysql_query($sql, $link);
			if (!$response)
			{
				error_log("TDOMailer::recordBounceEmail() failed to update bounce record for email: $lowerCaseEmail");
				TDOUtil::closeDBLink($link);
				return false;
			}
		}
		else
		{
			// We can create a brand new record in the database for this email
			$lowerCaseEmail = mysql_real_escape_string($lowerCaseEmail, $link);
			$sql = "INSERT INTO tdo_bounced_emails(email,bounce_type,timestamp,bounce_count) VALUE ('$lowerCaseEmail', $bounceType, $timestamp, $newCount)";
			$response = mysql_query($sql, $link);
			if (!$response)
			{
				error_log("TDOMailer::recordBounceEmail() failed to record new email: $lowerCaseEmail");
				TDOUtil::closeDBLink($link);
				return false;
			}
		}
		
		TDOUtil::closeDBLink($link);
		
		$bounceTypeString = TDOMailer::stringForBounceType($bounceType);
		$bounceMessage = "Recorded \"$bounceTypeString\" BOUNCE email ($lowerCaseEmail), count = $newCount.";
		error_log($bounceMessage);
		
		return true;
	}
	
	
	public static function clearBounceEmail($email)
	{
		if (empty($email))
		{
			error_log("TDOMailer::clearBounceEmail() missing email parameter.");
			return false;
		}
		
		// Convert the email to lowercase so that we don't ever get into a
		// situation where we don't find the user's email address.
		$lowerCaseEmail = strtolower($email);
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOMailer::clearBounceEmail() failed to get dblink");
			return false;
		}
		
		$lowerCaseEmail = mysql_real_escape_string($lowerCaseEmail, $link);
		$sql = "DELETE FROM tdo_bounced_emails WHERE email='$lowerCaseEmail'";
		
        if(mysql_query($sql, $link))
        {
			TDOUtil::closeDBLink($link);
            return true;
        }
        else
		{
			error_log("TDOMailer::clearBounceEmail() failed delete bounced email");
		}
		
		return false;
	}
    public static function sendUserFeedback($user, $feedback)
    {

        $mergeTags = array(
            array('name' => 'USER_DISPLAY_NAME',
                'content' => $user->displayName()),
            array('name' => 'USER_EMAIL',
                'content' => $user->username()),
            array('name' => 'USER_ID',
                'content' => $user->userId()),
            array('name' => 'FEEDBACK',
                'content' => $feedback)
        );

        return TDOMailer::sendMandrillEmailTemplate(
            'user-feedback',
            'support@appigo.com',
            NULL, // User Display Name
            $mergeTags);
    }
	
	public static function stringForBounceType($bounceType)
	{
		$bounceTypeString = "Unknown";
		if ($bounceType == BOUNCE_PERMANENT)
			$bounceTypeString = "Permanent";
		else if ($bounceType == BOUNCE_TRANSIENT)
			$bounceTypeString = "Transient";
		else if ($bounceType == BOUNCE_COMPLAINT)
			$bounceTypeString = "Complaint";
		else if ($bounceType == BOUNCE_UNDETERMINED)
			$bounceTypeString = "Undetermined";
		
		return $bounceType;
	}

    private function getLocalePrefix($email = FALSE)
    {
        $session = TDOSession::getInstance();
        $user_locale = FALSE;
        if ($session && $session->isLoggedIn()) {
            if ($session->getUserId()) {
                $user_locale = TDOUser::getLocaleForUser($session->getUserId());
            }
        } elseif (isset($_COOKIE['interface_language']) && $_COOKIE['interface_language'] !== '') {
            $user_locale = $_COOKIE['interface_language'];
        } elseif ($email) {
            $user = TDOUser::getUserForUsername($email);
            if ($user) {
                $user_locale = TDOUser::getLocaleForUser($user->userId());
            }
        } else {
            $user_locale = DEFAULT_LOCALE;
        }
        if (!$user_locale || $user_locale == '') {
            $user_locale = TDOInternalization::getUserBestMatchLocale();
        }
        if ($user_locale === DEFAULT_LOCALE) {
            return '';
        }
        $user_locale = mb_strtolower($user_locale);
        $user_locale = str_replace('_', '-', $user_locale) . '-';
        return $user_locale;
    }
};


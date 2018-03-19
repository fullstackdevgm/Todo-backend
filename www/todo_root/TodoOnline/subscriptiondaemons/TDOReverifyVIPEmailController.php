<?php

include_once('TDODaemonConfig.php');
include_once('TodoOnline/base_sdk.php');
include_once('TDODaemonLogger.php');
include_once('TDODaemonController.php');

class TDOReverifyVIPEmailController extends TDODaemonController
{
	function __construct($daemonID = '')
	{
		parent::__construct($daemonID);
	}

	public function processAboutToExpireVIPAccounts()
	{
		//Get all VIP accounts that are about to expire (2 weeks from now)
		
		//process all VIP accounts and send emails
		
		$this->log("---------------------------------------------------");
		
		$whitelistedDomains = explode(",", PROMO_CODE_WHITELISTED_DOMAINS);
		foreach ($whitelistedDomains as $domain)
		{
			// NOTE: getAboutToExpireVIPAccountIDsInDomain does NOT return
			// subscriptions that are part of a team account.
			$vipAccountIDs = TDOSubscription::getAboutToExpireVIPAccountIDsInDomain($domain);
			foreach ($vipAccountIDs as $userid)
			{
				// Make sure this user has not already been sent a verification
				// email so we don't send too many emails.
				if (TDOEmailVerification::getVerificationIdForUserId($userid) != false)
					continue;
				
				$user = TDOUser::getUserForUserId($userid);
				$email = $user->username();
				$userDisplayName = $user->displayName();
				
				$emailVerifyURL = NULL;
                
				$emailVerification = new TDOEmailVerification();
				$emailVerification->setUserId($userid);
				$emailVerification->setUsername($email);
				
				if($emailVerification->addEmailVerification())
				{
					// This domain name is hard-coded because the subscription
					// daemon running on auth.appigo.com would put the wrong
					// URL: https://auth.appigo.com/?verifyemail...
					$emailVerifyURL = "https://www.todo-cloud.com/?verifyemail=true&verificationid=".$emailVerification->verificationId();
					
					if(TDOMailer::sendVIPEmailReverificationEmail($userDisplayName, $email, $emailVerifyURL))
					{
						error_log("\nemail was sent to: ". $email ."\n");//return true;
					}
					else
						error_log("TDOEmailVerification failed to send VIP reverification email for user ". $email);
				}
				else
					error_log("TDOEmailVerification failed to add email reverification for user ".$email);
			}
		}
	}
	
	
}

?>
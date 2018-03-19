<?php

define('VIP_EXTENSION_INTERVAL', 'P1Y');

$message = '';
$reload_page = false;

if(isset($_GET['verificationid']))
{
    $verificationId = $_GET['verificationid'];
	
	// Get the userID from the verification record
	$verificationObj = TDOEmailVerification::getEmailVerificationForVerificationId($verificationId);
	if (!empty($verificationObj))
	{
		$user = TDOUser::getUserForUserId($verificationObj->userId());
		if (!empty($user))
		{
			// If we make it to here, the verification matches an existing user,
			// so mark the user as verified.
			$user->setEmailVerified(1);
			$username = $verificationObj->username();
			
			if($user->updateUser())
			{
                $message = '<p>' . sprintf(_('Your email address (%s) has been verified.'), $username) . '</p>';
                $reload_page = true;
				
				if($user->emailOptOut() == 0)
					AppigoEmailListUser::updateListUser($verificationObj->username(), EMAIL_CHANGE_SOURCE_TODO_CLOUD);
				
				$verificationObj->deleteEmailVerification();
			}
			else
			{
                $message = '<p>' . _('Unable to verify email address') . '</p>';
			}
			
			//update VIP user if matching domain
			if (TDOUtil::isEmailAddressInWhiteList($username))
			{
				$userId = $user->userId();
				
				$subscription = TDOSubscription::getSubscriptionForUserID($userId);
				
				$subscriptionId = $subscription->getSubscriptionID();
				$expirationDate = new DateTime('now');
				$newExpirationDate = $expirationDate->add(new DateInterval(VIP_EXTENSION_INTERVAL));
				$newExpirationTimestamp = $newExpirationDate->format('U');
				
				$subscriptionType = $subscription->getSubscriptionType();
				$subscriptionLevel = $subscription->getSubscriptionLevel();
				
				if (TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionId, $newExpirationTimestamp, $subscriptionType, $subscriptionLevel))
				{
                    $changeDescription = sprintf(_('New Expiration Date: %s, VIP user'), date("D d M Y", $newExpirationTimestamp));
					TDOUser::logUserAccountAction($userId, $userId, USER_ACCOUNT_LOG_TYPE_VIP_FREE_PREMIUM_ACCOUNT, $changeDescription);

                    $message .= '<p style="width:300px">' . _('You have received a one-year Todo Cloud Premium account') . '</p>';
					
					if ($GLOBALS['isIOS'] != true)
					{
						// Don't display this message if on iOS (since it's a bit confusing there anyway)
                        $message .= '<p style="width:300px">' . sprintf(_('You can find more details about your account %shere%s'), '<a style="text-decoration:underline" href="/?appSettings=show&option=subscription">', '</a>');
					}
					
					if($user->emailOptOut() == 0)
						AppigoEmailListUser::updateListUser($verificationObj->username(), EMAIL_CHANGE_SOURCE_TODO_CLOUD);
					$verificationObj->deleteEmailVerification();
				}
				else
				{
                    $message .= '<p>' . _('Unable to extend account for VIP user') . '</p>';
				}
			}
		}
	}
    else
    {
        $message = '<p>' . _('Unable to verify email address (verification code not valid)') . '</p>';
    }
}
else
{
    $message = '<p>' . _('Unable to verify email address (missing verification code)') . '</p>';
}
?>

<script type="text/javascript">displayVerifyEmailModal('<?php echo $message; ?>', '<?php echo $reload_page; ?>') </script>

<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/php/SessionHandler.php');
include_once('TodoOnline/DBConstants.php');

$message = '';

if (isset($_GET['email']) && isset($_GET['optOutKey']))
{
	$optOutKey = $_GET['optOutKey'];
	$email = $_GET['email'];
	
	$userAccount = TDOUser::getUserForUsername($email);
	
	if (!empty($userAccount))
	{
		$userID = $userAccount->userId();
		
		$calculatedMD5 = TDOUtil::computeOptOutKeyForUser($userID, $email);
		
		if ($calculatedMD5 == $optOutKey)
		{
			$userAccount->setEmailOptOut(true);
			$result = $userAccount->updateUser();
			
			if ($result)
			{
				$message = "<p>" . sprintf(_('Your email address (%s) has been unsubscribed.'),$email)."</p>";
				
				// Log this as an action on the user's account
                $changeDescription = _('User opted out of marketing emails');
				TDOUser::logUserAccountAction($userID, $userID, USER_ACCOUNT_LOG_TYPE_EMAIL_OPT_OUT, $changeDescription);
			}
			else
			{
                $message = "<p>" . _('There was an error unsubscribing your email address. Please try again later.') . "</p>";
			}
		}
		else
		{
            $message = "<p>" . _('Please check the unsubscribe link and try again.') . "</p>";
		}
	}
	else
	{
        $message = "<p>" . _('Unknown email address.') . "</p>";
	}
	
}
else
{
    $message = "<p>" . _('Unable to unsubscribe email address (missing email or optOutKey).') . "</p>";
}

?>

<script type="text/javascript">
	var header = "Unsubscribe";
	var body = '<?php echo $message; ?>';
	var footer = '<div class="button" onclick="top.location=\'.\'"><?php _e('OK'); ?></div>';

	displayModalContainer(body, header, footer);
</script>

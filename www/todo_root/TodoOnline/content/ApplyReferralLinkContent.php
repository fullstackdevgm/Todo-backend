
<script type="text/javascript" src="<?php echo TP_JS_PATH_APPLY_REFERRAL_LINK_FUNCTIONS; ?>"></script>

<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/php/SessionHandler.php');
include_once('TodoOnline/DBConstants.php');
	

if($session->isLoggedIn()) 
{
	$userID = $session->getUserId();
	
	$errorMessage = NULL;
	$silentlyFail = true;
	
	if(isset($_GET['referralcode']))
	{
		$referralCode = $_GET['referralcode'];
		
		// Make sure that this referral code is not owned by the logged-in user
		$myReferralCode = TDOUserSettings::referralCodeForUserID($userID);
		
		if ($referralCode != $myReferralCode)
		{
			// Make sure that the referral code is a valid referral code
			if (TDOReferral::isValidReferralCode($referralCode))
			{
				// If a referral record already exists for this user, do nothing
				$existingReferralRecord = TDOReferral::referralRecordForUserID($userID);
				if (empty($existingReferralRecord))
				{
					// Check to see if this user's account was just barely created. If it
					// was not, they are not eligible to use a referral code.
					$userRecord = TDOUser::getUserForUserId($userID);
					
					$now = time();
					$accountCreationTimestamp = $userRecord->creationTimestamp();
					$referralWindow = 60 * 2; // 2 minutes
					
					if ( ($now >= $accountCreationTimestamp) && (($now - $accountCreationTimestamp) < $referralWindow))
					{
						if (TDOReferral::recordNewReferral($userID, $referralCode))
						{
							$silentlyFail = false; ?>
							<script type="text/javascript">displayReferralLinkSuccessModal();</script>
                        <?php
						}
						else
						{
							$errorMessage = _('Unable to record referral link.');
						}
					}
					else
					{
						$errorMessage =  _('The referral link you attempted to use is for new accounts only.') . '<br/><br/>';
						$errorMessage .= _('If you&#39;d like to share your own referral link with others, please') . '<br/>';
                        $errorMessage .= sprintf(_('visit the %sReferrals%s page in your account settings.') . '<br/>', '<a href="?appSettings=show&option=referrals" style="text-decoration:underline;">', '</a>');
					}
				}
			}
			else
			{
				$errorMessage = _('You attempted to use a referral link that is not valid.');
			}
		}
		else
		{
			$errorMessage = sprintf(_('Your referral link is ready to share!%sClick %shere%s to see the different ways you can share it.'), '<br/><br/>', '<a href="?appSettings=show&option=referrals" style="text-decoration:underline;">', '</a>');
		}
	}

    if($errorMessage != NULL) : ?>
        <script type="text/javascript">displayReferralLinkErrorModal('<?php echo $errorMessage; ?>');</script>
    <?php else :
    	if ($silentlyFail) :
		// If we reach here, the user already has a referral code applied to
		// their account and we should just redirect to the main Todo Cloud page.
            ?>
            <script type="text/javascript">top.location = '.';</script>
        <?php
	    endif;
    endif;
}


?>


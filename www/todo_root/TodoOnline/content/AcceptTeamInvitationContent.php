<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/php/SessionHandler.php');
include_once('TodoOnline/DBConstants.php');
	
?>
<script type="text/javascript" src="<?php echo TP_JS_PATH_INVITATION_FUNCTIONS; ?>" ></script>
<?php


$userID = $session->getUserId();
$teamID = NULL;
$message = NULL;

if(isset($_GET["invitationid"]))
{
    $invitationID = $_GET["invitationid"];
	
	// Check the current user's eligibility. If they already have a paid
	// subscription through IAP or Google Play, we are not allowed to change
	// their subscription at all because of our obligation to Apple/Google to
	// provide the original subscription through the duration that it was
	// initially offered.
	
	$isIAP = false;
	if (TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userID))
	{
		// As of Todo Cloud Web 2.4, users that are paying with IAP can now
		// participate in a Todo for Business team.
		$isIAP = true;
//		$message = "Your account cannot join a team until the current In-App Purchase subscription period ends.<br/>If you would still like to join this team, please cancel your In-App Purchase subscription. When the current subscription period has completed, you can then join the team. Alternatively, please create a separate account to join this team.";
	}
	
	// Check for an existing invitation
	$invitationInfo = TDOTeamAccount::invitationInfoForInvitationID($invitationID);
	if (!$invitationInfo)
	{
		$message =  _('The invitation is no longer valid or no longer exists.');
	}
	else
	{
		// Check for an existing team
		$teamID = $invitationInfo['teamid'];
		$teamName = TDOTeamAccount::teamNameForTeamID($teamID);
		if (!$teamName)
		{
			$message =  _('The team you were invited to no longer exists.');
		}
		else {
            $membershipType = $invitationInfo['membershipType'];
            if ($invitationInfo['invited_userid'] && $invitationInfo['invited_userid'] !== '' && $userID !== $invitationInfo['invited_userid']) {
                $message = _('The invitation is no longer valid or no longer exists.');
            } else {
                if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN) {
                // If the code makes it here, it means we're good to offer them
                // the option to join the team! YAY!

                echo "<script type=\"text/javascript\">displayJoinTeamModal('" . $invitationID . "', '" . htmlspecialchars($teamName, ENT_QUOTES) . "', 'admin', false);</script>";
            } else {
                $subscription = TDOSubscription::getSubscriptionForUserID($userID);
                if ($subscription) {
                    // Don't let the user join a new team until they've left the one
                    // they already belong to (if they do).
                    $existingTeamID = $subscription->getTeamID();
                    if ($existingTeamID) {
                        $teamName = TDOTeamAccount::teamNameForTeamID($existingTeamID);
                        $message = sprintf(_('Your account already belongs to a team. If you would like to join a new team, please leave the &quot;%s&quot; team in your account settings.'), $teamName);
                    } else {
                        // If the user IS IAP-based, go ahead and let them join
                        // the team. We will email them instructions about how
                        // to discontinue their IAP auto-renewing subscription
                        // so their account can be paid for entirely by the team
                        // account during the next personal IAP renewal.
                        if ($isIAP == true) {
                            echo "<script type=\"text/javascript\">displayJoinTeamModal('" . $invitationID . "', '" . htmlspecialchars($teamName, ENT_QUOTES) . "', 'member', false);</script>";
                        } else {
                            // Check to see how much time is left on the user's
                            // existing subscription. If they have more than 14
                            // days, ask them what they want to do about it:
                            //
                            // Option 1) Donate their remaining time to the team
                            // Option 2) Receive a promo code via email to give to someone else
                            $teamName = TDOTeamAccount::teamNameForTeamID($teamID);
                            $now = time();
                            $forteenDaysFromNow = $now + 1209600;
                            if ($subscription->getExpirationDate() > $forteenDaysFromNow) {
                                // Calculate months left in the expiration so we
                                // can show it.
                                $currentExpirationTimestamp = $subscription->getExpirationDate();
                                $now = time();
                                $timeLeftInSeconds = $currentExpirationTimestamp - $now;
                                // Divide by 30 days and be generous in rounding
                                // up. Hopefully the user will give a gift code
                                // to someone who wouldn't have otherwise used
                                // Todo Cloud and that person will become a
                                // life-long Todo Cloud customer.
                                $monthsLeft = ceil($timeLeftInSeconds / 2592000);

                                // You've got X month(s) left of your Todo Cloud
                                // subscription. Join the team and:
                                //
                                //		1) Donate your X month(s) to the 'Plasma Tech' team account
                                //		2) Receive a promo code in your email for X month(s)
                                //
                                //		cancel
                                //
                                //

                                $emailAddress = TDOUser::usernameForUserId($userID);

                                echo "<script type=\"text/javascript\">displayDonateOrPromoCode('" . $invitationID . "', '" . htmlspecialchars($teamName, ENT_QUOTES) . "', '" . htmlspecialchars($emailAddress, ENT_QUOTES) . "', " . $monthsLeft . ");</script>";
                            } else {
                                // If the code makes it here, it means we're
                                // good to let them join the team! YAY!

                                echo "<script type=\"text/javascript\">displayJoinTeamModal('" . $invitationID . "', '" . htmlspecialchars($teamName, ENT_QUOTES) . "', 'member', false);</script>";
                            }
                        }
                    }
                } else {
                    $message = _('Your account is not valid (no subscription record). Please contact the support team for assistance.');
                }
            }
            }
		}
	}
}
	
	/*
	 // Code to wipe out any auto-renewing through Stripe
	 $stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);
	 if ($stripeCustomerID)
	 {
	 // Wipe out the Stripe User info because the account will
	 // now be paid for by the team.
	 if (!TDOSubscription::deleteStripeCustomerInfoForUserID($userID))
	 {
	 // This is a soft fail. Continue on with other stuff.
	 error_log("User joined a team, but we were not able to remove their Stripe information (userid: $userID, stripeCustomerID: $stripeCustomerID)");
	 }
	 }
	 */
		

$ios = false;
if($GLOBALS['isIOS'])
    $ios = true;
	
	if ($message)
	{
?>

<script type="text/javascript">
var headerHTML = '<?php _e('Team Invitation Error'); ?>';
var bodyHTML = '<div style="width:480px;"><p><?php echo $message; ?></p></div>';
var footerHTML = '<div class="button" onclick="cancelJoinTeam()"><?php _e('Ok'); ?></div>';

displayModalContainer(bodyHTML, headerHTML, footerHTML);
document.getElementById('modal_overlay').onclick = null;

</script>

<?php
	}

?>


<script type="text/javascript">

function displaySubscriptionInvitationModal(message, cancelButtonTitle, declineLocation, invitationID)
{
    var header = '<?php _e('Join Team Subscription'); ?>';
    var body = message;

    var footer = '';
    
    if(invitationID && invitationID.length > 0)
    {
        footer += '<div class="button" onclick="top.location=\'?method=acceptTeamSubscriptionInvitation&invitationid=' + invitationID + '\'"><?php _e('Accept'); ?></div>';
    }
    footer += '<div class="button" onclick="top.location=\'' + declineLocation + '\'">' + cancelButtonTitle + '</div>';

    displayModalContainer(body, header, footer);
}

</script>


<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/php/SessionHandler.php');



if($session->isLoggedIn()) 
{
	$userID = $session->getUserId();
	$message = '';
	$invitationID = '';
    if(isset($_GET["invitationid"]))
    {
        $invitationID = $_GET["invitationid"];
		
//		$subscription = TDOSubscription::getSubscriptionForInvitationID($invitationID, true); // uncomment this if we need to have information about the invitation, such as the email address or the date/time when it was sent out
		$subscription = TDOSubscription::getSubscriptionForInvitationID($invitationID);
		if ($subscription)
		{
			if ($subscription->getMemberUserID())
			{
				// This is a sanity check to make sure that the subscription
				// hasn't already been consumed by someone else.  The system
				// should never actually be able to get into this situation, but
				// just in case, we'll check here.
                $message = _('This subscription is no longer available.');
				$invitationID = '';
			}
			else
			{
				// We've got a valid subscription.  Now, check to see if the current
				// user already has a paid subscription and if they do, present a
				// bit different information here.
				
				// TODO: Also check to see if the invitation we're viewing is from a subscription we own
				
				$ownedSubscription = TDOSubscription::getSubscriptionForMemberUserID($userID);
//				if ( (!empty($ownedSubscription)) && ($ownedSubscription->getOwnerUserID() == $userID) && ($ownedSubscription->isActiveSubscription()) )
//				{
//					// The user already has an active subscription.  Decide what to
//					// present them with here!
//				}
//				else
//				{
//					// The user doesn't already have an active subscription, so just
//					// present them with something here to let them join this group
//					// subscription.
//				}
				
				
				$fromUserName = trim(TDOUser::fullNameForUserId($subscription->getOwnerUserID()));
				if(!$fromUserName)
					$fromUserName = TDOUser::usernameForUserId($subscription->getOwnerUserID());
				
				$expirationDate = date("D M j G:i:s T Y", $subscription->getExpirationDate());

                $message = sprintf(_('%s has paid for your subscription, which expires on %s %sClick &#39;Accept&#39; to begin your subscription now.'), $fromUserName, $expirationDate, '<br/><br/>');
			}
		}
		else
		{
			// For what ever reason, the invitation/subscription is no longer
			// available!
            $message = _('This subscription is no longer available.');
			$invitationID = '';
		}
    }
    else
    {
		// You must specify an Invitation ID for this to work.  We intentionally
		// NEVER pass a Subscription ID to someone we don't trust/know during
		// the invitation process.  If the subscription can't be found during
		// this process, tough luck for the user.  :)
        $message = _('No subscription invitation specified');
    }
	
	$cancelButtonTitle = '';
	$declineLocation = '';
	if (empty($invitationID))
	{
		$cancelButtonTitle = _('Close');
		$declineLocation = ".";
	}
	else
	{
        $cancelButtonTitle = _('Decline');
		$declineLocation = "?method=deleteTeamSubscriptionInvitation&invitationid=$invitationID";
	}


    echo "<script type=\"text/javascript\">displaySubscriptionInvitationModal('".$message."', '".$cancelButtonTitle."', '".$declineLocation."', '".$invitationID."');</script>";
} 


?>

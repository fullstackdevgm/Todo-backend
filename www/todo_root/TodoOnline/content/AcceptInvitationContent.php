<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/php/SessionHandler.php');
include_once('TodoOnline/DBConstants.php');

$userid = $session->getUserId();
$message = NULL;
$listid = NULL;
$joinedSuccessfully = false;
$showPremium = false;

if(isset($_GET["invitationid"]))
{
    $invitationid = $_GET["invitationid"];

    $invitation = TDOInvitation::getInvitationForInvitationId($invitationid);
    if($invitation)
    {
        $listid = $invitation->listId();
        $email = $invitation->email();
        
        if($listid == INVITE_ONLY_LIST)
        {
            // this was an invite that was only intended to get someone in the system
            // but not to actually add them to a list.  Simply delete the invitation
            // now because they have already made it in.
            TDOInvitation::deleteInvitation($invitationid);
            $message = _('Your invitation has been processed. Welcome to Todo Cloud!');
        }
        else
        {
            //make sure this is an email invitation, not a facebook request
            if($listid && $email)
            {
                //make sure the list exists and isn't deleted
                $list = TDOList::getListForListid($listid);
                if(!empty($list) && $list->deleted() == false)
                {
                    
                    $role = $invitation->membershipType();

// This was for limiting an unpaid user to 2 shared lists, but we're now letting them join as many as they want
//                    if(TDOUser::userCanAddSharedList($userid) == true)
//                    {
                        if(TDOList::shareWithUser($listid, $userid, $role) == true)
                        {
                            TDOInvitation::deleteInvitation($invitationid);
                            $listName = TDOList::getNameForList($listid);
                            if($listName)
                            {
                                $joinedSuccessfully = true;
                                $message = _('Successfully joined') . ' "' . $listName . '".';

                            }
                        }
                        else
                            $message = _('Failed to join list. You may already be a member of this list.');
//                    }
//                    else
//                    {
//                        $message = "You have reached the allowed number of shared lists for a regular account. Want unlimited shared lists? Click below to upgrade to a premium account!";
//                        $showPremium = true;
//                    }
                }
                else
                {
                    $message = _('Sorry, that list no longer exists.');
                }
            }
            else
            {
                $message = _('This invitation has been removed.');
            }
        }
    }
    else
    {
        $message = _('This invitation has been removed.');
    }
}
else
{
    $message =  _('No invitation to view.');
}

$ios = false;
if($GLOBALS['isIOS'])
    $ios = true;
?>

<script type="text/javascript" src="<?php echo TP_JS_PATH_INVITATION_FUNCTIONS; ?>" ></script>
<script type="text/javascript">




displayAcceptedEmailInvitationModal(<?php echo "'".$listid."' , '". htmlspecialchars($message, ENT_QUOTES) . "', '" . $joinedSuccessfully ."', '" . $showPremium . "', '" . $ios . "'"; ?>);

</script>

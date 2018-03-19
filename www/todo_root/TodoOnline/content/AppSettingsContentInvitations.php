<div class="setting_options_container">

<?php
    $toolbarButtons = array();
    
	if($session->isLoggedIn())
	{
        
		$userId = $session->getUserId(); ?>
        <div class="button_toolbar">
            <div class="button" onclick="displaySendInvitationsModal()"><?php _e('Invite People'); ?></div>
        </div>
        <?php
        $invitations = TDOInvitation::getInvitations($userId);
        
        if(!$invitations): ?>
            <br/>
            <br/>
            <div class="text-center"><?php _e('You have no pending invitations'); ?></div>';
        <?php else : ?>

            <h3><?php echo _('Pending Invitations') . ' (' . count($invitations) . ')'; ?></h3>
            <br/>
            <?php
            foreach($invitations as $invitation) :?>
                <div class="setting">
                <?php
                $invitationId = $invitation->invitationId();
                $listid = $invitation->listId();
//                $listName = TDOList::getListForListid($listid)->name();
                $userThatInvited = $invitation->userId();
                $inviter = TDOUser::displayNameForUserId($invitation->userId());
                $timeSent = $invitation->timestamp();
                $date = TDOUtil::humanReadableStringFromTimestamp($timeSent);
                
                $invitedName = $invitation->email();
                if(!empty($invitedName)) :
                    $invitedString = $date . ' - ' . $inviter . ' ' . _('invited') . ' ' . $invitedName;
                    if ($listid == INVITE_ONLY_LIST) {
                        $invitedString .= ' ' . _(' to join Todo Cloud');
                    } else {
                        $listName = TDOList::nameForListId($listid);
                        if (!empty($listName)) {
                            $invitedString .= ' ' . _('to the list') . ' "' . $listName . '"';
                        } else {
                            $invitedString .= ' ' . _('to a list');
                        }
                    }
                    
                    //echo '<td>'.$invitedString.'</td>';
                    ?>
                    <div style="width:70%;padding-left:10px;"><?php echo _('Sent to') . ' ' . $invitedName . ' ' . $date; ?></div>
                    <div style="margin-left:50px;" class="button" onclick="resendEmail('<?php echo $invitationId; ?>')"><?php _e('Resend'); ?></div>
                    <div class="button" onclick="deleteInvitation('<?php echo $invitationId; ?>')"><?php _e('Delete'); ?></div>
                <?php
                endif;
                //else this is a facebook invitation!!!
                ?>
                </div>
            <?php
            endforeach;
        endif;

        //echo '<br/>';
        //echo '<center><div class="button" onclick="displaySendInvitationsModal()">Invite Someone to Todo Cloud</div></center>';
    } else {
        include_once('TodoOnline/content/ContentToolbarButtons.php');
        ?>
        <h3><?php _e('You must log in to see this page'); ?></h3>
    <?php
    }
	
    ?>

<div id="fb-root"></div>
<?php
    include_once('TodoOnline/ajax_config.html');
    ?>

<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>
<script>

function resendEmail(invitationId)
{
    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
    if(!ajaxRequest)
    {
        return false;
    }
    // Create a function that will receive data sent from the server
    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    history.go(0);
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        alert("<?php _e('Unable to resend invitation.'); ?>");
                    }
                }
            }
            catch(e)
            {
                alert("<?php _e('Unknown response from server') ;?>");
            }
        }
    }
    var params = "method=resendInvite&invitationid=" + invitationId;
    
    ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);
}

function deleteInvitation(invitationId)
{
    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
    if(!ajaxRequest)
    {
        return false;
    }
    // Create a function that will receive data sent from the server
    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    history.go(0);
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        alert("<?php _e('Unable to delete invitation.'); ?>");
                    }
                }
            }
            catch(e)
            {
                alert("<?php _e('Unknown response from server') ;?>");
            }
        }
    }
    var params = "method=deleteInvite&invitationid=" + invitationId;
    
    ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);
    
}


</script>




<script type="text/javascript">
function submitForm(id)
{
    var element = "roleform".concat(id);
    document.getElementById(element).submit();
}
</script>
</div>
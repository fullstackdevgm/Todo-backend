
<?php
    
 	if(isset($_COOKIE['TodoOnlineListId']))
	{
		$selectedlistid = $_COOKIE['TodoOnlineListId'];
        if(TDOList::userCanViewList($selectedlistid, $session->getUserId()))
        {
            $currentUserRole = TDOList::getRoleForUser($selectedlistid, $session->getUserId());
            $listName = TDOList::getNameForList($selectedlistid);
            if(!empty($listName))
            {
                echo "<h1>$listName</h1>";
                echo "<input type=\"hidden\" id=\"member_page_listname\" value=\"$listName\">";
                echo "<input type=\"hidden\" id=\"member_page_listid\" value=\"$selectedlistid\">";
                echo "<input type=\"hidden\" id=\"member_page_userid\" value=\"".$session->getUserId()."\">";
//                echo "<input type=\"hidden\" id=\"member_page_user_role\" value=\"$currentUserRole\">";
            
                echo '<div id="share_buttons_container" class="button_toolbar"></div>';

                echo '<h3>' . _('Members') . '</h3>';
                echo '<div id="list_members_container"></div>';
                echo '<h3>' . _('Invitations') . '</h3>';
                echo '<div id="list_invitations_container"></div>';
                echo '<div id="fb-root"></div>';

                include_once('TodoOnline/ajax_config.html');
                include('Facebook/config.php');


                echo '<script type="text/javascript">';
                echo "var appid = '$fb_app_id';";
                echo "var curUserRole = $currentUserRole;";
                echo '</script>';
                echo '<script type="text/javascript" src="' . TP_JS_PATH_LIST_MEMBER_FUNCTIONS . '" ></script>';
                echo '<script type="text/javascript">';
                //Load the share buttons
                if($currentUserRole == LIST_MEMBERSHIP_OWNER)
                {
                    echo 'loadShareButtons();';
                }
                //Then load the members section
                echo 'loadMembersSection();';

                //Then load the invitations section
                echo 'loadInvitationsSection();';
                echo '</script>';
            }
            else
            {
                _e('The selected list was not found');
            }
        
        }
        else
        {
            _e('You do not have access to this page because you are not a member of this list');
        }
        
    }
    else
    {
        _e('No list selected');
    }
?>




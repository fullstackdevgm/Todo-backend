<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	

if($method == "changeRole")
{
	if(!isset($_POST['listid']))
	{
		error_log("HandleListMemberMethods.php called and missing a required parameter: listid");
		echo '{"success":false}';
		return;
	}
	if(!isset($_POST['uid']))
	{
		error_log("HandleListMemberMethods.php called and missing a required parameter: uid");
		echo '{"success":false}';
		return;
	}
	if(!isset($_POST['role']))
	{
		error_log("HandleListMemberMethods.php called and missing a required parameter: role");
		echo '{"success":false}';
		return;
	}
	$listid = $_POST['listid'];
	
	// If this is a team-owned list, make sure that the team subscription is NOT
	// expired. We do not allow expired team accounts to make changes to team-
	// owned lists.
	$teamID = TDOList::teamIDForList($listid);
	{
		if ($teamID)
		{
			$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
			if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
			{
				error_log("Method changeRole found that team subscription is expired for team-owned list: " . $listid);
				echo '{"success":false}';
				return;
			}
		}
	}
	
    $currentUser = $session->getUserId();
    $changedUser = $_POST['uid'];
    if($changedUser == 'current')
        $changedUser = $currentUser;
    
    $newRole = $_POST['role'];
    
	if(TDOList::getRoleForUser($listid, $currentUser) != LIST_MEMBERSHIP_OWNER && ($currentUser != $changedUser || $newRole != 'remove'))
	{
		error_log("HandleListMemberMethods.php access violation.  User not authorized: ");
		echo '{"success":false}';
		return;
	}

    $oldRole = TDOList::getRoleForUser($listid, $changedUser);
    if($oldRole === false)
    {
        error_log("HandleListMemberMethods.php attemt to edit non-member");
        echo '{"success":false}';
        return;
    }
    //Don't allow anyone to remove the last owner from a list
    if($oldRole == LIST_MEMBERSHIP_OWNER && TDOList::getOwnerCountForList($listid) <= 1)
    {
//        error_log("HandleListMemberMethods.php attempt to remove last owner from list");
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('lastowner'),
        ));
        return;
    }

    if($newRole == 'remove')
    {
        if(TDOList::removeUserFromList($listid, $changedUser))
        {
            $userName = TDOUser::fullNameForUserId($changedUser);
            TDOChangeLog::addChangeLog($listid, $session->getUserId(), $changedUser, $userName, ITEM_TYPE_USER, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
            
            echo '{"success":true}';
            return;
            
        }
        else
        {
            error_log("HandleListMemberMethods.php could not remove user from list");
            echo '{"success":false}';
            return;
        }
    }
    elseif(TDOList::changeUserRole($listid, $changedUser, $newRole) == false)
    {
            error_log("HandleListMemberMethods.php could not change user role");
            echo '{"success":false}';
            return;
    }
    else
    {
        $jsonChangedValues = '{ "old-role":"'.$oldRole.'", "role":"'.$newRole.'" }';
        $userName = TDOUser::fullNameForUserId($changedUser);
        TDOChangeLog::addChangeLog($listid, $session->getUserId(), $changedUser, $userName, ITEM_TYPE_USER, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonChangedValues);        
    }

	echo '{"success":true}';
}
elseif($method == "getMembersAndRoles" || $method == "getMembers")
{
    if(!isset($_POST['listid']))
    {
        error_log("getMembersAndRoles called missing required parameter");
        echo '{"success":false}';
        return;
    }
    
    $listid = $_POST['listid'];
    $userid = $session->getUserId();
    
    if(TDOList::userCanViewList($listid, $userid) == false)
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('You do not have permission to view this page'),
        ));
        return;
    }
    
    $jsonResponse = array();
    if($method == "getMembersAndRoles")
    {
        //Don't allow non-paid users to share lists
        if(TDOSubscription::getSubscriptionLevelForUserID($userid) < 2)
        {
            $jsonResponse['sharelimit'] = true;
        }
    }
    
    $orderByMembership = ($method == "getMembersAndRoles");
    $peopleArray = TDOList::getPeopleAndRolesForlistid($listid, $orderByMembership);
    if(empty($peopleArray))
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('Failed to get users for list'),
        ));
        return;
    }
    
    $jsonResponse['success'] = true;
    
    $peopleJSON = array();
    foreach($peopleArray as $uid=>$role)
    {
        if($uid)
        {
            $user = TDOUser::getUserForUserId($uid);
            if(!empty($user))
            {
                $name = $user->displayName();
                
                if(!empty($name))
                {
                    $userJSON = array();
                    $userJSON['name'] = $name;
                    $userJSON['id'] = $uid;
                    $userJSON['email'] = TDOUser::usernameForUserId($uid);
    //                $fbId = TDOUser::facebookIdForUserId($uid);
    //                if($fbId)
    //                {
    //                    $userJSON['imgurl'] = 'https://graph.facebook.com/'.$fbId.'/picture';
    //                }
                    $imgUrl = $user->fullImageURL();
                    if(!empty($imgUrl))
                    {
                        $userJSON['imgurl'] = $imgUrl;
                    }

                    $userJSON['role'] = $role;
                    
                    if($uid == $session->getUserId())
                    {
                        $userJSON['me'] = true;
                        array_unshift($peopleJSON, $userJSON);
                    }
                    else
                    {
                        $peopleJSON[] = $userJSON;
                    }
                }
            }
        }

    }
    
    $jsonResponse['members'] = $peopleJSON;
    $jsonResponse['myrole'] = TDOList::getRoleForUser($listid, $session->getUserId());
    echo json_encode($jsonResponse);

}
elseif($method == "getInvitationsForList")
{
    if(!isset($_POST['listid']))
    {
        error_log("getInvitationsForList called missing required parameter");
        echo '{"success":false}';
        return;
    }
    $listid = $_POST['listid'];
    $userid = $session->getUserId();
    
    if(TDOList::userCanViewList($listid, $userid) == false)
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('You do not have permission to view this page'),
        ));
        return;
    }
   
    $invitations = TDOInvitation::getInvitations(NULL, $listid);
    if($invitations === false)
    {
        echo '{"success":false}';
        return;
    }
    
    $jsonResponse = array();
    $jsonResponse['success'] = true;
    
    $invitationsArray = array();
    foreach($invitations as $invitation)
    {
        $invitationProperties = $invitation->getPropertiesArray();
        $invitationsArray[] = $invitationProperties;
        
    }
    $jsonResponse['invitations'] = $invitationsArray;

    echo json_encode($jsonResponse);

}

?>
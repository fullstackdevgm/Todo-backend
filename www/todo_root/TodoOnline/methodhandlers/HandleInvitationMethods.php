<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	

    
    //All methods in this file require invitationid to be set!
    if($method == "deleteInvite" || $method == "acceptInvite" || $method == "modifyFBInvite" || $method == "resendInvite" || $method == "updateInvite")
    {
        if(!isset($_POST['invitationid']))
        {
            error_log("HandleInviteActions.php called and missing a required parameter: invitationid");
            echo '{"success":false}';
            return;
        }

        $invitationId = $_POST['invitationid'];
            
        $invitation = TDOInvitation::getInvitationForInvitationId($invitationId);
        if(empty($invitation))
        {
            //deleteInvitations will sometimes intentionally get called with a bad invitation id if there's a facebook
            //request that we can't find an invitation for. In this case, just remove the facebook request and call it good.
            if($method == "deleteInvite" && isset($_POST['requestid']))
            {
                $fbid = TDOUser::facebookIdForUserId($session->getUserId());
                if($fbid && TDOFBUtil::removeFacebookRequest($_POST['requestid'], $fbid))
                {
                    echo '{"success":true}';
                }
                else
                {
                    echo '{"success":false}';
                }
            }
            else
            {
                error_log("HandleInviteActions.php could not find requested invite: ".$invitationId);
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('The invitation has been removed'),
                ));
            }
            return;
        }
    
    
        //These methods all require the user to be the owner of the list!!
        if($method == "modifyFBInvite" || $method == "resendInvite" || $method == "updateInvite")
        {
            $listid = $invitation->listId();
            if(empty($listid))
            {
                error_log("HandleInviteActions.php could not find list for invitation");
                echo '{"success":false}';
                return;
            }
            
            if($listid != INVITE_ONLY_LIST)
            {
                if(TDOList::getRoleForUser($invitation->listId(), $session->getUserId()) != LIST_MEMBERSHIP_OWNER)
                {
                    error_log("HandleInviteActions.php access violation.  User not authorized: ");
                    echo '{"success":false}';
                    return;
                }
            }
            
             //This is used when we resend a facebook invitation
            if($method == "modifyFBInvite")
            {
                if(!isset($_POST['requestid']) || !isset($_POST['fbuserid']))
                {
                    echo '{"success":false}';
                    return;
                }
            
                $newrequestid = $_POST['requestid'];
                $fbuserid = $_POST['fbuserid'];
            
                if($invitation->fbUserId() != $fbuserid)
                {
                    error_log("HandleInviteActions.php could not find requested invite: ".$invitation->invitationId());
                    echo '{"success":false}';
                    return;
                }
                
                //put the new request id in the database and delete the old request
                $oldrequestid = $invitation->fbRequestId();
                $invitation->setFBRequestId($newrequestid);
                $invitation->setTimestamp(time());
                
                if($invitation->updateInvitation() == false)
                {
                    error_log("HandleInviteActions.php failed to update invitation");
                    echo '{"success":false}';
                    return;
                }
                
                $invitedName = TDOFBUtil::getFBUserNameForFBUserId($fbuserid);
                    
                $session = TDOSession::getInstance();
                TDOChangeLog::addChangeLog($invitation->listId(), $session->getUserId(), $invitation->invitationId(), $invitedName, ITEM_TYPE_INVITATION, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB);
                
                if($oldrequestid)
                {
                    TDOFBUtil::removeFacebookRequest($oldrequestid, $fbuserid);
                }
                echo '{"success":true}';
                
            } //end modifyFBInvite method
            
            //This is used to resend an email invitation
            elseif($method == "resendInvite")
            {
                $emailstring = $invitation->email();
                $userid = $session->getUserId();
                
                if($listid != INVITE_ONLY_LIST)
                {
                    $list = TDOList::getListForListid($listid);
                    if(!$list)
                    {
                        error_log("HandleInviteActions.php could not find list");
                        echo '{"success":false}';
                        return;        
                    }
                    $listName = $list->name();
                }
                else
                    $listName = INVITE_ONLY_LIST;
                
				// If this is a team-owned list, make sure that the team subscription is NOT
				// expired.
				$teamID = TDOList::teamIDForList($listid);
				{
					if ($teamID)
					{
						$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
						if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
						{
							error_log("Method acceptInvite found that team subscription is expired for team-owned list: " . $listid);
                            echo json_encode(array(
                                'success' => FALSE,
                                'error' => _('Changes to team-owned lists are not allowed on expired team accounts.'),
                            ));
							return;
						}
					}
				}
				
                $fromUserName = trim(TDOUser::fullNameForUserId($userid));
                if(!$fromUserName)
                    $fromUserName = TDOUser::usernameForUserId($userid);
                
                $email = TDOMailer::validate_email($emailstring);
                if(empty($email))
                {
                    error_log("HandleInviteActions.php could not send the invitation due to invalid email");
                    echo '{"success":false}';
                    return;
                }
                
                $invitationURL = SITE_PROTOCOL . SITE_BASE_URL."?acceptinvitation=true&invitationid=".$invitation->invitationId();
                if(!TDOMailer::sendInvitation($fromUserName, $email, $invitationURL, $listName))
                {
                    error_log("HandleInviteActions.php failed sending the invitation.");
                    echo '{"success":false}';
                    return;
                }
                
                $invitation->setUserId($userid);
                $invitation->setTimestamp(time());
                $invitation->updateInvitation();
                
                $session = TDOSession::getInstance();
                TDOChangeLog::addChangeLog($listid, $session->getUserId(), $invitation->invitationId(), $email, ITEM_TYPE_INVITATION, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB);
                echo '{"success":true}';
                
            } // end resendInvite method
            
            elseif($method == "updateInvite")
            {
                if(!isset($_POST['role']))
                {
                    error_log("HandleInviteActions.php called missing parameter: role");
                    echo '{"success":false}';
                    return;
                }
                
                $role = $_POST['role'];
                $invitation->setMembershipType($role);
                
                if($invitation->updateInvitation())
                {
                    echo '{"success":true}';
                    return;
                }
                else
                {
                    echo '{"success":false}';
                    return;
                }
                echo '{"success":true}';
                
            } //end updateInvite method
            
        } //end of methods requiring authentication
        
        elseif($method == "acceptInvite")
        {
            $listid = $invitation->listId();
            if(empty($listid))
            {
                error_log("HandleInviteActions.php could not find list for invitation");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('The list could not be found'),
                ));
                return;
            }
			
			// If this is a team-owned list, make sure that the team subscription is NOT
			// expired.
			$teamID = TDOList::teamIDForList($listid);
			{
				if ($teamID)
				{
					$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
					if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
					{
						error_log("Method acceptInvite found that team subscription is expired for team-owned list: " . $listid);
                        echo json_encode(array(
                            'success' => FALSE,
                            'error' => _('Changes to team-owned lists are not allowed on expired team accounts.'),
                        ));
						return;
					}
				}
			}
			
            $invitedUserId = $invitation->invitedUserId();
            if(!empty($invitedUserId))
            {
                if($invitedUserId != $session->getUserId())
                {
                    error_log("HandleInviteActions.php was asked to accept an invitation for a user other than the one invited");
                    echo json_encode(array(
                        'success' => FALSE,
                        'error' => _('Invalid invitation for this user.'),
                    ));
                    return;
                }
            }
            
            if($listid == INVITE_ONLY_LIST)
            {
                // this was an invite that was only intended to get someone in the system
                // but not to actually add them to a list.  Simply delete the invitation
                // now because they have already made it in.
                TDOInvitation::deleteInvitation($invitation->invitationId());
                echo '{"success":true, "message":"Your invitation has been processed.  Welcome to Todo Cloud!"}';
                return;
            }

            //make sure this is an email invitation, not a facebook request
            $role = $invitation->membershipType();
            
            $list = TDOList::getListForListid($listid);
            if(empty($list) || $list->deleted())
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Sorry, that list no longer exists.'),
                ));
                return;
            }
            
    // This was for limiting an unpaid user to 2 shared lists, but we're now letting them join as many as they want
    //        if(TDOUser::userCanAddSharedList($session->getUserId()) == false)
    //        {
    //            echo '{"success":false, "error":"premium"}';
    //            return;
    //        }
            
            if(TDOList::shareWithUser($listid, $session->getUserId(), $role) == false)
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Failed to share list. You may already be a member of this list.'),
                ));
                return;
            }

            TDOInvitation::deleteInvitation($invitation->invitationId());
                
            if($invitation->fbUserId() != NULL && $invitation->fbRequestId() != NULL)
            {
                TDOFBUtil::removeFacebookRequest($invitation->fbRequestId(), $invitation->fbUserId());
            }

            echo '{"success":true}';
        
        }
        
        //This is used to delete/cancel an invitation (email or facebook)
        elseif($method == "deleteInvite")
        {
            //This method requires the user to be list owner unless we're deleting a facebook invitation
            //that was made to ourself
            $fbId = TDOUser::facebookIdForUserId($session->getUserId());
            if(empty($fbId) || $invitation->fbUserId() == NULL || $invitation->fbUserId() != $fbId)
            {
                $listid = $invitation->listId();
                if(empty($listid))
                {
                    error_log("HandleInviteActions.php could not find list for invitation");
                    echo '{"success":false}';
                    return;
                }
				
				// If this is a team-owned list, make sure that the team subscription is NOT
				// expired.
				$teamID = TDOList::teamIDForList($listid);
				{
					if ($teamID)
					{
						$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
						if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
						{
							error_log("Method deleteInvite found that team subscription is expired for team-owned list: " . $listid);
                            echo json_encode(array(
                                'success' => FALSE,
                                'error' => _('Changes to team-owned lists are not allowed on expired team accounts.'),
                            ));
							return;
						}
					}
				}
				
                if($listid != INVITE_ONLY_LIST)
                {
                    $isOwner = false;
                    $isInvitee = false;
                    
                    if(TDOList::getRoleForUser($invitation->listId(), $session->getUserId()) != LIST_MEMBERSHIP_OWNER)
                        $isOwner = false;
                    else
                        $isOwner = true;
                    
                    $invitedUserId = $invitation->invitedUserId();
                    if(!empty($invitedUserId))
                    {
                        if($invitedUserId == $session->getUserId())
                            $isInvitee = true;
                    }
                    
                    if(($isOwner == false) && ($isInvitee == false))
                    {
                        error_log("HandleInviteActions.php access violation.  User not authorized: ");
                        echo '{"success":false}';
                        return;
                    }
                }
            }
            
            if(!TDOInvitation::deleteInvitation($invitation->invitationId()))
            {
                error_log("HandleInviteActions.php failed to delete invitation: ".$invitation->invitationId());
                echo '{"success":false}';
                return;
            }
            if($invitation->fbUserId() != NULL && $invitation->fbRequestId() != NULL)
            {
                TDOFBUtil::removeFacebookRequest($invitation->fbRequestId(), $invitation->fbUserId());
            }
            
            $session = TDOSession::getInstance();
            if($invitation->email() != NULL)
                $invitedName = $invitation->email();
            else
                $invitedName = TDOFBUtil::getFBUserNameForFBUserId($invitation->fbUserId());
            
            TDOChangeLog::addChangeLog($invitation->listId(), $session->getUserId(), $invitation->invitationId(), $invitedName, ITEM_TYPE_INVITATION, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
            echo '{"success":true}';

        }
    }
    elseif($method == "getUserInvites")
    {
        $userid = $session->getUserId();
        
        $invitations = TDOInvitation::getInvitations(NULL, NULL, $userid);
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


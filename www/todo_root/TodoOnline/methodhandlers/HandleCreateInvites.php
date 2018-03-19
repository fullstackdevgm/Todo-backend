<?php

	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');

    //This is used to invite users via email
    if($method == "emailInvites")
    {
        if(isset($_POST['listid']))
        {
            $listid = $_POST['listid'];
        }
        else
        {
            $listid = INVITE_ONLY_LIST;
        }

        if(!isset($_POST['email']))
        {
            error_log("HandleCreateInvites.php called and missing a required parameter: email");
            echo '{"success":false}';
            return;
        }


        if($listid != INVITE_ONLY_LIST)
        {
            if(!isset($_POST['role']))
            {
                error_log("HandleCreateInvites.php called and missing a required parameter: role");
                echo '{"success":false}';
                return;
            }
            $role = $_POST['role'];

            if(TDOList::getRoleForUser($_POST['listid'], $session->getUserId()) != LIST_MEMBERSHIP_OWNER)
            {
                error_log("HandleCreateInvites.php access violation.  User not authorized: ");
                echo '{"success":false}';
                return;
            }
        }
        else
        {
            $role = 1; //it doesn't matter what the role is, since we're not inviting someone to a list
        }

        //This was for limiting an unpaid user to 2 shared lists, but now we don't let them share any
        //If the user has already reached his max number of shared lists and this is not one of the already shared lists,
        //return an error
//        if(TDOUser::userCanAddSharedList($session->getUserId(), $listid) == false)
//        {
//            echo '{"success":false, "error":"premium"}';
//            return;
//        }

// Starting in Todo Cloud 10.0.2 for iOS, we no longer require a paid
// subscription to invite someone to a shared list.
        // if(TDOSubscription::getSubscriptionLevelForUserID($session->getUserId()) < 2)
        // {
        //     echo json_encode(array(
        //         'success' => FALSE,
        //         'error' => _('premium'),
        //     ));
        //     return;
        // }


        //Don't allow sharing of the user inbox
        $userInbox = TDOList::getUserInboxId($session->getUserId(), false);
        if($userInbox == $listid)
        {
            error_log("User tried to share Inbox");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You may not share your Inbox'),
            ));
            return;
        }

        $emailstring = $_POST['email'];
        $userid = $session->getUserId();

        if($listid != INVITE_ONLY_LIST)
        {
            $list = TDOList::getListForListid($listid);
            if(!$list)
            {
                error_log("HandleCreateInvites.php could not find list");
                echo '{"success":false}';
                return;
            }
            $listName = $list->name();
        }
        else
            $listName = INVITE_ONLY_LIST;

        $fromUserName = trim(TDOUser::fullNameForUserId($userid));
        if(!$fromUserName)
            $fromUserName = TDOUser::usernameForUserId($userid);

        $emails = explode("\n", $emailstring);

        $sentInvitationJSON = array();
        $sentEmails = array();
        $unsentEmails = array();
        foreach($emails as $originalEmail)
        {
            $email = TDOMailer::validate_email($originalEmail);
            if($email && strlen($email) <= USER_NAME_LENGTH)
            {
                $invitations = TDOInvitation::getInvitationForEmail($email, $userid, $listid);
                if (sizeof($invitations)) {
                    $invitation = $invitations[0];
                } else {
                    $invitation = TDOInvitation::createInvitation($userid, $listid, $role, $email);
                }
                if ($invitation)
                {
                    $invitedUserInSystem = false;
                    // check to see if the invited user was already in the system
                    if($invitation->invitedUserId() != null)
                    {
                        $invitedUserInSystem = true;
                    }

                    $invitationURL = SITE_PROTOCOL . SITE_BASE_URL."?acceptinvitation=true&invitationid=".$invitation->invitationId();
                    if(TDOMailer::sendInvitation($fromUserName, $email, $invitationURL, $listName))
                    {
                        $sentEmails[] = $email;
                        $inviteJSON = $invitation->getPropertiesArray();
                        $inviteJSON['invitee'] = $invitation->email();
                        $sentInvitationJSON[] = $inviteJSON;
                    }
                    else
                    {
                        $unsentEmails[] = $email;
                        //delete the invitation if it didn't send
                        // and the user is not in the system
                        if($invitedUserInSystem == false)
                            TDOInvitation::deleteInvitation($invitation->invitationId());
                    }
                }
                else
                    $unsentEmails[] = $email;
            }
            else
            {
                if(trim($originalEmail))
                    $unsentEmails[] = $originalEmail;
            }
        }

        //Display which emails were sent and which were not sent
    //    if(count($sentEmails) > 0)
    //    {
    //        echo "Sent invitations to:";
    //        foreach($sentEmails as $email)
    //        {
    //            echo " $email";
    //        }
    //        echo "<br><br>";
    //    }
        if(count($unsentEmails) > 0)
        {
            $errorMSG = _("Could not send invitations to:");
            foreach($unsentEmails as $email)
            {
                $errorMSG .= " $email";
            }
            echo json_encode(array(
                'success' => FALSE,
                'error' => $errorMSG,
            ));
        }
        else
        {
            $responseJSON = array();
            $responseJSON['success'] = true;
            $responseJSON['invitations'] = $sentInvitationJSON;
            echo json_encode($responseJSON);
        }

    }
    //This is used to invite users via facebook
    elseif($method == "createFBInvites")
    {
        if(!isset($_POST['listid']))
        {
            error_log("HandleCreateInvites.php called and missing a required parameter: listid");
            echo '{"success":false}';
            return;
        }
        if(!isset($_POST['role']))
        {
            error_log("HandleCreateInvites.php called and missing a required parameter: role");
            echo '{"success":false}';
            return;
        }
        if(!isset($_POST['requestid']))
        {
            error_log("HandleCreateInvites.php called and missing a required parameter: requestid");
            echo '{"success":false}';
            return;
        }
        if(!isset($_POST['to']))
        {
            error_log("HandleCreateInvites.php called and missing a required parameter: to");
            echo '{"success":false}';
            return;
        }

        if(TDOList::getRoleForUser($_POST['listid'], $session->getUserId()) != LIST_MEMBERSHIP_OWNER)
        {
            error_log("HandleCreateInvites.php access violation.  User not authorized: ");
            echo '{"success":false}';
            return;
        }

        $listid = $_POST['listid'];

        //This was for limiting an unpaid user to 2 shared lists, but now we don't let them share any
        //If the user has already reached his max number of shared lists and this is not one of the already shared lists,
        //return an error
//        if(TDOUser::userCanAddSharedList($session->getUserId(), $listid) == false)
//        {
//            echo '{"success":false, "error":"premium"}';
//            return;
//        }

        // if(TDOSubscription::getSubscriptionLevelForUserID($session->getUserId()) < 2)
        // {
        //     echo json_encode(array(
        //         'success' => FALSE,
        //         'error' => _('premium'),
        //     ));
        //     return;
        // }

        //Don't allow sharing of the user inbox
        $userInbox = TDOList::getUserInboxId($session->getUserId(), false);
        if($userInbox == $listid)
        {
            error_log("User tried to share Inbox");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You may not share your Inbox'),
            ));
            return;
        }

        $requestid = $_POST['requestid'];
        $fbrole = $_POST['role'];
        $fbusers = $_POST['to'];

        $sentInvitationJSON = array();

        foreach($fbusers as $fbuser)
        {
            $invitation = new TDOInvitation();
            $invitation->setInvitationId(TDOUtil::uuid());
            $invitation->setMembershipType($fbrole);
            $invitation->setListId($listid);
            $invitation->setTimestamp(time());
            $invitation->setUserId($session->getUserId());
            $invitation->setFBUserId($fbuser);
            $invitation->setFBRequestId($requestid);

            if($invitation->addInvitation() == false)
            {
                error_log("Failed to add invitation to facebook user: ".$fbuser);
            }
            else
            {
                $invitationJSON = $invitation->getPropertiesArray();
                $sentInvitationJSON[] = $invitationJSON;
            }

        }
        $response = array();
        $response['success'] = true;
        $response['invitations'] = $sentInvitationJSON;

        echo json_encode($response);
    }

?>

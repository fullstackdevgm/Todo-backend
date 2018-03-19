<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');
	
    if($method == "sendResetPasswordEmail")
    {
        if(!isset($_POST['username']))
        {
            echo '{"success":false}';
            error_log("Method sendResetPasswordEmail called missing parameter: username");
            return;
        }
        
        $username = $_POST['username'];

        if(!$username || $username === '')
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Please enter your email.'),
            ));
            return;
        }

        $userid = TDOUser::userIdForUserName($username);
        
        if(empty($userid))
        {
            //Bug 7139 - We should not tell the user whether the username is valid or not, so
            //just return true
            echo '{"success":true}';
            return;
        }
        
        if(TDOPasswordReset::deleteExistingPasswordResetForUser($userid) == false)
            error_log("Unable to invalidate existing reset password request for user");
            
        $passwordReset = new TDOPasswordReset();
        $passwordReset->setUserId($userid);
        $passwordReset->setUsername($username);
        
        if($passwordReset->addPasswordReset())
        {
            $email = TDOMailer::validate_email($username);
            if($email)
            {
                $userDisplayName = TDOUser::displayNameForUserId($userid);
                if(empty($userDisplayName))
                    $userDisplayName = "Todo Cloud user";
                    
                $resetURL = SITE_PROTOCOL . SITE_BASE_URL."?resetpassword=true&resetid=".$passwordReset->resetId()."&uid=".$userid;
                if(TDOMailer::sendResetPasswordEmail($userDisplayName, $email, $resetURL))
                {
					// Log this account activity
					if (!TDOUser::logUserAccountAction($userid, $userid, USER_ACCOUNT_LOG_TYPE_MAIL_PASSWORD_RESET, "Reset password link sent"))
						error_log("HandleResetPasswordMethods::sendResetPasswordEmail could not log the account activity");
					
                    echo '{"success":true}';
                }
                else
                {
                    echo json_encode(array(
                        'success' => FALSE,
                        'error' => sprintf(_('failed to send email to %s'), $email),
                    ));
                }
            }
            else
            {
                error_log("Could not validate email: ".$username);
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => sprintf(_('could not send email to %s'), $username),
                ));
            }
        }
        else
        {
            echo '{"success":false}';
        }
    }
    
    if($method == "resetPassword")
    {
        if(!isset($_POST['password']) || !isset($_POST['userid']) || !isset($_POST['resetid']))
        {
            echo '{"success":false}';
            error_log("Method resetPassword called missing required parameter");
            return;
        }
        
        $newPassword = $_POST['password'];
		$newPassword = trim($newPassword);
        if(strlen($newPassword) > PASSWORD_LENGTH)
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('The password you have entered is too long. Please enter a shorter password.'),
            ));
            return;
        }
		else if(strlen($newPassword) < PASSWORD_MIN_LENGTH)
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('The password you have entered is too short. Please enter a password with a length of at least six characters.'),
            ));
			return;
		}
		
        $userid = $_POST['userid'];
        $resetid = $_POST['resetid'];
        
        //Make sure the user followed a valid link to get here before resetting the password
        $resetPassObj = TDOPasswordReset::getPasswordResetForResetIdAndUserId($resetid, $userid);
        
        if(!empty($resetPassObj))
        {
            $timestamp = $resetPassObj->timestamp();
            //If the timestamp is too old (one week), expire it
            if($timestamp < (time() - 604800))
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('The link you used is expired. Please request another email.'),
                ));
            }
            else
            {
                $user = TDOUser::getUserForUserId($userid);
                if(!empty($user))
                {
                    $user->setPassword($newPassword);
                    
                    if($user->updateUser())
                    {
                        //Go ahead and delete the password reset row from the db and log the user in
                        $resetPassObj->deletePasswordReset();
                        
                        $session = TDOSession::getInstance();
                        $session->login($user->username(), $newPassword);
						
						// Log this account activity
						if (!TDOUser::logUserAccountAction($userid, $userid, USER_ACCOUNT_LOG_TYPE_PASSWORD_RESET, "User reset their password with reset link"))
							error_log("HandleResetPasswordMethods::resetPassword could not log the account activity");
						
                        echo '{"success":true}';
                    }
                    else
                    {
                        echo json_encode(array(
                            'success' => FALSE,
                            'error' => _('Unable to update password.'),
                        ));
                    }
                    
                }
                else
                {
                    echo json_encode(array(
                        'success' => FALSE,
                        'error' => _('The link you used is invald. Please request another email.'),
                    ));
                }
            }
        }
        else
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('The link you used is invald. Please request another email.'),
            ));
        }
        
    }
    
?>
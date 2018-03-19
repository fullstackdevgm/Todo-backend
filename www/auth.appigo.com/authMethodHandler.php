<?php

    include_once('TodoOnline/base_sdk.php');
    include_once('ssoMethods.php');
    
    if(isset($_GET['method']))
    {
       $method = $_GET['method'];
    }

    if(isset($_POST['method']))
    {
        $method = $_POST['method'];
    }
    
    
    if($method == "login")
    {
        // username and password sent from form 
        $username=$_POST['username']; 
        $password=$_POST['password']; 
        
        // To protect MySQL injection (more detail about MySQL injection)
        $username = stripslashes($username);
        $password = stripslashes($password);
        
        $user = TDOUser::getUserForUsername($username);
        if($user == false)
        {
            $user = AppigoUser::getUserForUsername($username);
            if($user == false)
            {
                error_log("authMethodHandler::login no AppigoUser account found for: " . $username);
                echo '{"success":false, "error":"The username or password did not match."}';
                return;
            }
        }
        
        if($user->matchPassword($password) == true)
        {
            $fullName = $user->displayName();
            
            $response['success'] = true;
            $response['redirectURL'] = getSSOUrl($fullName, $user->username());
            
            echo json_encode($response);
            return true;
        }

        echo '{"success":false, "error":"The username or password did not match."}';
        return;    
    }

    else if($method == "joinMailingList")
    {
        // username and password sent from form 
        $email=$_POST['email']; 
        
        // To protect MySQL injection (more detail about MySQL injection)
        $email = stripslashes($email);
        
        $emailVerification = new AppigoEmailVerification();
        $emailVerification->setUserId(WEB_EMAIL_SIGNUP_USER);
        $emailVerification->setUsername($email);
        
        if($emailVerification->addEmailVerification())
        {
            $emailVerifyURL = "http://".SUPPORT_SITE_BASE_URL."?verifyemail=true&verificationid=".$emailVerification->verificationId();
            AppigoEmailListUser::sendMailingListSignupEmail($email, $emailVerifyURL);
            echo '{"success":true}';
            return;
        }

        error_log("AppigoEmailVerification::addEmailVerification() failed to add email verification for user " . $email);
        echo '{"success":false, "error":"Unable to send the email."}';
    }
    
    else if($method == "sendResetPasswordEmail")
    {
        if(!isset($_POST['username']))
        {
            echo '{"success":false}';
            error_log("authMethodHandler::sendResetPasswordEmail called missing parameter: username");
            return;
        }
        
        $username = $_POST['username'];
        
        // First check to see if this is a Todo Cloud user
        // redirect them to Todo Cloud if it is and they
        // can change their password there
        $userid = TDOUser::userIdForUserName($username);
        if(!empty($userid))
        {
            error_log("authMethodHandler::sendResetPasswordEmail Todo Cloud user found, redirecting to Todo Cloud for user: ". $username);
            // return an error with a redirect URL
            $response['success'] = false;
            $response['redirectURL'] = SITE_PROTOCOL . SITE_BASE_URL;
            
            echo json_encode($response);
            return;
        }

        $userid = AppigoUser::userIdForUserName($username);
        if(empty($userid))
        {
            // CRG - Changed this because Jeff said a lot of people are contacting support
            // saying they are not getting the email when in reality, they don't even 
            // have user acccounts.
            error_log("authMethodHandler::sendResetPasswordEmail unable to lookup userid from username: ". $username);
            echo '{"success":false, "error":"User not found"}';
            return;
        }
        
        if(AppigoPasswordReset::deleteExistingPasswordResetForUser($userid) == false)
            error_log("Unable to invalidate existing reset password request for user");
        
        $passwordReset = new AppigoPasswordReset();
        $passwordReset->setUserId($userid);
        $passwordReset->setUsername($username);
        
        if($passwordReset->addPasswordReset())
        {
            $email = TDOMailer::validate_email($username);
            if($email)
            {
                $userDisplayName = AppigoUser::displayNameForUserId($userid);
                if(empty($userDisplayName))
                    $userDisplayName = "Appigo Support user";
                
                $resetURL = "https://".SUPPORT_SITE_BASE_URL."?page=resetPassword&resetid=".$passwordReset->resetId()."&uid=".$userid;
                if(TDOMailer::sendAppigoResetPasswordEmail($userDisplayName, $email, $resetURL))
                {
					// Log this account activity
					if (!AppigoUser::logUserAccountAction($userid, $userid, APPIGO_ACCOUNT_LOG_TYPE_MAIL_PASSWORD_RESET, "Reset password link sent"))
						error_log("authMethodHandler::sendResetPasswordEmail could not log the account activity");
					
                    echo '{"success":true}';
                }
                else
                {
                    echo '{"success":false, "error":"failed to send email to '.$email.'"}';
                }
            }
            else
            {
                error_log("Could not validate email: ".$username);
                echo '{"success":false, "error":"could not send email to '.$username.'"}';
            }
        }
        else
        {
            echo '{"success":false}';
        }
    }
    
    else if($method == "resetPassword")
    {
        if(!isset($_POST['password']) || !isset($_POST['userid']) || !isset($_POST['resetid']))
        {
            echo '{"success":false}';
            error_log("authMethodHandler::resetPassword called missing required parameter");
            return;
        }
        
        $newPassword = $_POST['password'];
        if(strlen($newPassword) > APPIGO_PASSWORD_LENGTH)
        {
            echo '{"success":false, "error":"The password you have entered is too long. Please enter a shorter password."}';
            return;
        }
        
        $userid = $_POST['userid'];
        $resetid = $_POST['resetid'];
        
        //Make sure the user followed a valid link to get here before resetting the password
        $resetPassObj = AppigoPasswordReset::getPasswordResetForResetIdAndUserId($resetid, $userid);
        
        if(!empty($resetPassObj))
        {
            $timestamp = $resetPassObj->timestamp();
            //If the timestamp is too old (one week), expire it
            if($timestamp < (time() - 604800))
            {
                echo '{"success":false, "error":"The link you used is expired. Please request another email."}';
            }
            else
            {
                $user = AppigoUser::getUserForUserId($userid);
                if(!empty($user))
                {
                    $user->setPassword($newPassword);
                    
                    if($user->updateUser())
                    {
                        //Go ahead and delete the password reset row from the db and log the user in
                        $resetPassObj->deletePasswordReset();
                        
						// Log this account activity
						if (!AppigoUser::logUserAccountAction($userid, $userid, APPIGO_ACCOUNT_LOG_TYPE_PASSWORD_RESET, "User reset their password with reset link"))
							error_log("authMethodHandler::resetPassword could not log the account activity");
						
                        echo '{"success":true}';
                    }
                    else
                    {
                        echo '{"success"false, "error":"Unable to update password"}';
                    }
                    
                }
                else
                {
                    echo '{"success":false, "error":"The link you used is invald. Please request another email."}';
                }
            }
        }
        else
        {
            echo '{"success":false, "error":"The link you used is invald. Please request another email."}';
        }
    }

    else if($method == "signup")
    {
        $newFirstName=$_POST['firstname']; 
        $newLastName=$_POST['lastname']; 
        $newUsername=$_POST['username']; 
        $newPassword=$_POST['password'];
        $emailOptIn=$_POST['emailoptin'];
        
        // To protect MySQL injection (more detail about MySQL injection)
        $newFirstName = stripslashes($newFirstName);
        $newLastName = stripslashes($newLastName);
        $newUsername = stripslashes($newUsername);
        $newPassword = stripslashes($newPassword);
        
        
        if(TDOUser::existsUsername($newUsername))
        {
            error_log("signup.php - TDOUser user already exists: ".$newUsername);
            echo '{"success":false, "error":"That username is already taken."}';
            return;
        }
        
        if(AppigoUser::existsUsername($newUsername))
        {
            error_log("signup.php - AppigoUser user already exists: ".$newUsername);
            echo '{"success":false, "error":"That username is already taken."}';
            return;
        }
        
        if(!AppigoUser::isValidUsername($newUsername))
        {
            error_log("signup.php - AppigoUser invalid username - must be an email address.");
            echo '{"success":false, "error":"The username must be a valid email address."}';
            return;
        }
        
        $user = new AppigoUser();
        
        if(strlen($newUsername) > APPIGO_USER_NAME_LENGTH)
        {
            error_log("signup.php - username is too long");
            echo '{"success":false, "error":"The username you have chosen is too long."}';
            return;
        }
        
        $user->setUsername($newUsername);
        
        if(strlen($newPassword) > APPIGO_PASSWORD_LENGTH)
        {
            error_log("signup.php - password is too long");
            echo '{"success":false, "error":"The password you have entered is too long. Please enter a shorter password."}';
            return;
        }
        
        $user->setPassword($_POST['password']);
        
        if(!empty($newFirstName))
            $user->setFirstName($newFirstName);
        if(!empty($newLastName))
            $user->setLastName($newLastName);
        
        if(isset($_POST['emailoptin']))
        {
            if($_POST['emailoptin'] == "0")
                $user->setEmailOptOut(1);
        }
        
        if($user->addUser())
        {
            $fullName = $user->firstName() . " " . $user->lastName();
            
            $response['success'] = true;
            $response['redirectURL'] = getSSOUrl($fullName, $user->username());
            
            echo json_encode($response);
            return true;
        }
        else
        {
            echo '{"success":false, "error":"The password you have entered is too long. Please enter a shorter password."}';
        }
    }
    

//our whole site runs off 47 lines of code :D

?>







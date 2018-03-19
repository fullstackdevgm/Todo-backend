<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');
	include_once('TodoOnline/DBConstants.php');

	if(!$session->isLoggedIn())
	{
		error_log("HandleUpdateUser.php called without a valid session");
		
		echo '{"success":false}';
		return;
	}
	
	$user = TDOUser::getUserForUserId($session->getUserId());
		
	if($user == false)
	{
		error_log("HandleUpdateUser.php unable to fetch logged in user: ".$session->getUserId());
		echo '{"success":false}';
		return;
	}

    $email = NULL;
    if($method == "updateUser")
    {
        if(isset($_POST['username']))
        {
            if(TDOUser::existsUsername($_POST['username']))
            {
                error_log("HandleUpdateUser.php user already exists with name ".$_POST['username']);
                echo '{"success":false}';
                return;
            }
            $username = $_POST['username'];
            if(strlen($username) > USER_NAME_LENGTH)
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('The username you have entered is too long. Please enter a shorter username.'),
                ));
                return;
            }
            
            //If the user changes email addresses, we're going to have to mark it as unverified
            $user->setUsername($username);
            $user->setEmailVerified(0);
            
            if(!TDOEmailVerification::sendVerificationEmailForUser($user))
            {
                error_log("unable to send verification email to user");
            }
            else
            {
                $email = TDOMailer::validate_email($user->username());
            }
        }

        if(isset($_POST['password']))
        {
            $password = $_POST['password'];
			$password = trim($password);
            if(strlen($password) > PASSWORD_LENGTH)
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('The password you have entered is too long. Please enter a shorter password.'),
                ));
                return;
            }
			else if(strlen($password) < PASSWORD_MIN_LENGTH)
			{
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('The password you have entered is too short. Please enter a password with a length of at least six characters.'),
                ));
				return;
			}
			
            $user->setPassword($password);
        }

        if(isset($_POST['firstname']))
            $user->setFirstName($_POST['firstname']);

        if(isset($_POST['lastname']))
            $user->setLastName($_POST['lastname']);

        if(isset($_POST['email_opt_out']))
            $user->setEmailOptOut($_POST['email_opt_out']);
        
        if($user->updateUser())
        {
            if($email)
                echo '{"success":true, "email":"'.$email.'"}';
            else
                echo '{"success":true}';
            
            if(isset($_POST['username']))
            {
                if (!TDOUser::logUserAccountAction($session->getUserId(), $session->getUserId(), USER_ACCOUNT_LOG_TYPE_USERNAME, "User changed their username in Web UI Settings"))
                    error_log("HandleUpdateUser.php could not log the account activity");
            }

            if( (isset($_POST['firstname'])) || (isset($_POST['lastname'])) )
            {
                if (!TDOUser::logUserAccountAction($session->getUserId(), $session->getUserId(), USER_ACCOUNT_LOG_TYPE_NAME, "User changed their name in Web UI Settings"))
                    error_log("HandleUpdateUser.php could not log the account activity");
            }
            
            if(isset($_POST['password']))
            {
                if (!TDOUser::logUserAccountAction($session->getUserId(), $session->getUserId(), USER_ACCOUNT_LOG_TYPE_PASSWORD, "User changed their password in Web UI Settings"))
                    error_log("HandleUpdateUser.php could not log the account activity");
            }            
        }
        else
        {
            error_log("HandleUpdateUser.php failed to updateUser");	
            echo '{"success":false}';
        }
    }
    elseif($method == "setUserTimezone")
    {
        if(!isset($_POST['timezone_offset']) && !isset($_POST['timezone_id']))
        {
            error_log("HandleUpdateUser.php called with missing param: timezone");
            echo '{"success":false}';
            return;
        }
        if(isset($_POST['timezone_offset']))
        {
            $timezoneOffset = $_POST['timezone_offset'];
            $timezone = NULL;
            //find a timezone that matches this offset
            $utc = new DateTimeZone('UTC');
            $dt = new DateTime('now', $utc);
            foreach(DateTimeZone::listIdentifiers() as $tz) 
            {
                $current_tz = new DateTimeZone($tz);
                $offset =  $current_tz->getOffset($dt);
                if($offset == $timezoneOffset)
                {
                    $timezone = $tz;
                    break;
                }
            }
            if($timezone == NULL)
            {
                error_log("couldn't find timezone matching offset: $timezoneOffset");
                echo '{"success":false}';
                return;  
            }
        }
        elseif(isset($_POST['timezone_id']))
        {
            $timezone = $_POST['timezone_id'];
        }
        if(!TDOUserSettings::setTimezoneForUser($session->getUserId(),$timezone))
        {
            error_log("failed to set timezone");
            echo '{"success":false}';
            return;            
        }
        $_SESSION['timezone'] = $timezone;
        TDOSession::setDefaultTimezone();
        echo '{"success":true}';
        
    }
    elseif ($method == "setUserLanguage") {
        if (!isset($_POST['language_id'])) {
            error_log("HandleUpdateUser.php setUserLanguage called with missing param: language_id");
            echo '{"success":false}';
            return;
        }
        $language = $_POST['language_id'];
        if (!TDOUser::setLocaleForUser($session->getUserId(), $language)) {
            error_log("failed to set language");
            echo '{"success":false}';
            return;
        }
        setcookie('interface_language', $language, strtotime('+1 year'), '/');
        echo '{"success":true}';
        die();

    }
    elseif($method == "changeTagFilterSetting")
    {
        if(!isset($_POST['setting']))
        {
            error_log("changeTagFilterSetting called with missing parameter: setting");
            echo '{"success":false}';
            return;
        }
        $setting = $_POST['setting'];
        if(!TDOUserSettings::setUserTagFilterSetting($session->getUserId(), $setting))
        {
           error_log("failed to save user tag filter setting");
           echo '{"success":false}';
           return;
        }
        echo '{"success":true}';
    }
	else if ($method == "generateNewTaskCreationEmail")
	{
		$userID = $session->getUserId();
		
		// If the user does not have a valid subscription, they should NOT be
		// able to use this feature.  Fail this call.
		$subscriptionLevel = $session->getSubscriptionLevel();
		if ($subscriptionLevel < SUBSCRIPTION_LEVEL_TRIAL)
		{
			error_log("user attempted to generate a new task creation email but does not have a valid subscription");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('no subscription'),
            ));
			return;
		}
		
		$newEmail = TDOUserSettings::regenerateTaskCreationEmailForUserID($userID);
		
		if (empty($newEmail))
		{
			error_log("failed to generate a new task creation email");
			echo '{"success":false}';
			return;
		}
		
		echo '{"success":true, "task_creation_email":"' . $newEmail . '"}';
	}
	else if ($method == "deleteTaskCreationEmail")
	{
		$userID = $session->getUserId();
		
		if (!TDOUserSettings::clearTaskCreationEmailForUserID($userID))
		{
			error_log("failed to clear a user's task creation email setting");
			echo '{"success":false}';
			return;
		}
		
        echo '{"success":true}';
	}
    else if($method == "sendVerificationEmail")
    {
        $user = TDOUser::getUserForUserId($session->getUserId());
        if(!$user)
        {
            error_log("Failed to get user for userid: ".$session->getUserId());
            echo '{"success":false}';
            return;
        }
        
        if(TDOEmailVerification::sendVerificationEmailForUser($user))
        {
            echo '{"success":true, "email":"'.TDOMailer::validate_email($user->username()).'"}';
        }
        else
        {
            echo '{"success":false}';
        }
        
    }
    else if($method == "wipeUserData")
    {
        if(TDOUser::wipeOutDataForUser($session->getUserId()) == false)
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('We failed to delete your data. Make sure you are not the only owner of a shared list.'),
            ));
        }
        else
        {
            //Reset the session cookies
           setcookie('TodoOnlineListId',"all");
        
            echo '{"success":true}';
        }
    }
    else if($method == "reMigrateUserData")
    {
        if(!isset($_POST['password']))
        {
			error_log("reMigrateUserData was called without passing a password");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing password'),
            ));
            return;
        }            

        $migrateResult = TDOLegacy::reMigrateUser($session->getUserId(), $_POST['password']);
        if(empty($migrateResult['error']))
        {
            echo '{"success":true}';
        }
        else
        {
            echo '{"success":false}';
        }
    }
    else if($method == "verifyUserPassword")
    {
        if(!isset($_POST['password']))
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Missing parameter'),
            ));
            return;
        }
        
        $user = TDOUser::getUserForUserId($session->getUserId());
        if(empty($user))
        {
            echo '{"success":false}';
            return;
        }
        
        if($user->matchPassword($_POST['password']))
        {
            echo '{"success":true}';
        }
        else
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('The password you have entered is incorrect'),
            ));
        }
        
    }
    else if ($method == "updateUserMessage") {
        if ($_POST['message_key'] && $_POST['message_key'] != '') {
            $user = TDOUser::getUserForUserId($session->getUserId());
            $messages = $user->userMessages();
            if (is_array($messages) && array_key_exists($_POST['message_key'], $messages)) {
                $messages[$_POST['message_key']] = 0;
            }
            $user->setUserMessages($messages);
            echo $user->updateUser();
        }
    }
?>
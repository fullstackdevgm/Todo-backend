<?php
//      TDOUser
//      Used to handle all user data

// include files
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');
include_once('Facebook/config.php');
include_once('Facebook/facebook.php');
include_once('TodoOnline/DBConstants.php');

define('PW_SALT1',":(47");
define('PW_SALT2',";;47)");
define ('FB_OAUTH_PROVIDER', 1);

define ('PROFILE_IMAGE_SIZE', 50);
define ('FIRST_NAME_LENGTH', 60);
define ('LAST_NAME_LENGTH', 60);
define ('USER_NAME_LENGTH', 100);
define ('PASSWORD_LENGTH', 64);
define ('PASSWORD_MIN_LENGTH', 6);

define ('USER_ACCOUNT_LOG_TYPE_PASSWORD', 1);
define ('USER_ACCOUNT_LOG_TYPE_USERNAME', 2);
define ('USER_ACCOUNT_LOG_TYPE_NAME', 3);
define ('USER_ACCOUNT_LOG_TYPE_EXP_DATE', 4);
define ('USER_ACCOUNT_LOG_TYPE_PURCHASE_RECEIPT', 5);
define ('USER_ACCOUNT_LOG_TYPE_DOWNGRADE_TO_FREE_ACCOUNT', 6);
define ('USER_ACCOUNT_LOG_TYPE_MAIL_PASSWORD_RESET', 7);
define ('USER_ACCOUNT_LOG_TYPE_PASSWORD_RESET', 8);
define ('USER_ACCOUNT_LOG_TYPE_CLEAR_BOUNCE_EMAIL', 9);
define ('USER_ACCOUNT_LOG_TYPE_ENABLE_REMIGRATE', 10);
define ('USER_ACCOUNT_LOG_TYPE_VIP_FREE_PREMIUM_ACCOUNT', 11);
define ('USER_ACCOUNT_LOG_TYPE_CONVERT_SUBSCRIPTION_TO_GIFT_CODE', 12);
define ('USER_ACCOUNT_LOG_TYPE_JOIN_TEAM', 13);
define ('USER_ACCOUNT_LOG_TYPE_LEAVE_TEAM', 14);
define ('USER_ACCOUNT_LOG_TYPE_EMAIL_OPT_OUT', 15);
define ('USER_ACCOUNT_LOG_TYPE_IMPERSONATION', 16);

define ('USER_ACCOUNT_MESSAGE_TRIAL_ONE', 'msg_trial_one');
define ('USER_ACCOUNT_MESSAGE_TRIAL_MANY', 'msg_trial_many');
define ('USER_ACCOUNT_MESSAGE_CURRENT', 'msg_current');
define ('USER_ACCOUNT_MESSAGE_T4B_COACH', 'msg_t4b_coach');

class TDOUser extends TDODBObject
{

    public function __construct()
    {
        parent::__construct();
        $this->set_to_default();
    }
    
    public function set_to_default()
    {
        parent::set_to_default();
    }

	public static function deleteUser($userid)
	{
        if(!isset($userid))
            return false;
		
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }

		$userid = mysql_real_escape_string($userid, $link);
		$sql = "DELETE FROM tdo_user_accounts WHERE userid='$userid'";
		if(mysql_query($sql, $link))
		{
			TDOUtil::closeDBLink($link);

			return true;
		}
		else
		{
			error_log("Unable to delete user $userid");
		}

        TDOUtil::closeDBLink($link);
        return false;
	}
    
    public static function deleteUsers($userids)
    {
        if(!isset($userids))
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }

		foreach($userids as $uid)
		{
			$uid = mysql_real_escape_string($uid, $link);
			$sql = "DELETE FROM tdo_user_accounts WHERE userid='$uid'";
			if(!mysql_query($sql, $link))
			{
				error_log("Unable to delete user $uid");
			}
		}
		TDOUtil::closeDBLink($link);
		return true;
    }

	public static function existsUsername($username) 
	{
        if(!isset($username))
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }

		$username = mysql_real_escape_string($username, $link);
		$sql = "SELECT COUNT(*) FROM tdo_user_accounts WHERE username='$username'";
		$response = mysql_query($sql, $link);
		if($response)
		{
			$total = mysql_fetch_array($response);
			if($total && isset($total[0]) && $total[0] == 1)
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
		}
		else
		{
			error_log("Unable to get count of users");
		}

        TDOUtil::closeDBLink($link);
        return false;

	}

    // this will check for things like a + in the username
	public static function isValidUsername($username) 
	{
        if(empty($username))
            return false;
        
        $email = TDOMailer::validate_email($username);
        if($email == false)
            return false;
        
        // place other checks here if needed
        
        return true;
	}
    
    
    
    
	public static function userIdForUserName($username)
	{
        if(!isset($username))
		{
			error_log("TDOUser::userIdForUserName() called with a NULL username");
            return false;
		}
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
			error_log("TDOUser::userIdForUserName('" . $username . "') could not connect to mysql");
            return false;
        }
		$username = mysql_real_escape_string($username, $link);
		$result = mysql_query("SELECT userid FROM tdo_user_accounts where username='$username'");
        
		if($result)
		{
			$resultsArray = mysql_fetch_array($result);
			if($resultsArray)
			{
				if(isset($resultsArray['userid']))
				{
					$userid = $resultsArray['userid'];
                    TDOUtil::closeDBLink($link);
                    return $userid;
				}
			}
		}
		else
		{
			error_log("Unable to fetch user id");
		}
        
        TDOUtil::closeDBLink($link);
        return false;
    }
    
    

	public static function existsFacebookUser($fbuserid) 
	{
        if(!isset($fbuserid))
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$fbuserid = mysql_real_escape_string($fbuserid, $link);
		$sql = "SELECT COUNT(*) FROM tdo_user_accounts where oauth_uid='$fbuserid' AND oauth_provider=".FB_OAUTH_PROVIDER;
		$response = mysql_query($sql, $link);
		if($response)
		{
			$total = mysql_fetch_array($response);
			if($total && isset($total[0]) && $total[0] == 1)
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
		}
		else
		{
			error_log("Unable to get count of users");
		}

        TDOUtil::closeDBLink($link);
        return false;
    }

    public static function existsUserId($userid)
    {
        if(!isset($userid))
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$userid = mysql_real_escape_string($userid, $link);
		$sql = "SELECT COUNT(*) FROM tdo_user_accounts WHERE userid='$userid'";
		$response = mysql_query($sql, $link);
		if($response)
		{
			$total = mysql_fetch_array($response);
			if($total && isset($total[0]) && $total[0] == 1)
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
		}
		else
		{
			error_log("Unable to get count of users");
		}
        TDOUtil::closeDBLink($link);
        return false;
    }

	public function addUser($createTrialSubscription=true, $isMigratedUser=false)
	{
		if($this->username() == NULL)
        {
            error_log("TDOUser::addUser failed with no username");
			return false;
		}
        if($this->password() == NULL)
        {
            error_log("TDOUser::addUser failed with no password");
			return false;
		}

		if(TDOUser::existsUsername($this->username()) == true)
        {
            error_log("TDOUser::addUser failed. Username already exists");
			return false;
		}
        
        // Allow adding pigeon
        if($this->username() != "pigeon")
        {
            if(TDOUser::isValidUsername($this->username()) == false)
            {
                error_log("TDOUser::addUser failed. Invalid username");
                return false;
            }
        }
        
        // CRG - so legacy users can preserve their userId
        if($this->userId() == NULL)
        {
            $userid = TDOUtil::uuid();
            $this->setUserId($userid);
        }
        
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$username = mysql_real_escape_string($this->username(), $link);
        $username = strtolower($username);
		$password = $this->password();
		$userid = mysql_real_escape_string($this->userId(), $link);
        
        $firstName = mb_strcut($this->firstName(), 0, FIRST_NAME_LENGTH, 'UTF-8');
		$firstName = mysql_real_escape_string($firstName, $link);
        
        $lastName = mb_strcut($this->lastName(), 0, LAST_NAME_LENGTH, 'UTF-8');
		$lastName = mysql_real_escape_string($lastName, $link);
        $emailVerified = intval($this->emailVerified());
        $lastResetTimestamp = intval($this->lastResetTimestamp());
        $emailOptOut = intval($this->emailOptOut());
		
		$locale = $this->locale();
		$bestMatchLocale = $this->bestMatchLocale();
		$selectedLocale = $this->selectedLocale();

		$creationTimestamp = time();
        $imageGuid = mysql_real_escape_string($this->imageGuid(), $link);
        $imageUpdateTimestamp = intval($this->imageUpdateTimestamp());
        
        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("TDOUser::Couldn't start transaction".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        
		$sql = "INSERT INTO tdo_user_accounts (userid, username, email_verified, email_opt_out, password, first_name, last_name, creation_timestamp, locale, best_match_locale, selected_locale, last_reset_timestamp, image_guid, image_update_timestamp) VALUES ('$userid', '$username', $emailVerified , $emailOptOut, '$password', '$firstName', '$lastName', $creationTimestamp, '$locale', '$bestMatchLocale', '$selectedLocale', $lastResetTimestamp, '$imageGuid', $imageUpdateTimestamp)";
		$response = mysql_query($sql, $link);
		if($response)
		{
            $userSettings = new TDOUserSettings();
            if(!$userSettings->addUserSettings($userid, $link))
            {
                error_log("Failed to add user settings");
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
			
            $subscriptionID = NULL;
			if ($createTrialSubscription == true)
			{
				// Set up the default subscription trial period
				$expirationDate = new DateTime("now", new DateTimeZone("UTC"));
				$trialDateIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL);
				$expirationDate = $expirationDate->add(new DateInterval($trialDateIntervalSetting));
				$expirationDateTimestamp = $expirationDate->getTimestamp();
				
				$subscriptionID = TDOSubscription::createSubscription($userid, $expirationDateTimestamp, SUBSCRIPTION_TYPE_UNKNOWN, SUBSCRIPTION_LEVEL_TRIAL, $link);
				if(!$subscriptionID)
				{
					// Let's not fail the entire user creation process if
					// creating a subscription fails.  The user will still be
					// able to have a free account and hopefully create a valid
					// subscription manually.
					error_log("TDOUser::addUser() failed to create a trial subscription for user $userid");
				}
			}
            
            //Add entry for user to verify their email address
            $emailVerifyURL = NULL;
            if($this->emailVerified() == 0)
            {
                $emailVerification = new TDOEmailVerification();
                $emailVerification->setUserId($this->userId());
                $emailVerification->setUsername($this->username());
                
                if($emailVerification->addEmailVerification($link))
                {
                    $emailVerifyURL = SITE_PROTOCOL . SITE_BASE_URL."?verifyemail=true&verificationid=".$emailVerification->verificationId();
                }
                else
                    error_log("TDOUser::addUser() failed to add email verification for user ".$this->userId());
            }
        
            if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOUser::Couldn't commit transaction adding user".mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}

			TDOUtil::closeDBLink($link);
            
			// make sure the user has a default list created            
            TDOList::getUserInboxId($this->userId(), true);
            $this->sendWelcomeEmail($emailVerifyURL, $subscriptionID, $isMigratedUser);
            
			return true;
		}
		else
		{
            mysql_query("ROLLBACK", $link);
			error_log("Unable to add user $userid $username: ".mysql_error());
		}
        TDOUtil::closeDBLink($link);
        return false;

	}

	public function addFacebookUser($createTrialSubscription=true, $isMigratedUser=false)
	{
		if($this->oauthUID() == NULL)
			return false;
        if($this->firstName() == NULL)
            return false;
        if($this->lastName() == NULL)
            return false;
        if($this->username() == NULL)
            return false;

		if(TDOUser::existsFacebookUser($this->oauthUID()) == true)
			return false;

		$userid = TDOUtil::uuid();
		$this->setUserId($userid);

        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$oauth_uid = mysql_real_escape_string($this->oauthUID(), $link);
		$oauth_provider = FB_OAUTH_PROVIDER;
		$userid = mysql_real_escape_string($this->userId(), $link);
        
        $firstName = mb_strcut($this->firstName(), 0, FIRST_NAME_LENGTH, 'UTF-8');
		$firstName = mysql_real_escape_string($firstName, $link);
        
        $lastName = mb_strcut($this->lastName(), 0, LAST_NAME_LENGTH, 'UTF-8');
		$lastName = mysql_real_escape_string($lastName, $link);
        $userName = mysql_real_escape_string($this->username(), $link);
        $userName = strtolower($userName);
        $emailVerified = intval($this->emailVerified());
		$locale = $this->locale();
		$bestMatchLocale = $this->bestMatchLocale();
		$selectedLocale = $this->selectedLocale();
		$creationTimestamp = time();
        $lastResetTimestamp = intval($this->lastResetTimestamp());
        $emailOptOut = intval($this->emailOptOut());
        $imageGuid = mysql_real_escape_string($this->imageGuid(), $link);
        $imageTimestamp = intval($this->imageUpdateTimestamp());

        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("TDOUser::Couldn't start transaction".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }

		$sql = "INSERT INTO tdo_user_accounts (userid, oauth_uid, oauth_provider, first_name, last_name, username, email_verified, email_opt_out, creation_timestamp, locale, best_match_locale, selected_locale, last_reset_timestamp, image_guid, image_update_timestamp) VALUES ('$userid', '$oauth_uid', $oauth_provider, '$firstName', '$lastName', '$userName', $emailVerified, $emailOptOut, $creationTimestamp, '$locale', '$bestMatchLocale', '$selectedLocale', $lastResetTimestamp, '$imageGuid', $imageTimestamp)";
		$response = mysql_query($sql, $link);
		if($response)
		{
            $userSettings = new TDOUserSettings();
            if(!$userSettings->addUserSettings($userid, $link))
            {
                error_log("Failed to add user settings");
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
			
            $subscriptionID = NULL;
			if ($createTrialSubscription == true)
			{
				// Set up the default subscription trial period
				$expirationDate = new DateTime("now", new DateTimeZone("UTC"));
				$trialDateIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL);
				$expirationDate = $expirationDate->add(new DateInterval($trialDateIntervalSetting));
				$expirationDateTimestamp = $expirationDate->getTimestamp();
				
				$subscriptionID = TDOSubscription::createSubscription($userid, $expirationDateTimestamp, SUBSCRIPTION_TYPE_UNKNOWN, SUBSCRIPTION_LEVEL_TRIAL, $link);
				if (!$subscriptionID)
				{
					// Let's not fail the entire user creation process if
					// creating a subscription fails.  The user will still be
					// able to have a free account and hopefully create a valid
					// subscription manually.
					error_log("TDOUser::addFacebookUser() failed to create a trial subscription for user $userid");
				}
			}
            
            
            //Add entry for user to verify their email address
            $emailVerifyURL = NULL;
            if($this->emailVerified() == 0)
            {
                $emailVerification = new TDOEmailVerification();
                $emailVerification->setUserId($this->userId());
                $emailVerification->setUsername($this->username());
                
                if($emailVerification->addEmailVerification($link))
                {
                    $emailVerifyURL = SITE_PROTOCOL . SITE_BASE_URL."?verifyemail=true&verificationid=".$emailVerification->verificationId();
                }
                else
                    error_log("TDOUser::addUser() failed to add email verification for user ".$this->userId());
            }

            
            if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOUser::Couldn't commit transaction adding user ".mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}
        
			TDOUtil::closeDBLink($link);
            
			// make sure the user has a default list created
			TDOList::getUserInboxId($this->userId(), true);            
            $this->sendWelcomeEmail($emailVerifyURL, $subscriptionID, $isMigratedUser);
            
			return true;
		}
		else
		{
            error_log("Unable to add facebook user $oauth_uid $firstName, $lastName: ".mysql_error());
            mysql_query("ROLLBACK", $link);
		}
		

        TDOUtil::closeDBLink($link);
        return false;

	}
    
    public function sendWelcomeEmail($emailVerifyURL, $subscriptionID, $isMigratedUser)
    {
        $email = TDOMailer::validate_email($this->username());
        if($email)
        {
            $userDisplayName = $this->displayName();
            // If the user has a valid subscription, generate a task creation email and send it to them
            $taskCreationEmail = NULL;
            
            if($subscriptionID || $isMigratedUser)
            {
                $taskCreationEmail = TDOUserSettings::regenerateTaskCreationEmailForUserID($this->userId());
                if(empty($taskCreationEmail))
                    error_log("sendWelcomeEmail failed to generate task creation email");
            }
             
            if($isMigratedUser)
            {
                if(!TDOMailer::sendTodoProMigrationEmail($userDisplayName, $email, $emailVerifyURL, $taskCreationEmail))
                {
                    error_log("TDOUser::addUser() failed to send migration email for user ".$this->userId());
                }
            }
            else
            {
                if(!TDOMailer::sendTodoProWelcomeEmail($userDisplayName, $email, $emailVerifyURL, $taskCreationEmail))
                {
                    error_log("TDOUser::addUser() failed to send welcome email for user ".$this->userId());
                }
            }
        }
        else
            error_log("TDOUser could not validate email address: ".$this->username());
        
    }
    
	public function updateUser()
	{
		if($this->userId() == NULL)
		{
			error_log("TDOUser::updateUser failed calling because no userId was set");
			return false;
		}
		
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
			error_log("TDOUser::updateUser failed getting a DB link");
           return false;
        }
		$userid = mysql_real_escape_string($this->userId(), $link);

        if($this->username() != NULL)
        {
            if(TDOUser::isValidUsername($this->username()) == false)
            {
                error_log("TDOUser::updateUser invalid username");
                return false;
            }

            $username = mysql_real_escape_string($this->username(), $link);
            $username = strtolower($username);
            $updateString = "username='".$username."'";
        }
        else
            $updateString = "username=NULL";

        if($this->password() != NULL)
            $updateString .= ", password='".$this->password()."'";
        else
            $updateString .= ", password=NULL";

        if($this->firstName() != NULL)
        {
            $firstName = mb_strcut($this->firstName(), 0, FIRST_NAME_LENGTH, 'UTF-8');
            $updateString .= ", first_name='".mysql_real_escape_string($firstName, $link)."'";
        }
        else
            $updateString .= ", first_name=NULL";

        if($this->lastName() != NULL)
        {
            $lastName = mb_strcut($this->lastName(), 0, LAST_NAME_LENGTH, 'UTF-8');
            $updateString .= ", last_name='".mysql_real_escape_string($lastName, $link)."'";
        }
        else
            $updateString .=", last_name=NULL";
        
        if($this->imageGuid() != NULL)
            $updateString .= ", image_guid='".mysql_real_escape_string($this->imageGuid(), $link)."'";
        else
            $updateString .= ", image_guid=NULL";

        if($this->userMessages() != NULL)
            $updateString .= ", show_user_messages='".mysql_real_escape_string($this->_publicPropertyArray['show_user_messages'], $link)."'";
        else
            $updateString .= ", show_user_messages=NULL";

        $updateString .= ", email_verified=".intval($this->emailVerified());
        $updateString .= ", last_reset_timestamp=".intval($this->lastResetTimestamp());
        $updateString .= ", email_opt_out=".intval($this->emailOptOut());
        $updateString .= ", image_update_timestamp=".intval($this->imageUpdateTimestamp());
		
		if($this->locale() != NULL)
			$updateString .= ", locale='".mysql_real_escape_string($this->locale(), $link)."'";
		else
			$updateString .= ", locale=NULL";
		
		if($this->bestMatchLocale() != NULL)
			$updateString .= ", best_match_locale='".mysql_real_escape_string($this->bestMatchLocale(), $link)."'";
		else
			$updateString .= ", best_match_locale=NULL";

        if($this->selectedLocale() != NULL)
			$updateString .= ", selected_locale='".mysql_real_escape_string($this->selectedLocale(), $link)."'";
		else
			$updateString .= ", selected_locale=NULL";

        $sql = "UPDATE tdo_user_accounts SET $updateString WHERE userid='$userid'";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
            if( ($this->emailOptOut() == 0) && ($this->emailVerified()))
                AppigoEmailListUser::updateListUser($username, EMAIL_CHANGE_SOURCE_TODO_CLOUD);
            else if($this->emailOptOut() != 0)
                AppigoEmailListUser::deleteListUser($username);
            
            TDOUtil::closeDBLink($link);
            return true;
        }
        else
        {
            error_log("Unable to update user $userid ".mysql_error());
        }
		
        TDOUtil::closeDBLink($link);
        return false;

	}	   
    
    public static function getUserForUserId($id)
    {
        if(!isset($id))
            return false;
            
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
        
        $id = mysql_real_escape_string($id, $link);
        
		$result = mysql_query("SELECT * FROM tdo_user_accounts where userid='$id'");

		if($result)
		{
			$row = mysql_fetch_array($result);
			if($row)
			{
				$user = TDOUser::userFromRow($row);

				TDOUtil::closeDBLink($link);
				return $user;
			}
		}
		else
		{
			error_log("Unable to fetch user $id ".mysql_error());
		}
        TDOUtil::closeDBLink($link);
        return false;

    }

// This was used to limit a user to 2 shared lists without a premium account, which we're not doing any more
//    public static function userCanAddSharedList($userid, $excludedListId=NULL)
//    {
//        if(TDOSubscription::getSubscriptionLevelForUserID($userid) < 2)
//        {
//            $shareCount = TDOList::getSharedListCountForUser($userid, true, $excludedListId);
//            if($shareCount >= 2)
//            {
//               return false;
//            }
//            
//        }
//        return true;
//    }
    
	public static function getUserForUsername($username)
	{
        if(!isset($username))
		{
			error_log("TDOUser::getUserForUsername() called with a NULL username");
            return false;
		}
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
			error_log("TDOUser::getUserForUsername('" . $username . "') could not connect to mysql");
           return false;
        }
		$username = mysql_real_escape_string($username, $link);
		$result = mysql_query("SELECT * FROM tdo_user_accounts where username='$username'");

		if($result)
		{
			$resultsArray = mysql_fetch_array($result);
			if($resultsArray)
			{
                $user = TDOUser::userFromRow($resultsArray);
				TDOUtil::closeDBLink($link);
				return $user;
			}
		}
		else
		{
			error_log("Unable to fetch user $username ".mysql_error());
		}

        TDOUtil::closeDBLink($link);
        return false;
    
    }

    public static function getUserForFacebookId($fbuserid)
    {
        if(!isset($fbuserid))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$fbuserid = mysql_real_escape_string($fbuserid, $link);
		$result = mysql_query("SELECT * FROM tdo_user_accounts where oauth_uid='$fbuserid' AND oauth_provider=".FB_OAUTH_PROVIDER);

		if($result)
		{
			$resultsArray = mysql_fetch_array($result);
			if($resultsArray)
			{
                $user = TDOUser::userFromRow($resultsArray);
                
                TDOUtil::closeDBLink($link);
                return $user;
			}
		}
		else
		{
			error_log("Unable to fetch facebook user $fbuserid ".mysql_error());
		}

        TDOUtil::closeDBLink($link);
        return false;

    }
    
    public static function getAllUsers()
	{
		// error_log("getAllUsers() was called");
		
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$result = mysql_query("SELECT userid,username,password,first_name,last_name,admin_level FROM tdo_user_accounts ");

		if($result)
		{
			$users = array();
			while($row = mysql_fetch_array($result))
			{
				$user = TDOUser::userFromRow($row);
				$users[] = $user;
			}
			TDOUtil::closeDBLink($link);
			return $users;
			
		}
		else
		{
			error_log("Unable to get all users");
		}

        TDOUtil::closeDBLink($link);
        return false;
	}
    
    public static function userFromRow($row)
    {
        $user = new TDOUser();
        if(isset($row['username']))
        {
            $user->setUsername($row['username']);
        }
        if(isset($row['userid']))
        {
            $user->setUserId($row['userid']);
        }
        if(isset($row['password']))
        {
            if(empty($row['password']))
            {
                unset($user->_privatePropertyArray['password']);
            }
            else
            {
                $user->_privatePropertyArray['password'] = $row['password'];
            }
        }
        if(isset($row['first_name']))
        {
            $user->setFirstName($row['first_name']);
        }
        if(isset($row['last_name']))
        {
            $user->setLastName($row['last_name']);
        }
        if(isset($row['admin_level']))
        {
			$user->setUserAdminLevel($row['admin_level']);
        }
        if(isset($row['oauth_uid']))
        {
            $user->setOauthUID($row['oauth_uid']);
        }
        if(isset($row['oauth_provider']))
        {
            $user->setOauthProvider($row['oauth_provider']);
        }
		if(isset($row['creation_timestamp']))
		{
			$user->setCreationTimestamp($row['creation_timestamp']);
		}
		if(isset($row['locale']))
		{
			$user->setLocale($row['locale']);
		}
		if(isset($row['best_match_locale']))
		{
			$user->setBestMatchLocale($row['best_match_locale']);
		}
		if(isset($row['selected_locale']))
		{
			$user->setselectedLocale($row['selected_locale']);
		}
        if(isset($row['email_verified']))
        {
            $user->setEmailVerified($row['email_verified']);
        }
		if(isset($row['last_reset_timestamp']))
        {
            $user->setLastResetTimestamp($row['last_reset_timestamp']);
        }
        if(isset($row['email_opt_out']))
        {
            $user->setEmailOptOut($row['email_opt_out']);
        }
        if(isset($row['image_guid']))
        {
            $user->setImageGuid($row['image_guid']);
        }
        if(isset($row['image_update_timestamp']))
        {
            $user->setImageUpdateTimestamp($row['image_update_timestamp']);
        }
        if(isset($row['show_user_messages']))
        {
            $user->setUserMessages($row['show_user_messages'], TRUE);
        }

        return $user;
    }
    
    public static function userHashForUser($userid)
    {
        if(!isset($userid))
        {
            error_log("TDOUser::userHashForUser had invalid userId");
            return false;
        }
        
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOUser userHashForUser to get dblink");
			return false;
		}
        $escapedUserid = mysql_real_escape_string($userid, $link);
		
		// bht - Split this function up into multiple queries so the DB server
		// doesn't have to kill itself to pull off this query.
		
        //Here's how we take the hash: first, get the current user's info so we will sync if that info changes.
        //(userid as listid is to make the rows match up with the next clause. we don't really need the listid in this case)
        $sql = "SELECT first_name, last_name, username, oauth_uid, image_guid, userid AS listid FROM tdo_user_accounts WHERE userid='$escapedUserid' ";
        
//        //Next, get all list membership entries for OTHER users for any list the current user belongs to.
//        //Select listid to distinguish between the same user as a member of different lists
//        $sql .= "UNION SELECT first_name, last_name, username, oauth_uid, image_guid, listid FROM tdo_user_accounts JOIN tdo_list_memberships ON (tdo_user_accounts.userid=tdo_list_memberships.userid AND membership_type > 0 AND tdo_list_memberships.userid != '$escapedUserid' AND listid IN (SELECT listid FROM tdo_list_memberships WHERE tdo_list_memberships.userid='$escapedUserid'))";
        
		$userString = "";
        
        $timestamp = NULL;
        $result = mysql_query($sql);
		if (!$result)
		{
			error_log("TDOUser::userHashForUser Unable to select updated users: ".mysql_error());
			TDOUtil::closeDBLink($link);
			return false;
		}
		
		$row = mysql_fetch_array($result);
		if (!$row)
		{
			error_log("TDOUser::userHashForUser specified user not found: ".$escapedUserid);
			TDOUtil::closeDBLink($link);
			return false;
		}
		
		$userHash = TDOUser::userHashFromRow($row);
		if ($userHash)
		{
			$userString = $userHash;
		}
		
		// Get the lists the main user belongs to
		$mainLists = TDOList::getListIDsForUser($userid, $link);
		if (empty($mainLists))
		{
			// Should NEVER happen, but just in case...
			error_log("TDOUser::userHashForUser could not determine lists the user belongs to: ".$escapedUserid);
			TDOUtil::closeDBLink($link);
			return false;
		}
		
		//
		// Add the info from the OTHER users that belong to these lists
		//
		$sql = "SELECT first_name, last_name, username, oauth_uid, image_guid, tdo_user_accounts.userid AS listid FROM tdo_user_accounts JOIN tdo_list_memberships ON (tdo_user_accounts.userid=tdo_list_memberships.userid AND tdo_list_memberships.userid != '$escapedUserid' AND membership_type > 0 AND listid IN (";
		$listCount = 0;
		foreach($mainLists as $listid)
		{
			if ($listCount > 0)
				$sql .= ","; // comma separated
			
			$sql .= "'$listid'";
			$listCount++;
		}
		
		$sql .= "))";
		
        $result = mysql_query($sql);
		if (!$result)
		{
			error_log("TDOUser::userHashForUser Unable to select OTHER users: ".mysql_error());
			TDOUtil::closeDBLink($link);
			return false;
		}
		
		while ($row = mysql_fetch_array($result))
		{
			$otherUserHash = TDOUser::userHashFromRow($row);
			if ($otherUserHash)
			{
				$userString .= $otherUserHash;
			}
		}
		
		TDOUtil::closeDBLink($link);

		$md5Value = md5($userString);

		return $md5Value;
        
    }
	
	
	private static function userHashFromRow($row)
	{
		if (empty($row))
		{
			error_log("TDOUser::userHashFromRow() called with empty row");
			return NULL;
		}
		
		$userString = "";
		if(isset($row['first_name']))
			$userString .= strval($row['first_name']);
		if(isset($row['last_name']))
			$userString .= strval($row['last_name']);
		if(isset($row['username']))
			$userString .= strval($row['username']);
		if(isset($row['oauth_uid']))
			$userString .= strval($row['oauth_uid']);
		if(isset($row['listid']))
			$userString .= strval($row['listid']);
		if(isset($row['image_guid']))
			$userString .= strval($row['image_guid']);
		
		return $userString;
	}
    

    public static function getUsersForSearchString($searchString, $limit, $offset)
    {
        if(empty($searchString))
            return false;
        
        $searchArray = preg_split('/\s+/', $searchString);
        if(empty($searchArray))
            return false;
            
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("getUsersForSearchString unable to get db link");
            return false;
        }
        
        $searchString = mysql_real_escape_string($searchString, $link);
        $limit = intval($limit);
        $offset = intval($offset);
        
        $sql = "SELECT * FROM tdo_user_accounts WHERE ";
        
        $whereStatement = "";
        foreach($searchArray as $searchItem)
        {
            if(strlen($searchItem) == 0)
                continue;
        
            if(strlen($whereStatement) > 0)
            {
                $whereStatement .= " AND";
            }
            $whereStatement .= " (username LIKE '%".$searchItem."%' OR first_name LIKE '%".$searchItem."%' OR last_name LIKE '%".$searchItem."%')";
        }
        
        if(strlen($whereStatement) == 0)
        {
            return false;
        }
        
        $sql .= $whereStatement;
        $sql .= "ORDER BY username LIMIT $limit OFFSET $offset";
        
        $result = mysql_query($sql, $link);
        
        if($result)
        {
            $users = array();
            while($row = mysql_fetch_array($result))
            {
                $user = TDOUser::userFromRow($row);
                $users[] = $user;
            }
            
            TDOUtil::closeDBLink($link);
            return $users;
        }
        else
            error_log("getUsersForSearchString failed: ".mysql_error());
            
        TDOUtil::closeDBLink($link);
        return false;
        
    }
    //Used in admin pages only
    public static function getUserCount()
    {
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$result = mysql_query("SELECT COUNT(*) FROM tdo_user_accounts ");

		if($result)
		{
			if($row = mysql_fetch_array($result))
			{
                if(isset($row['0']))
                {
        			TDOUtil::closeDBLink($link);
                    return $row['0'];
                }
			}
		}
		else
		{
			error_log("Unable to get user count");
		}

        TDOUtil::closeDBLink($link);
        return false;
    }
	
	// Used in admin pages only
	public static function getNewUserCountWithDateRange($startDate, $endDate)
	{
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
			return false;
        }
		
		$sql = "SELECT COUNT(*) FROM tdo_user_accounts WHERE creation_timestamp >= $startDate AND creation_timestamp <= $endDate";
//		error_log("SQL: $sql");
		
		$result = mysql_query($sql, $link);
		
		if($result)
		{
			if($row = mysql_fetch_array($result))
			{
                if(isset($row['0']))
                {
        			TDOUtil::closeDBLink($link);
                    return $row['0'];
                }
			}
		}
		else
		{
			error_log("TDOUser::getNewUserCountWithDateRange($startDate, $endDate): Unable to get user count");
		}
		
        TDOUtil::closeDBLink($link);
        return false;
	}
	
	
	public static function getUserIDsWithDateRange($startDate, $endDate)
	{
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
			return false;
        }
		
		$sql = "SELECT userid FROM tdo_user_accounts WHERE creation_timestamp >= $startDate AND creation_timestamp <= $endDate ORDER BY creation_timestamp";
        
        $result = mysql_query($sql, $link);
        
        if($result)
        {
			$userids = array();
            while($row = mysql_fetch_array($result))
            {
				if (!empty($row['userid']))
				{
					$userid = $row['userid'];
					$userids[] = $userid;
				}
            }
            
            TDOUtil::closeDBLink($link);
            return $userids;
        }
        else
		{
			error_log("TDOUser::getUserIDsWithDateRange() failed: " . mysql_error());
		}
		
        TDOUtil::closeDBLink($link);
        return false;
	}
	

    public static function usernameForUserId($userid)
    {
        if(!isset($userid))
            return false;
       $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$userid = mysql_real_escape_string($userid, $link);
		$result = mysql_query("SELECT username FROM tdo_user_accounts where userid='$userid'");

		if($result)
		{
			$responseArray = mysql_fetch_array($result);
			if($responseArray)
			{
				if(isset($responseArray['username']))
				{
					TDOUtil::closeDBLink($link);
					return TDOUtil::ensureUTF8($responseArray['username']);
				}
			}
			
		}
		else
		{
			error_log("Unable to get all username for user $userid");
		}

        TDOUtil::closeDBLink($link);
        return false;
    

    }
    
    public static function firstNameForUserId($userid)
    {
        if(!isset($userid))
            return false;
       $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$userid = mysql_real_escape_string($userid, $link);
		$result = mysql_query("SELECT first_name FROM tdo_user_accounts where userid='$userid'");

		if($result)
		{
			$responseArray = mysql_fetch_array($result);
			if($responseArray)
			{
				if(isset($responseArray['first_name']))
				{
					TDOUtil::closeDBLink($link);
					return TDOUtil::ensureUTF8($responseArray['first_name']);
				}
			}
			
		}
		else
		{
			error_log("Unable to get first name for user $userid");
		}

        TDOUtil::closeDBLink($link);
        return false;
    }
    
    
    public static function lastNameForUserId($userid)
    {
        if(!isset($userid))
            return false;
            
       $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$userid = mysql_real_escape_string($userid, $link);
		$result = mysql_query("SELECT last_name FROM tdo_user_accounts where userid='$userid'");

		if($result)
		{
			$responseArray = mysql_fetch_array($result);
			if($responseArray)
			{
				if(isset($responseArray['last_name']))
				{
					TDOUtil::closeDBLink($link);
					return TDOUtil::ensureUTF8($responseArray['last_name']);
				}
			}
			
		}
		else
		{
			error_log("Unable to get last name for user $userid");
		}

        TDOUtil::closeDBLink($link);
        return false;
    }
    
    
    public static function fullNameForUserId($userid)
    {
        if(!isset($userid))
            return false;
            
		$link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }

		$userid = mysql_real_escape_string($userid, $link);
		$result = mysql_query("SELECT first_name, last_name FROM tdo_user_accounts where userid='$userid'");

		if($result)
		{
			$responseArray = mysql_fetch_array($result);
			if($responseArray)
			{
				if(isset($responseArray['first_name']))
					$firstName = TDOUtil::ensureUTF8($responseArray['first_name']);
				else
					$firstName = '';
				if(isset($responseArray['last_name']))                    
					$lastName = TDOUtil::ensureUTF8($responseArray['last_name']);
				else 
					$lastName = '';
				
				$fullName = trim("$firstName $lastName");
				
				TDOUtil::closeDBLink($link);

				return $fullName;
			}
			
		}
		else
		{
			error_log("Unable to full name for user $userid");
		}

        TDOUtil::closeDBLink($link);
        return false;    
    
    }
	
	public static function displayNameForUserId($userid)
	{
		$displayName = TDOUser::fullNameForUserId($userid);
		if(empty($displayName))
			$displayName = TDOUser::usernameForUserId($userid);
		if(empty($displayName))
			$displayName = $userid;
		
		return $displayName;
	}
    
    public function displayName()
    {
        $displayName = $this->fullName();
        if(empty($displayName))
            $displayName = $this->username();
        if(empty($displayName))
            $displayName = $this->userId();
            
        return $displayName;
    }
    
    public function fullName()
    {
        $firstName = $this->firstName();
        if($firstName == NULL)
            $firstName = '';
        $lastName = $this->lastName();
        if($lastName == NULL)
            $lastName = '';
            
        $fullName = trim("$firstName $lastName"); 
    
        return $fullName;
    }
	
	public static function getLocaleForUserId($userid)
	{
		if(!isset($userid))
			return false;
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			return false;
		}
		$userid = mysql_real_escape_string($userid, $link);
		$result = mysql_query("SELECT locale FROM tdo_user_accounts where userid='$userid'");
		$locale = NULL;
		
		if($result)
		{
			$responseArray = mysql_fetch_array($result);
			if ( ($responseArray) && (isset($responseArray['locale'])) )
				$locale = $responseArray['locale'];
		}
		else
		{
			error_log("Unable to get locale for $userid");
		}
		
		TDOUtil::closeDBLink($link);
		return $locale;
	}
	
	public static function getBestMatchLocaleForUserId($userid)
	{
		if(!isset($userid))
			return false;
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			return false;
		}
		$userid = mysql_real_escape_string($userid, $link);
		$result = mysql_query("SELECT best_match_locale FROM tdo_user_accounts where userid='$userid'");
		$locale = NULL;
		
		if($result)
		{
			$responseArray = mysql_fetch_array($result);
			if ( ($responseArray) && (isset($responseArray['best_match_locale'])) )
				$locale = $responseArray['best_match_locale'];
		}
		else
		{
			error_log("Unable to get best match locale for $userid");
		}
		
		TDOUtil::closeDBLink($link);
		return $locale;
	}
	
	public static function displayInfoForUserId($userid)
	{
		$displayName = TDOUser::fullNameForUserId($userid);
		if (empty($displayName))
			$displayName = 'UNKNOWN';
		$username = TDOUser::usernameForUserId($userid);
		if (empty($username))
			$username = 'UNKNOWN';
		$lastSyncTimestamp = TDOUser::lastSyncActivityTimestampForUserID($userid);
		if (empty($lastSyncTimestamp))
			$lastSyncTimestamp = 0;
		
		return array('displayName' => $displayName,
					 'userName' => $username,
					 'lastSyncActivityTimestamp' => $lastSyncTimestamp);
	}
	
    public function fullImageURL($largeVersion=true)
    {
        if($this->imageGuid() != NULL && $this->imageUpdateTimestamp() != 0)
        {
            if($largeVersion)
                return S3_BASE_USER_IMAGE_URL_LARGE.$this->imageGuid();//.'?lastmod='.$this->imageUpdateTimestamp();;
            else
                return S3_BASE_USER_IMAGE_URL.$this->imageGuid();//.'?lastmod='.$this->imageUpdateTimestamp();
        }
        
        return NULL;
    }
    
	public static function adminLevel($userid)
    {
        if(!isset($userid))
            return false;
            
       $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$userid = mysql_real_escape_string($userid, $link);
		$result = mysql_query("SELECT admin_level FROM tdo_user_accounts where userid='$userid'");
		$adminLevel = ADMIN_LEVEL_NONE;

		if($result)
		{
			$responseArray = mysql_fetch_array($result);
			if ( ($responseArray) && (isset($responseArray['admin_level'])) )
				$adminLevel = (int)$responseArray['admin_level'];
		}
		else
		{
			error_log("Unable to get admin_level for $userid");
		}

        TDOUtil::closeDBLink($link);
        return $adminLevel;
    }
    
	public static function setAdminLevel($userid, $newLevel)
    {
        if(!isset($userid))
            return false;
		
		$newLevel = (int)$newLevel;
		
		// Don't let the level ever exceed the highest level
		if ($newLevel > ADMIN_LEVEL_ROOT)
			$newLevel = ADMIN_LEVEL_ROOT;
            
       $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$userid = mysql_real_escape_string($userid, $link);
		$result = mysql_query("UPDATE tdo_user_accounts SET admin_level=$newLevel WHERE userid='$userid'");

		if($result)
		{
			TDOUtil::closeDBLink($link);
			return true;
		}
		else
		{
			error_log("Unable to set admin level ($newLevel) for user: $userid");
		}

        TDOUtil::closeDBLink($link);
        return false;

    }

    public static function setLocaleForUser($userid, $locale)
    {
        if (empty($userid) || empty($locale))
            return false;
        $link = TDOUtil::getDBLink();
        if (!$link)
            return false;

        $userid = mysql_real_escape_string($userid);
        $locale = TDOInternalization::getUserBestMatchLocale($locale);

        if (!mysql_query("UPDATE tdo_user_accounts SET selected_locale='$locale' WHERE userid='$userid'", $link)) {
            error_log("TDOUser::setLanguageForUser failed: " . mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }

        TDOUtil::closeDBLink($link);
        return true;
    }

    public static function getLocaleForUser($userid)
    {
        if (empty($userid))
            return false;
        $link = TDOUtil::getDBLink();
        if (!$link)
            return false;

        $userid = mysql_real_escape_string($userid);
        $user_locale = '';
        if ($result = mysql_query("SELECT selected_locale, best_match_locale, locale FROM tdo_user_accounts WHERE userid='$userid'", $link)) {
            if ($row = mysql_fetch_assoc($result)) {
                if ($row['selected_locale'] !== '' && TDOInternalization::isAvailableLocale($row['selected_locale'])) {
                    $user_locale = $row['selected_locale'];
                } elseif ($row['best_match_locale'] !== '' && TDOInternalization::isAvailableLocale($row['best_match_locale'])) {
                    $user_locale = $row['best_match_locale'];
                } elseif ($row['locale'] !== '' && TDOInternalization::isAvailableLocale($row['locale'])) {
                    $user_locale = $row['locale'];
                } else {
                    $user_locale = DEFAULT_LOCALE;
                }
            }
            TDOUtil::closeDBLink($link);
        } else {
            $user_locale = TDOInternalization::getUserPreferredLocale();
        }
        return $user_locale;
    }

    public static function facebookIdForUserId($userid)
    {
        if(!isset($userid))
            return false;
            
       $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$userid = mysql_real_escape_string($userid, $link);
		$result = mysql_query("SELECT oauth_uid FROM tdo_user_accounts where userid='$userid'");

		if($result)
		{
			$responseArray = mysql_fetch_array($result);
			if($responseArray)
			{
				if(isset($responseArray['oauth_uid']))
				{
					TDOUtil::closeDBLink($link);
					return $responseArray['oauth_uid'];
				}
			}
			
		}
		else
		{
			error_log("Unable to get facebook id for $userid");
		}

        TDOUtil::closeDBLink($link);
        return false;

    }
    
    public static function linkUserToFacebookId($userid, $fbuserid)
    {
        if(empty($userid) || empty($fbuserid))
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
            
        $userid = mysql_real_escape_string($userid, $link);
        $fbuserid = mysql_real_escape_string($fbuserid, $link);
        $result = mysql_query("UPDATE tdo_user_accounts SET oauth_uid='$fbuserid', oauth_provider=".FB_OAUTH_PROVIDER." WHERE userid='$userid'");
        if(!$result)
        {
            error_log("linkUserToFacebookId failed: ".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        else
        {
            TDOUtil::closeDBLink($link);
            return true;
        }
    }
    
    public static function unlinkFacebookAccountForUser($userid)
    {
        if(empty($userid))
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
            
        $userid = mysql_real_escape_string($userid, $link);
       
        $result = mysql_query("UPDATE tdo_user_accounts SET oauth_uid=NULL, oauth_provider=NULL WHERE userid='$userid'");
        if(!$result)
        {
            error_log("unlinkFacebookAccountForUser failed: ".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        else
        {
            TDOUtil::closeDBLink($link);
            return true;
        }
    }
	
	public static function logUserAccountAction($userID, $ownerUserID, $changeType, $description)
	{
		if (empty($userID))
			return false;
		if (empty($ownerUserID))
			return false;
		if (empty($description))
			return false;
		
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
		
		$userID = mysql_real_escape_string($userID, $link);
		$ownerUserID = mysql_real_escape_string($ownerUserID, $link);
		$description = mysql_real_escape_string($description, $link);
		$nowTimestamp = time();
		
		$sql = "INSERT INTO tdo_user_account_log (userid, owner_userid, change_type, description, timestamp) VALUES ('$userID', '$ownerUserID', $changeType, '$description', $nowTimestamp)";
		$response = mysql_query($sql, $link);
		if (!$response)
		{
			error_log("TDOUser::logUserAccountAction($userID, $ownerUserID, $changeType) failed to insert into db: " . mysql_error($link));
			TDOUtil::closeDBLink($link);
			return false;
		}
		
		TDOUtil::closeDBLink($link);
		return true;
	}
	
	
	public static function getAccountLogForUser($userID)
	{
		if (empty($userID))
			return false;
		
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
		
		$userID = mysql_real_escape_string($userID, $link);
		$sql = "SELECT * FROM tdo_user_account_log WHERE userid='$userID' ORDER BY timestamp DESC";
        $result = mysql_query($sql, $link);
        if($result)
        {
			$accountLog = array();
            while($row = mysql_fetch_array($result))
            {
				$logItem = array();
				
				if (isset($row['userid']))
					$logItem['userid'] = $row['userid'];
				
				if (isset($row['owner_userid']))
					$logItem['owner_userid'] = $row['owner_userid'];
				
				if (isset($row['change_type']))
					$logItem['change_type'] = $row['change_type'];
				
				if (isset($row['description']))
					$logItem['description'] = $row['description'];
				
				if (isset($row['timestamp']))
					$logItem['timestamp'] = $row['timestamp'];
				
				$accountLog[] = $logItem;
            }
            
            TDOUtil::closeDBLink($link);
            return $accountLog;
        }
        else
            error_log("TDOUser::getAccountLogForUser($userID) failed: ".mysql_error($link));
		
		TDOUtil::closeDBLink($link);
		return false;
	}
	
	public static function lastSyncActivityTimestampForUserID($userID, $link=NULL)
	{
		if (!isset($userID))
		{
			error_log("TDOUser::lastSyncActivityTimestampForUserID() failed because userID is empty");
			return false;
		}
		
		$closeLink = false;
		if ($link == NULL)
		{
			$closeLink = true;
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				error_log("TDOUser::lastSyncActivityTimestampForUserID() could not get DB connection.");
				return false;
			}
		}
		
		$userID = mysql_real_escape_string($userID, $link);
		$sql = "SELECT timestamp FROM tdo_user_devices WHERE userid='$userID' ORDER BY timestamp DESC LIMIT 1";
		if ($result = mysql_query($sql, $link))
		{
			if ($row = mysql_fetch_array($result))
			{
				$timestamp = $row['timestamp'];
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return $timestamp;
			}
		}
		else
		{
			error_log("TDOUser::lastSyncActivityTimestampForUserID($userID) had a failure reading from the DB: " . mysql_error());
		}
		
		if ($closeLink)
			TDOUtil::closeDBLink($link);
		return false;
	}
	
    public static function permanentlyDeleteUserAccount($userid)
    {
        if(empty($userid))
        {
            error_log("permanentlyDeleteUserAccount called missing parameter");
            return false;
        }
        
        $user = TDOUser::getUserForUserId($userid);
        if(empty($user))
        {
            error_log("permanentlyDeleteUserAccount could not get user for user id: ".$userid);
            return false;
        }
        
        if($user->userAdminLevel() >= ADMIN_LEVEL_ROOT)
        {
            //Don't allow anyone to remove the root user
            error_log("Attempting to remove root user");
            return false;
        }
        
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOUser failed to get DB link");
            return false;
        }
        
        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("TDOUser failed to start transaction");
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        if(TDOUser::wipeOutDataForUser($userid, false, $link) == false)
        {
            error_log("Unable to wipe out data for user");
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $escapedUserId = mysql_real_escape_string($userid, $link);
        
        $sql = "DELETE FROM tdo_user_accounts WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }

        $sql = "DELETE FROM tdo_user_settings WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }        

        $sql = "DELETE FROM tdo_user_sessions WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $sql = "DELETE FROM tdo_user_settings WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }

        $sql = "DELETE FROM tdo_user_migrations WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $sql = "DELETE FROM tdo_email_verifications WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $sql = "DELETE FROM tdo_password_reset WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        } 
 
        
        $sql = "DELETE FROM tdo_user_account_log WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $sql = "DELETE FROM tdo_promo_codes WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $sql = "DELETE FROM tdo_promo_code_history WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $sql = "DELETE FROM tdo_autorenew_history WHERE subscriptionid IN (SELECT subscriptionid FROM tdo_subscriptions WHERE userid='$escapedUserId')";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $sql = "DELETE FROM tdo_subscriptions WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        $sql = "DELETE FROM tdo_user_payment_system WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $sql = "DELETE FROM tdo_stripe_user_info WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $sql = "DELETE FROM tdo_stripe_payment_history WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $sql = "DELETE FROM tdo_iap_payment_history WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
		$sql = "DELETE FROM tdo_smart_lists WHERE userid='$escapedUserId'";
		if(!mysql_query($sql, $link))
		{
			error_log("permanentlyDeleteUserAccount failed with error: ".mysql_error());
			mysql_query("ROLLBACK", $link);
			TDOUtil::closeDBLink($link);
			return false;
		}

        if(!mysql_query("COMMIT", $link))
        {
            error_log("permanentlyDeleteUserAccount failed to commit transaction error: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        TDOUtil::closeDBLink($link);
        return true;
    }
    
    public static function wipeOutDataForUser($userid, $createInbox=true, $link=NULL)
    {
        //Go through each of the user's lists and either delete it or remove the user from it
        $listsAndMembers = TDOList::getAllListsAndMembersForUser($userid);
        if($listsAndMembers === false)
        {
            error_log("Failed to get lists and members for user: ".$userid);
            return false;
        }
        
        if(empty($link))
        {
            $closeTransaction = true;
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOUser failed to get db link");
                return false;
            }
            if(!mysql_query("START TRANSACTION", $link))
            {
                error_log("wipeOutDataForUser couldn't start transaction");
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        else
            $closeTransaction = false;
        
        foreach($listsAndMembers as $list)
        {
            $listid = $list['listid'];
            $members = $list['members'];
            
            $userIsOwner = false;
            $otherOwner = false;
            $otherMember = false;
            foreach($members as $member)
            {
                $role = $member['membership_type'];
                if($member['userid'] != $userid)
                {
                    $otherMember = true;
                    if($role == LIST_MEMBERSHIP_OWNER)
                        $otherOwner = true;
                }
                else
                {
                    if($role == LIST_MEMBERSHIP_OWNER)
                        $userIsOwner = true;
                }
            }
            $canDeleteList = ($userIsOwner && !$otherMember);
            $canLeaveList = $otherOwner;
            
            if($canDeleteList)
            {
                if(TDOList::permanentlyDeleteList($listid, $link) == false)
                {
                    if($closeTransaction)
                    {
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                    }
                    error_log("wipeOutDataForUser failed to remove the user list: " . $listid);
                    return false;
                }
                
            }
            elseif($canLeaveList)
            {
                if(!TDOList::removeUserFromList($listid, $userid, $link))
                {
                    if($closeTransaction)
                    {
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                    }
                    error_log("wipeOutDataForUser failed to remove user from list: " . $listid);
                    return false;
                }
            }
            else
            {
                //If the user is the sole owner of a shared list, we won't delete their data
                if($closeTransaction)
                {
                    error_log("Trying to wipe data for user who is sole owner of a shared list");
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;                
            }
        }
        
        if(TDOContext::permanentlyDeleteContextsForUser($userid, $link) == false)
        {
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            error_log("wipeOutDataForUser failed to remove contexts for user");
            return false;
        }
        
        $sql = "DELETE FROM tdo_user_devices WHERE userid='".mysql_real_escape_string($userid, $link)."'";
        if(!mysql_query($sql, $link))
        {
            error_log("wipeOutDataForUser failed with error: ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
		
		$sql = "DELETE FROM tdo_smart_lists WHERE userid='".mysql_real_escape_string($userid, $link)."'";
		if(!mysql_query($sql, $link))
		{
			error_log("wipeOutDataForUser failed with error: ".mysql_error());
			if($closeTransaction)
			{
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
			}
			return false;
		}
		
        if($createInbox)
        {
            $list = new TDOList();
			$list->setName('Inbox');
			$list->setCreator($userid);
			
			if($list->addList($userid, NULL, $link))
			{
                $sql = "UPDATE tdo_user_settings SET user_inbox='".mysql_real_escape_string($list->listId(), $link)."' WHERE userid='".mysql_real_escape_string($userid, $link)."'";
                if(!mysql_query($sql, $link))
                {
                    error_log("wipeUserData failed to create user inbox with error: ".mysql_error());
                    if($closeTransaction)
                    {
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                    }
                    
                    return false;
                }
			}
			else
			{
				error_log("wipeUserData failed to create inbox");
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                
				return false;
			}
        }
        
        //Update the last_reset_timestamp to be now
        $sql = "UPDATE tdo_user_accounts SET last_reset_timestamp=".time()." WHERE userid='".mysql_real_escape_string($userid, $link)."'";
        if(!mysql_query($sql, $link))
        {
            error_log("wipeUserData failed to set last reset stamp: ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            
            return false;
        }        
        
        if($closeTransaction)
        {
            if(!mysql_query("COMMIT", $link))
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
            else
                TDOUtil::closeDBLink($link);
        }
        return true;
    }
    
    
    
    public static function changedNotificationPropertiesForUser($tdoChange)
    {
        $displayProperties = array();
        
        $changes = json_decode($tdoChange->changeData());
        
        if(isset($changes->{'role'}))
        {
            if($changes->{'role'} == "0")
            {
                $displayProperties['Role'] = "View Only";
            }
            else if($changes->{'role'} == "1")
            {
                $displayProperties['Role'] = "Member";
            }
            else if($changes->{'role'} == "2")
            {
                $displayProperties['Role'] = "Owner";
            }
        }

        return $displayProperties;
    }
    
    
    
	//
	// Public Getters/Setters
	//
    public function username()
    {
        if(empty($this->_publicPropertyArray['username']))
            return NULL;
        else
            return $this->_publicPropertyArray['username'];
    }
    
    public function setUsername($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['username']);
        else
            $this->_publicPropertyArray['username'] = TDOUtil::ensureUTF8($val);
    }
    
    public function firstName()
    {
        if(empty($this->_publicPropertyArray['firstname']))
            return NULL;
        else
            return $this->_publicPropertyArray['firstname'];
    }
    
    public function setFirstName($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['firstname']);
        else
            $this->_publicPropertyArray['firstname'] = TDOUtil::ensureUTF8($val);
    }   
    
    public function lastName()
    {
        if(empty($this->_publicPropertyArray['lastname']))
            return NULL;
        else
            return $this->_publicPropertyArray['lastname'];
    }
    
    public function setLastName($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['lastname']);
        else
            $this->_publicPropertyArray['lastname'] = TDOUtil::ensureUTF8($val);
    }   
    
    public function oauthUID()
    {
        if(empty($this->_publicPropertyArray['oauth_uid']))
            return NULL;
        else
            return $this->_publicPropertyArray['oauth_uid'];
    }
    
    public function setOauthUID($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['oauth_uid']);
        else
            $this->_publicPropertyArray['oauth_uid'] = $val;
    }   

    public function oauthProvider()
    {
        if(empty($this->_publicPropertyArray['oauth_provider']))
            return 0;
        else
            return $this->_publicPropertyArray['oauth_provider'];
    }
    
    public function setOauthProvider($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['oauth_provider']);
        else
            $this->_publicPropertyArray['oauth_provider'] = $val;
    }   
    public function password()
    {
        if(empty($this->_privatePropertyArray['password']))
            return NULL;
        else
            return $this->_privatePropertyArray['password'];
    }
    
    public function setPassword($val)
    {
        if(empty($val))
            unset($this->_privatePropertyArray['password']);
        else
            $this->_privatePropertyArray['password'] = TDOUser::encryptPassword($val);
    }

    public function userId()
    {
        if(empty($this->_publicPropertyArray['userid']))
            return NULL;
        else
            return $this->_publicPropertyArray['userid'];
    }
    
    public function setUserId($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['userid']);
        else
            $this->_publicPropertyArray['userid'] = $val;
    }   
	
    public function emailVerified()
    {
        if(empty($this->_publicPropertyArray['email_verified']))
            return 0;
        else
            return $this->_publicPropertyArray['email_verified'];        
    }
    public function setEmailVerified($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['email_verified']);
        else
            $this->_publicPropertyArray['email_verified'] = $val;
    }

    public function emailOptOut()
    {
        if(empty($this->_publicPropertyArray['email_opt_out']))
            return 0;
        else
            return $this->_publicPropertyArray['email_opt_out'];        
    }
    public function setEmailOptOut($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['email_opt_out']);
        else
            $this->_publicPropertyArray['email_opt_out'] = intval($val);
    }
    
    public function userAdminLevel()
    {
        if(empty($this->_publicPropertyArray['admin_level']))
            return 0;
        else
            return $this->_publicPropertyArray['admin_level'];
    }
    
    public function setUserAdminLevel($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['admin_level']);
        else
            $this->_publicPropertyArray['admin_level'] = $val;
    }
	
	public function creationTimestamp()
	{
		if(empty($this->_publicPropertyArray['creation_timestamp']))
			return 0;
		else
			return $this->_publicPropertyArray['creation_timestamp'];
	}
	
	public function setCreationTimestamp($val)
	{
        if(empty($val))
            unset($this->_publicPropertyArray['creation_timestamp']);
        else
            $this->_publicPropertyArray['creation_timestamp'] = $val;
	}
    
	public function locale()
	{
		if(empty($this->_publicPropertyArray['locale']))
			return NULL;
		else
			return $this->_publicPropertyArray['locale'];
	}
	
	public function setLocale($val)
	{
		if(empty($val))
			unset($this->_publicPropertyArray['locale']);
		else
			$this->_publicPropertyArray['locale'] = $val;
	}
	
	public function bestMatchLocale()
	{
		if(empty($this->_publicPropertyArray['best_match_locale']))
			return NULL;
		else
			return $this->_publicPropertyArray['best_match_locale'];
	}
	
	public function setBestMatchLocale($val)
	{
		if(empty($val))
			unset($this->_publicPropertyArray['best_match_locale']);
		else
			$this->_publicPropertyArray['best_match_locale'] = $val;
	}

    public function selectedLocale()
    {
        if(empty($this->_publicPropertyArray['selected_locale']))
            return NULL;
        else
            return $this->_publicPropertyArray['selected_locale'];
    }

    public function setselectedLocale($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['selected_locale']);
        else
            $this->_publicPropertyArray['selected_locale'] = $val;
    }
	
    public function lastResetTimestamp()
	{
		if(empty($this->_publicPropertyArray['last_reset_timestamp']))
			return 0;
		else
			return $this->_publicPropertyArray['last_reset_timestamp'];
	}
	
	public function setLastResetTimestamp($val)
	{
        if(empty($val))
            unset($this->_publicPropertyArray['last_reset_timestamp']);
        else
            $this->_publicPropertyArray['last_reset_timestamp'] = $val;
	}
    
    public function imageGuid()
    {
        if(empty($this->_publicPropertyArray['image_guid']))
            return NULL;
        else
            return $this->_publicPropertyArray['image_guid'];
    }
    
    public function setImageGuid($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['image_guid']);
        else
            $this->_publicPropertyArray['image_guid'] = $val;
    }
    
    public function imageUpdateTimestamp()
    {
        if(empty($this->_publicPropertyArray['image_update_timestamp']))
            return 0;
        else
            return $this->_publicPropertyArray['image_update_timestamp'];
    }
    
    public function setImageUpdateTimestamp($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['image_update_timestamp']);
        else
            $this->_publicPropertyArray['image_update_timestamp'] = $val;
    }

    public function userMessages()
    {
        if(empty($this->_publicPropertyArray['show_user_messages']))
            return NULL;
        else
            return json_decode($this->_publicPropertyArray['show_user_messages'], TRUE);
    }
    public function setUserMessages($val, $skipEncode = FALSE)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['show_user_messages']);
        else {
            if(!$skipEncode){
                $this->_publicPropertyArray['show_user_messages'] = json_encode($val);
            }else{
                $this->_publicPropertyArray['show_user_messages'] = $val;
            }
        }
    }

	public function matchPassword($pw)
	{
		$epw = $this->encryptPassword($pw);
		$upw = $this->password();

		return $epw == $upw;
	}

    
	private function encryptPassword($pw)
	{
		$epw = md5($pw);
		//if (!function_exists('hash'))
		//{
		//	$epw = md5(PW_SALT1 . $pw . PW_SALT2);  
		//}
		//else
		//{
		//	$epw = hash('sha256', PW_SALT1 . $pw . PW_SALT2);
		//}
		return $epw;
	}
}


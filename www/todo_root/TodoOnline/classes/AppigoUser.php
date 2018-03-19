<?php
//      AppigoUser
//      Used to handle all user data

// include files
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');
include_once('Facebook/config.php');
include_once('Facebook/facebook.php');
include_once('TodoOnline/DBConstants.php');

define ('APPIGO_FIRST_NAME_LENGTH', 60);
define ('APPIGO_LAST_NAME_LENGTH', 60);
define ('APPIGO_USER_NAME_LENGTH', 100);
define ('APPIGO_PASSWORD_LENGTH', 64);

define ('APPIGO_ACCOUNT_LOG_TYPE_PASSWORD', 1);
define ('APPIGO_ACCOUNT_LOG_TYPE_USERNAME', 2);
define ('APPIGO_ACCOUNT_LOG_TYPE_NAME', 3);
define ('APPIGO_ACCOUNT_LOG_TYPE_MAIL_PASSWORD_RESET', 7);
define ('APPIGO_ACCOUNT_LOG_TYPE_PASSWORD_RESET', 8);

class AppigoUser extends TDODBObject
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
		$sql = "DELETE FROM appigo_user_accounts WHERE userid='$userid'";
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
			$sql = "DELETE FROM appigo_user_accounts WHERE userid='$uid'";
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
		$sql = "SELECT COUNT(*) FROM appigo_user_accounts WHERE username='$username'";
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
			error_log("AppigoUser::userIdForUserName() called with a NULL username");
            return false;
		}
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
			error_log("AppigoUser::userIdForUserName('" . $username . "') could not connect to mysql");
            return false;
        }
		$username = mysql_real_escape_string($username, $link);
		$result = mysql_query("SELECT userid FROM appigo_user_accounts where username='$username'");
        
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
			error_log("Unable to fetch user $id");
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
		$sql = "SELECT COUNT(*) FROM appigo_user_accounts WHERE userid='$userid'";
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
    

	public function addUser()
	{
		if($this->username() == NULL)
        {
            error_log("AppigoUser::addUser failed with no username");
			return false;
		}
        if($this->password() == NULL)
        {
            error_log("AppigoUser::addUser failed with no password");
			return false;
		}

		if(AppigoUser::existsUsername($this->username()) == true)
        {
            error_log("AppigoUser::addUser failed. Username already exists");
			return false;
		}
        
        // Allow adding pigeon
        if($this->username() != "pigeon")
        {
            if(AppigoUser::isValidUsername($this->username()) == false)
            {
                error_log("AppigoUser::addUser failed. Invalid username");
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
        
        $firstName = mb_strcut($this->firstName(), 0, APPIGO_FIRST_NAME_LENGTH, 'UTF-8');
		$firstName = mysql_real_escape_string($firstName, $link);
        
        $lastName = mb_strcut($this->lastName(), 0, APPIGO_LAST_NAME_LENGTH, 'UTF-8');
		$lastName = mysql_real_escape_string($lastName, $link);
        $emailVerified = intval($this->emailVerified());
        $lastResetTimestamp = intval($this->lastResetTimestamp());
        $emailOptOut = intval($this->emailOptOut());
        
		$creationTimestamp = time();
        $imageGuid = mysql_real_escape_string($this->imageGuid(), $link);
        $imageUpdateTimestamp = intval($this->imageUpdateTimestamp());
        
        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("AppigoUser::Couldn't start transaction".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        
		$sql = "INSERT INTO appigo_user_accounts (userid, username, email_verified, email_opt_out, password, first_name, last_name, creation_timestamp, last_reset_timestamp, image_guid, image_update_timestamp) VALUES ('$userid', '$username', $emailVerified , $emailOptOut, '$password', '$firstName', '$lastName', $creationTimestamp, $lastResetTimestamp, '$imageGuid', $imageUpdateTimestamp)";
		$response = mysql_query($sql, $link);
		if($response)
		{
            //Add entry for user to verify their email address
            $emailVerifyURL = NULL;
            if($this->emailVerified() == 0)
            {
                $emailVerification = new AppigoEmailVerification();
                $emailVerification->setUserId($this->userId());
                $emailVerification->setUsername($this->username());
                
                if($emailVerification->addEmailVerification($link))
                {
                    $emailVerifyURL = "http://".SUPPORT_SITE_BASE_URL."?verifyemail=true&verificationid=".$emailVerification->verificationId();
                }
                else
                    error_log("AppigoUser::addUser() failed to add email verification for user ".$this->userId());
            }
        
            if(!mysql_query("COMMIT", $link))
			{
				error_log("AppigoUser::Couldn't commit transaction adding user".mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}

			TDOUtil::closeDBLink($link);
            
            $this->sendSignupEmail($emailVerifyURL);
            
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

    
    public function sendSignupEmail($emailVerifyURL)
    {
        $email = TDOMailer::validate_email($this->username());
        if($email)
        {
            $userDisplayName = $this->displayName();
            if(empty($userDisplayName))
                $userDisplayName = $this->username();
            
            // If the user has a valid subscription, generate a task creation email and send it to them
            $taskCreationEmail = NULL;
            
            $subject = _('Appigo Support Account');

            $textBody = _('Hello') . $userDisplayName . ",\n\n";
            $htmlBody = '<p>' . _('Hello') . $userDisplayName . ",</p>\n";
            
            $textBody .= _('Welcome to Appigo Support.');
            $htmlBody .= '<p>' . _('Welcome to Appigo Support.') . "</p>\n";
            
            if(!empty($emailVerifyURL))
            {
                $textBody .= _('Please complete your registration by clicking the link below to verify your email address.') . "\n" . $emailVerifyURL;
                $htmlBody .= '<p>' . sprintf(_('Please complete your registration by %sclicking here%s to verify your email address.'), '<a href="' . $emailVerifyURL . '">', '</a>') . "</p>\n";
            }
            else
                error_log("Email verify URL was empty for user: " . $this->username());

            $textBody .= "\n\n" . _('Thank you from the Appigo Support Team') . "\n";
            $htmlBody .= '<p>' . _('Thank you from the Appigo Support Team') . "</p>\n";
            
            TDOMailer::sendAppigoHTMLAndTextEmail($email, $subject, SUPPORT_EMAIL_FROM_NAME, SUPPORT_EMAIL_FROM_ADDR, $htmlBody, $textBody);        
        }
        else
            error_log("AppigoUser could not validate email address: ".$this->username());

    }
    
	public function updateUser()
	{
		if($this->userId() == NULL)
		{
			error_log("AppigoUser::updateUser failed calling because no userId was set");
			return false;
		}
		
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
			error_log("AppigoUser::updateUser failed getting a DB link");
           return false;
        }
		$userid = mysql_real_escape_string($this->userId(), $link);

        if($this->username() != NULL)
        {
            if(AppigoUser::isValidUsername($this->username()) == false)
            {
                error_log("AppigoUser::updateUser invalid username");
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
            $firstName = mb_strcut($this->firstName(), 0, APPIGO_FIRST_NAME_LENGTH, 'UTF-8');
            $updateString .= ", first_name='".mysql_real_escape_string($firstName, $link)."'";
        }
        else
            $updateString .= ", first_name=NULL";

        if($this->lastName() != NULL)
        {
            $lastName = mb_strcut($this->lastName(), 0, APPIGO_LAST_NAME_LENGTH, 'UTF-8');
            $updateString .= ", last_name='".mysql_real_escape_string($lastName, $link)."'";
        }
        else
            $updateString .=", last_name=NULL";
        
        if($this->imageGuid() != NULL)
            $updateString .= ", image_guid='".mysql_real_escape_string($this->imageGuid(), $link)."'";
        else
            $updateString .= ", image_guid=NULL";
            
        $updateString .= ", email_verified=".intval($this->emailVerified());
        $updateString .= ", last_reset_timestamp=".intval($this->lastResetTimestamp());
        $updateString .= ", email_opt_out=".intval($this->emailOptOut());
        $updateString .= ", image_update_timestamp=".intval($this->imageUpdateTimestamp());
        
        $sql = "UPDATE appigo_user_accounts SET $updateString WHERE userid='$userid'";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
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
        
		$result = mysql_query("SELECT * FROM appigo_user_accounts where userid='$id'");

		if($result)
		{
			$row = mysql_fetch_array($result);
			if($row)
			{
				$user = AppigoUser::userFromRow($row);

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

	public static function getUserForUsername($username)
	{
        if(!isset($username))
		{
			error_log("AppigoUser::getUserForUsername() called with a NULL username");
            return false;
		}
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
			error_log("AppigoUser::getUserForUsername('" . $username . "') could not connect to mysql");
           return false;
        }
		$username = mysql_real_escape_string($username, $link);
		$result = mysql_query("SELECT * FROM appigo_user_accounts where username='$username'");

		if($result)
		{
			$resultsArray = mysql_fetch_array($result);
			if($resultsArray)
			{
                $user = AppigoUser::userFromRow($resultsArray);
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
    
    public static function getAllUsers()
	{
		// error_log("getAllUsers() was called");
		
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }
		$result = mysql_query("SELECT userid,username,password,first_name,last_name,admin_level FROM appigo_user_accounts ");

		if($result)
		{
			$users = array();
			while($row = mysql_fetch_array($result))
			{
				$user = AppigoUser::userFromRow($row);
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
        $user = new AppigoUser();
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
        
        return $user;
    }
    

    // Used in admin pages only
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
        
        $sql = "SELECT * FROM appigo_user_accounts WHERE ";
        
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
                $user = AppigoUser::userFromRow($row);
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
		$result = mysql_query("SELECT COUNT(*) FROM appigo_user_accounts ");

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
		
		$sql = "SELECT COUNT(*) FROM appigo_user_accounts WHERE creation_timestamp >= $startDate AND creation_timestamp <= $endDate";
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
			error_log("AppigoUser::getNewUserCountWithDateRange($startDate, $endDate): Unable to get user count");
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
		
		$sql = "SELECT userid FROM appigo_user_accounts WHERE creation_timestamp >= $startDate AND creation_timestamp <= $endDate ORDER BY creation_timestamp";
        
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
			error_log("AppigoUser::getUserIDsWithDateRange() failed: " . mysql_error());
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
		$result = mysql_query("SELECT username FROM appigo_user_accounts where userid='$userid'");

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
		$result = mysql_query("SELECT first_name FROM appigo_user_accounts where userid='$userid'");

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
		$result = mysql_query("SELECT last_name FROM appigo_user_accounts where userid='$userid'");

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
		$result = mysql_query("SELECT first_name, last_name FROM appigo_user_accounts where userid='$userid'");

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
		$displayName = AppigoUser::fullNameForUserId($userid);
		if(empty($displayName))
			$displayName = AppigoUser::usernameForUserId($userid);
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
	
	public static function displayInfoForUserId($userid)
	{
		$displayName = AppigoUser::fullNameForUserId($userid);
		if (empty($displayName))
			$displayName = 'UNKNOWN';
		$username = AppigoUser::usernameForUserId($userid);
		if (empty($username))
			$username = 'UNKNOWN';
		
		return array('displayName' => $displayName,
					 'userName' => $username);
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
		$result = mysql_query("SELECT admin_level FROM appigo_user_accounts where userid='$userid'");
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
		$result = mysql_query("UPDATE appigo_user_accounts SET admin_level=$newLevel WHERE userid='$userid'");

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
		
		$sql = "INSERT INTO appigo_user_account_log (userid, owner_userid, change_type, description, timestamp) VALUES ('$userID', '$ownerUserID', $changeType, '$description', $nowTimestamp)";
		$response = mysql_query($sql, $link);
		if (!$response)
		{
			error_log("AppigoUser::logUserAccountAction($userID, $ownerUserID, $changeType) failed to insert into db: " . mysql_error($link));
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
		$sql = "SELECT * FROM appigo_user_account_log WHERE userid='$userID' ORDER BY timestamp DESC";
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
            error_log("AppigoUser::getAccountLogForUser($userID) failed: ".mysql_error($link));
		
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
        
        $user = AppigoUser::getUserForUserId($userid);
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
            error_log("AppigoUser failed to get DB link");
            return false;
        }
        
        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("AppigoUser failed to start transaction");
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        if(AppigoUser::wipeOutDataForUser($userid, false, $link) == false)
        {
            error_log("Unable to wipe out data for user");
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $escapedUserId = mysql_real_escape_string($userid, $link);
        
        $sql = "DELETE FROM appigo_user_accounts WHERE userid='$escapedUserId'";
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
        if (empty($link)) {
            $closeTransaction = true;
        } else {
            $closeTransaction = false;
        }
        //Update the last_reset_timestamp to be now
        $sql = "UPDATE appigo_user_accounts SET last_reset_timestamp=" . time() . " WHERE userid='" . mysql_real_escape_string($userid, $link) . "'";
        if (!mysql_query($sql, $link))
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
            $this->_privatePropertyArray['password'] = AppigoUser::encryptPassword($val);
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


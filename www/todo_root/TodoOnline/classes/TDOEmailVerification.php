<?php
	//      TDOInvitation
	//      Used to handle all user data
	
	// include files
	include_once('AWS/sdk.class.php');
	include_once('TodoOnline/base_sdk.php');
	include_once('Facebook/config.php');
	include_once('Facebook/facebook.php');
	
	
	class TDOEmailVerification extends TDODBObject
	{
		
		public function __construct()
		{
			$this->set_to_default();      
		}
		
		public function set_to_default()
		{
			// clears values without going to database
            $this->setVerificationId(NULL);
            $this->setUserId(NULL);
            $this->setUsername(NULL);
            $this->setTimestamp(0);
        }
		
		public function deleteEmailVerification()
		{
            if($this->verificationId() == NULL)
                return false;
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOEmailVerification unable to get link");
                return false;
            }
            $verificationid = mysql_real_escape_string($this->verificationId(), $link);
			$sql = "DELETE FROM tdo_email_verifications WHERE verificationid='$verificationid'";
			$response = mysql_query($sql, $link);
            if($response)
            {
                TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("TDOEmailVerification delete failed: ".mysql_error());
			
            TDOUtil::closeDBLink($link);
			return false;
		}
        
        public static function deleteExistingEmailVerificationForUser($userid)
        {
            if(!isset($userid))
                return false;
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOEmailVerification unable to get link");
                return false;
            }
            $userid = mysql_real_escape_string($userid, $link);
			$sql = "DELETE FROM tdo_email_verifications WHERE userid='$userid'";
			$response = mysql_query($sql, $link);
            if($response)
            {
                TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("TDOEmailVerification delete failed: ".mysql_error());
			
            TDOUtil::closeDBLink($link);
			return false;            
        }
		
		public function addEmailVerification($link=NULL)
		{
			if($this->userId() == NULL || $this->username() == NULL)
			{
				error_log("TDOEmailVerification::addPassword failed because required properties were not set");
				return false;
			}
            
            if($link == NULL)
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {   
                    error_log("TDOEmailVerification unable to get link");
                    return false;               
                }
            }
            else
                $closeDBLink = false;
            
            if($this->verificationId() == NULL)
                $this->setVerificationId(TDOUtil::uuid());
			
			$verificationid = mysql_real_escape_string($this->verificationId(), $link);
			$userId = mysql_real_escape_string($this->userId(), $link);
            $userName = mysql_real_escape_string($this->username(), $link);
            $timestamp = time();
			
			$sql = "INSERT INTO tdo_email_verifications (verificationid, userid, username, timestamp) VALUES ('$verificationid', '$userId', '$userName', $timestamp)";
			
			$response = mysql_query($sql, $link);
			if($response)
            {
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOEmailVerification::addEmailVerification failed: ".mysql_error());
			}
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
		}
        
        public static function getEmailVerificationForVerificationId($verificationid)
        {
            if(empty($verificationid))
                return false;
			
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOEmailVerification::getEmailVerificationForVerificationId() unable to get link");
                return false;
            }
            
            $verificationid = mysql_real_escape_string($verificationid, $link);
            $sql = "SELECT * FROM tdo_email_verifications WHERE verificationid='$verificationid'";
			$response = mysql_query($sql, $link);
			
			if($response)
			{
                $row = mysql_fetch_array($response);
				if($row)
                {
					$verificationObj = TDOEmailVerification::emailVerificationForRow($row);
					
                    TDOUtil::closeDBLink($link);
                    return $verificationObj;
                }
				
			}
            else
                error_log("getEmailVerificationForVerificationId failed: ".mysql_error());
			
            TDOUtil::closeDBLink($link);
            return false;
			
        }
		
        public static function getEmailVerificationForVerificationIdAndUserId($verificationid, $userId)
        {
            if(empty($verificationid) || empty($userId))
                return false;
                
            $link = TDOUtil::getDBLink();
            if(!$link)
            {   
                error_log("TDOEmailVerification unable to get link");
                return false;               
            }
            
            $verificationid = mysql_real_escape_string($verificationid, $link);
            $userId = mysql_real_escape_string($userId, $link);
            $sql = "SELECT * FROM tdo_email_verifications WHERE verificationid='$verificationid' AND userid='$userId'";
			$response = mysql_query($sql, $link);
			
			if($response)
			{
                $row = mysql_fetch_array($response);
				if($row)
                {
					$verificationObj = TDOEmailVerification::emailVerificationForRow($row);
                        
                    TDOUtil::closeDBLink($link);
                    return $verificationObj;  
                }

			}
            else
                error_log("getEmailVerificationForVerificationIdAndUserId failed: ".mysql_error());
		
            TDOUtil::closeDBLink($link);
            return false;			

        }
		
		public static function getVerificationIdForUserId($userId)
		{
            if(empty($userId))
                return false;
			
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOEmailVerification unable to get link");
                return false;
            }
            
            $userId = mysql_real_escape_string($userId, $link);
            $sql = "SELECT verificationid FROM tdo_email_verifications WHERE userid='$userId'";
			$response = mysql_query($sql, $link);
			
			if($response)
			{
                $row = mysql_fetch_array($response);
				if($row)
                {
					if(isset($row['verificationid']))
					{
						$verificationId = $row['verificationid'];
						TDOUtil::closeDBLink($link);
						return $verificationId;
					}
				}
			}
			
            TDOUtil::closeDBLink($link);
            return false;
		}
		
        public static function sendVerificationEmailForUser($user)
        {
            if(TDOEmailVerification::deleteExistingEmailVerificationForUser($user->userId()))
            {
                $email = TDOMailer::validate_email($user->username());
                if($email)
                {
                    $userDisplayName = $user->displayName();
                    $emailVerifyURL = NULL;
                
                    $emailVerification = new TDOEmailVerification();
                    $emailVerification->setUserId($user->userId());
                    $emailVerification->setUsername($user->username());
                    
                    if($emailVerification->addEmailVerification())
                    {
                        $emailVerifyURL = SITE_PROTOCOL . SITE_BASE_URL."?verifyemail=true&verificationid=".$emailVerification->verificationId();
                        
                        if(TDOMailer::sendEmailVerificationEmail($userDisplayName, $email, $emailVerifyURL))
                        {
                            return true;
                        }
                        else
                            error_log("TDOEmailVerification failed to send verification email for user ".$user->userId());

                    }
                    else
                        error_log("TDOEmailVerification failed to add email verification for user ".$user->userId());
                }
                else
                    error_log("TDOEmailVerification could not validate email address: ".$user->username());

            }
            
            return false;
        }
        
        public static function emailVerificationForRow($row)
        {
            $verification = new TDOEmailVerification();
            if(isset($row['verificationid']))
                $verification->setVerificationId($row['verificationid']);
            if(isset($row['userid']))
                $verification->setUserId($row['userid']);
            if(isset($row['timestamp']))
                $verification->setTimestamp($row['timestamp']);
            if(isset($row['username']))
                $verification->setUsername($row['username']);
            
            return $verification;
        }
                
		
		public function verificationId()
		{
            if(empty($this->_privatePropertyArray['verificationid']))
                return NULL;
            else
                return $this->_privatePropertyArray['verificationid'];
		}
		public function setVerificationId($val)
		{
			if(empty($val))
                unset($this->_privatePropertyArray['verificationid']);
            else
                $this->_privatePropertyArray['verificationid'] = $val;
		}
        
		public function userId()
		{
            if(empty($this->_privatePropertyArray['userid']))
                return NULL;
            else
                return $this->_privatePropertyArray['userid'];
		}
		public function setUserId($val)
		{
			if(empty($val))
                unset($this->_privatePropertyArray['userid']);
            else
                $this->_privatePropertyArray['userid'] = $val;
		}

		public function username()
		{
            if(empty($this->_privatePropertyArray['username']))
                return NULL;
            else
                return $this->_privatePropertyArray['username'];
		}
		public function setUsername($val)
		{
			if(empty($val))
                unset($this->_privatePropertyArray['username']);
            else
                $this->_privatePropertyArray['username'] = $val;
		}
    
		public function timestamp()
		{
            if(empty($this->_privatePropertyArray['timestamp']))
                return NULL;
            else
                return $this->_privatePropertyArray['timestamp'];
		}
		public function setTimestamp($val)
		{
			if(empty($val))
                unset($this->_privatePropertyArray['timestamp']);
            else
                $this->_privatePropertyArray['timestamp'] = $val;
		}
		        
    }

<?php
	//      AppigoPasswordReset
	//      Used to handle all user data
	
	// include files
	include_once('AWS/sdk.class.php');
	include_once('TodoOnline/base_sdk.php');
	include_once('Facebook/config.php');
	include_once('Facebook/facebook.php');
	
	
	class AppigoPasswordReset extends TDODBObject
	{
		
		public function __construct()
		{
			$this->set_to_default();      
		}
		
		public function set_to_default()
		{
			// clears values without going to database
            $this->setResetId(NULL);
            $this->setUserId(NULL);
            $this->setUsername(NULL);
            $this->setTimestamp(0);
        }
		
		public function deletePasswordReset()
		{
            if($this->resetId() == NULL)
                return false;
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("AppigoPasswordReset unable to get link");
                return false;
            }
            $resetid = mysql_real_escape_string($this->resetId(), $link);
			$sql = "DELETE FROM appigo_password_reset WHERE resetid='$resetid'";
			$response = mysql_query($sql, $link);
            if($response)
            {
                TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("AppigoPasswordReset delete failed: ".mysql_error());
			
            TDOUtil::closeDBLink($link);
			return false;
		}
        
        public static function deleteExistingPasswordResetForUser($userid)
        {
            if(!isset($userid))
                return false;
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("AppigoPasswordReset unable to get link");
                return false;
            }
            $userid = mysql_real_escape_string($userid, $link);
			$sql = "DELETE FROM appigo_password_reset WHERE userid='$userid'";
			$response = mysql_query($sql, $link);
            if($response)
            {
                TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("AppigoPasswordReset delete failed: ".mysql_error());
			
            TDOUtil::closeDBLink($link);
			return false;            
        }
		
		public function addPasswordReset()
		{
			if($this->userId() == NULL || $this->username() == NULL)
			{
				error_log("AppigoPasswordReset::addPassword failed because required properties were not set");
				return false;
			}
            $link = TDOUtil::getDBLink();
            if(!$link)
            {   
                error_log("AppigoPasswordReset unable to get link");
                return false;               
            }
            if($this->resetId() == NULL)
                $this->setResetId(TDOUtil::uuid());
			
			$resetId = mysql_real_escape_string($this->resetId(), $link);
			$userId = mysql_real_escape_string($this->userId(), $link);
            $userName = mysql_real_escape_string($this->username(), $link);
            $timestamp = time();
			
			$sql = "INSERT INTO appigo_password_reset (resetid, userid, username, timestamp) VALUES ('$resetId', '$userId', '$userName', $timestamp)";
			
			$response = mysql_query($sql, $link);
			if($response)
            {
                TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("AppigoPasswordReset::addPasswordReset failed: ".mysql_error());
			}
            
            TDOUtil::closeDBLink($link);
            return false;
		}
        
        public static function getPasswordResetForResetIdAndUserId($resetId, $userId)
        {
            if(empty($resetId) || empty($userId))
                return false;
                
            $link = TDOUtil::getDBLink();
            if(!$link)
            {   
                error_log("AppigoPasswordReset unable to get link");
                return false;               
            }
            
            $resetId = mysql_real_escape_string($resetId, $link);
            $userId = mysql_real_escape_string($userId, $link);
            $sql = "SELECT * FROM appigo_password_reset WHERE resetid='$resetId' AND userid='$userId'";
			$response = mysql_query($sql, $link);
			
			if($response)
			{
                $row = mysql_fetch_array($response);
				if($row)
                {
					$resetObj = AppigoPasswordReset::PasswordResetForRow($row);
                        
                    TDOUtil::closeDBLink($link);
                    return $resetObj;  
                }

			}
            else
                error_log("getPasswordResetForResetIdAndUserId failed: ".mysql_error());
		
            TDOUtil::closeDBLink($link);
            return false;			

        }
		
        public static function PasswordResetForRow($row)
        {
            $reset = new AppigoPasswordReset();
            if(isset($row['resetid']))
                $reset->setResetId($row['resetid']);
            if(isset($row['userid']))
                $reset->setUserId($row['userid']);
            if(isset($row['timestamp']))
                $reset->setTimestamp($row['timestamp']);
            if(isset($row['username']))
                $reset->setUsername($row['username']);
            
            return $reset;
        }
                
		
		public function resetId()
		{
            if(empty($this->_privatePropertyArray['resetid']))
                return NULL;
            else
                return $this->_privatePropertyArray['resetid'];
		}
		public function setResetId($val)
		{
			if(empty($val))
                unset($this->_privatePropertyArray['resetid']);
            else
                $this->_privatePropertyArray['resetid'] = $val;
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

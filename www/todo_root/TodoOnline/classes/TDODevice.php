<?php
	//      TDOInvitation
	//      Used to handle all user data
	
	// include files
	include_once('AWS/sdk.class.php');
	include_once('TodoOnline/base_sdk.php');
	include_once('Facebook/config.php');
	include_once('Facebook/facebook.php');
	
	
	class TDODevice extends TDODBObject
	{
		
		public function __construct()
		{
			$this->set_to_default();      
		}
		
		public function set_to_default()
		{
			// clears values without going to database
            $this->setDeviceId(NULL);
            $this->setUserId(NULL);
            $this->setUserDeviceId(NULL);
            $this->setSessionId(NULL);
            $this->setDeviceType(NULL);
            $this->setOSVersion(NULL);
            $this->setAppId(NULL);
            $this->setAppVersion(NULL);
        }
		
		public static function deleteDevice($deviceId)
		{
            if(empty($deviceId))
                return false;
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDODevice::deleteDevice unable to get link");
                return false;
            }

            $deviceId = mysql_real_escape_string($deviceId, $link);

			$sql = "DELETE FROM tdo_user_devices WHERE deviceid='$deviceId'";
			$response = mysql_query($sql, $link);
            if($response)
            {
                TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("TDODevice::deleteDevice delete failed: ".mysql_error());
			
            TDOUtil::closeDBLink($link);
			return false;
		}

        
		public static function updateDeviceForUserAndSession($userid, $sessionid, $errornumber = 0, $errormessage = NULL)
		{
            //			error_log("TDOChangeLog::addChangeLog()");
			$link = TDOUtil::getDBLink();
			
            if(empty($userid))
                return false;
            
            if(empty($sessionid))
                return false;
            
			$realUserId = mysql_real_escape_string($userid, $link);
            $realSessionId = mysql_real_escape_string($sessionid, $link);
            
       		$nowTimestamp = time();
            
            $updateString = "timestamp = $nowTimestamp";
                
            if($errornumber != 0)
                $updateString .= ", error_number=$errornumber";
            else
                $updateString .= ", error_number=0";
            
            if($errormessage != NULL)
            {
                $realErrorMessage = mysql_real_escape_string($errormessage, $link);                    
                $updateString .= ", error_message='$realErrorMessage'";
            }
            else
                $updateString .= ", error_message=NULL";
                
            $sql = "UPDATE tdo_user_devices SET $updateString WHERE userid='$realUserId' AND sessionid = '$realSessionId'";
            
            $result = mysql_query($sql, $link);
            if(!$result)
            {
                error_log("TDODevice::updateOrAddDevice failed to add device with error: ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            TDOUtil::closeDBLink($link);
            return true;
		}
        
        
        
		public static function updateOrAddDevice($userid, $userdeviceid, $sessionid, $devicetype, $osversion, $appid, $appversion, $errornumber = 0, $errormessage = NULL)
		{
            $hasDevice = false;
            
            //			error_log("TDOChangeLog::addChangeLog()");
			
            if(empty($userid))
                return false;
            
            if(empty($userdeviceid))
                return false;
            
            if(empty($sessionid))
                return false;
            
            $link = TDOUtil::getDBLink();
            
            if(empty($link))
                return false;
            
			$realUserId = mysql_real_escape_string($userid, $link);
			$realDeviceId = mysql_real_escape_string($userdeviceid, $link);
            $realSessionId = mysql_real_escape_string($sessionid, $link);

            $sql = "SELECT COUNT(*) FROM tdo_user_devices WHERE userid='$realUserId' AND user_deviceid = '$realDeviceId'";
            $response = mysql_query($sql, $link);
            if($response)
            {
                $total = mysql_fetch_array($response);
                if($total && isset($total[0]) && $total[0] == 1)
                {
                    $hasDevice = true;
                }
            }
            
       		$nowTimestamp = time();
            
            if($hasDevice == true)
            {
                $updateString = "sessionid = '$realSessionId'";
                
                if(!empty($devicetype))
                {
                    $realDeviceType = mysql_real_escape_string($devicetype, $link);
                    $updateString .= ", devicetype='$realDeviceType'";
                }
                else
                    $updateString .= ", devicetype=NULL";
                    
                
                if(!empty($osversion))
                {
                    $realOSVersion = mysql_real_escape_string($osversion, $link);
                    $updateString .= ", osversion='$realOSVersion'";
                }
                else
                    $updateString .= ", osversion=NULL";
                
                if(!empty($appid))
                {
                    $realAppId = mysql_real_escape_string($appid, $link);
                    $updateString .= ", appid='$realAppId'";
                }
                else
                    $updateString .= ", appid=NULL";

                if(!empty($appversion))
                {
                    $realAppVersion = mysql_real_escape_string($appversion, $link);
                    $updateString .= ", appversion='$realAppVersion'";
                }
                else
                    $updateString .= ", appversion=NULL";

                if($errornumber != 0)
                    $updateString .= ", error_number=$errornumber";
                else
                    $updateString .= ", error_number=0";
                
                if($errormessage != NULL)
                {
                    $realErrorMessage = mysql_real_escape_string($errormessage, $link);                    
                    $updateString .= ", error_message=$realErrorMessage";
                }
                else
                    $updateString .= ", error_message=NULL";
                
                $updateString .= ", timestamp = $nowTimestamp";
                
                $sql = "UPDATE tdo_user_devices SET $updateString WHERE userid='$realUserId' AND user_deviceid = '$realDeviceId'";
                
                $result = mysql_query($sql, $link);
                if(!$result)
                {
                    error_log("TDODevice::updateOrAddDevice failed to add device with error: ".mysql_error());
                    TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            else
            {
                $deviceID = TDOUtil::uuid();
                
                $nameString = "deviceid, user_deviceid, userid, sessionid";
                $valueString = "'$deviceID', '$realDeviceId', '$realUserId', '$realSessionId'";

                if(!empty($devicetype))
                {
                    $realDeviceType = mysql_real_escape_string($devicetype, $link);
                    $nameString = $nameString.", devicetype";
                    $valueString = $valueString.", '$realDeviceType'";
                }

                if(!empty($osversion))
                {
                    $realOSVersion = mysql_real_escape_string($osversion, $link);
                    $nameString = $nameString.", osversion";
                    $valueString = $valueString.", '$realOSVersion'";
                }

                if(!empty($appid))
                {
                    $realAppId = mysql_real_escape_string($appid, $link);
                    $nameString = $nameString.", appid";
                    $valueString = $valueString.", '$realAppId'";
                }

                if(!empty($appversion))
                {
                    $realAppVersion = mysql_real_escape_string($appversion, $link);
                    $nameString = $nameString.", appversion";
                    $valueString = $valueString.", '$realAppVersion'";
                }

                if($errornumber != 0)
                {
                    $nameString = $nameString.", error_number";
                    $valueString = $valueString.", $errornumber";
                }

                if($errormessage != NULL)
                {
                    $realErrorMessage = mysql_real_escape_string($errormessage, $link);                    
                    $nameString = $nameString.", error_message";
                    $valueString = $valueString.", '$realErrorMessage'";
                }
                
                $sql = "INSERT INTO tdo_user_devices (".$nameString.") VALUES (".$valueString.")";
                
                $result = mysql_query($sql, $link);
                if(!$result)
                {
                    error_log("TDODevice::updateOrAddDevice failed to add device with error: ".mysql_error());
                    TDOUtil::closeDBLink($link);
                    return false;
                }
                
            }
            
            TDOUtil::closeDBLink($link);
            return true;
		}
        
        
        public static function allDevicesForUser($userId)
        {
            if(empty($userId))
                return false;
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {   
                error_log("TDODevice unable to get link");
                return false;               
            }
            
            $userId = mysql_real_escape_string($userId, $link);

            $sql = "SELECT * FROM tdo_user_devices WHERE userid='$userId'";
			$result = mysql_query($sql, $link);
            if($result)
            {
                $devices = array();
                while($row = mysql_fetch_array($result))
                {
                    $device = TDODevice::deviceForRow($row);
                    $devices[] = $device;
                }
                TDOUtil::closeDBLink($link);
                return $devices;
            }            

            error_log("TDODevice::allDevicesForUser failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;			
        } 
        
        
        public static function deviceForSession($sessionId)
        {
            if(empty($sessionId))
                return false;
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {   
                error_log("TDODevice unable to get link");
                return false;               
            }
            
            $sessionId = mysql_real_escape_string($sessionId, $link);
            
            $sql = "SELECT * FROM tdo_user_devices WHERE sessionid='$sessionId'";
			$result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    $device = TDODevice::deviceForRow($row);
                }
                TDOUtil::closeDBLink($link);
                
                if(!empty($device))
                    return $device;
                else
                    return false;
            }            
            
            error_log("TDODevice::deviceForSession failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;			
        }        

        
        public static function deviceForRow($row)
        {
            $device = new TDODevice();
            if(isset($row['deviceid']))
                $device->setDeviceId($row['deviceid']);
            if(isset($row['user_deviceid']))
                $device->setUserDeviceId($row['user_deviceid']);
            if(isset($row['userid']))
                $device->setUserId($row['userid']);
            if(isset($row['sessionid']))
                $device->setSessionId($row['sessionid']);
            if(isset($row['devicetype']))
                $device->setDeviceType($row['devicetype']);
            if(isset($row['osversion']))
                $device->setOSVersion($row['osversion']);
            if(isset($row['appid']))
                $device->setAppId($row['appid']);
            if(isset($row['appversion']))
                $device->setAppVersion($row['appversion']);
            if(isset($row['timestamp']))
                $device->setTimestamp($row['timestamp']);
            if(isset($row['error_number']))
                $device->setErrorNumber($row['error_number']);
            if(isset($row['error_message']))
                $device->setErrorMessage($row['error_message']);
            
            return $device;
        }
                
		
		public function deviceId()
		{
            if(empty($this->_publicPropertyArray['deviceid']))
                return NULL;
            else
                return $this->_publicPropertyArray['deviceid'];
		}
		public function setDeviceId($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['deviceid']);
            else
                $this->_publicPropertyArray['deviceid'] = $val;
		}
        
		public function userDeviceId()
		{
            if(empty($this->_publicPropertyArray['user_deviceid']))
                return NULL;
            else
                return $this->_publicPropertyArray['user_deviceid'];
		}
		public function setUserDeviceId($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['user_deviceid']);
            else
                $this->_publicPropertyArray['user_deviceid'] = $val;
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

		public function sessionId()
		{
            if(empty($this->_publicPropertyArray['sessionid']))
                return NULL;
            else
                return $this->_publicPropertyArray['sessionid'];
		}
		public function setSessionId($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['sessionid']);
            else
                $this->_publicPropertyArray['sessionid'] = $val;
		}        
        
		public function deviceType()
		{
            if(empty($this->_publicPropertyArray['devicetype']))
                return NULL;
            else
                return $this->_publicPropertyArray['devicetype'];
		}
		public function setDeviceType($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['devicetype']);
            else
                $this->_publicPropertyArray['devicetype'] = $val;
		}
    
		public function osVersion()
		{
            if(empty($this->_publicPropertyArray['osversion']))
                return NULL;
            else
                return $this->_publicPropertyArray['osversion'];
		}
		public function setOSVersion($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['osversion']);
            else
                $this->_publicPropertyArray['osversion'] = $val;
		}
        
		public function appId()
		{
            if(empty($this->_publicPropertyArray['appid']))
                return NULL;
            else
                return $this->_publicPropertyArray['appid'];
		}
		public function setAppId($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['appid']);
            else
                $this->_publicPropertyArray['appid'] = $val;
		} 
        
		public function appVersion()
		{
            if(empty($this->_publicPropertyArray['appversion']))
                return NULL;
            else
                return $this->_publicPropertyArray['appversion'];
		}
		public function setAppVersion($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['appversion']);
            else
                $this->_publicPropertyArray['appversion'] = $val;
		}         

		public function errorNumber()
		{
            if(empty($this->_publicPropertyArray['error_number']))
                return NULL;
            else
                return $this->_publicPropertyArray['error_number'];
		}
		public function setErrorNumber($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['error_number']);
            else
                $this->_publicPropertyArray['error_number'] = $val;
		}    
        
		public function errorMessage()
		{
            if(empty($this->_publicPropertyArray['error_message']))
                return NULL;
            else
                return $this->_publicPropertyArray['error_message'];
		}
		public function setErrorMessage($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['error_message']);
            else
                $this->_publicPropertyArray['error_message'] = $val;
		}         
        
        
    }

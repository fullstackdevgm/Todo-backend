<?php
//      TDOUtil
//      Used to handle all user data

// include files
include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/DBConstants.php');
	
class TDOUtil
{
	private static $_dbLink;
	private static $_dbLinkCount;
	
	public static function uuid()
	{
		// version 4 UUID
		return sprintf(
					   '%08x-%04x-%04x-%02x%02x-%012x',
					   mt_rand(),
					   mt_rand(0, 65535),
					   bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '0100', 11, 4)),
					   bindec(substr_replace(sprintf('%08b', mt_rand(0, 255)), '01', 5, 2)),
					   mt_rand(0, 255),
					   mt_rand() );
	}
	
	public static function generatePossiblePromoCode($codeLength=8)
	{
		$characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$code = "";
		for ($i = 0; $i < $codeLength; $i++)
		{
			$code = $code . $characters[mt_rand(0, strlen($characters) - 1)];
		}
		
		return $code;
	}

	public static function convertSelectToArray($select)
	{ 
		$results = array(); 
		$x = 0; 

		foreach($select->body->SelectResult->Item as $result)
		{ 
			foreach ($result as $field)
			{ 
				$results[$x][ (string) $field->Name ] = (string)$field->Value; 
			} 
			$x++; 
		} 
		return $results; 
	}
	
	public static function getDBLink($selectDB=true)
	{
		if(!empty(self::$_dbLink))
		{
			self::$_dbLinkCount++;
			return self::$_dbLink;
		}
		else
		{
			$link = mysql_connect(SQL_LOCATION, SQL_USERNAME, SQL_PASSWORD);
			if(!$link)
			{
				error_log("Failed to connect to DB with error :".mysql_error());
				return NULL;
			}
			if($selectDB)
			{
				if(!mysql_select_db(DB_NAME, $link))
				{
					error_log("Failed on select with error :".mysql_error());
					mysql_close($link);
					return NULL;
				}
			}
            
//            if(!mysql_query("set names 'utf8'", $link))
//            {
//                error_log("Failed to set UTF8 as default char set in mysql");
//            }
			
			self::$_dbLink = $link;
			self::$_dbLinkCount = 1;
			return self::$_dbLink;
		}
	}
    
    public static function cropAndScaleImage($originalFilename, $newFilename, $newWidth, $newHeight, $fileType)
    {
        //This code will crop the image, but it only works if GD is installed 
        if (extension_loaded('gd') && function_exists('gd_info')) 
        { 
            $fileType = strtolower($fileType);

            $original_image_size = getimagesize($originalFilename);
            $original_width = $original_image_size[0];
            $original_height = $original_image_size[1];

            if($fileType=='image/jpeg') 
            {
                $original_image_gd = imagecreatefromjpeg($originalFilename);
            }

            if($fileType=='image/gif') 
            { 
                $original_image_gd = imagecreatefromgif($originalFilename);
            }	

            if($fileType=='image/png') 
            {
                $original_image_gd = imagecreatefrompng($originalFilename);
            }

            $cropped_image_gd = imagecreatetruecolor($newWidth, $newHeight);
            $wm = $original_width /$newWidth;
            $hm = $original_height /$newHeight;
            $h_height = $newHeight/2;
            $w_height = $newWidth/2;

            if($original_width > $original_height ) 
            {
                $adjusted_width =$original_width / $hm;
                $half_width = $adjusted_width / 2;
                $int_width = $half_width - $w_height;

                imagecopyresampled($cropped_image_gd ,$original_image_gd ,-$int_width,0,0,0, $adjusted_width, $newHeight, $original_width , $original_height );
            } 
            elseif(($original_width < $original_height ) || ($original_width == $original_height ))
            {
                $adjusted_height = $original_height / $wm;
                $half_height = $adjusted_height / 2;
                $int_height = $half_height - $h_height;

                imagecopyresampled($cropped_image_gd , $original_image_gd ,0,-$int_height,0,0, $newWidth, $adjusted_height, $original_width , $original_height );
            } 
            else 
            {
                imagecopyresampled($cropped_image_gd , $original_image_gd ,0,0,0,0, $newWidth, $newHeight, $original_width , $original_height );
            }
            imagejpeg($cropped_image_gd, $newFilename); 
            return true;
        }
        else
        {
            error_log("Can't crop image because GD is not installed");
            return false;
        }

    }
    
    public static function ensureUTF8($strValue)
    {
        if(empty($strValue))
            return $strValue;
        
        if(mb_check_encoding($strValue, 'UTF-8') == false)
        {
            // try to get the current encoding and return it
            $oldEncoding = mb_detect_encoding($strValue);
            if($oldEncoding)
                $newValue = mb_convert_encoding($strValue, 'UTF-8', $oldEncoding);
            else
                $newValue = mb_convert_encoding($strValue, 'UTF-8');
                
            return $newValue;
        }
        
        return $strValue;
    }
    
	
	public static function closeDBLink($dbLink)
	{
		if($dbLink != self::$_dbLink)
		{
			mysql_close($dbLink);
			return;
		}

		self::$_dbLinkCount--;
		
		if(self::$_dbLinkCount == 0)
		{
			mysql_close(self::$_dbLink);
			self::$_dbLink = NULL;
		}
	}	
    
    public static function humanReadableStringFromTimestamp($timestamp, $currentTime=NULL)
    {
        if(!$currentTime)
            $currentTime = time();
        if($currentTime <= $timestamp)
            return sprintf(_('%s second ago'), '1');

        $timeDiff = $currentTime - $timestamp;
        
        if($timeDiff < 60)
        {
            if($timeDiff == 1)
                return sprintf(_('%s second ago'), '1');
            return sprintf(_('%s seconds ago'), $timeDiff);
        }
        $minuteDiff = intval($timeDiff / 60);
        if($minuteDiff < 60)
        {
            if($minuteDiff == 1)
                return sprintf(_('%s minute ago'), '1');
            return sprintf(_('%s minutes ago'), $minuteDiff);
        }
        $hourDiff = intval($minuteDiff / 60);
        if($hourDiff < 12)
        {
            if($hourDiff == 1)
                return sprintf(_('%s hour ago'), 1);
            return sprintf(_('%s hours ago'), $hourDiff);
        }
        //If it was more than 12 hours ago, go to "Today at", "Yesterday at", etc.
        list($tsMeridian, $tsSeconds, $tsMinutes, $tsHour, $tsDay, $tsMonth, $tsYear, $tsReadableMonth) = explode("-", date("a-s-i-g-d-m-Y-F", $timestamp));
        list($currentSeconds, $currentMinutes, $currentHour, $currentDay, $currentMonth, $currentYear) = explode("-", date("s-i-G-d-m-Y", $currentTime));  
        
        //See if the timestamp is today
        if($tsYear == $currentYear && $tsMonth == $currentMonth && $tsDay == $currentDay)
        {
            return sprintf(_('Today at %s: %s %s'), $tsHour, $tsMinutes, $tsMeridian);
        }
        
        //See if the timestamp is yesterday
        $startOfToday = $currentTime - intval($currentSeconds) - intval($currentMinutes) * 60 - intval($currentHour) * 60 * 60;
        
        $dayDiff = $startOfToday - $timestamp;
        if($dayDiff > 0 && $dayDiff < 24*60*60)
        {
            return sprintf(_('Yesterday at %s: %s %s'), $tsHour, $tsMinutes, $tsMeridian);
        }
        
        //Otherwise, just show the whole date, but leave off the year if it's the same year
        $year = '';
        if($tsYear != $currentYear)
        {
            $year = ", $tsYear";
        }

        return sprintf(_('"%s %s" %s at %s: %s %s'), $tsReadableMonth, $tsDay, $year, $tsHour, $tsMinutes, $tsMeridian);
        
    }
	
    public static function eventShortDateStringFromTimestamp($timestamp)
    {
		return date("D, M j h:i:s A", $timestamp);
    }
	
    public static function taskDueDateStringFromTimestamp($timestamp)
    {
        return _(date("l", $timestamp)) . ', ' . _(date("F", $timestamp)) . date(" j, Y", $timestamp);
    }

    public static function shortDueDateStringFromTimestamp($timestamp)
    {
		return date("D, M j", $timestamp);
    }
    
    public static function normalizedDateFromGMT($gmttimestamp, $timezone=NULL)
    {
        $dateTime = new DateTime();

        $timeZone = $dateTime->getTimeZone();

        if($timezone == NULL)
            $dateTime->setTimezone(new DateTimeZone('GMT'));
        else
            $dateTime->setTimezone(new DateTimeZone($timezone));

        $dateTime->setTimeStamp($gmttimestamp);
        $dateTime->setTime(12, 00, 00);
        $dateTime->setTimezone($timeZone);
        return $dateTime->getTimeStamp();
    }
    
    public static function dateFromGMT($gmttimestamp, $timezone=NULL)
    {
        $dateTime = new DateTime();
        $timeZone = $dateTime->getTimeZone();
        if($timezone == NULL)
            $dateTime->setTimezone(new DateTimeZone('GMT'));
        else
            $dateTime->setTimezone(new DateTimeZone($timezone));

        $dateTime->setTimeStamp($gmttimestamp);
        $dateTime->setTimezone($timeZone);
        return $dateTime->getTimeStamp();
    }
    
    //Takes a date in local time and converts it to 12:00:00 p.m. on that date in GMT
    public static function normalizeDateToNoonGMT($timestamp)
    {
        $oldDate = new DateTime();
        $oldDate->setTimeStamp($timestamp);
        
        $newDate = DateTime::createFromFormat("d, m, Y, H:i:s", $oldDate->format("d, m, Y, ")."12:00:00" ,new DateTimeZone('GMT'));
        return $newDate->getTimeStamp();
    }
    
    //Takes a date in GMT and converts it to 00:00:00 on that date in local time
    public static function denormalizedDateFromGMTDate($gmttimestamp)
    {
        $oldDate = new DateTime();
        $oldDate->setTimezone(new DateTimeZone('GMT'));
        $oldDate->setTimeStamp($gmttimestamp);
        
        $newDate = DateTime::createFromFormat("d, m, Y, H:i:s", $oldDate->format("d, m, Y, ")."00:00:00");
        return $newDate->getTimeStamp();
    }
    
    //Part of workaround for bug 7410. Takes a date in GMT and subtracts the necessary offset IF the
    //timezone offset is greater than or equal to +12
    public static function gmtAdjustedDate($gmttimestamp, $timezone)
    {
        if($gmttimestamp != 0)
        {
            $dateTime = new DateTime();
            $dateTime->setTimeStamp($gmttimestamp);
            return $gmttimestamp - TDOUtil::filterOffsetForCurrentGMTOffset($timezone, $dateTime);
        }
        
        return $gmttimestamp;
    }
    
    public static function filterOffsetForCurrentGMTOffset($timezone = NULL, $dateTime = NULL)
    {
        //Workaround for bug 7410. For users whose timezone offset is +12 or greater, the normalized
        //GMT due date is a day ahead of the due date in their timezone. When we're returning tasks
        //for a section in those timezones, we need to add an offset to the filter dates to capture tasks
        //with no due date that are due in the correct time frame. This does create a bug in those timezones
        //where tasks due at midnight (or 1 a.m. in GMT +13 zones) will show up a section too soon, but it's
        //better than what we have now where due dates don't work at all.
        
        if($timezone == NULL)
            $timezone = new DateTimeZone(date_default_timezone_get());
        
        if($dateTime == NULL)
            $dateTime = new DateTime();
        
        $offset = $timezone->getOffset($dateTime);
        
        if($offset/3600 >= 12)
        {
            return $offset - 43199; //43199 is one less than the number of seconds between gmt and gmt+12
        }
        return 0;
    }
    
    public static function dateWithTimeFromDate($originalDate, $timeDate)
    {
        $originalDateObj = new DateTime();
        $originalDateObj->setTimeStamp($originalDate);
        
        $time = date(" h:i:s a ", $timeDate);
        $originalDateObj->modify($time);
    
        return $originalDateObj->getTimeStamp();
    }
	
	public static function guidToMethodID($guid)
	{
		$guidArray = explode("-", $guid);
		$newGuid = "";
		
		foreach($guidArray as $guidStr)
		{
			$newGuid = $newGuid.$guidStr;
		}
		
		return $newGuid;
	}
    
    //Returns whether or not the given string contains only whitespace
    public static function stringIsWhitespace($string)
    {
        return preg_match('/^(\s)+$/', $string);
    }
    
    public static function isCurrentUserInWhiteList($session)
    {
    	$userID			= $session->getUserId();
		$username		= TDOUser::usernameForUserId($userID);
		
		if (empty($username))
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('No email address/username registered on this account.'),
            ));
			return;
		}
		
		// Determine the user's email domain
		$userEmailDomain = end(explode("@", $username));
		if (empty($userEmailDomain))
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Username appears to be an invalid email address.'),
            ));
			return;
		}
		
		$lowercaseEmailDomain = strtolower($userEmailDomain);
		
		// Verify that the user is on our whitelist
		$whitelistedDomains = explode(",", PROMO_CODE_WHITELISTED_DOMAINS);
		
		$isValidDomain = false;
		
		if (in_array($userEmailDomain, $whitelistedDomains))
			$isValidDomain = true;

		if ($isValidDomain)
			return true;
		else
			return false;
	}
	
	public static function isEmailAddressInWhiteList($email)
	{
		$result = false;
		
		if (empty($email))
			error_log('missing parameter $email in isEmailAddressInWhiteList() in TDOUtil.php');
		
		$emailDomain = end(explode("@", $email));
		
		if (empty($emailDomain))
			error_log('email appear to be invalid');
			
		$emailDomain = strtolower($emailDomain);
		
		// Verify that the email is on our whitelist
		$whitelistedDomains = explode(",", PROMO_CODE_WHITELISTED_DOMAINS);
		
		if (in_array($emailDomain, $whitelistedDomains))
			$result = true;
					
		return $result;
	}
	
	public static function getRequest($url, $optional_headers = null)
	{
		$params = array('http' => array('method' => 'GET'));
		if ($optional_headers !== null)
		{
			$params['http']['header'] = $optional_headers;
		}
		$ctx = stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $ctx);
		if (empty($fp))
		{
            if (strpos($http_response_header[0], '404') !== false)
                throw new Exception("Error 404: Document not found", 404);

            if (strpos($http_response_header[0], '401') !== false)
                throw new Exception("Error 404: Invalid Credentials.", 401);
            
			throw new Exception("Unable to load the URL: $url with response: ". $http_response_header[0], 0);
		}
		$response = @stream_get_contents($fp);
		if ($response === false)
		{
			throw new Exception("Problem reading data from the URL: $url", 0);
		}
		
		// Return an associative array
		return json_decode($response, true);
	}
    
    public static function postRequest($url, $data, $optional_headers = null)
	{
		$params = array('http' => array(
                                        'method' => 'POST',
                                        'content' => $data
                                        ));
		if ($optional_headers !== null)
		{
			$params['http']['header'] = $optional_headers;
		}
		$ctx = stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $ctx);
		if (!$fp)
		{
			throw new Exception("Problem with $url");
		}
		$response = @stream_get_contents($fp);
		if ($response === false)
		{
			throw new Exception("Problem reading data from $url");
		}
		
		// Return an associative array
		return json_decode($response, true);
	}
	
	public static function requestNewGooglePlayAccessToken()
	{
		// Use the Google Play refresh_token to request a new access token
		
		$refreshToken = TDOUtil::getStringSystemSetting(SYSTEM_SETTING_GOOGLE_PLAY_REFRESH_TOKEN);
		$clientID = TDOUtil::getStringSystemSetting(SYSTEM_SETTING_GOOGLE_PLAY_CLIENT_ID);
		$clientSecret = TDOUtil::getStringSystemSetting(SYSTEM_SETTING_GOOGLE_PLAY_CLIENT_SECRET);
		
		if (!$refreshToken)
		{
			error_log("TDOUtil::requestNewGooglePlayAccessToken() missing Google Play refresh token.");
			return false;
		}
		if (!$clientID)
		{
			error_log("TDOUtil::requestNewGooglePlayAccessToken() missing Google Play client id.");
			return false;
		}
		if (!$clientSecret)
		{
			error_log("TDOUtil::requestNewGooglePlayAccessToken() missing Google Play client secret.");
			return false;
		}
		
		$params = array(
						"grant_type" => "refresh_token",
						"client_id" => "$clientID",
						"client_secret" => "$clientSecret",
						"refresh_token" => "$refreshToken");
		$data = http_build_query($params);
		
		$response = NULL;
		try
		{
			$response = TDOUtil::postRequest("https://accounts.google.com/o/oauth2/token", $data);
		}
		catch (Exception $e)
		{
			error_log("TDOUtil::requestNewGooglePlayAccessToken() had an exception: " . $e->message());
			return false;
		}
		
		// If we make it this far, check to see that we have a new access token
		// and save it off if we do have one.
		
		if (empty($response) || !isset($response['access_token']))
		{
			error_log("TDOUtil::requestNewGooglePlayAccessToken() missing or bad response requesting new Google Play access token.");
			return false;
		}
		
		$accessToken = $response['access_token'];
		
		if (!TDOUtil::setStringSystemSetting(SYSTEM_SETTING_GOOGLE_PLAY_ACCESS_TOKEN, $accessToken))
		{
			error_log("TDOUtil::requestNewGooglePlayAccessToken() error storing the new Google Play access token into system settings.");
			return false;
		}
		
		return true;
	}
	
	public static function getStringSystemSetting($settingId, $defaultValue=NULL, $link=NULL)
	{
		$closeLink = false;
		if ($link == NULL)
		{
			$closeLink = true;
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				error_log("TDOUtil::getStringSystemSetting() unable to get link to database");
				return false;
			}
		}
		
		$settingId = mysql_real_escape_string($settingId, $link);
		$sql = "SELECT setting_value FROM tdo_system_settings WHERE setting_id='$settingId'";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			error_log("TDOUtil::getStringSystemSetting('$settingId') failed to make the SQL call: " . mysql_error());
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
        
		$row = mysql_fetch_array($result);
        if(isset($row['setting_value']))
		{
            $value = $row['setting_value'];
			if ($closeLink)
				TDOUtil::closeDBLink($link);
            return $value;
        }
		
		if (!empty($defaultValue))
		{
			error_log("TDOUtil::getStringSystemSetting('$settingId') empty, returning a default value: $defaultValue");
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return $defaultValue;
		}

		if (empty($defaultValue))
		{
			error_log("TDOUtil::getStringSystemSetting('$settingId'). No value stored in the database and no default specified. Returning NULL.");
		}
		else
		{
			error_log("TDOUtil::getStringSystemSetting('$settingId'). No value stored in the database. Returning default value: $defaultValue");
		}
		if ($closeLink)
			TDOUtil::closeDBLink($link);
		return false;
		
	}
	
	public static function setStringSystemSetting($settingKey, $settingValue)
	{
		$link = TDOUtil::getDBLink();
		if (!$link)
		{
			error_log("TDOUtil::setStringSystemSetting() unable to get link to database");
			return false;
		}
		
		$settingKey = mysql_real_escape_string($settingKey, $link);
		$settingValue = mysql_real_escape_string($settingValue, $link);
		
		// Check to see if this setting already exists. If it does, we'll just
		// do an update.
		$sql = NULL;
		if (TDOUtil::getStringSystemSetting($settingKey))
		{
			$sql = "UPDATE tdo_system_settings SET setting_value='$settingValue' WHERE setting_id='$settingKey'";
		}
		else
		{
			$sql = "INSERT INTO tdo_system_settings (setting_id, setting_value) VALUES ('$settingKey', '$settingValue')";
		}
		
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			error_log("TDOUtil::setStringSystemSetting('$settingKey') failed to make the SQL call: " . mysql_error());
			TDOUtil::closeDBLink($link);
			return false;
		}
		
		TDOUtil::closeDBLink($link);
		return true;
	}
	
	public static function arrayRecordSort($records, $field, $reverse=false)
	{
		$hash = array();
		
		foreach($records as $record)
		{
			$hash[$record[$field]] = $record;
		}
		
		($reverse)? krsort($hash) : ksort($hash);
		
		$records = array();
		
		foreach($hash as $record)
		{
			$records []= $record;
		}
		
		return $records;
	}
	
	
	// The originalValue parameter may be in RFC 3339 format or may be a
	// UNIX timestamp in milliseconds. This is used when dealing with App Store
	// Receipts. The old App Store would send milliseconds. The new App Store
	// sends RFC 3339.
	public static function unixTimestampFromDateStringOrMilliseconds($originalValue)
	{
		if (empty($originalValue))
			return 0;
		
		$timestamp = 0;
		
		if (ctype_digit($originalValue) == false)
		{
			// We're dealing with an RFC 3339 date
			$expDate = new DateTime($originalValue);
			$timestamp = $expDate->getTimestamp();
		}
		else
		{
			// We're dealing with an old-school # of milliseconds date
			$timestamp = (int)$originalValue/1000;
		}
		
		return $timestamp;
	}
	
	
	// The optOutKey is used to help prevent any random person from
	// unsubscribing email addresses from our Todo Cloud marketing list. This
	// key is computed by computing the MD5 of the following:
	//
	//     <OPT_OUT_EMAIL_HASH><email_address><user_id><OPT_OUT_EMAIL_HASH>47
	//
	// When an onboarding/marketing email is sent by the Todo Cloud system,
	// this key is also sent with it. When the user clicks the link, the same
	// key is computed and compared with the key passed in by the user. If they
	// match, the unsubscribe is accepted.
	//
	// This method is here for convenience in using the same code for both
	// generating the link and validating the unsubscribe call.
	public static function computeOptOutKeyForUser($userID, $emailAddress)
	{
		if (empty($userID) || empty($emailAddress))
			return false;
		
		$preHash = OPT_OUT_EMAIL_HASH . $emailAddress . $userID . OPT_OUT_EMAIL_HASH . "47";
		$calculatedMD5 = md5($preHash);
		
		return $calculatedMD5;
	}

    public static function mb_ucfirst($str)
    {
        return mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr(mb_convert_case($str, MB_CASE_LOWER, 'UTF-8'), 1, mb_strlen($str), 'UTF-8');
    }
}
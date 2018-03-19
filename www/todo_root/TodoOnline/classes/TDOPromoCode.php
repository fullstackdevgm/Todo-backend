<?php
	//      TDOPromoCode
	//      Used to manage and access our promo code system
	
	// include files
	include_once('AWS/sdk.class.php');
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/DBConstants.php');
	include_once('Facebook/config.php');
	include_once('Facebook/facebook.php');
	
	// Whitelisted Domains that are allowed to get yearly subscriptions free
	define ('PROMO_CODE_WHITELISTED_DOMAINS', 'apple.com,filemaker.com,musipal.com,gaisford.com,karren.com');
	
	// DON'T change the following. Keeping this long is gonna encourage the team
	// to actually record what they're up to and also might keep things a bit
	// more interesting.  Some good examples of 
	define ('PROMO_CODE_MINIMUM_NOTE_LENGTH', 47);
	
	define ('PROMO_CODE_MINIMUM_MONTHS', 1);
	define ('PROMO_CODE_MAXIMUM_MONTHS', 36);
	
	define ('PROMO_CODE_LIST_MAXIMUM_LIMIT', 100);
	
	//
	// Promo Code Error Codes
	//
	define ('PROMO_CODE_SUCCESS', 0);
	
	define ('PROMO_CODE_ERROR_CODE_MISSING_PARAMETER', 48001);
	define ('PROMO_CODE_ERROR_DESC_MISSING_PARAMETER', _('The request was missing required parameters.'));
	define ('PROMO_CODE_ERROR_CODE_INVALID_NOTE', 48002);
    define('PROMO_CODE_ERROR_DESC_INVALID_NOTE', _('It looks like you might be trying to cheat the system out of a good explanation for why you are creating a promo code.  Pull it together man and just explain what you might be up to.  Thanks!'));
	
	define ('PROMO_CODE_ERROR_CODE_INVALID_PROMO_LENGTH', 48003);
	define ('PROMO_CODE_ERROR_DESC_INVALID_PROMO_LENGTH', _('The number of months you specified to create a promo code is completely invalid.'));
	
	define ('PROMO_CODE_ERROR_CODE_DB_LINK', 48004);
	define ('PROMO_CODE_ERROR_DESC_DB_LINK', _('Could not get a connection to the database.'));
	
	define ('PROMO_CODE_ERROR_CODE_DB_INSERT', 48005);
	define ('PROMO_CODE_ERROR_DESC_DB_INSERT', _('Error inserting new promo code into the database.'));
	
	define ('PROMO_CODE_ERROR_CODE_DB_READ', 48006);
	define ('PROMO_CODE_ERROR_DESC_DB_READ', _('Error reading information from the database.'));
	
	define ('PROMO_CODE_ERROR_CODE_UNKNOWN_SUBSCRIPTION', 48007);
	define ('PROMO_CODE_ERROR_DESC_UNKNOWN_SUBSCRIPTION', _('Unknown subscription specified.'));
	
	define ('PROMO_CODE_ERROR_CODE_SUBSCRIPTION_UPDATE', 48008);
	define ('PROMO_CODE_ERROR_DESC_SUBSCRIPTION_UPDATE', _('Error updating the user subscription with promo code.'));
	
	define ('PROMO_CODE_ERROR_CODE_SUBSCRIPTION_DELETE', 48009);
	define ('PROMO_CODE_ERROR_DESC_SUBSCRIPTION_DELETE', _('Error deleting a used promo code.'));
	
	define ('PROMO_CODE_ERROR_CODE_USER_MISMATCH', 48010);
	define ('PROMO_CODE_ERROR_DESC_USER_MISMATCH', _('Promo code specified user that does not match session user.'));
	
	define ('PROMO_CODE_ERROR_CODE_TEAM_ACCOUNT', 48011);
	define ('PROMO_CODE_ERROR_DESC_TEAM_ACCOUNT', _('Promo code cannot be applied to an account that is part of a team.'));
	
	define ('PROMO_CODE_ERROR_CODE_EXCEPTION', 48998);
	define ('PROMO_CODE_ERROR_DESC_EXCEPTION', _('An exception occurred.'));
	
	define ('PROMO_CODE_ERROR_CODE_UNKNOWN', 48999);
	define ('PROMO_CODE_ERROR_DESC_UNKNOWN', _('An unknown error occurred.'));
	
	
	class TDOPromoCode
	{
		
		public function __construct()
		{
		}
		
		
		// PARAMETERS:
		//	numberOfMonths	(required)
		//		The number of months the promo code will be good for.  This
		//		value MUST be between 1 and 12.
		//	assigneeUserID	(optional)
		//	creatorUserID	(required)
		//		The userid of the person/service calling this method (this will
		//		be logged).
		//	note			(required)
		//		An explanation of why the promo code is being created.  This
		//		method will fail if the note is blank or seemingly too small.
		//
		// RETURNS:
		//	Returns an array with the following keys/values:
		//
		//	SUCCESS:
		//		"success"	=> true
		//		"promocode" => "Newly generated promo code"
		//		"promolink" => "Clickable link a user can use to redeem the promo code"
		//
		//	ERROR:
		//		"errcode"	=> <numeric error code>
		//		"errdesc"	=> "Description of the error"
		public static function createPromoCode($numberOfMonths, $assigneeUserID, $creatorUserID, $note)
		{
			if (!empty($assigneeUserID))
			{
				// Check to see if there is already a promo code generated for
				// this user and if so, just return it.
				$existingPromoCode = TDOPromoCode::_promoCodeForUserID($assigneeUserID);
				if (!empty($existingPromoCode))
				{
					$existingPromoCodeInfo = TDOPromoCode::getPromoCodeInfo($existingPromoCode);
					if (isset($existingPromoCodeInfo['promo_code_info']))
					{
						$promoCodeInfo = $existingPromoCodeInfo['promo_code_info'];
						$promoCode = $promoCodeInfo['promocode'];
						$promoLink = $promoCodeInfo['promolink'];
						return array(
									 "success"		=> true,
									 "promocode"	=> $promoCode,
									 "promolink"	=> $promoLink
									 );
					}
				}
			}
			
			if (empty($creatorUserID))
			{
				error_log("TDOPromoCode::createPromoCode() failed because creatorUserID is empty");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => PROMO_CODE_ERROR_DESC_MISSING_PARAMETER
							 );
			}
			
			$isValidNote = true;
			if (empty($note))
			{
				error_log("TDOPromoCode::createPromoCode() failed because note is empty (userid: $creatorUserID)");
				$isValidNote = false;
			}
			
			$note = trim($note);
			$noteLength = strlen($note);
			if ($noteLength < PROMO_CODE_MINIMUM_NOTE_LENGTH)
			{
				error_log("TDOPromoCode::createPromoCode() failed because note is not long enough (userid: $creatorUserID)");
				$isValidNote = false;
			}
			
			if (!$isValidNote)
			{
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_INVALID_NOTE,
							 "errdesc" => PROMO_CODE_ERROR_DESC_INVALID_NOTE
							 );
			}
			
			$numberOfMonths = (int)$numberOfMonths;
			if ( ($numberOfMonths < PROMO_CODE_MINIMUM_MONTHS) || ($numberOfMonths > PROMO_CODE_MAXIMUM_MONTHS) )
			{
				error_log("TDOPromoCode::createPromoCode() failed because the numberOfMonths ($numberOfMonths) specified is not in range (userid: $creatorUserID)");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_INVALID_PROMO_LENGTH,
							 "errdesc" => PROMO_CODE_ERROR_DESC_INVALID_PROMO_LENGTH
							 );
			}
			
            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOPromoCode::createPromoCode() failed to get dblink");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_DB_LINK,
							 "errdesc" => PROMO_CODE_ERROR_DESC_DB_LINK
							 );
			}
			
			$promoCode = NULL;
			while ($promoCode == NULL)
			{
				$possiblePromoCode = TDOUtil::generatePossiblePromoCode();
				
				$existingPromoCode = TDOPromoCode::getPromoCodeInfo($possiblePromoCode);
				if (!empty($existingPromoCode))
				{
					// If this promo code doesn't exist, that's what we're after, so bail out of this loop
					if (!empty($existingPromoCode["errcode"]) && $existingPromoCode["errcode"] == PROMO_CODE_ERROR_CODE_UNKNOWN)
					{
						$promoCode = $possiblePromoCode;
						break;
					}
					else if (!empty($existingPromoCode["success"]))
					{
						continue;
					}
					else
					{
						error_log("TDOPromoCode::createPromoCode() failed to get info about an existing promo code when trying to create a new promo code.");
						TDOUtil::closeDBLink($link);
						return array(
									 "errcode" => PROMO_CODE_ERROR_CODE_EXCEPTION,
									 "errdesc" => PROMO_CODE_ERROR_DESC_EXCEPTION);
					}
				}
			}
			
//			$promoCode = TDOUtil::uuid();
			$creatorUserID = mysql_real_escape_string($creatorUserID, $link);
			$note = mysql_real_escape_string($note, $link);
			$timestamp = time();
			
			
			
			$sql = "INSERT INTO tdo_promo_codes (promo_code, userid, subscription_duration, timestamp, creator_userid, note) ";
			$sql .= "VALUES ('$promoCode', ";
			if (empty($assigneeUserID))
			{
				$assigneeUserID = NULL;
				$sql .= "NULL, ";
			}
			else
			{
				$assigneeUserID = mysql_real_escape_string($assigneeUserID, $link);
				$sql .= "'$assigneeUserID', ";
			}
			$sql .= "$numberOfMonths, $timestamp, '$creatorUserID', '$note')";
			
			$response = mysql_query($sql, $link);
			if (!$response)
			{
				error_log("TDOPromoCode::createPromoCode() failed to insert new promo code into the database table (userid: $creatorUserID): " . mysql_error());
				TDOUtil::closeDBLink($link);
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_DB_INSERT,
							 "errdesc" => PROMO_CODE_ERROR_DESC_DB_INSERT
							 );
			}
			
			// Generate a link that can be used by a user to apply a promo code
			// to their account.
			
			$promoLink = SITE_PROTOCOL . SITE_BASE_URL . "?applypromocode=true&promocode=" . $promoCode;
			
			TDOUtil::closeDBLink($link);
			return array(
						 "success"		=> true,
						 "promocode"	=> $promoCode,
						 "promolink"	=> $promoLink
						 );
		}
		
		
		// Returns an array with "success" => true or an errcode & errdesc.
		public static function applyPromoCodeToSubscription($promoCode, $userID, $subscriptionID)
		{
			if (empty($promoCode))
			{
				error_log("TDOPromoCode::applyPromoCodeToSubscription() failed because promoCode is empty");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => PROMO_CODE_ERROR_DESC_MISSING_PARAMETER
							 );
			}
			
			if (empty($userID))
			{
				error_log("TDOPromoCode::applyPromoCodeToSubscription() failed because userID is empty");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => PROMO_CODE_ERROR_DESC_MISSING_PARAMETER
							 );
			}
			
			if (empty($subscriptionID))
			{
				error_log("TDOPromoCode::applyPromoCodeToSubscription() failed because subscriptionID is empty");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => PROMO_CODE_ERROR_DESC_MISSING_PARAMETER
							 );
			}
			
			$promoCodeInfo = TDOPromoCode::getPromoCodeInfo($promoCode);
			if (isset($promoCodeInfo['errcode']))
			{
				error_log("TDOPromoCode::applyPromoCodeToSubscription() had error (" . $promoCodeInfo['errcode'] . ") calling TDOPromoCode::getPromoCodeInfo (promo code: $promoCode): " . $promoCodeInfo['errdesc']);
				return $promoCodeInfo;
			}
			
			$subscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
			if (!$subscription)
			{
				error_log("TDOPromoCode::applyPromoCodeToSubscription() failed because subscriptionID is empty");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_UNKNOWN_SUBSCRIPTION,
							 "errdesc" => PROMO_CODE_ERROR_DESC_UNKNOWN_SUBSCRIPTION
							 );
			}
			
			$teamID = $subscription->getTeamID();
			if (!empty($teamID))
			{
				error_log("TDOPromoCode::applyPromoCodeToSubscription() failed because a promo code cannot be applied to a subscription that is part of a team");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_TEAM_ACCOUNT,
							 "errdesc" => PROMO_CODE_ERROR_DESC_TEAM_ACCOUNT
							 );
			}
			
			$promoCodeInfo = $promoCodeInfo['promo_code_info'];
			
			
			//
			// If the promo code specifies a userid, it MUST match $userID
			// because the promo code was made specifically for this user.
			//
			if (!empty($promoCodeInfo['userid']))
			{
				$promoCodeUserID = $promoCodeInfo['userid'];
				if ((string)$promoCodeUserID != (string)$userID)
				{
					error_log("TDOPromoCode::applyPromoCodeToSubscription() failed because a user was specified on the promo code ($promoCodeUserID) does not match the user ($userID) trying to redeem the promo code.");
					return array(
								 "errcode" => PROMO_CODE_ERROR_CODE_USER_MISMATCH,
								 "errdesc" => PROMO_CODE_ERROR_DESC_USER_MISMATCH
								 );
				}
			}
			
			$duration = $promoCodeInfo['subscription_duration'];
			$extensionInterval = new DateInterval("P" . $duration . "M");
			
			$expirationTimestamp = $subscription->getExpirationDate();
			$expirationDate = new DateTime('@' . $expirationTimestamp, new DateTimeZone("UTC"));
			$nowDate = new DateTime("now", new DateTimeZone("UTC"));
			
			// Make sure that the promo code at least starts from today and not
			// way back if the user's expiration date is far in the past.
			if ($nowDate > $expirationDate)
				$expirationDate = $nowDate;
			
			$newExpirationDate = $expirationDate->add($extensionInterval);
			$newExpirationTimestamp = $newExpirationDate->getTimestamp();
			
			$subscriptionType = $subscription->getSubscriptionType();
			
			// Adjust the subscription to the new expiration date
			if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $subscriptionType, SUBSCRIPTION_LEVEL_PROMO))
			{
				error_log("TDOPromoCode::applyPromoCodeToSubscription($promoCode, $subscriptionID) failed to update the user's subscription with the new expiration date");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_SUBSCRIPTION_UPDATE,
							 "errdesc" => PROMO_CODE_ERROR_DESC_SUBSCRIPTION_UPDATE
							 );
			}
			
			// Log the application of the promo code to the change log
			
			$originalTimestamp = $promoCodeInfo['timestamp'];
			$creatorUserID = $promoCodeInfo['creator_userid'];
			$note = $promoCodeInfo['note'];
			$nowTimestamp = $nowDate->getTimestamp();
			$userID = $subscription->getUserID();
			
			if (!TDOPromoCode::_logPromoCodeUsage($userID, $duration, $nowTimestamp, $creatorUserID, $originalTimestamp, $note))
			{
				error_log("TDOPromoCode::applyPromoCodeToSubscription() had an error logging the promo code ($promoCode) usage for userid: $userID");
			}
			
			// Delete the promo code from the database
			$result = TDOPromoCode::deletePromoCode($promoCode);
			if (isset($result['errcode']))
			{
				error_log("TDOPromoCode::applyPromoCodeToSubscription($promoCode, $subscriptionID) already applied the promo code to the user's subscription, but failed to delete the promo code.  BAD!!!!");
				
				$body = "Hello\n\n"
					. "The system just detected that a user ($userID) applied a promo code "
					. "successfully, but in our attempt to now remove the promo "
					. "code from the system, an error was encountered.  Please "
					. "immediately go and delete the following promo code:\n\n"
					. "Promo Code: $promoCode\n\n"
					. "NOTE: If you do not resolve this immediately, the promo "
					. "code may be able to be used multiple times.\n\n"
					. "TODO: Include a link that will take the support team "
					. "member directly to the promo code admin interface.";
				
				$recipient = "support@appigo.com";
				$subject = "[CRITICAL] Todo Cloud Promo Code Error - Failed to remove a used promo code";
				
				TDOMailer::notifyCriticalSystemError($recipient, $subject, $body);
			}
			
			return $result;
		}
		
		
		public static function deletePromoCode($promoCode)
		{
			if (empty($promoCode))
			{
				error_log("TDOPromoCode::deletePromoCode() failed because promoCode is empty");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => PROMO_CODE_ERROR_DESC_MISSING_PARAMETER
							 );
			}
			
            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOPromoCode::deletePromoCode() failed to get dblink");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_DB_LINK,
							 "errdesc" => PROMO_CODE_ERROR_DESC_DB_LINK
							 );
			}
			
			$promoCode = mysql_real_escape_string($promoCode, $link);
			
			$sql = "DELETE FROM tdo_promo_codes WHERE promo_code='$promoCode'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOPromoCode::deletePromoCode() unable to delete promo code ($deletePromoCode): " . mysql_error());
				TDOUtil::closeDBLink($link);
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_SUBSCRIPTION_DELETE,
							 "errdesc" => PROMO_CODE_ERROR_DESC_SUBSCRIPTION_DELETE
							 );
			}
			
			// SUCCESS!
            TDOUtil::closeDBLink($link);
			return array("success" => true);
		}
		
		
		// Returns an array of promo code info arrays
		public static function listPromoCodes($offset = 0, $limit = 50)
		{
            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOPromoCode::listPromoCodes() failed to get dblink");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_DB_LINK,
							 "errdesc" => PROMO_CODE_ERROR_DESC_DB_LINK
							 );
			}
			
			// Prevent a crazy number of promo codes from being returned in one call
			$limit = (int)$limit;
			if ($limit > PROMO_CODE_LIST_MAXIMUM_LIMIT)
				$limit = PROMO_CODE_LIST_MAXIMUM_LIMIT;
			
			$offset = (int)$offset;
			if ($offset < 0)
				$offset = 0;
			
			$promoCodes = array();
			
			$sql = "SELECT * FROM tdo_promo_codes ORDER BY timestamp DESC LIMIT $limit OFFSET $offset";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				TDOUtil::closeDBLink($link);
				error_log("TDOPromoCode::listPromoCodes() failed to read promo code information from database");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_DB_READ,
							 "errdesc" => PROMO_CODE_ERROR_CODE_DB_READ
							 );
			}
			
			while ($row = mysql_fetch_array($result))
			{
				$promoCodeInfo = TDOPromoCode::_promoCodeInfoFromRecord($row);
				if (empty($promoCodeInfo))
					continue;
				
				$promoCodes[] = $promoCodeInfo;
			}
			
			$numOfPromoCodes = TDOPromoCode::_numberOfPromoCodes();
			
			// Build a hash of user display names for the creator ids so the
			// admin interface can show a human-readable interface.
			$ownerDisplayNames = TDOPromoCode::_ownerDisplayNamesFromPromoCodes($promoCodes);
			
			TDOUtil::closeDBLink($link);
			
			$promoCodeList = array(
						 "success" => true,
						 "num_of_promo_codes" => $numOfPromoCodes,
						 "promo_code_infos" => $promoCodes
						 );
			
			if ($ownerDisplayNames)
				$promoCodeList["owner_display_names"] = $ownerDisplayNames;
			
			return $promoCodeList;
		}
		
		
		public static function listUsedPromoCodes($offset = 0, $limit = 50)
		{
            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOPromoCode::listUsedPromoCodes() failed to get dblink");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_DB_LINK,
							 "errdesc" => PROMO_CODE_ERROR_DESC_DB_LINK
							 );
			}
			
			// Prevent a crazy number of promo codes from being returned in one call
			$limit = (int)$limit;
			if ($limit > PROMO_CODE_LIST_MAXIMUM_LIMIT)
				$limit = PROMO_CODE_LIST_MAXIMUM_LIMIT;
			
			$offset = (int)$offset;
			if ($offset < 0)
				$offset = 0;
			
			$promoCodes = array();
			
			$sql = "SELECT * FROM tdo_promo_code_history ORDER BY timestamp DESC LIMIT $limit OFFSET $offset";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				TDOUtil::closeDBLink($link);
				error_log("TDOPromoCode::listUsedPromoCodes() failed to read promo code information from database");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_DB_READ,
							 "errdesc" => PROMO_CODE_ERROR_CODE_DB_READ
							 );
			}
			
			$ownerDisplayNames = array();
			$promoCodes = array();
			
			while ($row = mysql_fetch_array($result))
			{
				$promoCodeInfo = array();
				if (isset($row['userid']))
				{
					$userID = $row['userid'];
					$promoCodeInfo['userid'] = $userID;
					
					$displayName = TDOUser::displayNameForUserId($userID);
					$promoCodeInfo['displayname'] = $displayName;
				}
				else
					continue;
				
				if (isset($row['subscription_duration']))
					$promoCodeInfo['subscription_duration'] = $row['subscription_duration'];
				
				if (isset($row['timestamp']))
					$promoCodeInfo['timestamp'] = $row['timestamp'];
				
				if (isset($row['creator_userid']))
				{
					$creatorUserID = $row['creator_userid'];
					$promoCodeInfo['creator_userid'] = $creatorUserID;
					
					if (!isset($ownerDisplayNames[$creatorUserID]))
					{
						$ownerDisplayName = TDOUser::displayNameForUserId($creatorUserID);
						$ownerDisplayNames[$creatorUserID] = $ownerDisplayName;
					}
				}
				
				if (isset($row['creation_timestamp']))
					$promoCodeInfo['creation_timestamp'] = $row['creation_timestamp'];
				
				if (isset($row['note']))
					$promoCodeInfo['note'] = $row['note'];
				
				$promoCodes[] = $promoCodeInfo;
			}
			
			TDOUtil::closeDBLink($link);
			
			$numOfPromoCodes = TDOPromoCode::_numberOfUsedPromoCodes();
			
			$promoCodeList = array(
								   "success" => true,
								   "num_of_promo_codes" => $numOfPromoCodes,
								   "promo_code_infos" => $promoCodes
								   );
			
			if ($ownerDisplayNames)
				$promoCodeList["owner_display_names"] = $ownerDisplayNames;
			
			return $promoCodeList;
		}
		
		
		public static function getPromoCodeInfo($promoCode)
		{
			if (empty($promoCode))
			{
				error_log("TDOPromoCode::getPromoCodeInfo() failed because promoCode is empty");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => PROMO_CODE_ERROR_DESC_MISSING_PARAMETER
							 );
			}
			
            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOPromoCode::getPromoCodeInfo() failed to get dblink");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_DB_LINK,
							 "errdesc" => PROMO_CODE_ERROR_DESC_DB_LINK
							 );
			}
			
			$promoCode = mysql_real_escape_string($promoCode, $link);
			
			$sql = "SELECT * FROM tdo_promo_codes WHERE promo_code='$promoCode'";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				TDOUtil::closeDBLink($link);
				error_log("TDOPromoCode::getPromoCodeInfo() failed to read promo code information from database (promo code: $promoCode)");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_DB_READ,
							 "errdesc" => PROMO_CODE_ERROR_CODE_DB_READ
							 );
			}
			
			$row = mysql_fetch_array($result);
			if (!$row)
			{
				TDOUtil::closeDBLink($link);
				error_log("TDOPromoCode::getPromoCodeInfo() no row in the db results (promo code: $promoCode)");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_UNKNOWN,
							 "errdesc" => PROMO_CODE_ERROR_DESC_UNKNOWN
							 );
			}
			
			$promoCodeInfo = TDOPromoCode::_promoCodeInfoFromRecord($row);
			if (!$promoCodeInfo)
			{
				TDOUtil::closeDBLink($link);
				error_log("TDOPromoCode::getPromoCodeInfo() no promo code info in the db row (promo code: $promoCode)");
				return array(
							 "errcode" => PROMO_CODE_ERROR_CODE_UNKNOWN,
							 "errdesc" => PROMO_CODE_ERROR_DESC_UNKNOWN
							 );
			}
			
			TDOUtil::closeDBLink($link);
			return array(
						 "success" => true,
						 "promo_code_info" => $promoCodeInfo
						 );
		}
		
		
		//
		// PRIVATE METHODS
		//
		private static function _promoCodeInfoFromRecord($row)
		{
			if (empty($row))
				return false;
			
			// EVERY item must be set in order for us to return a valid info item
			
			if (isset($row['promo_code'], $row['subscription_duration'], $row['timestamp'], $row['creator_userid'], $row['note']))
			{
				$promoCode = $row['promo_code'];
				$promoLink = SITE_PROTOCOL . SITE_BASE_URL . "?applypromocode=true&promocode=" . $promoCode;
				
				$promoCodeInfo = array(
									   "promocode"				=> $promoCode,
									   "promolink"				=> $promoLink,
									   "subscription_duration"	=> (int)$row['subscription_duration'],
									   "timestamp"				=> (int)$row['timestamp'],
									   "creator_userid"			=> $row['creator_userid'],
									   "note"					=> $row['note']
									   );
				
				if (isset($row['userid']))
					$promoCodeInfo['userid'] = $row['userid'];
				
				return $promoCodeInfo;
			}
			
			return false;
		}
		
		
		// Returns true or false
		private static function _logPromoCodeUsage($userID, $subscriptionDuration, $timestamp, $creatorUserID, $creationTimestamp, $note)
		{
			if (empty($userID))
			{
				error_log("TDOPromoCode::_logPromoCodeUsage() called with an empty userID");
				return false;
			}
			
			if (empty($creatorUserID))
			{
				error_log("TDOPromoCode::_logPromoCodeUsage() called with an empty creatorUserID");
				return false;
			}
			
			if (empty($note))
			{
				error_log("TDOPromoCode::_logPromoCodeUsage() called with an empty note");
				
				// Go ahead and log the update with a blank note
				$note = "Empty note ... BAD!";
			}
			
            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOPromoCode::_logPromoCodeUsage() failed to get dblink");
				return false;
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			$subscriptionDuration = (int)$subscriptionDuration;
			$timestamp = (int)$timestamp;
			$creatorUserID = mysql_real_escape_string($creatorUserID, $link);
			$creationTimestamp = (int)$creationTimestamp;
			$note = mysql_real_escape_string($note, $link);
			
			$sql = "INSERT INTO tdo_promo_code_history (userid, subscription_duration, timestamp, creator_userid, creation_timestamp, note) VALUES ('$userID', $subscriptionDuration, $timestamp, '$creatorUserID', $creationTimestamp, '$note')";
			$response = mysql_query($sql, $link);
			if (!$response)
			{
				error_log("TDOPromoCode::_logPromoCodeUsage() failed to insert new history record into the database table (userid: $userID): " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			TDOUtil::closeDBLink($link);
			return true;
		}
		
		// Returns the total number of existing promo codes
		private static function _numberOfPromoCodes()
		{
            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOPromoCode::_numberOfPromoCodes() failed to get dblink");
				return 0;
			}
			
			$totalCount = 0;
			$sql = "SELECT COUNT(promo_code) FROM tdo_promo_codes";
			$result = mysql_query($sql, $link);
			if ($result)
			{
				$row = mysql_fetch_array($result);
				if ($row && isset($row[0]))
					$totalCount = $row[0];
			}
			
			TDOUtil::closeDBLink($link);
			return $totalCount;
		}
		
		
		private static function _numberOfUsedPromoCodes()
		{
            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOPromoCode::_numberOfUsedPromoCodes() failed to get dblink");
				return 0;
			}
			
			$totalCount = 0;
			$sql = "SELECT COUNT(userid) FROM tdo_promo_code_history";
			$result = mysql_query($sql, $link);
			if ($result)
			{
				$row = mysql_fetch_array($result);
				if ($row && isset($row[0]))
					$totalCount = $row[0];
			}
			
			TDOUtil::closeDBLink($link);
			return $totalCount;
		}
		
		
		private static function _ownerDisplayNamesFromPromoCodes($promoCodeInfos)
		{
			if (empty($promoCodeInfos))
				return false;
			
			$displayNames = array();
			
			foreach ($promoCodeInfos as $promoCodeInfo)
			{
				if (!isset($promoCodeInfo['creator_userid']))
					continue;
				
				$creatorID = $promoCodeInfo['creator_userid'];
				if (isset($displayNames[$creatorID]))
					continue; // we already have a display name for this creator
				
				$displayName = TDOUser::displayNameForUserId($creatorID);
				if ($displayName)
					$displayNames[$creatorID] = $displayName;
			}
			
			return $displayNames;
		}
		
		
		private static function _promoCodeForUserID($userID)
		{
			if (empty($userID))
				return false;
			
            $link = TDOUtil::getDBLink();
            if(!$link)
                return false;
			
            $userID = mysql_real_escape_string($userID, $link);
			
			$sql = "SELECT promo_code FROM tdo_promo_codes WHERE userid='$userID'";
            
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
					return $row['promo_code'];
                }
            }
            else
                error_log("TDOPromoCode::_promoCodeForUserID() failed:".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
		}
	}

?>

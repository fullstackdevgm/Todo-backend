<?php
	//      TDOSubscription
	//      Used to manage and access subscription information

	// include files
	include_once('AWS/sdk.class.php');
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/DBConstants.php');
	include_once('Facebook/config.php');
	include_once('Facebook/facebook.php');
	include_once('Stripe/Stripe.php');

	Stripe::setApiKey(APPIGO_STRIPE_SECRET_KEY);


	define('SUBSCRIPTIONS_DB_TABLE_COLUMNS', "subscriptionid, userid, expiration_date, type, level, teamid, timestamp");
	// prevent more than 100 subscriptions from being returned at a time
	define('SUBSCRIPTIONS_LIST_MAX_RETURN_LIMIT', 100);

	//
	// Subscription Types
	//
	define ('SUBSCRIPTION_TYPE_UNKNOWN', 0);
	define ('SUBSCRIPTION_TYPE_MONTH', 1);
	define ('SUBSCRIPTION_TYPE_YEAR', 2);

	//
	// Subscription Purchase Eligibility
	//
	// We don't want users to be able to purchase years and years of
	// subscriptions on their account.  The user must be within six months of
	// their expiration date to upgrade their account.
	//
	// The format used below is a PHP DateInterval.
	//
	define ('SUBSCRIPTION_ELIGIBILITY_INTERVAL', 'P6M');


	//
	// Autorenew Defines
	//
	define('SUBSCRIPTION_RETRY_INTERVAL', 120);			// 2 minutes
	define('SUBSCRIPTION_RETRY_MAX_ATTEMPTS', 3);
	define('SUBSCRIPTION_RENEW_LEAD_TIME',60*60*24);	// One day

	define('VIP_TWO_WEEKS', 60*60*24*14);				// 14 days

	//
	// Subscription Error Codes
	//
	define ('SUBSCRIPTION_SUCCESS', 0);

	define ('SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER', 47001);
	define ('SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER', _('The request was missing required parameters.'));

	define ('SUBSCRIPTION_ERROR_CODE_APP_STORE_RECEIPT_NOT_VALID', 47002);
	define ('SUBSCRIPTION_ERROR_DESC_APP_STORE_RECEIPT_NOT_VALID', _('The App Store receipt sent for the premium account purchase is not valid.'));

	define ('SUBSCRIPTION_ERROR_CODE_APP_STORE_BAD_RESPONSE', 47003);
	define ('SUBSCRIPTION_ERROR_DESC_APP_STORE_BAD_RESPONSE', _('The App Store returned a bad response when validating the receipt.'));

	define ('SUBSCRIPTION_ERROR_CODE_APP_STORE_MISSING_RECEIPT', 47004);
	define ('SUBSCRIPTION_ERROR_DESC_APP_STORE_MISSING_RECEIPT', _('The App Store returned success but did not include a readable receipt.'));

	define ('SUBSCRIPTION_ERROR_CODE_UNAUTHORIZED', 47005);
	define ('SUBSCRIPTION_ERROR_DESC_UNAUTHORIZED', _('Who do you think you are? You are NOT authorized to make this call.'));

	define ('SUBSCRIPTION_ERROR_CODE_MISSING_SUBSCRIPTION', 47006);
	define ('SUBSCRIPTION_ERROR_DESC_MISSING_SUBSCRIPTION', _('Unable to find a premium account to apply the IAP to.'));

	define ('SUBSCRIPTION_ERROR_CODE_MISSING_PRODUCTID', 47007);
	define ('SUBSCRIPTION_ERROR_DESC_MISSING_PRODUCTID', _('Unable to determine product id from decoded IAP receipt.'));

	define ('SUBSCRIPTION_ERROR_CODE_UNKNOWN_PRODUCT', 47008);
	define ('SUBSCRIPTION_ERROR_DESC_UNKNOWN_PRODUCT', _('IAP was made with an unknown product id.'));

	define ('SUBSCRIPTION_ERROR_CODE_EXTEND_EXPIRATION', 47009);
	define ('SUBSCRIPTION_ERROR_DESC_EXTEND_EXPIRATION', _('Unable to extend a premium account expiration date.'));

	define ('SUBSCRIPTION_ERROR_CODE_STRIPE_EXCEPTION', 47010);
	define ('SUBSCRIPTION_ERROR_DESC_STRIPE_EXCEPTION', _('An exception occurred communicating with the payment system.'));

	define ('SUBSCRIPTION_ERROR_CODE_STRIPE_LAST4_MISMATCH', 47011);
	define ('SUBSCRIPTION_ERROR_DESC_STRIPE_LAST4_MISMATCH', _('The specified last4 is either not found in the payment system or does not match payment system records'));

	define ('SUBSCRIPTION_ERROR_CODE_SAVE_PAYMENT_CUSTOMER', 47012);
	define ('SUBSCRIPTION_ERROR_DESC_SAVE_PAYMENT_CUSTOMER', _('Error saving payment system customer.'));

	define ('SUBSCRIPTION_ERROR_CODE_UNKNOWN_SUBSCRIPTION', 47013);
	define ('SUBSCRIPTION_ERROR_DESC_UNKNOWN_SUBSCRIPTION', _('The specified premium account does not exist.'));

	define ('SUBSCRIPTION_ERROR_CODE_ALREADY_MONTHLY', 47014);
	define ('SUBSCRIPTION_ERROR_DESC_ALREADY_MONTHLY', _('The specified premium account is already configured for monthly billing.'));

	define ('SUBSCRIPTION_ERROR_CODE_INVALID_MONTHLY_CANDIDATE', 47015);
	define ('SUBSCRIPTION_ERROR_DESC_INVALID_MONTHLY_CANDIDATE', _('The specified premium account cannot be changed to monthly billing without collecting additional payment because the current expiration date occurs sooner than one month from now.'));

    define ('SUBSCRIPTION_ERROR_CODE_MISSING_EXPIRATION_DATE', 47016);
    define ('SUBSCRIPTION_ERROR_DESC_MISSING_EXPIRATION_DATE', _('Unable to determine expiration date from decoded IAP receipt.'));

    define ('SUBSCRIPTION_ERROR_CODE_ACCOUNT_NOT_EXPIRED', 47017);
    define ('SUBSCRIPTION_ERROR_DESC_ACCOUNT_NOT_EXPIRED', _('A renewing subscription cannot be made with IAP because the specified account is not expired.'));

    define ('SUBSCRIPTION_ERROR_CODE_IAP_RECEIPT_NOT_SAVED', 47018);
    define ('SUBSCRIPTION_ERROR_DESC_IAP_RECEIPT_NOT_SAVED', _('Unable to save receipt for autorenewing in-app purchase.'));

    define ('SUBSCRIPTION_ERROR_CODE_IAP_AUTORENEWAL_DETECTED', 47019);
    define ('SUBSCRIPTION_ERROR_DESC_IAP_AUTORENEWAL_DETECTED', _('An IAP purchase cannot be made because the user has an auto-renewing IAP subscription.'));

	define ('SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_MISSING_ACCESS_TOKEN', 47020);
	define ('SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_MISSING_ACCESS_TOKEN', _('The access code required to commmunicate with Google Play is missing.'));

	define ('SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_EXCEPTION_RESPONSE', 47021);
	define ('SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_EXCEPTION_RESPONSE', _('Got an exception attempting to communicate with Google Play.'));

	define ('SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_BAD_RESPONSE', 47022);
	define ('SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_BAD_RESPONSE', _('Got no response from Google Play.'));

	define ('SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_REFRESH_ACCESS_TOKEN_FAILED', 47023);
	define ('SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_REFRESH_ACCESS_TOKEN_FAILED', _('Could not refresh Google OAuth access token.'));

	define ('SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_TOKEN_VERIFY_RETRY_FAILED', 47024);
	define ('SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_TOKEN_VERIFY_RETRY_FAILED', _('Could not verify a purchase token after a few retries.'));

	define ('SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_MISSING_VALID_UNTIL_FIELD', 47025);
	define ('SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_MISSING_VALID_UNTIL_FIELD', _('Could not verify a purchase token after a few retries.'));

	define ('SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_INVALID_TOKEN', 47026);
	define ('SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_INVALID_TOKEN', _('The Google Play subscription purchase token is invalid.'));

	define ('SUBSCRIPTION_ERROR_CODE_GOOGLE_PLAY_TOKEN_EXPIRED', 47027);
	define ('SUBSCRIPTION_ERROR_DESC_GOOGLE_PLAY_TOKEN_EXPIRED', _('The Google Play subscription has already expired for this token.'));

	define ('SUBSCRIPTION_ERROR_CODE_EXCEPTION', 47998);
	define ('SUBSCRIPTION_ERROR_DESC_EXCEPTION', _('An exception occurred.'));

	define ('SUBSCRIPTION_ERROR_CODE_UNKNOWN', 47999);
	define ('SUBSCRIPTION_ERROR_DESC_UNKNOWN', _('An unknown error occurred.'));



	class TDOSubscription
	{
		private $_subscriptionID;
		private $_userID;
        private $_expirationDate;
		private $_subscriptionType;
		private $_level;
		private $_teamid;
        private $_timestamp;

		public function __construct()
		{
			$this->set_to_default();
		}


		public function set_to_default()
		{
			// clears values without going to database
			$this->_subscriptionID = NULL;
			$this->_userID = NULL;
            $this->_expirationDate = 0;
			$this->_subscriptionType = SUBSCRIPTION_TYPE_UNKNOWN;
			$this->_level = SUBSCRIPTION_LEVEL_EXPIRED;
			$this->_teamid = NULL;
            $this->_timestamp = 0;
		}


		public static function deleteSubscription($subscriptionID)
		{
            if(!isset($subscriptionID))
                return false;

            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSubscription::deleteSubscription() unable to get link");
                return false;
            }

			if (!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOSubscription::deleteSubscription() couldn't start transaction: " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

            $subscriptionID = mysql_real_escape_string($subscriptionID, $link);

			$sql = "DELETE FROM tdo_subscriptions WHERE subscriptionid='$subscriptionID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOSubscription::deleteSubscription() unable to delete subscription ($subscriptionID): " . mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}

			// SUCCESS!
			if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOSubscription::deleteSubscription() couldn't commit transaction after deleting subscription ($subscriptionID)" . mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}

            TDOUtil::closeDBLink($link);
            return true;
		}


		public function addSubscription($link=NULL)
		{
			if($this->_userID == NULL)
			{
				error_log("TDOSubscription::addSubscription failed because user id was not set");
				return false;
			}
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOSubscription unable to get link");
                    return false;
                }
            }
            else
                $closeDBLink = false;

			// Check to see if we already have a subscription for this user and
			// if so, set the properties of this object to match the existing
			// one and return true.
			$existingSubscription = TDOSubscription::getSubscriptionForUserID($this->_userID);
			if ($existingSubscription)
			{
				$this->_subscriptionID = $existingSubscription->getSubscriptionID();
				$this->_userID = $existingSubscription->getUserID();
				$this->_subscriptionType = $existingSubscription->getSubscriptionType();
				$this->_expirationDate = $existingSubscription->getExpirationDate();
				$this->_level = $existingSubscription->getSubscriptionLevel();
				$this->_teamid = $existingSubscription->getTeamID();
				$this->_timestamp = $existingSubscription->getTimestamp();

				if($closeDBLink)
                    TDOUtil::closeDBLink($link);
				return true;
			}

            if($this->_subscriptionID == NULL)
                $this->_subscriptionID = TDOUtil::uuid();

			$userID = mysql_real_escape_string($this->_userID, $link);
			$expirationDate = intval($this->_expirationDate);
			$subscriptionType = intval($this->_subscriptionType);
			$level = intval($this->_level);
            $timestamp = intval($this->_timestamp);

			$sql = "INSERT INTO tdo_subscriptions (" . SUBSCRIPTIONS_DB_TABLE_COLUMNS . ") VALUES ('$this->_subscriptionID', '$userID', '$expirationDate', $subscriptionType, $level, NULL, $timestamp)";

			$response = mysql_query($sql, $link);
			if($response)
            {
				// If we have a username here, log it, otherwise it will be logged in the facebook user invite
//				$userName = $this->_email;
//				if(!empty($userName))
//				{
//					$session = TDOSession::getInstance();
//					TDOChangeLog::addChangeLog($this->_listid, $session->getUserId(), $this->_invitationid, $userName, ITEM_TYPE_INVITATION, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
//				}
				if($closeDBLink)
                    TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOSubscription::addSubscription failed: ".mysql_error());
			}
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
		}


        public static function createSubscription($userID, $expirationDate=0, $subscriptionType=SUBSCRIPTION_TYPE_UNKNOWN, $level=SUBSCRIPTION_LEVEL_EXPIRED, $link=NULL)
        {
            if(!isset($userID))
                return false;

			// Check to see if there's already a subscription created for the
			// userID and if so, return the existing subscription.
			$subscription = TDOSubscription::getSubscriptionForUserID($userID);
			if (!empty($subscription))
				return $subscription->getSubscriptionID();

			$subscription = new TDOSubscription();
			$subscription->setUserID($userID);

			if ($expirationDate)
				$subscription->setExpirationDate($expirationDate);

			if ($subscriptionType)
				$subscription->setSubscriptionType($subscriptionType);

			if ($level)
				$subscription->setSubscriptionLevel($level);

            $currentTime = time();
            $subscription->setTimestamp($currentTime);

            if($subscription->addSubscription($link) == false)
            {
                return false;
            }
            $subscriptionID = $subscription->getSubscriptionID();

            return $subscriptionID;
        }


		public static function deleteSubscriptions($subscriptionIDs)
		{
            if(!isset($subscriptionIDs))
                return false;

            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSubscription::deleteSubscriptions() unable to get link");
               return false;
            }

			if (!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOSubscription::deleteSubscriptions() couldn't start transaction: " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

            foreach($subscriptionIDs as $subscriptionID)
            {
                $sql = "DELETE FROM tdo_subscriptions WHERE subscriptionid='$subscriptionID'";
                if(!mysql_query($sql, $link))
                {
                    error_log("TDOSubscription::deleteSubscriptions() unable to delete subscription $subscriptionID: " . mysql_error());
					mysql_query("ROLLBACK", $link);
					TDOUtil::closeDBLink($link);
					return false;
                }
            }

			// SUCCESS!
			if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOSubscription::deleteSubscriptions() couldn't commit transaction after deleting subscriptions" . mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}

            TDOUtil::closeDBLink($link);
            return true;
		}


		// This function looks for the specified user's personal subscription
		// which can be used for personal purchases (either through Stripe or
		// IAP).
		public static function getSubscriptionIDForUserID($userID, $link=NULL)
		{
            if(empty($userID))
                return false;

			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOSubscription::getSubscriptionIDForUserID() could not get DB connection.");
					return false;
				}
			}

            $userID = mysql_real_escape_string($userID, $link);

			$sql = "SELECT subscriptionid FROM tdo_subscriptions WHERE userid='$userID'";

            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return $row['subscriptionid'];
                }
            }
            else
                error_log("getSubscriptionIDForUserID failed:".mysql_error());

			if ($closeLink)
				TDOUtil::closeDBLink($link);
            return false;
		}


        public static function getSubscriptionForUserID($userID, $link=NULL)
        {
            if(empty($userID))
                return false;

			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOSubscription::getSubscriptionForUserID() failed to get a link to the DB.");
					return false;
				}
			}

            $userID = mysql_real_escape_string($userID, $link);

			$sql = "SELECT " . SUBSCRIPTIONS_DB_TABLE_COLUMNS . " FROM tdo_subscriptions WHERE userid='$userID'";

            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
					$subscription = TDOSubscription::_subscriptionFromRecord($row);
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return $subscription;
                }
            }
            else
                error_log("getSubscriptionForUserID failed:".mysql_error());

			if ($closeLink)
				TDOUtil::closeDBLink($link);
            return false;
        }


		public static function getSubscriptionForSubscriptionID($subscriptionID)
		{
			if (empty($subscriptionID))
				return false;

            $link = TDOUtil::getDBLink();
            if(!$link)
                return false;

            $subscriptionID = mysql_real_escape_string($subscriptionID, $link);

			$sql = "SELECT " . SUBSCRIPTIONS_DB_TABLE_COLUMNS . " FROM tdo_subscriptions WHERE subscriptionid='$subscriptionID'";

            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
					$subscription = TDOSubscription::_subscriptionFromRecord($row);
					TDOUtil::closeDBLink($link);
					return $subscription;
                }
            }
            else
                error_log("getSubscriptionForSubscriptionID failed:".mysql_error());

            TDOUtil::closeDBLink($link);
            return false;
		}


		public static function getUserIDForSubscriptionID($subscriptionID, $link=NULL)
		{
			if(empty($subscriptionID))
				return false;

			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOSubscription::getUserIDForSubscriptionID() could not get DB connection.");
					return false;
				}
			}

			$subscriptionID = mysql_real_escape_string($subscriptionID, $link);

			$sql = "SELECT userid FROM tdo_subscriptions WHERE subscriptionid='$subscriptionID'";

			$result = mysql_query($sql, $link);
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return $row['userid'];
				}
			}
			else
				error_log("getUserIDForSubscriptionID failed:".mysql_error());

			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}


		public static function searchSubscriptions($searchTerm=NULL, $offset=0, $limit=10)
		{
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::getSubscriptions() failed to get dblink");
				return false;
			}

			// Prevent a crazy number of subscriptions from being returned in
			// one call.
			if ($limit > SUBSCRIPTIONS_LIST_MAX_RETURN_LIMIT)
				$limit = SUBSCRIPTIONS_LIST_MAX_RETURN_LIMIT;

			if ($offset < 0)
				$offset = 0;


			$subscriptions = array();

			// Use the search term to search usernames, first
			// names, and last names.

			$searchTerms = '';

			if ($searchTerm)
			{
				$searchTerm = mysql_real_escape_string($searchTerm, $link);
				$searchTerms = " WHERE tdo_user_accounts.username like '%$searchTerm%' OR tdo_user_accounts.first_name like '%$searchTerm%' OR tdo_user_accounts.last_name like '%$searchTerm%' ";
			}

			//
			// USER SEARCH
			//

			$sql = "SELECT " . SUBSCRIPTIONS_DB_TABLE_COLUMNS .
				" FROM tdo_subscriptions " .
				" LEFT JOIN tdo_user_accounts ON tdo_subscriptions.userid = tdo_user_accounts.userid " .
				$searchTerms .
//				" ORDER BY tdo_subscriptions.expiration_date " .
				" LIMIT $limit OFFSET $offset";

//			error_log("SQL: " . $sql);

			$result = mysql_query($sql, $link);
			if ($result)
			{
				while ($row = mysql_fetch_array($result))
				{
					$subscription = TDOSubscription::_subscriptionFromRecord($row);
					if ($subscription && empty($subscriptions[$subscription->getSubscriptionID()]))
					{
						$subscriptions[$subscription->getSubscriptionID()] = $subscription;
					}
				}
			}
			else
			{
				error_log("TDOSubscription::getSubscriptions() unable to return subscriptions with offset $offset and limit $limit: " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			TDOUtil::closeDBLink($link);
			return $subscriptions;
		}


		public static function getSubscriptionLevelForUserID($userID)
		{
			if (empty($userID))
				return false;

            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOSubscription::getSubscriptionLevelForUserID() failed to get dblink");
                return SUBSCRIPTION_LEVEL_EXPIRED;
			}

            $userID = mysql_real_escape_string($userID, $link);

			$sql = "SELECT expiration_date,level FROM tdo_subscriptions WHERE userid='$userID'";
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
					$expirationDate = $row['expiration_date'];
					$level = $row['level'];

					TDOUtil::closeDBLink($link);
					return TDOSubscription::getEffectiveSubscriptionLevel($expirationDate, $level);
                }
            }
            else
                error_log("TDOSubscription::getSubscriptionLevelForUserID() failed:".mysql_error());

            TDOUtil::closeDBLink($link);
            return SUBSCRIPTION_LEVEL_EXPIRED;
		}


		public static function getEffectiveSubscriptionLevel($expirationDate=0, $level=0)
		{
			$currentTime = time();
			if ($currentTime > $expirationDate)
			{
				// The user's subscription has expired
				return SUBSCRIPTION_LEVEL_EXPIRED; // default
			}

			return $level;
		}


		public static function updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationDate, $subscriptionType = SUBSCRIPTION_TYPE_UNKNOWN, $subscriptionLevel = SUBSCRIPTION_LEVEL_EXPIRED, $teamID = NULL, $link=NULL)
		{
//			error_log("updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationDate, $subscriptionType, $subscriptionLevel)");

			if (empty($subscriptionID))
			{
				error_log("TDOSubscription::updateSubscriptionWithNewExpirationDate() failed because subscriptionID is empty");
				return false;
			}

            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOSubscription::updateSubscriptionWithNewExpirationDate() failed to get dblink");
                    return false;
                }
            }
            else
                $closeDBLink = false;

			$subscriptionID = mysql_real_escape_string($subscriptionID, $link);
			if (empty($teamID))
				$teamID = "";
			else
				$teamID = mysql_real_escape_string($teamID, $link);

			$sql = "UPDATE tdo_subscriptions SET expiration_date=$newExpirationDate,type=$subscriptionType,level=$subscriptionLevel,teamid='$teamID' WHERE subscriptionid='$subscriptionID'";

			$response = mysql_query($sql, $link);
			if($response)
            {
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOSubscription::updateSubscriptionWithNewExpirationDate() failed: ".mysql_error());
			}

            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
		}


		public static function updateSubscriptionType($subscriptionID, $subscriptionType = SUBSCRIPTION_TYPE_UNKNOWN)
		{
			if (empty($subscriptionID))
			{
				error_log("TDOSubscription::updateSubscriptionType() failed because subscriptionID is empty");
				return false;
			}

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::updateSubscriptionType() failed to get dblink");
				return false;
			}

			$subscriptionID = mysql_real_escape_string($subscriptionID, $link);

			$sql = "UPDATE tdo_subscriptions SET type=$subscriptionType WHERE subscriptionid='$subscriptionID'";

			$response = mysql_query($sql, $link);
			if($response)
            {
                TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOSubscription::updateSubscriptionType() failed: ".mysql_error());
			}

            TDOUtil::closeDBLink($link);
            return false;
		}


		public static function setTeamForSubscription($subscriptionID, $teamID, $link=NULL)
		{
			$closeDBLink = false;
			if(empty($link))
			{
				$closeDBLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOSubscription::setTeamForSubscription() failed to get dblink");
					return false;
				}
			}

			$subscriptionID = mysql_real_escape_string($subscriptionID, $link);
			$teamID = mysql_real_escape_string($teamID, $link);

			$sql = "UPDATE tdo_subscriptions SET teamid='$teamID' WHERE subscriptionid='$subscriptionID'";

			$response = mysql_query($sql, $link);
			if($response)
			{
				if ($closeDBLink)
					TDOUtil::closeDBLink($link);
				return true;
			}
			else
			{
				error_log("TDOSubscription::setTeamForSubscription() failed: ".mysql_error());
			}

			if ($closeDBLink)
				TDOUtil::closeDBLink($link);
			return false;
		}


		public static function switchSubscriptionToMonthly($subscriptionID)
		{
			if (empty($subscriptionID))
			{
				error_log("TDOSubscription::switchSubscriptionToMonthly() called with a missing parameter.");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER,
							 );
			}

			// Make sure that the subscription is an active subscription on a
			// yearly billing cycle.  If it's not, there's no reason to
			// continue doing anything.

			$subscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
			if (!$subscription)
			{
				error_log("TDOSubscription::switchSubscriptionToMonthly() could not locate the specified subscription in the database.");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_UNKNOWN_SUBSCRIPTION,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_UNKNOWN_SUBSCRIPTION,
							 );
			}

			// Ensure that the subscription is currently billed YEARLY
			if ($subscription->getSubscriptionType() == SUBSCRIPTION_TYPE_MONTH)
			{
				error_log("TDOSubscription::switchSubscriptionToMonthly() was passed a subscription that is already configured for monthly billing.");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_ALREADY_MONTHLY,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_ALREADY_MONTHLY,
							 );
			}

			// Make sure that the expiration date is further away than one month
			// from now.

			$nowDate = new DateTime("now", new DateTimeZone("UTC"));
			$oneMonthPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL);
			$oneMonthFromNowDate = $nowDate->add(new DateInterval($oneMonthPeriodSetting));
			if ($oneMonthFromNowDate->getTimestamp() > $subscription->getExpirationDate())
			{
				error_log("TDOSubscription::switchSubscriptionToMonthly() was passed a subscription that is not eligible to change from yearly to monthly because the expiration date of the subscription occurs before one month from right now.");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_INVALID_MONTHLY_CANDIDATE,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_INVALID_MONTHLY_CANDIDATE,
							 );
			}

			// WOOT!  We can finally allow this to happen!

			if (!TDOSubscription::updateSubscriptionType($subscriptionID, SUBSCRIPTION_TYPE_MONTH))
			{
				error_log("TDOSubscription::switchSubscriptionToMonthly() failed in TDOSubscription::updateSubscriptionType for subscription ID: $subscriptionID");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_UNKNOWN,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_UNKNOWN,
							 );
			}

			// SUCCESS!
			return true;
		}


		public static function getSubscriptionInfoForUserID($userID, $includeBillingInfo = true)
		{
			if (empty($userID))
				return false;

			$subscription = TDOSubscription::getSubscriptionForUserID($userID);
			if (empty($subscription))
			{
				error_log("TDOSubscription::getSubscriptionInfoForUserID($userID) unable to locate user's subscription.");
				return false;
			}

			$subscriptionID = $subscription->getSubscriptionID();

			$now = time();
			$expirationDate = $subscription->getExpirationDate();
			$expired = true;
			if ($expirationDate > $now)
				$expired = false;

			$effectiveSubscriptionLevel = TDOSubscription::getEffectiveSubscriptionLevel($subscription->getExpirationDate(), $subscription->getSubscriptionLevel());

			$subscriptionType = $subscription->getSubscriptionType();
			$subscriptionTypeString = NULL;
			switch ($subscriptionType)
			{
				case SUBSCRIPTION_TYPE_MONTH:
					$subscriptionTypeString = "month";
					break;
				case SUBSCRIPTION_TYPE_YEAR:
					$subscriptionTypeString = "year";
					break;
				case SUBSCRIPTION_TYPE_UNKNOWN:
				default:
					$subscriptionTypeString = "unknown";
					break;
			}

			$eligibilityDate = TDOSubscription::getEligibilityDateForExpirationTimestamp($expirationDate);
			$eligible = false;
			if ($eligibilityDate < $now)
				$eligible = true;

			$pricingTable = TDOSubscription::getPersonalSubscriptionPricingTable();

			//
			// Determine what the new expiration dates would be for both a month
			// and year subscriptions.
			//
			$newMonthExpirationDate = $subscription->getSubscriptionRenewalExpirationDateForType(SUBSCRIPTION_TYPE_MONTH);
			$newYearExpirationDate = $subscription->getSubscriptionRenewalExpirationDateForType(SUBSCRIPTION_TYPE_YEAR);

            //See if the user has an autorenewing IAP subscription
            $autorenewingIAP = TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userID);
			$autorenewingType = "none";
			$isAutorenewalCancelled = false;
			if (TDOInAppPurchase::userIsAppleIAPUser($userID))
			{
				$autorenewingType = "Apple IAP";
				$iapReceipt = TDOInAppPurchase::IAPAutorenewReceiptForUser($userID);

				if ($iapReceipt['autorenewal_canceled'] == 1)
				{
					$isAutorenewalCancelled = true;
				}
			}
			else if (TDOInAppPurchase::userIsGooglePlayUser($userID))
			{
				$autorenewingType = "GooglePlay";
				$gpToken = TDOInAppPurchase::googlePlayTokenForUser($userID);

				if ($gpToken['autorenewal_canceled'] == 1)
				{
					$isAutorenewalCancelled = true;
				}
			}

			// Check to see if we need to set the "switch_to_monthly" option by
			// seeing if the current expiration date is farther out than one
			// month.  If so, change the newMonthExpirationDate to just be set
			// to the current expiration date and charge the user $0.00 to make
			// the switch from a yearly to monthly billing cycle.
			$switchToMonthlyOption = false;

			$billingInfo = NULL;
			if ($includeBillingInfo == true)
			{
				$billingInfo = TDOSubscription::getSubscriptionBillingInfoForUser($userID);

				// Only offer this as an option if the user is on a yearly billing
				// cycle AND the user is currently a normal billed user.
				if ( ($subscriptionType == SUBSCRIPTION_TYPE_YEAR) && (!empty($billingInfo)) )
				{
					$oneMonthFromToday = new DateTime('@' . $now, new DateTimeZone("UTC"));
					$oneMonthPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL);
					$oneMonthFromToday->add(new DateInterval($oneMonthPeriodSetting));
					if ($expirationDate > $oneMonthFromToday->getTimestamp())
					{
						$switchToMonthlyOption = true;
						$newMonthExpirationDate = $expirationDate;
					}
				}
			}

			$teamInfo = false;
			$teamID = $subscription->getTeamID();
			if ($teamID)
			{
				// Prevent the user from buying any more time on their personal
				// account because their account is paid for by a team.
				$eligible = false;

				// Return information about the team
				$teamName = TDOTeamAccount::teamNameForTeamID($teamID);

				$teamInfo = array(
								  "teamID" => $teamID,
								  "teamName" => $teamName
								  );
			}

			$isTeamBillingAdmin = TDOTeamAccount::isBillingAdminForAnyTeam($userID);

			$info = array(
						  "subscription_id" => $subscriptionID,
						  "expiration_date" => $expirationDate,
						  "expired" => $expired,

						  "subscription_level" => $effectiveSubscriptionLevel,
						  "subscription_type" => $subscriptionTypeString,

						  "eligibility_date" => $eligibilityDate,
						  "eligible" => $eligible,

						  "pricing_table" => $pricingTable,

						  "new_month_expiration_date" => $newMonthExpirationDate,
						  "new_year_expiration_date" => $newYearExpirationDate,

                          "iap_autorenewing_account" => $autorenewingIAP,

						  "iap_autorenewing_account_type" => $autorenewingType,

						  "iap_autorenewing_account_cancelled" => $isAutorenewalCancelled,

						  "switch_to_monthly" => $switchToMonthlyOption,

						  "teamInfo" => $teamInfo,

						  "isTeamBillingAdmin" => $isTeamBillingAdmin
						  );


			if ($billingInfo)
			{
				$info["billing_info"] = $billingInfo;
			}

			return $info;
		}


		//
		// Subscription Pricing and Purchasing Methods
		//


		public static function getPersonalSubscriptionPricingTable()
		{
			// Currency denomination is ALWAYS specified in USD
			return array(
						 "month" => 1.99,
						 "year" => 19.99
						 );
		}


		public static function getSubscriptionBillingInfoForUser($userID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::getSubscriptionBillingInfoForUser() failed because userID is empty");
				return false;
			}

			$stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);
			if (!$stripeCustomerID)
			{
				return false;
			}

			// Now read the Stripe Card information from Stripe using
			// the Stripe Customer ID.
			$billingInfo = array();

			try
			{
				$stripeCustomer = Stripe_Customer::retrieve($stripeCustomerID);

				// We've got information about a Stripe customer, so return the
				// pertinent information.
				$stripeCardInfo = array();
				$activeCard = TDOSubscription::getActiveStripeCard($stripeCustomer);
				if (!empty($activeCard))
				{
					// Only return this information if ALL of the information
					// we need is specified.
					if (isset($activeCard['exp_month'],
							  $activeCard['exp_year'],
							  $activeCard['last4'],
							  $activeCard['brand']))
					{
						$billingInfo['exp_month'] = $activeCard['exp_month'];
						$billingInfo['exp_year'] = $activeCard['exp_year'];
						$billingInfo['last4'] = $activeCard['last4'];
						$billingInfo['type'] = $activeCard['brand'];

						// Also throw on the name of the card if it exists
						if (isset($activeCard['name']))
							$billingInfo['name'] = $activeCard['name'];
					}
				}
			}
			catch (Stripe_Error $e)
			{
				$body = $e->getJsonBody();
				$err = $body['error'];

                //Boyd was seeing php errors spit out here because $err['code'] was not defined, so we should check all the indexes
                //before we use them
                $type = "UNKNOWN";
                if(isset($err['type']))
                    $type = $err['type'];

                $code = "UNKNOWN";
                if(isset($err['code']))
                    $code = $err['code'];

                $param = "UNKNOWN";
                if(isset($err['param']))
                    $param = $err['param'];

                $message = "UNKNOWN";
                if(isset($err['message']))
                    $message = $err['message'];

				error_log("TDOSubscription::getSubscriptionBillingInfoForUser received a Stripe_Error calling Stripe_Customer::retrieve($stripeCustomerID) for userid ($userID) ... we will silently ignore this error, status=" . $e->getHttpStatus() . ", type=" . $type . ", code=" . $code . ", param=" . $param . ", message=" . $message);
			}
			catch (Exception $e)
			{
				error_log("TDOSubscription::getSubscriptionBillingInfoForUser received an Excpetion calling Stripe_Customer::retrieve($stripeCustomerID) for userid ($userID) ... we will silently ignore this error: " . $e->getMessage());
				return false;
			}

			return $billingInfo;
		}


		public static function getEligibilityDateForExpirationTimestamp($expirationTimestamp)
		{
			$expirationDate = new DateTime('@' . $expirationTimestamp, new DateTimeZone("UTC"));
			$eligibilityDate = $expirationDate->sub(new DateInterval(SUBSCRIPTION_ELIGIBILITY_INTERVAL));
			return $eligibilityDate->format('U'); //getTimestamp();
		}


		//
		// Stripe Helper Methods
		//


		public static function jsonErrorForStripeException($stripeException, $stripeCall=NULL)
		{
			if (empty($stripeException))
				return false;

			$body = $stripeException->getJsonBody();
			$err = $body['error'];

			if (empty($stripeCall))
				$stripeCall = "<UNKNOWN STRIPE CALL>";

			$errString = "Received a Stripe_CardError ($stripeCall) with status=" . $stripeException->getHttpStatus();
			$jsonError = '{"success":false';
			if (isset($err, $err['message']))
			{
				$errString .= ", message=" . $err['message'];
				$jsonError .= ', "error":"' . $err['message'] . '"';
			}
			if (isset($err, $err['type']))
			{
				$errString .= ", type=" . $err['type'];
				$jsonError .= ', "type":"' . $err['type'] . '"';
			}
			if (isset($err, $err['code']))
			{
				$errString .= ", code=" . $err['code'];
				$jsonError .= ', "code":"' . $err['code'] . '"';
			}
			if (isset($err, $err['param']))
			{
				$errString .= ", param=" . $err['param'];
				$jsonError .= ', "param":"' . $err['param'] . '"';
			}


			$jsonError .= ', "http-status":' . $stripeException->getHttpStatus() . '"';
			$jsonError .= '}';

//			$backtraceArray = debug_backtrace();
//			$callingFunctionBacktrace = $backtraceArray[count($backtraceArray) - 2];
//			$errString .= "\n\n" . serialize($callingFunctionBacktrace);

			error_log($errString);
			return $jsonError;
		}


		public static function errorArrayForStripeException($stripeException, $userID, $stripeCall=NULL)
		{
			if (empty($stripeException))
				return false;

			$body = $stripeException->getJsonBody();
			$err = $body['error'];

			if (empty($stripeCall))
				$stripeCall = "<UNKNOWN STRIPE CALL>";

			$errString = "User ($userID) received a Stripe_CardError ($stripeCall) with status=" . $stripeException->getHttpStatus();
			if (isset($err, $err['message']))
			{
				$errString .= ", message=" . $err['message'];
			}
			if (isset($err, $err['type']))
			{
				$errString .= ", type=" . $err['type'];
			}
			if (isset($err, $err['code']))
			{
				$errString .= ", code=" . $err['code'];
			}
			if (isset($err, $err['param']))
			{
				$errString .= ", param=" . $err['param'];
			}
			error_log($errString);

			$errArray = array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_STRIPE_EXCEPTION,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_STRIPE_EXCEPTION,
							 "method" => $stripeCall,
							 "stripe_error" => $err,
							 "stripe_error_message" => $err['message'],
							 "stripe_http_status" => $stripeException->getHttpStatus(),
							 );

			return $errArray;
		}


		public static function errorArrayForException($exception, $userID, $methodCall=NULL)
		{
			if (empty($exception))
				return false;

			if (empty($methodCall))
				$methodCall = "<UNKNOWN METHOD>";

			$errString = "User ($userID) received an exception in method ($methodCall) with message: " . $exception->getMessage();
			error_log($errString);

			$errArray = array(
							  "errcode" => SUBSCRIPTION_ERROR_CODE_EXCEPTION,
							  "errdesc" => SUBSCRIPTION_ERROR_DESC_EXCEPTION,
							  "exception_message" => $exception->getMessage(),
							  "method" => $methodCall
							  );

			return $errArray;
		}


		public static function getStripeCustomerID($userID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::getStripeCustomerID() failed because userid is empty");
				return false;
			}

            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOSubscription::getStripeCustomerID() failed to get dblink");
                return false;
			}

            $userID = mysql_real_escape_string($userID, $link);

			$sql = "SELECT stripe_userid FROM tdo_stripe_user_info WHERE userid='$userID'";
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
					$stripeUserID = $row['stripe_userid'];

					TDOUtil::closeDBLink($link);
					return $stripeUserID;
                }
            }
            else
                error_log("TDOSubscription::getStripeCustomerID() failed:".mysql_error());

            TDOUtil::closeDBLink($link);
            return false;
		}


		public static function storeStripeCustomer($userID, $stripeCustomerID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::storeStripeCustomer() failed because userid is empty");
				return false;
			}
			if (empty($stripeCustomerID))
			{
				error_log("TDOSubscription::storeStripeCustomer() failed because stripeCustomerID is empty");
				return false;
			}

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::storeStripeCustomer() failed to get dblink");
				return false;
			}

			$userID = mysql_real_escape_string($userID, $link);
			$stripeCustomerID = mysql_real_escape_string($stripeCustomerID, $link);

			$sql = "INSERT INTO tdo_stripe_user_info (userid, stripe_userid) VALUES ('$userID', '$stripeCustomerID')";

			$response = mysql_query($sql, $link);
			if($response)
            {
                TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOSubscription::storeStripeCustomer() failed: ".mysql_error());
			}

            TDOUtil::closeDBLink($link);
            return false;
		}


		public static function logStripePayment($userID, $teamID, $numOfUsers, $stripeUserID, $stripeChargeID, $cardType, $last4, $subscriptionType, $amount, $timestamp, $chargeDescription)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::logStripePayment() failed because userID is empty");
				return false;
			}
			if(empty($stripeUserID))
			{
				error_log("TDOSubscription::logStripePayment() failed because stripeUserID is empty");
				return false;
			}
			if(empty($stripeChargeID))
			{
				error_log("TDOSubscription::logStripePayment() failed because stripeChargeID is empty");
				return false;
			}
			if(empty($cardType))
			{
				error_log("TDOSubscription::logStripePayment() failed because cardType is empty");
				return false;
			}
			if(empty($last4))
			{
				error_log("TDOSubscription::logStripePayment() failed because last4 is empty");
				return false;
			}
			if(empty($chargeDescription))
			{
				error_log("TDOSubscription::logStripePayment() failed because chargeDescription is empty");
				return false;
			}

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::logStripePayment() failed to get dblink");
				return false;
			}

			$userID = mysql_real_escape_string($userID, $link);
			$stripeUserID = mysql_real_escape_string($stripeUserID, $link);
			$stripeChargeID = mysql_real_escape_string($stripeChargeID, $link);
			$cardType = mysql_real_escape_string($cardType, $link);
			$last4 = mysql_real_escape_string($last4, $link);
			$chargeDescription = mysql_real_escape_string($chargeDescription, $link);
			$timestamp = time();

			if (isset($teamID))
				$teamID = mysql_real_escape_string($teamID, $link);

			$sql = "INSERT INTO tdo_stripe_payment_history (userid, stripe_userid, stripe_chargeid, card_type, last4, type, amount, charge_description, timestamp, teamid, license_count) VALUES ('$userID', '$stripeUserID', '$stripeChargeID', '$cardType', '$last4', $subscriptionType, $amount, '$chargeDescription', $timestamp";
			$sql .= $teamID ? ", '$teamID'" : ", NULL";
			$sql .= ", $numOfUsers";
			$sql .= ")";

			$response = mysql_query($sql, $link);
			if($response)
            {
                TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOSubscription::logStripePayment() failed: ".mysql_error());
			}

            TDOUtil::closeDBLink($link);
            return false;
		}


		public static function addOrUpdateUserPaymentSystemInfo($userID, $paymentSystemType, $paymentSystemUserID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::addOrUpdateUserPaymentSystemInfo() failed because userID is empty");
				return false;
			}
			if(empty($paymentSystemUserID))
			{
				error_log("TDOSubscription::addOrUpdateUserPaymentSystemInfo() failed because paymentSystemUserID is empty");
				return false;
			}

			$paymentSystemInfo = TDOSubscription::paymentSystemInfoForUserID($userID);
			if ($paymentSystemInfo)
			{
				return TDOSubscription::updateUserPaymentSystemInfo($userID, $paymentSystemType, $paymentSystemUserID);
			}
			else
			{
				return TDOSubscription::addUserPaymentSystemInfo($userID, $paymentSystemType, $paymentSystemUserID);
			}
		}


		public static function addUserPaymentSystemInfo($userID, $paymentSystemType, $paymentSystemUserID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::addUserPaymentSystemInfo() failed because userID is empty");
				return false;
			}
			if(empty($paymentSystemUserID))
			{
				error_log("TDOSubscription::addUserPaymentSystemInfo() failed because paymentSystemUserID is empty");
				return false;
			}

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::addUserPaymentSystemInfo() failed to get dblink");
				return false;
			}

			$userID = mysql_real_escape_string($userID, $link);
			$paymentSystemUserID = mysql_real_escape_string($paymentSystemUserID, $link);

			$sql = "INSERT INTO tdo_user_payment_system (userid, payment_system_type, payment_system_userid) VALUES ('$userID', $paymentSystemType, '$paymentSystemUserID')";

			$response = mysql_query($sql, $link);
			if($response)
            {
                TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOSubscription::addUserPaymentSystemInfo() failed: ".mysql_error());
			}

            TDOUtil::closeDBLink($link);
            return false;
		}


		public static function updateUserPaymentSystemInfo($userID, $paymentSystemType, $paymentSystemUserID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::updateUserPaymentSystemInfo() failed because userID is empty");
				return false;
			}
			if(empty($paymentSystemUserID))
			{
				error_log("TDOSubscription::updateUserPaymentSystemInfo() failed because paymentSystemUserID is empty");
				return false;
			}

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::updateUserPaymentSystemInfo() failed to get dblink");
				return false;
			}

			$userID = mysql_real_escape_string($userID, $link);
			$paymentSystemUserID = mysql_real_escape_string($paymentSystemUserID, $link);

			$sql = "UPDATE tdo_user_payment_system SET payment_system_type=$paymentSystemType, payment_system_userid='$paymentSystemUserID' WHERE userid='$userID'";

			$response = mysql_query($sql, $link);
			if($response)
            {
                TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOSubscription::updateUserPaymentSystemInfo() failed: ".mysql_error());
			}

            TDOUtil::closeDBLink($link);
            return false;
		}


		public static function paymentSystemInfoForUserID($userID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::paymentSystemInfoForUserID() failed because userid is empty");
				return false;
			}

            $link = TDOUtil::getDBLink();
            if(!$link)
			{
				error_log("TDOSubscription::paymentSystemInfoForUserID() failed to get dblink");
                return false;
			}

            $userID = mysql_real_escape_string($userID, $link);

			$sql = "SELECT payment_system_type,payment_system_userid FROM tdo_user_payment_system WHERE userid='$userID'";
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
					$paymentSystemType = $row['payment_system_type'];
					$paymentSystemUserID = $row['payment_system_userid'];

					TDOUtil::closeDBLink($link);

					$paymentSystemInfo = array(
											   "payment_system_type" => $paymentSystemType,
											   "payment_system_userid" => $paymentSystemUserID
											   );
					return $paymentSystemInfo;
                }
            }
            else
                error_log("TDOSubscription::paymentSystemInfoForUserID() failed:".mysql_error());

            TDOUtil::closeDBLink($link);
            return false;
		}


		public static function deleteStripeCustomerInfoForUserID($userID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::deleteStripeCustomerInfoForUserID() failed because userid is empty");
				return false;
			}

            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSubscription::deleteStripeCustomerInfoForUserID() unable to get link");
                return false;
            }

			if (!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOSubscription::deleteStripeCustomerInfoForUserID() couldn't start transaction: " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}


            $userID = mysql_real_escape_string($userID, $link);
			$sql = "DELETE FROM tdo_stripe_user_info WHERE userid='$userID'";
			$response = mysql_query($sql, $link);
            if(!$response)
            {
                error_log("TDOSubscription::deleteStripeCustomerInfoForUserID() delete stripe customer info failed: " . mysql_error());
				mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
				return false;
			}

			// Set the subscription type (monthly|yearly) to UNKNOWN so that if
			// the user becomes a team admin or anything else like that, they
			// won't get an erroneous update by the autorenew daemon. If the
			// autorenew daemon encounters an UNKNOWN subscription type, it will
			// not process an autorenewal for a standard (non-team) premium
			// account.
			$sql = "UPDATE tdo_subscriptions SET type=0 WHERE userid='$userID'";
			$response = mysql_query($sql, $link);
			if (!$response)
			{
				error_log("TDOSubscription::deleteSubscription() couldn't set the subscription type to unkonwn deleting customer stripe information" . mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}

			$sql = "DELETE FROM tdo_user_payment_system WHERE userid='$userID'";
			$response = mysql_query($sql, $link);
            if($response)
            {
				if(!mysql_query("COMMIT", $link))
				{
					error_log("TDOSubscription::deleteSubscription() couldn't commit transaction after deleting customer stripe information" . mysql_error());
					mysql_query("ROLLBACK", $link);
					TDOUtil::closeDBLink($link);
					return false;
				}

                TDOUtil::closeDBLink($link);
				return true;
            }
            else
			{
                error_log("TDOSubscription::deleteStripeCustomerInfoForUserID() delete user payment info: " . mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}

            TDOUtil::closeDBLink($link);
			return false;
		}


		public static function makeStripeCharge($userID, $teamID, $stripeToken, $last4, $unitPriceInCents, $unitCombinedPriceInCents, $subtotalInCents, $discountPercentage, $discountInCents, $teamCreditMonths, $teamCreditsPriceDiscountInCents, $authoritativePriceInCents, $chargeDescription, $subscriptionType, $newExpirationDate, $isGiftCodePurchase=false, $numOfSubscriptions=1)
		{
			// Parameters:
			//
			//		userID				(required)
			//			This is a Todo Cloud userid, the object that
			//			will have the credit card associated with it.
			//		teamID				(required if team purchase)
			//			This is the Todo Cloud teamid which is what this charge
			//			will be attributed to.
			//		stripeToken			(required if last4 is not specified)
			//			Either stripeToken or last4 must be specified but not
			//			both.
			//		last4				(required if stripeToken is not specified)
			//			Either stripeToken or last4 must be specified but not
			//			both.
			//		unitPriceInCents			(required)
			//			How much does each subscription cost?
			//		unitCombinedPriceInCents	(required)
			//			unitPrice x numOfSubscriptions
			//		subtotalInCents			(required)
			//			Amount in USD CENTS. For example, $19.99 should be
			//			specified as 1999.
			//		discountPercentage			(required, can be 0)
			//			Individual purchases should pass in 0. Team purchases
			//			with actual discounts should be passed in as an integer.
			//			For example, 5 would represent a 5% discount.
			//		discountInCents				(required, can be 0)
			//			The actual dollar discount in USD CENTS. For example,
			//			a discount of $3.99 would be specified as 399.
			//		teamCredits					(optional)
			//			An array of team credits that turn into a discount for
			//			the team. The array elements contain:
			//				userID						(the donor of the credits)
			//				numOfMonthsRemaining		(informational)
			//				numOfMonthsHarvested		(the number of donation month credits being used in this transaction)
			//		teamCreditsPriceDiscountInCents	(optional)
			//			The discount amount that the credits equal.
			//		authoritativePriceInCents	(required)
			//			Amount in USD CENTS.  For example, $19.99 should be
			//			specified as 1999.
			//		chargeDescription			(required)
			//			The description that will show up to the end user as the
			//			reason for the charge.
			//		numOfSubscriptions			(required to be > 0)
			//			If this is a team purchase, this will specify how many
			//			subscriptions are purchased.
			//

			if (!isset($userID, $authoritativePriceInCents, $chargeDescription))
			{
				error_log("TDOSubscription::makeStripeCharge() called with a missing parameter.");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER,
							 );
			}

			if (!isset($stripeToken) && !isset($last4)) // stripeToken OR last4 required
			{
				error_log("TDOSubscription::makeStripeCharge() called and missing a required parameter: stripeToken or last4 required");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER,
							 );
			}

			// The user can be one of two types of Stripe customers:
			//		1. They've NEVER paid us before
			//		2. They've paid us in the past
			//			2a. Their previous card is still valid and they chose to continue paying with that
			//			2b. They provided new card information
			//
			//			UNNATURAL PIG POSITION (IMPOSSIBLE CONDITION):
			//			2c. The user's previous card is expired.  We don't give the
			//				user this option when making a payment.  If the previous
			//				card has expired, we don't return that as a viable
			//				option.

			$username = TDOUser::usernameForUserId($userID);

			$stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);

			$stripeCustomer = NULL;
            $isNewCard = false;

			if ($stripeCustomerID)
			{
				// The user has previously paid us using Stripe, so we either need
				// to attempt a new payment OR update their card information and
				// THEN make a new payment.

				// Attempt to get the existing customer from Stripe
				try
				{
					$stripeCustomer = Stripe_Customer::retrieve($stripeCustomerID);
				}
				catch (Stripe_Error $e)
				{
					return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::makeStripeCharge(), Stripe_Customer::retrieve()");
				}
				catch (Exception $e)
				{
					return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::makeStripeCharge(), Stripe_Customer::retrieve()");
				}

				if ($last4)
				{
					// Just for kicks (even though it's next to impossible to get
					// into this situation since we just retrieved the user's info
					// before presenting them with purchase info), make sure that
					// the $last4 digits match up with what's in the customer's
					// Stripe account.
					$activeCard = TDOSubscription::getActiveStripeCard($stripeCustomer);
					if (!empty($activeCard))
					{
						if ( (empty($activeCard['last4'])) || ($last4 != $activeCard['last4']) )
						{
							error_log("TDOSubscription::makeStripeCharge (User: $userID) determined that the specified last4 ($last4) digits of the credit card aren't stored by Stripe or do not match the records on file with Stripe.");
							return array(
										 "errcode" => SUBSCRIPTION_ERROR_CODE_STRIPE_LAST4_MISMATCH,
										 "errdesc" => SUBSCRIPTION_ERROR_DESC_STRIPE_LAST4_MISMATCH,
										 );
						}
					}
				}
				else
				{
					// The user has provided NEW card information (we have a new
					// one-time stripeToken).  We need to update their card
					// information on their Stripe Customer ID.
					$stripeCustomer->card = $stripeToken;

					// Take the opportunity to update the email address on the
					// stripe account just in case they differ right now.  It may
					// make things easier debugging/searching our Stripe account to
					// track down any problems later on.
					if ($username)
					{
						if ( (empty($stripeCustomer->email)) || ($username != $stripeCustomer->email) )
							$stripeCustomer->email = $username;
					}

					try
					{
						$stripeCustomer->save();
					}
					catch (Stripe_Error $e)
					{
						return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::makeStripeCharge(), Stripe_Customer::save()");
					}
					catch (Exception $e)
					{
						return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::makeStripeCharge(), Stripe_Customer::save()");
					}
				}
			}
			else
			{
                $isNewCard = true;
				// This is a NEW Stripe customer.  Create a new Stripe Customer
				// object for this user.
				$parameters = array("description" => $userID,
									"card" => $stripeToken);

				if ($username)
					$parameters["email"] = $username;

//				error_log("STRIPE_CUSTOMER CREATE PARAMS: " . var_export($parameters, true));

				// Create the new Stripe Customer now
				try
				{
					$stripeCustomer = Stripe_Customer::create($parameters);
				}
				catch (Stripe_Error $e)
				{
					return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::makeStripeCharge(), Stripe Error, Stripe_Customer::create()");
				}
				catch (Exception $e)
				{
					return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::makeStripeCharge(), General Exception, Stripe_Customer::create()");
				}

				$stripeCustomerID = $stripeCustomer->id;

                // Store the Stripe customer ID IF THIS IS NOT A GIFT CODE PURCHASE
                if(!$isGiftCodePurchase)
                {
                    if (TDOSubscription::storeStripeCustomer($userID, $stripeCustomerID) == false)
                    {
                        error_log("TDOSubscription::makeStripeCharge (User: $userID) failed calling TDOSubscription::storeStripeCustomer().");
                        return array(
                                     "errcode" => SUBSCRIPTION_ERROR_CODE_SAVE_PAYMENT_CUSTOMER,
                                     "errdesc" => SUBSCRIPTION_ERROR_DESC_SAVE_PAYMENT_CUSTOMER,
                                     );
                    }
                }
			}

			// If we make it this far, we have a valid $stripeCustomerID we can use
			// to make a charge!

			$isTeamPurchase = false;
			if (isset($teamID) && strlen($teamID) > 0)
				$isTeamPurchase = true;

			$stripeCharge = NULL;

			try
			{
//				error_log("amount: $authoritativePriceInCents, currency: usd, customer: $stripeCustomerID, description: $chargeDescription");

				$stripeCharge = Stripe_Charge::create(array(
															"amount" => $authoritativePriceInCents,
															"currency" => "usd",
															"customer" => $stripeCustomerID,
															"description" => $chargeDescription
															));
			}
			catch (Stripe_Error $e)
			{
				return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::makeStripeCharge(), Stripe_Charge::create()");
			}
			catch (Exception $e)
			{
				return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::makeStripeCharge(), Stripe_Charge::create()");
			}

			// SUCCESS!  Notify the user via email of the purchase if it's not a gift code purchase. Notification of gift code purchase
            // is handled in HandleGiftCodeMethods.php
            if(!$isGiftCodePurchase)
            {
                try
                {
                    // Catch all exceptions here because if anything fails, we want
                    // to make sure that we continue on and return success.
                    $username = TDOUser::usernameForUserId($userID);
                    $displayName = TDOUser::displayNameForUserId($userID);
                    $purchaseDate = time();
                    $cardType = NULL;
                    $last4 = NULL;
                    $cardOwnerName = NULL;
                    if (isset($stripeCharge['card']))
                    {
                        $stripeCard = $stripeCharge['card'];

                        if (isset($stripeCard['type']))
                            $cardType = $stripeCard['type'];

                        if (isset($stripeCard['last4']))
                            $last4 = $stripeCard['last4'];

    //					if (isset($stripeCard['name']))
    //						$cardOwnerName = $stripeCard['name'];
                    }

					$unitPrice = $unitPriceInCents / 100;
					$unitCombinedPrice = $unitCombinedPriceInCents / 100;
					$discountAmount = $discountInCents / 100;
					$teamCreditsDiscountAmount = 0;
					if ($teamCreditsPriceDiscountInCents > 0)
						$teamCreditsDiscountAmount = $teamCreditsPriceDiscountInCents / 100;
					$subtotalAmount = $subtotalInCents / 100;
                    $purchaseAmount = $authoritativePriceInCents / 100;


    // bht - decided that we don't need to send the card owner name
    //                 TDOMailer::sendPremierAccountPurchaseReceipt($username, $displayName, $purchaseDate, $cardType, $last4, $cardOwnerName, $subscriptionType, $purchaseAmount, $newExpirationDate);
					if ($isTeamPurchase)
					{
						// Send a completely full purchase receipt for a team account
						TDOMailer::sendTeamPurchaseReceipt($username, $displayName, $teamID, $purchaseDate, $cardType, $last4, $subscriptionType, $unitPrice, $unitCombinedPrice, $discountPercentage, $discountAmount, $teamCreditMonths, $teamCreditsDiscountAmount, $subtotalAmount, $purchaseAmount, $newExpirationDate, $numOfSubscriptions);
					}
					else
					{
						TDOMailer::sendPremierAccountPurchaseReceipt($username, $displayName, $purchaseDate, $cardType, $last4, $subscriptionType, $purchaseAmount, $newExpirationDate);
					}

                }
                catch (Exception $e)
                {
                    error_log("TDOSubscription::makeStripeCharge (User: $userID) unable to send notification email about a successful purchase.");
                }
            }

            //In case of a gift code purchase, we don't save off the stripe customer. Delete the stripe customer now, as we do
            //when the user manually downgrades to a free account
            if($isGiftCodePurchase && $isNewCard)
            {
                try
                {
                    $stripeCustomer->delete();
                }
                //If we hit an error here, we should still return success from this method since the purchase went through. Just log the error.
                catch (Stripe_Error $e)
                {
                    $body = $e->getJsonBody();
                    $err = $body['error'];
                    // TODO: SEND AN EMAIL TO admin@appigo.com or some other email address that will be watched and will need to possibly respond to a critical error!
                    error_log("TDOSubscription::makeStripeCharge received an Error calling Stripe_Customer::delete() for userid ($userID) when attempting to update their credit card information: " . $err);
                }
                catch (Exception $e)
                {
                    // TODO: SEND AN EMAIL TO admin@appigo.com or some other email address that will be watched and will need to possibly respond to a critical error!
                    error_log("TDOSubscription::makeStripeCharge received an Exception calling Stripe_Customer::delete() for userid ($userID) when attempting to update their credit card information: " . $e->getMessage());
                }
            }

			return $stripeCharge;
		}

		public static function makeStripeChargeForTeamChange($userID, $teamID, $stripeToken, $last4, $chargeDescription, $billingFrequency, $newExpirationDateString, $numOfSubscriptions,
															 $subtotalInCents, $bulkDiscountInCents, $teamCreditMonths, $teamCreditsPriceDiscountInCents, $discountPercentage, $accountCreditInCents, $totalChargeInCents)
		{
			// Parameters:
			//
			//		$userID				(required)
			//			This is a Todo Cloud userid, the object that
			//			will have the credit card associated with it.
			//		teamID				(required if team purchase)
			//			This is the Todo Cloud teamid which is what this charge
			//			will be attributed to.
			//		stripeToken			(required if last4 is not specified)
			//			Either stripeToken or last4 must be specified but not
			//			both.
			//		last4				(required if stripeToken is not specified)
			//			Either stripeToken or last4 must be specified but not
			//			both.
			//		chargeDescription			(required)
			//			The description that will show up to the end user as the
			//			reason for the charge.
			//		billingFrequency			(required)
			//			One of SUBSCRIPTION_TYPE_MONTH | SUBSCRIPTION_TYPE_YEAR
			//			so the next billing cycle date can be shown to the purchaser.
			//		newExpirationDateString		(required)
			//			Shown to the user on the purchase receipt
			//		numOfSubscriptions			(required to be > 0)
			//			If this is a team purchase, this will specify how many
			//			subscriptions are purchased.
			//		subtotalInCents
			//			Price shown to the user before any discounts.
			//		bulkDiscountInCents
			//			required
			//		teamCreditMonths
			//			optional
			//		teamCreditsPriceDiscountInCents
			//			optional
			//		discountPercentage
			//			required, can be 0
			//		accountCreditInCents
			//			required, can be 0
			//		totalChargeInCents			(required)
			//			This is what's actually used to make the Stripe charge.
			//			All the other amounts in the parameters are for display
			//			to the user only.

			if (!isset($userID, $teamID, $chargeDescription, $billingFrequency, $newExpirationDateString, $numOfSubscriptions, $subtotalInCents, $bulkDiscountInCents, $discountPercentage, $accountCreditInCents, $totalChargeInCents))
			{
				error_log("TDOSubscription::makeStripeChargeForTeamChange() called with a missing parameter.");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER,
							 );
			}

			if (!isset($stripeToken) && !isset($last4)) // stripeToken OR last4 required
			{
				error_log("TDOSubscription::makeStripeChargeForTeamChange() called and missing a required parameter: stripeToken or last4 required");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER,
							 );
			}

			// The user can be one of two types of Stripe customers:
			//		1. They've NEVER paid us before
			//		2. They've paid us in the past
			//			2a. Their previous card is still valid and they chose to continue paying with that
			//			2b. They provided new card information
			//
			//			UNNATURAL PIG POSITION (IMPOSSIBLE CONDITION):
			//			2c. The user's previous card is expired.  We don't give the
			//				user this option when making a payment.  If the previous
			//				card has expired, we don't return that as a viable
			//				option.

			$username = TDOUser::usernameForUserId($userID);

			$stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);

			$stripeCustomer = NULL;
            $isNewCard = false;

			if ($stripeCustomerID)
			{
				// The user has previously paid us using Stripe, so we either need
				// to attempt a new payment OR update their card information and
				// THEN make a new payment.

				// Attempt to get the existing customer from Stripe
				try
				{
					$stripeCustomer = Stripe_Customer::retrieve($stripeCustomerID);
				}
				catch (Stripe_Error $e)
				{
					return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::makeStripeChargeForTeamChange(), Stripe_Customer::retrieve()");
				}
				catch (Exception $e)
				{
					return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::makeStripeChargeForTeamChange(), Stripe_Customer::retrieve()");
				}

				if ($last4)
				{
					// Just for kicks (even though it's next to impossible to get
					// into this situation since we just retrieved the user's info
					// before presenting them with purchase info), make sure that
					// the $last4 digits match up with what's in the customer's
					// Stripe account.
					$activeCard = TDOSubscription::getActiveStripeCard($stripeCustomer);
					if (!empty($activeCard))
					{
						if ( (empty($activeCard['last4'])) || ($last4 != $activeCard['last4']) )
						{
							error_log("TDOSubscription::makeStripeChargeForTeamChange (User: $userID) determined that the specified last4 ($last4) digits of the credit card aren't stored by Stripe or do not match the records on file with Stripe.");
							return array(
										 "errcode" => SUBSCRIPTION_ERROR_CODE_STRIPE_LAST4_MISMATCH,
										 "errdesc" => SUBSCRIPTION_ERROR_DESC_STRIPE_LAST4_MISMATCH,
										 );
						}
					}
				}
				else
				{
					// The user has provided NEW card information (we have a new
					// one-time stripeToken).  We need to update their card
					// information on their Stripe Customer ID.
					$stripeCustomer->card = $stripeToken;

					// Take the opportunity to update the email address on the
					// stripe account just in case they differ right now.  It may
					// make things easier debugging/searching our Stripe account to
					// track down any problems later on.
					if ($username)
					{
						if ( (empty($stripeCustomer->email)) || ($username != $stripeCustomer->email) )
							$stripeCustomer->email = $username;
					}

					try
					{
						$stripeCustomer->save();
					}
					catch (Stripe_Error $e)
					{
						return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::makeStripeChargeForTeamChange(), Stripe_Customer::save()");
					}
					catch (Exception $e)
					{
						return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::makeStripeChargeForTeamChange(), Stripe_Customer::save()");
					}
				}
			}
			else
			{
                $isNewCard = true;
				// This is a NEW Stripe customer.  Create a new Stripe Customer
				// object for this user.
				$parameters = array("description" => $userID,
									"card" => $stripeToken);

				if ($username)
					$parameters["email"] = $username;

				//				error_log("STRIPE_CUSTOMER CREATE PARAMS: " . var_export($parameters, true));

				// Create the new Stripe Customer now
				try
				{
					$stripeCustomer = Stripe_Customer::create($parameters);
				}
				catch (Stripe_Error $e)
				{
					return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::makeStripeChargeForTeamChange(), Stripe Error, Stripe_Customer::create()");
				}
				catch (Exception $e)
				{
					return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::makeStripeChargeForTeamChange(), General Exception, Stripe_Customer::create()");
				}

				$stripeCustomerID = $stripeCustomer->id;

				// Store the Stripe customer
				if (TDOSubscription::storeStripeCustomer($userID, $stripeCustomerID) == false)
				{
					error_log("TDOSubscription::makeStripeChargeForTeamChange() (User: $userID) failed calling TDOSubscription::storeStripeCustomer().");
					return array(
								 "errcode" => SUBSCRIPTION_ERROR_CODE_SAVE_PAYMENT_CUSTOMER,
								 "errdesc" => SUBSCRIPTION_ERROR_DESC_SAVE_PAYMENT_CUSTOMER,
								 );
				}
			}

			// If we make it this far, we have a valid $stripeCustomerID we can use
			// to make a charge!

			$stripeCharge = NULL;

			try
			{
				//				error_log("amount: $authoritativePriceInCents, currency: usd, customer: $stripeCustomerID, description: $chargeDescription");

				$stripeCharge = Stripe_Charge::create(array(
															"amount" => $totalChargeInCents,
															"currency" => "usd",
															"customer" => $stripeCustomerID,
															"description" => $chargeDescription
															));
			}
			catch (Stripe_Error $e)
			{
				return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::makeStripeChargeForTeamChange(), Stripe_Charge::create()");
			}
			catch (Exception $e)
			{
				return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::makeStripeChargeForTeamChange(), Stripe_Charge::create()");
			}

			// SUCCESS!  Notify the user via email of the purchase.
			try
			{
				// Catch all exceptions here because if anything fails, we want
				// to make sure that we continue on and return success.
				$username = TDOUser::usernameForUserId($userID);
				$displayName = TDOUser::displayNameForUserId($userID);
				$purchaseDate = time();
				$cardType = NULL;
				$last4 = NULL;
				$cardOwnerName = NULL;
				if (isset($stripeCharge['card']))
				{
					$stripeCard = $stripeCharge['card'];

					if (isset($stripeCard['type']))
						$cardType = $stripeCard['type'];

					if (isset($stripeCard['last4']))
						$last4 = $stripeCard['last4'];

					//					if (isset($stripeCard['name']))
					//						$cardOwnerName = $stripeCard['name'];
				}

				$subtotal = $subtotalInCents / 100;
				$bulkDiscount = $bulkDiscountInCents / 100;
				$accountCredit = $accountCreditInCents / 100;
				$totalCharge = $totalChargeInCents / 100;

				// Send a completely full purchase receipt for a team account
				TDOMailer::sendTeamChangePurchaseReceipt($username, $displayName, $teamID, $purchaseDate, $cardType, $last4, $billingFrequency,
														 $newExpirationDateString, $numOfSubscriptions, $subtotal, $bulkDiscount, $discountPercentage,
														 $teamCreditMonths, $teamCreditsPriceDiscountInCents,
														 $accountCredit, $totalCharge);
			}
			catch (Exception $e)
			{
				error_log("TDOSubscription::makeStripeChargeForTeamChange (User: $userID) unable to send notification email about a successful purchase.");
			}

			return $stripeCharge;
		}

        public static function saveStripeInfoForUser($userID, $stripeToken)
        {
			if (empty($userID))
			{
				error_log("TDOSubscription::saveStripeInfoForUser() called with a missing parameter.");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER,
							 );
			}

			if (!isset($stripeToken)) // stripeToken required
			{
				error_log("TDOSubscription::saveStripeInfoForUser() called and missing a required parameter: stripeToken or last4 required");
				return array(
							 "errcode" => SUBSCRIPTION_ERROR_CODE_MISSING_PARAMETER,
							 "errdesc" => SUBSCRIPTION_ERROR_DESC_MISSING_PARAMETER,
							 );
			}
			$username = TDOUser::usernameForUserId($userID);

			$stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);
			$stripeCustomer = NULL;

			if ($stripeCustomerID)
			{
				// The user has previously paid us using Stripe, so we need to update their card information
				// Attempt to get the existing customer from Stripe
				try
				{
					$stripeCustomer = Stripe_Customer::retrieve($stripeCustomerID);
				}
				catch (Stripe_Error $e)
				{
					return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::saveStripeInfoForUser(), Stripe_Customer::retrieve()");
				}
				catch (Exception $e)
				{
					return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::saveStripeInfoForUser(), Stripe_Customer::retrieve()");
				}

                // The user has provided NEW card information (we have a new
                // one-time stripeToken).  We need to update their card
                // information on their Stripe Customer ID.
                $stripeCustomer->card = $stripeToken;

                // Take the opportunity to update the email address on the
                // stripe account just in case they differ right now.  It may
                // make things easier debugging/searching our Stripe account to
                // track down any problems later on.
                if ($username)
                {
                    if ( (empty($stripeCustomer->email)) || ($username != $stripeCustomer->email) )
                        $stripeCustomer->email = $username;
                }

                try
                {
                    $stripeCustomer->save();
                }
                catch (Stripe_Error $e)
                {
                    return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::saveStripeInfoForUser(), Stripe_Customer::save()");
                }
                catch (Exception $e)
                {
                    return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::saveStripeInfoForUser(), Stripe_Customer::save()");
                }

			}
			else
			{
				// This is a NEW Stripe customer.  Create a new Stripe Customer
				// object for this user.
				$parameters = array("description" => $userID,
									"card" => $stripeToken);

				if ($username)
					$parameters["email"] = $username;

				// Create the new Stripe Customer now
				try
				{
					$stripeCustomer = Stripe_Customer::create($parameters);
				}
				catch (Stripe_Error $e)
				{
					return TDOSubscription::errorArrayForStripeException($e, $userID, "TDOSubscription::saveStripeInfoForUser(), Stripe_Customer::create()");
				}
				catch (Exception $e)
				{
					return TDOSubscription::errorArrayForException($e, $userID, "TDOSubscription::saveStripeInfoForUser(), Stripe_Customer::create()");
				}

				$stripeCustomerID = $stripeCustomer->id;

                //Store the stripe customer id
                if (TDOSubscription::storeStripeCustomer($userID, $stripeCustomerID) == false)
                {
                    error_log("TDOSubscription::saveStripeInfoForUser (User: $userID) failed calling TDOSubscription::storeStripeCustomer().");
                    return array(
                                 "errcode" => SUBSCRIPTION_ERROR_CODE_SAVE_PAYMENT_CUSTOMER,
                                 "errdesc" => SUBSCRIPTION_ERROR_DESC_SAVE_PAYMENT_CUSTOMER,
                                 );
                }

			}
			return array("stripeid" => $stripeCustomerID);
        }


		//
		// Autorenew Helper Methods
		//

		public static function getAutorenewableSubscriptionsWithinDate($expirationDate)
		{
			if (empty($expirationDate))
				return false;

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::getAutorenewableSubscriptionsWithinDate() failed to get dblink");
				return false;
			}

            $expirationDate = intval($expirationDate);

			$subscriptionIDs = array();

			//
			// STRIPE Users
			//
			$sql = "SELECT tdo_subscriptions.subscriptionid FROM tdo_subscriptions JOIN tdo_stripe_user_info ON tdo_subscriptions.userid=tdo_stripe_user_info.userid WHERE tdo_subscriptions.subscriptionid NOT IN (SELECT tdo_autorenew_history.subscriptionid FROM tdo_autorenew_history) AND expiration_date <= $expirationDate AND (tdo_subscriptions.teamid IS NULL OR tdo_subscriptions.teamid='')";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::getAutorenewableSubscriptionsWithinDate() failed to make the SQL call" . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			while ($row = mysql_fetch_array($result))
			{
				$subscriptionID = $row['subscriptionid'];
                if(in_array($subscriptionID, $subscriptionIDs) == false)
                    $subscriptionIDs[] = $subscriptionID;
			}

			//
			// Apple IAP Auto-renewal Subscriptions
			//
            $sql = "SELECT tdo_subscriptions.subscriptionid FROM tdo_subscriptions JOIN tdo_iap_autorenew_receipts ON tdo_subscriptions.userid=tdo_iap_autorenew_receipts.userid WHERE tdo_subscriptions.subscriptionid NOT IN (SELECT tdo_autorenew_history.subscriptionid FROM tdo_autorenew_history) AND tdo_subscriptions.expiration_date <= $expirationDate AND autorenewal_canceled=0";
            $result = mysql_query($sql, $link);
            if(!$result)
            {
                error_log("TDOSubscription::getAutorenewableSubscriptionsWithinDate() failed to make the SQL call ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }

            while($row = mysql_fetch_array($result))
            {
                $subscriptionID = $row['subscriptionid'];
                if(in_array($subscriptionID, $subscriptionIDs) == false)
                    $subscriptionIDs[] = $subscriptionID;
            }

			//
            // Google Play autorenewable subscriptions
			//
            $sql = "SELECT tdo_subscriptions.subscriptionid FROM tdo_subscriptions JOIN tdo_googleplay_autorenew_tokens ON tdo_subscriptions.userid=tdo_googleplay_autorenew_tokens.userid WHERE tdo_subscriptions.subscriptionid NOT IN (SELECT tdo_autorenew_history.subscriptionid FROM tdo_autorenew_history) AND tdo_subscriptions.expiration_date <= $expirationDate AND autorenewal_canceled=0";
            $result = mysql_query($sql, $link);
            if(!$result)
            {
                error_log("TDOSubscription::getAutorenewableSubscriptionsWithinDate() failed to make the SQL call ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }

            while($row = mysql_fetch_array($result))
            {
                $subscriptionID = $row['subscriptionid'];
                if(in_array($subscriptionID, $subscriptionIDs) == false)
                    $subscriptionIDs[] = $subscriptionID;
            }

			TDOUtil::closeDBLink($link);

			return $subscriptionIDs;
		}

		public static function addSubscriptionsForAutorenewal($subscriptionIDs)
		{
			if(empty($subscriptionIDs))
			{
				error_log("TDOSubscription::addSubscriptionsForAutorenewal() failed because subscriptionIDs is empty");
				return false;
			}

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::addSubscriptionsForAutorenewal() failed to get dblink");
				return false;
			}

			if (!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOSubscription::addSubscriptionsForAutorenewal() couldn't start transaction: " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

            foreach($subscriptionIDs as $subscriptionID)
			{
				$subscriptionID = mysql_real_escape_string($subscriptionID, $link);

				$sql = "INSERT INTO tdo_autorenew_history (subscriptionid) VALUES ('$subscriptionID')";
				if (!mysql_query($sql, $link))
				{
					error_log("TDOSubscription::addSubscriptionsForAutorenewal() unable to add subscriptions for autorenewal: " . mysql_error());
					mysql_query("ROLLBACK", $link);
					TDOUtil::closeDBLink($link);
					return false;
				}
			}

			if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOSubscription::addSubscriptionsForAutorenewal() couldn't commit transaction after adding subscriptions:" . mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}

			TDOUtil::closeDBLink($link);
			return true;
		}


		public static function getFailedAutorenewableSubscriptions()
		{
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::getFailedAutorenewableSubscriptions() failed to get dblink");
				return false;
			}

			$subscriptionIDs = array();

			// Pick up all subscriptions that have a renewal_attempt between 1 and 2
			$sql = "SELECT subscriptionid FROM tdo_autorenew_history WHERE renewal_attempts > 0 AND renewal_attempts < " . SUBSCRIPTION_RETRY_MAX_ATTEMPTS;
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::getFailedAutorenewableSubscriptions() failed to make the SQL call ($sql): " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			while ($row = mysql_fetch_array($result))
			{
				$subscriptionID = $row['subscriptionid'];
				$subscriptionIDs[] = $subscriptionID;
			}

			// Pick up all subscriptions that have a renewal_attempt of 3 or
			// more that are IAP-based subscriptions. We have no control over
			// the IAP renewal system, so we should never really give up. The
			// outcome of retrying an IAP-based renewal will be one of:
			//
			// 1. Fail because of network/etc.
			// 2. Succeed (the subscription will be removed from tdo_autorenew_history)
			// 3. Succeed because the subscription was cancelled (will be removed from tdo_autorenew_history)

			// Apple IAP
			$sql = "SELECT tdo_subscriptions.subscriptionid from tdo_subscriptions JOIN tdo_iap_autorenew_receipts ON tdo_subscriptions.userid=tdo_iap_autorenew_receipts.userid WHERE tdo_subscriptions.subscriptionid IN (SELECT tdo_autorenew_history.subscriptionid FROM tdo_autorenew_history WHERE renewal_attempts >= " . SUBSCRIPTION_RETRY_MAX_ATTEMPTS . ") AND autorenewal_canceled=0";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::getFailedAutorenewableSubscriptions() failed to make the SQL call ($sql): " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			while ($row = mysql_fetch_array($result))
			{
				$subscriptionID = $row['subscriptionid'];
				$subscriptionIDs[] = $subscriptionID;
			}

			// Google Play IAP
			$sql = "SELECT tdo_subscriptions.subscriptionid from tdo_subscriptions JOIN tdo_googleplay_autorenew_tokens ON tdo_subscriptions.userid=tdo_googleplay_autorenew_tokens.userid WHERE tdo_subscriptions.subscriptionid IN (SELECT tdo_autorenew_history.subscriptionid FROM tdo_autorenew_history WHERE renewal_attempts >= " . SUBSCRIPTION_RETRY_MAX_ATTEMPTS . ") AND autorenewal_canceled=0";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::getFailedAutorenewableSubscriptions() failed to make the SQL call ($sql): " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			while ($row = mysql_fetch_array($result))
			{
				$subscriptionID = $row['subscriptionid'];
				$subscriptionIDs[] = $subscriptionID;
			}

			TDOUtil::closeDBLink($link);

			return $subscriptionIDs;
		}


		public static function getNewAutorenewableSubscriptions()
		{
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::getNewAutorenewableSubscriptions() failed to get dblink");
				return false;
			}

			$subscriptionIDs = array();
			$sql = "SELECT subscriptionid FROM tdo_autorenew_history WHERE renewal_attempts = 0";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::getNewAutorenewableSubscriptions() failed to make the SQL call" . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			while ($row = mysql_fetch_array($result))
			{
				$subscriptionID = $row['subscriptionid'];
				$subscriptionIDs[] = $subscriptionID;
			}

			TDOUtil::closeDBLink($link);

			return $subscriptionIDs;
		}


		public static function updateFailureCountsForSubscriptionID($subscriptionID, $failureReason = NULL)
		{
			if(empty($subscriptionID))
			{
				error_log("TDOSubscription::updateFailureCountsForSubscriptionID() failed because subscriptionID is empty");
				return false;
			}

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSubscription::updateFailureCountsForSubscriptionID() failed to get dblink");
				return false;
			}

			$subscriptionID = mysql_real_escape_string($subscriptionID, $link);

            //If the user has reached SUBSCRIPTION_RETRY_MAX_ATTEMPTS, we need to email support so
            //their account can be taken care of. Otherwise, they will never be able to auto-renew
            //again, even if they switch payment systems.
            $sql = "SELECT renewal_attempts FROM tdo_autorenew_history WHERE subscriptionid='$subscriptionID'";
            $response = mysql_query($sql, $link);
            $renewalAttempts = 0;
            if($response)
            {
                if($row = mysql_fetch_array($response))
                {
                    if(isset($row['renewal_attempts']))
                    {
                        $renewalAttempts = intval($row['renewal_attempts']);
                    }
                }
            }
            else
            {
                error_log("TDOSubscription::updateFailureCountsForSubscriptionID failed with error ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }

            $newRenewalAttempts = $renewalAttempts + 1;
            if($newRenewalAttempts >= SUBSCRIPTION_RETRY_MAX_ATTEMPTS)
            {
                if(TDOMailer::sendSubscriptionRenewalMaxRetryAttemptsReachedNotification($subscriptionID) == false)
                {
                    error_log("TDOSubscription::updateFailureCountsForSubscriptionID failed to send email to support.");
                    TDOUtil::closeDBLink($link);
                    return false;
                }
            }


			$nowTimestamp = time();
			$sql = "UPDATE tdo_autorenew_history SET renewal_attempts = $newRenewalAttempts, attempted_time=$nowTimestamp";
			if (!empty($failureReason))
			{
				$failureReason = mysql_real_escape_string($failureReason, $link);
				$sql .= ", failure_reason='$failureReason'";
			}
			$sql .= " WHERE subscriptionid='$subscriptionID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOSubscription::updateFailureCountsForSubscriptionID() unable to update failed subscription autorenewal: " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}


			TDOUtil::closeDBLink($link);
			return true;
		}


		public static function removeSubscriptionFromAutorenewQueue($subscriptionID)
		{
            if(!isset($subscriptionID))
                return false;

            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSubscription::removeSubscriptionFromAutorenewQueue() unable to get link");
                return false;
            }

            $subscriptionID = mysql_real_escape_string($subscriptionID, $link);

			$sql = "DELETE FROM tdo_autorenew_history WHERE subscriptionid='$subscriptionID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOSubscription::removeSubscriptionFromAutorenewQueue() unable to delete subscription ($subscriptionID): " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			// SUCCESS!
            TDOUtil::closeDBLink($link);
            return true;
		}

		public static function processAutorenewalForSubscription($subscriptionID)
		{
			if (empty($subscriptionID))
			{
				error_log("TDOSubscription::processAutorenewalForSubscription() called with no subscriptionID");
				return false;
			}

			$subscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
			if (!$subscription)
			{
				error_log("TDOSubscription::processAutorenewalForSubscription() unable to locate a subscription for ID: $subscriptionID");
				TDOSubscription::updateFailureCountsForSubscriptionID($subscriptionID, "Unable to locate a subscription for specified subscription ID");
				return false;
			}

			$userID = $subscription->getUserID();

			// See if we can determine up-front whether this is a Google
			// Play, iTunes IAP, or Stripe Renewal without causing more work on
			// the server than is necessary.

            //See if the user has an IAP autorenewal receipt we should be checking.
			$iapJustCancelled = false;
            $iapResult = TDOInAppPurchase::processIAPAutorenewalForUser($userID, $subscription);
            if($iapResult['iap_autorenewing_account'] == true)
            {
				// If the IAP auto-renewal failed, we need to check to see if
				// the user is a member of an active team. Adjust the team
				// member's account according to the team's account.

				$subscriptionRenewed = $iapResult['account_renewed'];
				if ($subscriptionRenewed)
					return true;

				$iapJustCancelled = true;
            }
            else
            {
                // See if the user has a Google Play autorenewal token we should refresh
                $gpResult = TDOInAppPurchase::processGooglePlayAutorenewalForUser($userID, $subscription);
                if($gpResult['gp_autorenewing_account'] == true)
                {
					$subscriptionRenewed = $gpResult['account_renewed'];
					if ($subscriptionRenewed)
						return true;

					$iapJustCancelled = true;
                }
                else
                {

                    $stripeCustomerID = TDOSubscription::getStripeCustomerID($userID);

                    if (!$stripeCustomerID)
                    {
                        error_log("TDOSubscription::processAutorenewalForSubscription() unable to retrieve stripe customer id information for subscription: $subscriptionID");
                        //If there's no stripe user info for this user, we shouldn't be trying to autorenew them
                        TDOSubscription::removeSubscriptionFromAutorenewQueue($subscriptionID);

                        return false;
                    }

                    // Verify that all of the conditions are correct for autorenewing
                    // the specified subscription with the Stripe information.  If all
                    // the stars align, make the charge and update everything!

                    $nowTimestamp = time();
                    $advanceExpireDate = $nowTimestamp + SUBSCRIPTION_RENEW_LEAD_TIME;
                    $expirationDate = $subscription->getExpirationDate();

                    if ($expirationDate > $advanceExpireDate)
                    {
                        error_log("TDOSubscription::processAutorenewalForSubscription() subscription must have already been updated.  Removing it from the autorenewal queue.");
                        TDOSubscription::removeSubscriptionFromAutorenewQueue($subscriptionID);
                        return true;
                    }

                    // We've determined that the subscription is valid for renewal, so
                    // now determine whether this is a yearly or monthly renewal.
                    $subscriptionType = $subscription->getSubscriptionType();
                    if ($subscriptionType == SUBSCRIPTION_TYPE_UNKNOWN)
                    {
                        error_log("TDOSubscription::processAutorenewalForSubscription() unable to determine what type of renewal (month/year) this is for subscription: $subscriptionID. NOTE: This could be a team administrator with a non-paid account and this notice is completely normal.");
                        TDOSubscription::updateFailureCountsForSubscriptionID($subscriptionID, "Unknown subscription type (month/year)");
                        return false;
                    }

                    $pricingTable = TDOSubscription::getPersonalSubscriptionPricingTable();
                    $priceToCharge = 0;
                    if ($subscriptionType == SUBSCRIPTION_TYPE_MONTH)
                    {
                        $priceToCharge = $pricingTable['month'];
                        $chargeDescription = _('Todo® Cloud Premium Account (1 month)');
                    }
                    else if ($subscriptionType == SUBSCRIPTION_TYPE_YEAR)
                    {
                        $priceToCharge = $pricingTable['year'];
                        $chargeDescription = _('Todo® Cloud Premium Account (1 year)');
                    }

                    if (!$priceToCharge)
                    {
                        error_log("TDOSubscription::processAutorenewalForSubscription() unable to determine price to charge for subscription: $subscriptionID");
                        TDOSubscription::updateFailureCountsForSubscriptionID($subscriptionID, "Could not determine the price to charge for the renewal");
                        return false;
                    }

                    $priceToChargeInCents = $priceToCharge * 100;

                    // Now figure out what the new expiration date is
                    $newExpirationDate = $subscription->getSubscriptionRenewalExpirationDateForType($subscriptionType);
                    if (empty($newExpirationDate))
                    {
                        error_log("TDOSubscription:processAutorenewalForSubscription failed to determine a new subscription expiration date");
                        TDOSubscription::updateFailureCountsForSubscriptionID($subscriptionID, "Error determining new expiration date for subscription ID: $subscriptionID");
                        return false;
                    }

                    // Get the last4 credit card numbers from Stripe.
                    $billingInfo = TDOSubscription::getSubscriptionBillingInfoForUser($userID);
                    if (!$billingInfo)
                    {
                        error_log("TDOSubscription::processAutorenewalForSubscription() unable to get previous billing information for subscription: $subscriptionID");
                        TDOSubscription::updateFailureCountsForSubscriptionID($subscriptionID, "Could not determine previous billing information (last4)");
                        return false;
                    }

                    $last4 = $billingInfo['last4'];

                    $stripeCharge = TDOSubscription::makeStripeCharge($userID,
																	  NULL, // NULL teamID
                                                                      NULL, // NULL Stripe Token because this should NOT be a brand new user
                                                                      $last4,
																	  $priceToChargeInCents, // unitPriceInCents
																	  $priceToChargeInCents, // unitCombinedPriceInCents
																	  $priceToChargeInCents, // subtotalInCents
																	  0, // discountPercentage
																	  0, // discountInCents
																	  0, // teamCreditMonths
																	  0, // teamCreditsPriceDiscountInCents
                                                                      $priceToChargeInCents,
                                                                      $chargeDescription,
                                                                      $subscriptionType,
                                                                      $newExpirationDate);

                    if (empty($stripeCharge) || isset($stripeCharge['errcode']))
                    {
                        error_log("TDOSubscription::processAutorenewalForSubscription failed when calling TDOSubscription::makeStripeCharge()");

                        // TODO: Mail the user with the failure reason

                        TDOSubscription::updateFailureCountsForSubscriptionID($subscriptionID, "Error calling makeStripeCharge() with errCode = " . $stripeCharge['errcode'] . ", errDesc = " . $stripeCharge['errDesc']);
                        return false;
                    }

                    if (TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationDate, $subscriptionType, SUBSCRIPTION_LEVEL_PAID) == false)
                    {
                        // CRITIAL PROBLEM - A personal subscription was paid for but not
                        // updated, so send a mail to support so they can make sure to
                        // fix this.
                        error_log("TDOSubscription::processAutorenewalForSubscription unable to extend subscription after payment for subscriptionID ($subscriptionID) and expiration date ($newExpirationDate)");
                        TDOMailer::sendSubscriptionUpdateErrorNotification($subscriptionID, $newExpirationDate);
                    }

                    $cardType = 'N/A';
                    if (isset($stripeCharge->card))
                    {
                        $card = $stripeCharge->card;
                        if (isset($card['type']))
                            $cardType = $card['type'];
                    }

                    // Keep a record of the charge!
                    TDOSubscription::logStripePayment($userID, NULL, 1, $stripeCustomerID, $stripeCharge->id, $cardType, $last4, $subscriptionType, $stripeCharge->amount, $nowTimestamp, $chargeDescription);
                    TDOSubscription::addOrUpdateUserPaymentSystemInfo($userID, PAYMENT_SYSTEM_TYPE_STRIPE, $stripeCustomerID);

                    error_log("TDOSubscription::processAutorenewalForSubscription() successfully processed an autorenewal for subscription ID: $subscriptionID");

                    TDOSubscription::removeSubscriptionFromAutorenewQueue($subscriptionID);
                    return true;
                }
            }

			if ($iapJustCancelled)
			{
				// Check to see if this user is a member of a team. If they are
				// and the team's subscription is still valid, update the user's
				// subscription date with the team's expiration date.
				$teamAccount = TDOTeamAccount::getTeamForTeamMember($userID);
				if (!empty($teamAccount))
				{
					$now = time();
					$expirationDate = $teamAccount->getExpirationDate();
					if ($expirationDate > $now)
					{
						$teamID = $teamAccount->getTeamID();
						$subscriptionType = $teamAccount->getBillingFrequency();
						$subscriptionLevel = SUBSCRIPTION_LEVEL_TEAM;
						$subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userID);
						$subscriptionUpdated = TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $expirationDate, $subscriptionType, $subscriptionLevel, $teamID);

						if ($subscriptionUpdated)
						{
							error_log("TDOSubscription::processAutorenewalForSubscription() updated a user's subscription ($userID) to the team's expiration date after a recent IAP auto-renewal cancellation.");
							return true;
						}
					}
				}
			}

			return false;
		}

		//
		// Purchase History Methods
		//

		// Returns false on error or an array of objects with the following keys:
		//
		//		timestamp
		//			A UNIX timestamp
		//		subscriptionType
		//			One of SUBSCRIPTION_TYPE_MONTH, SUBSCRIPTION_TYPE_YEAR
		//		description
		//			Stuff here
		public static function getPurchaseHistoryForUserID($userID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::getPurchaseHistoryForUserID() failed because userID is empty");
				return false;
			}

			$iapPurchases = TDOInAppPurchase::getIAPPurchaseHistoryForUserID($userID);
			$googlePlayPurchases = TDOInAppPurchase::getGooglePlayPurchaseHistoryForUserID($userID);
			$stripePurchases = TDOSubscription::getStripePurchaseHistoryForUserID($userID);
			$promoCodeHistory = TDOSubscription::getPromoCodeHistoryForUserID($userID);
            $giftCodeHistory = TDOSubscription::getGiftCodeHistoryForUserId($userID);
			$referralHistory = TDOReferral::getReferralCreditHistoryForUserId($userID);

			if (empty($iapPurchases))
				$iapPurchases = array();
			if (empty($googlePlayPurchases))
				$googlePlayPurchases = array();
			if (empty($stripePurchases))
				$stripePurchases = array();
			if (empty($promoCodeHistory))
				$promoCodeHistory = array();
            if(empty($giftCodeHistory))
                $giftCodeHistory = array();
            if(empty($referralHistory))
                $referralHistory = array();

			$purchases = array_merge($iapPurchases, $googlePlayPurchases, $stripePurchases, $promoCodeHistory, $giftCodeHistory, $referralHistory);

			// Sort by timestamp.  First build an array of just the timestamps
			// that will be used.
			$timestamps = array();
			foreach($purchases as $purchase)
			{
				$timestamps[] = $purchase['timestamp'];
			}

			array_multisort($timestamps, SORT_DESC, $purchases);

			return $purchases;
		}

		// Returns false on error or an array of objects with the following keys:
		//
		//		timestamp
		//			A UNIX timestamp
		//		subscriptionType
		//			One of SUBSCRIPTION_TYPE_MONTH, SUBSCRIPTION_TYPE_YEAR
		//		description
		//			Stuff here
		private static function getStripePurchaseHistoryForUserID($userID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::getStripePurchaseHistoryForUserID() failed because userID is empty");
				return false;
			}

            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSubscription::getStripePurchaseHistoryForUserID() unable to get link");
                return false;
            }

			$userID = mysql_real_escape_string($userID, $link);

			$purchases = array();
			$sql = "SELECT timestamp,type,amount,stripe_chargeid FROM tdo_stripe_payment_history WHERE userid='$userID' AND (teamid IS NULL OR teamid='') ORDER BY timestamp DESC";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::getStripePurchaseHistoryForUserID() failed to make the SQL call" . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			while ($row = mysql_fetch_array($result))
			{
				$timestamp = $row['timestamp'];
				$subscriptionType = $row['type'];
				$amount = $row['amount'] / 100;
				$stripeChargeID = $row['stripe_chargeid'];

				$subscriptionTypeString = "month";
				if ($subscriptionType == SUBSCRIPTION_TYPE_UNKNOWN)
					continue;
				else if ($subscriptionType == SUBSCRIPTION_TYPE_YEAR)
					$subscriptionTypeString = "year";

				$description = sprintf(_('Payment of $%s USD'), $amount);

				$purchase = array(
								  "timestamp" => $timestamp,
								  "subscriptionType" => $subscriptionTypeString,
								  "description" => $description,
									"stripeChargeID" => $stripeChargeID
								  );

				$purchases[] = $purchase;
			}

			TDOUtil::closeDBLink($link);
			return $purchases;
		}


		public static function getStripePurchaseInfoForUserID($userID, $purchaseTimestamp)
		{
			if (empty($userID))
			{
				error_log("TDOSubscription::getStripePurchaseForUserID() failed because userID is empty");
				return false;
			}

            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSubscription::getStripePurchaseForUserID() unable to get link");
                return false;
            }

			$userID = mysql_real_escape_string($userID, $link);
			$sql = "SELECT card_type,last4,type,amount FROM tdo_stripe_payment_history WHERE userid='$userID' AND timestamp=$purchaseTimestamp";
			$result = mysql_query($sql, $link);
			if ($result)
			{
				$row = mysql_fetch_array($result);

				$amountInCents = $row['amount'];
				$amountInUSD = $amountInCents / 100;

				$purchaseInfo = array(
									  "card_type" => $row['card_type'],
									  "last4" => $row['last4'],
									  "type" => $row['type'],
									  "amount" => $amountInUSD
									  );

				TDOUtil::closeDBLink($link);
				return $purchaseInfo;
			}

			TDOUtil::closeDBLink($link);
			return false;
		}


		public static function getStripePurchaseCountInRange($type, $startDate, $endDate)
		{
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				return false;
			}

			$sql = "SELECT COUNT(*) FROM tdo_stripe_payment_history WHERE type=$type AND timestamp >= $startDate AND timestamp <= $endDate AND (teamid IS NULL OR teamid='')";
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
				error_log("TDOSubscription::getPurchaseCountForUserInRange($startDate, $endDate): Unable to get user count");
			}

			TDOUtil::closeDBLink($link);
			return false;
		}

        private static function getGiftCodeHistoryForUserId($userID)
        {
            if(empty($userID))
            {
                error_log("TDOSubscription::getGiftCodeHistoryForUserId failed because userID is empty");
                return false;
            }

            $giftCodes = TDOGiftCode::giftCodesConsumedByUser($userID);
            if($giftCodes === false)
            {
                error_log("TDOSubscription::getGiftCodeHistoryForUserId failed to get gift codes consumed by user");
                return false;
            }

            $history = array();
            foreach($giftCodes as $giftCode)
            {
                $timestamp = $giftCode->consumptionDate();
                $subscriptionTypeString = "gift";
                $subscriptionDuration = $giftCode->subscriptionDuration();

                if($subscriptionDuration == 12)
                    $description = "1 year";
                else
                    $description = $subscriptionDuration . " month(s)";

                $purchase = array(
                                    "timestamp" => $timestamp,
                                    "subscriptionType" => $subscriptionTypeString,
                                    "description" => $description
                );
                $history[] = $purchase;
            }

            return $history;
        }


		private static function getPromoCodeHistoryForUserID($userID)
		{
			if(empty($userID))
			{
				error_log("TDOSubscription::getPromoCodeHistoryForUserID() failed because userID is empty");
				return false;
			}

            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSubscription::getPromoCodeHistoryForUserID() unable to get link");
                return false;
            }

			$history = array();
			$sql = "select timestamp,subscription_duration FROM tdo_promo_code_history WHERE userid='$userID' ORDER BY timestamp DESC";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::getPromoCodeHistoryForUserID() failed to make the SQL call" . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			while ($row = mysql_fetch_array($result))
			{
				$timestamp = $row['timestamp'];
				$subscriptionDuration = $row['subscription_duration'];
				$subscriptionTypeString = "promo";

				$description = $subscriptionDuration . " month(s) free";

				$purchase = array(
								  "timestamp" => $timestamp,
								  "subscriptionType" => $subscriptionTypeString,
								  "description" => $description
								  );

				$history[] = $purchase;
			}

			TDOUtil::closeDBLink($link);
			return $history;
		}


		//
		// Class Getters/Setters
		//


		public function getSubscriptionID()
		{
			return $this->_subscriptionID;
		}
		public function setSubscriptionID($val)
		{
			$this->_subscriptionID = $val;
		}

		public function getUserID()
		{
			return $this->_userID;
		}
		public function setUserID($val)
		{
			$this->_userID = $val;
		}

		public function getSubscriptionType()
		{
			return $this->_subscriptionType;
		}
		public function setSubscriptionType($val)
		{
			$this->_subscriptionType = $val;
		}

        public function getExpirationDate()
		{
			return $this->_expirationDate;
		}
		public function setExpirationDate($val)
		{
			$this->_expirationDate = $val;
		}

		public function getSubscriptionLevel()
		{
			return $this->_level;
		}
		public function setSubscriptionLevel($val)
		{
			$this->_level = $val;
		}

		public function getTeamID()
		{
			return $this->_teamid;
		}
		public function setTeamID($val)
		{
			$this->_teamid = $val;
		}

        public function getTimestamp()
		{
			return $this->_timestamp;
		}
		public function setTimestamp($val)
		{
			$this->_timestamp = $val;
		}

		//
		// Renewal Methods
		//

		public function getSubscriptionRenewalExpirationDateForType($subscriptionType=SUBSCRIPTION_TYPE_UNKNOWN)
		{
			// Parameters
			//
			//		subscriptionType		(required)
			//			This specifies either SUBSCRIPTION_TYPE_MONTH or SUBSCRIPTION_TYPE_YEAR

			$expirationDate = new DateTime('@' . $this->getExpirationDate(), new DateTimeZone("UTC"));
			$now = new DateTime("now", new DateTimeZone("UTC"));
			$timeToAdd = NULL;
			if ($subscriptionType == SUBSCRIPTION_TYPE_MONTH)
			{
				$oneMonthPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL);
				$timeToAdd = new DateInterval($oneMonthPeriodSetting);
			}
			else if ($subscriptionType == SUBSCRIPTION_TYPE_YEAR)
			{
				$oneYearPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL);
				$timeToAdd = new DateInterval($oneYearPeriodSetting);
			}
			else
			{
				error_log("TDOSubscription::getSubscriptionRenewalExpirationDateWithType($subscriptionType) called with an invalid subscriptionType.");
				return false;
			}

			$newExpirationDate = NULL;

			// If the current expiration date already occurs in the future, add
			// on the new interval.
			if ($expirationDate < $now)
			{
				$now = new DateTime("now", new DateTimeZone("UTC"));
				$newExpirationDate = $now->add($timeToAdd);
			}
			else
			{
				$newExpirationDate = $expirationDate->add($timeToAdd);
			}

			// Return the date in a UNIX timestamp.
			return $newExpirationDate->getTimestamp();
		}


		public function toArray()
		{
			$subscriptionInfo = array(
									  "subscriptionid" => $this->getSubscriptionID(),
									  "userid" => $this->getUserID(),
									  "expiration" => $this->getExpirationDate(),
									  "type" => $this->getSubscriptionType(),
									  "level" => $this->getSubscriptionLevel(),
									  "timestamp" => $this->getTimestamp());
			return $subscriptionInfo;
		}



		public function isActiveSubscription()
		{
			if (!isset($this->_timestamp))
				return false;

			if ($this->_timestamp == 0)
				return false;

            $currentTime = time();
			if ($currentTime > $this->_timestamp)
				return false;

			return true;
		}

		public static function getAboutToExpireVIPAccountIDsInDomain($domain)
		{
			if (empty($domain))
			{
				error_log("TDOSubscription::getAboutToExpireVIPAccountIDsInDomain() called with no domain");
				return false;
			}

			$link = TDOUtil::getDBLink();

			if(!$link)
			{
				error_log("TDOSubscription::getAboutToExpireVIPAccountIDsInDomain() failed to get dblink");
				return false;
			}

			$domain = mysql_real_escape_string($domain, $link);

			$nowDate = time();
            $expirationDate = time() + VIP_TWO_WEEKS;

			$userIDs = array();
			$sql = "SELECT tdo_user_accounts.userid FROM tdo_user_accounts JOIN tdo_subscriptions ON tdo_user_accounts.userid=tdo_subscriptions.userid WHERE expiration_date > $nowDate AND expiration_date <= $expirationDate AND username LIKE '%$domain' AND (tdo_subscriptions.teamid IS NULL OR tdo_subscriptions.teamid='')";

			$result = mysql_query($sql, $link);

			if (!$result)
			{
				error_log("TDOSubscription::getAboutToExpireVIPAccountIDsInDomain() failed to make the SQL call" . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			while ($row = mysql_fetch_array($result))
			{
				$userId = $row['userid'];
//				$username = $row['username'];

//                if(in_array($userId, $userIDs) == false && TDOUtil::isEmailAddressInWhiteList($username))
                if(in_array($userId, $userIDs) == false)
                {
	                $userIDs[] = $userId;
                }
			}

			TDOUtil::closeDBLink($link);

			return $userIDs;
		}

		//
		// TEAM ACCOUNT METHODS
		//

		// Returns the new teamid if successful or FALSE if there was an error
		public static function addTeamSubscription($teamName,
												   $creatorUserID)
		{
			if (empty($teamName))
				$teamName = "Untitled Team";


			/*
			 teamid VARCHAR(36) NOT NULL,
			 teamname VARCHAR(128) NOT NULL,
			 license_count INT NOT NULL DEFAULT 0,
			 billing_userid VARCHAR(36) NOT NULL,
			 expiration_date INT NOT NULL DEFAULT 0,
			 creation_date INT NOT NULL DEFAULT 0,
			 modified_date INT NOT NULL DEFAULT 0,
			 billing_frequency TINYINT(1) NOT NULL DEFAULT 0,
			 new_license_count INT NOT NULL DEFAULT 0
			 */
		}

		// This function returns an array of email addresses of IAP customers
		// that are members of a team so a reminder email can be sent to them to
		// help them remember to cancel their auto-renewing IAP subscription in
		// order to have their premium subscription be paid for by the team
		// account.
		//
		// Returns an array with the following keys:
		//
		//	"endDate"	The end date which, if sending emails is successful,
		//				should be recorded into the system so we don't notify
		//				the same users more than once.
		//
		//	"userInfos"	An array of user info items that contains:
		//
		//		"userID"
		//		"expirationDate"
		public static function getAboutToExpireIAPTeamMembersForReminderEmail($link=NULL)
		{
			// Todo for Business feature: Send a reminder to team members that
			// are IAP accounts to remind them to cancel their auto-renewing IAP
			// subscription so their premium subscription can be paid for by the
			// team account.
			//
			// Get all IAP subscriptions that will expire within the 7-day
			// window from the last time we checked, to 7 days from now.

			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOSubscription::getAboutToExpireIAPTeamMembersForReminderEmail() could not get DB connection.");
					return false;
				}
			}

			// Read the last time we checked for expired accounts so we won't
			// include emails we've already notified in the past. If we've never
			// notified, this will be nil and we can just grab accounts between
			// now and 7 days from now.

			$now = time();
			$startDate = $now;
			$startDateString = TDOUtil::getStringSystemSetting('IAP_CANCELLATION_INSTRUCTIONS_LAST_NOTIFY_DATE', NULL, $link);
			if (!empty($startDateString))
			{
				// Convert the string to a UNIX timestamp
				$startDate = (int)$startDateString;
			}

			$reminderIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_IAP_CANCELLATION_REMINDER_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_TEAM_IAP_CANCELLATION_REMINDER_DATE_INTERVAL, $link);
			$reminderInterval = new DateInterval($reminderIntervalSetting);
			$endDate = new DateTime('@' . $now, new DateTimeZone("UTC"));
			$endDate = $endDate->add($reminderInterval);
			$endDateTimestamp = $endDate->getTimestamp();

			// Get subscriptions from Apple IAP
			$sql = "SELECT tdo_subscriptions.userid,tdo_subscriptions.expiration_date FROM tdo_subscriptions JOIN tdo_iap_autorenew_receipts ON tdo_subscriptions.userid = tdo_iap_autorenew_receipts.userid WHERE (tdo_subscriptions.teamid IS NOT NULL AND tdo_subscriptions.teamid != '') AND tdo_subscriptions.expiration_date > $startDate AND tdo_subscriptions.expiration_date <= $endDateTimestamp AND tdo_iap_autorenew_receipts.autorenewal_canceled = 0";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::getAboutToExpireIAPTeamMembersForReminderEmail() failed to make the SQL call: " . mysql_error());
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}

			$userInfos = array();

			while ($row = mysql_fetch_array($result))
			{
				$userID = $row['userid'];
				$expirationDate = $row['expiration_date'];

				$userInfo = array(
								  "userID" => $userID,
								  "expirationDate" => $expirationDate
								  );

				$userInfos[] = $userInfo;
			}

			// Get subscriptions from Google Play
			$sql = "SELECT tdo_subscriptions.userid,tdo_subscriptions.expiration_date FROM tdo_subscriptions JOIN tdo_googleplay_autorenew_tokens ON tdo_subscriptions.userid = tdo_googleplay_autorenew_tokens.userid WHERE (tdo_subscriptions.teamid IS NOT NULL AND tdo_subscriptions.teamid != '') AND tdo_subscriptions.expiration_date > $startDate AND tdo_subscriptions.expiration_date <= $endDateTimestamp AND tdo_googleplay_autorenew_tokens.autorenewal_canceled = 0";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::getAboutToExpireIAPTeamMembersForReminderEmail() failed to make the SQL call for Google Play: " . mysql_error());
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}

			while ($row = mysql_fetch_array($result))
			{
				$userID = $row['userid'];
				$expirationDate = $row['expiration_date'];

				$userInfo = array(
								  "userID" => $userID,
								  "expirationDate" => $expirationDate
								  );

				$userInfos[] = $userInfo;
			}

			if ($closeLink)
				TDOUtil::closeDBLink($link);

			$results = array();
			$results['endDate'] = $endDateTimestamp;
			$results['userInfos'] = $userInfos;

			return $results;

			/**

			 SELECT tdo_subscriptions.userid FROM tdo_subscriptions, tdo_iap_autorenew_receipts WHERE tdo_subscriptions.userid = tdo_iap_autorenew_receipts.userid AND tdo_subscriptions.teamid IS NOT NULL AND tdo_subscriptions.expiration_date > 0 AND tdo_subscriptions.expiration_date <= 123456789012 AND tdo_iap_autorenew_receipts.autorenewal_canceled = 0

			 SELECT tdo_subscriptions.userid FROM tdo_subscriptions JOIN tdo_iap_autorenew_receipts ON tdo_subscriptions.userid = tdo_iap_autorenew_receipts.userid WHERE (tdo_subscriptions.teamid IS NOT NULL AND tdo_subscriptions.teamid != '') AND tdo_subscriptions.expiration_date > 0 AND tdo_subscriptions.expiration_date <= 123456789012 AND tdo_iap_autorenew_receipts.autorenewal_canceled = 0

			 */
		}


//		public static function markIAPEmailReminderTimestampForSubscription($subscriptionID, $link=NULL)
//		{
//			// Todo for Business feature: Keep track that we've sent a reminder
//			// email so we don't send multiple to the same person.
//
//			// If the subscription already exists in the table, update it,
//			// otherwise, insert it.
//			$closeLink = false;
//			if ($link == NULL)
//			{
//				$closeLink = true;
//				$link = TDOUtil::getDBLink();
//				if (!$link)
//				{
//					error_log("TDOSubscription::markIAPEmailReminderTimestampForSubscription() could not get DB connection.");
//					return false;
//				}
//			}
//
//			$subscriptionID = mysql_real_escape_string($subscriptionID, $link);
//			$now = time();
//			$sql = "UPDATE tdo_iap_cancellation_emails SET timestamp=$now WHERE subscriptionid='$subscriptionID'";
//
//			$existingTimestamp = TDOSubscription::getTimestampOfLastIAPReminderEmailForSubscription($subscriptionID, $link);
//			if (empty($existingTimestamp))
//			{
//				// Insert a new row
//				"INSERT INTO tdo_iap_cancellation_emails (subscriptionid,timestamp) VALUES ('$subscriptionID', $now)"
//			}
//
//			if (!mysql_query($sql, $link))
//			{
//				error_log("TDOSubscription::markIAPEmailReminderTimestampForSubscription() unable to delete subscription ($subscriptionID): " . mysql_error());
//				if ($closeLink)
//					TDOUtil::closeDBLink($link);
//				return false;
//			}
//
//			// SUCCESS!
//			if ($closeLink)
//				TDOUtil::closeDBLink($link);
//			return true;
//		}
//
//
//		public static function getTimestampOfLastIAPReminderEmailForSubscription($subscriptionID, $link=NULL)
//		{
//			$closeLink = false;
//			if ($link == NULL)
//			{
//				$closeLink = true;
//				$link = TDOUtil::getDBLink();
//				if (!$link)
//				{
//					error_log("TDOSubscription::getTimestampOfLastIAPReminderEmailForSubscription() could not get DB connection.");
//					return false;
//				}
//			}
//
//			$subscriptionID = mysql_real_escape_string($subscriptionID, $link);
//
//			$sql = "SELECT timestamp FROM tdo_iap_cancellation_emails WHERE subscriptionid='$subscriptionID'";
//			$result = mysql_query($sql, $link);
//			if($result)
//			{
//				if($row = mysql_fetch_array($result))
//				{
//					$timestamp = $row['timestamp'];
//
//					if ($closeLink)
//						TDOUtil::closeDBLink($link);
//					return $timestamp;
//				}
//			}
//			else
//				error_log("TDOSubscription::getTimestampOfLastIAPReminderEmailForSubscription() failed:".mysql_error());
//
//			if ($closeLink)
//				TDOUtil::closeDBLink($link);
//			return false;
//		}


		public static function getActiveStripeCard($stripeCustomer)
		{
			if (empty($stripeCustomer))
			{
				return NULL;
			}

			if (empty($stripeCustomer->default_source))
			{
				return NULL;
			}

			$activeCard = NULL;
			$defaultCardID = $stripeCustomer->default_source;
			foreach ($stripeCustomer->sources['data'] as $source)
			{
				if ($source['id'] == $defaultCardID)
				{
					$activeCard = $source;
					break;
				}
			}

			return $activeCard;
		}

		//
		// PRIVATE METHODS
		//

		private static function _subscriptionFromRecord($row)
		{
			if (empty($row))
				return false;

			if (isset($row['subscriptionid']))
			{
				$subscription = new TDOSubscription();
				if (isset($row['subscriptionid']))
					$subscription->setSubscriptionID($row['subscriptionid']);
				if (isset($row['userid']))
					$subscription->setUserID($row['userid']);
				if (isset($row['expiration_date']))
					$subscription->setExpirationDate($row['expiration_date']);
				if (isset($row['type']))
					$subscription->setSubscriptionType($row['type']);
				if (isset($row['level']))
					$subscription->setSubscriptionLevel($row['level']);
				if (isset($row['teamid']))
					$subscription->setTeamID($row['teamid']);
				if (isset($row['timestamp']))
					$subscription->setTimestamp($row['timestamp']);

				return $subscription;
			}

			return false;
		}
	}

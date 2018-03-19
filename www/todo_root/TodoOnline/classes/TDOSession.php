<?php
//      TDOSession
//      This will track the login session of a user

// include files
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/DBConstants.php');
include_once('Facebook/config.php');
include_once('Facebook/facebook.php');

define('TDO_USER_SESSION_COOKIE',	'TodoOnlineSession');
define('TDO_FB_USER_SESSION_COOKIE',	'TodoOnlineFBSession');
define('TDO_ADMIN_SESSION_COOKIE',	'TodoOnlineAdminSession');
define('TDO_SYNC_SESSION_COOKIE',	'TodoOnlineSyncSession');
define('TDO_ADMIN_IMPERSONATION_SESSION_COOKIE',	'TodoOnlineImpersonation');
define('TDO_ADMIN_IMPERSONATION_REFERRER_URI_COOKIE',	'TodoOnlineImpersonationReferrer');

define('SESSION_TIMEOUT', 1209600);
define('ADMIN_SESSION_TIMEOUT', 3600);
define('SYNC_SESSION_TIMEOUT', 3600);
//define('SESSION_TIMEOUT', 3600);

define('NO_EMAIL_ERROR', -1);
define('EMAIL_TAKEN_ERROR', -2);
define('FB_USER_EXISTS_ERROR', -3);
define ('EMAIL_TOO_LONG_ERROR', -4);

class TDOSession
{
	private static $instance;
	private static $_cookieName;
    private static $_cookieTimeout;
    private static $_cookiePath;
    private static $_impersonation;

	private $_sessionId;		// session id generated when logged in
	private $_userId;			// user id is read from session table
	private $_loggedIn;			// logged in indicates a session was found
	private $_isFB;				// variable that identifies this is loaded in facebook

	private function __construct()
	{
        self::$_cookiePath = '';
        self::$_impersonation = FALSE;
	}

	public static function setIsAdmin()
	{
		$needToReconfigure = false;
        if(TDOSession::getCookieName() != TDO_ADMIN_SESSION_COOKIE)
        {
			if (self::$instance) {
				$needToReconfigure = true;
			}
        }

		self::$_cookieName = TDO_ADMIN_SESSION_COOKIE;
        self::$_cookieTimeout = ADMIN_SESSION_TIMEOUT;

		if ($needToReconfigure) {
			self::$instance->__configureSession();
		}
	}

	public static function setIsSync()
	{
		$needToReconfigure = false;
        if(TDOSession::getCookieName() != TDO_SYNC_SESSION_COOKIE)
        {
			if (self::$instance) {
				$needToReconfigure = true;
			}
		}

		self::$_cookieName = TDO_SYNC_SESSION_COOKIE;
		self::$_cookieTimeout = SYNC_SESSION_TIMEOUT;

		if ($needToReconfigure) {
			self::$instance->_configureSession();
		}
	}

	public static function getInstance($facebook = NULL)
	{
		if(!isset(self::$instance))
		{
			$className = __CLASS__;

			// Check for a JSON Web Token (JWT) to see if the caller is authorized
			// and authenticated.
			$headers = getallheaders();
			if (!empty($headers) && !empty($headers['Authorization'])) {
				$authHeader = $headers['Authorization'];
				$tokenArray = explode(' ', $authHeader);
				if ($tokenArray && count($tokenArray) > 1 && $tokenArray[0] == 'Bearer') {
					$token = $tokenArray[1];
					$userid = TDOAuthJWT::userIDFromToken($token);
					if ($userid) {
						self::$instance = new $className;

						self::$instance->_configureSessionWithToken($token);
					}
				}
			}

			// If we still don't have an instance, either the JWT didn't exist or it
			// was not valid.
			if (!isset(self::$instance)) {
				self::$instance = new $className;
				if(isset($facebook)) {
					self::$instance->_configureSession($facebook);
				}
			}
		}

		return self::$instance;
	}

	public function getSessionId()
	{
		return $this->_sessionId;
	}

	public function getUserId()
	{
		return $this->_userId;
	}

	public function isLoggedIn()
	{
        if($this->_loggedIn)
        {
            if (TDOUserMaintenance::isMaintenanceInProgressForUser($this->_userId))
                return false;
        }

		return $this->_loggedIn;
	}

	public function isFB()
	{
		return $this->_isFB;
	}

	private function getCookieName()
	{
		if(!empty(self::$_cookieName))
			return self::$_cookieName;

		if(TDOSession::_isFacebook())
			self::$_cookieName = TDO_FB_USER_SESSION_COOKIE;
		else
			self::$_cookieName = TDO_USER_SESSION_COOKIE;

		return self::$_cookieName;
	}

	// This method is used to configure TDOSession objects with a userID that has
	// already been authorized and is logged in (via JWT). There's no need to
	// store the session in the database because all the pertinent information is
	// stored inside the JWT.
	private function _configureSessionWithToken($token)
	{
		if (empty($token)) {
			error_log("TDOSession::_configureSessionWithUserID() passed empty token parameter.");
		}

		$payload = TDOAuthJWT::payloadForToken($token);
		if ($payload) {
			$this->_sessionId = $payload->jti;
			$this->_userId = $payload->data->userid;
			$this->_loggedIn = TRUE;
		}
	}

	private function _configureSession($facebook = NULL)
	{

        if(empty(self::$_cookieTimeout))
            self::$_cookieTimeout = SESSION_TIMEOUT;

		$this->_isFB = TDOSession::_isFacebook();

		$sessionName = TDOSession::getCookieName();

		if(isset($_COOKIE[$sessionName]))
			$this->_sessionId = $_COOKIE[$sessionName];
		else
		{
//			error_log("TDOSession initializing session found no user session id in cookie");
			return;
		}

        $link = TDOUtil::getDBLink();
        if(!$link)
		{
			error_log("TDOSession failed to get dblink");
			return;
		}

		$sql = "SELECT userid, timestamp FROM tdo_user_sessions where sessionid='".mysql_real_escape_string($this->_sessionId, $link)."'";
		$result = mysql_query($sql, $link);

		if(!$result)
		{
			error_log("Failed to find userid based on session db with error :".mysql_error());
			TDOUtil::closeDBLink($link);
			return;
		}

		if($row = mysql_fetch_array($result))
		{
            //If the timestamp is more than a day old, write out a new
            //timestamp so the session daemon doesn't delete this session
            //when it's still active
            $writeOutNewTimestamp = false;
            if(isset($row['timestamp']))
            {
                $oldTime = $row['timestamp'];
                if($oldTime < time() - 86400)
                {
                    $writeOutNewTimestamp = true;
                }
            }

			if(isset($row['userid']))
			{
				$userid = $row['userid'];

				if($this->_isFB && $facebook)
				{
					$user = $facebook->getUser();
					if($user)
					{
						$fbId = TDOUser::facebookIdForUserId($userid);
						if($user != $fbId)
						{
							error_log("Current session doesn't match facebook user");
							TDOSession::logout();
						}
						else
						{
							$this->_userId = $userid;
							$this->_loggedIn = true;
                            $time = time();
                            setcookie($sessionName, $this->_sessionId, $time+self::$_cookieTimeout, self::$_cookiePath);
                            if($writeOutNewTimestamp)
                                TDOSession::updateSessionTimestamp($this->_sessionId, $link);
						}
					}
					else
					{
						error_log("We have a session but can't get into facebook");
					}
				}
				else
				{
					$this->_userId = $userid;
					$this->_loggedIn = true;
                    $time = time();
                    setcookie($sessionName, $this->_sessionId, $time+self::$_cookieTimeout, self::$_cookiePath);
                    if($writeOutNewTimestamp)
                        TDOSession::updateSessionTimestamp($this->_sessionId, $link);
				}
			}
		}
        TDOUtil::closeDBLink($link);
	}

    public static function updateSessionTimestamp($sessionId, $link)
    {
        $sql = "UPDATE tdo_user_sessions SET timestamp=".time()." WHERE sessionid='".mysql_real_escape_string($sessionId, $link)."'";

        if(!mysql_query($sql, $link))
        {
            error_log("Unable to update session time with error: ".mysql_error());
        }

    }

    public static function deleteExpiredSessions()
    {
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOSession failed to get link");
            return false;
        }

        $oldTime = time() - SESSION_TIMEOUT;

        $sql = "DELETE FROM tdo_user_sessions WHERE timestamp < $oldTime";
        if(!mysql_query($sql, $link))
        {
            error_log("deleteExpiredSessions failed with error: ".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }

        TDOUtil::closeDBLink($link);
        return true;
    }

	public function setupFacebookSession($facebook)
	{
		# Let's see if we have an active session
		$fbuserid = $facebook->getUser();

		if($fbuserid)
		{
			try
			{
				$userData = $facebook->api('/me', 'GET');
			}
			catch (FacebookApiException $e)
			{
				error_log("TDOSession FacebookApiException ");
				return false;
			}

			if(empty($userData))
				return false;

			if(TDOUser::existsFacebookUser($fbuserid) == false)
				return false;

			$user = TDOUser::getUserForFacebookId($fbuserid);

			if($user == false)
				return false;

			$userid = $user->userId();

            //Update the user info if necessary to keep it up to date
            $user->setFirstName($userData['first_name']);
            $user->setLastName($userData['last_name']);
            if(TDOUser::firstNameForUserId($userid) != $user->firstName() || TDOUser::lastNameForUserId($userid) != $user->lastName())
            {
                $oldName = TDOUser::firstNameForUserId($userid);
                $user->updateUser();
                $name = $user->firstName();
            }

			if($this->_createSessionForUserid($userid) == false)
				return false;

			return true;
		}

		return false;
	}

    public function setupFacebookSyncSession($user, $userData)
    {
        if(empty($user) || empty($userData))
            return false;

        //Update the user info if necessary to keep it up to date
        if($user->firstName() != $userData['first_name'] || $user->lastName() != $userData['last_name'])
        {
            $user->setFirstName($userData['first_name']);
            $user->setLastName($userData['last_name']);
            $user->updateUser();
        }

        if($this->_createSessionForUserid($user->userId()) == false)
            return false;

        return true;
    }

	public static function isFacebook()
    {
        return TDOSession::_isFacebook();
    }

	private static function _isFacebook()
	{
		if(isset($_SERVER['SERVER_NAME']))
		{
			if($_SERVER['SERVER_NAME'] == 'fb.todopro.com')
				return true;
			else if($_SERVER['SERVER_NAME'] == 'plano-fb.appigo.com')
				return true;
		}
		return false;
	}

	public function login($username, $password, $admin_user_id = FALSE)
	{
        $result = array();

		$user = TDOUser::getUserForUsername($username);
		if($user == false)
		{
            // CRG - Removing Legacy support, no more migrations
            $error = array();
            $error['id'] = 4792;
            $error['msg'] = "TDOSession::login user not found: ". $username;
            error_log("TDOSession::login failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;


            // We didn't find a user so now go try to find this user on the legacy Todo Online
//            $tdoLegacy = new TDOLegacy();
//
//            $response = $tdoLegacy->authUser($username, $password);
//            if(empty($response['error']))
//            {
//                if(!empty($response['user']))
//                {
//                    $user = $response['user'];
//
//                    // only migrate users if they have paid and are not expired, otherwise we can't move
//                    // the data because the server won't let us in!
//
//                    // with the new Moki interface, we don't have to worry, migrate them all!
////                    if( ($user['ispaidsub'] != 0) && ($user['secondstosubexp'] > 0) )
////                    {
//                    if(!empty($user['userid']))
//                    {
//                        $userId = $user['userid'];
//                        error_log("TDOSession::login found a user and is beginning migration process: ".$userId);
//
//                        // check to see if we already have this userid in our system.  If we do this user has already
//                        // migrated but then changed their userid.  If we get back a valid Username then
//                        // fail the login
//                        $newUsername = TDOUser::usernameForUserId($userId);
//                        if($newUsername)
//                        {
//                            $error = array();
//                            $error['id'] = 4792;
//                            $error['msg'] = "TDOSession::login userid already exists for a different login: ". $username . " id: " . $userId;
//                            error_log("TDOSession::login failed with error: " . $error['msg']);
//                            $result['error'] = $error;
//                            return $result;
//                        }
//
//                        $startResult = TDOLegacy::startMigrationForLegacyUser($username, $password);
//                        if(empty($startResult['error']))
//                        {
//                            if(!empty($startResult['subscription_time_added']))
//                                $result['subscription_time_added'] = $startResult['subscription_time_added'];
//
//                            if(!empty($startResult['userid']))
//                                $result['userid'] = $startResult['userid'];
//
//                            $error = array();
//                            $error['id'] = 0;
//                            $error['msg'] = "User migration started for user";
//                            error_log("Started migration process for user: ".$startResult['userid']);
//                            $result['error'] = $error;
//                            return $result;
//                        }
//                        else
//                        {
//                            $error = $startResult['error'];
//                            error_log("TDOSession::login failed with error: " . $error['msg']);
//                            $result['error'] = $error;
//                            return $result;
//                        }
//                    }
////                    }
//                }
//
//                $error = array();
//                $error['id'] = 4792;
//                $error['msg'] = "TDOSession::login user not found and was not found on legacy system: ". $username;
//                error_log("TDOSession::login failed with error: " . $error['msg']);
//                $result['error'] = $error;
//                return $result;
//            }
//            else
//            {
//                $error = $response['error'];
//                error_log("TDOSession::login failed with error: " . $error['msg']);
//                $result['error'] = $error;
//                return $result;
//            }
        }
        else
        {
//            if(TDOLegacy::userIsBeingMigrated($username) == true)
//            {
//                $error = array();
//                $error['id'] = 0;
//                $error['msg'] = "Migration still in process for user: ".$username;
//                error_log("TDOSession::login failed with error: " . $error['msg']);
//                $result['error'] = $error;
//                return $result;
//            }

			if (TDOUserMaintenance::isMaintenanceInProgressForUser($user->userId()))
			{
                $error = array();
                $error['id'] = 1;
                $error['msg'] = "User account maintenance still in process for user: ".$username;
                error_log("TDOSession::login failed with error: " . $error['msg']);
                $result['error'] = $error;
                return $result;
			}
        }

        if ($password === FALSE && $admin_user_id !== FALSE) {
            self::$_cookieName = TDO_USER_SESSION_COOKIE;
            self::$_cookiePath = '/';
            self::$_cookieTimeout = ADMIN_SESSION_TIMEOUT;
            self::$_impersonation = TRUE;
        } else {
            if ($user->matchPassword($password) == false) {
                $error = array();
                $error['id'] = 4792;
                $error['msg'] = "TDOSession::login TDOUser->matchPassword returned false for user: " . $username;
                error_log("TDOSession::login failed with error: " . $error['msg']);
                $result['error'] = $error;
                return $result;
            }
        }

		$userid = $user->userId();

		if(!isset($userid))
		{
            $error = array();
            $error['id'] = 4793;
            $error['msg'] = "TDOSession::login user authenticated but was missing a userid: ". $username;
            error_log("TDOSession::login failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
		}
		else
		{
			if($this->_createSessionForUserid($userid) == true)
            {
                $result['userid'] = $userid;
            }
            else
            {
                $error = array();
                $error['id'] = 4794;
                $error['msg'] = "Error creating session for UserId";
                error_log("TDOSession::login failed with error: " . $error['msg']);
                $result['error'] = $error;
            }
		}

        return $result;
	}

	public function caldavlogin($username, $password)
	{
		$user = TDOUser::getUserForUsername($username);

		if($user == false)
		{
			error_log("TDOSession::login TDOUser->getUserForUsername returned false for user: ". $username);
			return false;
		}

		if($user->matchPassword($password) == false)
		{
			error_log("TDOSession::login TDOUser->matchPassword returned false for user: ". $username);
			return false;
		}

		$userid = $user->userId();

		if(!isset($userid))
		{
			error_log("TDOSession::login user authenticated but was missing a userid: ". $username);
			return false;
		}
		else
		{
			$sessionid = md5($userid . time());

			$this->_sessionId = $sessionid;
			$this->_userId = $userid;
			$this->_loggedIn = true;

			return true;
		}
	}


	private function _createSessionForUserid($userid)
	{
		if(!isset($userid))
		{
			error_log("TDOSession::createSessionForUserid failed with missing userid");
			return false;
		}
		else
		{
            if(empty(self::$_cookieTimeout))
                self::$_cookieTimeout = SESSION_TIMEOUT;

			$sessionName = TDOSession::getCookieName();

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSession failed to get dblink");
				return;
			}

			$sessionid = md5($userid . time());
			$time = time();
            setcookie($sessionName, $sessionid, $time + self::$_cookieTimeout, self::$_cookiePath);
            if (self::$_impersonation) {
                setcookie(TDO_ADMIN_IMPERSONATION_SESSION_COOKIE, TRUE, $time + self::$_cookieTimeout, self::$_cookiePath);
                setcookie(TDO_ADMIN_IMPERSONATION_REFERRER_URI_COOKIE, base64_encode($_SERVER['HTTP_REFERER']), $time + self::$_cookieTimeout, self::$_cookiePath);
            }


			// TODO: write code to go clean out the old sessions here
			$sql = "INSERT INTO tdo_user_sessions (sessionid, userid, timestamp) VALUES ('$sessionid', '$userid', '$time')";

			$result = mysql_query($sql, $link);

			if(!$result)
			{
				error_log("Failed to create session in db with error :".mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

			$this->_sessionId = $sessionid;
			$this->_userId = $userid;
			$this->_loggedIn = true;

			TDOUtil::closeDBLink($link);

			return true;
		}
	}

	public function createFacebookUser($facebook)
	{
		# Let's see if we have an active session
		$fbuserid = $facebook->getUser();

		if($fbuserid)
		{
			try
			{
				if(TDOUser::existsFacebookUser($fbuserid) == true)
					return FB_USER_EXISTS_ERROR;

				$user = new TDOUser();
				$user->setOauthUID($fbuserid);
				$userData = $facebook->api('/me', 'GET');

				if(isset($userData['first_name']))
					$user->setFirstName($userData['first_name']);

				if(isset($userData['last_name']))
					$user->setLastName($userData['last_name']);

                if(isset($userData['email']))
                {
                    $username = $userData['email'];
                    if(TDOUser::existsUsername($username))
                    {
                        return EMAIL_TAKEN_ERROR;
                    }
                    else
                    {
                        if(strlen($username) > USER_NAME_LENGTH)
                        {
                            return EMAIL_TOO_LONG_ERROR;
                        }

                        $user->setUsername($username);
                    }
                }
                else
                {
                    return NO_EMAIL_ERROR;
                }

				if($user->addFacebookUser() == false)
					return false;

				return $this->_createSessionForUserid($user->userId());
			}
			catch (FacebookApiException $e)
			{
				error_log("TDOSession FacebookApiException ");
				return false;
			}
		}
		return false;
	}

	public function getSubscriptionInfo()
	{
		$userID = $this->getUserId();
		if (empty($userID))
		{
			// Can't return subscription information for something we have no
			// user information about.
			return false;
		}

        $link = TDOUtil::getDBLink();
        if(!$link)
		{
			error_log("TDOSession::getSubscriptionInfo() failed to get dblink");
			return false;
		}

		$sql = "SELECT tdo_subscriptions.expiration_date,tdo_subscriptions.level,tdo_subscriptions.teamid,tdo_user_payment_system.payment_system_type FROM tdo_subscriptions LEFT JOIN tdo_user_payment_system ON tdo_subscriptions.userid = tdo_user_payment_system.userid WHERE tdo_subscriptions.userid='$userID'";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			error_log("TDOSession::getSubscriptionInfo() failed to discover user subscription information for userid (" . $userID . "): " . mysql_error());
		}
		else
		{
			if($row = mysql_fetch_array($result))
			{
				$expirationDate = $row['expiration_date'];
				if (empty($expirationDate))
				{
					error_log("TDOSession::getSubscriptionInfo() tried to read the expiration date for userid (" . $userID . ") but it did not exist.");
					TDOUtil::closeDBLink($link);
					return false;
				}

				$subscriptionLevel = $row['level'];
				if (empty($subscriptionLevel))
				{
					error_log("TDOSession::getSubscriptionInfo() tried to read the subscription level for userid ('$userID') but it did not exist.");
					TDOUtil::closeDBLink($link);
					return false;
				}

				$userPaymentSystem = PAYMENT_SYSTEM_TYPE_UNKNOWN;
				$userPaymentSystemV2 = PAYMENT_SYSTEM_TYPE_UNKNOWN; // Used in Todo/Todo Cloud v8.2+ clients
				if (isset($row['payment_system_type']))
				{
					$userPaymentSystem = $row['payment_system_type'];
					$userPaymentSystemV2 = $userPaymentSystem;
				}

				// Calculate how many seconds from right now that the
				// subscription will expire.  This will help us show the correct
				// subscription expiration on clients because it could be
				// possible that the client's date is set in the distant past.
				$expirationSecondsFromNow = $expirationDate - time();
				// bht - we need to let this be negavite, otherwise the client
				// will show that the user's expiration date is "Today", which
				// will not make sense.
//				if ($expirationSecondsFromNow < 0)
//					$expirationSecondsFromNow = 0; // Don't let it be negative. 0 will indicate "EXPIRED"

				// Let clients know whether this user comes from a whitelisted
				// domain.
				$isUserWhitelisted = TDOUtil::isCurrentUserInWhiteList($this);
				if ($isUserWhitelisted)
				{
					$userPaymentSystemV2 = PAYMENT_SYSTEM_TYPE_WHITELISTED;
				}

				$teamid = $row['teamid'];
				$teamExpirationDate = -1;
				$teamExpirationSecondsFromNow = -1;
				$teamName = "";
				$teamBillingAdminName = "";
				$teamBillingAdminEmail = "";


				if (!empty($teamid) && strlen($teamid) > 0)
				{
					// This user has a team account!
					$subscriptionLevel = SUBSCRIPTION_LEVEL_TEAM;

					$team = TDOTeamAccount::getTeamForTeamID($teamid, $userID, $link);
					if (!empty($team))
					{
						$teamName = $team->getTeamName();
						$teamExpirationDate = $team->getExpirationDate();

						// Get information about the Team Billing Administrator
						// so we can show it to users on devices.
						$billingUserID = $team->getBillingUserID();
						$teamBillingAdminName = TDOUser::fullNameForUserId($billingUserID);
						$teamBillingAdminEmail = TDOUser::usernameForUserId($billingUserID);
					}
					else
					{
						// As long as the system is working properly the real team
						// expiration date should already be set in the
						// expirationDate variable. Move it over to the
						// teamExpirationDate variable so that newer clients will be
						// able to properly display it.
						$teamExpirationDate = $expirationDate;
					}

					$teamExpirationSecondsFromNow = $teamExpirationDate - time();

					// Set the basic subscriptionLevel to something in the far
					// distant future so that old clients won't allow team users
					// to purchase anything through the client because their
					// account is managed by a team and must be done online.
					// Go 10 years from now so it may catch their attention.

					//					 1 day
					$tenYearsInSeconds = 86400 * 365 * 10;

					$expirationDate = time() + $tenYearsInSeconds;
					$expirationSecondsFromNow = $expirationDate - time();

					// Setting this to Stripe will force a check to see if the
					// user is eligible to renew their subscription (which will
					// send it into the check to see that the expiration date is
					// far into the future).
					$userPaymentSystem = PAYMENT_SYSTEM_TYPE_STRIPE;

					$userPaymentSystemV2 = PAYMENT_SYSTEM_TYPE_TEAM;
				}

				// Make sure that teamBillingAdminEmail and teamBillingAdminEmail
				// are never sent back as false. If these are nil, let's send
				// back an empty string. We had a customer report that he is not
				// able to sync on a fresh install. The reason is that these two
				// fields are coming back as false. This fix should prevent the
				// crash.
				if (empty($teamBillingAdminName)) {
					$teamBillingAdminName = "";
				}
				if (empty($teamBillingAdminEmail)) {
					$teamBillingAdminEmail = "";
				}

				$subscriptionUserDisplayName = TDOUser::displayNameForUserId($userID);

				$subscriptionInfo = array(
										  "subscriptionLevel" => $subscriptionLevel,
										  "subscriptionExpirationDate" => $expirationDate,
										  "subscriptionExpirationSecondsFromNow" => $expirationSecondsFromNow,
										  "subscriptionPaymentService" => $userPaymentSystem,
										  "subscriptionPaymentServiceV2" => $userPaymentSystemV2,
										  "subscriptionTeamExpirationDate" => $teamExpirationDate,
										  "subscriptionTeamExpirationSecondsFromNow" => $teamExpirationSecondsFromNow,
										  "subscriptionTeamName" => $teamName,
										  "subscriptionTeamAdminName" => $teamBillingAdminName,
										  "subscriptionTeamBillingAdminEmail" => $teamBillingAdminEmail,
										  "subscriptionUserDisplayName" => $subscriptionUserDisplayName
										  );

				// If the user is part of a team membership, Also read information about a team account:
				//	subscriptionTeamExpirationDate
				//	subscriptionTeamExpirationDateSecondsFromNow
//				$sql = "SELECT tdo_team_subscriptions.license_expiration_date,tdo_team_members.membership_type FROM tdo_team_subscriptions LEFT JOIN tdo_team_members ON tdo_team_subscriptions.teamid = tdo_team_members.teamid WHERE tdo_team_members.userid='$userID'";
//				$result = mysql_query($sql, $link);
//				if (
//					// teamid IN (SELECT teamid FROM tdo_team_members WHERE userid='$userID')";

				TDOUtil::closeDBLink($link);
				return $subscriptionInfo;
			}
			else
			{
				error_log("TDOSession::getSubscriptionInfo() no subscription information found for userid ('$userID').");
            }
		}

		TDOUtil::closeDBLink($link);
		return false;
	}

	public function getSubscriptionLevel()
	{
		$userID = $this->getUserId();
		if (empty($userID))
		{
			// Can't return subscription information for something we have no
			// user information about.
			return SUBSCRIPTION_LEVEL_EXPIRED;
		}

		return TDOSubscription::getSubscriptionLevelForUserID($userID);
	}


	public static function logout()
	{
		$sessionName = TDOSession::getCookieName();
		unset($_SESSION['timezone']);
        unset($_SESSION['unread_message_count']);
        unset($_SESSION['last_checked_messages']);

        if(setcookie('TodoOnlineShowCompletedTasks', 0))
            $_COOKIE['TodoOnlineShowCompletedTasks'] = 0;
        setcookie('TodoOnlineListId','', time() - 9999);
        setcookie('TodoOnlineContextId', '', time() - 9999);
        setcookie('TodoOnlineTagId', '', time() - 9999);

        if(isset($_COOKIE[$sessionName]))
			$sessionid = $_COOKIE[$sessionName];
		else
		{
			return;
		}
		//echo "TDOSession logout for session: " . $sessionid . " <br/>";
		setcookie($sessionName, '', time()-9999);
        if (isset($_COOKIE[TDO_ADMIN_IMPERSONATION_SESSION_COOKIE])) {
            setcookie(TDO_ADMIN_IMPERSONATION_SESSION_COOKIE, '', time() - 9999);
            setcookie(TDO_ADMIN_IMPERSONATION_REFERRER_URI_COOKIE, '', time() - 9999);
        }

        $link = TDOUtil::getDBLink();
        if(!$link)
		{
			error_log("TDOSession failed to get dblink");
			return;
		}

		// TODO: write code to go clean out the old sessions here

		$sql = "DELETE FROM tdo_user_sessions WHERE sessionid='$sessionid'";

		$result = mysql_query($sql, $link);
		if(!$result)
		{
			error_log("TDOSession failed to remove the user session with error :".mysql_error());
		}

		TDOUtil::closeDBLink($link);
	}


    public static function getAllSessions()
    {
        $sessions = array();

        $link = TDOUtil::getDBLink();
        if(!$link)
		{
			error_log("TDOSession failed to get dblink");
			return;
		}

		$sql = "SELECT * FROM tdo_user_sessions";
		$result = mysql_query($sql, $link);

		if(!$result)
		{
			error_log("TDOSession query failed with error :".mysql_error());
			TDOUtil::closeDBLink($link);
			return;
		}

		while($row = mysql_fetch_array($result))
		{
			$sessions[] = $row;
		}

		return $sessions;
    }


    public static function deleteAllSessionsForUser($userid)
    {
		$rc = true;

        $link = TDOUtil::getDBLink();
        if(!$link)
		{
			error_log("TDOSession failed to get dblink");
			return false;
		}

		// TODO: write code to go clean out the old sessions here
		$sql = "DELETE FROM tdo_user_sessions WHERE userid='$userid'";
		$result = mysql_query($sql, $link);
		if(!$result)
		{
			error_log("TDOSession delete all sessions for user failed with error :".mysql_error());
			TDOUtil::closeDBLink($link);
			return false;
		}

		TDOUtil::closeDBLink($link);

		return true;
    }


    public static function deleteSessions($sessionsids)
    {
		$rc = true;

        $link = TDOUtil::getDBLink();
        if(!$link)
		{
			error_log("TDOSession failed to get dblink");
			return;
		}

        foreach($sessionsids as $sessionid)
        {
			// TODO: write code to go clean out the old sessions here
			$sql = "DELETE FROM tdo_user_sessions WHERE sessionid='$sessionid'";
			$result = mysql_query($sql, $link);
			if(!$result)
			{
				error_log("TDOSession failed to delete a session with error :".mysql_error());
				$rc = false;
			}
		}

		TDOUtil::closeDBLink($link);

		return $rc;
    }

    public static function strleft($s1, $s2)
    {
        return substr($s1, 0, strpos($s1, $s2));
    }


    public static function saveCurrentURL()
    {
        if(!isset($_SERVER['REQUEST_URI']))
        {
            $serverrequri = $_SERVER['PHP_SELF'];
        }
        else
        {
            $serverrequri = $_SERVER['REQUEST_URI'];
        }
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocol = TDOSession::strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
        $_SESSION['ref'] = $protocol."://".$_SERVER['SERVER_NAME'].$port.$serverrequri;
//        $currentURL = $protocol."://".$_SERVER['SERVER_NAME'].$port.$serverrequri;
//        return $currentURL;

    }
    public static function currentURLHasFBRequest($fbUserId)
    {
        if(!$fbUserId)
            return false;

        if(isset($_GET['request_ids']))
        {
            $requestString = $_GET['request_ids'];
            $requestids = explode(",", $requestString);
            foreach($requestids as $requestid)
            {
                $invitation = TDOInvitation::getInvitationForRequestIdAndFacebookId($requestid, $fbUserId);
                if($invitation)
                {
                    return true;
                }
            }
        }
        return false;
    }
    public static function savedURLHasInvitation()
    {
        if(isset($_SESSION['ref']))
        {
            $invitationid = NULL;
            $invitationURL= $_SESSION['ref'];
            $queryString = parse_url($invitationURL, PHP_URL_QUERY);
            $urlParams = explode("&", $queryString);
            foreach ($urlParams as $urlParam)
            {
                $keyVal = explode("=", $urlParam);
                if($keyVal[0] == 'invitationid')
                {
                    $invitationid = $keyVal[1];
                    break;
                }
            }
            if($invitationid)
            {
                $invitation = TDOInvitation::getInvitationForInvitationId($invitationid);
                if($invitation)
                    return true;
            }
        }
        return false;
    }

    public function setDefaultTimezone()
    {
        //If the user's timezone isn't already in the session, put it there
        if(!isset($_SESSION['timezone']))
        {
            $timezone = TDOUserSettings::getTimezoneForUser($this->getUserId());
            if($timezone)
            {
                $_SESSION['timezone'] = $timezone;
            }
        }
        if(isset($_SESSION['timezone']))
        {
            date_default_timezone_set($_SESSION['timezone']);
            return true;
        }
        else
        {
            return false;
        }
    }

	//
	// Team Account Functions
	//

//	public static function getTeamExpirationDate($teamid, $link)
//	{
//	}

}

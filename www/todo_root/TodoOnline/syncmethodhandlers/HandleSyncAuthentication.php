<?php
	
	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/syncmethodhandlers/SyncConstants.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	include_once('TodoOnline/content/TaskContentFunctions.php');
	
//  Methods in this file do not require authentication
//	if(!$session->isLoggedIn())
//	{
//		error_log("Method called without a valid session");
//		
//		echo '{"success":false}';
//		return;
//	}
	
	if($method == "getSessionToken")
	{

        $retVal = 0;
        $user = NULL;
        if( isset($_POST['username']) && isset($_POST['password']) ) 
        {
        
        
            // first check to see if this user has a valid subscription
            $userId = TDOUser::userIdForUserName($_POST['username']);
            if(empty($userId))
            {
                // try to migrate the user from the previous system

                $retVal = $session->login($_POST['username'], $_POST['password']);
                if(!empty($retVal['error']))
                {
                    $error = $retVal['error'];
                    if($error['id'] == 0)
                    {
                        outputSyncError(ERROR_CODE_USER_BEING_MIGRATED, ERROR_DESC_USER_BEING_MIGRATED);
                        error_log("HandleSyncAuthentication:getSessionToken: user is being migrated: " . $_POST['username']);
                        return;
                    }
                    if($error['id'] == 1)
                    {
                        //TODO: swap these messages once we release 6.0.3 on iOS
                        outputSyncError(ERROR_CODE_USER_BEING_MIGRATED, ERROR_DESC_USER_BEING_MIGRATED);
//                      outputSyncError(ERROR_CODE_USER_MAINTENANCE, ERROR_DESC_USER_MAINTENANCE);

                        error_log("HandleSyncAuthentication:getSessionToken: user is being maintained: " . $_POST['username']);
                        return;
                    }
                }
                
                outputSyncError(ERROR_CODE_BAD_USERNAME_OR_PASSWORD, ERROR_DESC_BAD_USERNAME_OR_PASSWORD);
                error_log("HandleSyncAuthentication:getSessionToken: failed to locate user: " . $_POST['username']);
                return;
            }
            
            // We need to check the subscription information first.  If we
            // login, it gives the user a session even if we return an error so
            // the next time they get in.  This way it will fail before login
            // because the subscription is invalid.
//            $subscriptionLevel = TDOSubscription::getSubscriptionLevelForUserID($userId);
//            if ($subscriptionLevel < SUBSCRIPTION_LEVEL_TRIAL)
//            {
//                outputSyncError(ERROR_CODE_EXPIRED_SUBSCRIPTION, ERROR_DESC_EXPIRED_SUBSCRIPTION);
//                error_log("HandleSyncAuthentication:getSessionToken: failed because user's subscription is expired: " . $_POST['username']);
//                return;
//            }
            
            $retVal = $session->login($_POST['username'], $_POST['password']);
            if(!empty($retVal['error']))
            {
                $error = $retVal['error'];
                if($error['id'] == 0)
                {
                    outputSyncError(ERROR_CODE_USER_BEING_MIGRATED, ERROR_DESC_USER_BEING_MIGRATED);
                    error_log("HandleSyncAuthentication:getSessionToken: user is being migrated: " . $_POST['username']);
                    return;
                }
                else if($error['id'] == 1)
                {
                    //TODO: swap these messages once we release 6.0.3 on iOS
                    outputSyncError(ERROR_CODE_USER_BEING_MIGRATED, ERROR_DESC_USER_BEING_MIGRATED);
//                    outputSyncError(ERROR_CODE_USER_MAINTENANCE, ERROR_DESC_USER_MAINTENANCE);

                    error_log("HandleSyncAuthentication:getSessionToken: user is being maintained: " . $_POST['username']);
                    return;
                }
                else
                {
                    outputSyncError(ERROR_CODE_BAD_USERNAME_OR_PASSWORD, ERROR_DESC_BAD_USERNAME_OR_PASSWORD);
                    error_log("HandleSyncAuthentication:getSessionToken: login failed for username: " . $_POST['username']);
                    return;
                }
            }
        }
        elseif( isset($_POST['accesstoken']))
        {
            //Verify the access token with facebook
            $result = getUserDataForFacebookAccessToken($_POST['accesstoken']);
            if(empty($result))
            {
                outputSyncError(ERROR_CODE_FACEBOOK_LOGIN_FAILED, ERROR_DESC_FACEBOOK_LOGIN_FAILED);
                return;
            }
            $fbId = $result['id'];
            
            $user = TDOUser::getUserForFacebookId($fbId);
            
            if(!empty($user))
            {
                $userId = $user->userId();
            
                // We need to check the subscription information first.  If we
                // login, it gives the user a session even if we return an error so
                // the next time they get in.  This way it will fail before login
                // because the subscription is invalid.
                
//                $subscriptionLevel = TDOSubscription::getSubscriptionLevelForUserID($userId);
//                if ($subscriptionLevel < SUBSCRIPTION_LEVEL_TRIAL)
//                {
//                    outputSyncError(ERROR_CODE_EXPIRED_SUBSCRIPTION, ERROR_DESC_EXPIRED_SUBSCRIPTION);
//                    error_log("HandleSyncAuthentication:getSessionToken: failed because user's subscription is expired: " . $_POST['username']);
//                    return;
//                }
            }
            else
            {
                //If the client passed a userid, we should try to link the facebook account to that account
                if(isset($_POST['userid']))
                {
                    //First, we better make sure the user is logged in as this user before allowing them to link
                    //a random FB account to this acccount
                    if($session->isLoggedIn())
                    {
                        if($session->getUserId() == $_POST['userid'])
                        {
                            $user = TDOUser::getUserForUserId($_POST['userid']);
                            
                            if(empty($user))
                            {
                                outputSyncError(ERROR_CODE_OBJECT_NOT_FOUND, ERROR_DESC_OBJECT_NOT_FOUND);
                                return;
                            }
                            //If the user has a different facebook id, output an error
                            if($user->oauthUID() != NULL && $user->oauthUID() != $fbId)
                            {
                                outputSyncError(ERROR_CODE_LINKED_TO_OTHER_FACEBOOK, ERROR_DESC_LINKED_TO_OTHER_FACEBOOK);
                                return;
                            }
                            
                            //Once we get to this point, we can link the Facebook account and log the user in
                            if(TDOUser::linkUserToFacebookId($user->userId(), $fbId) == false)
                            {
                                outputSyncError(ERROR_CODE_ERROR_UPDATING_USER, ERROR_DESC_ERROR_UPDATING_USER);
                                return;
                            }
                        }
                        else
                        {
                            outputSyncError(ERROR_CODE_ACCESS_DENIED, ERROR_DESC_ACCESS_DENIED);
                            return;
                        }
                    }
                    else
                    {
                        outputSyncError(ERROR_CODE_USER_NOT_AUTHENTICATED, ERROR_DESC_USER_NOT_AUTHENTICATED);
                        return;
                    }
                }
                else
                {
                    outputSyncError(ERROR_CODE_FACEBOOK_USER_NOT_FOUND, ERROR_DESC_FACEBOOK_USER_NOT_FOUND);
                    return;
                }
            }
            
            if($session->setupFacebookSyncSession($user, $result) == false)
            {
                outputSyncError(ERROR_CODE_FACEBOOK_LOGIN_FAILED, ERROR_DESC_FACEBOOK_LOGIN_FAILED);
                error_log("HandleSyncAuthentication:getSessionToken: failed to log in facebook user");
                return;
            }
            
        }
        else
        {
            outputSyncError(ERROR_CODE_MISSING_REQUIRED_PARAMETERS, ERROR_DESC_MISSING_REQUIRED_PARAMETERS);
            error_log("HandleSyncAuthentication:getSessionToken: missing parameters");
            return;
        }
        
        //Check for required parameters: apikey, devicetype, osversion, appid, appversion
        if(isset($_POST['apikey']) && isset($_POST['deviceid']) && isset($_POST['devicetype']) && isset($_POST['osversion']) && isset($_POST['appid']) && isset($_POST['appversion']))
        {
            //TODO: what are we going to do with these?
//                error_log("Found api key: ".$_POST['apikey']);
        }
        else
        {
            outputSyncError(ERROR_CODE_MISSING_REQUIRED_PARAMETERS, ERROR_DESC_MISSING_REQUIRED_PARAMETERS);
            error_log("HandleSyncAuthentication:getSessionToken: missing parameters");
            return;
        }
        
        if($session->isLoggedIn() == true)
        {
            $responseArray = responseArrayForSession($session);
            if(!empty($user))
            {
                $responseArray['username'] = $user->username();
            }

            // log the device data to the database
            TDODevice::updateOrAddDevice($userId, $_POST['deviceid'], $session->getSessionId(), $_POST['devicetype'], $_POST['osversion'], $_POST['appid'], $_POST['appversion']);
            
            echo json_encode($responseArray);
        }
        else
        {
            outputSyncError(ERROR_CODE_CREATING_SESSION_FAILED, ERROR_DESC_CREATING_SESSION_FAILED);
        }

    }
    else if($method == "createFacebookUser")
    {
        if(isset($_POST['accesstoken']))
        {
            //Verify the access token with facebook
            $result = getUserDataForFacebookAccessToken($_POST['accesstoken']);
            if(empty($result))
            {
                outputSyncError(ERROR_CODE_FACEBOOK_LOGIN_FAILED, ERROR_DESC_FACEBOOK_LOGIN_FAILED);
                return;
            }
            $fbId = $result['id'];

            //If this user already exists, fail!
            if(TDOUser::existsFacebookUser($fbId))
            {
                outputSyncError(ERROR_CODE_FACEBOOK_USER_EXISTS, ERROR_DESC_FACEBOOK_USER_EXISTS);
                return;
            }
            
            $user = new TDOUser();
            $user->setUsername($result['email']);
            $user->setFirstName($result['first_name']);
            $user->setLastName($result['last_name']);
            $user->setOauthUID($result['id']);
            if(isset($_POST['emailoptin']))
            {
                if($_POST['emailoptin'] == "0")
                    $user->setEmailOptOut(1);
            }
           
            if($user->addFacebookUser())
            {
                $session->setupFacebookSyncSession($user, $result);

                if($session->isLoggedIn() == true)
                {
                    $responseArray = responseArrayForSession($session);
                    $responseArray['firstname'] = $user->firstName();
                    $responseArray['lastname'] = $user->lastName();
                    $responseArray['username'] = $user->username();
                    echo json_encode($responseArray);                        
                }
                else
                {
                    outputSyncError(ERROR_CODE_USER_NOT_AUTHENTICATED, ERROR_DESC_USER_NOT_AUTHENTICATED);
                    error_log("HandleSyncAuthentication:createUser: unable to login the user after creating them: " . $_POST['username']);
                }
            }
            else
            {
                outputSyncError(ERROR_CODE_ERROR_CREATING_USER, ERROR_DESC_ERROR_CREATING_USER);
                error_log("HandleSyncAuthentication:createUser: unknown error creating the user: " . $result['email']);
                return;
            }
        
        }
        else
        {
            outputSyncError(ERROR_CODE_MISSING_REQUIRED_PARAMETERS, ERROR_DESC_MISSING_REQUIRED_PARAMETERS);
        }
    }
    else if($method == "createUser")
    {
        if(isset($_POST['username']) || isset($_POST['password']))
        {
            // first check to see if this user has a valid subscription
            $userId = TDOUser::userIdForUserName($_POST['username']);
            if(empty($userId))
            {
                // try to migrate the user from the previous system
                
                error_log("No user was found on the server, trying to migrate");
                
                $retVal = $session->login($_POST['username'], $_POST['password']);
                if(!empty($retVal['error']))
                {
                    $error = $retVal['error'];
                    if($error['id'] == 0)
                    {
                        outputSyncError(ERROR_CODE_USER_BEING_MIGRATED, ERROR_DESC_USER_BEING_MIGRATED);
                        error_log("HandleSyncAuthentication:getSessionToken: user is being migrated: " . $_POST['username']);
                        return;
                    }
                    if($error['id'] == 1)
                    {
                        //TODO: swap these messages once we release 6.0.3 on iOS
                        outputSyncError(ERROR_CODE_USER_BEING_MIGRATED, ERROR_DESC_USER_BEING_MIGRATED);
//                      outputSyncError(ERROR_CODE_USER_MAINTENANCE, ERROR_DESC_USER_MAINTENANCE);

                        error_log("HandleSyncAuthentication:getSessionToken: user is being maintained: " . $_POST['username']);
                        return;
                    }
                    
                    // outputSyncError($error['id'], $error['msg']);
                    error_log("TDOLegacy->login returned the error: ".$error['id'].":".$error['msg']. " for user "  . $_POST['username']);
                    // return;
                }
                
                // CRG Removing Legacy (Todo online) support
//                else
//                {
//                    // check to see if there is a Todo Online account
//                    $tdoLegacy = new TDOLegacy();
//                    $legacyres = $tdoLegacy->authUser($username, $password);
//                    if(empty($legacyres['error']))
//                    {
//                        if(!empty($legacyres['user']))
//                        {
//                            outputSyncError(ERROR_CODE_ERROR_USERNAME_ALREADY_EXISTS, ERROR_DESC_ERROR_USERNAME_ALREADY_EXISTS);
//                            error_log("HandleSyncAuthentication:createUser: username already exists for the user: " . $_POST['username']);
//                            return;
//                        }
//                    }                    
//
//                    error_log("login returned no errro so we're going to just create the user" . $_POST['username']);
//                }

                
                $user = new TDOUser();
            
                $username = $_POST['username'];
                if(strlen($username) > USER_NAME_LENGTH)
                {
                    outputSyncError(ERROR_CODE_ERROR_CREATING_USER, ERROR_DESC_ERROR_CREATING_USER);
                    error_log("HandleSyncAuthentication:createUser: username is too long " . $_POST['username']);
                    return;
                }
                $user->setUsername($_POST['username']);
                
                $password = $_POST['password'];
				$password = trim($password);
                if(strlen($password) > PASSWORD_LENGTH)
                {
                    outputSyncError(ERROR_CODE_ERROR_CREATING_USER, ERROR_DESC_ERROR_CREATING_USER);
                    error_log("HandleSyncAuthentication:createUser: password is too long " . $_POST['username']);
                    return;
                }
				else if(strlen($password) < PASSWORD_MIN_LENGTH)
				{
					outputSyncError(ERROR_CODE_ERROR_CREATING_USER, ERROR_DESC_ERROR_CREATING_USER);
					error_log("HandleSyncAuthentication:createUser: password is too short " . $_POST['username']);
					return;
				}
				
                $user->setPassword($_POST['password']);
                
                if(isset($_POST['firstname']))
                    $user->setFirstName($_POST['firstname']);
                if(isset($_POST['lastname']))
                    $user->setLastName($_POST['lastname']);
                
                if(isset($_POST['emailoptin']))
                {
                    if($_POST['emailoptin'] == "0")
                        $user->setEmailOptOut(1);
                }
				//Convert locale name from 'en-US' to 'en_US'
				if(isset($_POST['locale']))
				{
                    $newLocale= str_replace('-', '_', $_POST['locale']);
                    $user->setLocale(str_replace('-', '_', $_POST['locale']));
				}
				//Find best available locale.
				if(isset($_POST['locale-best-match']))
				{
					$user->setBestMatchLocale(TDOInternalization::getUserBestMatchLocale($_POST['locale-best-match']));
				}
				if(isset($_POST['locale-selected']))
				{
					$user->setselectedLocale(TDOInternalization::getUserBestMatchLocale($_POST['locale-selected']));
				}

                if($user->addUser())
                {
                    $session->login($_POST['username'], $_POST['password']);

                    if($session->isLoggedIn() == true)
                    {
                        $responseArray = responseArrayForSession($session);
                        echo json_encode($responseArray);                        
                    }
                    else
                    {
                        outputSyncError(ERROR_CODE_USER_NOT_AUTHENTICATED, ERROR_DESC_USER_NOT_AUTHENTICATED);
                        error_log("HandleSyncAuthentication:createUser: unable to login the user after creating them: " . $_POST['username']);
                    }
                }
                else
                {
                    outputSyncError(ERROR_CODE_ERROR_CREATING_USER, ERROR_DESC_ERROR_CREATING_USER);
                    error_log("HandleSyncAuthentication:createUser: unknown error creating the user: " . $_POST['username']);
                }
            }
            else
            {
                outputSyncError(ERROR_CODE_ERROR_USERNAME_ALREADY_EXISTS, ERROR_DESC_ERROR_USERNAME_ALREADY_EXISTS);
                error_log("HandleSyncAuthentication:createUser: username already exists for the user: " . $_POST['username']);
            }
        }
        else
            outputSyncError(ERROR_CODE_MISSING_REQUIRED_PARAMETERS, ERROR_DESC_MISSING_REQUIRED_PARAMETERS);
    }
    else if($method == "getSyncInformation")
	{
        if($session->isLoggedIn() == true)
        {
			$user = NULL;
			$shouldUpdateUser = false;
			
			// Since locale is something new we're using, update the user's
			// locale with the latest they're using.
			if (isset($_POST['locale']))
			{
				$newLocale = $_POST['locale'];
                $newLocale= str_replace('-', '_', $newLocale);

				// Check to see if we need to update the user's locale info
				$userId = $session->getUserId();
				$currentLocale = TDOUser::getLocaleForUserId($userId);
				
				if (empty($currentLocale) || $currentLocale != $newLocale) {
					$user = TDOUser::getUserForUserId($userId);
					$user->setLocale($newLocale);
					$shouldUpdateUser = true;
				}
			}
			
			if (isset($_POST['locale-best-match']))
			{
				$newBestMatchLocale = $_POST['locale-best-match'];
				$newBestMatchLocale = TDOInternalization::getUserBestMatchLocale($newBestMatchLocale);

				$userId = $session->getUserID();
				$currentBestMatchLocale = TDOUser::getBestMatchLocaleForUserId($userId);
				
				if (empty($currentBestMatchLocale) || $currentBestMatchLocale != $newBestMatchLocale) {
					if (empty($user)) {
						$user = TDOUser::getUserForUserId($userId);
					}
					$user->setBestMatchLocale($newBestMatchLocale);
					$shouldUpdateUser = true;
				}
			}
			
			if ($shouldUpdateUser) {
				$user->updateUser();
			}
			
            $responseArray = responseArrayForSession($session);
        
            echo json_encode($responseArray);             
			
            TDODevice::updateDeviceForUserAndSession($session->getUserId(), $session->getSessionId(), 0, NULL);
		}
        else
        {
            outputSyncError(ERROR_CODE_USER_NOT_AUTHENTICATED, ERROR_DESC_USER_NOT_AUTHENTICATED);
        }
    }
    else if($method == "updateEmailOptout")
    {
        if($session->isLoggedIn() == true)
        {
            if(isset($_POST['emailoptout']))
            {
                $user = TDOUser::getUserForUserId($session->getUserId());
                if(!empty($user))
                {
                    $option = intval($_POST['emailoptout']);
                    $user->setEmailOptOut($option);
                    
                    if($user->updateUser())
                    {
                        echo '{"success":true}';
                    }
                    else
                    {
                        outputSyncError(ERROR_CODE_ERROR_UPDATING_USER, ERROR_DESC_ERROR_UPDATING_USER);
                    }
                }
                else
                {
                    outputSyncError(ERROR_CODE_OBJECT_NOT_FOUND, ERROR_DESC_OBJECT_NOT_FOUND);
                }
            }
            else
            {
                outputSyncError(ERROR_CODE_MISSING_REQUIRED_PARAMETERS, ERROR_DESC_MISSING_REQUIRED_PARAMETERS);
            }
        }
        else
        {
            outputSyncError(ERROR_CODE_USER_NOT_AUTHENTICATED, ERROR_DESC_USER_NOT_AUTHENTICATED);
        }
    }
//    else if($method == "linkFacebookAccount")
//    {
//        if(!isset($_POST['accesstoken']))
//        {
//            outputSyncError(ERROR_CODE_MISSING_REQUIRED_PARAMETERS, ERROR_DESC_MISSING_REQUIRED_PARAMETERS);
//            return;
//        }
//    
//        if($session->isLoggedIn() == true)
//        {
//            $result = TDOFBUtil::curl_get_file_contents('https://graph.facebook.com/me?access_token='.$_POST['accesstoken']);
//            if($result == false)
//            {
//                outputSyncError(ERROR_CODE_FACEBOOK_LOGIN_FAILED,ERROR_DESC_FACEBOOK_LOGIN_FAILED);
//                error_log("HandleSyncAuthentication:getSessionToken: failed to connect to facebook");
//                return;
//            }
//            $result = json_decode($result, true);
//            
//            if(isset($result['error']) || !isset($result['id']))
//            {
//                outputSyncError(ERROR_CODE_FACEBOOK_LOGIN_FAILED, ERROR_DESC_FACEBOOK_LOGIN_FAILED);
//                error_log("HandleSyncAuthentication:getSessionToken: failed to verify access token with facebook");
//                return;
//            }
//            $fbId = $result['id'];
//            
//            $user = TDOUser::getUserForUserId($session->getUserId());
//            
//            //If this already matches the user's facebook id, just return true
//            if($fbId == $user->oauthUID())
//            {
//                echo '{"success":true}';
//                return;
//            }
//            
//            //If the user has a different facebook id, output an error
//            if($user->oauthUID() != NULL && $user->oauthUID() != $fbId)
//            {
//                outputSyncError(ERROR_CODE_LINKED_TO_OTHER_FACEBOOK, ERROR_DESC_LINKED_TO_OTHER_FACEBOOK);
//                return;
//            }
//            
//            //If this facebook id is associated with a different Todo Cloud account, return an error
//            if(TDOUser::existsFacebookUser($fbId) == true)
//            {
//                outputSyncError(ERROR_CODE_FACEBOOK_LINKED_TO_OTHER_ACCOUNT, ERROR_DESC_FACEBOOK_LINKED_TO_OTHER_ACCOUNT);
//                return;
//            }
//            
//            //Make sure the access token came from one of our apps
//            $verifyAppResult = TDOFBUtil::curl_get_file_contents('https://graph.facebook.com/app?access_token='.$_POST['accesstoken']);
//            if($verifyAppResult == false)
//            {
//                outputSyncError(ERROR_CODE_FACEBOOK_LOGIN_FAILED, ERROR_DESC_FACEBOOK_LOGIN_FAILED);
//                error_log("HandleSyncAuthentication:getSessionToken: failed to verify access token came from our app");
//                return;
//            }
//            $verifyAppResult = json_decode($verifyAppResult, true);
//            if(isset($verifyAppResult['error']) || !isset($verifyAppResult['id']))
//            {
//                outputSyncError(ERROR_CODE_FACEBOOK_LOGIN_FAILED, ERROR_DESC_FACEBOOK_LOGIN_FAILED);
//                error_log("HandleSyncAuthentication:getSessionToken: failed to verify access token came from our app");
//                return;
//            }
//            
//            if($verifyAppResult['id'] != FB_PLANO_APP_ID && $verifyAppResult['id'] != FB_PILOT_APP_ID)
//            {
//                outputSyncError(ERROR_CODE_FACEBOOK_LOGIN_FAILED, ERROR_DESC_FACEBOOK_LOGIN_FAILED);
//                error_log("HandleSyncAuthentication:getSessionToken: failed to verify access token came from our app");
//                return;
//            }
//            
//            //Once we get to this point, we can link the Facebook account
//            if(TDOUser::linkUserToFacebookId($user->userId(), $fbId))
//            {
//                echo '{"success":true}';
//            }
//            else
//            {
//                outputSyncError(ERROR_CODE_ERROR_UPDATING_USER, ERROR_DESC_ERROR_UPDATING_USER);
//            }
//
//        }
//        else
//        {
//            outputSyncError(ERROR_CODE_USER_NOT_AUTHENTICATED, ERROR_DESC_USER_NOT_AUTHENTICATED);
//        }
//    }
    


function getUserDataForFacebookAccessToken($accessToken)
{
    //Verify the access token with facebook
    $result = TDOFBUtil::curl_get_file_contents('https://graph.facebook.com/me?access_token='.$accessToken);
    if($result == false)
    {
        outputSyncError(ERROR_CODE_FACEBOOK_LOGIN_FAILED,ERROR_DESC_FACEBOOK_LOGIN_FAILED);
        error_log("HandleSyncAuthentication:getSessionToken: failed to connect to facebook");
        return NULL;
    }
    $result = json_decode($result, true);
    
    if( ($result === NULL) || empty($result) )
    {
        error_log("HandleSyncAuthentication:getSessionToken: failed to parse data");
        return NULL;
    }
    
    
    if(isset($result['error']) || !isset($result['id']) || !isset($result['first_name']) || !isset($result['last_name']) || !isset($result['email']))
    {
        error_log("HandleSyncAuthentication:getSessionToken: failed to verify access token with facebook");
        return NULL;
    }
    
    //Make sure the access token came from one of our apps
    $verifyAppResult = TDOFBUtil::curl_get_file_contents('https://graph.facebook.com/app?access_token='.$accessToken);
    if($verifyAppResult == false)
    {
        error_log("HandleSyncAuthentication:getSessionToken: failed to verify access token came from our app");
        return NULL;
    }
    $verifyAppResult = json_decode($verifyAppResult, true);
    if(isset($verifyAppResult['error']) || !isset($verifyAppResult['id']))
    {
        error_log("HandleSyncAuthentication:getSessionToken: failed to verify access token came from our app");
        return NULL;
    }
    
    if($verifyAppResult['id'] != FB_PLANO_APP_ID && $verifyAppResult['id'] != FB_PILOT_APP_ID)
    {
        error_log("HandleSyncAuthentication:getSessionToken: failed to verify access token came from our app");
        return NULL;
    }
    
    return $result;
}

function responseArrayForSession($session)
{
    $responseArray = array();

    $userId = $session->getUserId();
    $sessionId = $session->getSessionId();
    
    $lists = TDOList::getListsForUser($userId);
	
	$smartListHash = TDOSmartList::smartListHashForUser($session->getUserId());
    $listTimeStamp = TDOList::listHashForUser($session->getUserId());
    $userTimeStamp = TDOUser::userHashForUser($session->getUserId());
    $allTaskTimeStamps = TDOTask::getAllTaskTimestampsForUser($session->getUserId(), $lists);
	$listMembershipHashes = TDOList::getListMembershipHashesForUser($session->getUserId(), $lists);
    $contextTimeStamp = TDOContext::getContextTimestampForUser($session->getUserId());
    $allNotificationTimeStamps = TDOTaskNotification::getAllNotificationTimestampsForUser($session->getUserId(), $lists);
    $allTaskitoTimeStamps = TDOTaskito::getAllTaskitoTimestampsForUser($session->getUserId(), $lists);
    $subscriptionInfo = $session->getSubscriptionInfo();
    $pendingInvitations = TDOInvitation::getInvitationCountForInvitedUser($session->getUserId());
    
    $user = TDOUser::getUserForUserId($session->getUserId());
    $lastResetTimestamp = $user->lastResetTimestamp();

    $responseArray['sessionToken'] = $sessionId;
    $responseArray['userid'] = $userId;
    $responseArray['emailConfirmed'] = $user->emailVerified();
    $responseArray['protocolVersion'] = CURRENT_SYNC_PROTOCOL_VERSION;
	$responseArray['smartListHash'] = $smartListHash;
    $responseArray['listHash'] = $listTimeStamp;
    $responseArray['userHash'] = $userTimeStamp;
    $responseArray['alltasktimestamps'] = $allTaskTimeStamps;
	$responseArray['listMembershipHashes'] = $listMembershipHashes;
    if($contextTimeStamp != false)
        $responseArray['contexttimestamp'] = $contextTimeStamp;
    $responseArray['allnotificationtimestamps'] = $allNotificationTimeStamps;
    $responseArray['alltaskitotimestamps'] = $allTaskitoTimeStamps;
    
    if($lastResetTimestamp)
        $responseArray['lastresetdatatimestamp'] = $lastResetTimestamp;
    
    if (!empty($subscriptionInfo))
    {
        $responseArray['subscriptionLevel'] = $subscriptionInfo['subscriptionLevel'];
        $responseArray['subscriptionExpirationDate'] = $subscriptionInfo['subscriptionExpirationDate'];
        $responseArray['subscriptionExpirationSecondsFromNow'] = $subscriptionInfo['subscriptionExpirationSecondsFromNow'];
        $responseArray['subscriptionPaymentService'] = $subscriptionInfo['subscriptionPaymentService'];
		$responseArray['subscriptionPaymentServiceV2'] = $subscriptionInfo['subscriptionPaymentServiceV2'];
		$responseArray['subscriptionTeamExpirationDate'] = $subscriptionInfo['subscriptionTeamExpirationDate'];
		$responseArray['subscriptionTeamExpirationSecondsFromNow'] = $subscriptionInfo['subscriptionTeamExpirationSecondsFromNow'];
        $responseArray['secondsToRetrySyncForExpiredSubscription'] = EXPIRED_SUBSCRIPTION_RETRY_SYNC_INTERVAL;
		
		$responseArray['subscriptionTeamName'] = $subscriptionInfo['subscriptionTeamName'];
		$responseArray['subscriptionTeamAdminName'] = $subscriptionInfo['subscriptionTeamAdminName'];
		$responseArray['subscriptionTeamBillingAdminEmail'] = $subscriptionInfo['subscriptionTeamBillingAdminEmail'];
		$responseArray['subscriptionUserDisplayName'] = $subscriptionInfo['subscriptionUserDisplayName'];
		
    }
    
    $systemNotification = TDOSystemNotification::getCurrentSystemNotification();
    if(!empty($systemNotification))
    {
        $responseArray['systemNotificationId'] = $systemNotification->notificationId();
        $responseArray['systemNotificationMessage'] = $systemNotification->message();
        $responseArray['systemNotificationTimestamp'] = $systemNotification->timestamp();
        if($systemNotification->learnMoreUrl() != NULL)
            $responseArray['systemNotificationLearnMoreUrl'] = $systemNotification->learnMoreUrl();
    }
    
    // this is how many outstanding invitations are waiting for this user
    if($pendingInvitations != false)
        $responseArray['pendinginvitations'] = $pendingInvitations;
    else
        $responseArray['pendinginvitations'] = 0;
    
    return $responseArray;
}
?>

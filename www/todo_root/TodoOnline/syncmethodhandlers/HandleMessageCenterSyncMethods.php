<?php
	
	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/syncmethodhandlers/MessageCenterConstants.php'); 
	include_once('TodoOnline/messageCenter/mc_base_sdk.php');
    
    //PARAMETERS:
    //protocol_version - (required) the message center sync protocol version the client is using
    //userid - (userid or deviceid required) the user's Todo Cloud userid. If this is set, the user must be logged in.
    //deviceid - (userid or deviceid required) the user's device id. If userid is not set, this must be set.
    //apikey - (required if userid not set) an md5 hash of 47<userid><MESSAGE_CENTER_API_KEY_SECRET>47
    //timestamp - (optional) the time that the user wants all messages since. If not set this defaults to 0.
    //devicetype - (required) the type of device being used (e.g. iPhone, iPad, etc)
    //osversion - (required) the operating system version of the client
    //appid - (required) the app identifier of the client
    //appversion - (required) the version of the app that the client is currently running
    
    if($method == "syncMessagesForUser")
    {
        //Before doing anything, check the protocol version of the client
        if(!isset($_POST['protocol_version']))
        {
            error_log("syncMessagesForUser called missing required parameter: protocol_version");
            outputMCSyncError(MC_ERROR_CODE_MISSING_PARAMETER, MC_ERROR_DESC_MISSING_PARAMETER);
            return;
        }
    
        $protocolVersion = floatval($_POST['protocol_version']);
        if($protocolVersion < REQUIRED_PROTOCOL_VERSION)
        {
            error_log("syncMessagesForUser called with outdated protocol version: ".$protocolVersion);
            outputMCSyncError(MC_ERROR_CODE_PROTOCOL_VERSION_NOT_SUPPORTED, MC_ERROR_DESC_PROTOCOL_VERSION_NOT_SUPPORTED);
            return;
        }
    
        if(!isset($_POST['timestamp']))
            $timestamp = 0;
        else
            $timestamp = intval($_POST['timestamp']);
        
        $userid = NULL;
        
        //If they sent a Todo Cloud userid, use that, otherwise use the device id as the userid
        $isTodoProUser = false;
        if(isset($_POST['userid']))
        {
            if($session->isLoggedIn() == false || $session->getUserId() != $_POST['userid'])
            {
                error_log("syncMessagesForUser called for Todo Cloud user that is not authenticated");
                outputMCSyncError(MC_ERROR_CODE_USER_NOT_AUTHENTICATED, MC_ERROR_DESC_USER_NOT_AUTHENTICATED);
                return;
            }
            
            $userid = $_POST['userid'];
            $isTodoProUser = true;
        }
        else if(isset($_POST['deviceid']))
        {
            $userid = $_POST['deviceid'];
            
            //If we're using the device id instead of the userid, the user doesn't have to be logged in,
            //so require an api key for added security
            if(!isset($_POST['apikey']))
            {
                error_log("syncMessagesForUser called and missing a required parameter: apikey");
                outputMCSyncError(MC_ERROR_CODE_MISSING_PARAMETER, MC_ERROR_DESC_MISSING_PARAMETER);
                return;
            }
            
            $apiKey = $_POST['apikey'];
            
            // Validate the secret API Key which is an MD5 hash of:
            // "47" + <userid> + <MESSAGE_CENTER_API_KEY_SECRET> + "47"
            $preHash = "47" . $userid . MESSAGE_CENTER_API_KEY_SECRET . "47";
            $calculatedMD5 = md5($preHash);
            
            if ($calculatedMD5 != $apiKey)
            {
                
                error_log("syncMessagesForUser called by unauthorized service");
                outputMCSyncError(MC_ERROR_CODE_CLIENT_UNAUTHORIZED, MC_ERROR_DESC_CLIENT_UNAUTHORIZED);
                return;
            }
        }
        else
        {
            error_log("syncMessagesForUser called missing userid or deviceid");
            outputMCSyncError(MC_ERROR_CODE_MISSING_PARAMETER, MC_ERROR_DESC_MISSING_PARAMETER);
            return;
        }

        if(!isset($_POST['devicetype']) || !isset($_POST['osversion']) || !isset($_POST['appid']) || !isset($_POST['appversion']) || !isset($_POST['syncservice']))
        {
            error_log("syncMessagesForUser called missing devicetype or osversion or appid or appversion or syncservice");
            outputMCSyncError(MC_ERROR_CODE_MISSING_PARAMETER, MC_ERROR_DESC_MISSING_PARAMETER);
            return;
        }
        
        $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        $userInfo = MCUserInfo::getUserInfoForUserId($userid, $dynamoDBClient);
        
        if(empty($userInfo))
        {
            error_log("syncMessageForUser failed to get userinfo for user");
            outputMCSyncError(MC_ERROR_CODE_GET_USER_INFO_FAILED, MC_ERROR_DESC_GET_USER_INFO_FAILED);
            return;
        }
        
        $updateResults = array();
        $userModifiedMessages = array();
        
        //First, handle the updates sent from the client (if any)
        if(isset($_POST['client_updates']))
        {
            $clientUpdates = json_decode($_POST['client_updates'], true);
            
            $newModDate = time() * 1000;
            $newModDate += count($clientUpdates);
            
            foreach($clientUpdates as $clientUpdate)
            {
                $updateClientArray = array();
                
                if(!isset($clientUpdate['messageid']))
                {
                    // if we don't have a messageid we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    $updateClientArray['errorcode'] = MC_ERROR_CODE_MISSING_PARAMETER;
                    $updateClientArray['errordesc'] = MC_ERROR_DESC_MISSING_PARAMETER . " messageid was missing.";
                    error_log("message sync error ".MC_ERROR_CODE_MISSING_PARAMETER." error desc ".MC_ERROR_DESC_MISSING_PARAMETER." messageid was missing.");
                    $updateResults[] = $updateClientArray;
                    continue;
                }
                
                $messageId = $clientUpdate['messageid'];
                $updateClientArray['messageid'] = $messageId;
                
                //It doesn't matter what we pass for the language code since we're not reading the message
                $oldMessage = MCUserMessage::getUserMessageForUserIdAndMessageId($userid, $messageId, false, LANGUAGE_CODE_ENGLISH, $dynamoDBClient);
                if(empty($oldMessage))
                {
                    $updateClientArray['errorcode'] = MC_ERROR_CODE_MESSAGE_NOT_FOUND;
                    $updateClientArray['errordesc'] = MC_ERROR_DESC_MESSAGE_NOT_FOUND;
                    error_log("message sync error ".MC_ERROR_CODE_MESSAGE_NOT_FOUND." error desc ".MC_ERROR_DESC_MESSAGE_NOT_FOUND."  unable to locate task from taskid.");
                    $updateResults[] = $updateClientArray;
                    continue;
                }
                
                $updateNeeded = false;
                
                $newMessage = clone $oldMessage;
                if(isset($clientUpdate['read']))
                {
                    $isRead = intval($clientUpdate['read']);
                    if($isRead != $oldMessage->read())
                    {
                        $newMessage->setRead($isRead);
                        $updateNeeded = true;
                    }
                }
                
                if(isset($clientUpdate['deleted']))
                {
                    $isDeleted = intval($clientUpdate['deleted']);
                    if($isDeleted != $oldMessage->deleted())
                    {
                        $newMessage->setDeleted($isDeleted);
                        $updateNeeded = true;
                    }
                }
                $newMessage->setModDate($newModDate);
                
                if($updateNeeded)
                {
                    if(MCUserMessage::updateUserMessage($oldMessage, $newMessage, $isTodoProUser, $dynamoDBClient, $userInfo, false) == true)
                    {
                        $userModifiedMessages[$messageId] = $newMessage->modDate();
                    }
                    else
                    {
                        $updateClientArray['errorcode'] = MC_ERROR_CODE_ERROR_UPDATING_MESSAGE;
                        $updateClientArray['errordesc'] = MC_ERROR_DESC_ERROR_UPDATING_MESSAGE;
                        error_log("message sync error ".MC_ERROR_CODE_ERROR_UPDATING_MESSAGE." error desc ".MC_ERROR_DESC_ERROR_UPDATING_MESSAGE);
                        continue;
                    }
                }
                
                $updateResults[] = $updateClientArray;
                $newModDate--;
            }
        }
        
        
        $creationTimestamp = 0;
        if($isTodoProUser)
        {
            $user = TDOUser::getUserForUserId($userid);
            if($user)
            {
                $creationTimestamp = $user->creationTimestamp();
            }
        }
        

        $appId = trimAppId($_POST['appid']);
        $appVersion = $_POST['appversion'];
        
        if(MCMessageLookupHandler::processNewMessagesForUser($userid, $isTodoProUser, $creationTimestamp, $appId, $appVersion, $dynamoDBClient, $userInfo, false) == false)
        {
            error_log("syncMessagesForUser failed to process new messages for user");
            outputMCSyncError(MC_ERROR_CODE_SEND_MESSAGES_FAILED, MC_ERROR_DESC_SEND_MESSAGES_FAILED);
            return;
        }
        
        //Update the userinfo so it will contain the correct timestamps for when we last updated and modified messages
        $userInfo->addOrUpdateUserInfo();
        
        
        $deviceTypeVal = getDeviceTypeValForDeviceTypeAndAppId($_POST['devicetype'], $_POST['appid']);
        $syncServiceVal = getSyncServiceValueForIdentifier($_POST['syncservice']);
        
        $languageCode = LANGUAGE_CODE_ENGLISH;
        if(isset($_POST['language_code']))
            $languageCode = $_POST['language_code'];
        
        $response = MCUserMessageLookupHandler::getMessagesForUserModifiedSince($userid, $timestamp, $deviceTypeVal, $syncServiceVal, $languageCode, $dynamoDBClient);
        if($response == false || !isset($response['messages']) || !isset($response['time_read']))
        {
            error_log("syncMessagesForUser failed to get recently posted messages for user");
            outputMCSyncError(MC_ERROR_CODE_GET_MESSAGES_FAILED, MC_ERROR_DESC_GET_MESSAGES_FAILED);
            return;
        }
        
        $messages = $response['messages'];
        
        $jsonMessages = array();
        foreach($messages as $userMessage)
        {
            $messageId = $userMessage->messageId();
            
            //If this is an item the client just updated, skip it
            if(isset($userModifiedMessages[$messageId]) && $userModifiedMessages[$messageId] >= $userMessage->modDate())
                continue;
        
            $jsonMessage = $userMessage->getPropertiesArray();
            $message = $userMessage->message();
            if($message)
            {
                unset($jsonMessage['message']);
                $jsonMessage = array_merge($jsonMessage, $message->getPropertiesArray());
            }
            
            $jsonMessages[] = $jsonMessage;
        }
        
        $jsonResponse = array('messages' => $jsonMessages, 'last_read_messages_timestamp' => $response['time_read']);
        
        if(!empty($updateResults))
            $jsonResponse['results'] = $updateResults;
        
        echo json_encode($jsonResponse);
        
    }
    
    //This function takes the sync service identifier passed from the client
    //and returns the correct constant
    function getSyncServiceValueForIdentifier($syncServiceIdentifier)
    {
        $syncServiceVal = -1;
        if(strcasecmp($syncServiceIdentifier, CLIENT_TODOPRO_IDENTIFIER) == 0)
        {
            $syncServiceVal = SyncService::TodoPro;
        }
        else if(strcasecmp($syncServiceIdentifier, CLIENT_DROPBOX_IDENTIFIER) == 0)
        {
            $syncServiceVal = SyncService::Dropbox;
        }
        else if(strcasecmp($syncServiceIdentifier, CLIENT_APPITOSYNC_IDENTIFIER) == 0)
        {
            $syncServiceVal = SyncService::AppigoSync;
        }
        else if(strcasecmp($syncServiceIdentifier, CLIENT_ICLOUD_IDENTIFIER) == 0)
        {
            $syncServiceVal = SyncService::iCloud;
        }
        else if(strcasecmp($syncServiceIdentifier, CLIENT_TOODLEDO_IDENTIFIER) == 0)
        {
            $syncServiceVal = SyncService::Toodledo;
        }
        else if(strcasecmp($syncServiceIdentifier, CLIENT_NOSERVICE_IDENTIFIER) == 0)
        {
            $syncServiceVal = SyncService::NoSyncService;
        }
        
        return $syncServiceVal;
    }
    
    function getDeviceTypeValForDeviceTypeAndAppId($deviceType, $appId)
    {
        $deviceTypeVal = -1;
        if(stristr($deviceType, CLIENT_IPHONE_IDENTIFIER) !== false)
        {
            $deviceTypeVal = DeviceType::iPhone;
        }
        else if(stristr($deviceType, CLIENT_IPOD_IDENTIFIER) !== false)
        {
            $deviceTypeVal = DeviceType::iPod;
        }
        else if(stristr($deviceType, CLIENT_IPAD_IDENTIFIER) !== false)
        {
            $deviceTypeVal = DeviceType::iPad;
        }
        //For todo for android, use the app id because there are many possible devices
        else if(stristr($appId, CLIENT_ANDROID_IDENTIFIER) !== false)
        {
            $deviceTypeVal = DeviceType::Android;
        }
        //For todo for mac, use the app id because there are many possible devices
        else if(stristr($appId, CLIENT_MAC_IDENTIFIER) !== false)
        {
            $deviceTypeVal = DeviceType::Mac;
        }

        
        return $deviceTypeVal;
    }
    
    //Trims the app id down to the value we're really interested in by getting rid of the 'com.appigo'
    //and the 'two' if present
    function trimAppId($appId)
    {
        //Special case: the ID for todo for android is com.appigo.android.todopro,
        //so check for android in the app id and if it's present, return todoproandroid
        if(stristr($appId, CLIENT_ANDROID_IDENTIFIER) !== false)
        {
            return 'todoproandroid';
        }
    
        //Get the string after the last '.'
        $components = explode('.', $appId);
        if(!empty($components))
        {
            $appId = $components[count($components) - 1];
            $appId = str_replace('two', '', $appId);
            
            return $appId;
        }
    
        return NULL;
    }
    
    ?>
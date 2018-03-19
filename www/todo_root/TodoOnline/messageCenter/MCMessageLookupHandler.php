<?php
    
    include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/messageCenter/mc_base_sdk.php');
    
    use Aws\DynamoDb\Exception\DynamoDbException;
    
    define('SYSTEM_ALERT_LOOKUP_TABLE_NAME', 'mc_system_alert_lookup');
    define('SYSTEM_ALERT_KEY', '0');
    define('ACCOUNT_DURATION_LOOKUP_TABLE_NAME', 'mc_account_duration_message_lookup');
    define('UPGRADE_BASED_LOOKUP_TABLE_NAME', 'mc_upgrade_based_message_lookup');
    
    class MCMessageLookupHandler
    {
        //Returns requests that can be put in a batchWriteItems array to add lookup table entries for this message
        public static function requestsToAddLookupTableEntryForMessage($message)
        {
            if(empty($message))
                return false;
        
            if($message->messageType() == MessageType::SystemAlert)
            {
                return MCMessageLookupHandler::requestsToAddSystemAlertLookupForMessage($message);
            }
            else if($message->messageType() == MessageType::AccountDurationBased)
            {
                return MCMessageLookupHandler::requestsToAddAccountDurationLookupForMessage($message);
            }
            else if($message->messageType() == MessageType::UpgradeBased)
            {
                return MCMessageLookupHandler::requestsToAddUpgradeBasedLookupForMessage($message);
            }
            
            return false;
        }
        
        public static function lookupTableNameForMessage($message)
        {
            if($message->messageType() == MessageType::SystemAlert)
            {
                return SYSTEM_ALERT_LOOKUP_TABLE_NAME;
            }
            else if($message->messageType() == MessageType::AccountDurationBased)
            {
                return ACCOUNT_DURATION_LOOKUP_TABLE_NAME;
            }
            else if($message->messageType() == MessageType::UpgradeBased)
            {
                return UPGRADE_BASED_LOOKUP_TABLE_NAME;
            }
            
            return false;
        }
        
        //Returns requests that can be put in a batchWriteItems array to remove lookup table entries for this message
        public static function requestsToRemoveLookupTableEntryForMessage($message)
        {
            if(empty($message))
                return false;
        
            if($message->messageType() == MessageType::SystemAlert)
            {
                return MCMessageLookupHandler::requestsToRemoveSystemAlertLookupForMessage($message);
            }
            else if($message->messageType() == MessageType::AccountDurationBased)
            {
                return MCMessageLookupHandler::requestsToRemoveAccountDurationLookupForMessage($message);
            }
            else if($message->messageType() == MessageType::UpgradeBased)
            {
                return MCMessageLookupHandler::requestsToRemoveUpgradeBasedLookupForMessage($message);
            }
            
            return false;
        }
        
        //This method will look up all new messages that need to be sent to this user
        //and add them to the user message table. We can then query all of a user's recent
        //messages from the user message table. 
        public static function processNewMessagesForUser($userid, $isTodoProUser, $accountCreationDate, $appId, $appVersion, $dynamoDBClient=NULL, $userInfo=NULL, $updateUserInfo=true)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
        
            if(empty($userid) || empty($appId) || empty($appVersion))
            {
                error_log("processNewMessagesForUser missing parameter");
                return false;
            }
            
            if(empty($userInfo))
            {
                $userInfo = MCUserInfo::getUserInfoForUserId($userid, $dynamoDBClient);
            }
            
            if($userInfo == false)
            {
                error_log("processNewMessagesForUser failed to get userinfo");
                return false;
            }
            
            $currentTime = time();
            
            //Load System Alerts that have been posted since the last time we pulled messages
            $lastCheckedTimestamp = $userInfo->lastCheckedMessagesTimestamp();
            
            $allNewMessages = array();
            $newSystemAlertMessages = MCMessageLookupHandler::getActiveSystemAlertMessagesPostedSince($lastCheckedTimestamp, $dynamoDBClient);
            if($newSystemAlertMessages === false)
            {
                error_log("processNewMessagesForUser failed to get system alert messages");
                return false;
            }
            $allNewMessages = array_merge($allNewMessages, $newSystemAlertMessages);
            $userInfo->setLastCheckedMessagesTimestamp($currentTime);
            
            //Check account based alerts. If the current number of weeks we're at is greater than it was the last time we checked,
            //we need to pull all messages with that duration, otherwise only pull messages that have been added since the last
            //time we checked
            if($isTodoProUser)
            {
                $oldAccountCreationWeeks = $userInfo->todoProAccountDurationWeeks();
                $newAccountCreationWeeks = floor(($currentTime - $accountCreationDate)/(86400 * 7));
                
                $accountCreationTimestamp = $lastCheckedTimestamp;
                if($newAccountCreationWeeks > $oldAccountCreationWeeks)
                {
                    $accountCreationTimestamp = 0; 
                }

                $newAccountBasedMessages = MCMessageLookupHandler::getActiveMessagesForAccountDuration($newAccountCreationWeeks, $accountCreationTimestamp, $dynamoDBClient);
                if($newAccountBasedMessages === false)
                {
                    error_log("processNewMessagesForUser failed to get account based messages");
                    return false;
                }

                $allNewMessages = array_merge($allNewMessages, $newAccountBasedMessages);
                $userInfo->setTodoProAccountDurationWeeks($newAccountCreationWeeks);
            }
            
            //Check upgrade based alerts. If the user's current version is different from the latest version we have stored for them,
            //we need to pull all messages for the current version, otherwise only pull messages that have been added since the last
            //time we checked
            $upgradeTimestamp = 0;
            $lastAppVersions = $userInfo->appVersions();
            if(isset($lastAppVersions[$appId]))
            {
               if($lastAppVersions[$appId] == $appVersion)
               {
                    $upgradeTimestamp = $lastCheckedTimestamp;
               }
            }
            
            $versionKey = $appId."_".$appVersion;
            $newUpgradeBasedMessages = MCMessageLookupHandler::getActiveMessagesForVersionKey($versionKey, $upgradeTimestamp, $dynamoDBClient);
            if($newUpgradeBasedMessages === false)
            {
               error_log("processNewMessagesForUser failed to get upgrade based messages");
               return false;
            }
            $allNewMessages = array_merge($allNewMessages, $newUpgradeBasedMessages);
            $lastAppVersions[$appId] = $appVersion;
            $userInfo->setAppVersions($lastAppVersions);
            
            if(!empty($allNewMessages))
            {
                if(MCUserMessage::sendMessagesToUser($userid, $allNewMessages, $isTodoProUser, $dynamoDBClient) == false)
                {
                    error_log("processNewMessagesForUser failed to send messages to user");
                    return false;
                }
                $userInfo->setLastUpdatedUserMessages($currentTime);
            }
            
            
            if($updateUserInfo)
                $userInfo->addOrUpdateUserInfo();
            
            return true;
            
        }
        
        //This table is used to locate system-alert type messages that have been
        //posted within a certain date
        public static function createSystemAlertLookupTable($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array();
            $args['TableName'] = SYSTEM_ALERT_LOOKUP_TABLE_NAME;
            $args['KeySchema'] = array
                                 (
                                     //Right now this is just a dummy value (should always be 0) so that we
                                     //can use dynamoDB queries on the time_posted without having to do a full table scan.
                                     //Seems like a hack but it's the only way to get this to work besides partitioning the data based on
                                     //some hash of the date and querying across several partitions. We shouldn't really need to do that since
                                     //this table is not going to be very large (it only holds the active system alerts).
                                     "HashKeyElement" => array
                                                          (
                                                            "AttributeName" => "system_alert_key",
                                                            "AttributeType" => "N"
                                                          ),
                                     //Store the time the message was posted. This means means we can't have 2 system alerts
                                     //posted in the same second, but that shouldn't really be an issue
                                     "RangeKeyElement" => array
                                                          (
                                                            "AttributeName" => "time_posted",
                                                            "AttributeType" => "N"
                                                          )
                                 );
            $args['ProvisionedThroughput'] = array
                                            (
                                                "ReadCapacityUnits" => 10,
                                                "WriteCapacityUnits" => 1
                                            );
            
            try
            {
                $dynamoDBClient->createTable($args);
            }
            catch (DynamoDbException $e)
            {
                error_log("createSystemAlertLookupTable failed with exception: ".$e->getMessage());
                return $e;
            }
            
            return true;
        }
        
        public static function requestsToAddSystemAlertLookupForMessage($message)
        {

            $item = array
                    (
                        'system_alert_key' => array('N' => SYSTEM_ALERT_KEY),
                        'time_posted' => array('N' => (string)$message->timePosted()),
                        'messageid' => array('S' => $message->messageId())
                     );
            
            return array(array('PutRequest' => array('Item' => $item)));
        }
        
        public static function requestsToRemoveSystemAlertLookupForMessage($message)
        {
            $key = array
                   (
                        'HashKeyElement' => array('N' => SYSTEM_ALERT_KEY),
                        'RangeKeyElement' => array('N' => (string)$message->timePosted())
                    );
            
            return array(array('DeleteRequest' => array('Key' => $key)));
        }
        
        public static function getActiveSystemAlertMessagesPostedSince($timestamp, $dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array('TableName' => SYSTEM_ALERT_LOOKUP_TABLE_NAME);
            $args['HashKeyValue'] = array('N' => SYSTEM_ALERT_KEY);
            $args['RangeKeyCondition'] = array('AttributeValueList' => array(array('N' => (string)$timestamp)), 'ComparisonOperator' => 'GT');
            $args['ConsistentRead'] = true;
            $args['ScanIndexForward'] = false;
            
            $items = DynamoDBUtil::getAllItemsForQuery($args, $dynamoDBClient);
            
            if($items === false)
                return false;
            
            $messages = array();
            foreach($items as $item)
            {
                if(isset($item['messageid']) && isset($item['time_posted']))
                {
                    
                    $messageId = $item['messageid']['S'];
                    $timePosted = $item['time_posted']['N'];
                    
                    //We don't really need to read the message here, because all we have to know to send it to the user
                    //is the message id and the type and the time posted
                    $message = new MCMessage();
                    $message->setMessageId($messageId);
                    $message->setTimePosted($timePosted);
                    $message->setMessageType(MessageType::SystemAlert);
                    
                    $messages[] = $message;
                }
            }
            
            return $messages;
        }
        
        public static function getAllActiveSystemAlertMessages($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array('TableName' => SYSTEM_ALERT_LOOKUP_TABLE_NAME);
            $args['HashKeyValue'] = array('N' => SYSTEM_ALERT_KEY);
            $args['ConsistentRead'] = true;
            $args['ScanIndexForward'] = false;
            
            $items = DynamoDBUtil::getAllItemsForQuery($args, $dynamoDBClient);
            
            if($items === false)
                return false;
            
            $messages = array();
            foreach($items as $item)
            {
                if(isset($item['messageid']))
                {
                    $messageId = $item['messageid']['S'];
                    $message = MCMessage::getMessageForMessageId($messageId, LANGUAGE_CODE_ENGLISH, $dynamoDBClient);
                    if($message != false)
                    {
                        $messages[] = $message;
                    }
                }
            }
            
            return $messages;
        }
        
        //This table is used to locate account duration based messages.
        public static function createAccountDurationMessageLookupTable($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array();
            $args['TableName'] = ACCOUNT_DURATION_LOOKUP_TABLE_NAME;
            $args['KeySchema'] = array
                                (
                                    //The hash key is the number of weeks at which the message should be sent
                                     "HashKeyElement" => array
                                     (
                                        "AttributeName" => "account_duration_weeks",
                                        "AttributeType" => "N"
                                      ),
                                    //The range key is the time the message was posted
                                     "RangeKeyElement" => array
                                     (
                                        "AttributeName" => "time_posted",
                                        "AttributeType" => "N"
                                      )
                                 );
            $args['ProvisionedThroughput'] = array
                                            (
                                                "ReadCapacityUnits" => 10,
                                                "WriteCapacityUnits" => 1
                                             );
            
            try
            {
                $dynamoDBClient->createTable($args);
            }
            catch (DynamoDbException $e)
            {
                error_log("createAccountDurationMessageLookupTable failed with exception: ".$e->getMessage());
                return $e;
            }
            
            return true;
        }
        
        public static function requestsToAddAccountDurationLookupForMessage($message)
        {

            $item =  array
                    (
                         'account_duration_weeks' => array('N' => (string)$message->accountDurationWeeks()),
                         'time_posted' => array('N' => (string)$message->timePosted()),
                         'messageid' => array('S' => $message->messageId())
                     );
            
            return array(array('PutRequest' => array('Item' => $item)));
            
        }
        
        public static function requestsToRemoveAccountDurationLookupForMessage($message)
        {
            $key = array
                   (
                        'HashKeyElement' => array('N' => (string)$message->accountDurationWeeks()),
                        'RangeKeyElement' => array('N' => (string)$message->timePosted())
                    );
            
            return array(array('DeleteRequest' => array('Key' => $key)));
        }
        
        public static function getActiveMessagesForAccountDuration($accountDurationWeeks, $postedSinceTimestamp, $dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array('TableName' => ACCOUNT_DURATION_LOOKUP_TABLE_NAME);
            $args['HashKeyValue'] = array('N' => (string)$accountDurationWeeks);

            if(!empty($postedSinceTimestamp))
            {
                $args['RangeKeyCondition'] = array('AttributeValueList' => array(array('N' => (string)$postedSinceTimestamp)), 'ComparisonOperator' => 'GT');
            }

            $args['ConsistentRead'] = true;
            $args['ScanIndexForward'] = false;
            
            $items = DynamoDBUtil::getAllItemsForQuery($args, $dynamoDBClient);
            
            if($items === false)
                return false;
            
            $messages = array();
            foreach($items as $item)
            {
                if(isset($item['messageid']))
                {
                    $messageId = $item['messageid']['S'];
                    
                    //We don't really need to read the message here, because all we have to know to send it to the user
                    //is the message id and the type
                    $message = new MCMessage();
                    $message->setMessageId($messageId);
                    $message->setMessageType(MessageType::AccountDurationBased);
                    
                    $messages[] = $message;
                }
            }
            
            return $messages;
        }
        
        public static function getAllActiveAccountDurationMessages($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            try
            {
                $args = array('TableName' => ACCOUNT_DURATION_LOOKUP_TABLE_NAME);
                $result = $dynamoDBClient->scan($args);
                
                $messages = array();
                if(isset($result['Items']))
                {
                    $items = $result['Items'];
                    foreach($items as $item)
                    {
                        if(isset($item['messageid']))
                        {
                            $message = MCMessage::getMessageForMessageId($item['messageid']['S']);
                            if(!empty($message))
                            {
                                $messages[] = $message;
                            }
                        }
                    }
                }
                
                usort($messages, "MCMessage::messageCompare");
            }
            catch(DynamoDbException $e)
            {
                error_log("getAllActiveAccountDurationMessages failed with exception: ".$e->getMessage());
                return false;
            }
            
            return $messages;
        }
        
        //This table is used to locate account duration based messages.
        public static function createUpdateBasedMessageLookupTable($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array();
            $args['TableName'] = UPGRADE_BASED_LOOKUP_TABLE_NAME;
            $args['KeySchema'] = array
                                (
                                    //The hash key is the version key
                                    //(a concatenation of the app id and version number, separated by an underscore)
                                     "HashKeyElement" => array
                                     (
                                        "AttributeName" => "version_key",
                                        "AttributeType" => "S"
                                      ),
                                     //The range key is the time the message was posted
                                     "RangeKeyElement" => array
                                     (
                                        "AttributeName" => "time_posted",
                                        "AttributeType" => "N"
                                      )
                                 );
            $args['ProvisionedThroughput'] = array
                                            (
                                                 "ReadCapacityUnits" => 10,
                                                 "WriteCapacityUnits" => 1
                                             );
            
            try
            {
                $dynamoDBClient->createTable($args);
            }
            catch (DynamoDbException $e)
            {
                error_log("createUpdateBasedMessageLookupTable failed with exception: ".$e->getMessage());
                return $e;
            }
            
            return true;
        }
        
        public static function requestsToAddUpgradeBasedLookupForMessage($message)
        {
            $versionKeys = $message->versionKeys();
            if(empty($versionKeys))
            {
                error_log("requestsToAddUpgradeBasedLookupForMessage found no version keys for provided message");
                return false;
            }
            
            $putRequests = array();
            foreach($versionKeys as $versionKey)
            {
                $item = array
                        (
                             'version_key' => array('S' => $versionKey),
                             'time_posted' => array('N' => (string)$message->timePosted()),
                             'messageid' => array('S' => $message->messageId())
                         );
                
                $putRequests[] = array('PutRequest' => array('Item' => $item));
            }
            
            return $putRequests;            
        }
        
        public static function requestsToRemoveUpgradeBasedLookupForMessage($message)
        {            
            $versionKeys = $message->versionKeys();
            if(empty($versionKeys))
            {
                error_log("requestsToRemoveUpgradeBasedLookupForMessage found no version keys for provided message");
                return false;
            }
            
            $deleteRequests = array();
            foreach($versionKeys as $versionKey)
            {
                $item = array
                        (
                            'HashKeyElement' => array('S' => $versionKey),
                            'RangeKeyElement' => array('N' => (string)$message->timePosted())
                         );
                
                $deleteRequests[] = array('DeleteRequest' => array('Key' => $item));
            }
            
            return $deleteRequests;
        }
        
        public static function getActiveMessagesForVersionKey($versionKey, $postedSinceTimestamp, $dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array('TableName' => UPGRADE_BASED_LOOKUP_TABLE_NAME);
            $args['HashKeyValue'] = array('S' => $versionKey);
            
            if(!empty($postedSinceTimestamp))
            {
                $args['RangeKeyCondition'] = array('AttributeValueList' => array(array('N' => (string)$postedSinceTimestamp)), 'ComparisonOperator' => 'GT');
            }
            
            $args['ConsistentRead'] = true;
            $args['ScanIndexForward'] = false;
            
            $items = DynamoDBUtil::getAllItemsForQuery($args, $dynamoDBClient);
            
            if($items === false)
                return false;
            
            $messages = array();
            foreach($items as $item)
            {
                if(isset($item['messageid']))
                {
                    $messageId = $item['messageid']['S'];
                    
                    //We don't really need to read the message here, because all we have to know to send it to the user
                    //is the message id and the type
                    $message = new MCMessage();
                    $message->setMessageId($messageId);
                    $message->setMessageType(MessageType::UpgradeBased);
                    
                    $messages[] = $message;
                }
            }
            
            return $messages;
        }

        public static function getAllActiveUpgradeBasedMessages($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            try
            {
                $args = array('TableName' => UPGRADE_BASED_LOOKUP_TABLE_NAME);
                $result = $dynamoDBClient->scan($args);
                
                $messages = array();
                if(isset($result['Items']))
                {
                    $items = $result['Items'];
                    foreach($items as $item)
                    {
                        if(isset($item['messageid']))
                        {
                            $message = MCMessage::getMessageForMessageId($item['messageid']['S']);
                            if(!empty($message))
                            {
                                $messages[] = $message;
                            }
                        }
                    }
                }
                
                usort($messages, "MCMessage::messageCompare");
            }
            catch(DynamoDbException $e)
            {
                error_log("getAllActiveUpgradeBasedMessages failed with exception: ".$e->getMessage());
                return false;
            }
            
            return $messages;
        }
    }

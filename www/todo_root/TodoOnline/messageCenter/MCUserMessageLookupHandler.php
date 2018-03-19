<?php
    
    include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/messageCenter/mc_base_sdk.php');
    
    use Aws\DynamoDb\Exception\DynamoDbException;
    
    
    define('USER_MESSAGE_SYNC_LOOKUP_TABLE_NAME', 'mc_user_message_sync_lookup');
    define('USER_MESSAGE_WEB_LOOKUP_TABLE_NAME', 'mc_user_message_web_lookup');
    
    class MCUserMessageLookupHandler
    {
        //This table stores the last mod date for a particular message for a user,
        //so we can look up which messages need to be synced down
        public static function createUserMessageSyncLookupTable($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array();
            $args['TableName'] = USER_MESSAGE_SYNC_LOOKUP_TABLE_NAME;
            $args['KeySchema'] = array
                                 (
                                    "HashKeyElement" => array
                                    (
                                        "AttributeName" => "userid",
                                        "AttributeType" => "S"
                                    ),
                                    //Store the last time the message was updated for the user (sent to the user
                                    //or marked as read, etc.) in milliseconds and ensure that it is unique
                                    //so we can use it as the range key
                                    "RangeKeyElement" => array
                                    (
                                        "AttributeName" => "mod_date",
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
                error_log("createUserMessageSyncLookupTable failed with exception: ".$e->getMessage());
                return $e;
            }
            
            return true;
        }
 
        public static function addUserMessageSyncLookupForUserMessage($userMessage)
        {
            if(empty($userMessage))
            {
                error_log("addMessageSyncLookupForUserMessage called missing message");
                return false;
            }
            
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array('TableName' => USER_MESSAGE_SYNC_LOOKUP_TABLE_NAME);
            $args['Item'] = array
                            (
                                'userid' => array('S' => $userMessage->userId()),
                                'mod_date' => array('N' => (string)$message->modDate()),
                                'messageid' => array('S' => $message->messageId())
                             );
            
            try
            {
                $dynamoDBClient->putItem($args);
            }
            catch(DynamoDbException $e)
            {
                error_log("addMessageSyncLookupForUserMessage failed with exception: ".$e->getMessage());
                return false;
            }
            
            return true;
        }
        
        //Accepts a unix timestamp in seconds and returns all messages for the user modified since that time
        public static function getMessagesForUserModifiedSince($userid, $timestamp, $deviceTypeVal, $syncServiceVal, $langCode=LANGUAGE_CODE_ENGLISH, $dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            //Convert the timestamp to milliseconds since our mod dates are stored in milliseconds
            $timestamp = $timestamp * 1000;
            
            $args = array('TableName' => USER_MESSAGE_SYNC_LOOKUP_TABLE_NAME);
            $args['HashKeyValue'] = array('S' => $userid);
            $args['ConsistentRead'] = true;
            $args['RangeKeyCondition'] = array('AttributeValueList' => array(array('N' => (string)$timestamp)), 'ComparisonOperator' => 'GT');
            
            $items = DynamoDBUtil::getAllItemsForQuery($args, $dynamoDBClient);
            if($items === false)
                return false;
            
            //Return the time we read from the user messages table (before reading the messages themselves)
            //so we can return it to the client and the client can track what the last read date was
            $response = array('time_read' => time());
            
            $messages = array();
            foreach($items as $item)
            {
                if(isset($item['messageid']) && isset($item['userid']))
                {
                    $messageId = $item['messageid']['S'];
                    $userId = $item['userid']['S'];
                    $message = MCUserMessage::getUserMessageForUserIdAndMessageId($userId, $messageId, true, $langCode, $dynamoDBClient);
                    if($message != false)
                    {
                        //Filter by device type and sync service
                        if($message->message()->shouldBeShownForDeviceType($deviceTypeVal) == true && $message->message()->shouldBeSentForSyncService($syncServiceVal) == true)
                        {
                            $messages[] = $message;
                        }          
                    }
                }
            }
            $response['messages'] = $messages;
            return $response;
            
        }
 
        //This table stores the time posted for a particular message for a user,
        //so we can look up which messages need to be shown in the message center
        //on the web
        public static function createUserMessageWebLookupTable($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array();
            $args['TableName'] = USER_MESSAGE_WEB_LOOKUP_TABLE_NAME;
            $args['KeySchema'] = array
                                (
                                    "HashKeyElement" => array
                                    (
                                        "AttributeName" => "userid",
                                        "AttributeType" => "S"
                                    ),
                                 //Store the time the message was sent to the user in milliseconds and ensure that it is unique
                                 //so we can use it as the range key
                                    "RangeKeyElement" => array
                                    (
                                        "AttributeName" => "time_sent",
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
                error_log("createUserMessageWebLookupTable failed with exception: ".$e->getMessage());
                return $e;
            }
            
            return true;
        }
        
        public static function addUserMessageWebLookupForUserMessage($userMessage)
        {
            if(empty($userMessage))
            {
                error_log("addUserMessageWebLookupForUserMessage called missing message");
                return false;
            }
            
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array('TableName' => USER_MESSAGE_WEB_LOOKUP_TABLE_NAME);
            $args['Item'] = array
                            (
                                'userid' => array('S' => $userMessage->userId()),
                                'time_sent' => array('N' => (string)$message->timeSent()),
                                'messageid' => array('S' => $message->messageId())
                             );
            
            try
            {
                $dynamoDBClient->putItem($args);
            }
            catch(DynamoDbException $e)
            {
                error_log("addUserMessageWebLookupForUserMessage failed with exception: ".$e->getMessage());
                return false;
            }
            
            return true;
        }
        
        //Returns the most recently posted messages (based on time_sent) for the user up to $limit,
        //and also returns the last key retrieved so the client can get more values if needed
        public static function getRecentlyPostedMessagesForUser($userid, $limit, $deviceTypeVal, $syncServiceVal, $lastEvaluatedKey=NULL, $langCode=LANGUAGE_CODE_ENGLISH, $dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $responseArray = array();
            try
            {
                $args = array('TableName' => USER_MESSAGE_WEB_LOOKUP_TABLE_NAME);
                $args['HashKeyValue'] = array('S' => $userid);
                
                if($lastEvaluatedKey != NULL)
                    $args['ExclusiveStartKey'] = $lastEvaluatedKey;
                
                $args['ConsistentRead'] = true;
                $args['ScanIndexForward'] = false;
                $args['Limit'] = $limit;
                
                $result = $dynamoDBClient->query($args);

                $messages = array();
                if(isset($result['Items']))
                {
                    $items = $result['Items'];
                    foreach($items as $item)
                    {
                        if(isset($item['messageid']) && isset($item['userid']))
                        {
                            $messageId = $item['messageid']['S'];
                            $userId = $item['userid']['S'];
                            $message = MCUserMessage::getUserMessageForUserIdAndMessageId($userId, $messageId, true, $langCode, $dynamoDBClient);
                            if($message != false)
                            {
                                //Filter by device type
                                if($message->message()->shouldBeShownForDeviceType($deviceTypeVal) == true && $message->message()->shouldBeSentForSyncService($syncServiceVal) == true)
                                {
                                    $messages[] = $message;
                                }
                            }
                        }
                    }
                }
                $responseArray['messages'] = $messages;
                
                if(isset($result['LastEvaluatedKey']))
                {
                    $responseArray['LastEvaluatedKey'] = $result['LastEvaluatedKey'];
                }
            }
            catch(DynamoDbException $e)
            {
                error_log("getRecentlyPostedMessagesForUser failed with exception: ".$e->getMessage());
                return false;
            }
            
            return $responseArray;
            
        }
        
    }

<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/messageCenter/mc_base_sdk.php');

use Aws\DynamoDb\Exception\DynamoDbException;

define('USER_MESSAGES_TABLE_NAME', 'mc_user_message_info');

//check for new messages every 24 hours on the web interface
define('WEB_UI_CHECK_MESSAGES_INTERVAL', 86400);

//This class stores per-user information about message center messages,
//including whether the messages have been read and the time they were delivered to the
//user

class MCUserMessage extends TDODBObject
{
    //This table stores per-user message info (which messages have been read, etc)
    public static function createUserMessageInfoTable($dynamoDBClient=NULL)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        $args = array();
        $args['TableName'] = USER_MESSAGES_TABLE_NAME;
        $args['KeySchema'] = array
                             (
                                 "HashKeyElement" => array
                                  (
                                      "AttributeName" => "userid",
                                      "AttributeType" => "S"
                                  ),

                                 "RangeKeyElement" => array
                                  (
                                      "AttributeName" => "messageid",
                                      "AttributeType" => "S"
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
            error_log("createUserMessageInfoTable failed with exception: ".$e->getMessage());
            return $e;
        }
        
        return true;
    }
    
    public static function getUserMessageForUserIdAndMessageId($userid, $messageid, $readMessage=false, $langCode=LANGUAGE_CODE_ENGLISH, $dynamoDBClient=NULL)
    {
        if(empty($messageid) || empty($userid))
        {
            error_log("getUserMessageForUserIdAndMessageId called missing messageid or userid");
            return false;
        }
        
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        $args = array('TableName' => USER_MESSAGES_TABLE_NAME);
        $args['Key'] = array('HashKeyElement' => array('S' => $userid), 'RangeKeyElement' => array('S' => $messageid));
        $args['ConsistentRead'] = true;
        
        try
        {
            $message = NULL;
            $result = $dynamoDBClient->getItem($args);
            
            if(isset($result['Item']))
            {
                $item = $result['Item'];
                $message = MCUserMessage::userMessageFromDynamoDBItem($item, $readMessage, $langCode, $dynamoDBClient);
            }
        }
        catch(DynamoDbException $e)
        {
            error_log("getUserMessageForUserIdAndMessageId failed with exception: ".$e->getMessage());
            return false;
        }
        
        return $message;
    }

    //AddToWebLookup should only be set to true for Todo Cloud users. Non Todo Cloud users are never going to
    //use that table, so it's a waste of space to add entries for their messages.
    public static function sendMessagesToUser($userid, $messages, $addToWebLookup, $dynamoDBClient=NULL)
    {
        error_log("Sending ".count($messages)." new messages to user...");
    
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
    
        //Store the mod_date and time_sent in milliseconds and ensure that
        //each entry is unique so we can use it for the range key
        $millisecondTime = time() * 1000;
        //We want to preserve the order of $messages, so set the first item
        //in the array to have the greatest timestamp
        $millisecondTime += count($messages);
        
        $userMessages = array();
        foreach($messages as $message)
        {
            //Don't send the message to the user if there's already an entry for it in the user messages table
            if(MCUserMessage::getUserMessageForUserIdAndMessageId($userid, $message->messageId()) != NULL)
            {
                error_log("*** Message already sent to user, skipping message ***");
                continue;
            }
            
        
            $userMessage = new MCUserMessage();
            $userMessage->setMessage($message);
            $userMessage->setMessageId($message->messageId());
            $userMessage->setUserId($userid);
            $userMessage->setRead(0);
            //If it's a system alert message, the time sent should match the time it was posted, to avoid
            //confusion for users
            if($message->messageType() == MessageType::SystemAlert)
            {
                $userMessage->setTimeSent($message->timePosted() * 1000);
            }
            else
            {
                $userMessage->setTimeSent($millisecondTime);
            }
            $userMessage->setModDate($millisecondTime);
            
            $userMessages[] = $userMessage;
        
            $millisecondTime--;
        }
        
        //BatchWriteItem can only handle 25 requests at a time, so break the messages into
        //groups of 8 since we need 3 requests per message (for the info table and the 2 lookup tables)
        $chunks = array_chunk($userMessages, 8);

        foreach($chunks as $userMessageArray)
        {
            try
            {
                $userInfoRequests = array();
                $userSyncLookupRequests = array();
                $userWebLookupRequests = array();
                foreach($userMessageArray as $userMessage)
                {
                    $userInfoRequests[] = array('PutRequest' => array('Item' => $userMessage->dynamoDBItemFromUserMessage($dynamoDBClient)));
                    $userSyncLookupRequests[] = array('PutRequest' => array('Item' => $userMessage->dynamoDBSyncLookupItemFromUserMessage($dynamoDBClient)));
                    if($addToWebLookup)
                        $userWebLookupRequests[] = array('PutRequest' => array('Item' => $userMessage->dynamoDBWebLookupItemFromUserMessage($dynamoDBClient)));
                   
                }
            
                $requestItems = array(USER_MESSAGES_TABLE_NAME => $userInfoRequests, USER_MESSAGE_SYNC_LOOKUP_TABLE_NAME => $userSyncLookupRequests);
                
                if(!empty($userWebLookupRequests))
                    $requestItems[USER_MESSAGE_WEB_LOOKUP_TABLE_NAME] = $userWebLookupRequests;
                
                $args = array('RequestItems' => $requestItems);
                
                $dynamoDBClient->batchWriteItem($args);

                
            }
            catch(DynamoDbException $e)
            {
                error_log("sendMessagesToUser failed with exception: ".$e->getMessage());
                return false;
            }
        }
    
        return true;
    }
    
    //useWebLookupTable should only be set to true for Todo Cloud users. Non Todo Cloud users are never going to
    //use that table, so it's a waste of space to add entries for their messages.
    public static function updateUserMessage($oldUserMessage, $newUserMessage, $useWebLookupTable, $dynamoDBClient=NULL, $userInfo=NULL, $updateUserInfo=true)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        if(empty($userInfo))
        {
            $userInfo = MCUserInfo::getUserInfoForUserId($oldUserMessage->userId(), $dynamoDBClient);
        }
        if($userInfo == false)
        {
            error_log("updateUserMessage failed to get userinfo");
            return false;
        }
        

        try
        {
            //Delete the sync lookup item with the prior mod date from the db, then
            //add a lookup item with the new mod date
            $userSyncLookupRequests = array
                                     (
                                            array
                                            (
                                                'PutRequest' => array('Item' => $newUserMessage->dynamoDBSyncLookupItemFromUserMessage($dynamoDBClient))
                                            ),
                                            array
                                            (
                                                'DeleteRequest' => array('Key' => array('HashKeyElement' => array('S' => $oldUserMessage->userId()), 'RangeKeyElement' => array('N' => (string)$oldUserMessage->modDate())))
                                            )
                        
                                      );
            
            //Update the item in the user message info table
            $userInfoRequests = array
                                (
                                    array('PutRequest' => array('Item' => $newUserMessage->dynamoDBItemFromUserMessage($dynamoDBClient)))
                                );
            
            $userWebLookupRequests = NULL;
            
            if($useWebLookupTable)
            {
                //If the item has been deleted, remove it from the web lookup table
                if($oldUserMessage->deleted() == 0 && $newUserMessage->deleted() > 0)
                {
                    $userWebLookupRequests = array
                                            (
                                                array('DeleteRequest' => array('Key' => array('HashKeyElement' => array('S' => $oldUserMessage->userId()), 'RangeKeyElement' => array('N' => (string)$oldUserMessage->timeSent()))))
                                            );

                }
                //If the item has been un-deleted, add it to the web lookup table
                else if($oldUserMessage->deleted() > 0 && $newUserMessage->deleted() == 0)
                {
                    $userWebLookupRequests = array
                                            (
                                                array('PutRequest' => array('Item' => $userMessage->dynamoDBWebLookupItemFromUserMessage($dynamoDBClient)))
                                            );
                }
            }
            
            $requestItems = array
                            (
                                USER_MESSAGES_TABLE_NAME => $userInfoRequests,
                                USER_MESSAGE_SYNC_LOOKUP_TABLE_NAME => $userSyncLookupRequests
                            );
                            
            if($userWebLookupRequests != NULL)
                $requestItems[USER_MESSAGE_WEB_LOOKUP_TABLE_NAME] = $userWebLookupRequests;
            
            $args = array('RequestItems' => $requestItems);
            $dynamoDBClient->batchWriteItem($args);
            
            $userInfo->setLastUpdatedUserMessages(time());
            if($updateUserInfo)
                $userInfo->addOrUpdateUserInfo();
            
        }
        catch(DynamoDbException $e)
        {
            error_log("updateUserMessage failed with exception: ".$e->getMessage());
            return false;
        }
        
        
        return true;
    }
    
    public static function userShouldCheckUnreadMessageCount()
    {
        if(isset($_SESSION['unread_message_count']) && isset($_SESSION['last_checked_messages']))
        {
            $lastCheckedMessagesTime = intval($_SESSION['last_checked_messages']);
            
            $timeDiff = time() - $lastCheckedMessagesTime;
            
            if($timeDiff < WEB_UI_CHECK_MESSAGES_INTERVAL)
                return false;
        }
        
        return true;
    }
    
    public static function getUnreadMessageCountForUser($userid, $deviceType, $dynamoDBClient=NULL)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        try
        {
            $args = array('TableName' => USER_MESSAGES_TABLE_NAME);
            $args['HashKeyValue'] = array('S' => $userid);
            $args['ConsistentRead'] = true;
            
            $items = DynamoDBUtil::getAllItemsForQuery($args, $dynamoDBClient);
            if($items === false)
                return false;
            
            $unreadCount = 0;
            foreach($items as $item)
            {
                if(!isset($item['read']['N']) || intval($item['read']['N']) == 0)
                {
                    if(!isset($item['deleted']['N']) || intval($item['deleted']['N']) == 0)
                    {
                        //Filter by device type
                        $message = MCMessage::getMessageForMessageId($item['messageid']['S'], LANGUAGE_CODE_ENGLISH, $dynamoDBClient);
                        if(!empty($message) && $message->shouldBeShownForDeviceType($deviceType) == true && $message->shouldBeSentForSyncService(SyncService::TodoPro))
                        {
                            $unreadCount++;
                        }
                    }
                }
            }
            
        }
        catch(DynamoDbException $e)
        {
            error_log("resetUnreadMessageCountForUser failed with exception: ".$e->getMessage());
            return false;
        }
        
        return $unreadCount;
    }
    
    public static function userMessageFromDynamoDBItem($item, $readMessage=false, $langCode, $dynamoDBClient=NULL)
    {
        $userMessage = new MCUserMessage();
        if(isset($item['messageid']))
        {
            $userMessage->setMessageId($item['messageid']['S']);
            if($readMessage)
            {
                $message = MCMessage::getMessageForMessageId($userMessage->messageId(), $langCode, $dynamoDBClient);
                if($message)
                    $userMessage->setMessage($message);
            }
        }
        if(isset($item['userid']))
            $userMessage->setUserId($item['userid']['S']);
        if(isset($item['time_sent']))
            $userMessage->setTimeSent(intval($item['time_sent']['N']));
        if(isset($item['read']))
            $userMessage->setRead(intval($item['read']['N']));
        if(isset($item['mod_date']))
            $userMessage->setModDate(intval($item['mod_date']['N']));
        if(isset($item['deleted']))
            $userMessage->setDeleted(intval($item['deleted']['N']));
        
        return $userMessage;
    }
    
    public function dynamoDBItemFromUserMessage($dynamoDBClient)
    {
        $item = array();
        
        $item['messageid'] = $this->messageId();
        $item['userid'] = $this->userId();
        $item['time_sent'] = $this->timeSent();
        $item['mod_date'] = $this->modDate();
        $item['read'] = $this->read();
        $item['deleted'] = $this->deleted();
        
        $item = $dynamoDBClient->formatAttributes($item);
//        error_log("USER MESSAGE ITEM: ".print_r($item, true));
        return $item;
    }
    
    public function dynamoDBSyncLookupItemFromUserMessage($dynamoDBClient)
    {
        $item = array();
        
        $item['messageid'] = $this->messageId();
        $item['userid'] = $this->userId();
        $item['mod_date'] = $this->modDate();
        
        $item = $dynamoDBClient->formatAttributes($item);
//                error_log("SYNC LOOKUP ITEM: ".print_r($item, true));
        return $item;
    }
    
    public function dynamoDBWebLookupItemFromUserMessage($dynamoDBClient)
    {
        $item = array();
        
        $item['messageid'] = $this->messageId();
        $item['userid'] = $this->userId();
        $item['time_sent'] = $this->timeSent();
        
        $item = $dynamoDBClient->formatAttributes($item);
//                error_log("WEB LOOKUP ITEM: ".print_r($item, true));
        return $item;
    }
	
    public static function userMessageCompare($message1, $message2)
    {
        if($message1->timePosted() > $message2->timeSent())
            return -1;
        if($message1->timePosted() < $message2->timeSent())
            return 1;
        
        return 0;
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
    
    public function messageId()
    {
        if(empty($this->_publicPropertyArray['messageid']))
            return NULL;
        else
            return $this->_publicPropertyArray['messageid'];
    }
    
    public function setMessageId($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['messageid']);
        else
            $this->_publicPropertyArray['messageid'] = $val;
    }
    
    public function message()
    {
        if(empty($this->_publicPropertyArray['message']))
            return NULL;
        else
            return $this->_publicPropertyArray['message'];
    }
    
    public function setMessage($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['message']);
        else
            $this->_publicPropertyArray['message'] = $val;
    }

    public function modDate()
    {
        if(empty($this->_publicPropertyArray['mod_date']))
            return 0;
        else
            return $this->_publicPropertyArray['mod_date'];
    }
    
    public function setModDate($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['mod_date']);
        else
            $this->_publicPropertyArray['mod_date'] = $val;
    }

    public function timeSent()
    {
        if(empty($this->_publicPropertyArray['time_sent']))
            return 0;
        else
            return $this->_publicPropertyArray['time_sent'];
    }
    
    public function setTimeSent($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['time_sent']);
        else
            $this->_publicPropertyArray['time_sent'] = $val;
    }
    
    public function read()
    {
        if(empty($this->_publicPropertyArray['read']))
            return 0;
        else
            return $this->_publicPropertyArray['read'];
    }
    
    public function setRead($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['read']);
        else
            $this->_publicPropertyArray['read'] = $val;
    }
}
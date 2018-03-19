<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/messageCenter/mc_base_sdk.php');

use Aws\DynamoDb\Exception\DynamoDbException;

define('MESSAGES_TABLE_NAME', 'mc_message');

abstract class MessagePriority
{
    const None = 0;
    const Low = 1;
    const Medium = 3;
    const Important = 5;
    const Urgent = 47;
}

abstract class MessageType
{
    const SystemAlert = 0;
    const UpgradeBased = 1;
    const AccountDurationBased = 2;
}

abstract class DeviceType
{
    const iPhone = 0;
    const iPod = 1;
    const iPad = 2;
    const Mac = 3;
    const Web = 4;
    const Android = 5;
}

define('CLIENT_IPHONE_IDENTIFIER', 'iPhone');
define ('CLIENT_IPOD_IDENTIFIER', 'iPod');
define('CLIENT_IPAD_IDENTIFIER', 'iPad');
define('CLIENT_MAC_IDENTIFIER', 'Mac');
define('CLIENT_ANDROID_IDENTIFIER', 'android');

abstract class SyncService
{
    const TodoPro = 0;
    const Dropbox = 1;
    const AppigoSync = 2;
    const iCloud = 3;
    const Toodledo = 4;
    const NoSyncService = 5;
}

define('CLIENT_TODOPRO_IDENTIFIER', 'TodoPro');
define('CLIENT_DROPBOX_IDENTIFIER', 'Dropbox');
define('CLIENT_APPITOSYNC_IDENTIFIER', 'AppigoSync');
define('CLIENT_ICLOUD_IDENTIFIER', 'CloudDocumentIdentifier');
define('CLIENT_TOODLEDO_IDENTIFIER', 'Toodledo');
define('CLIENT_NOSERVICE_IDENTIFIER', 'none');

define('LANGUAGE_CODE_ENGLISH', 'en');


class MCMessage extends TDODBObject
{
    //Returns an array of app ids that can communicate with message center
    public static function possibleAppIds()
    {
        return array('todo', 'todopro', 'todoipad', 'todomac', 'todopromac', 'todoproweb', 'todoproandroid');
    }

    //This table stores the actual data of a message center message
    public static function createMessagesTable($dynamoDBClient=NULL)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        $args = array();
        $args['TableName'] = MESSAGES_TABLE_NAME;
        $args['KeySchema'] = array
                            (
                                "HashKeyElement" => array
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
            error_log("createMessagesTable failed with exception: ".$e->getMessage());
            return $e;
        }
        
        return true;
    }


    public static function getMessageForMessageId($messageId, $langCode=LANGUAGE_CODE_ENGLISH, $dynamoDBClient=NULL)
    {
        if(empty($messageId))
        {
            error_log("getMessageForMessageId called missing messageId");
            return false;
        }
        
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        $args = array('TableName' => MESSAGES_TABLE_NAME);
        $args['Key'] = array('HashKeyElement' => array('S' => $messageId));
        $args['ConsistentRead'] = true;
        
        $args['AttributesToGet'] = MCMessage::messageAttributesToGetForLanguageCode($langCode);
       
        try
        {
            $message = NULL;
            $result = $dynamoDBClient->getItem($args);
            if(isset($result['Item']))
            {
                $item = $result['Item'];
                $message = MCMessage::messageFromDynamoDBItem($item, $langCode);
            }
        }
        catch(DynamoDbException $e)
        {
            error_log("getMessageForMessageId failed with exception: ".$e->getMessage());
            return false;
        }

        return $message;
    }
    
    private function messageAttributesToGetForLanguageCode($langCode)
    {
       $attributesToGet = array('messageid', 'message_html', 'subject', 'time_posted', 'priority', 'message_type', 'expiration_date', 'account_duration_weeks', 'removed_from_lookup', 'is_test_message', 'device_types', 'sync_services', 'version_keys');
       
       //We always read the english message and then try to read whatever language the client asked for
       if($langCode != LANGUAGE_CODE_ENGLISH)
       {
            $attributesToGet[] = $langCode . '_message_html';
            $attributesToGet[] = $langCode . '_subject';
       }
       return $attributesToGet;
    }
    
    public function addMessage($dynamoDBClient=NULL)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        if($this->subjectForLanguage(LANGUAGE_CODE_ENGLISH) == NULL)
        {
            error_log("Attempting to add message with no subject");
            return false;
        }
        
        if($this->messageForLanguage(LANGUAGE_CODE_ENGLISH) == NULL)
        {
           error_log("Attempting to add message with no body");
           return false;
        }
        
        if($this->messageId() == NULL)
        {
           $this->setMessageId(TDOUtil::uuid());
        }
        if($this->timePosted() == 0)
        {
           $this->setTimePosted(time());
        }
        $this->setRemovedFromLookupTables(0);
        
        try
        {
        
            //Add the message to the database, and also add it to the correct lookup
            //table and to the expiration table if it has an expiration date
            $messageTableRequests = array(array('PutRequest' => array('Item' => $this->dynamoDBItemFromMessage($dynamoDBClient))));
            $requestItems = array(MESSAGES_TABLE_NAME => $messageTableRequests);
            
            //If this is a test message, it only goes to one specific user and we don't add it to any lookup tables
            if($this->isTestMessage() == 0)
            {
                $expirationTableRequests = MCMessageExpirationMethods::requestsToAddExpirationEntryForMessage($this);
                $lookupTableRequests = MCMessageLookupHandler::requestsToAddLookupTableEntryForMessage($this);
                $lookupTableName = MCMessageLookupHandler::lookupTableNameForMessage($this);
                
                if(!empty($expirationTableRequests))
                    $requestItems[MESSAGE_EXPIRATION_TABLE_NAME] = $expirationTableRequests;
                
                if(!empty($lookupTableRequests) && !empty($lookupTableName))
                {
                    $requestItems[$lookupTableName] = $lookupTableRequests;
                }
                else
                {
                   error_log("addMessage failed to get lookup table requests for message");
                   return false;
                }
            }
            
            $args = array('RequestItems' => $requestItems);

            $dynamoDBClient->batchWriteItem($args);
        }
        catch(DynamoDbException $e)
        {
           error_log("addMessage failed with exception: ".$e->getMessage());
           return false;
        }
        
        return true;
    }

    //Takes a message that has been removed from lookup tables and adds it back to them.
    public function reAddMessageToLookupTables($dynamoDBClient=NULL)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        
        $this->setRemovedFromLookupTables(0);
        
        try
        {
            //Update the message in the messages table
            $args = array('TableName' => MESSAGES_TABLE_NAME);
            $args['Key'] = array('HashKeyElement' => array('S' => $this->messageId()));
            $args['AttributeUpdates'] = array
                                        (
                                            'removed_from_lookup' => array
                                                                    (
                                                                        'Action' => 'PUT',
                                                                        'Value' => array('N' => (string)$this->removedFromLookupTables())
                                                                    ),
                                            'expiration_date' => array
                                                                (
                                                                    'Action' => 'PUT',
                                                                    'Value' => array('N' => (string)$this->expirationDate())
                                                                 ),
                                            'time_posted' => array
                                                            (
                                                                'Action' => 'PUT',
                                                                'Value' => array('N' => (string)$this->timePosted())
                                                            )
                                         
                                         );
            
            $dynamoDBClient->updateItem($args);
            
            //Now add the message to the correct lookup
            //table and to the expiration table if it has an expiration date
            $requestItems = array();
            
            $expirationTableRequests = MCMessageExpirationMethods::requestsToAddExpirationEntryForMessage($this);
            $lookupTableRequests = MCMessageLookupHandler::requestsToAddLookupTableEntryForMessage($this);
            $lookupTableName = MCMessageLookupHandler::lookupTableNameForMessage($this);
            
            if(!empty($expirationTableRequests))
                $requestItems[MESSAGE_EXPIRATION_TABLE_NAME] = $expirationTableRequests;
            
            if(!empty($lookupTableRequests) && !empty($lookupTableName))
            {
                $requestItems[$lookupTableName] = $lookupTableRequests;
            }
            else
            {
                error_log("reAddMessageToLookupTables failed to get lookup table requests for message");
                return false;
            }
            
            $args = array('RequestItems' => $requestItems);
            
            $dynamoDBClient->batchWriteItem($args);
        }
        catch(DynamoDbException $e)
        {
            error_log("reAddMessageToLookupTables failed with exception: ".$e->getMessage());
            return false;
        }
        
        return true;
    }

    
    //Marks a message as having been removed and removes it from all associated lookup tables
    //If $newExpirationDate is not null, we will also update the message's expiration date (the
    //expiration date on the message should not change before this call because we need
    //the old expiration date to remove the message from the expiration table)
    public function removeMessage($newExpirationDate=NULL, $dynamoDBClient=NULL)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        try
        {
        
            $expirationTableRequests = MCMessageExpirationMethods::requestsToDeleteExpirationEntryForMessage($this);
            $lookupTableRequests = MCMessageLookupHandler::requestsToRemoveLookupTableEntryForMessage($this);
            $lookupTableName = MCMessageLookupHandler::lookupTableNameForMessage($this);
            
            
            //First, update the message itself in the messages table
            $this->setRemovedFromLookupTables(1);
            if($newExpirationDate !== NULL)
                $this->setExpirationDate($newExpirationDate);
            
            $args = array('TableName' => MESSAGES_TABLE_NAME);
            $args['Key'] = array('HashKeyElement' => array('S' => $this->messageId()));
            $args['AttributeUpdates'] = array
                                        (
                                            'removed_from_lookup' => array
                                                                    (
                                                                        'Action' => 'PUT',
                                                                        'Value' => array('N' => (string)$this->removedFromLookupTables())
                                                                    ),
                                            'expiration_date' => array
                                                                (
                                                                    'Action' => 'PUT',
                                                                    'Value' => array('N' => (string)$this->expirationDate())
                                                                )
             
                                         );
            
            $dynamoDBClient->updateItem($args);
            
            
            //Now, update the lookup tables
            $requestItems = array();
            
            if(!empty($expirationTableRequests))
                $requestItems[MESSAGE_EXPIRATION_TABLE_NAME] = $expirationTableRequests;
            
            if(!empty($lookupTableRequests) && !empty($lookupTableName))
            {
                $requestItems[$lookupTableName] = $lookupTableRequests;
            }
            else
            {
                error_log("removeMessage failed to get lookup table requests for message");
                return false;
            }
            
            $args = array('RequestItems' => $requestItems);
            
            $dynamoDBClient->batchWriteItem($args);
        }
        catch(DynamoDbException $e)
        {
            error_log("removeMessage failed with exception: ".$e->getMessage());
            return false;
        }
        
        return true;
    }
    
    //Takes a message and updates its expiration date, aslo updating the expiration lookup table as needed
    //This method will NOT add/remove lookup table entries if the message is moving from an unexpired to an expired
    //state (or vice versa) that should be handled elsewhere
    public function udpateExpirationDate($newExpirationDate, $dynamoDBClient=NULL)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        if($newExpirationDate == $this->expirationDate())
            return true;
        
        try
        {
            $expirationTableRequests = array();
        
            //Remove the previous entry in the expiration date table if needed
            if($this->expirationDate() != 0 && $this->removedFromLookupTables() == 0)
            {
                $expirationTableRequests = MCMessageExpirationMethods::requestsToDeleteExpirationEntryForMessage($this);
            }
            
            //Update the message in the messages table
            $this->setExpirationDate($newExpirationDate);
            
            $args = array('TableName' => MESSAGES_TABLE_NAME);
            $args['Key'] = array('HashKeyElement' => array('S' => $this->messageId()));
            $args['AttributeUpdates'] = array
                                        (
                                            'expiration_date' => array
                                                                 (
                                                                    'Action' => 'PUT',
                                                                    'Value' => array('N' => (string)$this->expirationDate())
                                                                  )
                                         
                                         );
            
            $dynamoDBClient->updateItem($args);
            
            
            //Add a new expiration date entry if needed
            if($this->expirationDate() != 0 && $this->removedFromLookupTables() == 0)
            {
                $expirationTableRequests = array_merge($expirationTableRequests, MCMessageExpirationMethods::requestsToAddExpirationEntryForMessage($this));
            }

            
            $requestItems = array();
            
            if(!empty($expirationTableRequests))
            {
                $requestItems = array(MESSAGE_EXPIRATION_TABLE_NAME => $expirationTableRequests);

                $args = array('RequestItems' => $requestItems);
                $dynamoDBClient->batchWriteItem($args);
            }
            
           
        }
        catch(DynamoDbException $e)
        {
            error_log("updateExpirationDate failed with exception: ".$e->getMessage());
            return false;
        }
        
        return true;
    }
    

    
    //If successful, returns an associative array where 'messages' is an array of MCMessage objects, and
    //'last_hash_key' and 'last_range_key' make up the primary key that should be passed in to continue getting messages from
    //where the scan left off, if applicable
    public static function getAllMessages($startDate, $endDate, $langCode=LANGUAGE_CODE_ENGLISH, $dynamoDBClient=NULL, $sorted=true)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
           
        if(empty($dynamoDBClient))
            return false;
           
        try
        {
            $args = array('TableName' => MESSAGES_TABLE_NAME);
            $args['ScanFilter'] = array
                                 (
                                    'time_posted' => array
                                                    (
                                                        'ComparisonOperator' => 'BETWEEN',
                                                        'AttributeValueList' => array(array('N' => (string)$startDate), array('N' => (string)$endDate))
                                                    ),
                                  
                                    'is_test_message' => array
                                                         (
                                                            'ComparisonOperator' => 'NE',
                                                            'AttributeValueList' => array(array('N' => '1'))
                                                         )
                                );
            $args['AttributesToGet'] = MCMessage::messageAttributesToGetForLanguageCode($langCode);
            
            $result = $dynamoDBClient->scan($args);
           
            $messages = array();
            if(isset($result['Items']))
            {
                $items = $result['Items'];
                foreach($items as $item)
                {
                    $message = MCMessage::messageFromDynamoDBItem($item, $langCode);
                    $messages[] = $message;
                }
            }
           
            usort($messages, "MCMessage::messageCompare");
        }
        catch(DynamoDbException $e)
        {
           error_log("getAllMessages failed with exception: ".$e->getMessage());
           return false;
        }
        
        return $messages;
    }
    
    public static function messageFromDynamoDBItem($item, $langCode)
    {
        $message = new MCMessage();
        if(isset($item['messageid']))
           $message->setMessageId($item['messageid']['S']);
        
        //If we have the message in the desired language, set the message html to that,
        //otherwise use English
        if($langCode != LANGUAGE_CODE_ENGLISH && isset($item[$langCode . '_message_html']))
        {
            $message->setMessageHtml($item[$langCode . '_message_html']['S']);
        }
        else
        {
            if(isset($item['message_html']))
                $message->setMessageHtml($item['message_html']['S']);
        }
        
        if($langCode != LANGUAGE_CODE_ENGLISH && isset($item[$langCode . '_subject']))
        {
            $message->setSubject($item[$langCode . '_subject']['S']);
        }
        else
        {
            if(isset($item['subject']))
                $message->setSubject($item['subject']['S']);
        }

        if(isset($item['time_posted']))
           $message->setTimePosted(intval($item['time_posted']['N']));
        if(isset($item['priority']))
           $message->setPriority(intval($item['priority']['N']));
        if(isset($item['message_type']))
           $message->setMessageType(intval($item['message_type']['N']));
        if(isset($item['expiration_date']))
           $message->setExpirationDate(intval($item['expiration_date']['N']));
        if(isset($item['account_duration_weeks']))
            $message->setAccountDurationWeeks(intval($item['account_duration_weeks']['N']));
        if(isset($item['removed_from_lookup']))
            $message->setRemovedFromLookupTables(intval($item['removed_from_lookup']['N']));
        if(isset($item['is_test_message']))
            $message->setIsTestMessage(intval($item['is_test_message']['N']));
        
        if(isset($item['device_types']))
        {
            $deviceTypeArray = $item['device_types']['NS'];
            $message->setDeviceTypes(DynamoDBUtil::integerArrayFromArray($deviceTypeArray));
        }
        if(isset($item['sync_services']))
        {
           $syncServiceArray = $item['sync_services']['NS'];
           $message->setSyncServices(DynamoDBUtil::integerArrayFromArray($syncServiceArray));
        }
        if(isset($item['version_keys']))
        {
            $upgradeArray = $item['version_keys']['SS'];
            $message->setVersionKeys(DynamoDBUtil:: stringArrayFromArray($upgradeArray));
        }
        return $message;
    }
    
    public function dynamoDBItemFromMessage($dynamoDBClient)
    {
        $item = array();
        $item['messageid'] = $this->messageId();
        
        $allMessages = $this->allLanguageMessages();
        foreach($allMessages as $langCode => $messageHtml)
        {
            if($langCode == LANGUAGE_CODE_ENGLISH)
                $item['message_html'] = $messageHtml;
            else
                $item[$langCode . '_message_html'] = $messageHtml;
        }
        
        $allSubjects = $this->allLanguageSubjects();
        foreach($allSubjects as $langCode => $subject)
        {
            if($langCode == LANGUAGE_CODE_ENGLISH)
                $item['subject'] = $subject;
            else
                $item[$langCode . '_subject'] = $subject;
        }
        
        $item['time_posted'] = $this->timePosted();
        
        if($this->priority() != 0)
           $item['priority'] = $this->priority();
        if($this->deviceTypes() != NULL)
           $item['device_types'] = $this->deviceTypes();
        
        if($this->syncServices() != NULL)
           $item['sync_services'] = $this->syncServices();
        
        if($this->versionKeys() != NULL)
            $item['version_keys'] = $this->versionKeys();
        
        if($this->messageType() != 0)
           $item['message_type'] = $this->messageType();
        if($this->expirationDate() != 0)
           $item['expiration_date'] = $this->expirationDate();
        if($this->accountDurationWeeks() != 0)
            $item['account_duration_weeks'] = $this->accountDurationWeeks();
        if($this->removedFromLookupTables() != 0)
            $item['removed_from_lookup'] = $this->removedFromLookupTables();
        if($this->isTestMessage() != 0)
            $item['is_test_message'] = $this->isTestMessage();
    
        $item = $dynamoDBClient->formatAttributes($item);
    
        return $item;
    }
	
    //Returns whether this message should be sent to a user with the given sync service
    public function shouldBeSentForSyncService($syncServiceVal)
    {
        $syncServices = $this->syncServices();
        //If the sync services array is empty, that means it should be sent to everyone
        if(!empty($syncServices))
        {
            if(!in_array($syncServiceVal, $syncServices))
            {
                return false;
            }
        }
        return true;
    }
    
    //Returns whether this message should be shown to a user with the given device type
    public function shouldBeShownForDeviceType($deviceTypeVal)
    {
        $deviceTypes = $this->deviceTypes();
        
        //If the device types array is empty, that means it should be sent to everyone
        if(!empty($deviceTypes))
        {
            if(!in_array($deviceTypeVal, $deviceTypes))
            {
                return false;
            }
        }
        
        return true;
    }
    
    public static function messageCompare($message1, $message2)
    {
        if($message1->timePosted() > $message2->timePosted())
            return -1;
        if($message1->timePosted() < $message2->timePosted())
            return 1;
        
        return 0;
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

    public function messageHtml()
    {
        if(empty($this->_publicPropertyArray['message_html']))
            return NULL;
        else
            return $this->_publicPropertyArray['message_html'];
    }
    public function setMessageHtml($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['message_html']);
        else
            $this->_publicPropertyArray['message_html'] = $val;
    }

    public function subject()
    {
        if(empty($this->_publicPropertyArray['subject']))
            return NULL;
        else
            return $this->_publicPropertyArray['subject'];
    }
    
    public function setSubject($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['subject']);
        else
            $this->_publicPropertyArray['subject'] = $val;
    }

    public function priority()
    {
        if(empty($this->_publicPropertyArray['priority']))
            return MessagePriority::None;
        else
            return $this->_publicPropertyArray['priority'];
    }

    public function setPriority($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['priority']);
        else
            $this->_publicPropertyArray['priority'] = $val;
    }
    
    public function deviceTypes()
    {
        if(empty($this->_publicPropertyArray['device_types']))
            return NULL;
        else
            return $this->_publicPropertyArray['device_types'];
    }
    
    public function setDeviceTypes($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['device_types']);
        else
            $this->_publicPropertyArray['device_types'] = $val;
    }
    
    public function syncServices()
    {
        if(empty($this->_publicPropertyArray['sync_services']))
            return NULL;
        else
            return $this->_publicPropertyArray['sync_services'];
    }
    
    public function setSyncServices($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['sync_services']);
        else
            $this->_publicPropertyArray['sync_services'] = $val;
    }
    
    public function versionKeys()
    {
        if(empty($this->_publicPropertyArray['version_keys']))
            return NULL;
        else
            return $this->_publicPropertyArray['version_keys'];
    }
    
    public function setVersionKeys($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['version_keys']);
        else
            $this->_publicPropertyArray['version_keys'] = $val;
    }
    
    public function timePosted()
    {
        if(empty($this->_publicPropertyArray['time_posted']))
            return 0;
        else
            return $this->_publicPropertyArray['time_posted'];
    }
    
    public function setTimePosted($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['time_posted']);
        else
            $this->_publicPropertyArray['time_posted'] = $val;
    }
    
    public function expirationDate()
    {
        if(empty($this->_publicPropertyArray['expiration_date']))
            return 0;
        else
            return $this->_publicPropertyArray['expiration_date'];
    }
    
    public function setExpirationDate($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['expiration_date']);
        else
            $this->_publicPropertyArray['expiration_date'] = $val;
    }
    
    public function messageType()
    {
        if(empty($this->_publicPropertyArray['message_type']))
            return MessageType::SystemAlert;
        else
            return $this->_publicPropertyArray['message_type'];
    }
    
    public function setMessageType($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['message_type']);
        else
            $this->_publicPropertyArray['message_type'] = $val;
    }
    
    public function accountDurationWeeks()
    {
        if(empty($this->_publicPropertyArray['account_duration_weeks']))
            return 0;
        else
            return $this->_publicPropertyArray['account_duration_weeks'];
    }
    
    public function setAccountDurationWeeks($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['account_duration_weeks']);
        else
            $this->_publicPropertyArray['account_duration_weeks'] = $val;
    }
    
    public function removedFromLookupTables()
    {
        if(empty($this->_publicPropertyArray['removed_from_lookup']))
            return 0;
        else
            return $this->_publicPropertyArray['removed_from_lookup'];
    }
    
    public function setRemovedFromLookupTables($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['removed_from_lookup']);
        else
            $this->_publicPropertyArray['removed_from_lookup'] = $val;
    }
    
    public function isTestMessage()
    {
        if(empty($this->_publicPropertyArray['is_test_message']))
            return 0;
        else
            return $this->_publicPropertyArray['is_test_message'];
    }
    
    public function setIsTestMessage($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['is_test_message']);
        else
            $this->_publicPropertyArray['is_test_message'] = $val;
    }
    
    private function allLanguageMessages()
    {
        if(!isset($this->_privatePropertyArray['message_languages']))
            return array();
        else
            return $this->_privatePropertyArray['message_languages'];
    }
    
    private function messageForLanguage($langCode)
    {
        if(isset($this->_privatePropertyArray['message_languages']))
        {
            $languages = $this->_privatePropertyArray['message_languages'];
            if(isset($languages[$langCode]))
            {
                return $languages[$langCode];
            }
        }
        return NULL;
    }
    
    public function setMessageForLanguage($val, $langCode)
    {
        $languages = NULL;
        if(isset($this->_privatePropertyArray['message_languages']))
        {
            $languages = $this->_privatePropertyArray['message_languages'];
        }
    
        if(empty($val))
        {
            if($languages != NULL)
                unset($languages[$langCode]);
        }
        else
        {
            if($languages == NULL)
                $languages = array();
            
            $languages[$langCode] = $val;
            
            $this->_privatePropertyArray['message_languages'] = $languages;
        }
    }
    
    private function allLanguageSubjects()
    {
        if(!isset($this->_privatePropertyArray['subject_languages']))
            return array();
        else
            return $this->_privatePropertyArray['subject_languages'];
    }
    
    private function subjectForLanguage($langCode)
    {
        if(isset($this->_privatePropertyArray['subject_languages']))
        {
            $languages = $this->_privatePropertyArray['subject_languages'];
            if(isset($languages[$langCode]))
            {
                return $languages[$langCode];
            }
        }
        return NULL;
    }
    
    public function setSubjectForLanguage($val, $langCode)
    {
        $languages = NULL;
        if(isset($this->_privatePropertyArray['subject_languages']))
        {
            $languages = $this->_privatePropertyArray['subject_languages'];
        }
        
        if(empty($val))
        {
            if($languages != NULL)
                unset($languages[$langCode]);
        }
        else
        {
            if($languages == NULL)
                $languages = array();
            
            $languages[$langCode] = $val;
            
            $this->_privatePropertyArray['subject_languages'] = $languages;
        }
    }
    
   
}
<?php
    
    include_once('TodoOnline/messageCenter/mc_base_sdk.php');
    
    use Aws\DynamoDb\Exception\DynamoDbException;
    
    define('MESSAGE_EXPIRATION_TABLE_NAME', 'mc_message_expiration');
    define('MESSAGE_EXPIRATION_KEY', '0');
    
    class MCMessageExpirationMethods
    {
        //This table is used to track expiration dates of messages so we can look for expired messages
        //and remove them from lookup tables
        public static function createMessageExpirationTable($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array();
            $args['TableName'] = MESSAGE_EXPIRATION_TABLE_NAME;
            $args['KeySchema'] = array
                                 (
                                     //Right now this is just a dummy value (should always be 0) so that we
                                     //can use dynamoDB queries on the expiration_date without having to do a full table scan.
                                     //Seems like a hack but it's the only way to get this to work besides partitioning the data based on
                                     //some hash of the date and querying across several partitions. We shouldn't really need to do that since
                                     //this table is not going to be very large.
                                     "HashKeyElement" => array
                                                          (
                                                            "AttributeName" => "message_expiration_key",
                                                            "AttributeType" => "N"
                                                          ),
                                     //Store the time the message will expire. This means means we can't have 2 system alerts
                                     //that expire in the same second, but that shouldn't really be an issue
                                     "RangeKeyElement" => array
                                                          (
                                                            "AttributeName" => "expiration_date",
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
                error_log("createMessageExpirationTable failed with exception: ".$e->getMessage());
                return $e;
            }
            
            return true;
        }
        
        //Returns an array that can be put in a batchWriteItem request to add the given
        //message to the database
        public static function requestsToAddExpirationEntryForMessage($message)
        {
            if(empty($message))
            {
                error_log("requestsToAddExpirationEntryForMessage called missing message");
                return false;
            }
            
            //If the message doesn't have an expiration date, we don't need to add it to the
            //table, so just return NULL
            if($message->expirationDate() == 0)
                return NULL;
            
            $itemArray = array
                        (
                            'message_expiration_key' => array('N' => MESSAGE_EXPIRATION_KEY),
                            'expiration_date' => array('N' => (string)$message->expirationDate()),
                            'messageid' => array('S' => $message->messageId())
                         );
            
            $requests = array(array('PutRequest' => array('Item' => $itemArray)));
            
            return $requests;
        }
        

        public static function requestsToDeleteExpirationEntryForMessage($message)
        {
            if(empty($message))
            {
                error_log("requestsToDeleteExpirationEntryForMessage called missing message");
                return false;
            }
            
            //If the message doesn't have an expiration date, it won't be in the expiration table,
            //so just return NULL
            if($message->expirationDate() == 0)
                return NULL;
            
            $args = array('TableName' => MESSAGE_EXPIRATION_TABLE_NAME);
            $key = array
                            (
                                 'HashKeyElement' => array('N' => MESSAGE_EXPIRATION_KEY),
                                 'RangeKeyElement' => array('N' => (string)$message->expirationDate())
                             );
            
            return array(array('DeleteRequest' => array('Key' => $key)));
            
        }
        
        
        public static function getExpiredMessages($timestamp, $dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $args = array('TableName' => MESSAGE_EXPIRATION_TABLE_NAME);
            $args['HashKeyValue'] = array('N' => MESSAGE_EXPIRATION_KEY);
            $args['RangeKeyCondition'] = array('AttributeValueList' => array(array('N' => (string)$timestamp)), 'ComparisonOperator' => 'LT');
            $args['ConsistentRead'] = true;
            
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
    
    }

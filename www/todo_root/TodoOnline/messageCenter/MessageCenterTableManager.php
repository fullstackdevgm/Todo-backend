<?php
    
    include_once('TodoOnline/messageCenter/mc_base_sdk.php');
    
    use Aws\DynamoDb\Exception\DynamoDbException;
    
    class MessageCenterTableManager
    {
        
        public static function createAllTables()
        {
            //If we get a resourceInUseException, we're probably trying to create a table
            //that already exists, so fail silently and continue creating the other tables
            $retVal = MCMessage::createMessagesTable();
            if($retVal !== true && $retVal->getExceptionCode() != 'ResourceInUseException')
                return false;
            
            $retVal = MCUserMessage::createUserMessageInfoTable();
            if($retVal !== true && $retVal->getExceptionCode() != 'ResourceInUseException')
                return false;
            
            $retVal = MCMessageLookupHandler::createSystemAlertLookupTable();
            if($retVal !== true && $retVal->getExceptionCode() != 'ResourceInUseException')
                return false;
            
            $retVal = MCMessageLookupHandler::createAccountDurationMessageLookupTable();
            if($retVal !== true && $retVal->getExceptionCode() != 'ResourceInUseException')
                return false;
            
            $retVal = MCMessageLookupHandler::createUpdateBasedMessageLookupTable();
            if($retVal !== true && $retVal->getExceptionCode() != 'ResourceInUseException')
                return false;
            
            $retVal = MCUserMessageLookupHandler::createUserMessageSyncLookupTable();
            if($retVal !== true && $retVal->getExceptionCode() != 'ResourceInUseException')
                return false;
            
            $retVal = MCUserMessageLookupHandler::createUserMessageWebLookupTable();
            if($retVal !== true && $retVal->getExceptionCode() != 'ResourceInUseException')
                return false;
            
            $retVal = MCUserInfo::createUserInfoTable();
            if($retVal !== true && $retVal->getExceptionCode() != 'ResourceInUseException')
                return false;
            
            $retVal = MCMessageExpirationMethods::createMessageExpirationTable();
            if($retVal !== true && $retVal->getExceptionCode() != 'ResourceInUseException')
                return false;
            
            return true;
        }
    
        
        public static function getAllTables($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
        
            try
            {
                $response = $dynamoDBClient->listTables();
                $tableNames = $response['TableNames'];
            }
            catch (DynamoDbException $e)
            {
                error_log("getAllTables failed with exception: ".$e->getMessage());
                return false;
            }
            
            return $tableNames;
        }
        
        public static function deleteAllTables($dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
        
            $tables = MessageCenterTableManager::getAllTables($dynamoDBClient);
            if($tables === false)
            {
                error_log("deleteAllTables unable to get table list");
                return false;
            }
            
            foreach($tables as $tableName)
            {
                if(MessageCenterTableManager::deleteTable($tableName, $dynamoDBClient) == false)
                {
                    error_log("deleteAllTables failed to delete table $tableName");
                    return false;
                }
            }
            
            return true;
        }
        
        public static function deleteTable($tableName, $dynamoDBClient=NULL)
        {
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
                
            if(empty($dynamoDBClient))
                return false;
            
            try
            {
                $dynamoDBClient->deleteTable(array("TableName"=>$tableName));
            }
            catch(DynamoDbException $e)
            {
                error_log("deleteTables failed with exception: ".$e->getMessage());
                return false;
            }
            
            return true;
        }
    }
?>
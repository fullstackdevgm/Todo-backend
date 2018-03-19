<?php
    include_once('TodoOnline/messageCenter/mc_base_sdk.php');
    include_once('TodoOnline/config.php');
    
    use Aws\DynamoDb\Exception\DynamoDbException;
    use Aws\DynamoDb\DynamoDbClient;
    
    class DynamoDBUtil
    {
        public static function getDynamoDBClient()
        {
            try
            {
                $dynamodb = DynamoDbClient::factory(array(
                                                       'key'    => AMAZON_AWS_KEY,
                                                       'secret' => AMAZON_AWS_SECRET,
                                                       'region' => 'us-west-2',
                                                    ));
            }
            catch (DynamoDbException $e)
            {
                error_log("getDynamoDBClient failed with exception: ".$e->getMessage());
                $dynamodb = NULL;
            }
            
            return $dynamodb;
        }
    
        //This is used for converting an "NS" type value from DynamoDB to an
        //actual array of numbers (since it really returns an array of strings)
        public static function integerArrayFromArray($stringArray)
        {
            $convertedArray = array();
            foreach($stringArray as $string)
            {
                $convertedArray[] = intval($string);
            }
            return $convertedArray;
        }
        
        public static function stringArrayFromArray($stringArray)
        {
            $convertedArray = array();
            foreach($stringArray as $string)
            {
                $convertedArray[] = (string) $string;
            }
            return $convertedArray;
        }
        
        //This method retrieves all items for a given query from the server, handling
        //the case where there are more items than can be retrieved in a single query
        public static function getAllItemsForQuery($queryArgs, $dynamoDBClient=NULL)
        {
            if(empty($queryArgs))
            {
                error_log("getAllItemsForQuery called missing queryArgs");
                return false;
            }
            
            if(empty($dynamoDBClient))
                $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
            
            if(empty($dynamoDBClient))
                return false;
            
            $lastEvaluatedKey = NULL;
            $allItems = array();
            
            try
            {
                do
                {
                    if($lastEvaluatedKey != NULL)
                        $queryArgs['ExclusiveStartKey'] = $lastEvaluatedKey;
                    else
                        unset($queryArgs['ExclusiveStartKey']);
                    
                    $result = $dynamoDBClient->query($queryArgs);
                    
                    if(isset($result['Items']))
                    {
                        $allItems = array_merge($allItems, $result['Items']);
                    }
                    if(isset($result['LastEvaluatedKey']))
                    {
                        $lastEvaluatedKey = $result['LastEvaluatedKey'];
                    }
                    else
                    {
                        $lastEvaluatedKey = NULL;
                    }
                    
                }
                while($lastEvaluatedKey != NULL);
            }
            catch(DynamoDbException $e)
            {
                error_log("getAllItemsForQuery failed with exception: ".$e->getMessage());
                return false;
            }
            
            return $allItems;
            
        }

    
    }
?>
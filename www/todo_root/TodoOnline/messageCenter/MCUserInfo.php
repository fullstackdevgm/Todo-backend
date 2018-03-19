<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/messageCenter/mc_base_sdk.php');

use Aws\DynamoDb\Exception\DynamoDbException;

define('USER_INFO_TABLE_NAME', 'mc_user_info');

//This class stores information about a user, such as the most recent time they
//pulled system alerts, or the most recent todo version they've been using.
//This helps us determine which messages need to be sent to a given user.

class MCUserInfo extends TDODBObject
{
    //This table stores per-user message info (which messages have been read, etc)
    public static function createUserInfoTable($dynamoDBClient=NULL)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        $args = array();
        $args['TableName'] = USER_INFO_TABLE_NAME;
        $args['KeySchema'] = array
                             (
                                 "HashKeyElement" => array
                                  (
                                      "AttributeName" => "userid",
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
    
    public function addOrUpdateUserInfo($dynamoDBClient=NULL)
    {
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        if($this->userId() == NULL)
        {
            error_log("Attempting to add user info with no user id");
            return false;
        }
        
        $args = array();
        $args['TableName'] = USER_INFO_TABLE_NAME;
        
        try
        {
            $item = $this->dynamoDBItemFromUserInfo($dynamoDBClient);
            $args['Item'] = $item;
            
            $dynamoDBClient->putItem($args);
        }
        catch(DynamoDbException $e)
        {
            error_log("addUserInfo failed with exception: ".$e->getMessage());
            return false;
        }
        
        return true;
    }
    
    //This method will create a userinfo entry if none exists for the given userid
    public static function getUserInfoForUserId($userid, $dynamoDBClient=NULL)
    {
        if(empty($userid))
        {
            error_log("getUserInfoForUserId called missing messageid or userid");
            return false;
        }
        
        if(empty($dynamoDBClient))
            $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        if(empty($dynamoDBClient))
            return false;
        
        $args = array('TableName' => USER_INFO_TABLE_NAME);
        $args['Key'] = array('HashKeyElement' => array('S' => $userid));
        $args['ConsistentRead'] = true;
        
        try
        {
            $userInfo = NULL;
            $result = $dynamoDBClient->getItem($args);
            
            if(isset($result['Item']))
            {
                $item = $result['Item'];
                $userInfo = MCUserInfo::userInfoFromDynamoDBItem($item);
            }
            else
            {
                $userInfo = new MCUserInfo();
                $userInfo->setUserId($userid);
                $userInfo->setLastCheckedMessagesTimestamp(0);
                $userInfo->setTodoProAccountDurationWeeks(0);
                $userInfo->setLastUpdatedUserMessages(0);
                
                if($userInfo->addOrUpdateUserInfo() == false)
                {
                    error_log("getUserInfoForUserId failed to add new userinfo");
                    return false;
                }
            }
        }
        catch(DynamoDbException $e)
        {
            error_log("getUserInfoForUserId failed with exception: ".$e->getMessage());
            return false;
        }
        
        return $userInfo;
    }
    
    public static function userInfoFromDynamoDBItem($item)
    {
        $userInfo = new MCUserInfo();
        if(isset($item['userid']))
            $userInfo->setUserId($item['userid']['S']);
        if(isset($item['last_checked_messages_timestamp']))
            $userInfo->setLastCheckedMessagesTimestamp(intval($item['last_checked_messages_timestamp']['N']));
        if(isset($item['todo_pro_account_duration_weeks']))
            $userInfo->setTodoProAccountDurationWeeks(intval($item['todo_pro_account_duration_weeks']['N']));
        if(isset($item['last_updated_user_messages']))
            $userInfo->setLastUpdatedUserMessages(intval($item['last_updated_user_messages']['N']));
        
        $appVersions = array();
        $possibleVersions = MCMessage::possibleAppIds();
        foreach($possibleVersions as $version)
        {
            if(isset($item[$version]))
            {
                $appVersions[$version] = $item[$version]['S'];
            }
        }
        
        $userInfo->setAppVersions($appVersions);
        
        return $userInfo;
    }
    
    public function dynamoDBItemFromUserInfo($dynamoDBClient)
    {
        $item = array();
        
        $item['userid'] = $this->userId();
        $item['last_checked_messages_timestamp'] = $this->lastCheckedMessagesTimestamp();
        $item['last_updated_user_messages'] = $this->lastUpdatedUserMessages();
        $item['todo_pro_account_duration_weeks'] = $this->todoProAccountDurationWeeks();
        
        $appVersions = $this->appVersions();
        foreach($appVersions as $key => $value)
        {
            $item[$key] = (string)$value;
        }
        
        $item = $dynamoDBClient->formatAttributes($item);
    
        return $item;
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
    
    //The time we last pulled system alerts for this user
    public function lastCheckedMessagesTimestamp()
    {
        if(empty($this->_publicPropertyArray['last_checked_messages_timestamp']))
            return 0;
        else
            return $this->_publicPropertyArray['last_checked_messages_timestamp'];
    }
    
    public function setLastCheckedMessagesTimestamp($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['last_checked_messages_timestamp']);
        else
            $this->_publicPropertyArray['last_checked_messages_timestamp'] = $val;
    }
    
    //The time we last updated something in the user's user_messages table
    public function lastUpdatedUserMessages()
    {
        if(empty($this->_publicPropertyArray['last_updated_user_messages']))
            return 0;
        else
            return $this->_publicPropertyArray['last_updated_user_messages'];
    }
    
    public function setLastUpdatedUserMessages($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['last_updated_user_messages']);
        else
            $this->_publicPropertyArray['last_updated_user_messages'] = $val;
    }
    
    //The duration of this user's Todo Cloud account (in weeks) last time we pulled their messages.
    //Compare this against the current duration of their account to see if we need to pull
    //more account duration based messages.
    public function todoProAccountDurationWeeks()
    {
        if(empty($this->_publicPropertyArray['todo_pro_account_duration_weeks']))
            return 0;
        else
            return $this->_publicPropertyArray['todo_pro_account_duration_weeks'];
    }
    
    public function setTodoProAccountDurationWeeks($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['todo_pro_account_duration_weeks']);
        else
            $this->_publicPropertyArray['todo_pro_account_duration_weeks'] = $val;
    }
    
    //Array where key is an app id the user has contacted message center with and the
    //value is the version of that app the user was running last time we pulled messages
    public function appVersions()
    {
        if(empty($this->_publicPropertyArray['app_versions']))
            return array();
        else
            return $this->_publicPropertyArray['app_versions'];
    }
    
    public function setAppVersions($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['app_versions']);
        else
            $this->_publicPropertyArray['app_versions'] = $val;
    }
}
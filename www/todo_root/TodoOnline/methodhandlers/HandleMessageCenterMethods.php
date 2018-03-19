<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	include_once('TodoOnline/messageCenter/mc_base_sdk.php');

if($method == "getRecentMessages" || $method == "getUpdatedUnreadMessageCount")
{
    if($method == "getRecentMessages")
    {
        if(!isset($_POST['limit']))
            $limit = 10;
        else
            $limit = intval($_POST['limit']);
    }
    
    $userid = $session->getUserId();
    $user = TDOUser::getUserForUserId($userid);
    
    if(empty($user))
    {
        error_log("$method failed to get user for user id");
        echo '{"success":false}';
        return;
    }
    
    $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
    
    $userInfo = MCUserInfo::getUserInfoForUserId($userid, $dynamoDBClient);
    if(empty($userInfo))
    {
        error_log("$method failed to get userinfo for user");
        echo '{"success":false}';
        return;
    }
    
    if(MCMessageLookupHandler::processNewMessagesForUser($userid, true, $user->creationTimestamp(), 'todoproweb', TDO_VERSION, $dynamoDBClient, $userInfo) == false)
    {
        error_log("$method failed to process new messages for user");
        echo '{"success":false}';
        return;
    }
    
    //If the messages have been updated since the last time we read, update the unread message count from the database
    $lastReadTime = 0;
    if(isset($_SESSION['last_checked_messages']))
        $lastReadTime = intval($_SESSION['last_checked_messages']);
    
    $lastUpdatedTime = intval($userInfo->lastUpdatedUserMessages());
    if($lastReadTime < $lastUpdatedTime)
    {
        $count = MCUserMessage::getUnreadMessageCountForUser($userid, DeviceType::Web, $dynamoDBClient);
        if($count === false)
        {
            error_log("getUpdatedUnreadMessageCount failed to get unread message count for user");
            echo '{"success":false}';
            return;
        }
        $_SESSION['unread_message_count'] = $count;
    }
    else
    {
        $count = 0;
        if(isset($_SESSION['unread_message_count']))
            $count = intval($_SESSION['unread_message_count']);
    }
    
    $currentTime = time();
    $_SESSION['last_checked_messages'] = $currentTime;
    
    if($method == "getRecentMessages")
    {
        $lastKey = NULL;
        if(isset($_POST['last_key']))
        {
            $lastKey = json_decode($_POST['last_key']);
        }
    
        $response = MCUserMessageLookupHandler::getRecentlyPostedMessagesForUser($userid, $limit, DeviceType::Web, SyncService::TodoPro, $lastKey, LANGUAGE_CODE_ENGLISH, $dynamoDBClient);
        if($response == false || !isset($response['messages']))
        {
            error_log("$method failed to get recently posted messages for user");
            echo '{"success":false}';
            return;
        }
        
        $messages = $response['messages'];
        
        $jsonMessages = array();
        foreach($messages as $userMessage)
        {
            $jsonMessage = $userMessage->getPropertiesArray();
            $message = $userMessage->message();
            if($message)
            {
                unset($jsonMessage['message']);
                $jsonMessage = array_merge($jsonMessage, $message->getPropertiesArray());
            }
            
            $jsonMessages[] = $jsonMessage;
        }
        
        $jsonResponse = array('success'=> true , 'messages' => $jsonMessages, 'unread_count' => $count);
        
        if(isset($response['LastEvaluatedKey']))
        {
            $jsonResponse['last_key'] = $response['LastEvaluatedKey'];
        }
        
        echo json_encode($jsonResponse);
    }
    else if($method == "getUpdatedUnreadMessageCount")
    {
        echo '{"success":true, "message_count":'.$count.'}';
    }

}

//Params:
// messages - (required) an array of message ids to update
// update - (required) the update to perform on the messages (can be one of: mark_read, mark_unread, mark_deleted)
else if($method == "updateMessages")
{
    if(!isset($_POST['messages']) || !isset($_POST['update']))
    {
        error_log("updateMessages called missing required parameter: messages or update");
        echo '{"success":false}';
        return;
    }
    
    $update = $_POST['update'];
       
    if($update != 'mark_read' && $update != 'mark_unread' && $update != 'mark_deleted')
    {
       error_log("updateMessages called with invalid update: ".$update);
       echo '{"success":false}';
       return;
    }
    
    $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
    
    if(isset($_SESSION['unread_message_count']))
    {
       $unreadMessageCount = intval($_SESSION['unread_message_count']);
    }
    else
    {
       $unreadMessageCount = MCUserMessage::getUnreadMessageCountForUser($session->getUserId(), DeviceType::Web, $dynamoDBClient);
    }
    
    $millisecondTime = time() * 1000;
    
    $messages = $_POST['messages'];
    foreach($messages as $messageid)
    {
        //It doesn't matter what we pass for the language code since we're not reading the message
       $userMessage = MCUserMessage::getUserMessageForUserIdAndMessageId($session->getUserId(), $messageid, false, LANGUAGE_CODE_ENGLISH, $dynamoDBClient);
       if(empty($userMessage))
       {
            error_log("updateMessages unable to locate UserMessage for given id");
            continue;
       }
       
       $needsUpdate = false;
       $newUserMessage = clone $userMessage;
       if($userMessage->read() == 0 && $update == 'mark_read')
       {
            $needsUpdate = true;
            $newUserMessage->setRead(1);
            $unreadMessageCount--;
       }
       
       if($userMessage->read() > 0 && $update == 'mark_unread')
       {
            $needsUpdate = true;
            $newUserMessage->setRead(0);
            $unreadMessageCount++;
       }
       
       if($userMessage->deleted() == 0 && $update == 'mark_deleted')
       {
            $needsUpdate = true;
            $newUserMessage->setDeleted(1);
            if($newUserMessage->read() == 0)
                $unreadMessageCount--;
       }
       
       if($needsUpdate)
       {
           $newUserMessage->setModDate($millisecondTime);
           $millisecondTime++;
            if(MCUserMessage::updateUserMessage($userMessage, $newUserMessage, true, $dynamoDBClient) == false)
                error_log("updateMessages unable to update user message");
       }
    }
    
    
    $_SESSION['unread_message_count'] = $unreadMessageCount;
    
    echo '{"success":true, "unread_count":'.$unreadMessageCount.'}';
}

?>
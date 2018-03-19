<?php

include_once('TodoOnline/messageCenter/mc_base_sdk.php');


if ($method == "createAllMessageCenterTables")
{
    if(MessageCenterTableManager::createAllTables())
        echo '{"success":true}';
    else
        echo '{"success":false}';
    
}
else if($method == "deleteAllMessageCenterTables")
{
    if(MessageCenterTableManager::deleteAllTables())
        echo '{"success":true}';
    else
        echo '{"success":false}';
}
else if($method == "getAllMessageCenterTables")
{
    $tables = MessageCenterTableManager::getAllTables();
    if($tables !== false)
    {
        $responseArray = array();
        $responseArray['success'] = true;
        $responseArray['tables'] = $tables;

        echo json_encode($responseArray);
    }
    else
        echo '{"success":false}';
}

//Parameters:

//message - (required) the html body of the message
//subject - (required) the subject of the message
//expiration_date - (required) the expiration date for the message
//type - (optional) the type of message (0=system alert, 1=upgrade based, 2=account duration based). Defaults to system alert.
//priority - (optional) the message priority as defined in MCMessage. Defaults to None (0).
//device_types - (optional) the devices the message should go to. If empty the message goes to all devices.
//sync_services - (optional) the sync services the message shoudl go to. If empty the message goes to all sync services.
//account_duration - (required if type=2) the number of weeks after which the account duration based message should be sent.
//version_keys - (required if type=1) an array with version keys indicating the upgrades that will trigger this message, where
//                                    each version key is a concatenation of <app id>_<app version>  

else if($method == "addMessageCenterMessage")
{
    if(!isset($_POST['message']) || !isset($_POST['subject']) || !isset($_POST['expiration_date']))
    {
        error_log("addMessageCenterMessage called missing required parameter message or subject or expiration_date");
        echo '{"success":false, "error":"missing required parameter"}';
        return;
    }
    
    $messageHtmlArray = $_POST['message'];
    $subjectArray = $_POST['subject'];
    
    if(!isset($messageHtmlArray[LANGUAGE_CODE_ENGLISH]) || !isset($subjectArray[LANGUAGE_CODE_ENGLISH]))
    {
        error_log("addMessageCenterMessage called missing english message or subject");
        echo '{"success":false, "error":"English message and subject are required"}';
        return;
    }
    
    $message = new MCMessage();
    
    foreach($messageHtmlArray as $langCode => $messageHtml)
    {
        $message->setMessageForLanguage($messageHtml, $langCode);
    }
    foreach($subjectArray as $langCode => $subject)
    {
        $message->setSubjectForLanguage($subject, $langCode);
    }

    if(!isset($_POST['type']))
        $type = MessageType::SystemAlert;
    else
        $type = intval($_POST['type']);


    if(!isset($_POST['priority']))
        $priority = MessagePriority::None;
    else
        $priority = intval($_POST['priority']);

    $message->setMessageType($type);
    $message->setPriority($priority);
    $message->setExpirationDate(intval($_POST['expiration_date']));
    
    //If this is an account duration type message, the client must send the number
    //of weeks at which to send the message
    if($type == MessageType::AccountDurationBased)
    {
        if(!isset($_POST['account_duration']))
        {
            error_log("addMessageCenterMessage called missing required parameter account_duration");
            echo '{"success":false, "error":"missing required parameter"}';
            return;
        }
        $message->setAccountDurationWeeks(intval($_POST['account_duration']));
    }
    if($type == MessageType::UpgradeBased)
    {
        if(!isset($_POST['version_keys']) || empty($_POST['version_keys']))
        {
            error("addMessageCenterMessage called missing required parameter version_keys");
            echo '{"success":false, "error":"missing required parameter"}';
            return;
        }
        $upgradeArray = DynamoDBUtil::stringArrayFromArray($_POST['version_keys']);
        $message->setVersionKeys($upgradeArray);
    }
    
    if(isset($_POST['device_types']))
    {
        $deviceTypeArray = $_POST['device_types'];
        $deviceTypeArray = DynamoDBUtil::integerArrayFromArray($deviceTypeArray);
        $message->setDeviceTypes($deviceTypeArray);
    }
    
    if(isset($_POST['sync_services']))
    {
        $syncServiceArray = $_POST['sync_services'];
        $syncServiceArray = DynamoDBUtil::integerArrayFromArray($syncServiceArray);
        $message->setSyncServices($syncServiceArray);
    }
    
    if($message->addMessage() == false)
    {
        error_log("addMessageCenterMessage failed to add new message");
        echo '{"success":false}';
        return;
    }
    
    //Return the english copy of the message
    $message->setMessageHtml($messageHtmlArray[LANGUAGE_CODE_ENGLISH]);
    $message->setSubject($subjectArray[LANGUAGE_CODE_ENGLISH]);
    
    $jsonResponse = array();
    $jsonResponse['success'] = true;
    $jsonResponse['message'] = $message->getPropertiesArray();
    echo json_encode($jsonResponse);
}
else if($method == "testMessageCenterMessage")
{
    if(!isset($_POST['message']) || !isset($_POST['subject']))
    {
        error_log("testMessageCenterMessage called missing required parameter message or subject");
        echo '{"success":false, "error":"missing required parameter"}';
        return;
    }
    
    $messageHtmlArray = $_POST['message'];
    $subjectArray = $_POST['subject'];
    
    if(!isset($messageHtmlArray[LANGUAGE_CODE_ENGLISH]) || !isset($subjectArray[LANGUAGE_CODE_ENGLISH]))
    {
        error_log("testMessageCenterMessage called missing english message or subject");
        echo '{"success":false, "error":"English message and subject are required"}';
        return;
    }
    
    $message = new MCMessage();
    
    foreach($messageHtmlArray as $langCode => $messageHtml)
    {
        $message->setMessageForLanguage($messageHtml, $langCode);
    }
    foreach($subjectArray as $langCode => $subject)
    {
        $message->setSubjectForLanguage($subject, $langCode);
    }

    
    $type = MessageType::SystemAlert;
    
    if(!isset($_POST['priority']))
        $priority = MessagePriority::None;
    else
        $priority = intval($_POST['priority']);
    
    $message->setMessageType($type);
    $message->setPriority($priority);
    $message->setIsTestMessage(1);
    
    
    if($message->addMessage() == false)
    {
        error_log("testMessageCenterMessage failed to add new message");
        echo '{"success":false}';
        return;
    }
    
    if(MCUserMessage::sendMessagesToUser($session->getUserId(), array($message), true, NULL) == false)
    {
        error_log("testMessageCenterMessage failed to send message to user");
        echo '{"success":false}';
        return;
    }
    
    $userInfo = MCUserInfo::getUserInfoForUserId($session->getUserId());
    if($userInfo == false)
    {
        error_log("testMessageCenterMessage failed to get userinfo");
    }
    $userInfo->setLastUpdatedUserMessages(time());
    $userInfo->addOrUpdateUserInfo();
    
    
    echo '{"success":true}';
}
else if($method == "getAllMessages")
{
    if(!isset($_POST['start_date']) || !isset($_POST['end_date']))
    {
        error_log("getAllMessages called missing required parameter start_date or end_date");
        echo '{"success":false}';
        return;
    }
    
    $messages = MCMessage::getAllMessages(intval($_POST['start_date']), intval($_POST['end_date']));
    if($messages === false)
    {
        error_log("getAllMessages failed to get messages from database");
        echo '{"success":false}';
        return;
    }
    
    $jsonResponse = array();
    
    $jsonMessages = array();
    foreach($messages as $message)
    {
        $jsonMessages[] = $message->getPropertiesArray();
    }
    
    $jsonResponse['messages'] = $jsonMessages;
       
    $jsonResponse['success'] = true;
    echo json_encode($jsonResponse);
}
else if($method == "getMessagesOfType")
{
    if(!isset($_POST['type']))
    {
        error_log("getMessagesOfType called missing required parameter: type");
        echo '{"success":false}';
        return;
    }
    
    $type = $_POST['type'];
    $messages = NULL;
    
    switch($type)
    {
        case MessageType::SystemAlert:
        {
            $messages = MCMessageLookupHandler::getAllActiveSystemAlertMessages();
            break;
        }
        case MessageType::UpgradeBased:
        {
            $messages = MCMessageLookupHandler::getAllActiveUpgradeBasedMessages();
            break;
        }
        case MessageType::AccountDurationBased:
        {
            $messages = MCMessageLookupHandler::getAllActiveAccountDurationMessages();
            break;
        }
        default:
        {
            error_log("getMessagesOfType called with invalid type: ".$type);
            echo '{"success":false}';
            return;
        }
    }
    
    if($messages === false)
    {
        error_log("getMessagesOfType failed to get messages from database");
        echo '{"success:false}';
        return;
    }
    
    $jsonResponse = array();
    
    $seenMessageIds = array();
    $jsonMessages = array();
    foreach($messages as $message)
    {
        if(in_array($message->messageId(), $seenMessageIds) == true)
            continue;
        
        $seenMessageIds[] = $message->messageId();
        $jsonMessages[] = $message->getPropertiesArray();
    }
    
    $jsonResponse['messages'] = $jsonMessages;
    
    $jsonResponse['success'] = true;
    echo json_encode($jsonResponse);
    
}
else if($method == "updateMessageExpirationDate")
{
    if(!isset($_POST['messageid']) || !isset($_POST['expiration_date']))
    {
        error_log("updateMessageExpirationDate called missing parameter messageid or expiration_date");
        echo '{"success":false}';
        return;
    }
    
    $dynamoDBClient= DynamoDBUtil::getDynamoDBClient();
    
    $messageId = $_POST['messageid'];
    
    $message = MCMessage::getMessageForMessageId($messageId, LANGUAGE_CODE_ENGLISH, $dynamoDBClient);
    if(empty($message))
    {
        error_log("updateMessageExpirationDate failed to get message for message id");
        echo '{"success":false}';
        return;
    }

    $newExpirationDate = intval($_POST['expiration_date']);
    
    if($newExpirationDate == $message->expirationDate())
    {
        //No need to update anything
        error_log("updateMessageExpirationDate called with identical expiration date");
        echo '{"success":true}';
        return;
    }
    
    $isExpired = ($newExpirationDate != 0 && ($newExpirationDate <= time()));
    
    //If the message is being marked as expired and it is still in lookup tables, remove it
    if($message->removedFromLookupTables() == 0 && $isExpired)
    {
        if($message->removeMessage($newExpirationDate, $dynamoDBClient) == false)
        {
            error_log("updateMessageExpirationDate failed to remove expired message");
            echo '{"success":false}';
            return;
        }
    }
    //If the message is being marked as unexpired and is not in lookup tables, add it
    else if($message->removedFromLookupTables() > 0 && !$isExpired)
    {
        $message->setExpirationDate($newExpirationDate);
        //Reset the time this was posted so it will go out to users who checked their messages while it was expired
        $message->setTimePosted(time());
        if($message->reAddMessageToLookupTables($dynamoDBClient) == false)
        {
            error_log("updateMessageExpirationDate failed to add unexpired message");
            echo '{"success":false}';
            return;
        }
    }
    else
    {
        //Otherwise, just update the expiration date on the message
        if($message->udpateExpirationDate($newExpirationDate, $dynamoDBClient) == false)
        {
            error_log("updateMessageExpirationDate failed to update expiration date for message");
            echo '{"success":false}';
            return;
        }
    }
    
    $jsonResponse = array('success' => true);
    $jsonResponse['message'] = $message->getPropertiesArray();
    
    echo json_encode($jsonResponse);
}

?>

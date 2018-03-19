<?php
	
	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/syncmethodhandlers/SyncConstants.php');    
	include_once('TodoOnline/php/SessionHandler.php');	
    
	if(!$session->isLoggedIn())
	{
		error_log("HandleTaskSyncMethods.php called without a valid session");
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
	
	$user = TDOUser::getUserForUserId($session->getUserId());

	if($user == false)
	{
		error_log("HandleTaskSyncMethods.php unable to fetch logged in user: ".$session->getUserId());
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
    
    
    function handleNotificationParameters($notification, $notificationValues, $modNotificationArray)
    {
        $haveUpdatedValues = false;
        
        if(isset($notificationValues['sound_name']))
        {
            $notification->setSoundName($notificationValues['sound_name'], true);
            $haveUpdatedValues = true;
        }
        
        if(isset($notificationValues['triggerdate']))
        {
            $notification->setTriggerDate($notificationValues['triggerdate'], true);
            $haveUpdatedValues = true;
        }
        
        if(isset($notificationValues['triggeroffset']))
        {
            $notification->setTriggerOffset((int)$notificationValues['triggeroffset'], true);
            $haveUpdatedValues = true;
        }
        
        if(isset($notificationValues['taskid']))
        {
            if($notificationValues['taskid'] == "0")
                $notification->setTaskId(NULL);
            else
            {
                $aTask = TDOTask::getTaskForTaskId($notificationValues['taskid']);
                if(empty($aTask))
                {
                    $modNotificationArray['errorcode'] = ERROR_CODE_OBJECT_NOT_FOUND;
                    $modNotificationArray['errordesc'] = ERROR_DESC_OBJECT_NOT_FOUND . " taskid was not a valid parent";
                    error_log("syncTasks error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND. " taskid was not a valid task");
                    return false;
                }
                
                $notification->setTaskId($notificationValues['taskid']);
            }
            $haveUpdatedValues = true;
        }

        return $haveUpdatedValues;
    }
    
    
    
    
    if($method == "syncNotifications")
    {
        $lastErrorCode = 0;
        $lastErrorDesc = NULL;          

        $userModifiedNotifications = array();
        
        $responseArray = array();
        $resultsArray = array();
        
        // Lists are going to be posted in the variables: addLists, updateLists, and deleteLists
        // The values will be a JSON encoded array of list properties like this:
        // $_POST['addNotifications'] = "[{"tmpnotificationid":"AFSDS2345", "taskid":"AFSDS2345"}, {"tmptaskid":"DF2345677", "taskid":"AFSDS2345"}]"
        // $_POST['updateNotifications'] = "[{"notificationid":"AFSDS2345", "taskid":"AFSDS2345"}, {"tmptaskid":"DF2345677", "taskid":"AFSDS2345"}]"
        // $_POST['deleteNotifications'] = "[{"notificationid":"AFSDS2345"}, {"notificationid":"DF2345677"}]"

        // The response will be a single JSON response with arrays of results in keys: addResults, updateResults, and deleteResults like this:
        // [{"results":{"added":[{"tmpnotificationid":"AFSDS2345", "notificationid":"BDCF234234"}, ...], "updated":[...], "deleted":[...]},
        //  {"actions":{"update":[{"notificationid":"AFSDS2345", "taskid":"AFSDS2345"}, ...], "delete":[...]},
        //   "notificationtimestamp":"234523423132"}]
 
        $link = TDOUtil::getDBLink();
        
        if(empty($link))
        {
            error_log("syncNotifications failed to get DBLink");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            return;
        }        
        
        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("syncNotification failed to start transaction");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            TDOUtil::closeDBLink($link);
            return;
        }
        
        if(isset($_POST['addNotifications']) == true)
        {
            $addResults = array();
            
            $addNotificationArray = json_decode($_POST['addNotifications'], true);

            if( ($addNotificationArray === NULL) || empty($addNotificationArray) )
            {
                error_log("syncNotifications had addNotifications that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("syncNotification failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            foreach($addNotificationArray as $notificationToAdd)
            {
                $addedNotificationArray = array();

                if(empty($notificationToAdd['tmpnotificationid']))
                {
                    // if we don't have a tmpListId we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " tmpnotificationid was missing.";
                    
                    $addedNotificationArray['errorcode'] = $lastErrorCode;
                    $addedNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." tmptaskid was missing.");
                    
                    $addResults[] = $addedNotificationArray;
                    continue;
                }
                    
                $addedNotificationArray['tmpnotificationid'] = $notificationToAdd['tmpnotificationid'];
                $tmpTaskId = $notificationToAdd['tmpnotificationid'];
                
                if(!isset($notificationToAdd['taskid']))
                {
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " taskid needs to be specified and you didn't!";
                    
                    $addedNotificationArray['errorcode'] = $lastErrorCode;
                    $addedNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." listid or parentid need to be specified and you gave neither!");
                    $addResults[] = $addedNotificationArray;
                    continue;
                }
                else
                {
                    $notifyTask = TDOTask::getTaskFortaskId($notificationToAdd['taskid'], $link);
                    if(empty($notifyTask))
                    {
                        $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                        $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " taskid was not a valid task";
                        
                        $addedNotificationArray['errorcode'] = $lastErrorCode;
                        $addedNotificationArray['errordesc'] = $lastErrorDesc;
                        error_log("syncNotifications error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND." the taskid sent was not found.");
                        $addResults[] = $addedNotificationArray;
                        continue;
                    }
                    else
                        $taskId = $notifyTask->taskId();
                }
                
                $listid = $notifyTask->listId();
                
                if(TDOList::userCanEditList($listid, $session->getUserId(), $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_DESC_ACCESS_DENIED . " user does not have priveledges to modify the specified list!";
                    
                    $addedNotificationArray['errorcode'] = $lastErrorCode;
                    $addedNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_ACCESS_DENIED." error desc ".ERROR_DESC_ACCESS_DENIED." user does not have priveledges to modify the specified list!");
                    
                    $addResults[] = $addedNotificationArray;
                    continue;
                }

                $newNotification = new TDOTaskNotification();
                
                if(handleNotificationParameters($newNotification, &$notificationToAdd, &$addedNotificationArray) == false)
                {
                    $addResults[] = $addedNotificationArray;
                    continue;
                }
                    
                if($newNotification->addTaskNotification($link))
                {
                    $addedNotificationArray['notificationid'] = $newNotification->notificationId();
                    $userModifiedNotifications[] = $newNotification->notificationId();
                    
                    //TDOChangeLog::addChangeLog($listid, $session->getUserId(), $newNotification->notificationId(), $notifyTask->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_SYNC);
                }
                else
                {
                    $lastErrorCode = ERROR_CODE_ERROR_ADDING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_ADDING_OBJECT;
                    
                    $addedNotificationArray['errorcode'] = $lastErrorCode;
                    $addedNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_ERROR_ADDING_OBJECT." error desc ".ERROR_DESC_ERROR_ADDING_OBJECT);
                    $addResults[] = $addedNotificationArray;
                    continue;
                }
                
                $addResults[] = $addedNotificationArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncNotification failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['added'] = $addResults;
        }
        
        if(isset($_POST['updateNotifications']) == true)
        {
            $jsonValues = array();
            $haveUpdatedValues = false;            

            $updateResults = array();

            $updatedNotificationArray = json_decode($_POST['updateNotifications'], true);
            
            if( ($updatedNotificationArray === NULL) || empty($updatedNotificationArray) )
            {
                error_log("syncNotifications had updateNotifications that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("updateNotifications failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            foreach($updatedNotificationArray as $notificationToUpdate)
            {
                $updateNotificationArray = array();
                
                if(empty($notificationToUpdate['notificationid']))
                {
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " notificationid was missing.";
                    
                    $updateNotificationArray['errorcode'] = $lastErrorCode;
                    $updateNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." taskid was missing.");
                    $updateResults[] = $updateNotificationArray;
                    continue;
                }
                
                $notificationId = $notificationToUpdate['notificationid'];
                $updateNotificationArray['notificationid'] = $notificationId;

                $notification = TDOTaskNotification::getNotificationForNotificationId($notificationId, $link);
                if(empty($notification))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " unable to locate notification from notificationid.";
                    
                    $updateNotificationArray['errorcode'] = $lastErrorCode;
                    $updateNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND."  unable to locate notification from notificationid.");
                    $updateResults[] = $updateNotificationArray;
                    continue;
                }
                
                
                if(!isset($notificationToUpdate['taskid']))
                {
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " taskid needs to be specified and you didn't!";
                    
                    $updateNotificationArray['errorcode'] = $lastErrorCode;
                    $updateNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." listid or parentid need to be specified and you gave neither!");
                    $updateResults[] = $updateNotificationArray;
                    continue;
                }
                else
                {
                    $notifyTask = TDOTask::getTaskFortaskId($notificationToUpdate['taskid'], $link);
                    if(empty($notifyTask))
                    {
                        $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                        $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " taskid was not a valid task";
                        
                        $updateNotificationArray['errorcode'] = $lastErrorCode;
                        $updateNotificationArray['errordesc'] = $lastErrorDesc;
                        error_log("syncNotifications error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND." the taskid sent was not found.");
                        $updateResults[] = $updateNotificationArray;
                        continue;
                    }
                    else
                        $taskId = $notifyTask->taskId();
                }
                
                $listid = $notifyTask->listId();
                
                if(TDOList::userCanEditList($listid, $session->getUserId(), $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_DESC_ACCESS_DENIED . " user does not have priveledges to modify the specified list!";
                    
                    $updateNotificationArray['errorcode'] = $lastErrorCode;
                    $updateNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_ACCESS_DENIED." error desc ".ERROR_DESC_ACCESS_DENIED." user does not have priveledges to modify the specified list!");
                    
                    $updateResults[] = $updateNotificationArray;
                    continue;
                }
                
                if(handleNotificationParameters($notification, &$notificationToUpdate, &$updateNotificationArray) == false)
                {
                    $updateResults[] = $updateNotificationArray;
                    continue;
                }

                if($notification->updateTaskNotification($link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ERROR_UPDATING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_UPDATING_OBJECT;
                    
                    $updateNotificationArray['errorcode'] = $lastErrorCode;
                    $updateNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_ERROR_UPDATING_OBJECT." error desc ".ERROR_DESC_ERROR_UPDATING_OBJECT);
                }
                    
                $userModifiedNotifications[] = $notification->notificationId();
                
                $updateResults[] = $updateNotificationArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncNotifications failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['updated'] = $updateResults;
        }

        if(isset($_POST['deleteNotifications']) == true)
        {
            $deleteResults = array();
            
            $deletedNotificationsArray = json_decode($_POST['deleteNotifications'], true);
            
            if( ($deletedNotificationsArray === NULL) || empty($deletedNotificationsArray) )
            {
                error_log("syncNotifications had deleteNotifications that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("deleteNotifications failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            foreach($deletedNotificationsArray as $notificationToDelete)
            {
                $deleteNotificationArray = array();
                
                if(empty($notificationToDelete['notificationid']))
                {
                    // if we don't have a tmpListId we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " notificationid was missing.";
                    
                    $deleteNotificationArray['errorcode'] = $lastErrorCode;
                    $deleteNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." listId was missing.");
                    $deleteResults[] = $deleteNotificationArray;
                    continue;
                }
                
                $notificationId = $notificationToDelete['notificationid'];
                $deleteNotificationArray['notificationid'] = $notificationId;
                
                $notification = TDOTaskNotification::getNotificationForNotificationId($notificationId, $link);
                if(empty($notification))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " unable to locate notification from notificationid.";
                    
                    $deleteNotificationArray['errorcode'] = $lastErrorCode;
                    $deleteNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND."  unable to locate notification from notificationid.");
                    $deleteResults[] = $deleteNotificationArray;
                    continue;
                }

                
                $notifyTask = TDOTask::getTaskFortaskId($notification->taskId(), $link);
                if(empty($notifyTask))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_CODE_OBJECT_NOT_FOUND . " taskid was not a valid task";
                    
                    $deleteNotificationArray['errorcode'] = $lastErrorCode;
                    $deleteNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_CODE_OBJECT_NOT_FOUND." the taskid was not found.");
                    $deleteResults[] = $deleteNotificationArray;
                    continue;
                }
                else
                    $taskId = $notifyTask->taskId();

                $listid = $notifyTask->listId();
                
                if(TDOList::userCanEditList($listid, $session->getUserId(), $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_CODE_ACCESS_DENIED . " user does not have priveledges to modify the specified list!";
                    
                    $deleteNotificationArray['errorcode'] = $lastErrorCode;
                    $deleteNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_ACCESS_DENIED." error desc ".ERROR_CODE_ACCESS_DENIED." user does not have priveledges to modify the specified list!");
                    
                    $deleteResults[] = $deleteNotificationArray;
                    continue;
                }

                if(TDOTaskNotification::deleteTaskNotification($notificationId, $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ERROR_DELETING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_DELETING_OBJECT;
                    
                    $deleteNotificationArray['errorcode'] = $lastErrorCode;
                    $deleteNotificationArray['errordesc'] = $lastErrorDesc;
                    error_log("syncNotifications error ".ERROR_CODE_ERROR_DELETING_OBJECT." error desc ".ERROR_DESC_ERROR_DELETING_OBJECT);
                }
                else
                {
                    $userModifiedNotifications[] = $notificationId;
                }
                
                $deleteResults[] = $deleteNotificationArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncNotifications failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['deleted'] = $deleteResults;
        }
        
        $responseArray['results'] = $resultsArray;

        // now go and find all of the changes that need to be sent back
        // from the server

        $addNotificationTimeStamp = true;
        
        $actionsArray = array();
        
        $notificationsArray = array();
        
        // we need to do this for each list individually
        // first get all of the lists and build timestamps for each one
        $lists = TDOList::getListsForUser($session->getUserId(), false, $link);
        foreach($lists as $list)
        {
            $listId = $list->listId();
            
            // check to see if the client passed up a timestamp for this list
            // if not set it to zero and get all tasks
            if(isset($_POST[$listId]))
                $timestamp = $_POST[$listId];
            else
                $timestamp = 0;
            
            // check to see if the timestamp the client sent is the same as what the list currently is
            // if so, or if the client sent nothing and our stamp is 1, we don't need to do the query
            if( ($timestamp == $list->notificationTimestamp()) || (($timestamp == 0) && ($list->notificationTimestamp() == 1)) )
            {
                //error_log("Sync requested for a list that had no notification changes: " . $list->name());
            }
            else
            {
                //error_log("Sync requested for a list that has notification changes: "  . $list->name());

                $notifications = TDOTaskNotification::getNotificationsForUserModifiedSince($session->getUserId(), $listId, $timestamp, false, $link);
                if(isset($notifications))
                {
                    foreach($notifications as $notification)
                    {
                        if(in_array($notification->notificationId(), $userModifiedNotifications) == false)
                        {
                            $notificationProperties = $notification->getPropertiesArray();
                            $notificationsArray[] = $notificationProperties;
                        }
                    }
                }
                else
                {
                    $addNotificationTimeStamp = false;
                    
                    $lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
                    $lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;
                    
                    $responseArray['errorcode'] = $lastErrorCode;
                    $responseArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
                }
            }
        }
        
        if(count($notificationsArray) > 0)
            $actionsArray['update'] = $notificationsArray;


        $notificationsArray = array();
        
        foreach($lists as $list)
        {
            $listId = $list->listId();
            
            // check to see if the client passed up a timestamp for this list
            // if not set it to zero and get all tasks
            if(isset($_POST[$listId]))
                $timestamp = $_POST[$listId];
            else
                $timestamp = 0;
        
            // only get deleted notifications if they have synced before with us and
            // only if the timestamp doesn't equal the recorded timestamp, otherwise
            // nothing has happened
            if( ($timestamp != 0) && ($timestamp != $list->notificationTimestamp()) )
            {
                $notifications = TDOTaskNotification::getNotificationsForUserModifiedSince($session->getUserId(), $listId, $timestamp, true, $link);
                if(isset($notifications))
                {
                    foreach($notifications as $notification)
                    {
                        if(in_array($notification->notificationId(), $userModifiedNotifications) == false)
                        {
                            $notificationProperties = $notification->getPropertiesArray();
                            $notificationsArray[] = $notificationProperties;
                        }
                    }
                }
                else
                {
                    $addNotificationTimeStamp = false;
                    
                    $lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
                    $lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;
                    
                    $responseArray['errorcode'] = $lastErrorCode;
                    $responseArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
                }
            }
        }
        if(count($notificationsArray) > 0)
            $actionsArray['delete'] = $notificationsArray;
        
        $responseArray['actions'] = $actionsArray;
        
        if($addNotificationTimeStamp == true)
        {
            $allNotificationTimeStamps = TDOTaskNotification::getAllNotificationTimestampsForUser($session->getUserId(), $lists, $link);
            if($allNotificationTimeStamps != NULL)
                $responseArray['allnotificationtimestamps'] = $allNotificationTimeStamps;
        }
        
        $jsonResponse = json_encode($responseArray);
        if(json_last_error() != JSON_ERROR_NONE)
        {
            mysql_query("ROLLBACK", $link);
            
            $lastErrorCode = ERROR_CODE_ERROR_INVALID_UTF8_IN_NOTIFICATIONS;
            $lastErrorDesc = ERROR_DESC_ERROR_INVALID_UTF8_IN_NOTIFICATIONS;
            
            outputSyncError($lastErrorCode, $lastErrorDesc);
            error_log("json_encoding the notifications from the server failed with error: " . json_last_error() . " Reporting ".$lastErrorCode." error desc ".$lastErrorDesc." For user: " . $session->getUserId());
        }
        else
        {
            if(!mysql_query("COMMIT", $link))
            {
                $lastErrorCode = ERROR_CODE_DB_LINK_FAILED;
                $lastErrorDesc = ERROR_DESC_DB_LINK_FAILED;
                
                error_log("syncNotifications failed to commit transaction");
                outputSyncError($lastErrorCode, $lastErrorDesc);
                
                mysql_query("ROLLBACK", $link);
            }
            else
                echo $jsonResponse;
        }
        
        TDOUtil::closeDBLink($link);        
        
        if($lastErrorCode != 0)
            TDODevice::updateDeviceForUserAndSession($session->getUserId(), $session->getSessionId(), $lastErrorCode, $method . ": " . $lastErrorDesc);
        else
            TDODevice::updateDeviceForUserAndSession($session->getUserId(), $session->getSessionId());
    }
    
?>

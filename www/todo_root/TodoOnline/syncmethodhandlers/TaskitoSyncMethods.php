<?php
	
	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/syncmethodhandlers/SyncConstants.php');    
	include_once('TodoOnline/php/SessionHandler.php');	
    
	if(!$session->isLoggedIn())
	{
		error_log("TaskitoSyncMethods.php called without a valid session");
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
	
	$user = TDOUser::getUserForUserId($session->getUserId());

	if($user == false)
	{
		error_log("TaskitoSyncMethods.php unable to fetch logged in user: ".$session->getUserId());
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
    
    
    function handleTaskitoParameters($taskito, $taskitoValues, $modTaskitoArray)
    {
        $haveUpdatedValues = false;
        
        if(isset($taskitoValues['name']))
        {
            $taskito->setName($taskitoValues['name'], true);
            $haveUpdatedValues = true;
        }
        
        if(isset($taskitoValues['completiondate']))
        {
            $compDateValue = TDOUtil::dateFromGMT((double)$taskitoValues['completiondate']);
            $taskito->setCompletionDate($compDateValue, true);
            $haveUpdatedValues = true;
        }
        else
        {
            error_log("completion date not set");
            $taskito->setCompletionDate(0, true);
        }
        
        if(isset($taskitoValues['sort_order']))
        {
            $taskito->setSortOrder($taskitoValues['sort_order']);
            $haveUpdatedValues = true;
        }
        
        if(!empty($taskitoValues['parentid']))
        {
            $parentTask = TDOTask::getTaskForTaskId($taskitoValues['parentid']);
            if(empty($parentTask))
            {
                $modTaskArray['errorcode'] = ERROR_CODE_OBJECT_NOT_FOUND;
                $modTaskArray['errordesc'] = ERROR_DESC_OBJECT_NOT_FOUND . " parentid was not a valid parent";
                error_log("syncTasks error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND. " parentid was not a valid parent");
                return false;
            }

            $taskito->setParentId($taskitoValues['parentid']);
        }
        
        $haveUpdatedValues = true;

        return $haveUpdatedValues;
    }
    
    
    
    if($method == "syncTaskitos")
    {
        $lastErrorCode = 0;
        $lastErrorDesc = NULL;         
        
        $userModifiedTaskitos = array();
        
        $responseArray = array();
        $resultsArray = array();
        
        
        
        // Lists are going to be posted in the variables: addLists, updateLists, and deleteLists
        // The values will be a JSON encoded array of list properties like this:
        // $_POST['addTaskitos'] = "[{"tmptaskid":"AFSDS2345", "name":"New List"}, {"tmptaskid":"DF2345677", "name":"New List 2"}]"
        // $_POST['updateTaskitos'] = "[{"taskid":"AFSDS2345", "name":"New List"}, {"taskid":"DF2345677", "name":"New List 2"}]"
        // $_POST['deleteTaskitos'] = "[{"taskid":"AFSDS2345"}, {"taskid":"DF2345677"}]"

        // The response will be a single JSON response with arrays of results in keys: addResults, updateResults, and deleteResults like this:
        // [{"results":{"added":[{"tmpListId":"AFSDS2345", "listId":"BDCF234234"}, ...], "updated":[...], "deleted":[...]},
        //  {"actions":{"update":[{"taskid":"AFSDS2345", "name":"task name"}, ...], "delete":[...]},
        //   "tasktimestamp":"234523423132"}]
 
        $link = TDOUtil::getDBLink();
        
        if(empty($link))
        {
            error_log("syncTaskitos failed to get DBLink");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            return;
        }        

        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("syncTaskitos failed to start transaction");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            TDOUtil::closeDBLink($link);
            return;
        }
        
        
        if(isset($_POST['addTaskitos']) == true)
        {
            $addResults = array();
            
            $addTaskArray = json_decode($_POST['addTaskitos'], true);
            
            if( ($addTaskArray === NULL) || empty($addTaskArray) )
            {
                error_log("syncTaskitos had addTaskitos that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("syncTaskitos failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }

            foreach($addTaskArray as $taskToAdd)
            {
                $addedTaskArray = array();

                if(empty($taskToAdd['tmptaskitoid']))
                {
                    // if we don't have a tmpListId we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " tmptaskitoid was missing.";
                    
                    $addedTaskArray['errorcode'] = $lastErrorCode;
                    $addedTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." tmptaskitoid was missing.");
                    
                    $addResults[] = $addedTaskArray;
                    continue;
                }
                    
                $addedTaskArray['tmptaskitoid'] = $taskToAdd['tmptaskitoid'];
                $tmpTaskId = $taskToAdd['tmptaskitoid'];
                
                if(empty($taskToAdd['parentid']))
                {
					$taskName = "Unknown checklist item name";
					if (!empty($taskToAdd['name'])) {
						$taskName = $taskToAdd['name'];
					}
					
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " parentid need to be specified for taskito: " . $taskName;
                    
                    $addedTaskArray['errorcode'] = $lastErrorCode;
                    $addedTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." parentid need to be specified for taskito (username: " . TDOUser::usernameForUserId($session->getUserId()) . "): " . $taskName);
                    $addResults[] = $addedTaskArray;
                    continue;
                }

                $parentTask = TDOTask::getTaskFortaskId($taskToAdd['parentid']);
                if(empty($parentTask))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " parentid was not found!";
                    
                    $addedTaskArray['errorcode'] = $lastErrorCode;
                    $addedTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc " . ERROR_DESC_OBJECT_NOT_FOUND . " parentid was not found.");
                    $addResults[] = $addedTaskArray;
                    continue;
                }

                $listid = $parentTask->listId();
                
                if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_CODE_ACCESS_DENIED . " user does not have priveledges to modify the specified list!";
                    
                    $addedTaskArray['errorcode'] = $lastErrorCode;
                    $addedTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_ACCESS_DENIED." error desc ".ERROR_CODE_ACCESS_DENIED." user does not have priveledges to modify the specified list!");
                    
                    $addResults[] = $addedTaskArray;
                    continue;
                }

                if (empty($taskToAdd['name']))
                {
                    $taskToAdd['name'] = 'Unnamed Task';
                }
		$taskName = trim($taskToAdd['name']);
		if (empty($taskName))
		{
			$taskToAdd['name'] = 'Unnamed Task';
		}
                
                $newTask = new TDOTaskito();
                    
                if(handleTaskitoParameters($newTask, &$taskToAdd, &$addedTaskArray) == false)
                {
                    $addResults[] = $addedTaskArray;
                    continue;
                }
                
                if($newTask->addObject($link))
                {
                    $addedTaskArray['taskitoid'] = $newTask->taskitoId();
                    $userModifiedTaskitos[] = $newTask->taskitoId();
                    
                    TDOChangeLog::addChangeLog($listid, $session->getUserId(), $newTask->taskitoId(), $newTask->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_ADD, CHANGE_LOCATION_SYNC, NULL, NULL, NULL, NULL, $link);
                }
                else
                {
                    $lastErrorCode = ERROR_CODE_ERROR_ADDING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_ADDING_OBJECT;
                    
                    $addedTaskArray['errorcode'] = $lastErrorCode;
                    $addedTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_ERROR_ADDING_OBJECT." error desc ".ERROR_DESC_ERROR_ADDING_OBJECT);
                    $addResults[] = $addedTaskArray;
                    continue;
                }
                
                $addResults[] = $addedTaskArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncTaskitos failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['added'] = $addResults;
        }
        
        if(isset($_POST['updateTaskitos']) == true)
        {
            $jsonValues = array();
            $haveUpdatedValues = false;            

            $updateResults = array();

            $updatedTaskitosArray = json_decode($_POST['updateTaskitos'], true);
            
            if( ($updatedTaskitosArray === NULL) || empty($updatedTaskitosArray) )
            {
                error_log("syncTaskitos had updateTaskitos that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("syncTasks failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            foreach($updatedTaskitosArray as $taskToUpdate)
            {
                $updateTaskArray = array();
                
                if(empty($taskToUpdate['taskitoid']))
                {
                    // if we don't have a taskid we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " taskitoid was missing.";
                    
                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." taskitoid was missing.");
                    $updateResults[] = $updateTaskArray;
                    continue;
                }
                
                $taskId = $taskToUpdate['taskitoid'];
                $updateTaskArray['taskitoid'] = $taskId;

                $task = TDOTaskito::taskitoForTaskitoId($taskId, $link);
                if(empty($task))
                {
                    // if we don't have a taskid we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " unable to locate task from taskitoid.";
                    
                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND."  unable to locate task from taskitoid.");
                    $updateResults[] = $updateTaskArray;
                    continue;
                }
                
                if(!empty($taskToAdd['parentid']))
                    $parentid = $taskToAdd['parentid'];
                else
                    $parentid = $task->parentId();
                
                $parentTask = TDOTask::getTaskFortaskId($parentid, $link);
                if(empty($parentTask))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " parent task was not found!";
                    
                    $addedTaskArray['errorcode'] = $lastErrorCode;
                    $addedTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc " . ERROR_DESC_OBJECT_NOT_FOUND . " parent task was not found!");
                    $addResults[] = $addedTaskArray;
                    continue;
                }
                
                $listId = $parentTask->listId();
                
                if(TDOList::userCanEditList($listId, $session->getUserId()) == false)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_DESC_ACCESS_DENIED;
                    
                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_ACCESS_DENIED." error desc ".ERROR_DESC_ACCESS_DENIED);
                    $updateResults[] = $updateTaskArray;
                    continue;
                }
				
				// If the taskito on the server is deleted, that means that some
				// other client deleted the taskito. However, if code makes it
				// here with that condition, the device synchronizing now must
				// have made a change to the taskito and it should be marked as
				// incomplete.
				if ($task->deleted()) {
					$task->setDeleted(0);
					
					// Call handleTaskitoParameters() but don't pay attention to
					// the result, because we at least need to update the taskito
					// in order to make it not marked as deleted. Calling
					// handleTaskitoParameters() will update any other property
					// that may have changed.
					handleTaskitoParameters($task, &$taskToUpdate, &$updateTaskArray);
				} else {
					if(handleTaskitoParameters($task, &$taskToUpdate, &$updateTaskArray) == false)
					{
						$updateResults[] = $updateTaskArray;
						continue;
					}
				}

                if($task->updateObject($link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ERROR_UPDATING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_UPDATING_OBJECT;
                    
                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_ERROR_UPDATING_OBJECT." error desc ".ERROR_DESC_ERROR_UPDATING_OBJECT);
                }
                else
                {
                    $userModifiedTaskitos[] = $task->taskitoId();
                }
                
                $updateResults[] = $updateTaskArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncTaskitos failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['updated'] = $updateResults;
        }

        if(isset($_POST['deleteTaskitos']) == true)
        {
            $deleteResults = array();
            
            $deleteTaskArray = json_decode($_POST['deleteTaskitos'], true);
            
            if( ($deleteTaskArray === NULL) || empty($deleteTaskArray) )
            {
                error_log("syncTaskitos had deleteTaskitos that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("syncTaskitos failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            foreach($deleteTaskArray as $taskToDelete)
            {
                $deleteTaskArray = array();
                
                if(empty($taskToDelete['taskitoid']))
                {
                    // if we don't have a tmpListId we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " taskitoid was missing.";
                    
                    $deleteTaskArray['errorcode'] = $lastErrorCode;
                    $deleteTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." taskitoid was missing.");
                    $deleteResults[] = $deleteTaskArray;
                    continue;
                }
                
                $taskId = $taskToDelete['taskitoid'];
                $deleteTaskArray['taskitoid'] = $taskId;
                
                $task = TDOTaskito::taskitoForTaskitoId($taskId, $link);
                if(empty($task))
                {
                    // if we don't have a taskid we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " unable to locate task from taskitoid.";
                    
                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND."  unable to locate task from taskitoid.");
                    $updateResults[] = $updateTaskArray;
                    continue;
                }
                
                if(!empty($taskToAdd['parentid']))
                    $parentid = $taskToAdd['parentid'];
                else
                    $parentid = $task->parentId();
                
                $parentTask = TDOTask::getTaskFortaskId($parentid, $link);
                if(empty($parentTask))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " parent task was not found!";
                    
                    $addedTaskArray['errorcode'] = $lastErrorCode;
                    $addedTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc " . ERROR_DESC_OBJECT_NOT_FOUND . " parent task was not found!");
                    $addResults[] = $addedTaskArray;
                    continue;
                }
                
                $listId = $parentTask->listId();
                
                if(TDOList::userCanEditList($listId, $session->getUserId(), $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_DESC_ACCESS_DENIED;
                    
                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_ACCESS_DENIED." error desc ".ERROR_DESC_ACCESS_DENIED);
                    $updateResults[] = $updateTaskArray;
                    continue;
                }

                if(TDOTaskito::deleteObject($taskId, $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ERROR_DELETING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_DELETING_OBJECT;
                    
                    $deleteTaskArray['errorcode'] = $lastErrorCode;
                    $deleteTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_ERROR_DELETING_OBJECT." error desc ".ERROR_DESC_ERROR_DELETING_OBJECT);
                }
                else
                {
                    $userModifiedTaskitos[] = $taskId;
                }
                
                $deleteResults[] = $deleteTaskArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncTaskitos failed to commit transaction");
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
		
		$numOfTasksLimit = 0;
		if (isset($_POST['numOfTasks']))
			$numOfTasksLimit = $_POST['numOfTasks'];

        $addTaskTimeStamp = true;
        
        $actionsArray = array();
		
		$listOffsetsArray = array();
        
        $tasksArray = array();

        // we need to do this for each list individually
        // first get all of the lists and build timestamps for each one
        $lists = TDOList::getListsForUser($session->getUserId(), false, $link);
        foreach($lists as $list)
        {
			// Check to see if we've already filled our limit of taskitos
			$currentTaskCount = count($tasksArray);
			if (($numOfTasksLimit > 0) && ($currentTaskCount >= $numOfTasksLimit))
			{
				break;
			}
			
            $listId = $list->listId();
            
            // check to see if the client passed up a timestamp for this list
            // if not set it to zero and get all tasks
            if(isset($_POST[$listId]))
                $timestamp = $_POST[$listId];
            else
                $timestamp = 0;
            
            // check to see if the timestamp the client sent is the same as what the list currently is
            // if so, or if the client sent nothing and our stamp is 1, we don't need to do the query
            if( ($timestamp == $list->taskitoTimestamp()) || (($timestamp == 0) && ($list->taskitoTimestamp() == 1)) )
            {
                //error_log("Sync requested for a list that had no taskito changes: " . $list->name());
            }
            else
            {
                //error_log("Sync requested for a list that has taskito changes: "  . $list->name());
				
				// Check to see whether there is an offset specified. If so, the
				// client is asking to sync taskitos in a paged fashion.
				$offset = 0;
				$offsetKey = $listId . 'activeTaskitosOffset';
				if (isset($_POST[$offsetKey]))
				{
					$offset = $_POST[$offsetKey];
				}

                $tasks = TDOTaskito::getTaskitosForUserModifiedSince($session->getUserId(), $listId, $timestamp, $offset, $numOfTasksLimit, false, $link); // get all non deleted tasks
                if(isset($tasks))
                {
					$returningTaskCount = 0;
                    foreach($tasks as $task)
                    {
                        if(in_array($task->taskitoId(), $userModifiedTaskitos) == false)
                        {
                            $taskProperties = $task->getPropertiesArray();
                            $tasksArray[] = $taskProperties;
							$returningTaskCount++;
                        }
                    }
					$listOffsetsArray[$listId . 'activeTaskitosOffset'] = $offset + $returningTaskCount;
                }
                else
                {
                    $addTaskTimeStamp = false;
                    
                    $lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
                    $lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;
                    
                    $responseArray['errorcode'] = $lastErrorCode;
                    $responseArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTaskitos error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
                }
            }
        }
        if(count($tasksArray) > 0)
            $actionsArray['update'] = $tasksArray;
		
		// Only proceed with getting deleted taskitos if we haven't already
		// filled our limit of taskitos to return.
		if (($numOfTasksLimit == 0) || ( ($numOfTasksLimit > 0) && (count($tasksArray) < $numOfTasksLimit)) )
		{
			$tasksArray = array();
			
			foreach($lists as $list)
			{
				// Check to see that we haven't already filled our limit of
				// taskitos.
				$currentTaskCount = count($tasksArray);
				if (($numOfTasksLimit > 0) && ($currentTaskCount >= $numOfTasksLimit))
				{
					break;
				}
				
				$listId = $list->listId();
				
				// check to see if the client passed up a timestamp for this list
				// if not set it to zero and get all tasks
				if(isset($_POST[$listId]))
					$timestamp = $_POST[$listId];
				else
					$timestamp = 0;
				
				// only get deleted taskitos if they have synced before with us and
				// only if the timestamp doesn't equal the recorded timestamp, otherwise
				// nothing has happened
				if( ($timestamp != 0) && ($timestamp != $list->taskitoTimestamp()) )
				{
					$offset = 0;
					$offsetKey = $listId . 'deletedTaskitosOffset';
					if (isset($_POST[$offsetKey]))
					{
						$offset = $_POST[$offsetKey];
					}
					
					$tasks = TDOTaskito::getTaskitosForUserModifiedSince($session->getUserId(), $listId, $timestamp, $offset, $numOfTasksLimit, true, $link);
					if(isset($tasks))
					{
						$returningTaskCount = 0;
						foreach($tasks as $task)
						{
							if(in_array($task->taskitoId(), $userModifiedTaskitos) == false)
							{
								// we only need to return the taskitoid on a delete
								$taskProperties = array();
								$taskProperties['taskitoid'] = $task->taskitoId();
								$tasksArray[] = $taskProperties;
								$returningTaskCount++;
							}
						}
						$listOffsetsArray[$listId . 'deletedTaskitosOffset'] = $offset + $returningTaskCount;
					}
					else
					{
						$addTaskTimeStamp = false;
						
						$lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
						$lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;
						
						$responseArray['errorcode'] = $lastErrorCode;
						$responseArray['errordesc'] = $lastErrorDesc;
						error_log("syncTaskitos error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
					}
				}
			}
			
			if(count($tasksArray) > 0)
				$actionsArray['delete'] = $tasksArray;
		}
		
        $responseArray['actions'] = $actionsArray;
		$responseArray['listOffsets'] = $listOffsetsArray;
		
        if($addTaskTimeStamp == true)
        {
            $allTaskitoTimeStamps = TDOTaskito::getAllTaskitoTimestampsForUser($session->getUserId(), $lists, $link);
            if($allTaskitoTimeStamps != NULL)
                $responseArray['alltaskitotimestamps'] = $allTaskitoTimeStamps;
        }
		

        $jsonResponse = json_encode($responseArray);
        if(json_last_error() != JSON_ERROR_NONE)
        {
            mysql_query("ROLLBACK", $link);
            
            $lastErrorCode = ERROR_CODE_ERROR_INVALID_UTF8_IN_TASKITOS;
            $lastErrorDesc = ERROR_DESC_ERROR_INVALID_UTF8_IN_TASKITOS;
            
            outputSyncError($lastErrorCode, $lastErrorDesc);
            error_log("json_encoding the subtasks from the server failed with error: " . json_last_error() . " Reporting ".$lastErrorCode." error desc ".$lastErrorDesc." For user: " . $session->getUserId());
        }
        else
        {
            if(!mysql_query("COMMIT", $link))
            {
                $lastErrorCode = ERROR_CODE_DB_LINK_FAILED;
                $lastErrorDesc = ERROR_DESC_DB_LINK_FAILED;

                error_log("syncTaskitos failed to commit transaction");
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

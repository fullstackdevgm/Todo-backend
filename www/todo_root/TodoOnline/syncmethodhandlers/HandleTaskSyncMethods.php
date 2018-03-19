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

    $didUpdateTaskIdForListChange = false;

    function handleTaskParameters($task, & $taskValues, & $modTaskArray, $isUpdate=true, & $jsonValues=NULL)
    {
        $haveUpdatedValues = false;

        if(isset($taskValues['name']))
        {
            $oldTaskName = $task->name();

            $task->setName($taskValues['name'], true);

            if($jsonValues !== NULL && $oldTaskName != $task->name())
            {
                $jsonValues['old-taskName'] = (string)$oldTaskName;
                $jsonValues['taskName'] = (string)$task->name();
            }

            $haveUpdatedValues = true;
        }

        //Update the task type at the beginning so that if it's a project we will set the
        //project_duedate/project_priority values when setting the due date/priority
        if(isset($taskValues['tasktype']))
        {
            $oldTaskType = $task->taskType();

            $task->setTaskType($taskValues['tasktype']);

            if($jsonValues !== NULL && $oldTaskType != $task->taskType())
            {
                $jsonValues['old-taskType'] = (int)$oldTaskType;
                $jsonValues['taskType'] = (int)$task->taskType();
            }
            $haveUpdatedValues = true;
        }

        if(isset($taskValues['tasktypedata']))
        {
            $oldTypeData = $task->typeData();

            if(empty($taskValues['tasktypedata']))
                $task->setTypeData(NULL);
            else
            {
                $task->setTypeData($taskValues['tasktypedata']);
            }

            if($jsonValues !== NULL && $oldTypeData != $task->typeData())
            {
                $jsonValues['old-typeData'] = (string)$oldTypeData;
                $jsonValues['typeData'] = (string)$task->typeData();
            }

            $haveUpdatedValues = true;
        }


        if(isset($taskValues['priority']))
        {
            $oldPriority = $task->priority();

            $task->setPriority($taskValues['priority'], true);

            if($jsonValues !== NULL && $oldPriority != $task->priority())
            {
                $jsonValues['old-priority'] = (int)$oldPriority;
                $jsonValues['priority'] = (int)$task->priority();
            }

            $haveUpdatedValues = true;
        }

        if(isset($taskValues['starred']))
        {
            $oldStarred = $task->starred();

            $task->setStarred((int)$taskValues['starred'], true);

            if($jsonValues !== NULL && $oldStarred != $task->starred())
            {
                $jsonValues['old-starred'] = (int)$oldStarred;
                $jsonValues['starred'] = (int)$task->starred();
            }
            $haveUpdatedValues = true;
        }

        if(isset($taskValues['duedate']))
        {
            $oldDueDate = $task->dueDate();

            $dueDateValue = TDOUtil::dateFromGMT((double)$taskValues['duedate']);

            if(!empty($dueDateValue))
            {
                $dueDateHasTime = false;
                if(isset($taskValues['duedatehastime']) && $taskValues['duedatehastime'] != 0)
                {
                    $dueDateHasTime = true;
                }

                $task->setDueDateHasTime($dueDateHasTime);
                if(!$dueDateHasTime)
                {
                    $dueDateValue = TDOUtil::normalizedDateFromGMT($dueDateValue);
                }
            }
            else
            {
                $task->setDueDateHasTime(false);
            }

            $task->setDueDate($dueDateValue, true);

            if($jsonValues !== NULL && $oldDueDate != $task->dueDate())
            {
                $jsonValues['old-taskDueDate'] = (double)$oldDueDate;
                $jsonValues['taskDueDate'] = (double)$task->dueDate();
            }

            $haveUpdatedValues = true;
        }

        if(isset($taskValues['completiondate']))
        {
            $oldCompletionDate = $task->completionDate();

            $compDateValue = TDOUtil::dateFromGMT((double)$taskValues['completiondate']);
            $task->setCompletionDate($compDateValue, true);

            if($jsonValues !== NULL && $oldCompletionDate != $task->completionDate())
            {
                $jsonValues['old-completiondate'] = (double)$oldCompletionDate;
                $jsonValues['completiondate'] = (double)$task->completionDate();
            }

            $haveUpdatedValues = true;
        }

        if(isset($taskValues['note']))
        {
            $oldTaskNote = $task->note();

            $task->setNote($taskValues['note'], true);

            if(TDOTask::noteIsTooLarge($task->note()))
            {
                $modTaskArray['errorcode'] = ERROR_CODE_OVERSIZED_NOTE;
                $modTaskArray['errordesc'] = ERROR_DESC_OVERSIZED_NOTE;
                error_log("syncTasks error ".ERROR_CODE_OVERSIZED_NOTE." error desc ".ERROR_DESC_OVERSIZED_NOTE);
                return false;
            }

            if($jsonValues !== NULL && $oldTaskNote != $task->note())
            {
                $jsonValues['old-taskNote'] = (string)$oldTaskNote;
                $jsonValues['taskNote'] = (string)$task->note();
            }

            $haveUpdatedValues = true;
        }

        if($isUpdate)
        {
            if(isset($taskValues['tags']))
            {
                if($jsonValues !== NULL)
                {
                    $oldTags = TDOTag::getTagStringForTask($task->taskId());
                }

                TDOTag::removeAllTagsFromTask($task->taskId());

                $tagValues = explode(",", $taskValues['tags']);

                foreach($tagValues as $tagValue)
                {
                    TDOTag::addTagNameToTask($tagValue, $task->taskId());
                }

                if($jsonValues !== NULL)
                {
                    $newTags = TDOTag::getTagStringForTask($task->taskId());
                    if($oldTags != $newTags)
                    {
                        $jsonValues['old-tags'] = (string)$oldTags;
                        $jsonValues['tags'] = (string)$newTags;
                    }
                }

                $haveUpdatedValues = true;
            }
        }

        if(isset($taskValues['sortorder']))
        {
            $oldSortOrder = $task->sortOrder();

            $task->setSortOrder($taskValues['sortorder']);

            if($jsonValues !== NULL && $oldSortOrder != $task->sortOrder())
            {
                $jsonValues['old-sortOrder'] = (int)$oldSortOrder;
                $jsonValues['sortOrder'] = (int)$task->sortOrder();
            }

            $haveUpdatedValues = true;
        }

        if(isset($taskValues['parentid']))
        {
            $oldParentId = $task->parentId();

            if($taskValues['parentid'] == "0")
                $task->setParentId(NULL);
            else
            {
                $parentTask = TDOTask::getTaskForTaskId($taskValues['parentid']);
                if(empty($parentTask) || ($parentTask->deleted() && $task->deleted() == 0))
                {
                    $modTaskArray['errorcode'] = ERROR_CODE_PARENT_TASK_NOT_FOUND;
                    $modTaskArray['errordesc'] = ERROR_DESC_PARENT_TASK_NOT_FOUND . " parentid was not a valid parent";
                    error_log("syncTasks error ".ERROR_CODE_PARENT_TASK_NOT_FOUND." error desc ".ERROR_DESC_PARENT_TASK_NOT_FOUND. " parentid was not a valid parent");
                    return false;
                }

                if($parentTask->isProject() == false)
                {
                    $modTaskArray['errorcode'] = ERROR_CODE_PARENT_TASK_NOT_PROJECT;
                    $modTaskArray['errordesc'] = ERROR_DESC_PARENT_TASK_NOT_PROJECT . " parentid was not a project";
                    error_log("syncTasks error ".ERROR_CODE_PARENT_TASK_NOT_PROJECT." error desc ".ERROR_DESC_PARENT_TASK_NOT_PROJECT. " parentid was not a project on task" . $task->name());
                    return false;
                }

                $task->setParentId($taskValues['parentid']);
            }

            if($jsonValues !== NULL && $oldParentId != $task->parentId())
            {
                $jsonValues['old-parentid'] = (string)$oldParentId;
                $jsonValues['parentid'] = (string)$task->parentId();
            }
            $haveUpdatedValues = true;
        }

        if(!empty($taskValues['contextid']))
        {
            if($taskValues['contextid'] != "0")
            {
                $ctx = TDOContext::getContextForContextid($taskValues['contextid']);
                if(empty($ctx))
                {
                    $modTaskArray['errorcode'] = ERROR_CODE_OBJECT_NOT_FOUND;
                    $modTaskArray['errordesc'] = ERROR_DESC_OBJECT_NOT_FOUND;
                    error_log("syncTasks error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND. " contextid was not a valid context: '" . $taskValues['contextid'] ."'");
                    return false;
                }
            }
        }

        if(isset($taskValues['repeattype']))
        {
            $oldRecurrenceType = $task->recurrenceType();

            $task->setRecurrenceType($taskValues['repeattype']);

            if($jsonValues !== NULL && $oldRecurrenceType != $task->recurrenceType())
            {
                $jsonValues['old-recurrenceType'] = (int)$oldRecurrenceType;
                $jsonValues['recurrenceType'] = (int)$task->recurrenceType();
            }

            $haveUpdatedValues = true;
        }

        if(isset($taskValues['advancedrepeat']))
        {
            $oldRepeatString = $task->advancedRecurrenceString();

            $task->setAdvancedRecurrenceString($taskValues['advancedrepeat']);
            $haveUpdatedValues = true;

            if($jsonValues !== NULL && $oldRepeatString != $task->advancedRecurrenceString())
            {
                $jsonValues['old-advancedRecurrenceString'] = (string)$oldRepeatString;
                $jsonValues['advancedRecurrenceString'] = (string)$task->advancedRecurrenceString();
            }
        }

        if(isset($taskValues['startdate']))
        {

            $oldStartDate = $task->startDate();

            $startDateValue = TDOUtil::dateFromGMT((double)$taskValues['startdate']);

            if(!empty($startDateValue))
            {
                $startDateValue = TDOUtil::normalizedDateFromGMT($startDateValue);
            }

            $task->setStartDate($startDateValue, true);

            if($jsonValues !== NULL && $oldStartDate != $task->startDate())
            {
                $jsonValues['old-taskStartDate'] = (double)$oldStartDate;
                $jsonValues['taskStartDate'] = (double)$task->startDate();
            }

            $haveUpdatedValues = true;
        }

        if(isset($taskValues['locationalert']))
        {
            $oldLocationAlertType = $task->parseLocationAlertType();
            $oldLocationAlertAddress = $task->parseLocationAlertAddress();

            $task->setLocationAlert($taskValues['locationalert']);

            if($jsonValues !== NULL)
            {
                if($oldLocationAlertType != $task->parseLocationAlertType())
                {
                    $jsonValues['old-locationAlertType'] = (int)$oldLocationAlertType;
                    $jsonValues['locationAlertType'] = (int)$task->parseLocationAlertType();
                }
                if($oldLocationAlertAddress != $task->parseLocationAlertAddress())
                {
                    $jsonValues['old-locationAlertAddress'] = (string)$oldLocationAlertAddress;
                    $jsonValues['locationAlertAddress'] = (string)$task->parseLocationAlertAddress();
                }
            }

            $haveUpdatedValues = true;
        }

        if(isset($taskValues['expansionproperties']))
        {
            if(!empty($taskValues['expansionproperties']))
                error_log("Sync does not yet handle expansionproperties, but the value was: ".$taskValues['expansionproperties']);
        }
        if(isset($taskValues['assigneduserid']))
        {
            $oldAssignedUserId = $task->assignedUserId();

            $task->setAssignedUserId($taskValues['assigneduserid']);

            if($jsonValues !== NULL && $oldAssignedUserId != $task->assignedUserId())
            {
                $jsonValues['old-assignedUserId'] = (string)$oldAssignedUserId;
                $jsonValues['assignedUserId'] = (string)$task->assignedUserId();
            }

            $haveUpdatedValues = true;
        }


        return $haveUpdatedValues;
    }


    if($method == "syncTasks")
    {
        $lastErrorCode = 0;
        $lastErrorDesc = NULL;

        $userModifiedTasks = array();

        $responseArray = array();
        $resultsArray = array();

        // Lists are going to be posted in the variables: addLists, updateLists, and deleteLists
        // The values will be a JSON encoded array of list properties like this:
        // $_POST['addTasks'] = "[{"tmptaskid":"AFSDS2345", "name":"New List"}, {"tmptaskid":"DF2345677", "name":"New List 2"}]"
        // $_POST['updateTasks'] = "[{"taskid":"AFSDS2345", "name":"New List"}, {"taskid":"DF2345677", "name":"New List 2"}]"
        // $_POST['deleteTasks'] = "[{"taskid":"AFSDS2345"}, {"taskid":"DF2345677"}]"

        // The response will be a single JSON response with arrays of results in keys: addResults, updateResults, and deleteResults like this:
        // [{"results":{"added":[{"tmpListId":"AFSDS2345", "listId":"BDCF234234"}, ...], "updated":[...], "deleted":[...]},
        //  {"actions":{"update":[{"taskid":"AFSDS2345", "name":"task name"}, ...], "delete":[...]},
        //   "tasktimestamp":"234523423132"}]

        $link = TDOUtil::getDBLink();

        if(empty($link))
        {
            error_log("syncTasks failed to get DBLink");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            return;
        }

        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("syncTasks failed to start transaction");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            TDOUtil::closeDBLink($link);
            return;
        }

        if(isset($_POST['addTasks']) == true)
        {
            $addResults = array();

            $addTaskArray = json_decode($_POST['addTasks'], true);

            if( ($addTaskArray === NULL) || empty($addTaskArray) )
            {
                error_log("syncTasks had addTasks that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
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

            foreach($addTaskArray as $taskToAdd)
            {
                $addedTaskArray = array();

                if(empty($taskToAdd['tmptaskid']))
                {
                    // if we don't have a tmpListId we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " tmptaskid was missing.";
                    $addedTaskArray['errorcode'] = $lastErrorCode;
                    $addedTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." tmptaskid was missing.");

                    $addResults[] = $addedTaskArray;
                    continue;
                }

                $addedTaskArray['tmptaskid'] = $taskToAdd['tmptaskid'];
                $tmpTaskId = $taskToAdd['tmptaskid'];

                if(empty($taskToAdd['listid']))
                {
                    if(!empty($taskToAdd['parentid']))
                    {
                        $parentTask = TDOTask::getTaskForTaskId($taskToAdd['parentid'], $link);
                        if(empty($parentTask))
                        {
                            $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                            $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " listid or parentid was missing.";
                            $addedTaskArray['errorcode'] = $lastErrorCode;
                            $addedTaskArray['errordesc'] = $lastErrorDesc;
                            error_log("syncTasks error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." listid or parentid was missing.");
                            $addResults[] = $addedTaskArray;
                            continue;
                        }
                        else
                            $listid = $parentTask->listId();
                    }
                    else
                    {
                        $listid = TDOList::getUserInboxId($session->getUserId(), false, $link);
                    }
                }
                else
                    $listid = $taskToAdd['listid'];

                switch ($listid)
                {
                    case "all":
                    case "focus":
                    case "starred":
                    case "today":
                    case "inbox":
                        $listid = TDOList::getUserInboxId($session->getUserId(), false, $link);
                        break;
                }

                if(TDOList::userCanEditList($listid, $session->getUserId(), $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_CODE_ACCESS_DENIED . " user does not have priveledges to modify the specified list!";

                    $addedTaskArray['errorcode'] = $lastErrorCode;
                    $addedTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_ACCESS_DENIED." error desc ".ERROR_CODE_ACCESS_DENIED." user does not have priveledges to modify the specified list!");

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
                $newTask = new TDOTask();

                $newTask->setListid($listid);

                if(handleTaskParameters($newTask, $taskToAdd, $addedTaskArray, false) == false)
                {
                    $addResults[] = $addedTaskArray;
                    continue;
                }

                if($newTask->addObject($link))
                {
                    if(isset($taskToAdd['contextid']))
                    {
                        if($taskToAdd['contextid'] == "0")
                            TDOContext::assignTaskToContext($newTask->taskId(), NULL, $session->getUserId(), $link);
                        else
                        {
                            TDOContext::assignTaskToContext($newTask->taskId(), $taskToAdd['contextid'], $session->getUserId(), $link);
                        }
                    }

                    if(isset($taskToAdd['tags']))
                    {
                        $tagValues = explode(",", $taskToAdd['tags']);

                        foreach($tagValues as $tagValue)
                        {
                            TDOTag::addTagNameToTask($tagValue, $newTask->taskId());
                        }
                    }

                    $parentId = $newTask->parentId();
                    if($parentId != NULL && strlen($parentId) > 0)
                    {
                        TDOTask::fixupChildPropertiesForTask(TDOTask::getTaskForTaskId($parentId), true, $link);
                    }

                    $addedTaskArray['taskid'] = $newTask->taskId();
                    $userModifiedTasks[] = $newTask->taskId();

                    //If the task is completed, add a change_type_modify log because it might be a completed recurring task, not really
                    //a new task at all.
                    if($newTask->completionDate() != 0)
                    {
                        $jsonValues = array('old-completiondate' => 0, 'completiondate' => (double)$newTask->completionDate());
                        $jsonValues = json_encode($jsonValues, JSON_FORCE_OBJECT);

                        TDOChangeLog::addChangeLog($listid, $session->getUserId(), $newTask->taskId(), $newTask->name(), ITEM_TYPE_TASK, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_SYNC, NULL, NULL, $jsonValues, NULL, $link);
                    }
                    else
                    {
                        TDOChangeLog::addChangeLog($listid, $session->getUserId(), $newTask->taskId(), $newTask->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_SYNC, NULL, NULL, NULL, NULL, $link);
                    }
                }
                else
                {
                    $lastErrorCode = ERROR_CODE_ERROR_ADDING_OBJECT;
                    $lastErrorDesc = ERROR_CODE_ERROR_ADDING_OBJECT;

                    $addedTaskArray['errorcode'] = $lastErrorCode;
                    $addedTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_ERROR_ADDING_OBJECT." error desc ".ERROR_CODE_ERROR_ADDING_OBJECT);
                    $addResults[] = $addedTaskArray;
                    continue;
                }


                $addResults[] = $addedTaskArray;
            }

//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncTasks failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }

            $resultsArray['added'] = $addResults;
        }

        if(isset($_POST['updateTasks']) == true)
        {
            $updateResults = array();

            $updatedTasksArray = json_decode($_POST['updateTasks'], true);

            if( ($updatedTasksArray === NULL) || empty($updatedTasksArray) )
            {
                error_log("syncTasks had updateTasks that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
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

            foreach($updatedTasksArray as $taskToUpdate)
            {
                $updateTaskArray = array();

                if(empty($taskToUpdate['taskid']))
                {
                    // if we don't have a taskid we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " taskid was missing.";

                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." taskid was missing.");
                    $updateResults[] = $updateTaskArray;
                    continue;
                }

                $taskId = $taskToUpdate['taskid'];
                $updateTaskArray['taskid'] = $taskId;

                $task = TDOTask::getTaskForTaskId($taskId, $link);
                if(empty($task))
                {
                    // if we don't have a taskid we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " unable to locate task from taskid.";

                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND."  unable to locate task from taskid.");
                    $updateResults[] = $updateTaskArray;
                    continue;
                }


                if(isset($taskToUpdate['listid']))
                {
                    $listId = $taskToUpdate['listid'];

                    if($listId == "0")
                        $listId = TDOList::getUserInboxId($session->getUserId(), false, $link);
                }
                else
                {
                    $listId = TDOTask::getListIdForTaskId($taskId, $link);
                }

                if(empty($listId))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND;

                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND);
                    $updateResults[] = $updateTaskArray;
                    continue;
                }

                if(TDOList::userCanEditList($listId, $session->getUserId(), $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_DESC_ACCESS_DENIED;

                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_ACCESS_DENIED." error desc ".ERROR_DESC_ACCESS_DENIED);
                    $updateResults[] = $updateTaskArray;
                    continue;
                }

                $didUpdateList = false;

                if(isset($taskToUpdate['listid']))
                {
                    //If the listid is different from the old listid, we need to delete and re-add the task
                    $oldListId = TDOTask::getListIdForTaskId($taskId, $link);
                    if($oldListId != $listId)
                    {
                        if(TDOTask::moveTaskToList($task, $listId, $link) == false)
                        {
                            $lastErrorCode = ERROR_CODE_ERROR_UPDATING_OBJECT;
                            $lastErrorDesc = ERROR_DESC_ERROR_UPDATING_OBJECT;

                            $updateTaskArray['errorcode'] = $lastErrorCode;
                            $updateTaskArray['errordesc'] = $lastErrorDesc;
                            error_log("syncTasks error ".ERROR_CODE_ERROR_UPDATING_OBJECT." error desc ".ERROR_DESC_ERROR_UPDATING_OBJECT);
                            $updateResults[] = $updateTaskArray;
                            continue;
                        }
                        else
                        {
                            //If the user moved the task between lists, we should add a change delete for the old list
                            //and an add for the new list. Wait to insert the add log until all changes have been processed
                            $didUpdateList = true;

                            // CRG - taking this out due the number of people claiming duplicate deletion notifications
                            //TDOChangeLog::addChangeLog($oldListId, $session->getUserId(), $taskId, $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_DELETE, CHANGE_LOCATION_SYNC, NULL, NULL, NULL, NULL, $link);

                            if($taskId != $task->taskId())
                            {
                                $didUpdateTaskIdForListChange = true;
                                $updateTaskArray['new-taskid'] = $task->taskId();
//                              error_log("Moved the task ".$task->name()." to a new list");
                            }
                        }
                    }

                }

                $taskWasComplete = $task->completionDate() != 0;
				$taskWasDeleted = $task->deleted() != 0;

                $jsonChangeValues = array();
                if(handleTaskParameters($task, $taskToUpdate, $updateTaskArray, true, $jsonChangeValues) == false)
                {
                    $updateResults[] = $updateTaskArray;
                    if($didUpdateList)
                        TDOChangeLog::addChangeLog($listId, $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_SYNC, NULL, NULL, NULL, NULL, $link);

                    continue;
                }

                if($taskWasComplete && $task->completionDate() == 0)
                {
                    $result = $task->moveFromCompletedTable($link);
                }
                else if($taskWasComplete == false && $task->completionDate() != 0)
                {
                    $result = $task->moveToCompletedTable($link);
                }
				else if ($taskWasDeleted && $task->deleted() == 1)
				{
					$result = $task->moveFromDeletedTable($link);
				}
                else
                {
                    $result = $task->updateObject($link);
                }


                if($result == false)
                {
                    $lastErrorCode = ERROR_CODE_ERROR_UPDATING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_UPDATING_OBJECT;

                    $updateTaskArray['errorcode'] = $lastErrorCode;
                    $updateTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_ERROR_UPDATING_OBJECT." error desc ".ERROR_DESC_ERROR_UPDATING_OBJECT);
                }
                else
                {
                    $parentId = $task->parentId();
                    if($parentId != NULL && strlen($parentId) > 0)
                    {
                        TDOTask::fixupChildPropertiesForTask(TDOTask::getTaskForTaskId($parentId), $link);
                    }
                    if($task->isProject())
                    {
                        TDOTask::fixupChildPropertiesForTask($task, $link);
                    }

                    if(isset($taskToUpdate['contextid']))
                    {
                        if($taskToUpdate['contextid'] == "0")
                            TDOContext::assignTaskToContext($task->taskId(), NULL, $session->getUserId(), $link);
                        else
                        {
                            TDOContext::assignTaskToContext($task->taskId(), $taskToUpdate['contextid'], $session->getUserId(), $link);
                        }
                    }

                    $jsonChangeValues = json_encode($jsonChangeValues, JSON_FORCE_OBJECT);

                    if($didUpdateList)
                        TDOChangeLog::addChangeLog($listId, $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_SYNC, NULL, NULL, $jsonChangeValues, NULL, $link);
                    else
                        TDOChangeLog::addChangeLog($listId, $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_SYNC, NULL, NULL, $jsonChangeValues, NULL, $link);

                    if(!$didUpdateTaskIdForListChange)
                        $userModifiedTasks[] = $task->taskId();
                }

                $updateResults[] = $updateTaskArray;
            }

//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncTasks failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }

            $resultsArray['updated'] = $updateResults;
        }

        if(isset($_POST['deleteTasks']) == true)
        {
            $deleteResults = array();

            $deleteTaskArray = json_decode($_POST['deleteTasks'], true);

            if( ($deleteTaskArray === NULL) || empty($deleteTaskArray) )
            {
                error_log("syncTasks had deleteTasks that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
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

            foreach($deleteTaskArray as $taskToDelete)
            {
                $deleteTaskArray = array();

                if(empty($taskToDelete['taskid']))
                {
                    // if we don't have a tmpListId we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " listId was missing.";

                    $deleteTaskArray['errorcode'] = $lastErrorCode;
                    $deleteTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_MISSING_REQUIRED_PARAMETERS." error desc ".ERROR_DESC_MISSING_REQUIRED_PARAMETERS." listId was missing.");
                    $deleteResults[] = $deleteTaskArray;
                    continue;
                }

                $taskId = $taskToDelete['taskid'];
                $deleteTaskArray['taskid'] = $taskId;

                $listId = TDOTask::getListIdForTaskId($taskId, $link);
                if(empty($listId))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND;

                    $deleteTaskArray['errorcode'] = $lastErrorCode;
                    $deleteTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_OBJECT_NOT_FOUND." error desc ".ERROR_DESC_OBJECT_NOT_FOUND);
                    $updateResults[] = $deleteTaskArray;
                    continue;
                }

                if(TDOList::userCanEditList($listId, $session->getUserId(), $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_DESC_ACCESS_DENIED;

                    $deleteTaskArray['errorcode'] = $lastErrorCode;
                    $deleteTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_ACCESS_DENIED." error desc ".ERROR_DESC_ACCESS_DENIED);
                    $updateResults[] = $deleteTaskArray;
                    continue;
                }

                $task = TDOTask::getTaskFortaskId($taskId);
                if(empty($task))
                {
                    $lastErrorCode = ERROR_CODE_ERROR_DELETING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_DELETING_OBJECT;

                    $deleteTaskArray['errorcode'] = $lastErrorCode;
                    $deleteTaskArray['errordesc'] = $lastErrorDesc;
                    error_log("syncTasks error ".ERROR_CODE_ERROR_DELETING_OBJECT." error desc ".ERROR_DESC_ERROR_DELETING_OBJECT);
                }
                else
                {
                    if($task->deleted())
                    {
                        // if it's already deleted, add it to the modified tasks and move on without creating a change log for it
                        $userModifiedTasks[] = $taskId;
                    }
                    else
                    {
                        if(TDOTask::deleteObject($taskId, $link, true) == false)
                        {
                            $lastErrorCode = ERROR_CODE_ERROR_DELETING_OBJECT;
                            $lastErrorDesc = ERROR_DESC_ERROR_DELETING_OBJECT;

                            $deleteTaskArray['errorcode'] = $lastErrorCode;
                            $deleteTaskArray['errordesc'] = $lastErrorDesc;
                            error_log("syncTasks error ".ERROR_CODE_ERROR_DELETING_OBJECT." error desc ".ERROR_DESC_ERROR_DELETING_OBJECT);
                        }
                        else
                        {
                            $parentId = TDOTask::getParentIdForTaskId($taskId, $link);
                            if($parentId != NULL && strlen($parentId) > 0)
                            {
                                TDOTask::fixupChildPropertiesForTask(TDOTask::getTaskForTaskId($parentId, $link), $link);
                            }

                            $name = TDOTask::getNameForTask($taskId);
                            TDOChangeLog::addChangeLog($listId, $session->getUserId(), $taskId, $name, ITEM_TYPE_TASK, CHANGE_TYPE_DELETE, CHANGE_LOCATION_SYNC, NULL, NULL, NULL, NULL, $link);

                            $userModifiedTasks[] = $taskId;
                        }
                    }
                }

                $deleteResults[] = $deleteTaskArray;
            }

//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncTasks failed to commit transaction");
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
			// Check to see that we haven't already filled our limit of tasks
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
            if( ($timestamp == $list->taskTimestamp()) || (($timestamp == 0) && ($list->taskTimestamp() == 1)) )
            {
                //error_log("Sync requested for a list that had no task changes: " . $list->name());
            }
            else
            {
                //error_log("Sync requested for a list that has task changes: "  . $list->name());

				//
				// Get active tasks
				//
				// Check to see whether there is an offset specified. If so, the
				// client is asking to sync tasks in a paged fashion.
				$offset = 0;
				$offsetKey = $listId . 'activeTasksOffset';
				if (isset($_POST[$offsetKey]))
				{
					$offset = $_POST[$offsetKey];
				}

				$results = TDOTask::getActiveTasksForUserModifiedSince($session->getUserId(), $timestamp, $listId, $offset, $numOfTasksLimit, false, $link);
				if (isset($results))
				{
					$tasks = $results['tasks'];

					if(isset($tasks) && count($tasks) > 0)
					{
						$returningTaskCount = 0;
						foreach($tasks as $task)
						{
							if(in_array($task->taskId(), $userModifiedTasks) == false)
							{
								$taskProperties = $task->getPropertiesArray(false, $link);
								$tasksArray[] = $taskProperties;
								$returningTaskCount++;
							}
						}

						$listOffsetsArray[$listId . 'activeTasksOffset'] = $offset + $returningTaskCount;
					}
				}
				else
				{
					$addTaskTimeStamp = false;

					$lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
					$lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;

					$responseArray['errorcode'] = $lastErrorCode;
					$responseArray['errordesc'] = $lastErrorDesc;
					error_log("syncTasks error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
				}

				//
				// Get subtasks
				//
				$offset = 0;
				$offsetKey = $listId . 'activeSubtasksOffset';
				if (isset($_POST[$offsetKey]))
				{
					$offset = $_POST[$offsetKey];
				}

				$results = TDOTask::getActiveTasksForUserModifiedSince($session->getUserId(), $timestamp, $listId, $offset, $numOfTasksLimit, true, $link);
				if (isset($results))
				{
					$tasks = $results['tasks'];

					if(isset($tasks) && count($tasks) > 0)
					{
						$returningTaskCount = 0;
						foreach($tasks as $task)
						{
							if(in_array($task->taskId(), $userModifiedTasks) == false)
							{
								$taskProperties = $task->getPropertiesArray(false, $link);
								$tasksArray[] = $taskProperties;
								$returningTaskCount++;
							}
						}

						$listOffsetsArray[$listId . 'activeSubtasksOffset'] = $offset + $returningTaskCount;
					}
				}
				else
				{
					$addTaskTimeStamp = false;

					$lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
					$lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;

					$responseArray['errorcode'] = $lastErrorCode;
					$responseArray['errordesc'] = $lastErrorDesc;
					error_log("syncTasks error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
				}

				//
				// Get completed tasks
				//
				$offset = 0;
				$offsetKey = $listId . 'completedTasksOffset';
				if (isset($_POST[$offsetKey]))
				{
					$offset = $_POST[$offsetKey];
				}

				// If this is an initial sync, $timestamp will be set to 0 and we should
				// send in something special for completed tasks so that ALL completed
				// tasks are NOT sent back to the client. We should only send back 2
				// weeks worth of completed tasks on an initial sync.
				$completedTimestamp = $timestamp;
				if ($completedTimestamp == 0) {
					$oneDayInSeconds = 86400;
					$completedTimestamp = time() - ($oneDayInSeconds * 14);
				}

				$results = TDOTask::getCompletedTasksForUserModifiedSince($session->getUserId(), $completedTimestamp, $listId, $offset, $numOfTasksLimit, $link);
				if (isset($results))
				{
					$tasks = $results['tasks'];

					if(isset($tasks) && count($tasks) > 0)
					{
						$returningTaskCount = 0;
						foreach($tasks as $task)
						{
							if(in_array($task->taskId(), $userModifiedTasks) == false)
							{
								$taskProperties = $task->getPropertiesArray(false, $link);
								$tasksArray[] = $taskProperties;
								$returningTaskCount++;
							}
						}
						$listOffsetsArray[$listId . 'completedTasksOffset'] = $offset + $returningTaskCount;
					}
				}
				else
				{
					$addTaskTimeStamp = false;

					$lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
					$lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;

					$responseArray['errorcode'] = $lastErrorCode;
					$responseArray['errordesc'] = $lastErrorDesc;
					error_log("syncTasks error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
				}
            }
        }

        if(count($tasksArray) > 0)
            $actionsArray['update'] = $tasksArray;

		// Only proceed with getting deleted tasks if we haven't already filled
		// our limit of tasks to return.
		if (($numOfTasksLimit == 0) || ( ($numOfTasksLimit > 0) && (count($tasksArray) < $numOfTasksLimit) ) )
		{
			$tasksArray = array();

			foreach($lists as $list)
			{
				// Check to see that we haven't already filled our limit of tasks
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

				// ******** Deleted Tasks *************

				// only get deleted tasks if they have synced before with us and
				// only if the timestamp doesn't equal the recorded timestamp, otherwise
				// nothing has happened
				if( ($timestamp != 0) && ($timestamp != $list->taskTimestamp()) )
				{
					//
					// Deleted tasks
					//
					$offset = 0;
					$offsetKey = $listId . 'deletedTasksOffset';
					if (isset($_POST[$offsetKey]))
					{
						$offset = $_POST[$offsetKey];
					}

					$results = TDOTask::getDeletedTasksForUserModifiedSince($session->getUserId(), $timestamp, $listId, $offset, $numOfTasksLimit, false, $link); // get all deleted tasks
					if (isset($results))
					{
						$tasks = $results['tasks'];

						if(isset($tasks) && count($tasks) > 0)
						{
							$returningTaskCount = 0;
							foreach($tasks as $task)
							{
								if(in_array($task->taskId(), $userModifiedTasks) == false)
								{
									// we only need to return the taskid on a delete
									$taskProperties = array();
									$taskProperties['taskid'] = $task->taskId();
									$tasksArray[] = $taskProperties;
									$returningTaskCount++;
								}
							}
							$listOffsetsArray[$listId . 'deletedTasksOffset'] = $offset + $returningTaskCount;
						}
					}
					else
					{
						$addTaskTimeStamp = false;

						$lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
						$lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;

						$responseArray['errorcode'] = $lastErrorCode;
						$responseArray['errordesc'] = $lastErrorDesc;
						error_log("syncTasks error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
					}

					//
					// Deleted subtasks
					//

					$offset = 0;
					$offsetKey = $listId . 'deletedSubtasksOffset';
					if (isset($_POST[$offsetKey]))
					{
						$offset = $_POST[$offsetKey];
					}

					$results = TDOTask::getDeletedTasksForUserModifiedSince($session->getUserId(), $timestamp, $listId, $offset, $numOfTasksLimit, true, $link); // get all deleted subtasks
					if (isset($results))
					{
						$tasks = $results['tasks'];

						if(isset($tasks) && count($tasks) > 0)
						{
							$returningTaskCount = 0;
							foreach($tasks as $task)
							{
								if(in_array($task->taskId(), $userModifiedTasks) == false)
								{
									// we only need to return the taskid on a delete
									$taskProperties = array();
									$taskProperties['taskid'] = $task->taskId();
									$tasksArray[] = $taskProperties;
									$returningTaskCount++;
								}
							}
							$listOffsetsArray[$listId . 'deletedSubtasksOffset'] = $offset + $returningTaskCount;
						}
					}
					else
					{
						$addTaskTimeStamp = false;

						$lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
						$lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;

						$responseArray['errorcode'] = $lastErrorCode;
						$responseArray['errordesc'] = $lastErrorDesc;
						error_log("syncTasks error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
					}
				}


				// ******** Archived Tasks *************

				// only get archived tasks if they have synced before with us and
				// only if the timestamp doesn't equal the recorded timestamp, otherwise
				// nothing has happened
				if( ($timestamp != 0) && ($timestamp != $list->taskTimestamp()) )
				{
					//
					// Archived tasks
					//
					$offset = 0;
					$offsetKey = $listId . 'archivedTasksOffset';
					if (isset($_POST[$offsetKey]))
					{
						$offset = $_POST[$offsetKey];
					}

					$results = TDOTask::getArchivedTasksForUserModifiedSince($session->getUserId(), $timestamp, $listId, $offset, $numOfTasksLimit, false, $link); // get all archived tasks
					if (isset($results))
					{
						$tasks = $results['tasks'];

						if(isset($tasks) && count($tasks) > 0)
						{
							$returningTaskCount = 0;
							foreach($tasks as $task)
							{
								if(in_array($task->taskId(), $userModifiedTasks) == false)
								{
									// we only need to return the taskid on a delete
									$taskProperties = array();
									$taskProperties['taskid'] = $task->taskId();
									$tasksArray[] = $taskProperties;
									$returningTaskCount++;
								}
							}
							$listOffsetsArray[$listId . 'archivedTasksOffset'] = $offset + $returningTaskCount;
						}
					}
					else
					{
						$addTaskTimeStamp = false;

						$lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
						$lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;

						$responseArray['errorcode'] = $lastErrorCode;
						$responseArray['errordesc'] = $lastErrorDesc;
						error_log("syncTasks error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
					}

					//
					// Archived subtasks
					//
					$offset = 0;
					$offsetKey = $listId . 'archivedSubtasksOffset';
					if (isset($_POST[$offsetKey]))
					{
						$offset = $_POST[$offsetKey];
					}

					$results = TDOTask::getArchivedTasksForUserModifiedSince($session->getUserId(), $timestamp, $listId, $offset, $numOfTasksLimit, true, $link); // get archived subtasks
					if (isset($results))
					{
						$tasks = $results['tasks'];

						if(isset($tasks) && count($tasks) > 0)
						{
							$returningTaskCount = 0;
							foreach($tasks as $task)
							{
								if(in_array($task->taskId(), $userModifiedTasks) == false)
								{
									// we only need to return the taskid on a delete
									$taskProperties = array();
									$taskProperties['taskid'] = $task->taskId();
									$tasksArray[] = $taskProperties;
									$returningTaskCount++;
								}
							}
							$listOffsetsArray[$listId . 'archivedSubtasksOffset'] = $offset + $returningTaskCount;
						}
					}
					else
					{
						$addTaskTimeStamp = false;

						$lastErrorCode = ERROR_CODE_ERROR_READING_USER_TASKS;
						$lastErrorDesc = ERROR_DESC_ERROR_READING_USER_TASKS;

						$responseArray['errorcode'] = $lastErrorCode;
						$responseArray['errordesc'] = $lastErrorDesc;
						error_log("syncTasks error ".ERROR_CODE_ERROR_READING_USER_TASKS." error desc ".ERROR_DESC_ERROR_READING_USER_TASKS);
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
            $allTaskTimeStamps = TDOTask::getAllTaskTimestampsForUser($session->getUserId(), $lists);
            if($allTaskTimeStamps != NULL)
                $responseArray['alltasktimestamps'] = $allTaskTimeStamps;

			$listMembershipHashes = TDOList::getListMembershipHashesForUser($session->getUserId(), $lists);
			if (!empty($listMembershipHashes))
				$responseArray['listMembershipHashes'] = $listMembershipHashes;
        }

        //If we change a task's list, we need to tell the client to sync taskitos and notifications again right away
        //or we'll lose the taskitos & notifications
        if($didUpdateTaskIdForListChange == true)
        {
            $allTaskitoTimeStamps = TDOTaskito::getAllTaskitoTimestampsForUser($session->getUserId(), $lists, $link);
            if($allTaskitoTimeStamps != NULL)
                $responseArray['alltaskitotimestamps'] = $allTaskitoTimeStamps;

            $allNotificationTimeStamps = TDOTaskNotification::getAllNotificationTimestampsForUser($session->getUserId(), $lists, $link);
            if($allNotificationTimeStamps != NULL)
                $responseArray['allnotificationtimestamps'] = $allNotificationTimeStamps;
        }

        $jsonResponse = json_encode($responseArray);
        if(json_last_error() != JSON_ERROR_NONE)
        {
            mysql_query("ROLLBACK", $link);

            $lastErrorCode = ERROR_CODE_ERROR_INVALID_UTF8_IN_TASKS;
            $lastErrorDesc = ERROR_DESC_ERROR_INVALID_UTF8_IN_TASKS;

            outputSyncError($lastErrorCode, $lastErrorDesc);
            error_log("json_encoding the tasks from the server failed with error: (" . json_last_error() . " - " . json_last_error_msg() . ") Reporting ".$lastErrorCode." error desc ".$lastErrorDesc." For user: " . $session->getUserId());


			// DEBUG - Detect bad UTF8 in the response array to weed out the bad
			// tasks. We don't really need to check the tasks that were deleted
			// because for those it's just a task ID that is stored, which
			// should only be a GUID.
			$updateTasksArray = $actionsArray['update'];
			if (isset($updateTasksArray))
			{
				foreach($updateTasksArray as $task)
				{
					// Attempt to encode the task in JSON format. If it fails
					// spit out the raw data to the screen.
					$jsonText = json_encode($task);
					if (json_last_error() != JSON_ERROR_NONE)
					{
						$username = TDOUser::usernameForUserId($session->getUserId());

						// Name
						$jsonText = json_encode($task['name']);
						if (json_last_error() != JSON_ERROR_NONE)
						{
							error_log("JSON UTF8 encoding error (" . json_last_error() . ") in task name for task with id: " . $task['taskid'] . ", userid: " . $session->getUserId());

							// Send an email to support@appigo.com to notify us of this problem so
							// we can be proactive about this issue.
							$body = "Hello\n\n"
							. "The system just detected a JSON encoding error which may prevent a user (" . $session->getUserId() . ") "
							. "from synchronizing tasks with Todo Cloud.\n\n"
							. "Username: " . $username
							. "Task ID: " . $task['taskid']
							. "\n\n"
							. "NOTE: To resolve this, you may have to look up the task directly in the database.";
							$recipient = "support@appigo.com";
							$subject = "[CRITICAL] Todo Cloud detected UTF8 JSON Encoding Error on a Task Name";
							TDOMailer::notifyCriticalSystemError($recipient, $subject, $body);
						}

						// Note
						$jsonText = json_encode($task['note']);
						if (json_last_error() != JSON_ERROR_NONE)
						{
							error_log("JSON UTF8 encoding error (" . json_last_error() . ") in task note for task with id: " . $task['taskid'] . ", userid: " . $session->getUserId());
							$body = "Hello\n\n"
							. "The system just detected a JSON encoding error which may prevent a user (" . $session->getUserId() . ") "
							. "from synchronizing tasks with Todo Cloud.\n\n"
							. "Username: " . $username
							. "Task ID: " . $task['taskid']
							. "Task Name: " . $task['name']
							. "\n\n"
							. "NOTE: To resolve this, you may be able to provide the customer with the task name and they could log in to the web client to change the content of the note.";
							$recipient = "support@appigo.com";
							$subject = "[CRITICAL] Todo Cloud detected UTF8 JSON Encoding Error on a Task Note";
							TDOMailer::notifyCriticalSystemError($recipient, $subject, $body);
						}
					}
				}
			}
        }
        else
        {
            if(!mysql_query("COMMIT", $link))
            {
                $lastErrorCode = ERROR_CODE_DB_LINK_FAILED;
                $lastErrorDesc = ERROR_DESC_DB_LINK_FAILED;

                error_log("syncTasks failed to commit transaction");
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

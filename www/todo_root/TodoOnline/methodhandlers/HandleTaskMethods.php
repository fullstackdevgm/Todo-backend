<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	include_once('TodoOnline/content/TaskContentFunctions.php');
	
	if(!$session->isLoggedIn())
	{
		error_log("Method called without a valid session");
		
		echo '{"success":false}';
		return;
	}
	
	if($method == "addTask")
	{
        $list_type = '';
		if(!isset($_POST['taskName']))
		{
			error_log("Method addTask missing parameter: taskName");
			echo '{"success":false}';
			return;
		}
		
		if(!isset($_POST['listid']))
		{
			if(isset($_POST['parentid']))
			{
				$parentTask = TDOTask::getTaskFortaskId($_POST['parentid']);
				if(empty($parentTask))
				{
					error_log("Method addTask missing parameter listid and it was not read from parentid");
					echo '{"success":false}';
					return;
				}
				else
					$listid = $parentTask->listId();
			}
			else
			{
				error_log("Method addTask missing parameter: listid");
				echo '{"success":false}';
				return;
			}
		}
		else
			$listid = $_POST['listid'];
		
		switch ($listid)
		{
			case "all":
			case "focus":
			case "starred":
			case "today":
			case "inbox":
                $list_type = $listid;
				$listid = TDOList::getUserInboxId($session->getUserId(), false);
				break;
		}		
		
		if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method addTask found that user cannot edit the list: ".$listid);
			echo '{"success":false}';
			return;
		}
		
		$newTask = new TDOTask();

        if(isset($_POST['parentid']))
        {
            $parentId = $_POST['parentid'];
            $newTask->setParentId($parentId);
            $newTask->setRecurrenceType(TaskRecurrenceType::WithParent);
        }
        
        if(isset($_COOKIE['TodoOnlineTaskAssignFilterId']))
        {
            $assignedUser = $_COOKIE['TodoOnlineTaskAssignFilterId'];
            if(!empty($assignedUser) && $assignedUser != 'none' && $assignedUser != 'all')
            {
                if(TDOList::userCanEditList($listid, $assignedUser) == true)
                {
                    $newTask->setAssignedUserid($assignedUser);
                }
            }
        }
        
        if(isset($_POST['assigned_user']))
        {
            $assignee = $_POST['assigned_user'];
            if(TDOList::userCanEditList($listid, $assignee) == false)
            {
                error_log("Method addTask found that assigned user cannot edit the list: ".$listid);
                echo '{"success":false}';
                return;
            }
            $newTask->setAssignedUserid($assignee);
        }
        
		$newTask->setListid($listid);
        if ($list_type === 'starred') {
            $newTask->setCompStarredVal(1);
        }
//        $parser = new DateStringParser();
        
        $taskName = $_POST['taskName'];
        // We're going to pull the due date from the task name if possible
        // but still use the original task name for the task
        
//        $dueDate = NULL;
//        //Bug 6843 - the name parser appears to come up with a date of today for
//        //one letter titles, so don't even try to parse those
//        if(strlen($taskName) > 1)
//        {
//            $dueDate = $parser->nltotime($taskName);
//        }
        
//        if(!empty($dueDate))
//        {
//            $newTask->setDueDate(TDOUtil::normalizeDateToNoonGMT($dueDate));
//        }
//        else
//        {

            //If we couldn't parse the date from the name, go get the default due date
            $dueDate = TDOUserSettings::getDefaultDueDateForUserForTaskCreationTime($session->getUserId(), time());
            $newTask->setDueDate($dueDate);

//        }
        
		$newTask->setName($taskName, true);
        if (isset($_POST['task_type']) && $_POST['task_type'] !== '') {

            $task_type = intval($_POST['task_type']);
            $updateResult = $newTask->updateTaskType($task_type);
        }

		if($newTask->addObject())
		{
            $parsedValues = $newTask->updateValuesFromTaskName($session->getUserId());
        
            //Bug 7254 - set the context and tags to whatever we're filtering by, unless
            //they were already set with intelligent task parsing
            if(isset($_COOKIE['TodoOnlineContextId']) && !in_array('context', $parsedValues))
            {
                $contextID = $_COOKIE['TodoOnlineContextId'];
                if(!empty($contextID) && $contextID != 'all' && $contextID != 'nocontext')
                {
                    if(TDOContext::assignTaskToContext($newTask->taskId(), $contextID, $session->getUserId()) == true)
                    {
                        $newTask->setContextId($contextID);
                        if($newTask->updateObject() == false)
                            error_log("HandleTaskMethods::addTask failed to update task with assigned context");
                    }
                    else
                        error_log("HandleTaskMethods::addTask failed to assign task to current context");
                }
                
            }
            if(isset($_COOKIE['TodoOnlineTagId']) && !in_array('tag', $parsedValues))
            {
                $tagsFilterString = $_COOKIE['TodoOnlineTagId'];
                if(strlen($tagsFilterString) > 0)
                {
                    $tagsFilter = explode(",", $tagsFilterString);
                    foreach($tagsFilter as $tagId)
                    {
                        if($tagId != 'all' && $tagId != 'notag')
                        {
                            if(TDOTag::addTagToTask($tagId, $newTask->taskId()) == false)
                                error_log("HandleTaskMethods::addTask failed to add tag to task");
                        }
                    }
                }
            }
        
        
            $parentId = $newTask->parentId();
            if($parentId != NULL && strlen($parentId) > 0)
            {
                TDOTask::fixupChildPropertiesForTask(TDOTask::getTaskForTaskId($parentId));
            }

            $taskJSON = $newTask->getPropertiesArray();

            $jsonArray = array();
            $jsonArray['success'] = true;
            $jsonArray['task'] = $taskJSON;
            $jsonResponse = json_encode($jsonArray);
            
            echo $jsonResponse;
            
            TDOChangeLog::addChangeLog($listid, $session->getUserId(), $newTask->taskId(), $newTask->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
		}
		else
		{
			error_log("Method addTask failed to add task: ".$newTask->taskId());
		}	
	}
	elseif($method == "deleteTask")
	{
        $result = array(
            'success' => TRUE,
            'error' => '',
            'affected_tasks' => array(),
            'affected_subtasks' => array()
        );
		if(!isset($_POST['tasks']) && !isset($_POST['taskitos']))
		{
			error_log("Method deleteTask missing parameter: tasks");
            $result['success'] = FALSE;
            echo json_encode($result);
            return;
		}
        $tasks_ids = explode(',', $_POST['tasks']);
        $subtasks_ids = explode(',', $_POST['taskitos']);
        if (sizeof($tasks_ids)) {
            foreach ($tasks_ids as $taskId) {
                $task = TDOTask::getTaskFortaskId($taskId);
                if(empty($task))
                {
                    error_log("Method deleteTask unable to load task: ".$_POST['taskId']);
                    continue;
                }

                $listid = $task->listId();
                if(empty($listid))
                {
                    error_log("Method deleteTask unable to find list for task: ".$task->taskId());
                    continue;
                }

                if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
                {
                    error_log("Method deleteTask found that user cannot edit the list: ".$listid);
                    continue;
                }

                if(TDOTask::deleteObject($task->taskId()))
                {
                    $parentId = $task->parentId();
                    if($parentId != NULL && strlen($parentId) > 0)
                    {
                        TDOTask::fixupChildPropertiesForTask(TDOTask::getTaskForTaskId($parentId));
                    }

                    TDOChangeLog::addChangeLog($task->listId(), $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
                    $result['affected_tasks'][] = $task->taskId();
                }
                else
                {
                    error_log("Method deleteTask failed to update task: ".$task->taskId());
                    continue;
                }
            }
        }
        if(sizeof($subtasks_ids)){
            foreach($subtasks_ids as $taskitoId){

                $taskito = TDOTaskito::taskitoForTaskitoId($taskitoId);
                if(empty($taskito))
                {
                    error_log("Method deleteTaskito unable to load task: ".$taskitoId);
                    continue;
                }

                $parentId = $taskito->parentId();
                if(empty($parentId))
                {
                    error_log("Method deleteTaskito found subtask without a parent: ".$taskito->taskitoId());
                    continue;
                }

                $parentTask = TDOTask::getTaskForTaskId($parentId);
                if(empty($parentTask))
                {
                    error_log("Method deleteTaskito was unable to read the parent task: ".$parentId);
                    continue;
                }

                $listid = $parentTask->listId();
                if(empty($listid))
                {
                    error_log("Method deleteTaskito unable to find list for parent task: ".$parentId);
                    continue;
                }

                if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
                {
                    error_log("Method deleteTaskito found that user cannot edit the list: ".$listid);
                    continue;
                }

                if(TDOTaskito::deleteObject($taskito->taskitoId()))
                {

                    TDOChangeLog::addChangeLog($listid, $session->getUserId(), $taskito->taskitoId(), $taskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
                    $result['affected_subtasks'][] = $taskito->taskitoId();
                }
                else
                {
                    error_log("Method deleteTask failed to update task: ".$taskito->taskitoId());
                    continue;
                }
            }
        }

        echo json_encode($result);
        return;
	}
    elseif($method == "completeTask")
    {
        if(!isset($_POST['taskId']))
		{
			error_log("Method compeleteTask missing parameter: taskId");
			echo '{"success":false}';
			return;
		}
    
        $task = TDOTask::getTaskFortaskId($_POST['taskId']);
		if(empty($task))
		{
			error_log("Method completeTask unable to load task: ".$_POST['taskId']);
			echo '{"success":false}';
			return;
		}
    
        if(isset($_POST['completiondate']))
		{
			if(is_numeric($_POST['completiondate']) == false)
			{
				error_log("Method completeTask completion date is invalid: ".$_POST['completiondate']);
				echo '{"success":false}';
				return;
			}
			
			$jsonValues['old-completiondate'] = (double)$task->completionDate();
            
			$completionDate = $_POST['completiondate'];
        
            if(!empty($completionDate))
            {
                $results = $task->completeTask($completionDate);
                if(isset($results['success']) && $results['success'] == true)
                {
                    $returnJSON = array();
                
                    $parentId = $task->parentId();
                    if($parentId != NULL && strlen($parentId) > 0)
                    {
                        TDOTask::fixupChildPropertiesForTask(TDOTask::getTaskForTaskId($parentId));
                    }
                    if($task->isProject())
                    {
                        TDOTask::fixupChildPropertiesForTask($task);
                    }
                    $returnJSON['success'] = true;
                    
                    $changeLogTask = $task;
                    
                    if(isset($results['completedTask']) && $results['completedTask'] != NULL)
                    {
                        $completedTask = $results['completedTask'];
                    
                        $recurrenceDate = $task->dueDate();
                        $returnJSON['recurrence'] = true;
                        $returnJSON['startDateTimestamp'] = $task->startDate();
                        $returnJSON['dueDateTimestamp'] = $recurrenceDate;
                        $returnJSON['dueDateHasTime'] = $task->dueDateHasTime();
                        $returnJSON['completedTask'] = $completedTask->getPropertiesArray();
                        
                        //For repeating tasks, create a modify change log entry on the completed task, not the original task
                        $changeLogTask = $completedTask;

                    }
                    $jsonValues['completiondate'] = $changeLogTask->completionDate();
                    $jsonChangedValues = json_encode($jsonValues, JSON_FORCE_OBJECT);
            
                    TDOChangeLog::addChangeLog($changeLogTask->listId(), $session->getUserId(), $changeLogTask->taskId(), $changeLogTask->name(), ITEM_TYPE_TASK, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonChangedValues);
                    
                    echo json_encode($returnJSON);
                    return;
                }
                else
                {
                    error_log("Method completeTask failed to complete task: ".$_POST['taskId']);	
                    echo '{"success":false}';
                    return;
                }
            }
            else
            {
                if($task->uncompleteTask())
                {
                    $parentId = $task->parentId();
                    if($parentId != NULL && strlen($parentId) > 0)
                    {
                        TDOTask::fixupChildPropertiesForTask(TDOTask::getTaskForTaskId($parentId));
                    }
                    if($task->isProject())
                    {
                        TDOTask::fixupChildPropertiesForTask($task);
                    }
                    
                    echo '{"success":true}';
                
                    $jsonValues['completiondate'] = (double)$completionDate;
                    $jsonChangedValues = json_encode($jsonValues, JSON_FORCE_OBJECT);
            
                    TDOChangeLog::addChangeLog($task->listId(), $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonChangedValues);
                }
                else
                {
                    error_log("Method completeTask failed to uncomplete task: ".$_POST['taskId']);	
                    echo '{"success":false}';
                    return;
                }
            }
		}
        else
        {
            error_log("Method completeTask missing parameter: completiondate");
            echo '{"success":false}';
            return;
        }
    }
    elseif($method == "moveTaskToParent")
    {
        if(!isset($_POST['taskId']) || !isset($_POST['parentId']))
        {
            error_log("Method moveTaskToParent called missing parameter taskId or parentId");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing parameter'),
            ));
            return;
        }
        
        $task = TDOTask::getTaskForTaskId($_POST['taskId']);
        $parent = TDOTask::getTaskForTaskId($_POST['parentId']);
        
        if(empty($task) || empty($parent))
        {
            error_log("Method moveTaskToParent unable to find task or parent");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('task not found'),
            ));
            return;
        }
        
        if(TDOList::userCanEditList($task->listId(), $session->getUserId()) == false || TDOList::userCanEditList($parent->listId(), $session->getUserId()) == false)
        {
            error_log("Method moveTaskToParent called with insufficient permissions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You do not have permission to edit this task'),
            ));
            return;
        }
        
        $oldParent = NULL;
        if($task->parentId() != NULL)
        {
            $oldParent = TDOTask::getTaskForTaskId($task->parentId());
        }
        
        if($parent->isProject())
        {
            $oldListId = $task->listId();
            $task->setParentId($parent->taskId());
            $task->setListId($parent->listId());
            
            //If we're moving the task into a project and it doesn't have a repeat value, set it to repeat with parent
            if($oldParent == NULL && ($task->recurrenceType() == TaskRecurrenceType::None || $task->recurrenceType() == TaskRecurrenceType::None + 100))
                $task->setRecurrenceType(TaskRecurrenceType::WithParent);
            
            if($task->updateObject() == false)
            {
                echo '{"success":false}';
                return;
            }
            
            TDOTask::fixupChildPropertiesForTask($parent);
            if($oldParent != NULL && $oldParent->isProject())
            {
                TDOTask::fixupChildPropertiesForTask($oldParent);
            }
            
            if($oldListId != $task->listId())
            {
                TDOChangeLog::addChangeLog($oldListId, $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
                TDOChangeLog::addChangeLog($task->listId(), $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
            }
            else
            {
                $jsonValues = array();
                
                if($oldParent != NULL)
                    $jsonValues['old-parentid'] = $oldParent->taskId();
                else
                    $jsonValue['old-parentid'] = '';
                
                $jsonValues['parentid'] = $parent->taskId();
                
                $jsonString = json_encode($jsonValues, JSON_FORCE_OBJECT);
                TDOChangeLog::addChangeLog($task->listId(), $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonString);
                
            }
            
            echo '{"success":true}';
            return;
        
        }
        elseif($parent->isChecklist())
        {
            $taskito = TDOTask::taskitoFromTask($task);
            $taskito->setParentId($parent->taskId());
            if($taskito->addObject() == false)
            {
                echo '{"success":false}';
                return;
            }

            if(TDOTask::deleteObject($task->taskId()) == false)
            {
                echo '{"success":false}';
                return;
            }
            
            if($oldParent != NULL && $oldParent->isProject())
            {
                TDOTask::fixupChildPropertiesForTask($oldParent);
            }

            TDOChangeLog::addChangeLog($task->listId(), $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
            TDOChangeLog::addChangeLog($parent->listId(), $session->getUserId(), $taskito->taskitoId(), $taskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
            
            echo '{"success":true}';
            return;
        }
        else
        {
            error_log("Attempt to move task to parent that is not a project or checklist");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You can only add subtasks to projects and checklists'),
            ));
            return;
        }
    }
	elseif($method == "updateTask")
	{
		$haveUpdatedValues = false;
        $shouldUpdateParentProperties = false;
        $oldParent = NULL;
        $updateNotificationsDate = NULL;
		$jsonValues = array();
		
		if(!isset($_POST['taskId']))
		{
			error_log("Method updateTask missing parameter: taskId");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing taskId'),
            ));
			return;
		}

		$task = TDOTask::getTaskFortaskId($_POST['taskId']);
		if(empty($task))
		{
			error_log("Method updateTask unable to load task: ".$_POST['taskId']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unable to get task'),
            ));
			return;
		}

		$listid = $task->listId();
		if(empty($listid))
		{
			error_log("Method updateTask unable to find list for task: ".$_POST['taskId']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unable to find list for task'),
            ));
			return;
		}
		
		if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method updateTask found that user cannot edit the list: ".$listid);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('permissions'),
            ));
			return;
		}

		if(isset($_POST['taskName']))
		{
			$jsonValues['old-taskName'] = (string)$task->name();

			$task->setName($_POST['taskName'], true);

			$jsonValues['taskName'] = (string)$task->name();
			$haveUpdatedValues = true;
		}
        
        if(isset($_POST['moveFromProject']))
        {
            if($task->parentId() != NULL)
            {
                $haveUpdatedValues = true;
                
                $oldParent = TDOTask::getTaskForTaskId($task->parentId());
                
                $jsonValues['old-parentid'] = $oldParent->taskId();
                $task->setParentId(NULL);
                $jsonValues['parentid'] = '';
            
                if($task->recurrenceType() == TaskRecurrenceType::WithParent || $task->recurrenceType() == TaskRecurrenceType::WithParent + 100)
                {
                    $task->setRecurrenceType(TaskRecurrenceType::None);
                }
            }
        }
		
		if(isset($_POST['taskNote']))
		{
			$jsonValues['old-taskNote'] = (string)$task->note();

            $taskNote = $_POST['taskNote'];
            
            //Bug 7226 - Check to see if the note is too large
            if(TDOTask::noteIsTooLarge($taskNote))
            {
                error_log("HanelTaskMethods::updateTask attempting to add an oversized note");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Note is too large for database'),
                ));
                return;
            }

			$task->setNote($taskNote, true);
			
			$jsonValues['taskNote'] = (string)$task->note();
			$haveUpdatedValues = true;
		}
		
		if(isset($_POST['taskStartDate']))
		{
			$jsonValues['old-taskStartDate'] = (double)$task->startDate();
            
			$startDateValue = (double)$_POST['taskStartDate'];
			
            if(!empty($startDateValue))
            {
                $startDateValue = TDOUtil::normalizeDateToNoonGMT($startDateValue);
            }
            
			$task->setStartDate($startDateValue);
			
			$jsonValues['taskStartDate'] = (double)$task->startDate();
			$haveUpdatedValues = true;
            $shouldUpdateParentProperties = true;
		}
		
		if(isset($_POST['taskDueDate']))
		{
			$jsonValues['old-taskDueDate'] = (double)$task->dueDate();
			$updateNotificationsDate = $task->dueDate();
            
			$dueDateValue = (double)$_POST['taskDueDate'];
            if(!empty($dueDateValue))
            {
                $dueDateHasTime = false;
                if(isset($_POST['dueDateHasTime']) && $_POST['dueDateHasTime'] != 0)
                {
                    $dueDateHasTime = true;
                }
                
                $preserveDueTime = false;
                if(isset($_POST['preserveDueTime']) && $_POST['preserveDueTime'] != 0)
                {
                    $preserveDueTime = true;
                }
                
                if($preserveDueTime)
                {
                    if($task->dueDate() && $task->dueDateHasTime())
                        $dueDateValue = TDOUtil::dateWithTimeFromDate($dueDateValue, $task->dueDate());
                    else
                        $dueDateValue = TDOUtil::normalizeDateToNoonGMT($dueDateValue);
                }
                else
                {
                    $task->setDueDateHasTime($dueDateHasTime);
                    if(!$dueDateHasTime)
                    {
                        $dueDateValue = TDOUtil::normalizeDateToNoonGMT($dueDateValue);
                    }
                        
                }
            }
            else
            {
                $task->setDueDateHasTime(false);
            }
            
			$task->setDueDate($dueDateValue, true);

			$jsonValues['taskDueDate'] = (double)$task->dueDate();
			$haveUpdatedValues = true;
            $shouldUpdateParentProperties = true;
		}
        
        if(isset($_POST['sort_order']))
        {
            $jsonValues['old-sortOrder'] = intval($task->sortOrder());
            $task->setSortOrder($_POST['sort_order']);
            
            $jsonValues['sortOrder'] = intval($task->sortOrder());
            $haveUpdatedValues = true;
        }
		
		if(isset($_POST['starred']))
		{
			$jsonValues['old-starred'] = $task->starred();
			
			$task->setStarred((int)$_POST['starred'], true);
			
			$jsonValues['starred'] = (int)$task->starred();
			$haveUpdatedValues = true;
            
            $shouldUpdateParentProperties = true;
		}
		
        if(isset($_POST['priority']))
		{
			$jsonValues['old-priority'] = $task->priority();
			
			$task->setPriority($_POST['priority'], true);
			
			$jsonValues['priority'] = (int)$task->priority();
			$haveUpdatedValues = true;
            $shouldUpdateParentProperties = true;
		}
        
        if(isset($_POST['recurrenceType']))
        {
            $jsonValues['old-recurrenceType'] = $task->recurrenceType();
            $task->setRecurrenceType($_POST['recurrenceType']);
            $jsonValues['recurrenceType'] = $task->recurrenceType();
            
            if(($task->recurrenceType() == 50 || $task->recurrenceType() == 150) && (!isset($_POST['advancedRecurrenceString']) || $_POST['advancedRecurrenceString'] == '' ))
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Invalid Recurrence'),
                ));
                return;
            }
            
            $jsonValues['old-advancedRecurrenceString'] = $task->advancedRecurrenceString();
            if(isset($_POST['advancedRecurrenceString']) && ($task->recurrenceType() == 50 || $task->recurrenceType() == 150))
            {
                $task->setAdvancedRecurrenceString($_POST['advancedRecurrenceString']);
            }
            else
            {
                $task->setAdvancedRecurrenceString(NULL);
            }
            $jsonValues['advancedRecurrenceString'] = $task->advancedRecurrenceString();
            
            $haveUpdatedValues = true;
        }
        
        if(isset($_POST['locationAlertType']))
        {
            $jsonValues['old-locationAlertType'] = $task->parseLocationAlertType();
            $locationAlertType = $_POST['locationAlertType'];
            
            if($task->setLocationAlertType($locationAlertType))
            {
                $jsonValues['locationAlertType'] = $locationAlertType;
                $haveUpdatedValues = true;
            }
            else
            {
                error_log("Failed to set locaiton alert type for task");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('failed to set location alert type'),
                ));
                return;
            }
        }
        
        if(isset($_POST['locationAlertAddress']))
        {
            $jsonValues['old-locationAlertAddress'] = $task->parseLocationAlertAddress();
            $locationAlertAddress = $_POST['locationAlertAddress'];
            
            if($task->setLocationAlertAddress($locationAlertAddress))
            {
                $jsonValues['locationAlertAddress'] = $locationAlertAddress;
                $haveUpdatedValues = true;
            }
            else
            {
                error_log("Failed to set location alert address for task");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('failed to set location alert address'),
                ));
                return;
            }
        }
        
        if(isset($_POST['assignedUserId']))
        {

            $jsonValues['old-assignedUserId'] = $task->assignedUserId();
            $assignedUserId = $_POST['assignedUserId'];
            
            if($assignedUserId == "none")
                $assignedUserId = NULL;
            
            $task->setAssignedUserId($assignedUserId);
            $jsonValues['assignedUserId'] = $task->assignedUserId();
            
            $haveUpdatedValues = true;
        }

//        if( (isset($_POST['checklistSubtasks'])) && (isset($_POST['checklistUncompletedCount']) ) )
//        {
//			$jsonValues['old-checklistSubtasks'] = $task->getChecklist();
//            $task->setChecklist($_POST['checklistSubtasks']);
//            $jsonValues['checklistSubtasks'] = $task->getChecklist();
//
//            $jsonValues['old-checklistUncompletedCount'] = $task->getChecklistUncompletedCount();
//            $task->setChecklistUncompletedCount($_POST['checklistUncompletedCount']);
//            $jsonValues['checklistUncompletedCount'] = $task->getChecklistUncompletedCount();
//            
//            $haveUpdatedValues = true;
//        }
		
		if(!$haveUpdatedValues)
		{
			error_log("Method updateTask was called with no values to update");
			echo '{"success":false, "no values to update"}';
			return;
		}
		
		if($task->updateObject())
		{
            if($shouldUpdateParentProperties)
            {
                $parentId = $task->parentId();
                if($parentId != NULL && strlen($parentId) > 0)
                {
                    TDOTask::fixupChildPropertiesForTask(TDOTask::getTaskForTaskId($parentId));
                }
                if($task->isProject())
                {
                    TDOTask::fixupChildPropertiesForTask($task);
                }
            }
            
            if($oldParent != NULL)
            {
                TDOTask::fixupChildPropertiesForTask($oldParent);
            }
            
            if($updateNotificationsDate != NULL)
            {
                TDOTaskNotification::updateNotificationsForTask($task->taskId(), $updateNotificationsDate);
            }
            
            echo '{"success":true}';
                
            $jsonChangedValues = json_encode($jsonValues, JSON_FORCE_OBJECT);

			TDOChangeLog::addChangeLog($task->listId(), $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonChangedValues);
		}
		else
		{
			error_log("Method updateTask failed to update task: ".$_POST['taskId']);	
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('failed to update task'),
            ));
		}
	}
    elseif($method == "groupUpdateTask")
    {
        $tasks = $_POST['tasks'];
        $response = array('success' => TRUE, 'error' => '');

        if ($tasks && $tasks !== FALSE && is_array($tasks) && sizeof($tasks) > 0) {
            foreach ($tasks as $taskid => $properties) {
                $task = TDOTask::getTaskFortaskId($taskid);
                if (empty($task)) {
                    $task = TDOTaskito::taskitoForTaskitoId($taskid);
                    if (empty($task)) {
                        error_log("Method updateTask unable to load task: " . $taskid);
                        $response['success'] = FALSE;
                        $response['error'] .= 'Unable to get task: ' . $taskid . "\n\r";
                    }
                }
                if (isset($properties['sort_order'])) {
                    $task->setSortOrder(intval($properties['sort_order']));
                }
                if (!$task->updateObject()) {
                    error_log("Method updateTask failed to update task: " . $taskid);
                    $response['success'] = FALSE;
                    $response['error'] .= 'Failed to update tasks' . $taskid . "\n\r";
                }
            }
        }
        echo json_encode($response);
    }
    elseif($method == "taskConvert")
    {
        //check for valid parameters
        if(!isset($_POST['taskId']))
        {
            error_log("Method taskConvert called and missing a required parameter: taskId");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Missing Parameter taskId'),
            ));
            return;
        }
        
        if(!isset($_POST['tasktype']))
        {
            error_log("Method taskConvert called and missing a required parameter: tasktype");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Missing Parameter tasktype'),
            ));
            return;
        }
        
        if(isset($_POST['typedata']))
            $typeData = $_POST['typedata'];
        else
            $typeData = NULL;
            
        
        $taskid = $_POST['taskId'];
        $newTaskType = $_POST['tasktype'];
        $task = TDOTask::getTaskForTaskId($taskid);
        if(empty($task))
        {
            error_log("Method taskConvert unable to load task: ".$_POST['taskId']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to find task'),
            ));
            return;
        }
        
        $listid = $task->listId();
        if(empty($listid))
        {
            error_log("Method taskConvert unable to find list for task: ".$_POST['taskId']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to find list for task'),
            ));
            return;
        }
        
        if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
        {
            error_log("Method taskConvert found that user cannot edit the list: ".$listid);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You do not have permission to edit this list.'),
            ));
            return;
        }
        $parentId = $task->parentId();
        if($parentId != NULL && strlen($parentId) > 0)
        {
            if($newTaskType == TaskType::Project)
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('subtasks may not be converted to projects'),
                ));
                return;
            }
        }

        $oldTaskType = $task->taskType();
        $oldTypeData = $task->typeData();
        $updateResult = $task->updateTaskType($newTaskType, $typeData);
        if($updateResult)
        {
            $jsonValues = array();
			
			$jsonValues['old-taskType'] = $oldTaskType;
            $jsonValues['old-typeData'] = $oldTypeData;
            
            $jsonValues['taskType'] = $newTaskType;
            $jsonValues['typeData'] = $typeData;
				
			$jsonChangedValues = json_encode($jsonValues, JSON_FORCE_OBJECT);
			TDOChangeLog::addChangeLog($task->listId(), $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonChangedValues);
            
            if($newTaskType == TaskType::Checklist || $newTaskType == TaskType::Project)
            {
                pagedSubtaskContentForTaskID($task->taskId(), $session->getUserId(), NULL, NULL, false, NULL, false);
            }
            else
            {
                if(gettype($updateResult) == "array")
                {
                    $resultJSON = array();
                    $resultJSON['success'] = true;
                    $tasksJSONArray = array();
                    foreach($updateResult as $newTask)
                    {
                        $taskJSON = $newTask->getPropertiesArray();
                        $tasksJSONArray[] = $taskJSON;
                    }
                    $resultJSON['tasks'] = $tasksJSONArray;
                    
                    echo json_encode($resultJSON);
                    return;
                }
                else
                {
                    echo '{"success":true}';
                    return;
                }
            }

        }
        else
        {
            error_log("Method taskConvert failed for task: ".$task->taskId());
            echo '{"success":false}';
            return;
        }

    }
    elseif($method == "changeTaskList")
    {
        if(!isset($_POST['taskid']) || !isset($_POST['listid']))
        {
            error_log("Method changeTaskList called and missing a required parameter");
            echo '{"success":false}';
            return;
        }
        
        //In order to handle syncing correctly when the user changes a task's list, we need to delete the task
        //from the old list and add a new copy to the new list
        $oldTaskId = $_POST['taskid'];
        $task = TDOTask::getTaskForTaskId($oldTaskId);
        
        if(empty($task))
        {
            error_log("Method changeTaskList unable to find task for id: ".$oldTaskId);
            echo '{"success":false}';
            return;
        }
        
        if($task->parentId() != NULL)
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You cannot change the list of a subtask'),
            ));
            return;
        }
        
        $oldTaskList = $task->listId();
        
        if(TDOList::userCanEditList($oldTaskList, $session->getUserId()) == false)
        {
            error_log("changeTaskList called with insufficient permissions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You do not have permission to edit this task'),
            ));
            return;
        }
        
        $newTaskList = $_POST['listid'];
        if(TDOList::userCanEditList($newTaskList, $session->getUserId()) == false)
        {
            error_log("changeTaskList called with insufficient permissions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You do not have permission to move tasks to this list'),
            ));
            return;
        }
        
        //If the task is already in that list, just return
        if($newTaskList == $oldTaskList)
        {
            echo '{"success":false}';
            return;
        }
            
        if(TDOTask::moveTaskToList($task, $newTaskList) == true)
        {
            TDOChangeLog::addChangeLog($oldTaskList, $session->getUserId(), $oldTaskId, $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
            TDOChangeLog::addChangeLog($newTaskList, $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
            
            $jsonArray = array();
            $jsonArray['success'] = true;
            $jsonArray['newtask'] = $task->getPropertiesArray();
            echo json_encode($jsonArray);
            return;
        }
        else
        {
            echo '{"success":false}';
            return;
        }
        
    }
    elseif($method == "getTaskForTaskId")
    {
        if(!isset($_POST['taskId']))
        {
            error_log("Method getTaskForTaskId called and missing a required parameter: taskId");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Missing Parameter taskId'),
            ));
            return;
        }
        $taskid = $_POST['taskId'];
        $task = TDOTask::getTaskForTaskId($taskid);
        
        if(empty($task))
        {
            error_log("Method getTaskForTaskId unable to load task: ".$_POST['taskId']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to find task'),
            ));
            return;
        }
        
        $listid = $task->listId();
        if(empty($listid))
        {
            error_log("Method getTaskForTaskId unable to find list for task: ".$_POST['taskId']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to find list for task'),
            ));
            return;
        }
        
        if(TDOList::userCanViewList($listid, $session->getUserId()) == false)
        {
            error_log("Method getTaskForTaskId found that user cannot view the list: ".$listid);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You do not have permission to edit this list.'),
            ));
            return;
        }
        $jsonValues = array();
        $jsonValues['task'] = $task->getPropertiesArray();
        $jsonValues['success'] = true;
        
        $jsonResponse = json_encode($jsonValues);
        //error_log("jsonResponse we're sending is: ". $jsonResponse);
        echo $jsonResponse;
    }
?>
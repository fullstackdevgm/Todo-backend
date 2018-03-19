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
	
	if($method == "addTaskito")
	{
		if(!isset($_POST['taskName']))
		{
			error_log("Method addTaskito missing parameter: taskName");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to add task without parameter taskName'),
            ));
			return;
		}
		
        if(isset($_POST['parentid']))
        {
            $parentTask = TDOTask::getTaskForTaskId($_POST['parentid']);
            if(empty($parentTask))
            {
                error_log("Method addTaskito was unable to locate the referenced parent task: ".$_POST['parentid']);
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Unable to locate the referenced parent task'),
                ));
                return;
            }
            if($parentTask->taskType() != TaskType::Checklist)
            {
                error_log("Method addTaskito cannot add taskito to non-checklist task");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Subtasks may only be added to projects or checklists'),
                ));
                return;
            }
            $listid = $parentTask->listId();
        }
        else
        {
            error_log("Method addTaskito missing parameter: parentid");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to add task without parameter parentid'),
            ));
            return;
        }
		
		switch ($listid)
		{
			case "all":
			case "focus":
			case "starred":
			case "today":
			case "inbox":
				$listid = TDOList::getUserInboxId($session->getUserId(), false);
				break;
		}		
		
		if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method addTask found that user cannot edit the list: ".$listid);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Permission denied.'),
            ));
			return;
		}
		
		$newTaskito = new TDOTaskito();

        $parentId = $_POST['parentid'];
        $newTaskito->setParentId($parentId);
		$newTaskito->setName($_POST['taskName'], true);
        
        $highestSortValue = TDOTaskito::highestSortOrderForTaskitosOfTask($parentId);
		$newSortValue = $highestSortValue + 10;
        $newTaskito->setSortOrder($newSortValue);
        
		if($newTaskito->addObject())
		{

            $taskitoJSON = $newTaskito->getPropertiesArray();
            $jsonArray = array();
            $jsonArray['success'] = true;
            $jsonArray['taskito'] = $taskitoJSON;
            $jsonResponse = json_encode($jsonArray);
            
            echo $jsonResponse;

            TDOChangeLog::addChangeLog($listid, $session->getUserId(), $newTaskito->taskitoId(), $newTaskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
            
            return;
        }
		else
		{
			error_log("Method addTask failed to add task: ".$newTaskito->taskitoId());
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Error adding task.'),
            ));
		}
        return;
	}
	elseif($method == "deleteTaskito")
	{
		if(!isset($_POST['taskitoId']))
		{
			error_log("Method deleteTaskito missing parameter: taskitoId");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing parameter: taskitoId'),
            ));
			return;
		}
		
		$taskito = TDOTaskito::taskitoForTaskitoId($_POST['taskitoId']);
		if(empty($taskito))
		{
			error_log("Method deleteTaskito unable to load task: ".$_POST['taskitoId']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unable to read subtask'),
            ));
			return;
		}

		$parentId = $taskito->parentId();
        if(empty($parentId))
        {
			error_log("Method deleteTaskito found subtask without a parent: ".$taskito->taskitoId());
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('subtask did not have parent task'),
            ));
			return;
        }
        
        $parentTask = TDOTask::getTaskForTaskId($parentId);
		if(empty($parentTask))
		{
			error_log("Method deleteTaskito was unable to read the parent task: ".$parentId);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('parent of subtask was not able to be read'),
            ));
			return;
		}
        
		$listid = $parentTask->listId();
		if(empty($listid))
		{
			error_log("Method deleteTaskito unable to find list for parent task: ".$parentId);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unable to read list for parent of subtask'),
            ));
			return;
		}
		
		if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method deleteTaskito found that user cannot edit the list: ".$listid);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('access denied to modify anything in the list'),
            ));
			return;
		}
		
		if(TDOTaskito::deleteObject($taskito->taskitoId()))
		{
        
			TDOChangeLog::addChangeLog($listid, $session->getUserId(), $taskito->taskitoId(), $taskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
			echo '{"success":true}';
		}
		else
		{
			error_log("Method deleteTask failed to update task: ".$taskito->taskitoId());	
			echo '{"success":false}';
		}	
	}
    elseif($method == "completeTaskito")
    {
		$jsonValues = array();
        
		if(!isset($_POST['taskitoId']))
		{
			error_log("Method completeTaskito missing parameter: taskitoId");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing parameter: taskitoId'),
            ));
			return;
		}
		
		$taskito = TDOTaskito::taskitoForTaskitoId($_POST['taskitoId']);
		if(empty($taskito))
		{
			error_log("Method completeTaskito unable to load task: ".$_POST['taskitoId']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unable to read subtask'),
            ));
			return;
		}
        
		$parentId = $taskito->parentId();
        if(empty($parentId))
        {
			error_log("Method completeTaskito found subtask without a parent: ".$taskito->taskitoId());
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('subtask did not have parent task'),
            ));
			return;
        }
        
        $parentTask = TDOTask::getTaskForTaskId($parentId);
		if(empty($parentTask))
		{
			error_log("Method completeTaskito was unable to read the parent task: ".$parentId);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('parent of subtask was not able to be read'),
            ));
			return;
		}
        
		$listid = $parentTask->listId();
		if(empty($listid))
		{
			error_log("Method completeTaskito unable to find list for parent task: ".$parentId);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unable to read list for parent of subtask'),
            ));
			return;
		}
		
		if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method completeTaskito found that user cannot edit the list: ".$listid);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('access denied to modify anything in the list'),
            ));
			return;
		}
    
        if(isset($_POST['completiondate']))
		{
			if(is_numeric($_POST['completiondate']) == false)
			{
				error_log("Method completeTaskito completion date is invalid: ".$_POST['completiondate']);
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('completion date is invalid'),
                ));
				return;
			}
			
			$jsonValues['old-completiondate'] = (double)$taskito->completionDate();
            
			$completionDate = $_POST['completiondate'];
        
            if(!empty($completionDate))
            {
                $results = $taskito->completeTaskito($completionDate);
                if(isset($results['success']) && $results['success'] == true)
                {
                    $parentId = $taskito->parentId();

                    
                    echo '{"success":true}';
                }
                else
                {
                    error_log("Method completeTask failed to complete task: ".$_POST['taskId']);	
                    echo json_encode(array(
                        'success' => FALSE,
                        'error' => _('Unable to complete task'),
                    ));
                    return;
                }
            }
            else
            {
                if($taskito->uncompleteTaskito())
                {
                    $parentId = $taskito->parentId();
                    echo '{"success":true}';
                }
                else
                {
                    error_log("Method completeTask failed to uncomplete task: ".$_POST['taskId']);	
                    echo json_encode(array(
                        'success' => FALSE,
                        'error' => _('Unable to uncomplete task'),
                    ));
                    return;
                }
            }
            
            $jsonValues['completiondate'] = (double)$completionDate;
            $jsonChangedValues = json_encode($jsonValues, JSON_FORCE_OBJECT);
            
            TDOChangeLog::addChangeLog($listid, $session->getUserId(), $taskito->taskitoId(), $taskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonChangedValues);
            
		}
        else
        {
            error_log("Method completeTask missing parameter: completiondate");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Missing parameter completiondate'),
            ));
            return;
        }
    }
    elseif($method == "moveTaskitoToParent")
    {
        if(!isset($_POST['taskitoId']) || !isset($_POST['parentId']))
        {
            error_log("Method moveTaskitoToParent called missing parameter taskitoId or parentId");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing parameter'),
            ));
            return;
        }
        
        $taskito = TDOTaskito::taskitoForTaskitoId($_POST['taskitoId']);
        $parent = TDOTask::getTaskForTaskId($_POST['parentId']);
        
        if(empty($taskito) || empty($parent))
        {
            error_log("Method moveTaskitoToParent unable to find taskito or parent");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('task not found'),
            ));
            return;
        }
        
        $oldParent = TDOTask::getTaskForTaskId($taskito->parentId());
        if(empty($oldParent))
        {
            error_log("Method moveTaskitoToParent unable to find parent");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('task not found'),
            ));
            return;
        }
        
        if(TDOList::userCanEditList($oldParent->listId(), $session->getUserId()) == false || TDOList::userCanEditList($parent->listId(), $session->getUserId()) == false)
        {
            error_log("Method moveTaskitoToParent called with insufficient permissions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You do not have permission to edit this task'),
            ));
            return;
        }
        
        if($parent->isChecklist())
        {
            $taskito->setParentId($parent->taskId());
            
            if($taskito->updateObject() == false)
            {
                echo '{"success":false}';
                return;
            }
            
            if($oldParent->listId() != $parent->listId())
            {
                TDOChangeLog::addChangeLog($oldParent->listId(), $session->getUserId(), $taskito->taskitoId(), $taskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
                TDOChangeLog::addChangeLog($parent->listId(), $session->getUserId(), $taskito->taskitoId(), $taskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
            }
            else
            {
                $jsonValues = array();
                $jsonValues['old-parentid'] = $oldParent->taskId();

                $jsonValues['parentid'] = $parent->taskId();
                
                $jsonString = json_encode($jsonValues, JSON_FORCE_OBJECT);
                TDOChangeLog::addChangeLog($parent->listId(), $session->getUserId(), $taskito->taskitoId(), $taskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonString);
                
            }
            
            echo '{"success":true}';
            return;
        
        }
        elseif($parent->isProject())
        {
            $task = TDOTask::taskFromTaskito($taskito);
            $task->setParentId($parent->taskId());
            $task->setListId($parent->listId());
            $task->setRecurrenceType(TaskRecurrenceType::WithParent);
            if($task->addObject() == false)
            {
                echo '{"success":false}';
                return;
            }

            if(TDOTaskito::deleteObject($taskito->taskitoId()) == false)
            {
                echo '{"success":false}';
                return;
            }
            
            TDOTask::fixupChildPropertiesForTask($parent);

            TDOChangeLog::addChangeLog($oldParent->listId(), $session->getUserId(), $taskito->taskitoId(), $taskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
            TDOChangeLog::addChangeLog($parent->listId(), $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
            
			$taskData = $task->getPropertiesArray();
	        $responseJson = array();
	        $responseJson['success'] = true;
	        $responseJson['task'] = $taskData;
	        echo json_encode($responseJson);
	        return;
        }
        else
        {
            error_log("Attempt to move taskito to parent that is not a project or checklist");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You can only add subtasks to projects and checklists'),
            ));
            return;
        }
    }
    elseif($method == "moveTaskitoFromParent")
    {
        if(!isset($_POST['taskitoId']))
        {
            error_log("Method moveTaskitoFromParent called missing parameter taskitoId");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing parameter'),
            ));
            return;
        }
        
        $taskito = TDOTaskito::taskitoForTaskitoId($_POST['taskitoId']);
        
        if(empty($taskito))
        {
            error_log("Method moveTaskitoFromParent unable to find taskito");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('task not found'),
            ));
            return;
        }
        
        $oldParent = TDOTask::getTaskForTaskId($taskito->parentId());
        if(empty($oldParent))
        {
            error_log("Method moveTaskitoFromParent unable to find parent");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('task not found'),
            ));
            return;
        }
        
        if(TDOList::userCanEditList($oldParent->listId(), $session->getUserId()) == false)
        {
            error_log("Method moveTaskitoFromParent called with insufficient permissions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You do not have permission to edit this task'),
            ));
            return;
        }
        
        
        $task = TDOTask::taskFromTaskito($taskito);
        $task->setListId($oldParent->listId());
        
        if($task->addObject() == false)
        {
            echo '{"success":false}';
            return;
        }

        if(TDOTaskito::deleteObject($taskito->taskitoId()) == false)
        {
            echo '{"success":false}';
            return;
        }

        TDOChangeLog::addChangeLog($oldParent->listId(), $session->getUserId(), $taskito->taskitoId(), $taskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
        TDOChangeLog::addChangeLog($oldParent->listId(), $session->getUserId(), $task->taskId(), $task->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
        
        $taskData = $task->getPropertiesArray();
        $responseJson = array();
        $responseJson['success'] = true;
        $responseJson['task'] = $taskData;
        echo json_encode($responseJson);
        return;

        
    }
	elseif($method == "updateTaskito")
	{
		$haveUpdatedValues = false;
		$jsonValues = array();
		
		if(!isset($_POST['taskitoId']))
		{
			error_log("Method updateTaskito missing parameter: taskitoId");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing parameter: taskitoId'),
            ));
			return;
		}
		
		$taskito = TDOTaskito::taskitoForTaskitoId($_POST['taskitoId']);
		if(empty($taskito))
		{
			error_log("Method updateTaskito unable to load task: ".$_POST['taskitoId']);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unable to read subtask'),
            ));
			return;
		}
        
		$parentId = $taskito->parentId();
        if(empty($parentId))
        {
			error_log("Method updateTaskito found subtask without a parent: ".$taskito->taskitoId());
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('subtask did not have parent task'),
            ));
			return;
        }
        
        $parentTask = TDOTask::getTaskFortaskId($parentId);
		if(empty($parentTask))
		{
			error_log("Method updateTaskito was unable to read the parent task: ".$parentId);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('parent of subtask was not able to be read'),
            ));
			return;
		}
        
        if($parentTask->taskType() != TaskType::Checklist)
        {
            error_log("Method updateTaskito cannot assign taskito to non-checklist task");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Subtasks may only be added to projects or checklists'),
            ));
            return;
        }
        
		$listid = $parentTask->listId();
		if(empty($listid))
		{
			error_log("Method updateTaskito unable to find list for parent task: ".$parentId);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unable to read list for parent of subtask'),
            ));
			return;
		}
		
		if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method updateTaskito found that user cannot edit the list: ".$listid);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('access denied to modify anything in the list'),
            ));
			return;
		}
        
		if(isset($_POST['taskitoName']))
		{
			$jsonValues['old-taskName'] = (string)$taskito->name();

			$taskito->setName($_POST['taskitoName'], true);

			$jsonValues['taskName'] = (string)$taskito->name();
			$haveUpdatedValues = true;
		}
		
        if(isset($_POST['sort_order']))
        {
            $jsonValues['old-sortOrder'] = intval($taskito->sortOrder());
            $taskito->setSortOrder($_POST['sort_order']);
            $jsonValues['sortOrder'] = intval($taskito->sortOrder());
            $haveUpdatedValues = true;
        }
        
		if(!$haveUpdatedValues)
		{
			error_log("Method updateTaskito was called with no values to update");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('No values were passed to change.'),
            ));
			return;
		}
		
		if($taskito->updateObject())
		{

            echo '{"success":true}';
                
            $jsonChangedValues = json_encode($jsonValues, JSON_FORCE_OBJECT);

			TDOChangeLog::addChangeLog($listid, $session->getUserId(), $taskito->taskitoId(), $taskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonChangedValues);
		}
		else
		{
			error_log("Method updateTaskito failed to update task: ".$_POST['taskitoId']);	
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to update subtask.'),
            ));
		}
	}
    elseif($method == "updateTaskitoSortOrders")
    {
        if(!isset($_POST['taskito_data']))
        {
            error_log("Method updateTaskitoSortOrders called missing parameter taskito_data");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Missing parameter taskito_data'),
            ));
            return;
        }
        
        $taskitoData = json_decode($_POST['taskito_data'], true);
        if(empty($taskitoData))
        {
            error_log("Method updateTaskitoSortOrders unable to decode taskito_data JSON");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to interpret request'),
            ));
            return;
        }
        
        if(!isset($taskitoData['parent_id']))
        {
            error_log("Method updateTaskitoSortOrders called with bad data: missing parent_id");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Missing parent_id in request'),
            ));
            return;
        }
        
        $parentId = $taskitoData['parent_id'];
        
        $parentTask = TDOTask::getTaskForTaskId($parentId);
        if(empty($parentTask))
        {
            error_log("Method updateTaskitoSortOrders was unable to find the parent task: ".$parentId);
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('parent of subtasks not found'),
            ));
            return;
        }

		if(TDOList::userCanEditList($parentTask->listId(), $session->getUserId()) == false)
		{
			error_log("Method updateTaskitoSortOrders found that user cannot edit the list: ".$parentTask->listId());
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('access denied to modify anything in the list'),
            ));
			return;
		}

        if(!isset($taskitoData['taskitos']) || is_array($taskitoData['taskitos']) == false)
        {
            error_log("Method updateTaskitoSortOrders called with bad data: missing taskitos");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Missing taskitos in request'),
            ));
            return;
        }
        
        $taskitos = $taskitoData['taskitos'];
        
        $errors = array();
        
        foreach($taskitos as $taskitoInfo)
        {
            if(isset($taskitoInfo['taskito_id']) && isset($taskitoInfo['sort_order']))
            {
                $taskitoId = $taskitoInfo['taskito_id'];
                $sortOrder = $taskitoInfo['sort_order'];
                
                $taskito = TDOTaskito::taskitoForTaskitoId($taskitoId);
                if(!empty($taskito))
                {
                    //Make sure the parent id of the taskito matches the expected parent id that
                    //we know we have permission to edit
                    if($taskito->parentId() == $parentId)
                    {
                        $taskito->setSortOrder($sortOrder);
                        if($taskito->updateObject() == false)
                        {
                            error_log("Method updateTaskitoSortOrders failed to update taskito");
                            $error = array("message"=>"Failed to update taskito", "taskito"=>$taskitoInfo);
                            $errors[] = $error;
                        }
                    }
                    else
                    {
                        error_log("Method updateTaskitoSortOrders parentId doesn't match expected parentId");
                        $error = array("message"=>"Parentid of taskito does not match", "taskito"=>$taskitoInfo);
                        $errors[] = $error;
                    }

                }
                else
                {
                    error_log("Method updateTaskitoSortOrders unable to find taskito: $taskitoId");
                    $error = array("message"=>"Unable to find taskito in database", "taskito"=>$taskitoInfo);
                    $errors[] = $error;
                }
            }
            else
            {
                error_log("Method updateTaskitoSortOrders called with bad taskito");
                $error = array("message"=>"Bad taskito data", "taskito"=>$taskitoInfo);
                $errors[] = $error;
            }
        }
        
        if(!empty($errors))
        {
            $jsonResponse = array("success"=>false, "errors"=>$errors);
            echo json_encode($jsonResponse);
        }
        else
        {
            echo '{"success":true}';
        }
    }
    
    
    
    

?>
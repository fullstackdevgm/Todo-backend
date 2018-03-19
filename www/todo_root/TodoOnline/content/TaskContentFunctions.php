<?php
	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/php/SessionHandler.php');
	
	function pagedTaskContentForSectionID($sectionID, $userid, $listid, $contextid, $tagsFilter, $tagsFilterByAnd, $userFilter, $showCompleted, $showSubtasksSetting)
	{
        $jsonResponseArray = array();

		$tasks = TDOTask::getTasksForSectionID($sectionID, $userid, $listid, $contextid, $tagsFilter, $tagsFilterByAnd, $userFilter, $showCompleted, $showSubtasksSetting);
        if(isset($tasks))
        {
            $tasksArray = array();
            
            foreach($tasks as $task)
            {
                $taskProperties = $task->getPropertiesArray();
                $tasksArray[] = $taskProperties;
            }
            
            $jsonResponseArray['success'] = true;
            $jsonResponseArray['tasks'] = $tasksArray;
        }
        else
        {
            $jsonResponseArray['success'] = false;
            $jsonResponseArray['error'] = _('Unable to read tasks for section');
        }
        
//        $jsonResponse = json_encode($jsonResponseArray);
        //error_log("jsonResponse we're sending is: ". $jsonResponse);
        return $jsonResponseArray;
	}
    

	function pagedSubtaskContentForTaskID($taskid, $userid, $contextid, $tagsFilter, $tagsFilterByAnd, $userFilter, $starredOnly)
	{
        $jsonResponseArray = array();

		$taskIsProject = TDOTask::isTaskIdAProject($taskid);

		if($taskIsProject == true)
		{
            $successful = true;
            
            $tasksArray = array();

            // first get the uncompleted subtasks so they sort up to the top by due date or user setting
			$subTasks = TDOTask::getSubTasksForTask($taskid, $userid, $contextid, $tagsFilter, $tagsFilterByAnd, $userFilter, $starredOnly, false);
            if(isset($subTasks))
            {
                foreach($subTasks as $subTask)
                {
                    $taskProperties = $subTask->getPropertiesArray();
                    $tasksArray[] = $taskProperties;
                }

                // second get the completed tasks so they sort at the bottom by completion date
                $subTasks = TDOTask::getSubTasksForTask($taskid, $userid, $contextid, $tagsFilter, $tagsFilterByAnd, $userFilter, $starredOnly, true);
                if(isset($subTasks))
                {
                    foreach($subTasks as $subTask)
                    {
                        $taskProperties = $subTask->getPropertiesArray();
                        $tasksArray[] = $taskProperties;
                    }
                }
                else
                    $successful = false;
            }
            else
                $successful = false;

            if($successful == true)
            {
                $jsonResponseArray['success'] = true;
                $jsonResponseArray['subtasks'] = $tasksArray;
            }
            else
            {
                $jsonResponseArray['success'] = false;
                $jsonResponseArray['error'] = _('Unable to read subtasks for task:'). ' '  . $taskid;
            }
		}
		else
		{
            $sortAlphabetically = false;
            $userSettings = TDOUserSettings::getUserSettingsForUserid($userid);
            if(!empty($userSettings))
            {
                $sortSetting = $userSettings->taskSortOrder();
                if($sortSetting == TaskSortOrder::Alphabetical)
                    $sortAlphabetically = true;
            }
        
            //First get the incomplete taskitos, then the completed (so the completed will sort below the incomplete ones)
			$taskitos = TDOTaskito::getTaskitosForTask($taskid, true, false, NULL, $sortAlphabetically);
            if(isset($taskitos))
            {
                $tasksArray = array();

                foreach($taskitos as $taskito)
                {
                    $taskitoProperties = $taskito->getPropertiesArray();
                    $tasksArray[] = $taskitoProperties;
                }
                
                $taskitos = TDOTaskito::getTaskitosForTask($taskid, false, true, NULL, $sortAlphabetically);
                if(isset($taskitos))
                {
                    foreach($taskitos as $taskito)
                    {
                        $taskitoProperties = $taskito->getPropertiesArray();
                        $tasksArray[] = $taskitoProperties;
                    }
                
                    $jsonResponseArray['success'] = true;
                    $jsonResponseArray['taskitos'] = $tasksArray;
                }
                else
                {
                    $jsonResponseArray['success'] = false;
                    $jsonResponseArray['error'] = _('Unable to read subtasks for task:'). ' ' . $taskid;
                }
            }
            else
            {
                $jsonResponseArray['success'] = false;
                $jsonResponseArray['error'] = _('Unable to read subtasks for task:') . ' ' . $taskid;
            }
        }

        $jsonResponse = json_encode($jsonResponseArray);
        echo $jsonResponse;
	}
    
    function searchContentForSearchString($userId, $searchString)
    {
        $jsonResponseArray = array();
        $tasks = TDOTask::allTasksContainingText($userId, $searchString);
        if($tasks)
        {
            $tasksArray = array();
            foreach($tasks as $task)
            {
                $taskProperties = $task->getPropertiesArray();
                $tasksArray[] = $taskProperties;
            }
            $jsonResponseArray['success'] = true;
            $jsonResponseArray['tasks'] = $tasksArray;
        }
        else
        {
            $jsonResponseArray['success'] = false;
            $jsonResponseArray['error'] = _('Unable to read tasks for search:'). ' ' . $searchString;
        }
        
        $jsonResponse = json_encode($jsonResponseArray);
        echo $jsonResponse;
    }

?>

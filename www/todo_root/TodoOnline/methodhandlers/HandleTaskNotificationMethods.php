<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	
    if($method == "getNotificationsForTask")
    {
		if(!isset($_POST['taskid']))
		{
			error_log("Method getNotificationsForTask missing parameter: taskid");
			echo '{"success":false}';
			return;
		}
        $taskid = $_POST['taskid'];
        
        $listid = TDOTask::getListIdForTaskId($taskid);
        if(empty($listid))
        {
            error_log("Method getNotificationsForTask unable to get list for taskid");
            echo '{"success":false}';
            return;
        }
        
        if(TDOList::userCanViewList($listid, $session->getUserId()) == false)
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('permissions'),
            ));
            return;
        }
        
        $notifications = TDOTaskNotification::getNotificationsForTask($taskid);
        $jsonResponseArray = array();
        if($notifications !== false)
        {
            $notificationsArray = array();
            
            foreach($notifications as $notification)
            {
                $notificationProperties = $notification->getPropertiesArray();
                $notificationsArray[] = $notificationProperties;
            }
            
            $jsonResponseArray['success'] = true;
            $jsonResponseArray['notifications'] = $notificationsArray;
        }
        else
        {
            $jsonResponseArray['success'] = false;
            $jsonResponseArray['error'] = "Unable to read notifications for task";
        }
        
        $jsonResponse = json_encode($jsonResponseArray);
        //error_log("jsonResponse we're sending is: ". $jsonResponse);
        echo $jsonResponse;

        
    }
	
	if($method == "addTaskNotification")
	{
		if(!isset($_POST['taskid']))
		{
			error_log("Method addTaskNotification missing parameter: taskid");
			echo '{"success":false}';
			return;
		}
        
        $taskid = $_POST['taskid'];
        
        $task = TDOTask::getTaskForTaskId($taskid);
        
        
        if(empty($task))
        {
            error_log("Method addTaskNotification unable to get task for taskid");
            echo '{"success":false}';
            return;
        }
        
        if(!TDOList::userCanEditList($task->listId(), $session->getUserId()))
        {
            error_log("User has insufficient permissions to add notification to task: ".$session->getUserId());
            echo '{"success":false}';
            return;
        }
        
        $newNotification = new TDOTaskNotification();
        $newNotification->setTaskId($taskid);
        
        if(isset($_POST['sound_name']))
        {
            $newNotification->setSoundName($_POST['sound_name']);
        }
        
        if(isset($_POST['triggeroffset']) && $_POST['triggeroffset'] != 0)
        {
            $triggeroffset = $_POST['triggeroffset'];
        
            $newNotification->setTriggerOffset($triggeroffset);
            
            $date = $task->dueDate();
            if(empty($date))
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('You may not set an offset alert on a task with no due date'),
                ));
                return;
            }
            if($task->dueDateHasTime() == false)
                $date = TDOUtil::denormalizedDateFromGMTDate($date);
            
            $triggerdate = $date - $triggeroffset;
            $newNotification->setTriggerDate($triggerdate);
        }
        elseif(isset($_POST['triggerdate']))
        {
            $newNotification->setTriggerOffset(0);
            $newNotification->setTriggerDate($_POST['triggerdate']);
        }
        else
        {
            error_log("Method addTaskNotification missing triggeroffset or triggerdate");
            echo '{"success":false}';
            return;
        }
		
		if($newNotification->addTaskNotification())
		{
//			TDOChangeLog::addChangeLog($TDOContext->getContextid(), $session->getUserId(), $TDOContext->getContextid(), $TDOContext->getName(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
//			$taskHTML = contentDisplayForTask($TDOTask);
            echo '{"success":true}';
			// echo '{"success":true, "contextName":"'.$TDOContext->getName().'", "contextid":"'.$TDOContext->getContextid().'"}';
		}
		else
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Creation of task notification failed'),
            ));
		}	
	}
	elseif($method == "deleteTaskNotification")
	{
        if(!isset($_POST['notificationid']))
        {
            error_log("Method deleteTaskNotification missing parameter: notificationid");
            echo '{"success":false}';
            return;
        }
        
        $notificationid = $_POST['notificationid'];
        $taskid = TDOTaskNotification::getTaskIdForNotification($notificationid);
        if(empty($taskid))
        {
            error_log("deleteTaskNotification unable to get task for notification");
            echo '{"success":false}';
            return;
        }
        
        $listid = TDOTask::getListIdForTaskId($taskid);
        if(empty($listid))
        {
            error_log("deleteTaskNotification unable to get list for task");
            echo '{"success":false}';
            return;
        }
        
        if(!TDOList::userCanEditList($listid, $session->getUserId()))
        {
            error_log("User has insufficient permissions to remove notification from task: ".$session->getUserId());
            echo '{"success":false}';
            return;
        }
        
        if(TDOTaskNotification::deleteTaskNotification($notificationid))
        {
            //TODO: change log
            echo '{"success":true}';
            return;
        }
        else
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('failed to delete notification'),
            ));
            return;
        }
        
	}
	elseif($method == "updateTaskNotification")
	{
        if(!isset($_POST['notificationid']))
        {
            error_log("Method updateTaskNotification missing parameter: notificationid");
            echo '{"success":false}';
            return;
        }
        
        $notificationid = $_POST['notificationid'];
        
        $notification = TDOTaskNotification::getNotificationForNotificationId($notificationid);
        if(empty($notification))
        {
            error_log("updateTaskNotification unable to get notification for notification id");
            echo '{"success":false}';
            return;
        }
        
        
        $task = TDOTask::getTaskForTaskId($notification->taskId());
        
        if(empty($task))
        {
            error_log("Method updateTaskNotification unable to get task for taskid");
            echo '{"success":false}';
            return;
        }
        
        if(!TDOList::userCanEditList($task->listId(), $session->getUserId()))
        {
            error_log("User has insufficient permissions to update notification: ".$session->getUserId());
            echo '{"success":false}';
            return;
        }

        
        $updateMade = false;
        
        if(isset($_POST['triggeroffset']) && $_POST['triggeroffset'] != 0)
        {
            $triggeroffset = $_POST['triggeroffset'];
        
            $notification->setTriggerOffset($triggeroffset);
            
            $date = $task->dueDate();
            if(empty($date))
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('You may not set an offset alert on a task with no due date'),
                ));
                return;
            }
            if($task->dueDateHasTime() == false)
                $date = TDOUtil::denormalizedDateFromGMTDate($date);
            
            $triggerdate = $date - $triggeroffset;
            $notification->setTriggerDate($triggerdate);
            
            $updateMade = true;

        }
        if(isset($_POST['triggerdate']))
        {
            $notification->setTriggerOffset(0);
            $notification->setTriggerDate($_POST['triggerdate']);
            
            $updateMade = true;
        }
        if(isset($_POST['sound_name']))
        {
            $notification->setSoundName($_POST['sound_name']);
            $updateMade = true;
        }
        
        if(!$updateMade)
        {
            error_log("updateTaskNotification called with nothing to update");
            echo '{"success":false}';
            return;
        }
        
        if($notification->updateTaskNotification())
        {
            //TODO: change log
            echo '{"success":true}';
            return;
        }
        else
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('failed to update notification'),
            ));
            return;
        }
    
    }
    elseif($method == "getNextNotificationForUser")
    {
        if(isset($_POST['triggertime']))
            $triggerTime = $_POST['triggertime'];
        else
            $triggerTime = time();
            
        if(isset($_POST['lastnotification']))
            $lastNotification = $_POST['lastnotification'];
        else
            $lastNotification = NULL;
            
        $notification = TDOTaskNotification::getNextNotificationForUserAfterTime($session->getUserId(), $triggerTime, $lastNotification);

        $jsonResponseArray = array();
        if($notification)
        {
            $notificationProperties = $notification->getPropertiesArray();
            $jsonResponseArray['notification'] = $notificationProperties;
        }
            
        $jsonResponseArray['success'] = true;
        $jsonResponse = json_encode($jsonResponseArray);
        //error_log("jsonResponse we're sending is: ". $jsonResponse);
        echo $jsonResponse;
        return;

    }
    
?>
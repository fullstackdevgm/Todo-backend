<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	include_once('TodoOnline/DBConstants.php');
	
	define ("CREATE_EMAIL_TASK_SECRET", '86104B2D-DC10-4538-9A0E-61E974565D5E');
	
    if($method == "createTaskFromEmail")
    {
		// Check for valid parameters
		if (!isset($_POST['apikey']))
		{
			error_log("HandleEmailTaskMethods::createTaskFromEmail called and missing a required parameter: apikey");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('apikey'),
            ));
			return;
		}
		
		if (!isset($_POST['sender']))
		{
			error_log("HandleEmailTaskMethods::createTaskFromEmail called and missing a required parameter: sender");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('sender'),
            ));
			return;
		}
		
		if (!isset($_POST['recipient']))
		{
			error_log("HandleEmailTaskMethods::createTaskFromEmail called and missing a required parameter: recipient");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('recipient'),
            ));
			return;
		}
		
		$apiKey = $_POST['apikey'];
		$sender = $_POST['sender'];
		$recipient = $_POST['recipient'];
		
		// Validate the API Secret Key, which is an MD5 hash of:
		// <SECRET><SENDER><RECIPIENT><SECRET>47
		$preHash = CREATE_EMAIL_TASK_SECRET . $sender . $recipient . CREATE_EMAIL_TASK_SECRET . "47";
		$calculatedMD5 = md5($preHash);
		
		if ($calculatedMD5 != $apiKey)
		{
			error_log("HandleEmailTaskMethods::createTaskFromEmail called by unauthorized service");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unauthorized'),
            ));
			return;
		}
		
		$userID = TDOUserSettings::getUserIDForTaskCreationEmail($recipient);
		if (empty($userID))
		{
			error_log("HandleEmailTaskMethods::createTaskFromEmail called with unknown recipient");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unknown recipient'),
            ));
			return;
		}
		
		// Figure out the user's inbox list so we can put the task there
		$inboxID = TDOList::getUserInboxId($userID);
		if (empty($inboxID))
		{
			error_log("HandleEmailTaskMethods::createTaskFromEmail could not determine the INBOX list ID for user: $userID");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('no inbox'),
            ));
			return;
		}
		
		$taskName = NULL;
		$taskNote = NULL;
        
        if(TDOSubscription::getSubscriptionLevelForUserID($userID) > 1)
        {
            if (isset($_POST['subject']))
                $taskName = trim($_POST['subject']);
			
            if (empty($taskName))
                $taskName = "Email Task (no subject)";
			
            if (isset($_POST['body']))
                $taskNote = trim($_POST['body']);
        }
        else
        {
            $taskName = _("Email task creation available with a premium account (see note)");
            $taskNote = _("You attempted to create this task from an email. Upgrade to a premium account (in Settings) and future tasks created by email will automatically appear in your Inbox.");
        }
		
		$newTask = new TDOTask();
		$newTask->setListId($inboxID);
    
		$newTask->setName($taskName);
		
		if (!empty($taskNote))
        {
            //Bug 7226 - Check to see if the note is too large
            if(TDOTask::noteIsTooLarge($taskNote))
            {
                error_log("HandleEmailTaskMethods::createTaskFromEmail attempting to add an oversized note");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Note is too large for database'),
                ));
                return false;
            }
        
			$newTask->setNote($taskNote);
        }
		

        $link = TDOUtil::getDBLink();
        if(!$link)
        {
			error_log("HandleEmailTaskMethods::createTaskFromEmail Unable to connect to database");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to connect to database'),
            ));
            return;
        }
        
        //mysql_query("set names 'utf8'", $link);
        
        //Set the timezone so that dates parsed from the task name will be correct for the user's timezone
        $timezone = TDOUserSettings::getTimezoneForUser($userID);
        if($timezone)
        {
            date_default_timezone_set($timezone);
        }
        
        //Set the default due date for the task
        $dueDate = TDOUserSettings::getDefaultDueDateForUserForTaskCreationTime($userID, time());
        $newTask->setDueDate($dueDate);
        
		if ($newTask->addObject($link) == false)
		{
            TDOUtil::closeDBLink($link);
            
			error_log("HandleEmailTaskMethods::createTaskFromEmail unable to add the task to the database");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to add task to database'),
            ));
			return;
		}

        TDOUtil::closeDBLink($link);
		
        $newTask->updateValuesFromTaskName($userID);
        
		$jsonValue = '{"sender":"' . $sender . '"}';
		
		TDOChangeLog::addChangeLog($newTask->listId(), EMAIL_TASK_CREATION_USERID, $newTask->taskId(), $newTask->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_EMAIL, NULL, NULL, $jsonValue);
		
		echo '{"success":true}';
    }
	
?>

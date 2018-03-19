<?php
//      TDOTaskNotification

// include files
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');	

class TDOTaskNotification extends TDODBObject
{
	public function __construct()
	{
        parent::__construct();
		$this->set_to_default();      
	}
    
	public function set_to_default()
	{
        parent::set_to_default();
    }
	
    
	public static function deleteAllTaskNotificationsForChildrenOfTask($taskid, $link=NULL)
	{
        if(!isset($taskid))
            return false;
        
        if(!$link)
        {
            $link = TDOUtil::getDBLink();        
            if(!$link)
            {
                error_log("TDOTaskNotification::deleteAllTaskNotificationsForTask failed to get dblink");
                return false;
            }  
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }
		
        $escapedtaskid = mysql_real_escape_string($taskid, $link);
		$timestamp = time();        
		
        // Delete Notification (mark as deleted)
        $sql = "UPDATE tdo_task_notifications SET deleted=1, timestamp='$timestamp' WHERE taskid IN ";
        $sql .= "(SELECT taskid FROM tdo_tasks WHERE parentid='$escapedtaskid' UNION SELECT taskid FROM tdo_completed_tasks WHERE parentid='$escapedtaskid')";
        
        if(!mysql_query($sql, $link))
        {
            error_log("TDOTaskNotification::deleteAllTaskNotificationsForChildrenOfTask could not delete notifications: ".mysql_error());
            if($shouldCloseLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
        
        if(mysql_affected_rows($link) > 0)
        {
            $listId = TDOTask::getListIdForTaskId($taskid, $link);
            if($listId != false)
            {
                TDOList::updateNotificationTimestampForList($listId, $timestamp, $link);
            }
        }
        
        if($shouldCloseLink)
            TDOUtil::closeDBLink($link);
        return true;
	}     
    
    
	public static function deleteAllTaskNotificationsForTask($taskid, $link=NULL)
	{
        if(!isset($taskid))
            return false;
        
        if(!$link)
        {
            $link = TDOUtil::getDBLink();        
            if(!$link)
            {
                error_log("TDOTaskNotification::deleteAllTaskNotificationsForTask failed to get dblink");
                return false;
            }  
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }
		
        $escapedtaskid = mysql_real_escape_string($taskid, $link);
		$timestamp = time();        
		
        // Delete Notification (mark as deleted)
        if(!mysql_query("UPDATE tdo_task_notifications SET deleted=1, timestamp='$timestamp' WHERE taskid='$escapedtaskid'", $link))
        {
            error_log("TDOTaskNotification::deleteAllTaskNotificationsForTask Could not delete notifications: ".mysql_error());
            if($shouldCloseLink)
                TDOUtil::closeDBLink($link);
            return false;
        }

        if(mysql_affected_rows($link) > 0)
        {
            $listId = TDOTask::getListIdForTaskId($taskid, $link);
            if($listId != false)
            {
                TDOList::updateNotificationTimestampForList($listId, $timestamp, $link);
            }
        }
        
        
        if($shouldCloseLink)
            TDOUtil::closeDBLink($link);
        return true;
	}    
    
    public static function permanentlyDeleteAllTaskNotificationsForTask($taskid, $link = NULL)
    {
        if(empty($taskid))
            return false;
        
        if(empty($link))
        {
            $closeLink = true;
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOTaskNotification failed to get db link");
                return false;
            }
        }
        else
            $closeLink = false;
        
        $escapedTaskID = mysql_real_escape_string($taskid, $link);
        $sql = "DELETE FROM tdo_task_notifications WHERE taskid='$escapedTaskID'";
        if(mysql_query($sql, $link))
        {
            if($closeLink)
                TDOUtil::closeDBLink($link);
            return true;
        }
        else
            error_log("permanentlyDeleteAllTaskNotificationsForTask failed with error: ".mysql_error());
        
        if($closeLink)
            TDOUtil::closeDBLink($link);
        
        return false;
    }
    
	public static function deleteTaskNotification($notificationid, $link=NULL)
	{
        if(!isset($notificationid))
            return false;
            
        if($link == NULL)
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTaskNotification failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
		
        $notificationid = mysql_real_escape_string($notificationid, $link);
		$timestamp = time();        
		
        // Delete Notification (mark as deleted)
        if(!mysql_query("UPDATE tdo_task_notifications SET deleted=1, timestamp='$timestamp' WHERE notificationid='$notificationid'", $link))
        {
            error_log("TDOTaskNotification::Could not delete notification".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
        
        if(mysql_affected_rows($link) > 0)
        {
            $taskId = TDOTaskNotification::getTaskIdForNotification($notificationid, $link);
            if(!empty($taskId))
            {        
                $listId = TDOTask::getListIdForTaskId($taskId, $link);
                if(!empty($listId))
                {
                    TDOList::updateNotificationTimestampForList($listId, $timestamp, $link);
                }
            }        
        }
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return true;
	}

	public function addTaskNotification($link=NULL)
	{
		if($this->taskId() == NULL)
		{
			error_log("TDOTaskNotification::addTaskNotification failed because taskid was not set");
			return false;
		}

        if($link == NULL)
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTaskNotification::addTaskNotification failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
		
        // CRG - Added for legacy migration
        $notificationId = $this->notificationId();
        if($notificationId == NULL)
        {
            $notificationId = TDOUtil::uuid();
            $this->setNotificationId($notificationId);
        }
        
        $taskId = mysql_real_escape_string($this->taskId(), $link);
        $deleted = intval($this->deleted());
		$timestamp = time();
        if($this->soundName() != NULL)
            $soundName = mysql_real_escape_string($this->soundName(), $link);
        else
            $soundName = NULL;
            
        if($this->triggerOffset() != 0)
            $triggerOffset = intval($this->triggerOffset());
        else
            $triggerOffset = 0;
            
        if($this->triggerDate() != 0)
            $triggerDate = intval($this->triggerDate());
        else
            $triggerDate = 0;
		
		// Create the list
		$sql = "INSERT INTO tdo_task_notifications (notificationid, taskid, timestamp, sound_name, deleted, triggerdate, triggeroffset) VALUES ('$notificationId', '$taskId', $timestamp, '$soundName', $deleted, $triggerDate, $triggerOffset)";
		$result = mysql_query($sql, $link);
		if(!$result)
		{
			error_log("TDOTaskNotifcation::addTaskNotification failed to add notification with error :".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			return false;
		}
        
        $listId = TDOTask::getListIdForTaskId($taskId, $link);
        if(!empty($listId))
        {
            TDOList::updateNotificationTimestampForList($listId, $timestamp, $link);
        }
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
		return true;
	}
	
	
	public function updateTaskNotification($link=NULL)
	{
		if($this->notificationId() == NULL)
		{
			error_log("TDOTaskNotification::updateTaskNotification() failed: notification id not set");
			return false;
		}
		if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTaskNotification::updateTaskNotification() failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $timestamp = time();
		$updateString = "timestamp=".$timestamp;
        
        if($this->taskId() != NULL)
            $updateString .= ",taskid='".mysql_real_escape_string($this->taskId(), $link)."'";
        else
            $updateString .= ",taskid=NULL";
            
        if($this->soundName() != NULL)
            $updateString .= ",sound_name='".mysql_real_escape_string($this->soundName(), $link)."'";
        else
            $updateString .= ",sound_name=NULL";
            
        if($this->deleted() != 0)
            $updateString .= ",deleted=".intval($this->deleted());
        else
            $updateString .= ",deleted=0";
            
        if($this->triggerDate() != 0)
            $updateString .= ",triggerdate=".intval($this->triggerDate());
        else
            $updateString .= ",triggerdate=0";
            
        if($this->triggerOffset() != 0)
            $updateString .= ",triggeroffset=".intval($this->triggerOffset());
        else
            $updateString .= ",triggeroffset=0";
            
        $notificationId = mysql_real_escape_string($this->notificationId());
		
        $sql = "UPDATE tdo_task_notifications SET " . $updateString . " WHERE notificationid='$notificationId'";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
            if(mysql_affected_rows($link) > 0)
            {
                $taskId = $this->taskId();
                if(!empty($taskId))
                {        
                    $listId = TDOTask::getListIdForTaskId($taskId, $link);
                    if(!empty($listId))
                    {
                        TDOList::updateNotificationTimestampForList($listId, $timestamp, $link);
                    }
                }        
            }
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return true;
        }
        else
        {
            error_log("Unable to update tasknotification $notificationId: ".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }

	}
    	
	public static function getNotificationForNotificationId($notificationId, $link=NULL)
    {
        if(!isset($notificationId))
            return false;
            
        if(!$link)
        {
            $link = TDOUtil::getDBLink();        
            if(!$link)
            {
                error_log("TDOTaskNotification failed to get dblink");
                return false;
            }  
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }
        
        $notificationId = mysql_real_escape_string($notificationId);
        
		$sql = "SELECT * FROM tdo_task_notifications WHERE notificationid='$notificationId'";

        $response = mysql_query($sql, $link);
        if($response)
        {
            $row =  mysql_fetch_array($response);
            if($row)
            {
                $notification = TDOTaskNotification::taskNotificationFromRow($row);
                if($shouldCloseLink)
                    TDOUtil::closeDBLink($link);
                return $notification;
            }

        }
        else
            error_log("Unable to get notification: ".mysql_error());
        
        if($shouldCloseLink)
            TDOUtil::closeDBLink($link);

        return false;        
    }
    
    public static function getNotificationCountForTask($taskid, $includeDeleted=false)
    {
        if(empty($taskid))
            return false;
            
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOTaskNotification failed to get dblink");
            return false;
        }
        
        $taskid = mysql_real_escape_string($taskid, $link);
        $sql = "SELECT COUNT(notificationid) FROM tdo_task_notifications WHERE taskid='$taskid'";
        
        if(!$includeDeleted)
            $sql .= " AND deleted=0";
            
        $result = mysql_query($sql, $link);
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                if(isset($row['0']))
                {
                    $count = $row['0'];
                    TDOUtil::closeDBLink($link);
                    return $count;
                }
            }
        }
        else
            error_log("getNotificationCountForTask failed: ".mysql_error());
            
        TDOUtil::closeDBLink($link);
        return false;        
    }
    
    public static function getNotificationsForTask($taskid, $includeDeleted=false, $link=NULL)
    {
        if(empty($taskid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTaskNotification failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $taskid = mysql_real_escape_string($taskid, $link);
        
        $sql = "SELECT * from tdo_task_notifications WHERE taskid='$taskid'";
        if(!$includeDeleted)
            $sql .= " AND deleted=0";
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            $notifications = array();
            while($row = mysql_fetch_array($result))
            {
                $notification = TDOTaskNotification::taskNotificationFromRow($row);
                $notifications[] = $notification;
            }
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            
            return $notifications;
        }
        else
            error_log("getNotificationsForTask failed: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }
    
    
    public static function buildSQLFilterForUserForListID($userID)
    {
        // build up the list for the given userid
        $listFilter = " (listid IN (";
        $lists = TDOList::getListsForUser($userID);
        $firstTime = true;
        foreach($lists as $list)
        {
            $listId = $list->listId();

            if(!$firstTime)
                $listFilter = $listFilter . ", ";
            $listFilter = $listFilter . "'" . $listId . "'";
            $firstTime = false;
        }
        
        //If we didn't find any lists, stick an empty string to avoid sql syntax error
        if($firstTime)
            $listFilter .= "''";
        $listFilter = $listFilter . ")) ";

        return $listFilter;
    }
    
    
    
    public static function getNotificationsForUserModifiedSince($userid, $listid, $timestamp, $deletedNotifications=false, $link=false)
    {
        if(!$link)
        {
            $closeLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTask::getNotificationsForUserModifiedSince() could not get DB connection.");
                return false;
            }
        }
        else
            $closeLink = false;
        

        $escapedListId = mysql_real_escape_string($listid, $link);
        $listFilter = " listid = '". $escapedListId . "'";
        $whereFilter = "";

        if(!empty($timestamp))
        {
            $whereFilter = $whereFilter." AND tdo_task_notifications.timestamp > ".$timestamp;
        }
        
        if($deletedNotifications == false)
            $whereFilter = $whereFilter." AND (tdo_task_notifications.deleted = 0) ";
        else
            $whereFilter = $whereFilter." AND (tdo_task_notifications.deleted != 0) ";


        $notifications = array();
        
        $sql = "SELECT tdo_task_notifications.* from tdo_task_notifications JOIN tdo_tasks on (tdo_task_notifications.taskid = tdo_tasks.taskid) WHERE ".$listFilter.$whereFilter;
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            while($row = mysql_fetch_array($result))
            {
                $notification = TDOTaskNotification::taskNotificationFromRow($row);
                if (empty($notification) == false)
                {
                    $notifications[$row['notificationid']] = $notification;
                }
            }
        }
        else
        {
            error_log("getNotificationsForUserModifiedSince failed with error: ".mysql_error());
            if($closeLink)
                TDOUtil::closeDBLink($link);
            return false;
        }

        $sql = "SELECT tdo_task_notifications.* from tdo_task_notifications JOIN tdo_completed_tasks on (tdo_task_notifications.taskid = tdo_completed_tasks.taskid) WHERE ".$listFilter.$whereFilter;
        
        //Bug 7227 - limit the notifications returned to tasks completed within the last year
        if(empty($timestamp))
        {
            $limitDate = mktime(date("H"), date("i"), date("s"), date("n"), date("j"), date("Y") - 1);
            $sql = $sql." AND tdo_completed_tasks.completiondate > ".intval($limitDate);
            
        }

        $result = mysql_query($sql, $link);
        if($result)
        {
            while($row = mysql_fetch_array($result))
            {
                $notification = TDOTaskNotification::taskNotificationFromRow($row);
                if (empty($notification) == false)
                {
                    $notifications[$row['notificationid']] = $notification;
                }
            }
        }
        else
        {
            error_log("getNotificationsForUserModifiedSince failed with error: ".mysql_error());
            if($closeLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
        
        //Don't bother returning notifications on deleted tasks if we're not returning deleted notifications, since
        //all notifications on deleted tasks should be deleted
        if($deletedNotifications)
        {
            $sql = "SELECT tdo_task_notifications.* from tdo_task_notifications JOIN tdo_deleted_tasks on (tdo_task_notifications.taskid = tdo_deleted_tasks.taskid) WHERE ".$listFilter.$whereFilter;
            
            $result = mysql_query($sql, $link);
            if($result)
            {
                while($row = mysql_fetch_array($result))
                {
                    $notification = TDOTaskNotification::taskNotificationFromRow($row);
                    if (empty($notification) == false)
                    {
                        $notifications[$row['notificationid']] = $notification;
                    }
                }
            }
            else
            {
                error_log("getNotificationsForUserModifiedSince failed with error: ".mysql_error());
                if($closeLink)
                    TDOUtil::closeDBLink($link);
                return false;
            }
        }
        
        if($closeLink)
            TDOUtil::closeDBLink($link);
        
        return array_values($notifications);
        
    }

    
	public static function getAllNotificationTimestampsForUser($userid, $lists=NULL, $link=NULL)
    {
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTask::getAllNotificationTimestampsForUser() could not get DB connection.");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $timeStamps = array();
        
        // first get all of the lists and build timestamps for each one
        if(empty($lists))
            $lists = TDOList::getListsForUser($userid, false, $link);
        
        foreach($lists as $list)
        {
            $listId = $list->listId();
            
            if($list->notificationTimestamp() > 0)
            {
                // If a value of 1 is stored, then we've already done the calculation (see below) and
                // stored a 1 in the timestamp.  Nothing as changed so don't return a timestamp in
                // this case.
                if($list->notificationTimestamp() != 1)
                    $timeStamps[$listId] = $list->notificationTimestamp();
            }
            // if we didn't have a timestamp stored on the list for tasks, go figure it out and then store it
            else    
            {
                // error_log("Long Notification timestamp query being called on list: " . $list->name());
                
                $listFilter = " listid='" . $listId ."'";
            
                $maxTimestamp = 0;
                $sql = "SELECT MAX(tdo_task_notifications.timestamp) AS timestamp FROM tdo_task_notifications JOIN tdo_tasks on (tdo_task_notifications.taskid = tdo_tasks.taskid) WHERE ".$listFilter;
                $result = mysql_query($sql);
                if($result)
                {
                    $row = mysql_fetch_array($result);
                    if(!empty($row['timestamp']))
                    {
                        $maxTimestamp = $row['timestamp'];
                    }
                }
                
                $sql = "SELECT MAX(tdo_task_notifications.timestamp) AS timestamp FROM tdo_task_notifications JOIN tdo_completed_tasks on (tdo_task_notifications.taskid = tdo_completed_tasks.taskid) WHERE ".$listFilter;
                $result = mysql_query($sql);
                if($result)
                {
                    $row = mysql_fetch_array($result);
                    if(!empty($row['timestamp']))
                    {
                        $tmpTimestamp = $row['timestamp'];
                        if($tmpTimestamp > $maxTimestamp)
                            $maxTimestamp = $tmpTimestamp;
                    }
                }
                
                $sql = "SELECT MAX(tdo_task_notifications.timestamp) AS timestamp FROM tdo_task_notifications JOIN tdo_deleted_tasks on (tdo_task_notifications.taskid = tdo_deleted_tasks.taskid) WHERE ".$listFilter;
                $result = mysql_query($sql);
                if($result)
                {
                    $row = mysql_fetch_array($result);
                    if(!empty($row['timestamp']))
                    {
                        $tmpTimestamp = $row['timestamp'];
                        if($tmpTimestamp > $maxTimestamp)
                            $maxTimestamp = $tmpTimestamp;
                    }
                }
                
                if($maxTimestamp > 0)
                {
    //                error_log("New Timestamp for list " . $listId. " is: " .$maxTimestamp);
                    TDOList::updateNotificationTimestampForList($listId, $maxTimestamp, $link);
                    $timeStamps[$listId] = $maxTimestamp;
                }
                else
                {
                    // if we go to calculate the timestamp and it's 0, store a 1 so we at least
                    // know we've calculated it once, otherwise we'll keep running this expensive
                    // query for no reason
                    TDOList::updateNotificationTimestampForList($listId, 1, $link);
                }                        
                
            }
        }
        

        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return $timeStamps;
    }
    
    
    
    public static function updateNotificationsForTask($taskid, $originalDueDate = NULL)
    {
        $task = TDOTask::getTaskForTaskId($taskid);
        if(empty($task))
            return false;
    
        $haveDueDate = false;
        $taskNotifications = TDOTaskNotification::getNotificationsForTask($taskid);
        
        if($task->dueDate() != 0)
        {
            $haveDueDate = true;
        }
        
        foreach($taskNotifications as $notification)
        {
            if ($notification->triggerOffset() != 0)
            {
                // calculate the notification based on the duedate/time + offset
                if($haveDueDate == false)
                    TDOTaskNotification::deleteTaskNotification($notification->notificationId());
                else
                {
                    if ($task->dueDateHasTime())
                    {
                        $newTriggerDate = $task->dueDate() - $notification->triggerOffset();
                        $notification->setTriggerDate($newTriggerDate);
                    }
                    else
                    {
                        $dueDate = TDOUtil::denormalizedDateFromGMTDate($task->dueDate());
                        $newTriggerDate = $dueDate - $notification->triggerOffset();
                        $notification->setTriggerDate($newTriggerDate);
                    }
                    $notification->updateTaskNotification();
                }
            }
            else
            {
                if($notification->triggerDate() == 0)
                {
                    // if we don't have a triggerDate then remove the notification because it will do NOTHING
                    TDOTaskNotification::deleteTaskNotification($notification->notificationId());
                    continue;
                }
                else
                {
                    if($haveDueDate && !empty($originalDueDate))
                    {
                        // if the task has a due date and the original duedate has a value, calculate the offset and move the
                        // notification forward the same amount
                        $offset =  $task->dueDate() - $originalDueDate;
                        $newTriggerDate = $notification->triggerDate() + $offset;
                        $notification->setTriggerDate($newTriggerDate);
                        $notification->updateTaskNotification();
                    }
                }
            }
        }
        
        return true;
    }

    public static function createNotificationsForRecurringTask($taskId, $completedTaskId, $offset)
    {
        $taskNotifications = TDOTaskNotification::getNotificationsForTask($taskId);
        foreach($taskNotifications as $notification)
        { 
            //Create a new notification to assign to the completed version, then mark it deleted
            $newNotification = new TDOTaskNotification();
            $newNotification->setSoundName($notification->soundName());
            $newNotification->setTriggerDate($notification->triggerDate());
            $newNotification->setTriggerOffset($notification->triggerOffset());
            $newNotification->setTaskId($completedTaskId);
            $newNotification->setDeleted(1);
            $newNotification->addTaskNotification();
            
            $notification->setTriggerDate($notification->triggerDate() + $offset);
            $notification->updateTaskNotification();
        }
    }


    
    
    public static function taskNotificationFromRow($row)
    {
        if($row)
        {
            $taskNotification = new TDOTaskNotification();
            
            if(isset($row['notificationid']))
                $taskNotification->setNotificationId($row['notificationid']);
            if(isset($row['taskid']))
                $taskNotification->setTaskId($row['taskid']);
            if(isset($row['sound_name']))
                $taskNotification->setSoundName($row['sound_name']);
            if(isset($row['deleted']))
                $taskNotification->setDeleted($row['deleted']);
            if(isset($row['triggerdate']))
                $taskNotification->setTriggerDate($row['triggerdate']);
            if(isset($row['triggeroffset']))
                $taskNotification->setTriggerOffset($row['triggeroffset']);
                
            return $taskNotification;
        }
        
        return NULL;
    }
	
    public static function getTaskIdForNotification($notificationId, $link=NULL)
    {
        if(empty($notificationId))
            return false;
        
        if($link == NULL)
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTaskNotification failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;        
        
            
        $notificationId = mysql_real_escape_string($notificationId, $link);
        
        $sql = "SELECT taskid FROM tdo_task_notifications WHERE notificationid='$notificationId'";
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                if(isset($row['taskid']))
                {
                    $taskid = $row['taskid'];
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return $taskid;
                }
            }
        }
        else
            error_log("getTaskIdForNotification failed: ".mysql_error());
            
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function getNextNotificationForUserAfterTime($userid, $triggerTime, $lastNotificationId=NULL)
    {
        if(empty($userid))
            return false;
        
        if(empty($triggerTime))
            $triggerTime = time();
            
        $link = TDOUtil::getDBLink();
        
        if(!$link)
        {
            error_log("TDOTaskNotification unable to get db link");
            return false;
        }
        
        $userid = mysql_real_escape_string($userid, $link);
        $triggerTime = intval($triggerTime);
        
        if(empty($lastNotificationId))
            $triggerWhereStatement = "triggerdate >= $triggerTime";
        else
            $triggerWhereStatement = "triggerdate > $triggerTime OR (triggerdate = $triggerTime AND notificationid > '".mysql_real_escape_string($lastNotificationId)."')";
            
        $sql = "SELECT tdo_task_notifications.* FROM tdo_task_notifications INNER JOIN tdo_tasks ON tdo_task_notifications.taskid=tdo_tasks.taskid LEFT JOIN tdo_list_memberships ON tdo_tasks.listid=tdo_list_memberships.listid WHERE userid='$userid' AND ($triggerWhereStatement) AND tdo_tasks.deleted=0 AND tdo_task_notifications.deleted=0 AND tdo_tasks.completiondate=0 AND ( (tdo_tasks.assigned_userid = '$userid') OR (tdo_tasks.assigned_userid IS NULL) OR (tdo_tasks.assigned_userid = '') ) ORDER BY triggerdate ASC, notificationid ASC LIMIT 1";
        
        $result = mysql_query($sql, $link);
        
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                $notification = TDOTaskNotification::taskNotificationFromRow($row);
                TDOUtil::closeDBLink($link);
                return $notification;
            }
        }
        else
            error_log("getNextNotificationForUserAfterTime query failed ".mysql_error());
            
        TDOUtil::closeDBLink($link);
        return false;
    }
    
	public function notificationId()
	{
        if(empty($this->_publicPropertyArray['notificationid']))
            return NULL;
        else
            return $this->_publicPropertyArray['notificationid'];
		          
	}
	public function setNotificationId($val)
	{
        if(empty($val))
            unset($this->_publicPropertyArray['notificationid']);
        else
            $this->_publicPropertyArray['notificationid'] = $val;
	}
	
	public function taskId()
	{
        if(empty($this->_publicPropertyArray['taskid']))
            return NULL;
        else
            return $this->_publicPropertyArray['taskid'];
		          
	}
	public function setTaskId($val)
	{
        if(empty($val))
            unset($this->_publicPropertyArray['taskid']);
        else
            $this->_publicPropertyArray['taskid'] = $val;
	}
    
    public function soundName()
	{
        if(empty($this->_publicPropertyArray['sound_name']))
            return NULL;
        else
            return $this->_publicPropertyArray['sound_name'];
		          
	}
	public function setSoundName($val)
	{
        if(empty($val))
            unset($this->_publicPropertyArray['sound_name']);
        else
            $this->_publicPropertyArray['sound_name'] = $val;
	}
    
	public function triggerDate()
	{
        if(empty($this->_publicPropertyArray['triggerdate']))
            return 0;
        else
            return $this->_publicPropertyArray['triggerdate'];
		          
	}
	public function setTriggerDate($val)
	{
        if(empty($val))
            unset($this->_publicPropertyArray['triggerdate']);
        else
            $this->_publicPropertyArray['triggerdate'] = $val;
	}
    
	public function triggerOffset()
	{
        if(empty($this->_publicPropertyArray['triggeroffset']))
            return NULL;
        else
            return $this->_publicPropertyArray['triggeroffset'];
		          
	}
	public function setTriggerOffset($val)
	{
        if(empty($val))
            unset($this->_publicPropertyArray['triggeroffset']);
        else
            $this->_publicPropertyArray['triggeroffset'] = $val;
	}
	
}


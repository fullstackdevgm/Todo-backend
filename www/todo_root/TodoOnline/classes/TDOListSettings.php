<?php
//      TDOListSettings
//      Used to handle all user data

// include files
include_once('TodoOnline/base_sdk.php');	
include_once('TodoOnline/DBConstants.php');	

define ('TASK_NOTIFICATION_TYPE', 'task');
define ('USER_NOTIFICATION_TYPE', 'user');
define ('COMMENT_NOTIFICATION_TYPE', 'comment');
//define ('INVITATION_NOTIFICATION_TYPE', 'invitation');
//define ('NOTE_NOTIFICATION_TYPE', 'note');
//define ('LIST_NOTIFICATION_TYPE', 'list');
//define ('EVENT_NOTIFICATION_TYPE', 'event');

class TDOListSettings extends TDODBObject 
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
	
    public function addListSettings($listid, $userid, $link=NULL)
    {
        if(empty($listid) || empty($userid))
        {
            return false;
        }
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOListSettings unable to get link");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        
        //Set the list notification settings according to the user's default email notification preferences
        $userSettings = TDOUserSettings::getUserSettingsForUserid($userid, $link);
        if($userSettings)
        {
            $emailNotificationDefaults = $userSettings->emailNotificationDefaults();
            
            $taskNotificationSetting = intval(($emailNotificationDefaults & TASK_EMAIL_NOTIFICATIONS_OFF) == 0);
            $userNotificationSetting = intval(($emailNotificationDefaults & USER_EMAIL_NOTIFICATIONS_OFF) == 0);
            $commentNotificationSetting = intval(($emailNotificationDefaults & COMMENT_EMAIL_NOTIFICATIONS_OFF) == 0);
            $assignedOnlyNotificationSetting = intval(($emailNotificationDefaults & ASSIGNED_ONLY_EMAIL_NOTIFICATIONS_ON) == ASSIGNED_ONLY_EMAIL_NOTIFICATIONS_ON);
        }
        else
        {
            $taskNotificationSetting = 0;
            $userNotificationSetting = 0;
            $commentNotificationSetting = 0;
            $assignedOnlyNotificationSetting = 0;
        }
        
        // Add a row for list settings for this user
        $userid = mysql_real_escape_string($userid, $link);
        $listid = mysql_real_escape_string($listid, $link);
        $cdavOrder = mysql_real_escape_string($this->cdavOrder(), $link);
        $cdavColor = mysql_real_escape_string($this->cdavColor(), $link);
        $hideDashboard = intval($this->hideDashboard());
		$iconName = mysql_real_escape_string($this->iconName(), $link);
		$sortOrder = intval($this->sortOrder());
		$sortType = intval($this->sortType());
		$defaultDueDate = intval($this->defaultDueDate());
        
        if($this->color() == NULL)
            $color = "150, 150, 150"; // TODO, generate a random color
        else
            $color = mysql_real_escape_string($this->color(), $link);
            
        if ($this->timestamp() == 0)
            $timestamp = time();
        else
            $timestamp = intval($this->timestamp());
        
            
        $sql = "INSERT INTO tdo_list_settings (listid, userid, cdavOrder, cdavColor, color, timestamp, hide_dashboard, task_notifications, user_notifications, comment_notifications, notify_assigned_only, icon_name, sort_order, sort_type, default_due_date) VALUES ('$listid', '$userid', '$cdavOrder', '$cdavColor', '$color', '$timestamp', $hideDashboard, $taskNotificationSetting, $userNotificationSetting, $commentNotificationSetting, $assignedOnlyNotificationSetting, '$iconName', $sortOrder, $sortType, $defaultDueDate)";
        $result = mysql_query($sql, $link);
		if(!$result)
		{
			error_log("Failed to add list settings with error :".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			return false;
		}
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return true;
    }
    
    public static function getListSettingsForUser($listid, $userid, $link=NULL)
    {
        if(empty($listid) || empty($userid))
        {
            return false;
        }
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOListSettings unable to get link");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $userid = mysql_real_escape_string($userid, $link);
        $listid = mysql_real_escape_string($listid, $link);
        
        $sql = "SELECT * FROM tdo_list_settings WHERE listid='$listid' AND userid='$userid'";
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                $listSettings = new TDOListSettings();
                $listSettings->updateWithSQLRow($row);
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return $listSettings;
            }
        
        }
        else
            error_log("getListSettingsForUser failed: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
        
    }
    
    public function getUpdateString($link)
    {
    
        $updateString = " timestamp='" . time() . "' ";
    
        if($this->cdavOrder() != NULL )
        {
            $order = mysql_real_escape_string($this->cdavOrder(), $link);
            $updateString .= ", cdavOrder='$order'";
        }
        else
        {
            $updateString .= ", cdavOrder=NULL";
        }
        
        if($this->cdavColor() != NULL)
        {
            $color = mysql_real_escape_string($this->cdavColor(), $link);
            $updateString .= ", cdavColor='$color'";
        }
        else
        {
            $updateString .= ", cdavColor=NULL";
        }
        
        $updateString .= ", sync_filter_tasks=".intval($this->filterSyncedTasks());
 	
        if($this->changeNotificationSettings() != NULL)
        {
            $notifications = $this->changeNotificationSettings();
            foreach($notifications as $notificationType=>$value)
            {
                $insertVal = intval($value);
                $insertParam = $notificationType."_notifications";
                    
                $updateString .= ", $insertParam=$insertVal";
            }
        }
        
        $updateString .= ", notify_assigned_only=".intval($this->notifyAssignedOnly());
        
        if($this->color() != NULL )
        {
			$color = mysql_real_escape_string($this->color(), $link);
			$updateString .= ", color='$color'";
        }
        else
        {
            $updateString .= ", color=NULL";
        }
		
		if($this->iconName() != NULL)
		{
			$iconName = mysql_real_escape_string($this->iconName(), $link);
			$updateString .= ", icon_name='$iconName'";
		}
		else
		{
			$updateString .= ", icon_name=NULL";
		}
		
		$sortOrder = intval($this->sortOrder());
		$updateString .= ", sort_order=$sortOrder";
		
		$sortType = intval($this->sortType());
		$updateString .= ", sort_type=$sortType";
		
		$defaultDueDate = intval($this->defaultDueDate());
		$updateString .= ", default_due_date=$defaultDueDate";
    
        $updateString .= ", hide_dashboard=".intval($this->hideDashboard());
    
        return $updateString;
    }
    
    public function updateListSettings($listid, $userid, $link=NULL)
    {
        if(empty($userid) || empty($listid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
                return false;
        }
        else
            $closeDBLink = false;
            
         $updateString = $this->getUpdateString($link);
        if(strlen($updateString) == 0)
        {
            error_log("Nothing to update in list settings");
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            
            return false;
        }       
        $userid = mysql_real_escape_string($userid, $link);
        $listid = mysql_real_escape_string($listid, $link);
       
         $sql = "UPDATE tdo_list_settings SET $updateString WHERE userid='$userid' AND listid='$listid'";
        
        if(mysql_query($sql, $link))
        {
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return true;
        }
        else
            error_log("updateListSettings failed with error: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }
    
    public function updateWithSQLRow($row)
    {
        if(isset($row['cdavOrder']))
            $this->setcdavOrder($row['cdavOrder']);
        if(isset($row['cdavColor']))
            $this->setcdavColor($row['cdavColor']);
        if(isset($row['sync_filter_tasks']))
            $this->setFilterSyncedTasks($row['sync_filter_tasks']);
        if(isset($row['color']))
            $this->setColor($row['color']);
		else
			$this->setColor("0, 0, 0");
		if(isset($row['icon_name']))
			$this->setIconName($row['icon_name']);
		if(isset($row['sort_order']))
			$this->setSortOrder($row['sort_order']);
		if(isset($row['sort_type']))
			$this->setSortType($row['sort_type']);
		if(isset($row['default_due_date']))
			$this->setDefaultDueDate($row['default_due_date']);
        if(isset($row['timestamp']))
            $this->setTimestamp($row['timestamp']);
        if(isset($row['hide_dashboard']))
            $this->setHideDashboard($row['hide_dashboard']);
        if(isset($row['notify_assigned_only']))
            $this->setNotifyAssignedOnly($row['notify_assigned_only']);
            
            
        $changeNotifications = array();
        
        $notificationTypes = TDOListSettings::getNotificationTypes();
        foreach($notificationTypes as $notificationType)
        {
            $paramString = $notificationType."_notifications";
            if(isset($row[$paramString]))
                $changeNotifications[$notificationType] = $row[$paramString];
            else
                $changeNotifications[$notificationType] = 0;
        }
        
        $this->setChangeNotificationSettings($changeNotifications);
    }
  	
    
    public static function shouldFilterSyncedTasksForList($listid, $userid)
    {
        if(empty($listid) || empty($userid))
        {
            return false;
        }
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOList failed to get dblink");
            return false;
        }
        
        $listid = mysql_real_escape_string($listid, $link);
        $userid = mysql_real_escape_string($userid, $link);
        $sql = "SELECT sync_filter_tasks FROM tdo_list_settings WHERE userid='$userid' AND listid='$listid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                if(isset($row['sync_filter_tasks']) && $row['sync_filter_tasks'] == 1)
                {
                    TDOUtil::closeDBLink($link);
                    return true;
                }
            }
        }
        else
            error_log("shouldFilterSyncedTasksForList failed: ".mysql_error());
            
        TDOUtil::closeDBLink($link);
        return false;
        
    }
	
    public static function getUsersToNotifyForChange($listid, $changeType, $itemId, $targetId, $userid)
    {
        if(empty($listid) || empty($changeType))
        {
            return false;
        }
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
        
        $listid = mysql_real_escape_string($listid);
        $userid = mysql_real_escape_string($userid);
        switch($changeType)
        {
            case ITEM_TYPE_INVITATION:
            case ITEM_TYPE_USER:
                $notificationType = USER_NOTIFICATION_TYPE;
                break;
            case ITEM_TYPE_TASKITO:
            case ITEM_TYPE_TASK:
                $notificationType = TASK_NOTIFICATION_TYPE;
                break;
            case ITEM_TYPE_COMMENT:
                $notificationType = COMMENT_NOTIFICATION_TYPE;
                break;
                
//            case ITEM_TYPE_LIST:
//                $notificationType = LIST_NOTIFICATION_TYPE;
//                break;

//            case ITEM_TYPE_NOTE:
//                $notificationType = NOTE_NOTIFICATION_TYPE;
//                break;
//            case ITEM_TYPE_EVENT:
//                $notificationType = EVENT_NOTIFICATION_TYPE;
//                break;

//            case ITEM_TYPE_INVITATION:
//                $notificationType = INVITATION_NOTIFICATION_TYPE;
//                break;

            default:
            {
//                error_log("Unknown change type $changeType");
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        
        $notificationField = $notificationType."_notifications";
        
        $sql = "SELECT userid FROM tdo_list_settings WHERE listid='$listid' AND $notificationField=1 AND userid != '$userid'";
        
        $task = NULL;
        $assignedObject = false;
        if($changeType == ITEM_TYPE_TASK)
        {
            $assignedObject = true;
            $task = TDOTask::getTaskForTaskId($itemId);
        }
        elseif($changeType == ITEM_TYPE_TASKITO)
        {
            $assignedObject = true;
            $taskito = TDOTaskito::taskitoForTaskitoId($itemId);
            if(!empty($taskito))
                $task = TDOTask::getTaskForTaskId($taskito->parentId());
        }
        elseif($changeType == ITEM_TYPE_COMMENT)
        {
            $assignedObject = true;
            $task = TDOTask::getTaskForTaskId($targetId);
        }
        
        if($assignedObject)
        {
            $sql .= " AND ((notify_assigned_only IS NULL OR notify_assigned_only = 0) ";
            if(!empty($task) && $task->assignedUserId() != NULL)
            {
                $sql .= " OR userid = '".mysql_real_escape_string($task->assignedUserId(), $link)."'";
            }
            $sql .= ")";
        }
       
        $result = mysql_query($sql, $link);
        if($result)
        {
            $userids = array();
            while($row=mysql_fetch_array($result))
            {
                if(isset($row['userid']))
                {
                    $userids[] = $row['userid'];
                }
            }
            
            TDOUtil::closeDBLink($link);
            return $userids;
        }
        else
            error_log("getUsersToNotifyForChange failed: ".mysql_error());
            
        TDOUtil::closeDBLink($link);
        return false;
        
    }
    
    
    public static function getUsersToNotifyOnCommentsInMessageCenter($listid, $userid)
    {
        if(empty($listid))
        {
            return false;
        }

        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
        
        $listid = mysql_real_escape_string($listid);
        $userid = mysql_real_escape_string($userid);
//        switch($changeType)
//        {
//            case ITEM_TYPE_INVITATION:
//            case ITEM_TYPE_USER:
//                $notificationType = USER_NOTIFICATION_TYPE;
//                break;
//            case ITEM_TYPE_TASKITO:
//            case ITEM_TYPE_TASK:
//                $notificationType = TASK_NOTIFICATION_TYPE;
//                break;
//            case ITEM_TYPE_COMMENT:
//                $notificationType = COMMENT_NOTIFICATION_TYPE;
//                break;
//            default:
//            {
//                //                error_log("Unknown change type $changeType");
//                TDOUtil::closeDBLink($link);
//                return false;
//            }
//        }
        
        // $notificationField = $notificationType."_notifications";
        
        // to begin, Message Center is going to notify all users
        $sql = "SELECT userid FROM tdo_list_settings WHERE listid='$listid' AND userid != '$userid'";
        
//        $task = NULL;
//        $assignedObject = false;
//        if($changeType == ITEM_TYPE_TASK)
//        {
//            $assignedObject = true;
//            $task = TDOTask::getTaskForTaskId($itemId);
//        }
//        elseif($changeType == ITEM_TYPE_TASKITO)
//        {
//            $assignedObject = true;
//            $taskito = TDOTaskito::taskitoForTaskitoId($itemId);
//            if(!empty($taskito))
//                $task = TDOTask::getTaskForTaskId($taskito->parentId());
//        }
//        elseif($changeType == ITEM_TYPE_COMMENT)
//        {
//            $assignedObject = true;
//            $task = TDOTask::getTaskForTaskId($targetId);
//        }
//        
//        if($assignedObject)
//        {
//            $sql .= " AND ((notify_assigned_only IS NULL OR notify_assigned_only = 0) ";
//            if(!empty($task) && $task->assignedUserId() != NULL)
//            {
//                $sql .= " OR userid = '".mysql_real_escape_string($task->assignedUserId(), $link)."'";
//            }
//            $sql .= ")";
//        }
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            $userids = array();
            while($row=mysql_fetch_array($result))
            {
                if(isset($row['userid']))
                {
                    $userids[] = $row['userid'];
                }
            }
            
            TDOUtil::closeDBLink($link);
            return $userids;
        }
        else
            error_log("getUsersToNotifyForChange failed: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
        
    }    
    
    
    
    public static function displayNameForNotificationType($changeType)
    {
        switch($changeType)
        {
//            case LIST_NOTIFICATION_TYPE:
//                return "List Settings";
            case USER_NOTIFICATION_TYPE:
                return "Members";
            case TASK_NOTIFICATION_TYPE:
                return "Tasks";
//            case INVITATION_NOTIFICATION_TYPE:
//                return "Invitations";
            case COMMENT_NOTIFICATION_TYPE:
                return "Comments";
            default:
            {
                return "Unknown";
            }
        }
        return "Unknown";

    }

    public static function shouldHideDashboardForListForUser($listid, $userid)
    {
        if($listid == "all" || $listid == "focus" || $listid == "starred")
        {
            $userSettings = TDOUserSettings::getUserSettingsForUserid($userid);
            if(empty($userSettings))
            {
                error_log("TDOListSettings could not get user settings for user: ".$userid);
                return false;
            }
        
            if($listid == "focus")
                return $userSettings->focusListHideDashboard();
            elseif($listid == "starred")
                return $userSettings->starredListHideDashboard();
            else
                return $userSettings->allListHideDashboard();
        }
        else
        {
            $listSettings = TDOListSettings::getListSettingsForUser($listid, $userid);
            if(empty($listSettings))
            {
                error_log("TDOListSettings could not get list settings for user for list: ".$listid);
                return false;
            }
            
            return $listSettings->hideDashboard();
        }
    }
    
    //Returns 
    public static function getListsAndSettingsForUser($userid, $includeInbox=false)
    {
        if(empty($userid))
        {
            error_log("getListsAndSettingsForUser called missing parameter userid");
            return false;
        }
        
        $userInbox = TDOList::getUserInboxId($userid, false);

        $lists = TDOList::getListsForUser($userid);
        if(empty($lists))
        {
            error_log("getListsAndSettingsForUser failed to get lists for user: $userid");
            return false;
        }
        
        $listArray = array();
        foreach($lists as $list)
        {
            if($includeInbox || $list->listId() != $userInbox)
            {
                $settings = TDOListSettings::getListSettingsForUser($list->listId(), $userid);
                if($settings)
                {
                    $listData = array();
                    $listData['list'] = $list;
                    $listData['settings'] = $settings;
                    $listArray[] = $listData;
                }
                else
                {
                    error_log("getListsAndSettingsForUser failed to get settings for user for list: ".$list->listId());
                    return false;
                }
            }
        }
        
        return $listArray;
    }
    
    public static function updateEmailNotificationsForAllListsForUser($userid, $taskNotificationSetting, $userNotificationSetting, $commentNotificationSetting, $assignedOnlyNotificationSetting)
    {
        if(empty($userid))
        {
            error_log("updateEmailNotificationsForAllListsForUser called missing parameter userid");
            return false;
        }
        
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOListSettings failed to get DB link");
            return false;
        }

        $sql = "UPDATE tdo_list_settings SET task_notifications=".intval($taskNotificationSetting).", user_notifications=".intval($userNotificationSetting).", comment_notifications=".intval($commentNotificationSetting).", notify_assigned_only=".intval($assignedOnlyNotificationSetting)." WHERE userid='".mysql_real_escape_string($userid, $link)."'";
        
        if(!mysql_query($sql, $link))
        {
            error_log("updateEmailNotificationsForAllListsForUser failed with error: ".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        TDOUtil::closeDBLink($link);
        return true;
    }

	public function cdavOrder()
	{
        if(empty($this->_publicPropertyArray['cdav_order']))
            return NULL;
        else
            return $this->_publicPropertyArray['cdav_order'];
	}
	public function setcdavOrder($val)
	{
        if(empty($val))
            unset($this->_publicPropertyArray['cdav_order']);
        else
            $this->_publicPropertyArray['cdav_order'] = $val;
	}	

	public function cdavColor()
	{
        if(empty($this->_publicPropertyArray['cdav_color']))
            return NULL;
        else
            return $this->_publicPropertyArray['cdav_color'];
	}
	public function setcdavColor($val)
	{
        if(empty($val))
            unset($this->_publicPropertyArray['cdav_color']);
        else
            $this->_publicPropertyArray['cdav_color'] = $val;
	}	

    public function filterSyncedTasks()
    {
        if(empty($this->_publicPropertyArray['filter_synced_tasks']))
            return 0;
        else
            return $this->_publicPropertyArray['filter_synced_tasks'];
    }
    public function setFilterSyncedTasks($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['filter_synced_tasks']);
        else
            $this->_publicPropertyArray['filter_synced_tasks'] = $val;
    }

    public function notifyAssignedOnly()
    {
        if(empty($this->_publicPropertyArray['notify_assigned_only']))
            return 0;
        else
            return $this->_publicPropertyArray['notify_assigned_only'];
    }
    public function setNotifyAssignedOnly($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['notify_assigned_only']);
        else
            $this->_publicPropertyArray['notify_assigned_only'] = $val;
    }

    public function changeNotificationSettings()
    {
        if(empty($this->_publicPropertyArray['change_notifications']))
            return NULL;
        else
            return $this->_publicPropertyArray['change_notifications'];
    }
    
    public function setChangeNotificationSettings($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['change_notifications']);
        else
            $this->_publicPropertyArray['change_notifications'] = $val;
    }
    
    public function color()
    {
        if(empty($this->_publicPropertyArray['color']))
            return NULL;
        else
            return $this->_publicPropertyArray['color'];
    }
    
    public function setColor($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['color']);
        else
            $this->_publicPropertyArray['color'] = $val;
    }
	
	public function iconName()
	{
		if(empty($this->_publicPropertyArray['icon_name']))
			return NULL;
		else
			return $this->_publicPropertyArray['icon_name'];
	}
	
	public function setIconName($val)
	{
		if(empty($val))
			unset($this->_publicPropertyArray['icon_name']);
		else
			$this->_publicPropertyArray['icon_name'] = $val;
	}
	
	public function sortOrder()
	{
		if(empty($this->_publicPropertyArray['sort_order']))
			return 0;
		else
			return intval($this->_publicPropertyArray['sort_order']);
	}
	
	public function setSortOrder($intVal)
	{
		if(empty($intVal))
			unset($this->_publicPropertyArray['sort_order']);
		else
			$this->_publicPropertyArray['sort_order'] = $intVal;
	}
	
	public function sortType()
	{
		if(empty($this->_publicPropertyArray['sort_type']))
			return 0;
		else
			return intval($this->_publicPropertyArray['sort_type']);
	}
	
	public function setSortType($intVal)
	{
		if(empty($intVal))
			unset($this->_publicPropertyArray['sort_type']);
		else
			$this->_publicPropertyArray['sort_type'] = $intVal;
	}
	
	public function defaultDueDate()
	{
		if(empty($this->_publicPropertyArray['default_due_date']))
			return 0;
		else
			return intval($this->_publicPropertyArray['default_due_date']);
	}
	
	public function setDefaultDueDate($intVal)
	{
		if(empty($intVal))
			unset($this->_publicPropertyArray['default_due_date']);
		else
			$this->_publicPropertyArray['default_due_date'] = $intVal;
	}
	
    public function hideDashboard()
    {
        if(empty($this->_publicPropertyArray['hide_dashboard']))
            return 0;
        else
            return $this->_publicPropertyArray['hide_dashboard'];
    }
    
    public function setHideDashboard($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['hide_dashboard']);
        else
            $this->_publicPropertyArray['hide_dashboard'] = $val;
    }
    
    public static function getNotificationTypes()
    {
        return array(TASK_NOTIFICATION_TYPE, COMMENT_NOTIFICATION_TYPE, USER_NOTIFICATION_TYPE);
//        return array(LIST_NOTIFICATION_TYPE, TASK_NOTIFICATION_TYPE, EVENT_NOTIFICATION_TYPE, NOTE_NOTIFICATION_TYPE, COMMENT_NOTIFICATION_TYPE,  INVITATION_NOTIFICATION_TYPE, USER_NOTIFICATION_TYPE);
        
    }
	
}


<?php
//      TDOList
//      Used to handle all user data

// include files
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');	
include_once('Facebook/config.php');
include_once('Facebook/facebook.php');

define ('LIST_NAME_LENGTH', 72);

class TDOList extends TDODBObject 
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
    
    // ------------------------
    // property Methods
    // ------------------------
    
    
    public function listId()
    {
        if(empty($this->_publicPropertyArray['listid']))
            return NULL;
        else
            return $this->_publicPropertyArray['listid'];
    }
    public function setListId($val, $updateCalDav = false)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['listid']);
        else
            $this->_publicPropertyArray['listid'] = $val;
    }
    
    // transient icon_name property
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
    
    public function description()
    {
        if(empty($this->_publicPropertyArray['description']))
            return NULL;
        else
            return $this->_publicPropertyArray['description'];
    }
    public function setDescription($val, $updateCalDav = false)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['description']);
        else
            $this->_publicPropertyArray['description'] = TDOUtil::ensureUTF8($val);
    }

    public function creator()
    {
        if(empty($this->_publicPropertyArray['creator']))
            return NULL;
        else
            return $this->_publicPropertyArray['creator'];
    }
    public function setCreator($val, $updateCalDav = false)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['creator']);
        else
            $this->_publicPropertyArray['creator'] = $val;
    }
    

    public function caldavUri()
    {
        if(empty($this->_privatePropertyArray['caldavuri']))
            return NULL;
        else
            return $this->_privatePropertyArray['caldavuri'];
    }
    public function setCaldavUri($val, $updateCalDav = false)
    {
        if(empty($val))
            unset($this->_privatePropertyArray['caldavuri']);
        else
            $this->_privatePropertyArray['caldavuri'] = $val;
    }
	

    public function caldavTimeZone()
    {
        if(empty($this->_privatePropertyArray['caldavtimezone']))
            return NULL;
        else
            return $this->_privatePropertyArray['caldavtimezone'];
    }
    public function setCaldavTimeZone($val, $updateCalDav = false)
    {
        if(empty($val))
            unset($this->_privatePropertyArray['caldavtimezone']);
        else
            $this->_privatePropertyArray['caldavtimezone'] = $val;
    }
    
    
    public function taskTimestamp()
    {
        if(empty($this->_publicPropertyArray['task_timestamp']))
            return 0;
        else
            return $this->_publicPropertyArray['task_timestamp'];
    }
    private function setTaskTimestamp($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['task_timestamp']);
        else
            $this->_publicPropertyArray['task_timestamp'] = $val;
    }

    
    public function taskitoTimestamp()
    {
        if(empty($this->_publicPropertyArray['taskito_timestamp']))
            return 0;
        else
            return $this->_publicPropertyArray['taskito_timestamp'];
    }
    private function setTaskitoTimestamp($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['taskito_timestamp']);
        else
            $this->_publicPropertyArray['taskito_timestamp'] = $val;
    }

    
    public function notificationTimestamp()
    {
        if(empty($this->_publicPropertyArray['notification_timestamp']))
            return 0;
        else
            return $this->_publicPropertyArray['notification_timestamp'];
    }
    private function setNotificationTimestamp($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['notification_timestamp']);
        else
            $this->_publicPropertyArray['notification_timestamp'] = $val;
    }

    public function getMembersByListId($list_id)
    {
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOList failed to get dblink");
            return false;
        }
        $list_id = mysql_real_escape_string(trim($list_id));
        $sql = 'SELECT * FROM tdo_user_accounts LEFT JOIN tdo_list_memberships ON tdo_list_memberships.userid = tdo_user_accounts.userid WHERE tdo_list_memberships.listid = "' . $list_id . '"';
        $result = mysql_query($sql);
        if($result)
        {
            $users = array();
            while($row = mysql_fetch_array($result))
            {
                $user = TDOUser::userFromRow($row);
                $users[] = array(
                    'user' => $user,
                    'membership_type' => $row['membership_type']
                );
            }

            TDOUtil::closeDBLink($link);
            return $users;
        }
        else
        {
            error_log("Unable to get members for list: ".mysql_error());
        }

        TDOUtil::closeDBLink($link);
        return false;
    }
    /**
     * @return bool|int
     *
     * get members count in list
     */
    public function getMembersCount()
    {
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOList failed to get dblink");
            return false;
        }
        $list_id = $this->listId();
        $sql = 'SELECT COUNT(userid) FROM tdo_list_memberships WHERE listid = "' . $list_id . '"';
        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                TDOUtil::closeDBLink($link);
                return intval($total[0]);
            }
        }
        else
        {
            error_log("Unable to get member count: ".mysql_error());
        }

        TDOUtil::closeDBLink($link);
        return false;
    }

    /**
     * @param bool|FALSE $deleted
     * @return bool|int
     *
     * get task count in list
     */
    public function getTaskCount($deleted = FALSE)
    {
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOList failed to get dblink");
            return false;
        }
        $list_id = $this->listId();
        $sql = 'SELECT COUNT(taskid) FROM tdo_tasks WHERE listid = "' . $list_id . '"';
        if ($deleted) {
            $sql .= ' AND deleted = 1';
        }
        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                TDOUtil::closeDBLink($link);
                return intval($total[0]);
            }
        }
        else
        {
            error_log("Unable to get task count: ".mysql_error());
        }

        TDOUtil::closeDBLink($link);
        return false;
    }

    
    
    //If we're passing in a link, it better already have a trasnaction in place
	public static function deleteList($listid, $link=NULL)
	{
//		error_log("TDOList::deleteList('" . $listid . "')");
        if(!isset($listid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }    
            
            //Do all of this in a transaction so we won't end up with a partially deleted list
            if(!mysql_query("START TRANSACTION", $link))
            {
                error_log("TDOList::Couldn't start transaction".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $escapedListId = mysql_real_escape_string($listid, $link);
        
        // we need to get all of the tasks and then delete all of them here
        $taskIds = TDOTask::getAllTaskIdsForListId($listid, $link);
        
        foreach($taskIds as $taskid)
        {
            if(TDOTask::deleteObject($taskid, $link, true) == false)
            {
                error_log("TDOList::Failed removing task from list: " . $taskid);
                if($closeDBLink)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
        }
        
        //Delete the list
        if(!mysql_query("UPDATE tdo_lists SET deleted=1, timestamp='" . time() . "' WHERE listid='$escapedListId'", $link))
        {
            error_log("TDOList::Could not delete list, rolling back".mysql_error());
            if($closeDBLink)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
        
        if($closeDBLink)
        {
            if(!mysql_query("COMMIT", $link))
            {
                error_log("TDOList::Couldn't commit transaction".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
            else
                TDOUtil::closeDBLink($link);
        }
        
        return true;
	}

    public static function permanentlyDeleteList($listid, $link=NULL)
    {
        if(empty($link))
        {
            $closeTransaction = true;
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOList failed to get db link");
                return false;
            }
            
            if(!mysql_query("START TRANSACTION", $link))
            {
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        else
            $closeTransaction = false;
    
        $escapedListId = mysql_real_escape_string($listid, $link);
    
        //Completely wipe out the list and all its tasks, comments, notifications, taskitos, etc.
        $tableNames = array("tdo_tasks", "tdo_completed_tasks", "tdo_deleted_tasks");
        foreach($tableNames as $tableName)
        {
            $sql = "SELECT taskid FROM $tableName WHERE listid='$escapedListId'";
            $response = mysql_query($sql, $link);
            if($response)
            {
                while($row = mysql_fetch_array($response))
                {
                    if(isset($row['taskid']))
                    {
                        if(TDOTask::permanentlyDeleteTask($row['taskid'], $tableName, $link) == false)
                        {
                            if($closeTransaction)
                            {
                                mysql_query("ROLLBACK", $link);
                                TDOUtil::closeDBLink($link);
                            }
                            return false;
                        }
                    }
                }
                
            }
            else
            {
                error_log("permanentlyDeleteList failed to get tasks for list with error: ".mysql_error());
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
        }
        
        //Wipe out all change log items for the list
        if(TDOChangeLog::permanentlyDeleteAllChangeLogsForList($listid, $link) == false)
        {
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false; 
        }
        
        if(!mysql_query("DELETE FROM tdo_list_settings WHERE listid='$escapedListId'", $link))
        {
            error_log("TDOList::Could not delete list settings, rolling back ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
        
        if(!mysql_query("DELETE FROM tdo_list_memberships WHERE listid='$escapedListId'",$link))
        {
            error_log("TDOList::Could not delete list memberships, rolling back ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;            
        }
        
        if(!mysql_query("DELETE FROM tdo_invitations WHERE listid='$escapedListId'",$link))
        {
            error_log("TDOList::Could not delete list invitations, rolling back ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;            
        }
        
        if(!mysql_query("DELETE FROM tdo_lists WHERE listid='$escapedListId'", $link))
        {
            error_log("TDOList::Could not delete list, rolling back ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
        
        if($closeTransaction)
        {
            if(!mysql_query("COMMIT", $link))
            {
                error_log("TDOList::Couldn't commit transaction ".mysql_error());
                mysql_query("ROLLBACK");
                TDOUtil::closeDBLink($link);
                return false;
            }
            else
                TDOUtil::closeDBLink($link);
        }
        
        return true;
    }

	public function addList($userid, $teamid=NULL, $link=NULL)
	{
        if(empty($userid))
            return false;
            
		if($this->name() == NULL)
		{
			error_log("TDOList::addList failed because name was not set");
			return false;
		}
		if($this->creator() == NULL)
		{
			error_log("TDOList::addList failed because creator was not set");
			return false;
		}

        if(empty($link))
        {
            $closeTransaction = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
            
            if(!mysql_query("START TRANSACTION", $link))
            {
                error_log("addList failed to start transaction");
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        else
            $closeTransaction = false;

        // CRG Added this for legacy migration
        $listId = $this->listId();
        if($listId == NULL)
        {
            $listId = TDOUtil::uuid();
            $this->setListId($listId);
        }
        
        $name = mb_strcut($this->name(), 0, LIST_NAME_LENGTH, 'UTF-8');
        $name = mysql_real_escape_string($name, $link);
        $description = mysql_real_escape_string($this->description(), $link);
        $creator = mysql_real_escape_string($this->creator(), $link);
		$userid = mysql_real_escape_string($userid, $link);
		
		if ($this->caldavUri() == NULL)
			$cdavUri = $this->listId();
		else
			$cdavUri = mysql_real_escape_string($this->caldavUri(), $link);
		
        $cdavTimeZone = mysql_real_escape_string($this->caldavTimeZone(), $link);

        if ($this->timestamp() == 0)
            $timestamp = time();
        else
            $timestamp = intval($this->timestamp());

        
        $deleted = intval($this->deleted());
      
		
		// Create the list
		$sql = "INSERT INTO tdo_lists (listid, name, description, creator, cdavUri, cdavTimeZone, deleted, timestamp) VALUES ('$listId', '$name', '$description', '$creator', '$cdavUri', '$cdavTimeZone', $deleted, '$timestamp')";
		$result = mysql_query($sql, $link);
		if(!$result)
		{
			error_log("Failed to add list with error :".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
			return false;
		}

        $listId = $this->listId();

		// Add the user as a LIST_MEMBERSHIP_OWNER
		$listOwner = $userid;
		$sql = "INSERT INTO tdo_list_memberships (listid, userid, membership_type) VALUES ('$listId', '$listOwner', ".LIST_MEMBERSHIP_OWNER.")";
		
		
		$result = mysql_query($sql, $link);
		if(!$result)
		{
			error_log("Failed to add owner with error :".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
			return false;
		}
        
        $listSettings = new TDOListSettings();
        
        if(!$listSettings->addListSettings($this->listId(), $userid, $link))
        {
            error_log("Failed to add list settings");
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
		
        if($closeTransaction)
        {
            if(!mysql_query("COMMIT", $link))
            {
                error_log("TDOList failed to commit transaction");
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
            else
                TDOUtil::closeDBLink($link);
        }
        
		
		return true;
	}
	
    
    
    
    
    
    
	
	public function updateList($userid, $link=NULL)
	{
		
		if($this->listId() == NULL || empty($userid))
		{
			error_log("Update failed: listid or UserID was emtpy");
			return false;
		}
		
        
        if(!$link)
        {
            $link = TDOUtil::getDBLink();        
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }  
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }
        
        
        $listid = $this->listId();
        
		$updateString = " tdo_lists.timestamp='" . time() . "' ";
        
		if($this->name() != NULL)
        {
            $name = mb_strcut($this->name(), 0, LIST_NAME_LENGTH, 'UTF-8');
			$name = mysql_real_escape_string($name, $link);

            if (strlen($updateString) > 0)
				$updateString .= ", ";
			
			$updateString = $updateString . " name='$name'";
        }

		if($this->description() != NULL )
        {
			$description = mysql_real_escape_string($this->description(), $link);
			
			if (strlen($updateString) > 0)
				$updateString .= ", ";
			
			$updateString .= " description='$description'";
        }
		
        
		if($this->caldavTimeZone() != NULL )
        {
			$timeZone = mysql_real_escape_string($this->caldavTimeZone(), $link);
			
			if (strlen($updateString) > 0)
				$updateString .= ", ";
			
			$updateString .= " cdavTimeZone='$timeZone'";
        }
		
        $deleted = intval($this->deleted());
        
        if (strlen($updateString) > 0)
            $updateString .= ", ";
        
        $updateString .= " deleted=$deleted";
        
        if(strlen($updateString) == 0)
		{
            error_log("TDOList::updateList() nothing to update");
            if($shouldCloseLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
		
        $sql = "UPDATE tdo_lists SET " . $updateString . " WHERE tdo_lists.listid='$listid'";
		//$sql = "UPDATE tdo_lists SET $updateString WHERE listid='$listid'";

        
        $response = mysql_query($sql, $link);
        if($response)
        {
            if($shouldCloseLink)
                TDOUtil::closeDBLink($link);
			
            return true;
        }
        else
        {
			error_log("Unable to update list $listid :".mysql_error());
            if($shouldCloseLink)
                TDOUtil::closeDBLink($link);
            return false;
        }

	}
    
    
    //Call this if you want to mark a list as updated without doing a full update (for
    //example if a color is added or removed)
    public static function updateTimestampForList($listid)
    {
        if(empty($listid))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOList failed to get DB link");
            return false;
        }
        
        $listid = mysql_real_escape_string($listid, $link);
        $sql = "UPDATE tdo_lists SET timestamp=".time()." WHERE listid='$listid'";
        
        if(!mysql_query($sql, $link))
        {
            error_log("TDOList::updateTimestampForList failed: ".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        TDOUtil::closeDBLink($link);
        return true;
    }
    
    
	public static function taskTimestampForList($listid, $link=NULL)
    {
        if(!isset($listid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $realListid = mysql_real_escape_string($listid, $link);
        
        $sql = "SELECT task_timestamp FROM tdo_lists WHERE listid='$realListid'";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
            $row =  mysql_fetch_array($response);
            if($row)
            {
                if(isset($row['task_timestamp']))
                {
                    $taskTimeStamp = $row['task_timestamp'];
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return $taskTimeStamp;
                }
            }
        }
        
        error_log("TDOList::taskTimestampForList failed: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);

        return false;        
    }    
    
    // Called to update the task timestamp on a list
    // this will not modify the list's timestamp or any other property
    public static function updateTaskTimestampForList($listid, $timestamp, $link=NULL)
    {
        if(empty($listid))
            return false;

        if(empty($timestamp))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;        
        
        $listid = mysql_real_escape_string($listid, $link);
        $sql = "UPDATE tdo_lists SET task_timestamp=".$timestamp." WHERE listid='$listid'";
        
        if(!mysql_query($sql, $link))
        {
            error_log("TDOList::updateTaskTimestampForList failed: ".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return true;
    }    
    
    
	public static function taskitoTimestampForList($listid, $link=NULL)
    {
        if(!isset($listid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $realListid = mysql_real_escape_string($listid, $link);
        
        $sql = "SELECT taskito_timestamp FROM tdo_lists WHERE listid='$realListid'";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
            $row =  mysql_fetch_array($response);
            if($row)
            {
                if(isset($row['taskito_timestamp']))
                {
                    $taskitoTimeStamp = $row['taskito_timestamp'];
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return $taskitoTimeStamp;
                }
            }
        }
        
        error_log("TDOList::taskitoTimestampForList failed: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        
        return false;        
    }     
    
    // Called to update the taskito timestamp on a list
    // this will not modify the list's timestamp or any other property
    public static function updateTaskitoTimestampForList($listid, $timestamp, $link=NULL)
    {
        if(empty($listid))
            return false;
        
        if(empty($timestamp))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;        
        
        $listid = mysql_real_escape_string($listid, $link);
        $sql = "UPDATE tdo_lists SET taskito_timestamp=".$timestamp." WHERE listid='$listid'";
        
        if(!mysql_query($sql, $link))
        {
            error_log("TDOList::updateTaskitoTimestampForList failed: ".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return true;
    }     
    
    
	public static function notificationTimestampForList($listid, $link=NULL)
    {
        if(!isset($listid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $realListid = mysql_real_escape_string($listid, $link);
        
        $sql = "SELECT notification_timestamp FROM tdo_lists WHERE listid='$realListid'";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
            $row =  mysql_fetch_array($response);
            if($row)
            {
                if(isset($row['notification_timestamp']))
                {
                    $notificationTimeStamp = $row['notification_timestamp'];
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return $notificationTimeStamp;
                }
            }
        }
        
        error_log("TDOList::notificationTimestampForList failed: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        
        return false;        
    }    
    
    // Called to update the notification timestamp on a list
    // this will not modify the list's timestamp or any other property
    public static function updateNotificationTimestampForList($listid, $timestamp, $link=NULL)
    {
        if(empty($listid))
            return false;
        
        if(empty($timestamp))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;        
        
        $listid = mysql_real_escape_string($listid, $link);
        $sql = "UPDATE tdo_lists SET notification_timestamp=".$timestamp." WHERE listid='$listid'";
        
        if(!mysql_query($sql, $link))
        {
            error_log("TDOList::updateNotificationTimestampForList failed: ".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return true;
    }    
    
    

    public static function getRoleForUser($listid, $userid, $link=NULL)
    {
        if(!isset($listid) || !isset($userid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $userid = mysql_real_escape_string($userid, $link);
        $listid = mysql_real_escape_string($listid, $link);
        
        $sql = "SELECT membership_type FROM tdo_list_memberships WHERE listid='$listid' AND userid='$userid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $resultArray = mysql_fetch_array($result);
            if($resultArray)
            {
                if(isset($resultArray['membership_type']))
                {
                    $type = $resultArray['membership_type'];
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return $type;
                }
            }
        }
        else
        {
            error_log("TDOList unable to get role for user: ".mysql_error());
        }
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }
	
	public static function getRoleForCalDavUser($calDavListUri, $userId)
	{
		if ( (empty($calDavListUri)) || (empty($userId)) )
		{
			error_log("TDOList::getRoleForCalDavUser() called with empty parameters");
			return LIST_MEMBERSHIP_VIEWER; // default to lowest role available
		}
		
		$listid = TDOList::getListidForCalDavUri($calDavListUri);
		if ($listid == false)
		{
			error_log("TDOList::getRoleForCalDavUser() could not find list id from calDavListUri: " . $calDavListUri);
			return LIST_MEMBERSHIP_VIEWER; // default to lowest role available
		}
		
		return TDOList::getRoleForUser($listid, $userId);
	}
	
	public static function getListidForCalDavUri($calDavListUri)
	{
		if (empty($calDavListUri))
			return false;
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList::getListidForCalDavUri() failed to get dblink");
			return false;
		}    
        $uri = mysql_real_escape_string($calDavListUri, $link);
        
        $sql = "SELECT listid FROM tdo_lists WHERE cdavUri='$uri'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $resultArray = mysql_fetch_array($result);
            if($resultArray)
            {
                if(isset($resultArray['listid']))
                {
					$listid = $resultArray['listid'];
                    TDOUtil::closeDBLink($link);
                    return $listid;
                }
            }
        }
        else
        {
            error_log("TDOList::getListidForCalDavUri() unable to get list id from caldav uri: ".mysql_error());
        }
        
        TDOUtil::closeDBLink($link);
        return false;
	}


    public static function getPeopleAndRolesForlistid($listid, $orderByMembershipType=false, $userid=NULL, $link=NULL)
    {
        if(empty($listid))
            return false;
    
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }  
        }
        else
            $closeDBLink = false;
        
        if($listid == 'all' || $listid == 'focus' || $listid == 'starred')
        {
            if(empty($userid))
            {
                error_log("getPeopleAndRolesForlistid ".$listid." called with missing userid");
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return false;
            }
            
//          This call was showing up in slow calls so I broke it up into two queries and removed the distinct call so make it faster since we'll only return unique results in the array we populate
//            $sql = "SELECT DISTINCT tdo_list_memberships.userid, membership_type FROM tdo_list_memberships INNER JOIN tdo_user_accounts ON tdo_list_memberships.userid=tdo_user_accounts.userid WHERE listid IN (SELECT listid FROM tdo_list_memberships WHERE userid='".mysql_real_escape_string($userid, $link)."') ORDER BY ";

            // get all of the list IDs for the user
            $lists = TDOList::getListIDsForUser($userid);
            if(empty($lists))
            {
                _e('Unable to get lists for user:') . $userid . "\n";
                return false;
            }
            
            $listString = "";
            foreach($lists as $listId)
            {
                if(strlen($listString) > 0)
                    $listString .= ",";
                
                $listString .= "'$listId'";
            }
            
            if(strlen($listString) == 0)
            {
                _e('Unable to get lists for user:') . $userid . "\n";
                return false;        
            }
            
            $sql = "SELECT tdo_list_memberships.userid, membership_type FROM tdo_list_memberships INNER JOIN tdo_user_accounts ON tdo_list_memberships.userid=tdo_user_accounts.userid WHERE listid IN ($listString) ORDER BY ";
        }
        else
        {
            $listid = mysql_real_escape_string($listid);
            
            //Inner join with user accounts to enable sort by name
            $sql = "SELECT tdo_list_memberships.userid, membership_type FROM tdo_list_memberships INNER JOIN tdo_user_accounts ON tdo_list_memberships.userid=tdo_user_accounts.userid WHERE listid='$listid' ORDER BY ";
        }
            
        if($orderByMembershipType)
        {
            $sql .= "membership_type DESC, ";
        }
        $sql .= "first_name, last_name, username";
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            $peopleArray = array();
            while($row = mysql_fetch_array($result))
            {
                if(isset($row['userid']) && isset($row['membership_type']))
                {
                    $userid = $row['userid'];
                    $type = $row['membership_type'];
                    $peopleArray[$userid] = $type;
                }
            }
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return $peopleArray;
        }
        else
        {
            error_log("TDOList unable to fetch roles: ".mysql_error());
        }
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function getEditingMembersForlistid($listid, $link=NULL)
    {
        if(!isset($listid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }  
        }
        else
            $closeDBLink = false;        
    
	    $listid = mysql_real_escape_string($listid);
        $sql = "SELECT userid FROM tdo_list_memberships WHERE listid='$listid' AND membership_type !=".LIST_MEMBERSHIP_VIEWER;
        $result = mysql_query($sql, $link);
        if($result)
        {
            $peopleArray = array();
            while($row = mysql_fetch_array($result))
            {
                if(isset($row['userid']))
                {
                    $peopleArray[] = $row['userid'];
                }
            }
            if($closeDBLink)            
                TDOUtil::closeDBLink($link);
            return $peopleArray;
        }
        else
        {
            error_log("TDOList unable to fetch users: ".mysql_error());
        }
        if($closeDBLink)            
            TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function changeUserRole($listid, $userid, $newRole)
    {
        if(!isset($listid) || !isset($userid) || !isset($newRole))
            return false;
            
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}  
        
        $listid = mysql_real_escape_string($listid, $link);
        $userid = mysql_real_escape_string($userid, $link);
        $membershipType = intval($newRole);
        
        $sql = "UPDATE tdo_list_memberships SET membership_type=$membershipType WHERE listid='$listid' AND userid='$userid'";
        $result = mysql_query($sql);
        if($result)
        {
            TDOUtil::closeDBLink($link);
            return true;
        } 
        else
        {
            error_log("Unable to change user role: ".mysql_error());
        }
        
        TDOUtil::closeDBLink($link);
        return false;
    }

	
	// this method is to sort the results of the lists once we get them back
	public static function listCompare($a, $b)
	{
		return strcasecmp($a->name(), $b->name());
	}
	
	public static function getListIDsForUser($userid, $link = NULL)
	{
        if(!isset($userid))
            return false;
		
        if(empty($link))
        {
            $closeLink = true;
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOList::getListIDsForUser failed to get db link");
                return false;
            }
        }
        else
            $closeLink = false;
		
        $escapedUserid = mysql_real_escape_string($userid, $link);
        
        $sql = "SELECT listid FROM tdo_list_memberships WHERE userid='$escapedUserid'";
        $result = mysql_query($sql);
        if($result)
        {
			$listIDs = array();
            while($row = mysql_fetch_array($result))
            {
                if(isset($row['listid']))
                {
                    $listid = $row['listid'];
					$listIDs[] = $listid;
                }
            }
            if($closeLink)
                TDOUtil::closeDBLink($link);
			
            return $listIDs;
        }
        else
        {
            error_log("Unable to get list ids for user ($userid): ".mysql_error());
        }
        
		if($closeLink)
			TDOUtil::closeDBLink($link);
        return false;
	}
	
	public static function getListsForUser($userid, $includeDeleted=false, $link=NULL)
	{
        if(!isset($userid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
		
		// If this user is a member of a team, make sure to NOT return any
		// shared team lists if the team subscription has expired and the
		// grace period has passed.
		// More info: https://github.com/Appigo/todo-issues/issues/1047
		//
		// If the team has expired and is past the grace period, build a list of
		// team-owned lists so we can prevent them from being added.
		
		$teamListIDs = array();
		$teamAccount = TDOTeamAccount::getTeamForTeamMember($userid, $link);
		if (!empty($teamAccount))
		{
			$teamID = $teamAccount->getTeamID();
			if (TDOTeamAccount::getTeamSubscriptionStatus($teamID, $link) == TEAM_SUBSCRIPTION_STATE_EXPIRED || TDOTeamAccount::getTeamSubscriptionStatus($teamID, $link) == TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD)
			{
				$teamLists = TDOList::getSharedListsForTeam($teamID, $includeDeleted, $link);
				if (!empty($teamLists) && count($teamLists) > 0)
				{
					// Track all team list ids so we can prevent them from
					// being added and returned to the user.
					foreach ($teamLists as $teamList)
					{
						$teamListIDs[] = $teamList->listId();
					}
				}
			}
		}
		
        $escapedUserid = mysql_real_escape_string($userid, $link);
        
        $sql = "SELECT listid FROM tdo_list_memberships WHERE userid='$escapedUserid'";
        $result = mysql_query($sql);
        if($result)
        {
            $lists = array();
            while($row = mysql_fetch_array($result))
            {
                if(isset($row['listid']))
                {  
                    $listid = $row['listid'];
					
					// Only add lists that aren't part of a team if the team's
					// subscription & grace period have expired.
					if (in_array($listid, $teamListIDs) == false)
					{
						$list = TDOList::getListForListid($listid, $link);
						if($list)
						{
							if($includeDeleted || !$list->deleted())
							{
								$lists[] = $list;
							}
						}
					}
                }
            }
			
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			
			uasort($lists, 'TDOList::listCompare');
			
            return $lists;
        } 
        else
        {
            error_log("Unable to read user lists: ".mysql_error());
        }
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;    
	}
	
	
	public static function getSharedListsForTeam($teamID, $includeDeleted=false, $link=NULL)
	{
		if(!isset($teamID))
			return false;
		
		if(empty($link))
		{
			$closeDBLink = true;
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOList failed to get dblink");
				return false;
			}
		}
		else
			$closeDBLink = false;
		
		$escapedTeamID = mysql_real_escape_string($teamID, $link);
		
		$sql = "SELECT listid FROM tdo_lists WHERE creator='$escapedTeamID'";
		$result = mysql_query($sql);
		if($result)
		{
			$lists = array();
			while($row = mysql_fetch_array($result))
			{
				if(isset($row['listid']))
				{
					$listid = $row['listid'];
					$list = TDOList::getListForListid($listid, $link);
					if($list)
					{
						if($includeDeleted || !$list->deleted())
							$lists[] = $list;
					}
				}
			}
			if($closeDBLink)
				TDOUtil::closeDBLink($link);
			
			uasort($lists, 'TDOList::listCompare');
			
			return $lists;
		}
		else
		{
			error_log("Unable to read shared team lists: ".mysql_error());
		}
		if($closeDBLink)
			TDOUtil::closeDBLink($link);
		return false;
	}
	public static function getSharedListsCountForTeam($team_id, $link=NULL)
	{
        if(!isset($team_id))
            return false;

        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOList::getSharedListsCountForTeam failed to get dblink");
            return false;
        }

        $team_id = mysql_real_escape_string($team_id, $link);

        $sql = "SELECT COUNT(listid) FROM tdo_lists WHERE creator='$team_id'";
        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                TDOUtil::closeDBLink($link);
                return $total[0];
            }
        }

        TDOUtil::closeDBLink($link);
        return false;
	}

	
	public static function getListCount()
	{
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}
		
        $sql = "SELECT COUNT(listid) FROM tdo_lists";
        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                TDOUtil::closeDBLink($link);
                return $total[0];
            }
        }
        else
        {
            error_log("Unable to get list count: ".mysql_error());
        }
        
        TDOUtil::closeDBLink($link);
        return false;
	}    
    
	
	public static function getListCountForUser($userid)
	{
        if(!isset($userid))
            return false;
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}
        
        $listSql = TDOList::buildSQLListFilterForUser($userid);
        
        $escapedUserid = mysql_real_escape_string($userid, $link);
        
        $sql = "SELECT COUNT(listid) FROM tdo_list_memberships WHERE userid='$escapedUserid' AND " . $listSql;
        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                TDOUtil::closeDBLink($link);
                return $total[0];
            }
        }
        else
        {
            error_log("Unable to get list count for user ($userid): ".mysql_error());
        }
        
        TDOUtil::closeDBLink($link);
        return false;
	}
	
    //This is used in the wipeOutDataForUser method
    public static function getAllListsAndMembersForUser($userid)
    {
        if(empty($userid))
            return false;
            
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}
        
        $escapedUserid = mysql_real_escape_string($userid, $link);
        
        $sql = "SELECT listid FROM tdo_list_memberships WHERE userid='$escapedUserid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $lists = array();
            while($row = mysql_fetch_array($result))
            {
                if(isset($row['listid']))
                {  
                    $list = array();
                    $list['listid'] = $row['listid'];
                    
                    $sql = "SELECT userid, membership_type FROM tdo_list_memberships WHERE listid='".mysql_real_escape_string($row['listid'], $link)."'";
                    $listResponse = mysql_query($sql, $link);
                    if($listResponse)
                    {
                        $members = array();
                        while($listRow = mysql_fetch_array($listResponse))
                        {
                            if(isset($listRow['userid']) && isset($listRow['membership_type']))
                            {
                                $user = $listRow;
                                $members[] = $user;
                            }
                        }
                        $list['members'] = $members;
                    }
                    else
                    {
                        TDOUtil::closeDBLink($link);
                        return false;
                    }
                    $lists[] = $list;
                }
            }
            TDOUtil::closeDBLink($link);
            return $lists;
        } 
        else
            error_log("getAllListsAndMembersForUser failed with error: ".mysql_error());
        
        
        TDOUtil::closeDBLink($link);
        return false;           
    }
	
	// Returns the number of lists that the user is marked as an owner
	public static function getOwnedListCountForUser($userid)
	{
        if(!isset($userid))
            return false;
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}
        
        $listSql = TDOList::buildSQLListFilterForUser($userid);
        
        $escapedUserid = mysql_real_escape_string($userid, $link);
        
        $sql = "SELECT COUNT(listid) FROM tdo_list_memberships WHERE userid='$escapedUserid' AND membership_type=" . LIST_MEMBERSHIP_OWNER . " AND " . $listSql;
        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                TDOUtil::closeDBLink($link);
                return $total[0];
            }
        }
        else
        {
            error_log("Unable to get owned list count for user ($userid): ".mysql_error());
        }
        
        TDOUtil::closeDBLink($link);
        return false;
	}
	
	
	// Returns the total number of lists that the user has either shared with
	// someone else or that they are a member of (i.e., lists with more than
	// one member that the user belongs to).
	public static function getSharedListCountForUser($userid, $includeInvitations = false, $excludedList = NULL)
	{
		$userListIDs = TDOList::getListIDsForUser($userid);
		if (!$userListIDs)
			return false;
		
		$sharedListCount = 0;
		foreach ($userListIDs as $listID)
		{
            if($listID == $excludedList)
                continue;
            
			$listPeopleCount = TDOList::getPeopleCountForList($listID);
            if($listPeopleCount === false)
                return false;
            
			if ($listPeopleCount > 1)
				$sharedListCount++;
            else
            {
                //Also see if there are pending invitations to this list, because
                //then it will count towards the user's total shared list count
                $invitationCount = TDOInvitation::getInvitationCountForList($listID);
                if($invitationCount === false)
                    return false;
                
                if($invitationCount > 0)
                    $sharedListCount++;
            }
		}
		
		return $sharedListCount;
	}
	
	
	// Returns true/false if the list is a shared list.
	public static function isSharedList($listid)
	{
		if (empty($listid))
			return false;
		
		$listPeopleCount = TDOList::getPeopleCountForList($listid);
		if ($listPeopleCount === false)
			return false;
		
		if ($listPeopleCount > 1)
			return true;
		
		return false;
	}
	
	
	// If the specified list is owned by a team, return the teamID, otherwise
	// return false.
	public static function teamIDForList($listID, $link=NULL)
	{
		// If the specified list is owned by a team
		if(!isset($listID))
		{
			error_log("TDOList::isTeamOwnedList missing listID");
			return false;
		}
		
		$closeLink = false;
		if ($link == NULL)
		{
			$closeLink = true;
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				error_log("TDOList::teamIDForList() could not get DB connection.");
				return false;
			}
		}
		
		$listID = mysql_real_escape_string($listID, $link);
		
		$sql = "SELECT creator FROM tdo_lists JOIN tdo_team_accounts ON teamid=creator WHERE listid='$listID'";
		if ($result = mysql_query($sql, $link))
		{
			$row = mysql_fetch_array($result);
			if ($row && isset($row['creator']))
			{
				// A team-owned list has the creator matching the team ID.
				$teamID = $row['creator'];
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return $teamID;
			}
		}
		
		if ($closeLink)
			TDOUtil::closeDBLink($link);
		return false;
	}

	
	public static function getUserInboxId($userid, $createIfNeeded=true, $link=NULL)
	{
		// this method should actually look at a setting to read the default
		// list, verify that the list exists and return it.  For now we
		// just go look at all of their lists and create one if one does
		// not exist

        if(!isset($userid))
		{
			error_log("TDOList::getUserInboxId missing userid");
            return false;
		}
		
		$userSettings = TDOUserSettings::getUserSettingsForUserid($userid, $link);
		
		if($userSettings)
		{
			$defaultListId = $userSettings->userInbox();
		}
		else
		{
			error_log("TDOList::unable to fetch user");
            return false;
		}
		
		if($defaultListId)
		{
			$defaultList = TDOList::getListForListid($defaultListId, $link);

			if($defaultList)
			{
				return $defaultList->listId();
			}
		}
		
		if($createIfNeeded)
		{
        
			$list = new TDOList();
			$list->setName('Inbox');
			$list->setCreator($userid);
			
			if($list->addList($userid, NULL, $link))
			{
				$userSettings->setUserInbox($list->listId());
				$userSettings->updateUserSettings($link);
				return $list->listId();
			}
			else
			{
				error_log("TDOList::getUserInboxId failed to create a default list and found no lists for the user"); 
				return false;
			}
		}

        error_log("TDOList::getUserInboxId error getting lists for user".mysql_error());
        
        return false;    
	}


    public static function shareWithUser($listid, $userid, $role)
    {      
        if(!isset($listid) || !isset($userid) || !isset($role))
            return false;
        
        //if the user is already a member of the list, return false
        if(TDOList::userCanViewList($listid, $userid) == true)
            return false;
        
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}  
        $escapedListId = mysql_real_escape_string($listid, $link);
        $membershipType = intval($role);

        $escapedUserId = mysql_real_escape_string($userid, $link);
        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("TDOList unable to start transaction".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }

        $sql = "INSERT INTO tdo_list_memberships (listid, userid, membership_type) VALUES ('$escapedListId', '$escapedUserId', $membershipType)";
        $result = mysql_query($sql, $link);
        if(!$result)
        {
            error_log("Unable to share with user: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        $listSettings = new TDOListSettings();
        
        if($listSettings->addListSettings($listid, $userid, $link) == false)
        {
            error_log("Unable to add list settings when sharing list: ".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
//        if(!mysql_query("INSERT INTO tdo_list_settings (listid, userid) VALUES ('$escapedListId', '$escapedUserId')", $link))
//        {
//            error_log("Unable to share with user:".mysql_error());
//            mysql_query("ROLLBACK", $link);
//            TDOUtil::closeDBLink($link);
//            return false;
//        }
        if(!mysql_query("COMMIT", $link))
        {
            error_log("TDOList failed to commit transaction".mysql_error());
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        // normally we only do this in our web handlers but this can only be done on the web and it works
        $session = TDOSession::getInstance();
        $userName = TDOUser::fullNameForUserId($userid);
        TDOChangeLog::addChangeLog($listid, $session->getUserId(), $userid, $userName, ITEM_TYPE_USER, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
        
        TDOUtil::closeDBLink($link);
        return true;
    }

	
    
    public static function removeUserFromList($listid, $userid, $link=NULL)
    {
        if(!isset($listid) || !isset($userid))
            return false;
        
        if(empty($link))
        {
            $closeTransaction = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
            if(!mysql_query("START TRANSACTION", $link))
            {
                error_log("TDOList unable to start transaction ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        else
            $closeTransaction = false;
 

        $userid = mysql_real_escape_string($userid, $link);
        $listid = mysql_real_escape_string($listid, $link);


        if(!mysql_query("DELETE FROM tdo_list_memberships where listid='$listid' AND userid='$userid'", $link))
        {
            error_log("Unable to share with user: ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
        
        if(!mysql_query("DELETE FROM tdo_list_settings where listid='$listid' AND userid='$userid'", $link))
        {
            error_log("Unable to delete from list settings: ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
        
        if(!mysql_query("UPDATE tdo_tasks SET assigned_userid=NULL, timestamp=".time()." WHERE assigned_userid='$userid' AND listid='$listid'", $link))
        {
            error_log("Unable to remove tasks assigned to user: ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }

        if(!mysql_query("UPDATE tdo_completed_tasks SET assigned_userid=NULL, timestamp=".time()." WHERE assigned_userid='$userid' AND listid='$listid'", $link))
        {
            error_log("Unable to remove tasks assigned to user: ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }

        if(!mysql_query("UPDATE tdo_deleted_tasks SET assigned_userid=NULL WHERE assigned_userid='$userid' AND listid='$listid'", $link))
        {
            error_log("Unable to remove tasks assigned to user: ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }

        if($closeTransaction)
        {
            if(!mysql_query("COMMIT", $link))
            {
                error_log("TDOList unable to commit transaction ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                
                return false;
            }
        
            TDOUtil::closeDBLink($link);
        }

        $session = TDOSession::getInstance();
        TDOTeamSlackIntegration::processNotification(array(
            'listid' => $listid,
            'userid' => $session->getUserId(),
            'itemType' => ITEM_TYPE_USER,
            'changeType' => CHANGE_TYPE_DELETE,
            'itemid' => $userid,
        ));
        return true;        
    }
    
	
    
    public static function getAllLists($includeDeleted=false)
    {
		$link = TDOUtil::getDBLink();    
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}  
        
        $sql = "SELECT name,description,listid,creator,cdavUri,cdavTimeZone,deleted,timestamp,task_timestamp,taskito_timestamp,notification_timestamp FROM tdo_lists";
        if(!$includeDeleted)
            $sql .= " WHERE deleted != 1";
        $response = mysql_query($sql, $link);
        if($response)
        {
            $lists = array();
            while($row = mysql_fetch_array($response))
            {
                $list = TDOList::listFromRow($row);
                $lists[] = $list;
            }
            TDOUtil::closeDBLink($link);
            return $lists;
        }
        else
            error_log("Unable to get all lists: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;

    }
    
    
    
    public static function listFromRow($row)
    {
        if ( (empty($row)) || (count($row) == 0) )
        {
            error_log("TDOTask::listFromRow() was passed a NULL row");
            return NULL;
        }
        
        if (empty($row['listid']))
        {
            error_log("TDOTask::listFromRow() did not contain an listid");
            return NULL;
        }

        
        $list = new TDOList();
        if(isset($row['listid']))
            $list->setListId($row['listid']);
        if(isset($row['name']))
            $list->setName($row['name']);
        if(isset($row['description']))
            $list->setDescription($row['description']);
        if(isset($row['creator']))
            $list->setCreator($row['creator']);
        if(isset($row['cdavUri']))
            $list->setCaldavUri($row['cdavUri']);
        else
            $list->setCaldavUri($row['listid']);
        if(isset($row['cdavTimeZone']))
            $list->setCaldavTimeZone($row['cdavTimeZone']);
        if(isset($row['deleted']))
            $list->setDeleted($row['deleted']);
        if(isset($row['timestamp']))
            $list->setTimestamp($row['timestamp']);
        if(isset($row['task_timestamp']))
            $list->setTaskTimestamp($row['task_timestamp']);
        if(isset($row['taskito_timestamp']))
            $list->setTaskitoTimestamp($row['taskito_timestamp']);
        if(isset($row['notification_timestamp']))
            $list->setNotificationTimestamp($row['notification_timestamp']);
        
        if(isset($row['icon_name']))
            $list->setIconName($row['icon_name']);

        return $list;
    }
    

    
	public static function getListForListid($listid, $link=NULL)
    {
        if(!isset($listid))
            return false;
            
        if(!$link)
        {
            $link = TDOUtil::getDBLink();        
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }  
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }
        
        $listid = mysql_real_escape_string($listid);
        

        // $sql = "SELECT name,description,listid,creator,cdavUri,cdavTimeZone,deleted,timestamp,task_timestamp,taskito_timestamp,notification_timestamp FROM tdo_lists WHERE listid='$listid'";
        $sql = "SELECT s.icon_name, l.name, l.description, l.listid, l.creator, l.cdavUri, l.cdavTimeZone, l.deleted, l.timestamp, l.task_timestamp, l.taskito_timestamp, l.notification_timestamp FROM tdo_lists l "
            . "LEFT JOIN tdo_list_settings s on s.listid = l.listid "
            . "WHERE l.listid = '$listid' "
            . "ORDER BY s.sort_order ";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
            $row =  mysql_fetch_array($response);
            if($row)
            {
                $list = TDOList::listFromRow($row);
                return $list;
            }

        }
        else
            error_log("Unable to get list: ".mysql_error());
        
        if($shouldCloseLink)
            TDOUtil::closeDBLink($link);
        return false;        
    }
    
    
//	public static function getUserListsChangedAfterDate($userid, $modDate, $deletedTasks = false)
//    {
//        if(!isset($userid))
//        {
//            error_log("TDOList::getUserListsChangedAfterDate had invalid userId");
//            return false;
//        }
//        
//		$link = TDOUtil::getDBLink();
//		if(!$link)
//		{
//			error_log("TDOList failed to get dblink");
//			return false;
//		}  
//        
//        $escapedUserid = mysql_real_escape_string($userid, $link);
//
//        $sql = "SELECT DISTINCT name,description,color,tdo_lists.listid as listid,creator,cdavUri,cdavTimeZone,deleted,timestamp FROM tdo_lists JOIN tdo_list_memberships ON (tdo_lists.listid = tdo_list_memberships.listid AND userid='$escapedUserid')";
//        
//        
//        $sql .= " WHERE";
//        
//        if($deletedTasks == false)
//            $sql .= " deleted = 0";
//        else
//            $sql .= " deleted = 1";
//        
//        if(!empty($modDate))
//        {
//            $sql .= " AND timestamp >= $modDate";
//        }
//        
//        $result = mysql_query($sql);
//        if($result)
//        {
//            $lists = array();
//            while($row = mysql_fetch_array($result))
//            {
//                $list = TDOList::listFromRow($row);
//                $lists[] = $list;
//            }
//
//            TDOUtil::closeDBLink($link);
//
//            return $lists;
//        } 
//
//        error_log("Unable to select updates lists: ".mysql_error());
//        TDOUtil::closeDBLink($link);
//        return false;    
//    }
    
    
	public static function listHashForUser($userid, $link=NULL)
    {
        if(!isset($userid))
        {
            error_log("TDOList::listTimeStampForUser had invalid userId");
            return false;
        }
		
		// If the user belongs to a team that has expired and is past the grace
		// period, always return a brand new list hash so that they will always
		// get updates (so the lists disappear that are expired and team-owned
		// or come back if the team subscription is paid again).
		$team = TDOTeamAccount::getTeamForTeamMember($userid, $link);
		if (!empty($team))
		{
            $teamID = $team->getTeamID();
			if (TDOTeamAccount::getTeamSubscriptionStatus($teamID, $link) == TEAM_SUBSCRIPTION_STATE_EXPIRED)
			{
				$currentDateString = date('c'); // ISO 8609 String
				$md5Value = md5($currentDateString);
				
				error_log('Date String: ' . $currentDateString);
				error_log('List Hash: ' . $md5Value);
				
				return $md5Value;
			}
		}
		
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList listTimeStampForUserfailed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $escapedUserid = mysql_real_escape_string($userid, $link);
        
        $sql = "SELECT DISTINCT timestamp FROM tdo_lists JOIN tdo_list_memberships ON (tdo_lists.listid = tdo_list_memberships.listid AND userid='$escapedUserid') ORDER BY timestamp DESC";
        
        $timestamp = NULL;
        $result = mysql_query($sql);
        if($result)
        {
            $listString = "";
            $lists = array();
            while($row = mysql_fetch_array($result))
            {
                $listString .= strval($row['timestamp']);
            }
        } 

        // we should also go get the timestamp from the list settings because if the colors change we need to update too
        $sql = "SELECT DISTINCT timestamp FROM tdo_list_settings WHERE userid='$escapedUserid' ORDER BY timestamp DESC";
        
        $result = mysql_query($sql);
        if($result)
        {
            while($row = mysql_fetch_array($result))
            {
                $listString .= strval($row['timestamp']);
            }
        } 
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        
        if(!empty($listString))
        {
            $md5Value = md5($listString);
        
            return $md5Value;
        }
        
        
        error_log("TDOList::listTimeStampForUser Unable to select updates lists: ".mysql_error());
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;    
    }
	
	
	public static function getListMembershipHashesForUser($userID, $lists=NULL, $link=NULL)
	{
		$closeDBLink = false;
		if(empty($link))
		{
			$closeDBLink = true;
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOList::getListMembershipHashesForUser() could not get DB connection.");
				return false;
			}
		}
		
		$listHashes = array();
		
		// Make sure we have all the lists for each user
		if (empty($lists))
			$lists = TDOList::getListsForUser($userID, false, $link);
		foreach($lists as $list)
		{
			$listID = $list->listId();
			
			$userIDs = "";
			$listMembers = TDOList::getEditingMembersForlistid($listID, $link);
			if (!empty($listMembers))
			{
				foreach($listMembers as $memberUserID)
				{
					$userIDs = $userIDs . $memberUserID;
				}
				
				$md5Value = md5($userIDs);
				$listHashes[$listID] = $md5Value;
			}
		}
		
		if ($closeDBLink)
			TDOUtil::closeDBLink($link);
		
		return $listHashes;
	}
	
	
	public static function nameForListId($listid)
    {
        if(!isset($listid))
            return false;
		
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOList failed to get dblink");
            return false;
        }  
        $shouldCloseLink = true;
        
        $listid = mysql_real_escape_string($listid);
        
        $sql = "SELECT name FROM tdo_lists WHERE listid='$listid'";

        $response = mysql_query($sql, $link);
        if($response)
        {
            $row =  mysql_fetch_array($response);
            if($row)
            {
                if(isset($row['name']))
                {
                    $listName = TDOUtil::ensureUTF8($row['name']);
                    return $listName;
                }
            }
        }

        error_log("Unable to get list name: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;        
    }
    
    
    
    public static function deleteLists($listids)
    {
//		error_log("TDOList::deleteLists");
		
        if(!isset($listids))
            return false;
            
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
            error_log("TDOList unable to get link");
           return false;
        }
 
        foreach($listids as $listid)
        {
            TDOList::deleteList($listid);
        }
        TDOUtil::closeDBLink($link);
        return true;

    }
    
    public static function getOwnerCountForList($listid)
    {
        if(!isset($listid))
            return false;
            
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}  

        $listid = mysql_real_escape_string($listid, $link);

        $sql = "SELECT COUNT(*) from tdo_list_memberships WHERE listid='$listid' AND membership_type=".LIST_MEMBERSHIP_OWNER;
        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                TDOUtil::closeDBLink($link);
                return $total[0];
            }

        }
        
        TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function getPeopleCountForList($listid)
    {
        if(!isset($listid))
            return false;
            
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}  

        $listid = mysql_real_escape_string($listid, $link);

        $sql = "SELECT COUNT(*) from tdo_list_memberships WHERE listid='$listid'";
        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                TDOUtil::closeDBLink($link);
                return $total[0];
            }

        }
        
        TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function getNameForList($listid)
    {
        if(!isset($listid))
            return false;
		
		if($listid == "all")
			return _('All');
		else if($listid == "focus")
			return _('Focus');
		else if($listid == "starred")
			return _('Starred');
		else if($listid == "inbox")
			return _('Inbox');
            
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}  

        $listid = mysql_real_escape_string($listid, $link);

        $sql = "SELECT name from tdo_lists WHERE listid='$listid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $resultArray = mysql_fetch_array($result);
            if(isset($resultArray['name']))
            {
                TDOUtil::closeDBLink($link);
                return $resultArray['name'];
            }

        }
        
        TDOUtil::closeDBLink($link);
        return false;         
    }
	
	
    public static function getIsListDeleted($listid)
    {
        if(!isset($listid))
            return false;
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}  
		
        $listid = mysql_real_escape_string($listid, $link);
		
        $sql = "SELECT deleted from tdo_lists WHERE listid='$listid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $resultArray = mysql_fetch_array($result);
            if(isset($resultArray['deleted']))
            {
                TDOUtil::closeDBLink($link);
                return ($resultArray['deleted'] != 0);
            }
        }
        
        TDOUtil::closeDBLink($link);
        return true;         
    }	
	
    public static function setNameForList($listid, $name)
    {
        if(empty($listid) || empty($name))
            return false;
            
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOList failed to get dblink");
			return false;
		}  

        $listid = mysql_real_escape_string($listid, $link);
        
        $name = mb_strcut($name, 0, LIST_NAME_LENGTH, 'UTF-8');
        $name = mysql_real_escape_string($name, $link);

        $sql = "UPDATE tdo_lists SET name='$name' WHERE listid='$listid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            TDOUtil::closeDBLink($link);
            return true;
        }
        
        TDOUtil::closeDBLink($link);
        return false;   
    }

    public static function userCanEditList($listid, $userid, $link=NULL)
    {
		$userCanEdit = false;
		
        if(!isset($listid) || !isset($userid))
            return false;
		
        if(TDOList::listIdIsSpecialType($listid))
            return true;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;

        $userid = mysql_real_escape_string($userid, $link);
        $listid = mysql_real_escape_string($listid, $link);
        
        $sql = "SELECT membership_type FROM tdo_list_memberships WHERE listid='$listid' AND userid='$userid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $resultArray = mysql_fetch_array($result);
            if($resultArray)
            {
                if(isset($resultArray['membership_type']))
                {
					$type = $resultArray['membership_type'];
					
					switch($type)
					{
						case LIST_MEMBERSHIP_OWNER:
							$userCanEdit = true;
							break;
						case LIST_MEMBERSHIP_MEMBER:
							$userCanEdit = true;
							break;
						default:
							break;
					}
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return $userCanEdit;
                }
            }
        }
        else
        {
            error_log("TDOList unable to get role for user: ".mysql_error());
        }
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }	
	
    public static function userCanViewList($listid, $userid, $link=NULL)
    {
         if(!isset($listid) || !isset($userid))
            return false;
            
        if(TDOList::listIdIsSpecialType($listid))
            return true;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOList failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;

        $userid = mysql_real_escape_string($userid, $link);
        $listid = mysql_real_escape_string($listid, $link);
        
        $sql = "SELECT COUNT(membership_type) FROM tdo_list_memberships WHERE listid='$listid' AND userid='$userid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $row = mysql_fetch_array($result);
            if($row && isset($row['0']) && $row['0'] > 0)
            {
                $sql = "SELECT COUNT(listid) FROM tdo_lists WHERE deleted='0' AND listid='$listid'";
                $result = mysql_query($sql, $link);
                if ($result) {
                    $row = mysql_fetch_array($result);
                    if ($row && isset($row['0']) && $row['0'] > 0) {
                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
                        return true;
                    }
                }
            }
        }
        else
        {
            error_log("TDOList unable to get role for user: ".mysql_error());
        }
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;

    }

    public static function listIdIsSpecialType($listid)
    {
        if($listid == "all" || $listid == "focus" || $listid == "today" || $listid == "starred")
            return true;
        else
            return false;
    }
    
    public function getPropertiesArrayWithUserSettings($userid)
    {
        $pArray =  $this->getPropertiesArray();
        if(!empty($userid))
        {
            $listSettings = TDOListSettings::getListSettingsForUser($this->listId(), $userid);
            if($listSettings)
            {
                $color = $listSettings->color();
                if($color)
                    $pArray['color'] = $color;
				
				$iconName = $listSettings->iconName();
				if($iconName)
					$pArray['iconName'] = $iconName;
				
				$pArray['sortOrder'] = $listSettings->sortOrder();
				$pArray['sortType'] = $listSettings->sortType();
				$pArray['defaultDueDate'] = $listSettings->defaultDueDate();
            }
        }
        
        
        return $pArray;
    }
    
    public static function buildSQLListFilterForUser($userID)
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
    
    
}


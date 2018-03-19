<?php
	//      TDOChangeLog
	//      Used to handle all user data
	
	// include files
	include_once('AWS/sdk.class.php');
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/DBConstants.php');	
	
	
	//TABLE tdo_change_log
	// Columns changeid, listid, userid, itemid, item_name, item_type, change_type, targetid, target_type, mod_date, serializeid
	
	// Changelog defines
//	define('CHANGE_TYPE_ADD', 1);
//	define('CHANGE_TYPE_MODIFY', 2);
//	define('CHANGE_TYPE_DELETE', 3);	
//	define('CHANGE_TYPE_RESTORE', 4);
	
//	define('ITEM_TYPE_LIST', 1);
//	define('ITEM_TYPE_USER', 2);
//	define('ITEM_TYPE_EVENT', 3);	
//	define('ITEM_TYPE_COMMENT', 4);	
//	define('ITEM_TYPE_NOTE', 5);	
//	define('ITEM_TYPE_INVITATION', 6);
//	define('ITEM_TYPE_TASK', 7);

    define('CHANGE_LOG_MERGE_INTERVAL', 120); //merge all changes on an item by the same user that occur within a 2 minute interval
	define ('ITEM_NAME_LENGTH', 72);
    
	class TDOChangeLog extends TDODBObject
	{
    
        public function __construct()
        {
            parent::__construct();
            $this->setToDefault();      
        }
        
        public function setToDefault()
        {
            parent::set_to_default();
        }
    
		public static function addChangeLog($listid, $userid, $itemid, $itemName, $itemType, $changeType, $changeLocation, $targetid = NULL, $targetType = NULL, $changeData=NULL, $serializedData = NULL, $link=NULL)
		{
			if(empty($listid) || empty($userid) || empty($itemid) || empty($itemType) || empty($changeType))
			{
				error_log("TDOChangeLog::addChange failed with missing parameter");
				return false;
			}

			if(empty($itemName))
			{
				$itemName = TDOChangeLog::getDefaultNameForItemType($itemType);
			}
            
            //If this item has been modified by the user in the last 5 minutes, merge the two change log items instead of
            //creating a new one. If the item is being added, there should be no previous entries, so we don't need to check
            /*if($changeType != CHANGE_TYPE_ADD)
            {
                $previousChangeItem = TDOChangeLog::getRecentChangeForItemByUser($itemType, $itemid, $listid, $userid, CHANGE_LOG_MERGE_INTERVAL, $link);
                
                if(!empty($previousChangeItem))
                {
                    //If the item is being deleted, remove the recent change log item because we're not interested in those changes any more
                    if($changeType == CHANGE_TYPE_DELETE)
                    {
                        TDOChangeLog::deleteChangeLog($previousChangeItem->changeId(), $link);
                        
                        if($previousChangeItem->changeType() == CHANGE_TYPE_ADD)
                        {
                            //If the item was just barely added, don't bother notifying that it was deleted
                            return true;
                        }
                    }
                    else
                    {
                        //Merge the change data from the old entry and the new entry and just update the old entry
                        $mergedData = TDOChangeLog::mergeChangeData($previousChangeItem->changeData(), $changeData);
                        if(count($mergedData) == 0 && $previousChangeItem->changeType() != CHANGE_TYPE_ADD)
                        {
                            //The user just reverted all old changes, so remove the old change item
                            TDOChangeLog::deleteChangeLog($previousChangeItem->changeId(), $link);
                            return true;

                        }
                        else
                        {
                            //Update the previous change item with the new change data
                            $previousChangeItem->setChangeData(json_encode($mergedData));
                            $previousChangeItem->setItemName($itemName);
                            return $previousChangeItem->updateChangeLog($link);
                        }
                    }
                }
            }*/
            
			
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
            }
            else
                $closeDBLink = false;
			
			$modDate = time();
			$changeid = TDOUtil::uuid();

			$reallistid = mysql_real_escape_string($listid, $link);
			$realUserid = mysql_real_escape_string($userid, $link);
			$realItemid = mysql_real_escape_string($itemid, $link);
            
            $realItemName = mb_strcut($itemName, 0, ITEM_NAME_LENGTH, 'UTF-8');
			$realItemName = mysql_real_escape_string($realItemName, $link);
			$realItemType = mysql_real_escape_string($itemType, $link);
			$realChangeType = mysql_real_escape_string($changeType, $link);
			$realChangeLocation = mysql_real_escape_string($changeLocation, $link);
			
			$nameString = "changeid, listid, userid, itemid, item_name, item_type, change_type, mod_date, change_location";
			$valueString = "'$changeid', '$reallistid', '$realUserid', '$realItemid', '$realItemName', $realItemType, $realChangeType, $modDate, $realChangeLocation";
			
			if(!empty($targetid))
			{
				$realTargetid = mysql_real_escape_string($targetid, $link);
				$nameString = $nameString.", targetid";
				$valueString = $valueString.", '$realTargetid'";
			}
			if(!empty($targetType))
			{
				$realTargetType = mysql_real_escape_string($targetType, $link);
				$nameString = $nameString.", target_type";
				$valueString = $valueString.", $realTargetType";
			}
			if(!empty($changeData))
		    {
				$realChangeData = mysql_real_escape_string($changeData, $link);
			    $nameString = $nameString.", change_data";
			    $valueString = $valueString.", '$realChangeData'";
		    }
			if(!empty($serializedData))
			{
				// We need to put the serialized data in another table and store the id here
				// do this later
			}

			$sql = "INSERT INTO tdo_change_log (".$nameString.") VALUES (".$valueString.")";

			$result = mysql_query($sql, $link);
			if(!$result)
			{
				error_log("TDOChangeLog::addChange failed to add change to changelog error: ".mysql_error());
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
				return false;
			}
            
            if(!mysql_query("INSERT INTO tdo_email_notifications (changeid, timestamp) VALUES ('$changeid', $modDate)"))
            {
                error_log("Unable to insert into notifications".mysql_error());
            }
			if($closeDBLink)
                TDOUtil::closeDBLink($link);


            if($itemType === ITEM_TYPE_TASK || $itemType === ITEM_TYPE_TASKITO || $itemType === ITEM_TYPE_COMMENT || $itemType === ITEM_TYPE_USER){

                //$listid, $userid, $itemid, $itemName, $itemType, $changeType, $changeLocation, $targetid = NULL, $targetType = NULL, $changeData=NULL, $serializedData = NULL, $link=NULL
                $data = array(
                    'listid' => $listid,
                    'userid' => $userid,
                    'itemid' => $itemid,
                    'itemName' => $itemName,
                    'itemType' => $itemType,
                    'changeType' => $changeType,
                    'changeid' => $changeid,
                    'targetid' => $targetid,
                );
                TDOTeamSlackIntegration::processNotification($data);
            }
			return true;
		}
        
        public function updateChangeLog($link=NULL)
        {
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                {
                    error_log("TDOChangeLog unable to get db link");
                    return false;
                }
            }
            else
                $closeDBLink = false;
            
            //The only values that can be updated are the change data, item name, and timestamp
            $sql = "UPDATE tdo_change_log SET mod_date=".time();
            
            if($this->changeData() != NULL)
                $sql .= ", change_data='".mysql_real_escape_string($this->changeData(), $link)."'";
            else
                $sql .= ", change_data=NULL";
            
            if($this->itemName() != NULL)
            {
                $itemName = mb_strcut($this->itemName(), 0, ITEM_NAME_LENGTH, 'UTF-8');
                $sql .= ", item_name='".mysql_real_escape_string($itemName, $link)."'";
            }
            else
                $sql .= ", item_name=NULL";
            
            $sql .= " WHERE changeid='".mysql_real_escape_string($this->changeId(), $link)."'";
            
            if(mysql_query($sql, $link))
            {
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("updateChangeLog failed with error: ".mysql_error());
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
    
        public static function getRecentChangeForItemByUser($itemType, $itemId, $list, $user, $timeDifference, $link=NULL)
        {
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                {
                    error_log("TDOChangeLog unable to get db link");
                    return false;
                }
            }
            else
                $closeDBLink = false;
            
            $itemType = intval($itemType);
            $itemId = mysql_real_escape_string($itemId);
            $user = mysql_real_escape_string($user);
            $list = mysql_real_escape_string($list);
            
            $compTime = time() - intval($timeDifference);
            
            $sql = "SELECT * from tdo_change_log WHERE item_type=$itemType AND itemid='$itemId' AND listid='$list' AND userid='$user' AND mod_date > $compTime";
            
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    $change = TDOChangeLog::changeLogFromRow($row);
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return $change;
                }
            }
            else
                error_log("getRecentChangeForItemByUser failed with error: ".mysql_error());
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
        
        public static function mergeChangeData($oldChangeData, $newChangeData)
        {
            if(empty($oldChangeData))
                $oldChangeData = array();
            else
                $oldChangeData = json_decode($oldChangeData, true);
            
            
            if(empty($newChangeData))
                return $oldChangeData;
            else
                $newChangeData = json_decode($newChangeData, true);
            
            foreach($newChangeData as $key=>$value)
            {
                if(isset($oldChangeData[$key]))
                {
                    //If this is the entry for the old value, keep whatever is in the old array, but replace
                    //the new value with what's in the new array
                    if(strlen($key) < 3 || substr($key, 0, 3) != "old")
                    {
                        $oldKey = "old-".$key;
                        if(isset($oldChangeData[$oldKey]) && $oldChangeData[$oldKey] == $value)
                        {
                            //If the old value is the same as the new value now, remove them from the array because there's no change
                            unset($oldChangeData[$oldKey]);
                            unset($oldChangeData[$key]);
                        }
                        else
                        {
                            $oldChangeData[$key] = $value;
                        }
                    }
                }
                else
                {
                    $oldChangeData[$key] = $value;
                }
            }
            
            return $oldChangeData;
        }
    
        public static function deleteChangeLog($changeid, $link=NULL)
        {
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                {
                    error_log("TDOChangeLog unable to get db link");
                    return false;
                }
            }
            else
                $closeDBLink = false;
            
            $changeid = mysql_real_escape_string($changeid, $link);
            
            $sql = "DELETE FROM tdo_change_log WHERE changeid='$changeid'";
            
            $result = mysql_query($sql, $link);
            if($result)
            {
                $sql = "DELETE FROM tdo_email_notifications WHERE changeid='$changeid'";
                if(!mysql_query($sql, $link))
                {
                    error_log("DeleteChangeLog failed to delete associated email notification entry: ".mysql_error());
                }
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("getRecentChangeForItemByUser failed with error: ".mysql_error());
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
    
        public static function permanentlyDeleteAllChangeLogsForList($listid, $link = NULL)
        {
            if(empty($listid))
                return false;
            
            if(empty($link))
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                {
                    error_log("TDOChangeLog failed to get db link");
                    return false;
                }
            }
            else
                $closeLink = false;
            
            $escapedListID = mysql_real_escape_string($listid, $link);
            $sql = "DELETE FROM tdo_change_log WHERE listid='$escapedListID'";
            if(mysql_query($sql, $link))
            {
                if($closeLink)
                    TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("permanentlyDeleteAllChangeLogsForList failed with error: ".mysql_error());
            
            if($closeLink)
                TDOUtil::closeDBLink($link);
            
            return false;
        }
    
		public static function getChangesForList($listid, $userId, $limit=NULL, $lastChangeTimestamp=0, $lastChangeId=NULL, $itemTypes=NULL,  $includeUser=false, $includeDeleted=false)
		{
			$changes = array();
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOChangeLog failed to get dblink");
				return;
			}

            $sql = "SELECT * " . TDOChangeLog::getChangeQueryForList($listid, $userId, $lastChangeTimestamp, $lastChangeId, $itemTypes,  $includeUser, $includeDeleted, $link);
            
            $sql .= " ORDER BY mod_date DESC, changeid";

			if(!empty($limit))
			{
                $sql = $sql." LIMIT ".intval($limit);
			}
			
			$result = mysql_query($sql, $link);
			
			if(!$result)
			{
				error_log("TDOChangeLog query failed with error :".mysql_error());
				TDOUtil::closeDBLink($link);
				return;
			}
			
			while($row = mysql_fetch_array($result))
			{
                $change = TDOChangeLog::changeLogFromRow($row);
				$changes[] = $change;
			}
			
			TDOUtil::closeDBLink($link);
			return $changes;
		}		

        public static function getChangeCountForList($listid, $userId, $lastChangeTimestamp=0, $lastChangeId=NULL, $itemTypes=NULL,  $includeUser=false, $includeDeleted=false)
        {
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOChangeLog failed to get dblink");
                return;
            }
            
            $sql = " SELECT count(changeid) " . TDOChangeLog::getChangeQueryForList($listid, $userId, $lastChangeTimestamp, $lastChangeId, $itemTypes,  $includeUser, $includeDeleted, $link);
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
                error_log("getChangeCountForList failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
            
        }
        
        //This is used to keep our query consistent between getChangesForList and getChangeCountForList
        public static function getChangeQueryForList($listid, $userId, $lastChangeTimestamp, $lastChangeId, $itemTypes,  $includeUser, $includeDeleted, $link)
        {
            $sql = " FROM tdo_change_log ";
            
            $reallistid = mysql_real_escape_string($listid, $link);
			$realUserId = mysql_real_escape_string($userId, $link);
            
            if($listid == "all" || $listid == "focus" || $listid == "today" || $listid == "starred")
            {
                $sql .= " INNER JOIN tdo_list_memberships ON tdo_change_log.listid=tdo_list_memberships.listid WHERE tdo_list_memberships.userid='$realUserId' ";
            }
            else
            {
                $sql .= " WHERE listid='$reallistid' ";
            }
            
            if(!$includeDeleted)
                $sql .= " AND deleted != 1";
            
            if(!$includeUser)
			{
				$sql .= " AND userid!='$realUserId'";
			}

            $itemTypeString = "";
            foreach($itemTypes as $itemType)
            {
                if(strlen($itemTypeString) > 0)
                    $itemTypeString .= " OR ";
                
                $itemTypeString .= "item_type=".intval($itemType);
            }
            
            if(strlen($itemTypeString) > 0)
                $sql .= " AND ($itemTypeString) ";
			
            if(!empty($lastChangeTimestamp) && !empty($lastChangeId))
            {
                $sql .= " AND (mod_date < ".intval($lastChangeTimestamp)." OR (mod_date = ".intval($lastChangeTimestamp)." AND changeid > '".mysql_real_escape_string($lastChangeId, $link)."'))";
            }
                
            return $sql;
        }
        
		public static function getAllChangesForUser($userid, $limit=NULL, $lastChangeTimestamp=0, $lastChangeId=NULL, $includeDeleted=false)
		{
			$changes = array();
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOChangeLog failed to get dblink");
				return false;
			}

			$realUserid = mysql_real_escape_string($userid, $link);
			
			$sql = "SELECT * FROM tdo_change_log WHERE userid='$realUserid'";
            if(!$includeDeleted)
                $sql .= " AND deleted != 1";

            if(!empty($lastChangeTimestamp) && !empty($lastChangeId))
            {
                $sql .= " AND (mod_date < ".intval($lastChangeTimestamp)." OR (mod_date = ".intval($lastChangeTimestamp)." AND changeid > '".mysql_real_escape_string($lastChangeId, $link)."'))";
            }
            
            $sql .= " ORDER BY mod_date DESC, changeid";

			if(!empty($limit))
			{
                $sql = $sql." LIMIT ".intval($limit);
			}

			$result = mysql_query($sql, $link);
			
			if(!$result)
			{
				error_log("TDOChangeLog query failed with error :".mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			while($row = mysql_fetch_array($result))
			{
                $change = TDOChangeLog::changeLogFromRow($row);
				$changes[] = $change;
			}
			
			TDOUtil::closeDBLink($link);
			return $changes;
		}		
		
		public static function getAllChangesForItem($itemid, $includeDeleted=false)
		{
			$changes = array();
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOChangeLog failed to get dblink");
				return;
			}
			
			$realItemid = mysql_real_escape_string($itemid, $link);
			
			$sql = "SELECT * FROM tdo_change_log WHERE itemid='$realItemid'";
            if(!$includeDeleted)
                $sql .= " AND deleted != 1";
            $sql .= " ORDER BY mod_date DESC";
			$result = mysql_query($sql, $link);
			
			if(!$result)
			{
				error_log("TDOChangeLog query failed with error :".mysql_error());
				TDOUtil::closeDBLink($link);
				return;
			}
			
			while($row = mysql_fetch_array($result))
			{
                $change = TDOChangeLog::changeLogFromRow($row);
				$changes[] = $change;
			}
			
			TDOUtil::closeDBLink($link);
			return $changes;
		}		
		
        public static function getChangeForChangeId($changeid)
        {
            $link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOChangeLog failed to get dblink");
				return false;
			}
			
			$sql = "SELECT * FROM tdo_change_log WHERE changeid='$changeid'";
			$result = mysql_query($sql, $link);
			
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    $change = TDOChangeLog::changeLogFromRow($row);
                    TDOUtil::closeDBLink($link);
                    return $change;
                }
            }
            else
                error_log("TDOChangeLog query failed with error :".mysql_error());
            				
            TDOUtil::closeDBLink($link);
            return false;

        }
		
		
		public static function getChangeDataForChange($changeid)
		{
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOChangeLog failed to get dblink");
				return;
			}
			
			$sql = "SELECT change_data FROM tdo_change_log WHERE changeid='$changeid'";
			$result = mysql_query($sql, $link);
			
			if(!$result)
			{
				error_log("TDOChangeLog query failed with error :".mysql_error());
				TDOUtil::closeDBLink($link);
				return;
			}
			
			if($row = mysql_fetch_array($result))
			{
				$changeData = $row['change_data'];
			}
			else
			{
				error_log("TDOChangeLog db query failed to find change data for changeid: ".$changeid);
			}
			
			TDOUtil::closeDBLink($link);

			return $changeData;
		}
        
      
		public static function getDefaultNameForItemType($type)
		{
			$name = 'Unnamed Item';
			switch($type)
			{
				case ITEM_TYPE_LIST:
					$name = 'New List';
					break;
				case ITEM_TYPE_USER:
					$name = 'New User';
					break;
				case ITEM_TYPE_EVENT:
					$name = 'New Event';
					break;					
				case ITEM_TYPE_COMMENT:
					$name = 'New Comment';
					break;					
				case ITEM_TYPE_INVITATION:
					$name = 'New Invitation';
					break;
				case ITEM_TYPE_TASK:
					$name = "New Task";
					break;
			}		
			return $name;
		}		
		
		public static function getDisplayItemType($type)
		{
			$itemType = 'Item';
			switch($type)
			{
				case ITEM_TYPE_LIST:
					$itemType = 'the list';
					break;
				case ITEM_TYPE_USER:
					$itemType = 'the user';
					break;
				case ITEM_TYPE_EVENT:
					$itemType = 'the event';
					break;					
				case ITEM_TYPE_COMMENT:
					$itemType = 'a comment on';
					break;					
				case ITEM_TYPE_INVITATION:
					$itemType = 'an invitation for';
					break;
                case ITEM_TYPE_TASKITO:
				case ITEM_TYPE_TASK:
					$itemType = 'the task';
					break;
			}		
			return $itemType;
		}
        
        
		public static function stringForItemType($type)
		{
			$itemType = 'Item';
			switch($type)
			{
				case ITEM_TYPE_LIST:
					$itemType = 'list';
					break;
				case ITEM_TYPE_USER:
					$itemType = 'user';
					break;
				case ITEM_TYPE_EVENT:
					$itemType = 'event';
					break;					
				case ITEM_TYPE_COMMENT:
					$itemType = 'comment';
					break;					
				case ITEM_TYPE_INVITATION:
					$itemType = 'invitation';
					break;
                case ITEM_TYPE_TASKITO:
				case ITEM_TYPE_TASK:
					$itemType = 'task';
					break;
			}		
			return $itemType;
		}
		
		
		public static function getIconURLForItemType($type)
		{
			$iconUrl = TP_IMG_PATH_CHANGELOG_TASK;	
			switch($type)
			{
				case ITEM_TYPE_LIST:
					$iconUrl = TP_IMG_PATH_CHANGELOG_LIST;
					break;
				case ITEM_TYPE_USER:
					$iconUrl = TP_IMG_PATH_CHANGELOG_PERSON;
					break;
				case ITEM_TYPE_EVENT:
					$iconUrl = TP_IMG_PATH_CHANGELOG_EVENT;	
					break;					
				case ITEM_TYPE_COMMENT:
					$iconUrl = TP_IMG_PATH_CHANGELOG_COMMENT;
					break;					
				case ITEM_TYPE_INVITATION:
					$iconUrl = TP_IMG_PATH_CHANGELOG_INVITE;
					break;
				case ITEM_TYPE_TASK:
					$iconUrl = TP_IMG_PATH_CHANGELOG_TASK;
					break;
			}		
			return $iconUrl;
		}		

		
		public static function getDisplayChangeLocation($location)
		{
			switch($location)
			{
				case CHANGE_LOCATION_CALDAV:
					return 'via caldav';
				case CHANGE_LOCATION_SYNC:
					return 'via sync';
				case CHANGE_LOCATION_WEB:
				default:
					return ' ';
			}
		}

		
		public static function getDisplayChangeType($type)
		{
			switch($type)
			{
				case CHANGE_TYPE_ADD:
					return 'created';
				case CHANGE_TYPE_DELETE:
					return 'deleted';
				case CHANGE_TYPE_RESTORE:
					return 'restored';
				default:
					return 'modified';
			}
		}
		
		public static function getDisplayUserName($userid)
		{
			$userName = TDOUser::fullNameForUserId($userid);
			if(empty($userName))
				$userName = TDOUser::usernameForUserId($userid);
			if(empty($userName))
				$userName = $userid;

			return $userName;
		}
		
		
		public function displayableChangeTitle()
		{
			$changeTitle = NULL;
			
			$itemType = TDOChangeLog::getDisplayItemType($this->itemType());
			$changeType = TDOChangeLog::getDisplayChangeType($this->changeType());
			
			switch($this->itemType())
			{
				case ITEM_TYPE_INVITATION:
				{
					switch($this->changeType())
					{
						case CHANGE_TYPE_ADD:
							$changeTitle = "Invited";
							break;
						case CHANGE_TYPE_DELETE:
							$changeTitle = "Invitation Removed";
							break;
					}
					break;
				}
				case ITEM_TYPE_USER:
				{
					switch($this->changeType())
					{
						case CHANGE_TYPE_ADD:
							$changeTitle = "Added";
							break;
						case CHANGE_TYPE_DELETE:
						{
							if($this->userId() == $this->itemId())
								$changeTitle = "Left";
							else
								$changeTitle = "Removed";
							break;
						}
						case CHANGE_TYPE_MODIFY:
						{
							$changeTitle = "Role Changed";
							break;
						}
					}
					break;
				}
				case ITEM_TYPE_COMMENT:
				{
					switch($this->changeType())
					{
						case CHANGE_TYPE_ADD:
							$changeTitle = "Comment Added";
							break;
						case CHANGE_TYPE_DELETE:
							$changeTitle = "Comment Deleted";
							break;
					}
					break;
				}
				case ITEM_TYPE_TASKITO:
				case ITEM_TYPE_TASK:
				{
					switch($this->changeType())
					{
						case CHANGE_TYPE_ADD:
							$changeTitle = "Task Added";
							break;
						case CHANGE_TYPE_DELETE:
							$changeTitle = "Task Deleted";
							break;
						case CHANGE_TYPE_MODIFY:
						{
							if($this->changeData() == NULL)
								$changeTitle = "Task Changed";
							else
							{
								$changes = json_decode($this->changeData());
								
								if(isset($changes->{'completiondate'}))
								{
									if($changes->{'completiondate'} == "0")
										$changeTitle = "Task Un-completed";
									else
										$changeTitle = "Task Completed";
								}
								else
								{
									$changeTitle = "Task Changed";
								}
							}
							break;
						}
					}
					break;
				}
					
				default:
				{
					$changeTitle = $changeType;
					break;
				}
			}
			
			if($changeTitle == NULL)
			{
				$changeTitle = $changeType;
			}
			
			return $changeTitle;
		}
		
		
        public function displayableString($limitItemName = false)
        {
			$changeLogString = NULL;
			
			if ($this->userId() == EMAIL_TASK_CREATION_USERID)
			{
				$userName = "Todo Cloud";
			}
			else
			{
				$userName = htmlspecialchars(TDOChangeLog::getDisplayUserName($this->userId()));
			}
			
			$itemType = TDOChangeLog::getDisplayItemType($this->itemType());
			$targetItemType = TDOChangeLog::getDisplayItemType($this->targetType());

			$changeType = TDOChangeLog::getDisplayChangeType($this->changeType());
			$itemName = htmlspecialchars($this->itemName());
            if( (strlen($itemName) > 50) && ($limitItemName == true) )
            {
                $itemName = substr($itemName, 0, 47);
                $itemName .= "...";
            }
			$listName = htmlspecialchars(TDOList::getNameForList($this->listId()));
			$listIsDeleted = TDOList::getIsListDeleted($this->listId());
			
            $listNameLink = $listName;
			
			switch($this->itemType())
			{
				case ITEM_TYPE_INVITATION:
				{
					switch($this->changeType())
					{
						case CHANGE_TYPE_ADD:
							$changeLogString = "Invited: " . $userName." invited '".$itemName."' to join ".$listNameLink;
							break;
						case CHANGE_TYPE_DELETE:
							$changeLogString = "Invitation Removed: " . $userName." removed the invitation for '".$itemName."' to join ".$listNameLink;
							break;
					}
					break;
				}
				case ITEM_TYPE_USER:
				{
					switch($this->changeType())
					{
						case CHANGE_TYPE_ADD:
							$changeLogString = "Added: ".$userName." joined the list ".$listNameLink;
							break;
						case CHANGE_TYPE_DELETE:
						{
							if($this->userId() == $this->itemId())
								$changeLogString = "Left:" . $userName." left the list ".$listNameLink;
							else
								$changeLogString = "Removed: " . $userName." removed ".$itemName." from the list ".$listNameLink;
							break;
						}
						case CHANGE_TYPE_MODIFY:
						{
							if($this->userId() == $this->itemId())
								$changeLogString = "Role Changed: " . $userName." changed roles in the list ".$listNameLink;
							else
								$changeLogString = "Role Changed: " . $userName." changed ".$itemName."'s role in the list ".$listNameLink;
							break;
						}
					}
					break;
				}
				case ITEM_TYPE_COMMENT:
				{
					switch($this->changeType())
					{
						case CHANGE_TYPE_ADD:
							if($this->targetId() == $this->listId())
								$changeLogString = "Comment Added: New comment on list \"".$listNameLink."\"";
							else
								$changeLogString = "Comment Added: New comment on \"".$itemName."\"";
							break;
						case CHANGE_TYPE_DELETE:
							if($this->targetId() == $this->listId())
								$changeLogString = "Comment Deleted: Comment deleted from list \"".$listNameLink."\"";
							else
								$changeLogString = "Comment Deleted: Comment deleted from \"".$itemName."\"";
							break;
					}
					break;
				}
                case ITEM_TYPE_TASKITO:
				case ITEM_TYPE_TASK:
				{
                    if($this->itemType() == ITEM_TYPE_TASK)
                        $taskName = htmlspecialchars(TDOTask::getNameForTask($this->itemId()));
                    else
                        $taskName = htmlspecialchars(TDOTaskito::getNameForTaskito($this->itemId()));

                    if( (strlen($taskName) > 50) && ($limitItemName == true) )
                    {
                        $taskName = substr($taskName, 0, 47);
                        $taskName .= "...";
                    }
					
					switch($this->changeType())
					{
						case CHANGE_TYPE_ADD:
							$changeLogString = "Task Added: \"" .$taskName."\" added to ".$listNameLink;
							break;
						case CHANGE_TYPE_DELETE:
							$changeLogString = "Task Deleted: \"" .$taskName."\" deleted from ".$listNameLink;
							break;
						case CHANGE_TYPE_MODIFY:
						{
							if($this->changeData() == NULL)
								$changeLogString = "Task Changed: \"" .$taskName."\" changed in ".$listNameLink;
							else
							{
								$changes = json_decode($this->changeData());
								
								if(isset($changes->{'completiondate'}))
								{
									if($changes->{'completiondate'} == "0")
										$changeLogString = "Task Un-completed: \"" .$taskName."\" un-completed in ".$listNameLink;
									else
										$changeLogString = "Task Completed: \"" .$taskName."\" completed in ".$listNameLink;
								}
                                else
                                {
                                    $changeLogString = "Task Changed: \"" .$taskName."\" changed in ".$listNameLink;
                                }
//								elseif(isset($changes->{'taskName'}))
//								{
//									$changeLogString = "\"" .$taskName."\" renamed in ".$listNameLink;
//								}
//								elseif(isset($changes->{'taskNote'}))
//								{
//									if($changes->{'old-taskNote'} == "")
//										$changeLogString = "\"" .$taskName."\" had a note added in ".$listNameLink;
//									elseif($changes->{'taskNote'} == "")
//                                        $changeLogString = "\"" .$taskName."\" had a note removed in ".$listNameLink;
//									else
//										$changeLogString = "\"" .$taskName."\" has an updated note in  ".$listNameLink;
//								}
//								elseif(isset($changes->{'taskDueDate'}))
//								{
//									$changeLogString = "\"" .$taskName."\" is now due ".TDOUtil::shortDueDateStringFromTimestamp($changes->{'taskDueDate'})." in ".$listNameLink;
//								}
//								elseif(isset($changes->{'assignedUser'}))
//								{
//									if($changes->{'assignedUser'} == "")
//									{
//										$oldUserName = TDOChangeLog::getDisplayUserName($changes->{'old-assignedUser'});
//										$changeLogString = "\"" .$taskName."\" was un-assigned in ".$listNameLink;
//									}
//									else
//									{
//										$assignedName = TDOChangeLog::getDisplayUserName($changes->{'assignedUser'});
//										if($changes->{'old-assignedUser'} == "")
//											$oldUserName = "unassigned";
//										else
//											$oldUserName = TDOChangeLog::getDisplayUserName($changes->{'old-assignedUser'});
//										$changeLogString = "\"" .$taskName."\" is now assigned to ".$assignedName." in ".$listNameLink;
//									}
//								}
//								else
//									$changeLogString = "\"" .$taskName."\" was updated in ".$listNameLink;
							}
							break;
						}
					}
					break;
				}
					
				default:
				{

                    $changeLogString = strtoupper($changeType) . ": \"" .$itemName."\" was ".$changeType." in '".$listName."'";
					break;
					//			case ITEM_TYPE_EVENT:
					//				$itemType = 'the event';
					//				break;					
				}
			}	
			
			if($changeLogString == NULL)
			{
                $changeLogString = strtoupper($changeType) . ": \"" .$itemName."\" was ".$changeType." in '".$listName."'";
			}

			return $changeLogString;
        }

        public function getPropertiesArray()
        {
            $pArray = $this->_publicPropertyArray;
            $pArray['recordString'] = $this->displayableString();
            $pArray['iconUrl'] = TDOChangeLog::getIconURLForItemType($this->itemType());
            $pArray['dateStamp'] = TDOUtil::humanReadableStringFromTimestamp($this->timestamp());
            
            
            return $pArray;

        }
		
        public static function changeLogFromRow($row)
        {
            $changeLog = new TDOChangeLog();
            if(isset($row['changeid']))
                $changeLog->setChangeId($row['changeid']);
            if(isset($row['listid']))
                $changeLog->setListId($row['listid']);
            if(isset($row['userid']))
                $changeLog->setUserId($row['userid']);
            if(isset($row['itemid']))
                $changeLog->setItemId($row['itemid']);
            if(isset($row['item_name']))
                $changeLog->setItemName($row['item_name']);
            if(isset($row['item_type']))
                $changeLog->setItemType($row['item_type']);
            if(isset($row['change_type']))
                $changeLog->setChangeType($row['change_type']);
            if(isset($row['targetid']))
                $changeLog->setTargetId($row['targetid']);
            if(isset($row['target_type']))
                $changeLog->setTargetType($row['target_type']);
            if(isset($row['mod_date']))
                $changeLog->setTimestamp($row['mod_date']);
            if(isset($row['serializeid']))
                $changeLog->setSerializeId($row['serializeid']);
            if(isset($row['deleted']))
                $changeLog->setDeleted($row['deleted']);
            if(isset($row['change_location']))
                $changeLog->setChangeLocation($row['change_location']);
            if(isset($row['change_data']))
                $changeLog->setChangeData($row['change_data']);
            
            return $changeLog;
                
        }
        
        public function changeId()
        {
            if(empty($this->_publicPropertyArray['changeId']))
                return NULL;
            else
                return $this->_publicPropertyArray['changeId'];
        }
        public function setChangeId($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['changeId']);
            else
                $this->_publicPropertyArray['changeId'] = $val;
        }
        
        public function listId()
        {
            if(empty($this->_publicPropertyArray['listid']))
                return NULL;
            else
                return $this->_publicPropertyArray['listid'];
        }
        public function setListId($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['listid']);
            else
                $this->_publicPropertyArray['listid'] = $val;
        }
        
        public function userId()
        {
            if(empty($this->_publicPropertyArray['userid']))
                return NULL;
            else
                return $this->_publicPropertyArray['userid'];
        }
        public function setUserId($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['userid']);
            else
                $this->_publicPropertyArray['userid'] = $val;
        }
        
        public function itemId()
        {
            if(empty($this->_publicPropertyArray['itemid']))
                return NULL;
            else
                return $this->_publicPropertyArray['itemid'];
        }
        public function setItemId($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['itemid']);
            else
                $this->_publicPropertyArray['itemid'] = $val;
        }
        
        public function itemType()
        {
            if(empty($this->_publicPropertyArray['itemtype']))
                return 0;
            else
                return $this->_publicPropertyArray['itemtype'];
        }
        public function setItemType($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['itemtype']);
            else
                $this->_publicPropertyArray['itemtype'] = $val;
        }
        
        public function itemName()
        {
            if(empty($this->_publicPropertyArray['itemName']))
                return NULL;
            else
                return $this->_publicPropertyArray['itemName'];
        }
        public function setItemName($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['itemName']);
            else
                $this->_publicPropertyArray['itemName'] = $val;
        }
        
        public function changeType()
        {
            if(empty($this->_publicPropertyArray['changetype']))
                return 0;
            else
                return $this->_publicPropertyArray['changetype'];
        }
        public function setChangeType($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['changetype']);
            else
                $this->_publicPropertyArray['changetype'] = $val;
        }
        
        public function targetId()
        {
            if(empty($this->_publicPropertyArray['targetid']))
                return NULL;
            else
                return $this->_publicPropertyArray['targetid'];
        }
        public function setTargetId($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['targetid']);
            else
                $this->_publicPropertyArray['targetid'] = $val;
        }
        
        public function targetType()
        {
            if(empty($this->_publicPropertyArray['targettype']))
                return 0;
            else
                return $this->_publicPropertyArray['targettype'];
        }
        public function setTargetType($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['targettype']);
            else
                $this->_publicPropertyArray['targettype'] = $val;
        }
    
        public function serializeId()
        {
            if(empty($this->_publicPropertyArray['serializeid']))
                return NULL;
            else
                return $this->_publicPropertyArray['serializeid'];
        }
        public function setSerializeId($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['serializeid']);
            else
                $this->_publicPropertyArray['serializeid'] = $val;
        }
        
        public function changeLocation()
        {
            if(empty($this->_publicPropertyArray['changelocation']))
                return 0;
            else
                return $this->_publicPropertyArray['changelocation'];
        }
        public function setChangeLocation($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['changelocation']);
            else
                $this->_publicPropertyArray['changelocation'] = $val;
        }
        public function changeData()
        {
            if(empty($this->_publicPropertyArray['changedata']))
                return NULL;
            else
                return $this->_publicPropertyArray['changedata'];
        }
        public function setChangeData($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['changedata']);
            else
                $this->_publicPropertyArray['changedata'] = $val;
        }
    
    }
	

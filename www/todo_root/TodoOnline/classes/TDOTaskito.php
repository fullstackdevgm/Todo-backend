<?php
	// TDOTaskito
	//
	// Created by Calvino on 6/26/2012.
	// Copyright (C) 2012 Plunkboard, Inc. All rights reserved.
	
	// include files
	include_once('TodoOnline/base_sdk.php');
	include_once('Sabre/VObject/includes.php');
	
	class TDOTaskito extends TDODBObject
	{
		/*
        taskid
        parentid
        name (title)
        completiondate INT (Use NULL to indicate it is not completed)
        timestamp (last modified by the user)
        */
		
		public function __construct()
		{
            parent::__construct();
            
			$this->set_to_default();
		}
		
		public function set_to_default()
		{
            parent::set_to_default();

            $this->setCompletionDate(0);
            $this->setSortOrder(0);
		}
        

        // ------------------------
        // property Methods
        // ------------------------
        
        // override the base object to use the correct identifier
		public function identifier()
		{
            return $this->taskitoId();
		}
		public function setIdentifier($val)
		{
            $this->setTaskitoId($val);
        }
        
		public function taskitoId()
		{
            if(empty($this->_publicPropertyArray['taskitoid']))
                return NULL;
            else
                return $this->_publicPropertyArray['taskitoid'];
		}
		public function setTaskitoId($val)
		{
            if(empty($val))
                unset($this->_publicPropertyArray['taskitoid']);
            else
                $this->_publicPropertyArray['taskitoid'] = $val;
        }
        
        
        public function parentId()
        {
            if(empty($this->_publicPropertyArray['parentid']))
                return NULL;
            else
                return $this->_publicPropertyArray['parentid'];
        }
        public function setParentId($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['parentid']);
            else
                $this->_publicPropertyArray['parentid'] = $val;
        }
		
		public function completionDate()
		{
            if(empty($this->_publicPropertyArray['completiondate']))
                return 0;
            else
                return $this->_publicPropertyArray['completiondate'];
		}
		public function setCompletionDate($val)
		{
            if(empty($val))
                unset($this->_publicPropertyArray['completiondate']);
            else
                $this->_publicPropertyArray['completiondate'] = $val;
		}
        
        public function sortOrder()
        {
            if(empty($this->_publicPropertyArray['sort_order']))
                return 0;
            else
                return $this->_publicPropertyArray['sort_order'];
        }
        
        public function setSortOrder($val)
		{
            if(empty($val))
                unset($this->_publicPropertyArray['sort_order']);
            else
                $this->_publicPropertyArray['sort_order'] = $val;
		}
        
        public static function deleteTaskitoChildrenOfTask($taskid, $link = NULL)
		{
			if(!isset($taskid))
				return false;
            
			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTaskito::deleteChildrenOfTask() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;			
            
            $timestamp = time();
            
			$escapedTaskID = mysql_real_escape_string($taskid, $link);
			$sql = "UPDATE tdo_taskitos SET deleted=1, timestamp=" . $timestamp ." WHERE parentid = '$escapedTaskID'";
			if(mysql_query($sql, $link))
			{
                if(mysql_affected_rows($link) > 0)
                {
                    $listId = TDOTask::getListIdForTaskId($taskid, $link);
                    if(!empty($listId))
                    {
                        TDOList::updateTaskitoTimestampForList($listId, $timestamp, $link);
                    }
                }                
                
				if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return true;
			}
			else
			{
				error_log("TDOTaskito::deleteChildrenOfTask() could not delete $escapedTaskID: " . mysql_error());
			}
			
			if($closeLink == true)
				TDOUtil::closeDBLink($link);
            
			return false;
		}
        
        
        public static function archiveTaskitoChildrenOfTask($taskid, $link = NULL, $transactionInPlace = false)
		{
			if(!isset($taskid))
				return false;
            
			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTaskito::archiveTaskitoChildrenOfTask() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;	
            
            // there are other methods like delete list that may already have a transation in place
            // so there is no need to start a transaction here
            if($transactionInPlace == false)
            {
                // First start a transaction since we'll be deleting a lot of children tasks and notifications
                if(!mysql_query("START TRANSACTION", $link))
                {
                    error_log("TDOTask::archiveObject Couldn't start transaction: ".mysql_error());
                    TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            
            $timestamp = time();
            
            $taskitos = TDOTaskito::getTaskitosForTask($taskid, true, true, $link);
            if($taskitos === false)
            {
                error_log("TDOTask::archiveTaskitoChildrenOfTask could not get taskitos: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
            }
            
            foreach($taskitos as $taskito)
            {
                if($taskito->addObject($link, true) == false)
                {
                    error_log("TDOTask::archiveTaskitoChildrenOfTask failed archiving taskito: ".mysql_error());
                    if($transactionInPlace == false)
                        mysql_query("ROLLBACK", $link);
                    if($closeLink == true)
                        TDOUtil::closeDBLink($link);                
                    return false;
                }

            }
            
            $escapedTaskID = mysql_real_escape_string($taskid, $link);
            $sql = "DELETE FROM tdo_taskitos WHERE parentid='$escapedTaskID'";
			if(mysql_query($sql, $link))
			{
                if($transactionInPlace == false)
                {
                    if(!mysql_query("COMMIT", $link))
                    {
                        error_log("TDOTaskito::archiveTaskitoChildrenOfTask couldn't commit transaction deleting taskitos");
                        mysql_query("ROLLBACK", $link);
                        if($closeLink == true)
                            TDOUtil::closeDBLink($link);
                        return false;
                    }
                }

                if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return true;
			}
			else
			{
				error_log("TDOTaskito::archiveTaskitoChildrenOfTask() could not delete children tasksitos: " . mysql_error());
			}
			
            if($transactionInPlace == false)
                mysql_query("ROLLBACK", $link);
			if($closeLink == true)
				TDOUtil::closeDBLink($link);
            
			return false;
		}        
        
        
        
        public static function permanentlyDeleteTaskitoChildrenOfTask($taskid, $link = NULL)
        {
            if(empty($taskid))
                return false;
            
            if(empty($link))
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                {
                    error_log("TDOTaskito failed to get db link");
                    return false;
                }
            }
            else
                $closeLink = false;
            
            $escapedTaskID = mysql_real_escape_string($taskid, $link);
            $sql = "DELETE FROM tdo_taskitos WHERE parentid='$escapedTaskID'";
            if(mysql_query($sql, $link))
            {
                if($closeLink)
                    TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("permanentlyDeleteTaskitoChildrenOfTask failed with error: ".mysql_error());
            
            if($closeLink)
                TDOUtil::closeDBLink($link);
            
            return false;
        }
        
		public static function deleteObject($taskitoid, $link = NULL)
		{
			if(!isset($taskitoid))
				return false;
            
			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTaskito::deleteObject() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;	
            
            $timestamp = time();
            
			$escapedTaskID = mysql_real_escape_string($taskitoid, $link);
			$sql = "UPDATE tdo_taskitos SET deleted=1, timestamp=" . $timestamp ." WHERE taskitoid = '$escapedTaskID'";
			if(mysql_query($sql, $link))
			{
                if(mysql_affected_rows($link) > 0)
                {
                    $parentId = TDOTaskito::parentIdForTaskitoId($escapedTaskID);
                    if(!empty($parentId))
                    {        
                        $listId = TDOTask::getListIdForTaskId($parentId, $link);
                        if(!empty($listId))
                        {
                            TDOList::updateTaskitoTimestampForList($listId, $timestamp, $link);
                        }
                    }        
                }                
                
				if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return true;
			}
			else
			{
				error_log("TDOTaskito::deleteObject() could not delete $taskitoid: " . mysql_error());
			}
			
			if($closeLink == true)
				TDOUtil::closeDBLink($link);

			return false;
		}
		
		public function deleteMe()
		{
			return TDOTaskito::deleteObject($this->getId());
		}
		
		public function addObject($link = NULL, $addToArchive=false)
		{
			if($this->name() == NULL)
			{
				error_log("TDOTaskito::addObject failed because the name was not set");
				return false;
			}

			if($this->parentId() == NULL)
			{
				error_log("TDOTaskito::addObject failed because parent id was not set");
				return false;
			}

            // CRG - Added for legacy migration
            if($this->taskitoId() == NULL)
                $this->setTaskitoId(TDOUtil::uuid());
			
			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTaskito::addObject() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;
			
			$taskitoid = mysql_real_escape_string($this->taskitoId(), $link);
			
			if ($this->name() == NULL)
				$name = NULL;
			else
            {
                $name =  mb_strcut($this->name(), 0, TASK_NAME_LENGTH, 'UTF-8');
				$name = mysql_real_escape_string($name, $link);
            }
			
			$completionDate = $this->completionDate();

			if ($this->timestamp() == 0)
				$timestamp = time();
			else
				$timestamp = intval($this->timestamp());
			
			if($this->parentId() == NULL)
				$parentid = NULL;
			else
				$parentid = mysql_real_escape_string($this->parentId(), $link);
			
            $sortOrder = intval($this->sortOrder());
            
            $deleted = intval($this->deleted());
			
            
            $table = "tdo_taskitos";
            if($addToArchive)
                $table = "tdo_archived_taskitos";
            
			// Create the task
			$sql = "INSERT INTO $table (taskitoid, name, parentid, completiondate, timestamp, deleted, sort_order) VALUES ('$taskitoid', '$name', '$parentid', '$completionDate', '$timestamp', $deleted, $sortOrder)";
			$result = mysql_query($sql, $link);
			if(!$result)
			{
				error_log("TDOTaskito::addObject() failed with error :" . mysql_error());
				if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return false;
			}
            
            if(!empty($parentid))
            {        
                $listId = TDOTask::getListIdForTaskId($parentid, $link);
                if(!empty($listId))
                {
                    TDOList::updateTaskitoTimestampForList($listId, time(), $link);
                }
            }        
			
			if($closeLink == true)
				TDOUtil::closeDBLink($link);
			
			return true;
		}
		
		
		public function updateObject($link = NULL)
		{
			if($this->taskitoId() == NULL)
				return false;

			if($this->parentId() == NULL)
				return false;
			
			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTaskito::updateObject() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;
			$timestamp = time();
            
			$sql = "UPDATE tdo_taskitos SET timestamp='" . $timestamp . "' ";
			
			if ($this->name() == NULL)
				$sql = $sql . ",name=null";
			else
            {
                $name = mb_strcut($this->name(), 0, TASK_NAME_LENGTH, 'UTF-8');
				$sql = $sql . ",name='" . mysql_real_escape_string($name, $link) . "'";
            }
			
			$sql = $sql . ",completiondate='" . intval($this->completionDate()) . "'";
			
            $sql = $sql . ",deleted=" . intval($this->deleted());
            
			if($this->parentId() == NULL)
                $sql = $sql . ",parentid = null";
			else
                $sql = $sql . ",parentid = '" . mysql_real_escape_string($this->parentId(), $link) . "'";
			
            $sql = $sql . ",sort_order=" . intval($this->sortOrder());
            
			$sql = $sql . " WHERE taskitoid = '" . mysql_real_escape_string($this->taskitoId(), $link) . "'";
			
			
			$response = mysql_query($sql, $link);
			if($response)
			{
                $parentid = $this->parentId();
                if(!empty($parentid))
                {        
                    $listId = TDOTask::getListIdForTaskId($parentid, $link);
                    if(!empty($listId))
                    {
                        TDOList::updateTaskitoTimestampForList($listId, $timestamp, $link);
                    }
                }        
                
				if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return true;
			}
			else
			{
				error_log("TDOTaskito::updateObject() failed to update task: ". $this->taskitoId());
				if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return false;
			}
		}	
		
        public static function taskitoCountForTask($taskid, $includeCompleted = false)
        {
            if(empty($taskid))
            {
                error_log("TDOTaskito::taskitoCountForTask called with an empty taskid");
                return false;
            }
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTaskito::taskitoCountForTask could not get DB connection.");
                return false;
            }
            
            $taskitoCount = 0;
            $escapedTaskId = mysql_real_escape_string($taskid, $link);
            $sql = "SELECT COUNT(taskitoid) FROM tdo_taskitos WHERE ";
            
            if($includeCompleted == false)
            {
                $sql .= " completiondate = 0 AND ";
            }
            
            $sql .= "deleted=0 AND parentid='".$escapedTaskId."'";

            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                        $taskitoCount = $row['0'];
                }
            }
            else
            {
                error_log("TDOTaskito::taskitoCountForTask() could not get child count" . mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }

            TDOUtil::closeDBLink($link);
            return $taskitoCount;
        }
        
        
        public static function parentIdForTaskitoId($taskitoId, $link=NULL)
        {
            if(empty($taskitoId))
                return false;
            
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOTaskito::taskIdForTaskitoId() could not get DB connection.");
                    return false;
                }
            }
            else
                $closeDBLink = false;
            
            
            $realTaskitoId = mysql_real_escape_string($taskitoId, $link);
            
            $sql = "SELECT parentid FROM tdo_taskitos WHERE taskitoid='$realTaskitoId'";
            
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['parentid']))
                    {
                        $taskid = $row['parentid'];
                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
                        return $taskid;
                    }
                }
            }
            else
                error_log("parentIdForTaskitoId failed: ".mysql_error());
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }        
        
        
        
		public static function getAllTaskitoTimestampsForUser($userid, $lists=NULL, $link=NULL)
		{
            $timestamp = 0;
            
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOTaskito::getAllTaskitoTimestampsForUser() could not get DB connection.");
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
                
                if($list->taskitoTimestamp() > 0)
                {
                    // If a value of 1 is stored, then we've already done the calculation (see below) and
                    // stored a 1 in the timestamp.  Nothing as changed so don't return a timestamp in
                    // this case.
                    if($list->taskitoTimestamp() != 1)
                        $timeStamps[$listId] = $list->taskitoTimestamp();
                }
                // if we didn't have a timestamp stored on the list for tasks, go figure it out and then store it
                else    
                {
                    //error_log("Long Taskito timestamp query being called on list: " . $list->name());
                    
                    $listsql = " listid='" . $listId ."'";
                    
                    $maxTimestamp = 0;
                    $sql = "SELECT MAX(tdo_taskitos.timestamp) AS timestamp FROM tdo_taskitos JOIN tdo_tasks ON (tdo_taskitos.parentid = tdo_tasks.taskid) WHERE ".$listsql;
                    $result = mysql_query($sql);
                    if($result)
                    {
                        $row = mysql_fetch_array($result);
                        if(!empty($row['timestamp']))
                        {
                            $maxTimestamp = $row['timestamp'];
                        }
                    }

                    $sql = "SELECT MAX(tdo_taskitos.timestamp) AS timestamp FROM tdo_taskitos JOIN tdo_completed_tasks ON (tdo_taskitos.parentid = tdo_completed_tasks.taskid) WHERE ".$listsql;
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

                    $sql = "SELECT MAX(tdo_taskitos.timestamp) AS timestamp FROM tdo_taskitos JOIN tdo_deleted_tasks ON (tdo_taskitos.parentid = tdo_deleted_tasks.taskid) WHERE ".$listsql;;
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
                        TDOList::updateTaskitoTimestampForList($listId, $maxTimestamp, $link);
                        $timeStamps[$listId] = $maxTimestamp;
                    }
                    else
                    {
                        // if we go to calculate the timestamp and it's 0, store a 1 so we at least
                        // know we've calculated it once, otherwise we'll keep running this expensive
                        // query for no reason
                        TDOList::updateTaskitoTimestampForList($listId, 1, $link);
                    }                        
                }                
                
            }
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return $timeStamps;
		}
        
        
        public static function buildSQLFilterForUserForLists($userID)
        {
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
        
        
		public static function getTaskitosForUserModifiedSince($userid, $listid, $timestamp, $offset=0, $limit=0, $deletedTasks=false, $link=false)
		{
			if(!$link)
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOTaskito::getTaskitosForUserModifiedSince() could not get DB connection.");
                    return false;
                }
            }
            else
                $closeLink = false;

            $escapedListId = mysql_real_escape_string($listid, $link);
            $listsql = " listid = '". $escapedListId . "'"; //TDOTaskito::buildSQLFilterForUserForLists($userid);
            $wheresql = "";
            
            if(!empty($timestamp))
            {
                $wheresql = $wheresql." AND tdo_taskitos.timestamp > ".$timestamp;
            }
            
            if($deletedTasks == false)
                $wheresql = $wheresql." AND (tdo_taskitos.deleted = 0) ";
			else
                $wheresql = $wheresql." AND (tdo_taskitos.deleted != 0) ";
            
            
            $sql = "SELECT tdo_taskitos.*, tdo_tasks.listid FROM tdo_taskitos JOIN tdo_tasks ON (tdo_taskitos.parentid = tdo_tasks.taskid) WHERE ".$listsql.$wheresql;
            $sql .= " UNION SELECT tdo_taskitos.*, tdo_completed_tasks.listid FROM tdo_taskitos JOIN tdo_completed_tasks ON (tdo_taskitos.parentid = tdo_completed_tasks.taskid) WHERE ".$listsql.$wheresql;
            $sql .= " UNION SELECT tdo_taskitos.*, tdo_deleted_tasks.listid FROM tdo_taskitos JOIN tdo_deleted_tasks ON (tdo_taskitos.parentid = tdo_deleted_tasks.taskid) WHERE ".$listsql.$wheresql;
            
			//error_log("SQL query string is: " . $sql);
			
			if (isset($limit) && $limit != 0)
			{
				$sql = $sql . " LIMIT $limit OFFSET $offset";
			}
			
			$result = mysql_query($sql);
			if($result)
			{
				$tasks = array();
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskitoid']) == false) && (count($row) > 0) )
					{
						$task = TDOTaskito::taskitoFromRow($row);
						if (empty($task) == false)
						{
							$tasks[$row['taskitoid']] = $task;
						}
					}
				}
                if($closeLink)
                    TDOUtil::closeDBLink($link);
				return array_values($tasks);
			} 
            
            if($closeLink)
                TDOUtil::closeDBLink($link);
            
			error_log("TDOTaskito::getTaskitosForUserModifiedSince() could not get tasks for the user '$userid'" . mysql_error());
			
			return false;
		}
        
        
		
		
		public static function getTaskitosForTask($taskid, $includeIncomplete=true, $includeCompleted=true, $link = NULL, $sortAlphabetically=false, $archived=false)
		{
			if (empty($taskid))
			{
				error_log("TDOTaskito::getTaskitosForTask() called with a NULL taskid");
				return false;
			}
			if(!$link)
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOTaskito::getTaskitosForTask() could not get DB connection.");
                    return false;
                }
            }
            else
                $closeLink = false;
			
            $tableName = "tdo_taskitos";
            if($archived)
                $tableName = "tdo_archived_taskitos";
            
			$sql = "SELECT * FROM " . $tableName . " WHERE parentid='" . mysql_real_escape_string($taskid, $link)."'";

			if($includeCompleted == false)
				$sql = $sql." AND completiondate = 0";
            if($includeIncomplete == false)
                $sql = $sql." AND completiondate != 0";

            $sql = $sql." AND deleted=0";

			
//			$sql = $sql." ORDER BY duedate=0, duedate, priority=0, priority ASC, name ASC";

            if(!$includeIncomplete)
                $sql = $sql." ORDER BY completiondate DESC,sort_order, name ASC";
            else
            {
                if($sortAlphabetically)
                    $sql = $sql." ORDER BY name ASC, sort_order, completiondate DESC";
                else
                    $sql = $sql." ORDER BY sort_order, taskitoid";
            }
			
			//error_log("Query string for subtasks: " . $sql);
			
			$result = mysql_query($sql);
			if($result)
			{
				$taskitos = array();
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskitoid']) == false) && (count($row) > 0) )
					{
						$taskito = TDOTaskito::taskitoFromRow($row);

						if (empty($taskito) == false)
						{
							$taskitos[] = $taskito;
						}
					}
				}
                if($closeLink)
                {
                    TDOUtil::closeDBLink($link);
                }
                
				return $taskitos;
			} 
			else
			{
				error_log("TDOTaskito::getTaskitosForTask() could not get tasks for the specified task '$taskid'" . mysql_error());
			}
			
            if($closeLink)
            {
                TDOUtil::closeDBLink($link);
            }
			return false;
		}		
		
		
		public static function deleteObjects($taskitoids)
		{
            // CRG - Disabled this method, I don't think it's used but it
            // needs to check for projects and child subtasks when deleting

//			if (!isset($taskitoids))
//			{
//				error_log("TDOTaskito::deleteObjects() called with NULL array");
//				return false;
//			}
//			
//			if (count($taskitoids) == 0)
//				return true; // Nothing to do
//			
//			$link = TDOUtil::getDBLink();
//			if(!$link)
//			{
//				error_log("TDOTaskito::deleteObjects() could not get DB connection.");
//				return false;
//			}
//			
//			$sql = "UPDATE tdo_taskitos SET deleted=1 WHERE ";
//			
//			$firstItem = true;
//			foreach($taskids as $taskid)
//			{
//				if ($firstItem)
//					$firstItem = false;
//				else
//				{
//					$sql = $sql . " OR ";
//				}
//				
//				$sql = $sql . "taskitoid='" . mysql_real_escape_string($taskid, $link) . "'";
//			}
//			
//			if(mysql_query($sql, $link))
//			{
//				TDOUtil::closeDBLink($link);
//				return true;
//			}
//			else
//			{
//				error_log("TDOTaskito::deleteObjects() failed" . mysql_error());
//			}
//			
//			TDOUtil::closeDBLink($link);
			return false;
		}
		
		
		public static function taskitoForTaskitoId($taskitoid, $link=NULL)
		{
			if (!isset($taskitoid))
			{
				error_log("TDOTaskito::taskitoForTaskitoId() called with NULL taskitoid");
				return false;
			}
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
            }
            else
                $closeDBLink = false;
			
			$sql = "SELECT * FROM tdo_taskitos WHERE taskitoid = '" . mysql_real_escape_string($taskitoid, $link) . "'";
			$result = mysql_query($sql);
			if($result)
			{
				$row = mysql_fetch_array($result);
				if ( (empty($row) == false) && (count($row) > 0) )
				{
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
					return TDOTaskito::taskitoFromRow($row);
				}
				else
				{
					//error_log("TDOTaskito::taskitoForTaskitoId() empty row for task id: " . $taskitoid);
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
					return false;
				}
			}
			
			error_log("TDOTaskito::taskitoForTaskitoId() no result for task id: " . $taskitoid);
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			return false;
		}
		

		public static function getNameForTaskito($taskitoid)
		{
			if(!isset($taskitoid))
				return false;
            
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTaskito failed to get dblink");
				return false;
			}  
			
			$taskitoid = mysql_real_escape_string($taskitoid, $link);
			
			$sql = "SELECT name from tdo_taskitos WHERE taskitoid='$taskitoid'";
			$result = mysql_query($sql);
			if($result)
			{
				$resultArray = mysql_fetch_array($result);
				if(isset($resultArray['name']))
				{
					TDOUtil::closeDBLink($link);
					return TDOUtil::ensureUTF8($resultArray['name']);
				}
				
			}
			
			TDOUtil::closeDBLink($link);
			return false;         
		}		
		
		public static function taskitoFromRow($row)
		{
			
			if ( (empty($row)) || (count($row) == 0) )
			{
				error_log("TDOTaskito::taskitoFromRow() was passed a NULL row");
				return NULL;
			}
			
			if (empty($row['taskitoid']))
			{
				error_log("TDOTaskito::taskitoFromRow() did not contain an taskitoid");
				return NULL;
			}
			
			$task = new TDOTaskito();
            
			$task->setTaskitoId($row['taskitoid']);
			
			if (isset($row['name']))
				$task->setName($row['name']);
			
			if (isset($row['completiondate']))
				$task->setCompletionDate($row['completiondate']);
			
			if (isset($row['timestamp']))
				$task->setTimestamp($row['timestamp']);
			
            if(isset($row['deleted']))  
                $task->setDeleted($row['deleted']);

            if(isset($row['parentid']))
                $task->setParentId($row['parentid']);
                
            if(isset($row['sort_order']))
                $task->setSortOrder($row['sort_order']);
			
			return $task;
		}
		

        public function uncompleteTaskito()
        {
            $link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTaskito failed to get dblink");
				return false;
			}   
        
            // Do all of this in a transaction so we won't end up with uncompleted subtask with completed parents
			if(!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTaskito::Couldn't start transaction".mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
        
            $this->setCompletionDate(0, true);
            if($this->updateObject($link) == false)
            {
                error_log("TDOTaskito::Could not update uncompleted taskito, rolling back ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }

            if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOTaskito::Couldn't commit transaction completing taskito ".mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}
            
            TDOUtil::closeDBLink($link);
            return true;
        }

        
        public function completeTaskito($completionDate = NULL)
        {
            if(empty($completionDate))
                $completionDate = time();
        
            $results = array();
        
            $link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTaskito failed to get dblink");
				return false;
			}   
        
            // Do all of this in a transaction so we won't end up with completed task with uncompleted subtasks
			if(!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTaskito::Couldn't start transaction".mysql_error());
				TDOUtil::closeDBLink($link);
				$results['success'] = false;
                return $results;
			}
            
            $this->setCompletionDate($completionDate, true);
            
            if($this->updateObject($link) == false)
            {
                error_log("TDOTaskito::Could not update completed task, rolling back ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
				$results['success'] = false;
                return $results;
            }
            
            if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOTaskito::Couldn't commit transaction completing task ".mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				$results['success'] = false;
                return $results;
			}
            
            TDOUtil::closeDBLink($link);
            $results['success'] = true;
            return $results;
        }
        
        public static function highestSortOrderForTaskitosOfTask($taskid)
        {
            if(empty($taskid))
                return 0;
                
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTaskito failed to get DB link");
                return 0;
            }
            
            $taskid = mysql_real_escape_string($taskid, $link);
            
            $sql = "SELECT MAX(sort_order) FROM tdo_taskitos WHERE deleted=0 AND parentid='".$taskid."'";
            
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                    {
                        $maxSort = $row['0'];
                        TDOUtil::closeDBLink($link);
                        return $maxSort;
                    }
                }
            }
            else
                error_log("highestSortOrderForTaskitosOfTask failed with error: ".mysql_error());
                
            TDOUtil::closeDBLink($link);
            return 0;
        }
        
        
        //Returns the uncompleted child count
        public function getTaskitosHash($taskid, $link)
        {
            if(!$link)
                return false;
            
            $hashString = "";
            

			// first read the subtasks outside of the sql transaction
            $taskitos = TDOTaskito::getTaskitosForTask($taskid, true, true, $link, true);
            foreach($taskitos as $taskito)
            {
                $taskitoName = $taskito->name();
                if(!empty($taskitoName))
                    $hashString .= hash('md5', $taskitoName);

                $completionDate = $taskito->completionDate();
                if(!empty($completionDate))
                    $hashString .= hash('md5', strval($completionDate));
            }
            
            if(strlen($hashString) > 0)
                return hash('md5', $hashString);
            else
                return $hashString;
        }
        

	}
	

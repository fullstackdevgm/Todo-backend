


<?php

//	
//	This script was written to identify lists (and owners) with duplicated tasks
//    
    
define('DEFAULT_DUPE_LIMIT', 2);
define('PURGE_LIMIT', 10);
define('DEFAULT_DAEMON_ID', 'TaskDuped-X');
    
define('TASK_COMPARE_PROPERTIES', 'listid,name,note,duedate,completiondate,priority,deleted,starred,parentid,task_type,assigned_userid,recurrence_type,advanced_recurrence_string,location_alert,project_priority,project_duedate,project_starred');

include_once('TodoOnline/base_sdk.php');


//define('SQL_TEMP_LOCATION','tdo-fixup.cwi9sxs6sdl7.us-east-1.rds.amazonaws.com');
//define('SQL_TEMP_USERNAME', 'tdoadmin');
//define('SQL_TEMP_PASSWORD', 'aws!appengine');

function operationForLimit($limit)
{
//    $operation = USER_MAINTENANCE_OPERATION_TYPE_DELETE_DUPLICATE_TASKS;
//    
//    if($limit >= DELETE_DUPE_LIMIT)
    $operation = USER_MAINTENANCE_OPERATION_TYPE_PURGE_DUPLICATE_TASKS;

    return $operation;
}
    
    
function getTempDBLink()
{
    return TDOUtil::getDBLink();
    
//	$link = mysql_connect(SQL_LOCATION, SQL_USERNAME, SQL_PASSWORD);
//	if (!$link)
//	{
//		echo "Failed to connect to DB with error : " . mysql_error() . "\n";
//		return NULL;
//	}
//
//	if (!mysql_select_db(DB_NAME, $link))
//	{
//		echo "Failed to select DB with error : " . mysql_error() . "\n";
//		mysql_close($link);
//		return NULL;
//	}
//
//	return $link;
}


function closeTempDBLink($link = NULL)
{
    return TDOUtil::closeDBLink($link);
//	if (empty($link))
//		return false;
//
//	mysql_close($link);
//	return true;
}


function getAllListIDs($link = NULL)
{
	if (empty($link))
		return NULL;

	$sql = "SELECT listid FROM tdo_lists ORDER BY listid";
	$result = mysql_query($sql, $link);
	if (!$result)
	{
		echo "Could not select any lists\n";
		return NULL;
	}

	$lists = array();
	while ($row = mysql_fetch_array($result))
	{
		$list = $row['listid'];
		$lists[] = $list;
	}

	return $lists;
}
	
function usernameForUserId($userid, $link = NULL)
{
        if(!isset($userid))
            return false;

	if (empty($link))
		return false;

	$userid = mysql_real_escape_string($userid, $link);
	$result = mysql_query("SELECT username FROM tdo_user_accounts where userid='$userid'");

	if($result)
	{
		$responseArray = mysql_fetch_array($result);
		if($responseArray)
		{
			if(isset($responseArray['username']))
			{
				return $responseArray['username'];
			}
		}
			
	}
	else
	{
		error_log("Unable to get all username for user $userid");
	}

        return false;
    

}

    
function identifyLists($limit = DEFAULT_DUPE_LIMIT)
{
    markUsers($limit, NULL, true);
}

    
function markUsers($limit = DEFAULT_DUPE_LIMIT, $link=NULL, $identifyOnly=false)
{
    if(!markUsersForTable("tdo_tasks", $limit, $link, $identifyOnly))
        return false;
    if(!markUsersForTable("tdo_completed_tasks", $limit, $link, $identifyOnly))
        return false;
    
    return true;
}
    
    
function markUsersForTable($tableName, $limit = DEFAULT_DUPE_LIMIT, $link=NULL, $identifyOnly=false)
{
    if(empty($link))
    {
        $closeDBLink = true;
        $link = getTempDBLink();
        if(!$link)
        {
            echo("repairList failed to get dblink\n");
            return false;
        }
    }
    else
        $closeDBLink = false;
    
    $allListIDs = getAllListIDs($link);
    
    $count = 0;
    $listCount = 0;
    $dupeListCount = 0;
    
    $totalCount = count($allListIDs);
    
    echo "Searching ".$tableName." for duplicates > $limit\n";
    
    foreach($allListIDs as $listID)
    {
        $listCount++;

        $sql = "SELECT COUNT(*) FROM ".$tableName." WHERE listid='$listID' GROUP BY ".TASK_COMPARE_PROPERTIES." HAVING COUNT(*) >= $limit LIMIT 1";
        $result = mysql_query($sql, $link);
        if ($result)
        {
            $row = mysql_fetch_row($result);
            if ($row)
            {
                $taskCount = $row[0];
                $dupeListCount++;
                
                echo "-- " . $tableName . " - " . $dupeListCount . " of " . $listCount . " from " . $totalCount . " : Count: " . $taskCount . " --\n";
                
                $userIds = TDOList::getEditingMembersForlistid($listID, $link);
               
                
                foreach ($userIds as $userId)
                {
                    if($identifyOnly == false)
                    {
                        $operation = operationForLimit($limit);
                        
                        if(!TDOUserMaintenance::addUserForMaintenance($userId, $operation))
                        {
                            echo "Failed to add user for maintenance: " . $userId . "\n";
                            if($closeDBLink)
                                closeTempDBLink($link);
                            return false;
                        }
                    }
                    echo "Marked User: " . $userId . " for list: " .$listID."\n";
                }                
            }
        }
    }
    echo "\n";
    
    if($closeDBLink)
        closeTempDBLink($link);

    echo "\nFinished table: " . $tableName . " and found " . $dupeListCount . " of " . $totalCount . " lists with duplicates of ". $limit . " or more in them\n";
    
    return true;
    
}    
    
    

function repairList($listid, $limit = DEFAULT_DUPE_LIMIT, $link=NULL)
{
    if(!repairListFromTable($listid, "tdo_tasks", $limit, $link))
        return false;
    if(!repairListFromTable($listid, "tdo_completed_tasks", $limit, $link))
        return false;
    
    return true;
}
    
    
    
    
function repairListFromTable($listid, $tableName, $limit = DEFAULT_DUPE_LIMIT, $link=NULL)
{
    
    if(empty($link))
    {
        $closeDBLink = true;
        $link = getTempDBLink();
        if(!$link)
        {
            echo("repairList failed to get dblink\n");
            return false;
        }
    }
    else
        $closeDBLink = false;
    
    
    
    echo "Checking list: $listid\n";

    $tasksToRead = array();
    
    // get normal tasks
    $sql = "SELECT taskid FROM ".$tableName." WHERE listid='$listid' GROUP BY ".TASK_COMPARE_PROPERTIES." HAVING COUNT(*) >= $limit";
    $result = mysql_query($sql, $link);
	if($result)
	{
        while($row = mysql_fetch_array($result))        
        {
            if(isset($row['taskid']))
            {
                $tasksToRead[] = $row['taskid'];
            }
        }
    }
    
    if(count($tasksToRead) == 0)
    {
        if($closeDBLink)
            closeTempDBLink($link);
        return true;
    }

    foreach($tasksToRead as $taskIdToRead)
    {    
        $taskGroupsToDelete = array();

        $tdoTask = TDOTask::getTaskForTaskId($taskIdToRead, $link);
        if(!$tdoTask)
        {
            echo "Unable to locate the task!\n";
        }
        else
        {
            // this is the task we want to keep
            $keeperTaskid = "";
            
            echo "Checking duplicates of task: ".$tdoTask->name()." id: ".$tdoTask->taskId()."\n";

            $sql = "SELECT taskid,name,timestamp FROM ".$tableName." WHERE ";

            $sql .= "listid = '" . mysql_real_escape_string($tdoTask->listId(), $link) . "'";
            
            $value = $tdoTask->name();
            if(!empty($value))
                $sql .= " AND name='" . mysql_real_escape_string($value, $link) . "'";
            else
                $sql .= " AND (name IS NULL OR name='')";
            
            $value = $tdoTask->note();
            if(!empty($value))
                $sql .= " AND note='" . mysql_real_escape_string($value, $link) . "'";
            else
                $sql .= " AND (note IS NULL OR note='')";
            
            $sql .= " AND duedate=" . intval($tdoTask->compDueDate());
            $sql .= " AND completiondate='" . intval($tdoTask->completionDate()) . "'";
            $sql .= " AND priority='" . intval($tdoTask->compPriority()) . "'";
            $sql .= " AND deleted=" . intval($tdoTask->deleted());
            $sql .= " AND starred=" . intval($tdoTask->compStarredVal());
            
            $value = $tdoTask->parentId();
            if(!empty($value))
                $sql .= " AND parentid = '" . mysql_real_escape_string($value, $link) . "'";
            else
                $sql .= " AND (parentid IS NULL OR parentid='')";
            
            $sql .= " AND task_type=" . intval($tdoTask->taskType());
            
            $value = $tdoTask->assignedUserId();
            if(!empty($value))
                $sql .= " AND assigned_userid='" . mysql_real_escape_string($value, $link) . "'";
            else
                $sql .= " AND (assigned_userid IS NULL OR assigned_userid='')";
            
            $sql .= " AND recurrence_type=" . intval($tdoTask->recurrenceType());
            
            $value = $tdoTask->advancedRecurrenceString();
            if(!empty($value))
                $sql .= " AND advanced_recurrence_string='" . mysql_real_escape_string($value, $link) . "'";
            else
                $sql .= " AND (advanced_recurrence_string IS NULL OR advanced_recurrence_string='')";
            
            $value = $tdoTask->locationAlert();
            if(!empty($value))
                $sql .= " AND location_alert='".mysql_real_escape_string($value, $link). "'";
            else
                $sql .= " AND (location_alert IS NULL OR location_alert='')";
            
            if($tdoTask->isProject())
            {
                $sql .= " AND project_priority=".intval($tdoTask->projectPriority());
                $sql .= " AND project_duedate=".intval($tdoTask->projectDueDate());
                $sql .= " AND project_starred=".intval($tdoTask->projectStarred());
            }
            
            $sql .= " order by timestamp desc";
            
            $result = mysql_query($sql, $link);
            if($result)
            {
                $tasksToDelete = array();
                
                while($row = mysql_fetch_array($result))
                {
                    // take the first task returned since we're sorting my timestamp and we want the most recent one
                    if(empty($keeperTaskid))
                    {
                        if(isset($row['taskid']))
                        {
                            $keeperTaskid = $row['taskid'];                        
                        }
                    }
                    else
                    {
                        if(isset($row['taskid']))
                        {
                            $tasksToDelete[] = $row['taskid'];                        
                        }
                    }
                }
            }
            
            if( !empty($keeperTaskid) && (count($tasksToDelete) > 0) )
            {
                $keeperTask = TDOTask::getTaskForTaskId($keeperTaskid, $link);
                
                $taskGroup = array();
                
                $taskGroup['tasksToDelete'] = $tasksToDelete;
                
                // get the hash for this tasks subtasks
                if($keeperTask->isProject())
                {
                    $subtaskHash = TDOTask::getSubtaskHash($keeperTaskid, $link);
                    if(!empty($subtaskHash))
                        $taskGroup['subtaskHash'] = $subtaskHash;
                }
                
                if($keeperTask->isChecklist())
                {
                    $taskitosHash = TDOTaskito::getTaskitosHash($keeperTaskid, $link);
                    if(!empty($taskitosHash))
                        $taskGroup['taskitosHash'] = $taskitosHash;
                }
                
                $tagHash = TDOTask::getTagHash($keeperTaskid, $link);
                if(!empty($tagHash))
                {
                    $taskGroup['tagHash'] = $tagHash;
                }

                $taskGroupsToDelete[] = $taskGroup;
            }
        }

        if(count($taskGroupsToDelete) > 0)
        {
            foreach($taskGroupsToDelete as $taskGroup)
            {
                $tasksToDelete = $taskGroup['tasksToDelete'];
                
                $performPurge = false;
                $outputOperation = true;
                
                if(count($tasksToDelete) >= PURGE_LIMIT)
                    $performPurge = true;
                
                foreach($tasksToDelete as $taskId)
                {
                    if(TDOComment::getCommentCountForItem($taskId, false, $link) > 0)
                    {
//                        echo "Not deleting due to comment: " . $taskId."\n";
                        continue;
                    }
                    if(!empty($taskGroup['subtaskHash']))
                    {
                        $thisHash = TDOTask::getSubtaskHash($taskId, $link);
                        if(strcmp($thisHash, $taskGroup['subtaskHash']) != 0)
                        {
//                            echo "Not deleting due to subtaskHash mismatch: " . $taskId."\n";
//                            echo $thisHash." Does not equal ".$taskGroup['subtaskHash']."\n";
                            continue;
                        }
                    }

                    if(!empty($taskGroup['taskitosHash']))
                    {
                        $thisHash = TDOTaskito::getTaskitosHash($taskId, $link);
                        if(strcmp($thisHash, $taskGroup['taskitosHash']) != 0)
                        {
//                            echo "Not deleting due to taskitoHash mismatch: " . $taskId."\n";
//                            echo $thisHash." Does not equal ".$taskGroup['taskitosHash']."\n";
                            continue;
                        }
                    }

                    if(!empty($taskGroup['tagHash']))
                    {
                        $thisHash = TDOTask::getTagHash($taskId, $link);
                        if(strcmp($thisHash, $taskGroup['tagHash']) != 0)
                        {
//                            echo "Not deleting due to tags mismatch: " . $taskId."\n";
//                            echo $thisHash." Does not equal ".$taskGroup['tagHash']."\n";
                            continue;
                        }
                    }
                    
                    if($outputOperation)
                    {
                        $outputOperation = false;
                        if($performPurge)
                            echo "Purging   ";
                        else
                            echo "Archiving ";
                    }
                    
                    echo ".";

                    if($performPurge == true)
                    {
                        $result = TDOTask::permanentlyDeleteTask($taskId, $tableName, $link);
                    }
                    else
                    {
                        $result = TDOTask::archiveObject($taskId, $link, true);
                    }
                    
                    if(!$result)
                    {
                        echo "\n";
                        echo "Error deleting object: " . $taskId . "\n";
                        if($closeDBLink)
                            closeTempDBLink($link);
                        return false;
                    }
                }
                if($outputOperation == false)
                    echo "\n";
            }
        }
    }
    if($closeDBLink)
        closeTempDBLink($link);
    
    return true;
}    
   
    
    
function restoreArchivedTasksFromList($listid, $link=NULL)
{
    
    if(empty($link))
    {
        $closeDBLink = true;
        $link = getTempDBLink();
        if(!$link)
        {
            echo("repairList failed to get dblink\n");
            return false;
        }
    }
    else
        $closeDBLink = false;
    
    
    echo "Restoring archived tasks for list: $listid\n";
    
    $tasksToRestore = array();
    
    // get normal tasks
    $sql = "SELECT taskid FROM tdo_archived_tasks WHERE listid='$listid'";
    $result = mysql_query($sql, $link);
    if($result)
    {
        while($row = mysql_fetch_array($result))        
        {
            if(isset($row['taskid']))
            {
                $tasksToRestore[] = $row['taskid'];
            }
        }
    }
    
    if(count($tasksToRestore) == 0)
    {
        if($closeDBLink)
            closeTempDBLink($link);
        return true;
    }
    
        
    foreach($tasksToRestore as $taskId)
    {
        echo ".";
        
        $task = TDOTask::getArchivedTaskForTaskId($taskId, $link);
        
        if($task)
        {
            $oldTaskId = $task->taskId();
            
            $task->setTaskId(NULL);
            $task->setTimeStamp(0);
            
            if($task->addObject($link))
            {
                if($task->isProject())
                {
                    $childrenTasks = TDOTask::getSubTasksForTask($oldTaskId, NULL, NULL, NULL, false, NULL, false, false, $link, true);
                    if($childrenTasks)
                    {
                        foreach($childrenTasks as $childTask)
                        {
                            $oldTaskitoParentId = $childTask->taskId();
                            
                            $childTask->setTaskId(NULL);
                            $childTask->setParentId($task->taskId());
                            $childTask->setTimeStamp(0);
                            
                            if($childTask->addObject($link))
                            {
                                if($childTask->isChecklist())
                                {
                                    $taskitos = TDOTaskito::getTaskitosForTask($oldTaskitoParentId, true, true, $link, false, true);
                                    if($taskitos)
                                    {
                                        foreach($taskitos as $taskito)
                                        {
                                            $taskito->setTaskitoId(NULL);
                                            $taskito->setParentId($childTask->taskId());
                                            $taskito->setTimeStamp(0);
                                            
                                            
                                            $taskito->addObject($link);
                                        }                                    
                                    }
                                }
                            }
                        }
                    }
                }
                else if($task->isChecklist())
                {
                    $taskitos = TDOTaskito::getTaskitosForTask($oldTaskId, true, true, $link, false, true);
                    if($taskitos)
                    {
                        foreach($taskitos as $taskito)
                        {
                            $taskito->setTaskitoId(NULL);
                            $taskito->setParentId($task->taskId());
                            $taskito->setTimeStamp(0);
                            $taskito->addObject($link);
                        }                                    
                    }
                }
            }
        }
    }
    
    if($closeDBLink)
        closeTempDBLink($link);
    
    return true;
}        
    
   
function restoreArchivedTasksForUser($userid)
{
    $link = getTempDBLink();
    if (!$link)
    {
        echo "Failed to open the DB. Exiting.\n";
        exit(1);
    }    
    
    echo "\n==============================================\n";
    echo "  Processing User: ". $userid . "\n";
    echo "==============================================\n";
    
    
    $lists = TDOList::getListsForUser($userid, false, $link);
    foreach($lists as $list)
    {
        $listId = $list->listId();
        
        if(!restoreArchivedTasksFromList($listId, $link))
        {
            echo "Error unarchiving tasks for list: " . $listId . " for user: " . $userid . "\n";
            closeTempDBLink($link);
            return;
        }
    }
    
    echo "\n==============================================\n";
    echo "  Done Processing User: ". $userid . "\n";
    echo "==============================================\n";
    
    closeTempDBLink($link);
}    
    
    
    
    
    
function runDaemonLoop($daemonid = DEFAULT_DAEMON_ID, $limit = DEFAULT_DUPE_LIMIT)
{
    $link = getTempDBLink();
    if (!$link)
    {
        echo "Failed to open the DB. Exiting.\n";
        exit(1);
    }    
    
    // first try to go and get a marked user from the queue
    $operation = operationForLimit($limit);
    
    if(!TDOUserMaintenance::markUserWithDaemon($daemonid, $operation, $link))
    {
        echo "Unable to mark a user for maitenance, returning.\n";
        closeTempDBLink($link);
        return;
    }

    $userid = TDOUserMaintenance::getMarkedUserForDaemon($daemonid, $link);
    if(!$userid)
    {
        echo "Unable to get a marked user, returning.\n";
        closeTempDBLink($link);
        return;
    }
    
    echo "\n==============================================\n";
    echo "  Processing User: ". $userid . "\n";
    echo "==============================================\n";
    

    $lists = TDOList::getListsForUser($userid, false, $link);
    foreach($lists as $list)
    {
        $listId = $list->listId();
        
        if(!repairList($listId, $limit, $link))
        {
            echo "Error processing list: " . $listId . " for user: " . $userid . "\n";
            closeTempDBLink($link);
            return;
        }
    }

    // if we got to here the user's lists were all processed normally, we can remove them from the list
    if(!TDOUserMaintenance::removeUser($userid, $link))
    {
        echo "\n==============================================\n";
        echo "  Error removing user from table: ". $userid . "\n";
        echo "==============================================\n";
    }
    else
    {
        echo "\n==============================================\n";
        echo "  Done Processing User: ". $userid . "\n";
        echo "==============================================\n";
    }
    
    closeTempDBLink($link);
}

    
  
function showUsage()
{
    echo "Usage: repairTaskDupes rundaemon=true [id=<daemonid>] [dupeCount=<dupeCount>]\n  or\n  repairTaskDupes markUsers=true [dupeCount=<dupeCount>]\n  or\n  repairTaskDupes restoreUser=<userid>\n  or\n  repairTaskDupes listid=<listid> [dupeCount=<dupeCount>]\n  or\n  repairTaskDupes identify=true [dupeCount=<dupeCount>]\n\n";
}    
    
    
// $_GET['a'] to '1' and $_GET['b'] to array('2', '3').
parse_str(implode('&', array_slice($argv, 1)), $_GET);

if(!empty($_GET['help']))
{
    showUsage();
    return;
}

$dupeCount = DEFAULT_DUPE_LIMIT;
if(!empty($_GET['dupeCount']))
    $dupeCount = intval($_GET['dupeCount']);
    
    
if(!empty($_GET['identify']))
{
    identifyLists($dupeCount);
    return;
}
    
//if(!empty($_GET['listid']))
//{
//    repairList($_GET['listid'], $dupeCount);
//    return;
//}
    
$daemonID = DEFAULT_DAEMON_ID;

if(!empty($_GET['id']))
{
    $daemonID = $_GET['id'];
}

if(!empty($_GET['markUsers']))
{
    markUsers($dupeCount);
    return;
}
    
if(!empty($_GET['rundaemon']))
{
    runDaemonLoop($daemonID, $dupeCount);
    return;
}
    
if(!empty($_GET['restoreUser']))
{
    restoreArchivedTasksForUser($_GET['restoreUser']);
    return;
}

showUsage();

    
    
    
    
// Get all lists, including deleted ones


?>

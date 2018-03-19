<?php

include_once('TodoOnline/base_sdk.php');
    

    function showUsage()
    {
        echo "\nUsage: purgeUserCompletedTasks username=<username> completiondate=<timestamp> purgeshared=<Y/N> (default N)\n";
    }
    
    // $_GET['a'] to '1' and $_GET['b'] to array('2', '3').
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    
    if(isset($_GET['help']))
    {
        showUsage();
        return;
    }

    if( !isset($_GET['username']) || !isset($_GET['completiondate']))
    {
        showUsage();
        return;
    }

    
    $username = $_GET['username'];
    $completionDate = intval($_GET['completiondate']);
    
    $completionDateStr = date("d/m/Y h:i:s a", $completionDate);
    
    $purgeShared = false;
    if(isset($_GET['purgeshared']))
    {
        $str = $_GET['purgeshared'];
        if(strlen($str) > 0)
        {
            $str = substr($str, 0, 1);
            if($str == 'y' || $str == 'Y')
                $purgeShared = true;
        }
    }
    
    
    $user = TDOUser::getUserForUsername($username);
    if(empty($user))
    {
        echo "Unable to locate user with username: $username\n";
        return;
    }
    
    $userId = $user->userId();
    
    
    $lists = TDOList::getListIDsForUser($userId);
    if(empty($lists))
    {
        echo "Unable to get lists for user: $username\n";
        return;
    }
    
    $listString = "";
    foreach($lists as $listId)
    {
        $addList = true;
        if($purgeShared == false)
        {
            $count = TDOList::getPeopleCountForList($listId);
            if(empty($count))
            {
                echo "Error getting list count for list: $listId\n";
                return;
            }
            if($count > 1)
                $addList = false;
        }
        
        if($addList)
        {
            if(strlen($listString) > 0)
                $listString .= ",";
            
            $listString .= "'$listId'";
        }
    }
    
    if(strlen($listString) == 0)
    {
        echo "Unable to get lists for user: $username\n";
        return;        
    }
    

    $link = TDOUtil::getDBLink();
    
    if(empty($link))
    {
        echo "Error: unable to get DB link\n";
        return;
    }
    
    //Get all the completed tasks prior to the given date
    $sql = "SELECT taskid FROM tdo_completed_tasks WHERE listid IN ($listString) AND completiondate < $completionDate";
    
    $result = mysql_query($sql, $link);
    $taskIds = array();
    if($result)
    {
        while($row = mysql_fetch_array($result))
        {
            if(isset($row['taskid']))
            {
                $taskIds[] = $row['taskid'];
            }
        }
        TDOUtil::closeDBLink($link);
    }
    else
    {
        echo "Database error: ".mysql_error()."\n";
        TDOUtil::closeDBLink($link);
        return;
    }
    
    
    if(count($taskIds) == 0)
    {
        echo "No tasks found completed before $completionDateStr for user $username\n";
        return;
    }

    //Prompt the user to confirm deletion
    $userResponse = NULL;
    while($userResponse != "n" && $userResponse != "N" && $userResponse != "y" && $userResponse != "Y")
    {
        echo "\nPermanently delete ".count($taskIds)." tasks completed before $completionDateStr for user $username? ";
        
        if($purgeShared)
            echo "Tasks in shared lists are included. ";
        else
            echo "Tasks in shared lists are not included. ";
        
        echo "(Y/N) ";

        // get input
        $userResponse = trim(fgets(STDIN));
        if(strlen($userResponse) > 0)
        {
            //trim to the first character so the user can type 'yes' or 'no'
            $userResponse = substr($userResponse, 0, 1);
        }
    }
    
    if($userResponse != "y" && $userResponse != "Y")
    {
        return;
    }
    
    // Call permanentlyDeleteTask on each task we retrieved so that their notifications,
    // taskitos, etc. also get deleted
    
    echo "Deleting tasks...\n";
    $deletedCount = 0;
    foreach($taskIds as $taskId)
    {
        if(TDOTask::permanentlyDeleteTask($taskId, "tdo_completed_tasks") == false)
        {
            echo " ** Error deleting task: ".$taskId.", continuing on\n";
        }
        else
        {
            $deletedCount++;
        }
    }
    
    echo "Finished deleting $deletedCount tasks\n";
    
    
?>


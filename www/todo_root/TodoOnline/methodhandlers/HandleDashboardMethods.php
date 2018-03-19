<?php

//All dashboard methods require the listid to be set
if(!isset($_POST['listid']) && !isset($_GET['listid']))
{
    error_log("getDashboardContent called and missing a required parameter: listid ");
    echo '{"success":false}';
    return;
}

if(isset($_POST['listid']))
    $listid = $_POST['listid'];
else
    $listid = $_GET['listid'];


if($listid != "all" && $listid != "focus" && $listid != "starred" && $listid != "today")
{
    $isSpecialList = false;
    if(TDOList::userCanViewList($listid, $session->getUserId()) == false)
    {
        error_log("getDashboardContent called with insufficient permissions");
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('You are not allowed to view this list'),
        ));
        return;
    }
}
else
    $isSpecialList = true;


if($method == "getDashboardContent")
{
    $jsonResponse = array();
    
    //get the list name & description
    if($isSpecialList == false)
    {
        $list = TDOList::getListForListid($listid);
        if($list == false)
        {
            error_log("getDashboardContent could not get list for list id: $listid");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Could not find list'),
            ));
            return;
        }
        $listName = $list->name();
        if($list->description() != NULL)
            $jsonResponse['listdescription'] = $list->description();
    }
    else
    {
        $listName = TDOList::getNameForList($listid);
    }
    $jsonResponse['listname'] = $listName;

    //get whether or not the list can be shared
    $jsonResponse['shareable'] = $isSpecialList || $listName == 'Inbox' ? false : true;
    
    //error_log('list name: '. $listName);
    
    //get whether the list is shared or not
    $isShared = TDOList::getPeopleCountForList($listid) > 1 ? true : false;
    $jsonResponse['shared'] = $isShared;
    
    //get the active task count and completed task count
    $activeCount = TDOTask::taskCountForList($listid, $session->getUserId(), false);
    $completedCount = TDOTask::taskCountForList($listid, $session->getUserId(), true);
    $jsonResponse['completed_taskcount'] = intval($completedCount);
    $jsonResponse['active_taskcount'] = intval($activeCount);
    

    //get the 3 most recent comments from the server
    $jsonCommentData = getCommentJsonForList($listid, $session->getUserId(), 3, NULL, 0, NULL);
    if($jsonCommentData['success'] == false)
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('Unable to get comments for list'),
        ));
        return;
    }
    
    $jsonResponse['comment_tasks'] = $jsonCommentData['comment_tasks'];
    $jsonResponse['more_comment_count'] = $jsonCommentData['more_comment_count'];
    $jsonResponse['oldest_comment_timestamp'] = $jsonCommentData['oldest_comment_timestamp'];
    $jsonResponse['oldest_comment_id'] = $jsonCommentData['oldest_comment_id'];
    
    //get the 3 most recent changes from the server
    $jsonChangeData = getDashboardChangeLogJsonForList($listid, $session->getUserId(), 3, 0, NULL);
    if($jsonChangeData['success'] == false)
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('Unable to get changes for list'),
        ));
        return;
    }
    $jsonResponse['changes'] = $jsonChangeData['changes'];
    $jsonResponse['more_change_count'] = $jsonChangeData['more_change_count'];
    $jsonResponse['oldest_change_timestamp'] = $jsonChangeData['oldest_change_timestamp'];
    $jsonResponse['oldest_change_id'] = $jsonChangeData['oldest_change_id'];
    
    //get the 'today' tasks
    
//    include_once('TodoOnline/content/TaskContentFunctions.php');
//    $showSubtasksSetting = false;
//    $userSettings= TDOUserSettings::getUserSettingsForUserid($session->getUserId());
//    if($userSettings)
//    {
//        if($userSettings->focusShowSubtasks())
//            $showSubtasksSetting = true;
//    }
//    $taskResponse = pagedTaskContentForSectionID('today_tasks_container', $session->getUserId(), $listid, NULL, NULL, false, NULL, false, 0, 0, $showSubtasksSetting);
//    if($taskResponse['success'] == false)
//    {
//        echo '{"success":false, "error":"Unable to get today tasks for list"}';
//        return;
//    }
//    
//    $jsonResponse['today_tasks'] = $taskResponse['tasks'];
    
    $jsonResponse['success'] = true;
    echo json_encode($jsonResponse);
}

elseif($method == "setDashboardHidden")
{
    if(!isset($_POST['hidden']))
    {
        error_log("setDashboardHidden called missing parameter: hidden");
        echo '{"success":false}';
        return;
    }
    
    $hiddenVal = $_POST['hidden'];
    
    if($isSpecialList)
    {
        $userSettings = TDOUserSettings::getUserSettingsForUserid($session->getUserId());
        if(empty($userSettings))
        {
            error_log("setDashboardHidden unable to get user settings for user: ".$session->getUserId());
            echo '{"success":false}';
            return;
        }
        
        if($listid == "focus")
            $userSettings->setFocusListHideDashboard($hiddenVal);
        elseif($listid == "starred")
            $userSettings->setStarredListHideDashboard($hiddenVal);
        elseif($listid == "all")
            $userSettings->setAllListHideDashboard($hiddenVal);
            
        if($userSettings->updateUserSettings() == false)
        {
            echo '{"success":false}';
            return;
        }
    }
    else
    {
        $listSettings = TDOListSettings::getListSettingsForUser($listid, $session->getUserId());
        
        if(empty($listSettings))
        {
            error_log("setDashboardHidden unable to get list settings for list: ".$listid);
            echo '{"success":false}';
            return;           
        }
        
        $listSettings->setHideDashboard($hiddenVal);
        
        if($listSettings->updateListSettings($listid, $session->getUserId()) == false)
        {
            echo '{"success":false}';
            return;
        }
    }
    echo '{"success":true}';
}
//The parameters are count, lasttimestamp, lastcommentid, and excludedtaskids, where count is the number of comments you want,
//lasttimestamp is the timestamp on the oldest comment returned to you from the server,
//lastcommentid is the id of the oldest comment returned to you by the server, and
//excludedtaskids is a comma separated list of all task ids already returned to you from the server
elseif($method == "getMoreComments")
{
    if(!isset($_POST['count']) || !isset($_POST['lasttimestamp']) || !isset($_POST['lastcommentid']) || !isset($_POST['excludedtaskids']))
    {
        error_log("getMoreComments called missing required parameter: count or lasttimestamp or lastcommentid or excludedtaskids");
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('missing parameter'),
        ));
        return;
    }
    
    $limit = $_POST['count'];
    $timestamp = $_POST['lasttimestamp'];
    $commentid = $_POST['lastcommentid'];
    $excludedstring = $_POST['excludedtaskids'];
    
    $jsonResponse = getCommentJsonForList($listid, $session->getUserId(), $limit, $excludedstring, $timestamp, $commentid);
    
    echo json_encode($jsonResponse);
    
}

//The parameters are count, lasttimestamp, and lastchangeid, where count is the number of changes you want,
//lasttimestamp is the timestamp on the oldest changes returned to you from the server, and
//lastchangeid is the id of the oldest changes returned to you by the server
elseif($method == "getMoreDashboardChanges")
{
    if(!isset($_POST['count']) || !isset($_POST['lasttimestamp']) || !isset($_POST['lastchangeid']))
    {
        error_log("getMoreDashboardChanges called missing required parameter: count or lasttimestamp or lastchangeid");
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('missing parameter'),
        ));
        return;
    }
    
    $limit = $_POST['count'];
    $timestamp = $_POST['lasttimestamp'];
    $changeid = $_POST['lastchangeid'];
    
    $jsonResponse = getDashboardChangeLogJsonForList($listid, $session->getUserId(), $limit, $timestamp, $changeid);
    echo json_encode($jsonResponse);
    
}

function getCommentJsonForList($listid, $userid, $limit, $excludedstring, $timestamp, $commentid)
{
    $jsonArray = array();
    
    $excludedArray = explode(",", $excludedstring);
    
    $comments = TDOComment::getRecentCommentsForList($listid, $userid, $limit, $excludedArray, $timestamp, $commentid);
    
    if($comments === false)
    {
        $jsonArray['success'] = false;
        return;
    }
    
    $oldestTimestamp = $timestamp;
    $oldestCommentId = $commentid;
    
    //Group the comments by task
    $tasks = array();
    foreach($comments as $comment)
    {
        $taskid = $comment->itemId();
        if(!empty($taskid))
        {
            if(isset($tasks[$taskid]))
            {
                //If we're already returning this task, simply pull out its
                //comment array and add in this comment
                $jsonTask = $tasks[$taskid];
                $jsonComments = $jsonTask['comments'];
                
                $jsonComment = $comment->getPropertiesArray();
                
                //insert it to the beginning of the array
                array_unshift($jsonComments, $jsonComment);
                $jsonTask['comments'] = $jsonComments;
                
            }
            else
            {
                $jsonTask = array();
                $jsonTask['name'] = $comment->itemName();
                $jsonTask['commentcount'] = TDOComment::getCommentCountForItem($taskid);
                $jsonTask['taskid'] = $taskid;
                
                $jsonComments = array();
                $jsonComment = $comment->getPropertiesArray();
                $jsonComments[] = $jsonComment;
                
                $jsonTask['comments'] = $jsonComments;
                
                $excludedArray[] = $taskid;
                
            }
            $oldestTimestamp = $comment->timestamp();
            $oldestCommentId = $comment->commentId();
            
                        
            $tasks[$taskid] = $jsonTask;
        }
        else
            error_log("Found a comment with no item");
    }
    
    //Convert jsonTasks from an associative array to an indexed array, or else it won't work right with json
    $jsonTasks = array();
    foreach($tasks as $key=>$value)
    {
        $jsonTasks[] = $value;
    }
    
    $moreCount = 0;
    if(count($comments) == $limit)
    {
        $moreCount = TDOComment::getRecentCommentCountForList($listid, $userid, $excludedArray, $oldestTimestamp, $oldestCommentId);
    }
    
    $jsonArray['success'] = true;
    $jsonArray['comment_tasks'] = $jsonTasks;
    $jsonArray['more_comment_count'] = $moreCount;
    $jsonArray['oldest_comment_timestamp'] = $oldestTimestamp;
    $jsonArray['oldest_comment_id'] = $oldestCommentId;
    
    return $jsonArray;
}

function getDashboardChangeLogJsonForList($listid, $userid, $limit, $lastChangeTimestamp, $lastChangeId)
{
    
    $itemTypes = array(ITEM_TYPE_USER, ITEM_TYPE_INVITATION, ITEM_TYPE_TASK, ITEM_TYPE_LIST);
    $showUserTasks = true;
    
    $changes = TDOChangeLog::getChangesForList($listid, $userid, $limit, $lastChangeTimestamp, $lastChangeId, $itemTypes, $showUserTasks);

    if($changes === false)
    {
        return array("success"=>false);
    }
    
    $jsonResponse = array();
    
    $oldestTimestamp = $lastChangeTimestamp;
    $oldestChangeId = $lastChangeId;
    
    $jsonChanges = array();
    foreach($changes as $change)
    {
        $jsonChange = $change->getPropertiesArray();
        $jsonChanges[] = $jsonChange;
        
        $oldestTimestamp = $change->timestamp();
        $oldestChangeId = $change->changeId();
        
    }
    
    $moreCount = 0;
    if(count($changes) == $limit)
    {
        $moreCount = TDOChangeLog::getChangeCountForList($listid, $userid, $oldestTimestamp, $oldestChangeId, $itemTypes, $showUserTasks);
    }
    $jsonResponse['success'] = true;
    $jsonResponse['changes'] = $jsonChanges;
    $jsonResponse['more_change_count'] = $moreCount;
    $jsonResponse['oldest_change_timestamp'] = $oldestTimestamp;
    $jsonResponse['oldest_change_id'] = $oldestChangeId;
    
    return $jsonResponse;

}

?>
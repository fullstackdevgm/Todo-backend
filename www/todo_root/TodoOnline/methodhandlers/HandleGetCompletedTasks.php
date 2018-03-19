<?php

include_once('TodoOnline/syncmethodhandlers/SyncConstants.php');

if($method == "getCompletedTasks")
{
    $userFilter = NULL;
	$sectionID = NULL;
	$contextID = NULL;
    $tagsFilter = NULL;
    $limit = 50;

	if(!isset($_POST['listid']))
	{
		error_log("HandleGetCompletedTasks called and missing a required parameter: listid");
		echo '{"success":false}';
		return;
	}

    $listid = $_POST['listid'];
    if(TDOList::userCanViewList($listid, $session->getUserId()) == false)
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('You do not have permission to view this list'),
        ));
        return;
    }

    if(isset($_POST['limit']))
    {
        $limit = $_POST['limit'];
    }

    if(isset($_COOKIE['TodoOnlineTaskAssignFilterId']))
    {
        $userFilter = $_COOKIE['TodoOnlineTaskAssignFilterId'];
    }

    if(isset($_POST['section_id']))
    {
        $sectionID = $_POST['section_id'];
    }

    if(isset($_COOKIE['TodoOnlineContextId']))
    {
        $contextID = $_COOKIE['TodoOnlineContextId'];
    }

    if(isset($_COOKIE['TodoOnlineTagId']))
    {
        $tagsFilterString = $_COOKIE['TodoOnlineTagId'];
        if(strlen($tagsFilterString) > 0)
            $tagsFilter = explode(",", $tagsFilterString);
    }

    $tagsFilterSetting = false;
    $showSubtasksSetting = false;

    $userSettings= TDOUserSettings::getUserSettingsForUserid($session->getUserId());
    if($userSettings)
    {
        if($userSettings->tagFilterWithAnd())
            $tagsFilterSetting = true;
        if($userSettings->focusShowSubtasks())
            $showSubtasksSetting = true;
    }

    $completedBeforeTask = NULL;

    if(isset($_POST['before_timestamp']))
    {
        $completedBeforeTask = new TDOTask();
        $completedBeforeTask->setCompletionDate($_POST['before_timestamp']);

        if(isset($_POST['before_sortorder']))
        {
            $completedBeforeTask->setSortOrder($_POST['before_sortorder']);
        }
        if(isset($_POST['before_priority']))
        {
            $completedBeforeTask->setPriority($_POST['before_priority']);
        }
        if(isset($_POST['before_name']))
        {
            $completedBeforeTask->setName($_POST['before_name']);
        }
        if(isset($_POST['before_id']))
        {
            $completedBeforeTask->setTaskId($_POST['before_id']);
        }
    }

    $oldestCompletedDate = NULL;
    if(TDOSubscription::getSubscriptionLevelForUserID($session->getUserId()) < 2)
    {
        //Only show 30 days of completed tasks for users who don't have a valid subscription
        $date = new DateTime();
        $date->setTimestamp(time());
        $date->modify(" - 30 days");
        $oldestCompletedDate = $date->getTimestamp();
    }

    $tasks = TDOTask::getTasksForSectionID("completed_tasks_container", $session->getUserId(), $listid, $contextID, $tagsFilter, $tagsFilterSetting, $userFilter, true, $showSubtasksSetting, $limit, $completedBeforeTask, $oldestCompletedDate);

    if($tasks === false)
    {
        echo '{"success":false}';
        return;
    }

    $jsonTasks = array();
    foreach($tasks as $task)
    {
        $taskProperties = $task->getPropertiesArray();
        $jsonTasks[] = $taskProperties;
    }

    $jsonResponse = array();
    $jsonResponse['success'] = true;
    $jsonResponse['tasks'] = $jsonTasks;

    //If there are more tasks to get but we limited it because the user doesn't have a premium account, tell the user
    if($oldestCompletedDate != NULL && count($tasks) < $limit)
    {
        if(count($tasks) > 0)
            $lastTask = $tasks[(count($tasks) - 1)];
        else
            $lastTask = $completedBeforeTask;
        $realTasks = TDOTask::getTasksForSectionID("completed_tasks_container", $session->getUserId(), $listid, $contextID, $tagsFilter, $tagsFilterSetting, $userFilter, true, $showSubtasksSetting, 1, $lastTask, NULL);
        if(!empty($realTasks))
            $jsonResponse['premium_limited'] = true;
    }

    echo json_encode($jsonResponse);
}
else if ($method == "getCompletedTasksAPI")
{
    header('Content-Type: application/json');
    $userid = $session->getUserId();

    // add a check here, it their subscription is not valid, return an error
    $subscriptionLevel = TDOSubscription::getSubscriptionLevelForUserID($userid);
    if ($subscriptionLevel < SUBSCRIPTION_LEVEL_TRIAL) {
        echo json_encode(array(
            'success' => false,
            'errorCode' => 4714,
            'errorDesc' => 'User\'s subscription is expired.',

        ));
        error_log("getCompletedTasksAPI method call: " . $_POST["method"] . " because user's subscription is expired: " . TDOUser::usernameForUserId($session->getUserId()));
        exit;
    }

    $link = TDOUtil::getDBLink();

    $resultsArray = array(
        'totalCount' => 0,
        'tasks' => array(),
    );

    $smartListID = NULL;
    $listsToExclude = NULL;
    $listid = 'all';
    $limit = 30;
    $offset = 0;

    if (isset($_POST['smartlistid']) && $_POST['smartlistid'] !== '') {
      $smartListID = $_POST['smartlistid'];
    }
    if (isset($_POST['listsToExclude']) && $_POST['listsToExclude'] != '') {
      $listsToExclude = $_POST['listsToExclude'];
    }
    if (isset($_POST['listid']) && $_POST['listid'] !== '') {
        $listid = $_POST['listid'];
    }
    if (isset($_POST['limit']) && $_POST['limit'] !== '' && intval($_POST['limit']) > 0) {
        $limit = intval($_POST['limit']);
    }
    if (isset($_POST['offset']) && $_POST['offset'] !== '' && intval($_POST['offset']) >= 0) {
        $offset = $_POST['offset'];
    }

    if (empty($link)) {
        error_log("getCompletedTasksAPI failed to get DBLink");
        echo json_encode(array(
            'success' => false,
            'errorCode' => 4730,
            'errorDesc' => 'Unknown database error.',

        ));
        exit;
    }

    $sql = 'SELECT * FROM tdo_completed_tasks WHERE ';
    $whereClause = NULL;
    if ($smartListID) {
      if (TDOSmartList::userCanEditSmartList($smartListID, $userid, $link) == false) {
        echo json_encode(array(
          'success' => FALSE,
          'error' => _('You do not have permission to this smart list'),
        ));
        exit;
      }

      $smartList = TDOSmartList::getSmartListForListid($smartListID, $link);
      if (!$smartList) {
        // Smart list not found
        echo json_encode(array(
          'success' => FALSE,
          'error' => _('The specified smart list was not found.'),
        ));
        exit;
      }

      $usingStartDates = !$smartList->excludeStartDates();

      $whereClause = $smartList->sqlWhereStatementUsingStartDates($usingStartDates);

      // The code has to scope the tasks to only come from the lists that the
      // user has access to. First, get ALL of the lists that the user can see
      // and then remove the ones specified by the exclude list (if an exclude
      // list is specified).
      $listIDsA = TDOList::getListIDsForUser($userid, $link);

      // First check to see if the call to getCompletedTasksAPI included a param
      // of some lists to exclude (based on the device's settings of which lists
      // to view tasks).
      if (!empty($listsToExclude)) {
        $excludedListIDs = explode(',', $listsToExclude);
        if (count($excludedListIDs) > 0) {
          foreach($excludedListIDs as $aListID) {
            if (($key = array_search($aListID, $listIDsA)) !== false) {
              unset($listIDsA[$key]);
            }
          }
        }
      }

      // Now check to see if the smart list specifies lists that should be
      // excluded and also remove those from the lists that we'll check.
      $listsToExclude = $smartList->excludedListIDs();
      if ($listsToExclude) {
        foreach ($listsToExclude as $aListID) {
          if (($key = array_search($aListID, $listIDsA)) !== false) {
            unset($listIDsA[$key]);
          }
        }
      }

      // Get rid of any empty spots in the array from previous calls to 'unset'
      $listsSQL = '';
      $listsToQuery = array_values($listIDsA);
      foreach ($listsToQuery as $aListID) {
        if (strlen($listsSQL) > 0) {
          $listsSQL .= " OR ";
        }
        $listsSQL .= "listid = '" . $aListID . "'";
      }

      if (strlen($listsSQL) == 0) {
        // There must be something wrong. This method should never let anyone
        // get tasks from just any list, so if there aren't any lists to grab
        // from, return an error.
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('Invalid list filter specified.'),
        ));
        exit;
      }

      if (empty($whereClause) || strlen($whereClause) == 0) {
        $whereClause = "(" . $listsSQL . ")";
      } else {
        $whereClause = "(" . $listsSQL . ") AND " . $whereClause;
      }

    } else {
      if ($listid == "INBOX") {
        $listid = TDOList::getUserInboxId($userid, false, $link);
        if (empty($listid)) {
          echo json_encode(array(
              'success' => false,
              'errorCode' => ERROR_CODE_OBJECT_NOT_FOUND,
              'errorDesc' => ERROR_DESC_OBJECT_NOT_FOUND,

          ));
          error_log("getCompletedTasksAPI method call: " . $_POST["method"] . " because the inbox id was not found: " . TDOUser::usernameForUserId($session->getUserId()));
          exit;
        }
      }

      if(TDOList::userCanViewList($listid, $userid, $link) == false) {
          echo json_encode(array(
              'success' => FALSE,
              'error' => _('You do not have permission to view this list'),
          ));
          exit;
      }

      $listsql = TDOTask::buildSQLFilterForUserForListID($userid, $listid, false, true);
      $whereClause = $listsql;
    }

    if ($whereClause == NULL) {
      // Something went wrong building the sql
      echo json_encode(array(
        'success' => FALSE,
        'error' => _('Could not query the database for completed tasks.'),
      ));
      exit;
    }

    // For this call, do NOT return subtasks. Subtasks can be returned by
    // calling the getCompletedSubtasksAPI.
    $whereClause = " (parentid IS NULL OR parentid = '') AND " . $whereClause;

    $sql .= " " . $whereClause . ' ORDER BY completiondate DESC,sort_order,priority,name LIMIT ' . $offset . ', ' . $limit;

    $result = mysql_query($sql, $link);
    $tasks = array();
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            if ((empty($row['taskid']) == false) && (count($row) > 0)) {
                $task = TDOTask::taskFromRow($row);
                if (empty($task) == false) {
                    $resultsArray['tasks'][] = $task->getPropertiesArray();
                }
            }
        }
    } else {
        error_log("HandleTaskSyncMethods getCompletedTasks could not get tasks for the specified list '$listid' " . mysql_error());
    }

    $sql = 'SELECT COUNT(taskid) AS totalCount FROM tdo_completed_tasks WHERE ' . $whereClause;
    $result = mysql_query($sql, $link);
    if ($result) {
        $row = mysql_fetch_array($result);
        $resultsArray['totalCount'] = $row['totalCount'];
				$resultsArray['success'] = true;
    } else {
        error_log("HandleTaskSyncMethods getCompletedTasks could not get tasks total count for the specified list '$listid' " . mysql_error());
    }
    TDOUtil::closeDBLink($link);


    echo json_encode($resultsArray);
    exit;
}
else if ($method == "getCompletedSubtasksAPI")
{
    header('Content-Type: application/json');
    $userid = $session->getUserId();

    // add a check here, it their subscription is not valid, return an error
    $subscriptionLevel = TDOSubscription::getSubscriptionLevelForUserID($userid);
    if ($subscriptionLevel < SUBSCRIPTION_LEVEL_TRIAL) {
        echo json_encode(array(
            'success' => false,
            'errorCode' => ERROR_CODE_EXPIRED_SUBSCRIPTION,
            'errorDesc' => ERROR_DESC_EXPIRED_SUBSCRIPTION,

        ));
        error_log("getCompletedSubtasksAPI method call: " . $_POST["method"] . " because user's subscription is expired: " . TDOUser::usernameForUserId($session->getUserId()));
        exit;
    }

    $link = TDOUtil::getDBLink();

    $resultsArray = array(
        'totalCount' => 0,
        'tasks' => array(),
    );

    $parentTaskID = 'parentTaskID';
    $limit = 30;
    $offset = 0;

    if (isset($_POST['parentTaskID']) && $_POST['parentTaskID'] !== '') {
        $parentTaskID = $_POST['parentTaskID'];
    }
    if (isset($_POST['limit']) && $_POST['limit'] !== '' && intval($_POST['limit']) > 0) {
        $limit = intval($_POST['limit']);
    }
    if (isset($_POST['offset']) && $_POST['offset'] !== '' && intval($_POST['offset']) >= 0) {
        $offset = $_POST['offset'];
    }

    if (empty($link)) {
        error_log("getCompletedSubtasksAPI failed to get DBLink");
        echo json_encode(array(
            'success' => false,
            'errorCode' => ERROR_CODE_DB_LINK_FAILED,
            'errorDesc' => ERROR_DESC_DB_LINK_FAILED,

        ));
        exit;
    }

    // Make sure that the user has access to the specified project (parentTaskID)
    $listID = TDOTask::getListIdForTaskId($parentTaskID, $link);
    if (empty($listID)) {
      TDOUtil::closeDBLink($link);
      error_log("getCompletedSubtasksAPI failed to find the list for the parentTaskID");
      echo json_encode(array(
          'success' => false,
          'errorCode' => ERROR_CODE_LIST_NOT_FOUND,
          'errorDesc' => ERROR_DESC_LIST_NOT_FOUND,
      ));
      exit;
    }

    if (TDOList::userCanViewList($listID, $userid, $link) == false) {
      TDOUtil::closeDBLink($link);
      error_log("getCompletedSubtasksAPI user does not have access to this project.");
      echo json_encode(array(
          'success' => false,
          'errorCode' => ERROR_CODE_ACCESS_DENIED,
          'errorDesc' => ERROR_DESC_ACCESS_DENIED,
      ));
      exit;
    }

    $sql = 'SELECT * FROM tdo_completed_tasks WHERE ';

    $listID = mysql_real_escape_string($listID, $link);
    $parentTaskID = mysql_real_escape_string($parentTaskID, $link);
    $whereClause = "listid='" . $listID . "' AND parentid='" . $parentTaskID . "' AND (deleted IS NULL OR deleted=0)";

    $sql .= " " . $whereClause . ' ORDER BY completiondate DESC,sort_order,priority,name LIMIT ' . $offset . ', ' . $limit;

    $result = mysql_query($sql, $link);
    $tasks = array();
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            if ((empty($row['taskid']) == false) && (count($row) > 0)) {
                $task = TDOTask::taskFromRow($row);
                if (empty($task) == false) {
                    $resultsArray['tasks'][] = $task->getPropertiesArray();
                }
            }
        }
    } else {
        error_log("HandleTaskSyncMethods getCompletedSubtasksAPI could not get subtasks for the specified parentTaskID 'parentTaskID' " . mysql_error());
    }

    $sql = 'SELECT COUNT(taskid) AS totalCount FROM tdo_completed_tasks WHERE ' . $whereClause;
    $result = mysql_query($sql, $link);
    if ($result) {
        $row = mysql_fetch_array($result);
        $resultsArray['totalCount'] = $row['totalCount'];
				$resultsArray['success'] = true;
    } else {
        error_log("HandleTaskSyncMethods getCompletedSubtasksAPI could not get tasks total count for the specified list '$listid' " . mysql_error());
    }
    TDOUtil::closeDBLink($link);


    echo json_encode($resultsArray);
    exit;
}
else if ($method == "getCompletedTaskitosAPI")
{
    header('Content-Type: application/json');
    $userid = $session->getUserId();

    // add a check here, it their subscription is not valid, return an error
    $subscriptionLevel = TDOSubscription::getSubscriptionLevelForUserID($userid);
    if ($subscriptionLevel < SUBSCRIPTION_LEVEL_TRIAL) {
        echo json_encode(array(
            'success' => false,
            'errorCode' => ERROR_CODE_EXPIRED_SUBSCRIPTION,
            'errorDesc' => ERROR_DESC_EXPIRED_SUBSCRIPTION,

        ));
        error_log("getCompletedTaskitosAPI method call: " . $_POST["method"] . " because user's subscription is expired: " . TDOUser::usernameForUserId($session->getUserId()));
        exit;
    }

    $link = TDOUtil::getDBLink();

    $resultsArray = array(
        'totalCount' => 0,
        'tasks' => array(),
    );

    $parentTaskID = 'parentTaskID';
    $limit = 30;
    $offset = 0;

    if (isset($_POST['parentTaskID']) && $_POST['parentTaskID'] !== '') {
        $parentTaskID = $_POST['parentTaskID'];
    }
    if (isset($_POST['limit']) && $_POST['limit'] !== '' && intval($_POST['limit']) > 0) {
        $limit = intval($_POST['limit']);
    }
    if (isset($_POST['offset']) && $_POST['offset'] !== '' && intval($_POST['offset']) >= 0) {
        $offset = $_POST['offset'];
    }

    if (empty($link)) {
        error_log("getCompletedTaskitosAPI failed to get DBLink");
        echo json_encode(array(
            'success' => false,
            'errorCode' => ERROR_CODE_DB_LINK_FAILED,
            'errorDesc' => ERROR_DESC_DB_LINK_FAILED,

        ));
        exit;
    }

    // Make sure that the user has access to the specified checklist (parentTaskID)
    $listID = TDOTask::getListIdForTaskId($parentTaskID, $link);
    if (empty($listID)) {
      TDOUtil::closeDBLink($link);
      error_log("getCompletedTaskitosAPI failed to find the list for the parentTaskID");
      echo json_encode(array(
          'success' => false,
          'errorCode' => ERROR_CODE_LIST_NOT_FOUND,
          'errorDesc' => ERROR_DESC_LIST_NOT_FOUND,
      ));
      exit;
    }

    if (TDOList::userCanViewList($listID, $userid, $link) == false) {
      TDOUtil::closeDBLink($link);
      error_log("getCompletedTaskitosAPI user does not have access to this checklist.");
      echo json_encode(array(
          'success' => false,
          'errorCode' => ERROR_CODE_ACCESS_DENIED,
          'errorDesc' => ERROR_DESC_ACCESS_DENIED,
      ));
      exit;
    }

    $sql = 'SELECT * FROM tdo_taskitos WHERE ';

    $parentTaskID = mysql_real_escape_string($parentTaskID, $link);
    $whereClause = "parentid='" . $parentTaskID . "' AND completiondate IS NOT NULL AND completiondate != 0 AND (deleted IS NULL OR deleted=0)";

    $sql .= " " . $whereClause . ' ORDER BY completiondate DESC,sort_order,name LIMIT ' . $offset . ', ' . $limit;

    $result = mysql_query($sql, $link);
    $tasks = array();
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            if ((empty($row['taskitoid']) == false) && (count($row) > 0)) {
                $task = TDOTaskito::taskitoFromRow($row);
                if (empty($task) == false) {
                    $resultsArray['tasks'][] = $task->getPropertiesArray();
                }
            }
        }
    } else {
      TDOUtil::closeDBLink($link);
      error_log("HandleTaskSyncMethods getCompletedTaskitosAPI could not get subtasks for the specified parentTaskID 'parentTaskID' " . mysql_error());
      echo json_encode(array(
          'success' => false,
          'errorCode' => ERROR_CODE_DB_LINK_FAILED,
          'errorDesc' => ERROR_DESC_DB_LINK_FAILED,
      ));
      exit;
    }

    $sql = 'SELECT COUNT(taskitoid) AS totalCount FROM tdo_taskitos WHERE ' . $whereClause;
    $result = mysql_query($sql, $link);
    if ($result) {
        $row = mysql_fetch_array($result);
        $resultsArray['totalCount'] = $row['totalCount'];
				$resultsArray['success'] = true;
    } else {
        error_log("HandleTaskSyncMethods getCompletedTaskitosAPI could not get tasks total count for the specified list '$listid' " . mysql_error());
    }
    TDOUtil::closeDBLink($link);


    echo json_encode($resultsArray);
    exit;
}


?>

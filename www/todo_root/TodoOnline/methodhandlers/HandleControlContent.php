<?php
	
	include_once('TodoOnline/content/ContentConstants.php');
		
	if(isset($_POST["type"]))
		$type = $_POST["type"];
	else
	{
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('control type was not set'),
        ));
		exit();
	}	
		
	switch ($type)
	{
		case "list":
        {
            $getCounts = false;
            if(isset($_POST['counts']))
            {
                $getCounts = true;
            }
            
			echo returnLists($getCounts);
			break;
        }
		case "context":
			echo returnContexts();
			break;
        case "tag":
        {
            if(isset($_POST['taskid']))
                echo returnTagsForTask();
            else
                echo returnTags();
            break;
        }
        case "listUsers":
        	echo returnUsersForList();
        	break;
		default:
            echo json_encode(array(
                'success' => FALSE,
                'error' => sprintf(_('control type was &quot;%s&quot; unknown'), $type),
            ));
			break;
	}	
	
    
    function returnUsersForList()
    {
        $session = TDOSession::getInstance();
        $sessionUser = $session->getUserId();
        $usersArray = array();
        
        if(isset($_POST['listId']))
        {
            $listId = $_POST['listId'];
            $userIds = TDOList::getPeopleAndRolesForlistid($listId, false, $sessionUser);
            
            if(!empty($userIds))
            {
                foreach($userIds as $userId=>$role)
                {
                    $user = TDOUser::getUserForUserId($userId);
                    $userArray = array();
                    
//                    $fbId = TDOUser::facebookIdForUserId($userId);
//                    if($fbId)
//                    {
//                        $userPicUrl = 'https://graph.facebook.com/'.$fbId.'/picture';
//                    }
                    if($user->fullImageURL() != NULL)
                    {
                        $userPicUrl = $user->fullImageURL();
                    }
                    else
                    {
                        $userPicUrl = SMALL_PROFILE_IMG_PLACEHOLDER;
                    }
                    
                    $userArray['name'] = TDOUser::displayNameForUserId($userId);
                    $userArray['imgurl'] = $userPicUrl;
                    $userArray['id'] = $userId;
                    
                    if($userId == $sessionUser)
                    {
                        array_unshift($usersArray,$userArray);
                    }
                    else
                    {
                        $usersArray[] = $userArray;
                    }
                }
            }
        }
        
        $response = array();
        if(!empty($usersArray))
        {
            $response['success'] = true;
            $response['users'] = $usersArray;
        }
        else
        {
            $response['success'] = false;
        }
        
        return json_encode($response);
    }
    
    		
	//returnLists function
	//purpose: returns a json string of the list names and their ID's for the current user
	function returnLists($getCounts)
	{
		$session = TDOSession::getInstance();
		$lists = TDOList::getListsForUser($session->getUserId());
        
        if($getCounts)
        {
            $tagsFilter = NULL;
            if(isset($_COOKIE['TodoOnlineTagId']))
            {
                $tagsFilterString = $_COOKIE['TodoOnlineTagId'];
                if(strlen($tagsFilterString) > 0)
                    $tagsFilter = explode(",", $tagsFilterString);
            }
            $tagsFilterSetting = false;
            if(!empty($tagsFilterString))
            {
                $userSettings= TDOUserSettings::getUserSettingsForUserid($session->getUserId());
                if($userSettings)
                {
                    if($userSettings->tagFilterWithAnd())
                        $tagsFilterSetting = true;
                }
            }
            $contextFilterString = NULL;
            if(isset($_COOKIE['TodoOnlineContextId']))
            {
                $contextFilterString = $_COOKIE['TodoOnlineContextId'];
            }
            $assignmentFilterString = NULL;
            if(isset($_COOKIE['TodoOnlineTaskAssignFilterId']))
            {
                $assignmentFilterString = $_COOKIE['TodoOnlineTaskAssignFilterId'];
            }
        }
		
		$listsJSON = array();
		
        $userInbox = TDOList::getUserInboxId($session->getUserId(), false);
        
        $visitedLists = array();
		foreach($lists as $list)
		{
            if(in_array($list->listId(), $visitedLists))
            {
                continue;
            }
            $listPropertyArray = $list->getPropertiesArrayWithUserSettings($session->getUserId());

			if(TDOList::isSharedList($list->listId()))
				$listPropertyArray['shared'] = true;
			else
				$listPropertyArray['shared'] = false;
            
            if($getCounts)
            {
                $taskCount = TDOTask::taskCountForList($list->listId(), $session->getUserId(), false, false, $contextFilterString, $tagsFilter, $tagsFilterSetting, $assignmentFilterString);
                $listPropertyArray['taskcount'] = $taskCount;
                $overdueCount = TDOTask::taskCountForList($list->listId(), $session->getUserId(), false, true, $contextFilterString, $tagsFilter, $tagsFilterSetting, $assignmentFilterString);
                $listPropertyArray['overduecount'] = $overdueCount;
            }
            
			if ($list->listId() == $userInbox)
			{
                $listPropertyArray['inbox'] = true;
				//make sure the inbox list is the first list
				$inboxArray = array($listPropertyArray);
				array_splice($listsJSON, 0, 0 ,$inboxArray);
			}
			else
				array_push($listsJSON, $listPropertyArray);
            
            $visitedLists[] = $list->listId();
                
		}	
		
		$response = array();
		$response['success'] = true;
		$response['lists'] = $listsJSON;
        $response['default_lists_color'] = array();
        $focusListSettings = TDOListSettings::getListSettingsForUser(FOCUS_LIST_ID, $session->getUserId());
        $starredListSettings = TDOListSettings::getListSettingsForUser(STARRED_LIST_ID, $session->getUserId());
        $allListSettings = TDOListSettings::getListSettingsForUser(ALL_LIST_ID, $session->getUserId());

        $response['default_lists_color'] = array(
            'focus' => ($focusListSettings) ? $focusListSettings->color() : '255, 152, 31',
            'all' => ($allListSettings) ? $allListSettings->color() : '73, 136, 251',
            'starred' => ($starredListSettings) ? $starredListSettings->color() : '255, 246, 134'
        );
        unset($focusListSettings, $starredListSettings, $allListSettings);


        if($getCounts)
        {
            $response['allcount'] = TDOTask::taskCountForList('all', $session->getUserId(), false, false, $contextFilterString, $tagsFilter, $tagsFilterSetting, $assignmentFilterString);
            $response['focuscount'] = TDOTask::taskCountForList('focus', $session->getUserId(), false, false, $contextFilterString, $tagsFilter, $tagsFilterSetting, $assignmentFilterString);
            $response['starredcount'] = TDOTask::taskCountForList('starred', $session->getUserId(), false, false, $contextFilterString, $tagsFilter, $tagsFilterSetting, $assignmentFilterString);
            
            $response['overdueallcount'] = TDOTask::taskCountForList('all', $session->getUserId(), false, true, $contextFilterString, $tagsFilter, $tagsFilterSetting, $assignmentFilterString);
            $response['overduefocuscount'] = TDOTask::taskCountForList('focus', $session->getUserId(), false, true, $contextFilterString, $tagsFilter, $tagsFilterSetting, $assignmentFilterString);
            $response['overduestarredcount'] = TDOTask::taskCountForList('starred', $session->getUserId(), false, true, $contextFilterString, $tagsFilter, $tagsFilterSetting, $assignmentFilterString);
        }
					
		return json_encode($response);	
	}
	
	//returnContexts function
	//purpose: returns a json string of the context names and their ID's for the current user
	function returnContexts()
	{
		$session = TDOSession::getInstance();
		$contexts = TDOContext::getContextsForUser($session->getUserId());
		
		$contextsJSON = array();
		
		foreach($contexts as $context)
		{
			$singleContext = array();
			$singleContext['name'] = $context->getName();
			$singleContext['id'] = $context->getContextid();

			array_push($contextsJSON, $singleContext);
		}	
				
		$response = array();
		$response['success'] = true;
		$response['contexts'] = $contextsJSON;
					
		return json_encode($response);	
	}
    
    //returnTagsfunction
	//purpose: returns a json string of the tag names and their ID's for the current user
    function returnTags()
    {
		$session = TDOSession::getInstance();
        $tags = TDOTag::getTagsForUser($session->getUserId());
        
        $tagsJSON = array();
        foreach($tags as $tag)
        {
            $tagJSON = $tag->getJSON();
            $tagJSON['name'] = htmlspecialchars($tagJSON['name']);
            array_push($tagsJSON, $tagJSON);
        }
        
        $response = array();
		$response['success'] = true;
		$response['tags'] = $tagsJSON;
        
        $tagsFilterSetting = TDOUserSettings::isUserTagFilterSettingAnd($session->getUserId());
        if($tagsFilterSetting)
            $response['filterByAnd'] = true;
        else
            $response['filterByAnd'] = false;
					
		return json_encode($response);
    }
    
    //returns the the tags for the current user with an extra parameter 'selected' that is
    //set to true if the tag belongs to the given task
    function returnTagsForTask()
    {
        if(!isset($_POST['taskid']))
        {
            error_log("getControlContent:tags called with missing parameter: taskid");
            return json_encode(array(
                'success' => FALSE,
                'error' => _('missing parameter'),
            ));
        }
        
        $taskid = $_POST['taskid'];
        $listid = TDOTask::getListIdForTaskId($taskid);
        
        if(empty($listid))
        {
            error_log("getControlContent:tags could not find list for task: $taskid");
            return json_encode(array(
                'success' => FALSE,
                'error' => _('missing list for task'),
            ));
        }
        
        $session = TDOSession::getInstance();
        if(TDOList::userCanViewList($listid, $session->getUserId()))
        {
           $userTags = TDOTag::getTagsForUser($session->getUserId());
           $taskTags = TDOTag::getTagsForTask($taskid);
           $tagsJSON = array();
           foreach($userTags as $tag)
           {   
                $tagJSON = $tag->getJSON();
                if(in_array($tag, $taskTags))
                    $tagJSON['selected'] = true;
                else
                    $tagJSON['selected'] = false;
                
               array_push($tagsJSON, $tagJSON);
           }
           return json_encode($tagsJSON);
        }
        else
        {
            error_log("getControlContent:tags called with invalid permissions");
            return json_encode(array(
                'success' => FALSE,
                'error' => _('invalid permissions'),
            ));
        }
        
    }
?>
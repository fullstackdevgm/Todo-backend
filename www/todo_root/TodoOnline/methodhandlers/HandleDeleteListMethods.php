<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	
    if($method == "getDeleteListInfo")
    {
        if(!isset($_POST['listid']))
        {
            error_log("HandleDeleteListMethods.php called missing parameter: listid");
            echo '{"success":false}';
            return;
        }
        
        $listid = $_POST['listid'];
        $userInbox = TDOList::getUserInboxId($session->getUserId(), false);
        if($listid == $userInbox)
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You may not delete your inbox'),
            ));
            return;
        }
        
        if(TDOList::userCanViewList($listid, $session->getUserId()) == false)
        {
            error_log("HandleDeleteListMethods.php failed with insufficient permissions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You do not have permission to view this list'),
            ));
            return;
        }
        
        $currentRole = TDOList::getRoleForUser($listid, $session->getUserId());
        $ownerCount = TDOList::getOwnerCountForList($listid);
        $peopleCount = TDOList::getPeopleCountForList($listid);
        
        if($currentRole === false || $ownerCount === false || $peopleCount === false)
        {
            echo '{"success":false}';
            return;
        }
        
        
        $jsonArray = array();
        $jsonArray['success'] = true;
        $jsonArray['role'] = $currentRole;
        $jsonArray['owner_count'] = $ownerCount;
        $jsonArray['people_count'] = $peopleCount;
        
        echo json_encode($jsonArray);
        
    }
	elseif($method == "deleteList")
	{
		if(!isset($_POST['listid']))
		{
			error_log("HandleListAction.php called missing parameter: listid");
			echo '{"success":false}';
			return;
		}
		$listid = $_POST['listid'];

        $userInbox = TDOList::getUserInboxId($session->getUserId(), false);
        if($listid == $userInbox)
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You may not delete your inbox'),
            ));
            return;
        }

		if(TDOList::getRoleForUser($listid, $session->getUserId()) != LIST_MEMBERSHIP_OWNER)
		{
			error_log("HandleListAction.php failed with insufficient permissions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Only an owner can delete this list'),
            ));
			return;
		}
        
//        // Lists should not be deleted if there are still tasks in them
//        $taskCount = TDOTask::taskCountForList($listid, $session->getUserId(), true);
//        if($taskCount > 0)
//        {
//			error_log("HandleListAction.php failed to delete a list with a non-empty list");
//			echo '{"success":false, "error":"A list must be empty before it can be deleted."}';
//			return;
//        }

        //Lists should not be deleted if there are other members
        $peopleCount = TDOList::getPeopleCountForList($listid);
        if($peopleCount != 1)
        {
            error_log("HandleDeleteListMethods.php failed to delete a list with other members");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('There are other members of this list'),
            ));
            return;
        }
        
		if(!TDOList::deleteList($listid))
		{
			error_log("Failed to delete list $listid");
			echo '{"success":false}';
			return;
		}

		$listName = TDOList::getNameForList($listid);
		TDOChangeLog::addChangeLog($listid, $session->getUserId(), $listid, $listName, ITEM_TYPE_LIST, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);

		echo '{"success":true}';
	}
	?>

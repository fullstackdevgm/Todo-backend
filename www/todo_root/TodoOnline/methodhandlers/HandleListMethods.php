<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');

    if($method == "updateList")
	{
		if(!isset($_POST['listid']))
		{
			error_log("HandleListAction.php called missing parameter: listid");
			echo '{"success":false}';
			return;
		}
		$listid = $_POST['listid'];

        $list = TDOList::getListForListid($listid);
		if(empty($list))
		{
			error_log("Method updateList unable to load list: $listid");
			echo '{"success":false}';
			return;
		}

		if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method updateList found that user cannot edit the list: ".$listid);
			echo '{"success":false}';
			return;
		}
		
		$teamID = TDOList::teamIDForList($listid);
		{
			if ($teamID)
			{
				// This list is a team-owned list and we need to check to see
				// if the team subscription is expired before allowing it to
				// be changed.
				$teamSubscriptionState = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
				if ($teamSubscriptionState == TEAM_SUBSCRIPTION_STATE_EXPIRED)
				{
					error_log("Method updateList found that team subscription is expired for team-owned list: " . $listid);
					echo '{"success":false}';
					return;
				}
			}
		}
		
        $haveUpdatedValues = false;
        $jsonChangedValues = '{';
		if(isset($_POST['listname'])) 
		{
            $userInbox = TDOList::getUserInboxId($session->getUserId(), false);
            if($userInbox == $listid)
            {
                error_log("Attempting to change inbox name");
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('You may not update the name of your inbox.'),
                ));
                return;
            }
        
            $listname = $_POST['listname'];
            if(strlen(trim($listname)) > 0 && strlen($listname) <= 36)
            {
                if($haveUpdatedValues == true)
                    $jsonChangedValues = $jsonChangedValues.', ';

                $jsonChangedValues = $jsonChangedValues.'"old-listName":"'.$list->name().'"';

                $list->setName($listname);

                $jsonChangedValues = $jsonChangedValues.', ';
                $jsonChangedValues = $jsonChangedValues.'"listName":"'.$list->name().'"';
                $haveUpdatedValues = true;
            }
		}
		if(isset($_POST['description'])) 
		{
            $description = $_POST['description'];
            if(strlen($description) <= 512)
            {
                if($haveUpdatedValues == true)
                    $jsonChangedValues = $jsonChangedValues.', ';

                $jsonChangedValues = $jsonChangedValues.'"old-description":"'.$list->description().'"';

                $list->setDescription($description);

                $jsonChangedValues = $jsonChangedValues.', ';
                $jsonChangedValues = $jsonChangedValues.'"description":"'.$list->description().'"';
                $haveUpdatedValues = true;
            }
		}
		
		if(!$haveUpdatedValues)
		{
			error_log("Method updateList was called with no values to update");
			echo '{"success":false}';
			return;
		}
		
		if($list->updateList($session->getUserId()))
		{
			echo '{"success":true}';

			$jsonChangedValues = $jsonChangedValues."}";
            TDOChangeLog::addChangeLog($listid, $session->getUserId(), $listid, $list->name(), ITEM_TYPE_LIST, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonChangedValues);
		}
		else
		{
			error_log("Method updateList failed to update list: ".$_POST['listid']);	
			echo '{"success":false}';
		}

	}
	elseif($method == "addList")
	{
		if(!$session->isLoggedIn())
		{
			error_log("HandleListAction.php called without a valid session");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Invalid session'),
            ));
			return;
		}
		
		if(!isset($_POST['listName']))
		{
			error_log("HandleaddList.php missing parameter: listName");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Missing parameter:listName'),
            ));
			return;
		}
		
		$result = false;
		
		$userid = $session->getUserId();
		
		$list = new TDOList();
		$list->setName($_POST['listName']);
		$list->setCreator($userid);
		
		$result = $list->addList($userid);
		
		if($result == true)
		{
			TDOChangeLog::addChangeLog($list->listId(), $session->getUserId(), $list->listId(), $list->name(), ITEM_TYPE_LIST, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
			
			echo '{"success":true}';
		}
		else
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('Creation of list failed'),
        ));
	}

?>

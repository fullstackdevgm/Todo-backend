<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	   
if($method == "getChangeLogForChangeId")
{
    if(!isset($_POST['changeid']))
    {
        error_log("getChangeLogForChangeId called missing parameter: changeid");
        echo '{"success":false}';
        return;
    }

    $change = TDOChangeLog::getChangeForChangeId($changeid);
    if($change == false)
    {
        echo '{"success":false}';
        return;
    }
    
    if($change->listId() == NULL || TDOList::userCanViewList($change->listId(), $session->getUserId()) == false)
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('You do not have permission to view this item'),
        ));
        return;
    }
    
    $jsonArray = array();
    $jsonArray['success'] = true;
    $jsonArray['change'] = $change->getPropertiesArray();
    
    echo json_encode($jsonArray);
    return;
    
}

//The parameters are limit, lastchangetimestamp, lastchangeid where limit is the number of changes you want,
//lastchangetimestamp is the timestamp on the last change returned to you from the server, and
//lastchangeid is the id of the last change returned to you by the server

elseif($method == "getPagedChangeLogForUser")
{
    $limit = 10;
    
    if(isset($_POST['limit']))
        $limit = $_POST['limit'];
        
    $lastChangeTimestamp = 0;
    if(isset($_POST['lastchangetimestamp']))
        $lastChangeTimestamp = $_POST['lastchangetimestamp'];
        
    $lastChangeId = NULL;
    if(isset($_POST['lastchangeid']))
        $lastChangeId = $_POST['lastchangeid'];
        
    $changes = TDOChangeLog::getAllChangesForUser($session->getUserId(), $limit, $lastChangeTimestamp, $lastChangeId);

    if($changes === false)
    {
        echo '{"success":false}';
        return;
    }
    
    $jsonChanges = array();
    foreach($changes as $change)
    {
        $jsonChange = $change->getPropertiesArray();
        $jsonChanges[] = $jsonChange;
    }
    
    $jsonResponse = array();
    $jsonResponse['success'] = true;
    $jsonResponse['changes'] = $jsonChanges;
    
    echo json_encode($jsonResponse);
    return;
}

//The parameters are listid, limit, filterUser, itemtypes  lastchangetimestamp, lastchangeid where
//limit is the number of changes you want,
//filterUser is set if you want to filter out the current user's changes and unset otherwise,
//itemtypes is a comma separated list of the item types you want returned (all types are returned if this is not set),
//lastchangetimestamp is the timestamp on the last change returned to you from the server, and
//lastchangeid is the id of the last change returned to you by the server
elseif($method == "getPagedChangeLogForList")
{
    if(!isset($_POST['listid']))
    {
        error_log("getPagedChangeLogForList called missing parameter: listid");
        echo '{"success":false}';
        return;
    }
    
    $listid = $_POST['listid'];
    
    if(TDOList::userCanViewList($listid, $session->getUserId()) == false)
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('You do not have permission to view changes for this list'),
        ));
        return;
    }

    $limit = 10;
    
    if(isset($_POST['limit']))
        $limit = $_POST['limit'];
        
    $lastChangeTimestamp = 0;
    if(isset($_POST['lastchangetimestamp']))
        $lastChangeTimestamp = $_POST['lastchangetimestamp'];
        
    $lastChangeId = NULL;
    if(isset($_POST['lastchangeid']))
        $lastChangeId = $_POST['lastchangeid'];
        
    $itemTypes = NULL;
	//Comma separated list of item types
	if(isset($_POST['itemtypes']))
    {
        $typeString = $_POST['itemtypes'];
        $itemTypes = explode(",", $typeString);
	}
    
    if(isset($_POST['filterUser']) )
		$filterUser = true;
	else
		$filterUser = false;
        
    $changes = TDOChangeLog::getChangesForList($listid, $session->getUserId(), $limit, $lastChangeTimestamp, $lastChangeId, $itemTypes, !$filterUser);

    if($changes === false)
    {
        echo '{"success":false}';
        return;
    }
    
    $jsonChanges = array();
    foreach($changes as $change)
    {
        $jsonChange = $change->getPropertiesArray();
        $jsonChanges[] = $jsonChange;
    }
    
    $jsonResponse = array();
    $jsonResponse['success'] = true;
    $jsonResponse['changes'] = $jsonChanges;
    
    echo json_encode($jsonResponse);
    return;
}
	
?>
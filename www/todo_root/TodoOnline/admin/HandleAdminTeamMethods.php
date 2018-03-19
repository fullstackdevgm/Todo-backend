<?php

include_once('TodoOnline/base_sdk.php');


if ($method == "searchTeams")
{
    if(!isset($_POST['searchString']))
    {
        error_log("admin method searchTeams called with no search string");
        echo '{"success":false}';
        return;
    }
    
    if(isset($_POST['limit']))
        $limit = $_POST['limit'];
    else
        $limit = 10;
        
    if(isset($_POST['offset']))
        $offset = $_POST['offset'];
    else
        $offset = 0;
    
    $searchString = $_POST['searchString'];
	
	$teams = TDOTeamAccount::getTeamsForSearchString($searchString, $limit, $offset);
    
    if($teams === false)
    {
        error_log("getTeamsForSearchString failed");
        echo '{"success":false}';
        return;
    }
    
    $jsonResponseArray = array();
		
    $teamsArray = array();
    
    foreach($teams as $team)
    {
		$teamProperties = $team->getPropertiesArray();
        $teamsArray[] = $teamProperties;
    }
    
    $jsonResponseArray['success'] = true;
	
    $jsonResponseArray['teams'] = $teamsArray;

    
    $jsonResponse = json_encode($jsonResponseArray);
    //error_log("jsonResponse we're sending is: ". $jsonResponse);
    echo $jsonResponse;

    
}
else if ($method == "getTeamInfo")
{
    if(!isset($_POST['teamid']))
    {
        error_log("admin method getTeamInfo called with no teamid");
        echo '{"success":false}';
        return;
    }
    
    $teamid = $_POST['teamid'];
	
	$team = TDOTeamAccount::getTeamForTeamID($teamid);
    if(!$team)
    {
        error_log("getTeamInfo unable to find team for teamid: " . $teamid);
        echo '{"success":false}';
        return;
    }
	
    $jsonResponseArray = array();
    $jsonResponseArray['success'] = true;
    
	// Basic team object properties
    $teamProperties = $team->getPropertiesArray();
    $jsonResponseArray['team'] = $teamProperties;	
	
	// Purchase history
	$purchaseHistory = TDOTeamAccount::getTeamPurchaseHistory($teamid);
	if ($purchaseHistory)
	{
		$jsonResponseArray['purchaseHistory'] = $purchaseHistory;
	}
	
	// Admins
	$teamAdminIDs = TDOTeamAccount::getAdminUserIDsForTeam($teamid);
	if ($teamAdminIDs)
	{
		$admins = array();
		foreach ($teamAdminIDs as $adminID)
		{
			$displayName = TDOUser::displayNameForUserId($adminID);
			$email = TDOUser::usernameForUserId($adminID);
			$admins[] = array(
							  "name" => $displayName,
							  "username" => $email
							  );
		}
		$jsonResponseArray['admins'] = $admins;
	}
	
	// Members
	$teamMemberIDs = TDOTeamAccount::getUserIDsForTeam($teamid);
	if ($teamMemberIDs)
	{
		$members = array();
		foreach ($teamMemberIDs as $memberID)
		{
			$displayName = TDOUser::displayNameForUserId($memberID);
			$email = TDOUser::usernameForUserId($memberID);
			$members[] = array(
							   "name" => $displayName,
							   "username" => $email
							   );
		}
		$jsonResponseArray['members'] = $members;
	}
	
	// Account log?
	
    
    echo json_encode($jsonResponseArray);
}
else if ($method == "adjustTeamExpirationDate")
	{
		if (!isset($_POST['teamid']))
		{
			error_log("admin method adjustTeamExpirationDate called with no teamid");
			echo '{"success":false,"error":"Missing parameter: teamid"}';
			return;
		}
		$teamid = $_POST['teamid'];
		
		if (!isset($_POST['newExpirationTimestamp']))
		{
			error_log("admin method adjustTeamExpirationDate called with no newExpirationTimestamp");
			echo '{"success":false,"error":"Missing parameter: newExpirationTimestamp"}';
			return;
		}
		$newExpirationTimestamp = $_POST['newExpirationTimestamp'];
		
		if (!isset($_POST['note']))
		{
			error_log("admin method adjustTeamExpirationDate called with no note");
			echo '{"success":false,"error":"Missing parameter: note"}';
			return;
		}
		$note = trim($_POST['note']);
		if (strlen($note) == 0)
		{
			error_log("admin method adjustTeamExpirationDate called with empty note");
			echo '{"success":false,"error":"Empty note"}';
			return;
		}
		
		$teamAccount = TDOTeamAccount::getTeamForTeamID($teamid);
		if (!$teamAccount)
		{
			error_log("admin method adjustTeamExpirationDate unable to locate team for team ($teamid)");
			echo '{"success":false,"error":"Cannot find the specified team account."}';
			return;
		}
		
		$billingFrequency = $teamAccount->getBillingFrequency();
		
		if (!TDOTeamAccount::updateTeamAccountWithNewExpirationDate($teamid, $newExpirationTimestamp, $billingFrequency))
		{
			error_log("admin method adjustTeamExpirationDate unable to update the team expiration date for team ($teamid)");
			echo '{"success":false,"error":"Unable to update the team expiration date."}';
			return;
		}
		
		// Notify all the team admins of the change
		$teamName = TDOTeamAccount::teamNameForTeamID($teamid);
		$teamAdminIDs = TDOTeamAccount::getAdminUserIDsForTeam($teamid);
		if ($teamAdminIDs)
		{
			foreach ($teamAdminIDs as $adminID)
			{
				$displayName = TDOUser::displayNameForUserId($adminID);
				$email = TDOUser::usernameForUserId($adminID);
				
				TDOMailer::notifyTeamAdminOfExpirationChange($email, $displayName, $teamName, $teamid, $newExpirationTimestamp);
			}
		}
				
		echo '{"success":true}';
	}

?>

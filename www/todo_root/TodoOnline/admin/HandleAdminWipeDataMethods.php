<?php

include_once('TodoOnline/base_sdk.php');

if($method == "wipeOutUserData")
{
    if (!isset($_POST['userid']))
    {
        error_log("admin method sendResetPasswordEmail called with no userid");
        echo '{"success":false,"error":"Missing parameter: userid"}';
        return;
    }
    $userid = $_POST['userid'];

	if (!TDOUser::wipeOutDataForUser($userid))
	{
		error_log("admin method wipeOutUserData failed");
		echo '{"success":false,"error":"Error deleting user data."}';
		return;
	}
	
	echo '{"success":true}';
}
else if($method == "wipeOutUserAccount")
{
    if (!isset($_POST['userid']))
    {
        error_log("admin method sendResetPasswordEmail called with no userid");
        echo '{"success":false,"error":"Missing parameter: userid"}';
        return;
    }
    $userid = $_POST['userid'];
	
	// Check to see if the user is a team admin or member of any team
	$administeredTeams = TDOTeamAccount::getTeamsForTeamAdmin($userid);
	if ($administeredTeams && count($administeredTeams) > 0)
	{
        echo '{"success":false,"error":"This user is an administrator of one or more teams."}';
        return;
	}
	
	$memberOfTeam = TDOTeamAccount::getTeamForTeamMember($userid);
	if ($memberOfTeam)
	{
        echo '{"success":false,"error":"This user is a member of a team: ' . $memberOfTeam->getTeamName() . '"}';
        return;
	}

    if(!TDOUser::permanentlyDeleteUserAccount($userid))
    {
        error_log("admin method wipeOutUserAccount failed");
		echo '{"success":false,"error":"Error deleting user account."}';
		return;
    }
    
	echo '{"success":true}';

}
else if($method == "enableUserReMigration")
{
    if (!isset($_POST['userid']))
    {
        error_log("admin method enableUserReMigration called with no userid");
        echo '{"success":false,"error":"Missing parameter: userid"}';
        return;
    }
    $userid = $_POST['userid'];
    
    if(!TDOLegacy::enableUserRecordForReMigration($userid))
    {
        error_log("admin method enableUserReMigration failed");
		echo '{"success":false,"error":"Error deleting user account."}';
		return;
    }
	
	// Log this in the user's actions
	$adminUserID = $session->getUserId();
	$changeDescription = "Enabled account for re-migration";
	if (!TDOUser::logUserAccountAction($userid, $adminUserID, USER_ACCOUNT_LOG_TYPE_ENABLE_REMIGRATE, $changeDescription))
	{
		error_log("admin method enableUserReMigration could not log the change of enabling an account ($userid) for re-migration by admin user: $adminUserID");
	}
    
	echo '{"success":true}';

}
    
?>

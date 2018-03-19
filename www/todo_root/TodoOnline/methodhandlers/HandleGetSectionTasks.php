<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');
	include_once('TodoOnline/content/TaskContentFunctions.php');	
	
	$showCompleted = false;
    $userFilter = NULL;
	$sectionID = NULL;
	$contextID = NULL;
    $tagsFilter = NULL;
	
	if(!isset($_POST['listid']))
	{
		error_log("HandleGetSectionTasks.php called and missing a required parameter: listid");
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('missing parameter'),
        ));
		return;
	}
    
    $listid = $_POST['listid'];
    
    if(TDOList::userCanViewList($listid, $session->getUserId()) == false)
    {
        setcookie('TodoOnlineListId',"all");
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('You do not have permission to view this list'),
        ));
        return;
    }

	if(isset($_POST['completed']))
	{
		$showCompleted = true;
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
        if ($listid === "focus" && $sectionID === 'completed_tasks_container' && $userSettings->focusShowCompletedDate()) {
                $showCompleted = true;
        }
    }
	echo json_encode(pagedTaskContentForSectionID($sectionID, $session->getUserId(), $listid, $contextID, $tagsFilter, $tagsFilterSetting, $userFilter, $showCompleted, $showSubtasksSetting));

?>
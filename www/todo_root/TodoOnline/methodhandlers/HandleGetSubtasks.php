<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');
	include_once('TodoOnline/content/TaskContentFunctions.php');	
	
    $userFilter = NULL;
	$contextid = NULL;
    $tagsFilter = NULL;
    $starredOnly = false;
	
	if(!isset($_POST['taskid']))
	{
		error_log("HandleGetSubtasks.php called and missing a required parameter: taskid");
		echo "missing parameter";
		return;
	}

    $taskid = $_POST['taskid'];
	
    if($method == "getSubtasks")
	{
        //Make sure the user has permissions for this task!!
        $listid = TDOTask::getListIdForTaskId($taskid);
        if(TDOList::userCanViewList($listid, $session->getUserId()) == false)
        {
            error_log("HandleGetSubtasks.php called with invalid permissions");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('You do not have permission to view subtasks for this task'),
            ));
            return;
        }
        
        //Bug 7427 - if the parent task matches the current filters, we don't filter children
        $parentUserId = NULL;
        $parentContextId = NULL;
        $parentTagString = NULL;
        $parentStarred = false;
        if(isset($_POST['parent_starred']))
        {
            if(intval($_POST['parent_starred']) > 0)
                $parentStarred = true;
        }
        if(isset($_POST['parent_context']))
        {
            $parentContextId = $_POST['parent_context'];
        }
        if(isset($_POST['parent_tags']))
        {
            $parentTagString = $_POST['parent_tags'];
        }
        if(isset($_POST['parent_assigned_user']))
        {
            $parentUserId = $_POST['parent_assigned_user'];
        }
        
    
        if(isset($_COOKIE['TodoOnlineTaskAssignFilterId']))
        {
            $userFilter = $_COOKIE['TodoOnlineTaskAssignFilterId'];
            
            //Bug 7427 - if the parent task matches the current filter, don't filter children,
            //unless we're looking for unassigned tasks
            if($userFilter == $parentUserId)
                $userFilter = NULL;
        }
        if(isset($_COOKIE['TodoOnlineContextId']))
        {
            $contextid = $_COOKIE['TodoOnlineContextId'];
            
            //Bug 7427 - if the parent task matches the current filter, don't filter children,
            //unless we're looking for tasks with no context
            if($contextid == $parentContextId)
                $contextid = NULL;
            
        }

        if(isset($_POST['starred_only']))
        {
            $starredOnly = $_POST['starred_only'];
            
            //Bug 7427 - if the parent task matches the current filter, don't filter children
            if($parentStarred)
                $starredOnly = 0;
        }
        
        $tagsFilterSetting = false;
        $userSettings= TDOUserSettings::getUserSettingsForUserid($session->getUserId());
        if($userSettings)
        {
            if($userSettings->tagFilterWithAnd())
                $tagsFilterSetting = true;
        }

        if(isset($_COOKIE['TodoOnlineTagId']))
        {
            $tagsFilterString = $_COOKIE['TodoOnlineTagId'];
            if(strlen($tagsFilterString) > 0)
                $tagsFilter = explode(",", $tagsFilterString);
            
            //Bug 7427 - if the parent task matches the current filter, don't filter children,
            //unless we're looking for tasks with no tags
            if(!empty($parentTagString))
            {
                if(!empty($tagsFilter) && in_array("all", $tagsFilter) == false && in_array("notag", $tagsFilter) == false)
                {
                    $parentTags = explode(",", $parentTagString);
                
                    if($tagsFilterSetting)
                    {
                        //make sure the parent matches all of the tags
                        $matched = true;
                        foreach($tagsFilter as $tagId)
                        {
                            $tag = TDOTag::getTagForTagId($tagId);
                            if(!empty($tag) && in_array($tag->getName(), $parentTags) == false)
                            {
                                $matched = false;
                                break;
                            }
                        }
                        if($matched)
                        {
                            //the parent matches all tags, so don't filter children
                            $tagsFilter = NULL;
                        }
                    }
                    else
                    {
                        //make sure the parent matches at least one tag
                        foreach($tagsFilter as $tagId)
                        {
                            $tag = TDOTag::getTagForTagId($tagId);
                            if(!empty($tag) && in_array($tag->getName(), $parentTags) == true)
                            {
                                //The parent matches at least one tag, so don't filter children
                                $tagsFilter = NULL;
                                break;
                            }
                        }
                    }
                }
            }
            
        }

        pagedSubtaskContentForTaskID($taskid, $session->getUserId(), $contextid, $tagsFilter, $tagsFilterSetting, $userFilter, $starredOnly);
    }
    else
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('Invalid Method was requested.'),
        ));
    }
	
?>
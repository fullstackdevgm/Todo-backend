<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	
	if(isset($_SERVER['HTTP_REFERER']))
	{
		$referrer = $_SERVER['HTTP_REFERER'];
	}
	else
	{
		$referrer = ".";
	}	
	
	if(!$session->isLoggedIn())
	{
		error_log("Method called without a valid session");
		
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('authentication'),
        ));
		return;
	}
	
	if($method == "addTag")
	{
		if(!isset($_POST['tagName']))
		{
			error_log("Method addTag missing parameter: tagName");
			echo '{"success":false}';
			return;
		}
        if(!isset($_POST['taskid']))
        {
            error_log("Method addTag missing parameter: taskid");
			echo '{"success":false}';
			return;
        }
        $name = $_POST['tagName'];
        $taskid = $_POST['taskid'];
        
        $listid = TDOTask::getListIdForTaskId($taskid);
        if(empty($listid))
        {
            error_log("Method addTag could not find list for task: ".$taskid);
			echo '{"success":false}';
			return;
        }
        if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method addTag found that user cannot edit the list: ".$listid);
			echo '{"success":false}';
			return;
		}
        if(TDOTag::taskContainsTagName($taskid, $name))
        {
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('duplicateName'),
            ));
            return;
        }
		if($tagid = TDOTag::addTagNameToTask($name, $taskid))
		{
            echo '{"success":true, "tagid":"'.$tagid.'"}';
		}
		else
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Creation of tag failed'),
            ));
		}	
	}
    elseif($method == "updateTagsForTask")
    {
        if(!isset($_POST['taskid']))
        {
            error_log("Method updateTagsForTask missing parameter: taskid");
			echo '{"success":false}';
			return;
        }
        
        $taskid = $_POST['taskid'];
        
        $listid = TDOTask::getListIdForTaskId($taskid);
        if(empty($listid))
        {
            error_log("Method updateTagsForTask could not find list for task: ".$taskid);
			echo '{"success":false}';
			return;
        }
        if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method updateTagsForTask found that user cannot edit the list: ".$listid);
			echo '{"success":false}';
			return;
		}

        TDOTag::removeAllTagsFromTask($taskid);
        
        if(isset($_POST['tags']))
        {
            $tagValues = explode(",", $_POST['tags']);
            
            foreach($tagValues as $tagValue)
            {
                TDOTag::addTagNameToTask($tagValue, $taskid);
            }
        }
        echo '{"success":true}';

    }
    elseif($method == "removeTagFromTask")
    {
        if(!isset($_POST['tagid']))
        {
            error_log("Method removeTagFromTask missing parameter: tagid");
            echo '{"success":false}';
            return;
        }
        if(!isset($_POST['taskid']))
        {
            error_log("Method removeTagFromTask missing parameter: taskid");
            echo '{"success":false}';
            return;
        }
        
        $tagid = $_POST['tagid'];
        $taskid = $_POST['taskid'];
        
        $listid = TDOTask::getListIdForTaskId($taskid);
        if(empty($listid))
        {
            error_log("Method removeTagFromTask could not find list for task: ".$taskid);
			echo '{"success":false}';
			return;
        }
        if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method removeTagFromTask found that user cannot edit the list: ".$listid);
			echo '{"success":false}';
			return;
		}
        
        if(TDOTag::removeTagFromTask($tagid, $taskid))
		{
            echo '{"success":true}';
		}
		else
		{
			echo '{"success":false}';
		}
        
    }
    elseif($method == "renameTag")
    {
        if(!isset($_POST['tagid']))
        {
            error_log("Method renameTag missing parameter: tagid");
            echo '{"success":false}';
            return;
        }
        if(!isset($_POST['name']))
        {
            error_log("Method renameTag missing parameter: name");
            echo '{"success":false}';
            return;
        }
        
        $name = $_POST['name'];
        $tagid = $_POST['tagid'];
        
        $newTagId = TDOTag::renameTagForUser($session->getUserId(), $tagid, $name);
        if($newTagId)
		{
            echo '{"success":true, "tagid":"'.$newTagId.'"}';
		}
		else
		{
			echo '{"success":false}';
		}
    }
    elseif($method == "deleteTag")
    {
        if(!isset($_POST['tagid']))
        {
            error_log("Method deleteTag missing parameter: tagid");
            echo '{"success":false}';
            return;
        }

        $tagid = $_POST['tagid'];
        
        if(TDOTag::deleteTagForUser($session->getUserId(), $tagid))
		{
            echo '{"success":true}';
		}
		else
		{
			echo '{"success":false}';
		}
    }

   
?>
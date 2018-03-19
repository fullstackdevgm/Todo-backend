<?php
//      TDOContext
//      Used to handle all user data

// include files
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');	

define('TAG_NAME_LENGTH', 72);

class TDOTag
{
	private $_name;
	private $_tagid;

	public function __construct()
	{
		$this->set_to_default();      
	}
    
	public function set_to_default()
	{
		// clears values without going to database
		// SimpleDB requires a value for every attribue...
		$this->_name = NULL;
		$this->_tagid = NULL;
	}
	

	public function addTag($link=NULL)
	{
		if($this->_name == NULL)
		{
			error_log("TDOTag::addTag failed because name was not set");
			return false;
        }

        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTag::addTag failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
		
		$this->_tagid = TDOUtil::uuid();
    
        $name = mb_strcut($this->_name, 0, TAG_NAME_LENGTH, 'UTF-8');
        $name = mysql_real_escape_string($name, $link);
		
		// Create the list
		$sql = "INSERT INTO tdo_tags (tagid, name) VALUES ('$this->_tagid', '$name')";
		$result = mysql_query($sql, $link);
		if(!$result)
		{
			error_log("TDOTag::addTag failed to add context with error :".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			return false;
		}
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
		return true;
	}
	
    public function getJSON()
    {
        $jsonArray = array();
        $jsonArray['name'] = $this->getName();
        $jsonArray['tagid'] = $this->getTagid();
        
        return $jsonArray;
    }
	
	// this method is to sort the results of the lists once we get them back
	public static function tagCompare($a, $b)
	{
		return strcasecmp($a->_name, $b->_name);
	}	
    	
	public static function getTagForTagId($tagid, $link=NULL)
    {
        if(!isset($tagid))
            return false;
            
        if(!$link)
        {
            $link = TDOUtil::getDBLink();        
            if(!$link)
            {
                error_log("TDOTag failed to get dblink");
                return false;
            }  
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }
        
        $tagid = mysql_real_escape_string($tagid, $link);
        
		$sql = "SELECT tagid,name FROM tdo_tags WHERE tagid='$tagid'";

        $response = mysql_query($sql, $link);
        if($response)
        {
            $row =  mysql_fetch_array($response);
            if($row)
            {
               $tag = TDOTag::tagFromRow($row);
				
                if($shouldCloseLink)
                    TDOUtil::closeDBLink($link);
                return $tag;
            }

        }
        else
            error_log("Unable to get tag: ".mysql_error());
        
        if($shouldCloseLink)
            TDOUtil::closeDBLink($link);

        return false;        
    }
    
    public static function getTagidForName($name, $link=NULL)
    {
        if(!isset($name))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTag failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $name = mb_strcut($name, 0, TAG_NAME_LENGTH, 'UTF-8');
        $name = mysql_real_escape_string($name, $link);
        
        $sql = "SELECT tagid FROM tdo_tags WHERE name='$name'";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
            $row = mysql_fetch_array($response);
            if($row)
            {
                if(isset($row['tagid']))
                {
                    $tagid = $row['tagid'];
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return $tagid;
                }
            }
        }
        else
            error_log("Unalbe to get tagid for name: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function addTagNameToTask($name, $taskid, $link=NULL)
    {
        if(empty($name) || empty($taskid))
            return false;
        
        $name = trim($name);
        if(strlen($name) == 0)
            return false;
            
        $tagid = TDOTag::getTagidForName($name, $link);
        
        if(empty($tagid))
        {
            $tag = new TDOTag();
            $tag->setName($name);
            if(!$tag->addTag($link))
                return false;
            $tagid = $tag->getTagid();
            
            if(empty($tagid))
                return false;
        }
        return TDOTag::addTagToTask($tagid, $taskid, $link);
    }
    
    public static function addTagToTask($tagid, $taskid, $link=NULL)
    {
        if(empty($tagid) || empty($taskid))
            return false;
        
        if(TDOTag::taskContainsTag($taskid, $tagid) == true)
            return true;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
                return false;
        }
        else
            $closeDBLink = false;
        
        $taskid = mysql_real_escape_string($taskid, $link);
        $escapedTagid = mysql_real_escape_string($tagid, $link);
        
        $sql = "INSERT INTO tdo_tag_assignments(taskid, tagid) VALUES ('$taskid', '$escapedTagid')";
        if(!mysql_query($sql, $link))
        {
            error_log("Unable to assign tag to task: ".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
            
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        
        //Update the mod date on the tasks
        TDOTask::updateTimestampForTask($taskid);
        
        return $tagid;
    }
    
    public static function taskContainsTagName($taskid, $name)
    {
        if(empty($taskid) || empty($name))
            return false;
            
        $tagid = TDOTag::getTagidForName($name);
        if(empty($tagid))
            return false;
        
        return TDOTag::taskContainsTag($taskid, $tagid);
    }

    public static function taskContainsTag($taskid, $tagid)
    {
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
    
        if(empty($taskid) || empty($tagid))
            return false;
        
        $tagid = mysql_real_escape_string($tagid, $link);
        $taskid = mysql_real_escape_string($taskid, $link);
            
        $sql = "SELECT COUNT(tagid) FROM tdo_tag_assignments WHERE taskid='$taskid' AND tagid='$tagid'";
        if($result = mysql_query($sql,$link))
        {
            if($row = mysql_fetch_array($result))
            {
                if(isset($row['0']) && $row['0'] > 0)
                {
                    TDOUtil::closeDBLink($link);
                    return true;
                }
            }
        }
        else
            error_log("taskContainsTag failed: ".mysql_error());
            
        TDOUtil::closeDBLink($link);
        return false;
        
    }

    public static function removeTagFromTask($tagid, $taskid)
    {
        if(empty($tagid) || empty($taskid))
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
            
        $taskid = mysql_real_escape_string($taskid, $link);
        $tagid = mysql_real_escape_string($tagid, $link);
        
        $sql = "DELETE FROM tdo_tag_assignments WHERE taskid='$taskid' AND tagid='$tagid'";
        if(!mysql_query($sql, $link))
        {
            error_log("Unable to remove tag from task: ".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        TDOUtil::closeDBLink($link);
        
        //Update the mod date on the tasks
        TDOTask::updateTimestampForTask($taskid);
        
        return true;
        
    }
    
    public static function removeAllTagsFromTask($taskid)
    {
        if(empty($taskid))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
        
        $taskid = mysql_real_escape_string($taskid, $link);
        
        $sql = "DELETE FROM tdo_tag_assignments WHERE taskid='$taskid'";
        if(!mysql_query($sql, $link))
        {
            error_log("Unable to remove tags from task: ".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        TDOUtil::closeDBLink($link);
        
        //Update the mod date on the tasks
        TDOTask::updateTimestampForTask($taskid);
        
        return true;
    }
    
    public static function getTasksForUserForTag($userid, $tagid)
    {
        if(empty($userid) || empty($tagid))
            return false;
    
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
        
        $userid = mysql_real_escape_string($userid, $link);
        $tagid = mysql_real_escape_string($tagid, $link);
        
        //Read all tasks having the old tag so we can change them to have the new tag
        $sql = "SELECT tdo_tag_assignments.taskid FROM tdo_tasks INNER JOIN tdo_list_memberships on tdo_tasks.listid = tdo_list_memberships.listid AND userid='$userid' INNER JOIN tdo_tag_assignments ON tdo_tasks.taskid=tdo_tag_assignments.taskid AND tagid='$tagid'";
        $sql .= " UNION SELECT tdo_tag_assignments.taskid FROM tdo_completed_tasks INNER JOIN tdo_list_memberships on tdo_completed_tasks.listid = tdo_list_memberships.listid AND userid='$userid' INNER JOIN tdo_tag_assignments ON tdo_completed_tasks.taskid=tdo_tag_assignments.taskid AND tagid='$tagid'";
     
        $taskids = array();
     
        $result = mysql_query($sql, $link);
        
        if($result)
        {
            while($row = mysql_fetch_array($result))
            {
                if(isset($row['taskid']))
                {
                    $taskids[] = $row['taskid'];
                }
            }
        }
        else
        {
            error_log("TDOTag getTasksForUserForTag failed with error: ".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        TDOUtil::closeDBLink($link);
        return $taskids;
        
    }

    public static function renameTagForUser($userid, $oldTagid, $newTagName)
    {
        if(empty($userid) || empty($oldTagid) || empty($newTagName))
            return false;
            
        $newTagName = trim($newTagName);
        if(strlen($newTagName) == 0)
            return false;
        
        $newTagid = TDOTag::getTagidForName($newTagName);
        if(empty($newTagid))
        {
            $tag = new TDOTag();
            $tag->setName($newTagName);
            if(!$tag->addTag())
                return false;
            $newTagid = $tag->getTagid();
            
            if(empty($newTagid))
                return false;
        }
        
        $tasksToChange = TDOTag::getTasksForUserForTag($userid, $oldTagid);
        
        if($tasksToChange === false)
        {
            error_log("renameTagForUser failed to get tasks for user for old tag");
            return false;
        }
        
        foreach($tasksToChange as $taskid)
        {
            TDOTag::removeTagFromTask($oldTagid, $taskid);
            TDOTag::addTagToTask($newTagid, $taskid);
            
            TDOTask::updateTimestampForTask($taskid);
        }
        
        //We changed this code to loop through the tasks one at a time instead of trying to update in bulk because that locks up the database
        //and also makes it more difficult to update the timestamps correctly
        
//        //Update the mod date on the tasks having the old tag, so we will sync the tag changes
//        $sql = "UPDATE tdo_tasks INNER JOIN tdo_list_memberships on tdo_tasks.listid = tdo_list_memberships.listid AND userid='$userid' INNER JOIN tdo_tag_assignments ON tdo_tasks.taskid=tdo_tag_assignments.taskid AND tagid='$oldTagid' SET timestamp=".time();
//        if(!mysql_query($sql, $link))
//            error_log("Unable to update task timestamp when modifying tags ".mysql_error());
//        
//        $sql = "UPDATE tdo_completed_tasks INNER JOIN tdo_list_memberships on tdo_completed_tasks.listid = tdo_list_memberships.listid AND userid='$userid' INNER JOIN tdo_tag_assignments ON tdo_completed_tasks.taskid=tdo_tag_assignments.taskid AND tagid='$oldTagid' SET timestamp=".time();
//        if(!mysql_query($sql, $link))
//            error_log("Unable to update task timestamp when modifying tags ".mysql_error());
//        
//        
//        $getUserTasksTableSQL = "SELECT taskid FROM tdo_tasks INNER JOIN tdo_list_memberships on tdo_tasks.listid = tdo_list_memberships.listid WHERE userid='$userid'";
//        $getUserTasksTableSQL .= " UNION SELECT taskid FROM tdo_completed_tasks INNER JOIN tdo_list_memberships on tdo_completed_tasks.listid = tdo_list_memberships.listid WHERE userid='$userid'";
//        
//        //UPDATE IGNORE makes it so duplicate entries will be ignored. This allows the user to rename a tag to the same name as an existing tag without
//        //causing errors. We just need to make sure to wipe out everything with the old tag id afterward
//        $sql = "UPDATE IGNORE tdo_tag_assignments SET tagid='$newTagid' WHERE taskid IN ($getUserTasksTableSQL) AND tagid='$oldTagid'";
//        if(!mysql_query($sql, $link))
//        {
//            error_log("Unable to rename tag: ".mysql_error());
//            TDOUtil::closeDBLink($link);
//            return false;
//        }
//        //this needs to be here to wipe out any remaining instances of the old tag that were ignored in the first call
//        if(!mysql_query("DELETE FROM tdo_tag_assignments WHERE taskid IN ($getUserTasksTableSQL) AND tagid='$oldTagid'", $link))
//        {
//            error_log("Unable to delete old tag: ".mysql_error());
//            TDOUtil::closeDBLink($link);
//            return false;
//        }
//    
//        TDOUtil::closeDBLink($link);


        return $newTagid;
    }
    
    public static function deleteTagForUser($userid, $tagid)
    {
        if(empty($userid) || empty($tagid))
            return false;
        
        $tasksToChange = TDOTag::getTasksForUserForTag($userid, $tagid);
        
        if($tasksToChange === false)
        {
            error_log("deleteTagForUser failed to get tasks for user for old tag");
            return false;
        }
        
        foreach($tasksToChange as $taskid)
        {
            TDOTag::removeTagFromTask($tagid, $taskid);
            TDOTask::updateTimestampForTask($taskid);
        }

        //We changed this code to loop through the tasks one at a time instead of trying to update in bulk because that locks up the database
        //and also makes it more difficult to update the timestamps correctly
        
//        $link = TDOUtil::getDBLink();
//        if(!$link)
//            return false;
//        
//        $userid = mysql_real_escape_string($userid, $link);
//        $tagid = mysql_real_escape_string($tagid, $link);
//
//        //Update the mod date on the tasks having the old tag, so we will sync the tag changes
//        $sql = "UPDATE tdo_tasks INNER JOIN tdo_list_memberships on tdo_tasks.listid = tdo_list_memberships.listid AND userid='$userid' INNER JOIN tdo_tag_assignments ON tdo_tasks.taskid=tdo_tag_assignments.taskid AND tagid='$tagid' SET timestamp=".time();
//        if(!mysql_query($sql, $link))
//            error_log("Unable to update task timestamp when modifying tags ".mysql_error());
//            
//        $sql = "UPDATE tdo_completed_tasks INNER JOIN tdo_list_memberships on tdo_completed_tasks.listid = tdo_list_memberships.listid AND userid='$userid' INNER JOIN tdo_tag_assignments ON tdo_completed_tasks.taskid=tdo_tag_assignments.taskid AND tagid='$tagid' SET timestamp=".time();
//        if(!mysql_query($sql, $link))
//            error_log("Unable to update task timestamp when modifying tags ".mysql_error());
//
//        $getUserTasksTableSQL = "SELECT taskid FROM tdo_tasks INNER JOIN tdo_list_memberships on tdo_tasks.listid = tdo_list_memberships.listid WHERE userid='$userid'";
//        $getUserTasksTableSQL .= " UNION SELECT taskid FROM tdo_completed_tasks INNER JOIN tdo_list_memberships ON tdo_completed_tasks.listid = tdo_list_memberships.listid WHERE userid='$userid'";
//        
//        $sql = "DELETE FROM tdo_tag_assignments WHERE taskid IN ($getUserTasksTableSQL) AND tagid='$tagid'";
//        if(!mysql_query($sql, $link))
//        {
//            error_log("Unable to delete tag: ".mysql_error());
//            TDOUtil::closeDBLink($link);
//            return false;
//        }
//        
//        TDOUtil::closeDBLink($link);

        return true;
    }


    public static function getTagsForTask($taskid, $link=NULL)
    {
        if(empty($taskid))
        {
            return false;
        }
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
                return false;
        }
        else
        {
            $closeDBLink = false;
        }
            
        $taskid = mysql_real_escape_string($taskid, $link);
        
        $sql = "SELECT tdo_tags.tagid, tdo_tags.name FROM (tdo_tags INNER JOIN tdo_tag_assignments ON tdo_tags.tagid = tdo_tag_assignments.tagid) WHERE taskid='$taskid' ORDER BY tdo_tags.name";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
            $tags = array();
            while($row = mysql_fetch_array($response))
            {
                $tag = TDOTag::tagFromRow($row);
                if($tag)
                    $tags[] = $tag;
            }
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return $tags;
        }
        else
            error_log("getTagsForTask returned error: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;

    }
    
    public static function getTagStringForTask($taskId, $link=NULL)
    {
        $tagArray = TDOTag::getTagsForTask($taskId, $link);
        $tagString = "";
        if(!empty($tagArray))
        {
            foreach($tagArray as $tag)
            {
                if(strlen($tagString) > 0)
                    $tagString .= ", ";
                    
                $tagString .= $tag->getName();
            }
        }
        return $tagString;
    }

    public static function getTagCountForTask($taskid, $link=NULL)
    {
        if(empty($taskid))
        {
            return false;
        }
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
                return false;
        }
        else
            $closeDBLink = false;
        
        $taskid = mysql_real_escape_string($taskid, $link);
        
        $sql = "SELECT COUNT(tdo_tags.tagid) FROM (tdo_tags INNER JOIN tdo_tag_assignments ON tdo_tags.tagid = tdo_tag_assignments.tagid) WHERE taskid='$taskid'";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
            $row = mysql_fetch_array($response);
            if($row)
            {
                if(isset($row['0']))
                {
                    $taskCount = $row['0'];
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return $taskCount;
                }
            }
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
            
        }
        else
            error_log("getTagCountForTask returned error: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function getTagsForUser($userid)
    {
        if(empty($userid))
            return false;
            
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
            
        $userid = mysql_real_escape_string($userid, $link);
        
        $getUserTasksTableSQL = "((SELECT taskid FROM tdo_tasks INNER JOIN tdo_list_memberships on tdo_tasks.listid = tdo_list_memberships.listid WHERE userid='$userid') UNION (SELECT taskid FROM tdo_completed_tasks INNER JOIN tdo_list_memberships ON tdo_completed_tasks.listid = tdo_list_memberships.listid WHERE userid='$userid')) AS usertasks";
        $getUserTagsTableSQL = "tdo_tag_assignments INNER JOIN ($getUserTasksTableSQL) ON tdo_tag_assignments.taskid = usertasks.taskid";
        
        
        $sql = "SELECT tdo_tags.tagid, tdo_tags.name FROM (tdo_tags INNER JOIN ($getUserTagsTableSQL) ON tdo_tags.tagid = tdo_tag_assignments.tagid) ORDER BY tdo_tags.name";
        
        $response = mysql_query($sql, $link);
        if($response)
        {
            $tags = array();
            while($row = mysql_fetch_array($response))
            {
                $tag = TDOTag::tagFromRow($row);
                if($tag)
                    $tags[$row['tagid']] = $tag;
            }
            
            TDOUtil::closeDBLink($link);
            return array_values($tags);
        }
        else
            error_log("getTagsForUser returned error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;

        
    }
    
    public static function tagFromRow($row)
    {
        if($row)
        {
            $tag = new TDOTag();
            if(isset($row['tagid']))
                $tag->setTagid($row['tagid']);
            if(isset($row['name']))
                $tag->setName($row['name']);
            
            return $tag;
        }
        
        return NULL;
    }

	public function getName()
	{
		return $this->_name;
	}
	public function setName($val)
	{
		$this->_name = TDOUtil::ensureUTF8(strval($val));
	}
	
	public function getTagid()
	{
		return $this->_tagid;
	}
	public function setTagid($val)
	{
		$this->_tagid = $val;
	}
	
}


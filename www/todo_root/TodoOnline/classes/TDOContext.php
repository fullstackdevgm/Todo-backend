<?php
//      TDOContext
//      Used to handle all user data

// include files
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');	

define ('CONTEXT_NAME_LENGTH', 72);

class TDOContext
{
	private $_name;
	private $_contextid;
	private $_userid;
    private $_deleted;
	private $_lastmodified;	

	public function __construct()
	{
		$this->set_to_default();      
	}
    
	public function set_to_default()
	{
		// clears values without going to database
		// SimpleDB requires a value for every attribue...
		$this->_name = NULL;
		$this->_contextid = NULL;
		$this->_userid = NULL;
        $this->_deleted = 0;
		$this->_lastmodified = 0;	
	}
    
    // This hasn't been converted to a TDODBObject so just make an array and return the properties
    public function getPropertiesArray()
    {
        $propertiesArray = array();
        
        $propertiesArray['contextid'] = $this->getContextid();
        $propertiesArray['name'] = $this->getName();
        $propertiesArray['deleted'] = $this->isDeleted();
        $propertiesArray['context_timestamp'] = $this->getLastModified();

        return $propertiesArray;
    }
    
	
	public static function deleteContext($contextid, $link=NULL)
	{
        if(!isset($contextid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOContext failed to get dblink");
                return false;
            }
            //Do all of this in a transaction so we won't end up with a partially deleted list
            if(!mysql_query("START TRANSACTION", $link))
            {
                error_log("TDOContext::Couldn't start transaction".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        else
            $closeDBLink = false;
		

        $contextid = mysql_real_escape_string($contextid, $link);
		$timestamp = time();        
		
        // Delete Context (mark as deleted)
        if(!mysql_query("UPDATE tdo_contexts SET deleted=1, context_timestamp='$timestamp' WHERE contextid='$contextid'", $link))
        {
            error_log("TDOContext::Could not delete context, rolling back".mysql_error());
            if($closeDBLink)
            {
                mysql_query("ROLLBACK");
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
		
        // Remove the context associated with a task and update the timestamp
		// don't delete it because we need this when we sync
        if(!mysql_query("UPDATE tdo_context_assignments set contextid=NULL, context_assignment_timestamp='$timestamp' where contextid='$contextid'", $link))
        {
            error_log("Unable to remove context assignment from tasks: ".mysql_error());
            if($closeDBLink)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
		
        if($closeDBLink)
        {
            if(!mysql_query("COMMIT", $link))
            {
                error_log("TDOContext::Couldn't commit transaction".mysql_error());
                mysql_query("ROLLBACK");
                TDOUtil::closeDBLink($link);
                return false;
            }
            else
                TDOUtil::closeDBLink($link);
        }
        
        return true;
	}
    
    public static function permanentlyDeleteContextsForUser($userid, $link=NULL)
    {
        if(empty($userid))
            return false;
        
        if(empty($link))
        {
            $closeTransaction = true;
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOContext failed to get db link");
                return false;
            }
            
            if(!mysql_query("START TRANSACTION", $link))
            {
                error_log("TDOContext failed to start transaction");
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        else
            $closeTransaction = false;
        
        $escapedUserId = mysql_real_escape_string($userid, $link);
        
        $sql = "DELETE FROM tdo_contexts WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteContextsForUser failed deleting from tdo_contexts with error: ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
        
        $sql = "DELETE FROM tdo_context_assignments WHERE userid='$escapedUserId'";
        if(!mysql_query($sql, $link))
        {
            error_log("permanentlyDeleteContextsForUser failed deling from tdo_context_assignments with error: ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }
        
        if($closeTransaction)
        {
            if(!mysql_query("COMMIT", $link))
            {
                error_log("permanentlyDeleteContextsForUser failed to commit transaction: ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
            else
                TDOUtil::closeDBLink($link);
        }
        
        return true;
    }

	public function addContext($link=NULL)
	{
		if($this->_name == NULL)
		{
			error_log("TDOContext::addContext failed because name was not set");
			return false;
		}
		if($this->_userid == NULL)
		{
			error_log("TDOContext::addContext failed because userid was not set");
			return false;
		}

        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOContext::addContext failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
		
        // CRG - added for legacy migration
        if(empty($this->_contextid))
        {
            $this->_contextid = TDOUtil::uuid();
        }
        
        $name = mb_strcut($this->_name, 0, CONTEXT_NAME_LENGTH, 'UTF-8');
        $name = mysql_real_escape_string($name, $link);
        $deleted = intval($this->_deleted);
		
		$timestamp = time();
		
		// Create the list
		$sql = "INSERT INTO tdo_contexts (contextid, userid, name, deleted, context_timestamp) VALUES ('$this->_contextid', '$this->_userid', '$name', $deleted, '$timestamp')";
		$result = mysql_query($sql, $link);
		if(!$result)
		{
			error_log("TDOContext::addContext failed to add context with error :".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			return false;
		}

        if($closeDBLink)
            TDOUtil::closeDBLink($link);
		return true;
	}
	
	
	public function updateContext($link=NULL)
	{
		if(isset($this->_contextid) == false)
		{
			error_log("TDOContext::updateContext() failed: contextid was not set");
			return false;
		}
		
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOContext::updateContext() failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
		$updateString = "";
		
		if(isset($this->_name))
        {
            $name = mb_strcut($this->_name, 0, CONTEXT_NAME_LENGTH, 'UTF-8');
			$name = mysql_real_escape_string($name, $link);
			
			$updateString .= " name='$name'";
        }
		
        if(isset($this->_deleted) )
        {
			$deleted = intval($this->_deleted);
			
			if (strlen($updateString) > 0)
				$updateString .= ", ";
			
			$updateString .= " deleted=$deleted";
        }
        
        if(strlen($updateString) == 0)
		{
            error_log("TDOContext::updateContext() nothing to update");
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
        
        if (strlen($updateString) > 0)
            $updateString .= ", ";
        
        $updateString .= " context_timestamp='" . time() . "' ";
		
		$contextid = $this->_contextid;
		
        $sql = "UPDATE tdo_contexts SET " . $updateString . " WHERE contextid='$contextid'";
		
        $response = mysql_query($sql, $link);
        if($response)
        {
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return true;
        }
        else
        {
            error_log("Unable to update context $contextid: ".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }

	}

	
	// this method is to sort the results of the lists once we get them back
	public static function contextCompare($a, $b)
	{
		return strcasecmp($a->_name, $b->_name);
	}	
	
	public static function getContextsForUser($userid, $includeDeleted=false)
	{
        if(!isset($userid))
            return false;
            
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOContext failed to get dblink");
			return false;
		}  
        
        $escapedUserid = mysql_real_escape_string($userid, $link);
        
        $sql = "SELECT contextid FROM tdo_contexts WHERE userid='$escapedUserid'";
        $result = mysql_query($sql);
        if($result)
        {
            $contexts = array();
            while($row = mysql_fetch_array($result))
            {
                if(isset($row['contextid']))
                {  
                    $contextid = $row['contextid'];
                    $context = TDOContext::getContextForContextid($contextid, $link);
                    if($context)
                    {
                        if($includeDeleted || !$context->isDeleted())
                            $contexts[] = $context;
                    }
                }
            }
            TDOUtil::closeDBLink($link);
			
			uasort($contexts, 'TDOContext::contextCompare');

            return $contexts;
        } 
        else
        {
            error_log("Unable to get Contexts: ".mysql_error());
        }
        
        TDOUtil::closeDBLink($link);
        return false;    
	}
    
    
    public static function getContextsForUserModifiedSince($userid, $timestamp, $deletedContexts=false, $link=NULL)
    {
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTask::getContextsForUserModifiedSince() could not get DB connection.");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $escapedUserid = mysql_real_escape_string($userid, $link);
        
        $sql = "SELECT contextid,userid,name,deleted,context_timestamp FROM tdo_contexts WHERE userid='$escapedUserid'";
        
        if(!empty($timestamp))
        {
            $sql = $sql." AND tdo_contexts.context_timestamp > ".$timestamp;
        }
        
        if($deletedContexts == false)
            $sql = $sql." AND (tdo_contexts.deleted = 0) ";
        else
            $sql = $sql." AND (tdo_contexts.deleted != 0) ";
        
        $result = mysql_query($sql);
        if($result)
        {
            $contexts = array();
            while($row = mysql_fetch_array($result))
            {
                if ( (empty($row['contextid']) == false) && (count($row) > 0) )
                {
                    $context = TDOContext::contextFromRow($row);
                    if (empty($context) == false)
                    {
                        $contexts[] = $context;
                    }
                }
            }
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return $contexts;
        } 
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        
        error_log("TDOTask::getContextsForUserModifiedSince() could not get tasks for the user '$userid'" . mysql_error());
        
        return false;
    }
    

    public static function getContextTimestampForUser($userid, $link=NULL)
    {
        $timestamp = 0;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTask::getContextTimestampForUser() could not get DB connection.");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $sql = "SELECT context_timestamp FROM tdo_contexts ";
        
        $sql .= " ORDER BY context_timestamp DESC LIMIT 1";
        
        $result = mysql_query($sql);
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                if(!empty($row['context_timestamp']))
                {
                    $timestamp = $row['context_timestamp'];
                }
            }
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return $timestamp;
        } 
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        
        error_log("TDOTask::getContextTimestampForUser() could not get timestamp for specified user" . mysql_error());
        
        return false;
    }
    
    
    public static function contextFromRow($row)
    {
        if(empty($row))
            return false;
        
        $context = new TDOContext();
        if(isset($row['contextid']))
            $context->setContextid($row['contextid']);
        if(isset($row['userid']))
            $context->setUserid($row['userid']);
        if(isset($row['name']))
            $context->setName($row['name']);
        if(isset($row['deleted']))
            $context->setDeleted($row['deleted']);
        if(isset($row['context_timestamp']))
            $context->setLastModified($row['context_timestamp']);
        
        return $context;
    }
    

    	
	public static function getContextForContextid($contextid, $link=NULL)
    {
        if(!isset($contextid))
            return false;
            
        if(!$link)
        {
            $link = TDOUtil::getDBLink();        
            if(!$link)
            {
                error_log("TDOContext failed to get dblink");
                return false;
            }  
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }
        
        $contextid = mysql_real_escape_string($contextid);
        
		$sql = "SELECT contextid,userid,name,deleted,context_timestamp FROM tdo_contexts WHERE contextid='$contextid'";

        $response = mysql_query($sql, $link);
        if($response)
        {
            $row =  mysql_fetch_array($response);
            if($row)
            {
                $context = TDOContext::contextFromRow($row);
                return $context;
            }
        }
        else
            error_log("Unable to get context: ".mysql_error());
        
        if($shouldCloseLink)
            TDOUtil::closeDBLink($link);

        return false;        
    }
    
    public static function deleteContexts($contextids)
    {
//		error_log("TDOContext::deleteLists");
		
        if(!isset($contextids))
            return false;
            
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
            error_log("TDOContext unable to get link");
           return false;
        }
 
        foreach($contextids as $contextid)
        {
            TDOContext::deleteContext($contextid);
        }
        TDOUtil::closeDBLink($link);
        return true;
    }
    
    public static function getNameForContext($contextid)
    {
        if(!isset($contextid))
            return "No Context";
		
		if($contextid == "all")
			return "All";
		else if($contextid == "nocontext")
			return "No Context";
            
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOContext failed to get dblink");
			return false;
		}  

        $contextid = mysql_real_escape_string($contextid, $link);

        $sql = "SELECT name from tdo_contexts WHERE contextid='$contextid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $resultArray = mysql_fetch_array($result);
            if(isset($resultArray['name']))
            {
                TDOUtil::closeDBLink($link);
                return TDOUtil::ensureUTF8(strval($resultArray['name']));
            }

        }
        
        TDOUtil::closeDBLink($link);
        return false;         
    }
	
	
    public static function getIsContextDeleted($contextid)
    {
        if(!isset($contextid))
            return false;
		
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOContext failed to get dblink");
			return false;
		}  
		
        $contextid = mysql_real_escape_string($contextid, $link);
		
        $sql = "SELECT deleted from tdo_contexts WHERE contextid='$contextid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $resultArray = mysql_fetch_array($result);
            if(isset($resultArray['deleted']))
            {
                TDOUtil::closeDBLink($link);
                return ($resultArray['deleted'] != 0);
            }
        }
        
        TDOUtil::closeDBLink($link);
        return true;         
    }	
	
	
	public static function assignTaskToContext($taskid, $contextid, $userid, $link=NULL)
	{
		if(empty($taskid) )
			return false;
		
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

		if(empty($contextid))
			$contextid = NULL;
		else
			$contextid = mysql_real_escape_string($contextid, $link);
		
		$timestamp = time();        
		
        $sql = "SELECT taskid from tdo_context_assignments WHERE taskid='$taskid' AND userid='$userid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
			if($row = mysql_fetch_array($result))
			{
				// try to update the record first
				if(!mysql_query("UPDATE tdo_context_assignments SET contextid='$contextid', context_assignment_timestamp='$timestamp' WHERE taskid='$taskid' AND userid='$userid'", $link))
				{
					error_log("Unable to assign task to context".mysql_error());
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
					return false;
				}
			}
			else
			{
				// try to update the record first
				if(!mysql_query("INSERT INTO tdo_context_assignments (taskid, userid, contextid, context_assignment_timestamp) VALUES ('$taskid', '$userid', '$contextid', '$timestamp')", $link))
				{
					error_log("Unable to assign task to context".mysql_error());
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
					return false;
				}
			}
		}
		else
		{
					error_log("Unable to assign task to context".mysql_error());
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
					return false;
		}		
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
		return true;
	}

	

	public function getName()
	{
		return $this->_name;
	}
	public function setName($val)
	{
		$this->_name = TDOUtil::ensureUTF8(strval($val));
	}
	
	public function getContextid()
	{
		return $this->_contextid;
	}
	public function setContextid($val)
	{
		$this->_contextid = $val;
	}
	
	public function getUserid()
	{
		return $this->_userid;
	}
	public function setUserid($val)
	{
		$this->_userid = $val;
	}
          
    public function isDeleted()
    {
        return $this->_deleted;
    }
    public function setDeleted($val)
    {
        $this->_deleted = intval($val);
    }	
	
	public function getLastModified()
	{
		return $this->_lastmodified;
	}
	public function setLastModified($val)
	{
		$this->_lastmodified = $val;
	}	
	
	
}


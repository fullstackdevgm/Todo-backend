<?php
//      TDOComment
//      Used to handle all user data

// include files
include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/DBConstants.php');


class TDOComment extends TDODBObject
{

	public function __construct()
	{
        parent::__construct();
		$this->setToDefault();
	}

	public function setToDefault()
	{
        parent::set_to_default();

		// clears values without going to database
		// SimpleDB requires a value for every attribue...
        $this->setCommentId(NULL);
        $this->setUserId(NULL);
        $this->setItemId(NULL);
        $this->setItemType(NULL);
        $this->setItemName(NULL);
        $this->setText(NULL);
	}


    public static function deleteAllCommentsForChildrenOfTask($taskid, $link=NULL)
    {
        if(!isset($taskid))
            return false;

        if(!$link)
        {
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOComment::deleteAllCommentsForChildrenOfTask failed to get dblink");
                return false;
            }
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }

        $escapedtaskid = mysql_real_escape_string($taskid, $link);
		$timestamp = time();

        // Delete Notification (mark as deleted)
        $sql = "UPDATE tdo_comments SET deleted=1, timestamp='$timestamp' WHERE itemid IN ";
        $sql .= "(SELECT taskid FROM tdo_tasks WHERE parentid='$escapedtaskid' UNION SELECT taskid FROM tdo_completed_tasks WHERE parentid='$escapedtaskid')";

        if(!mysql_query($sql, $link))
        {
            error_log("TDOComment::deleteAllCommentsForChildrenOfTask could not delete comments: ".mysql_error());
            if($shouldCloseLink)
                TDOUtil::closeDBLink($link);
            return false;
        }

        if($shouldCloseLink)
            TDOUtil::closeDBLink($link);
        return true;
    }


	public static function deleteAllCommentsForTask($taskid, $link=NULL)
	{
        if(!isset($taskid))
            return false;

        if(!$link)
        {
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOComment::deleteAllCommentsForTask failed to get dblink");
                return false;
            }
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }

        $escapedtaskid = mysql_real_escape_string($taskid, $link);
		$timestamp = time();

        // Delete Notification (mark as deleted)
        if(!mysql_query("UPDATE tdo_comments SET deleted=1, timestamp='$timestamp' WHERE itemid='$escapedtaskid'", $link))
        {
            error_log("TDOComment::deleteAllCommentsForTask Could not delete comments: ".mysql_error());
            if($shouldCloseLink)
                TDOUtil::closeDBLink($link);
            return false;
        }

        if($shouldCloseLink)
            TDOUtil::closeDBLink($link);
        return true;
	}

    public static function permanentlyDeleteAllCommentsForTask($taskid, $link = NULL)
    {
        if(empty($taskid))
            return false;

        if(empty($link))
        {
            $closeLink = true;
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOComment failed to get db link");
                return false;
            }
        }
        else
            $closeLink = false;

        $escapedTaskID = mysql_real_escape_string($taskid, $link);
        $sql = "DELETE FROM tdo_comments WHERE itemid='$escapedTaskID'";
        if(mysql_query($sql, $link))
        {
            if($closeLink)
                TDOUtil::closeDBLink($link);
            return true;
        }
        else
            error_log("permanentlyDeleteAllCommentsForTask failed with error: ".mysql_error());

        if($closeLink)
            TDOUtil::closeDBLink($link);

        return false;
    }

	public static function deleteComment($commentid)
	{
        if($commentid == NULL)
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOComment unable to get link");
            return false;
        }

   		$timestamp = time();

        $commentid = mysql_real_escape_string($commentid, $link);
        $sql = "UPDATE tdo_comments SET deleted=1, timestamp='$timestamp' WHERE commentid='$commentid'";
        if(mysql_query($sql, $link))
        {
            TDOUtil::closeDBLink($link);
            return true;
        }
        else
        {
            error_log("Unable to delete comment $commentid");
        }

        TDOUtil::closeDBLink($link);
        return false;
	}

    public function addComment()
    {
        if($this->userId() == NULL || $this->itemId() == NULL
            || $this->itemType() == NULL || $this->text() == NULL)
            return false;
        if($this->commentId() == NULL);
            $this->setCommentId(TDOUtil::uuid());
        if($this->timestamp() == NULL)
            $this->setTimestamp(time());
        if($this->itemName() == NULL)
            $this->setItemName("Unnamed Item");

        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOComment unable to get link");
            return false;
        }
        $commentid = mysql_real_escape_string($this->commentId(), $link);
        $userid = mysql_real_escape_string($this->userId(), $link);
        $itemid = mysql_real_escape_string($this->itemId(), $link);
        $itemType = intval($this->itemType());
        $text = mysql_real_escape_string($this->text(), $link);
        $timestamp = intval($this->timestamp());


        $itemName = mb_strcut($this->itemName(), 0, ITEM_NAME_LENGTH, 'UTF-8');
        $itemName = mysql_real_escape_string($itemName);

        $sql = "INSERT INTO tdo_comments (commentid, userid, itemid, item_type, text, timestamp, item_name) VALUES ('$commentid', '$userid', '$itemid', $itemType, '$text', $timestamp, '$itemName')";
        if(mysql_query($sql, $link))
        {
            TDOUtil::closeDBLink($link);
            return true;
        }
        else
            error_log("addComment failed: ".mysql_error());

        TDOUtil::closeDBLink($link);
        return false;
    }

	public static function getCommentCountForItem($itemid, $includeDeleted=false, $link=NULL)
    {
        if(!isset($itemid))
            return false;

        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOComment unable to get link");
                return false;
            }
        }
        else
            $closeDBLink = false;

        $itemid = mysql_real_escape_string($itemid, $link);
        $sql = "SELECT count(commentid) FROM tdo_comments WHERE itemid='$itemid' ";
        if(!$includeDeleted)
            $sql .= "AND deleted != 1 ";

        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return $total[0];
            }
        }
		if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }


	public static function getCommentsForItem($itemid, $includeDeleted=false)
    {
        if(!isset($itemid))
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOComment unable to get link");
            return false;
        }

        $itemid = mysql_real_escape_string($itemid, $link);
        $sql = "SELECT commentid, userid, text, timestamp, deleted, itemid, item_type, item_name FROM tdo_comments WHERE itemid='$itemid' ";
        if(!$includeDeleted)
            $sql .= "AND deleted != 1 ";
        $sql .= "ORDER BY timestamp";

        $result = mysql_query($sql, $link);
        if($result)
        {
            $comments = array();
            while($row = mysql_fetch_array($result))
            {
                if(isset($row['commentid']))
                {
                    $comment = TDOComment::commentFromRow($row);
                    $comments[] = $comment;
                }
            }

            TDOUtil::closeDBLink($link);
            return $comments;
        }
        else
            error_log("getCommentsForItem failed: ".mysql_error());

        TDOUtil::closeDBLink($link);
        return false;

    }

    public static function commentFromRow($row)
    {
        $comment = new TDOComment();
        if(isset($row['commentid']))
            $comment->setCommentId($row['commentid']);
        if(isset($row['userid']))
            $comment->setUserId($row['userid']);
        if(isset($row['text']))
            $comment->setText($row['text']);
        if(isset($row['timestamp']))
            $comment->setTimestamp($row['timestamp']);
        if(isset($row['deleted']))
            $comment->setDeleted($row['deleted']);
        if(isset($row['itemid']))
            $comment->setItemId($row['itemid']);
        if(isset($row['item_type']))
            $comment->setItemType($row['item_type']);
        if(isset($row['item_name']))
            $comment->setItemName($row['item_name']);

        return $comment;
    }

	public static function getCommentForCommentId($commentId)
    {
        if(!isset($commentId))
            return false;

        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOComment unable to get link");
            return false;
        }

        $commentId = mysql_real_escape_string($commentId, $link);
        $sql = "SELECT commentid, userid, text, timestamp, deleted, itemid, item_type, item_name FROM tdo_comments WHERE commentid='$commentId' ";

        $result = mysql_query($sql, $link);
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                if(isset($row['commentid']))
                {
                    $comment = TDOComment::commentFromRow($row);

					TDOUtil::closeDBLink($link);
					return $comment;
                }
            }
        }
        else
            error_log("getCommentsForItem failed: ".mysql_error());

        TDOUtil::closeDBLink($link);
        return false;
    }


    public static function notificationPropertiesForComment($commentId)
    {
        $displayProperties = array();

		$comment = TDOComment::getCommentForCommentId($commentId);
		if(empty($comment))
			return false;

		$date = TDOUtil::eventShortDateStringFromTimestamp($comment->timestamp());
        $displayProperties['Date'] = $date;

        $displayProperties['Comment'] = htmlspecialchars($comment->text());

        return $displayProperties;
    }


    public static function getRecentCommentsForList($listid, $currentUserid, $limit, $excludedTaskIds=NULL, $lastCommentTimestamp=0, $lastCommentid=NULL)
    {
        if(empty($listid) || empty($currentUserid))
        {
            return false;
        }

        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOComment failed to get db link");
            return false;
        }

        $sql = "SELECT tdo_comments.*, name, completiondate FROM ";
        $sql .= TDOComment::getQueryStringForRecentCommentsForList($listid, $currentUserid, $excludedTaskIds, $lastCommentTimestamp, $lastCommentid, $link, "tdo_tasks");
        $sql .= " UNION SELECT tdo_comments.*, name, completiondate FROM " . TDOComment::getQueryStringForRecentCommentsForList($listid, $currentUserid, $excludedTaskIds, $lastCommentTimestamp, $lastCommentid, $link, "tdo_completed_tasks");
        $sql .= " ORDER BY timestamp DESC, commentid ";
        if($limit)
        {
            $sql .= " LIMIT ".intval($limit);
        }

        $result = mysql_query($sql, $link);
        if($result)
        {
            //read the comment and also insert the task name into the comment array
            $comments = array();
            while($row = mysql_fetch_array($result))
            {
                $comment = TDOComment::commentFromRow($row);
                if(isset($row['name']))
                {
                    $comment->setItemName($row['name']);
                }
                $comments[] = $comment;
            }

            TDOUtil::closeDBLink($link);
            return $comments;
        }
        else
            error_log("getRecentCommentsForList failed with error: ".mysql_error());

        TDOUtil::closeDBLink($link);
        return false;

    }

    public static function getRecentCommentCountForList($listid, $currentUserid, $excludedTaskIds=NULL, $lastCommentTimestamp=0, $lastCommentid=NULL)
    {
        if(empty($listid) || empty($currentUserid))
        {
            return false;
        }

        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOComment failed to get db link");
            return false;
        }

        $sql = "SELECT COUNT(commentid) FROM ";
        $sql .= TDOComment::getQueryStringForRecentCommentsForList($listid, $currentUserid, $excludedTaskIds, $lastCommentTimestamp, $lastCommentid, $link, "tdo_tasks");
        $sql .= " UNION SELECT COUNT(commentid) FROM ".TDOComment::getQueryStringForRecentCommentsForList($listid, $currentUserid, $excludedTaskIds, $lastCommentTimestamp, $lastCommentid, $link, "tdo_completed_tasks");

        $result = mysql_query($sql, $link);
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                if(isset($row['0']))
                {
                    $count = $row['0'];
                    if(isset($row['1']))
                    {
                        $count += $row['1'];
                    }

                    TDOUtil::closeDBLink($link);
                    return $count;
                }
            }
        }
        else
            error_log("getRecentCommentCountForList failed with error: ".mysql_error());

        TDOUtil::closeDBLink($link);
        return false;
    }

    //This is used to keep our query consistent between getRecentCommentsForList and getRecentCommentCountForList
    public static function getQueryStringForRecentCommentsForList($listid, $currentUserid, $excludedTaskIds, $lastCommentTimestamp, $lastCommentid, $link, $tableName)
    {
         $sql = " tdo_comments INNER JOIN $tableName ON tdo_comments.itemid=$tableName.taskid ";
        if($listid == "all" || $listid == "focus" || $listid == "today" || $listid == "starred")
        {
            $sql .= " INNER JOIN tdo_list_memberships ON $tableName.listid=tdo_list_memberships.listid WHERE tdo_list_memberships.userid='".mysql_real_escape_string($currentUserid, $link)."' ";
        }
        else
        {
            $sql .= " WHERE $tableName.listid='".mysql_real_escape_string($listid, $link)."' ";
        }
        $sql .= " AND tdo_comments.deleted=0 AND $tableName.deleted=0 ";
        if($lastCommentTimestamp && $lastCommentid)
        {
            $sql .= " AND (tdo_comments.timestamp < ".intval($lastCommentTimestamp)." OR (tdo_comments.timestamp = ".intval($lastCommentTimestamp)." AND commentid > '".mysql_real_escape_string($lastCommentid, $link)."')) ";
        }
        if(!empty($excludedTaskIds))
        {
            $excludeString = "";
            foreach($excludedTaskIds as $taskId)
            {
                if(strlen($taskId) > 0)
                {
                    if(strlen($excludeString) > 0)
                        $excludeString .= ",";
                    $excludeString .= "'".mysql_real_escape_string($taskId)."'";
                }
            }
            if(strlen($excludeString) > 0)
                $sql .= " AND tdo_comments.itemid NOT IN (".$excludeString.") ";
        }

        return $sql;
    }

    public static function commentIsTooLarge($comment)
    {
        if(!empty($comment))
        {
            $numBytes = mb_strlen($comment, '8bit');
            if($numBytes > 1048576) // number of bytes in a megabyte
            {
                return true;
            }
        }

        return false;
    }

    public function getPropertiesArray()
    {
//        $fbId = TDOUser::facebookIdForUserId($this->userId());
//		if($fbId)
//            $this->_publicPropertyArray['imgurl'] = "https://graph.facebook.com/".$fbId."/picture";

        $user = TDOUser::getUserForUserId($this->userId());

        if(!empty($user))
        {
            $displayName = $user->displayName();

            $imgUrl = $user->fullImageURL();
            if(!empty($imgUrl))
            {
                $this->_publicPropertyArray['imgurl'] = $imgUrl;
            }
        }
        else
        {
            $displayName = NULL;
        }

        if($displayName)
            $this->_publicPropertyArray['username'] = $displayName;
        else
            $this->_publicPropertyArray['username'] = "Unknown User";

        $this->_publicPropertyArray['text'] = $this->text();

        $date = TDOUtil::humanReadableStringFromTimestamp($this->timestamp());
        $this->_publicPropertyArray['readabledate'] = $date;

        return $this->_publicPropertyArray;
    }

	public function commentId()
	{
        if(empty($this->_publicPropertyArray['commentid']))
            return NULL;
        else
            return $this->_publicPropertyArray['commentid'];
	}
	public function setCommentId($val)
	{
        if(empty($val))
            unset($this->_publicPropertyArray['commentid']);
        else
            $this->_publicPropertyArray['commentid'] = $val;
	}

	public function userId()
	{
		if(empty($this->_publicPropertyArray['userid']))
            return NULL;
        else
            return $this->_publicPropertyArray['userid'];
    }
	public function setUserId($val)
	{
		if(empty($val))
            unset($this->_publicPropertyArray['userid']);
        else
            $this->_publicPropertyArray['userid'] = $val;
	}

	public function itemId()
	{
		if(empty($this->_publicPropertyArray['itemid']))
            return NULL;
        else
            return $this->_publicPropertyArray['itemid'];
	}
	public function setItemId($val)
	{
		if(empty($val))
            unset($this->_publicPropertyArray['itemid']);
        else
            $this->_publicPropertyArray['itemid'] = $val;
	}

	public function itemType()
	{
		if(empty($this->_publicPropertyArray['itemtype']))
            return NULL;
        else
            return $this->_publicPropertyArray['itemtype'];
	}
	public function setItemType($val)
	{
		if(empty($val))
            unset($this->_publicPropertyArray['itemtype']);
        else
            $this->_publicPropertyArray['itemtype'] = $val;
	}

	public function text()
	{
		if(empty($this->_publicPropertyArray['text']))
            return NULL;
        else
            return $this->_publicPropertyArray['text'];
	}
	public function setText($val)
	{
		if(empty($val))
            unset($this->_publicPropertyArray['text']);
        else
            $this->_publicPropertyArray['text'] = TDOUtil::ensureUTF8($val);
	}

    public function itemName()
    {
        if(empty($this->_publicPropertyArray['itemname']))
            return NULL;
        else
            return $this->_publicPropertyArray['itemname'];
    }
    public function setItemName($val)
    {
		if(empty($val))
            unset($this->_publicPropertyArray['itemname']);
        else
            $this->_publicPropertyArray['itemname'] = TDOUtil::ensureUTF8($val);
    }

}

<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');
	
if($method == "postComment")
{
	if(!isset($_POST['itemtype']))
	{
		error_log("HandleCommentActions.php called and missing a required parameter: itemtype");
		echo '{"success":false}';
		return;
	}
	if(!isset($_POST['itemid']))
	{
		error_log("HandleCommentActions.php called and missing a required parameter: itemid");
		echo '{"success":false}';
		return;
	}
	if(!isset($_POST['itemname']))
	{
		error_log("HandleCommentActions.php called and missing a required parameter: itemname");
		echo '{"success":false}';
		return;
	}
	if(!isset($_POST['comment']))
	{
		error_log("HandleCommentActions.php called and missing a required parameter: comment");
		echo '{"success":false}';
		return;
	}

    $itemType = intval($_POST['itemtype']);
    $itemId = $_POST['itemid'];
    
    if($itemType == ITEM_TYPE_TASK)
    {
        $listid = TDOTask::getListIdForTaskId($itemId);
        if(empty($listid))
        {
            error_log("HandleCommentActions.php could not find list for task: ".$itemId);
            echo '{"success":false}';
            return;
        }
    }
    else
    {
        error_log("HandleCommentActions.php called with invalid item type: ".$itemType);
        echo '{"success":false}';
        return;
    }
    
	if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
	{
		error_log("HandleCommentActions.php access violation.  User not authorized: ");
		echo '{"success":false}';
		return;
	}    


	$commentText = $_POST['comment'];
	$newComment = new TDOComment();
	$newComment->setText($commentText);
	$newComment->setItemType($itemType);
	$newComment->setItemId($itemId);
	$newComment->setItemName($_POST['itemname']);
	$newComment->setTimestamp(time());
	$newComment->setUserId($session->getUserId());
		
	if($newComment->addComment() == false)
	{
		error_log("HandlePostComment.php TDOComment failed to add comment.");
		echo '{"success":false}';
		return;
	}
	else
    {
        //Update the timestamp for the task that this comment was added to, so the comment count will sync
        if($itemType == ITEM_TYPE_TASK)
        {
            if(!TDOTask::updateTimestampForTask($itemId))
                error_log("postComment unable to update timestamp for task");
        }
		TDOChangeLog::addChangeLog($listid, $session->getUserId(), $newComment->commentId(), $_POST['itemname'], ITEM_TYPE_COMMENT, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB, $itemId, $itemType);
	}

    $jsonResponse = array();
    $jsonResponse['success'] = true;
    
    $commentJSON = $newComment->getPropertiesArray();
    
    $userIsOwner = (TDOList::getRoleForUser($listid, $session->getUserId()) == LIST_MEMBERSHIP_OWNER);
    if($userIsOwner || $newComment->userId() == $session->getUserId())
        $commentJSON['canremove'] = true;
    else
        $commentJSON['canremove'] = false;

    $jsonResponse['comment'] = $commentJSON;

    echo json_encode($jsonResponse);
}
elseif($method == "removeComment")
{
	if(!isset($_POST['commentid']))
	{
		error_log("HandleCommentActions.php called and missing a required parameter: commentid");
		echo '{"success":false}';
		return;
	}
    
    $commentId = $_POST['commentid'];
    $comment = TDOComment::getCommentForCommentId($commentId);
    
    $itemType = $comment->itemType();
    $itemId = $comment->itemId();
    
    if($itemType == ITEM_TYPE_TASK)
    {
        $listid = TDOTask::getListIdForTaskId($itemId);
        if(empty($listid))
        {
            error_log("HandleCommentActions.php could not find list for task: ".$itemId);
            echo '';
            return;
        }
    }
    else
    {
        error_log("HandleCommentActions.php called with invalid item type: ".$itemType);
        echo '';
        return;
    }
    
    if(TDOList::getRoleForUser($listid, $session->getUserId()) != LIST_MEMBERSHIP_OWNER && $session->getUserId() != $comment->userId())
    {
        error_log("HandleCommentActions.php access violation. User not authorized");
        echo '{"success":false}';
        return; 
    }
    
    if(TDOComment::deleteComment($commentId) == false)
    {
        error_log("HandleCommentActions.php access violation. User not authorized");
        echo '{"success":false}';
        return; 
    }
	else
	{
        //Update the timestamp for the task that this comment was added to, so the comment count will sync
        if($itemType == ITEM_TYPE_TASK)
        {
            if(!TDOTask::updateTimestampForTask($itemId))
                error_log("removeComment unable to update timestamp for task");
        }
		TDOChangeLog::addChangeLog($listid, $session->getUserId(), $comment->commentId(), $comment->itemName(), ITEM_TYPE_COMMENT, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB, $comment->itemId(), $comment->itemType());
	}
    
    echo '{"success":true}';
}
elseif($method == "getCommentsForObject")
{
    if(!isset($_POST['itemid']) || !isset($_POST['itemtype']))
    {
        error_log("HandleCommentActions.php called and missing required parameter");
        echo '{"success":false}';
        return;
    }
    
    $itemId = $_POST['itemid'];
    $itemType = intval($_POST['itemtype']);
    
    if($itemType == ITEM_TYPE_TASK)
    {
        $listid = TDOTask::getListIdForTaskId($itemId);
        if(empty($listid))
        {
            error_log("HandleCommentActions.php could not find list for task: ".$itemId);
            echo '{"success":false}';
            return;
        }
    }
    else
    {
        error_log("HandleCommentActions.php called with invalid item type: ".$itemType);
        echo '{"success":false}';
        return;
    }

	if(TDOList::userCanViewList($listid, $session->getUserId()) == false)
    {
        error_log("HandleCommentActions.php access violation. User not authorized");
        echo '{"success":false}';
        return;
    }
    $userIsOwner = (TDOList::getRoleForUser($listid, $session->getUserId()) == LIST_MEMBERSHIP_OWNER);
    
    $jsonArray = array();
    $jsonArray['success'] = true;
    $comments = TDOComment::getCommentsForItem($itemId);
    
    $jsonComments = array();
    foreach($comments as $comment)
    {
        $commentJSON = $comment->getPropertiesArray();
        
        //add a field indicating if the user can remove the comment, so the ui will know whether
        //to show the delete option
        if($userIsOwner || $comment->userId() == $session->getUserId())
            $commentJSON['canremove'] = true;
        else
            $commentJSON['canremove'] = false;

        
        $jsonComments[] = $commentJSON;
    }
    
    $jsonArray['comments'] = $jsonComments;
    echo json_encode($jsonArray);
    
}

	
?>
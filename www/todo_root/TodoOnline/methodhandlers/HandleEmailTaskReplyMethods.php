<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	include_once('TodoOnline/DBConstants.php');
	
	define ("REPLY_EMAIL_TASK_SECRET", 'F69754A3-9C48-4443-980F-766282FB467A');
    define ("REPLY_EMAIL_ADDRESS_PREFIX", "comment+");
	
    if($method == "createCommentFromEmail")
    {
		// Check for valid parameters
		if (!isset($_POST['apikey']) || !isset($_POST['sender']) || !isset($_POST['recipient']))
		{
			error_log("HandleEmailTaskReplyMethods::createCommentFromEmail called and missing a required parameter");            
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing parameter'),
            ));
			return;
		}
		

		$apiKey = $_POST['apikey'];
		$sender = $_POST['sender'];
		$recipient = $_POST['recipient'];
		
		// Validate the API Secret Key, which is an MD5 hash of:
		// <SECRET><SENDER><RECIPIENT><SECRET>47
		$preHash = REPLY_EMAIL_TASK_SECRET . $sender . $recipient . REPLY_EMAIL_TASK_SECRET . "47";
		$calculatedMD5 = md5($preHash);
		
		if ($calculatedMD5 != $apiKey)
		{
			error_log("HandleEmailTaskReplyMethods::createCommentFromEmail called by unauthorized service");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unauthorized'),
            ));
			return;
		}
        
        $atCharPos = strpos($recipient, "@");
        $startPos = strlen(REPLY_EMAIL_ADDRESS_PREFIX);

        //verify that the recipient address starts with and is longer than "comment+"
        if($atCharPos === false || $atCharPos == 0 || strlen($recipient) <= $startPos  || strpos($recipient, REPLY_EMAIL_ADDRESS_PREFIX) !== 0 )
        {
            error_log("HandleEmailTaskReplyMethods::createCommentFromEmail called with unknown recipient");
            
            if(TDOMailer::sendEmailCommentErrorNotification($sender, "The address you sent the email to is not valid.") == false)
                error_log("Unable to send error notification email to: $sender");
            
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unknown recipient'),
            ));
			return;
        }
        
        $taskId = substr($recipient, $startPos, $atCharPos - $startPos);
        if(empty($taskId))
        {
            error_log("HandleEmailTaskReplyMethods::createCommentFromEmail called with unknown recipient");
            
            if(TDOMailer::sendEmailCommentErrorNotification($sender, "The address you sent the email to is not valid.") == false)
                error_log("Unable to send error notification email to: $sender");
            
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('unknown recipient'),
            ));
			return;            
        }

        //Verify that the sender belongs to the list
        $listId = TDOTask::getListIdForTaskId($taskId);
        if(empty($listId))
        {
            error_log("HandleEmailTaskReplyMethods::createCommentFromEmail could not find list for task");
            
            if(TDOMailer::sendEmailCommentErrorNotification($sender, "The task you attempted to comment on could not be found.") == false)
                error_log("Unable to send error notification email to: $sender");
            
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing task'),
            ));
            return;
        }
        //TODO: START HERE TOMORROW - verify the user belongs to the list and add the comment to the list
        $sender = trim($sender);
        $user = TDOUser::getUserForUsername($sender);
        if(empty($user))
        {
            error_log("HandleEmailTaskReplyMethods::createCommentFromEmail could not find user");
            
            if(TDOMailer::sendEmailCommentErrorNotification($sender, "The address you sent the email from is not associated with a Todo Cloud account.") == false)
                error_log("Unable to send error notification email to: $sender");
            
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to send error notification email'),
            ));
            return;
        }
        
        if(TDOList::userCanEditList($listId, $user->userId()) == false)
        {
            error_log("HandleEmailTaskReplyMethods::createCommentFromEmail user not allowed to edit list");
            
            if(TDOMailer::sendEmailCommentErrorNotification($sender, "The account associated with ".$sender." does not have permission to comment on that task.") == false)
                error_log("Unable to send error notification email to: $sender");
            
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('user permissions'),
            ));
            return;
        }
        
        if(TDOSubscription::getSubscriptionLevelForUserID($user->userId()) <= 1)
        {
            error_log("HandleEmailTaskReplyMethods::createCommentFromEmail user needs premium account");
            
            if(TDOMailer::sendEmailCommentErrorNotification($sender, "Sending comments via email is a premium account feature.") == false)
                error_log("Unable to send error notification email to: $sender");
            
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('premium account'),
            ));
            return;
        }
        
        $commentText = NULL;
        if(isset($_POST['body']))
        {
            $commentText = trim($_POST['body']);
        }
        
        if(empty($commentText))
        {
            error_log("HandleEmailTaskReplyMethods::creatCommentFromEmail called with no comment body");
            
            if(TDOMailer::sendEmailCommentErrorNotification($sender, "There was no comment text found in your reply.") == false)
                error_log("Unable to send error notification email to: $sender");
            
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('missing comment'),
            ));
            return;
        }
        
        if(TDOComment::commentIsTooLarge($commentText))
        {
            error_log("HandleEmailTaskReplyMethods::creatCommentFromEmail called with no oversized comment");
            
            if(TDOMailer::sendEmailCommentErrorNotification($sender, "The comment you attempted to add is too large. Max size is 1 MB.") == false)
                error_log("Unable to send error notification email to: $sender");
            
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Comment is too large for database'),
            ));
            return;           
        }
        
        $comment = new TDOComment();

        $comment->setText($commentText);
        $comment->setItemType(ITEM_TYPE_TASK);
        $comment->setItemId($taskId);
        
        $name = TDOTask::getNameForTask($taskId);
        if(empty($name))
            $name = "Unnamed Task";
        
        $comment->setItemName($name);
        $comment->setTimestamp(time());
        $comment->setUserId($user->userId());


        if($comment->addComment() == false)
        {
            error_log("HandleEmailTaskReplyMethods::creatCommentFromEmail failed to add comment.");
            
            if(TDOMailer::sendEmailCommentErrorNotification($sender, "There was an unknown server error adding your comment.") == false)
                error_log("Unable to send error notification email to: $sender");
            
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('error adding comment'),
            ));
            return;
        }
        else
        {
            //Update the timestamp for the task that this comment was added to, so the comment count will sync

            if(!TDOTask::updateTimestampForTask($taskId))
                error_log("creatCommentFromEmail unable to update timestamp for task");
            
            TDOChangeLog::addChangeLog($listId, $user->userId(), $comment->commentId(), $name, ITEM_TYPE_COMMENT, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB, $taskId, ITEM_TYPE_TASK);
        }

        echo '{"success":true}';

    }
	
?>

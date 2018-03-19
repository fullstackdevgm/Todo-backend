<?php

include_once('TDODaemonConfig.php');
include_once('TodoOnline/base_sdk.php');
//include_once('Plunkboard/DBConstants.php');
include_once('TDODaemonLogger.php');
include_once('TDODaemonController.php');

class TDONotificationController extends TDODaemonController
{			

	function __construct($daemonID = '')
	{
		parent::__construct($daemonID);
	}
		
    
	public function queueNotifications()
	{
//		$this->log("-----------------------------------------------");

        // Mark a set of records in the notification table that we will consume
        $markedRecords = $this->markRecords();
        if (!$markedRecords)
        {
            // There weren't even any notifications that we marked.  Return FALSE
            // so that the daemon will sleep for X seconds before trying to
            // process again.
//            $this->log("No alerts marked for sending or deletion, returning FALSE");
            return false;
        }

//        $this->log($markedRecords . " - Marked ");
        $totalMarkedRecords = $markedRecords;

        $count = $this->processRecords();
//        $this->log($count . " - Queued ");
        

        // Delete the records
        $deletedRowCount = $this->deleteRecords();
//        $this->log($deletedRowCount . " - Deleted ");
		
		// Return how many records were marked.  If any were marked, we did "work"
		// during this loop and the queue daemon should turn around again and do
		// more work immediately.
		return $totalMarkedRecords;
	}

    
	private function markRecords()
	{
		// Grab/mark the records with our daemon's guid so that no other daemon will
		// process them. 
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
			$this->log("markRecords() Failed to get a link to the database, returning 0");
            return 0;
        }
        
        $daemon_owner = mysql_real_escape_string($this->guid, $link);
        
        $maxTime = time() - CHANGE_LOG_MERGE_INTERVAL; //only mark notifications older than 5 minutes, because otherwise the change log item is still subject to change
		$query = "UPDATE tdo_email_notifications SET queue_daemon_owner='$daemon_owner' WHERE (queue_daemon_owner='' OR queue_daemon_owner IS NULL) AND timestamp < $maxTime ORDER BY timestamp ASC LIMIT ".QUEUE_PROCESS_LOOP_LIMIT;
//		$query = "UPDATE tdo_email_notifications SET queue_daemon_owner='$daemon_owner' WHERE (queue_daemon_owner='' OR queue_daemon_owner IS NULL) ORDER BY timestamp ASC LIMIT ".QUEUE_PROCESS_LOOP_LIMIT;
        $result = mysql_query($query, $link);
		if (!$result)
		{
			$this->log("Invalid statement to mark records: " . mysql_error());
            TDOUtil::closeDBLink($link);
			return 0;
		}

		$markedRowCount = mysql_affected_rows($link);
        TDOUtil::closeDBLink($link);
		return $markedRowCount;
	}



	private function processRecords()
	{
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
			$this->log("processRecords() Failed to get a link to the database, returning 0");
            return 0;
        }
    
        $daemon_owner = mysql_real_escape_string($this->guid, $link);
		$query = "SELECT changeid FROM tdo_email_notifications WHERE queue_daemon_owner='$daemon_owner'";
        $result = mysql_query($query, $link);
		if (!$result)
		{
			$this->log("Invalid statement to select records: " . mysql_error());
            TDOUtil::closeDBLink($link);
			return false;
		}
        $rowsProcessed = 0;
		while($row = mysql_fetch_array($result))
		{
            $changeid = $row['changeid'];
            $tdoChange = TDOChangeLog::getChangeForChangeId($changeid);
            
            if(!empty($tdoChange) && $tdoChange->listId() != NULL && $tdoChange->userId() != NULL)
            {
                $listid = $tdoChange->listId();
                $changeType = $tdoChange->itemType();
                $userid = $tdoChange->userId();

                $userids = TDOListSettings::getUsersToNotifyForChange($listid, $changeType, $tdoChange->itemId(), $tdoChange->targetId(), $userid);
                $count = count($userids);
                if($userids)
                {
                    foreach($userids as $userid)
                    {
                        $user = TDOUser::getUserForUserId($userid);
                        if($user)
                        {
                            //Only send notifications to users who have verified their email addresses
                            if($user->emailVerified())
                            {
                                //Only send notifications to users who have a subscription
                                if(TDOSubscription::getSubscriptionLevelForUserID($userid) > 1)
                                {
                                    if($email=TDOMailer::validate_email($user->username()))
                                    {
										// Prevent our system from sending any
										// emails to emails that have been
										// marked as bounced.
										if (TDOMailer::isBouncedEmail($email))
										{
											$this->log("Skipping this email notification because the email is marked as BOUNCED: $email");
                                            continue;
										}

                                        // check to see if this change is due to our incoming email and if so, make sure the person sending
                                        // the email is not the user being notified
                                        if ($tdoChange->changeLocation() == CHANGE_LOCATION_EMAIL)
                                        {
                                            $sender = $this->senderForEmailTaskChange($tdoChange);
                                            if($sender)
                                            {
                                                // if the sender is the same as the user's username (login)
                                                // skip sending them the notification
                                                if($sender == $email)
                                                {
                                                    continue;
                                                }
                                            }
                                        }

                                        // Amazon SES requires a "To:" field, so instead of
                                        // queuing up all the recipients on a BCC, send the
                                        // emails individually to each user.
                                        if (!$this->sendEmailNotification($tdoChange, $email))
                                        {
                                            $this->log("Unable to send email notification to: $email");
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $rowsProcessed++;
		}

        TDOUtil::closeDBLink($link);
		return $rowsProcessed;
	}

    
	private function deleteRecords()
	{
		// Delete all the records that we've previously marked.  This may include
		// alerts for users who have their accounts disabled (apns_enabled = 0) or
		// the user has chosen to not receive notifications (text_alerts = 0).
        $link = TDOUtil::getDBLink();
        if(!$link)
        {
			$this->log("deleteRecords() Failed to get a link to the database, returning 0");
            return 0;
        }
            
        $daemon_owner = mysql_real_escape_string($this->guid, $link);
		$query = "DELETE FROM tdo_email_notifications WHERE queue_daemon_owner = '$daemon_owner';";

		$result = mysql_query($query, $link);
		if (!$result)
		{
			$this->log("Invalid statement to delete records: " . mysql_error());
			TDOUtil::closeDBLink($link);
			return false;
		}

		$deletedRowCount = mysql_affected_rows($link);
        TDOUtil::closeDBLink($link);
		return $deletedRowCount;
	}
    
	
    private function sendEmailNotification($tdoChange, $email)
    {
        if ($tdoChange->userId() == EMAIL_TASK_CREATION_USERID)
        {
            $userName = "Todo Cloud";
        }
        else
        {
            $userName = htmlspecialchars(TDOChangeLog::getDisplayUserName($tdoChange->userId()));
        }
        
		$fromName = $userName;
		$fromAddress = EMAIL_FROM_ADDR;
        $replyAddress = TDONotificationController::replyAddressForTDOChange($tdoChange);

        $subject = $tdoChange->displayableString(true);

		return $this->sendEmail($email, $subject, $fromName, $fromAddress, $tdoChange, $replyAddress);
    }
	
	private function sendEmail($email, $subject, $fromName, $fromAddress, $tdoChange, $replyAddress)
	{
		$shouldAbort = false;
		$htmlBody = "";
		
		$message = $tdoChange->displayableString();
		
		$properties = NULL;
		
		switch($tdoChange->itemType())
		{
			case ITEM_TYPE_TASK:
				switch($tdoChange->changeType())
			{
				case CHANGE_TYPE_ADD:
					$properties = TDOTask::notificationPropertiesForTask($tdoChange->itemId());
					break;
				case CHANGE_TYPE_MODIFY:
					$properties = TDOTask::changedNotificationPropertiesForTask($tdoChange);
					if(count($properties) < 1)
					{
						// there are no properties on this change, abort now.
						$shouldAbort = true;
					}
					break;
			}
				break;
			case ITEM_TYPE_COMMENT:
				$properties = TDOComment::notificationPropertiesForComment($tdoChange->itemId());
				
				$htmlBody .= "<p>\n";
				$htmlBody .= "\"" . $properties['Comment'] . "\"</p>\n";
				
				unset($properties['Comment']);
				break;
			case ITEM_TYPE_USER:
				$properties = TDOUser::changedNotificationPropertiesForUser($tdoChange);
				break;
			default:
				break;
		}
		
		if($shouldAbort == true)
			return true;
		
		$htmlBody .= "<table border=\"0\" align=\"center\" width=\"100%\">\n";
		
		if($properties)
		{
			foreach ($properties as $name => $value)
			{
				$htmlBody .= "<tr>\n";
				$htmlBody .= "<td width=\"90\" align=\"right\" valign=\"top\">\n";
				$htmlBody .= "<span style=\"color:gray;width:90px;display:inline-block;text-align:right;margin-right:30px;font-size:12px\">" . $name . "&nbsp;&nbsp;</span>\n";
				$htmlBody .= "</td>\n";
				$htmlBody .= "<td valign=\"top\">\n";
				$htmlBody .= "<span>" . $value . "<span><br/>\n";
				$htmlBody .= "</td>\n";
				$htmlBody .= "</tr>\n";
			}
		}
		
		if($tdoChange->itemType() == ITEM_TYPE_TASK)
		{
			$task = TDOTask::getTaskForTaskId($tdoChange->itemId());
			if(!empty($task))
			{
				$parentID = $task->parentID();
				if(!empty($parentID))
				{
					$parentTask = TDOTask::getTaskForTaskId($parentID);
					if(!empty($parentTask))
					{
						$projectName = htmlspecialchars($parentTask->name());
						$htmlBody .= "<tr>\n";
						$htmlBody .= "<td width=\"90\" align=\"right\" valign=\"top\">\n";
						$htmlBody .= "<span style=\"color:gray;width:180px;display:inline-block;text-align:right;margin-right:30px;font-size:12px\">Project&nbsp;&nbsp;</span>\n";
						$htmlBody .= "</td>\n";
						$htmlBody .= "<td valign=\"top\">\n";
						$htmlBody .= "<span>" . $projectName . "<span><br/>\n";
						$htmlBody .= "</td>\n";
						$htmlBody .= "</tr>\n";
					}
				}
			}
		}
		
		if($tdoChange->itemType() == ITEM_TYPE_TASKITO)
		{
			$taskito = TDOTaskito::taskitoForTaskitoId($tdoChange->itemId());
			if(!empty($taskito))
			{
				$parentID = $taskito->parentId();
				if(!empty($parentID))
				{
					$parentTask = TDOTask::getTaskForTaskId($parentID);
					if(!empty($parentTask))
					{
						$checklistName = htmlspecialchars($parentTask->name());
						$htmlBody .= "<tr>\n";
						$htmlBody .= "<td width=\"90\" align=\"right\" valign=\"top\">\n";
						$htmlBody .= "<span style=\"color:gray;width:180px;display:inline-block;text-align:right;margin-right:30px;font-size:12px\">Checklist&nbsp;&nbsp;</span>\n";
						$htmlBody .= "</td>\n";
						$htmlBody .= "<td valign=\"top\">\n";
						$htmlBody .= "<span>" . $checklistName . "<span><br/>\n";
						$htmlBody .= "</td>\n";
						$htmlBody .= "</tr>\n";
						
						$projectID = $parentTask->parentID();
						if(!empty($projectID))
						{
							$parentTask = TDOTask::getTaskForTaskId($projectID);
							if(!empty($parentTask))
							{
								$projectName = htmlspecialchars($parentTask->name());
								$htmlBody .= "<tr>\n";
								$htmlBody .= "<td width=\"90\" align=\"right\" valign=\"top\">\n";
								$htmlBody .= "<span style=\"color:gray;width:180px;display:inline-block;text-align:right;margin-right:30px;font-size:12px\">Project&nbsp;&nbsp;</span>\n";
								$htmlBody .= "</td>\n";
								$htmlBody .= "<td valign=\"top\">\n";
								$htmlBody .= "<span>" . $projectName . "<span><br/>\n";
								$htmlBody .= "</td>\n";
								$htmlBody .= "</tr>\n";
							}
						}
					}
				}
			}
		}
		
		// always add the list name on the bottom
		$listName = htmlspecialchars(TDOList::getNameForList($tdoChange->listId()));
		if($listName)
		{
			$htmlBody .= "<tr>\n";
			$htmlBody .= "<td width=\"90\" align=\"right\" valign=\"top\">\n";
			$htmlBody .= "<span style=\"color:gray;width:180px;display:inline-block;text-align:right;margin-right:30px;font-size:12px\">List&nbsp;&nbsp;</span>\n";
			$htmlBody .= "</td>\n";
			$htmlBody .= "<td valign=\"top\">\n";
			$htmlBody .= "<span>" . $listName . "<span><br/>\n";
			$htmlBody .= "</td>\n";
			$htmlBody .= "</tr>\n";
		}
		
		
		if ($tdoChange->userId() == EMAIL_TASK_CREATION_USERID)
		{
			$userName = "Todo Mailinator";
		}
		else
		{
			$userName = htmlspecialchars(TDOChangeLog::getDisplayUserName($tdoChange->userId()));
		}
		
		switch($tdoChange->changeType())
		{
			case CHANGE_TYPE_ADD:
				$htmlBody .= "<tr>\n";
				$htmlBody .= "<td width=\"90\" align=\"right\" valign=\"top\">\n";
				$htmlBody .= "<span style=\"color:gray;width:180px;display:inline-block;text-align:right;margin-right:30px;font-size:12px\">Created by&nbsp;&nbsp;</span>\n";
				$htmlBody .= "</td>\n";
				$htmlBody .= "<td valign=\"top\">\n";
				$htmlBody .= "<span>" . $userName . "<span><br/>\n";
				$htmlBody .= "</td>\n";
				$htmlBody .= "</tr>\n";
				
				break;
			case CHANGE_TYPE_MODIFY:
				$htmlBody .= "<tr>\n";
				$htmlBody .= "<td width=\"90\" align=\"right\" valign=\"top\">\n";
				$htmlBody .= "<span style=\"color:gray;width:180px;display:inline-block;text-align:right;margin-right:30px;font-size:12px\">Updated by&nbsp;&nbsp;</span>\n";
				$htmlBody .= "</td>\n";
				$htmlBody .= "<td valign=\"top\">\n";
				$htmlBody .= "<span>" . $userName . "<span><br/>\n";
				$htmlBody .= "</td>\n";
				$htmlBody .= "</tr>\n";
				
				break;
			case CHANGE_TYPE_DELETE:
				$htmlBody .= "<tr>\n";
				$htmlBody .= "<td width=\"90\" align=\"right\" valign=\"top\">\n";
				$htmlBody .= "<span style=\"color:gray;width:180px;display:inline-block;text-align:right;margin-right:30px;font-size:12px\">Deleted by&nbsp;&nbsp;</span>\n";
				$htmlBody .= "</td>\n";
				$htmlBody .= "<td valign=\"top\">\n";
				$htmlBody .= "<span>" . $userName . "<span><br/>\n";
				$htmlBody .= "</td>\n";
				$htmlBody .= "</tr>\n";
				
				break;
		}
		
		$htmlBody .= "</table>\n";
		
		// set the targetTaskId accoring to the change type
		if($tdoChange->itemType() == ITEM_TYPE_COMMENT)
			$targetTaskId = $tdoChange->targetId();
		else
			$targetTaskId = $tdoChange->itemId();
		
		if ($tdoChange->itemType() != ITEM_TYPE_USER && $tdoChange->itemType() != ITEM_TYPE_INVITATION)
		{
			$htmlBody .= "<p style=\"text-align:center\"><a href=\"https://www.todo-cloud.com?showtask=" . $targetTaskId . "\" style=\"display: inline-block; font-size: 12pt; border-radius: 4px; line-height: 15px; padding: 16px 36px; text-align: center; text-decoration: none !important; transition: all .2s; color: #fff !important; font-family: Arial, Helvetica, sans-serif; background-color: #f8ae16;\">View Task</a></p>";
		}
		
		if (!empty($replyAddress))
		{
			$htmlBody .= "<div style=\"color:gray;font-size:12px;margin-top:40px;\"><center>Reply to this email to add a comment to this task.</center></div>\n";
		}
		
		//Adding a link to update email preferences
		$htmlFooterAdditions = "Don't want these emails? <a href=\"https://www.todo-cloud.com?appSettings=show&option=notifications\" target=\"_blank\" alt=\"Change your notification preferences\">Change your notification preferences</a>.";
		//        $htmlBody .= "<p style=\"font-size:smaller;\">Don't want these notifications? <a href=\"SITE_PROTOCOL . SITE_BASE_URL . "?appSettings=show&option=notifications\" target=\"_blank\" alt=\"Change your email preferences\">Change your email preferences</a>.</p>\n";
		
		$changeTitle = $tdoChange->displayableChangeTitle();
		
		$mergeTags = array(
						   array('name' => 'CHANGE_TITLE',
								 'content' => $changeTitle),
						   array('name' => 'EMAIL_FROM_NAME',
								 'content' => $fromName),
						   array('name' => 'EMAIL_SUBJECT',
								 'content' => $subject),
						   array('name' => 'CHANGE_NOTIFICATION_CONTENT',
								 'content' => $htmlBody)
						   );
		
		return TDOMailer::sendMandrillEmailTemplate('todo-cloud-change-notification',
													$email,
													$recipientName, // User Display Name
													$mergeTags,
													null,			// From email address
													$fromName,
													$replyAddress,
													$subject);
		
//		return TDOMailer::sendHTMLAndTextEmail($email, $subject, $fromName, $fromAddress, $htmlBody, $textBody, $replyAddress, $htmlFooterAdditions);
	}
	
    private function replyAddressForTDOChange($tdoChange)
    {
        $replyAddress = NULL;
        if($tdoChange->itemType() == ITEM_TYPE_TASK)
        {
            if($tdoChange->changeType() != CHANGE_TYPE_DELETE)
            {
                $replyAddress = "comment+".$tdoChange->itemId()."@".INCOMING_MAIL_ADDR;
            }
        }
        else if($tdoChange->itemType() == ITEM_TYPE_COMMENT)
        {
            if($tdoChange->targetType() == ITEM_TYPE_TASK)
            {
                $replyAddress = "comment+".$tdoChange->targetId()."@".INCOMING_MAIL_ADDR;
            }
        }
		
        return $replyAddress;
    }
	
	
    public function senderForEmailTaskChange($tdoChange)
    {
        $displayProperties = array();
		
        $task = TDOTask::getTaskForTaskId($tdoChange->itemId());
        if(empty($task))
            return false;
		
        $changes = json_decode($tdoChange->changeData());
		
        if(isset($changes->{'sender'}))
        {
            $sender = $changes->{'sender'};
            if(!empty($sender))
                return $sender;
        }
		
        return false;
    }


}


?>


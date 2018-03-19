<?php

include_once('TDODaemonConfig.php');
include_once('TodoOnline/base_sdk.php');
include_once('TDODaemonLogger.php');
include_once('TDODaemonController.php');
	
class TDOOnboardingController extends TDODaemonController
{			

	function __construct($daemonID = '')
	{
		parent::__construct($daemonID);
	}
		
    
	public function processOnboardingEmails()
	{
		$this->log("---------------------------------------------------------");
		
		// The daemon will do its best to never send the same email more than
		// once to a given user. But, if the daemon is not running (for system
		// maintenance), it could be possible that it misses sending emails out.
		// The only way to support never missing would be to keep track
		// specifically on a user-by-user basis of which emails were sent.
		//
		// The daemon will track the last time it processed emails. If the last
		// processed time exceeds the daemon's sleep time, the daemon sleep time
		// will be used as the last processed time to make sure we don't send
		// out any emails that are no longer timely.
		//
		// The time between now and the last processed time is the rolling
		// window that is used to determine who to send emails to.
		
		$daemonSleepIntervalString = TDOUtil::getStringSystemSetting("SYSTEM_SETTING_ONBOARDING_DAEMON_SLEEP_INTERVAL", DEFAULT_SYSTEM_SETTING_ONBOARDING_DAEMON_SLEEP_INTERVAL);
		
		$lastRunDate = new DateTime("now", new DateTimeZone("UTC"));
		$lastRunDate->sub(new DateInterval($daemonSleepIntervalString));
		
		$lastRunTimestampString = TDOUtil::getStringSystemSetting("SYSTEM_SETTING_ONBOARDING_DAEMON_LAST_RUN_DATE");
		if (!empty($lastRunTimestampString))
		{
			$lastRunTimestamp = intval($lastRunTimestampString);
			
			$timestampDate = new DateTime('@' . $lastRunTimestamp, new DateTimeZone("UTC"));
			
			// Only use the actual last run timestamp if it's newer than the one
			// we've already set.
			if ($timestampDate->getTimestamp() > $lastRunDate->getTimestamp())
			{
				$lastRunDate = $timestampDate;
			}
		}
		
		$emailDefinitions = $this->getOnboardingEmailDefinitions();
		if (empty($emailDefinitions) || count($emailDefinitions) == 0)
		{
			$this->log("TDOOnboardingController::processOnboardingEmails() found no email definitions.");
			return;
		}
		
		$nowDate = new DateTime("now", new DateTimeZone("UTC"));
		
		foreach ($emailDefinitions as $emailDefinition)
		{
			$numOfEmailsSent = 0;
			$this->log("Processing email type: " . $emailDefinition['EmailID']);
			$mandrillTemplateID = $emailDefinition['MandrillTemplateID'];
			$mergeTagNames = NULL;
			if (isset($emailDefinition['MergeTags']))
			{
				$mergeTagNames = $emailDefinition['MergeTags'];
			}
			
			$startDate = clone $lastRunDate;
			$startDate->sub($emailDefinition['DateInterval']);
			$endDate = clone $nowDate;
			$endDate->sub($emailDefinition['DateInterval']);
			
			$startTimestamp = $startDate->getTimestamp();
			$endTimestamp = $endDate->getTimestamp();
			
//			$this->log("Start Timestamp: $startTimestamp, End Timestamp: $endTimestamp");
			
			$userIDs = $this->getUsersCreatedInDateRange($startTimestamp, $endTimestamp);
			if (!empty($userIDs))
			{
				foreach ($userIDs as $userID)
				{
					// Prepare the merge tags for the email
					$mergeTags = array();
					if ($mergeTagNames)
					{
						foreach ($mergeTagNames as $mergeTagName)
						{
							$mergeTagValue = $this->getMergeTagValueForUser($mergeTagName, $userID);
							if (!empty($mergeTagValue))
							{
								$mergeTags[] = array('name' => $mergeTagName,
													 'content' => $mergeTagValue);
							}
						}
					}
					
					$username = TDOUser::usernameForUserId($userID);
					$displayName = TDOUser::displayNameForUserId($userID);
					$optOutLink = $this->buildOptOutLinkForUser($userID, $username);
					$mergeTags[] = array('name' => "OPT_OUT_LINK",
										 'content' => $optOutLink);
					
					$result = TDOMailer::sendMandrillEmailTemplate($mandrillTemplateID,
																   $username,
																   $displayName,
																   $mergeTags);
					if ($result)
					{
						$this->log("Sent $mandrillTemplateID to $username ($userID)");
						$numOfEmailsSent++;
					}
					else
					{
						$this->log("Failed to send $mandrillTemplateID to $username ($userID)");
					}
				}
			}
			$this->log("$numOfEmailsSent - emails sent");
		}
		
		
		
		$now = time();
		$nowString = strval($now);
		TDOUtil::setStringSystemSetting("SYSTEM_SETTING_ONBOARDING_DAEMON_LAST_RUN_DATE", $nowString);
	}
	
	
	// Returns an array of Onboarding Email definitions or NULL if no
	// definitions exist.
	private function getOnboardingEmailDefinitions()
	{
		$emailIDsSetting = TDOUtil::getStringSystemSetting("SYSTEM_SETTING_ONBOARDING_EMAIL_IDS");
		if (empty($emailIDsSetting))
		{
			return NULL;
		}
		
		$emailIDs = explode(",", $emailIDsSetting);
		if (empty($emailIDs) || count($emailIDs) == 0)
		{
			$this->log("TDOOnboardingController::getOnboardingEmailDefinitions(): No email IDs specified in SYSTEM_SETTING_ONBOARDING_EMAIL_IDS");
			return NULL;
		}
		
		$emailDefinitions = array();
		
		foreach ($emailIDs as $emailID)
		{
//			$this->log("Email ID: " . $emailID);
			$emailDefinitionSetting = TDOUtil::getStringSystemSetting($emailID);
//			$this->log("Email Definition Setting: " . $emailDefinitionSetting);
			if (empty($emailDefinitionSetting))
			{
				$this->log("TDOOnboardingController::getOnboardingEmailDefinitions(): Email ID not found in system settings: $emailID");
				continue;
			}
			
			$emailDefinition = $this->parseEmailDefinitionSetting($emailDefinitionSetting);
			if (!empty($emailDefinition))
			{
				$emailDefinitions[] = $emailDefinition;
			}
		}
		
		return $emailDefinitions;
	}
	
	
	// The fields in the email definition settings are separated by colons and
	// are specified as follows:
	//
	//   - Period from creation date specified with PHP DateInterval format
	//   - Mandrill Template ID
	//   - (optional) Comma-separated merge tags to send. Valid merge tags are:
	//       USER_EMAIL_ADDRESS
	//       USER_DISPLAY_NAME
	//       NUM_OF_LISTS
	//       NUM_OF_ACTIVE_TASKS
	//       NUM_OF_SHARED_LISTS
	//       NUM_OF_COMPLETED_TASKS

	private function parseEmailDefinitionSetting($emailDefinitionSetting)
	{
		if (empty($emailDefinitionSetting))
		{
			$this->log("TDOOnboardingController::parseEmailDefinitionSetting() sent an empty definition setting.");
			return NULL;
		}
		
		$fields = explode(":", $emailDefinitionSetting);
		if (count($fields) < 2)
		{
			$this->log("TDOOnboardingController::parseEmailDefinitionSetting('$emailDefinitionSetting') this value doesn't have at least two fields.");
			return NULL;
		}
		
		$dateInterval = new DateInterval($fields[0]);
		$mandrillTemplateID = $fields[1];
		$mergeTags = NULL;
		if (count($fields) > 2)
		{
			$mergeTags = explode(",", $fields[2]);
		}
		
		if (empty($dateInterval))
		{
			$this->log("TDOOnboardingController::parseEmailDefinitionSetting('$emailDefinitionSetting'): Invalid DateInterval specified.");
			return NULL;
		}
		
		if (empty($mandrillTemplateID))
		{
			$this->log("TDOOnboardingController::parseEmailDefinitionSetting('$emailDefinitionSetting'): Empty Mandrill Template ID.");
			return NULL;
		}
		
		$emailDefinition = array(
								 "EmailID" => $emailDefinitionSetting,
								 "DateInterval" => $dateInterval,
								 "MandrillTemplateID" => $mandrillTemplateID,
								 );
		
		if ($mergeTags)
		{
			$emailDefinition["MergeTags"] = $mergeTags;
		}
		
		return $emailDefinition;
	}
	
	
	private function getMergeTagValueForUser($mergeTagName, $userID)
	{
		if (empty($mergeTagName))
		{
			$this->log("TDOOnboardingController::getMergeTagValueForUser(): Empty mergeTagName");
			return NULL;
		}
		
		if (empty($userID))
		{
			$this->log("TDOOnboardingController::getMergeTagValueForUser(): Empty userID");
			return NULL;
		}
		
		$value = NULL;
		switch ($mergeTagName)
		{
			case "USER_EMAIL_ADDRESS":
			{
				$value = TDOUser::usernameForUserId($userID);
				break;
			}
			case "USER_DISPLAY_NAME":
			{
				$value = TDOUser::displayNameForUserId($userID);
				break;
			}
			case "NUM_OF_LISTS":
			{
				$value = TDOList::getListCountForUser($userID);
				break;
			}
			case "NUM_OF_ACTIVE_TASKS":
			{
				$value = TDOTask::getTaskCountForUser($userID, false);
				break;
			}
			case "NUM_OF_SHARED_LISTS":
			{
				$value = TDOList::getSharedListCountForUser($userID);
				break;
			}
			case "NUM_OF_COMPLETED_TASKS":
			{
				$value = TDOTask::getTaskCountForUser($userID, true);
				break;
			}
		}
		
		return $value;
	}
	
	
	private function getUsersCreatedInDateRange($startTimestamp, $endTimestamp)
	{
		// Return an array of user IDs that have opted in and that are between
		// the specified dates.
		
		$link = TDOUtil::getDBLink();
		if (!$link)
		{
			$this->log("TDOOnboardingController::getUsersCreatedInDateRange() unable to get a link to the DB.");
			return false;
		}
		
		$sql = "SELECT userid FROM tdo_user_accounts WHERE creation_timestamp >= $startTimestamp AND creation_timestamp < $endTimestamp AND email_opt_out = 0";
		$result = mysql_query($sql, $link);
		if ($result)
		{
			$userIDs = array();
			while($row = mysql_fetch_array($result))
			{
				if (!empty($row['userid']))
				{
					$userID = $row['userid'];
					$userIDs[] = $userID;
				}
			}
			
			TDOUtil::closeDBLink($link);
			return $userIDs;
		}
		
		TDOUtil::closeDBLink($link);
		return false;
	}
	
	
	private function buildOptOutLinkForUser($userID, $emailAddress)
	{
		$optOutKey = TDOUtil::computeOptOutKeyForUser($userID, $emailAddress);
		
		
		
		$baseURL = SITE_PROTOCOL . SITE_BASE_URL;
		if (TDO_SERVER_TYPE == "production" || TDO_SERVER_TYPE == "beta" || TDO_SERVER_TYPE == "auth")
		{
			$baseURL = "https://www.todo-cloud.com/";
		}
		
		$optOutLink = $baseURL . "?optOutEmails=true&email=$emailAddress&optOutKey=$optOutKey";
		return $optOutLink;
	}
}


?>


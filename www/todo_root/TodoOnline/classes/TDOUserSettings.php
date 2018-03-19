<?php
//      TDOUserSettings
//      Used to handle all user data

// include files
include_once('TodoOnline/base_sdk.php');	
include_once('TodoOnline/DBConstants.php');	


define ('FOCUS_DUE_FILTER_NONE', 0);
define ('FOCUS_DUE_FILTER_TODAY', 1);
define ('FOCUS_DUE_FILTER_TOMORROW', 2);
define ('FOCUS_DUE_FILTER_THREE_DAYS', 3);
define ('FOCUS_DUE_FILTER_ONE_WEEK', 4);
define ('FOCUS_DUE_FILTER_TWO_WEEKS', 5);
define ('FOCUS_DUE_FILTER_ONE_MONTH', 6);
define ('FOCUS_DUE_FILTER_TWO_MONTHS', 7);

define ('FOCUS_COMPLETED_FILTER_NONE', 0);
define ('FOCUS_COMPLETED_FILTER_ONE_DAY', 1);
define ('FOCUS_COMPLETED_FILTER_TWO_DAYS', 2);
define ('FOCUS_COMPLETED_FILTER_THREE_DAYS', 3);
define ('FOCUS_COMPLETED_FILTER_ONE_WEEK', 4);
define ('FOCUS_COMPLETED_FILTER_TWO_WEEKS', 5);
define ('FOCUS_COMPLETED_FILTER_ONE_MONTH', 6);
define ('FOCUS_COMPLETED_FILTER_ONE_YEAR', 7);

//These flags are used for bitwise operations on the new_feature_flags column
//to determine which new features the user has yet to view
define ('NEW_FEATURE_FLAG_REFERRALS', 1);
//define ('NEW_FEATURE_FLAG_TEST1', 2);
//define ('NEW_FEATURE_FLAG_TEST2', 4);
//define ('NEW_FEATURE_FLAG_TEST3', 8);

//These flags are used for bitwise operations on the email_notification_defaults column
//to determine what email notifications should default to when a user creates a new list
define ('TASK_EMAIL_NOTIFICATIONS_OFF', 1);
define ('USER_EMAIL_NOTIFICATIONS_OFF', 2);
define ('COMMENT_EMAIL_NOTIFICATIONS_OFF', 4);
define ('ASSIGNED_ONLY_EMAIL_NOTIFICATIONS_ON', 8);

class TDOUserSettings extends TDODBObject
{

	public function __construct()
	{
        parent::__construct();
        $this->set_to_default();    
	}
    
	public function set_to_default()
	{
        parent::set_to_default();
	}
	
    public function addUserSettings($userid, $link)
    {
        if(empty($userid))
        {
            return false;
        }
        if(!$link)
        {
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOUserSettings unable to get link");
                return false;
            }
            $closeDBLink = true;
        }
        else
            $closeDBLink = false;
        
        // Add a row for user settings for this user
        $userid = mysql_real_escape_string($userid, $link);
            
        $sql = "INSERT INTO tdo_user_settings (userid) VALUES ('$userid')";
        $result = mysql_query($sql, $link);
		if(!$result)
		{
			error_log("Failed to add user settings with error :".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			return false;
		}
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return true;
    }
    
    public static function getUserSettingsForUserid($userid, $link=NULL)
    {
        if(empty($userid))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOUserSettings unable to get link");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        $userid = mysql_real_escape_string($userid, $link);
        
        $sql = "SELECT * from tdo_user_settings WHERE userid='$userid'";
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                $settings = new TDOUserSettings();
                $settings->updateWithSQLRow($row);
                
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return $settings;
            }
        }
        else
            error_log("getUserSettingsForUserid failed: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }
    
    public function getUpdateString($link)
    {
        $updateString = "";
    
        if($this->timezone() != NULL)
            $updateString .= "timezone='".mysql_real_escape_string($this->timezone(), $link)."'";
        else
            $updateString .= "timezone=NULL";
            
        if($this->userInbox() != NULL)
            $updateString .= ",user_inbox='".mysql_real_escape_string($this->userInbox(), $link)."'";
        else
            $updateString .= ",user_inbox=NULL";
            
        if($this->tagFilterWithAnd() != NULL)
            $updateString .= ",tag_filter_with_and=".intval($this->tagFilterWithAnd());
        else
            $updateString .= ",tag_filter_with_and=0";
            
        if($this->taskSortOrder() != NULL)
            $updateString .= ",task_sort_order=".intval($this->taskSortOrder());
        else
            $updateString .= ",task_sort_order=0";
		
		if($this->startDateFilter() != NULL)
			$updateString .= ",start_date_filter=".intval($this->startDateFilter());
		else
			$updateString .= ",start_date_filter=0";
            
        if($this->focusShowUndueTasks() != NULL)
            $updateString .= ",focus_show_undue_tasks=".intval($this->focusShowUndueTasks());
        else
            $updateString .= ",focus_show_undue_tasks=0";
            
        if($this->focusShowStarredTasks() != NULL)
            $updateString .= ",focus_show_starred_tasks=".intval($this->focusShowStarredTasks());
        else
            $updateString .= ",focus_show_starred_tasks=0";
            
        if($this->focusShowCompletedDate() != NULL)
            $updateString .= ",focus_show_completed_date=".intval($this->focusShowCompletedDate());
        else
            $updateString .= ",focus_show_completed_date=0";
            
        if($this->focusHideTaskDate() != NULL)
            $updateString .= ",focus_hide_task_date=".intval($this->focusHideTaskDate());
        else
            $updateString .= ",focus_hide_task_date=0";
            
        if($this->focusHideTaskPriority() != NULL)
            $updateString .= ",focus_hide_task_priority=".intval($this->focusHideTaskPriority());
        else
            $updateString .= ",focus_hide_task_priority=0";
            
        if($this->focusListFilterString() != NULL)
            $updateString .= ",focus_list_filter_string='".mysql_real_escape_string($this->focusListFilterString(), $link)."'";
        else
            $updateString .= ",focus_list_filter_string=NULL";
            
        if($this->focusShowSubtasks() != NULL)
            $updateString .= ",focus_show_subtasks=".intval($this->focusShowSubtasks());
        else
            $updateString .= ",focus_show_subtasks=0";
		
		if($this->focusUseStartDates() == true)
			$updateString .= ",focus_ignore_start_dates=0";
		else
			$updateString .= ",focus_ignore_start_dates=1";
		
		if($this->taskCreationEmail() != NULL)
			$updateString .= ",task_creation_email='".mysql_real_escape_string($this->taskCreationEmail(), $link)."'";
		else
			$updateString .= ",task_creation_email=NULL";
		
		if ($this->referralCode() != NULL)
			$updateString .= ",referral_code='".mysql_real_escape_string($this->referralCode(), $link)."'";
		else
			$updateString .= ",referral_code=NULL";
        
        if($this->allListFilter() != NULL)
            $updateString .= ",all_list_filter_string='".mysql_real_escape_string($this->allListFilter(), $link)."'";
        else
            $updateString .= ",all_list_filter_string=NULL";
        
        $updateString .= ",default_duedate=".intval($this->defaultDueDate());
            
        $updateString .= ",all_list_hide_dashboard=".intval($this->allListHideDashboard());
        $updateString .= ",starred_list_hide_dashboard=".intval($this->starredListHideDashboard());
        $updateString .= ",focus_list_hide_dashboard=".intval($this->focusListHideDashboard());
        
        $updateString .= ",show_overdue_section=".intval($this->showOverdueSection());
        
        $updateString .= ",skip_task_date_parsing=".intval($this->skipTaskDateParsing());
        $updateString .= ",skip_task_startdate_parsing=".intval($this->skipTaskStartDateParsing());
        $updateString .= ",skip_task_priority_parsing=".intval($this->skipTaskPriorityParsing());
        $updateString .= ",skip_task_list_parsing=".intval($this->skipTaskListParsing());
        $updateString .= ",skip_task_context_parsing=".intval($this->skipTaskContextParsing());
        $updateString .= ",skip_task_tag_parsing=".intval($this->skipTaskTagParsing());
        $updateString .= ",skip_task_checklist_parsing=".intval($this->skipTaskChecklistParsing());
        $updateString .= ",skip_task_project_parsing=".intval($this->skipTaskProjectParsing());
		$updateString .= ",new_feature_flags=".intval($this->newFeatureFlags());
        $updateString .= ",email_notification_defaults=".intval($this->emailNotificationDefaults());
        $updateString .= ",enable_google_analytics_tracking=".intval($this->googleAnalyticsTracking());

        return $updateString;
    }
    
    public function updateUserSettings($link=NULL)
    {
        if($this->userId() == NULL)
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
        
         $updateString = $this->getUpdateString($link);
        if(strlen($updateString) == 0)
        {
            error_log("Nothing to update in user settings");
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }       
        $userid = mysql_real_escape_string($this->userId(), $link);
        
        $sql = "UPDATE tdo_user_settings SET $updateString WHERE userid='$userid'";
        
        
        if(mysql_query($sql, $link))
        {
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return true;
        } 
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
    }
    
    public function updateWithSQLRow($row)
    {
        if(isset($row['userid']))
            $this->setUserId($row['userid']);
        if(isset($row['timezone']))
            $this->setTimezone($row['timezone']);
        if(isset($row['user_inbox']))
            $this->setUserInbox($row['user_inbox']);
        if(isset($row['tag_filter_with_and']))
            $this->setTagFilterWithAnd($row['tag_filter_with_and']);
        if(isset($row['task_sort_order']))
            $this->setTaskSortOrder($row['task_sort_order']);
		if(isset($row['start_date_filter']))
			$this->setStartDateFilter($row['start_date_filter']);
        if(isset($row['focus_show_undue_tasks']))
            $this->setFocusShowUndueTasks($row['focus_show_undue_tasks']);
        if(isset($row['focus_show_starred_tasks']))
            $this->setFocusShowStarredTasks($row['focus_show_starred_tasks']);
        if(isset($row['focus_show_completed_date']))
            $this->setFocusShowCompletedDate($row['focus_show_completed_date']);
        if(isset($row['focus_hide_task_date']))
            $this->setFocusHideTaskDate($row['focus_hide_task_date']);
        if(isset($row['focus_hide_task_priority']))
            $this->setFocusHideTaskPriority($row['focus_hide_task_priority']);
        if(isset($row['focus_list_filter_string']))
            $this->setFocusListFilterString($row['focus_list_filter_string']);
        if(isset($row['focus_show_subtasks']))
            $this->setFocusShowSubtasks($row['focus_show_subtasks']);
		if(isset($row['focus_ignore_start_dates']))
		{
			$val = $row['focus_ignore_start_dates'];
			if ($val == true)
				$this->setFocusUseStartDates(false);
			else
				$this->setFocusUseStartDates(true);
		}
		if(isset($row['task_creation_email']))
			$this->setTaskCreationEmail($row['task_creation_email']);
		if(isset($row['referral_code']))
			$this->setReferralCode($row['referral_code']);
        if(isset($row['all_list_hide_dashboard']))
            $this->setAllListHideDashboard($row['all_list_hide_dashboard']);
        if(isset($row['focus_list_hide_dashboard']))
            $this->setFocusListHideDashboard($row['focus_list_hide_dashboard']);
        if(isset($row['starred_list_hide_dashboard']))
            $this->setStarredListHideDashboard($row['starred_list_hide_dashboard']);
        if(isset($row['all_list_filter_string']))
            $this->setAllListFilter($row['all_list_filter_string']);
        if(isset($row['default_duedate']))
            $this->setDefaultDueDate($row['default_duedate']);
        if(isset($row['show_overdue_section']))
            $this->setShowOverdueSection($row['show_overdue_section']);
        if(isset($row['skip_task_date_parsing']))
            $this->setSkipTaskDateParsing($row['skip_task_date_parsing']);
        if(isset($row['skip_task_startdate_parsing']))
            $this->setSkipTaskStartDateParsing($row['skip_task_startdate_parsing']);
        if(isset($row['skip_task_priority_parsing']))
            $this->setSkipTaskPriorityParsing($row['skip_task_priority_parsing']);
        if(isset($row['skip_task_list_parsing']))
            $this->setSkipTaskListParsing($row['skip_task_list_parsing']);
        if(isset($row['skip_task_context_parsing']))
            $this->setSkipTaskContextParsing($row['skip_task_context_parsing']);
        if(isset($row['skip_task_tag_parsing']))
            $this->setSkipTaskTagParsing($row['skip_task_tag_parsing']);
        if(isset($row['skip_task_checklist_parsing']))
            $this->setSkipTaskChecklistParsing($row['skip_task_checklist_parsing']);
        if(isset($row['skip_task_project_parsing']))
            $this->setSkipTaskProjectParsing($row['skip_task_project_parsing']);
        if(isset($row['new_feature_flags']))
            $this->setNewFeatureFlags($row['new_feature_flags']);
        if(isset($row['email_notification_defaults']))
            $this->setEmailNotificationDefaults($row['email_notification_defaults']);
        if(isset($row['enable_google_analytics_tracking']))
            $this->setGoogleAnalyticsTracking($row['enable_google_analytics_tracking']);
    }
    

    public static function setTimezoneForUser($userid, $timezone)
    {
        if(empty($userid) || empty($timezone))
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
        
        $userid = mysql_real_escape_string($userid);
        $timezone = mysql_real_escape_string($timezone);
        
        if(!mysql_query("UPDATE tdo_user_settings SET timezone='$timezone' WHERE userid='$userid'",$link))
        {
            error_log("setTimezoneForUser failed: ".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        TDOUtil::closeDBLink($link);
        return true;
    }
    
    public static function getTimezoneForUser($userid)
    {
        if(empty($userid))
            return false;
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
        
        $userid = mysql_real_escape_string($userid);
        
        if($result = mysql_query("SELECT timezone FROM tdo_user_settings WHERE userid='$userid'",$link))
        {
            if($row = mysql_fetch_array($result))
            {
                if(isset($row['timezone']))
                {
                    TDOUtil::closeDBLink($link);
                    return $row['timezone'];
                } 
            }
        }
        else
            error_log("getTimezoneForUser failed: ".mysql_error());            
        
        TDOUtil::closeDBLink($link);
        return false;
    }
	
	public static function getUserIDForTaskCreationEmail($taskCreationEmail)
	{
		if (empty($taskCreationEmail))
			return false;
		
		$link = TDOUtil::getDBLink();
		if (!$link)
			return false;
		
		$taskCreationEmail = mysql_real_escape_string($taskCreationEmail);
		
		// Only compare the left-hand side of the email (ignore the @xyz.com)
		$atCharPos = strpos($taskCreationEmail, "@");
		if ($atCharPos !== false)
		{
			if ($atCharPos == 0)
				return false; // nothing provided on the left of the @ symbol
			
			$taskCreationEmail = substr($taskCreationEmail, 0, $atCharPos);
		}
		
		//error_log("taskCreationEmail: $taskCreationEmail");
		
		$sql = "SELECT userid FROM tdo_user_settings WHERE task_creation_email='$taskCreationEmail'";
		if ($result = mysql_query($sql, $link))
		{
			if ($row = mysql_fetch_array($result))
			{
				if (isset($row['userid']))
				{
					$userid = $row['userid'];
					TDOUtil::closeDBLink($link);
					return $userid;
				}
			}
		}
		
		TDOUtil::closeDBLink($link);
		return false;
	}
	
	public static function getUserIDForReferralCode($referralCode)
	{
		if (empty($referralCode))
			return false;
		
		$link = TDOUtil::getDBLink();
		if (!$link)
			return false;
		
		$referralCode = mysql_real_escape_string($referralCode);
		
		$sql = "SELECT userid FROM tdo_user_settings WHERE referral_code='$referralCode'";
		if ($result = mysql_query($sql, $link))
		{
			if ($row = mysql_fetch_array($result))
			{
				if (isset($row['userid']))
				{
					$userid = $row['userid'];
					TDOUtil::closeDBLink($link);
					return $userid;
				}
			}
		}
		
		TDOUtil::closeDBLink($link);
		return false;
	}
	
    public static function isUserTagFilterSettingAnd($userid)
    {
        if(empty($userid))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
        
        $userid = mysql_real_escape_string($userid, $link);
        if($result = mysql_query("SELECT tag_filter_with_and FROM tdo_user_settings WHERE userid='$userid'",$link))
        {
            if($row = mysql_fetch_array($result))
            {
                if(isset($row['tag_filter_with_and']))
                {
                    TDOUtil::closeDBLink($link);
                    return $row['tag_filter_with_and'];
                }
            }
        }
        else
            error_log("isUserTagFilterSettingAnd failed: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function setUserTagFilterSetting($userid, $filterWithAnd)
    {
        if(empty($userid))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
        
        $userid = mysql_real_escape_string($userid, $link);
        $intVal = 0;
        if($filterWithAnd)
            $intVal = 1;
        if($result = mysql_query("UPDATE tdo_user_settings SET tag_filter_with_and=$intVal WHERE userid='$userid'", $link))
        {
            TDOUtil::closeDBLink($link);
            return true;
        }
        else
            error_log("setUserTagFilterSetting failed: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
    }
	
	
	// Returns a user's referral code. Generates one if it doesn't exist.
	public static function referralCodeForUserID($userid, $link = NULL)
	{
		if (empty($userid))
		{
			error_log("TDOReferral::referralCodeForUserID() passed an empty userid");
			return false;
		}
		
		$closeDBLink = false;
		if ($link == NULL)
		{
			$closeDBLink = true;
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOUserSettings::referralCodeForUserID() could not get a link to the DB");
				return false;
			}
		}
		
		$referralCode = NULL;
		$userid = mysql_real_escape_string($userid, $link);
		$sql = "SELECT referral_code FROM tdo_user_settings WHERE userid='$userid'";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			error_log("TDOUserSettings::referralCodeForUserID() failed to query the DB");
			if ($closeDBLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		$row = mysql_fetch_array($result);
		if ($row && isset($row['referral_code']))
		{
			$referralCode = $row['referral_code'];
			if ($closeDBLink)
				TDOUtil::closeDBLink($link);
			return $referralCode;
		}
		
		$referralCode = NULL;
		while ($referralCode == NULL)
		{
			$possibleReferralCode = TDOReferral::generatePossibleReferralCode();
			
			if (TDOUserSettings::isReferralCodeUnique($possibleReferralCode, $link))
				$referralCode = $possibleReferralCode;
		}
		
        $referralCode = mysql_real_escape_string($referralCode, $link);
		$sql = "UPDATE tdo_user_settings SET referral_code='$referralCode' WHERE userid='$userid'";
        
        if (!mysql_query($sql, $link))
        {
			error_log("TDOUserSettings::referralCodeForUserID() failed to update user's ($userid) settings in DB for new referral_code ($referralCode): " .mysql_error());
			if ($closeDBLink)
				TDOUtil::closeDBLink($link);
            return false;
        }
		
		if ($closeDBLink)
			TDOUtil::closeDBLink($link);
		return $referralCode;
	}
	
	
	public static function isReferralCodeUnique($possibleReferralCode, $link = NULL)
	{
		if (empty($possibleReferralCode))
			return false;
		
		$closeDBLink = false;
		if ($link == NULL)
		{
			$closeDBLink = true;
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOUserSettings::isReferralCodeUnique() could not get a link to the DB");
				return false;
			}
		}
		
		$possibleReferralCode = mysql_real_escape_string($possibleReferralCode, $link);
		$sql = "SELECT userid FROM tdo_user_settings WHERE referral_code='$possibleReferralCode'";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			if ($closeDBLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		if (mysql_fetch_array($result))
		{
			if ($closeDBLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		if ($closeDBLink)
			TDOUtil::closeDBLink($link);
		return true;
	}
	
	// Generates a new task creation email and saves it into the user's
	// settings.  If successful, this function will return the new task creation
	// email (a string).
	public static function regenerateTaskCreationEmailForUserID($userid)
	{
		if (empty($userid))
		{
			error_log("TDOUserSettings::regenerateTaskCreationEmailForUserID() passed an empty userid");
			return false;
		}
		
		$newTaskCreationEmail = TDOUserSettings::newTaskCreationEmailForUserID($userid);
		if (empty($newTaskCreationEmail))
		{
			error_log("TDOUserSettings::regenerateTaskCreationEmailForUserID() received bad return value from TDOUserSettings::newTaskCreationEmailForUserID()");
			return false;
		}
		
        $link = TDOUtil::getDBLink();
        if(!$link)
		{
			error_log("TDOUserSettings::regenerateTaskCreationEmailForUserID() could not get a link to the DB");
            return false;
		}
        
        $newTaskCreationEmail = mysql_real_escape_string($newTaskCreationEmail, $link);
		$sql = "UPDATE tdo_user_settings SET task_creation_email='$newTaskCreationEmail' WHERE userid='$userid'";
        
        if (!mysql_query($sql, $link))
        {
			error_log("TDOUserSettings::regenerateTaskCreationEmailForUserID() failed to update user's ($userid) settings in DB for new task creation email ($newTaskCreationEmail): " .mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
		
		TDOUtil::closeDBLink($link);
        return $newTaskCreationEmail;
	}
	
	// Generates a new task creation email for the given userid but does NOT
	// store or save it anywhere.
	public static function newTaskCreationEmailForUserID($userid)
	{
		if (empty($userid))
		{
			error_log("TDOUserSettings::newTaskCreationEmailForUserID() passed an empty userid");
			return false;
		}
		
		$emailPrefix = '';
		
		// Attempt to user the username first and if that doesn't exist, use
		// the display name (truncating it at the first space character).
		$username = TDOUser::usernameForUserId($userid);
		if (!empty($username))
		{
			$emailPrefix = $username;
		}
		
		// If we don't have a username yet, use the user's display name
		if (empty($emailPrefix))
		{
			$emailPrefix = TDOUser::displayNameForUserId($userid);
		}
		
		if (empty($emailPrefix))
		{
			error_log("TDOUserSettings::newTaskCreationEmailForUserID() unable to determine username or display name");
			return false;
		}
		
		// Only use the left-hand side of an email address
		$atCharPos = strpos($emailPrefix, "@");
		if ($atCharPos !== false)
		{
			if ($atCharPos > 0)
			{
				$emailPrefix = substr($emailPrefix, 0, $atCharPos);
			}
		}
		
		// Only use the first word in a display name (truncate at space char)
		$spaceCharPos = strpos($emailPrefix, " ");
		if ($spaceCharPos !== false)
		{
			if ($spaceCharPos > 0)
			{
				$emailPrefix = substr($emailPrefix, 0, $spaceCharPos);
			}
		}
		
		// Only try this up to 10 times.  If we fail, something odd must be
		// happening and we should bail!
		$maxAttempts = 10;
		
		for ($numberOfAttempts = 0; $numberOfAttempts < $maxAttempts; $numberOfAttempts++)
		{
			$possibleEmail = $emailPrefix . "-" . uniqid();
			if (TDOUserSettings::isTaskCreationEmailUnique($possibleEmail))
				return $possibleEmail;
		}
		
		error_log("TDOUserSettings::newTaskCreationEmailForUserID() could not generate a unique email after $maxAttempts attempts for: $username");
		return false;
	}
	
	public static function isTaskCreationEmailUnique($possibleEmail)
	{
		if (empty($possibleEmail))
			return false;
		
		$link = TDOUtil::getDBLink();
		if (!$link)
			return false;
		
		$possibleEmail = mysql_real_escape_string($possibleEmail, $link);
		$sql = "SELECT userid FROM tdo_user_settings WHERE task_creation_email='$possibleEmail'";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			TDOUtil::closeDBLink($link);
			return false;
		}
		
		if (mysql_fetch_array($result))
		{
			TDOUtil::closeDBLink($link);
			return false;
		}
		
		TDOUtil::closeDBLink($link);
		return true;
	}
	
	public static function clearTaskCreationEmailForUserID($userid)
	{
		if (empty($userid))
		{
			error_log("TDOUserSettings::clearTaskCreationEmailForUserID() passed an empty userid");
			return false;
		}
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;

		$sql = "UPDATE tdo_user_settings SET task_creation_email=NULL WHERE userid='$userid'";
		if (mysql_query($sql, $link))
		{
			TDOUtil::closeDBLink($link);
			return true;
		}
		
		TDOUtil::closeDBLink($link);
		return false;
	}
    
    public static function getDefaultDueDateForUserForTaskCreationTime($userid, $time)
    {
        if(empty($userid))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOUserSettings failed to get db link");
            return false;
        }
        
        $sql = "SELECT default_duedate FROM tdo_user_settings WHERE userid='".mysql_real_escape_string($userid, $link)."'";
        if($response = mysql_query($sql, $link))
        {
            if($row = mysql_fetch_array($response))
            {
                if(isset($row['default_duedate']))
                {
                    $defaultDueDateValue = $row['default_duedate'];
                    
                    if($defaultDueDateValue == 0)
                        return 0;
                    
                    //Add $defaultDueDateValue - 1 days to the time, then normalize
                    $addDays = $defaultDueDateValue - 1;
                    $date = new DateTime();
                    $date->setTimestamp($time);
                    
                    if($addDays > 0)
                        $date->modify(" + ".$addDays." days");
                    
                    $dueDateValue = TDOUtil::normalizeDateToNoonGMT($date->getTimestamp());
                    return $dueDateValue;
                }
            }
        }
        else
            error_log("getDefaultDueDateForUserForTaskCreationTime failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
    }
    
    //This will return the current new feature flags that have not been viewed by a user, based
    //on what is set in getCurrentNewFeatureFlags
    public static function getCurrentNewFeatureFlagsForUser($userid)
    {
        if(empty($userid))
            return false;
        
        $userSettings = TDOUserSettings::getUserSettingsForUserid($userid);
        if(empty($userSettings))
            return false;
        
        $userFlags = intval($userSettings->newFeatureFlags());
        $currentFlags = intval(TDOUserSettings::getCurrentNewFeatureFlags());
        
        //We are going to NOT the userFlags because we want bits that are set to 0 (not viewed),
        //and then we will AND it with the current flags.
        return (~ $userFlags) & $currentFlags;
    }
    
    //This should be updated on each release to reflect which new features are
    //currently being highlighted by the system (bitwise OR the new features together, for example
    //NEW_FEATURE_FLAG_FEATURE1 | NEW_FEATURE_FLAG_FEATURE2 | NEW_FEATURE_FLAG_FEATURE3)
    public static function getCurrentNewFeatureFlags()
    {
        return NEW_FEATURE_FLAG_REFERRALS;
//        return NEW_FEATURE_FLAG_TEST1 | NEW_FEATURE_FLAG_TEST2 | NEW_FEATURE_FLAG_REFERRALS | NEW_FEATURE_FLAG_TEST3;
    }
    
	
    public function setUserId($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['userid']);
        else
            $this->_publicPropertyArray['userid'] = $val;
    }
    
    public function userId()
    {
        if(empty($this->_publicPropertyArray['userid']))
            return NULL;
        else
            return $this->_publicPropertyArray['userid'];
    }
    
    public function setTimezone($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['timezone']);
        else
            $this->_publicPropertyArray['timezone'] = $val;
    }
    
    public function timezone()
    {
        if(empty($this->_publicPropertyArray['timezone']))
            return NULL;
        else
            return $this->_publicPropertyArray['timezone'];
    }
    
    public function setUserInbox($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['user_inbox']);
        else
            $this->_publicPropertyArray['user_inbox'] = $val;
    }
    
    public function userInbox()
    {
        if(empty($this->_publicPropertyArray['user_inbox']))
            return NULL;
        else
            return $this->_publicPropertyArray['user_inbox'];
    }
    
    public function setTagFilterWithAnd($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['tag_filter_with_and']);
        else
            $this->_publicPropertyArray['tag_filter_with_and'] = $val;
    }
    
    public function tagFilterWithAnd()
    {
        if(empty($this->_publicPropertyArray['tag_filter_with_and']))
            return 0;
        else
            return $this->_publicPropertyArray['tag_filter_with_and'];
    }
    
    public function setTaskSortOrder($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['task_sort_order']);
        else
            $this->_publicPropertyArray['task_sort_order'] = $val;
    }
    
    public function taskSortOrder()
    {
        if(empty($this->_publicPropertyArray['task_sort_order']))
            return 0;
        else
            return $this->_publicPropertyArray['task_sort_order'];
    }
	
	public function setStartDateFilter($val)
	{
		if(empty($val))
			unset($this->_publicPropertyArray['start_date_filter']);
		else
			$this->_publicPropertyArray['start_date_filter'] = $val;
	}
	
	public function startDateFilter()
	{
		if(empty($this->_publicPropertyArray['start_date_filter']))
			return 0;
		else
			return $this->_publicPropertyArray['start_date_filter'];
	}
	
	// Returns the number of seconds into the future when tasks with start dates
	// should be hidden from the results.
	public function startDateFilterInterval()
	{
//		error_log("TDOUserSettings::startDateFilterInterval()");
		$filterValue = 0;
		$settingValue = $this->startDateFilter();
		
		switch ($settingValue)
		{
			default:
			case 0: // don't hide
				$filterValue = 0;
				break;
			case 1: // hide after today
				$filterValue = 86400;
				break;
			case 2: // hide after tomorrow
				$filterValue = 86400 * 2;
				break;
			case 3: // hide after next three days
				$filterValue = 86400 * 3;
				break;
			case 4: // hide after one week
				$filterValue = 86400 * 7;
				break;
			case 5: // hide after two weeks
				$filterValue = 86400 * 14;
				break;
			case 6: // hide after one month
				$filterValue = 86400 * 31;
				break;
			case 7: // hide after two months
				$filterValue = 86400 * 62;
				break;
		}
		
		return $filterValue;
	}
    
    public function setFocusShowUndueTasks($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['focus_show_undue_tasks']);
        else
            $this->_publicPropertyArray['focus_show_undue_tasks'] = $val;
    }
    
    public function focusShowUndueTasks()
    {
        if(empty($this->_publicPropertyArray['focus_show_undue_tasks']))
            return 0;
        else
            return $this->_publicPropertyArray['focus_show_undue_tasks'];
    }
    
    public function setFocusShowStarredTasks($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['focus_show_starred_tasks']);
        else
            $this->_publicPropertyArray['focus_show_starred_tasks'] = $val;
    }
    
    public function focusShowStarredTasks()
    {
        if(empty($this->_publicPropertyArray['focus_show_starred_tasks']))
            return 0;
        else
            return $this->_publicPropertyArray['focus_show_starred_tasks'];
    }
    
    public function setFocusShowCompletedDate($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['focus_show_completed_date']);
        else
            $this->_publicPropertyArray['focus_show_completed_date'] = $val;
    }
    
    public function focusShowCompletedDate()
    {
        if(empty($this->_publicPropertyArray['focus_show_completed_date']))
            return 0;
        else
            return $this->_publicPropertyArray['focus_show_completed_date'];
    }
    
    public function setFocusHideTaskDate($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['focus_hide_task_date']);
        else
            $this->_publicPropertyArray['focus_hide_task_date'] = $val;
    }
    
    public function focusHideTaskDate()
    {
        if(empty($this->_publicPropertyArray['focus_hide_task_date']))
            return 0;
        else
            return $this->_publicPropertyArray['focus_hide_task_date'];
    }
    
    public function setFocusHideTaskPriority($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['focus_hide_task_priority']);
        else
            $this->_publicPropertyArray['focus_hide_task_priority'] = $val;
    }
    
    public function focusHideTaskPriority()
    {
        if(empty($this->_publicPropertyArray['focus_hide_task_priority']))
            return 0;
        else
            return $this->_publicPropertyArray['focus_hide_task_priority'];
    }
	
    public function setFocusListFilterString($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['focus_list_filter_string']);
        else
            $this->_publicPropertyArray['focus_list_filter_string'] = $val;
    }
    
    public function focusListFilterString()
    {
        if(empty($this->_publicPropertyArray['focus_list_filter_string']))
            return NULL;
        else
            return $this->_publicPropertyArray['focus_list_filter_string'];
    }
    
    public function setFocusShowSubtasks($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['focus_show_subtasks']);
        else
            $this->_publicPropertyArray['focus_show_subtasks'] = $val;
    }
    
    public function focusShowSubtasks()
    {
        if(empty($this->_publicPropertyArray['focus_show_subtasks']))
            return 0;
        else
            return $this->_publicPropertyArray['focus_show_subtasks'];
    }
	
	public function setFocusUseStartDates($val)
	{
		if(empty($val) || $val == 0)
			$this->_publicPropertyArray['focus_ignore_start_dates'] = 1;
		else
			$this->_publicPropertyArray['focus_ignore_start_dates'] = 0;
	}
	
	public function focusUseStartDates()
	{
		if(empty($this->_publicPropertyArray['focus_ignore_start_dates']) || $this->_publicPropertyArray['focus_ignore_start_dates'] == 0)
			return true;
		else
			return false;
	}
	
	public function setTaskCreationEmail($val)
	{
		if (empty($val))
			unset($this->_publicPropertyArray['task_creation_email']);
		else
			$this->_publicPropertyArray['task_creation_email'] = $val;
	}
	
	public function taskCreationEmail()
	{
		if (empty($this->_publicPropertyArray['task_creation_email']))
			return NULL;
		else
			return $this->_publicPropertyArray['task_creation_email'];
	}
    
	public function setReferralCode($val)
	{
		if (empty($val))
			unset($this->_publicPropertyArray['referral_code']);
		else
			$this->_publicPropertyArray['referral_code'] = $val;
	}
	
	public function referralCode()
	{
		if (empty($this->_publicPropertyArray['referral_code']))
			return NULL;
		else
			return $this->_publicPropertyArray['referral_code'];
	}
    
    public function setAllListHideDashboard($val)
    {
		if (empty($val))
			unset($this->_publicPropertyArray['all_list_hide_dashboard']);
		else
			$this->_publicPropertyArray['all_list_hide_dashboard'] = $val;        
    }
    
	public function allListHideDashboard()
	{
		if (empty($this->_publicPropertyArray['all_list_hide_dashboard']))
			return 0;
		else
			return $this->_publicPropertyArray['all_list_hide_dashboard'];
	}
    
    public function setFocusListHideDashboard($val)
    {
		if (empty($val))
			unset($this->_publicPropertyArray['focus_list_hide_dashboard']);
		else
			$this->_publicPropertyArray['focus_list_hide_dashboard'] = $val;        
    }
    
	public function focusListHideDashboard()
	{
		if (empty($this->_publicPropertyArray['focus_list_hide_dashboard']))
			return 0;
		else
			return $this->_publicPropertyArray['focus_list_hide_dashboard'];
	}
    
    public function setStarredListHideDashboard($val)
    {
		if (empty($val))
			unset($this->_publicPropertyArray['starred_list_hide_dashboard']);
		else
			$this->_publicPropertyArray['starred_list_hide_dashboard'] = $val;        
    }
    
	public function starredListHideDashboard()
	{
		if (empty($this->_publicPropertyArray['starred_list_hide_dashboard']))
			return 0;
		else
			return $this->_publicPropertyArray['starred_list_hide_dashboard'];
	}
    
    public function setAllListFilter($val)
    {
		if (empty($val))
			unset($this->_publicPropertyArray['all_list_filter']);
		else
			$this->_publicPropertyArray['all_list_filter'] = $val;     
    }
    
    public function allListFilter()
    {
        if (empty($this->_publicPropertyArray['all_list_filter']))
			return NULL;
		else
			return $this->_publicPropertyArray['all_list_filter'];
    }
    
    public function setDefaultDueDate($val)
    {
		if (empty($val))
			unset($this->_publicPropertyArray['default_duedate']);
		else
			$this->_publicPropertyArray['default_duedate'] = $val;          
    }
    
    public function defaultDueDate()
    {
		if (empty($this->_publicPropertyArray['default_duedate']))
			return 0;
		else
			return $this->_publicPropertyArray['default_duedate'];        
    }
    
    public function setShowOverdueSection($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['show_overdue_section']);
        else
            $this->_publicPropertyArray['show_overdue_section'] = $val;
    }
    
    public function showOverdueSection()
    {
        if(empty($this->_publicPropertyArray['show_overdue_section']))
            return 0;
        else
            return $this->_publicPropertyArray['show_overdue_section'];
    }
    
    public function setSkipTaskDateParsing($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['skip_task_date_parsing']);
        else
            $this->_publicPropertyArray['skip_task_date_parsing'] = $val;        
    }
    
    public function skipTaskDateParsing()
    {
        if(empty($this->_publicPropertyArray['skip_task_date_parsing']))
            return 0;
        else
            return $this->_publicPropertyArray['skip_task_date_parsing'];        
    }
    
    public function setSkipTaskStartDateParsing($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['skip_task_startdate_parsing']);
        else
            $this->_publicPropertyArray['skip_task_startdate_parsing'] = $val;
    }
    
    public function skipTaskStartDateParsing()
    {
        if(empty($this->_publicPropertyArray['skip_task_startdate_parsing']))
            return 0;
        else
            return $this->_publicPropertyArray['skip_task_startdate_parsing'];
    }
    
    public function setSkipTaskPriorityParsing($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['skip_task_priority_parsing']);
        else
            $this->_publicPropertyArray['skip_task_priority_parsing'] = $val;
    }
    
    public function skipTaskPriorityParsing()
    {
        if(empty($this->_publicPropertyArray['skip_task_priority_parsing']))
            return 0;
        else
            return $this->_publicPropertyArray['skip_task_priority_parsing'];
    }
    
    public function setSkipTaskListParsing($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['skip_task_list_parsing']);
        else
            $this->_publicPropertyArray['skip_task_list_parsing'] = $val;
    }
    
    public function skipTaskListParsing()
    {
        if(empty($this->_publicPropertyArray['skip_task_list_parsing']))
            return 0;
        else
            return $this->_publicPropertyArray['skip_task_list_parsing'];
    }
    
    public function setSkipTaskContextParsing($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['skip_task_context_parsing']);
        else
            $this->_publicPropertyArray['skip_task_context_parsing'] = $val;
    }
    
    public function skipTaskContextParsing()
    {
        if(empty($this->_publicPropertyArray['skip_task_context_parsing']))
            return 0;
        else
            return $this->_publicPropertyArray['skip_task_context_parsing'];
    }
    
    public function setSkipTaskTagParsing($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['skip_task_tag_parsing']);
        else
            $this->_publicPropertyArray['skip_task_tag_parsing'] = $val;
    }
    
    public function skipTaskTagParsing()
    {
        if(empty($this->_publicPropertyArray['skip_task_tag_parsing']))
            return 0;
        else
            return $this->_publicPropertyArray['skip_task_tag_parsing'];
    }
    
    public function setSkipTaskChecklistParsing($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['skip_task_checklist_parsing']);
        else
            $this->_publicPropertyArray['skip_task_checklist_parsing'] = $val;
    }
    
    public function skipTaskChecklistParsing()
    {
        if(empty($this->_publicPropertyArray['skip_task_checklist_parsing']))
            return 0;
        else
            return $this->_publicPropertyArray['skip_task_checklist_parsing'];
    }
    
    public function setSkipTaskProjectParsing($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['skip_task_project_parsing']);
        else
            $this->_publicPropertyArray['skip_task_project_parsing'] = $val;
    }
    
    public function skipTaskProjectParsing()
    {
        if(empty($this->_publicPropertyArray['skip_task_project_parsing']))
            return 0;
        else
            return $this->_publicPropertyArray['skip_task_project_parsing'];        
    }
    
    public function setNewFeatureFlags($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['new_feature_flags']);
        else
            $this->_publicPropertyArray['new_feature_flags'] = $val;
    }
    
    public function newFeatureFlags()
    {
        if(empty($this->_publicPropertyArray['new_feature_flags']))
            return 0;
        else
            return $this->_publicPropertyArray['new_feature_flags'];
    }
    
    public function setEmailNotificationDefaults($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['email_notification_defaults']);
        else
            $this->_publicPropertyArray['email_notification_defaults'] = $val;
    }
    
    public function emailNotificationDefaults()
    {
        if(empty($this->_publicPropertyArray['email_notification_defaults']))
            return 0;
        else
            return $this->_publicPropertyArray['email_notification_defaults'];
    }
    public function setGoogleAnalyticsTracking($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['enable_google_analytics_tracking']);
        else
            $this->_publicPropertyArray['enable_google_analytics_tracking'] = $val;
    }

    public function googleAnalyticsTracking()
    {
        if(empty($this->_publicPropertyArray['enable_google_analytics_tracking']))
            return 0;
        else
            return $this->_publicPropertyArray['enable_google_analytics_tracking'];
    }
}


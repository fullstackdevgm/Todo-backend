<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	

	if($method == "updateUserSettings")
	{
        $userid = $session->getUserId();
        $userSettings = TDOUserSettings::getUserSettingsForUserid($userid);
        if(empty($userSettings))
        {
            error_log("No user settings found for user: ".$userid);
            echo '{"success":false}';
            return;
        }
        
        $updatesMade = false;
        
        if(isset($_POST['task_sort_order']))
        {
            $userSettings->setTaskSortOrder($_POST['task_sort_order']);
            $updatesMade = true;
        }
		
		if(isset($_POST['start_date_filter']))
		{
			$userSettings->setStartDateFilter($_POST['start_date_filter']);
			$updatesMade = true;
		}
        
        if(isset($_POST['focus_show_undue_tasks']))
        {
            $userSettings->setFocusShowUndueTasks($_POST['focus_show_undue_tasks']);
            $updatesMade = true;
        }
        
        if(isset($_POST['focus_show_starred_tasks']))
        {
            $userSettings->setFocusShowStarredTasks($_POST['focus_show_starred_tasks']);
            $updatesMade = true;
        }
        
        if(isset($_POST['focus_show_completed_date']))
        {
            $userSettings->setFocusShowCompletedDate($_POST['focus_show_completed_date']);
            $updatesMade = true;
        }
        
        if(isset($_POST['focus_hide_task_date']))
        {
            $userSettings->setFocusHideTaskDate($_POST['focus_hide_task_date']);
            $updatesMade = true;
        }
        
        if(isset($_POST['focus_hide_task_priority']))
        {
            $userSettings->setFocusHideTaskPriority($_POST['focus_hide_task_priority']);
            $updatesMade = true;
        }
        
        if(isset($_POST['focus_list_filter_string']))
        {
            if($_POST['focus_list_filter_string'] == "none")
                $userSettings->setFocusListFilterString(NULL);
            else
                $userSettings->setFocusListFilterString($_POST['focus_list_filter_string']);
            $updatesMade = true;
        }
        
        if(isset($_POST['all_list_filter_string']))
        {
            if($_POST['all_list_filter_string'] == "none")
                $userSettings->setAllListFilter(NULL);
            else
                $userSettings->setAllListFilter($_POST['all_list_filter_string']);
            $updatesMade = true;
        }
        
        if(isset($_POST['focus_show_subtasks']))
        {
            $userSettings->setFocusShowSubtasks($_POST['focus_show_subtasks']);
            $updatesMade = true;
        }
		
		if(isset($_POST['focus_use_start_dates']))
		{
			$userSettings->setFocusUseStartDates($_POST['focus_use_start_dates']);
			$updatesMade = true;
		}
        
        if(isset($_POST['show_overdue_section']))
        {
            $userSettings->setShowOverdueSection($_POST['show_overdue_section']);
            $updatesMade = true;
        }
        
        if(isset($_POST['default_due_date']))
        {
            $userSettings->setDefaultDueDate($_POST['default_due_date']);
            $updatesMade = true;
        }
        
        if(isset($_POST['skip_task_date_parsing']))
        {
            $userSettings->setSkipTaskDateParsing($_POST['skip_task_date_parsing']);
            $updatesMade = true;
        }
        if(isset($_POST['skip_task_startdate_parsing']))
        {
            $userSettings->setSkipTaskStartDateParsing($_POST['skip_task_startdate_parsing']);
            $updatesMade = true;
        }
        if(isset($_POST['skip_task_priority_parsing']))
        {
            $userSettings->setSkipTaskPriorityParsing($_POST['skip_task_priority_parsing']);
            $updatesMade = true;
        }
        if(isset($_POST['skip_task_list_parsing']))
        {
            $userSettings->setSkipTaskListParsing($_POST['skip_task_list_parsing']);
            $updatesMade = true;
        }
        if(isset($_POST['skip_task_context_parsing']))
        {
            $userSettings->setSkipTaskContextParsing($_POST['skip_task_context_parsing']);
            $updatesMade = true;
        }
        if(isset($_POST['skip_task_tag_parsing']))
        {
            $userSettings->setSkipTaskTagParsing($_POST['skip_task_tag_parsing']);
            $updatesMade = true;
        }
        if(isset($_POST['skip_task_checklist_parsing']))
        {
            $userSettings->setSkipTaskChecklistParsing($_POST['skip_task_checklist_parsing']);
            $updatesMade = true;
        }
        if(isset($_POST['skip_task_project_parsing']))
        {
            $userSettings->setSkipTaskProjectParsing($_POST['skip_task_project_parsing']);
            $updatesMade = true;
        }
        // To mark a new feature as viewed, set this parameter with the bit value of the flag
        // as defined in TDOUserSettings.php. For example, to set the referrals feature as viewed,
        // you would pass a 1 because NEW_FEATURE_FLAG_REFERRALS is defined as 1.
        // To set more than one feature at once, bitwise OR their flag values together
        // e.g. if there were a feature defined as NEW_FEATURE_FLAG_EXAMPLE = 16, you could pass the value
        // 17 (= 1|16) to set both NEW_FEATURE_FLAG_EXAMPLE and NEW_FEATURE_FLAG_REFERRALS as viewed.
        if(isset($_POST['new_feature_flags']))
        {
            $originalValue = $userSettings->newFeatureFlags();
            
            $userSettings->setNewFeatureFlags($originalValue | intval($_POST['new_feature_flags']));
            $updatesMade = true;
        }
        
        if(isset($_POST['task_email_notifications']))
        {
            $oldEmailFlags = $userSettings->emailNotificationDefaults();
            if($_POST['task_email_notifications'])
                $oldEmailFlags &= ~TASK_EMAIL_NOTIFICATIONS_OFF;
            else
                $oldEmailFlags |= TASK_EMAIL_NOTIFICATIONS_OFF;
            
            $userSettings->setEmailNotificationDefaults($oldEmailFlags);
            $updatesMade = true;
        }
        if(isset($_POST['comment_email_notifications']))
        {
            $oldEmailFlags = $userSettings->emailNotificationDefaults();
            if($_POST['comment_email_notifications'])
                $oldEmailFlags &= ~COMMENT_EMAIL_NOTIFICATIONS_OFF;
            else
                $oldEmailFlags |= COMMENT_EMAIL_NOTIFICATIONS_OFF;
            
            $userSettings->setEmailNotificationDefaults($oldEmailFlags);
            $updatesMade = true;
        }
        if(isset($_POST['user_email_notifications']))
        {
            $oldEmailFlags = $userSettings->emailNotificationDefaults();
            if($_POST['user_email_notifications'])
                $oldEmailFlags &= ~USER_EMAIL_NOTIFICATIONS_OFF;
            else
                $oldEmailFlags |= USER_EMAIL_NOTIFICATIONS_OFF;
            
            $userSettings->setEmailNotificationDefaults($oldEmailFlags);
            $updatesMade = true;
        }
        if(isset($_POST['assigned_only_email_notifications']))
        {
            $oldEmailFlags = $userSettings->emailNotificationDefaults();
            if($_POST['assigned_only_email_notifications'])
                $oldEmailFlags |= ASSIGNED_ONLY_EMAIL_NOTIFICATIONS_ON;
            else
                $oldEmailFlags &= ~ASSIGNED_ONLY_EMAIL_NOTIFICATIONS_ON;
            
            $userSettings->setEmailNotificationDefaults($oldEmailFlags);
            $updatesMade = true;
        }
        if(isset($_POST['google_analytics_tracking']))
        {
            $userSettings->setGoogleAnalyticsTracking($_POST['google_analytics_tracking']);
            $updatesMade = true;
        }

        if(!$updatesMade)
        {
            error_log("updateUserSettings called with nothing to update");
            echo '{"success":false}';
            return;
        }
        
        if(!$userSettings->updateUserSettings())
        {
            error_log("Update user settings failed");
            echo '{"success":false}';
            return;
        }
        else
        {
            echo '{"success":true}';
            return;
        }
        
    }
    else if($method == "applyDefaultNotificationSettingsToAllLists")
    {
        $userSettings = TDOUserSettings::getUserSettingsForUserid($session->getUserId());
        if(empty($userSettings))
        {
            error_log("applyDefaultNotificationSettingsToAllLists unable to get settings for user");
            echo '{"success":false}';
            return;
        }
        
        $emailNotificationDefaults = $userSettings->emailNotificationDefaults();

        $taskNotificationSetting = (($emailNotificationDefaults & TASK_EMAIL_NOTIFICATIONS_OFF) == 0);
        $userNotificationSetting = (($emailNotificationDefaults & USER_EMAIL_NOTIFICATIONS_OFF) == 0);
        $commentNotificationSetting = (($emailNotificationDefaults & COMMENT_EMAIL_NOTIFICATIONS_OFF) == 0);
        $assignedOnlyNotificationSetting = (($emailNotificationDefaults & ASSIGNED_ONLY_EMAIL_NOTIFICATIONS_ON) == ASSIGNED_ONLY_EMAIL_NOTIFICATIONS_ON);
        
        
        if(TDOListSettings::updateEmailNotificationsForAllListsForUser($session->getUserId(), $taskNotificationSetting, $userNotificationSetting, $commentNotificationSetting, $assignedOnlyNotificationSetting) == false)
        {
            error_log("applyDefaultNotificationSettingsToAllLists failed to update list settings for user");
            echo '{"success":false}';
            return;
        }

        echo '{"success":true}';
    }
	
?>

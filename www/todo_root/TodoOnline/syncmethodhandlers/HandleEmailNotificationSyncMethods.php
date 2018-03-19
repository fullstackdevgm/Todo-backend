<?php
	
    include_once('TodoOnline/base_sdk.php');
	
    if($method == "getEmailNotificationInfo")
    {
        $userSettings = TDOUserSettings::getUserSettingsForUserid($session->getUserId());
        $listSettingsInfo = TDOListSettings::getListsAndSettingsForUser($session->getUserId());
        
        if(empty($userSettings) || $listSettingsInfo === false)
        {
            echo '{"success":false, "error":"Unable to get settings for user"}';
            return;
        }
        
        $jsonResponseArray = array();
        
        $userNotificationSettings = $userSettings->emailNotificationDefaults();
        
        $jsonResponseArray['default_task_setting'] = (($userNotificationSettings & TASK_EMAIL_NOTIFICATIONS_OFF) == 0);
        $jsonResponseArray['default_comment_setting'] = (($userNotificationSettings & COMMENT_EMAIL_NOTIFICATIONS_OFF) == 0);
        $jsonResponseArray['default_user_setting'] = (($userNotificationSettings & USER_EMAIL_NOTIFICATIONS_OFF) == 0);
        $jsonResponseArray['default_assigned_only_setting'] = (($userNotificationSettings & ASSIGNED_ONLY_EMAIL_NOTIFICATIONS_ON) == ASSIGNED_ONLY_EMAIL_NOTIFICATIONS_ON);
        
        $listsJson = array();
        foreach($listSettingsInfo as $listInfo)
        {
            $list = $listInfo['list'];
            $listSettings = $listInfo['settings'];
            
            $listJson = array();
            $listJson['name'] = $list->name();
            $listJson['listid'] = $list->listId();
            $listJson['color'] = $listSettings->color();
            $changeNotifications = $listSettings->changeNotificationSettings();
            $listJson['task_setting'] = $changeNotifications['task'];
            $listJson['user_setting'] = $changeNotifications['user'];
            $listJson['comment_setting'] = $changeNotifications['comment'];
            $listJson['assigned_only_setting'] = $listSettings->notifyAssignedOnly();
            
        
            $listsJson[] = $listJson;
        }

        $jsonResponseArray['lists'] = $listsJson;
        
        $jsonResponseArray['success'] = true;
        echo json_encode($jsonResponseArray);
    
    }
  
    
    
?>
<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	

    if($method == "updateListSettings")
    {
        $autoCreate = false;
        if(!isset($_POST['listid']))
        {
            error_log("updateListSettings called missing parameter: listid");
            echo '{"success":false}';
            return;
        }    
        
        $listid = $_POST['listid'];
        if (in_array($listid, array('starred', 'all', 'focus')))
        {
            $listid = constant(strtoupper($listid) . '_LIST_ID');
            $autoCreate = true;
        }
        $listSettings = TDOListSettings::getListSettingsForUser($listid, $session->getUserId());
        if(!$listSettings)
        {
            if($autoCreate)
            {
                $listSettings = new TDOListSettings();

                if(!$listSettings->addListSettings($listid, $session->getUserId()))
                {
                    error_log("Failed to add list settings");
                    echo '{"success":false}';
                    return;
                }
                else
                {
                    $listSettings = TDOListSettings::getListSettingsForUser($listid, $session->getUserId());
                }
            }
            else
            {
                error_log("updateListSettings unable to find list settings for user");
                echo '{"success":false}';
                return;
            }
        }
        
        $updatesMade = false;
        $shouldUpdateTimestamp = false;
        
        if(isset($_POST['filter_sync']))
        {
            $shouldFilterSync = 0;
            if($_POST['filter_sync'] == "true")
                $shouldFilterSync = 1;
                
            $listSettings->setFilterSyncedTasks($shouldFilterSync);
            $updatesMade = true;
        }
        
        if(isset($_POST['color']))
		{
            $listSettings->setColor($_POST['color']);

            $updatesMade = true;
            $shouldUpdateTimestamp = true;
		}
        
        if(isset($_POST['notifications']))
        {
            $notificationArray = $_POST['notifications'];
            $listSettings->setChangeNotificationSettings($notificationArray);      
            $updatesMade = true;
        }
        if(isset($_POST['notify_assigned']))
        {
            $listSettings->setNotifyAssignedOnly($_POST['notify_assigned']);
            $updatesMade = true;
        }
        
        if(!$updatesMade)
        {
            error_log("updateListSettings called with no changes");
            echo '{"success":false}';
            return;
        }
        
        if(!$listSettings->updateListSettings($listid, $session->getUserId()))
        {
            error_log("Unable to update list settings");
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Unable to update settings.'),
            ));
            return;
        }
        else
        {
            // CRG - Added so clients that sync will get updates when they
            // change the color of the list.  This will result in everyone
            // syncing even if they didn't need to but it still resolves the
            // issue in the easiest way
            if($shouldUpdateTimestamp)
                TDOList::updateTimestampForList($listid);
            
            echo '{"success":true}';
            return;
        }
        
    }
    elseif($method == "getListSettings")
    {
        if(!isset($_POST['listid']))
        {
            error_log("getListSettings called missing parameter: listid");
            echo '{"success":false}';
            return;
        }    
        
        $listid = $_POST['listid'];
        
        $listSettings = TDOListSettings::getListSettingsForUser($listid, $session->getUserId());
        if(!$listSettings)
        {
            error_log("getListSettings unable to find list settings for user");
            echo '{"success":false}';
            return;
        }
        $emailVerified = false;
        $user = TDOUser::getUserForUserId($session->getUserId());
        if($user)
        {
            $emailVerified = $user->emailVerified();
        }
        $validSubscription = false;
        if(TDOSubscription::getSubscriptionLevelForUserID($session->getUserId()) > 1)
        {
            $validSubscription = true;
        }
        
        $changeNotifications = $listSettings->changeNotificationSettings();
        $responseChangeNotifications = array();
    
        foreach($changeNotifications as $notificationType=>$value)
        {
            $setting = array("key"=>$notificationType, "value"=>$value, "displayname"=>_(TDOListSettings::displayNameForNotificationType($notificationType)));
            $responseChangeNotifications[] = $setting;
        }
        
        $jsonResponse = array();
        $jsonResponse['success'] = true;
        $jsonResponse['notificationsettings'] = $responseChangeNotifications;
        $jsonResponse['notifyassignedsetting'] = $listSettings->notifyAssignedOnly();
        $jsonResponse['emailverified'] = $emailVerified;
        $jsonResponse['validsubscription'] = $validSubscription;
        
        echo json_encode($jsonResponse, true);
    }
//    if($method == "changeNotificationSettings")
//    {
//        if(!isset($_POST['notifications']))
//        {
//            error_log("HandleListSettingsMethods.php called missing parameter: notifications");
//            echo '{"success":false}';
//            return;            
//        }
//        if(isset($_POST['listid']))
//            $listid = $_POST['listid'];
//        else
//            $listid = NULL;
//        
//        $notificationArray = $_POST['notifications'];
//        $listSettings = new TDOListSettings();
//        $listSettings->setChangeNotificationSettings($notificationArray);
//        if($listSettings->updateListSettings($listid, $session->getUserId()) == false)
//        {
//            error_log("Unable to update list settings");
//            echo '{"success":false}';
//            return;              
//        }
//
//        echo '{"success":true}';
//    }
	
?>

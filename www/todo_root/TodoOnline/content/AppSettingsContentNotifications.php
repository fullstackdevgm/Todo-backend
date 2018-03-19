<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_NOTIFICATION_SETTINGS_FUNCTIONS; ?>" ></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>
<?php


if($session->isLoggedIn())
{
    if(TDOSubscription::getSubscriptionLevelForUserID($session->getUserId()) <= 1)
    {
    ?>
        <div class="setting_options_container" style="padding:10px">
            <h2 style="margin-top:20px;"><?php _e('Email Notification Settings'); ?></h2>
            <div><?php _e('This feature requires a premium account.'); ?></div>
            <div style="margin-top:20px;"><a class="button" href="?appSettings=show&option=subscription"><?php _e('Go Premium'); ?></a></div>
        </div>
    <?php
    }
    else
    {
    
        $emailVerified = false;
        $user = TDOUser::getUserForUserId($session->getUserId());
        if($user)
        {
            $emailVerified = $user->emailVerified();
        }
    
        if(!$emailVerified)
        {
        ?>
            <div class="setting_options_container" style="padding:10px">
                <h2 style="margin-top:20px;"><?php _e('Email Notification Settings'); ?></h2>
                <div><?php _e('This feature requires verification of your email address.'); ?></div>
                <div style="margin-top:20px;"><a class="button" onclick="verifyUserEmail()"><?php _e(' Verify '); ?></a></div>
            </div>
        <?php
        }
        else
        {
    
            $userSettings = TDOUserSettings::getUserSettingsForUserid($session->getUserId());
            $listSettingsInfo = TDOListSettings::getListsAndSettingsForUser($session->getUserId());
            if(empty($userSettings) || $listSettingsInfo === false)
            {
                _e('Sorry, we are unable to get your notification settings.');
            }
            else
            {
                ?>
                <div class="setting_options_container" style="padding:10px">
                <h2 style="margin-top:10px;"><?php _e('Email Notification Settings'); ?></h2>
                <?php
                if(!empty($listSettingsInfo)) : ?>
                    <div style="margin-top:30px;margin-bottom:10px;"><?php _e('Select the types of items you would like to receive notifications about for each list:'); ?></div>
                <?php else : ?>
                    <div style="margin-top:30px;margin-bottom:10px;max-width:600px;"><?php _e('Select the types of items you would like to receive notifications about for custom lists you create or join:'); ?></div>
                <?php endif; ?>

                    <table class="settings_notification_table">
                        <tr>
                            <th></th>
                            <th><?php _e('Tasks'); ?></th>
                            <th><?php _e('Comments'); ?></th>
                            <th><?php _e('Members'); ?></th>
                            <th><?php _e('Only notify about tasks assigned to me'); ?></th>
                        </tr>
                <?php
                foreach($listSettingsInfo as $listInfo)
                {
                    $list = $listInfo['list'];
                    $listSettings = $listInfo['settings'];
                    $changeSettings = $listSettings->changeNotificationSettings();
                    ?>
                    <tr>

                    <td>
                        <span class="list_icon custom_list_icon" style="margin-top:-2px;"></span>
                        <span style="padding-bottom:2px;margin-left:-15px;height:16px;width:50px; background-color:rgba('<?php echo $listSettings->color(); ?>', 1);" > </span>
                        <span style="padding-left:18px;"><?php echo $list->name(); ?></span>
                    </td>
                    <?php
                    echo htmlForNotificationCheckbox('task', $changeSettings, $list->listId());
                    echo htmlForNotificationCheckbox('comment', $changeSettings, $list->listId());
                    echo htmlForNotificationCheckbox('user', $changeSettings, $list->listId());
                    
                    if($listSettings->notifyAssignedOnly())
                        $checkedString = 'checked="true"';
                    else
                        $checkedString = '';
                    ?>

                    <td align="center"><input type="checkbox" <?php echo $checkedString; ?> onclick="updateNotificationSetting('assigned_only', this.checked, '<?php echo $list->listId(); ?>')"/></td>
                    </tr>
                    <?php
                }
                $userNotificationSettings = $userSettings->emailNotificationDefaults();
            
                if(!empty($listSettingsInfo))
                    $borderStyle = "border-top:1px solid rgb(203,203,203);";
                else
                    $borderStyle = "";
                ?>
                    <tr style="<?php echo $borderStyle; ?>">
                        <td><?php _e('Default Settings'); ?></td>
                <?php
                    echo htmlForDefaultNotificationCheckbox('task', TASK_EMAIL_NOTIFICATIONS_OFF, $userNotificationSettings);
                    echo htmlForDefaultNotificationCheckbox('comment', COMMENT_EMAIL_NOTIFICATIONS_OFF, $userNotificationSettings);
                    echo htmlForDefaultNotificationCheckbox('user', USER_EMAIL_NOTIFICATIONS_OFF, $userNotificationSettings);
                
                if($userNotificationSettings & ASSIGNED_ONLY_EMAIL_NOTIFICATIONS_ON)
                    $checkedString = 'checked="true"';
                else
                    $checkedString = '';
                ?>
                <td align="center"><input type="checkbox" <?php echo $checkedString; ?> onclick="updateDefaultNotificationSetting('assigned_only', this.checked)"/></td>
                <?php
                if(!empty($listSettingsInfo)) : ?>
                    <td><span class="button" onclick="confirmApplyToAll()"><?php _e('Apply to All'); ?></span></td>
                <?php else : ?>
                    <td></td>
                <?php endif; ?>
                    </tr>
                </table>

                <?php
                if(!empty($listSettingsInfo)) : ?>
                    <div style="margin-left:10px;max-width:200px;">(<?php _e('These settings will be applied to future lists you create or join'); ?>)</div>
                <?php endif; ?>
                </div>
                <?php
            }
        }
    }
}


function htmlForNotificationCheckbox($notificationType, $userNotificationSettings, $listId)
{
    if($userNotificationSettings[$notificationType])
        $checkedString = 'checked="true"';
    else
        $checkedString = '';

    $onclick = "updateNotificationSetting('".$notificationType."', this.checked, '".$listId."')";

    return '<td align="center"><input type="checkbox" '.$checkedString.' onchange="'.$onclick.'"/></td>';
}

function htmlForDefaultNotificationCheckbox($notificationType, $notificationFlag, $userNotificationSettings)
{
    if($userNotificationSettings & $notificationFlag)
        $checkedString = '';
    else
        $checkedString = 'checked="true"';
    
    $onclick = "updateDefaultNotificationSetting('".$notificationType."', this.checked)";
    
    echo '<td align="center"><input type="checkbox" '.$checkedString.' onchange="'.$onclick.'"/></td>';

}
    
?>


<style>

.settings_notification_table th, td{padding: 10px;min-width:80px;max-width:120px;height:30px;}

</style>


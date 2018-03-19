<?php
	$firstNameStatus = "(not set)";
	$lastNameStatus = "(not set)";
	$usernameStatus = "(not set)";
	$passwordStatus = "(not set)";
	$fakePass = "**************";
    $selectedTimezone = '';
	$taskCreationEmailStatus = "(not set)";
    $verifiedEmail = 0;
    $emailOptOut = 0;
    $gaOptOut = 1;

	if($session->isLoggedIn())
	{
		$user = TDOUser::getUserForUserId($session->getUserId());
		
		if(!empty($user))
		{
            $userHasPremium = false;
            if(TDOSubscription::getSubscriptionLevelForUserID($user->userId()) > 1)
            {
                $userHasPremium = true;
            }
        
			$firstName = htmlspecialchars($user->firstName());
			$lastName = htmlspecialchars($user->lastName());
			
			if(!empty($firstName))
				$firstNameStatus = "";
			if(!empty($lastName))
				$lastNameStatus = "";

			$username = htmlspecialchars($user->username());
			
			if(!empty($username))
				$usernameStatus = "";
			
			$password = $user->password();
			if(!empty($password))
				$passwordStatus = "";
			else
			{
				$fakePass = "";
			}
            
            $imageGuid = $user->imageGuid();
            if(empty($imageGuid))
                $imageGuid = '';
            $imageTimestamp = $user->imageUpdateTimestamp();
            if(empty($imageTimestamp))
                $imageTimestamp = 0;
            
            $verifiedEmail = $user->emailVerified();
            
            $emailOptOut = $user->emailOptOut();
            
            $userSettings = TDOUserSettings::getUserSettingsForUserid($user->userId());
            if($userSettings)
            {
                $gaOptOut = $userSettings->googleAnalyticsTracking();
                $userTimezone = $userSettings->timezone();
                if(!empty($userTimezone))
                {
                    $selectedTimezone = $userTimezone;
                }
                $filterSetting = $userSettings->tagFilterWithAnd();
                
                $showUndueTaskSetting = $userSettings->focusShowUndueTasks();
                $showStarredTaskSetting = $userSettings->focusShowStarredTasks();
                $hideDueAfterSetting = $userSettings->focusHideTaskDate();
                $hidePrioritySetting = $userSettings->focusHideTaskPriority();
                $focusListFilterString = $userSettings->focusListFilterString();
                $completedTasksSetting = $userSettings->focusShowCompletedDate();
                
                $filters = explode("," , $focusListFilterString);
                $filterCount = 0;
                foreach($filters as $filter)
                {
                    if(strlen($filter) > 0 && $filter != "none" && $filter != NULL)
                    {
                        $filterCount++;
                    }
                }
                $focusListFilterSummary = _('Show All');
                if($filterCount > 0)
                    $focusListFilterSummary = "" . $filterCount . ' ' . _('Hidden');
				
				$taskCreationEmail = $userSettings->taskCreationEmail();
				if (!empty($taskCreationEmail))
					$taskCreationEmailStatus = "";
            }
            else
                error_log("Unable to get user settings for user: ".$user->userId());
		}

	}
	function formatOffset($offset) 
    {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int) abs($hours);
        $minutes = (int) abs($remainder / 60);

        if ($hour == 0 AND $minutes == 0) 
        {
            $sign = ' ';
        }
        return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) .':'. str_pad($minutes,2, '0');

    }
	
?>	
<script>
	var currentTimezoneValue = "<?php echo $selectedTimezone; ?>";
	
	var hideDueAfterOptions = ["<?php _e('No Filter'); ?>", "<?php _e('Today'); ?>", "<?php _e('Tomorrow'); ?>", "<?php _e('Next Three Days'); ?>", "<?php _e('One Week'); ?>", "<?php _e('Two Weeks'); ?>", "<?php _e('One Month'); ?>", "<?php _e('Two Months'); ?>"];
	var selectedDueAfterOption = "<?php echo $hideDueAfterSetting; ?>";
	
	var hidePriorityOptions = [{value:0, title:"<?php _e('None'); ?>"},{value:9, title:"<?php _e('Low'); ?>"},{value:5, title:"<?php _e('Medium'); ?>"},{value:1, title:"<?php _e('High'); ?>"}];
	
	var selectedPriorityOption = "<?php echo $hidePrioritySetting; ?>";
	var selectedPriorityIndex = 0;
	
	var focusListFilterString = "<?php echo $focusListFilterString; ?>";
	
	var completedTasksOptions = ["<?php _e('None'); ?>", "<?php _e('One Day'); ?>", "<?php _e('Two Days'); ?>", "<?php _e('Three Days'); ?>", "<?php _e('One Week'); ?>", "<?php _e('Two Weeks'); ?>", "<?php _e('One Month'); ?>", "<?php _e('One Year'); ?>"];
	var selectedCompletedTasksOption = "<?php echo $completedTasksSetting; ?>";
	

</script>

<div class="setting_options_container">
		<!--first name & lastname-->
		<div id="first_last_name_setting" class="setting">
			<input id="origFirstName" type="hidden" value="<?php echo $firstName; ?>" />
			<input id="origLastName" type="hidden" value="<?php echo $lastName; ?>" />
			
			<span class="setting_name"><?php _e('Name'); ?></span>
			<span class="setting_details" >
				<span id="firstNameLabel"><?php echo $firstName; ?> </span>
				<span id="lastNameLabel"><?php echo $lastName; ?></span>
			</span>
			<span id="first_last_name_edit" class="setting_edit" onclick="displaySettingDetails('first_last_name_config', 'first_last_name_edit')"><?php _e('Edit'); ?></span>
			<div id="first_last_name_config" class="setting_details setting_config">
				<div class="labeled_control">
					<label><?php _e('First Name'); ?></label>
					<input type="text" onkeyup="validateFirstLastName()" oninput="validateFirstLastName()" name="firstName" id="firstName" value="<?php echo $firstName; ?>" autocomplete="off"/>
					<span id="first_name_status" class="option_status"></span>
				</div>
				<div class="labeled_control">
					<label><?php _e('Last Name'); ?></label>
					<input type="text" onkeyup="validateFirstLastName()" oninput="validateFirstLastName()" name="lastName" id="lastName" value="<?php echo $lastName; ?>" autocomplete="off"/>
					<span id="last_name_status" class="option_status"></span>
				</div>
				<div class="save_cancel_button_wrap">
					<div id="nameSubmit" class="button disabled"><?php _e('Save'); ?></div>
	 				<div class="button" onclick="cancelFirstLastNameUpdate()"><?php _e('Cancel'); ?></div>
				</div>
			</div>
		</div>
		
		<!--username-->
		<div id="username_setting" class="setting">
			<input id="origUsername" type="hidden" value="<?php echo $username; ?>" />
			
			<span class="setting_name"><?php _e('Username'); ?></span>
			<div class="setting_details" >
				<span id="usernameLabel"><?php echo $username; ?></span>
                <div class="button" id="verify_email_button" style="display:<?php if($verifiedEmail) echo 'none'; else echo 'inline-block';?>;" onclick="verifyUserEmail()"><?php _e('Verify'); ?></div>
			</div>
			<span id="username_edit" class="setting_edit" onclick="displaySettingDetails('username_config', 'username_edit')"><?php _e('Edit'); ?></span>
		
			<div id="username_config" class="setting_details setting_config">
				<div class="labeled_control">
					<label><?php _e('Username'); ?></label>
					<input type="text" onkeyup="validateUsername()" name="username" id="username" value="<?php echo $username; ?>" autocomplete="off"/>
					<span id="username_status" class="option_status"></span>
				</div>
			 	<div class="save_cancel_button_wrap">		
					<div class="button disabled" id="usernameSubmit"><?php _e('Save'); ?></div>
					<div class="button" onclick="cancelUsernameUpdate()"><?php _e('Cancel'); ?></div>
			 	</div>		
			</div>
		</div>
		
		<!--password-->
		<div id="password_setting" class="setting">
			<span class="setting_name"><?php _e('Password'); ?></span>
			<span class="setting_details" id="passwordLabel"><?php echo $fakePass;?></span>
			<span id="password_edit" class="setting_edit" onclick="displaySettingDetails('password_config', 'password_edit')"><?php _e('Edit'); ?></span>
		
			<div id="password_config" class="setting_details setting_config">
				<div class="labeled_control">
					<label><?php _e('New Password'); ?></label>
	 				<input type="password" onkeyup="validatePassword()" name="password" id="password" value="">
	 				<span id="password_status" class="option_status"></span>
				</div>
				<div class="labeled_control">
					<label><?php _e('Verify Password'); ?></label>
					<input type="password" onkeyup="validatePassword()" name="verifyPassword" id="verifyPassword" value="">
				</div>	
				<div class="save_cancel_button_wrap">		
					<div class="button disabled" id="passwordSubmit"><?php _e('Save'); ?></div>
					<div class="button" onclick="cancelPasswordUpdate()"><?php _e('Cancel'); ?></div>
				</div>		
			</div>
        </div>

        <!--Image Upload-->

        <div id="profile_image_setting" class="setting">
            <span class="setting_name"><?php _e('User Photo'); ?></span>
            <span class="setting_details" id="profile_image_details"></span>
            <span id="profile_image_edit" class="setting_edit" onclick="editProfileImageSetting()"><?php _e('Edit'); ?></span>
            <span id="profile_image_remove" class="setting_edit" onclick="confirmRemoveProfileImage()"><?php _e('Remove'); ?></span>
            <div id="profile_image_config" class="setting_details setting_config">
            </div>
        </div>

        <!--show email opt out setting-->
        <div id="show_email_opt_out_setting" class="setting">
            <span class="setting_name"><?php _e('Announcement Emails'); ?></span>
            <span class="setting_details">
            <input type="checkbox" <?php 
                if($emailOptOut == 0) 
                    echo 'checked="true"';
                  ?> id="emailOptOutSettingCheckbox" onchange="toggleEmailOptOutSetting()">
            </span>
            <span class="setting_checkbox"></span>
            <span id="show_email_opt_out_edit" class="setting_edit"></span>
        </div>
    <?php /**
     *
     * Remove the option to opt-out from the user settings - #269
     *
        <div id="enable_google_analytics_tracking_setting" class="setting">
            <span class="setting_name"><?php _e('Allow Google Analytics tracking'); ?></span>
            <span class="setting_details">
            <input type="checkbox" <?php
                if($gaOptOut == 1)
                    echo 'checked="true"';
                  ?> id="gaOptOutSettingCheckbox" onchange="toggleGAOptOutSetting()">
            </span>
            <span class="setting_checkbox"></span>
            <span id="enable_google_analytics_tracking_edit" class="setting_edit"></span>
        </div>
    */ ?>
    <!--language-->
    <div id="language_setting" class="setting">
        <span class="setting_name"><?php _e('Language'); ?></span>
			<div class="setting_details" id="languageValue">
				<div class="select_wrapper language_select">
                    <select id="languageSelect" onchange="saveLanguage()">
                        <?php
                        $language_labels = TDOInternalization::getLanguageLabels();
                        $user_locale = TDOUser::getLocaleForUser($session->getUserId());
                        foreach(TDOInternalization::getAvailableLocales() as $k=>$v) : ?>
                        <option value="<?php echo $v; ?>" <?php echo ($user_locale == $v) ? 'selected' : ''; ?>><?php echo $language_labels[$k]; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
			</div>
        <span id="language_edit" class="setting_edit" ></span>
    </div>
        
        <!--timezone-->
		<div id="timezone_setting" class="setting">
			<span class="setting_name"><?php _e('Time Zone'); ?></span>
			<div class="setting_details" id="timezoneValue">
				<div class="select_wrapper timezone_select">
				<?php
                    $utc = new DateTimeZone('UTC');
                    $dt = new DateTime('now', $utc);

                    echo '<select id="timezoneSelect" onchange="saveTimezone()">';
                    foreach(DateTimeZone::listIdentifiers() as $tz) 
                    {
                        $current_tz = new DateTimeZone($tz);
                        $offset =  $current_tz->getOffset($dt);
                        $transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
                        $abbr = $transition[0]['abbr'];
                        $selectedString = '';
                        if($tz == $selectedTimezone)
                        {
                            $selectedString = 'selected="selected"';
                        }
                        $tz_t = explode('/', $tz);
                        if (sizeof($tz_t) > 1) {
                            $tz_t = _($tz_t[0]) . '/' . _(str_replace('_', ' ', $tz_t[1]));
                        }else{
                            $tz_t = _(str_replace('_', ' ', $tz));
                        }
                        echo '<option id="'.$tz.'" value="' ,$tz, '" '.$selectedString.'>' ,$tz_t, ' [' ,$abbr, ' ', formatOffset($offset), ']</option>';
                    }
                    echo '</select>';
                ?>
				</div>				
			</div>
            <span id="timezone_edit" class="setting_edit" ></span>
            
		</div>
		
		<!-- Task Creation Email -->
		<?php
        
            if($userHasPremium)
            {
                $emailString = '';
                $emailLabel = '<div class="button" onclick="regenerateTaskCreationEmail()">' . _('Generate New Address') . '</div>';//'You don\'t have an inboud task email';//Click \'Edit\' to generate an email address to create tasks via email';
                $editString = '';
                
                if (strlen($taskCreationEmail) > 0)
                {
                    $emailString = $taskCreationEmail . "@newtask.todo-cloud.com";	
                    $emailLabel = $emailString;
                    $editString = _('Edit');
                }
                
            }
            else
            {
                $emailLabel = _('This feature requires a premium account.');
                $emailLabel .= '<a class="button" href="?appSettings=show&option=subscription">' . _('Go Premium') . '</a>';
                $editString = '';
            }

		?>
		
		<div id="task_email_setting" class="setting">
			<span class="setting_name"><?php _e('Inbound Task Email'); ?></span>
			<span class="setting_details" id="taskEmailLabel">
				<?php echo $emailLabel; ?>
			</span>
			<span id="task_email_edit" class="setting_edit" onclick="displaySettingDetails('task_email_config', 'task_email_edit')"><?php echo $editString; ?></span>
			<div id="task_email_config" class="setting_details setting_config"> 
				<div class="setting_details" id="taskCreationEmailValue">
					<div class="save_cancel_button_wrap">		
						<div class="button" onclick="confirmNewTaskEmailRequest()"><?php _e('Generate New Address'); ?></div>
						<div class="button" onclick="cancelTaskEmailUpdate()"><?php _e('Cancel'); ?></div>
					</div>	
				</div>
			</div>
		</div>
        
         <!--Facebook-->
        <?php
        //NCB - Taking out Facebook integration for initial release.
        
//        if($session->isFB() == false)
//        {
//        	$fbSettingLabel = '<a href="?method=linkFacebook" ><div class="button">Link My Facebook Account</div></a>';
//        	$editString = '';
//        	$unlinkButton ='';
//        	
//            $fbId = TDOUser::facebookIdForUserId($session->getUserId());
//            if(empty($fbId))
//            {
//                TDOSession::saveCurrentURL();
//            }
//            else
//            {	
//        		$fbUserFirstName = '';
//        		$fbUserLastName = '';
//        	    $fbUserImg = '<img src="https://graph.facebook.com/'.$fbId.'/picture"/>';
//
//                $userData = json_decode(file_get_contents("https://graph.facebook.com/$fbId"), true);
//                if($userData)
//                {
//                    if(isset($userData['first_name']))
//                       	$fbUserFirstName = $userData['first_name'];
//                    if(isset($userData['last_name']))
//                    	$fbUserLastName = $userData['last_name'];
//                }
//                
//                $fbSettingLabel = $fbUserImg. ' <span>'. $fbUserFirstName .' '. $fbUserLastName.'</span>';
//                $editString = 'Edit';
//                
//                //If the user has other login credentials, give him the option to unlink his facebook account
//                $uName = $user->username();
//                $pass = $user->password();
//                if(!empty($uName) && !empty($pass))
//                {
//                    $unlinkButton = '<div class="button" onclick="unlinkFacebook()">Unlink Account</div>';
//                }
//            }
//        }
        ?>
        <!--
        <div id="social_setting" class="setting">
			<span class="setting_name">Facebook</span>
			<span class="setting_details" id="taskEmailLabel">
				<?php /*echo $fbSettingLabel;*/ ?>
			</span>
			<span id="social_edit" class="setting_edit" onclick="displaySettingDetails('social_config', 'social_edit')"><?php /*echo $editString;*/ ?></span>
			<div id="social_config" class="setting_details setting_config"> 
				<span class="setting_details" id="socialSettingValue">
					<div class="save_cancel_button_wrap">		
						<?php /*echo $unlinkButton; */?>
						<div class="button" onclick="cancelUnlinkFBAccount()">Cancel</div>
					</div>	
				</span>
			</div>
		</div>
        -->

    <!-- Mobile Integration -->
        <?php
            $label = '';
            if($userHasPremium)
            {
                $label = '<ol class="indent-40">';
                $label .= '<h5>' . _('Instructions for iPhone and iPad') . '</h5>';
                $label .= '<li>' . _('Go into the iPhone/iPad Settings App and select &quot;Mail, Contacts, Calendars&quot;') . '</li>';
                $label .= '<li>' . _('Select &quot;Add Account...&quot;') . '</li>';
                $label .= '<li>' . _('Select Other (at the bottom)') . '</li>';
                $label .= '<li>' . _('Select &quot;Add CalDAV Account&quot;') . '</li>';
                $label .= '<li>' . _('Enter the following:') . '</li>';
                $label .= '<ul class="indent-20">';
                $label .= '<li>' . sprintf(_('%sServer:%s siri.todo-cloud.com'), '<b>', '</b>') . '</li>';
                $label .= '<li>' . sprintf(_('%sUser Name:%s %s'), '<b>', '</b>', $username) . '</li>';
                $label .= '<li>' . sprintf(_('%sPassword:%s [your password]'), '<b>', '</b>') . '</li>';
                $label .= '<li>' . sprintf(_('%sDescription:%s Todo Cloud'), '<b>', '</b>') . '</li>';
                $label .= '</ul>';
                $label .= '<li>' . _('Make sure the button for reminders is set to on and calendar is set to off') . '</li>';
                $label .= '<li>' . _('In the iPhone/iPad Settings App, scroll down to &quot;Reminders&quot; and select.') . '</li>';
                $label .= '<li>' . _('Set the Default List to Todo Cloud.') . '</li>';
                $label .= '<li>' . _('Now use Siri with a command like: &quot;Remind me to send feedback about Todo Cloud&quot;') . '</li>';
                $label .= '</ol>';
            }
            else
            {
                $label = _('This feature requires a premium account.');
                $label .= '<a class="button" href="?appSettings=show&option=subscription">' . _('Go Premium') . '</a>';
            }
        ?>

    	<div id="siri_setting" class="setting">
    		<span class="setting_name"><?php _e('Siri Integration'); ?></span>
			<span class="setting_details" id="taskEmailLabel">
                <?php echo $label; ?>
			</span>
    	</div>

    <!-- Reset Data -->
        <div id="reset_data" class="setting">
            <span class="setting_name"><?php _e('Advanced'); ?></span>
            <div class="setting_details">
                <div class="button" onclick="showDeleteServerDataModal()"><?php _e('Delete Server Data'); ?></div>
            </div>
        </div>
        
        
    <!-- Re-migrate button -->
    <?php
        if(TDOLegacy::userCanReMigrate($session->getUserId()) == true) : ?>
            <div id="migrate_date" class="setting">
                <div class="setting_name"><?php _e('Re-migrate'); ?></div>
                <div class="setting_details">
                    <div class="button" id="migrate_button" onclick="showMigrationConfirmationDialog()"><?php _e('Migrate and merge my tasks from Todo Online again.'); ?></div>
                </div>
            </div>
        <?php endif;
    ?>
        
</div>

<script> 

		
	var username = "";
	var password = "";

	/*
var currentFirstNameValue = document.getElementById('firstName').value;
	var currentLastNameValue = document.getElementById('lastName').value;
	var currentUsernameValue = document.getElementById('username').value;
*/
    
</script>	
<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>
<script>
<?php
    echo "var s3baseUrl = '".S3_BASE_USER_IMAGE_URL_LARGE."';";
    echo "var imageGuid = '$imageGuid';";
    echo "var imageTimestamp = $imageTimestamp;";
?>
populateProfileImageDivs();
</script>
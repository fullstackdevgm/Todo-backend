<?php
	$selectedTimezone ='';
	
	if($session->isLoggedIn())
	{
		$user = TDOUser::getUserForUserId($session->getUserId());
		
		if(!empty($user))
		{
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
            
            $userSettings = TDOUserSettings::getUserSettingsForUserid($user->userId());
            if($userSettings)
            {
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
                $showSubtasksSetting = $userSettings->focusShowSubtasks();
				$useStartDatesSetting = $userSettings->focusUseStartDates();

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
                    $focusListFilterSummary = $filterCount . _('Hidden');
            }
            else
                error_log("Unable to get user settings for user: ".$user->userId());
		}

	}

?>
<script>
	var currentTimezoneValue = "<?php echo $selectedTimezone; ?>";
	
	var hideDueAfterOptions = ["<?php _e('No Filter'); ?>", "<?php _e('Today'); ?>", "<?php _e('Tomorrow'); ?>", "<?php _e('Next Three Days'); ?>", "<?php _e('One Week'); ?>", "<?php _e('Two Weeks'); ?>", "<?php _e('One Month'); ?>", "<?php _e('Two Months'); ?>"];
	var selectedDueAfterOption = "<?php echo $hideDueAfterSetting; ?>";
	
	var hidePriorityOptions = [{value:0, title:"<?php _e('No Filter'); ?>"},{value:9, title:"<?php _e('Low'); ?>"},{value:5, title:"<?php _e('Medium'); ?>"},{value:1, title:"<?php _e('High'); ?>"}];
	
	var selectedPriorityOption = "<?php echo $hidePrioritySetting; ?>";
	var selectedPriorityIndex = 0;
	
	var focusListFilterString = "<?php echo $focusListFilterString; ?>";
	
	var completedTasksOptions = ["<?php _e('None'); ?>", "<?php _e('One Day'); ?>", "<?php _e('Two Days'); ?>", "<?php _e('Three Days'); ?>", "<?php _e('One Week'); ?>", "<?php _e('Two Weeks'); ?>", "<?php _e('One Month'); ?>", "<?php _e('One Year'); ?>"];
	var selectedCompletedTasksOption = "<?php echo $completedTasksSetting; ?>";

</script>
<div class="setting_options_container">
        <!--show tasks with no due date setting-->
        <div id="show_undue_tasks_setting" class="setting">
            <span class="setting_name"><?php _e('Show Tasks With No Due Date'); ?></span>
            <span class="setting_details">
                <input type="checkbox" <?php if($showUndueTaskSetting) echo 'checked="true"';  ?> id="focusShowUndueTasksSettingCheckbox" onchange="toggleFocusShowUndueTasksSetting()">
            </span>
            <span class="setting_checkbox"></span>
            <span id="show_undue_tasks_edit" class="setting_edit" ></span>
        </div>
        
        <!--show starred tasks setting-->
        <div id="show_starred_tasks_setting" class="setting">
            <span class="setting_name"><?php _e('Show Starred Tasks'); ?></span>
            <span class="setting_details">
                <input type="checkbox" <?php if($showStarredTaskSetting) echo 'checked="true"';  ?> id="focusShowStarredTasksSettingCheckbox" onchange="toggleFocusShowStarredTasksSetting()">
            </span>
            <span class="setting_checkbox"></span>
            <span id="show_starred_tasks_edit" class="setting_edit"></span>
        </div>
 
        <!--show subtasks setting-->
        <div id="show_subtasks_setting" class="setting">
            <span class="setting_name"><?php _e('Show Subtasks'); ?></span>
            <span class="setting_details">
                <input type="checkbox" <?php if($showSubtasksSetting) echo 'checked="true"';  ?> id="focusShowSubtasksSettingCheckbox" onchange="toggleFocusShowSubtasksSetting()">
            </span>
            <span class="setting_checkbox"></span>
            <span class="setting_edit" id="show_subtasks_edit"></span>
        </div>
                                        
		<!--show subtasks setting-->
		<div id="use_start_dates_setting" class="setting">
			<span class="setting_name"><?php _e('Show Tasks Using Start Dates'); ?></span>
			<span class="setting_details">
				<input type="checkbox" <?php if($useStartDatesSetting) echo 'checked="true"';  ?> id="focusUseStartDatesSettingCheckbox" onchange="toggleFocusUseStartDatesSetting()">
			</span>
			<span class="setting_checkbox"></span>
			<span class="setting_edit" id="use_start_dates_edit"></span>
		</div>

        <!--show completed tasks setting-->
        <div id="show_completed_tasks_setting" class="setting">
            <span class="setting_name"><?php _e('Show Completed Tasks:'); ?></span>
            <span class="setting_details">
            	<div class="select_wrapper">
            		<select id="focusCompletedTasksSelectBox" onchange="toggleFocusCompletedTasksSetting()">
                    <script>
                        var selectHTML = "";
                        for(var i=0; i < completedTasksOptions.length; i++)
                        {
                            var option = completedTasksOptions[i];
                            selectHTML += "<option value=" + i;
                            if(i == selectedCompletedTasksOption)
                            {
                                selectHTML += " selected=\"true\"";
                            }
                            selectHTML += ">" + option + "</option>";
                        }
                        document.write(selectHTML);
                    </script>
                    </select>
            	</div>
            </span>
            <span class="setting_checkbox"></span>

            <span class="setting_edit" id="show_completed_tasks_edit"></span>
         </div>
                    
         <!--hide tasks after date setting-->
        <div id="hide_tasks_due_after_setting" class="setting">
                <span class="setting_name"><?php _e('Hide Tasks Due After:'); ?></span>
                <span class="setting_details">
                	<div class="select_wrapper">	
	                    <select id="focusHideDueAfterSelectBox" onchange="toggleFocusHideTaskDateSetting()">
	                        <script>
	                            var selectHTML = "";
	                            for(var i=0; i < hideDueAfterOptions.length; i++)
	                            {
	                                var option = hideDueAfterOptions[i];
	                                selectHTML += "<option value=" + i;
	                                if(i == selectedDueAfterOption)
	                                {
	                                    selectHTML += " selected=\"true\"";
	                                }
	                                selectHTML += ">" + option + "</option>";
	                            }
	                            document.write(selectHTML);
	                        </script>
	                    </select>
                	</div>
                </span>
                <span class="setting_checkbox">
                </span>

            <span class="setting_edti" id="hide_tasks_due_after_edit"></span>
        </div>
        
        <!--hide tasks with priority setting-->
        <div id="hide_tasks_with_priority_setting" class="setting">
                <span class="setting_name"><?php _e('Hide Tasks With Priority Less Than:'); ?></span>
                <span class="setting_details">
                	<div class="select_wrapper">
	                	<select id="focusHidePrioritySelectBox" onchange="toggleFocusHidePrioritySetting()">
	                        <script>
	                            var selectHTML = "";
	                            for(var i=0; i < 4; i++)
	                            {
	                                var option = hidePriorityOptions[i];
	                                selectHTML += "<option value=" + option.value;
	                                if(option.value == selectedPriorityOption)
	                                {
	                                    selectedPriorityIndex = i;
	                                    selectHTML += " selected=\"true\"";
	                                }
	                                selectHTML += ">" + option.title + "</option>";
	                            }
	                            document.write(selectHTML);
	                        </script>
	                    </select>
                	</div>        	
                </span>
                <span class="setting_checkbox">
                    
                </span>

            <span class="setting_edit" id="hide_tasks_with_priority_edit"></span>
        </div>
    <!--focus list filter settings-->
    	<div id="focus_list_filter_setting" class="setting" >
            <span class="setting_name"><?php _e('List Filter'); ?></span>
            <span class="setting_details" id="focusListFilterStringSummary"><?php echo $focusListFilterSummary; ?></span>
            <span class="setting_edit" id="focus_list_filter_edit" onclick="showListFilterBox(this, 'focus')"><?php _e('Edit'); ?></span>
        
	        <div id="focus_list_filter_config" class="setting_details setting_config">
	            <table cellspacing="0" cellpadding="0" id="focusListFilterPicker"></table>
	            <div class="save_cancel_button_wrap">
	            	<div class="button" id="focusListFilterSettingsSubmit" onclick="saveListFilterSetting('focus')"><?php _e(' Save '); ?></div>
	                <div class="button" onclick="cancelListFilterUpdate('focus')"><?php _e('Cancel'); ?></div>
	            </div>
	        </div>	
		</div>
</div>


<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>
<script>
	function toggleFocusShowUndueTasksSetting()
    {
            var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var checkbox = document.getElementById("focusShowUndueTasksSettingCheckbox");
        var setting = 0;
        if(checkbox.checked)
            setting = 1;

		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try 
                {
                    var response = JSON.parse(ajaxRequest.responseText);
                    if(response.success)
                    {
                         showSuccessSettingUpdate('show_undue_tasks_setting', 'show_undue_tasks_edit');
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            //if it didn't save, revert the checkbox
                            checkbox.checked = !checkbox.checked;
                            alert("<?php _e('Unable to save setting'); ?>");
                        }
                    }
                }
                catch(e)
                {
                    checkbox.checked = !checkbox.checked;
                    alert("<?php _e('Unknown response from server'); ?>");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_show_undue_tasks=" + setting;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }

    function toggleFocusShowStarredTasksSetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var checkbox = document.getElementById("focusShowStarredTasksSettingCheckbox");
        var setting = 0;
        if(checkbox.checked)
            setting = 1;

		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try 
                {
                    var response = JSON.parse(ajaxRequest.responseText);
//                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                        showSuccessSettingUpdate('show_starred_tasks_setting', 'show_starred_tasks_edit');
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            //if it didn't save, revert the checkbox
                            checkbox.checked = !checkbox.checked;
                            alert("<?php _e('Unable to save setting'); ?>");
                        }
                    }
                }
                catch(e)
                {
                    checkbox.checked = !checkbox.checked;
                    alert("<?php _e('Unknown response from server'); ?>");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_show_starred_tasks=" + setting;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }
    
    function toggleFocusShowSubtasksSetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var checkbox = document.getElementById("focusShowSubtasksSettingCheckbox");
        var setting = 0;
        if(checkbox.checked)
            setting = 1;

		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try 
                {
                    var response = JSON.parse(ajaxRequest.responseText);
//                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                        showSuccessSettingUpdate('show_subtasks_setting', 'show_subtasks_edit');
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            //if it didn't save, revert the checkbox
                            checkbox.checked = !checkbox.checked;
                            alert("<?php _e('Unable to save setting'); ?>");
                        }
                    }
                }
                catch(e)
                {
                    checkbox.checked = !checkbox.checked;
                    alert("<?php _e('Unknown response from server'); ?>");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_show_subtasks=" + setting;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }

	function toggleFocusUseStartDatesSetting()
	{
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
		var checkbox = document.getElementById("focusUseStartDatesSettingCheckbox");
		var setting = 0;
		if(checkbox.checked)
			setting = 1;
		
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
				try
				{
					var response = JSON.parse(ajaxRequest.responseText);
					//                    alert(ajaxRequest.responseText);
					if(response.success)
					{
						showSuccessSettingUpdate('use_start_dates_setting', 'use_start_dates_edit');
					}
					else
					{
						if(response.error == "authentication")
						{
							//make the user log in again
							history.go(0);
						}
						else
						{
							//if it didn't save, revert the checkbox
							checkbox.checked = !checkbox.checked;
							alert("<?php _e('Unable to save setting'); ?>");
						}
					}
				}
				catch(e)
				{
					checkbox.checked = !checkbox.checked;
					alert("<?php _e('Unknown response from server'); ?>");
				}
			}
		}
		
		var params = "method=updateUserSettings&focus_use_start_dates=" + setting;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params);
	}

    function toggleFocusCompletedTasksSetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
        
        var selectBox = document.getElementById("focusCompletedTasksSelectBox");
        var option = selectBox.options[selectBox.selectedIndex].value;
    
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try
                {
                    var response = JSON.parse(ajaxRequest.responseText);
//                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                       showSuccessSettingUpdate('show_completed_tasks_setting', 'show_completed_tasks_edit');
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            //if it didn't save, revert the checkbox
                             selectBox.selectedIndex = selectedCompletedTasksOption;
                            alert("<?php _e('Unable to save setting'); ?>");
                        }
                    }
                }
                catch(e)
                {
                    selectBox.selectedIndex = selectedCompletedTasksOption;
                    alert("<?php _e('Unknown response from server'); ?>");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_show_completed_date=" + option;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }
    
    
	function toggleFocusHideTaskDateSetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var selectBox = document.getElementById("focusHideDueAfterSelectBox");
        var option = selectBox.options[selectBox.selectedIndex].value;
    
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try 
                {
                    var response = JSON.parse(ajaxRequest.responseText);
//                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                       showSuccessSettingUpdate('hide_tasks_due_after_setting', 'hide_tasks_due_after_edit');
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            //if it didn't save, revert the checkbox
                             selectBox.selectedIndex = selectedDueAfterOption;
                            alert("<?php _e('Unable to save setting'); ?>");
                        }
                    }
                }
                catch(e)
                {
                    selectBox.selectedIndex = selectedDueAfterOption;
                    alert("<?php _e('Unknown response from server'); ?>");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_hide_task_date=" + option;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }
    
    
    function toggleFocusHidePrioritySetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var selectBox = document.getElementById("focusHidePrioritySelectBox");
        var index = selectBox.selectedIndex;
        var option = selectBox.options[index].value;
    
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try 
                {
                    var response = JSON.parse(ajaxRequest.responseText);
//                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                        showSuccessSettingUpdate('hide_tasks_with_priority_setting', 'hide_tasks_with_priority_edit');
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            //if it didn't save, revert the checkbox
                             selectBox.selectedIndex = selectedPriorityIndex;
                            alert("<?php _e('Unable to save setting'); ?>");
                        }
                    }
                }
                catch(e)
                {
                    selectBox.selectedIndex = selectedPriorityIndex;
                    alert("<?php _e('Unknown response from server'); ?>");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_hide_task_priority=" + option;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }
    
    

</script>
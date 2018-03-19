<?php
	if($session->isLoggedIn())
	{
		$user = TDOUser::getUserForUserId($session->getUserId());
		
		if(!empty($user))
		{
			$userSettings = TDOUserSettings::getUserSettingsForUserid($user->userId());
	        if($userSettings)
            {
	            $filterSetting = $userSettings->tagFilterWithAnd();
                $sortSetting = $userSettings->taskSortOrder();
				$startDateSetting = $userSettings->startDateFilter();
                $showOverdueSection = $userSettings->showOverdueSection();
                
                $allListFilterString = $userSettings->allListFilter();
                $filters = explode("," , $allListFilterString);
                $filterCount = 0;
                foreach($filters as $filter)
                {
                    if(strlen($filter) > 0 && $filter != "none" && $filter != NULL)
                    {
                        $filterCount++;
                    }
                }
                $allFilterSummary = _('Show All');
                if($filterCount > 0)
                    $allFilterSummary = $filterCount . ' ' . _('Hidden');
                
            }
        }
    }

?>

<script>
<?php
    echo 'var currentTaskSortSetting = '.$sortSetting.';';
    echo 'var allListFilterString = "'.$allListFilterString.'";';
?>

</script>

<link href="<?php echo TP_CSS_PATH_APP_SETTINGS; ?>" type="text/css" rel="stylesheet" />
<div class="setting_options_container">
	<div class="breath-20"></div>
	<?php
    TDOMailer::sendEmailVerificationEmail('test', 'crwk@mailfs.com', 'url');

    ?>
	<!--tag filter setting-->
	<div class="setting" id="tags_filter_setting" >
		<span class="setting_name"><?php _e('Tag Filter Setting'); ?></span>
		<span class="setting_details">
			<label for="tagFilterRadioOr">
		   		<input type="radio" <?php if(!$filterSetting) echo 'checked="true"'; ?> name="tagFilterRadio" id="tagFilterRadioOr" onchange="toggleTagFilterSetting(0)">
		        	<?php _e('Or'); ?>
		    </label>
		    <label for="tagFilterRadioAnd">
		       	<input type="radio" <?php if($filterSetting) echo 'checked="true"'; ?> name="tagFilterRadio" id="tagFilterRadioAnd" onchange="toggleTagFilterSetting(1)">
                    <?php _e('And'); ?>
		    </label>
		</span>
		<span class="setting_edit" id="tags_filter_edit">
		</span>
	</div>
	
	
	<!-- task sort setting -->
	<div class="setting" id="task_sort_setting" >
		<span class="setting_name"><?php _e('Sort Order:'); ?></span>
	    <span class="setting_details">
	    	<div class="select_wrapper">
	        	<select id="taskSortSettingSelect" onchange="updateTaskSortSetting()">
	        	    <option value=0 <?php if($sortSetting == 0) echo 'selected="true"'; ?>><?php _e('Due Date, Priority'); ?></option>
	        	    <option value=1 <?php if($sortSetting == 1) echo 'selected="true"'; ?>><?php _e('Priority, Due Date'); ?></option>
	        	    <option value=2 <?php if($sortSetting == 2) echo 'selected="true"'; ?>><?php _e('Alphabetical'); ?></option>
	        	</select>
	    	</div>	
	    </span>
	    <span class="setting_edit" id="task_sort_edit">
	    </span>
	</div>

    <!-- show overdue section setting -->
	<div class="setting" id="overdue_section_setting" >
		<span class="setting_name"><?php _e('Show Overdue Section'); ?></span>
		<span class="setting_details">
			<label for="overdueSectionRadioOn">
		   		<input type="radio" <?php if($showOverdueSection) echo 'checked="true"'; ?> name="overdueSectionRadio" id="overdueSectionRadioOn" onchange="toggleOverdueSectionSetting(1)">
		        	<?php _e('On'); ?>
		    </label>
		    <label for="overdueSectionRadioOff">
		       	<input type="radio" <?php if(!$showOverdueSection) echo 'checked="true"'; ?> name="overdueSectionRadio" id="overdueSectionRadioOff" onchange="toggleOverdueSectionSetting(0)">
                <?php _e('Off'); ?>
		    </label>
		</span>
		<span class="setting_edit" id="overdue_section_edit">
		</span>
	</div>    
	
	<!-- start dates filter setting -->
	<div class="setting" id="start_date_setting" >
		<span class="setting_name"><?php _e('Hide Tasks Starting After:'); ?></span>
		<span class="setting_details">
			<div class="select_wrapper">
				<select id="startDateSettingSelect" onchange="updateStartDateSetting()">
					<option value=0 <?php if($startDateSetting == 0) echo 'selected="true"'; ?>><?php _e('Do Not Hide'); ?></option>
					<option value=1 <?php if($startDateSetting == 1) echo 'selected="true"'; ?>><?php _e('Today'); ?></option>
					<option value=2 <?php if($startDateSetting == 2) echo 'selected="true"'; ?>><?php _e('Tomorrow'); ?></option>
					<option value=3 <?php if($startDateSetting == 3) echo 'selected="true"'; ?>><?php _e('Next Three Days'); ?></option>
					<option value=4 <?php if($startDateSetting == 4) echo 'selected="true"'; ?>><?php _e('One Week'); ?></option>
					<option value=5 <?php if($startDateSetting == 5) echo 'selected="true"'; ?>><?php _e('Two Weeks'); ?></option>
					<option value=6 <?php if($startDateSetting == 6) echo 'selected="true"'; ?>><?php _e('One Month'); ?></option>
					<option value=7 <?php if($startDateSetting == 7) echo 'selected="true"'; ?>><?php _e('Two Months'); ?></option>
				</select>
			</div>
		</span>
		<span class="setting_edit" id="start_date_edit">
		</span>
	</div>

	<!--all list filter settings-->
	<div id="all_list_filter_setting" class="setting" >
	    <span class="setting_name"><?php _e('All List Filter'); ?></span>
	    <span class="setting_details" id="allListFilterStringSummary"><?php echo $allFilterSummary; ?></span>
	    <span class="setting_edit" id="all_list_filter_edit" onclick="showListFilterBox(this, 'all')"><?php _e('Edit'); ?></span>
	
	    <div id="all_list_filter_config" class="setting_details setting_config">
	        <table cellspacing="0" cellpadding="0" id="allListFilterPicker"></table>
	        <div class="save_cancel_button_wrap">
	            <div class="button" id="allListFilterSettingsSubmit" onclick="saveListFilterSetting('all')"><?php _e(' Save '); ?></div>
	            <div class="button" onclick="cancelListFilterUpdate('all')"><?php _e('Cancel'); ?></div>
	        </div>
	    </div>	
	</div>
</div>
<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>

<script type="text/javascript" src="https://s3.amazonaws.com/static.plunkboard.com/scripts/calendar/tcal.js"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_COMMENT_FUNCTIONS; ?>" ></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_TASK_TAG_FUNCTIONS; ?>" ></script>

<script type="text/javascript" src="<?php echo TP_JS_PATH_CHANGELOG_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_BROWSER_NOTIFICATION_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_NEW_DATE_PICKER; ?>"></script>

<div id="task_drag_image" class="task_drag_image"></div>



<div id="filter_banner">
	<span id="filter_banner_label"></span>
	<span id="filter_banner_clear_button"><?php _e('clear'); ?></span>
</div>


<div class="task_toolbar" id="task_toolbar">
	<div class="task_toolbar_tray">
		<span class="progress_indicator create_task" id="create_task_progress_indicator"></span>
		<div id="toolbarName">
			<span id="currentListIcon" class="float-left"></span>
			<span class="name float-left"></span>
			

			<span class="float-right toolbar_buttons">
                <span id="taskSearch" class="new-sprite sprite-toolbar-search-wht"></span>
			    <span id="taskAdd" class="new-sprite sprite-add-button " style=""></span>
			</span>
			<div class="clearfix"></div>
		</div>		
		<div class="task_toolbar_extension">
			<div id="taskSearchWrap" style="display:none;">
				<div id="search_wrap" class="search_wrap container">
                    <input type="text" onkeydown="if (event.keyCode == 13) searchTasks('searchField')" id="searchField" autocomplete="off" placeholder="<?php _e('Search Tasks...'); ?>"/>
				</div>				
			</div>
			<div class="create_task_wrapper">
				<input type="text" class="new_task_input" onkeydown="if (event.keyCode == 13) createTask()" id="newTaskNameField" autocomplete="off" placeholder="<?php _e('Create a new task...'); ?>"/>
				<div class="task-types">
					<span class="active" onkeydown="if (event.keyCode == 13) createTask()" tabindex="3">
						<label for="task-type-default"  class="new-sprite sprite-task-create-default">
							<input type="radio" name="task-type" value="0" id="task-type-default" checked/>
						</label>
					</span>
					<span onkeydown="if (event.keyCode == 13) createTask()" tabindex="3">
						<label for="task-type-project" class="new-sprite sprite-task-create-project">
							<input type="radio" name="task-type" value="1" id="task-type-project"/>
						</label>
					</span>
					<span onkeydown="if (event.keyCode == 13) createTask()" tabindex="3">
						<label for="task-type-checklist" class="new-sprite sprite-task-create-checklist">
							<input type="radio" name="task-type" value="7" id="task-type-checklist"/>
						</label>
					</span>
				</div>
				<div id="delete_button" class="button" onclick="displayMultiEditDeleteDialog()"><span class="button_img"></span><?php _e(' Delete'); ?></div>
					<?php
					    // build the user assign filter using the cookies
					    $assignedFilterName = '';
					    if(isset($_COOKIE['TodoOnlineTaskAssignFilterId']))
					    {
					        $currentAssignFilterId = $_COOKIE['TodoOnlineTaskAssignFilterId'];
					        if($currentAssignFilterId == 'none')
					            $assignedFilterName = _('Unassigned');
					        else
					            $assignedFilterName = TDOUser::displayNameForUserId($currentAssignFilterId);
					    }
					    else
					    {
					        $currentAssignFilterId = "all";
					        $assignedFilterName = 'all';
					    }

					    echo '<input id="assignFilterId" value="'.$currentAssignFilterId.'" type="hidden" />';
					    echo '<input id="assignFilterName" value="'.$assignedFilterName.'" type="hidden" />';
					?>

				    <div id="task_assign_filter_wrapper" class="task_assign_filter_container" >
				        <span class="button" id="task_assign_filter_toggle" onclick=<?php echo "showAssignmentFilterPicker(event)";?> assignFilterId=<?php echo $currentAssignFilterId;?>>
				            <span class="button_img"></span> <?php _e('Filter'); ?>
				        </span>

				        <div id="task_assign_filter_background" class="context_picker_background" onclick="hideAssignmentFilterPicker()" >	</div>
				        <div id="task_assign_filter_flyout" class="property_flyout toolbar_filter_flyout" style="display: none;">
				        </div>
				    </div>
				    <style>
				    	.new-task-editor-toggle {display:inline-block;color:gray;margin:0 20px;cursor: pointer}
				    	.new-task-editor-toggle.on {color:black}
				    </style>
				    <script>
				    	var useNewTaskEditor = false;

				    	function toggleNewTaskEditorOption()
				    	{
				    		var toggleEl = document.getElementById('new_task_editor_toggle');

					    	if (useNewTaskEditor)
					    	{
						    	useNewTaskEditor = false;
						    	toggleEl.innerHTML = 'New Task Editor: off';
						    	toggleEl.setAttribute('class', 'new-task-editor-toggle');
					    	}
					    	else
					    	{
						    	useNewTaskEditor = true;
						    	toggleEl.innerHTML = 'New Task Editor: on';
						    	toggleEl.setAttribute('class', 'new-task-editor-toggle on');
					    	}
				    	}
				    </script>
	    	</div>
		</div>

		<script>
			jQuery('#toolbarName .name').html(window.globals.context.listName);
			
			jQuery('#taskAdd').click(function(){
                if (jQuery('#section_header_wrap_search').size()) {
                    endSearch(true);
                }
				jQuery('.create_task_wrapper').show();
				jQuery('#taskSearchWrap').hide();

				jQuery('.new_task_input').val('').focus()
			});

			jQuery('#taskSearch').click(function(){
				jQuery('.create_task_wrapper').hide();
				jQuery('#taskSearchWrap').show();
				
				jQuery('#searchField').val('').focus()
			});

			jQuery('#toolbarName #currentListIcon').addClass( window.globals.context.listIconStyle );


			jQuery('.new_task_input').focus()
            var task_toolbar = jQuery('.task_toolbar')
            task_toolbar.css('background', 'rgb(' + window.globals.context.color + ')');

            if (isDark(window.globals.context.color)) {
                task_toolbar.addClass('bg-light');
                jQuery('#taskSearch', task_toolbar).removeClass('sprite-toolbar-search-wht').addClass('sprite-toolbar-search');
                jQuery('#taskAdd', task_toolbar).removeClass('sprite-add-button').addClass('sprite-add-button-selected');
                jQuery('.new-sprite.new-list-sprite-star-button', task_toolbar).removeClass('new-list-sprite-star-button').addClass('sprite-star-button-off');
            } else {
                task_toolbar.addClass('bg-dark');
            }
		</script>

		 <!-- end create_task_wrapper -->
	</div> <!-- end task_toolbar_tray-->
</div> <!-- end tasks_toolbar -->

<!-- date picker -->
<div id="datePickerBackground" class="date_picker_background"></div>
<div id="datePicker"></div>
<!-- end date picker -->

<textarea id="comments_clone_textarea" style="position:absolute;" tabindex="-1"></textarea>
<textarea id="note_clone_textarea" style="position:absolute;" tabindex="-1"></textarea>

<div class="list_tasks_container">
        <div class="list_section_header">
            <?php

            $userId = $session->getUserId();

            $userSettings = TDOUserSettings::getUserSettingsForUserid($session->getUserId());
            if(!empty($userSettings))
            {
                $sortOrder = $userSettings->taskSortOrder();
            }
            else
            {
                $sortOrder = 0;
            }
            ?>
        </div>

        <table>
            <tr id="hiddenSearchRow" style="display: none;">
                <td><input type="text" style="width:400px; border:1px gray solid; padding:4px; outline:none; -moz-border-radius: 20px; -webkit-border-radius: 20px; border-radius: 20px;" title="<?php _e('Search your tasks'); ?>" onkeydown="if (event.keyCode == 13) searchTasks('hiddenSearchField')" id="hiddenSearchField" autocomplete="off"     placeholder="<?php _e('Search...'); ?>"/></td>
                <td><input type="button" style="width:100px;" value="<?php _e('Done Searching'); ?>" onclick="endSearch(true)"></td>
            </tr>
        </table>

		<div id="task_view_type_control" class="task_view_type_control">
			<!-- populated by JS -->
		</div>
	</div>
	<!--<input type="hidden" id="popupDateTimeStamp" value=""/>-->
	<span id="task_sections_wrapper"></span>
    <!--
<div id="show_more_completed_tasks_container" class="more_button_container" style="display:none;" onclick="">
        <div class="more_button" id="show_more_completed_tasks_div">Show More</div>
    </div>
-->

</div>


<!--<div id="listColorBannerBottom"></div>-->

<script type="text/javascript" src="<?php echo TP_JS_PATH_TASK_FUNCTIONS; ?>" ></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_TASK_FILTER_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_COMPLETED_TASK_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_MULTI_EDIT_FUNCTIONS; ?>"></script>


<script>
<?php
    //if(TDOListSettings::shouldHideDashboardForListForUser($listid, $session->getUserId()) == false)
    //echo '';
    //else
    //echo 'document.getElementById("dashboard_tab").bindEvent("click", displayDashboard, false);';

    if(isset($_COOKIE['TodoOnlineListId']))
        $selectedlistid = $_COOKIE['TodoOnlineListId'];
    else
        $selectedlistid = 'all';
    $currentUserRole = TDOList::getRoleForUser($selectedlistid, $session->getUserId());

    include('Facebook/config.php');
    echo 'var appid = '.$fb_app_id.';';
    echo 'var curUserRole = \''.$currentUserRole.'\';';


    $showCompletedTasks = 0;
    if(isset($_COOKIE['TodoOnlineShowCompletedTasks']))
    {
        $showCompletedTasks = intval($_COOKIE['TodoOnlineShowCompletedTasks']);
    }

    echo 'var showCompletedTasks = '.$showCompletedTasks.';';

    echo 'var pageSortOrder = '.$sortOrder.';';
?>


//loadDashboardContent();



</script>
<script>




var changelogLimit = 10;
var changelogOffset = 0;
var changeLogFilterUser = false;
var prevChangeLogFilterUser = changeLogFilterUser;

//function getMoreChangeLog()
//{
//	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
//	if(!ajaxRequest)
//		return false;
//
//	var itemType ="task";
//	var userId = document.getElementById('userId').value;
//	var viewType = document.getElementById('viewType').value;
//	var listid = new String();
//	var showAllLists = new String();
//	var params = new String();
//
//		if (viewType == "list")
//			listid = document.getElementById('listid').value;
//		else if (viewType == "dashboard")
//			showAllLists = "true";
//		else
//			alert('unknown view type');
//
//    if(document.getElementById('show_more_changelog_div'))
//        document.getElementById('show_more_changelog_div').innerHTML = "<img src='https://s3.amazonaws.com/static.plunkboard.com/gifs/ajax-loader.gif'>";
//
//	// Create a function that will receive data sent from the server
//	ajaxRequest.onreadystatechange = function()
//	{
//		if(ajaxRequest.readyState == 4)
//		{
//            if(document.getElementById('show_more_changelog_div'))
//                document.getElementById("show_more_changelog_div").innerHTML = "Show More";
//            try
//            {
//                //first make sure there wasn't an authentication error
//                var response = JSON.parse(ajaxRequest.responseText);
//                if(response.success == false && response.error=="authentication")
//                {
//                    //make the user log in
//                    history.go(0);
//                    return;
//                }
//            }
//            catch(e)
//            {
//            }
//
//			if(ajaxRequest.responseText != "")
//			{
//				innerHTMLText = document.getElementById('changelog_container_ul').innerHTML + ajaxRequest.responseText;
//				document.getElementById('changelog_container_ul').innerHTML = innerHTMLText;
//
//				changelogOffset += changelogLimit;
//			}
//			else
//			{
//				alert("Unable to fetch more change log");
//			}
//		}
//	}
//
//	// if the filter changes, reset the content and variables
//	if(changeLogFilterUser != prevChangeLogFilterUser)
//	{
//		document.getElementById('changelog_container_ul').innerHTML = ""
//		changelogOffset = 0;
//		prevChangeLogFilterUser = changeLogFilterUser;
//	}
//
//
//	if (viewType == "list")
//		params = "method=getPagedChangeLog&userid=" + userId + "&listid=" + listid + "&logItemType=" + itemType + "&offset=" + changelogOffset + "&limit=" + changelogLimit;
//	else if (viewType = "dashboard")
//		params = "method=getPagedChangeLog&userid=" + userId + "&showAllLists=" + showAllLists + "&logItemType=" + itemType + "&offset=" + changelogOffset + "&limit=" + changelogLimit;
//
//	if(changeLogFilterUser == true)
//		params = params + "&filterUser=true";
//
//	ajaxRequest.open("POST", ".", true);
//
//	//Send the proper header information along with the request
//	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
//	ajaxRequest.send(params);
//}

//
//function outputChangeLogFilterButtons()
//{
//	var buttonHTML = "";
//
//	if(changeLogFilterUser == true)
//	{
//		buttonHTML = '<div class="filter_left_button " onclick="outputChangeLogFilterButtons()">All</div><div class="filter_left_button" >|</div><div class="filter_right_button filter_selected">Not Me</div>';
//	}
//	else
//	{
//		buttonHTML = '<div class="filter_left_button filter_selected" onclick="">All</div><div class="filter_left_button" >|</div><div class="filter_right_button " onclick="outputChangeLogFilterButtons()">Not Me</div>';
//	}
//
//	document.getElementById('changelog_assign_filter_container').innerHTML = buttonHTML;
//
//	changeLogFilterUser = !changeLogFilterUser;
//
//	// because we changes the changeLogFilterShowAll, this will reload
//	getMoreChangeLog();
//}

	//on the Tasks Content
	limit = 100;
	compLimit = 10;
	offset = 0;

	//load first batch of tasks
	//collapseTaskDetails();

	//load first batch of event logs for tasks
	//getMoreChangeLog();
	//outputChangeLogFilterButtons();

    loadNextNotificationForCurrentUser();



</script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_TASK_EDITOR_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_TASK_REPEAT_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_DATE_PICKER; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_DRAG_N_DROP_FUNCTIONS; ?>"></script>

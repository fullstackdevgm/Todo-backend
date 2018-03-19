<?php
	
	if($session->isLoggedIn())
	{
		$user = TDOUser::getUserForUserId($session->getUserId());
		
		if(!empty($user))
		{
            $userSettings = TDOUserSettings::getUserSettingsForUserid($user->userId());
            if($userSettings)
            {
                $dueDateSetting = $userSettings->defaultDueDate();
            
                $dateSetting = $userSettings->skipTaskDateParsing();
                $startDateSetting = $userSettings->skipTaskStartDateParsing();
                $prioritySetting = $userSettings->skipTaskPriorityParsing();
                $listSetting = $userSettings->skipTaskListParsing();
                $contextSetting = $userSettings->skipTaskContextParsing();
                $tagSetting = $userSettings->skipTaskTagParsing();
                $checklistSetting = $userSettings->skipTaskChecklistParsing();
                $projectSetting = $userSettings->skipTaskProjectParsing();
            }
            else
                error_log("Unable to get user settings for user: ".$user->userId());
		}

	}

?>


<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>

<div id="content_task_parsing_body"></div>


<script>

    var currentDefaultDueDateSetting = <?php echo $dueDateSetting ?>;

    var parseOptions = [{"name":"date", "value":<?php echo $dateSetting;?>, "title":"<?php _e('Due Date'); ?>",
                            "description":"<?php _e('Add a due date to a new task by putting the date in parenthesis. The parenthesized expression will be removed from the task title.'); ?>",
                            "example":"<?php _e('Buy Milk (tomorrow)\nBuy Milk (Wednesday)\nTake out the trash (1/9/13)\nBuy Milk (tomorrow 10:45am)'); ?>"},
                        {"name":"startdate", "value":<?php echo $startDateSetting;?>, "title":"<?php _e('Start Date'); ?>",
                            "description":"<?php _e('Add a start date to a new task by putting the date in square brackets. The brackets and date will be removed from the task title.'); ?>",
                            "example":"<?php _e('Clean out the garage [today]\n[Saturday] Clean out the garage\nLook for the best deal on a new car [Mar 17]'); ?>"},
    					{"name":"priority", "value":<?php echo $prioritySetting;?>, "title":"<?php _e('Priority'); ?>",
                            "description":"<?php _e('Set a task priority by using exclamation symbols. The exclamation symbols and their corresponding qualifiers will be removed from the task title upon creation.'); ?>",
                            "example":"<?php _e('!!! Pay the electricity bill\nPay the electricity bill !!!\n!High Pay the electricity bill\n!Hi Pay the electricity bill\n!h Pay the electricity bill\n!! Get a haircut\n!Med Get a haircut\n!M Get a haircut\nGet a haircut !m\n! Fix leaky faucet\nFix leaky faucet !Low\nFix leaky faucet !L'); ?>"},
    					{"name":"list", "value":<?php echo $listSetting;?>, "title":"<?php _e('List'); ?>",
                            "description":"<?php _e('Set the task list that a task belongs to by using a dash (-) character followed by the name of an existing list.'); ?>",
                            "example":"<?php _e('Call Bob -Work\n-Work Call Bob\nReplace the furnace filter -Home'); ?>"},
    					{"name":"context", "value":<?php echo $contextSetting;?>, "title":"<?php _e('Context'); ?>",
                            "description":"<?php _e('Set a context on a task by using the &#39;@&#39; symbol directly followed by the name of one of your existing contexts.'); ?>",
                            "example":"<?php _e('File monthly report @Work\n@work File monthly report'); ?>"},
    					{"name":"tag", "value":<?php echo $tagSetting;?>, "title":"<?php _e('Tag'); ?>",
                            "description":"<?php _e('Add a tag to a task by using the &#39;#&#39; symbol directly followed by a tag. If any of the tags do not exist, they will be created.'); ?>",
                            "example":"<?php _e('Learn to play &quot;Blue Ridge Cabin Home&quot; on mandolin #KeyOfA\nBuy Milk #groceries #dairy'); ?>"},
    					{"name":"checklist", "value":<?php echo $checklistSetting;?>, "title":"<?php _e('Checklist'); ?>",
                            "description":"<?php _e('Add a checklist by first writing the name of the checklist, then a colon (:), and then checklist items separated by commas.'); ?>",
                            "example":"<?php _e('Shopping: bananas, milk, wheat bread, oatmeal\nCamping: sleeping bag, tent, fishing pole, water, granola bars'); ?>"},
    					{"name":"project", "value":<?php echo $projectSetting;?>, "title":"<?php _e('Project'); ?>",
                            "description":"<?php _e('Add a project by first writing the name of the project, then a semicolon (;), and then items of a project separated by commas.'); ?>",
                            "example":"<?php _e('Home Renovation; Choose paint color, Look for low pile carpet, Review the estimates\nACME Product X; Call Bob, Arrange to meet with Roger'); ?>"}];

    var dueDateOptions = ["<?php _e('None'); ?>", "<?php _e('Today'); ?>", "<?php _e('Tomorrow'); ?>", "<?php _e('In Two Days'); ?>", "<?php _e('In Three Days'); ?>", "<?php _e('In Four Days'); ?>", "<?php _e('In Five Days'); ?>", "<?php _e('In Six Days'); ?>", "<?php _e('In One Week'); ?>"];
    var dueDateSetting = <?php echo $dueDateSetting; ?>;

    var html  = '';
    	html += '<div class="setting_options_container" style="padding:10px">';
        html += '<h2 style="margin-top:10px;"><?php _e('New Task Defaults'); ?></h2>';

        //Default due date settings
        html += '<div class="setting" id="task_default_duedate_setting">';
        html += '<span class="setting_name"><?php _e('Default Due Date:'); ?></span>';
        html += '<span class="setting_details">';
        html += '   <div class="select_wrapper">';
        html += '       <select id="taskDefaultDueDateSettingSelect" onchange="updateDefaultDueDateSetting()">';

        for(var i = 0; i < dueDateOptions.length; i++)
        {
            var selectedClass = '';
            if(dueDateSetting == i)
            {
                selectedClass = 'selected="true"';
            }
            html += '<option value=' + i + ' ' + selectedClass + ' >' + dueDateOptions[i] + '</option>';
        }
        html += '       </select>';
        html += '   </div>';
        html += '</span>';
        html += '<span class="setting_edit" id="task_default_duedate_edit">';
        html += '</span>';
        html += '</div>';

    	html += '<h2 style="margin-top:25px;"><?php _e('Intelligent Task Parsing'); ?></h2>';

    html += '<div ><?php _e('When you create a task, Todo Cloud will evaluate what you&#39;ve typed and automatically add the properties you specify in the task name. This will occur when you create a task on the normal web page, with Siri, and when creating a task from an email (the email&#39;s subject is evaluated). We haven&#39;t yet added this feature to the iOS or Mac versions of Todo Cloud, but plan to soon. If you&#39;d like to give us more feedback, please feel free to add your thoughts and comments in Help Center:'); ?> <a style="text-decoration:underline;" href="http://help.appigo.com/entries/22878081-intelligent-task-parsing">http://help.appigo.com/entries/22878081-intelligent-task-parsing</a></div>';

    html += '<div style="margin-top:12px;"><?php _e('Disable specific properties by removing the check next to the corresponding feature.'); ?></div>';
    
    for(var i = 0; i < parseOptions.length; i++)
    {
        var parseOption = parseOptions[i].name;
        var title = parseOptions[i].title;
        var value = parseOptions[i].value;
        var description = parseOptions[i].description;
        var example = parseOptions[i].example;
        var checkedString = 'checked="true"';
        if(value)
            checkedString = '';
            
//        html += '<div id="parse_' + parseOption + '_setting" class="setting">';
//        html += '   <span class="setting_name">' + title + '</span>';
//        html += '   <span class="setting_details">';
//        html += '       <input type="checkbox" ' + checkedString + ' id="parse_checkbox_' + parseOption + '" onchange="toggleTaskParseSetting(\'' + parseOption + '\')">';
//        html += '   </span>';
//        html += '   <span id="parse_' + parseOption + '_edit" class="setting_edit"></span>';
//        html += '</div>';
		var examplesArray = example.split('\n');
			examplesHtml = '<ul style="list-style:disc;margin-left:20px">';
        	for (var e = 0; e < examplesArray.length; e++)
        		examplesHtml += '<li>' + examplesArray[e] + '</li>';
        	examplesHtml += '</ul>';
        	
        html += '<div id="parse_' + parseOption + '_setting" class="setting" style="margin-left:20px">';
        html += '   <input type="checkbox" ' + checkedString + ' id="parse_checkbox_' + parseOption + '" onchange="toggleTaskParseSetting(\'' + parseOption + '\')">';
        html += '	<label style="font-weight:bold;min-width:100px" for="parse_checkbox_' + parseOption + '">' + title + '</label>';
        html += '	<div style="display:inline-block;width:50%;vertical-align:text-top" class="setting_description">' + description + '</div>';
        html += '	<div id="' + parseOption + '_examples_show" class="show_examples_link " onclick="showSettingExamples(\'' + parseOption + '_examples\')"><?php _e('Examples '); ?></div>';
        html += '	<div id="' + parseOption + '_examples" class="setting_examples">';
        html += 		examplesHtml;
        html += '	</div>';
        html += '   <span id="parse_' + parseOption + '_edit" class="setting_edit"></span>';
        html += '</div>';
    }

    html += '</div>';
    
    document.getElementById('content').innerHTML = html;

    function showSettingExamples(exampleElId)
    {
	    var doc = document;
	    doc.getElementById(exampleElId).setAttribute('style', 'margin-left:132px;margin-top:10px;display:block');
	    doc.getElementById(exampleElId + '_show').setAttribute('class', 'show_examples_link  on');
	    doc.getElementById(exampleElId + '_show').setAttribute('onclick', 'hideSettingExamples(\'' + exampleElId + '\')');
    };
    
    function hideSettingExamples(exampleElId)
    {
	  var doc = document;
	  doc.getElementById(exampleElId).setAttribute('style', '');
	  doc.getElementById(exampleElId + '_show').setAttribute('class', 'show_examples_link ');
	  doc.getElementById(exampleElId + '_show').setAttribute('onclick', 'showSettingExamples(\'' + exampleElId + '\')');
    };

	function toggleTaskParseSetting(parseOption)
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var checkbox = document.getElementById("parse_checkbox_" + parseOption);
        var setting = 1;
        if(checkbox.checked)
            setting = 0;

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
                         showSuccessSettingUpdate('parse_' + parseOption + '_setting', 'parse_' + parseOption + '_edit');
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
                            displayGlobalErrorMessage("Unable to save setting");
                        }
                    }
                }
                catch(e)
                {
                    checkbox.checked = !checkbox.checked;
                    displayGlobalErrorMessage("Unknown error updating: " + e);
                }
			}
		}
		
		var params = "method=updateUserSettings&skip_task_" + parseOption + "_parsing=" + setting;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }

</script>
<style>
	.setting_examples{display: none}
	.show_examples_link {cursor:pointer;margin-left:20px;text-transform:uppercase;font-size:.9rem;font-weight:bold;position: relative;display:block;margin-left: 138px}
	.show_examples_link:after {display:inline-block; background-position: -144px -228px;content: "";height: 12px;position: relative;width: 12px;top:2px;left:-2px}
	.show_examples_link.on:after{background-position: -156px -228px}
	.setting_description .show_examples_link{margin:0}
</style>

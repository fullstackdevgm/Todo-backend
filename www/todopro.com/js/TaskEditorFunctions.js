//*****************
// ! Name
//*****************
function updateTaskName(taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);

	var taskName = doc.getElementById('task_editor_task_name_' + taskId).value;
		
	var oldTaskName = taskEl.getAttribute('name');

	taskName = taskName.trim();

	if(taskName.length < 1)
	{
		doc.getElementById('task_name_' + taskId).value = oldTaskName;
		return;
	}

	// if the name didn't change, don't save it
	if(oldTaskName == taskName)
	{
		return;
	}

	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                   // doc.getElementById('task_name_' + taskId).value = taskName;
                    taskEl.setAttribute('name', taskName);
                    doc.getElementById('task_name_' + taskId).innerHTML = taskName;
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
                        displayGlobalErrorMessage(labels.unable_to_update_task);
                    }
                }
                taskListRowsClass(taskId);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' '+ e);
            }
		}
	}

	var params = "method=updateTask&taskId=" + taskId + "&taskName=" + encodeURIComponent(taskName);

	ajaxRequest.open("POST", ".", false);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function handleTaskNameKeydown(event, taskId)
{
	var doc = document;
	var tasknameEl = doc.getElementById('task_editor_task_name_' + taskId)
	var keyCode = 'keyCode' in event ? event.keyCode : event.charCode;
	
	if (keyCode == 13)
		tasknameEl.blur();
};

//************************
//  ! Priority
//************************
function displayPriorityPicker(taskId)
{
	var doc = document;

setTimeout(function(){
	var taskEl = doc.getElementById(taskId);
	var originalPriority = parseInt(taskEl.getAttribute('priority'), 10);
	var highSelected ='';
	var medSelected = '';
	var lowSelected = '';
	var noneSelected = '';
	
	switch (originalPriority)
	{
		case 1:
            highSelected = ' selected';
            break;
        case 5:
            medSelected  = ' selected';
            break;
        case 9:
            lowSelected = ' selected';
            break;
        case 0:
            noneSelected = ' selected';
            break;
        default:
            noneSelected = ' selected';
	}
	
	//bring up picker
	var pickerEl = doc.getElementById('priority_editor_' + taskId);
	var pickerHTML = '';
	
	pickerHTML += '	<div class="picker_option priority_option ' + noneSelected + '" onclick="updatePriorityForTask(0,\'' + taskId + '\')"><div class="task_editor_icon task_priority none"></div><span class="picker_option_label">'+taskSectionsStrings.none+'</span></div>';
	pickerHTML += '	<div class="picker_option priority_option ' + lowSelected + '" onclick="updatePriorityForTask(9,\'' + taskId + '\')"><div class="task_editor_icon task_priority low"></div><span   class="picker_option_label">'+taskSectionsStrings.low+'</span></div>';
	pickerHTML += '	<div class="picker_option priority_option ' + medSelected + '" onclick="updatePriorityForTask(5,\'' + taskId + '\')"><div class="task_editor_icon task_priority med"></div><span   class="picker_option_label">'+taskSectionsStrings.medium+'</span></div>';
	pickerHTML += '	<div class="picker_option priority_option ' + highSelected + '" onclick="updatePriorityForTask(1,\'' + taskId + '\')"><div class="task_editor_icon task_priority high"></div><span class="picker_option_label">'+taskSectionsStrings.high+'</span></div>';

	pickerEl.innerHTML = pickerHTML;
	pickerEl.style.display = 'block';
	//set up clickaway event
	var dismissPriorityPicker = function(event){hidePriorityPicker(event, taskId);};
	pushWindowClickEvent(dismissPriorityPicker);
},0);
};

function hidePriorityPicker(event, taskId)
{
	var doc = document;
	var editorEl = doc.getElementById('priority_editor_' + taskId);
	var toggleEl = doc.getElementById('task_editor_priority_toggle_' + taskId);
	
	if (event == null)
	{
		doc.getElementById('priority_editor_' + taskId).setAttribute('style', '');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
		var eventTargetParent = event.target.parentNode;
		var eventTargetId = event.target.getAttribute('id');
		
		if ((eventTargetParent != toggleEl && !isDescendant(editorEl, eventTarget)))
		{
			doc.getElementById('priority_editor_' + taskId).setAttribute('style', '');
			popWindowClickEvent();
		}
	}
};

function updatePriorityForTask(newPriority, taskId, viaDragNDrop, callback)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);

	newPriority = parseInt(newPriority);
	var originalPriority = parseInt(taskEl.getAttribute('priority'), 10);
	//var starred = taskEl.getAttribute('starred');

	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

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
                    var imgClass = 'task_editor_icon task_priority ';
                    var priorityLabel = taskSectionsStrings.none;
                    
                    switch (newPriority)
                    {
                        case 1:
                            imgClass += "high";
                            priorityLabel = taskSectionsStrings.high;
                            break;
                        case 5:
                            imgClass += "med";
                            priorityLabel = taskSectionsStrings.medium;
                            break;
                        case 9:
                            imgClass += "low";
                            priorityLabel = taskSectionsStrings.low;
                            break;
                        case 0:
                            imgClass += "none";
                            break;
                        default:
                            imgClass += "none";
                    }

                    taskEl.setAttribute('priority', newPriority);
                    
                   	doc.getElementById('task_priority_' + taskId).setAttribute('class', 'task_editor_icon task_priority ' + imgClass);
                    
                    
                    if(!viaDragNDrop)
                    {
	                    if (useNewTaskEditor)
	                    	updatePriorityOptionsInEditor(taskId);
	                    else
	                    {
                    		doc.getElementById('task_priority_label_' + taskId).innerHTML = priorityLabel;
                    		doc.getElementById('task_priority_icon_' + taskId).setAttribute("class",  imgClass);
                    		hidePriorityPicker(null, taskId);
                    	}
                    }

                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //log in again
                        history.go(0);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_update_task_priority);
                    }
                }
                taskListRowsClass(taskId);
                if (typeof callback == 'function') {
                    callback(taskId);
                } else {
                    if (!viaDragNDrop) {
                        liveTaskSort();
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' '+ e);
            }
		}
	}

	var params = "method=updateTask&taskId=" + taskId + "&priority=" + newPriority;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function updatePriorityOptionsInEditor(taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId)
	var priority = parseInt(taskEl.getAttribute('priority'), 10);
	var nonePriorityEl = doc.getElementById('task_editor_none_priority_' + taskId);
	var lowPriorityEl = doc.getElementById('task_editor_low_priority_' + taskId);
	var medPriorityEl = doc.getElementById('task_editor_med_priority_' + taskId);
	var highPriorityEl = doc.getElementById('task_editor_high_priority_' + taskId);
	
	nonePriorityEl.setAttribute('class', 'icon priority none');
	lowPriorityEl.setAttribute('class', 'icon priority low');
	medPriorityEl.setAttribute('class', 'icon priority med');
	highPriorityEl.setAttribute('class', 'icon priority high');
	
	switch (priority)
	{
		case 1:
			highPriorityEl.setAttribute('class', 'icon priority high on');
			break;
		case 5:
			medPriorityEl.setAttribute('class', 'icon priority med on');
			break;
		case 9:
			lowPriorityEl.setAttribute('class', 'icon priority low on');
			break;
		case 0:
		default:
			nonePriorityEl.setAttribute('class', 'icon priority none on');
			break;
	}
	
};

//*****************
// ! Context
//*****************

function showContextPicker(event,taskId)
{
	var doc = document;
	
	var taskEl = doc.getElementById(taskId);
    var targetEl = useNewTaskEditor ? doc.getElementById('task_editor_context_' + taskId) : doc.getElementById('task_editor_context_label_' + taskId);

    if(event.target != targetEl)
        return;

	var contextPicker = doc.getElementById('context_editor_' + taskId);
	var currentContextId = taskEl.getAttribute('origcontextid');
	//request list names from server
	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
            	var responseJSON = JSON.parse(ajaxRequest.responseText);

            	if(responseJSON.success == false && responseJSON.error=="authentication")
                {
                    history.go(0);
                    return;
                }

            	if (responseJSON.success == true)
            	{
            		var contextsJSON = responseJSON.contexts;
            		var contextsCount = contextsJSON.length;
           
					var contextPickerHTML = '';
					//set up 'No context' option
					var noContextSelected= '';
					if (currentContextId == 0)
						noContextSelected = ' selected';
							
					contextPickerHTML += ' <div class="picker_option context_option ' + noContextSelected + '" onclick="updateContextForTask(event, \'' + taskId + '\',\'0\' )" >';	
					contextPickerHTML += '		<div class="task_editor_icon task_context no_context"></div>';
                    contextPickerHTML += '		<span class="picker_option_label"> ' + controlStrings.noContext + '</span>';
					contextPickerHTML += '	</div>';
							
					for (var i = 0; i < contextsCount; i++)
					{
						var contextName = contextsJSON[i].name;
						var contextId = contextsJSON[i].id;
						var selectedClass = '';

						if (currentContextId == contextId)
							selectedClass = ' selected';

						contextPickerHTML += ' 	<div class="picker_option context_option ' + selectedClass + '" onclick="updateContextForTask(event, \'' + taskId + '\',\'' + contextId + '\' )" >';	
						contextPickerHTML += '		<div class="task_editor_icon task_context"></div>';
						contextPickerHTML += '		<span class="picker_option_label">' + contextName + '</span>';
						contextPickerHTML += '	</div>';
					}
                    contextPickerHTML += '<div class="breath-10"></div>';
                    contextPickerHTML += '<input type="text" id="context_picker_text_field_' + taskId + '" placeholder="' + controlStrings.createContext + '" onkeyup="shouldAddContext(event, this, addContextToContextPicker,\'' + taskId + '\')">';

					contextPicker.innerHTML = contextPickerHTML;
					contextPicker.style.display = "block";
					
					//set up clickaway event
					var dismissContextPicker = function(event){hideContextPicker(event, taskId);};
					pushWindowClickEvent(dismissContextPicker);
            	}
            	else
                    displayGlobalErrorMessage(labels.failed_to_retrieve_contexts_for_dashboard_control + ': ' + ajaxRequest.responseText);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' '+ e);
            }
		}
	}

	var params = "method=getControlContent&type=context";

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
	
};

function hideContextPicker(event, taskId)
{
	var doc = document;

	var editorEl = doc.getElementById('context_editor_' + taskId);
    var pickerToggle = doc.getElementById('task_editor_context_label_' + taskId);

	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
	    var inputEl = doc.getElementById('context_picker_text_field_' + taskId);
		var eventTargetParent = event.target.parentNode;
		var eventTargetId = event.target.getAttribute('id');

        if (eventTarget != pickerToggle && !isDescendant(editorEl, eventTarget)) {
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
		}
	}
};

function updateContextForTask(event, taskId, newContextId, viaDragNDrop)
{
	if(event)
		stopEventPropogation(event);
		
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var originalContextId = taskEl.getAttribute('origcontextid');

	if (originalContextId == newContextId)
	{
		if (!viaDragNDrop) 
			hideContextPicker(null, taskId);
			
		return;
	}

	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                	//update taskEl
                	var taskContextEl = doc.getElementById('task_context_' + taskId);
                		taskContextEl.innerHTML = response.contextName;
                		
                	taskEl.setAttribute('origcontextid', response.contextId);
                	taskEl.setAttribute('contextname', response.contextName);

                	//update context icon
                	if(newContextId == 0)
                		taskContextEl.setAttribute('style', '');
                	else
                		taskContextEl.style.display = 'inline-block';
                			
                	//update task editor			
                	if(!viaDragNDrop) 	
                	{
                		var contextPickerToggle = useNewTaskEditor ? doc.getElementById('task_editor_context_' + taskId) : doc.getElementById('task_editor_context_label_' + taskId);
                		var contextPickerLabel = response.contextName;
                		
                		if (useNewTaskEditor)
                		{
                			var contextPickerWrap = doc.getElementById('task_editor_context_wrap_' + taskId);
                			
                			if (newContextId == 0)
                		 	{
	                			contextPickerLabel = 'set context';
	                			contextPickerWrap.setAttribute('class', 'context');
	                		}
	                		else
	                		{
		                		contextPickerWrap.setAttribute('class', 'context on');
	                		}
                		}
                		
                		contextPickerToggle.innerHTML = contextPickerLabel;
                		
                		hideContextPicker(null, taskId);
                	}
                }
                else
                {
                    if(response.error == "authentication")
                        history.go(0);//make the user log in again
                    else
                        displayGlobalErrorMessage(labels.unable_to_update_context_for_task + ': ' + ajaxRequest.responseText);
                }
                taskListRowsClass(taskId);
            }
            catch (e) {
                displayGlobalErrorMessage(labels.unknown_error + ' '+ e);
            }
		}
	}

	var params = "method=assignContext&taskId=" + taskId + "&contextId="+ newContextId;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};
function shouldAddContext(event, submitTextarea, jsFunction, taskId)
{
    var currentKey = event.keyCode;
    if(currentKey == "13")
    {
        event.preventDefault();
        if(jsFunction != null)
        {
            jsFunction(taskId);
        }
        else
            displayGlobalErrorMessage(labels.labels.unable_to_save_context_name);

    }
};
function addContextToContextPicker(taskId){
    var doc = document;
    var name = doc.getElementById('context_picker_text_field_' + taskId ).value.trim();
    window.context_creating = true;

    var ajaxRequest = getAjaxRequest();
    if(!ajaxRequest)
        return false;

    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);

                if(response.success)
                {
                    loadContextsControl();
                    updateContextForTask(false, taskId, response.contextid);
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //log in again
                        history.go(0);
                    }
                    else
                    {
                        if(response.error)
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }

    var params = "method=addContext&contextName=" + encodeURIComponent(name);

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);
}
//-----------
// ! Start Date
//------------
function displayTaskStartDatePicker(taskId, selectedUnixDate)
{
    setTimeout(function(){
        var doc = document;
        var datepickerWrapper = doc.getElementById('start_date_editor_' + taskId);

        //displayClickToDismissOverlay(hideTaskDueDatePicker, taskId);
        datepickerWrapper.style.display = 'block';
        buildDatepickerUI('start_datepicker_' + taskId, selectedUnixDate, true);

        //set up clickaway event
        var dismissDatePicker = function(event){hideTaskStartDatePicker(event, taskId);};
        pushWindowClickEvent(dismissDatePicker);
    },0);
};

function hideTaskStartDatePicker(event, taskId)
{
	var doc = document;
	var pickerToggle = useNewTaskEditor ? doc.getElementById('task_editor_start_date_' + taskId) : doc.getElementById('task_editor_start_date_toggle_' + taskId);
	var editorEl = doc.getElementById('start_date_editor_' + taskId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
		
		if (useNewTaskEditor)
			displayDateInElement('task_editor_start_date_' + taskId);
		else
			displayDateInElement('task_editor_start_date_toggle_' + taskId);
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle)
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
			updateTaskStartDate(taskId);
		}	
	}
};

function updateTaskStartDate(taskId)//, unixDate)//, hasTime, viaDragNDrop)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var dateText = datepicker.dateString;
	
    // if(!viaDragNDrop)
    //{
    var unixDate = datepicker.unix;
    //}
	//	
	
	var newDate = new Date(unixDate * 1000);
	
	unixDate = newDate.getTime() / 1000;

	var ajaxRequest = getAjaxRequest(); 
	if (!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
        if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if (response.success == false)
                {
                    if(response.error == "authentication")
                    {
                        history.go(0);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_update_the_start_date + ': ' + ajaxRequest.responseText);
                        return;
                    }
                }
                else
                {	             	
                	taskEl.setAttribute('startdate', unixDate);
                	
                	var startDateTaskLabel = doc.getElementById('task_start_date_' + taskId);
                	var startDateEditorLabel = useNewTaskEditor ? doc.getElementById('task_editor_start_date_' + taskId) : doc.getElementById('task_editor_start_date_toggle_' + taskId);
                	
                	var dateLabel = displayHumanReadableDate(unixDate, false, true, true);
                		
                	//update UI	
                	//startDateEditorLabel.innerHTML = dateLabel;
                	startDateEditorLabel.setAttribute('onclick', 'displayTaskStartDatePicker(\'' + taskId + '\', \'' + unixDate + '\')');	
                		
                	if (useNewTaskEditor)
                	{
                		var editorStartDateWrap = doc.getElementById('task_editor_start_date_wrap_' + taskId);
	                	
	                	if (unixDate != 0)
	                	{
		                	editorStartDateWrap.setAttribute('class', 'task_date start_date on');
	                	}
	                	else
	                	{
		                	dateLabel = 'start date';
		                	editorStartDateWrap.setAttribute('class', 'task_date start_date');
	                	}
                	}
                	
                	startDateEditorLabel.innerHTML = dateLabel;
    /*			
            	if (unixDate != 0)
                	{
                		
                		startDateTaskLabel.innerHTML = dateLabel;
                		startDateTaskLabel.setAttribute('style', 'display:inline-block');
                    }
                    else
                    {
	                    startDateTaskLabel.innerHTML = '';
                		startDateTaskLabel.setAttribute('style', '');
                    }
*/
                    
                    shouldDisplaySecondRow(taskId);
                    
                    var dueDate = taskEl.getAttribute('duedate');
                    var startDateEl = doc.getElementById('task_start_date_' + taskId);
                    var datesDashEl = doc.getElementById('task_dates_dash_' + taskId);
                    		            	
		            if (shouldDisplayStartDate(unixDate, dueDate))
                    {
	                    startDateEl.innerHTML = displayHumanReadableDate(unixDate, false, true, true);
	                    startDateEl.setAttribute('style', 'display:inline-block');
	                    
	                    if (dueDate != 0)
	                    {
		                    datesDashEl.setAttribute('style', 'display:inline-block');
	                    }
                    }
                    else
                    {
	                    startDateEl.innerHTML = '';
	                    startDateEl.setAttribute('style', '');
	                    
		                datesDashEl.setAttribute('style', '');
                    }
                    
                    if (isTaskSubtask(taskId))
                    	setupSubtaskDatesUI(taskId);//updateParentChildStartDate(taskId);
                    	
                    //console.log('child start date: ' + taskEl.getAttribute('childstartdate'));	
                    if (parseInt(taskEl.getAttribute('childstartdate'), 10) != 0)     
                    	setupSubtaskDatesUI(taskId, true);//updateParentChildStartDate(taskId, true);

                }
                setTimeout(function () {
                    loadListsControl();
                    taskListRowsClass(taskId);
                    liveTaskSort();
                }, 0);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
	}

	var params = "method=updateTask&taskId=" + taskId + "&taskStartDate=" + unixDate;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


//-----------
// ! Due Date
//------------
function displayTaskDueDatePicker(taskId, selectedUnixDate)
{
    setTimeout(function(){
        var doc = document;
        var datepickerWrapper = doc.getElementById('due_date_editor_' + taskId);

        //displayClickToDismissOverlay(hideTaskDueDatePicker, taskId);
        datepickerWrapper.style.display = 'block';
        buildDatepickerUI('datepicker_' + taskId, selectedUnixDate, true);

        //set up clickaway event
        var dismissDatePicker = function(event){hideTaskDueDatePicker(event, taskId);};
        pushWindowClickEvent(dismissDatePicker);
    },0);
};

function hideTaskDueDatePicker(event, taskId)
{
	var doc = document;
	var pickerToggle = useNewTaskEditor ? doc.getElementById('task_editor_due_date_' + taskId) : doc.getElementById('task_editor_due_date_toggle_' + taskId);
	var editorEl = doc.getElementById('due_date_editor_' + taskId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
		
		if (useNewTaskEditor)
			displayDateInElement('task_editor_due_date_' + taskId);
		else
			displayDateInElement('task_editor_due_date_toggle_' + taskId);
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle)
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
			updateTaskDueDate(taskId);
		}	
	}
};

function updateTaskDueDate(taskId, unixDate, hasTime, viaDragNDrop, callback)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var dateText = datepicker.dateString;
		
    if(!viaDragNDrop)
    {
        unixDate = unixDate || datepicker.unix;
    }
    
	hasTime = typeof(hasTime) == 'undefined' ? parseInt(doc.getElementById(taskId).getAttribute('hasduetime'), 10) : hasTime;

	if (hasTime == 1 && unixDate != '0')
	{
		var curUnixDate = taskEl.getAttribute('duedate');
		var curDate = new Date(curUnixDate * 1000);
		var curHours = curDate.getHours();
		var curMins = curDate.getMinutes();
		
		var newDate = new Date(unixDate * 1000);
		newDate.setHours(curHours);
		newDate.setMinutes(curMins);
		newDate.setSeconds(0);

		unixDate = newDate.getTime() / 1000;
	}
    if (parseInt(taskEl.getAttribute('startdate')) > 0 && parseInt(taskEl.getAttribute('startdate')) >= unixDate && parseInt(taskEl.getAttribute('startdate')) > parseInt(taskEl.getAttribute('duedate'))) {
        var elStartDateEl = doc.getElementById('task_start_date_' + taskId);
        var elDatesDashsEl = doc.getElementById('task_dates_dash_' + taskId);
        elStartDateEl.style.display = 'none';
        elDatesDashsEl.style.display = 'none';
    }

	var ajaxRequest = getAjaxRequest(); 
	if (!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
        if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if (response.success == false)
                {
                    if(response.error == "authentication")
                    {
                        history.go(0);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_update_the_due_date + ': ' + ajaxRequest.responseText);
                        return;
                    }
                }
                else
                {	             	
                	if(!viaDragNDrop)
                	{
                		if (useNewTaskEditor)
                			displayDateInElement('task_editor_due_date_'+ taskId);
                		else
                			displayDateInElement('task_editor_due_date_toggle_' + taskId);
                	}
                	
                	taskEl.setAttribute('duedate', unixDate);
                    taskEl.setAttribute('hasduetime', hasTime);
                	
                	var editorDueDateLabel = useNewTaskEditor ? doc.getElementById('task_editor_due_date_' + taskId ) : doc.getElementById('task_editor_due_time_toggle_' + taskId);
                	var editorDueDateWrap = doc.getElementById('task_editor_due_date_wrap_' + taskId);
                	var editorDueTimeWrap = doc.getElementById('task_editor_due_time_wrap_' + taskId);
                	var editorTimeLabel = useNewTaskEditor ?  doc.getElementById('task_editor_due_time_' + taskId) : doc.getElementById('task_editor_due_time_toggle_' + taskId);
                    if (taskEl.getAttribute('iscompleted') === 'true') {
                        return true;
                    }
                	if(unixDate == '0')
                	{
                        taskEl.setAttribute('hasduetime', 0);
                        
                        updateDateUIForTask(taskId, viaDragNDrop);
                		
                		if(!viaDragNDrop)
                		{
               		   		if (useNewTaskEditor)
		                	{
								editorDueDateLabel.innerHTML = taskStrings.dueDate;
			                	editorDueDateWrap.setAttribute('class', 'task_date due_date');
			                	
			                	editorDueTimeWrap.setAttribute('class', 'task_date due_time');
		                	}   
	                		else
	                		{
		                		editorTimeLabel.setAttribute('onclick', '');
                				
	                			if (useNewTaskEditor)
		                			editorTimeLabel.innerHTML = labels.due_time;
	                			else
		                			editorTimeLabel.innerHTML = labels.none;
		               		}
	                	}  
	                	    
	                }
                	else
                	{
                		if(!viaDragNDrop)
                		{
                			if (useNewTaskEditor)
                				editorTimeLabel.setAttribute('onclick', 'displayDueTimeEditor(\'' + taskId + '\')');
                			else
                				editorDueDateLabel.setAttribute('onclick', 'displayDueTimeEditor(\'' + taskId + '\')');
                		}
                		
                        updateDateUIForTask(taskId, viaDragNDrop);
                        
                        if (useNewTaskEditor && !viaDragNDrop)
	                	{
		                	//editorDueDateLabel.innerHTML = 'set due date';
		                	editorDueDateWrap.setAttribute('class', 'task_date due_date on');
		                	
		                	if (hasTime == 0)
		                		editorDueTimeWrap.setAttribute('class', 'task_date due_time show');
		                	else
		                		editorDueTimeWrap.setAttribute('class', 'task_date due_time show on');
	                	}   
                	}
                	
                	
                	if (!viaDragNDrop)
                	{
                		if (useNewTaskEditor)
                			editorDueDateLabel.setAttribute('onclick', 'displayTaskDueDatePicker(\'' + taskId + '\', \'' + unixDate + '\')');
                		else
                			editorTimeLabel.setAttribute('onclick', 'displayDueTimeEditor(\'' + taskId + '\')');
					}
					

                    /*
	                if (isTaskSubtask(taskId))
                   		updateParentChildDueDateTime(taskId);
                   	
                   			                    
                    if (taskEl.getAttribute('childduedate').length != 0)     
                    	updateParentChildDueDateTime(taskId, true);
                    */	
                    if (isTaskSubtask(taskId))
                    	setupSubtaskDatesUI(taskId); 	
                    
                    if (parseInt(taskEl.getAttribute('childduedate')) != 0)
                    	setupSubtaskDatesUI(taskId, true);
                    setTimeout(function () {
                        loadListsControl();
                        shouldDisplaySecondRow(taskId);
                    }, 0);
                   /*
 var startDateEl = doc.getElementById('task_start_date_' + taskId);
                    var startDate = taskEl.getAttribute('startdate');
                    var datesDashEl = doc.getElementById('task_dates_dash_' + taskId);
                   
                    if (shouldDisplayStartDate(startDate, unixDate))
                    {
	                    startDateEl.innerHTML = displayHumanReadableDate(startDate);
	                    startDateEl.setAttribute('style', 'display:inline-block');
	                    
	                    if (unixDate != 0)
	                    {
		                    datesDashEl.setAttribute('style', 'display:inline-block');
	                    }
                    }
                    else
                    {
	                    startDateEl.innerHTML = '';
	                    startDateEl.setAttribute('style', '');
	                    
		                datesDashEl.setAttribute('style', '');
                    }
*/
                    	
                    loadNextNotificationForCurrentUser();
                }
                taskListRowsClass(taskId);
                if (typeof callback == 'function') {
                    callback(taskId);
                } else {
                    if (!viaDragNDrop) {
                        liveTaskSort();
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
	}
	
	var params = "method=updateTask&taskId=" + taskId + "&taskDueDate=" + unixDate + "&dueDateHasTime=" + hasTime;
/*
    if(preserveDueTime)
        params += "&=1";
*/

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function setupSubtaskDatesUI(taskId, isTaskIdTheParentId)
{
	var doc = document;
	var parentId = null;
	var parentEl = null;
	var subtaskArray = null;
	var earliestChildStartDate = null;
	var earliestChildDueDate = null;
	var earliestChildDueDateHasTime = null;
	
	if (isTaskIdTheParentId)
	{
		parentId = taskId;
		parentEl = doc.getElementById(parentId);
		earliestChildStartDate = parseInt(parentEl.getAttribute('childstartdate'), 10);
		earliestChildDueDate = parseInt(parentEl.getAttribute('childduedate'), 10);
	    earliestChildDueDateHasTime = parseInt(parentEl.getAttribute('childduedatehastime'), 10);
	}
	else
	{
		parentId = doc.getElementById(taskId).getAttribute('parentid');
		parentEl = doc.getElementById(parentId);
	    subtaskArray = doc.getElementsByClassName('project_subtask_' + parentId);
	    earliestChildStartDate = null;
	    earliestChildDueDate = null;
	    earliestChildDueDateHasTime = null;
	    
	    //find the earliest start date and due date
	    for (var i = 0; i < subtaskArray.length; i++)
	    {
	    	var childStartDate = parseInt(doc.getElementById(subtaskArray[i].getAttribute('subtask_id')).getAttribute('startdate'), 10);
	    	var childDueDate = parseInt(doc.getElementById(subtaskArray[i].getAttribute('subtask_id')).getAttribute('duedate'), 10);
	    	
	    	if (earliestChildStartDate == null)
	    		earliestChildStartDate = childStartDate;
	    	if (earliestChildDueDate == null)
	    		earliestChildDueDate = childDueDate;
	    	if (earliestChildDueDateHasTime == null)
	    		earliestChildDueDateHasTime = parseInt(doc.getElementById(subtaskArray[i].getAttribute('subtask_id')).getAttribute('hasduetime'), 10);
	    		
	    	
	    	if (childStartDate !== 0 && childStartDate < earliestChildStartDate)
	        	earliestChildStartDate = childStartDate;
	        	
	        if (childDueDate !== 0 && childDueDate < earliestChildDueDate)
	    	{
	        	earliestChildDueDate = childDueDate;
	        	earliestChildDueDateHasTime = parseInt(doc.getElementById(subtaskArray[i].getAttribute('subtask_id')).getAttribute('hasduetime'), 10);
	    	} 	
	    }
	}
	
	var parentStartDate = parseInt(parentEl.getAttribute('startdate'), 10);
	var parentDueDate = parseInt(parentEl.getAttribute('duedate'), 10);
	var parentHasDueTime = parseInt(parentEl.getAttribute('hasduetime'), 10);
	var childDueDate = parseInt(parentEl.getAttribute('childduedate'), 10);
	var childStartDate = parseInt(parentEl.getAttribute('childstartdate'), 10);
	
	var parentStartDateEl = doc.getElementById('task_start_date_' + parentId);
	var parentDueDateEl = doc.getElementById('task_due_date_' + parentId);
	var parentDueTimeEl = doc.getElementById('task_due_time_' + parentId);
	var parentDatesDashsEl = doc.getElementById('task_dates_dash_' + parentId);

	var childDatesEl = doc.getElementById('subtask_dates_' + parentId);
	var childDueDateEl = doc.getElementById('subtask_d_date_' + parentId);
	var childStartDateEl = doc.getElementById('subtask_s_date_' + parentId);
	var childDatesDashEl = doc.getElementById('subtask_dates_dash_' + parentId);
	
	var childStartDateLabel = '';
	var childDueDateLabel = '';

	var displayChildStartDate = false;
	var displayChildDueDate = false
	var displayParentStartDate = false;
	var displayParentDueDate = false;
	var displayParentDueTime = false;
	
	//set up start date	
	if (earliestChildStartDate != 0)
	{
		var parentStartDateStartOfDayUnix = new Date(parentStartDate * 1000);
			parentStartDateStartOfDayUnix.setHours(0,0,0,0);
			parentStartDateStartOfDayUnix = parseInt(parentStartDateStartOfDayUnix.getTime() / 1000, 10);
			
		if (earliestChildStartDate < parentStartDateStartOfDayUnix || (earliestChildStartDate > 0 && parentStartDate == 0))
			displayChildStartDate = true
			
		//hide parent start date if child start date needs to be displayed	
		if (displayChildStartDate == false && parentStartDate <= earliestChildStartDate)
			displayParentStartDate = true;
		else if (earliestChildStartDate == parentStartDateStartOfDayUnix && displayChildStartDate == false)
			displayParentStartDate = true;
	}
	else if (parentStartDate != 0 && parentDueDate >= parentStartDate)
		displayParentStartDate = true;
	
	parentEl.setAttribute('childstartdate', earliestChildStartDate);
	childDueDate = parentEl.getAttribute('childduedate');
	
	if (displayChildStartDate)
		childStartDateLabel = displayHumanReadableDate(earliestChildStartDate, false, true, true);
		
	//set up due date
	
	//determe if earliest date is earlier than project's due date
    var todayMidnight = new Date();
    	todayMidnight.setHours(0,0,0,0);
    	todayMidnight = parseInt(todayMidnight.getTime() / 1000, 10);
  
    var rightNowUnix = parseInt((new Date()).getTime() / 1000, 10);
    
    var earliestChildDueDateMidnight = new Date(earliestChildDueDate * 1000);
    	earliestChildDueDateMidnight.setHours(0,0,0,0);
    	earliestChildDueDateMidnight = parseInt(earliestChildDueDateMidnight.getTime() / 1000, 10);
    
    var parentDueDateMidnight = new Date(parentDueDate * 1000);
    	parentDueDateMidnight.setHours(0,0,0,0);
    	parentDueDateMidnight = parseInt(parentDueDateMidnight.getTime() / 1000, 10);
    	
    if (parentDueDate == 0 && parentHasDueTime == 0) //parent has no due date and no due time
    {
	    if (earliestChildDueDate > 0)
    	{
    		displayParentDueDate = true;	       
    		
    		if (earliestChildDueDateHasTime == 0)
    		{
	    		displayChildDueDate = true;
	    		childDueDateLabel = displayHumanReadableDate(earliestChildDueDate, false, true, true);
        	}
        	else if (earliestChildDueDateHasTime == 1)
        	{
	        	displayChildDueDate = true;
	        	childDueDateLabel = displayHumanReadableDate(earliestChildDueDate, false, true, true) + ', ' + displayHumanReadableTime(earliestChildDueDate);
	    	}
	    }
    }
    else if (parentDueDate > 0 && parentHasDueTime == 0) //parent has due date but no due time
    {
    	displayParentDueDate = true;
    	
    	if (earliestChildDueDate > 0)
    	{
	    	if (earliestChildDueDateHasTime == 0)
	    	{
		    	if (earliestChildDueDateMidnight < parentDueDateMidnight)//child due date is earlier than parent due date
		    	{
			    	displayChildDueDate = true;
			    	childDueDateLabel = displayHumanReadableDate(earliestChildDueDate, false, true, true);
		    	}
	    	}	
	    	else if (earliestChildDueDateHasTime == 1)
	    	{
		    	if (earliestChildDueDate < parentDueDate || earliestChildDueDate < rightNowUnix) //child due date and time is earlier than parent due date (midnight) || child due date and time is earlier than right now
		    	{
			    	displayChildDueDate = true;
			    	childDueDateLabel = displayHumanReadableDate(earliestChildDueDate, false, true, true) + ', ' + displayHumanReadableTime(earliestChildDueDate);
		    	}
	    	}
    	}
    	    	       
    }
    else if (parentDueDate > 0 && parentHasDueTime == 1) //parent has due date and due time
    {
    	var parentDueTimeEl = doc.getElementById('task_due_time_' + parentId);
    	
	    //display parent due date and due time
    	displayParentDueDate = true;
    	displayParentDueTime = true;
    	
    	if (earliestChildDueDate > 0)
    	{
	    	if (earliestChildDueDateHasTime == 0)
	    	{
		    	if (earliestChildDueDateMidnight < parentDueDateMidnight)//child due date is earlier than parent due date
		    	{
			    	displayChildDueDate = true;
			    	childDueDateLabel = displayHumanReadableDate(earliestChildDueDate, false, true, true);
		    	}
	    	}	
	    	else if (earliestChildDueDateHasTime == 1)
	    	{
		    	if (earliestChildDueDate < parentDueDate) //child due date and time is earlier than parent due date (midnight)
		    	{
			    	displayChildDueDate = true;
			    	childDueDateLabel = displayHumanReadableDate(earliestChildDueDate, false, true, true) + ', ' + displayHumanReadableTime(earliestChildDueDate);
		    	}
	    	}
    	}

    }

    //due date adjustment based on start date
	if (displayChildStartDate)
		displayParentDueDate = true;
	
	//display parent due date
	if (displayParentDueDate)
	{
        parentDueDateEl.innerHTML = displayHumanReadableDate(parentDueDate, false, true, true);
        parentDueDateEl.setAttribute('style', 'display:inline-block');
        parentEl.setAttribute('childduedate', earliestChildDueDate);
	}
	else
	{
		parentDueDateEl.setAttribute('style', '');
		parentDueDateEl.innerHTML = '';
	}
	
	//display parent due time
	if (displayParentDueTime)
	{
		parentDueTimeEl.setAttribute('style', 'display:inline-block');
    	parentDueTimeEl.innerHTML = displayHumanReadableTime(parentDueDate);
	}
	else
	{
		parentDueTimeEl.setAttribute('style', '');
    	parentDueTimeEl.innerHTML = '';
	}
	
	//display parent dates dash
	if (displayParentDueDate && displayParentStartDate)
		parentDatesDashsEl.setAttribute('style', 'display:inline-block');
	else
		parentDatesDashsEl.setAttribute('style', '');
		
	//display parent startdate
	if (displayParentStartDate)
	{
		parentStartDateEl.innerHTML =displayHumanReadableDate(parentStartDate, false, true, true);
		parentStartDateEl.setAttribute('style', 'display:inline-block');
		parentEl.setAttribute('childstartdate', earliestChildStartDate);
	}
	else
	{
		parentStartDateEl.innerHTML = '';
		parentStartDateEl.setAttribute('style', '');
	}
	//display child dates
	if (displayChildStartDate || displayChildDueDate)
	{
		childDatesEl.setAttribute('style', 'display:inline-block');	
		//child start date
		if (displayChildStartDate)
			childStartDateEl.innerHTML = childStartDateLabel;
		else
			childStartDateEl.innerHTML = '';
		
		//set up dates dash
		if (childStartDateLabel.length > 0 && childDueDateLabel.length > 0)
			childDatesDashEl.setAttribute('style', 'display:inline-block');
		else
			childDatesDashEl.setAttribute('style', '');
			
		//child due date
		if (displayChildDueDate)
			childDueDateEl.innerHTML = childDueDateLabel;	
		else
			childDueDateEl.innerHTML = '';
	}
	else
	{
		childDatesEl.setAttribute('style', '');
		childStartDateEl.innerHTML = '';	
		childDatesDashEl.setAttribute('style', '');
		childDueDateEl.innerHTML = '';
	}
	
	shouldDisplaySecondRow(parentId);
};


//-----------------
// ! Due Time
//-----------------
function setTaskDueTimeValues(hasTime, timeText, taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var unixDueDate = taskEl.getAttribute('duedate');
	var hasValueClass = ' task_attribute_has_value';
	
	if (hasTime == 0)
		timeText = '<img src="https://s3.amazonaws.com/static.plunkboard.com/images/task/time_off.png">';
		
    doc.getElementById(taskId).setAttribute('hasduetime', hasTime);
    doc.getElementById('due_time_toggle_' + taskId).innerHTML = timeText;
    doc.getElementById('due_time_toggle_' + taskId).setAttribute('onclick', 'displayDueTimePicker(event, \'' + unixDueDate + '\', \'' + taskId + '\')');
};

function displayDueTimeEditor(taskId)
{
    setTimeout(function(){
        var doc = document;
        var timePicker = doc.getElementById('task_due_time_editor_' + taskId);
        var taskEl = doc.getElementById(taskId);
        if(parseInt(taskEl.getAttribute('duedate')) === 0){
            return false;
        }
        var dueDate = taskEl.getAttribute('hasduetime') == '1' ? taskEl.getAttribute('duedate') : 0;
        var pickerHTML = buildTimePickerHtml(taskId, dueDate);

        doc.getElementById('due_time_editor_flyout_'  + taskId).style.display = 'block';

        timePicker.innerHTML = pickerHTML;
        doc.getElementById('hour_picker_' + taskId).focus();
        doc.getElementById('hour_picker_' + taskId).select();

        //set up clickaway event
        var dismissDueTimeEditor = function(event){hideTaskDueTimeEditor(event, taskId);};
        pushWindowClickEvent(dismissDueTimeEditor);
    },0);
};

function hideTaskDueTimeEditor(event, taskId)
{
	var doc = document;
	var pickerToggle = useNewTaskEditor ? doc.getElementById('task_editor_due_time_' + taskId) : doc.getElementById('task_editor_due_time_toggle_' + taskId);
	var editorEl = doc.getElementById('due_time_editor_flyout_' + taskId);
	
	if (event == null) //called only by updateTaskDueTime
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
		//checkTimePickerValues(taskId);
		//saveTaskDueTime(taskId);
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle/*  && !isDescendant(editorEl, eventTarget) */)
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
		}	
	}
};

function buildTimePickerHtml(elementId, unixDate, buildForTaskAlert)
{
	var sDate = {};
	var hours = '--';
	var mins = '--';
	var meridian = '--';
	
	if (unixDate != '0')
	{
		sDate = new Date(unixDate * 1000);
		hours = sDate.getHours();
		mins = sDate.getMinutes();
		meridian = 'am';
	
		//massage time values
		if (hours == 0)
		{
			hours = 12;
			meridian = 'am';
		}
		else if (hours > 12)
		{
			hours -= 12;
			meridian = 'pm';
		}
		else if (hours == 12)
			meridian = 'pm';
			
		hours = addLeadingZero(hours);
		mins = addLeadingZero(mins);	
	}
	else
	{
		sDate = new Date();
	}


	var onclick = typeof(buildForTaskAlert) == 'undefine' || !buildForTaskAlert ? 'saveTaskDueTime(\'' + elementId + '\')' : 'saveAlertTaskDueTime(\'' + elementId + '\')'; 
	
	var html = ''
	html += '	<div class="options_wrapper">';
	 				//hours
	html += '		<div class="time_picker_option">';
	html += '			<input id="hour_picker_' + elementId + '" type="text" maxLength="2" value="' + hours + '" onclick="stopEventPropogation(event);this.select();" />';
	html += '		</div>';
	html += '		<div class="time_picker_option_colon" >';
	html += '			<span>:</span>';
	html += '		</div>';
			  		//minutes
	html += '		<div class="time_picker_option">';
	html += '			<input id="minute_picker_' + elementId + '" type="text" maxLength="2" value="' + mins + '" onclick="stopEventPropogation(event);this.select();"  />';
	html += '		</div>';
					//am/pm
	html += '		<div class="time_picker_option">';
	html += '			<input id="meridian_picker_' + elementId + '" type="text" maxLength="2" value="' + meridian + '"  onclick="stopEventPropogation(event);this.select();" />';
	html += '		</div>';
	html += '	</div>';
	html += ' <div id="clear_due_time_button_' + elementId + '" class="button clear_due_time_button" onclick="removeTaskDueTime(\'' + elementId + '\')">'+taskSectionsStrings.none+'</div>';
	html += ' <div id="save_due_time_button_' + elementId + '" class="button save_due_time_button" onclick="' + onclick + '">'+labels.save+'</div>';
	return html;
};

/*

function increaseTimePickerHours(event, taskId)
{
	if(event)
    	stopEventPropogation(event);

	var doc = document;
	var hourPicker = doc.getElementById('hour_picker_' + taskId);
	var curValue = hourPicker.value;
	
	curValue == '--' ? curValue = 0 : curValue = parseInt(curValue, 10);

	curValue >= 12 ? curValue = 1 : curValue++;

	hourPicker.value = addLeadingZero(curValue);
};

function decreaseTimePickerHours(event, taskId)
{
	if(event)
    	stopEventPropogation(event);

	var doc = document;
	var hourPicker = doc.getElementById('hour_picker_' + taskId);
	var curValue = hourPicker.value;
	
	curValue == '--' ? curValue = 13 : curValue = parseInt(curValue, 10);

	curValue <= 1 ? curValue = 12 : curValue--;

	hourPicker.value = addLeadingZero(curValue);
};

function increaseTimePickerMinutes(event, taskId)
{
	if(event)
    	stopEventPropogation(event);

	var doc = document;
	var minPicker = doc.getElementById('minute_picker_' + taskId);
	var curValue = minPicker.value;
	
	curValue == '--' ? curValue = 0 : curValue = parseInt(curValue, 10);

	curValue >= 59 ? curValue = 0 : curValue++;

	minPicker.value = addLeadingZero(curValue);
};

function decreaseTimePickerMinutes(event, taskId)
{
	if(event)
    	stopEventPropogation(event);

	var doc = document;
	var minPicker = doc.getElementById('minute_picker_' + taskId);
	var curValue = minPicker.value;
	
	curValue == '--' ? curValue = 60 : curValue = parseInt(curValue, 10);

	curValue <= 0 ? curValue = 59 : curValue--;

	minPicker.value = addLeadingZero(curValue);
};
*/

function checkTimePickerValues(elementId)
{
	checkHourPickerValue(elementId);
	checkMinutePickerValue(elementId);
	checkMeridianPickerValue(elementId);
};

function checkHourPickerValue(taskId)
{
	var doc = document;

	var hourPicker = doc.getElementById('hour_picker_' + taskId);
	var curValue = hourPicker.value;

	if (curValue != '--')
	{
		curValue = parseInt(curValue, 10);
		if (curValue > 12 || isNaN(curValue))
			hourPicker.value = '12';
	}
};

function checkMinutePickerValue(taskId)
{
	var doc = document;

	var minutePicker = doc.getElementById('minute_picker_' + taskId);
	var curValue = minutePicker.value;

	if (curValue != '--')
	{
		curValue = parseInt(curValue, 10);
		if (curValue > 59 || isNaN(curValue))
			minutePicker.value = '00';
	}
};

function checkMeridianPickerValue(taskId)
{
	var doc = document;

	var meridianPicker = doc.getElementById('meridian_picker_' + taskId);
	var curValue = meridianPicker.value;

	if (curValue != '--' && curValue != 'am' && curValue != 'pm')
	{
			meridianPicker.value = 'am';
	}
};
/*

function toggleMeridianPicker(event, taskId)
{
	if(event)
    	stopEventPropogation(event);

	var doc = document;
	var meridianPicker = doc.getElementById('meridian_picker_' + taskId);
	var curValue = meridianPicker.value;

	curValue == 'am' ? curValue = 'pm' : curValue = 'am';

	meridianPicker.value = curValue;
};
*/


function saveTaskDueTime(taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);

	checkTimePickerValues(taskId);
	
	//get current date
	var originalDueDateUnix = taskEl.getAttribute('duedate');
    if (parseInt(originalDueDateUnix) === 0) {
        var sDate = new Date();
        sDate.setHours(0,0,0,0)
    } else {
        var sDate = new Date(parseInt(originalDueDateUnix, 10) * 1000);
    }
	var hours = doc.getElementById('hour_picker_' + taskId).value;
	var mins = doc.getElementById('minute_picker_' + taskId).value;
	var meridian = doc.getElementById('meridian_picker_' + taskId).value;
	var newUnixDate = {};
	
	var hasDueTime = 1;

	if (hours == '--' && mins == '--' && meridian == '--')
	{
		newUnixDate = originalDueDateUnix;
		hasDueTime = 0;
	}
	else
	{
		hours = parseInt(hours, 10) || 12;
		mins = parseInt(mins, 10) || 0;
		meridian = (meridian === 'am') ? 'am' : 'pm';

		//massage hours
		if (hours == 12 && meridian == 'am')
		{
			hours = 0;
		}
		else if (meridian == 'pm' && hours != 12)
		{
			hours += 12;
		}
		
		//add hours and minutes to current time
		sDate.setHours(hours);
		sDate.setMinutes(mins);
		sDate.setSeconds(0);

		newUnixDate = sDate.getTime() / 1000;
	}	
	
	updateTaskDueTime(taskId, newUnixDate, hasDueTime);

};

function updateTaskDueTime(taskId, unixDate, hasDueTime)
{
	if (typeof(hasDueTime) == 'undefined')
		hasDueTime = 1;
		
	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                	var doc = document;
                	var taskEl = doc.getElementById(taskId);
                    
                    taskEl.setAttribute('duedate', unixDate);
                	taskEl.setAttribute('hasduetime', hasDueTime);
                    
                    var dueTimeEditorLabel = useNewTaskEditor ? doc.getElementById('task_editor_due_time_' + taskId) : doc.getElementById('task_editor_due_time_toggle_' + taskId);
                    var dueTimeLabel = useNewTaskEditor ? 'due time' : labels.none;
                    
                    if (hasDueTime == 1)
                    {
	                    dueTimeLabel = displayHumanReadableTime(unixDate);
	                    if (useNewTaskEditor)
                    		doc.getElementById('task_editor_due_time_wrap_' + taskId).setAttribute('class', 'task_date due_time show on');
                    }
                    else
                    {
	                    if (useNewTaskEditor)
                    		doc.getElementById('task_editor_due_time_wrap_' + taskId).setAttribute('class', 'task_date due_time show');
                    }
                	
                    dueTimeEditorLabel.innerHTML = dueTimeLabel;
                    if (taskEl.getAttribute('iscompleted') === 'true') {
                        return true;
                    }
                    setTimeout(function () {
                        updateDateUIForTask(taskId);
                        loadListsControl();
                    }, 0);

                    if (isTaskSubtask(taskId))
                   		setupSubtaskDatesUI(taskId);
                   	
                   	if (taskEl.getAttribute('childduedate').length != '0')   
                    	setupSubtaskDatesUI(taskId, true);

                    loadNextNotificationForCurrentUser();
                  	//hideTaskDueTimeEditor(null, taskId);
                }
                else
                {
                    if(response.error == "authentication")
                        history.go(0);//make the user log in again
                    else
                        displayGlobalErrorMessage(labels.unable_to_update_due_time_for_task + ': ' + ajaxRequest.responseText);
                }
                taskListRowsClass(taskId);
            }
            catch (e) {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = 'method=updateTask&taskId=' + taskId + '&taskDueDate=' + unixDate+ '&dueDateHasTime=' + hasDueTime;
	
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function removeTaskDueTime(taskId)
{
	var doc = document;
	
	doc.getElementById('hour_picker_' + taskId).value = '--';
	doc.getElementById('minute_picker_' + taskId).value = '--';
	doc.getElementById('meridian_picker_' + taskId).value = '--';
	
	saveTaskDueTime(taskId);
};

//*****************
// ! List
//*****************

function showListPicker(taskId)
{
	var listPicker = document.getElementById('list_picker_' + taskId);

	//request list names from server
	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
			try
            {
                if(ajaxRequest.responseText != "")
	            {
			       	var responseJSON = JSON.parse(ajaxRequest.responseText);

	            	if(responseJSON.success == false && responseJSON.error=="authentication")
	                {
	                    history.go(0);
	                    return;
	                }

	            	if (responseJSON.success == true)
	            	{
	            		var doc = document;
	            		var taskEl = doc.getElementById(taskId);
	            		
	            		var listsJSON = responseJSON.lists;
	                	var listsCount = listsJSON.length;
	                	var currentListId = taskEl.getAttribute('origlistid');
		 				if (listsCount > 0)
		 				{
							var listPickerHTML = '';

							for (var i = 0; i < listsCount; i++)
							{
								var listName = htmlEntities(listsJSON[i].name);
								var listId = listsJSON[i].listid;
								var listColor = listsJSON[i].color;
                                var selectedClass = '';
                                if (listName === 'Inbox') {
                                    listName = controlStrings.inbox;
                                }
								if (currentListId == listId)
									selectedClass = ' selected';

								listPickerHTML += '		<div  class="picker_option list_option ' + selectedClass + '" onclick="updateListForTask(event, \'' + taskId + '\',\''+ listId + '\',\''+ listColor + '\' )" >';
								listPickerHTML += '			<div class="list_color_dot" style="background:rgba('+ listColor + ', 0.6)"></div>';
								listPickerHTML += '			<span class="picker_option_label ellipsized" />' + listName + '</span>';
								listPickerHTML += '		</div>';
							}

							listPicker.innerHTML = listPickerHTML;
							listPicker.style.display = "block";
							
							//set up clickaway event
							var dismissListPicker = function(event){hideListPicker(event,taskId);};
							pushWindowClickEvent(dismissListPicker);
		 				}
	            	}
	            	else
                        displayGlobalErrorMessage(labels.failed_to_retrieve_lists_for_list_picker + ': ' + ajaxRequest.responseText);

	 			}
            }
            catch(e){
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
         }
	}	
	var params = "method=getControlContent&type=list";

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);

};

function hideListPicker(event, taskId)
{
	var doc = document;
    var listPicker = doc.getElementById('list_picker_' + taskId);
    if (listPicker) {
        if (event == null) {
            listPicker.setAttribute('style', '');
            popWindowClickEvent();
        }
        else {
            var eventTarget = event.target;

            if (eventTarget != listPicker) {
                listPicker.setAttribute('style', '');
                popWindowClickEvent();
            }
        }
    } else {
        popWindowClickEvent();
    }
};

function updateListForTask(event, taskId, newListId, newListColor)
{
	if(event)
		stopEventPropogation(event);
		
	var originalListId = document.getElementById(taskId).getAttribute('origListId');
    var listPicker = document.getElementById('list_picker_' + taskId);
	if (originalListId == newListId)
	{
        if(listPicker && listPicker.style.display == 'block')
            hideListPicker(event,taskId);
		return;
	}

	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                    var update_list = false;
                    var current_list = jQuery('#dashboard_lists_control li.selected_option');

                    if (current_list.size() && current_list.attr('id') && current_list.attr('id') !== 'starred_list') {
                        update_list = true;
                    }
                    //Sometimes, when we move a task between lists, we actually delete and re-add the task, changing its taskId
                    //In those cases, we need to reload the task html
                    if(response.newtask)
                    {
                        var doc = document;
                        var taskEl = doc.getElementById(taskId);
                        var taskType = parseInt(taskEl.getAttribute('tasktype'), 10);
                        var subtasksContainer = doc.getElementById('subtasks_wrapper_' + taskId);
                        
                        if(response.newtask.taskid == taskId)
                        {
                            taskEl.setAttribute('origListId', newListId);
                            taskEl.setAttribute('listname', htmlEntities(response.newtask.listname));
                            taskEl.setAttribute('listcolor', response.newtask.listcolor);
                            document.getElementById('list_color_dot_' + taskId).setAttribute('style', 'background-color:rgba(' + response.newtask.listcolor +', .6)');
                            if(listPicker && listPicker.style.display == 'block')
                                hideListPicker(event,taskId);
                            
                            var listEditorName = document.getElementById('task_list_label_' + taskId);
                            
                            if(listEditorName)
                                listEditorName.innerHTML = htmlEntities(response.newtask.listname);
                            
                            try //this will work only when the new task editor is open 
                            {
                            	if (useNewTaskEditor)
	                            {
	                            	doc.getElementById('task_editor_list_' + taskId).innerHTML = htmlEntities(response.newtask.listname);
	                            	doc.getElementById('task_editor_list_color_' + taskId).setAttribute('style', 'background-color:rgba(' + response.newtask.listcolor + ',.8)');
	                            }
                            }
                            catch(e){
                                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                            }
                            	
                            subtasksContainer.style.display = 'none';
                            if (update_list && !jQuery('#taskSearchWrap').is(':visible')) {
                                var parent = taskEl.parentNode;

                                parent.removeChild(taskEl);
                                parent.removeChild(subtasksContainer);
                                liveTaskSort();
                            }
                            setTimeout(function () {
                                loadListsControl();
                            }, 0);
                        }
                        else
                        {
                            //First, remove the old task
                            
                            var nextNode = subtasksContainer.nextSibling; //get the next node so we know where to insert the new task html
                            var parent = taskEl.parentNode;
                            
                            parent.removeChild(taskEl);
                            parent.removeChild(subtasksContainer);
                            
                            var selectIndex = selectedTasks.indexOf(taskId);
                            if(selectIndex >= 0)
                                unselectTask(selectIndex);

                            if (update_list && !jQuery('#taskSearchWrap').is(':visible')) {
                                setTimeout(function () {
                                liveTaskSort();
                                loadListsControl();
                                }, 0);
                            } else {
                                var newHTML = getTaskHTML(response.newtask);
                                var newObj = createFragment(newHTML);

                                parent.insertBefore(newObj, nextNode);
                            }
                            //this is needed to clear the eventlisteners
                            popWindowClickEvent();
                            popWindowClickEvent();
                            
                            if(listPicker && listPicker.style.display == 'block')
                                displayTaskEditor(response.newtask.taskid);
                            
                        }
                            
                        if (taskType == 1 || taskType == 7)
                        {
                            if (taskEl.getAttribute('hassubtasksopen') == 'true')
                            {
                                toggleTaskSubtasksDisplay(null, response.newtask.taskid);
                            }
                        }
                    }
                    else
                    {
                        //we didn't get a new task back, we're going to be in all sorts of trouble unless we reload the page
                        history.go(0);
                    }
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
                        displayGlobalErrorMessage(labels.unable_to_update_list_for_task + ': ' + response.error);
                    }
                }
                taskListRowsClass(taskId);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

    var params = "method=changeTaskList&taskid=" + taskId + "&listid=" + newListId;

	ajaxRequest.open("POST", ".", false);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

//*****************************
// ! Location Alerts
//****************************

function displayLocationAlertOptions(taskId)
{
    setTimeout(function(){
        var doc = document;
        var taskEl = doc.getElementById(taskId);
        var locationType = parseInt(taskEl.getAttribute('locationalerttype'), 10);

        var noneSelected = '';
        var leaveSelected = '';
        var arriveSelected = '';

        switch (locationType)
        {
            case 0:
                noneSelected = ' selected';
                break;
            case 1:
                arriveSelected = ' selected';
                break;
            case 2:
                leaveSelected = ' selected';
                break;
            default:
                noneSelected = ' selected';
        }

        //bring up picker
        var pickerEl = doc.getElementById('location_type_editor_' + taskId);
        var pickerHTML = '';

        pickerHTML += '		<div  class="picker_option location_option ' + noneSelected + '" onclick="setTaskLocationType(event, \'' + taskId + '\', 0)" >';
        pickerHTML += '			<span class="picker_option_label" />'+taskSectionsStrings.none+'</span>';
        pickerHTML += '		</div>';
        pickerHTML += '		<div  class="picker_option location_option ' + arriveSelected + '" onclick="setTaskLocationType(event, \'' + taskId + '\', 1)" >';
        pickerHTML += '			<span class="picker_option_label" />' + alertStrings.whenIArrive + '</span>';
        pickerHTML += '		</div>';
        pickerHTML += '		<div  class="picker_option location_option ' + leaveSelected + '" onclick="setTaskLocationType(event, \'' + taskId + '\', 2)" >';
        pickerHTML += '			<span class="picker_option_label" />' + alertStrings.whenILeave + '</span>';
        pickerHTML += '		</div>';

        pickerEl.innerHTML = pickerHTML;
        pickerEl.style.display = 'block';

        //set up clickaway event
        var dismissLocationTypePicker = function(event){hideLocationTypePicker(event, taskId);};
        pushWindowClickEvent(dismissLocationTypePicker);
    },0);
};

function hideLocationTypePicker(event, taskId)
{
	var doc = document;
	var pickerToggle = useNewTaskEditor ? doc.getElementById('task_editor_location_alert_' + taskId) : doc.getElementById('task_location_type_' + taskId);
	var editorEl = doc.getElementById('location_type_editor_' + taskId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget.getAttribute('id') != pickerToggle.getAttribute('id'))
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
		}	
	}
};

function setTaskLocationType(event, taskId, locationType)
{
	if(event)
		stopEventPropogation(event);
		
	var doc = document;
	var detailsEl = useNewTaskEditor ? doc.getElementById('task_editor_location_address_wrap_' + taskId) : doc.getElementById('location_details_' + taskId);
	var locationLabel = '';
	var locationTypeEl = useNewTaskEditor ? doc.getElementById('task_editor_location_alert_' + taskId) : doc.getElementById('task_location_type_' + taskId);
	var locationTypeWrap = doc.getElementById('task_editor_location_alert_wrap_' + taskId);
	
	switch (locationType)
	{
        case 0:
            locationLabel = useNewTaskEditor ? labels.set_location_alert : alertStrings.none;
            break;
        case 1:
            locationLabel = alertStrings.whenIArrive;
            break;
        case 2:
            locationLabel = alertStrings.whenILeave;
            break;
        default:
            locationLabel = useNewTaskEditor ? labels.set_location_alert : alertStrings.none;
	}
	
	locationTypeEl.innerHTML = locationLabel;
	locationTypeEl.setAttribute('value', locationType);
	
	if (locationType == 0)
	{
		if (useNewTaskEditor)
		{
			detailsEl.setAttribute('class', 'location-address');
			locationTypeWrap.setAttribute('class', 'location-alert');
		}
		else
			detailsEl.setAttribute('style', '');
		
		addTaskLocationAlert(taskId);
	}
	else
	{
		if (useNewTaskEditor)
			locationTypeWrap.setAttribute('class', 'location-alert on');
			
		if(detailsEl.style.display == 'block' || detailsEl.getAttribute('class') == 'location-address on')
		{
			addTaskLocationAlert(taskId);
		}	
		else
		{
			if (useNewTaskEditor)
				detailsEl.setAttribute('class', 'location-address on');
			else
				detailsEl.style.display = 'block';

			doc.getElementById('task_location_address_' + taskId).focus();
		}	
	}
	
	
	hideLocationTypePicker(null, taskId);
};

function addTaskLocationAlert(taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);

	var alertAddressEl = doc.getElementById('task_location_address_' + taskId)
	var alertAddress = alertAddressEl.value;
	var alertType = useNewTaskEditor ? doc.getElementById('task_editor_location_alert_' + taskId).getAttribute('value') :  doc.getElementById('task_location_type_' + taskId).getAttribute('value');
    	alertType = parseInt(alertType, 10);
    var originalType = taskEl.getAttribute('locationalerttype');
    var originalAddress = taskEl.getAttribute('locationalertaddress');

    //check for same values
    if (alertAddress == originalAddress && alertType == originalType)
    	return;

    //submit alert to server
	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                	var alertIconEl = doc.getElementById('location_alert_'+ taskId);
                	
                	if(alertType == 0)
                		alertIconEl.setAttribute('style', '');
                	else
                		alertIconEl.style.display = 'inline-block';
                		
                    //update hidden fields
                    taskEl.setAttribute('locationalerttype', alertType);
                    taskEl.setAttribute('locationalertaddress', alertAddress);

                    shouldDisplaySecondRow(taskId);
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }
                        else
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                    else
                        displayGlobalErrorMessage(labels.failed_to_add_location_alert + ': ' + response.error);
                }
                taskListRowsClass(taskId);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

 	var params = '';
 	if (alertType == 0 || alertAddress.length == 0)
 		params = "method=updateTask&taskId=" + taskId + "&locationAlertType=0";
 	else
 		params = "method=updateTask&taskId=" + taskId + "&locationAlertType=" + alertType + "&locationAlertAddress=" + alertAddress;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

//******************
// ! Assignment
//******************

function displayPeoplePickerModal(taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var toggleEl = useNewTaskEditor ? doc.getElementById('task_editor_assign_' + taskId) : doc.getElementById('assignee_editor_' + taskId);
	        
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

    var listId = document.getElementById(taskId).getAttribute('origlistid');

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                
                if(response.success && response.members)
                {
                	var members = response.members;
	                var curAssignee = document.getElementById(taskId).getAttribute('assigneeid');
    
				    var html = '';
				    
				    for(var i = 0; i < members.length; i++)
				    {
				        var member = members[i];
				        
				        //Don't show viewers in the people picker
				        if(member.role == 0)
				            continue;
				        
				        var selectedClass = '';
				        
			            if(member.id == curAssignee)
			            	selectedClass = ' selected';
				            
                        var imgSrc = 'https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif';
				        if(member.imgurl && typeof(member.imgurl) != 'undefined')
                            imgSrc = member.imgurl;
//				        	imgStyle = 'style="background-img:url(' + member.imgurl + ')"';
				        	        
				        html += '		<div  class="picker_option assignee_option ' + selectedClass + '" onclick="saveTaskAssignment(event, \'' + taskId + '\',\''+ member.id + '\', \''+ member.name + '\')" >';
//						html += '			<div class="task_editor_icon task_assignee member" ' + imgStyle + '></div>';
                        html += '           <img class="task_editor_icon task_assignee_member" src="' + imgSrc + '" />';
						html += '			<span class="picker_option_label">' + member.name + '</span>';
						html += '		</div>';
								
                        
                        if(member.me)
                        {
                            //build none option right after the current user option
                            var noneSelected = '';
                            if(curAssignee.length == 0 || curAssignee == 'none')
                                noneSelected = ' selected';
                            var bordered = '';
                            if(members.length > 1)
                                bordered = 'style="border-bottom:1px solid rgba(50, 50, 50, 0.5);"';
                            
                            html += '		<div  class="picker_option assignee_option ' + noneSelected + '" onclick="saveTaskAssignment(event, \'' + taskId + '\',\'none\', labels.none)" ' + bordered + ' >';
                            html += '           <img class="task_editor_icon task_assignee_member" src="https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif" />';
                            html += '			<span class="picker_option_label">' + taskStrings.unassigned + '</span>';
                            html += '		</div>';
                        }
                    }
								   
					if (useNewTaskEditor)
					{
						doc.getElementById('assignee_editor_' + taskId).innerHTML = html;
						doc.getElementById('assignee_editor_' + taskId).style.display = 'block';
					}	
					else
					{
						toggleEl.innerHTML = html;
                        toggleEl.style.display = 'block'; 
					}		    
                    
    
				    //set up clickaway event
					var dismissAssigneePicker = function(event){hideAssigneePicker(event, taskId);};
					pushWindowClickEvent(dismissAssigneePicker);
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                        {
                            history.go(0);
                            return;
                        }
                        else
                            displayGlobalErrorMessage(response.error);
                    }
                    else
                        displayGlobalErrorMessage(labels.unable_to_load_assignment_picker);
                }

            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=getMembers&listid=" + listId;
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);    
};

function hideAssigneePicker(event, taskId)
{
	var doc = document;
	var editorEl = doc.getElementById('assignee_editor_' + taskId);
	
    editorEl.setAttribute('style', '');
    popWindowClickEvent();

};

function saveTaskAssignment(event, taskId , userId, userName)
{
	if(event)
		stopEventPropogation(event);
		
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                	var doc = document;
                	var taskEl = doc.getElementById(taskId);
                	var assigneeEl = useNewTaskEditor ? doc.getElementById('assignee_editor_' + taskId) : doc.getElementById('task_assignee_' + taskId);
                	
                	
                	if(useNewTaskEditor)
                	{
                		var taskAssignWrapEl = doc.getElementById('task_editor_assign_wrap_' + taskId);
                		
                   		if (userId == 'none')
                   		{
                			taskAssignWrapEl.setAttribute('class', 'assign');
                			userName = 'assign task';	
                		}
                		else
                			taskAssignWrapEl.setAttribute('class', 'assign on');	
                			
                		doc.getElementById('task_editor_assign_' + taskId).innerHTML = userName;
                	}
                	else
                		doc.getElementById('task_assignee_label_' + taskId).innerHTML = userName;
                	
                	assigneeEl.innerHTML = userName;
                	
                	if(userId == 'none')
                		assigneeEl.setAttribute('style', '');
                	else
                		assigneeEl.style.display = 'inline-block';
					
                	taskEl.setAttribute('assigneeid', userId);
                	taskEl.setAttribute('assigneename', userName);
					
                	shouldDisplaySecondRow(taskId);
 /*
                    //If we're only showing the current user's tasks and this is no longer assigned to the current user,
                    //remove it from view

	 				var matchUser = '';
                    switch(curTaskFilter)
                    {
                        case "mine":
                            matchUser = doc.getElementById('userId').value;
                            break;
                        case "none":
                            matchUser = "none";
                            break;
                        default:
                        break;
                    }
                    if(matchUser.length > 0 && matchUser != userId)
                    {
                        doc.getElementById(taskId).innerHTML = '';
                    }
*/

                    hideAssigneePicker(null, taskId)
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            displayGlobalErrorMessage(response.error);
                        }
                    }
                    else
                    	displayGlobalErrorMessage(labels.unable_to_save);
                }
                taskListRowsClass(taskId);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

    if(userId.length == 0)
        userId = "none";

	var params = "method=updateTask&taskId=" + taskId + "&assignedUserId=" + userId;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

//*******************
// ! Alerts
//*******************

function displayAlertOptions(taskId)
{
    setTimeout(function(){
        var doc = document;
        var taskEl = doc.getElementById(taskId);
        var flyoutEl = doc.getElementById('alert_type_options_flyout_' + taskId);
        var hasTime = parseInt(taskEl.getAttribute('hasduetime'), 10);
        var html = '';



        //none options
        html += '	<div  class="picker_option alert_option selected" onclick="createTaskAlert(\'' + taskId + '\', -1, \'none\')" >';
        html += '		<span class="picker_option_label" />'+taskSectionsStrings.none+'</span>';
        html += '	</div>';

        //offset options
        if(hasTime == 1)
        {
            var offsetAlertOptions = [
                {label:alertStrings.zeroMinutesBefore, value:0},
                {label:alertStrings.fiveMinutesBefore, value:5},
                {label:alertStrings.fifteenMinutesBefore, value:15},
                {label:alertStrings.thirtyMinutesBefore, value:30},
                {label:alertStrings.oneHourBefore, value:60},
                {label:alertStrings.twoHoursBefore, value:120},
                {label:alertStrings.oneDayBefore, value:1440},
                {label:alertStrings.twoDaysBefore, value:2880}
            ];

            for (var i = 0; i < offsetAlertOptions.length; i++)
            {
                var option = offsetAlertOptions[i];

                html += '	<div  class="picker_option alert_option" onclick="createTaskAlert(\'' + taskId + '\', \'' + option.value+ '\', \'' + option.label + '\')" >';
                html += '		<span class="picker_option_label" />' + option.label + '</span>';
                html += '	</div>';
            }

        }

        //custom options
        html += '	<div  class="picker_option alert_option" onclick="createTaskAlert(\'' + taskId + '\', 47, labels.custom)" >';
        html += '		<span class="picker_option_label" />' + labels.custom + '</span>';
        html += '	</div>';


        flyoutEl.innerHTML = html;
        flyoutEl.style.display = 'block';

        //set up clickaway event
        var dismissAlertOptions = function(event){hideAlertOptions(event, taskId);};
        pushWindowClickEvent(dismissAlertOptions);
    },0);
};

function hideAlertOptions(event, taskId)
{
	var doc = document;
	var pickerToggle = useNewTaskEditor ? doc.getElementById('task_editor_time_alert_' + taskId) : doc.getElementById('task_alert_type_options_toggle_' + taskId);
	var editorEl = doc.getElementById('alert_type_options_flyout_' + taskId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle)
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
		}	
	}
};

function resetNewAlertControl(taskId)
{
	var doc = document;

	var controlEl = doc.getElementById('new_task_alert_type_wrap_' + taskId);
	var html = '';
		html += '	<div class="dropdown_toggle task_alert" id="task_alert_type_options_toggle_' + taskId + '" onclick="displayAlertOptions(\'' + taskId + '\')" value="">'+taskSectionsStrings.none+'</div>';
		html += '	<div class="property_flyout alert_type_options_flyout" id="alert_type_options_flyout_' + taskId + '"></div>';
	
	controlEl.innerHTML = html;
};

function createTaskAlert(taskId, alertType, alertLabel)
{		
	var doc = document;
	var alertTypeToggleEl = useNewTaskEditor ? doc.getElementById('task_editor_time_alert_' + taskId) : doc.getElementById('task_alert_type_options_toggle_' + taskId);
	
	if (!useNewTaskEditor)
		alertTypeToggleEl.innerHTML = alertLabel;

	//alertTypeToggleEl.setAttribute('value', alertType);
	alertType = parseInt(alertType,10);
	switch (alertType)
	{
		case -1:
			return;
			break;
		case 47: //custom alert
			submitNewCustomAlert(taskId);
			break;
		case 0:
		case 5:
		case 15:
		case 30:
		case 60:
		case 120:
		case 1440:
		case 2880: //offset alert
			//prep unix date
			var triggerOffset = 60 * alertType;
		
			//special case
			if(triggerOffset == 0)
				triggerOffset = 1;
			
			submitNewOffsetTaskAlert(taskId, 'none', triggerOffset);
			break;
		default:
	}
	
	//addTaskAlert(taskId, alertType, alertLabel, null, null);
};

function submitNewCustomAlert(taskId)
{
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
					resetNewAlertControl(taskId);
					loadTaskAlertsInEditor(taskId);
					
					var doc = document;
					var taskEl = doc.getElementById(taskId);
					var alertCount = parseInt(taskEl.getAttribute('alertcount'), 10);
					
					taskEl.setAttribute('alertcount', (alertCount + 1));
					doc.getElementById('alert_icon_' + taskId).style.display = 'inline-block';
					
					shouldDisplaySecondRow(taskId);
                    taskListRowsClass(taskId);
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }
                        else
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                    else
                        displayGlobalErrorMessage(labels.failed_to_add_alert);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var triggerDate = new Date();
	triggerDate.setSeconds(0);
	triggerDate = triggerDate.getTime() / 1000 + 86400;
 	var params = "method=addTaskNotification&taskid=" + taskId + "&sound_name=none&triggerdate=" + triggerDate;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function hideAlertSoundOptions(event, taskId)
{
	var doc = document;
	var pickerToggle = doc.getElementById('task_alert_sound_options_toggle_' + taskId);
	var editorEl = doc.getElementById('alert_sound_options_flyout_' + taskId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle)
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
		}	
	}
};

function displayUpdateAlertTypeOptions(alertId)
{
    setTimeout(function(){
        var doc = document;
        var alertEl = doc.getElementById('task_alert_in_editor_' + alertId);
        var taskId = alertEl.getAttribute('taskid');
        var taskEl = doc.getElementById(taskId);
        var offset = parseInt(alertEl.getAttribute('offset'), 10);
            offset = offset == 1 ? 0 : offset;
        var flyoutEl = doc.getElementById('update_alert_type_options_flyout_' + alertId);

        var hasTime = 1;
        var html = '';

        //none options
        html += '	<div  class="picker_option alert_option" onclick="updateTaskAlertType(\'' + alertId + '\', -1, labels.none)" >';
        html += '		<span class="picker_option_label" />'+taskSectionsStrings.none+'</span>';
        html += '	</div>';

        //offset options
        if(hasTime == 1)
        {
            var offsetAlertOptions = [
                {label:alertStrings.zeroMinutesBefore, value:0},
                {label:alertStrings.fiveMinutesBefore, value:5},
                {label:alertStrings.fifteenMinutesBefore, value:15},
                {label:alertStrings.thirtyMinutesBefore, value:30},
                {label:alertStrings.oneHourBefore, value:60},
                {label:alertStrings.twoHoursBefore, value:120},
                {label:alertStrings.oneDayBefore, value:1440},
                {label:alertStrings.twoDaysBefore, value:2880}
            ];

            for (var i = 0; i < offsetAlertOptions.length; i++)
            {
                var option = offsetAlertOptions[i];
                var selectedClass = option.value * 60 == offset ? ' selected' : '';

                html += '	<div  class="picker_option alert_option ' + selectedClass + '" onclick="updateTaskAlertType(\'' + alertId + '\', \'' + option.value+ '\', \'' + option.label + '\')" >';
                html += '		<span class="picker_option_label" />' + option.label + '</span>';
                html += '	</div>';
            }
        }

        //custom options
        html += '	<div  class="picker_option alert_option" onclick="createTaskAlert(\'' + taskId + '\', 47, labels.custom)" >';
        html += '		<span class="picker_option_label" />' + labels.custom + '</span>';
        html += '	</div>';

        flyoutEl.innerHTML = html;
        flyoutEl.style.display = 'block';

        //set up clickaway event
        var dismissUpdateAlertTypeOptions = function(event){hideUpdateAlertTypeOptions(event, alertId);};
        pushWindowClickEvent(dismissUpdateAlertTypeOptions);
    },0);
};

function hideUpdateAlertTypeOptions(event, alertId)
{
	var doc = document;
	var pickerToggle = doc.getElementById('task_update_alert_type_options_toggle_' + alertId);
	var editorEl = doc.getElementById('update_alert_type_options_flyout_' + alertId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle)
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
		}	
	}
};

function updateTaskAlertType(alertId, taskType, typeLabel)
{
	if(taskType == -1)
	{
		deleteTaskAlert(alertId)
		return;
	}
	
	if(taskType == 0)
		taskType = 1; //special case
	else
		taskType = 60 * parseInt(taskType);	
		
	var ajaxRequest = getAjaxRequest(); 
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
					var doc = document;
					doc.getElementById('task_update_alert_type_options_toggle_' + alertId).innerHTML = typeLabel;
                    taskListRowsClass(alertId);
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }
                        else
                            displayGlobalErrorMessage(labels.error_from_server + ': ' + response.error);
                    }
                    else
                        displayGlobalErrorMessage(labels.failed_to_add_alert);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

 	var params = "method=updateTaskNotification&notificationid=" + alertId + '&triggeroffset=' + taskType;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function displayUpdateAlertSoundOptions(alertId)
{
    setTimeout(function(){
        var doc = document;
        var flyoutEl = doc.getElementById('update_alert_sound_options_flyout_' + alertId);
        var html = '';

        var alertSoundOptions = [
            {label: alertStrings.none, value: 'none'},
            {label: alertStrings.bells, value: 'bells'},
            {label: alertStrings.data, value: 'data'},
            {label: alertStrings.flute, value: 'flute'},
            {label: alertStrings.morse, value: 'morse'}
        ];

        for (var i = 0; i < alertSoundOptions.length; i++)
        {
            var option = alertSoundOptions[i];

            html += '	<div  class="picker_option alert_sound_option" onclick="updateTaskAlertSound(\'' + alertId + '\', \'' + option.value + '\', \'' + option.label + '\' )" >';
            html += '		<span class="picker_option_label" />' + option.label + '</span>';
            html += '	</div>';
        }

        flyoutEl.innerHTML = html;
        flyoutEl.style.display = 'block';

        //set up clickaway event
        var dismissUpdateAlertSoundOptions = function(event){hideUpdateAlertSoundOptions(event, alertId);};
        pushWindowClickEvent(dismissUpdateAlertSoundOptions);
    },0);
};

function hideUpdateAlertSoundOptions(event, alertId)
{
	var doc = document;
	var pickerToggle = doc.getElementById('task_alert_sound_options_toggle_' + alertId);
	var editorEl = doc.getElementById('update_alert_sound_options_flyout_' + alertId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle)
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
		}	
	}
};

function updateTaskAlertSound(alertId,  soundValue, soundLabel)
{
	var ajaxRequest = getAjaxRequest(); 
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
					var doc = document;
					doc.getElementById('task_alert_sound_options_toggle_' + alertId).innerHTML = soundLabel;
			    }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }
                        else
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                    else
                        displayGlobalErrorMessage(labels.failed_to_add_alert);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

 	var params = "method=updateTaskNotification&notificationid=" + alertId + '&sound_name=' + soundValue;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function showAlertPicker(event, taskId)
{
    if(event)
    	stopEventPropogation(event);
	var doc = document;

	var taskEl = doc.getElementById(taskId);
	var alertPicker = doc.getElementById('alert_picker_' + taskId);
	var alertPickerBackground = doc.getElementById('alert_picker_background_' +  taskId);
	var alertPickerHTML = '';
	var alertPickerWidth = '';
	var soundPickerDisplay = 'display:none;';
	var datePickerDisplay = 'display:none;';
	var addAlertBtnDisplay = 'display:none;';
	//var currentAlertsDisplay = 'display:none;';
	var alertCount = parseInt(taskEl.getAttribute('alertcount'), 10);

	//set up hidden elements
	alertPickerHTML += '	<input id="new_alert_offset_' + taskId + '" type="hidden" value="" />';
	alertPickerHTML += '	<input id="new_alert_sound_' + taskId + '" type="hidden" value="" />';

	//set up existing alerts
	alertPickerHTML += ' <span id="current_alerts_wrapper_' + taskId + '" onclick="stopEventPropogation(event)">';// style="' + currentAlertsDisplay + '">';
	alertPickerHTML += ' 	<label class="no_hover bold">' + alertStrings.currentAlerts +':</label>';
	alertPickerHTML += '	<span id="task_alerts_wrapper_' + taskId + '" class="task_alerts_wrapper">';

	if (alertCount > 0)// || alertCount != 'NaN')
	{
		alertPickerHTML += getCurrentTaskAlertsHTML(taskId);
	}
	else
		alertPickerHTML += '<label class="no_hover indent" >' + alertStrings.none + '</label>';

	alertPickerHTML += ' 	</span>';
	alertPickerHTML += ' </span>';

	//set up picker body
						//offset picker
	alertPickerHTML += ' 	<label class="no_hover bold" onclick="stopEventPropogation(event)">' + alertStrings.alertDelivery +':</label >';
	alertPickerHTML += '	<div class="select_wrapper alert_delivery_select" onclick="stopEventPropogation(event)">';
	alertPickerHTML += '	    <select id="alert_delivery_select_' + taskId + '" onchange="alertDeliverySelected(event, \'' + taskId + '\', this.value)">';
	alertPickerHTML += '	    	<option value="none">' + alertStrings.none + '</option>';
	alertPickerHTML += '	    	<option value="0">' + alertStrings.zeroMinutesBefore + '</option>';
	alertPickerHTML += '	    	<option value="5">' + alertStrings.fiveMinutesBefore + '</option>';
	alertPickerHTML += '	    	<option value="15">' + alertStrings.fifteenMinutesBefore + '</option>';
	alertPickerHTML += '	    	<option value="30">' + alertStrings.thirtyMinutesBefore + '</option>';
	alertPickerHTML += '	    	<option value="60">' + alertStrings.oneHourBefore + '</option>';
	alertPickerHTML += '	    	<option value="120">' + alertStrings.twoHoursBefore + '</option>';
	alertPickerHTML += '	    	<option value="1440">' + alertStrings.oneDayBefore + '</option>';
	alertPickerHTML += '	    	<option value="2880">' + alertStrings.twoDaysBefore + '</option>';
	alertPickerHTML += '	    	<option value="other">' + alertStrings.other + '</option>';
	alertPickerHTML += '	    </select>';
	alertPickerHTML += '	</div>';

						//sound picker
	//alertPickerHTML += ' <span class="label" id="alert_sound_picker_wrapper_' + taskId + '" style="' + soundPickerDisplay + '" onclick="stopEventPropogation(event)">';
	//alertPickerHTML += ' 	<span class="label">' + alertStrings.alertSound +':</span>';
	//alertPickerHTML += '			<div class="select_wrapper">';
	alertPickerHTML += '	<div id="alert_sound_picker_wrapper_' + taskId + '" style="' + soundPickerDisplay + '" class="select_wrapper alert_sound_picker_select">';
	alertPickerHTML += ' 	    <select id="alert_sound_select_' + taskId + '">';
	alertPickerHTML += '	    	<option value="none">' + alertStrings.none + '</option>';
	alertPickerHTML += '	    	<option value="bells">' + alertStrings.bells + '</option>';
	alertPickerHTML += '	    	<option value="data">' + alertStrings.data + '</option>';
	alertPickerHTML += '	    	<option value="flute">' + alertStrings.flute + '</option>';
	alertPickerHTML += '	    	<option value="morse">' + alertStrings.morse + '</option>';
	alertPickerHTML += '	    </select>';
	alertPickerHTML += '	</div>';
	//alertPickerHTML += '	</span>';
	
	alertPickerHTML += ' 	<div id="alert_date_picker_wrapper_' + taskId + '" style="' + datePickerDisplay + '" onclick="stopEventPropogation(event)">';
	alertPickerHTML += ' 		<div id="alert_date_picker_' + taskId + '" class="alert_date_picker">';
	alertPickerHTML += ' 		</div>';
	alertPickerHTML += ' 		<div id="alert_time_picker_wrapper_' + taskId + '" class="alert_time_picker_wrapper">';
	alertPickerHTML += ' 			<span class="alert_time_picker_label">Time</span>';	
	alertPickerHTML += ' 			<span id="alert_time_picker_' + taskId + '"  class="alert_time_picker"></span>';
	alertPickerHTML += ' 		</div>';
	alertPickerHTML += ' 	</div>';

	

	alertPickerHTML += ' <div id="add_alert_btn_' + taskId + '" style="' + addAlertBtnDisplay + '" onclick="stopEventPropogation(event)">';
	alertPickerHTML += '	<div class="breath-8"></div>';
	alertPickerHTML += '	<div class="button" onclick="addTaskAlert(\'' + taskId + '\')">' + alertStrings.scheduleAlert + '</div>';
	alertPickerHTML += '	<div class="breath-4"></div>';
	alertPickerHTML += ' </div>';

	alertPicker.innerHTML = alertPickerHTML;

	alertPicker.style.display = "block";
	alertPickerBackground.style.height = "100%";
	alertPickerBackground.style.width = "100%";
	scrollUpViewport(event);
};

function submitNewOffsetTaskAlert(taskId, soundName, triggerOffset)
{
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                	if (useNewTaskEditor)
                	{
	                	loadTaskAlertsInEditor(taskId);
                	}
                	else
                	{
						resetNewAlertControl(taskId);
						loadTaskAlertsInEditor(taskId);
					}
					
					var doc = document;
					var taskEl = doc.getElementById(taskId);
					var alertCount = parseInt(taskEl.getAttribute('alertcount'), 10);
					
					taskEl.setAttribute('alertcount', (alertCount + 1));
					doc.getElementById('alert_icon_' + taskId).style.display = 'inline-block';
                    taskListRowsClass(taskId);
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }
                        else
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                    else
                        displayGlobalErrorMessage(labels.failed_to_add_alert);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

 	var params = '';

	//if (alertType == "other")
	//	params = "method=addTaskNotification&taskid=" + taskId + "&sound_name=" + soundName + "&triggerdate=" + triggerDate;
	//else
		params = "method=addTaskNotification&taskid=" + taskId + "&sound_name=" + soundName + "&triggeroffset=" + triggerOffset;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function findOffset(baseDate, offsetDate)
{
	var unixOffset =  baseDate- offsetDate;
	var offset = '';

	if (unixOffset == 1)
		offset = '' + 0 + ' ' + alertStrings.minutes;
	else if (unixOffset <= 1800) //30 mins
		offset = '' + unixOffset/60 + ' ' + alertStrings.minutes;
	else if (unixOffset <= 7200) //2 hours
		offset = '' + unixOffset/3600 + ' ' + alertStrings.hours;
	else
		offset = '' + unixOffset/86400 + ' ' + alertStrings.days;

	return offset;
};


function deleteTaskAlert(alertId)
{
	var doc = document;
	//var taskEl = doc.getElementById(taskId);
	//submit alert to server
	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                	var doc = document;
                	var alertEl = doc.getElementById('task_alert_in_editor_' + alertId);
                    var taskId = alertEl.getAttribute('taskid');
                    var taskEl = doc.getElementById(taskId);
	                    
                	if (useNewTaskEditor)
                		loadTaskAlertsInEditor(taskId);
                	else
                	{
	                    var newAlertCount = parseInt(taskEl.getAttribute('alertcount'), 10) - 1;
		                
		                taskEl.setAttribute('alertcount', newAlertCount);
		                if (newAlertCount == 0)
		                	doc.getElementById('alert_icon_' + taskId).setAttribute('style', '');    
	                    
	                    alertEl.parentNode.removeChild(alertEl);
                    }	
                    loadNextNotificationForCurrentUser();
                    taskListRowsClass(taskId);
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }
                        else
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                    else
                        displayGlobalErrorMessage(labels.failed_to_remove_alert + ' ' + response.error);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

 	var params = "method=deleteTaskNotification&notificationid=" + alertId;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);

};


function displayTaskAlertDueDatePicker(alertId, selectedUnixDate)
{
    setTimeout(function(){
        var doc = document;
        var datepickerWrapper = doc.getElementById('due_date_editor_' + alertId);

        //displayClickToDismissOverlay(hideTaskDueDatePicker, taskId);
        datepickerWrapper.style.display = 'block';
        buildDatepickerUI('datepicker_' + alertId, selectedUnixDate, true);

        //set up clickaway event
        var dismissAlertDatePicker = function(event){hideTaskAlertDueDatePicker(event, alertId);};
        pushWindowClickEvent(dismissAlertDatePicker);
    },0);
};

function hideTaskAlertDueDatePicker(event, alertId)
{
	var doc = document;
	var pickerToggle = doc.getElementById('task_alert_trigger_date_label_' + alertId);
	var editorEl = doc.getElementById('due_date_editor_' + alertId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', 'display:none');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle)
		{
			editorEl.setAttribute('style', 'display:none');
			popWindowClickEvent();
			updateTaskAlertDueDate(alertId);
		}	
	}

};

function updateTaskAlertDueDate(alertId)
{
	var doc = document;
	var unixDate = datepicker.unix;
	
	var alertEl = doc.getElementById('task_alert_in_editor_' + alertId);
	var triggerOffset = parseInt(alertEl.getAttribute('triggerdate'), 10);
	
	var origUnixDate = new Date(triggerOffset * 1000);
	var	hours = origUnixDate.getHours();
	var	minutes = origUnixDate.getMinutes();
	
	var newUnixDate = new Date(unixDate * 1000);
	
	newUnixDate.setHours(hours);
	newUnixDate.setMinutes(minutes);
	newUnixDate.setSeconds(0);

	unixDate = newUnixDate.getTime()/1000;
    //if Date is not set
    if (unixDate < 60 * 60 * 24) {
        updateTaskAlertType(alertId, -1, labels.none);
        return false;
    }
	var ajaxRequest = getAjaxRequest(); 
	if (!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
        if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if (response.success == false)
                {
                    if(response.error == "authentication")
                    {
                        history.go(0);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_update_the_due_date+ ': ' + ajaxRequest.responseText);
                        return;
                    }
                }
                else
                {	
                	var doc = document;
                	
                	doc.getElementById('task_alert_trigger_date_label_' + alertId).innerHTML = displayHumanReadableDate(unixDate, false, true, true);
                	doc.getElementById('task_alert_in_editor_' + alertId).setAttribute('triggerdate', unixDate);
                	doc.getElementById('task_alert_trigger_date_label_' + alertId).setAttribute('onclick', 'displayTaskAlertDueDatePicker(\'' + alertId + '\' , \'' + unixDate + '\')');
                    loadNextNotificationForCurrentUser();
                    taskListRowsClass(alertId);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
	}
	
	
		
	var params = "method=updateTaskNotification&notificationid=" + alertId + "&triggerdate=" + unixDate;;

	ajaxRequest.open("POST", ".", true);

	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};
function displayAlertDueTimeEditor(alertId)
{
    setTimeout(function(){
        var doc = document;
        var timePicker = doc.getElementById('task_due_time_editor_' + alertId);
        var alertEl = doc.getElementById('task_alert_in_editor_' + alertId);
        var dueDate = alertEl.getAttribute('triggerdate');
        var pickerHTML = buildTimePickerHtml(alertId, dueDate, true);

        doc.getElementById('due_time_editor_flyout_'  + alertId).style.display = 'block';

        timePicker.innerHTML = pickerHTML;

        //set up clickaway event
        var dismissAlertDueTimeEditor = function(event){hideAlertTaskDueTimeEditor(event, alertId);};
        pushWindowClickEvent(dismissAlertDueTimeEditor);
    },0);
};

function hideAlertTaskDueTimeEditor(event, alertId)
{
	var doc = document;
	var pickerToggle = doc.getElementById('task_alert_trigger_time_label_' + alertId);
	var editorEl = doc.getElementById('due_time_editor_flyout_' + alertId);
	
	if (event == null) //called only by updateTaskDueTime
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
		//checkTimePickerValues(taskId);
		//saveTaskDueTime(taskId);
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle && !isDescendant(editorEl, eventTarget))
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
			checkTimePickerValues(alertId);
			saveAlertTaskDueTime(alertId);
		}	
	}
};  
function saveAlertTaskDueTime(alertId)
{
	var doc = document;

	//get current date
	var alertEl = doc.getElementById('task_alert_in_editor_' + alertId);
	var originalDueDateUnix = alertEl.getAttribute('triggerdate');
	var sDate = new Date(parseInt(originalDueDateUnix, 10) * 1000);
	var hours = doc.getElementById('hour_picker_' + alertId).value;
	var mins = doc.getElementById('minute_picker_' + alertId).value;
	var meridian = doc.getElementById('meridian_picker_' + alertId).value;
	var newUnixDate = {};


	hours = parseInt(hours, 10);

	//massage hours
	if (hours == 12 && meridian == 'am')
	{
		hours = 0;
	}
	else if (meridian == 'pm' && hours != 12)
	{
		hours += 12;
	}
	
	//add hours and minutes to current time
	sDate.setHours(hours);
	sDate.setMinutes(mins);
	sDate.setSeconds(0);

	newUnixDate = sDate.getTime() / 1000;
    hideAlertTaskDueTimeEditor(null, alertId);
	//save new duedate
	updateAlertTaskDueTime(alertId, newUnixDate);
};

function updateAlertTaskDueTime(alertId, newUnixDate)
{
	var ajaxRequest = getAjaxRequest(); 
	if (!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
        if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if (response.success == false)
                {
                    if(response.error == "authentication")
                    {
                        history.go(0);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_update_the_due_date + ': ' + ajaxRequest.responseText);
                        return;
                    }
                }
                else
                {	
                	var doc = document;
                	
                	doc.getElementById('task_alert_trigger_time_label_' + alertId).innerHTML = displayHumanReadableTime(newUnixDate);
                	doc.getElementById('task_alert_in_editor_' + alertId).setAttribute('triggerdate', newUnixDate);
                    loadNextNotificationForCurrentUser();
                    taskListRowsClass(alertId);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
	}
	
	
		
	var params = "method=updateTaskNotification&notificationid=" + alertId + "&triggerdate=" + newUnixDate;;

	ajaxRequest.open("POST", ".", true);

	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};
//*******************
//  ! Tags
//*******************

var currentTagsForEditingTask = "";

function showTagsEditor(event, taskId)
{
	var doc = document;
	var targetEl = useNewTaskEditor ? doc.getElementById('task_editor_add_tag_' + taskId) : doc.getElementById('task_editor_tags_label_' + taskId);
	
	if(event.target != targetEl)
		return;
	
	var tagsEditor = doc.getElementById('tags_editor_' + taskId);

	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var tagsJSON = JSON.parse(ajaxRequest.responseText);

 				if (!tagsJSON.error)
 				{
                    var tagsEditorHTML = htmlForTagPicker(tagsJSON, taskId);
                    var tagsToggleEl = doc.getElementById('tags_toggle_' + taskId);

              
					tagsEditor.innerHTML = tagsEditorHTML;
					tagsEditor.style.display = "block";

                    currentTagsForEditingTask = tagStringForTagPicker(taskId, false);
                    
                    //doc.getElementById('tags_picker_text_field_' + taskId).focus();
                    //updateTagStringForTagPicker(null, taskId);
                    
                    //set up clickaway event
					var dismissTagsEditor = function(event){hideTagsEditor(event, taskId);};
					pushWindowClickEvent(dismissTagsEditor);
                    
                    document.getElementById('tags_picker_text_field_' + taskId).focus();
 				}
                else
                {
                    if(tagsJSON.error == "authentication")
                        history.go(0); //make the user log in again
                    else
                        displayGlobalErrorMessage(labels.unable_to_retrieve_tags_for_task + ': ' + ajaxRequest.responseText);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=getControlContent&type=tag&taskid=" + taskId;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	

	ajaxRequest.send(params);

};

function hideTagsEditor(event, taskId)
{
	var doc = document;
	var pickerToggle = doc.getElementById('task_editor_tags_label_' + taskId);
	var editorEl = doc.getElementById('tags_editor_' + taskId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
		updateTagsForTask(taskId);
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle && !isDescendant(editorEl, eventTarget))
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
			updateTagsForTask(taskId);
		}	
	}
};

function htmlForTagPicker(tagsJSON, pickerId) //The pickerId is the task id or 'multi_edit' if this is the multi-edit tag picker
{

    var html = '';
    //tagsEditorHTML += '<label class="no_hover" id="current_tags_string_' + pickerId + '"></label>';
    html += '<div id="tag_options_' + pickerId + '">';
    for (var i = 0; i < tagsJSON.length; i++)
    {
    	var tag = tagsJSON[i];
    	
    	var selectedClass = '';
    	var isChecked = (tag.selected == true);
    	
    	if(isChecked == true)
    	 	selectedClass = 'checked="true"'; 
        
        var tagName = tag.name;
        var tagId = tag.tagid;

        html += ' <div>';
        html += '	<label for="tag_option_' + pickerId + '_' + tagId + '">';
    	html += '		<input type="checkbox" name="tags_picker_checkboxes_' + pickerId + '" id="tag_option_' + pickerId + '_' + tagId + '" onclick="' + onclick + '" onchange="updateTagStringForTagPicker(event, \'' + pickerId + '\')" value="' + tagName + '" ' + selectedClass + '/>';
	    html += ' 		<span > ' + tagName + '</span>';
        html += '	</label>';
        html += ' </div>';
    }

    html += '</div>';
    html += '<div class="breath-10"></div>';
    html += '<input type="text" id="tags_picker_text_field_' + pickerId + '" placeholder="'+labels.new_tag_d+'" onkeydown="shouldAddTag(event, this, addTagToTagPicker,\'' + pickerId + '\')">';
    
    return html;
}

function htmlForTagOption(pickerId, optionId, tagName, ischecked)
{
    var selectedClass = '';
    if(ischecked == true)
        selectedClass = 'checked="true"';

    var onclick = "";
    if(pickerId == 'multi_edit')
    {
        onclick = "enableMultiEditAddTagsButton()"
    }

    var html  = '<label for="tag_option_' + pickerId + '_' + optionId + '">';
    	html += '<input type="checkbox" name="tags_picker_checkboxes_' + pickerId + '" id="tag_option_' + pickerId + '_' + optionId + '" onclick="' + onclick + '" onchange="updateTagStringForTagPicker(event, \'' + pickerId + '\')" value="' + tagName + '" ' + selectedClass + '/>';
        html += ' ' + tagName;
        html += '</label>';
        
    return html;
}

function tagStringForTagPicker(pickerId, addSpace)
{
    var options = document.getElementsByName('tags_picker_checkboxes_' + pickerId);
    var tagString = "";

    for(var i = 0; i < options.length; i++)
    {
        var option = options[i];
        if(option.checked == true)
        {
            if(tagString.length > 0)
            {
                tagString += ",";
                if(addSpace)
                    tagString += " ";
            }

            tagString += option.value;
        }
    }
    return tagString;
}

function updateTagStringForTagPicker(event, pickerId)
{
	if (event)
		stopEventPropogation(event);
	
	var doc = document;
	var taskEl = doc.getElementById(pickerId);
    var tagsCount = parseInt(taskEl.getAttribute('tagcount'), 10);
    var tagString = tagStringForTagPicker(pickerId, true);
    var taskTagsEl = document.getElementById('task_tags_' + pickerId);
    var taskEditorTagsEl = doc.getElementById('task_editor_tags_label_' + pickerId);
    
    var tags = tagString.split(', ');
    
    if(tagString.length == 0)
    {
        if(useNewTaskEditor)
            doc.getElementById('task_editor_tags_wrap_' + pickerId).innerHTML = '';
        else
       		taskEditorTagsEl.innerHTML = labels.none;
        
        taskTagsEl.innerHTML =  '';
        taskTagsEl.setAttribute('style', '');
        taskEl.setAttribute('tagcount', 0);
        taskEl.setAttribute('tags', '');
    }
    else
    {
	    taskTagsEl.innerHTML =  tagString;
	    taskTagsEl.style.display = 'inline-block';
	    taskEl.setAttribute('tagcount', tags.length);
	    taskEl.setAttribute('tags', tagString);
	    
	  	if(useNewTaskEditor)
            doc.getElementById('task_editor_tags_wrap_' + pickerId).innerHTML = getTaskEditorTagsHtml(pickerId);
        else
            taskEditorTagsEl.innerHTML = tagString;
	}

    shouldDisplaySecondRow(pickerId);
    taskListRowsClass(pickerId);
}

function shouldAddTag(event, submitTextarea, jsFunction, pickerId)
{
	var currentKey = event.keyCode;
    if(currentKey == "13")
	{
        event.preventDefault();

        if(jsFunction != null)
        {
            jsFunction(pickerId);
        }
        else
            displayGlobalErrorMessage(labels.no_submit_method_was_assigned_to_this_tag_editor);

	}
};

function addTagToTagPicker(pickerId)
{
    var doc = document;

    var newTagName = doc.getElementById('tags_picker_text_field_' + pickerId).value;
    newTagName = newTagName.trim();
    if(newTagName.length == 0)
        return;

    var guid = uuid();

    var newElement = doc.createElement("div");
    newElement.innerHTML = htmlForTagOption(pickerId, guid, newTagName, true);

    var parent = doc.getElementById('tag_options_' + pickerId);
    var tagOptions = doc.getElementsByName('tags_picker_checkboxes_' + pickerId);

    var didAddNewItem = false;
    for(var i = 0; i < tagOptions.length; i++)
    {
        var tagOption = tagOptions[i];

        if(typeof(tagOption.value) == 'undefined' ||  tagOption.value.length == 0 || newTagName < tagOption.value)
        {
            parent.insertBefore(newElement, tagOption.parentNode.parentNode);
            didAddNewItem = true;
            break;
        }

        if(tagOption.value == newTagName)
        {
            tagOption.checked = true;
            didAddNewItem = true;
            break;
        }
    }
    if(!didAddNewItem)
    {
        parent.appendChild(newElement);
    }
    doc.getElementById('tags_picker_text_field_' + pickerId).value = "";
    updateTagStringForTagPicker(null, pickerId);
};

function removeAllTagsForTask(taskId)
{
	var doc = document;
    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

    tagName = tagName.trim();

    if(tagName.length == 0)
        return;

	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                    //Add the tag name into the tag button
                    var newTagString = tagName;
	                var taskEl = doc.getElementById(taskId);
	                var currentTagsString = taskEl.getAttribute('tags');
	                var currentTagsCount = parseInt(taskEl.getAttribute('tagcount'), 10);	
	                var newTagsCount = currentTagsCount + 1;
	                var taskTagsEl = doc.getElementById('task_tags_' + taskId)
	                newTagString = currentTagsString.length > 0 ? currentTagsString + ',' + tagName : tagName;
	                
	                taskEl.setAttribute('tagcount', newTagsCount);
	                taskEl.setAttribute('tags', newTagString);
	                
	                taskTagsEl.innerHTML = newTagString;
	                
	                if (newTagsCount == 1)
	                {
		                taskTagsEl.style.display = 'inline-block';
		                
	                }
	                
                    //updateTagCountForTask(taskId, newTagString);

                    //loadTagsControl();
                }
                else
                {
                    if(response.success == false && response.error=="authentication")
                    {
                        //make the user log in again
                        history.go(0);
                        return;
                    }
                    if(response.success == false && response.error == "duplicateName")
                    {
                        return;
                    }
                    displayGlobalErrorMessage(labels.failed_to_add_tag);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=addTag&tagName=" + encodeURIComponent(tagName) + "&taskid=" + taskId;
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);	
};

function addTagToTask(taskId,tagName, viaDragNDrop)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var currentTagsString = taskEl.getAttribute('tags');
	var currentTagsArray = currentTagsString.split(', ');
	
	if (currentTagsArray.indexOf(tagName) > -1)
		return;
		
	var newTagString = '';
	
	if (tagName.length > 0)
	{
		newTagString = currentTagsString.length > 0 ? currentTagsString + ', ' + tagName : tagName;
	}
	else
	{
		newTagString = '';
	}
	                
    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

    tagName = tagName.trim();

	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                    //Add the tag name into the tag button
                    
	                var currentTagsCount = parseInt(taskEl.getAttribute('tagcount'), 10);	
	                var newTagsCount = newTagString.length > 0  ? currentTagsCount + 1 : 0;
	                var taskTagsEl = doc.getElementById('task_tags_' + taskId)
	                
	                
	                taskEl.setAttribute('tagcount', newTagsCount);
	                taskEl.setAttribute('tags', newTagString);
	                
	                taskTagsEl.innerHTML = newTagString;
	                
	                if (newTagsCount > 0)
	                {
		                taskTagsEl.style.display = 'inline-block';
		                
	                }
	                else
	                {
		                taskTagsEl.setAttribute('style', '');
	                }
	                
                    loadTagsControl();
                    
                    shouldDisplaySecondRow(taskId);
                }
                else
                {
                    if(response.success == false && response.error=="authentication")
                    {
                        //make the user log in again
                        history.go(0);
                        return;
                    }
                    if(response.success == false && response.error == "duplicateName")
                    {
                        return;
                    }
                    displayGlobalErrorMessage(labels.failed_to_add_tag);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=updateTagsForTask&taskid=" + taskId;
	
    if(newTagString.length > 0)
        params += "&tags=" + encodeURIComponent(newTagString);

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function updateTagsForTask(taskId)
{
    var tagString = tagStringForTagPicker(taskId, false);

    if(currentTagsForEditingTask == tagString)
    {
        return;
    }

    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                	var doc = document;
                	var taskEl = doc.getElementById(taskId);

                    //updateTagCountForTask(taskId, tagString);

                    loadTagsControl();
                    shouldDisplaySecondRow(taskId);
                }
                else
                {
                    if(response.success == false && response.error=="authentication")
                    {
                        //make the user log in again
                        history.go(0);
                        return;
                    }
                    displayGlobalErrorMessage(labels.failed_to_add_tag);
                }
                taskListRowsClass(taskId);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	var params = "method=updateTagsForTask&taskid=" + taskId;
    if(tagString.length > 0)
        params += "&tags=" + encodeURIComponent(tagString);

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function updateTagsForTaskViaNewTaskEditor(taskId, selectedTag)
{
	//determine new tags
	var taskEl = document.getElementById(taskId);
	var tagsArray = taskEl.getAttribute('tags').split(', ');
	var newTagsArray = [];
	var newTags = '';
	var newTagsCount = 0;
	
	if (tagsArray.length != 0)
	{
		for (var i = 0; i < tagsArray.length; i++)
		{
			if (selectedTag != tagsArray[i])
			{
				newTagsArray.push(tagsArray[i]);
			}
		}
		
		if (newTagsArray.length > 0)
		{
			for (var i = 0; i < newTagsArray.length; i++)
			{
				newTags += newTagsArray[i];
				
				if (i != newTagsArray.length - 1)
					newTags += ', ';		
			}
		}
	}	
	
		
	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
					//update tagscount in taskEl
					taskEl.setAttribute('tagcount', newTagsCount);
					//update tagString in taskEl
					taskEl.setAttribute('tags', newTags);
					
					//update tags in editor picker
					loadTaskEditorProperties(taskId, 2);
                }
                else
                {
                    if(response.success == false && response.error=="authentication")
                    {
                        //make the user log in again
                        history.go(0);
                        return;
                    }
                    displayGlobalErrorMessage(labels.failed_to_add_tag);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	var params = "method=updateTagsForTask&taskid=" + taskId;
    if(newTags.length > 0)
        params += "&tags=" + encodeURIComponent(newTags);

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

/*
function updateTagCountForTask(taskId, tagString)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	//var tagsButtonName = '';
    tagString  = tagString || '';

    if(tagString.length > 0)
    {
        //tagsButtonName = tagString;

        //tagsClass = 'task_attribute_has_value';
        //doc.getElementById('task_tag_icon_' + taskId).setAttribute('src', 'https://s3.amazonaws.com/static.plunkboard.com/images/task/tag_on_icon.png');

    }
    else
    {
        //tagsButtonName = taskStrings.tag;
       // tagsButtonName = tagString;
       // tagsClass = '';

        //doc.getElementById('task_tag_icon_' + taskId).setAttribute('src', 'https://s3.amazonaws.com/static.plunkboard.com/images/task/tag_off_icon.png');
    }

    //var toggleButton = doc.getElementById('tags_toggle_' + taskId);
    //toggleButton.innerHTML = tagsButtonName.replace(',', ' ,');
    //toggleButton.className = tagsClass;
};
*/


//********************
// ! Task Type
//********************

function showTaskActionOptions(taskId)
{
    setTimeout(function(){
        var doc = document;
        var flyoutEl = doc.getElementById('task_actions_flyout_' + taskId);
        if (flyoutEl.innerText.length) {
            flyoutEl.style.display = 'block';

            //set up clickaway event
            var dismissTaskActionOptions = function (event) {
                hideTaskActionOptions(event, taskId);
            };
            pushWindowClickEvent(dismissTaskActionOptions);
        }
    },0);
};


function hideTaskActionOptions(event, taskId)
{
	var doc = document;
	var editorEl = doc.getElementById('task_actions_flyout_' + taskId);
	var pickerToggle = doc.getElementById('task_type_details_' + taskId);
	var pickerTextToggle = doc.getElementById('task_type_data_toggle_text_' + taskId);
	var pickerDataToggle = doc.getElementById('task_type_data_toggle_' + taskId);
	var pickerIconToggle = doc.getElementById('task_type_toggle_icon_' + taskId)
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle && eventTarget != pickerTextToggle && eventTarget != pickerDataToggle && eventTarget != pickerIconToggle)
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
		}	
	}
};

function displayTaskTypeDataInput(event, taskId)
{
	var doc = document;
	
	doc.getElementById('task_type_data_toggle_text_' + taskId).innerHTML = '';
	
	var inputEl = doc.getElementById('task_type_data_' + taskId);
		inputEl.style.display = 'inline-block';
		inputEl.focus();
		
	hideTaskActionOptions(event, taskId);
};

function titleForTaskType(type)
{
    switch(parseInt(type))
    {
        case 0:
            return taskStrings.normalType;
        case 1:
            return taskStrings.projectType;
        case 2:
            return taskStrings.callType;
        case 3:
            return taskStrings.SMSType;
        case 4:
            return taskStrings.emailType;
        case 5:
            return taskStrings.locationType;
        case 6:
            return taskStrings.websiteType;
        case 7:
            return taskStrings.checklistType;
        default:
            return taskStrings.unknownType;
    }
};

function selectCurrentTaskTypeForTaskId(taskId)
{
	var doc = document;

	var taskEl = doc.getElementById(taskId);

    var currentTaskType = taskEl.getAttribute('tasktype');
    var currentTaskData = taskEl.getAttribute('tasktypedata');

    selectTaskType(taskId, currentTaskType);

    if(currentTaskType >= 2 && currentTaskType != 7)
    {
        if(currentTaskType == 6)
        {
	       	if(currentTaskData.search(/http\:\/\/|https\:\/\//i) != 0)
	        	currentTaskData = 'http://' + currentTaskData;

	        var linkHTML = '';

	        linkHTML += '<br/>';
	        linkHTML += '<label class="no_hover bold">Link:</label>';
	        linkHTML += '<p><a target="_blank" href="' + currentTaskData + '">'+ currentTaskData + '</a></p>';

	        doc.getElementById('type_picker_details_' + taskId).innerHTML += linkHTML;
        }

        doc.getElementById('type_picker_data_' + taskId + '_' + currentTaskType).value = currentTaskData;
    }

    //select radio button
    doc.getElementById('type_radio_' + taskId + '_' + currentTaskType).checked = true;
    selectedTaskType = currentTaskType;
};

var selectedTaskType = null;

function selectTaskType(taskId, type)
{
	if(type == selectedTaskType)
		return;

	if(selectedTaskType == null)
		selectedTaskType = 0;

	var doc = document;

	type = parseInt(type, 10);

	var options = doc.getElementsByName('task_type_options_' + taskId);
	var detailsContainer = doc.getElementById('type_picker_details_' + taskId);

	var detailsHTML = '';
	var detailsWidth = 170;

	switch (type)
	{
		case 0: //normal
			detailsHTML += pickerStrings.normalTaskDescription;
			break;
		case 1: //project
			detailsHTML += pickerStrings.projectDescription;
			break;
		case 7: //checklist
			detailsHTML += pickerStrings.checklistDescription;
			break;
		default:
			var isValid = true;
			var detailsTitle = '';
			switch (type)
			{
				case 2: //call
					detailsTitle = pickerStrings.phoneNumber;
					break;
				case 3: //sms
					detailsTitle = pickerStrings.phoneNumber;
					break;
				case 4: //email
					detailsTitle = pickerStrings.emailAddress;
					break;
				case 5: //location
					detailsTitle = pickerStrings.address;
					break;
				case 6: //url
					detailsTitle = pickerStrings.url;
					break;
				default:
					isValid = false;
					break;
			}

			if (isValid)
			{
				detailsHTML += '<label class="details_label">' + detailsTitle + ':</label>';
				detailsHTML += '<div id="type_picker_section_' + taskId + '_' + type + '" >';
	            detailsHTML += '	<input type="text" onkeydown="if (event.keyCode == 13) hideTypePicker(event,\'' + taskId + '\', \'' + taskId + '\');" placeholder="" id="type_picker_data_' + taskId + '_' + type + '" onclick="stopEventPropogation(event)" />';
	            detailsHTML += '</div>';
	        }
	        else
	        	detailsHTML += labels.unknown_task_type_selected;
			break;
	}

	detailsContainer.innerHTML = detailsHTML;

	//set up picker details UI
	detailsContainer.style.width = detailsWidth + 'px';
	detailsContainer.style.height = doc.getElementById('type_picker_options_' + taskId).clientHeight + 'px';

	//update previously selected option UI
	doc.getElementById('type_option_' + taskId + '_' + selectedTaskType).removeAttribute('class');

	//update selected option UI
	doc.getElementById('type_option_' + taskId + '_' + type).setAttribute('class', 'selected');


	//prep values for next selection
	selectedTaskType = type;
};

function hideTaskTypeEditor(event,taskId)
{
	var doc = document;
	var pickerToggle = useNewTaskEditor ? doc.getElementById('task_editor_task_type_' + taskId) : doc.getElementById('task_editor_task_type_label_' + taskId);
	var editorEl = doc.getElementById('task_type_editor_' + taskId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
		
		if (eventTarget != pickerToggle)
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
		}	
	}
	
	//updateTypeValuesForTask(taskId, taskId);
    selectedTaskType = null;
};

function showTaskTypeEditor(taskId)
{
    setTimeout(function(){
        var doc = document;
        var taskEl = doc.getElementById(taskId);
        var taskType = parseInt(taskEl.getAttribute('tasktype'), 10);
        var typePicker = doc.getElementById('task_type_editor_' + taskId);


        var typePickerBackground = doc.getElementById('type_picker_background_' +  taskId);


        var pickerHTML = '';
        var isSubtask = isTaskSubtask(taskId);
        var options = [
                        {value:0, style:' normal', title:taskStrings.normalType},
                        {value:1, style:' project', title:taskStrings.projectType},
                        {value:7, style:' checklist', title:taskStrings.checklistType},
                        {value:2, style:' call', title:taskStrings.callType},
                        {value:4, style:' email', title:taskStrings.emailType},
                        {value:3, style:' sms', title:taskStrings.SMSType} ,
                        {value:5, style:' location', title:taskStrings.locationType},
                        {value:6, style:' website', title:taskStrings.websiteType}];
        var optionsHTML = '';
        var detailsHTML = '';

        for (var i=0; i < options.length; i++)
        {
            if(isSubtask && i == 1)
                continue;

            option = options[i];
            var selectedClass = option.value == taskType ? 'selected': '';


            optionsHTML += '	<div onclick="updateTaskType(event, \'' + taskId + '\',\'' + option.value + '\' )" class="picker_option task_type_option ' + selectedClass + '">';
            optionsHTML += '		<div class="task_editor_icon task_type ' + option.style + '"></div>';
            optionsHTML += '		<span class="picker_option_label">' + option.title + '</span>';
            optionsHTML += '	</div>';
        }


        pickerHTML += '	<div id="type_picker_options_' + taskId + '" class="type_picker_options">';
        pickerHTML += 	    optionsHTML;
        pickerHTML += '	</div>';

        typePicker.innerHTML = pickerHTML;

        typePicker.style.display = "block";

        //selectCurrentTaskTypeForTaskId(taskId);

        //set up clickaway event
        var dismissTaskTypeEditor = function(event){hideTaskTypeEditor(event, taskId);};
        pushWindowClickEvent(dismissTaskTypeEditor);
    },0);

};

function updateTaskType(event, taskId, newTaskType)
{
	if (event)
		stopEventPropogation(event);
		
	var doc = document;

	var taskEl = doc.getElementById(taskId);

    var oldTaskType = taskEl.getAttribute('tasktype');
    var oldTypeData = taskEl.getAttribute('tasktypedata');
    var set_default_type_data = true;
    newTaskType = parseInt(newTaskType , 10);
    var newTypeData = '';
    
    //if(newTaskType >= 2 && newTaskType != 7)
    //    newTypeData = doc.getElementById('type_picker_data_' + taskId + '_' + newTaskType).value.trim();


    if(oldTaskType == newTaskType && oldTypeData == newTypeData)
        return;

    var subtasksBody = doc.getElementById("subtasks_" + taskId);

	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

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
                    if(newTaskType == 1) //project
                    {
                        var subtasksHTML = '';
                        var subtasksJSON = response.subtasks;
                        for(var i = 0; i < subtasksJSON.length; i++)
                            subtasksHTML += getTaskHTML(subtasksJSON[i], true);

                        subtasksBody.innerHTML = subtasksHTML;

                        doc.getElementById('task_subtasks_toggle_' + taskId).style.display = 'inline-block';
                        //turnOnSubtasks(taskId);

                    }
                    else if(newTaskType == 7) //checklist
                    {
                        var subtasksHTML = '';
                        var taskitosJSON = response.taskitos;
                        for(var i = 0; i < taskitosJSON.length; i++)
                            subtasksHTML += getTaskitoHTML(taskitosJSON[i]);

                        subtasksBody.innerHTML = subtasksHTML;

                        doc.getElementById('task_subtasks_toggle_' + taskId).style.display = 'inline-block';
                        //turnOnSubtasks(taskId)
                    }
                    else
                    {
                    	//display details field
                        if(oldTaskType == 7 || oldTaskType == 1)
                        {
                            doc.getElementById('task_subtasks_toggle_' + taskId).style.display = 'none';
                        }
                    	
                        subtasksBody.innerHTML = "";
                        var subtasksWrapper = doc.getElementById("subtasks_wrapper_" + taskId);
                        if(subtasksWrapper.style.display != "none")
                            toggleTaskSubtasksDisplay(null,taskId);

                        //If the task was converted from a project/checklist, show all its former subtasks in the
                        //new section
                        if(response.tasks)
                        {
                            //If we're converting a checklist that's a subtaks, just reload its parent
                            if(isTaskSubtask(taskId))
                            {
                                var parentId = parentIdForTask(taskId);
                                loadSubtasksForTaskIdAndEditSubtask(parentId, taskId);
                                return;
                            }
                            else
                            {
                                var newSectionElement = doc.getElementById('new__tasks_container');
                                newSectionElement.parentNode.parentNode.setAttribute("style", "display:block;");

                                for(var i = 0; i < response.tasks.length; i++)
                                {
                                    var taskJSON = response.tasks[i];
                                    newSectionElement.innerHTML = getTaskHTML(taskJSON) + newSectionElement.innerHTML;
                                }

                                //Also move the converted parent task to the new section
                                var taskContainer = doc.getElementById(taskId);
                                taskContainer.parentNode.removeChild(taskContainer);
                                newSectionElement.insertBefore(taskContainer, newSectionElement.firstChild);
                                setTimeout(function () {
                                    doc.getElementById('active_badge_count_' + taskId).setAttribute('style', '');
                                    doc.getElementById('active_badge_count_' + taskId).innerHTML = 0;
                                    taskEl.setAttribute('activesubtasks', 0);
                                    liveTaskSort();
                                }, 0);
                            }

                           // turnOffSubtasks(taskId);
                        }

                        //if (oldTaskType == 7 || oldTaskType == 1)
                        	//turnOffSubtasks(taskId);
                        	
                        	
                    }
                    jQuery('#' + taskId).attr('tasktype', newTaskType).attr('tasktypedata', newTypeData);

                    var newTitle = titleForTaskType(newTaskType);
                    
                    if (newTaskType == 0 /*normal*/ && useNewTaskEditor)
                    	newTitle = 'change task type';

                    var toggleEl = useNewTaskEditor ? doc.getElementById('task_editor_task_type_' + taskId) : doc.getElementById('task_editor_task_type_label_' + taskId);
                    	toggleEl.innerHTML = newTitle;
                    
                    //update task type icon and details container
                    var taskTypeClass = '';
                    var taskTypeDisplay = 'display:inline-block;';
                    var taskTypeDetailsDisplay = 'display:block;';
                    var taskTypeDataPlaceholder = '';
					var specialTaskAction = false;

					switch (newTaskType)
                    {
                    	case 0:
                    		taskTypeClass = 'normal';
                    		taskTypeDisplay = '';
                    		taskTypeDetailsDisplay = '';
                            set_default_type_data = false;
                    		break;
                    	case 1:
                    		taskTypeClass = 'project';
                    		taskTypeDetailsDisplay = '';
                            set_default_type_data = false;
                    		break;
                    	case 2:
                    		taskTypeClass = 'call';
                    		taskTypeDataPlaceholder = taskStrings.enterPhoneNumber;
                    		break;
                    	case 3:
                    		taskTypeClass = 'sms';
                    		taskTypeDataPlaceholder = taskStrings.enterPhoneNumber;
                    		break;
                    	case 4:
                    		taskTypeClass = 'email';
                    		taskTypeDataPlaceholder = taskStrings.enterEmailAddress;
                    		var specialAction = 'parent.location=\'mailto:' + newTypeData + '\'';
							taskTypeDisplay = 'display:none;z-index:4;';
							specialTaskAction = true;
							break;
                    	case 5:
                    		taskTypeClass = 'location';
                    		taskTypeDataPlaceholder = taskStrings.enterStreetAddress;
							specialTaskAction = true;
							taskTypeDisplay = 'display:none;';
							break;
                    	case 6:
                    		taskTypeClass = 'website';
                    		taskTypeDataPlaceholder = taskStrings.enterWebsiteAddress;
							specialTaskAction = true;
							taskTypeDisplay = 'display:none;';
							break;
                    	case 7:
                    		taskTypeClass = 'checklist';
                    		taskTypeDetailsDisplay = '';
                            set_default_type_data = false;
                    		break;
                    	default:
                    		break;
                    }
                    
                    if (useNewTaskEditor)
                    {
                     	if(newTaskType != 0 /*normal*/ && newTaskType != 1 /*project*/ && newTaskType != 7 /*checklist*/)
                     		doc.getElementById('task_editor_type_details_' + taskId).setAttribute('class', 'type-details on');
                     	else
                     		doc.getElementById('task_editor_type_details_' + taskId).setAttribute('class', 'type-details');
                     		
                     	if (newTaskType == 0)
                     	{
                     		doc.getElementById('task_editor_task_type_wrap_' + taskId).setAttribute('class', 'task-type');
                     		doc.getElementById('task_editor_task_type_icon_' + taskId).setAttribute('class', 'icon ');
                     	}
                     	else
                     	{
                     		doc.getElementById('task_editor_task_type_wrap_' + taskId).setAttribute('class', 'task-type on');
                     		doc.getElementById('task_editor_task_type_icon_' + taskId).setAttribute('class', 'icon ' + taskTypeClass);
                     	}	
                    }	
                    else
                    	doc.getElementById('task_type_details_' + taskId).setAttribute('style', taskTypeDetailsDisplay);       
                    

                	doc.getElementById('task_type_toggle_icon_' + taskId).setAttribute('class', 'task_editor_icon task_type ' + taskTypeClass);
                	doc.getElementById('task_type_' + taskId).setAttribute('class', 'task_editor_icon task_type ' + taskTypeClass);
                	doc.getElementById('task_type_data_toggle_text_' + taskId).innerHTML = '';

                    doc.getElementById('task_type_' + taskId).setAttribute('style', taskTypeDisplay);
                    doc.getElementById('task_type_data_' + taskId).style.display = 'inline-block';
                    doc.getElementById('task_type_data_' + taskId).focus();
                    
                    doc.getElementById('task_type_data_' + taskId).setAttribute('placeholder', taskTypeDataPlaceholder);
                    
                    if (newTaskType == 4)
                    	doc.getElementById('task_type_' + taskId).setAttribute('onclick', specialAction);

					if(specialTaskAction){
						doc.getElementById('task_type_' + taskId).onclick = function(){showTaskQuickActions(taskId)};
					}
                    if (set_default_type_data) {
                        updateTaskTypeData(taskId);
                    }
                   	hideTaskTypeEditor(null,taskId);   
                          
                }
                else
                {

                    if(response.error == "authentication")
                    {
                        //log in again
                        history.go(0);
                    }
                    else
                    {
                       // selectCurrentTaskTypeForTaskId(taskId);

                        if(response.error)
                            displayGlobalErrorMessage(response.error);
                    }
                }
                taskListRowsClass(taskId);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=taskConvert&taskId=" + taskId + "&tasktype=" + newTaskType;
    //if(newTypeData.length > 0)
     //   params += "&typedata=" + newTypeData

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function updateTaskTypeData(taskId, hideEdit)
{
	var doc = document;
	var newTaskTypeDataValue = doc.getElementById('task_type_data_' + taskId).value;
	var taskEl = doc.getElementById(taskId);
	var taskType = parseInt(taskEl.getAttribute('tasktype'), 10);
	
	//prepare task type data
	var typeDataString = '';
	var valueDescription = 'other:';
    var validData = true;

	switch (taskType)
	{
		case 2://sms
			typeDataString = 'Call';
			break;
		case 3: //call
			typeDataString = 'SMS';
			break;
		case 4: //email
			typeDataString = 'Email';
            validData = false;

            if (newTaskTypeDataValue.length > 0) {
                validData = isValidEmailAddress(newTaskTypeDataValue);
            }
			break;
		case 5://address
			typeDataString = 'Location';
			valueDescription = 'location:';
			break;
		case 6://website
			typeDataString = 'URL';
            validData = false;
			valueDescription = 'url:';
            if (newTaskTypeDataValue.length > 0) {
                if (isValidURL(newTaskTypeDataValue) || isValidIP(newTaskTypeDataValue) ||isValidURLIP(newTaskTypeDataValue))
                    validData = true;
            }
            break;
		case 0:
		case 1:
		case 7:
			break;
	};

    if (!validData) {
        doc.getElementById('task_type_data_' + taskId).value = '';
        doc.getElementById('task_type_data_' + taskId).style.border = '1px solid #e00';
    }else {
        doc.getElementById('task_type_data_' + taskId).style.border = 'none';
    }
	var taskTypeData ='';
		taskTypeData += '---- Task Type: ' + typeDataString + ' ---- \n';
		taskTypeData += 'contact: ' + newTaskTypeDataValue + ' \n';
		taskTypeData += valueDescription + ' ' + newTaskTypeDataValue + ' \n';
		taskTypeData += '---- End Task Type ---- \n';
		
		
		
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	if(newTaskTypeDataValue.length > 0)
	{
		doc.getElementById('task_type_' + taskId).style.display = 'inline-block';
	}
	else{
		doc.getElementById('task_type_' + taskId).style.display = 'none';
	}
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
                    taskEl.setAttribute('tasktypedata', taskTypeData); 
                    
                    //if (taskType == 4)
                    //	doc.getElementById('task_type_' + taskId).setAttribute('onclick', 'parent.location=\'mailto:' + taskTypeData + '\'');
                    updateTaskDataFlyout(taskId, hideEdit);
                }
                else
                {

                    if(response.error == "authentication")
                    {
                        //log in again
                        history.go(0);
                    }
                    else
                    {
                       // selectCurrentTaskTypeForTaskId(taskId);

                        if(response.error)
                            displayGlobalErrorMessage(response.error);
                    }
                }
                taskListRowsClass(taskId);
            }
            catch(e)
            {
               // selectCurrentTaskTypeForTaskId(taskId);
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	var params = "method=taskConvert&taskId=" + taskId + "&tasktype=" + taskType + "&typedata=" + taskTypeData;
    //if(newTypeData.length > 0)
     //   params += "&typedata=" + newTypeData

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


//*************************
//        ~Notes
//*************************
function toggleTaskNotes(event,taskId)
{
    if(event)
    	stopEventPropogation(event);
	var doc = document;

	var notesElement = doc.getElementById("notes_wrapper_" + taskId);
	var noteToggle = doc.getElementById("notes_toggle_" + taskId);

	if (notesElement.style.display == "none")
	{
		notesElement.style.display = "block";
		//noteToggle.innerHTML = "Hide Notes";
		noteToggle.innerHTML = labels.hide+ ' ';
		shouldResizeTextarea(doc.getElementById("task_note_" + taskId));
		if (doc.getElementById("task_note_" + taskId).value.length == 0)
			doc.getElementById("task_note_" + taskId).focus();
	}
	else
	{
		notesElement.style.display = "none";
		//noteToggle.innerHTML = "Notes";
		noteToggle.innerHTML = "";
		var note = doc.getElementById("task_note_" + taskId).value;
		if(note == "")
		{
			doc.getElementById('notes_toggle_' + taskId).className = "task_due_date_button";
			doc.getElementById('notes_icon_' + taskId).setAttribute('src', 'https://s3.amazonaws.com/static.plunkboard.com/images/task/note_off_icon.png');
		}
		else
		{
			doc.getElementById('notes_toggle_' + taskId).className = "task_due_date_button task_attribute_has_value";
			doc.getElementById('notes_icon_' + taskId).setAttribute('src', 'https://s3.amazonaws.com/static.plunkboard.com/images/task/note_on_icon.png');
		}
	}
};

function shouldResizeTextarea(focusedTextarea)
{
	var doc = document;

	if (!doc.getElementById('note_clone_textarea'))
		doc.write('<textarea id="clone_textarea" style="position:absolute;"></textarea>');
	var cloneTextarea = doc.getElementById('note_clone_textarea');


	cloneTextarea.style.height = focusedTextarea.style.height;
	cloneTextarea.value = "BU FFER " + focusedTextarea.value;

	//increase submitTextarea's height if addition next few chars trigger a scroll
	while(focusedTextarea.clientHeight < cloneTextarea.scrollHeight)
	{
		var parsedHeight = cloneTextarea.style.height.replace("px", "");
		focusedTextarea.style.height = parseInt(parsedHeight) + parseInt(lineHeight) + "px";
		cloneTextarea.style.height = focusedTextarea.style.height;
	}
	//decrease submitTextarea's height if deletion of next few chars trigger a scroll
};



//*****************
// ! Note
//*****************

function showNoteEditor(taskId)
{
	var doc = document;
	
	var taskNote = doc.getElementById('task_editor_task_note_' + taskId).textContent;
	var headerHTML = labels.edit_note;
    var bodyHTML = '<textarea id="note_editor_' + taskId + '" style="width:98%;height:96%;margin:0 auto;display:block">' + taskNote + '</textarea>';
    var footerHTML = '<div class="button" onclick="cancelNoteEditor(event, \'' + taskId + '\')" id="cancel_note_editor_' + taskId + '">' + labels.cancel + '</div>';
    	footerHTML += '<div class="button" onclick="saveNoteEditorChanges(event, \'' + taskId + '\')" id="save_note_editor_changes_' + taskId +'" >' + labels.save + '</div>';
    
    displayModalContainer(bodyHTML, headerHTML, footerHTML);
    resizeTaskNoteEditor();
    
    doc.getElementById('modal_overlay').onclick = null;
    doc.getElementById('note_editor_' + taskId).focus();
    
    isNoteEditorOpen = true;
};

function resizeTaskNoteEditor()
{
	var doc = document;
    var modalContainer = doc.getElementById('modal_container');
    	modalContainer.setAttribute('style', 'width:80%;height:70%;display:block;');
    	// centerElementInViewPort(modalContainer, true);
    
    var modalHeaderHeight = doc.getElementById('modal_header').clientHeight;
    var modalFooterHeight = doc.getElementById('modal_footer').clientHeight;
    var modalHeight = modalContainer.clientHeight;
    var modalBodyHeight = modalHeight - modalFooterHeight - modalHeaderHeight;
    var modalBody = doc.getElementById('modal_body');
       	modalBody.setAttribute('style', 'height:' + modalBodyHeight + 'px;max-height:' + modalBodyHeight + 'px' );
};

function cancelNoteEditor(event, taskId)
{
	if(event)
		stopEventPropogation(event);
		
	hideModalContainer();
	isNoteEditorOpen = false;
	
	//displayTaskEditor(taskId);
};

function saveNoteEditorChanges(event, taskId)
{
	if(event)
		stopEventPropogation(event);
		
	var doc = document;
	var taskNote = doc.getElementById('note_editor_' + taskId).value;
		taskNote = taskNote.replace(/\n/ig, '<br>');
	doc.getElementById('task_editor_task_note_' + taskId).innerHTML = taskNote;
	
	cancelNoteEditor(event, taskId);
	doc.getElementById('task_editor_task_note_' + taskId).focus();
}

function handleTaskNoteOnClick(event, taskId)
{
	var taskNoteEl = document.getElementById('task_editor_task_note_' + taskId);
	var taskNote = taskNoteEl.innerHTML;
	
	if (taskNote == 'add note')
		taskNoteEl.innerHTML = '';
};

function handleTaskNoteOnChange(event, taskId)
{
	var doc = document;
	var taskNoteEl = doc.getElementById('task_editor_task_note_' + taskId);
	var taskNote = removeHtmlBreaks(taskNoteEl.innerHTML);
	var taskNoteWrap = doc.getElementById('task_editor_note_wrap_' + taskId);	
	
	if (taskNote.length == 0)
	{
		taskNoteEl.innerHTML = 'add note';
		taskNoteWrap.setAttribute('class', 'note');	
	}
	else
		taskNoteWrap.setAttribute('class', 'note on');	
};

function removeHtmlBreaks(aString)
{
	aString = aString.replace(/<div><br><\/div>|<p>/ig, '');
	aString = aString.replace(/<br>|<div>/ig, '');
	aString = aString.replace(/<\/p>|&nbsp;|<div>|<\/div>/ig, '');
	
	return aString;
};

function updateTaskNote(taskId)
{
	var doc = document;

	var taskNote = doc.getElementById('task_editor_task_note_' + taskId).value;
		taskNote = taskNote.replace(/<div><br><\/div>|<p>/ig, '\n');
		taskNote = taskNote.replace(/<br>|<div>/ig, '\n');
		taskNote = taskNote.replace(/<\/p>|&nbsp;|<div>|<\/div>/ig, '');
		
		taskNote = stripHtmlTags(taskNote);
	var oldTaskNote = doc.getElementById('task_original_note_' + taskId).value;

	taskNote = taskNote.trim();
	
	if (useNewTaskEditor)
    	handleTaskNoteOnChange(null, taskId); 

	// if the name didn't change, don't save it
	if(oldTaskNote == taskNote)
	{
		return;
	}

	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                	doc.getElementById('task_original_note_' + taskId).innerHTML = taskNote;
                	//doc.getElementById('task_editor_task_note_' + taskId).innerHTML = taskNote;
                	
                	if (taskNote.length == 0)
                		doc.getElementById('note_icon_' + taskId).setAttribute('style', '');
                	else
                		doc.getElementById('note_icon_' + taskId).style.display = 'inline-block';
                			
                    shouldDisplaySecondRow(taskId);
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
                        displayGlobalErrorMessage(labels.unable_to_update_task);
                    }
                }
                taskListRowsClass(taskId);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=updateTask&taskId=" + taskId + "&taskNote=" + encodeURIComponent(taskNote);

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


//***********************************
// ! Comments
//***********************************

function postTaskComment(event, taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);

	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	var taskName = taskEl.getAttribute('name');
	var newCommentEl = doc.getElementById('new_comment_' + taskId);
	var commentText = newCommentEl.value;
    commentText = commentText.replace(/<(?:.|\n)*?>/gm, '');
	var keyCode = 'keyCode' in event ? event.keyCode : event.charCode;
	
	if (keyCode == 13) //enter key
		event.preventDefault();
		
	if (commentText.length > 0)
	{
		//replace new line char with <br/>
		//var newLinePattern = new RegExp('\\n','g');
		//commentText = linkify(commentText.replace(newLinePattern, '<br/>'));
	
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
	            try
	            {
	                //first make sure there wasn't an authentication error
	                var response = JSON.parse(ajaxRequest.responseText);
	                if(response.success && response.comment)
	                {
	                    var html = doc.getElementById('comments_' + taskId).innerHTML;
	                    var count = parseInt(taskEl.getAttribute('commentscount'), 10);
	                    var newCount = count + 1;
	                    
	                    doc.getElementById('comments_' + taskId).innerHTML = html + htmlForComment(response.comment);
	                    doc.getElementById('new_comment_' + taskId).value = "";
	                    
	                    taskEl.setAttribute('commentscount', newCount);
	                    
	                    if(newCount == 1)
	                    	doc.getElementById('comment_icon_' + taskId).style.display = 'inline-block';
	                    	
	                    if(useNewTaskEditor)
	                   	{
	                   		var newCommentPlaceholder = labels.add_a_comment;
	                   		
		                   	if (newCount == 0)
		                   	{
			                	newCommentPlaceholder = labels.start_a_conversation;
		                   	}
		                   	
		                   	newCommentEl.setAttribute('placeholder', newCommentPlaceholder);
	                   	} 	
	                    	
	                    shouldDisplaySecondRow(taskId);
                        taskListRowsClass(taskId);
	                }
	                else
	                {
	                    if(response.error)
	                    {
	                        if(response.error=="authentication")
	                        {
	                            //make the user log in again
	                            history.go(0);
	                            return;
	                        }
	                        else
	                        {
	                            displayGlobalErrorMessage(response.error);
	                        }
	                    }
	                    else
	                    {
	                        displayGlobalErrorMessage(labels.unable_to_post_comment);
	                    }
	                }
	            }
	            catch(e)
	            {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
	            }
			}
		}
	
		var params = "method=postComment&itemid=" + taskId + "&itemtype=7&itemname=" + taskName + "&comment=" + encodeURIComponent(commentText);
		ajaxRequest.open("POST", ".", true);
	
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);
	}
};

function removeTaskComment(objectId, commentId)
{
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
            	var doc = document;

                var response = JSON.parse(ajaxRequest.responseText);

                if(response.success == true)
                {
					var element = doc.getElementById('task_comment_' + commentId);
						element.parentNode.removeChild(element);
		
					var taskEl = doc.getElementById(objectId);
					var count = parseInt(taskEl.getAttribute('commentscount'), 10);
                    var newCount = count - 1;
                    
                    taskEl.setAttribute('commentscount', newCount);
                    
                    if(newCount == 0)
                    {
                    	doc.getElementById('comment_icon_' + objectId).setAttribute('style', '');
                    
                    	if (useNewTaskEditor)
                    		doc.getElementById('new_comment_' + objectId).setAttribute('placeholder', labels.start_a_conversation);
                    }
                    	
                    shouldDisplaySecondRow(objectId);
                    taskListRowsClass(objectId);
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
                        displayGlobalErrorMessage(labels.failed_to_remove_comment);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=removeComment&commentid=" + commentId;
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


//***********
// ! Star
//*********

function updateTaskStar(taskId, viaDragNDrop)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);

	var hasStar = viaDragNDrop ? 0 : parseInt(taskEl.getAttribute('starred'));
	var starClass = useNewTaskEditor ? 'star icon' : 'task_icon task_editor_star  off';
	
	if (hasStar == 1)
		hasStar = 0;
	else
	{
		hasStar = 1;
		starClass = useNewTaskEditor ? 'star icon on' : 'task_icon task_editor_star';
	}


	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                	if (!viaDragNDrop)
	             		doc.getElementById('task_editor_star_' + taskId).setAttribute('class', starClass);

                	taskEl.setAttribute('starred', hasStar);
                	
                	if (hasStar == 0)
                		doc.getElementById('task_star_' + taskId).setAttribute('style', '');
                	else
                		doc.getElementById('task_star_' + taskId).style.display = 'inline-block';
                    setTimeout(function () {
                        loadListsControl();
                    }, 0);
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }
                        else
                            displayGlobalErrorMessage(response.error);
                    }
                    else
                        displayGlobalErrorMessage(labels.failed_to_toggle_task_star);
                }
                taskListRowsClass(taskId);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	var params = "method=updateTask" + "&taskId=" + taskId + "&starred=" + hasStar;
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function updateTaskDataFlyout(taskId, hideEdit){

    var doc = document;
    var flyout_container = doc.getElementById('task_actions_flyout_' + taskId);
    var task_type_data_container = doc.getElementById('task_type_data_' + taskId);
    var task_type_data_toggle_text = doc.getElementById('task_type_data_toggle_text_' + taskId);
    var flyoutData = getTaskDataFlyoutContent(taskId);
    if (flyoutData === false) {
        flyout_container.innerHTML = '';
        task_type_data_container.setAttribute('style', 'display:inline-block');
    } else {
        flyout_container.innerHTML = flyoutData;
        if (hideEdit) {
            task_type_data_container.setAttribute('style', 'display:none');
            task_type_data_toggle_text.innerHTML = task_type_data_container.value;
        }
    }


}
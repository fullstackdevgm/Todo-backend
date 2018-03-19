
var TASK_RECURRENCE_NONE = 0;
var TASK_RECURRENCE_WEEKLY = 1;
var TASK_RECURRENCE_MONTHLY = 2;
var TASK_RECURRENCE_YEARLY = 3;
var TASK_RECURRENCE_DAILY = 4;
var TASK_RECURRENCE_BIWEEKLY = 5;
var TASK_RECURRENCE_BIMONTHLY = 6;
var TASK_RECURRENCE_SEMIANNUALLY = 7;
var TASK_RECURRENCE_QUARTERLY = 8;
var TASK_RECURRENCE_WITHPARENT = 9;
var TASK_RECURRENCE_ADVANCED = 50;

var ADVANCED_RECURRENCE_EVERYXDAYSWEEKSMONTHS = 0;
var ADVANCED_RECURRENCE_THEXOFEACHMONTH = 1;
var ADVANCED_RECURRENCE_EVERYMONTUEETC = 2;
var ADVANCED_RECURRENCE_UNKNOWN = 3;
    

var MON_SELECTION = 0x0001;
var TUE_SELECTION = 0x0002;
var WED_SELECTION = 0x0004;
var THU_SELECTION = 0x0008;
var FRI_SELECTION = 0x0010;
var SAT_SELECTION = 0x0020;
var SUN_SELECTION = 0x0040;
var WEEKDAY_SELECTION = (MON_SELECTION | TUE_SELECTION | WED_SELECTION | THU_SELECTION | FRI_SELECTION);
var WEEKEND_SELECTION = (SAT_SELECTION | SUN_SELECTION);


function displayRepeatPicker(event,taskId)
{
    setTimeout(function() {

        var taskEl = document.getElementById(taskId);
        var repeatType = parseInt(taskEl.getAttribute('repeat'));
        var repeatString = taskEl.getAttribute('advrepeat');
        var isSubtask = taskEl.getAttribute('isSubtask');

        var normalRepeatOptions = [TASK_RECURRENCE_NONE, TASK_RECURRENCE_DAILY, TASK_RECURRENCE_WEEKLY, TASK_RECURRENCE_BIWEEKLY, TASK_RECURRENCE_MONTHLY, TASK_RECURRENCE_QUARTERLY, TASK_RECURRENCE_SEMIANNUALLY, TASK_RECURRENCE_YEARLY];
        if (isSubtask && isSubtask == "true")
            normalRepeatOptions.push(TASK_RECURRENCE_WITHPARENT);


        //bring up picker
        var pickerEl = document.getElementById('repeat_editor_' + taskId);
        var pickerHTML = '';

        for (var i = 0; i < normalRepeatOptions.length; i++) {
            var option = normalRepeatOptions[i];
            var selected = '';
            var onclick = 'setRepeatValuesForTask(\'' + taskId + '\', ' + option + ', \'\')';
            if (repeatType == option || repeatType == option + 100) {
                selected = ' selected';
                onclick = 'hideRepeatPicker(null, \'' + taskId + '\')';
            }
            var string = localizedStringForTaskRecurrenceType(option, repeatString);
            pickerHTML += '<div class="picker_option repeat_option ' + selected + '" onclick="' + onclick + '"><span class="picker_option_label">' + string + '</span></div>';
        }

        var advancedRepeatOptions = [ADVANCED_RECURRENCE_EVERYXDAYSWEEKSMONTHS, ADVANCED_RECURRENCE_THEXOFEACHMONTH, ADVANCED_RECURRENCE_EVERYMONTUEETC];
        var advancedValToSet = TASK_RECURRENCE_ADVANCED;
        if (repeatType >= 100)
            advancedValToSet += 100;

        var selectedAdvancedType = -1;
        if (repeatType == TASK_RECURRENCE_ADVANCED || repeatType == TASK_RECURRENCE_ADVANCED + 100) {
            selectedAdvancedType = advancedRecurrenceTypeForString(repeatString);
        }
        for (var i = 0; i < advancedRepeatOptions.length; i++) {
            var option = advancedRepeatOptions[i];

            var displayString = localizedGenericStringForAdvancedRecurrenceType(option);

            var selected = '';
            var onclick = 'setRepeatValuesForTask(\'' + taskId + '\', ' + advancedValToSet + ', \'' + defaultStringForAdvancedRecurrenceType(option) + '\')';
            if (selectedAdvancedType == option) {
                selected = ' selected';
                displayString = localizedStringForAdvancedRecurrenceStringOfType(repeatString, option);
                onclick = 'hideRepeatPicker(null, \'' + taskId + '\')';
            }
            pickerHTML += '<div class="picker_option repeat_option ' + selected + '" onclick="' + onclick + '"><span class="picker_option_label">' + displayString + '</span></div>';
        }

        pickerEl.innerHTML = pickerHTML;
        pickerEl.style.display = 'block';

        //set up clickaway event
        var dismissRepeatPicker = function (event) {
            hideRepeatPicker(event, taskId);
        };
        pushWindowClickEvent(dismissRepeatPicker);
    },0);
}

function hideRepeatPicker(event, taskId)
{
	var doc = document;
	var editorEl = doc.getElementById('repeat_editor_' + taskId);
	var toggleEl = useNewTaskEditor ? doc.getElementById('task_editor_repeat_' + taskId) : doc.getElementById('task_editor_repeat_toggle_' + taskId);
	
    if(editorEl && toggleEl && editorEl.style.display == 'block')
    {
        if (event == null)
        {
            editorEl.setAttribute('style', '');
            popWindowClickEvent();
        }
        else
        {
            var eventTarget = event.target;
            var eventTargetParent = event.target.parentNode;
            var eventTargetId = event.target.getAttribute('id');
            
            if ((eventTarget != toggleEl && eventTargetParent != toggleEl && !isDescendant(editorEl, eventTarget)))
            {
                editorEl.setAttribute('style', '');
                popWindowClickEvent();
            }
        }
    }
}

function displayRepeatFromPicker(event, taskId)
{
    setTimeout(function() {
        var doc = document;
        var taskEl = doc.getElementById(taskId);
        var repeatType = parseInt(taskEl.getAttribute('repeat'));
        var repeatString = taskEl.getAttribute('advrepeat');

        //bring up picker
        var pickerEl = useNewTaskEditor ? doc.getElementById('task_editor_repeat_from_editor_' + taskId) : doc.getElementById('repeat_from_editor_' + taskId);

        var selected = '';
        var onclick = 'setRepeatFromValueForTask(\'' + taskId + '\', true)';
        if(repeatType < 100)
        {
            selected = ' selected';
            onclick = 'hideRepeatFromPicker(null, \'' + taskId + '\')';
        }
        var pickerHTML = '<div class="picker_option repeat_option' + selected + '" onclick="' + onclick + '"><span class="picker_option_label">'+labels.repeat_from_due_date +'</span></div>';

        selected = '';
        onclick = 'setRepeatFromValueForTask(\'' + taskId + '\', false)';
        if(repeatType >= 100)
        {
            selected = ' selected';
            onclick = 'hideRepeatFromPicker(null, \'' + taskId + '\')';
        }

        pickerHTML +=  '<div class="picker_option repeat_option' + selected + '" onclick="' + onclick + '"><span class="picker_option_label">'+labels.repeat_from_completion_date+'</span></div>';

        pickerEl.innerHTML = pickerHTML;
        pickerEl.style.display = 'block';

        //set up clickaway event
        var dismissRepeatFromPicker = function(event){hideRepeatFromPicker(event, taskId);};
        pushWindowClickEvent(dismissRepeatFromPicker);
    },0);
}

function hideRepeatFromPicker(event, taskId)
{
	var doc = document;
	var editorEl = useNewTaskEditor ? doc.getElementById('task_editor_repeat_from_editor_' + taskId): doc.getElementById('repeat_from_editor_' + taskId);
	var toggleEl = useNewTaskEditor ? doc.getElementById('task_editor_repeat_from_' + taskId) : doc.getElementById('task_editor_repeat_from_toggle_' + taskId);
	
	if (event == null)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
	}
	else
	{
		var eventTarget = event.target;
		var eventTargetParent = event.target.parentNode;
		var eventTargetId = event.target.getAttribute('id');
		
		if ((eventTarget != toggleEl && eventTargetParent != toggleEl && !isDescendant(editorEl, eventTarget)))
		{
			editorEl.setAttribute('style', '');
			popWindowClickEvent();
		}
	}
}


function setRepeatValuesForTask(taskId, repeatType, advancedString)
{
	var doc = document;
	var taskEl = document.getElementById(taskId);
    var oldRepeatValue = parseInt(taskEl.getAttribute('repeat'));
    var oldRepeatString = taskEl.getAttribute('advrepeat');
    
    if(oldRepeatValue > 100 && repeatType < 100)
        repeatType += 100;
    
    if(oldRepeatValue == repeatType && oldRepeatString == advancedString)
    {
        hideRepeatPicker(null, taskId);
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
                if(response.success)
                {
                    taskEl.setAttribute('repeat', repeatType);
                    taskEl.setAttribute('advrepeat', advancedString);
                    hideRepeatPicker(null, taskId);
                    hideTaskXOrdinalPicker(null, taskId);
                    hideTaskXDayPicker(null, taskId);
                    hideTaskXUnitPicker(null, taskId);
                    
                    var string = localizedStringForTaskRecurrenceType(repeatType, advancedString);
                    var toggle = useNewTaskEditor ? doc.getElementById('task_editor_repeat_' + taskId) : doc.getElementById('task_editor_repeat_toggle_' + taskId);
                    	toggle.innerHTML = string;
                    
                    string = localizedFromDueDateOrCompletionStringForType(repeatType);
                    var repeatFromToggle = useNewTaskEditor ? doc.getElementById('task_editor_repeat_from_' + taskId) : doc.getElementById('task_editor_repeat_from_toggle_' + taskId);
                    	repeatFromToggle.innerHTML = string;
                    
                    var display = 'none';
                    if(repeatType != TASK_RECURRENCE_NONE && repeatType != TASK_RECURRENCE_NONE + 100)
                        display = 'block';
                    
                    var repeatFromWrapEl = useNewTaskEditor ? doc.getElementById('task_editor_repeat_from_wrap_' + taskId) : doc.getElementById('repeat_from_wrapper');
                    	
                    if (useNewTaskEditor)
                    {
                    	repeatToggleWrapEl = doc.getElementById('task_editor_repeat_wrap_' + taskId);
                    	
                    	if (repeatType == 0 || repeatType == 100)
                    	{
                    		repeatFromWrapEl.setAttribute('class', 'repeat-from');
                    		repeatToggleWrapEl.setAttribute('class', 'repeat');
                    		toggle.innerHTML = labels.set_repeat_frequency;
                    	}
                    	else
                    	{
                    		repeatFromWrapEl.setAttribute('class', 'repeat-from on');
                    		repeatToggleWrapEl.setAttribute('class', 'repeat on');
                    	}
                    }
                  
                    	repeatFromWrapEl.style.display = display;
                    
                    	var advancedHtml = htmlForAdvancedPicker(taskId, repeatType, advancedString);
                    	var advancedWrapEl = doc.getElementById('advanced_repeat_wrapper_' + taskId);
	                    if(advancedHtml.length > 0)
	                    {
	                        advancedWrapEl.style.display = 'block';
	                        advancedWrapEl.innerHTML = advancedHtml;
	                    }
	                    else
	                    {
	                        advancedWrapEl.style.display = 'none';
	                        advancedWrapEl.innerHTML = '';
	                    }
                  
                    
                    if(repeatType != 0 && repeatType != 100 && repeatType != 9 && repeatType != 109)
                       doc.getElementById('repeat_icon_' + taskId).style.display = 'inline-block';
                    else
                        doc.getElementById('repeat_icon_' + taskId).style.display = 'none';
                    
                    
                    shouldDisplaySecondRow(taskId);
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
                            displayGlobalErrorMessage(response.error)
                        else
                            displayGlobalErrorMessage(labels.unable_to_update_task_recurrence + '.');
                    }
                }
                taskListRowsClass(taskId);
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_update_task_recurrence + ': ' + e);
            }
		}
	}

	var params = "method=updateTask&taskId=" + taskId + "&recurrenceType=" + repeatType;

    if(advancedString.length > 0)
        params += "&advancedRecurrenceString=" + advancedString;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function setRepeatFromValueForTask(taskId, repeatFromDueDate)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
    var repeatVal = parseInt(taskEl.getAttribute('repeat'));
    var repeatString = taskEl.getAttribute('advrepeat');
    
    if(repeatFromDueDate)
    {
        if(repeatVal >= 100)
            repeatVal -= 100;
        else
        {
            hideRepeatFromPicker(null, taskId);
            return;
        }
    }
    else
    {
        if(repeatVal < 100)
            repeatVal += 100;
        else
        {
            hideRepeatFromPicker(null, taskId);
            return;
        }
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
                if(response.success)
                {
                    taskEl.setAttribute('repeat', repeatVal);
                    taskEl.setAttribute('advrepeat', repeatString);
                    hideRepeatFromPicker(null, taskId);
                    
                    var string = localizedFromDueDateOrCompletionStringForType(repeatVal);
                    var toggleEl = useNewTaskEditor ? doc.getElementById('task_editor_repeat_from_' + taskId) : doc.getElementById('task_editor_repeat_from_toggle_' + taskId);
                    	toggleEl.innerHTML = string;
                    
                    shouldDisplaySecondRow(taskId);
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
                            displayGlobalErrorMessage(response.error)
                        else
                            displayGlobalErrorMessage(labels.unable_to_update_task_recurrence +'.');
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_update_task_recurrence + ': ' + e);
            }
		}
	}

	var params = "method=updateTask&taskId=" + taskId + "&recurrenceType=" + repeatVal;

    if(repeatString.length > 0)
        params += "&advancedRecurrenceString=" + repeatString;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function htmlForAdvancedPicker(taskId, type, advancedString)
{
    if(type == TASK_RECURRENCE_ADVANCED || type == TASK_RECURRENCE_ADVANCED + 100)
    {
        var advancedType = advancedRecurrenceTypeForString(advancedString);
        
        switch(advancedType)
        {
            case ADVANCED_RECURRENCE_EVERYMONTUEETC:
            {
                return htmlForEveryEtcPicker(taskId, advancedString);
            }
            case ADVANCED_RECURRENCE_THEXOFEACHMONTH:
            {
                return htmlForTheXOfEachMonthPicker(taskId, advancedString);
            }
            case ADVANCED_RECURRENCE_EVERYXDAYSWEEKSMONTHS:
            {
                return htmlForEveryXDaysPicker(taskId, advancedString);
            }
            default:
                break;
        }
    }
    
    return '';
}

function htmlForEveryXDaysPicker(taskId, advancedString)
{
    var interval = getIntervalFromRepeatEveryXDaysString(advancedString);
    var units = getLocalizedUnitsFromRepeatEveryXDaysString(advancedString);
    
    if(interval != null && units != null)
    {
        var html = '    <div class="property_label"></div>';
        
            html += '	<span class="property_wrapper repeat_wrapper">';
            html += '		<input type="text" size="3" class="every_x_input" onkeyup="formatXDaysRecurrenceInterval(this)" size="2" maxlength="3" value="' + interval + '" onblur="updateEveryXDaysRecurrenceWithNewInterval(this, \'' + taskId + '\', \'' + advancedString + '\')" />';
        
            html += '       <span class="dropdown_toggle" id="task_x_units_picker_toggle_' + taskId + '" onclick="displayTaskXUnitPicker(event, \'' + taskId + '\', \'' + advancedString + '\', \'' + units + '\')" >' + units + '</span>';
            html += '       <div class="property_flyout task_repeat_editor_flyout" id="task_x_units_picker_flyout_' + taskId + '"></div>';
            html += ' 	</span>';
        
        return html;
    }
    
    return '';
}

function displayTaskXUnitPicker(event, taskId, advancedString, selectedLocalizedUnit)
{
    var taskEl = document.getElementById(taskId);
    var repeatType = parseInt(taskEl.getAttribute('repeat'));
    
    var pickerHTML = '';
    
    var options = [{unlocalized:'Days', localized:labels.days},{unlocalized:'Weeks', localized:labels.weeks},{unlocalized:'Months', localized:labels.months},{unlocalized:'Years', localized:labels.years}];
    
    for(var i = 0; i < options.length; i++)
    {
        var obj = options[i];
        var localizedStr = obj.localized;
        var unlocalizedStr = obj.unlocalized;
        
        var selected = '';
        var newAdvancedString = stringByReplacingUnitsInAdvancedString(advancedString, unlocalizedStr);
        var onclick = 'setRepeatValuesForTask(\'' + taskId + '\', ' + repeatType + ', \'' + newAdvancedString + '\')';
        if(selectedLocalizedUnit == localizedStr)
        {
            selected = 'selected';
            onclick = 'hideTaskXUnitPicker(null, \'' + taskId + '\')';
        }
        
        
        pickerHTML += '<div class="picker_option repeat_option ' + selected + '" onclick="' + onclick + '"><span class="picker_option_label">' + localizedStr + '</span></div>';
    }
    var pickerEl = document.getElementById('task_x_units_picker_flyout_' + taskId);
    
    pickerEl.innerHTML = pickerHTML;
    pickerEl.style.display = 'block';
    
    //set up clickaway event
    var dismissRepeatPicker = function(event){hideTaskXUnitPicker(event, taskId);};
    pushWindowClickEvent(dismissRepeatPicker);
    
    
}

function hideTaskXUnitPicker(event, taskId)
{
    var editorEl = document.getElementById('task_x_units_picker_flyout_' + taskId);
	var toggleEl = document.getElementById('task_x_units_picker_toggle_' + taskId);
	
    if(editorEl && toggleEl && editorEl.style.display == 'block')
    {
        if (event == null)
        {
            editorEl.setAttribute('style', '');
            popWindowClickEvent();
        }
        else
        {
            var eventTarget = event.target;
            var eventTargetParent = event.target.parentNode;
            var eventTargetId = event.target.getAttribute('id');
            
            if ((eventTarget != toggleEl && eventTargetParent != toggleEl && !isDescendant(editorEl, eventTarget)))
            {
                editorEl.setAttribute('style', '');
                popWindowClickEvent();
            }
        }
    }
}


function formatXDaysRecurrenceInterval(input)
{
    var num = input.value.replace(/\,/g,'');
    if(!isNaN(num))
    {
        if(num.indexOf('.') > -1)
        {
            input.value = input.value.substring(0,input.value.length-1);
        }
        else if(parseInt(num) > 100)
        {
            input.value = "100";
        }
        else if(parseInt(num) <= 0)
        {
            input.value = "1";
        }
    }
    else
    {
        input.value = input.value.substring(0,input.value.length-1);
    }
}

function updateEveryXDaysRecurrenceWithNewInterval(input, taskId, advancedString)
{
    formatXDaysRecurrenceInterval(input);
    
    if(input.value.length == 0)
        input.value = "1";
    
    var num = input.value.replace(/\,/g,'');
    
    var newAdvancedString = stringByReplacingIntervalInAdvancedString(advancedString, num);
    var taskEl = document.getElementById(taskId);
    var repeatType = parseInt(taskEl.getAttribute('repeat'));
    
    if(newAdvancedString != null && newAdvancedString != advancedString)
    {
        setRepeatValuesForTask(taskId, repeatType, newAdvancedString);
    }
}

function htmlForTheXOfEachMonthPicker(taskId, advancedString)
{
    var day = getLocalizedDayStringFromRepeatOnXOfMonthString(advancedString);
    var ordinal = getLocalizedOrdinalStringFromRepeatOnXOfMonthString(advancedString);
    if(ordinal != null && day != null)
    {
        var html = '    <div class="property_label"></div>';
        
            html += '	<span class="dropdown_toggle property_wrapper repeat_wrapper" style="width:40px">';
            html += '		<span class="" id="task_x_ordinal_picker_toggle_' + taskId + '" onclick="displayTaskXOrdinalPicker(event,\'' + taskId + '\', \'' + advancedString + '\', \'' + ordinal + '\')" >' + ordinal + '</span>';
            html += '		<div class="property_flyout task_repeat_editor_flyout" id="task_x_ordinal_picker_flyout_' + taskId + '"></div>';
            html += '	</span>';
            html += '	<span class="dropdown_toggle property_wrapper repeat_wrapper" style="width:100px">';
            html += '       <span class="" id="task_x_day_picker_toggle_' + taskId + '" onclick="displayTaskXDayPicker(event,\'' + taskId + '\', \'' + advancedString + '\', \'' + day + '\')" >' + day + '</span>';
            html += '       <div class="property_flyout task_repeat_editor_flyout" id="task_x_day_picker_flyout_' + taskId + '"></div>';
            html += ' 	</span>';
        
        return html;
    }
    return '';
}

function displayTaskXOrdinalPicker(event, taskId, advancedString, selectedLocalizedOrdinal)
{
    var taskEl = document.getElementById(taskId);
    var repeatType = parseInt(taskEl.getAttribute('repeat'));
    
    var pickerHTML = '';
    
    var options = [{unlocalized:'1st', localized:labels._1st},{unlocalized:'2nd', localized:labels._2nd},{unlocalized:'3rd', localized:labels._3rd},{unlocalized:'4th', localized:labels._4th},{unlocalized:'5th',localized:labels._5th},{unlocalized:'last',localized:labels._last}];
    
    
    for(var i = 0; i < options.length; i++)
    {
        var obj = options[i];
        var localizedStr = obj.localized;
        var unlocalizedStr = obj.unlocalized;
        
        var selected = '';
        var newAdvancedString = stringByReplacingOrdinalInAdvancedString(advancedString, unlocalizedStr);
        var onclick = 'setRepeatValuesForTask(\'' + taskId + '\', ' + repeatType + ', \'' + newAdvancedString + '\')';
        if(selectedLocalizedOrdinal == localizedStr)
        {
            selected = 'selected';
            onclick = 'hideTaskXOrdinalPicker(null, \'' + taskId + '\')';
        }
        
        
        pickerHTML += '<div class="picker_option repeat_option ' + selected + '" onclick="' + onclick + '"><span class="picker_option_label">' + localizedStr + '</span></div>';
    }
    var pickerEl = document.getElementById('task_x_ordinal_picker_flyout_' + taskId);
    
    pickerEl.innerHTML = pickerHTML;
    pickerEl.style.display = 'block';
    
    //set up clickaway event
    var dismissRepeatPicker = function(event){hideTaskXOrdinalPicker(event, taskId);};
    pushWindowClickEvent(dismissRepeatPicker);
    
    
}

function hideTaskXOrdinalPicker(event, taskId)
{
    var editorEl = document.getElementById('task_x_ordinal_picker_flyout_' + taskId);
	var toggleEl = document.getElementById('task_x_ordinal_picker_toggle_' + taskId);
	
    if(editorEl && toggleEl && editorEl.style.display == 'block')
    {
        if (event == null)
        {
            editorEl.setAttribute('style', '');
            popWindowClickEvent();
        }
        else
        {
            var eventTarget = event.target;
            var eventTargetParent = event.target.parentNode;
            var eventTargetId = event.target.getAttribute('id');
            
            if ((eventTarget != toggleEl && eventTargetParent != toggleEl && !isDescendant(editorEl, eventTarget)))
            {
                editorEl.setAttribute('style', '');
                popWindowClickEvent();
            }
        }
    }
}

function displayTaskXDayPicker(event, taskId, advancedString, selectedLocalizedDay)
{
    var taskEl = document.getElementById(taskId);
    var repeatType = parseInt(taskEl.getAttribute('repeat'));
    
    var pickerHTML = '';
    
    var options = [{unlocalized:'Monday', localized:labels.monday },{unlocalized:'Tuesday', localized:labels.tuesday},{unlocalized:'Wednesday', localized:labels.wednesday},{unlocalized:'Thursday', localized:labels.thursday},{unlocalized:'Friday',localized:labels.friday},{unlocalized:'Saturday',localized:labels.saturday}, {unlocalized:'Sunday', localized:labels.sunday}];
    
    
    for(var i = 0; i < options.length; i++)
    {
        var obj = options[i];
        var localizedStr = obj.localized;
        var unlocalizedStr = obj.unlocalized;
        
        var selected = '';
        var newAdvancedString = stringByReplacingDayInAdvancedString(advancedString, unlocalizedStr);
        var onclick = 'setRepeatValuesForTask(\'' + taskId + '\', ' + repeatType + ', \'' + newAdvancedString + '\')';
        if(selectedLocalizedDay == localizedStr)
        {
            selected = 'selected';
            onclick = 'hideTaskXDayPicker(null, \'' + taskId + '\')';
        }
        
        
        pickerHTML += '<div class="picker_option repeat_option ' + selected + '" onclick="' + onclick + '"><span class="picker_option_label">' + localizedStr + '</span></div>';
    }
    var pickerEl = document.getElementById('task_x_day_picker_flyout_' + taskId);
    
    pickerEl.innerHTML = pickerHTML;
    pickerEl.style.display = 'block';
    
    //set up clickaway event
    var dismissRepeatPicker = function(event){hideTaskXDayPicker(event, taskId);};
    pushWindowClickEvent(dismissRepeatPicker);
    
    
}

function hideTaskXDayPicker(event, taskId)
{
    var editorEl = document.getElementById('task_x_day_picker_flyout_' + taskId);
	var toggleEl = document.getElementById('task_x_day_picker_toggle_' + taskId);
	
    if(editorEl && toggleEl && editorEl.style.display == 'block')
    {
        if (event == null)
        {
            editorEl.setAttribute('style', '');
            popWindowClickEvent();
        }
        else
        {
            var eventTarget = event.target;
            var eventTargetParent = event.target.parentNode;
            var eventTargetId = event.target.getAttribute('id');
            
            if ((eventTarget != toggleEl && eventTargetParent != toggleEl && !isDescendant(editorEl, eventTarget)))
            {
                editorEl.setAttribute('style', '');
                popWindowClickEvent();
            }
        }
    }
}

function htmlForEveryEtcPicker(taskId, advancedString)
{
    var options = [{value:MON_SELECTION, localized:labels.every_monday},{value:TUE_SELECTION, localized:labels.every_tuesday },{value:WED_SELECTION, localized:labels.every_wednesday},{value:THU_SELECTION, localized:labels.every_thursday},{value:FRI_SELECTION,localized:labels.every_friday },{value:SAT_SELECTION,localized:labels.every_saturday}, {value:SUN_SELECTION, localized:labels.every_sunday}];

    var selectedDays = getSelectedDaysFromAdvancedString(advancedString);

    var html = '   <div class="property_label"></div>';
    html += '      <span class="property_wrapper repeat_wrapper" style="margin-top:3px;">';
    
    for(var i= 0; i < options.length; i++)
    {
        var option = options[i];
        var value = option.value;
        var localizedStr = option.localized;
        
        var checked = '';
        if(selectedDays & value)
        {
            checked = 'checked="true"';
        }
        
        html += '<label style="padding:0;" for="every_etc_checkbox_' + taskId + '_' + value + '">';
        html += '<input type="checkbox" ' + checked + ' id="every_etc_checkbox_' + taskId + '_' + value + '" value="' + value + '" name="everyEtcCheckbox_' + taskId + '" onchange="saveEveryEtcStringFromCheckboxes(\'' + taskId + '\')"/> ';
        html += localizedStr + '</label>';
        
    }
    
    html += ' 	</span>';
    
    return html;

}

function saveEveryEtcStringFromCheckboxes(taskId)
{
    var selectedDays = 0;
    var checkboxes = document.getElementsByName('everyEtcCheckbox_' + taskId);
    
    for(var i = 0; i < checkboxes.length; i++)
    {
        var checkbox = checkboxes[i];
        if(checkbox.checked)
        {
            var value = parseInt(checkbox.value, 10);
            selectedDays |= value;
        }
    }
    
    var repeatString = getRepeatStringForSelectedDays(selectedDays, false, false, false, false);
    var taskEl = document.getElementById(taskId);
    var repeatType = parseInt(taskEl.getAttribute('repeat'));
    
    setRepeatValuesForTask(taskId, repeatType, repeatString);
}


function localizedStringForTaskRecurrenceType(type, advancedString)
{
    switch(type)
    {
        case TASK_RECURRENCE_DAILY:
        case TASK_RECURRENCE_DAILY + 100:
        {
            return labels.every_day;
        }
        case TASK_RECURRENCE_NONE:
        case TASK_RECURRENCE_NONE + 100:
        {
            return labels.none;
        }
        case TASK_RECURRENCE_WEEKLY:
        case TASK_RECURRENCE_WEEKLY + 100:
        {
            return labels.every_week;
        }
        case TASK_RECURRENCE_BIWEEKLY:
        case TASK_RECURRENCE_BIWEEKLY + 100:
        {
            return labels.every_2_weeks;
        }
        case TASK_RECURRENCE_MONTHLY:
        case TASK_RECURRENCE_MONTHLY + 100:
        {
            return labels.every_month;
        }
        case TASK_RECURRENCE_QUARTERLY:
        case TASK_RECURRENCE_QUARTERLY + 100:
        {
            return labels.quarterly;
        }
        case TASK_RECURRENCE_SEMIANNUALLY:
        case TASK_RECURRENCE_SEMIANNUALLY + 100:
        {
            return labels.semiannually;
        }
        case TASK_RECURRENCE_YEARLY:
        case TASK_RECURRENCE_YEARLY + 100:
        {
            return labels.every_year;
        }
        case TASK_RECURRENCE_WITHPARENT:
        case TASK_RECURRENCE_WITHPARENT + 100:
        {
            return labels.repeat_with_parent_task;
        }
        case TASK_RECURRENCE_ADVANCED:
        case TASK_RECURRENCE_ADVANCED + 100:
        {
            advancedString = trim(advancedString);
        console.log()
            if(advancedString != null && advancedString.length > 0)
            {
                var advType = advancedRecurrenceTypeForString(advancedString);
                if(advType != ADVANCED_RECURRENCE_UNKNOWN)
                {
                    return localizedStringForAdvancedRecurrenceStringOfType(advancedString, advType);
                }
            }
        
        }
        default:
            return subStrings.unknown;
    }
}

function localizedFromDueDateOrCompletionStringForType(type)
{
    if(type >= 100)
        return labels.repeat_from_completion_date;
    else
        return labels.repeat_from_due_date
}

function localizedGenericStringForAdvancedRecurrenceType(type)
{
    switch(type)
    {
        case ADVANCED_RECURRENCE_EVERYXDAYSWEEKSMONTHS:
        case ADVANCED_RECURRENCE_EVERYXDAYSWEEKSMONTHS + 100:
        {
            return labels.every_x_days;
        }
        case ADVANCED_RECURRENCE_EVERYMONTUEETC:
        case ADVANCED_RECURRENCE_EVERYMONTUEETC + 100:
        {
            return labels.on_days_of_the_week ;
        }
        case ADVANCED_RECURRENCE_THEXOFEACHMONTH:
        case ADVANCED_RECURRENCE_THEXOFEACHMONTH + 100:
        {
            return labels.the_x_day_of_each_month ;
        }
        default:
            return subStrings.unknown;
    }
}

function localizedStringForAdvancedRecurrenceStringOfType(advancedString, advancedType)
{
    switch(advancedType)
    {
        case ADVANCED_RECURRENCE_EVERYXDAYSWEEKSMONTHS:
        case ADVANCED_RECURRENCE_EVERYXDAYSWEEKSMONTHS + 100:
        {
            return localizedStringForRepeatEveryXDaysString(advancedString);
        }
        case ADVANCED_RECURRENCE_EVERYMONTUEETC:
        case ADVANCED_RECURRENCE_EVERYMONTUEETC + 100:
        {
            return localizedStringForRepeatEveryXEtcString(advancedString);
        }
        case ADVANCED_RECURRENCE_THEXOFEACHMONTH:
        case ADVANCED_RECURRENCE_THEXOFEACHMONTH + 100:
        {
            return localizedStringForRepeatOnTheXOfTheMonthString(advancedString);
        }
        default:
            return subStrings.unknown;
    }

}

function defaultStringForAdvancedRecurrenceType(advancedType)
{
    switch(advancedType)
    {
        case ADVANCED_RECURRENCE_EVERYXDAYSWEEKSMONTHS:
        case ADVANCED_RECURRENCE_EVERYXDAYSWEEKSMONTHS + 100:
        {
            return 'Every 1 days';
        }
        case ADVANCED_RECURRENCE_EVERYMONTUEETC:
        case ADVANCED_RECURRENCE_EVERYMONTUEETC + 100:
        {
            return 'Every Monday';
        }
        case ADVANCED_RECURRENCE_THEXOFEACHMONTH:
        case ADVANCED_RECURRENCE_THEXOFEACHMONTH + 100:
        {
            return 'The 1st Monday of each month';
        }
        default:
            return subStrings.unknown;
    }

}

function localizedStringForRepeatEveryXDaysString(advancedString)
{
    var interval = getIntervalFromRepeatEveryXDaysString(advancedString);
    var units = getLocalizedUnitsFromRepeatEveryXDaysString(advancedString);
    
    if(units != null && interval != null)
    {
        return labels.every + " " + interval + " " + units;
    }
    
    return subStrings.unknown;
}

function getIntervalFromRepeatEveryXDaysString(advancedString)
{
    if( (advancedString == null) || (advancedString.length == 0) )
        return null;

    var components = advancedString.split(/\s+/);
    if(components.length < 3)
        return null;

    if(components[0].toLowerCase() == "every")
    {
        var number = parseInt(components[1], 10);
        if(number >= 0)
            return number;
    }
    
    return null;
}

function getLocalizedUnitsFromRepeatEveryXDaysString(advancedString)
{
     if( (advancedString == null) || (advancedString.length == 0) )
        return null;

    var components = advancedString.split(/\s+/);
    if(components.length < 3)
        return null;

    if(components[0].toLowerCase() == "every")
    {
        var dayMonthYearVal = components[2];

        if(dayMonthYearVal.toLowerCase() ==  "weeks" || dayMonthYearVal.toLowerCase() == "week")
        {
            return labels.weeks;
        }
        else if(dayMonthYearVal.toLowerCase() == "months" || dayMonthYearVal.toLowerCase() ==  "month")
        {
            return labels.months;
        }
        else if(dayMonthYearVal.toLowerCase() == "years" || dayMonthYearVal.toLowerCase() == "year")
        {
            return labels.years;
        }
        else
        {
            return labels.days;
        }
    }
    return null;
}

function stringByReplacingIntervalInAdvancedString(advancedString, interval)
{
    if( (advancedString == null) || (advancedString.length == 0) )
        return null;

    var components = advancedString.split(/\s+/);
    if(components.length > 1)
    {
        components.splice(1, 1, interval);
        return components.join(" ");
        
    }

    return null;
}

function stringByReplacingUnitsInAdvancedString(advancedString, units)
{
    if( (advancedString == null) || (advancedString.length == 0) )
        return null;

    var components = advancedString.split(/\s+/);
    if(components.length > 2)
    {
        components.splice(2, 1, units);
        return components.join(" ");
        
    }

    return null;
}

function localizedStringForRepeatOnTheXOfTheMonthString(advancedString)
{
    if( (advancedString == null) || (advancedString.length == 0) )
        return subStrings.unknown;

    var localizedDayString = getLocalizedDayStringFromRepeatOnXOfMonthString(advancedString);

    if(localizedDayString != null)
    {
        var localizedOrdinal = getLocalizedOrdinalStringFromRepeatOnXOfMonthString(advancedString);
        if(localizedOrdinal != null)
        {
            return sprintf(labels.the_of_each_month, localizedOrdinal, localizedDayString);
        }
    }
    
    return subStrings.unknown;
}

function getLocalizedDayStringFromRepeatOnXOfMonthString(advancedString)
{
    if( (advancedString == null) || (advancedString.length == 0) )
        return null;

    var localizedDayString = null;

    var components = advancedString.split(/\s+/);
    var compZero = components[0].toLowerCase();
    var compOne = components[1].toLowerCase();
    var compTwo = components[2].toLowerCase();

    if(compZero == "the")
    {
        if( compTwo == "monday" || compTwo == "mon")
            localizedDayString = labels.monday;
        else if( (compTwo == "tuesday") || (compTwo == "tue") || (compTwo == "tues"))
            localizedDayString = labels.tuesday;
        else if( (compTwo == "wednesday") || (compTwo == "wed") )
            localizedDayString = labels.wednesday;
        else if( (compTwo == "thursday") || (compTwo == "thu")
                || (compTwo == "thur") || (compTwo == "thurs"))
            localizedDayString = labels.thursday;
        else if( (compTwo == "friday") || (compTwo == "fri"))
            localizedDayString = labels.friday;
        else if( (compTwo == "saturday") || (compTwo == "sat") )
            localizedDayString = labels.saturday;
        else if( (compTwo == "sunday") || (compTwo == "sun") )
            localizedDayString = labels.sunday;
    }
    
    return localizedDayString;
}

function stringByReplacingDayInAdvancedString(advancedString, newDay)
{
    if( (advancedString == null) || (advancedString.length == 0) )
        return null;

    var components = advancedString.split(/\s+/);
    if(components.length > 2)
    {
        components.splice(2, 1, newDay);
        return components.join(" ");
        
    }

    return null;
}

function getLocalizedOrdinalStringFromRepeatOnXOfMonthString(advancedString)
{
    if( (advancedString == null) || (advancedString.length == 0) )
        return null;

    var localizedOrdinal = null;

    var components = advancedString.split(/\s+/);
    var compZero = components[0].toLowerCase();
    var compOne = components[1].toLowerCase();
    var compTwo = components[2].toLowerCase();

    if(compZero == "the")
    {
        if( (compOne == "first") || (compOne == "1st"))
            localizedOrdinal = labels._1st;
        else if( (compOne == "second") || (compOne == "2nd"))
            localizedOrdinal = labels._2nd;
        else if( (compOne == "third") || (compOne == "3rd"))
            localizedOrdinal = labels._3rd;
        else if( (compOne == "fourth") || (compOne == "4th"))
            localizedOrdinal = labels._4th;
        else if( (compOne == "fifth") || (compOne == "5th"))
            localizedOrdinal = labels._5th;
        else if( (compOne == "last") || (compOne == "final"))
            localizedOrdinal = labels._last;
    }
    return localizedOrdinal;
}

function stringByReplacingOrdinalInAdvancedString(advancedString, newOrdinal)
{
    if( (advancedString == null) || (advancedString.length == 0) )
        return null;

    var components = advancedString.split(/\s+/);
    if(components.length > 1)
    {
        components.splice(1, 1, newOrdinal);
        return components.join(" ");
        
    }

    return null;
}

function localizedStringForRepeatEveryXEtcString(advancedString)
{
    if( (advancedString == null) || (advancedString.length == 0) )
        return subStrings.unknown;

    advancedString = advancedString.toLowerCase();

    var selectedDays = getSelectedDaysFromAdvancedString(advancedString);
    
    var addSecond = false;
    var addThird = false;
    var addFourth = false;

    if(advancedString.indexOf("second") >= 0)
    {
        addSecond = true;
        addThird = false;
        addFourth = false;
    }
    if(advancedString.indexOf("third") >= 0)
    {
        addSecond = false;
        addThird = true;
        addFourth = false;
    }
    if(advancedString.indexOf("fourth") >= 0)
    {
        addSecond = false;
        addThird = false;
        addFourth = true;
    }

    var localizedString = getRepeatStringForSelectedDays(selectedDays, true, addSecond, addThird, addFourth);

    return localizedString;
}

function getRepeatStringForSelectedDays(selectedDays, localized, addSecond, addThird, addFourth)
{
    //TODO: localize this method so that if localized=true, we return translated strings
    var weekdaySelected = false;
    var repeatString = null;
    
    if( (selectedDays & (WEEKEND_SELECTION | WEEKDAY_SELECTION)) == (WEEKEND_SELECTION | WEEKDAY_SELECTION) )
    {
        if(localized)
            repeatString = labels.every_day;
        else
            repeatString = "Every Day";
    }
    else
    {
        if(localized)
            repeatString = labels.every + " ";
        else
            repeatString = "Every ";

        if(addSecond)
        {
            if(localized)
                repeatString += labels._2nd + " ";
            else
                repeatString += "2nd ";
        }
        else if(addThird)
        {
            if(localized)
                repeatString += labels._3rd+" ";
            else
                repeatString += "3rd ";
        }
        else if(addFourth)
        {
            if(localized)
                repeatString += labels._4th+" ";
            else
                repeatString += "4th ";
        }

        if( ( (selectedDays & WEEKDAY_SELECTION) == WEEKDAY_SELECTION) &&
           ( !(selectedDays & SAT_SELECTION) ) &&
           ( !(selectedDays & SUN_SELECTION) ) )
        {
            if(localized)
                repeatString += labels.weekday;
            else
                repeatString += "Weekday";
            
            repeatString += ", ";
            weekdaySelected = true;
        }
        else
        {
            if(selectedDays & MON_SELECTION)
            {
                if(localized)
                    repeatString += labels.monday;
                else
                    repeatString += "Monday";
                
                repeatString += ", ";
                weekdaySelected = true;
            }
            if(selectedDays & TUE_SELECTION)
            {
                if(localized)
                    repeatString += labels.tuesday;
                else
                    repeatString += "Tuesday";
                
                repeatString += ", ";
                weekdaySelected = true;
            }
            if(selectedDays & WED_SELECTION)
            {
                if(localized)
                    repeatString += labels.wednesday;
                else
                    repeatString += "Wednesday";
                
                repeatString += ", ";
                weekdaySelected = true;
            }
            if(selectedDays & THU_SELECTION)
            {
                if(localized)
                    repeatString += labels.thursday;
                else
                    repeatString += "Thursday";
                
                repeatString += ", ";
                weekdaySelected = true;
            }
            if(selectedDays & FRI_SELECTION)
            {
                if(localized)
                    repeatString += labels.friday;
                else
                    repeatString += "Friday";
                
                repeatString += ", ";
                weekdaySelected = true;
            }
        }

        if( ( (selectedDays & WEEKEND_SELECTION) == WEEKEND_SELECTION) && (!weekdaySelected) )
        {
            if(localized)
                repeatString += labels.weekend;
            else
                repeatString += "Weekend";
            repeatString += ", ";
        }
        else
        {
            if(selectedDays & SAT_SELECTION)
            {
                if(localized)
                    repeatString += labels.saturday;
                else
                    repeatString += "Saturday";
                
                repeatString += ", ";
            }

            if(selectedDays & SUN_SELECTION)
            {
                if(localized)
                    repeatString += labels.sunday;
                else
                    repeatString += "Sunday";
                repeatString += ", ";
            }
        }
        if(repeatString.toLowerCase() == "every ")
        {
            if(localized)
                return subStrings.unknown;
        }
        else
        {
            //take off the tailing comma space
            if(repeatString.length > 1)
            {
                var lastChar = repeatString.substring(repeatString.length - 2);

                if(lastChar == ", ")
                {
                    repeatString = repeatString.substring(0, repeatString.length - 2);
                }
            }
        }
    }
    return repeatString;
    
}

function getSelectedDaysFromAdvancedString(advancedString)
{
    advancedString = advancedString.toLowerCase();
    
    var selectedDays = 0;

    if(advancedString.indexOf("monday") >= 0)
        selectedDays |= MON_SELECTION;
    if(advancedString.indexOf("mon") >= 0)
        selectedDays |= MON_SELECTION;

    if(advancedString.indexOf("tuesday") >= 0)
        selectedDays |= TUE_SELECTION;
    if(advancedString.indexOf("tue") >= 0)
        selectedDays |= TUE_SELECTION;
    if(advancedString.indexOf("tues") >= 0)
        selectedDays |= TUE_SELECTION;

    if(advancedString.indexOf("wednesday") >= 0)
        selectedDays |= WED_SELECTION;
    if(advancedString.indexOf("wed") >= 0)
        selectedDays |= WED_SELECTION;
    if(advancedString.indexOf("wensday") >= 0)
        selectedDays |= WED_SELECTION;

    if(advancedString.indexOf("thursday") >= 0)
        selectedDays |= THU_SELECTION;
    if(advancedString.indexOf("thu") >= 0)
        selectedDays |= THU_SELECTION;
    if(advancedString.indexOf("thurs") >= 0)
        selectedDays |= THU_SELECTION;

    if(advancedString.indexOf("friday") >= 0)
        selectedDays |= FRI_SELECTION;
    if(advancedString.indexOf("fri") >= 0)
        selectedDays |= FRI_SELECTION;
    if(advancedString.indexOf("fryday") >= 0)
        selectedDays |= FRI_SELECTION;

    if(advancedString.indexOf("saturday") >= 0)
        selectedDays |= SAT_SELECTION;
    if(advancedString.indexOf("sat") >= 0)
        selectedDays |= SAT_SELECTION;

    if(advancedString.indexOf("sunday") >= 0)
        selectedDays |= SUN_SELECTION;
    if(advancedString.indexOf("sun") >= 0)
        selectedDays |= SUN_SELECTION;

    if(advancedString.indexOf("weekday") >= 0)
        selectedDays |= WEEKDAY_SELECTION;

    if(advancedString.indexOf("weekend") >= 0)
        selectedDays |= WEEKEND_SELECTION;

    if(advancedString.indexOf("every day") >= 0)
        selectedDays |= (WEEKEND_SELECTION | WEEKDAY_SELECTION);
    
    return selectedDays;
}


function advancedRecurrenceTypeForString(string)
{
    if(string == null || string.length == 0)
    {
       return ADVANCED_RECURRENCE_UNKNOWN;
    }

    var components = string.split(/\s+/);

    if(components.length == 0)
    {
       return ADVANCED_RECURRENCE_UNKNOWN;
    }
    var firstWord = components[0].toLowerCase();

    if(firstWord == "every")
    {
        if(components.length < 2)
            return ADVANCED_RECURRENCE_UNKNOWN;

        var secondWord = components[1].toLowerCase();
        if(secondWord == "0")
            return ADVANCED_RECURRENCE_UNKNOWN;

        if(!isNaN(parseInt(secondWord, 10)))
        {
            return ADVANCED_RECURRENCE_EVERYXDAYSWEEKSMONTHS;
        }

        if(secondWord.length > 0)
        {
            var char = secondWord.substring(0, 1);
            if(isAlphabet(char) == false)
            {
                return ADVANCED_RECURRENCE_UNKNOWN;
            }
        }
        return ADVANCED_RECURRENCE_EVERYMONTUEETC;

    }

    if(firstWord.toLowerCase() == "on" || firstWord.toLowerCase() == "the")
    {
       return ADVANCED_RECURRENCE_THEXOFEACHMONTH;
    }

    return ADVANCED_RECURRENCE_UNKNOWN;

}


var currentNotificationToDisplay = null;
var currentNotificationTimer = null;

var lastDeliveredNotificationId = null;
var lastDeliveredNotificationTriggerDate = null;

function loadNextNotificationForCurrentUser()
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
                    if(response.notification)
                    {
                        var notification = response.notification;
                        if(notification.triggerdate)
                        {
                            currentNotificationToDisplay = notification;
                            var currentTimestamp = new Date().getTime();
                            var triggerTimeInMilliseconds = notification.triggerdate * 1000;
                            var secondsToTrigger = triggerTimeInMilliseconds - currentTimestamp;

                            //SetTimeout will break and trigger immediately for anything larger than the max
                            //32 bit int, so if it's larger than that, just don't schedule it hopefully the user
                            //won't be sitting on the web page for more than 24 days without doing anything
                            if(secondsToTrigger > 2147483647)
                            {
                                currentNotificationToDisplay = null;
                                currentNotificationTimer = null;
                            }
                            else
                            {
                                currentNotificationTimer = setTimeout(displayCurrentNotification, secondsToTrigger);
                            }
                        }
                    }
                    else
                    {
                        currentNotificationToDisplay = null;
                        currentNotificationTimer = null;
                    }
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
                        displayGlobalErrorMessage(labels.unable_to_get_next_notification_from_server);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.error_from_server + ' ' + e);
            }
            
		}
	}
    
    if(currentNotificationTimer)
        clearTimeout(currentNotificationTimer);
        
    currentNotificationTimer = null;
    currentNotificationToDisplay = null;
	
	var params = "method=getNextNotificationForUser";
    if(lastDeliveredNotificationTriggerDate)
        params += "&triggertime=" + lastDeliveredNotificationTriggerDate;
    if(lastDeliveredNotificationId)
        params += "&lastnotification=" + lastDeliveredNotificationId;
    
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function displayCurrentNotification()
{
    if(currentNotificationToDisplay)
    {
        lastDeliveredNotificationId = currentNotificationToDisplay.notificationid;
        lastDeliveredNotificationTriggerDate = currentNotificationToDisplay.triggerdate;
        
        if(currentNotificationToDisplay.taskid)
        {
            var taskid = currentNotificationToDisplay.taskid;
            var triggerdate = currentNotificationToDisplay.triggerdate;
            var soundName = '';
            soundName = currentNotificationToDisplay.sound_name;
            
            //Go get the task from the server to display in the notification
            //getTaskForTaskId
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
                            if(response.task)
                            {
                                var task = response.task;
                                if(!task.completiondate || task.completiondate == 0)
                                {
                                    if(!task.deleted || task.deleted == 0)
                                    {
                                    	var alertHTML = '';
                                    	var onClickFunction = '';
                                    	var taskNameLabel = alertStrings.unknownTask;
                                    	var dueDateLabel = alertStrings.noDueDate;
                                    	var soundFileUrl = '';
                                    	
                                    	var now = new Date();
                                    	now = now.getTime();
                                    	                                   	
                                    	//prep labels
                                    	if(!task.parentid)
                                            onClickFunction = 'showTask(null, \'' + task.taskid + '\', null, true)';
                                        else
                                            onClickFunction = 'showTask(null, \'' + task.taskid + '\', \'' + task.parentid + '\', true)';

                                    	if(task.name)
                                            taskNameLabel = task.name;

                                        if(task.duedate)
                                        {
                                            var showTime = false;
                                            if(task.duedatehastime)
                                                showTime = true;
                                                
                                            dueDateLabel = displayHumanReadableDate(task.duedate, showTime);
                                        }
                                        
                                        //prep sound
                                        switch (soundName)
                                        {
                                        	case "bells":
                                        		soundFileUrlMp3 = 'https://s3.amazonaws.com/static.plunkboard.com/audio/alerts/Bells.mp3';
                                        		soundFileUrlOgg = 'https://s3.amazonaws.com/static.plunkboard.com/audio/alerts/Bells.ogg';
                                        		break;
                                        	case "data":
                                        		soundFileUrlMp3 = 'https://s3.amazonaws.com/static.plunkboard.com/audio/alerts/Data.mp3';
                                        		soundFileUrlOgg = 'https://s3.amazonaws.com/static.plunkboard.com/audio/alerts/Data.ogg';
                                        		break;
                                        	case "morse":
                                        		soundFileUrlMp3 = 'https://s3.amazonaws.com/static.plunkboard.com/audio/alerts/Morse.mp3';
                                        		soundFileUrlOgg = 'https://s3.amazonaws.com/static.plunkboard.com/audio/alerts/Morse.ogg';
                                        		break;
                                        	case "flute":
                                        		soundFileUrlMp3 = 'https://s3.amazonaws.com/static.plunkboard.com/audio/alerts/Flute.mp3';
                                        		soundFileUrlOgg = 'https://s3.amazonaws.com/static.plunkboard.com/audio/alerts/Flute.ogg';
                                        		break;
                                        	default:
                                        		soundName = '';
                                        		break;
                                        }
                                    	
                                    	//build alert HTML
                                    	alertHTML += '<div id="task_alert_' + now + '" class="single_task_alert" >';
                                    	alertHTML += '	<img class="alert_dismiss_btn" src="https://s3.amazonaws.com/static.plunkboard.com/images/task/dismiss_alert_icon.png" onclick="dismissTaskAlert(\'task_alert_' + now + '\')" />';
                                    	alertHTML += ' 	<span class="alert_info_wrapper" onclick="' + onClickFunction + '">';
                                    	alertHTML += '		<span class="alert_task_name">' + taskNameLabel + '</span>';
                                    	alertHTML += '		<span class="alert_task_due_date">' + dueDateLabel + '</span>';
                                    	alertHTML += '	</span>';
                                    	alertHTML += '</div>';
                                    	
                                    	//display all alerts
                                    	var alertContainer = document.getElementById('alertsContainer');
                                        alertContainer.innerHTML = alertHTML + alertContainer.innerHTML;
                                        alertContainer.style.display = "block";
                                        
                                        if (soundName != '')
                                        	document.getElementById('alertSoundPlayer').innerHTML = '<audio autoplay="autoplay"> <source src="' + soundFileUrlMp3 + '" type="audio/mpeg" /><source src="' + soundFileUrlOgg + '" type="audio/ogg" /></audio>';
                                      		//document.getElementById("alertSoundPlayer").innerHTML= '<embed src="' + soundFileUrl + '" hidden="true" autostart="true" loop="true" />';
                                    }
                                }
                            }
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
                                displayGlobalErrorMessage(labels.unable_to_get_next_task_for_notification);
                            }
                        }
                    }
                    catch(e)
                    {
                         displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                    }
                }
            }
            var params = "method=getTaskForTaskId&taskId=" + taskid;
            ajaxRequest.open("POST", ".", true);
            
            //Send the proper header information along with the request
            ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajaxRequest.send(params);
        }
    
    }
    
    loadNextNotificationForCurrentUser();
}

function showTask(taskitoid, taskid, parentid, reloadIfNotFound)
{
    if(taskitoid == null && taskid == null)
    {
        taskitoIdToHighlight = null;
        taskIdToHighlight = null;
        parentIdToHighlight = null;
        
        return;
    }
    var taskOrParentFound = false;
    
    if(taskitoid != null)
    {
        var taskitoContainer = document.getElementById(taskitoid);
        if(taskitoContainer)
        {
            scrollToElement(taskitoContainer);
            selectTask(taskitoid);
            taskitoIdToHighlight = null;
            taskIdToHighlight = null;
            parentIdToHighlight = null;
            taskOrParentFound = true;
        }
        else
        {
            if(taskid != null)
            {
                var taskContainer = document.getElementById(taskid);
                if(taskContainer)
                {
                    taskitoIdToHighlight = taskitoid; //Global variable will cause the subtask to be highlighted after it loads
                    taskIdToHighlight = null;
                    parentIdToHighlight = null;
                    var subtasksWrapper = document.getElementById("subtasks_wrapper_" + taskid);
                    if(subtasksWrapper)
                    {
                        subtasksWrapper.style.display = "none";
                        toggleTaskSubtasksDisplay(null, taskid);
                        taskOrParentFound = true;
                    }

                }
                else
                {
                    if(parentid != null)
                    {
                        var parentContainer = document.getElementById(parentid);
                        if(parentContainer)
                        {
                            taskitoIdToHighlight = taskitoid; //Global variable will cause the subtask to be highlighted after it loads
                            taskIdToHighlight = taskid; 
                            parentIdToHighlight = null;
                            var subtasksWrapper = document.getElementById("subtasks_wrapper_" + parentid);
                            if(subtasksWrapper)
                            {
                                subtasksWrapper.style.display = "none";
                                toggleTaskSubtasksDisplay(null, parentid);
                                taskOrParentFound = true;
                            }
                        }

                    }
                }
            }
        }
    }
    else
    {
        var taskContainer = document.getElementById(taskid);
        if(taskContainer)
        {
            scrollToElement(taskContainer);
            selectTask(taskid);
            taskitoIdToHighlight = null;
            taskIdToHighlight = null;
            parentIdToHighlight = null;
            taskOrParentFound = true;
        }
        else
        {
            if(parentid != null)
            {
                var parentContainer = document.getElementById(parentid);
                if(parentContainer)
                {
                    taskitoIdToHighlight = null;
                    taskIdToHighlight = taskid; //Global variable will cause the subtask to be highlighted after it loads
                    parentIdToHighlight = null;
                    var subtasksWrapper = document.getElementById("subtasks_wrapper_" + parentid);
                    if(subtasksWrapper)
                    {
                        subtasksWrapper.style.display = "none";
                        toggleTaskSubtasksDisplay(null, parentid);
                        taskOrParentFound = true;
                    }
                    
                }
            }
        }
    }
    
    if(taskOrParentFound == false && reloadIfNotFound)
    {
        var redirect = "?showtask=";
        if(taskitoid != null)
            redirect += taskitoid;
        else
            redirect += taskid;
        
        window.location = redirect;
    }
    
    return;
};

function dismissTaskAlert(taskAlertId)
{
	var alert = document.getElementById(taskAlertId);
	
	alert.parentNode.removeChild(alert);

};
function updateNotificationSetting(notificationType, value, listid)
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
                var response = JSON.parse(ajaxRequest.responseText);
				
				if (response.success)
				{
                    hideModalContainer();
				}
				else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                            history.go(0);
                        else
                            displayGlobalErrorMessage(response.error);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.failed_to_update_notification_settings);
                    }
                }
                
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
    
    var set = 0;
    if(value)
        set = 1;
    
	var params = 'method=updateListSettings&listid=' + listid;
    
    if(notificationType == 'assigned_only')
        params += '&notify_assigned=' + set;
    else
        params += '&notifications[' + notificationType + ']=' + set;
   
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function updateDefaultNotificationSetting(notificationType, value)
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
                var response = JSON.parse(ajaxRequest.responseText);
				
				if (response.success)
				{
                    hideModalContainer();
				}
				else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                            history.go(0);
                        else
                            displayGlobalErrorMessage(response.error);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.failed_to_update_notification_settings);
                    }
                }
                
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
    
    var set = 0;
    if(value)
        set = 1;
    
	var params = 'method=updateUserSettings&' + notificationType + '_email_notifications=' + set;
    
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function confirmApplyToAll()
{
    var body = labels.apply_default_settings_to_all_of_your;
    var header = labels.apply_changes_q;
    var footer = '<div class="button" id="apply_to_all_cancel_button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    footer += '<div class="button" id="apply_to_all_button" onclick="applyDefaultNotificationSettingsToExistingLists()">' + labels.apply + '</div>';
    
    displayModalContainer(body, header, footer);
}

function applyDefaultNotificationSettingsToExistingLists()
{
    document.getElementById('modal_overlay').setAttribute('onclick', '');
    document.getElementById('apply_to_all_cancel_button').setAttribute('class', 'button disabled');
    document.getElementById('apply_to_all_cancel_button').setAttribute('onclick', '');
    
    document.getElementById('apply_to_all_button').innerHTML += ' <div class="progress_indicator" style="display:inline-block"></div>';
    document.getElementById('apply_to_all_button').setAttribute('onclick', '');
    
    
    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;
    
	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            hideModalContainer();
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
				
				if (response.success)
				{
                    history.go(0);
				}
				else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                            history.go(0);
                        else
                            displayGlobalErrorMessage(response.error);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.failed_to_update_notification_settings);
                    }
                }
                
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
    
    
	var params = 'method=applyDefaultNotificationSettingsToAllLists';
    
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}





function loadCurrentSystemNotification()
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
                
                if(response.success)
                {
					if(response.notification)
                    {
                        setHtmlForSystemNotification(response.notification);
                    }
                    else
                    {
                        document.getElementById('current_notification_container').innerHTML = 'No active system notification to display.';
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
                        if(response.error)
                            displayGlobalErrorMessage(response.error);
                        else
                            displayGlobalErrorMessage("Unable to load current system notification");
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage("Error: " + e);
            }
		}
	}
    
	var params = 'method=getCurrentSystemNotification';
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function setHtmlForSystemNotification(notification)
{
    var html = '';
    if(notification.notificationid && notification.message && notification.timestamp)
    {
        html = '<span style="font-weight:bold;">Current Notification:</span> ' + notification.message + ' ';
        if(notification.learn_more_url)
            html += '<a href="' + notification.learn_more_url + '" style="text-decoration:underline;">' + notification.learn_more_url + '</a>';
        html += '<div class="button" onclick="confirmRemoveSystemNotification(\'' + notification.notificationid + '\')">Remove</div>';
        html += '<div style="color:gray; margin-left:130px;">(Posted: ' + displayHumanReadableDate(notification.timestamp, true, true) + ')</div>';
        
    }
    else
    {
        html = 'Current notification found but missing data';
    }

     document.getElementById('current_notification_container').innerHTML = html;
};

function getMessageFromTextArea()
{
    var textarea = document.getElementById('notification_message_textarea');
    var string = textarea.value;
    
    string = trim(string);
    
    return string;
};

function getLearnMoreUrlFromTextInput()
{
    var textInput = document.getElementById('notification_url_textinput');
    var string = textInput.value;
    string = trim(string);
    return string;
}

function updateNotificationButtonEnablement()
{
    var button = document.getElementById('notification_button');
    var message = getMessageFromTextArea();
    
    if(message.length == 0)
    {
        button.setAttribute('class', 'button disabled');
        button.setAttribute('onclick', '');
    }
    else
    {
        button.setAttribute('class', 'button');
        button.setAttribute('onclick', 'confirmPostSystemNotification()');
    }
};

function confirmPostSystemNotification()
{
    var header = 'Post Notification?';
    var body = 'This notification will be visible to all Todo Cloud users and will replace any current system notification.';
    
    var learnMore = getLearnMoreUrlFromTextInput();
    if(learnMore.length == 0)
    {
        body += '<div style="font-style:italic;margin-top:10px;">You have not specified a "Learn More" link for this message.</div>';
    }
    
    var footer = '<div class="button" onclick="hideModalContainer()">Cancel</div>';
    footer += '<div class="button" onclick="postSystemNotification()">Post</div>';
    
    displayModalContainer(body, header, footer);
};

function postSystemNotification()
{
    var message = getMessageFromTextArea();
    if(message.length == 0)
        return;
    
    var url = getLearnMoreUrlFromTextInput();

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
                
                if(response.success && response.notification)
                {
                    setHtmlForSystemNotification(response.notification);
                    document.getElementById('notification_message_textarea').value = '';
                    document.getElementById('notification_url_textinput').value = '';
                    updateNotificationButtonEnablement();
                    hideModalContainer();
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
                            displayGlobalErrorMessage(response.error);
                        else
                            displayGlobalErrorMessage("Unable to add system notification");
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage("Error: " + e);
            }
		}
	}
    
	var params = 'method=addSystemNotification&message=' + encodeURIComponent(message);
    if(url.length > 0)
        params += '&url=' + encodeURIComponent(url);
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);    
};

function confirmRemoveSystemNotification(notificationid)
{
    var header = 'Remove Notification?';
    var body = 'This notification will no longer be visible to any Todo Cloud users.';
    var footer = '<div class="button" onclick="hideModalContainer()">Cancel</div>';
    footer += '<div class="button" onclick="removeSystemNotification(\'' + notificationid + '\')">Remove</div>';
    
    displayModalContainer(body, header, footer);
};


function removeSystemNotification(notificationid)
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
                
                if(response.success)
                {
                    document.getElementById('current_notification_container').innerHTML = 'No active system notification to display.';
                    hideModalContainer();
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
                            displayGlobalErrorMessage(response.error);
                        else
                            displayGlobalErrorMessage("Unable to remove system notification");
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage("Error: " + e);
            }
		}
	}
    
	var params = 'method=removeSystemNotification&notificationid=' + notificationid;
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);    
}





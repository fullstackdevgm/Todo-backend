

function displayEditListFlyout(event, listid, listName, listColor, listDescription, isInbox)
{
	if (event)
		stopEventPropogation(event);

    var group_option_element = jQuery('#control_edit_icon_' + listid).parents('.group_option');
    var position_element = group_option_element.position();
    var flyout = document.getElementById('list_edit_flyout_' + listid);
    
    var html = '';
    
    if(isInbox == false)
    {
        html += '<div class="control_edit_option control_edit_option_bordered" id="sharing_option">'+labels.sharing+'</div>';
        html += '<div class="control_edit_option" id="rename_option">'+labels.rename+'</div>';
    }
    html += '<div class="control_edit_option" id="color_option">'+labels.color+'</div>';
    var descriptionClass = "control_edit_option";
    if(isInbox == false)
        descriptionClass = "control_edit_option control_edit_option_bordered";
    //html += '<div class="' + descriptionClass + '" id="description_option">Description</div>';
    
    if(isInbox == false)
    {
        html += '<div class="control_edit_option control_edit_option_bordered" id="settings_option">'+labels.settings+'</div>';
        html += '<div class="control_edit_option" id="delete_option">'+labels.delete+'</div>';
    }
    flyout.innerHTML = html;
    flyout.style.display = "block";
    flyout.style.top = position_element.top + 25 + "px";
    flyout.style.left = "13px";

    var background = document.getElementById('list_edit_background_' + listid);
    background.style.height = "100%";
    background.style.width = "100%";
    
    //Bind all the onlick events with closures!
    var el;
    var event;
    listName = htmlEntities(listName);
    if(isInbox == false)
    {
        el = document.getElementById('sharing_option');
        event = (function(li,ln){return function(){displaySharingModal(li,ln, false);}}(listid, listName));
        el.bindEvent('click', event, false);
        
        el = document.getElementById('rename_option');
        event = (function(li,ln){return function(){displayRenameListModal(li,ln);}}(listid, listName));
        el.bindEvent('click', event, false);
    }
    
    el = document.getElementById('color_option');
    event = (function(li,ln){return function(){displayListColorModal(li,ln);}}(listid, listName));
    el.bindEvent('click', event, false);
    
    /*el = document.getElementById('description_option');
    event = (function(li,ln,ld){return function(){displayListDescriptionModal(li,ln,ld);}}(listid, listName, listDescription));
    el.bindEvent('click', event, false);*/
 
    if(isInbox == false)
    {
        el = document.getElementById('settings_option');
        event = (function(li,ln){return function(){loadUserListSettings(li,ln);}}(listid, listName));
        el.bindEvent('click', event, false);
        
        el = document.getElementById('delete_option');
        event = (function(li,ln){return function(){getDeleteListInfo(li,ln);}}(listid, listName));
        el.bindEvent('click', event, false);
    }
    
}
function hideListEditFlyout(event,listid)
{
    document.getElementById('list_edit_flyout_' + listid).style.display = "none";
    document.getElementById('list_edit_flyout_' + listid).innerHTML = '';
       
    var background = document.getElementById('list_edit_background_' + listid);
    background.style.height = "0px";
    background.style.width = "0px";
}

/**** Rename functions *****/
function displayRenameListModal(listid, listName)
{
    hideListEditFlyout(null, listid);

    var header = labels.rename + ' "' + listName + '"';
    var body = '<div>';
    body += '<input type="text" id="rename_list_text_field" class="centered_text_field" value="' + listName + '" />';
    body += '</div>';
    
    var footer = '';
    
    footer += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    footer += '<div class="button disabled" onclick="" id="save_list_name_button">' + labels.save + '</div>';
    
    
    displayModalContainer(body, header, footer);
    var el = document.getElementById('rename_list_text_field');
    el.focus();
    el.select();
    var event = (function(li,ln){return function(event){validateListName(event,li,ln);}}(listid, listName));
    el.bindEvent('keyup', event, false);

}
function validateListName(event, listid, oldListName)
{
    if(event.keyCode == 27) //escape button
    {
        hideModalContainer();
        return;
    }

    var listName = document.getElementById('rename_list_text_field').value;
    var trimmedName = trim(listName);
    var button = document.getElementById('save_list_name_button');
    
    if(trimmedName.length == 0 || trimmedName.length > 36 || trimmedName == trim(oldListName))
    {
        button.setAttribute("class", "button disabled");
        button.setAttribute("onclick", "");
    }
    else
    {
        button.setAttribute("class", "button");
        button.setAttribute("onclick", "saveListName('" + listid + "')");
        if(event.keyCode == 13)
            saveListName(listid);
    }
}

function saveListName(listid)
{
    var listName = document.getElementById('rename_list_text_field').value;
    listName = trim(listName);
    
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
                    window.location = ".";
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in
                        history.go(0);
                    }
                    else
                    {
                        if(response.error)
                            displayGlobalErrorMessage(response.error);
                        else
                            displayGlobalErrorMessage(labels.unable_to_save_list_name);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	var params = "method=updateList&listname=" + encodeURIComponent(listName) + "&listid=" + listid;
	ajaxRequest.open("POST", "." , true);
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
	
};

/***** Description functions *****/
function displayListDescriptionModal(listid, listName, listDescription)
{
    hideListEditFlyout(null, listid);

    var header = labels.description_of + ' "' + listName + '"';
    var body = '<div>';
    body += '<textarea id="list_description_text_area" class="centered_text_field" value="' + listDescription + '" >' + listDescription + '</textarea>';
    body += '</div>';
    
    var footer = '';
    
    footer += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    footer += '<div class="button disabled" onclick="" id="save_list_description_button">' + labels.save + '</div>';
    
    
    displayModalContainer(body, header, footer);
    var el = document.getElementById('list_description_text_area');
    el.focus();
    el.select();
    var event = (function(li,ld){return function(event){validateListDescription(event,li,ld);}}(listid, listDescription));
    el.bindEvent('keyup', event, false);
}
function validateListDescription(event, listid, oldListDescription)
{
    if(event.keyCode == 27) //escape button
    {
        hideModalContainer();
        return;
    }

    var description = document.getElementById('list_description_text_area').value;
    var trimmedName = trim(description);
    var button = document.getElementById('save_list_description_button');
    
    if(trimmedName.length == 0 || trimmedName.length > 512 || trimmedName == trim(oldListDescription))
    {
        button.setAttribute("class", "button disabled");
        button.setAttribute("onclick", "");
    }
    else
    {
        button.setAttribute("class", "button");
        button.setAttribute("onclick", "saveListDescription('" + listid + "')");
    }
    
}

function saveListDescription(listid)
{
    var description = document.getElementById('list_description_text_area').value;

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
                   	window.location = ".";
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
                        if(response.error)
                            displayGlobalErrorMessage(response.error);
                        else
                            displayGlobalErrorMessage(labels.unable_to_save_description + '.');
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_save_description + ': ' + e);
            }
		}
	}
	
	var params = "method=updateList&description=" + encodeURIComponent(description) + "&listid=" + listid;
	ajaxRequest.open("POST", "." , true);
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
	
};

/**** List Color functions *****/

function displayListColorModal(listid, listName)
{
    hideListEditFlyout(null, listid);

    var header = labels.select_a_color_for + ' "' + listName + '"';
    
    var colors = [["0,0,0","255,210,125","214,249,127","120,251,214","119,215,254", "213,136,253"],["33,33,33","255,125,122","255,250,127","121,248,126","129,253,255","120,134,253"],["66,66,66","255,145,27","146,247,42","0,248,150","0,154,253","145,71,252"],["95,94,95","255,35,4","255,248,45","0,246,40","0,253,255","0,68,252"],["121,121,121","147,81,10","72,133,11","0,142,83","0,86,145","81,36,145"],["145,145,145","147,15,4","147,142,21","0,141,18","0,145,146","0,35,145"]];
    
    var body = '';
    
    body += '<div id="colorPickerWrapper">';
    
    for(var i=0; i < colors.length; i++)
    {
        body += '<div class="colorPickerRow">';
        var subarray = colors[i];
        for(var j= 0; j < subarray.length; j++)
        {
            var color = subarray[j];
            body += '<div class="listColor" onclick="updateListColor(event, \'' + listid + '\')" style="background:rgb(' + color + ');"></div>';
        }
        
        body += '</div>';
    }
    
    body += '</div>';
                    
    var footer = '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    displayModalContainer(body, header, footer);
}

//updateListColor function
//purpose: updates the list setting for the list being viewed
function updateListColor(windowEvent, listId)
{
	var colorRGBString = windowEvent.target.style.backgroundColor;
	colorRGBString = colorRGBString.substring(4, colorRGBString.length -1);
	
	//console.log(colorRGBString);
	
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
                    window.location = ".";
				}
				else
					displayGlobalErrorMessage(labels.failed_to_update_list_color + ' ' + response.error);

            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_update_list_color + ': ' + e);
            }
		}
	}
	
	var params = 'method=updateListSettings&listid=' + listId + '&color=' + colorRGBString; 
	    
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);

};

/**** List settings functions ****/
function displayListSettingsModal(listid, listName, emailNotificationHtml, footerDiv)
{
    hideListEditFlyout(null, listid);
    
    var header = labels.settings_for + ' "' + listName + '"';
    
    var body = '';
    var footer = '';
    //take out the border for this setting, since there's only one setting right now
    body += '<div id="notifications_setting" class="setting" style="border:none;">';
    body += '<span class="setting_name" style="vertical-align:top;">' + labels.email_notifications + '</span>';
    body += '<span id="email_notification_settings">' + emailNotificationHtml + '</span>';
    body += '</div>';

    footer = '<div id="settings_footer_div">' + footerDiv + '</div>';

    displayModalContainer(body, header, footer);
}

function loadUserListSettings(listid,listName)
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
				
				if (response.success && response.notificationsettings)
				{
                    var footerDiv = '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
                    
                    var emailNotificationHtml = '';
                    var validSubscription = response.validsubscription;
                    if(validSubscription)
                    {
                        var emailVerified = response.emailverified;
                        if(emailVerified)
                        {
                            emailNotificationHtml += '<div id="notifications_config" class="setting_details">';
    //                        emailNotificationHtml += 'Receive email notifications for changes to the following items:';
                            var notificationSettings = response.notificationsettings;
                            for(var i=0; i < notificationSettings.length; i++)
                            {
                                var setting = notificationSettings[i];
                                emailNotificationHtml += '<label for="' + setting.key + '">';
                                emailNotificationHtml += setting.displayname;
                                
                                var checkedStr = '';
                                if(setting.value > 0)
                                {
                                    checkedStr = 'checked="true"';
                                }
                                
                                emailNotificationHtml += ' <input type="checkbox" id="' + setting.key + '" name="notifications" ' + checkedStr + ' />';
                                emailNotificationHtml += '</label>';

                            }
                            
                            var checkedStr = '';
                            if(response.notifyassignedsetting)
                                checkedStr = 'checked="true"';
                            emailNotificationHtml += '<div style="margin:10px 0 0 0"><input type="checkbox" id="notify_assigned_checkbox" ' + checkedStr + ' /><label for="notify_assigned_checkbox">'+labels.only_send_emails_for_tasks +' </label></div>';
                            
                            emailNotificationHtml += '</div>';
                            
                            footerDiv += '<div class="button" onclick="saveNotificationSettings(\'' + listid + '\')">' + labels.save + '</div>';
                        }
                        else
                        {
                            emailNotificationHtml += '<span class="setting_details">';
                            emailNotificationHtml += labels.this_feature_requires_verification_of_your ;
                            emailNotificationHtml += '</span>';
                            emailNotificationHtml += '<div class="button" onclick="verifyUserEmail()">' + labels.verify + '</div>';
                        }
                    }
                    else
                    {
                        emailNotificationHtml += '<span class="setting_details">';
                        emailNotificationHtml += labels.this_feature_requires;
                        emailNotificationHtml += '</span>';
                        emailNotificationHtml += '<div style="display:inline-block;"><a class="button" href="?appSettings=show&option=subscription">' + labels.go_premium + '</a></div>';
                    }

                    displayListSettingsModal(listid, listName, emailNotificationHtml, footerDiv);
            
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
                        displayGlobalErrorMessage(labels.failed_to_get_list_settings);
                    }
                }

            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_get_list_settings + ': ' + e);
            }
		}
	}
	
	var params = 'method=getListSettings&listid=' + listid;
	    
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}


function saveNotificationSettings(listid)
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
    
	var params = 'method=updateListSettings&listid=' + listid;
    
    var notifications = document.getElementsByName('notifications');
    for(var i=0; i < notifications.length; i++)
    {
        var notification = notifications[i];
        var set = 0;
        if(notification.checked)
        {
            set = 1;
        }
        params += '&notifications[' + notification.id + ']=' + set;
    }
	
    var notifyAssigned = 0;
    if(document.getElementById('notify_assigned_checkbox').checked == true)
    {
        notifyAssigned = 1;
    }
    params += '&notify_assigned=' + notifyAssigned;
    
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);   
}

/**** Sharing functions *****/
var memberPageListId = null;
function displaySharingModal(listid, listName, showListDeleteOnDone)
{
    memberPageListId = listid;
    
    hideListEditFlyout(null, listid);
    
    var header = labels.members_of + ' "' + listName + '"';
    
    var body = '';
    
    body += '<div style="height:400px; width:500px; margin:0 20px 0 20px;">';
   
    
    body += '<input type="hidden" id="member_page_listname" value="' + listName + '">';
    
    body += '<div id="share_buttons_container" class="modal_button_toolbar"></div>';
    body += '<div id="list_members_container" style="position:relative;clear:both;"></div>';
//    body += '<h3>Invitations</h3>';
    body += '<div id="list_invitations_container" style="position:relative;clear:both;"></div>';
    
    body += '</div>';
    
    body += '<div id="sharing_confirmation_dialog" class="confirmation_dialog"></div>';
    body += '<div id="sharing_confirmation_dialog_background" class="confirmation_dialog_background" onclick="hideFlyoutInModalBody(\'sharing_confirmation_dialog\',\'sharing_confirmation_dialog_background\')"></div>';
    
    var footer = '<div class="button" id="sharing_done_button" onclick="hideModalContainer();hideGlobalErrorMessage();">' + labels.cancel + '</div>';

    displayModalContainer(body, header, footer);
    
    if(showListDeleteOnDone)
    {
        var event = (function(li,ln){return function(){getDeleteListInfo(li,ln);}}(listid, listName));
        document.getElementById('sharing_done_button').bindEvent('click', event, false);
    }

    loadMembersSection();

}




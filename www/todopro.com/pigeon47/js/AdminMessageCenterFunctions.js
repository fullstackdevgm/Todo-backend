const MESSAGE_TYPE_ALL = 47; //Set displayedMessageType to this when showing all messages
const MESSAGE_TYPE_NONE = -1; //Set displayedMessageType to this when showing no messages

var displayedMessageType = MESSAGE_TYPE_NONE; //On page load we're not showing any messages
var supportedLanguageCodes = ['de','es','fr','it','ja','pt','ru','zh_CN','zh_TW'];

function setUpMessageCenterPage()
{
    //Set up the input areas for supporting multiple languages
    setupLanguageInputs();

    //Set the range of dates of the messages we want to see
    //Default to all messages in the last month
    var today = new Date();
    setMessagesEndDate(today.getTime() / 1000);
    
    today.setMonth(today.getMonth() - 1);
    setMessagesStartDate(today.getTime() / 1000);
    
    today.setMonth(today.getMonth() + 2);
    setPageExpirationDate(today.getTime() / 1000);
    
//    loadAllMessages();
//    loadAllTables();
    
}

/******** PAGE SETUP METHODS **********/
function setupLanguageInputs()
{
    var html = '';

    //Set up the input areas for supporting multiple languages
    for(var i = 0; i < supportedLanguageCodes.length; i++)
    {
        var langCode = supportedLanguageCodes[i];
        var displayName = displayNameForLanguageCode(langCode);
        
        html += '<div id="' + langCode + '_toggle_button" style="text-decoration:underline;cursor:pointer;margin-top:10px;margin-bottom:2px;" onclick="toggleLanguageInput(\'' + langCode + '\')">Add ' + displayName + '</div>';
        
        html += '<div style="display:none;" id="' + langCode + '_message_entry">';
        html += '   <div><input id="' + langCode + '_subject_textbox" type="text" placeholder="Subject..."></input></div>';
        html += '   <textarea id="' + langCode + '_message_textarea" placeholder="Html message body..." style="width:400px;margin-top:10px;"></textarea>';
        html += '</div>';
        
    }
    document.getElementById('message_languages_container').innerHTML = html;
};

function displayNameForLanguageCode(langCode)
{
    switch(langCode)
    {
        case 'de':
            return 'German';
        case 'it':
            return 'Italian';
        case 'ja':
            return 'Japanese';
        case 'pt':
            return 'Portuguese';
        case 'ru':
            return 'Russian';
        case 'zh_CN':
            return 'Simplified Chinese';
        case 'zh_TW':
            return 'Traditional Chinese';
        case 'fr':
            return 'French';
        case 'es':
            return 'Spanish';
    }
    
    return 'Unknown';
}

function toggleLanguageInput(langCode)
{
    var messageEntry = document.getElementById(langCode + '_message_entry');
    var toggleButton = document.getElementById(langCode + '_toggle_button');

    if(messageEntry.style.display == 'none')
    {
        messageEntry.style.display = 'block';
        toggleButton.innerHTML = 'Remove ' + displayNameForLanguageCode(langCode);
    }
    else
    {
        messageEntry.style.display = 'none';
        toggleButton.innerHTML = 'Add ' + displayNameForLanguageCode(langCode);
    }
};

/******** ADD MESSAGE METHODS *********/
function updateAddMessageButtonEnablement()
{
    var button = document.getElementById('add_message_button');
    var testButton = document.getElementById('test_message_button');
    var message = getMessageFromTextArea('en');
    var subject = getSubjectFromTextBox('en');
    var deviceTypes = getSelectedValuesOfType('device_type');
    var syncServiceTypes = getSelectedValuesOfType('sync_service');
    
    var type = getSelectedMessageType();
    //If this is an upgrade based message, be sure at least one app
    //id is selected
    if(type == 1)
    {
        var appIds = getSelectedValuesOfType('app_id');
        if(appIds.length == 0)
        {
            button.setAttribute('class', 'button disabled');
            button.setAttribute('onclick', '');
            
            testButton.setAttribute('class', 'button disabled');
            testButton.setAttribute('onclick', '');
            
            return;
        }
    }
    
    if(message.length == 0 || subject.length == 0 || deviceTypes.length == 0 || syncServiceTypes.length == 0)
    {
        button.setAttribute('class', 'button disabled');
        button.setAttribute('onclick', '');
        
        testButton.setAttribute('class', 'button disabled');
        testButton.setAttribute('onclick', '');
    }
    else
    {
        button.setAttribute('class', 'button');
        button.setAttribute('onclick', 'confirmPostMessage()');
        
        testButton.setAttribute('class', 'button');
        testButton.setAttribute('onclick', 'confirmTestMessage()');
    }
}

function setPageExpirationDate(unixDate)
{
    if(unixDate != 0)
    {
        //Set the hours, minutes, and seconds of the expiration date to be the current time
        //to minimize the chance that we will attempt to post two messages with the same timestamp
        var now = new Date();
        var date = new Date(unixDate * 1000);
        date.setHours(now.getHours());
        date.setMinutes(now.getMinutes());
        date.setSeconds(now.getSeconds());
        
        unixDate = date.getTime() / 1000;
    }

    var displayDate = displayHumanReadableDate(unixDate, false, true);
    document.getElementById('expiration_date_label').innerHTML = displayDate;
    document.getElementById('expiration_date_value').value = unixDate;
}

function getPageExpirationDate()
{
    return parseInt(document.getElementById('expiration_date_value').value);
}

function getSubjectFromTextBox(langCode)
{
    var textbox = document.getElementById(langCode + '_subject_textbox');
    var string = textbox.value;
    string = trim(string);
    return string;
}

function getMessageFromTextArea(langCode)
{
    var textarea = document.getElementById(langCode + '_message_textarea');
    var string = textarea.value;
    
    string = trim(string);
    
    return string;
}

function getSelectedMessageType()
{
    var el = document.getElementById('message_type_picker');
    return el.options[el.selectedIndex].value;
}

function getSelectedMessagePriority()
{
    var el = document.getElementById('message_priority_picker');
    return el.options[el.selectedIndex].value;
}

function getSelectedValuesOfType(identifier)
{
    var checkboxes = document.getElementsByName(identifier);
    
    var selectedTypes = new Array();
    
    for(var i=0; i < checkboxes.length; i++)
    {
        var checkbox = checkboxes[i];
        if(checkbox.checked)
        {
            selectedTypes.push(checkbox.value);
        }
    }
    return selectedTypes;
}

function getAccountDurationWeeks()
{
    var string = document.getElementById('account_duration_weeks').value;

    var value = 0;
    if(string.length > 0)
        value = parseInt(string);
    
    return value;
}

function getSelectedUpgradeVersions()
{
    var selectedVersions = new Array();
    
    var selectedAppIds = getSelectedValuesOfType('app_id');
    for(var i=0; i < selectedAppIds.length; i++)
    {
        var selectedAppId = selectedAppIds[i];
        var versionString = trim(document.getElementById(selectedAppId + '_version').value);
        
        //If we left the version blank on one of them, return nothing from this method
        //so we'll report an error to the user
        if(versionString.length == 0)
        {
            return null;
        }
        selectedVersions.push(selectedAppId + '_' + versionString);
    }
    
    return selectedVersions;
}

function updateVisibleDetailsForMessageType()
{
    var value = getSelectedMessageType();
    
    if(value == 2) //account duration based message
    {
        document.getElementById('account_based_message_details').style.display = 'block';
        document.getElementById('sync_service_options').style.display = 'none';
    }
    else
    {
        document.getElementById('account_based_message_details').style.display = 'none';
        document.getElementById('sync_service_options').style.display = 'inline-block';
    }
    
    if(value == 1) //upgrade based message
    {
        document.getElementById('upgrade_based_message_details').style.display = 'block';
    }
    else
    {
        document.getElementById('upgrade_based_message_details').style.display = 'none';
    }
}


function confirmPostMessage()
{
    var header = 'Post Message?';
    var footer = '<div class="button" id="add_message_cancel_button" onclick="hideModalContainer()">Cancel</div>';
    footer += '<div class="button" id="add_message_confirm_button" onclick="postMessageToServer()">Post</div>';
    
    var messageSubject = getSubjectFromTextBox('en');
    var messageText = getMessageFromTextArea('en');
    
    var body = '<div style="margin-bottom:5px;font-weight:bold;">' + messageSubject + '</div>';
    body += '<div>' + messageText + '</div>';
    
    
    displayModalContainer(body, header, footer);
}

function confirmTestMessage()
{
    var header = 'Test Message?';
    var footer = '<div class="button" id="test_message_cancel_button" onclick="hideModalContainer()">Cancel</div>';
    footer += '<div class="button" id="test_message_confirm_button" onclick="postTestMessage()">Test</div>';
    var body = 'This message will only be sent to you and will not be visible in any other users\' message centers.<br/>';
    body += 'The message will be sent as a system alert and all filters will be ignored.';
    
    displayModalContainer(body, header, footer);
}

function postMessageToServer()
{
    var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

    var messageText = getMessageFromTextArea('en');
    if(messageText.length == 0)
        return false;
    
    var subject = getSubjectFromTextBox('en');
    if(subject.length == 0)
        return false;
    
    var deviceTypes = getSelectedValuesOfType('device_type');
    if(deviceTypes.length == 0)
        return false;
    
    var syncServiceTypes = getSelectedValuesOfType('sync_service');
    if(syncServiceTypes.length == 0)
        return false;

    var selectedType = getSelectedMessageType();

    //If this is an upgrade-based message, make sure we have at least one
    //upgrade version selected
    var upgradeVersions = null;
    if(selectedType == 1)
    {
        upgradeVersions = getSelectedUpgradeVersions();
        if(upgradeVersions == null)
        {
            alert('Missing version for at least one selected app');
            return;
        }
        else if(upgradeVersions.length == 0)
        {
            alert('Please select at least one app and version');
            return;
        }
    }

    disableOnClickDismissalOfModalContainer();
    
    var cancelButton = document.getElementById('add_message_cancel_button');
    var confirmButton = document.getElementById('add_message_confirm_button');
    
    cancelButton.setAttribute('onclick', '');
    confirmButton.setAttribute('onclick', '');
    
    cancelButton.setAttribute('class', 'button disabled');
    confirmButton.setAttribute('class', 'button disabled');
    
    confirmButton.innerHTML = '<span class="progress_indicator" style="display:inline-block"></span>';
    
    ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                hideModalContainer();
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    if(response.message)
                    {
                        //add the message to the messages container if we're showing messages
                        //of this type
                        if(displayedMessageType == MESSAGE_TYPE_ALL || displayedMessageType == selectedType)
                        {
                            var html = htmlForMessage(response.message);
                            
                            var messagesList = document.getElementById('messages_container');
                            
                            //remove the "no messages" element if it exists
                            var noMessages = document.getElementById('no_messages_message');
                            if(noMessages)
                            {
                                noMessages.parentNode.removeChild(noMessages);
                            }
                            
                            messagesList.innerHTML = html + messagesList.innerHTML;
                        }
                    }
                    
                    var header = 'Success!';
                    var body = 'Your message was successfully posted';
                    var footer = '<div class="button" onclick="hideModalContainer()">OK</div>';
                    displayModalContainer(body, header, footer);
                
                    //Clear out the fields for adding a message
                    document.getElementById('en_message_textarea').value = '';
                    document.getElementById('en_subject_textbox').value = '';
                    
                    for(var i=0; i < supportedLanguageCodes.length; i++)
                    {
                        var langCode = supportedLanguageCodes[i];
                        document.getElementById(langCode + '_message_textarea').value = '';
                        document.getElementById(langCode + '_subject_textbox').value = '';
                    }
                    updateAddMessageButtonEnablement();
                
                    //Reset the page expiration date so we don't try to set two messages with the same date
                    var today = new Date();
                    today.setMonth(today.getMonth() + 1);
                    setPageExpirationDate(today.getTime() / 1000);
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
                            alert(response.error);
                        else
                            alert("Error adding message");
                    }
                }
            }
            catch(e)
            {
                alert("Error adding message: " + e);
            }
		}
	}
    
    var expirationDate = getPageExpirationDate();
    var selectedPriority = getSelectedMessagePriority();
   
	var params = 'method=addMessageCenterMessage&message[en]=' + encodeURIComponent(messageText) + '&subject[en]=' + encodeURIComponent(subject) + '&type=' + selectedType + '&priority=' + selectedPriority + '&expiration_date=' + expirationDate;
    
    //If all the device types are checked, don't send anything to the server
    //(then the server will know not to filter on device type)
    var checkboxCount = document.getElementsByName('device_type').length;
    if(checkboxCount > deviceTypes.length)
    {
        for(var i=0; i < deviceTypes.length; i++)
        {
            var val = deviceTypes[i];
            params += '&device_types[]=' + val;
        }
    }
    
    //If this is an upgrade-based message, send the selected app ids and versions
    if(upgradeVersions != null)
    {
        for(var i=0; i < upgradeVersions.length; i++)
        {
            var val = upgradeVersions[i];
            params += '&version_keys[]=' + val;
        }
    }
    
    //If this is an account-based message, send the number of weeks up to the server,
    //and don't bother sending the sync service filters because this type of message
    //is exclusive to Todo Cloud users
    if(selectedType == 2)
    {
        var weeks = getAccountDurationWeeks();
        params += '&account_duration=' + weeks;
    }
    else
    {
        //If all the sync services are checked, don't send anything to the server
        //(then the server will know not to filter on sync service)
        checkboxCount = document.getElementsByName('sync_service').length;
        if(checkboxCount > syncServiceTypes.length)
        {
            for (var i=0; i < syncServiceTypes.length; i++)
            {
                var val = syncServiceTypes[i];
                params += '&sync_services[]=' + val;
            }
        }
    }
    
    //Add message html for all other languages
    for(var i=0; i < supportedLanguageCodes.length; i++)
    {
        var langCode = supportedLanguageCodes[i];
        
        //Only add the language if it's showing and it has a non-empty subject and message
        if(document.getElementById(langCode + '_message_entry').style.display == 'block')
        {
            var m = getMessageFromTextArea(langCode);
            if(m.length == 0)
                continue;
            
            var s = getSubjectFromTextBox(langCode);
            if(s.length == 0)
                continue;
            
            params += '&message[' + langCode + ']=' + encodeURIComponent(m) + '&subject[' + langCode + ']=' + encodeURIComponent(s);
        }
    }
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);

}

function postTestMessage()
{
    var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;
    
    var messageText = getMessageFromTextArea('en');
    if(messageText.length == 0)
        return false;
    
    var subject = getSubjectFromTextBox('en');
    if(subject.length == 0)
        return false;
    
    disableOnClickDismissalOfModalContainer();
    
    var cancelButton = document.getElementById('test_message_cancel_button');
    var confirmButton = document.getElementById('test_message_confirm_button');
    
    cancelButton.setAttribute('onclick', '');
    confirmButton.setAttribute('onclick', '');
    
    cancelButton.setAttribute('class', 'button disabled');
    confirmButton.setAttribute('class', 'button disabled');
    
    confirmButton.innerHTML = '<span class="progress_indicator" style="display:inline-block"></span>';
    
    ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                hideModalContainer();
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    
                    var header = 'Success!';
                    var body = 'Your message was successfully posted';
                    var footer = '<div class="button" onclick="hideModalContainer()">OK</div>';
                    displayModalContainer(body, header, footer);
                    
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
                            alert(response.error);
                        else
                            alert("Error testing message");
                    }
                }
            }
            catch(e)
            {
                alert("Error testing message: " + e);
            }
		}
	}
    
    var selectedPriority = getSelectedMessagePriority();
	var params = 'method=testMessageCenterMessage&message[en]=' + messageText + '&subject[en]=' + subject + '&priority=' + selectedPriority;
    
    //Add message html for all other languages
    for(var i=0; i < supportedLanguageCodes.length; i++)
    {
        var langCode = supportedLanguageCodes[i];
        
        //Only add the language if it's showing and it has a non-empty subject and message
        if(document.getElementById(langCode + '_message_entry').style.display == 'block')
        {
            var m = getMessageFromTextArea(langCode);
            if(m.length == 0)
                continue;
            
            var s = getSubjectFromTextBox(langCode);
            if(s.length == 0)
                continue;
            
            params += '&message[' + langCode + ']=' + m + '&subject[' + langCode + ']=' + s;
        }
    }
    
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
    
}

/******** LOAD MESSAGES METHODS *******/
function loadAllMessages()
{
    var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;
    
    showLoadingMessagesUI();

    ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                hideLoadingMessagesUI();
                
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    var html = '';
                    var messageContainer = document.getElementById('messages_container');
                    //display the messages
                    if(response.messages && response.messages.length > 0)
                    {
                        for(var i = 0; i < response.messages.length; i++)
                        {
                            var message = response.messages[i];
                            html += htmlForMessage(message);
                        }
                        
                    }
                    else
                    {
                        html += '<div id="no_messages_message">No messages to display</div>';
                    }
                    
                    messageContainer.innerHTML = html;
                    showMessagesUIWithTitle('All Messages', true);
                    displayedMessageType = MESSAGE_TYPE_ALL;
                    
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
                            alert(response.error);
                        else
                            alert("Error loading messages");
                    }
                }
            }
            catch(e)
            {
                alert("Error loading messages: " + e);
            }
		}
	}
    
    var startDate = document.getElementById('start_date_value').value;
    var endDate = document.getElementById('end_date_value').value;


    var params = 'method=getAllMessages&start_date=' + startDate + '&end_date=' + endDate;
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);

}

function loadMessagesOfType(type, title)
{
    var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;
    
    showLoadingMessagesUI();
    
    ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                hideLoadingMessagesUI();
                
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    var html = '';
                    var messageContainer = document.getElementById('messages_container');
                    //display the messages
                    if(response.messages && response.messages.length > 0)
                    {
                        for(var i = 0; i < response.messages.length; i++)
                        {
                            var message = response.messages[i];
                            html += htmlForMessage(message);
                        }
                        
                    }
                    else
                    {
                        html += '<div id="no_messages_message">No messages to display</div>';
                    }
                    
                    messageContainer.innerHTML = html;
                    showMessagesUIWithTitle(title, false);
                    displayedMessageType = type;
                    
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
                            alert(response.error);
                        else
                            alert("Error loading messages");
                    }
                }
            }
            catch(e)
            {
                alert("Error loading messages: " + e);
            }
		}
	}

    var params = 'method=getMessagesOfType&type=' + type;
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function htmlForMessage(message)
{
    //If the message is inactive, set the background to gray
    var backgroundColor = 'rgb(256,256,256)';
    if(message.removed_from_lookup)
        backgroundColor = 'rgb(200,200,200)';

    var html = '<div class="setting" id="' + message.messageid + '" style="background:' + backgroundColor + ';">';
    
    html += '<div class="message_name">' + message.subject + '</div>';
    html += '<div class="message_body">' + message.message_html + '</div>';
    
    var postedDate = 'Unknown';
    if(message.time_posted)
        postedDate = displayHumanReadableDate(message.time_posted, false, true);
    html += '<div class="message_date" id="' + message.messageid + '_posted_date">' + postedDate + '</div>';
    
    var expirationDate = 0;
    if(message.expiration_date)
        expirationDate = message.expiration_date;
    
    var expirationString = displayHumanReadableDate(expirationDate, false, true);
    
    html += '<span id="' + message.messageid + '_date_wrap" style="display:inline-block;" class="property_wrapper">';
    html += '<div class="message_date" id="' + message.messageid + '_date_label" style="cursor:pointer;text-decoration:underline;" onclick="displayDatePicker(\'' + message.messageid + '\')">' + expirationString + '</div>';

    html += '<input type="hidden" id="' + message.messageid + '_date_value" value="' + expirationDate + '"/>';
    html += '<div id="' + message.messageid + '_date_editor" class="property_flyout datepicker_wrapper">';
    html += '   <div id="' + message.messageid + '_datepicker" class="task_datepicker"> </div>';
    html += '</div>';
    html += '</span>';


    
    html += '<div class="message_name">' + messageTypeDisplayStringForMessage(message) + '</div>';
    
    var priority = 0;
    if(message.priority)
        priority = message.priority;
    html += '<div class="message_name">' + displayStringForMessagePriority(priority) + '</div>';
    
    var deviceTypes = null;
    if(message.device_types)
        deviceTypes = message.device_types;
    html += '<div class="message_name">' + displayStringForDeviceTypes(deviceTypes) + '</div>';
    
    var syncServices = null;
    if(message.sync_services)
        syncServices = message.sync_services;
    html += '<div class="message_name">' + displayStringForSyncServices(syncServices) + '</div>';
    
    html += '</div>';
    
    return html;
}

function messageTypeDisplayStringForMessage(message)
{
    var messageType = 0;
    if(message.message_type)
        messageType = message.message_type;

    switch(messageType)
    {
        case 0:
            return 'System Alert';
        case 1:
        {
            var string = 'Upgrade Based';
            
            if(message.version_keys)
            {
                string += ' (';
                var versionKeys = message.version_keys;
                for(var i=0; i < versionKeys.length; i++)
                {
                    var key = versionKeys[i];
                    if(i > 0)
                        string += ', ';
                    string += key;
                }
                string += ')';
            }
            
            return string;
        }
        case 2:
        {
            var string = 'Account Duration Based';
        
            var numWeeks = 0;
            if(message.account_duration_weeks)
            {
                numWeeks = message.account_duration_weeks;
            }
            string += ' (' + numWeeks + ' weeks)';
            
            return string;
        }
        default:
            break;
    }
    
    return 'Unknown';
}

function displayStringForMessagePriority(priority)
{
    switch(priority)
    {
        case 0:
            return 'None';
        case 1:
            return 'Low';
        case 3:
            return 'Medium';
        case 5:
            return 'Important';
        case 47:
            return 'Urgent';
        default:
            break;
    }
    
    return 'Unknown';
    
}

function displayStringForDeviceTypes(deviceTypes)
{
    if(deviceTypes == null || deviceTypes.length == document.getElementsByName('device_type').length)
        return 'All';
    
    if(deviceTypes.length == 0)
        return 'None';
    
    var string = '';
    for(var i=0; i < deviceTypes.length; i++)
    {
        var typeString = displayStringForDeviceType(parseInt(deviceTypes[i]));
        if(string.length > 0)
            string += ', ';
        
        string += typeString;
    }
    return string;
}

function displayStringForDeviceType(type)
{
    switch(type)
    {
        case 0:
            return 'iPhone';
        case 1:
            return 'iPod Touch';
        case 2:
            return 'iPad';
        case 3:
            return 'Mac';
        case 4:
            return 'Web';
        case 5:
            return 'Android';
        default:
            break;
    }
    
    return 'Unknown';
}

function displayStringForSyncServices(syncServices)
{
    if(syncServices == null || syncServices.length == document.getElementsByName('sync_service').length)
        return 'All';
    
    if(syncServices.length == 0)
        return 'None';
    
    var string = '';
    for(var i=0; i < syncServices.length; i++)
    {
        var typeString = displayStringForSyncService(parseInt(syncServices[i]));
        if(string.length > 0)
            string += ', ';
        
        string += typeString;
    }
    return string;
}

function displayStringForSyncService(service)
{
    switch(service)
    {
        case 0:
            return 'Todo Cloud';
        case 1:
            return 'Dropbox';
        case 2:
            return 'Appigo Sync';
        case 3:
            return 'iCloud';
        case 4:
            return 'Toodledo';
        case 5:
            return 'No Sync Service';
        default:
            break;
    }
    
    return 'Unknown';
}

function showLoadingMessagesUI()
{
    document.getElementById('messages_loading_ui').style.display = "inline-block";
    document.getElementById('messages_container').style.display = "none";
    
    document.getElementById('reload_messages_button').setAttribute('class', 'button disabled');
    document.getElementById('reload_messages_button').setAttribute('onclick', '');
}

function hideLoadingMessagesUI()
{
    document.getElementById('messages_loading_ui').style.display = "none";
    document.getElementById('messages_container').style.display = "inline-block";
    
    document.getElementById('reload_messages_button').setAttribute('class', 'button');
    document.getElementById('reload_messages_button').setAttribute('onclick', 'loadAllMessages()');
}

function showMessagesUIWithTitle(title, showDatePickers)
{
    document.getElementById('messages_wrapper').style.display = 'block';
    document.getElementById('messages_title').innerHTML = title;
    if(showDatePickers)
    {
        document.getElementById('message_date_wrapper').style.display = 'block';
    }
    else
    {
        document.getElementById('message_date_wrapper').style.display = 'none';
    }
}

function hideMessagesUI()
{
    document.getElementById('messages_wrapper').style.display = 'none';
    document.getElementById('message_date_wrapper').style.display = 'none';
}

function displayDatePicker(identifier)
{
	var datepickerWrapper = document.getElementById(identifier + '_date_editor');
	datepickerWrapper.style.display = 'block';
    
    var selectedDate = parseInt(document.getElementById(identifier + '_date_value').value);
    if(selectedDate == 0)
    {
        var today = new Date();
        selectedDate = today.getTime() / 1000;
    }
    
	buildDatepickerUI(identifier + '_datepicker', selectedDate, true);
	
	//set up clickaway event
	var dismissDatePicker = function(event){hideDatePicker(event, identifier);};
	pushWindowClickEvent(dismissDatePicker);
};


function hideDatePicker(event, identifier)
{
	var doc = document;

	var editorEl = doc.getElementById(identifier + '_date_editor');
	var startDateLabel = document.getElementById(identifier + '_date_label');
    
	if (event == null || event.target != startDateLabel)
	{
		editorEl.setAttribute('style', '');
		popWindowClickEvent();
        
        if(identifier == 'start')
            setMessagesStartDate(datepicker.unix);
        else if(identifier == 'end')
            setMessagesEndDate(datepicker.unix);
        else if(identifier == 'expiration')
            setPageExpirationDate(datepicker.unix);
        else
        {
            //This is a message expiration date and the identifier is the message id
            setMessageExpirationDate(identifier, datepicker.unix);
        }
            
    }
};

function setMessagesStartDate(unixDate)
{
    if(unixDate != 0)
        unixDate = getStartOfDayOfDate(unixDate);
    
    var displayDate = displayHumanReadableDate(unixDate, false, true);
    document.getElementById('start_date_label').innerHTML = displayDate;
    document.getElementById('start_date_value').value = unixDate;
}
function setMessagesEndDate(unixDate)
{
    if(unixDate != 0)
        unixDate = getEndOfDayOfDate(unixDate);
    
    var displayDate = displayHumanReadableDate(unixDate, false, true);
    document.getElementById('end_date_label').innerHTML = displayDate;
    document.getElementById('end_date_value').value = unixDate;
}

//there can only be one window click event listener at a time to ensure that previous pop up menus are closed in the same order they were opened
var windowClickEvents = [];
function pushWindowClickEvent(jsFunction)
{
	//add new event to arrays
	windowClickEvents.push(jsFunction);
	
	//unset one before last event listener
	if (windowClickEvents.length > 1)
		window.unbindEvent('click', windowClickEvents[windowClickEvents.length - 2], false);
    
	//set new event listener
	window.bindEvent('click', windowClickEvents[windowClickEvents.length - 1], false);
};

//pops last window click event and set
function popWindowClickEvent()
{
	//unsets last click event and removes it from the array
	window.unbindEvent('click', windowClickEvents.pop(), false);
	
	//set previous to last event listener in array
	if(windowClickEvents.length > 0)
		window.bindEvent('click', windowClickEvents[windowClickEvents.length - 1], false);
};

/******** Message Expiration Date Update Methods **********/

function setMessageExpirationDate(messageid, unixDate)
{
    //If the new expiration date equals the old, just return
    var selectedDate = parseInt(document.getElementById(messageid + '_date_value').value);
    if(selectedDate == unixDate)
    {
        return;
    }

    if(unixDate != 0)
    {
        //Set the hours, minutes, and seconds of the expiration date to be the current time
        //to minimize the chance that we will attempt to post two messages with the same timestamp
        var now = new Date();
        var date = new Date(unixDate * 1000);
        date.setHours(now.getHours());
        date.setMinutes(now.getMinutes());
        date.setSeconds(now.getSeconds());
        
        unixDate = date.getTime() / 1000;
    }
    
    updateMessageExpirationDate(messageid, unixDate);
    

}

function updateMessageExpirationDate(messageid, newExpirationDate)
{
    if(messageid.length == 0)
        return false;
    
    document.getElementById(messageid + '_date_label').innerHTML = '<span class="progress_indicator" style="display:inline-block"></span>';
    

    var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;
    
    ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                hideModalContainer();
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    var displayDate = displayHumanReadableDate(newExpirationDate, false, true);
                    document.getElementById(messageid + '_date_label').innerHTML = displayDate;
                    document.getElementById(messageid + '_date_value').value = newExpirationDate;
                    
                    if(response.message)
                    {
                        if(response.message.removed_from_lookup)
                            document.getElementById(messageid).style.background = 'rgb(200,200,200)';
                        else
                            document.getElementById(messageid).style.background = 'rgb(256,256,256)';
                    
                        document.getElementById(messageid + '_posted_date').innerHTML = displayHumanReadableDate(response.message.time_posted, false, true);
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
                            alert(response.error);
                        else
                            alert("Error updating message");
                    }
                }
            }
            catch(e)
            {
                alert("Error updating message: " + e);
            }
		}
	}
    
	var params = 'method=updateMessageExpirationDate&messageid=' + messageid + '&expiration_date=' + newExpirationDate;
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}



/******** TABLE MANAGEMENT METHODS (remove these after development) ******/

function showLoadingTablesUI()
{
    document.getElementById('table_loading_ui').style.display = "inline-block";
    document.getElementById('message_center_tables').style.display = "none";
    
    document.getElementById('create_tables_button').setAttribute('class', 'button disabled');
    document.getElementById('delete_tables_button').setAttribute('class', 'button disabled');
    document.getElementById('create_tables_button').setAttribute('onclick', '');
    document.getElementById('delete_tables_button').setAttribute('onclick', '');
}
function hideLoadingTablesUI()
{
    document.getElementById('table_loading_ui').style.display = "none";
    document.getElementById('message_center_tables').style.display = "inline-block";
    
    document.getElementById('create_tables_button').setAttribute('class', 'button');
    document.getElementById('delete_tables_button').setAttribute('class', 'button');
    document.getElementById('create_tables_button').setAttribute('onclick', 'createAllTables()');
    document.getElementById('delete_tables_button').setAttribute('onclick', 'deleteAllTables()');
}

function createAllTables()
{
    var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;
    
    showLoadingTablesUI();
    
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                hideLoadingTablesUI();
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    loadAllTables();
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
                            alert(response.error);
                        else
                            alert("Error creating tables");
                    }
                }
            }
            catch(e)
            {
                alert("Error creating tables: " + e);
            }
		}
	}
    
	var params = 'method=createAllMessageCenterTables';
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function deleteAllTables()
{
    var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;
    
    showLoadingTablesUI();
    
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                hideLoadingTablesUI();
                if(response.success)
                {
                    loadAllTables();
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
                            alert(response.error);
                        else
                            alert("Error deleting tables");
                    }
                }
            }
            catch(e)
            {
                alert("Error deleting tables: " + e);
            }
		}
	}
    
	var params = 'method=deleteAllMessageCenterTables';
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function loadAllTables()
{
    var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;
    
    showLoadingTablesUI();
    
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                hideLoadingTablesUI();
                if(response.success && response.tables)
                {
                    var html = '';
                    if(response.tables.length == 0)
                    {
                        html = '<div style="margin-left:10px;">There are no tables in the system</div>';
                    }
                    else
                    {
                        for(var i = 0; i < response.tables.length; i++)
                        {
                            var tablename = response.tables[i];
                            html += '<div style="margin-left:10px;">' + tablename + '</div>';
                        }
                    }
                    
                    document.getElementById('message_center_tables').innerHTML = html;
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
                            alert(response.error);
                        else
                            alert("Error loading tables");
                    }
                }
            }
            catch(e)
            {
                alert("Error loading tables: " + e);
            }
		}
	}
    
	var params = 'method=getAllMessageCenterTables';
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}


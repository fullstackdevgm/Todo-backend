//var openMessageId = null; //Use this to keep track of which message is currently open so we only allow one open at a time
var messages = [];
var lastMessageKey = null;


function getMessageWithId(msgId)
{
	message = null;
	
	for (var i = 0; i < messages.length; i++)
	{
		if (messages[i].messageid == msgId)
		{
			message = messages[i];
			break;	
		}
	}
	
	return message;
};


function loadMessageCenterContent()
{
    loadMessagesFromServer();
}



function loadMessagesFromServer()
{
    document.getElementById('messages_loading_indicator').style.display = 'block';
    document.getElementById('messages_show_more_button').style.display = 'none';

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
                document.getElementById('messages_loading_indicator').style.display = 'none';
            
                var response = JSON.parse(ajaxRequest.responseText);
				
				if (response.success && response.messages)
				{
                    var html = '';
                    
                    messages = response.messages;
                    
                    for(var i=0; i < response.messages.length; i++)
                    {
                        html += htmlForMessage(response.messages[i]);
                        
                    }
                    if(response.last_key)
                    {
                        lastMessageKey = response.last_key;
                        document.getElementById('messages_show_more_button').style.display = 'inline-block';
                    }
                    else
                    {
                        lastMessageKey = null;
                        document.getElementById('messages_show_more_button').style.display = 'none';
                    }
                    
                    document.getElementById('messages_container').innerHTML += html;
                    
                    updatePageWithUnreadMessagesCount(response.unread_count);
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
                        displayGlobalErrorMessage(labels.unable_to_load_announcements + '.');
                    }
                }
                
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_load_announcements + ':' + e);
            }
		}
	}
    
   
    
	var params = 'method=getRecentMessages&limit=10';
    if(lastMessageKey != null)
        params += '&last_key=' + JSON.stringify(lastMessageKey);
   
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function htmlForMessage(message)
{

    var unreadClass = '';
    var readVal = 1;
    if(!message.read)
    {
        unreadClass = ' unread';
        readVal = 0;
    }
    
    var messagePreview = stripHtmlTags(message.message_html);

    var dateText = displayHumanReadableDate(message.time_sent/1000, false, true);


    var html  = '<div id="' + message.messageid + '_container" class="mc_message' + unreadClass + '" onclick="openMessage(\'' + message.messageid + '\')">';
    	html += '	<input type="checkbox" name="message_checkbox" messageid="' + message.messageid + '" onclick="stopEventPropogation(event);updateMarkAndDeleteButtons();"/>';
    	html += '	<span class="mc_message_label subject" >' + message.subject + '</span>';
/*     	html += '	<span class="mc_message_label preview" >' + messagePreview + '</span>'; */
    	html += '	<span class="mc_message_label date" >' + dateText + '</span>';
    	html += '</div>';
    	html += '<div id="' + message.messageid + '_body" class="mc_message_content">';
    	html += 	message.message_html;
    	html += '</div>';
    
    	//Hidden input element keeping track of whether this message is read or not
    	html += '<input type="hidden" id="' + message.messageid + '_read" value=' + readVal + '>';
    
    return html;
}

function closeMessage(messageId)
{

	hideModalContainer();
    //document.getElementById(message + '_body').setAttribute('class', 'mc_message_content');
    //document.getElementById(message + '_container').setAttribute('onclick', 'openMessage(\'' + message + '\')');
    
//    openMessageId = null;
}

function openMessage(messageId)
{
//    if(openMessageId != null)
//    {
//        closeMessage(openMessageId);
//    }

    var message = getMessageWithId(messageId);
    
    var body = '<div style="width:400px"> ' + message.message_html + '</div>';
    var header = message.subject;
    var footer = '<div class="button" onclick="closeMessage(\'' + messageId + '\')">'+labels.ok+'</div>';
    
    displayModalContainer(body, header, footer);
   

    //Update the message to be read on the server if it's currently unread
    var messageReadValue = document.getElementById(messageId + '_read').value;
    if(messageReadValue == 0)
    {
        updateMessages([messageId], 'mark_read', false);
    }
    
    
    //document.getElementById(messageId + '_body').setAttribute('class', 'mc_message_content open');
    //document.getElementById(messageId + '_container').setAttribute('onclick', 'closeMessage(\'' + messageId + '\')');
    
//    openMessageId = messageId;
    
}

function updateMessages(messageids, method, isBulkUpdate)
{
    if(messageids == null || messageids.length == 0)
        return;

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
                    updatePageWithUnreadMessagesCount(response.unread_count);
                    
                    if(isBulkUpdate)
                        hideModalOverlay();
                    
                    for(var i=0; i < messageids.length; i++)
                    {
                        var messageid = messageids[i];
                        var messageElement = document.getElementById(messageid + '_container');
                        var messageReadElement = document.getElementById(messageid + '_read');
                        
                        if(method == 'mark_read')
                        {
                            messageElement.setAttribute('class', 'mc_message');
                            messageReadElement.value = 1;
                        }
                        else if(method == 'mark_unread')
                        {
                            messageElement.setAttribute('class', 'mc_message unread');
                            messageReadElement.value = 0;
                        }
                        else if(method == 'mark_deleted')
                        {
                            hideModalContainer();
//                            if(messageid == openMessageId)
//                                closeMessage(messageid);
                        
                            messageElement.parentNode.removeChild(messageElement);

                        }
                    }
                    if(isBulkUpdate)
                        resetMessageCheckboxes();
                    else
                        updateMarkAndDeleteButtons();
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
                        displayGlobalErrorMessage(labels.unable_to_update_announcements + '.');
                    }
                }
                
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_update_announcements + ': ' + e);
            }
		}
	}
    
    
    
	var params = 'method=updateMessages&update=' + method;
    
    for (var i=0; i < messageids.length; i++)
    {
        params += '&messages[]=' + messageids[i];
    }
    
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function resetMessageCheckboxes()
{
    var checkboxes = document.getElementsByName('message_checkbox');
    for(var i=0; i < checkboxes.length; i++)
    {
        var checkbox = checkboxes[i];
        checkbox.checked = false;
    }
    
    var markButton = document.getElementById('message_mark_button');
    var deleteButton = document.getElementById('message_delete_button');
    
    markButton.setAttribute('class', 'button disabled');
    markButton.setAttribute('onclick', '');
    markButton.innerHTML = 'Mark Read';
    
    deleteButton.setAttribute('class', 'button disabled');
    deleteButton.setAttribute('onclick', '');
}

function updateMarkAndDeleteButtons()
{
    var checkboxes = document.getElementsByName('message_checkbox');
    
    var checkedCount = 0;
    var hasUnreadCheckedMessage = false;
    
    for(var i=0; i < checkboxes.length; i++)
    {
        var checkbox = checkboxes[i];
        if(checkbox.checked)
        {
            checkedCount++;
            if(!hasUnreadCheckedMessage)
            {
                var messageid = checkbox.getAttribute('messageid');
                //see if this message is unread
                if(document.getElementById(messageid + '_read').value == 0)
                    hasUnreadCheckedMessage = true;
            }
        }
    }
    
    var markButton = document.getElementById('message_mark_button');
    var deleteButton = document.getElementById('message_delete_button');
    
    if(checkedCount > 0)
    {
        markButton.setAttribute('class', 'button');
        markButton.setAttribute('onclick', 'markCheckedMessages()');
        
        if(hasUnreadCheckedMessage)
            markButton.innerHTML = 'Mark Read';
        else
            markButton.innerHTML = 'Mark Unread';
        
        deleteButton.setAttribute('class', 'button');
        deleteButton.setAttribute('onclick', 'promptToDeleteCheckedMessages()');
    }
    else
    {
        markButton.setAttribute('class', 'button disabled');
        markButton.setAttribute('onclick', '');
        markButton.innerHTML = 'Mark Read';
        
        deleteButton.setAttribute('class', 'button disabled');
        deleteButton.setAttribute('onclick', '');
    }
    
}

function markCheckedMessages()
{
    var checkboxes = document.getElementsByName('message_checkbox');

    var method = 'mark_unread';
    var messageIds = new Array();
    
    for(var i=0; i < checkboxes.length; i++)
    {
        var checkbox = checkboxes[i];
        if(checkbox.checked)
        {
            var messageid = checkbox.getAttribute('messageid');
            messageIds.push(messageid);

            if(method == 'mark_unread')
            {
                //see if this message is unread
                if(document.getElementById(messageid + '_read').value == 0)
                {
                    method = 'mark_read';
                }
            }
        }
    }

    var overlayMessage = '<div class="progress_indicator" style="display:inline-block"></div> ' + labels.updating_announcements;
    displayModalOverlay(null, overlayMessage);
    disableOnClickDismissalOfModalContainer();

    updateMessages(messageIds, method, true);
    
}

function promptToDeleteCheckedMessages()
{
    var checkboxes = document.getElementsByName('message_checkbox');
    var checkedCount = 0;
    
    for(var i=0; i < checkboxes.length; i++)
    {
        var checkbox = checkboxes[i];
        if(checkbox.checked)
        {
            checkedCount++;
        }
    }
    if(checkedCount == 0)
        return;
    
    var header = labels.delete_announcements_q;
    var body = labels.are_you_sure_you_want_to_delete;
    if(checkedCount == 1)
        body += labels.this_announcement_q + ' <br/>' +labels.the_announcement_will_be_removed_from ;
    else
        body += ' ' + labels.these + ' ' + checkedCount + ' ' + labels.announcements + '?<br/>' + labels.selected_announcements_will_be_removed_from;
    
    var footer = '<div class="button" id="delete_cancel_button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    footer += '<div class="button" id="delete_confirm_button" onclick="deleteCheckedMessages()">' + labels.delete + '</div>';
    displayModalContainer(body, header, footer);
}

function deleteCheckedMessages()
{
    var checkboxes = document.getElementsByName('message_checkbox');
    
    var messageIds = new Array();
    
    for(var i=0; i < checkboxes.length; i++)
    {
        var checkbox = checkboxes[i];
        if(checkbox.checked)
        {
            var messageid = checkbox.getAttribute('messageid');
            messageIds.push(messageid);
            
        }
    }
    
    document.getElementById('delete_cancel_button').setAttribute('onclick', '');
    document.getElementById('delete_cancel_button').setAttribute('class', 'button disabled');
    
    document.getElementById('delete_confirm_button').setAttribute('onclick', '');
    document.getElementById('delete_confirm_button').setAttribute('class', 'button disabled');
    document.getElementById('delete_confirm_button').innerHTML = '<span class="progress_indicator" style="display:inline-block;"></span>';
    
    disableOnClickDismissalOfModalContainer();
    
    updateMessages(messageIds, 'mark_deleted', true);
    
}

function getUnreadMessagesCountFromServer()
{
	var userIdEl = document.getElementById('userId');
	
	if (userIdEl != null && userIdEl.value.length > 0)
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
                        updatePageWithUnreadMessagesCount(response.message_count);
	                }
	                
	                if(response.error)
	                {
	                    if(response.error == "authentication")
	                    {
	                        history.go(0);
	                    }
	                }
	            }
	            catch(e)
	            {
	            }
	        }
	    }
	    
	    var params = 'method=getUpdatedUnreadMessageCount';
	    ajaxRequest.open("POST", "." , true);
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    ajaxRequest.send(params);
    }
}

//This should be called at page load time. If we already have a recent message count stored in
//the session, it will have been written to unread_message_count by PageLoader.php, so we will just pull the count
//from there and update the page. Otherwise, we'll go check the server for the unread message
//count and wait for a response before updating the page. 
function getUnreadMessagesCount()
{
    if(document.getElementById('unread_message_count'))
    {
        var count = parseInt(document.getElementById('unread_message_count').value, 10);
        updatePageWithUnreadMessagesCount(count);
    }
    else
    {
        //wait a few seconds to let the rest of the page loading ajax calls trigger
        //so that we won't hold everything up
        setTimeout(function(){getUnreadMessagesCountFromServer()}, 2000);
    }
}


function updatePageWithUnreadMessagesCount(count)
{
    var countEl = document.getElementById('message_center_count_item');
    var settingsMessagesBadgeEl = document.getElementById('unreadMessagesSettingsBadge');
    
    if(count > 0)
    {
        if(countEl)
        {
            countEl.innerHTML =  count;
            countEl.setAttribute('class', 'count on');
        }
        
        if(settingsMessagesBadgeEl)
        {
            settingsMessagesBadgeEl.setAttribute('class', 'settings-alert on');
        }
    }
    else
    {
        if(countEl)
            countEl.setAttribute('class', 'count');
        
        if(settingsMessagesBadgeEl)
            settingsMessagesBadgeEl.setAttribute('class', 'settings-alert');
    }
}
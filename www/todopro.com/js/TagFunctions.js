function displayEditTagFlyout(event, tagid, tagName)
{
	if(event)
		stopEventPropogation(event);

    var group_option_element = jQuery('#edit_tag_link_' + tagid).parents('.group_option');
    var position_element = group_option_element.position();
    var flyout = document.getElementById('tag_edit_flyout_' + tagid);
    
    var html = '<div class="control_edit_option control_edit_option_bordered" id="rename_option">' + labels.rename + '</div>';
    html += '<div class="control_edit_option" id="delete_option">' + labels.delete + '</div>';
    
    flyout.innerHTML = html;
    flyout.style.display = "block";
    flyout.style.top = position_element.top + 25 + "px";
    flyout.style.left = "13px";

    var background = document.getElementById('tag_edit_background_' + tagid);
    background.style.height = "100%";
    background.style.width = "100%";
    
    //Now bind the onclick actions using closures to avoid issues with special characters in the tag name
    var event = (function(id,n){return function(){displayRenameTagModal(id,n);}}(tagid, tagName));
    document.getElementById('rename_option').bindEvent('click', event, false);
    
    event = (function(id,n){return function(){displayDeleteTagModal(id,n);}}(tagid, tagName));
    document.getElementById('delete_option').bindEvent('click', event, false); 
    
}
function hideTagEditFlyout(event,tagid)
{
    document.getElementById('tag_edit_flyout_' + tagid).style.display = "none";
    document.getElementById('tag_edit_flyout_' + tagid).innerHTML = '';
    var background = document.getElementById('tag_edit_background_' + tagid);
    background.style.height = "0px";
    background.style.width = "0px";
}

function displayDeleteTagModal(tagid, tagName)
{
    hideTagEditFlyout(null, tagid);

    var header = labels.delete_tag;
    var body = labels.are_you_sure_you_want_to_delete_the_tag + ' "' + tagName + '"? ' + labels.this_will_remove_it_from_all_of_your_tasks + ' <br>';
    
    var footer = '<div class="button" onclick="deleteTag(\'' + tagid + '\')">' + labels.delete + '</div>';
    footer += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    
    displayModalContainer(body, header, footer);
}

function deleteTag(tagid)
{
    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
    if(!ajaxRequest)
    {
        return false;  
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
                    //If we're filtering by this tag, remove it from
                    //the current filter
                    var tagString = getCookieForName('TodoOnlineTagId');
                    if(tagString)
                    {
                        var tagArray = tagString.split(',');
                        var tagIndex = tagArray.indexOf(tagid)
                        if(tagIndex >= 0)
                        {
                            tagArray.splice(tagIndex, 1);
                            var newString = tagArray.join(',');
                            if(newString.length == 0)
                                newString = 'all';
                            
                            SetCookie('TodoOnlineTagId', newString);
                        }
                    }
                
                    window.location = ".";
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
                            history.go(0);
                        }
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_delete_tag);
                        history.go(0);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                history.go(0);
            }
        }
    }
    var params = "method=deleteTag&tagid=" + tagid;    

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);

}

function displayRenameTagModal(tagid, tagName)
{
    hideTagEditFlyout(null, tagid);

    var header = labels.rename + ' "' + tagName + '"';
    var body = '<div>';
    body += '<input type="text" id="rename_tag_text_field" class="centered_text_field" value="' + tagName + '" />';
    body += '</div>';
    
    var footer = '';
    footer += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    footer += '<div class="button disabled" onclick="" id="save_tag_name_button">' + labels.save + '</div>';
    
    displayModalContainer(body, header, footer);
    var el = document.getElementById('rename_tag_text_field');
    el.focus();
    el.select();
    var event = (function(id,n){return function(event){validateTagName(event,id,n);}}(tagid, tagName));
    el.bindEvent('keyup', event, false);
}


	
function saveTagName(tagid)
{
    var tagName = document.getElementById('rename_tag_text_field').value;
    tagName = trim(tagName);
    

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
                    //update values and UI
                    var newTagId = response.tagid;
                                        
                    //if the old tag is in the cookie, replace it with the new tag
                    var currentTagString = getCookieForName('TodoOnlineTagId');
                    if(currentTagString)
                    {
                        var tagArray = currentTagString.split(',');
                        var tagIndex = tagArray.indexOf(tagid)
                        if(tagIndex >= 0)
                        {
                            tagArray.splice(tagIndex, 1, newTagId);
                            var newString = tagArray.join(',');
                            if(newString.length == 0)
                                newString = 'all';
                            SetCookie('TodoOnlineTagId', newString);
                        }
                    }
                    window.location = ".";
                    
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in
                            history.go(0);
                        }
                        else
                        {
                            displayGlobalErrorMessage(response.error);
                        }
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_save_tag_name );
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' '+ e);
            }
        }
    }
    
    var params = "method=renameTag&name=" + encodeURIComponent(tagName) + "&tagid=" + tagid;
    ajaxRequest.open("POST", "." , true);
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);
    
}

function validateTagName(event, tagid, oldTagName)
{
    if(event.keyCode == 27) //escape button
    {
        hideModalContainer();
        return;
    }

    var tagName = document.getElementById('rename_tag_text_field').value;
    var trimmedName = trim(tagName);
    var button = document.getElementById('save_tag_name_button');
    
    if(trimmedName.length == 0 || trimmedName == trim(oldTagName))
    {
        button.setAttribute("class", "button disabled");
        button.setAttribute("onclick", "");
    }
    else
    {
        button.setAttribute("class", "button");
        button.setAttribute("onclick", "saveTagName('" + tagid + "')");
        
        if(event.keyCode == 13)
            saveTagName(tagid);
    }

}

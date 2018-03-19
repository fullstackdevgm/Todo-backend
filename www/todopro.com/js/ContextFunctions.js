function displayEditContextFlyout(event, contextid, contextName)
{
	if(event)
		stopEventPropogation(event);

    var group_option_element = jQuery('#edit_context_link_' + contextid).parents('.group_option');
    var position_element = group_option_element.position();
    var flyout = document.getElementById('context_edit_flyout_' + contextid);
    
    var html = '<div class="control_edit_option control_edit_option_bordered" id="rename_option">'+labels.rename+'</div>';
    html += '<div class="control_edit_option" id="delete_option">'+labels.delete+'</div>';
    
    flyout.innerHTML = html;
    flyout.style.display = "block";
    flyout.style.top = position_element.top + 25 + "px";
    flyout.style.left = "13px";

    var background = document.getElementById('context_edit_background_' + contextid);
    background.style.height = "100%";
    background.style.width = "100%";
    
    //Now bind the onclick actions using closures to avoid issues with special characters in the context name
    var event = (function(id,n){return function(){displayRenameContextModal(id,n);}}(contextid, contextName));
    document.getElementById('rename_option').bindEvent('click', event, false);
    
    event = (function(id,n){return function(){displayDeleteContextModal(id,n);}}(contextid, contextName));
    document.getElementById('delete_option').bindEvent('click', event, false); 
}
function hideContextEditFlyout(event,contextid)
{
    document.getElementById('context_edit_flyout_' + contextid).style.display = "none";
    document.getElementById('context_edit_flyout_' + contextid).innerHTML = '';
    
    var background = document.getElementById('context_edit_background_' + contextid);
    background.style.height = "0px";
    background.style.width = "0px";
}

function displayDeleteContextModal(contextId)
{
    hideContextEditFlyout(null, contextId);

	var bodyHTML = '';
	var headerHTML = labels.delete_context;
	var footerHTML = '';

    bodyHTML += '   <div>' + labels.are_you_sure_you_want_to_permanently_delete + '</div>';
	bodyHTML += '	<div class="breath-10"></div>';
	bodyHTML += '	<div>' + labels.doing_so_will_also_remove_this_context + '</div>';
	bodyHTML += '	<div class="breath-10"></div>';
    
    footerHTML += '<div id="delete_context_modal_button_container" style="display:inline-block;"></div>';
	footerHTML += '<div id="delete_context_ok_button" class="button" onclick="deleteContext(\'' + contextId + '\')">'+labels.delete_context+'</div>';
    footerHTML += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';

	displayModalContainer(bodyHTML, headerHTML, footerHTML);
};

function deleteContext(contextid)
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
                    if(getCookieForName('TodoOnlineContextId') == contextid)
                    {
                        SetCookie('TodoOnlineContextId', 'all');
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
                        displayGlobalErrorMessage(labels.unable_to_delete_context + '. ');
                        history.go(0);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_delete_context + ': ' + e);
                history.go(0);
            }
        }
    }
    var params = "method=deleteContext&contextId=" + contextid;    

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);

};


function displayRenameContextModal(contextid, contextName)
{
    hideContextEditFlyout(null, contextid);

    var header = labels.rename + ' "' + contextName + '"';
    var body = '<div>';
    body += '<input type="text" id="rename_context_text_field" class="centered_text_field" value="' + contextName + '" />';
    body += '</div>';
    
    var footer = '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    footer += '<div class="button disabled" onclick="" id="save_context_name_button">' + labels.ok + '</div>';
    
    displayModalContainer(body, header, footer);
    var el = document.getElementById('rename_context_text_field')
    el.focus();
    el.select();
    
    var event = (function(id,n){return function(event){validateContextName(event,id,n);}}(contextid, contextName));
    el.bindEvent('keyup', event, false);
    
}


	
function saveContextName(contextid)
{
    var contextName = document.getElementById('rename_context_text_field').value;
    contextName = trim(contextName);
    

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
                        displayGlobalErrorMessage(labels.unable_to_save_context_name + '.');
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_save_context_name+ ': ' + e);
            }
        }
    }
    
    var params = "method=updateContext&contextName=" + encodeURIComponent(contextName) + "&contextId=" + contextid;
    ajaxRequest.open("POST", "." , true);
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);
		
}

function validateContextName(event, contextid, oldContextName)
{
    if(event.keyCode == 27) //escape button
    {
        hideModalContainer();
        return;
    }

    var contextName = document.getElementById('rename_context_text_field').value;
    var trimmedName = trim(contextName);
    var button = document.getElementById('save_context_name_button');
    
    if(trimmedName.length == 0 || trimmedName == trim(oldContextName))
    {
        button.setAttribute("class", "button disabled");
        button.setAttribute("onclick", "");
    }
    else
    {
        button.setAttribute("class", "button");
        button.setAttribute("onclick", "saveContextName('" + contextid + "')");
        if(event.keyCode == 13)
            saveContextName(contextid);
    }

}

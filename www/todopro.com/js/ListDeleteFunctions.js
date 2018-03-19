

function setDeleteListModalWithInfo(listid, role, ownerCount, peopleCount, listname)
{
    var bodyHTML = '';
    var buttonHTML = '';
    var doc = document;
    
    if(role != 2) 
    {
        //If the user not an owner, their only option is to leave the list
        bodyHTML = labels.are_you_sure_you_want;
        buttonHTML = '<div class="button" onclick="leaveList(\'' + listid + '\')">' + labels.leave_list + '</div>';
    }
    else
    {
        if(peopleCount == 1)
        {
            bodyHTML = labels.are_you_sure_you_want_to_permanently;
            buttonHTML = '<div id="delete_list_progress_indicator" class="progress_indicator"></div><div id="delete_list_button" class="button" onclick="deleteList(\'' + listid + '\')">' + labels.delete_list + '</div>';
        }
        else
        {
            bodyHTML = labels.you_may_not_delete_this_list_because ;
            
            if(ownerCount > 1)
            {
                buttonHTML = '<div class="button" onclick="leaveList(\'' + listid + '\')">'+labels.leave_list+'</div>';
                bodyHTML += '<br><br>' + labels.you_may_leave_the_list_or_remove_all;
            }
            else
            {
                buttonHTML = '';
//                buttonHTML = '<div class="button" onclick="leaveList(\'' + listid + '\')">Leave List</div>';
                bodyHTML += '<br><br>' + labels.you_may_assign_another_owner_and;
            }

            buttonHTML += '<div class="button" id="manage_member_button">' + labels.manage_members + '</div>';
        }
    }
    





    hideListEditFlyout(null, listid);

    var bodyHTMLwrapper = '';
    var headerHTML = labels.delete + ' "' + listname + '"';
    var footerHTML = '';

    bodyHTMLwrapper += ' 	<div class="breath-4"></div>';
    bodyHTMLwrapper += '   <div id="delete_list_modal_body">' + bodyHTML + '</div>';

    footerHTML += '<div id="delete_list_modal_button_container" style="display:inline-block;">'+buttonHTML+'</div>';
    footerHTML += '<div id="cancel_delete_list_button" class="button" onclick="hideModalContainer()">'+labels.cancel+'</div>';

    displayModalContainer(bodyHTMLwrapper, headerHTML, footerHTML);
    if(doc.getElementById('manage_member_button'))
    {
        var event = (function(li,ln){return function(){hideModalContainer();displaySharingModal(li,ln, true);}}(listid, listname));
        doc.getElementById('manage_member_button').bindEvent('click', event, false);
    }
}

function getDeleteListInfo(listid, listname)
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
                if(response.success == true)
                {
                    var role = response.role;
                    var ownerCount = response.owner_count;
                    var peopleCount = response.people_count;
                    
                    setDeleteListModalWithInfo(listid, role, ownerCount, peopleCount, listname);
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
                    {
                        displayGlobalErrorMessage(labels.unknown_error_getting_delete_list_info);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }

    var params = "method=getDeleteListInfo&listid=" + listid;

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);
}

function leaveList(listid)
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
                if(response.success == true)
                {
                    if(getCookieForName('TodoOnlineListId') == listid)
                    {
                        SetCookie('TodoOnlineListId', 'all');
                    }
                        
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
                        displayGlobalErrorMessage(labels.unable_to_remove_you_from_this_list);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }
    var params = "method=changeRole&listid=" + listid + "&uid=current&role=remove";

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);
    
}

function deleteList(listid)
{
	var doc = document;
	var progressIndicator = doc.getElementById('delete_list_progress_indicator');
	var deleteButton = doc.getElementById('delete_list_button');
	var cancelButton = doc.getElementById('cancel_delete_list_button');
	
	progressIndicator.style.display = 'inline-block';
	deleteButton.setAttribute('onclick', '');
	cancelButton.setAttribute('onclick', '');
	cancelButton.setAttribute('class', 'button disabled');
	
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
                    if(getCookieForName('TodoOnlineListId') == listid)
                    {
                        SetCookie('TodoOnlineListId', 'all');
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
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                        }
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_delete_list);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }
    var params = "method=deleteList&listid=" + listid;    

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);

}

  
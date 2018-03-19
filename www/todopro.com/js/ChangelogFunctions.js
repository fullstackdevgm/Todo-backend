function showEventlogDetails(id) 
{
	
	var popupId = 'changelog_event_popup_' + id;
	var popupContentId = 'changelog_event_popup_content_' + id;
	var detailPopup = document.getElementById(popupId);
	var popupContent = document.getElementById(popupContentId);
	var popupContentBg = document.getElementById('changelog_event_popup_bg_' + id);
	var eventContainer = document.getElementById('changelog_event_container_' + id);
	var itemType = document.getElementById('changelog_item_type_' + id).value;
	
	
	if (detailPopup.style.display == "none")
	{
		eventContainer.style.backgroundColor = "rgb(216,223,234)";
		eventContainer.style.borderBottom = "1px solid rgb(168,178,206)";
		eventContainer.style.borderTop = "1px solid rgb(168,178,206)";
		
		detailPopup.style.display = "block";
		//detailPopup.focus();
		popupContentBg.style.display = "block";
		popupContent.innerHTML = labels.loading;
		switch (itemType)
		{
			case "7":
				displayTaskDetails(id);
				break;
			case "4":
				displayTaskDetails(id);
				break;	
			default:
                popupContent.innerHTML = labels.this_item_type_1 + itemType + labels.this_item_type_2;
				break;
		}
	}
	else
		hideEventlogDetails(id);
}

function hideEventlogDetails(id)
{
	var popupId = 'changelog_event_popup_' + id;
	var popupContentBg = document.getElementById('changelog_event_popup_bg_' + id);
	var eventContainer = document.getElementById('changelog_event_container_' + id);
	var popupContent = document.getElementById('changelog_event_popup_content_' + id);
	
	document.getElementById(popupId).style.display = "none";
	popupContentBg.style.display = "none";
	
	eventContainer.style.backgroundColor = "white";
	eventContainer.style.borderBottom = "1px solid lightgray";
	eventContainer.style.borderTop = "1px solid white";
	
	
	popupContent.innerHTML ="";
	
	return false;
}

function displayTaskDetails(eventId)
{

	var taskId = document.getElementById('changelog_item_id_' +  eventId).value;
	var containerId = 'e';
	var detailPopup = document.getElementById('changelog_event_popup_' + eventId);
	var popupContent = document.getElementById('changelog_event_popup_content_' + eventId);
	
	var itemType = document.getElementById('changelog_item_type_' + eventId).value;
	
	
		
	var ajaxRequest = getAjaxRequest();  
	if(!ajaxRequest)
		return false;
	
   	//Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{  
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == false && response.error=="authentication")
                {
                    //make the user log in again
                    history.go(0);
                    return;
                }
            }
            catch(e)
            {
            }
            
			if(ajaxRequest.responseText != "")
			{
				popupContent.innerHTML = ajaxRequest.responseText;
				var commentString = document.getElementById('comment_toggle_' + containerId + "_" + taskId).innerHTML;
				var noteString = document.getElementById('task_note_' + containerId + "_" + taskId).value;
							
				if (commentString != "Comment")
					toggleTaskCommentsDisplay(taskId, containerId);
					
			   	if (noteString != "")
			   		toggleTaskNotes(taskId, containerId);		
			}
		}
	}
	
	
	var	params = "method=getPagedTasks&taskId=" + taskId;
		
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);

}
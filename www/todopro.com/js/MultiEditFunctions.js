/* lists */

function displayMultiEditListPicker()
{
	var bodyHTML = '<div id="multi_edit_lists_options"></div>';
	var headerHTML = labels.move_tasks_to_list;
	var footerHTML = '';
	
	
	footerHTML += '<div class="button disabled" id="multiEditListOkButton">' + labels.ok + '</div>';
	footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.cancel + '</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	loadMultiEditListOptions();
};

function loadMultiEditListOptions()
{
	var doc = document;
	
	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
			try
            {
                if(ajaxRequest.responseText != "")
	            {
			       	var responseJSON = JSON.parse(ajaxRequest.responseText);

	            	if(responseJSON.success == false && responseJSON.error=="authentication")
	                {
	                    history.go(0);
	                    return;
	                }

	            	if (responseJSON.success == true)
	            	{
	            		var listsJSON = responseJSON.lists;
	                	var listsCount = listsJSON.length;

		 				if (listsCount > 0)
		 				{
							var listPickerHTML = '';

							for (var i = 0; i < listsCount; i++)
							{
								var listName = htmlEntities(listsJSON[i].name);
								var listId = listsJSON[i].listid;
								var listColor = listsJSON[i].color;

								listPickerHTML += '	<label for="multi_edit_list_option_' + listId + '">';
								listPickerHTML += '		<input type="radio" name="multi_edit_move_to_list_options" id="multi_edit_list_option_' + listId + '" value="' + listId + '" listcolor="' + listColor + '" onclick="enableMultiEditListOkButton()"/>';
								listPickerHTML += '		<div style="background-color:rgba(' + listColor +', .6);" class="listColorIcon"></div>';
                                listPickerHTML += 			listName;
								listPickerHTML += '		</label>';
								listPickerHTML += '	</label>';
							}

							doc.getElementById('multi_edit_lists_options').innerHTML = listPickerHTML;
		 				}
	            	}
	            	else
	            		displayGlobalErrorMessage(labels.failed_to_retrieve_lists_for_list + ' ' + ajaxRequest.responseText);

	 			}
            }
            catch (e) {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
         }
	}

	var params = "method=getControlContent&type=list";

	ajaxRequest.open("POST", ".", false);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);

};

function enableMultiEditListOkButton()
{
	var okButton = document.getElementById('multiEditListOkButton');
	
	okButton.onclick = function(){multi_edit_move_to_list()};
	okButton.className = 'button';
};

function cancelMultiEditListSelection()
{
	hideModalContainer();
	//hideMultiEditActions();
};

/*due dates*/
function displayMultiEditDueDatePicker()
{
	var multiEditPickerId = 'multi_edit_due_date_picker';
	var bodyHTML = '<div id="' + multiEditPickerId + '"></div>';
	var headerHTML = labels.schedule_tasks;
	var footerHTML = '';
	
	footerHTML += '<div class="button" id="multiEditListOkButton" onclick="multi_edit_schedule_due_date()">' + labels.ok + '</div>';
	footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.cancel + '</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	buildDatepickerUI(multiEditPickerId, '0', true);
};


/*delete tasks*/
function displayMultiEditDeleteDialog()
{
	var bodyHTML = '';
	var headerHTML = '';
	var footerHTML = '';
	
	headerHTML = labels.delete_tasks;
	
	if (selectedTasks.length > 0)
	{
		bodyHTML = labels.are_you_sure_you_want_to_delete_the_selected;
		footerHTML += '<div class="button" id="multiEditListOkButton" onclick="multi_edit_delete()">' + labels.ok + '</div>';
		footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.cancel + '</div>';
	}
	else
	{
        bodyHTML += labels.you_must_select_at_least_one_task;
        bodyHTML += '<br/><br/>';
        bodyHTML += labels.you_can_select_a_task_by_clicking_on_it;
        footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.ok + '</div>';
	}
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
};

/*contexts*/

function displayMultiEditContextPicker()
{
    var headerHTML = labels.assign_context;
    var bodyHTML = '<div id="multi_edit_context_picker_body">' + labels.loading_contexts + '</div>';
    
    
    var footerHTML = '<div class="button disabled" id="multiEditContextOkButton">' + labels.cancel + 'Assign</div>';
    footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.cancel + '</div>';
    
    loadMultiEditContextOptions();
    displayModalContainer(bodyHTML, headerHTML, footerHTML);
}

function enableMultiEditContextAssignButton()
{
	var okButton = document.getElementById('multiEditContextOkButton');
	
	okButton.onclick = function(){multi_edit_assign_context()};
	okButton.className = 'button';
}

function loadMultiEditContextOptions()
{
    //request list names from server
    var ajaxRequest = getAjaxRequest();
    if(!ajaxRequest)
        return false;

    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success && response.contexts)
                {
                    var html = '<ul>';
                    html += '<li>';
                    html += '<label for="me_context_option_null">';
                    html += '<input type="radio" name="multi_edit_assign_context" onclick="enableMultiEditContextAssignButton()" id="me_context_option_null" value="0"/>';
                    html += controlStrings.noContext+'</label>';
                    html += '</li>';
                    
                    var contexts = response.contexts;
                    for (var i = 0; i < contexts.length; i++)
                    {
                        var contextName = contexts[i].name;
                        var contextId = contexts[i].id;
                       
                       html += '<li>';
                       html += '<label for="me_context_option_' + contextId + '">';
                       html += '<input type="radio" name="multi_edit_assign_context" onclick="enableMultiEditContextAssignButton()" id="me_context_option_' + contextId + '" value="' + contextId + '"/>';
                       html += contextName + '</label>';
                       html += '</li>';		
                    }

                    html += '</ul>';
                    document.getElementById('multi_edit_context_picker_body').innerHTML = html;
                    
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                        {
                            history.go(0);
                            return;
                        }
                        else
                            displayGlobalErrorMessage(response.error);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_load_contexts + '.');
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_load_contexts + ': ' + e);
            }
        }
    }

    var params = "method=getControlContent&type=context";

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);

}

/*priority*/


function displayMultiEditPriorityPicker()
{
    var headerHTML = labels.set_priority ;
    var bodyHTML = '<ul>';
    
    bodyHTML += '<li>';
    bodyHTML += '<label for="me_priority_option_high">';
    bodyHTML += '<input type="radio" onclick="enableMultiEditPrioritySetButton()" name="multi_edit_set_priority" id="me_priority_option_high" value="1"/>';
    bodyHTML += '<img src="https://s3.amazonaws.com/static.plunkboard.com/images/task/task-priority-high.png"> '+taskSectionsStrings.high+'</label>';
    bodyHTML += '</li>';
    
    bodyHTML += '<li>';
    bodyHTML += '<label for="me_priority_option_medium">';
    bodyHTML += '<input type="radio" onclick="enableMultiEditPrioritySetButton()" name="multi_edit_set_priority" id="me_priority_option_medium" value="5"/>';
    bodyHTML += '<img src="https://s3.amazonaws.com/static.plunkboard.com/images/task/task-priority-med.png"> '+taskSectionsStrings.medium+'</label>';
    bodyHTML += '</li>';
    
    bodyHTML += '<li>';
    bodyHTML += '<label for="me_priority_option_low">';
    bodyHTML += '<input type="radio" onclick="enableMultiEditPrioritySetButton()" name="multi_edit_set_priority" id="me_priority_option_low" value="9"/>';
    bodyHTML += '<img src="https://s3.amazonaws.com/static.plunkboard.com/images/task/task-priority-low.png"> ' + taskSectionsStrings.low + '</label>';
    bodyHTML += '</li>';
    
    bodyHTML += '<li>';
    bodyHTML += '<label for="me_priority_option_none">';
    bodyHTML += '<input type="radio" onclick="enableMultiEditPrioritySetButton()" name="multi_edit_set_priority" id="me_priority_option_none" value="0"/>';
    bodyHTML += '<img src="https://s3.amazonaws.com/static.plunkboard.com/images/task/task-priority-none.png"> ' + taskSectionsStrings.none + '</label>';
    bodyHTML += '</li>';
    
    bodyHTML += '</ul>';
    
    var footerHTML = '<div class="button disabled" id="multiEditPrioritySetButton">' + labels.save + '</div>';
    footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.cancel + '</div>';
    
    displayModalContainer(bodyHTML, headerHTML, footerHTML);
}

function enableMultiEditPrioritySetButton()
{
	var okButton = document.getElementById('multiEditPrioritySetButton');
	
	okButton.onclick = function(){multi_edit_set__priority()};
	okButton.className = 'button';
}






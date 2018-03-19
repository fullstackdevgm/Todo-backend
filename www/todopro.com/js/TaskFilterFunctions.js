function showAssignmentFilterPicker(e)
{
	var doc = document;
	var picker = doc.getElementById('task_assign_filter_flyout');
	var pickerBackground = doc.getElementById('task_assign_filter_background');
	var listId = 'all'; //this will display all of the users across all lists no matter which list the picker is in
	var currentFilterId = doc.getElementById('task_assign_filter_toggle').getAttribute('assignFilterId');
	
	//try {listId = doc.getElementById('listId').value;}
	
	//catch (err) {listId = 'all';}
		
	if (picker.style.display == "block")
	{
		hideAssignmentFilterPicker();
	}
	else
	{
		var pickerHTML = '';
		
		//get list users from server
		picker.innerHTML = pickerHTML;
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
	                if(response.success == false && response.error=="authentication")
	                {
	                    history.go(0);
	                    return;
	                }
	            }
	            catch(e){}
	            
	            if(ajaxRequest.responseText != "")
	            {
	            	var responseJSON = JSON.parse(ajaxRequest.responseText);
	
	            	if(responseJSON.success)
	            	{
						var usersJSON = responseJSON.users;
						var selectedOption = '';
						var unassignedSelectedOption = '';
						var everyoneSelectedOption = '';
//						var usedIds = new Array();
						
						if (currentFilterId == 'all' || currentFilterId == '')
							everyoneSelectedOption = ' checked="checked"';
						else if (currentFilterId == 'none')
							unassignedSelectedOption = ' checked="checked"';
						
						//build everyone option
						pickerHTML += '	<label for="user_option_all" class="control_edit_option" onclick="filterTasks(\'all\')">';
						pickerHTML += ' 	<input type="radio" name="task_assign_selected_option" value="all" id="user_option_all" ' + everyoneSelectedOption + '/>';
						pickerHTML += '			<img class="small_usr_pic" src="https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif" />';
						pickerHTML += '			<span class="user_option_name">' + controlStrings.everyone + '</span>';
						pickerHTML += '	</label>';
						
                        //build unnassigned option
						pickerHTML += '	<label for="user_option_nobody" class="control_edit_option" onclick="filterTasks(\'none\')">';
						pickerHTML += ' 	<input type="radio" name="task_assign_selected_option" value="none" id="user_option_nobody" ' + unassignedSelectedOption + '/>';
						pickerHTML += '			<img class="small_usr_pic" src="https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif" />';
						pickerHTML += '			<span class="user_option_name">' + controlStrings.unassigned + '</span>';
						pickerHTML += '	</label>';
                        
						//build user options
						for (var i = 0; i < usersJSON.length; i++)
						{
							
							var nameLabel = '';
							var filterLabel = '';
							var selectedOption = '';
                            var borderedClass = '';
							
//							if (usedIds.indexOf(usersJSON[i].id) < 0)
							{
                                nameLabel = usersJSON[i].name;
                                filterLabel = nameLabel;// + controlStrings.someonesTasks;
								
								if (currentFilterId == usersJSON[i].id)
									selectedOption = ' checked="checked"';
                                if(i == 0 && usersJSON.length > 1) //add a separator after the current user
                                    borderedClass = ' control_edit_option_bordered';
											
								pickerHTML += '	<label for="user_option_' + usersJSON[i].id + '" class="control_edit_option' + borderedClass + '" onclick="filterTasks(\'' + usersJSON[i].id + '\')">';
								pickerHTML += ' 	<input type="radio" name="task_assign_selected_option" value="' + usersJSON[i].id + '" id="user_option_' + usersJSON[i].id + '"  ' + selectedOption + '/>';
								pickerHTML += '			<img class="small_usr_pic" src="' + usersJSON[i].imgurl + '" />';
								pickerHTML += '			<span class="user_option_name">' + nameLabel + '</span>';
								pickerHTML += ' </label>';
								
//								usedIds.push(usersJSON[i].id);								
							}
                            
						}
						
						
						picker.innerHTML = pickerHTML;
						picker.style.display = "block";
						pickerBackground.style.height = "100%";
						pickerBackground.style.width = "100%";
						
						//pop picker to the left
						
                         picker.style.visibility = 'hidden';
						picker.style.display = "block";
						pickerWidth = picker.clientWidth;
						picker.style.marginLeft = '-' + ((picker.clientWidth) - 26) + 'px';
						picker.style.visibility = 'visible';
						picker.style.maxHeight = '400px';
                        
						
						pickerBackground.style.height = "100%";
						pickerBackground.style.width = "100%";
				
						scrollUpViewport(e);	
	            	}
	            	else
	            		displayGlobalErrorMessage(labels.failed_to_retrieve_users_for_list + ': ' + ajaxRequest.responseText);
	            }
	        }
	    }
	    
	    var params = 'method=getControlContent&type=listUsers&listId=' + listId;
	        
	    ajaxRequest.open("POST", ".", false);
	    
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    ajaxRequest.send(params);
	}
};

function hideAssignmentFilterPicker()
{
	try
    {
		var doc = document;
		var picker = doc.getElementById('task_assign_filter_flyout');
		var pickerBackground = doc.getElementById('task_assign_filter_background');
		
		picker.style.display = "none";
		
		pickerBackground.style.height = "0px";
		pickerBackground.style.width = "0px";
	}
	catch (err)
	{
		
	}	
};


function filterTasks(filterId)
{
    SetCookieAndLoad('TodoOnlineTaskAssignFilterId',filterId);

};



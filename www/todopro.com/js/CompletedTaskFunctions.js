
var lastRetrievedCompletedTask = null;

function getMoreCompletedTasks(limit)
{
	var doc = document;

	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;
    window.preocessed_sections = 2;
    var container = null;
    var div = null;
    if(doc.getElementById('show_more_completed_tasks_container') && doc.getElementById('show_more_completed_tasks_div'))
    {
        container = doc.getElementById('show_more_completed_tasks_container');
        div = doc.getElementById('show_more_completed_tasks_div');
        
        container.setAttribute("onclick", "");
        div.innerHTML = "<img src='https://s3.amazonaws.com/static.plunkboard.com/gifs/ajax-loader.gif'>";
    }

	var listid = doc.getElementById('listid').value;

	//Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success && response.tasks)
                {
                    var sectionEl = doc.getElementById("completed_tasks_container");
					var html = '';

				 	for (var i = 0; i < response.tasks.length; i++)
					{
                        var task = response.tasks[i];
						html += getTaskHTML(task);
                        
                        if(i == response.tasks.length - 1)
                        {
                            var id = task.taskid;
                            var name = task.name;
                            var completiondate = 0;
                            if(task.completiondate)
                                completiondate = task.completiondate;
                            var sortorder = 0;
                            if(task.sort_order)
                                sortorder = task.sort_order;
                            var priority = 0;
                            if(task.priority)
                                priority = task.priority;
                                
                            lastRetrievedCompletedTask = new Object();
                            lastRetrievedCompletedTask.id = id;
                            lastRetrievedCompletedTask.name = name;
                            lastRetrievedCompletedTask.completiondate = completiondate;
                            lastRetrievedCompletedTask.priority = priority;
                            lastRetrievedCompletedTask.sortorder = sortorder;
                        }
					}
                    if(container != null && div != null)
                    {
                        if(response.tasks.length == limit)
                        {
                            container.style.display = "block";
                            container.setAttribute("onclick", "getMoreCompletedTasks('5')");
                            div.innerHTML = "Show More";
                        }
                        else if(response.premium_limited)
                        {
                            container.style.display = "block";
                            container.setAttribute("onclick", "displayPremiumCompletedTaskModal()");
                            div.innerHTML = "Show More";
                        }
                        else
                        {
                            container.setAttribute("onclick", "");
                            container.style.display = "none";
                        }
                    }

                    if(html.length > 0)
                        sectionEl.parentNode.parentNode.setAttribute("style", "display:block;");

				    sectionEl.innerHTML = sectionEl.innerHTML + html;
                    
                    showTask(taskitoIdToHighlight, taskIdToHighlight, parentIdToHighlight, false);
                    liveTaskSort();

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
                        displayGlobalErrorMessage(labels.unable_to_load_completed_tasks+ '.');
                        
                    }
                }
                
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_load_completed_tasks + ': ' + e);
            }
        }

    }
    var params = '';

    params = "method=getCompletedTasks&listid=" + listid + "&limit=" + limit;
    
    if(lastRetrievedCompletedTask != null)
    {
        params += "&before_timestamp=" + lastRetrievedCompletedTask.completiondate + "&before_sortorder=" + lastRetrievedCompletedTask.sortorder + "&before_priority=" + lastRetrievedCompletedTask.priority + "&before_name=" + lastRetrievedCompletedTask.name + "&before_id=" + lastRetrievedCompletedTask.id;
    }

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);    
}

function displayPremiumCompletedTaskModal()
{
     var footerHTML = '<div class="button" onclick="hideModalContainer()">Later</div><a class="button" href="?appSettings=show&option=subscription">' + labels.go_premium + '</a>';

    displayModalContainer(labels.you_need_a_premium_account, labels.premium_feature, footerHTML);
}


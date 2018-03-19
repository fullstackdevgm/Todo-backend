//****************************
// ! onload
//****************************

window.preocessed_sections = 0;
window.create_task_precessing = false;
window.dragged_tasks_count = 0;
window.dragged_tasks_updated_count = 0;

var taskSections = [];

window.bindEvent('load', function()
{
	setFilterBanner();
	calculateMainContainersSize();
    loadTasks();
	//loadAssignmentHTML();

	loadSearchBox();
	buildDatepickerUI('dashboard_calendar', new Date().getTime()/1000, false);
	//getUnreadMessagesCount();
	//document.getElementById('footer_links').innerHTML = getFooterLinksHtml();
}, false);

window.bindEvent('resize', function(){
	calculateMainContainersSize();
	if (isNoteEditorOpen)
		resizeTaskNoteEditor();

},	false);

function loadSearchBox()
{
	document.getElementById('search_wrap').innerHTML = '<input type="text" onkeydown="if (event.keyCode == 13) searchTasks(\'searchField\')" id="searchField" autocomplete="off" placeholder="'+labels.search_tasks_d +'"/>';
};

//****************
// ! Task sections
//****************

function loadTasks()
{
    loadTaskSectionHeaders(pageSortOrder);
	getMoreTasks();
}

function loadTaskSectionHeaders(sortType)
{
	var sectionsHTML = '';
	var tasksContainer = document.getElementById('task_sections_wrapper');

    if(showCompletedTasks)
    {
        if(sortType == 3)
        {
            taskSections = ['search'];
            lastRetrievedCompletedTask = null;
        }
        else
            taskSections =  ['new_', 'completed'];
    }
    else
    {
        //set up task sections
        switch (sortType)
        {
            case 0: //Sort by due date
                //taskSections = ['noduedate', 'new_', 'overdue', 'today', 'tomorrow', 'nextsevendays', 'future', 'completed'];
                taskSections = ['new_','overdue', 'today', 'tomorrow', 'nextsevendays', 'future', 'noduedate', 'completed'];
                break;
            case 1: //Sort by priority
                taskSections = ['new_', 'high', 'medium', 'low', 'none', 'completed'];
                break;
            case 2: //Sort alphabetically
                taskSections = ['new_', 'incomplete', 'completed'];
                break;
            case 3: //SEARCH section
                taskSections = ['search'];
                break;
            default:
                break;
        }
    }
    if(sortType != 3)
        endSearch(false);

	//set up sections HTML
	for (var i = 0; i < taskSections.length; i++)
	{
        var hide_class = '';
        if(sortType !== 3){
		    hide_class = 'hidden';
        }
		sectionsHTML += '<div id="' + taskSections[i] + '_tasks_container_wrap" class="tasks_container_wrap ' + hide_class + '">';
		sectionsHTML += '	<div class="tasks_container" >';
		sectionsHTML += '		<div id="section_header_wrap_' + taskSections[i] + '" class="section_header_wrap" ondragenter="sectionHeaderDragEnter(event, \'' + taskSections[i] + '\')" ondragover="return false" ondragleave="sectionHeaderDragLeave(event, \'' + taskSections[i] + '\')" ondrop="sectionHeaderCatchDrop(event, \'' + taskSections[i] + '\')">';
		sectionsHTML += '			<h2 >' + taskSectionsStrings[taskSections[i]] + '</h2>';
		sectionsHTML += '			<div class="section_header_target drop_target"></div>';
		sectionsHTML += '		</div>';
		sectionsHTML += '		<div class="section_tasks_container" name="task_section_header" id="' + taskSections[i] + '_tasks_container" >';
		sectionsHTML += '		</div>';
		sectionsHTML +=	'	</div>';
		sectionsHTML +=	'	<div class="tasks_container dropshadow left"></div>';
		sectionsHTML +=	'	<div class="tasks_container dropshadow right"></div>';
		sectionsHTML += '</div>';

		// ondrop="contextCatchDrop(event, '0')"  </div>
	}

	var moreTasksButtonHTML = '<div id="show_more_completed_tasks_container" class="more_button_container" style="display:none;" onclick="">';
    	moreTasksButtonHTML += '    <div class="button more_button" id="show_more_completed_tasks_div">'+labels.show_more+'</div>';
    	moreTasksButtonHTML += '</div>';

	tasksContainer.innerHTML = sectionsHTML + moreTasksButtonHTML;

    if (jQuery('#footer_links').size() === 0) {
        var footerLinksHTML = '<div id="footer_links" class="tasks_footer_links">' + getFooterLinksHtml() + '</div>';
        jQuery('#task_sections_wrapper').after(footerLinksHTML);
    }
    if (!userSettings.showOverdueTasks) {
        var overdue_section = document.getElementById('overdue_tasks_container_wrap');
        if (overdue_section) {
            overdue_section.style.display = "none";
        }
    }

};



//*********************
// ! Banner filter
//*********************
function clearBannerFilterAndLoad()
{
    document.cookie = 'TodoOnlineTagId=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    document.cookie = 'TodoOnlineContextId=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    document.cookie = 'TodoOnlineTaskAssignFilterId=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    document.cookie = 'TodoOnlineShowCompletedTasks=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';

    window.location.reload()
};

function setFilterBanner()
{
	var doc = document;

    var filterString = '';
    var dividerString = ' | ';

    if (filterBannerContextName != 'all')
    {
        if(filterBannerContextName == 'nocontext')
            filterBannerContextName = controlStrings.noContext;

        if(filterString.length > 0)
            filterString += dividerString;

        filterString += controlStrings.context + ': ' + filterBannerContextName;
    }

    if (filterBannerTagNames != 'all')
    {
        var regex = new RegExp('notag');
        filterBannerTagNames = filterBannerTagNames.replace(regex,controlStrings.noTags);

        if(filterString.length > 0)
            filterString += dividerString;

        filterString += taskStrings.tags + ': ' + filterBannerTagNames;
    }

    var pageAssignedUserName = doc.getElementById('assignFilterName').value;
    if(pageAssignedUserName != 'all')
    {
        if(filterString.length > 0)
            filterString += dividerString;

        filterString += labels.assigned_to+ ': ' + pageAssignedUserName;
    }

    if(showCompletedTasks)
    {
        if(filterString.length > 0)
            filterString += dividerString;

        filterString += labels.completed;
    }

	if (filterString.length > 0)
	{
		displayFilterBanner(filterString);

		doc.getElementById('filter_banner_clear_button').setAttribute('onclick', 'clearBannerFilterAndLoad()');
	}
};

function displayFilterBanner(html)
{
	var doc = document;
	var bannerElement = doc.getElementById('filter_banner');
	var bannerLabel = doc.getElementById('filter_banner_label');

	bannerLabel.innerHTML = html;
	bannerElement.style.display = "block";

	//style elements around banner
	var toolbarEl = doc.getElementById('task_toolbar');
	var tasksEl = doc.getElementById('task_sections_wrapper');

	tasksEl.style.marginTop = '117px';
	toolbarEl.style.marginTop = '32px';
};

//*********************
// ~Multi edit
//*********************
var isMultiEditModeOn = false;
var selectedTasks = new Array();
var lastSelectedTaskId = '';
var lastSelectedYCoord = '';
var lastSelectedPointer = '';
var lastSelectedPointerCoord = '';
var isShiftSelecting = false;
var isCommandSelecting = false;


// ! Multi-edit
function selectTask(taskId, ev, recurcive)
{
	//stopEventPropogation(ev);
    if (ev) {
        if (ev.detail == 0) {
            return false;
        }
        ev.preventDefault();
    }
	var doc = document;

    //shift multi selection case
    if (ev)
    {
    	if (ev.metaKey == 1 || ev.ctrlKey == 1) //command or control multi selection
    	{
	    	isCommandSelecting = true;
	    	isShiftSelecting = false;
            lastSelectedYCoord = ev.clientY;
    	}
		else if (ev.shiftKey == 1 && lastSelectedTaskId != '' && isShiftSelecting == false) //shift multi selection
		{
			isShiftSelecting = true;
			isCommandSelecting = false;
            var all_tasks = jQuery('.task_wrap');
            for(i = 0; selectedTasks.length > i; i++){
                if (selectedTasks[i] !== lastSelectedPointer) {
                    var tmpContainer = doc.getElementById(selectedTasks[i]);
                    tmpContainer.style.backgroundColor = '';
                }
            }
            selectedTasks = [lastSelectedPointer];
			var sectionTasks = new Array();
			var anchorTaskId = lastSelectedPointer;
			var anchorYCoord = lastSelectedPointerCoord;

            all_tasks.each(function(){
				var currentTaskId = jQuery(this).attr('id');
				if(currentTaskId.length == 36) //GUID length
					sectionTasks.push(currentTaskId);
            });

			var anchorIndex = sectionTasks.indexOf(anchorTaskId);
			var currentIndex = sectionTasks.indexOf(taskId);

			if (anchorYCoord > ev.clientY)
			{
				for (var i = currentIndex+1; i < anchorIndex; i++)
				{
					selectTask(sectionTasks[i], ev, true);
				}
			}
			else
			{
				for (var i = anchorIndex+1; i < currentIndex; i++)
				{
					selectTask(sectionTasks[i], ev, true);
				}
			}

			isShiftSelecting = false;
		}
		else //single selection
		{
			//isCommandSelecting = false;
			//isShiftSelecting = false;

			//unselect all selected tasks
			if (!isShiftSelecting)
			{
				unselectAllSelectedTasks();
			}

			lastSelectedTaskId = taskId;
			lastSelectedYCoord = ev.clientY;
		}
	}



	//select or unselect task based on current task's state

		if (selectedTasks.indexOf(taskId) < 0) //if taskId is not already in selectedTasks
			selectedTasks.push(taskId);
		var taskContainer = doc.getElementById(taskId);
        if (ev && ev.shiftKey == 0) {
            if (recurcive !== true) {
                lastSelectedPointer = taskId;
                lastSelectedPointerCoord = ev.clientY;
            }
        }
		taskContainer.style.backgroundColor = 'rgba(181, 213, 255, 0.3)';//"rgba(128,128,128,0.1)";
        if (parseInt(jQuery('#' + taskId).attr('tasktype')) === 1) {
            jQuery('.create_task_wrapper .task-types span:nth-child(2)').addClass('disabled').removeClass('active');
            jQuery('.create_task_wrapper .task-types span').removeClass('active');
            jQuery('.create_task_wrapper .task-types span:nth-child(1)').addClass('active');
            jQuery('#task-type-project').prop('disabled', true);
            jQuery('#task-type-default').prop('checked', true);
        } else if (parseInt(jQuery('#' + taskId).attr('tasktype')) === 7 || ((parseInt(jQuery('#' + taskId).attr('tasktype')) !== 7) && jQuery('#' + taskId).attr('parentid'))) {
            jQuery('.create_task_wrapper .task-types span:nth-child(2), .create_task_wrapper .task-types span:nth-child(3)').addClass('disabled').removeClass('active');
            jQuery('.create_task_wrapper .task-types span').removeClass('active');
            jQuery('.create_task_wrapper .task-types span:nth-child(1)').addClass('active');
            jQuery('#task-type-project, #task-type-checklist').prop('disabled', true);
            jQuery('#task-type-default').prop('checked', true);
        } else {
            jQuery('.create_task_wrapper .task-types span:nth-child(2), .create_task_wrapper .task-types span:nth-child(3)').removeClass('disabled');
            jQuery('#task-type-project, #task-type-checklist').prop('disabled', false);
        }


	//enableActionButtons();
	//updateMultiEditActionsButton();
};


function unselectTask(event, taskIndex)
{
	if(event)
		stopEventPropogation(event);
	var doc = document;
	var taskContainer = document.getElementById(selectedTasks[taskIndex]);
	selectedTasks.splice(taskIndex, 1);

	try
    {
        if(taskContainer.getAttribute('iscompleted') == 'true')
            taskContainer.setAttribute('style', 'color:gray');
        else
            taskContainer.setAttribute('style', '');
    }//taskContainer.style.backgroundColor = "transparent";}
	catch(e){
    }

	lastSelectedTaskId = ''; //this is needed for the multi-selection functionality


	//update actions button
	//updateMultiEditActionsButton();
	//shouldDisableActionButtons();
};

function unselectAllSelectedTasks()
{
	for(var i = selectedTasks.length - 1; i >= 0; i--)
		unselectTask(null, i);
    jQuery('.create_task_wrapper .task-types span').removeClass('disabled');
    jQuery('#task-type-project, #task-type-checklist').prop('disabled', false);
};

function enableActionButtons()
{
	var doc = document;
	var deleteButton = doc.getElementById('delete_button');
	var toolbarButtons = [deleteButton];

	for (var i = 0; i < toolbarButtons.length; i++)
	{
		toolbarButtons[i].setAttribute('class', 'button');
	}
};

function shouldDisableActionButtons()
{
	if (selectedTasks.length == 0)
	{
		var doc = document;
		var deleteButton = doc.getElementById('delete_button');
		var toolbarButtons = [deleteButton];

		for (var i = 0; i < toolbarButtons.length; i++)
		{
			toolbarButtons[i].setAttribute('class', 'button disabled');
		}
	}
};
//***********************
// ~multi editing actions
//***********************
function showMultiEditActions(event)
{
	if(selectedTasks.length > 0) //isMultiEditModeOn &&
	{
		var actionsPicker = document.getElementById('multi_edit_actions_flyout');
		var actionsPickerBackground = document.getElementById('multi_edit_actions_background');

		if (actionsPicker.style.display == "block")
		{
			hideListPicker(event,taskId);
		}
		else
		{
			actionsPicker.style.display = "block";
			actionsPickerBackground.style.height = "100%";
			actionsPickerBackground.style.width = "100%";
			scrollUpViewport(event);
		}
	}
};

/*
function hideMultiEditActions()
{
	//var doc = document;

	//var actionsPicker = doc.getElementById('multi_edit_actions_flyout');
	//var actionsPickerBackground = doc.getElementById('multi_edit_actions_background');
	//var actionsButton = doc.getElementById('multi_edit_actions');

	//actionsPicker.style.display = "none";
	//actionsPickerBackground.style.height = "0px";
	//actionsPickerBackground.style.width = "0px";
};
*/

function multi_edit_move_to_list()
{
	var listOptions = document.getElementsByName('multi_edit_move_to_list_options');
	var listId = {};

	//find selected list
	for (var i = 0; i < listOptions.length; i++)
	{
		if (listOptions[i].checked)
		{
			listId = listOptions[i].value;
			listColor = listOptions[i].getAttribute('listcolor');
		}
	}

	if(listId != null)
	{
		for (var i = 0; i < selectedTasks.length; i++)
		{
			updateListForTask(null, selectedTasks[i],listId, listColor);
		}

	//	hideModalWindow();
	//	hideMultiEditActions();
		cancelMultiEditListSelection();
	}
	else
		displayGlobalErrorMessage(labels.no_list_was_selected);
};

function multi_edit_assign_context()
{
	var contextOptions = document.getElementsByName('multi_edit_assign_context');
	var contextId = null;

	//find selected context
	for (var i = 0; i < contextOptions.length; i++)
	{
		if (contextOptions[i].checked)
			contextId = contextOptions[i].value;
	}

	for (var i = 0; i < selectedTasks.length; i++)
	{
		updateContextForTask(null, selectedTasks[i], contextId);
	}

	hideModalContainer();
	//hideMultiEditActions();
};

function multi_edit_add_tag()
{
    var tagOptions = document.getElementsByName('tags_picker_checkboxes_multi_edit');

    for(var i = 0; i < tagOptions.length; i++)
    {
        if(tagOptions[i].checked)
        {
            var tagId = tagOptions[i].value;
            for (var j = 0; j < selectedTasks.length; j++)
            {
                addTagToTask(selectedTasks[j], tagId);
            }
        }
    }

    hideModalContainer();
    //hideMultiEditActions();
};

function multi_edit_set__priority()
{
	var priorityOptions = document.getElementsByName('multi_edit_set_priority');
	var priorityString = '';

	//find selected priority
	for (var i = 0; i < priorityOptions.length; i++)
	{
		if (priorityOptions[i].checked)
			priorityString = priorityOptions[i].value;
	}

	for (var i = 0; i < selectedTasks.length; i++)
	{
		updatePriorityForTask(priorityString, selectedTasks[i]);
	}

	hideModalContainer();
	//hideMultiEditActions();
};

/*
function multi_edit_schedule_due_date()
{
	for (var i = 0; i < selectedTasks.length; i++)
	{
		updateTaskDueDate(selectedTasks[i], false, datepicker.unix, true);
	}

	cancelMultiEditListSelection();
	//hideMultiEditActions();
};
*/

function multi_edit_delete()
{
    var tasks_ids = {'tasks':[], 'subtasks':[]};
	for (var i = 0; i < selectedTasks.length; i++)
	{
		var taskId = selectedTasks[i];
		var doc = document;
		var taskEl = doc.getElementById(taskId);
		var parentId = taskEl.getAttribute('parentId');

		if ( parentId != null)
		{
			var parentEl = doc.getElementById(parentId);
			var parentType = parseInt(parentEl.getAttribute('tasktype'), 10);

            if (parentType == 7) {
                tasks_ids.subtasks.push(taskId);
            }
            else {
                tasks_ids.tasks.push(taskId);
            }
        } else {
            tasks_ids.tasks.push(taskId);
        }
    }
    deleteTask(tasks_ids);

	//clear selectedTasks array
	selectedTasks = new Array();

	cancelMultiEditListSelection();
	//hideMultiEditActions();
	//updateMultiEditActionsButton();
	//toggleMultiEditMode();
};

function updateMultiEditActionsButton()
{
	var doc = document;
	var count = selectedTasks.length;
	var button = doc.getElementById('multi_edit_actions');

	if (count < 1)
	{
		button.setAttribute('class', 'multi_edit_actions');
	}
	else
	{
		button.setAttribute('class', 'multi_edit_actions multi_edit_actions_on_state');
	}

	doc.getElementById('multi_edit_count').innerHTML = '(' + count + ')';
};


//*********************
// ~Task
//*********************

//var viewType = "list";//document.getElementById('viewType').value;
var userId = document.getElementById('userId').value;

var listid = '';

var taskOffset = 0;
var compOffset = 0;
var taskLimit = 10;
var compLimit = 10;

var curTaskFilter = "";
var curSearchString = "";



function getMoreTasks()
{
    if(curSearchString.length > 0)
    {
        getTasksForSearchString(curSearchString);
    }
    else if(showCompletedTasks)
    {
        getMoreCompletedTasks(20);
    }
    else
    {
        // Because of the fancy queries to get projects if the subtasks match it's
        // possible to get the project returned in multiple sections.  This will ensure
        // that the project is only added on the first matching section
        var displayedTaskIds = {};

        //var sectionArray = [];
        //sectionArray = document.getElementsByName('task_section_header');
        //sectionArray = document.getElementsByTagName('UL');

        var sections = taskSections;

        for(var i = 0; i < sections.length; i++)
        {
            getMoreTasksForSectionID(sections[i] + '_tasks_container', i, displayedTaskIds);
        }
    }
};

// ! Tasks

//buildTasksHTMLForSection function
//purpose: creates the HTML output that will go in a section header
//parameter: a JSON object with tasks objects, the section id of the HTML element to which the tasks will be appended
function getTaskHTML(taskJSON, isSubtask)
{
	try
	{
		var taskHTML  = '';
		var currentListId = '';

		try	{ currentListId = document.getElementById('listId').value;}
		catch(err){currentListId = 'all';};

		var displaySecondRowLeft = false;
		var displaySecondRowRight = false;

		var taskId = taskJSON.taskid;
		var taskName = taskJSON.name;
		var startDate = parseInt(taskJSON.startdate, 10);
		var childStartDate = typeof(taskJSON.childstartdate) == 'undefined' ? 0 : parseInt(taskJSON.childstartdate, 10);
		var taskDueDate = parseInt(taskJSON.duedate, 10);
		var taskHasDueTime = typeof(taskJSON.duedatehastime) == 'undefined' ? 0 : taskJSON.duedatehastime ? 1 : 0;
		var childDueDate = parseInt(taskJSON.childduedate, 10);
		var childDueDateHasTime = parseInt(taskJSON.childduedatehastime, 10);
		var listId = taskJSON.listid;
		var listName = htmlEntities(taskJSON.listname);
		var listColor = taskJSON.listcolor;
	    var tagsCount = typeof(taskJSON.tagscount) == 'undefined' ? 0 : taskJSON.tagscount;

	    var tagString = typeof(taskJSON.tags) == 'undefined' ? '' : taskJSON.tags;
	    	//tagString = tagString.replace(/,/g,', ');

		var contextId = taskJSON.contextid;
		var contextName = taskJSON.contextname;
		var completionDate = taskJSON.completiondate;
		var note = taskJSON.note;
		var priority = taskJSON.priority;
		var repeat = taskJSON.recurrence_type;
        if(typeof(repeat) == 'undefined')
            repeat = 0;
		var advancedRepeat = typeof(taskJSON.advanced_recurrence_string) == 'undefined' ? '' : taskJSON.advanced_recurrence_string;
        if(advancedRepeat)
            advancedRepeat = trim(advancedRepeat);

	    var typeData = typeof(taskJSON.type_data) == 'undefined' ? '' : taskJSON.type_data;
	    var taskType = taskJSON.task_type;
	    var taskTypeClass = '';
		var taskTypeDisplay = '';
		var alertCount = taskJSON.notificationscount;

		var locationAlertType = taskJSON.location_alert_type;
		var locationAlertAddress = taskJSON.location_alert_address;

		var parentId = taskJSON.parentid;
		var starred = taskJSON.starred;

		if (starred == false)
			starred = 0;

		var assignedUserId = taskJSON.assigneduserid;
		var assignedUserName = taskJSON.assigned_username;
		var commentCount = parseInt(taskJSON.commentcount, 10);
		var taskitosCount = parseInt(taskJSON.taskitocount, 10);
		var subtasksCount = parseInt(taskJSON.subtaskcount, 10);
		var subtasksToggleDisplay = '';
		var startDateLabel = '';
		var dueDateLabel = '';
		var dueDateClass = '';
		var subtaskStartDateLabel ='';
		var subtaskDatesDashDisplay= 'display:none';
		var subtaskDueDateLabel = '';
		var subtasksLabel = '';
		var childrenCount = 0;
		var timeLabel = '';
		var isSubtaskValue = 'false';

		var checkboxOnclick = 'completeTask(event,\'' + taskId + '\', null)';

		var taskWrapClass= 'task_wrap';
		var priorityClass = '';
		var starDisplay = '';
		var assignedClass = '';
		var assignedDisplay = '';
		var datesDashDisplay = '';
		var startDateDisplay = '';
		var dueDateDisplay = '';
		var dueTimeClass = '';
		var subtaskDatesDisplay = '';
		var commentsCount = typeof(taskJSON.commentcount) == 'undefined' ? 0 : taskJSON.commentcount;
		var commentsDisplay = '';
		var noteClass = '';
		var contextDisplay = '';
		var subtasksClass = '';
		var completedStyle = '';
		var alertDisplay = '';
		var locationAlertClass = '';
		var locationAlertDisplay = '';
		var checkboxClass = '';
		var dueTimeLabel = '';
		var dueTimeDisplay = '';
		var displaySubtasksClass = '';
		var badgeCountDisplay = '';
		var tagsDisplay = '';
		var noteDisplay = '';
		var specialTaskAction = '';

		var secondRowLeftDisplay = '';
		var secondRowRightDisplay = '';
		var taskDateHtml = '';
		var additionalSubtaskProperties = '';
		var isCompleted = false;
		var taskOrder = 0;

		var todayMidnightUnix = new Date();
			todayMidnightUnix.setHours(0,0,0,0);
			todayMidnightUnix = parseInt(todayMidnightUnix.getTime() / 1000, 10);

		var todayEndOfDayUnix = new Date();
			todayEndOfDayUnix.setHours(23,59,59,999);
			todayEndOfDayUnix = parseInt(todayEndOfDayUnix.getTime()/1000, 10);

		/*check values and set classes to determine icon/property visibility*/

		//list color
		var listColorStyle =  'background-color:rgba(' + listColor + ', .8);';

		//due date
		if(typeof(taskDueDate) == 'undefined')
			taskDueDate = 0;

		if (taskDueDate != 0)
		{
			dueDateLabel = displayHumanReadableDate(taskDueDate, false, true, true);
			dueDateDisplay = 'display:inline-block';
			displaySecondRowLeft = true;

			//determine if task is overdue
			if (taskHasDueTime == 1 && taskDueDate < parseInt((new Date()).getTime() / 1000, 10))
			{
				 dueDateClass += ' red';
				 dueTimeClass += ' red';
			}
			else
			{
				if (taskDueDate < todayMidnightUnix)
					dueDateClass += ' red';
			}
		}

        if(listName === 'Inbox'){
            listName = controlStrings.inbox;
        }

		//due time
		if(taskHasDueTime == 1)
		{
			//displayDueTime = true;
			dueTimeDisplay = 'display:inline-block';
		}

		//child due date
		if (isNaN(childDueDate))
			childDueDate = 0;

		if (isNaN(childDueDateHasTime))
			childDueDateHasTime = 0;

		if (subtasksCount > 0 && childDueDate != 0)
		{
			if (taskDueDate == 0)
			{
				if (childDueDate == 0)
				{
					;
				}
				else
				{
					if (childDueDateHasTime == 0)
					{
						subtaskDueDateLabel = displayHumanReadableDate(childDueDate, false, true, true);
					}
					else if (childDueDateHasTime == 1)
					{
						subtaskDueDateLabel = displayHumanReadableDate(childDueDate, false, true, true) + ', ' + displayHumanReadableTime(childDueDate);
					}
				}
			}
			else if (taskDueDate > 0 && taskHasDueTime == 0)
			{
				if (childDueDate == 0)
				{
					;
				}
				else
				{
					if (childDueDateHasTime == 0)
					{
						if (childDueDate < taskDueDate)
							subtaskDueDateLabel = displayHumanReadableDate(childDueDate, false, true, true);
					}
					else if (childDueDateHasTime == 1)
					{
						if (childDueDate < taskDueDate)
							subtaskDueDateLabel = displayHumanReadableDate(childDueDate, false, true, true) + ', ' + displayHumanReadableTime(childDueDate);
					}
				}
			}
			else if (taskDueDate > 0 && taskHasDueTime == 1)
			{
				if (childDueDate == 0)
				{
					;
				}
				else
				{
					if (childDueDateHasTime == 0)
					{
						if (childDueDate < taskDueDate)
							subtaskDueDateLabel = displayHumanReadableDate(childDueDate, false, true, true);
					}
					else if (childDueDateHasTime == 1)
					{
						if (childDueDate < taskDueDate)
							subtaskDueDateLabel = displayHumanReadableDate(childDueDate, false, true, true) + ', ' + displayHumanReadableTime(childDueDate);
					}
				}
			}

			//determine if 'No date' needs to be displayed for task
			if (taskDueDate != 0)
			{
				dueDateLabel = displayHumanReadableDate(taskDueDate, false, true, true);
				dueDateDisplay = 'display:inline-block';
			}

		}


		if (subtaskDueDateLabel.length > 0)
		{
			subtaskDatesDisplay = 'display:inline-block';
			displaySecondRowLeft = true;
		}
			//determine if childDueDate is overdue
			/*
if (childDueDate < todayMidnightUnix)
			{
				dueDateClass += ' red';
				dueTimeClass += ' red';
			}
*/

		//start date
		if(typeof(startDate) == 'undefined')
			startDate = 0;
		else if (shouldDisplayStartDate(startDate, taskDueDate) && subtaskStartDateLabel.length == 0)
		{
			startDateLabel = displayHumanReadableDate(startDate, false, true, true);
			startDateDisplay = 'display:inline-block';
			displaySecondRowLeft = true;

			if (dueDateLabel.length > 0)
				datesDashDisplay = parseInt(taskDueDate,10) != 0 && parseInt(startDate, 10) != 0 ? 'display:inline-block' : '';
		}

		//child start date
		if (childStartDate!=0)
		{
			if (childStartDate < startDate || (childStartDate > 0 && startDate == 0))
			{
				subtaskStartDateLabel = displayHumanReadableDate(childStartDate, false, true, true);
				subtaskDatesDisplay = 'display:inline-block';
				displaySecondRowLeft = true;
			}
		}
		//due date adjustment based on start date
		if (subtaskStartDateLabel.length > 0)
		{
			dueDateLabel = displayHumanReadableDate(taskDueDate, false, true, true);
			dueDateDisplay = 'display:inline-block';
			displaySecondRowLeft = true;
		}

		//subtasks dates dash
		if (subtaskDueDateLabel.length > 0 && subtaskStartDateLabel.length > 0)
			subtaskDatesDashDisplay = 'display:inline-block';

		//task assignment
		if (typeof(assignedUserId) == 'undefined' && typeof(assignedUserName) == 'undefined')
		{
			assignedUserId = 'none';
			assignedUserName = labels.none;
		}
		else
		{
			assignedClass = 'task_assignee';
			assignedDisplay = 'display:inline-block';
			displaySecondRowRight = true;
		}

		//comment
		if (typeof(commentCount) == 'undefined' || isNaN(commentCount))
		{
			;
		}
		else
		{
	    	commentsDisplay = 'display:inline-block';
	    	displaySecondRowRight = true;
		}

		//tags
		var tagsClass = '';

	    if(tagsCount > 0)
	    {
	        tagsDisplay = 'display:inline-block';
	        displaySecondRowRight = true;
	    }
	    else
	        tagsCount = 0;

	    //type

	    if(typeof(taskType) == 'undefined' || isNaN(taskType))
	    {
	        taskType = 0;

	    }
	    else
	    {
            taskTypeDisplay = 'display:inline-block;';

            if (taskType == 7 || taskType == 1) {
                switch (taskType) {
                    case 1:
                        taskTypeClass = ' project';
                        subtasksToggleDisplay = 'display:inline-block';
                        break;
                    case 7:
                        taskTypeClass = ' checklist';
                        subtasksToggleDisplay = 'display:inline-block';
                        break;
                    default:
                        break;

                }
            } else if (typeData.length) {
                var taskTypeDataArray = typeData.split('\n');
                switch (taskType) {
                    case 1:
                        taskTypeClass = ' project';
                        subtasksToggleDisplay = 'display:inline-block';
                        break;
                    case 2:
                        taskTypeClass = ' call';
                        break;
                    case 3:
                        taskTypeClass = ' sms';
                        break;
                    case 4:
                        taskTypeClass = ' email';
                        specialTaskAction = ' onclick="showTaskQuickActions(\'' + taskId + '\')" ';
                        if (taskTypeDataArray[1].replace('contact: ', '').length > 1) {
                            taskTypeDisplay += 'z-index:4';
                        }
                        else {
                            taskTypeDisplay = 'display:none;';
                        }
                        break;
                    case 5:
                        taskTypeClass = ' location';
                        specialTaskAction = ' onclick="showTaskQuickActions(\'' + taskId + '\')" ';
                        if (taskTypeDataArray[1].replace('contact: ', '').length > 1) {
                            taskTypeDisplay += 'z-index:4';
                        }
                        else {
                            taskTypeDisplay = 'display:none;';
                        }
                        break;
                    case 6:
                        taskTypeClass = ' website';
                        specialTaskAction = ' onclick="showTaskQuickActions(\'' + taskId + '\')" ';
                        if (taskTypeDataArray[1].replace('contact: ', '').length > 1) {
                            taskTypeDisplay += 'z-index:4';
                        }
                        else {
                            taskTypeDisplay = 'display:none;';
                        }
                        break;
                    case 7:
                        taskTypeClass = ' checklist';
                        subtasksToggleDisplay = 'display:inline-block';
                        break;
                    default:
                        break;

                }
            }
	    }



	    var typeTitle = titleForTaskType(taskType);

		//note
		if (typeof(note) == 'undefined')
			note = '';
		else
		{
			noteDisplay = 'display:inline-block';
			displaySecondRowRight = true;
		}

		//task context
		if (typeof(contextId) == 'undefined' && typeof(contextName) == 'undefined')
		{
			contextId = 0;
			contextName = controlStrings.noContext;
		}
		else
		{
			//contextClass = 'task_context';
			contextDisplay = 'display:inline-block';
		}


		//task priority
		if (typeof(priority) != 'undefined')
		{
			priority = parseInt(priority, 10);

			switch(priority)
			{
				case 1:
					priorityClass += ' high';
					break;
				case 5:
					priorityClass += ' med';
					break;
				case 9:
					priorityClass += ' low';
					break;
				default:
					priorityClass += ' none';
			}

			if (priority != 0)
				priorityDisplay = 'display:inline-block';
		}
		else
			priority = 0;

		//subtasks
		if (!isNaN(subtasksCount))
		{
			childrenCount = subtasksCount;
		}

		if (!isNaN(taskitosCount))
		{
			childrenCount = taskitosCount;
		}

		if(taskType == 7 || taskType == 1)
		{
			checkboxOnclick = 'confirmProjectChecklistCompletion(event, \'' + taskId + '\', \'' + taskType+  '\')';

	    }

	    if (childrenCount > 0)
	    	badgeCountDisplay = 'display: inline-block';

		//star status
		if(starred == 1)
		{
			starClass = 'starClass ';
			//priorityDisplay = 'display:block;';
			starDisplay = 'display:inline-block';
		}

		//alert
		if(typeof(alertCount) != 'undefined')
		{
			alertDisplay = 'display:inline-block';
			displaySecondRowLeft = true;
		}
		else
			alertCount = 0;

		//location alert
		if (typeof(locationAlertType) == 'undefined')
		{
			locationAlertAddress = '';
			locationAlertType = 0;
		}
		else
		{
			locationAlertDisplay = 'display:inline-block';
			displaySecondRowLeft = true;
		}

		//isSubtaskValue
		if (isSubtask)
		{
			isSubtaskValue = 'true';
			additionalSubtaskProperties = 'parentid="' + parentId + '"';
		}

		//repeat
		var repeatStyle = 'display:none;';
        if(typeof(repeat) != 'undefined')
        {
            var repeatVal = parseInt(repeat, 10);
            if(repeatVal != 0 && repeatVal != 100 && repeatVal != 9 && repeatVal != 109)
            {
                repeatStyle = 'display:inline-block';
                displaySecondRowLeft = true;
            }
        }

		//completion status
		if(typeof(completionDate) != 'undefined')
		{
			completedStyle = ' color:gray';
			checkboxClass = 'checked';
			checkboxOnclick = 'uncompleteTask(event,\'' + taskId + '\')';
			dueDateLabel = displayHumanReadableDate(completionDate, false, true, true);
			dueTimeLabel = displayHumanReadableTime(completionDate);
			dueDateDisplay = 'display:inline-block';
			dueTimeDisplay = 'display:inline-block';

            dueDateClass = '';
            dueTimeClass = '';

			displaySecondRowLeft = true;
			isCompleted = true;
			taskWrapClass += ' completed';
		}
		else
		{
			completionDate = 0;
			dueTimeLabel = displayHumanReadableTime(taskDueDate);
		}
        if (taskJSON.sort_order) {
            taskOrder = taskJSON.sort_order;
        }

		if(displaySecondRowLeft)
			secondRowLeftDisplay = 'show-left-second-row';
		if(displaySecondRowRight)
			secondRowRightDisplay = 'show-right-second-row';

		//build task html
		taskHTML += '<div style="' + completedStyle + '" id="' + taskId + '" class="' + taskWrapClass + '" iscompleted="' + isCompleted + '" completiondate="' + completionDate + '" assigneename="' + assignedUserName + '" assigneeid="' + assignedUserId + '" activesubtasks="' + childrenCount + '" hasSubtasksOpen="false" isSubtask="' + isSubtaskValue + '" name="' + htmlEntities(taskName) + '" origListId="'+ listId +'" listname="' + listName + '" commentscount="' + commentsCount + '" origContextId="'+ contextId +'" contextname="' + htmlEntities(contextName) + '" starred="' + starred + '" repeat="'+ repeat +'" advrepeat="'+ advancedRepeat + '" alertcount="' + alertCount +'" locationalertaddress="' + locationAlertAddress + '" locationalerttype="' + locationAlertType + '" tasktype="' + taskType + '" tasktypedata="' + typeData +'" tagCount="'+ tagsCount +'" tags="' + tagString + '"  priority="' + priority + '" startdate="' + startDate +'"  duedate="' + taskDueDate + '"  hasduetime="' + taskHasDueTime + '" childstartdate="' + childStartDate + '" childduedate="' + childDueDate + '" childduedatehastime="' + childDueDateHasTime+ '" ' + additionalSubtaskProperties + ' listcolor="' + listColor + '" taskorder="' + taskOrder + '">';

		if (isSubtask)
			taskHTML += '	<input id="project_subtask_' + taskId +'" class="project_subtask_' + parentId +'" type="hidden" parentid="' + parentId + '" container_id="' + taskId + '" subtask_id="'+ taskId + '" subtask_completed="' + isCompleted + '">';

		//drag N drop containers
		taskHTML += '	<div id="insert_above_target_' + taskId + '" class="drop_target above" name="insertAboveTargets" ondragenter="aboveTaskDragEnter(event)" ondragover="return false" ondragleave="aboveTaskDragLeave(event)" ondrop="catchTaskDropAbove(event)" ></div>';
		taskHTML += '	<div id="insert_below_target_' + taskId + '" class="drop_target below" name="insertBelowTargets" ondragenter="belowTaskDragEnter(event)" ondragover="return false" ondragleave="belowTaskDragLeave(event)" ondrop="catchTaskDropBelow(event)" ></div>';
		taskHTML += '	<a id="insert_inside_target_' + taskId + '" class="drop_target inside" name="insertTargets"  ondblclick="displayTaskEditor(\'' + taskId + '\')" onclick="selectTask(\'' + taskId + '\', event)" draggable="true" ondragstart="taskDragStart(event, \'' + taskId + '\')" ondragover="return false" ondragleave="insideTaskDragLeave(event)" ondrop="catchTaskDropInside(event)" ondragenter="insideTaskDragEnter(event)" href="#" ></a>';

		//pop up editor
		//taskHTML += '	<div style="position:absolute;left:25%;width:20px;height:20px;background:gold">***</div>';
		taskHTML += '	<div class="property_flyout task_editor" id="task_editor_' + taskId + '"></div>';



		taskHTML += '    <div class="task_left_properties">';
							//subtasks
		taskHTML += '		  <div id="task_subtasks_toggle_' + taskId +'"  class="task_editor_icon task_subtasks_toggle off" onclick="toggleTaskSubtasksDisplay(event, \'' + taskId + '\')" style="' + subtasksToggleDisplay + '" ></div>';
							//checkbox
		taskHTML += '		  <div id="checkbox_' + taskId + '" class="task_checkbox ' + checkboxClass + '" onclick="' + checkboxOnclick + '"></div>';
							//color dot
		taskHTML += '    	<div id="list_color_dot_' + taskId + '" class="list_color_dot" style="' + listColorStyle + '" title="' + taskStrings.list + '" ></div>';
		taskHTML += '    </div>'; // end float left

		
		var rightDetailsStyle = '';
		if( taskType == 1 || taskType == 7 ){
			rightDetailsStyle += 'background: rgba(' + listColor + ', 0.2); ';
		}
		taskHTML += ' <div class="task_right_properties ' + secondRowLeftDisplay + ' ' + secondRowRightDisplay + '" style="' + rightDetailsStyle + '">';

		//taskHTML += '	<div class="task_top_properties" style="' + padTopRowIfNoSecondRow + '">';
		taskHTML += '	<div class="task_top_properties left-part">';
							//task name
		taskHTML += '	 	<div id="task_name_' + taskId + '" class="task_name">' + htmlEntities(taskName) + '</div>';
							//task type
		taskHTML += '		<div id="task_type_' + taskId + '" class="task_editor_icon task_type ' + taskTypeClass + '" style="' + taskTypeDisplay + '" ' + specialTaskAction + '></div>';
		taskHTML += '		<div id="task_quick_actions_flyout_' + taskId + '" class="property_flyout task_actions_flyout"></div>';
		taskHTML += '	</div>';

		taskHTML += '	<div class="task_top_properties right-part">';
							//priority
		taskHTML += '		<div id="task_priority_' + taskId + '" class="task_editor_icon task_priority ' + priorityClass + '" title="' + taskStrings.priority + '" ></div>';
							//star
		taskHTML += '		<div id="task_star_' + taskId + '" class="task_editor_icon task_star" style="' + starDisplay + '" ></div>';
							//subtasks badge count
		taskHTML += '		<div id="subtask_badge_count_' + taskId + '" class="badge_count_wrapper " style="">';
		taskHTML += 			'<span class="active" id="active_badge_count_' + taskId + '" style="' + badgeCountDisplay + '">' + childrenCount + '</span>';
		taskHTML += '		</div>';
							//context
		taskHTML += '		<div id="task_context_' + taskId + '" class="task_context" style="' + contextDisplay + '" >' + contextName + '</div>';
		taskHTML += '	</div>';

		taskHTML += '	<div class="task_bottom_properties left-part">';
							//start date
		taskHTML += '		<div id="task_start_date_' + taskId + '" class="task_start_date" style="' + startDateDisplay +'">' + startDateLabel + '</div>';
							//dash
		taskHTML += '		<span id="task_dates_dash_' + taskId + '" class="task_dates_dash" style="' + datesDashDisplay + '"> - </span>';
							//due date
		taskHTML += '		<div id="task_due_date_' + taskId + '" class="task_due_date ' + dueDateClass + '" style="' + dueDateDisplay +'">' + dueDateLabel + '</div>';
							//due time
		taskHTML += '		<div id="task_due_time_' + taskId + '" class="task_due_time ' + dueTimeClass + '" style="' + dueTimeDisplay + '">' + dueTimeLabel + '</div>';
							//subtask start and due date
		taskHTML += '		<div id="subtask_dates_' + taskId + '" class="subtask_due_date" style="' + subtaskDatesDisplay +'">(<span id="subtask_s_date_' + taskId + '" class="task_start_date">' + subtaskStartDateLabel + '</span><span id="subtask_dates_dash_' + taskId + '" class="subtask_dates_dash" style="' + subtaskDatesDashDisplay + '"> - </span><span id="subtask_d_date_' + taskId + '">' + subtaskDueDateLabel + '</span>)</div>';
							//repeat
    taskHTML += '       <div class="task_repeat task_editor_icon" style="' + repeatStyle + '" id="repeat_icon_' + taskId + '" title="' + taskStrings.repeatInterval + '" ></div>';
    					//alert
		taskHTML += '     	<div class="task_alert  task_editor_icon" style="' + alertDisplay + '" id="alert_icon_' + taskId + '" title="' + alertStrings.taskAlerts + '" ></div>';
							//location alert
		taskHTML += '     	<div class="task_location  task_editor_icon" style="' + locationAlertDisplay + '" id="location_alert_' + taskId + '"  title="' + alertStrings.locationAlert + '" ></div>';
		taskHTML += '</div>';

		taskHTML += '	<div class="task_bottom_properties right-part">';

		//assignee
		taskHTML += '		<span class="task_assignee" style="' + assignedDisplay  + '" id="task_assignee_' + taskId + '" >' + assignedUserName +' </span>';
							//note
		taskHTML += '     	<div class="task_editor_icon task_note" style="' + noteDisplay + '" id="note_icon_' + taskId + '" ></div>';
							//comments
		taskHTML += '     	<div class="task_editor_icon task_comments" style="' + commentsDisplay + '" id="comment_icon_' + taskId + '" ></div>';
							//tags
		taskHTML += '		<div id="task_tags_' + taskId + '" class="task_tags" style="' + tagsDisplay + '">' + tagString + '</div>';
		taskHTML += '	</div>';
	    taskHTML += '	<div id="task_note_p_' + taskId + '" class="task_note_p">' + note + '</div>';
	    taskHTML += '	<textarea id="task_original_note_' + taskId + '" tabindex="-1" class="task_original_note" disabled="disabled">' + note + '</textarea>';
		taskHTML += '</div>';
		taskHTML += '    </div>'; // end float left

	                 //subtasks container
		taskHTML += '<div id="subtasks_wrapper_' + taskId + '" style="display:none;" class="subtasks_wrapper">';
		taskHTML += '    <div class="task_subtasks_container" id="subtasks_' + taskId + '" issubtask="false">';
		taskHTML += '    </div>'; //end task_subtasks_container
		taskHTML += '</div>'; //end subtasks_wrapper


		return taskHTML;
	}
	catch (err)
	{
		//console.log(err);
	}
};
function shouldDisplayStartDate(startDate, dueDate)
{
	startDate = parseInt(startDate, 10);
	dueDate = parseInt(dueDate, 10);

	var result = false;

	if (startDate != 0)
	{
		var todayEndOfDayUnix = new Date();
			todayEndOfDayUnix.setHours(23,59,59,999);
			todayEndOfDayUnix = parseInt(todayEndOfDayUnix.getTime()/1000, 10);

		var todayStartOfDayUnix = new Date();
			todayStartOfDayUnix.setHours(0,0,0,0);
			todayStartOfDayUnix = parseInt(todayStartOfDayUnix.getTime()/1000, 10);

		var startDateStartOfDayUnix = new Date (startDate * 1000);
			startDateStartOfDayUnix.setHours(0,0,0,0);
			startDateStartOfDayUnix = parseInt(startDateStartOfDayUnix.getTime()/1000, 10);

		var startDateEndOfDayUnix = new Date(startDate * 1000);
			startDateEndOfDayUnix.setHours(23,59,59,999);
			startDateEndOfDayUnix = parseInt(startDateEndOfDayUnix.getTime()/1000, 10);

		var dueDateStartOfDayUnix = new Date(dueDate * 1000);
			dueDateStartOfDayUnix.setHours(0,0,0,0);
			dueDateStartOfDayUnix = parseInt(dueDateStartOfDayUnix.getTime()/1000, 10);

		var dueDateEndOfDayUnix = new Date(dueDate * 1000);
			dueDateEndOfDayUnix.setHours(23,59,59,999);
			dueDateEndOfDayUnix = parseInt(dueDateEndOfDayUnix.getTime()/1000, 10);

		if (dueDateStartOfDayUnix > startDateStartOfDayUnix/*  && startDateStartOfDayUnix >= todayStartOfDayUnix */)
			result = true;
		else if (dueDate == 0 && startDate != 0)
			result = true;
	}

	return result;
};

function showTaskQuickActions(taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var flyoutEl = doc.getElementById('task_quick_actions_flyout_' + taskId);
		flyoutEl.style.display = 'inline-block';


	 var taskTypeData = taskEl.getAttribute('tasktypedata');
	 var taskType = parseInt(taskEl.getAttribute('tasktype'), 10);

    //quick actions menu

    if(taskType == 4 || taskType == 6 || taskType == 5)
    {

	    var specialTaskActionsHTML = '';
    	if (taskTypeData.indexOf('Task Type') == -1 ) //new format is NOT found format old data into new data
		{
			var typeDataString = '';
			var valueDescription = 'other:';
			switch (taskType)
			{
				case 2://sms
					typeDataString = 'Call';
					break;
				case 3: //call
					typeDataString = 'SMS';
					break;
				case 4: //email
					typeDataString = 'Email';
					break;
				case 5://address
					typeDataString = 'Location';
					valueDescription = 'location:';
					break;
				case 6://website
					typeDataString = 'URL';
					valueDescription = 'url:';
					break;
				case 0:
				case 1:
				case 7:
					break;
			}

			var newTaskTypeDataValue = taskTypeData;

			taskTypeData ='';
			taskTypeData += '---- Task Type: ' + typeDataString + ' ---- \n';
			taskTypeData += 'contact: ' + newTaskTypeDataValue + ' \n';
			taskTypeData += valueDescription + ' ' + newTaskTypeDataValue + ' \n';
			taskTypeData += '---- End Task Type ---- \n';
		}

		var taskTypeAction = '';
		var taskTypeDataArray = taskTypeData.split('\n');

		taskTypeDataLabel = taskTypeDataArray[1].replace('contact: ', '');

		specialTaskActionsHTML += '<label>' + taskTypeDataLabel + '</label>';
		specialTaskActionsHTML += '<hr style="margin: 6px auto"/>';

		for (var i = 0; i < taskTypeDataArray.length; i++)
		{
			if (taskTypeDataArray[i].indexOf('---- Task Type:') == -1 && taskTypeDataArray[i].indexOf('contact:')== -1 && taskTypeDataArray[i].indexOf('---- End Task Type ----')== -1)
			{
				var actionElArray = [];

				switch(taskType)
				{
					case 4: //email
						actionElArray = taskTypeDataArray[i].split(' ');
						taskTypeAction = 'parent.location=\'mailto:' + actionElArray[1] + '\'';
						break;
					case 5: //location
						taskTypeAction = 'window.open(\'http://maps.google.com/maps?q=' + taskTypeDataArray[i].replace('location: ', '') + '\')';
						break;
					case 6: //URL
						var url = taskTypeDataArray[i].replace('url: ', '');
                        if (url.indexOf('http://') == -1 && url.indexOf('https://') == -1) {
                            url = 'http://' + url;
                        }
						taskTypeAction = 'window.open(\'' + url + '\')';
						break;
					default:
						break;
				}
                if(taskTypeDataArray[i].trim() !== '') {
                    specialTaskActionsHTML += '<div class="picker_option" onclick="' + taskTypeAction + '">' + taskTypeDataArray[i] + '</div>';
                }
			}
		}


		flyoutEl.innerHTML = specialTaskActionsHTML;
    }

	//set up clickaway event
	var dismissQuickActions = function(event){hideTaskQuickActions(event,taskId);};
	pushWindowClickEvent(dismissQuickActions);
};

function hideTaskQuickActions(event, taskId)
{
	var doc = document;
	var flyoutEl = doc.getElementById('task_quick_actions_flyout_' + taskId);

	if (event == null)
	{
	  flyoutEl.setAttribute('style', '');
	  popWindowClickEvent();
	}
	else
	{
	  var eventTarget = event.target;

	  if (eventTarget != doc.getElementById('task_type_' + taskId))
	  {
	  	flyoutEl.setAttribute('style', '');
	  	popWindowClickEvent();
	  }
	}
};

function updateDateUIForTask(taskId, viaDragNDrop)
{
    var taskEl = document.getElementById(taskId);
    var task_entry = jQuery("#" + taskId);
	var task_editor = jQuery("#task_priority_" + taskId);

    //console.log(task_entry);

    var duedate = taskEl.getAttribute('duedate');
    var startdate = taskEl.getAttribute('startdate');
    var duedatehastime = taskEl.getAttribute('hasduetime');
    var iscompleted = taskEl.getAttribute('iscompleted') == "true";

    var dueDateEl = document.getElementById('task_due_date_' + taskId);
    var dueTimeEl = document.getElementById('task_due_time_' + taskId);
    var dashEl = document.getElementById('task_dates_dash_' + taskId);

    if(iscompleted)
    {
		task_editor.removeClass("task_editor_center");

        var timestamp = taskEl.getAttribute('completiondate');
        dueDateEl.style.display = 'inline-block';
        dueTimeEl.style.display = 'inline-block';

        dueDateEl.innerHTML = displayHumanReadableDate(timestamp, false, true, true);
        dueTimeEl.innerHTML = displayHumanReadableTime(timestamp);

        dueDateEl.setAttribute('class', 'task_due_date');
        dueTimeEl.setAttribute('class', 'task_due_time');

    }
    else
    {
        if(duedate == 0)
        {
			task_editor.addClass("task_editor_center");

			//console.log(task_editor);
			//console.log(taskId);

            dueDateEl.setAttribute('style', '');
            dueDateEl.innerHTML = '';
            dueTimeEl.setAttribute('style', '');
            dueTimeEl.innerHTML = '';
            dashEl.setAttribute('style', '');
        }
        else
        {
			task_editor.removeClass("task_editor_center");
            
            dueDateEl.style.display = 'inline-block';
            if(startdate > 0) {
                dashEl.style.display = 'inline-block';
            }else{
                dashEl.style.display = 'none';
            }
            dueDateEl.innerHTML = displayHumanReadableDate(duedate, false, true, true);

            if (duedatehastime == '1')
            {
                dueTimeEl.style.display = 'inline-block';
                dueTimeEl.innerHTML = displayHumanReadableTime(duedate);

                if(duedate  < parseInt((new Date()).getTime() / 1000, 10))
                {
                    dueTimeEl.setAttribute('class', 'task_due_time red');
                    dueDateEl.setAttribute('class', 'task_due_date red');
                }
                else
                {
                    dueTimeEl.setAttribute('class', 'task_due_time');
                    dueDateEl.setAttribute('class', 'task_due_date');
                }
            }
            else
            {
                dueTimeEl.setAttribute('style', '');
                dueTimeEl.innerHTML = '';

                var todayMidnight = new Date();
				todayMidnight.setHours(0,0,0,0);

				if (duedate < parseInt(todayMidnight.getTime() / 1000, 10))
				{
					dueDateEl.setAttribute('class', 'task_due_date red');
				}
				else
				{
                    dueDateEl.setAttribute('class', 'task_due_date');
				}
			}
        }
    }
    shouldDisplaySecondRow(taskId);
    taskListRowsClass();
    if (!viaDragNDrop) {
        liveTaskSort();
    } else {
        window.dragged_tasks_updated_count++;
        if (window.dragged_tasks_count == window.dragged_tasks_updated_count) {
            window.dragged_tasks_updated_count = 0;
            liveTaskSort();
        }
    }

}

function shouldDisplaySecondRow(taskId)
{
	var doc = document;
	var taskEl = jQuery('#' + taskId);

    if (taskEl.attr('parentid')) {
        var parentEl = jQuery('#' + taskEl.attr('parentid'));
        if (parseInt(parentEl.attr('tasktype')) === 7 && taskEl.hasClass('taskito')) {
            return false;
        }
    }

	var prop_container = jQuery('.task_right_properties', taskEl);
	var prop_top_left = jQuery('.task_top_properties.left-part', prop_container);
	var prop_top_right = jQuery('.task_top_properties.right-part', prop_container);
	var prop_bottom_left = jQuery('.task_bottom_properties.left-part', prop_container);
	var prop_bottom_right = jQuery('.task_bottom_properties.right-part', prop_container);

	var show_top_left = true;
	var show_top_right = true;
	var show_bottom_left = false;
	var show_bottom_right = false;


	var bottomRowEl = doc.getElementById('bottom_properties_' + taskId);

	var startDate = parseInt(taskEl.attr('startdate'));
	var childDueDate = parseInt(taskEl.attr('childduedate'), 10);
	var dueDate = parseInt(taskEl.attr('duedate'));
	var repeat = parseInt(taskEl.attr('repeat'));
	var alertCount = parseInt(taskEl.attr('alertcount'), 10);
	var locationAlertType = parseInt(taskEl.attr('locationalerttype'), 10);

	var tagsCount = parseInt(taskEl.attr('tagcount'), 10);
	var commentCount = parseInt(taskEl.attr('commentscount'), 10);
	var note = doc.getElementById('task_original_note_' + taskId).value;
	var assignee = taskEl.attr('assigneeid');
	var iscompleted = taskEl.attr('iscompleted') == "true";

	var subtaskStartDateLength = doc.getElementById('subtask_s_date_' + taskId).innerHTML.length;
	var subtaskDueDateLength = doc.getElementById('subtask_d_date_' + taskId).innerHTML.length;
    if (startDate || childDueDate || dueDate || (repeat && repeat !== 9 ) || alertCount || locationAlertType) {
        prop_container.addClass('show-left-second-row');
    } else {
        prop_container.removeClass('show-left-second-row');
    }
    if (tagsCount || commentCount || note !== '' || assignee || iscompleted || subtaskStartDateLength || subtaskDueDateLength) {
        prop_container.addClass('show-right-second-row');
    } else {
        prop_container.removeClass('show-right-second-row');
    }
};


function searchTasks(searchTextFieldId)
{
    var searchText = document.getElementById(searchTextFieldId).value;

    searchText = searchText.trim();
    if(searchText.length == 0)
    {
        endSearch(true);
        return;
    }

    //Uncomment this code to move the search bar
//    document.getElementById('hiddenSearchRow').style.display = "table-row";
//
//    var hiddenSearchField = document.getElementById('hiddenSearchField');
//    hiddenSearchField.value = searchText;
//    hiddenSearchField.focus();
//    hiddenSearchField.setSelectionRange(searchText.length, searchText.length);
//
//    document.getElementById('task_toolbar').style.display = "none";
//    document.getElementById('task_assign_filter_wrapper').style.display = "none";

    //Hide the filter banner
    var bannerElement = document.getElementById('filter_banner');
    bannerElement.style.display = "none";
    var taskToolbarElement = document.getElementById('task_toolbar');
    taskToolbarElement.style.marginTop = "0";
    var taskSectionsWrapperElement = document.getElementById('task_sections_wrapper');
    taskSectionsWrapperElement.style.marginTop = "";

    //document.getElementById('newTaskNameField').setAttribute('disabled', 'disabled');
    
    curSearchString = searchText;
	loadTaskSectionHeaders(3);
	getMoreTasks();

}

function endSearch(reloadTasks)
{
    curSearchString = "";

    //Uncomment this code if moving the search bar
//    document.getElementById('hiddenSearchRow').style.display = "none";
//    document.getElementById('task_toolbar').style.display = "block";
//    document.getElementById('task_assign_filter_wrapper').style.display = "block";

    //show the filter banner if needed
    setFilterBanner();
    try
    {
   		 document.getElementById('newTaskNameField').removeAttribute('disabled');
   		 
   		 document.getElementById('searchField').value = "";
   	}
   	catch(err){}

    if(reloadTasks)
    {
        window.preocessed_sections = 0;
        loadTaskSectionHeaders(pageSortOrder);
        getMoreTasks();
    }
}



function getTasksForSearchString(searchString)
{
 	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

	//Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            if(ajaxRequest.responseText != "")
			{
                try
                {
                    var response = JSON.parse(ajaxRequest.responseText);

                    if(response.success = true)
                    {
                        var sectionEl = document.getElementById('search_tasks_container');
                        var tasksHTML = '';
                        var tasksJSON = response.tasks;
                        if(tasksJSON)
                        {
                            var tasksCount = tasksJSON.length;

                            for (var i = 0; i < tasksCount; i++)
                            {
                                tasksHTML += getTaskHTML(tasksJSON[i]);
                            }
                        }
                        else
                        {
                            tasksHTML = labels.no_results_to_display;
                        }
                        sectionEl.parentNode.parentNode.setAttribute("style", "display:block;");
                        sectionEl.innerHTML = tasksHTML;
                        taskListRowsClass();

                    }
                    else if(response.success == false && response.error=="authentication")
                    {
                        //make the user log in again
                        history.go(0);
                        return;
                    }
                    else
                    {
                        if(response.error)
                            displayGlobalErrorMessage(response.error);
                        else
                            displayGlobalErrorMessage(labels.unable_to_get_search_results);
                    }
                }
                catch(e)
                {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }
			}
		}
	}
    params = "method=getSearchTasks&searchstring=" + searchString;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);

}

function getMoreTasksForSectionID(sectionId, sectionIndex, displayedTaskIds)
{
	var doc = document;

	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;

    if(doc.getElementById('show_more_tasks_div'))
        doc.getElementById('show_more_tasks_div').innerHTML = "<img src='https://s3.amazonaws.com/static.plunkboard.com/gifs/ajax-loader.gif'>";

	var listid = "";

	//if (viewType == "list")
		listid = doc.getElementById('listid').value;
	//else if (viewType == "dashboard")
	//	listid = doc.getElementById('defaultlistid').value;

	//Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            if(doc.getElementById('show_more_tasks_div'))
            {
                doc.getElementById("show_more_tasks_div").innerHTML = labels.show_more;
            }

            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);

                if(response.success == true && response.tasks)
                {
                    var sectionEl = doc.getElementById(sectionId);
                    var sectionWrapperEl = doc.getElementById(sectionId + '_wrap');
                    var tasksHTML = '';
                    var tasksJSON = response.tasks;
                    var tasksCount = tasksJSON.length;

                    for (var i = 0; i < tasksCount; i++)
                    {
                        var taskid = tasksJSON[i].taskid;
                        if(displayedTaskIds[taskid])
                        {
                            //If this task is already displayed in an earlier section, skip it
                            var index = displayedTaskIds[taskid].sectionIndex;
                            var displayedSectionId = displayedTaskIds[taskid].section;
                            if(index <= sectionIndex)
                                continue;
                            else
                            {
                                //If this task is displayed in a later section, remove it and add to this section
                                var element = document.getElementById(taskid);
                                var parentNode = element.parentNode;
                                parentNode.removeChild(element);
                                if(parentNode.childNodes.length == 0)
                                    document.getElementById(displayedSectionId).parentNode.style.display = "none";

                            }
                        }

                        displayedTaskIds[taskid] = {"sectionIndex":sectionIndex, "section":sectionId};

                        tasksHTML += getTaskHTML(tasksJSON[i]);
                    }

                    if(tasksHTML.length > 0)
                    	sectionWrapperEl.style.display = 'block';

                        //sectionEl.parentNode.setAttribute("style", "display:block;");

                    sectionEl.innerHTML = tasksHTML;

                    showTask(taskitoIdToHighlight, taskIdToHighlight, parentIdToHighlight, false);


                    //buildTasksHTMLForSection(tasksJSON, sectionId);

                    taskOffset = taskOffset+taskLimit;
                    liveTaskSort(++window.preocessed_sections);
                    taskListRowsClass();
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }
                        else if (response.error == labels.you_do_not_have_permission_to_view_this_list ) {
                            window.location.reload();
                        }
                        else {
                            displayGlobalErrorMessage(response.error);
                        }
                    }
                    else
                        displayGlobalErrorMessage(labels.unable_to_load_tasks);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
    var params = '';

	//if (viewType == "list")
	 	params = "method=getSectionTasks&section_id=" + sectionId + "&listid=" + listid + "&offset=" + taskOffset + "&limit=" + taskLimit;
	//else if(viewType ="dashboard")
	//	params = "method=getSectionTasks&section_id=" + sectionId + "&contextid=" + contextid + tagString + "&listid=all&offset=" + taskOffset + "&limit=" + taskLimit;

	if (showCompletedTasks && sectionId == 'completed_tasks_container')
		params += '&completed=true';

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function uncompleteTask(event, taskId)
{
    if(event)
    	stopEventPropogation(event);

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
                    var taskEl = document.getElementById(taskId);
                    taskEl.setAttribute('completiondate', 0);
                    taskEl.setAttribute('iscompleted', 'false');
                    updateDateUIForTask(taskId);

                    setUIUncompletedForTask(taskId, true);
                    loadNextNotificationForCurrentUser();

                    if (isTaskEditorOpen(taskId) && useNewTaskEditor)
                    {
                        var taskEditorCheckbox = document.getElementById('task_editor_checkbox_' + taskId);
                        	taskEditorCheckbox.setAttribute('class', 'icon checkbox');
                        	taskEditorCheckbox.setAttribute('onclick', 'completeTask(event, \'' + taskId + '\', null)');

                        loadTaskEditorProperties(taskId, 1);
                    }
                    if (isTaskSubtask(taskId))
                    {
                        var parentId = taskEl.getAttribute('parentid');
                        var parentEl = document.getElementById(parentId);
                        if (parentEl.getAttribute('iscompleted') == 'true') {
                            uncompleteTask(false, parentId);
                        }
                    }
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
                        displayGlobalErrorMessage(labels.unable_to_update_task);
                    }
                }
            }
            catch(e)
            {
                if( e.message != 'Unexpected end of input') {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }
            }
		}
	}

	var unixTimestamp = 0;
	var params = "method=completeTask&taskId=" + taskId + "&completiondate=" + unixTimestamp;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};



function deleteTask(tasks_ids)
{
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;
    var doc = document;
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
                    if (response.affected_tasks.length > 0) {
                        for (var i = 0; i < response.affected_tasks.length; i++) {
                            var taskId = response.affected_tasks[i];
                            var taskEl = doc.getElementById(taskId);

                            if (isTaskSubtask(taskId))
                            {
                                var parentId = taskEl.getAttribute('parentid');
                                var parentEl = doc.getElementById(parentId);

                                // update parent badge count
                                if (taskEl.getAttribute('iscompleted') == 'false')
                                {
                                    var currentActiveSubtasks = parseInt(parentEl.getAttribute('activesubtasks'), 10);
                                    var newCount = currentActiveSubtasks - 1;
                                    parentEl.setAttribute('activesubtasks', newCount);
                                    doc.getElementById('active_badge_count_' + parentId).innerHTML = newCount;

                                    if (newCount == 0)
                                        doc.getElementById('active_badge_count_' + parentId).setAttribute('style', '');
                                }
                            }

                            /* updateParentChildDueDateTime(taskId); */

                            //update task that is about to be deleted so that the parent subtasks dates can be properly updated before the task is removed from UI
                            taskEl.setAttribute('duedate', 0);
                            taskEl.setAttribute('hasduetime', 0);
                            taskEl.setAttribute('startdate', 0);

                            if (isTaskSubtask(taskId))
                                setupSubtaskDatesUI(taskId);

                            if (taskEl.getAttribute('childduedate').length != 0)
                                setupSubtaskDatesUI(taskId, true);

                            //remove task from UI
                            taskEl.parentNode.removeChild(taskEl);

                            //remove children if checklist or project
                            var subtasksEl = doc.getElementById('subtasks_wrapper_' + taskId);
                            subtasksEl.parentNode.removeChild(subtasksEl);
                        }
                    }
                    if (response.affected_subtasks.length > 0){
                        for (i = 0; i < response.affected_subtasks.length; i++) {
                            var taskId = response.affected_subtasks[i];
                            var taskEl = doc.getElementById(taskId);
                            if(taskEl){

                            var parentId = taskEl.getAttribute('parentid');
                            var taskContainer = doc.getElementById(taskId);

                            taskContainer.parentNode.removeChild(taskContainer);

                            //update parent badge count
                            var parentEl = doc.getElementById(parentId);
                            if(parentEl) {
                                var currentActiveSubtasks = parseInt(parentEl.getAttribute('activesubtasks'), 10);
                                var newCount = currentActiveSubtasks - 1;
                                parentEl.setAttribute('activesubtasks', newCount);
                                doc.getElementById('active_badge_count_' + parentId).innerHTML = newCount;
                            }
                            if (newCount == 0)
                                doc.getElementById('active_badge_count_' + parentId).setAttribute('style', '');
                            }
                        }

                    }
                    setTimeout(function () {
                        loadTagsControl();
                        loadListsControl();
                        liveTaskSort();
                        hideModalContainer();
                    }, 0);
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
                        displayGlobalErrorMessage(labels.unable_to_update_task);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=deleteTask&tasks=" + tasks_ids.tasks + "&taskitos=" + tasks_ids.subtasks;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function confirmProjectChecklistCompletion(event, taskId, parentType)
{
	var bodyHTML = parentType == 7 ? labels.completing_this_checklist_will_also_complete_all_of_its_subtasks : labels.completing_this_project_will_also_complete_all_of_its_subtasks ;
	var headerHTML = parentType == 7 ? labels.confirm_checklist_completion : labels.confirm_project_completion ;
	var footerHTML = '';


	footerHTML += '<div class="button" id="confirm_Project_Checklist_Completion_Ok_Button">' + labels.ok + '</div>';
	footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.cancel + '</div>';

	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	document.getElementById('confirm_Project_Checklist_Completion_Ok_Button').onclick = function() {hideModalContainer();completeTask(event, taskId, null);};
};

function completeTask(event, taskId, unixTimestamp)
{
    if(event)
    	stopEventPropogation(event);

	var doc = document;

	var taskEl = doc.getElementById(taskId);
    var checkboxEl = doc.getElementById('checkbox_'+ taskId);

    //don't allow the user to uncomplete the task while we're processing the recurrence
    checkboxEl.setAttribute("onclick", "");

    if(!unixTimestamp)
        unixTimestamp = Math.round(+new Date()/1000);

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
                if(response.success == true)
                {
                    var hasRecurrence = false;
                    var newDueDateTimestamp = 0;
                    var newDueDateString = "";
                    var newStartDateTimestamp = 0;

                    var taskType = taskEl.getAttribute('tasktype');

                    if(response.recurrence)
                    {
                        hasRecurrence = true;
                        if (parseInt(response.dueDateTimestamp) > 0) {
                            newDueDateTimestamp = parseInt(response.dueDateTimestamp);
                        }
                        if (parseInt(response.startDateTimestamp) > 0) {
                            newStartDateTimestamp = parseInt(response.startDateTimestamp);
                        }
                        var hasDueTime = false;
                        if(response.dueDateHasTime)
                            hasDueTime = true;
                        if (newDueDateTimestamp > 0) {
                            newDueDateString = displayHumanReadableDate(newDueDateTimestamp, hasDueTime, true, true);
                        }
                    }

                    if(hasRecurrence == false) //handle non recurring tasks
                    {
                        if(taskType == 1)
                            loadSubtasksForTaskId(taskId);

                        document.getElementById(taskId).setAttribute('completiondate', unixTimestamp);
                        document.getElementById(taskId).setAttribute('iscompleted', 'true');
                        
                        updateDateUIForTask(taskId);
                        
                        //reflect completion in UI
                        setUICompletedForTask(taskId, !hasRecurrence);
                       	setUICompletedForSubtasksOfTask(taskId, taskId, !hasRecurrence);
                    }
                    else  //handle recurring tasks
                    {
                    	var parentId = taskEl.getAttribute('parentid');

                		//insert completed task UI below original repeating task
	    	           	//if (parentType != 1)
	    	           	var subtasksEl = doc.getElementById('subtasks_wrapper_' + taskId);
	    	           	var completedTask = response.completedTask;
	    	           	var completedTaskHtml = getTaskHTML(completedTask);
	    	           	var completedTaskEl = createFragment(completedTaskHtml);
	    	           	taskEl.parentNode.insertBefore(completedTaskEl, subtasksEl.nextSibling);
                        taskListRowsClass();

                    	if(parentId)
                    	{
	                    	//console.log('task is a child');
                    	}
                    	else
                    	{
	                    	//insert completed task UI in place or original repeating task

	                    	//move original task to New section
	                    	taskEl.parentNode.removeChild(taskEl);
	                    	subtasksEl.parentNode.removeChild(subtasksEl);

	                    	doc.getElementById('new__tasks_container_wrap').style.display = 'block';
	                    	var tasksContainer = doc.getElementById('new__tasks_container');

	                    	if (tasksContainer.innerHTML.length == 0)
	                    	{
	                    		tasksContainer.appendChild(taskEl);//+ subtasksEl;
	                    		tasksContainer.appendChild(subtasksEl)
	                    	}
	                    	else
	                    	{
		           				var firstChild = tasksContainer.firstChild;

	                    		firstChild.parentNode.insertBefore(subtasksEl, firstChild);
	                    		subtasksEl.parentNode.insertBefore(taskEl, subtasksEl);
	                    	}
                    	}

                    	if (taskEl.getAttribute('hassubtasksopen') == 'true')
                    			loadSubtasksForTaskId(taskId);

                    	setTimeout("setUIUncompletedForTask('" + taskId + "', true)", 500);
                        if (newDueDateTimestamp > 0) {
                            setTaskDueDateValues(newDueDateTimestamp, newDueDateString, taskId);
                        }
                        if (newStartDateTimestamp > 0) {
                            setTaskStartDateValues(newStartDateTimestamp, taskId);
                        }
                        if(taskType == 1)
                            setTimeout("loadSubtasksForTaskId('" + taskId + "')", 500);
                        else
                            setTimeout("setUIUncompletedForSubtasksOfTask('" + taskId + "', '" + taskId + "', true)", 500);
                        liveTaskSort();
                    }


                    if (isTaskEditorOpen(taskId) && useNewTaskEditor)
                    {
                        var taskEditorCheckbox = doc.getElementById('task_editor_checkbox_' + taskId);
                        	taskEditorCheckbox.setAttribute('class', 'icon checkbox checked');
                        	taskEditorCheckbox.setAttribute('onclick', 'uncompleteTask(event, \'' + taskId + '\')');

                        loadTaskEditorProperties(taskId, 1);
                    }
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
                        displayGlobalErrorMessage(labels.unable_to_update_task);
                        doc.getElementById('checkbox_' + taskId).setAttribute("onclick", "completeTask(event,'" + taskId + "', null)");
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                doc.getElementById('checkbox_'+ taskId).setAttribute("onclick", "completeTask(event,'" + taskId + ", null)");
            }
		}
	}


	var params = "method=completeTask&taskId=" + taskId + "&completiondate=" + unixTimestamp;

	ajaxRequest.open("POST", ".", false);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function setUICompletedForTask(taskId, setOnclickAction)
{
	var doc = document;

    doc.getElementById('checkbox_' + taskId).setAttribute('class', 'task_checkbox  checked');
    doc.getElementById(taskId).setAttribute('class', 'task_wrap completed');

    if(setOnclickAction)
    {
		doc.getElementById('checkbox_' + taskId).setAttribute("onclick", "uncompleteTask(event,'" + taskId + "')");
    }

    var isSubtask = (doc.getElementById("subtasks_" + taskId).getAttribute("isSubtask") == "true");
    if(isSubtask)
    {
        doc.getElementById('project_subtask_' + taskId).setAttribute("subtask_completed", "true");
    }
};

function setUIUncompletedForTask(taskId, setOnclickAction)
{
	var doc = document;

    doc.getElementById('checkbox_' + taskId).setAttribute('class', 'task_checkbox');
    doc.getElementById(taskId).setAttribute('class', 'task_wrap');

    if(setOnclickAction)
    {
        var taskEl = doc.getElementById(taskId);
		var taskType = parseInt(taskEl.getAttribute('tasktype'), 10);

		switch (taskType)
		{
			case 1:
				doc.getElementById('checkbox_' + taskId).setAttribute("onclick", "confirmProjectChecklistCompletion(event,'" + taskId + "', 1)");
				break;
			case 7:
				doc.getElementById('checkbox_' + taskId).setAttribute("onclick", "confirmProjectChecklistCompletion(event,'" + taskId + "', 7)");
				break;
			default:
				doc.getElementById('checkbox_' + taskId).setAttribute("onclick", "completeTask(event,'" + taskId + "', null)");
				break;
		}

    }

    var isSubtask = (doc.getElementById("subtasks_" + taskId).getAttribute("isSubtask") == "true");
    if(isSubtask)
    {
        doc.getElementById('project_subtask_' + taskId).setAttribute("subtask_completed", "false");
    }
}



function createTask()
{
    //olny one creating task in one moment
    if (window.create_task_precessing === false) {
        window.create_task_precessing = true;
    } else {
        return false;
    }
	var doc = document;
	var task_type = 0;
	var newTaskProgressIndicator = doc.getElementById('create_task_progress_indicator');
	var newTaskInput = doc.getElementById('newTaskNameField');


	if (selectedTasks.length == 1) {
        var taskId = selectedTasks[0];
        var taskEl = doc.getElementById(taskId);
        var taskType = parseInt(taskEl.getAttribute('tasktype'), 10);
        if ((taskType == 7 || taskType == 1 && taskEl.getAttribute('parentid') == null)) {

            if (taskType == 7 /*checklist*/ || taskType == 1 /*project*/) {
                addSubtask(taskId);
                return;
            }
            else if (taskType == 0 && taskEl.getAttribute('parentid') != null) {
                var parentId = taskEl.getAttribute('parentid');
                addSubtask(parentId);
                return;
            }
        }
    }

	var taskName = newTaskInput.value.trim();

    var listid = doc.getElementById('listid').value;
    task_type = 0;
	if(jQuery('.create_task_wrapper .task-types input[name="task-type"]').size() > 0) {
		task_type = jQuery('.create_task_wrapper .task-types input[name="task-type"]:checked').val()
	}
	if(taskName.length < 1)
	{
        window.create_task_precessing = false;
		return;
	}

	newTaskProgressIndicator.setAttribute('style', 'display:inline-block');
	newTaskInput.setAttribute('placeholder', '      ' + labels.creating_task);
	
	newTaskInput.setAttribute('disabled', 'disabled');

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
				var responseJSON = JSON.parse(ajaxRequest.responseText);

				if (responseJSON.success == true)
				{
                    var current_listid = doc.getElementById("listId");
                    if (
                        !current_listid ||
                            (
                                current_listid &&
                                (
                                    (responseJSON.task.listid === current_listid.value || current_listid.value === 'all' || current_listid.value === 'starred' || current_listid.value === 'focus')
                                    ||
                                    (userSettings.focusShowUndueTasks === 1 && current_listid.value === 'focus')
                                )
                            )
                        ) {
                        var sectionElement = doc.getElementById('new__tasks_container');
                        sectionElement.parentNode.parentNode.setAttribute("style", "display:block;");
                        sectionElement.parentNode.parentNode.className = 'tasks_container_wrap';
                        sectionElement.innerHTML = getTaskHTML(responseJSON.task) + sectionElement.innerHTML;
                    }
					doc.getElementById('newTaskNameField').value = "";
                    taskListRowsClass();

                    //If a new tag was added, we should reload the tags control
                    if(responseJSON.task.tags)
                    {
                        loadTagsControl();
                    }
                    if(responseJSON.task.contextname)
                    {
                        loadContextsControl();
                    }

                    //update Task Count in list
                    setTimeout(function () {
                        loadListsControl();
                        // liveTaskSort();
                    }, 0);
                    //displayTaskInfo(null, responseJSON.task.taskid);
				}
			}
			else
			{
				displayGlobalErrorMessage(labels.unable_to_add_task);
			}

			newTaskProgressIndicator.setAttribute('style', '');
            newTaskInput.setAttribute('placeholder', labels.create_a_new_task);
			newTaskInput.removeAttribute('disabled');
			doc.getElementById('newTaskNameField').focus();
		}
        setTimeout(function () {
            window.create_task_precessing = false;
        }, 400);
	}

	var params = "method=addTask&taskName=" +  encodeURIComponent(taskName) + "&listid=" + listid+"&task_type="+task_type;

	switch(curTaskFilter)
	{
		case "mine":
			userId = doc.getElementById('userId').value;
			params = params.concat("&assigned_user=" + userId);
			break;
		default:
			break;
	}

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


//confirmTaskDeletion function
//purpose: prompts user to confirm a task deletion via modal window
//parameters: the id of the task that will be deleted (string), the name of the task that will be deleted (string)
function confirmTaskDeletion(event,taskId, taskName)
{
    if(event)
    	stopEventPropogation(event);
	var doc = document;

    var header = taskStrings.deleteTask;
    var body = labels.are_you_sure_you_want_to_delete_the_task + ' "' + taskName + '"?';

    var footer = '<div class="button" onclick="deleteTaskAndDismissModal(\'' + taskId + '\', \'' + taskId + '\')">'+labels.delete+'</div>';
    footer += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';


	displayModalContainer(body, header, footer);


};

//deleteTaskAndDismissModal function
//purpose: deletes a task and dismisses the modal window
//parameters: the id of the task that will be deleted (string), the id of the HTML element that contains the task to be deleted
function deleteTaskAndDismissModal(taskId, containerId)
{
	deleteTask(taskId);
	hideModalContainer();
};


//************************
// ~subtasks and ~taskitos
//************************
function loadSubtasksForTaskId(taskId)
{
    loadSubtasksForTaskIdAndEditSubtask(taskId, null)
}
function loadSubtasksForTaskIdAndEditSubtask(taskId, subtaskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var toggleEl = doc.getElementById('task_subtasks_toggle_' + taskId);

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
				var responseJSON = JSON.parse(ajaxRequest.responseText);

				if (responseJSON.success == true)
				{
					var subtasksBody = doc.getElementById("subtasks_" + taskId);
                    var taskType = taskEl.getAttribute('tasktype');
					var subtasksHTML = '';

					if (taskType == 1)/*project*/
					{
						//load subtasks
						var subtasksJSON = responseJSON.subtasks;
						var subtasksCount = subtasksJSON.length;

						for (var i = 0; i < subtasksCount; i++)
							subtasksHTML += getTaskHTML(subtasksJSON[i], true);
					}
					else /*checklist*/
					{
						//load taskitos
						var taskitosJSON = responseJSON.taskitos;
						var taskitosCount = taskitosJSON.length;

						for (var i = 0; i < taskitosCount; i++)
							subtasksHTML += getTaskitoHTML(taskitosJSON[i]);
					}

					subtasksBody.innerHTML = subtasksHTML;
                    showTask(taskitoIdToHighlight, taskIdToHighlight, parentIdToHighlight, false);

                    if(subtaskId != null)
                        displayTaskEditor(subtaskId);

                    //make new subtask input visible again
                    //var newSubtaskInput = doc.getElementById('new_subtask_' + taskId);
                    //newSubtaskInput.style.display = 'block';


				}
				else
					displayGlobalErrorMessage(labels.failed_to_retrieve_subtasks_from_server  + ': ' + ajaxRequest.responseText);
			}
		}
	}

    var starredString = "";
    var listid = "";

	//if (viewType == "list")
		listid = doc.getElementById('listid').value;
	//else if (viewType == "dashboard")
	//	listid = doc.getElementById('defaultlistid').value;

    if(listid == 'starred')
    {
        starredString = "&starred_only=1";
    }

	var params = "method=getSubtasks&taskid=" + taskId + starredString;

    //Bug 7427 - pass information about the parent task so the server can decide whether
    //it should filter the children or not
	var taskEl = document.getElementById(taskId);
    var starred = taskEl.getAttribute('starred');
    if(starred)
        params += "&parent_starred=" + starred;

    var contextid = taskEl.getAttribute('origcontextid');
    if(contextid)
        params += "&parent_context=" + contextid;

    var tags = taskEl.getAttribute('tags');
    if(tags)
        params += "&parent_tags=" + tags;

    var assignee = taskEl.getAttribute('assigneeid');
    if(assignee)
        params += "&parent_assigned_user=" + assignee;


	ajaxRequest.open("POST", ".", false);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function setUICompletedForTaskito(taskitoId)
{
	var doc = document;

    var checkboxId = 'checkbox_' + taskitoId;

    doc.getElementById(taskitoId).style.color = 'gray';
    doc.getElementById(checkboxId).setAttribute('class', 'task_checkbox checked' );
    doc.getElementById(checkboxId).setAttribute("onclick", "uncompleteTaskito(event, '" + taskitoId + "')");

    doc.getElementById('taskito_name_' + taskitoId).setAttribute("subtask_completed", "true");
};

function setUIUncompletedForTaskito(taskitoId)
{
	var doc = document;

	var checkboxId = 'checkbox_' + taskitoId;

    //doc.getElementById(checkboxId).removeAttribute("src");
    doc.getElementById(checkboxId).setAttribute('class', 'task_checkbox');
    doc.getElementById(checkboxId).setAttribute("onclick", "completeTaskito(event, '" + taskitoId + "')");
    doc.getElementById(taskitoId).setAttribute('style', '');

   	doc.getElementById('taskito_name_' + taskitoId).setAttribute("subtask_completed", "false");
};

function setUIUncompletedForSubtasksOfTask(taskId, containerId, setOnclickAction)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);

    var taskType = taskEl.getAttribute('tasktype');

    if(taskType != 1)
    {
        var subtaskArray = doc.getElementsByClassName('task_name_' + taskId);
        for (var i=0; i<subtaskArray.length; i++)
        {
            var subtaskID = subtaskArray[i].getAttribute('subtask_id');
            setUIUncompletedForChecklistItem(subtaskID, taskId);
        }
    }
    else
    {
        var subtaskArray = doc.getElementsByClassName('project_subtask_' + taskId);
        for(var i=0; i < subtaskArray.length; i++)
        {
            var id = subtaskArray[i].getAttribute('subtask_id');
            var container = subtaskArray[i].getAttribute('container_id');

            setUIUncompletedForTask(taskId, setOnclickAction);
        }
    }

    updateSubtasksTextForTask(taskId, containerId);
}

function setUICompletedForSubtasksOfTask(taskId, containerId, setOnclickAction)
{
	var doc = document;

	var taskEl = doc.getElementById(taskId);

    var taskType = taskEl.getAttribute('tasktype');

    if(taskType != 1)
    {
        var subtaskArray = jQuery('[parentid="' + taskId + '"]');
        if (subtaskArray.size()) {
            subtaskArray.each(function () {
                var el = jQuery(this);
                setUICompletedForChecklistItem(el.attr('id'), setOnclickAction);
            });
        }
    }
    else
    {
        var subtaskArray = doc.getElementsByClassName('project_subtask_' + taskId);
        for(var i=0; i < subtaskArray.length; i++)
        {
            var subtaskId = subtaskArray[i].getAttribute('subtask_id');
            var subtaskContainer = subtaskArray[i].getAttribute('container_id');
            setUICompletedForTask(subtaskId,  true);
        }
    }

    updateSubtasksTextForTask(taskId, containerId);
}

function deleteTaskito(taskId)
{
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
                if(response.success == true)
                {
                	var doc = document;

                	//remove element from UI
                	var parentId = doc.getElementById(taskId).getAttribute('parentid');
                    var taskContainer = doc.getElementById(taskId);

                    taskContainer.parentNode.removeChild(taskContainer);

                    //update parent badge count
                    var parentEl = doc.getElementById(parentId);
                	var currentActiveSubtasks = parseInt(parentEl.getAttribute('activesubtasks'), 10);
                	var newCount = currentActiveSubtasks - 1;
                	parentEl.setAttribute('activesubtasks', newCount);
                	doc.getElementById('active_badge_count_' + parentId).innerHTML = newCount;

                	if (newCount == 0)
               			doc.getElementById('active_badge_count_' + parentId).setAttribute('style', '');

                    //updateParentBadgeCount(parentId); //***
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
                        displayGlobalErrorMessage(labels.unable_to_update_task);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }


    var params = "method=deleteTaskito&taskitoId=" + taskId;

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);
};

function toggleCompletionForSubtask(subtaskID, taskId)
{
	var doc = document;

	var subtaskImg = doc.getElementById('subtask_checkbox_' + subtaskID);
	if(subtaskImg == null)
	{
		displayGlobalErrorMessage(labels.we_didnt_find_a_matching_subtask_image );
		return false;
	}
	var subtaskName = doc.getElementById('task_name_' + subtaskID);
	if(subtaskName == null)
	{
		displayGlobalErrorMessage(labels.we_didnt_find_a_matching_subtask_image );
		return false;
	}

	var subtaskCompleted = subtaskName.getAttribute('subtask_completed');
	if(subtaskCompleted == "true")
	{
        setUIUncompletedForChecklistItem(subtaskID, taskId);
	}
	else
	{
        setUICompletedForChecklistItem(subtaskID, taskId);
	}

	updateSubtasks(taskId);
}


function completeTaskito(event, taskitoId)
{
	if(event)
		stopEventPropogation(event);

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
                    setUICompletedForTaskito(taskitoId);
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
                        displayGlobalErrorMessage(response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var unixTimestamp = Math.round(+new Date()/1000);

	var params = "method=completeTaskito&taskitoId=" + taskitoId + "&completiondate=" + unixTimestamp;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}


function uncompleteTaskito(event, taskitoId)
{
	if(event)
		stopEventPropogation(event);

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
                    setUIUncompletedForTaskito(taskitoId);
                    var taskitoEl = document.getElementById(taskitoId);

                    var parentId = taskitoEl.getAttribute('parentid');
                    var parentEl = document.getElementById(parentId);
                    if (parentEl.getAttribute('iscompleted') == 'true') {
                        uncompleteTask(false, parentId);
                    }
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
                        displayGlobalErrorMessage(response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var unixTimestamp = 0;

	var params = "method=completeTaskito&taskitoId=" + taskitoId + "&completiondate=" + unixTimestamp;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}


function setUICompletedForChecklistItem(subtaskId, setOnclickAction) {
    var doc = document;
    doc.getElementById('checkbox_' + subtaskId).setAttribute('class', 'task_checkbox checked');
    doc.getElementById(subtaskId).style.color = 'gray';

    if (setOnclickAction) {
        doc.getElementById('checkbox_' + subtaskId).setAttribute("onclick", "uncompleteTaskito(event,'" + subtaskId + "')");
    }
}

function setUIUncompletedForChecklistItem(subtaskId, taskId)
{
	var doc = document;

    var subtaskImg = doc.getElementById('subtask_checkbox_' + subtaskId);
	if(subtaskImg == null)
	{
		displayGlobalErrorMessage(labels.we_didnt_find_a_matching_subtask_image);
		return false;
	}
	var subtaskName = doc.getElementById('task_name_' + subtaskId);
	if(subtaskName == null)
	{
		displayGlobalErrorMessage(labels.we_didnt_find_a_matching_subtask_image);
		return false;
	}
    subtaskName.setAttribute('subtask_completed', 'false');
    //subtaskImg.setAttribute('src', 'https://s3.amazonaws.com/static.plunkboard.com/images/task/task-checkbox-unchecked.png');
}

function updateTaskitoName(taskitoId)
{
	var doc = document;

	var taskEl = doc.getElementById(taskitoId);

	var taskName = doc.getElementById('taskito_name_' + taskitoId).value;
	var originalName = taskEl.getAttribute('name');

	taskName = taskName.trim();

	if(taskName.length < 1 || taskName == originalName)
	{
        doc.getElementById('taskito_name_' + taskitoId).value = originalName;
		return false;
	}

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
                if(response.success == true)
                {
                	var inputEl = doc.getElementById('taskito_name_'+ taskitoId);
                    //inputEl.setAttribute('disabled', 'disabled');
                    inputEl.blur();
                    inputEl.setAttribute('style', '');
                    taskEl.setAttribute('name', taskName);

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
                        displayGlobalErrorMessage(response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=updateTaskito&taskitoId=" + taskitoId + "&taskitoName=" + taskName;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}


function updateTaskitosSortOrder(taskitoId)
{
	var doc = document;

	var taskitoEl = doc.getElementById(taskitoId);
	var parentId = taskitoEl.getAttribute('parentid');
	var parentEl = doc.getElementById(parentId);
	var subtasksEl = doc.getElementById('subtasks_' + parentId);
	var subtaskEls = subtasksEl.children;

	var taskitos = [];

	for (var i = 0; i < subtaskEls.length; i++)
	{
		var taskito = {};
			taskito.taskito_id = subtaskEls[i].getAttribute('id');
			taskito.sort_order = i;

		taskitos.push(taskito);
	}

	var taskitoData = {};
		taskitoData.parent_id = parentId;
		taskitoData.taskitos = taskitos;

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
                if(response.success == true)
                {
  					//loadSubtasksForTaskId(parentId);
                }
                else
                {
                	loadSubtasksForTaskId(parentId);
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
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=updateTaskitoSortOrders&taskito_data=" + JSON.stringify(taskitoData);

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};



function getTaskitoHTML(taskitoJSON)
{
	var name = taskitoJSON.name;
	var parentId = taskitoJSON.parentid;
	var completionDate = taskitoJSON.completiondate;
	var deleted = taskitoJSON.deleted;
	var taskitoId = taskitoJSON.taskitoid;
	var taskitoTimestamp = taskitoJSON.timestamp;
	var completedStyle = '';

	var isCompleted = false;
	var checkboxClass = '';
	var checkboxOnclick = 'completeTaskito(event, \'' + taskitoId + '\')';

    var completionMethod = 'completeTaskito';

	if (completionDate > 0)
	{
		isCompleted = true;
		checkboxClass = ' checked';
		checkboxOnclick = 'uncompleteTaskito(event,\'' + taskitoId + '\')';


        completionMethod = 'uncompleteTaskito';
        completedStyle = 'color:gray';
	}
	var taskitoHTML = '';

	taskitoHTML += '	<div id="' + taskitoId + '" style="' + completedStyle + '" class="task_wrap taskito" parentid="' + parentId + '" name="' + htmlEntities(name) + '" tasktype="0" >';

	//drag N drop containers
    taskitoHTML += '	    <div id="insert_above_target_' + taskitoId + '" class="drop_target above" name="insertAboveTargets" ondragenter="aboveTaskDragEnter(event)" ondragover="return false" ondragleave="aboveTaskDragLeave(event)" ondrop="catchTaskDropAbove(event)"></div>';
	taskitoHTML += '		<div id="insert_below_target_' + taskitoId + '" class="drop_target below" name="insertBelowTargets" ondragenter="belowTaskDragEnter(event)" ondragover="return false" ondragleave="belowTaskDragLeave(event)" ondrop="catchTaskDropBelow(event)"></div>';
	taskitoHTML += '		<a href="#" id="insert_inside_target_' + taskitoId + '" class="drop_target inside" name="insertTargets"  ondblclick="startTaskitoNameEditing(\'' + taskitoId + '\')" onclick="selectTask(\'' + taskitoId + '\', event)" draggable="true" ondragstart="taskDragStart(event, \'' + taskitoId + '\')" ondragover="return false" ondragleave="insideTaskDragLeave(event)" ondrop="catchTaskDropInside(event)" ondragenter="insideTaskDragEnter(event)"></a>';

	//pop up editor
	taskitoHTML += '		<div class="property_flyout task_editor " id="task_editor_' + taskitoId + '"></div>';


	taskitoHTML += '		<div class="task_top_properties">';
	    						//checkbox
	taskitoHTML += '			<div id="checkbox_' + taskitoId + '" class="task_checkbox ' + checkboxClass + '" onclick="' + checkboxOnclick + '"></div>';
	    						//task name
	taskitoHTML += '			<input id="taskito_name_' + taskitoId + '" class="task_name" type="text" value="' + htmlEntities(name) + '" onchange="updateTaskitoName(\'' + taskitoId + '\')" onkeydown="if (event.keyCode == 13) this.onchange()"/>';
	//taskitoHTML += '	 	<div id="task_name_' + taskitoId + '" class="task_name">' + htmlEntities(name) + '</div>';
	    					//star
	//taskitoHTML += '		<div id="task_star_' + taskId + '" class="task_editor_icon task_star" style="' + starDisplay + '" ></div>';
	taskitoHTML += '		</div>';
	taskitoHTML += '	</div>';

	return taskitoHTML;
};

function startTaskitoNameEditing(taskitoId)
{
	var doc = document;
	var taskitoNameInputEl = doc.getElementById('taskito_name_' + taskitoId);
		taskitoNameInputEl.removeAttribute('disabled');
		taskitoNameInputEl.style.zIndex = '1';
		taskitoNameInputEl.focus();
		taskitoNameInputEl.select();
}
// ! Subtasks

function addSubtask(parentId)
{
	var doc = document;

	var parentEl = doc.getElementById(parentId);
	var subtaskInput = doc.getElementById('newTaskNameField');
	var newTaskName = subtaskInput.value;
	var parentTaskType = parentEl.getAttribute('tasktype');
    var task_type = 0;
	newTaskName = newTaskName.trim();

	if(newTaskName == "")
		return false;

    if(jQuery('.create_task_wrapper .task-types input[name="task-type"]').size() > 0) {
        task_type = jQuery('.create_task_wrapper .task-types input[name="task-type"]:checked').val()
    }
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
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }

            if(ajaxRequest.responseText != "")
            {
                var response = JSON.parse(ajaxRequest.responseText);

                if (response.success)
                {
                	// open subtasks if needed
                	var subtasksWrapper = doc.getElementById("subtasks_wrapper_" + parentId);

                	if (subtasksWrapper.style.display == "none")
                		toggleTaskSubtasksDisplay(null, parentId);

                	// reload subtasks
                	loadSubtasksForTaskId(parentId);

                	// update parent badge count
                	var currentActiveSubtasks = parseInt(parentEl.getAttribute('activesubtasks'), 10);
                	var newCount = currentActiveSubtasks + 1;
                	parentEl.setAttribute('activesubtasks', newCount);
                	doc.getElementById('active_badge_count_' + parentId).innerHTML = newCount;
               		doc.getElementById('active_badge_count_' + parentId).style.display = 'inline-block';

                	//clear new task input textfield
                	doc.getElementById('newTaskNameField').value = '';
                    setTimeout(function () {
                        loadListsControl();
                    }, 0);
                }
                else
                {
                    if(response.error)
                        displayGlobalErrorMessage(response.error);
                    else
                        displayGlobalErrorMessage(labels.unable_to_add_task);
                }
            }
            else
            {
                displayGlobalErrorMessage(labels.unable_to_add_task);
            }
            setTimeout(function () {
                window.create_task_precessing = false;
            }, 400);
        }
    }

    var params = '';

    if(parentTaskType == 1)
    {
        params = "method=addTask&taskName=" + encodeURIComponent(newTaskName) + "&parentid=" + parentId+"&task_type="+task_type;;

        switch(curTaskFilter)
        {
            case "mine":
                userId = doc.getElementById('userId').value;
                params = params.concat("&assigned_user=" + userId);
                break;
            default:
                break;
        }
    }
    else
    {
        params = "method=addTaskito&taskName=" + encodeURIComponent(newTaskName) + "&parentid=" + parentId+"&task_type="+task_type;;
    }

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);

};

function toggleTaskSubtasksDisplay(event, taskId)
{
	if(event)
		stopEventPropogation(event);

	var doc = document;
	var taskEl = doc.getElementById(taskId);

	var subtasksWrapper = doc.getElementById("subtasks_wrapper_" + taskId);
	var subtasksBody = doc.getElementById("subtasks_" + taskId);
	var newSubtaskInput = doc.getElementById('new_subtask_' + taskId);

	if (subtasksWrapper.style.display == "none")
	{
		subtasksWrapper.style.display = "block";
		doc.getElementById('task_subtasks_toggle_' + taskId).setAttribute('class', 'progress_indicator');
        loadSubtasksForTaskId(taskId);
        updateSubtasksTextForTask(taskId, taskId);
        taskEl.setAttribute('hasSubtasksOpen', true);
        doc.getElementById('task_subtasks_toggle_' + taskId).setAttribute('class', 'task_editor_icon task_subtasks_toggle');
	}
	else
	{
		subtasksWrapper.style.display = "none";
        updateSubtasksTextForTask(taskId, taskId);

		var taskType = taskEl.getAttribute('tasktype');

        if(taskType == 1)
		{
			subtasksBody.innerHTML = "";
        }
        taskEl.setAttribute('hasSubtasksOpen', false);

        doc.getElementById('task_subtasks_toggle_' + taskId).setAttribute('class', 'task_editor_icon task_subtasks_toggle off');
	}
};

function updateSubtasksTextForTask(taskId, containerId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);

    var subtasksWrapper = doc.getElementById("subtasks_wrapper_" + containerId);
    var subtaskBadgeCount = doc.getElementById("active_badge_count_" + containerId);
    var subtasksBody = doc.getElementById("subtasks_" + containerId);
    var taskType = taskEl.getAttribute('tasktype');

    if(subtasksWrapper.style.display == "none")
    {
        var uncompletedSubtaskCount = 0;
        var subtaskCount = 0;

        var subtaskArray;

        if(taskType != 1)
        {
            subtaskArray = doc.getElementsByClassName('task_name_' + taskId);
        }
        else
        {
            subtaskArray = doc.getElementsByClassName('project_subtask_' + taskId);
        }

        for (var i=0; i<subtaskArray.length; i++)
        {
            subtaskCount++;
            var completed = subtaskArray[i].getAttribute('subtask_completed');
            if(completed == "false")
                uncompletedSubtaskCount++;
        }

        if( (taskType == 7 || taskType == 1) && uncompletedSubtaskCount > 0 )
        	subtaskBadgeCount.innerHTML = uncompletedSubtaskCount;
    }
    else
    {
        //subtaskBadgeCount.innerHTML = "Hide Subtasks";
        //subtaskBadgeCount.innerHTML = "";
		//doc.getElementById('subtask_toggle_' + containerId).className = "task_attribute_has_value";
    }
};

function setTaskDueDateValues(taskDueDateTimestamp, dateText, taskId)
{
	var doc = document;
    doc.getElementById(taskId).setAttribute('duedate', taskDueDateTimestamp);
    doc.getElementById('task_due_date_' + taskId).style.display = 'inline-block';
    doc.getElementById('task_due_date_' + taskId).innerHTML = displayHumanReadableDate(taskDueDateTimestamp, false, false);
};
function setTaskStartDateValues(taskStartDateTimestamp, taskId)
{
	var doc = document;
    doc.getElementById(taskId).setAttribute('startdate', taskStartDateTimestamp);
    doc.getElementById('task_start_date_' + taskId).style.display = 'inline-block';
    doc.getElementById('task_start_date_' + taskId).innerHTML = displayHumanReadableDate(taskStartDateTimestamp, false, false);;
};

//************************
//  ChangeLog view control
//************************
function showChangeLotDetail(changeId)
{
	displayGlobalErrorMessage(labels.going_to_show_details_for_changelog_item + ': ' + changeId);
};


//-------------
// utilities
//-------------
function turnOnSubtasks(taskId)
{
	var doc = document;

	doc.getElementById('task_subtasks_icon_' + taskId).style.display = 'block';
	doc.getElementById('subtasks_wrapper_' + taskId).style.display = 'block';
};


function turnOffSubtasks(taskId)
{
	var doc = document;

	doc.getElementById('task_subtasks_icon_' + taskId).style.display = 'none';
	doc.getElementById('subtasks_wrapper_' + taskId).style.display = 'none';
	doc.getElementById('active_badge_count_' + taskId).style.display = 'none';

	//turn chidren into normal tasks
};

//-----------------
// ! Task Editor
//-----------------

var windowClickEvents = [];
var isNoteEditorOpen = false;

function displayTaskEditor(taskId)
{
    if (windowClickEvents.length > 0) {
        for (var i = 0; i < windowClickEvents.length; i++) {
            var action = windowClickEvents[i];
            action('close-task-editor');
        }
    }
	var doc = document;
	var editorEl = doc.getElementById('task_editor_' + taskId);
		editorEl.style.display = 'block';

	if (useNewTaskEditor)
	{
		loadNewTaskEditorContent(taskId);
		editorEl.setAttribute('class', 'property_flyout task_editor new');
	}
	else
	{
		loadTaskEditorContent(taskId);
		editorEl.setAttribute('class', 'property_flyout task_editor');
	}

	var dismissTaskEditor = function(event){hideTaskEditor(event, taskId);};

	selectTask(taskId, null);
    removeTextSelection();
	pushWindowClickEvent(dismissTaskEditor);
};


function hideTaskEditor(event, taskId)
{
	var doc = document;
	var shouldHideEditor = false;
    var editorEl = doc.getElementById('task_editor_' + taskId);
    if (editorEl) {
        if (event !== 'close-task-editor') {
            var eventTarget = event.target;

            if (isNoteEditorOpen) {
                if (!isNoteEditor(event, taskId))
                    shouldHideEditor = true;
            }
            else if ((eventTarget.getAttribute('id') != 'task_editor_' + taskId && !isDescendant(editorEl, eventTarget)) && eventTarget.getAttribute('id') != 'note_zoom_icon_' + taskId)
                shouldHideEditor = true;
        } else {
            shouldHideEditor = true;
        }
        if (shouldHideEditor) {
            doc.getElementById('task_editor_' + taskId).setAttribute('style', '');
            popWindowClickEvent();
        }
    } else {
        popWindowClickEvent();
    }
};


function isNoteEditor(event, taskId)
{
	var result = true;
	var eventTarget = event.target;
	var eventTargetId = eventTarget.getAttribute('id');

	if (eventTargetId == 'cancel_note_editor_' + taskId && eventTargetId == 'save_note_editor_changes_' + taskId)
		result = false;

	return result;
};

//there can only be one window click event listener at a time to ensure that previous pop up menus are closed in the same order they were opened
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

function isTaskEditorOpen(taskId)
{
	var doc = document;
	var taskEditor = doc.getElementById('task_editor_' + taskId);
	var taskEditorStyle = taskEditor.style;

	var isEditorOpen = false;

	if (taskEditorStyle != 'undefined' && taskEditorStyle.length > 0)
		isEditorOpen = true;

	return isEditorOpen;
};

function loadNewTaskEditorContent(taskId)
{
	var doc = document;
	var editorEl = doc.getElementById('task_editor_' + taskId);
	var taskEl = doc.getElementById(taskId);
	var isCompleted = taskEl.getAttribute('iscompleted');
	var completionDate = taskEl.getAttribute('completiondate');
	var checkboxClass = 'checkbox icon';
	var checkboxOnClick = 'completeTask(event, \'' + taskId + '\', null)';

	if (isCompleted == 'true' && completionDate != '0')
	{
		checkboxClass += ' checked';
		checkboxOnclick = 'uncompleteTask(event, \'' + taskId + '\')';
	}

    var html  = '';
    	html += '<div class="static">';
        html += '	<div class="close-task-editor" onclick="hideTaskEditor(\'close-task-editor\', \'' + taskId + '\')"><i class="fa fa-times"></i></div>';
        html += '	<div id="task_editor_checkbox_' + taskId + '" class="' + checkboxClass + '" onclick="' + checkboxOnClick + '"></div>';
    	html += '	<div class="task_name_wrap">';
    	html += '		<div id="task_editor_task_name_' + taskId + '" class="task_name" contenteditable="true" onblur="updateTaskName(\'' + taskId + '\')" onkeydown="handleTaskNameKeydown(event, \'' + taskId + '\')">' + taskEl.getAttribute('name') + '</div>';
    	html += '	</div>';
    	html += '</div>';
    	html += '<div class="properties_wrap">';
	    html += '	<div id="task_editor_properties_' + taskId + '" class="properties">' + labels.task_properties + '</div>';
	    html += '</div>';
	    html += '<div id="task_editor_tabs_' + taskId +'" class="tabs">';
	    html += '	<div id="tab_1_' + taskId + '" class="first tab" onclick="selectTaskEditorTab(\''+ taskId + '\', 1)"><div class="icon"></div></div>';
	    html += '	<div id="tab_2_' + taskId + '" class="second tab" onclick="selectTaskEditorTab(\''+ taskId + '\', 2)"><div class="icon"></div></div>';
	    html += '	<div id="tab_3_' + taskId + '" class="third tab" onclick="selectTaskEditorTab(\''+ taskId + '\', 3)"><div class="icon"></div></div>';
	    html += '	<div id="tab_4_' + taskId + '" class="fourth tab" onclick="selectTaskEditorTab(\''+ taskId + '\', 4)"><div class="icon"></div></div>';
	    html += '	<div id="tab_5_' + taskId + '" class="fifth tab" onclick="selectTaskEditorTab(\''+ taskId + '\', 5)"><div class="icon"></div></div>';
	    html += '</div>';

    editorEl.innerHTML = html;

    var tabNumber = 1;

   	selectTaskEditorTab(taskId, tabNumber);

    var editorTaskName = doc.getElementById('task_editor_task_name_' + taskId);
    	editorTaskName.scrollTop = 0;

    	//editorTaskName.select();
};

function selectTaskEditorTab(taskId, tabNumber)
{
	loadTaskEditorProperties(taskId, tabNumber);

	var doc = document;
	var tabsEl = doc.getElementById('task_editor_tabs_' + taskId);

	var tab1 = doc.getElementById('tab_1_' + taskId);
	var tab2 = doc.getElementById('tab_2_' + taskId);
	var tab3 = doc.getElementById('tab_3_' + taskId);
	var tab4 = doc.getElementById('tab_4_' + taskId);
	var tab5 = doc.getElementById('tab_5_' + taskId);

	//unselect all tabs
	tab1.setAttribute('class', 'first tab');
	tab2.setAttribute('class', 'second tab');
	tab3.setAttribute('class', 'third tab');
	tab4.setAttribute('class', 'fourth tab');
	tab5.setAttribute('class', 'fifth tab');

	var taskEl = doc.getElementById(taskId);
	var alertsCount = parseInt(taskEl.getAttribute('alertcount'), 10);
	var commentsCount = parseInt(taskEl.getAttribute('commentscount'), 10);

	//select selected tab & fill in/format data
	switch (tabNumber)
	{
		case 1:
			tabsEl.setAttribute('class', 'tabs first-tab');
			tab1.setAttribute('class', 'first tab selected');
			doc.getElementById('task_editor_task_name_' + taskId).scrollTop = 0;
			break;
		case 2:
			tabsEl.setAttribute('class', 'tabs second-tab');
			tab2.setAttribute('class', 'second tab selected');
			break;
		case 3:
			tabsEl.setAttribute('class', 'tabs third-tab');
			tab3.setAttribute('class', 'third tab selected');
			if (alertsCount > 0)
			{
				loadTaskAlertsInEditor(taskId);
				doc.getElementById('task_alerts_in_editor_' + taskId).scrollTop = 0;
			}
			break;
		case 4:
			tabsEl.setAttribute('class', 'tabs fourth-tab');
			tab4.setAttribute('class', 'fourth tab selected');
			if (commentsCount > 0)
				loadTaskCommentsInTaskEditor(taskId);
			break;
		case 5:
			tabsEl.setAttribute('class', 'tabs fifth-tab');
			tab5.setAttribute('class', 'fifth tab selected');
			break;
		default:
			displayErrorMessage(labels.invalid_tab_selected_in_task_editor);
			break;
	}
};

function loadTaskEditorProperties(taskId, tabNumber)
{
	var doc = document;
	var propertiesEl = doc.getElementById('task_editor_properties_' + taskId);
	var html  = '';

	switch (tabNumber)
	{
		case 1:
			html = getEditorFirstTabHtml(taskId);
			break;
		case 2:
			html = getEditorSecondTabHtml(taskId);
			break;
		case 3:
			html = getEditorThirdTabHtml(taskId);
			break;
		case 4:
			html = getEditorFourthTabHtml(taskId);
			break;
		case 5:
			html = getEditorFifthTabHtml(taskId);
			break;
		default:
			displayErrorMessage(labels.invalid_tab_selected_in_task_editor);
			break;
	}

	propertiesEl.innerHTML = html;
};

function getEditorFirstTabHtml(taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var startDate = parseInt(taskEl.getAttribute('startdate'), 10);
	var dueDate = taskEl.getAttribute('duedate');
	var taskNote = doc.getElementById('task_original_note_' + taskId).value;
	var hasTime = parseInt(taskEl.getAttribute('hasduetime'), 10);
	var isCompleted = taskEl.getAttribute('iscompleted') == 'true' ? true : false;

	var startDateLabel = '';
	var dueDateLabel = '';
	var dueTimeLabel = '';
	var noteLabel = '';
	var completedDateLabel = '';

	var startDateClass = 'task_date start_date';
	var dueDateClass = 'task_date due_date';
	var dueTimeClass = 'task_date due_time';
	var noteClass = 'note';
	var completedDateClass = 'task_date completed_date';

	if (isCompleted)
	{
		var completedDate = parseInt(taskEl.getAttribute('completiondate'), 10);

		completedDateLabel = displayHumanReadableDate(completedDate) + ', ' + displayHumanReadableTime(completedDate);
		completedDateClass = 'task_date completed_date on';
	}


	if (startDate != 0)
	{
		startDateLabel = displayHumanReadableDate(startDate);
		startDateClass += ' on';
	}
	else
		startDateLabel = 'start date';

	if (dueDate != 0)
	{
		dueDateLabel = displayHumanReadableDate(dueDate);
		dueDateClass += ' on';
		dueTimeClass += ' show';
	}
	else
		dueDateLabel = 'due date';

	if (hasTime != 0)
	{
		dueTimeLabel = displayHumanReadableTime(dueDate);
		dueTimeClass += ' on';
	}
	else
		dueTimeLabel = 'due time';

	if (taskNote.length > 0)
	{
		noteLabel = taskNote.replace(/\n/ig, '<br>');
		noteClass += ' on';
	}
	else
		noteLabel = 'add note';


	var html  = '';
				//due date
		html += '<div id="task_editor_completed_date_wrap_' + taskId + '" class="' + completedDateClass + '">';
		html += '	<div class="icon"></div>';
		html += '	<div id="task_editor_completed_date_' + taskId + '" class="label" onclick="">' + completedDateLabel + '</div>';
		html += '	<div id="completed_date_editor_' + taskId + '" class="property_flyout datepicker_wrapper">';
		html += '		<div id="completed_datepicker_' + taskId + '" class="task_datepicker"> </div>';
		html += '	</div>';
		html += '</div>';
				//due date
		html += '<div id="task_editor_due_date_wrap_' + taskId + '" class="' + dueDateClass + '">';
		html += '	<div class="icon"></div>';
		html += '	<div id="task_editor_due_date_' + taskId + '" class="label" onclick="displayTaskDueDatePicker(\'' + taskId + '\',\''+ dueDate + '\')">' + dueDateLabel + '</div>';
		html += '	<div id="due_date_editor_' + taskId + '" class="property_flyout datepicker_wrapper">';
		html += '		<div id="datepicker_' + taskId + '" class="task_datepicker"> </div>';
		html += '	</div>';
		html += '</div>';
				//due time
		html += '<div id="task_editor_due_time_wrap_' + taskId + '" class="' + dueTimeClass + '">';
		html += '	<div class="icon"></div>';
		html += '	<div id="task_editor_due_time_' + taskId + '" class="label" onclick="displayDueTimeEditor(\'' + taskId + '\')">' + dueTimeLabel + '</div>';
		html += '	<div id="due_time_editor_flyout_' + taskId + '" class="property_flyout due_time_editor_flyout">';
		html += '		<div id="task_due_time_editor_' + taskId + '" class="task_due_time_editor"> </div>';
		html += '	</div>';
		html += '</div>';
				//start date
		html += '<div id="task_editor_start_date_wrap_' + taskId + '" class="' + startDateClass + '">';
		html += '	<div class="icon"></div>';
		html += '	<div id="task_editor_start_date_' + taskId + '" class="label" onclick="displayTaskStartDatePicker(\'' + taskId + '\',\''+ startDate + '\')">' + startDateLabel + '</div>';
		html += '	<div id="start_date_editor_' + taskId + '" class="property_flyout datepicker_wrapper">';
		html += '		<div id="start_datepicker_' + taskId + '" class="task_datepicker"> </div>';
		html += '	</div>';
		html += '</div>';
				//note
		html += '<div id="task_editor_note_wrap_' + taskId + '" class="' + noteClass + '">';
		html += '	<div id="note_zoom_icon_' + taskId + '" class="task_icon note_zoom" onclick="showNoteEditor(\'' + taskId + '\')"></div>';
		html += '	<div class="icon"></div>';
		html += ' 	<div id="task_editor_task_note_' + taskId + '" class="label" contenteditable="true" onblur="updateTaskNote(\'' + taskId + '\')" onclick="handleTaskNoteOnClick(event, \'' + taskId + '\')">' + noteLabel + '</div>';
		html += '</div>';

	return html;
};

function getEditorSecondTabHtml(taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var hasStar = taskEl.getAttribute('starred');
	var priority = parseInt(taskEl.getAttribute('priority'), 10);
	var listName = htmlEntities(taskEl.getAttribute('listname'));
	var listId = taskEl.getAttribute('origlistid');
	var listColor = taskEl.getAttribute('listcolor');
	var contextName = taskEl.getAttribute('contextname');
	var contextId = taskEl.getAttribute('origcontextid');
	var tags = taskEl.getAttribute('tags');

	var listLabel = listName;
	var contextLabel = labels.set_context;
	var addTagLabel = taskStrings.noTags;

	var starClass = 'star icon';
	var lowPriorityClass = 'icon priority low';
	var medPriorityClass = 'icon priority med';
	var highPriorityClass = 'icon priority high';
	var nonePriorityClass = 'icon priority none';
	var contextClass = 'context';

	var listColorStyle = 'background-color:rgba(' + listColor + ', .8)';

	if (hasStar == 1)
		starClass += ' on';

	switch(priority)
	{
		case 1:
			highPriorityClass += ' on';
			break;
		case 5:
			medPriorityClass += ' on';
			break;
		case 9:
			lowPriorityClass += ' on';
			break;
		case 0:
		default:
			nonePriorityClass += ' on';
			break;
	}

	if (contextId != '0')
	{
		contextClass += ' on';
		contextLabel = contextName;
	}

	//tags
	if (tags.length > 0)
		tagsLabel = tags;
	else
		tagsLabel = labels.none;

	var html  = '';
				//star
		html += '<div id="task_editor_star_' + taskId + '" class="' + starClass + '" onclick="updateTaskStar(\'' + taskId + '\')">S</div>';
				//priority options
		html += '<div id="task_editor_priorities_wrap_' + taskId + '" class="priorities_wrap">';
		html += '	<div id="task_editor_high_priority_' + taskId + '" class="' + highPriorityClass + '" onclick="updatePriorityForTask(1,\'' + taskId + '\')" title="high priority">' + labels.priority_h + '</div>';
		html += '	<div id="task_editor_med_priority_' + taskId + '" class="' + medPriorityClass + '" onclick="updatePriorityForTask(5,\'' + taskId + '\')" title="medium priority">' + labels.priority_m + '</div>';
		html += '	<div id="task_editor_low_priority_' + taskId + '" class="' + lowPriorityClass + '" onclick="updatePriorityForTask(9,\'' + taskId + '\')" title="low priority">' + labels.priority_l + '</div>';
		html += '	<div id="task_editor_none_priority_' + taskId + '" class="' + nonePriorityClass + '" onclick="updatePriorityForTask(0,\'' + taskId + '\')" title="no priority">' + labels.priority_n + '</div>';
		html += '</div>';
		html += '<hr />';
				//list
		html += '<div id="task_editor_list_wrap_' + taskId + '" class="list">';
		html += '	<div class="icon" ></div>';
		html += '	<div id="task_editor_list_color_' + taskId + '" class="icon-bg" style="' + listColorStyle + '"></div>';
		html += '	<div id="task_editor_list_' + taskId + '" class="dropdown_toggle label" onclick="showListPicker(\'' + taskId + '\')">' + listLabel + '</div>';
		html += '	<div id="list_picker_' + taskId + '" class="property_flyout task_list_picker"> </div>';
		html += '</div>';
				//context
		html += '<div id="task_editor_context_wrap_' + taskId + '" class="' + contextClass + '">';
		html += '	<div class="icon"></div>';
		html += '	<div id="task_editor_context_' + taskId + '" class="dropdown_toggle label" onclick="showContextPicker(event, \'' + taskId + '\')">' + contextLabel + '</div>';
		html += '	<div id="context_editor_' + taskId + '" class="property_flyout context_picker_flyout"></div>';
		html += '</div>';
		html += '<hr />';
				//tags
		html += '<div id="task_editor_tags_wrap_' + taskId + '" class="tags_wrap">';
		html += 	getTaskEditorTagsHtml(taskId);
		html += '</div>';
		html += '<div id="task_editor_add_tag_wrap_' + taskId + '" class="tag set">';
		html += '	<div class="icon"></div>';
		html += '	<div id="task_editor_add_tag_' + taskId + '" class="dropdown_toggle label" onclick="showTagsEditor(event, \'' + taskId + '\')" onkeydown="">'+labels.add_tags+'</div>';
		html += '	<div id="tags_editor_' + taskId + '" class="property_flyout tags_picker_flyout"></div>';
		html += '</div>';

	return html;
};

function getTaskEditorTagsHtml(taskId)
{
	var html = '';
	var tags = document.getElementById(taskId).getAttribute('tags');

	if (tags.length != 0)
	{
		tags = tags.split(', ');

		for (var i = 0 ; i < tags.length; i++)
		{
			var label = tags[i];
			var removeTagHtml = useNewTaskEditor ? '<span class="delete-button icon" onclick="updateTagsForTaskViaNewTaskEditor(\'' + taskId + '\', \'' + label + '\')"></span>' : '';

			if (label.charAt(0) == ' ')
				label = label.substr(1, label.length);

			html += '<div class="tag on">';
			html += '	<div class="icon"></div>';
			html += '	<div class="label">' + label + '</div>';
			html += 	removeTagHtml;
			html += '</div>';
		}
	}

	return html;
};

function getEditorThirdTabHtml(taskId)
{
	var taskEl = document.getElementById(taskId);
	var repeat = parseInt(taskEl.getAttribute('repeat'), 10);
	var advancedRepeatString = taskEl.getAttribute('advrepeat');
	var locationType = parseInt(taskEl.getAttribute('locationalerttype'), 10);
	var locationAddress = taskEl.getAttribute('locationalertaddress');

	var advancedRepeatHtml = '';

	var repeatLabel = labels.set_repeat_frequency;
	var repeatFromLabel = '';
	var locationAlertTypeLabel = labels.set_location_alert;
	var locationAddressLabel = '';
	var timeAlertsLabel = labels.set_time_alert;

	var repeatClass = 'repeat';
	var repeatFromClass = 'repeat-from';
	var advancedRepeatClass = 'advanced-repeat';
	var locationAlertTypeClass = 'location-alert';
	var locationAddressClass = 'location-address';
	var timeClass = 'time-alert';

	if (repeat != 0 && repeat !=100)
	{
		repeatLabel = localizedStringForTaskRecurrenceType(repeat, advancedRepeatString);
		repeatClass += ' on';
		repeatFromClass += ' on';
	}

	if (advancedRepeatString.length > 0)
	{
		advancedRepeatHtml = htmlForAdvancedPicker(taskId, repeat, advancedRepeatString);
		advancedRepeatClass += ' on';
	}

	if (locationType == 1 || locationType == 2)
	{
		if (locationType == 1)
			locationAlertTypeLabel = alertStrings.whenIArrive;
		else
			locationAlertTypeLabel = alertStrings.whenILeave;

		locationAlertTypeClass += ' on';
		locationAddressClass += ' on';
	}

	if (locationAddress.length > 0)
	{
		locationAddressLabel = locationAddress;
	}

	repeatFromLabel = localizedFromDueDateOrCompletionStringForType(repeat);


	var html  = '';
				//repeat
		html += '<div id="task_editor_repeat_wrap_' + taskId + '" class="' + repeatClass + '">';
		html += '	<div class="icon" style=""></div>';
		html += '	<div id="task_editor_repeat_' + taskId + '" class="dropdown_toggle label" onclick="displayRepeatPicker(event, \'' + taskId + '\')">' + repeatLabel + '</div>';
		html += '	<div id="repeat_editor_' + taskId + '" class="property_flyout task_repeat_editor_flyout"></div>';
		html += '</div>';
		html += '<div id="task_editor_repeat_from_wrap_' + taskId + '" class="' + repeatFromClass + '">';
		html += '	<div class="icon" style=""></div>';
		html += '	<div id="task_editor_repeat_from_' + taskId + '" class="dropdown_toggle label" onclick="displayRepeatFromPicker(event, \'' + taskId + '\')">' + repeatFromLabel + '</div>';
		html += '	<div id="task_editor_repeat_from_editor_' + taskId + '"class="property_flyout task_repeat_from_editor_flyout"></div>';
		html += '</div>';
		html += '<div id="advanced_repeat_wrapper_' + taskId + '" class="' + advancedRepeatClass + '" >';
		html += 	advancedRepeatHtml;
		html += '</div>';
		html += '<hr />';
				//location alert
		html += '<div id="task_editor_location_alert_wrap_' + taskId + '" class="' + locationAlertTypeClass + '">';
		html += '	<div class="icon" style=""></div>';
		html += '	<div id="task_editor_location_alert_' + taskId + '" class="dropdown_toggle  label" onclick="displayLocationAlertOptions(\'' + taskId + '\')" value="' + locationType + '">' + locationAlertTypeLabel + '</div>';
		html += '	<div id="location_type_editor_' + taskId + '" class="property_flyout location_type_flyout"></div>';
		html += '</div>';
		html += '<div id="task_editor_location_address_wrap_' + taskId + '" class="' + locationAddressClass + '">';
		html += '	<div class="icon" style=""></div>';
		html += '	<input id="task_location_address_' + taskId + '" placeholder="'+labels.type_an_address+'" class="label" value="' + locationAddressLabel + '" onblur="addTaskLocationAlert(\'' + taskId + '\')"/>';
		html += '	</div>';
		html += '</div>';
				//time alerts
		html += '<hr />';
		html += '<div id="task_alerts_in_editor_' + taskId + '" class="alerts_in_editor"></div>';
		html += '<div id="task_editor_list_wrap_' + taskId + '" class="' + timeClass + '">';
		html += '	<div class="icon" style=""></div>';
		html += '	<div id="task_editor_time_alert_' + taskId + '" class="dropdown_toggle label" onclick="displayAlertOptions(\'' + taskId + '\')">' + timeAlertsLabel + '</div>';
		html += '	<div id="alert_type_options_flyout_061fbfa8-6c9f-60a8-eb71-00000c9752a8" class="property_flyout alert_type_options_flyout" style="">';
		html += '	<div class="picker_option alert_option selected" onclick="createTaskAlert(\'' + taskId + '\', -1, labels.none)">';
		html += '	<div class="picker_option alert_option" onclick="createTaskAlert(\'' + taskId + '\', 47, labels.custom)">';
		html += '	</div>';
		html += '</div>';

	return html;
};

function getEditorFourthTabHtml(taskId)
{
	var userPicUrl = document.getElementById('userImgUrl').value;
		userPicUrl = userPicUrl.length == 0 ? defaultUserImgUrl : userPicUrl;

	var taskEl = document.getElementById(taskId);
	var assigneeId = taskEl.getAttribute('assigneeid');
	var assigneeName = taskEl.getAttribute('assigneename');
	var commentsCount = parseInt(taskEl.getAttribute('commentscount'), 10);

	var assignLabel = 'assign task';
	var newCommentPlaceholder = commentsCount == 0 ? labels.start_a_conversation : labels.add_a_comment;

	var assignClass = 'assign';

	if (assigneeId != 'none')
	{
		assignLabel = assigneeName;
		assignClass += ' on';
	}

	var html  = '';
				//assignment
		html += '<div id="task_editor_assign_wrap_' + taskId + '" class="' + assignClass + '">';
		html += '	<div class="icon" style=""></div>';
		html += '	<div id="task_editor_assign_' + taskId + '" class="dropdown_toggle label" onclick="displayPeoplePickerModal(\'' + taskId + '\')">' + assignLabel + '</div>';
		html += '	<div id="assignee_editor_' + taskId + '" class="property_flyout assign_editor_flyout"></div>';
		html += '</div>';
		html += '<hr />';
				//comments
		html += '<div id="comments_' + taskId + '" class="comments-wrap" ></div>';
				//new comment
		html += '<div id="task_editor_new_comment_wrap_' + taskId + '" class="new comment">';
		html += '	<img class="user_pic" src="' + userPicUrl + '"/>';
		html += '	<input id="new_comment_' + taskId + '" class="label" placeholder="' + newCommentPlaceholder + '" onblur="postTaskComment(event, \'' + taskId + '\')" onkeydown="newCommentHandleKeydown(event, \'' + taskId + '\')" />';
		html += '</div>';

	return html;
};

function newCommentHandleKeydown(event, taskId)
{
	var keyCode = 'keyCode' in event ? event.keyCode : event.charCode;

	if (keyCode == 13)/*enter key*/
		postTaskComment(event,taskId);
};

function getEditorFifthTabHtml(taskId)
{
	var taskEl = document.getElementById(taskId)
	var taskType = parseInt(taskEl.getAttribute('tasktype'), 10);
	var taskTypeData = taskEl.getAttribute('tasktypedata');

	//var convertTaskHtml = getConvertTaskHtml(taskType);

	var taskTypeClass = 'task-type';
	var taskTypeDetailsClass = 'type-details';
	var taskTypeDataClass = 'task_type_data';

	var taskTypeLabel = 'change task type';
	var taskTypeDetailsLabel = '';

	if (taskType != 0)
	{
		taskTypeClass += ' on';
		taskTypeLabel = titleForTaskType(taskType);
	}

	if (taskType != 7/*checklist*/ && taskType != 1 /*project*/ && taskType != 0/*normal*/)
		taskTypeDetailsClass += ' on';

	//task type
	var typeImgClass = '';
	var taskTypeDataPlaceholder = '';
	var newTypeDataInputDisplay = '';

	switch (taskType)
	{
		case 2:
			taskTypeDataPlaceholder = taskStrings.enterPhoneNumber;
			typeImgClass = ' call';
			break;
		case 3:
			taskTypeDataPlaceholder = taskStrings.enterPhoneNumber;
			typeImgClass = ' sms';
			break;
		case 4:
			taskTypeDataPlaceholder = taskStrings.enterEmailAddress;
			typeImgClass = ' email';
			break;
		case 5:
			taskTypeDataPlaceholder = taskStrings.enterStreetAddress;
			typeImgClass = ' location';
			break;
		case 6:
			taskTypeDataPlaceholder = taskStrings.enterWebsiteAddress;
			typeImgClass = ' website';
			break;

		case 1:
			typeImgClass = ' project';
			break;
		case 7:
			typeImgClass = ' checklist';
			break;
		case 0:
			break;
	};

	//task type data
	if (taskTypeData != '')
	{
		taskTypeDataClass += ' off';

		if (taskTypeData.indexOf('Task Type') == -1 ) //new format is NOT found format old data into new data
		{
			var typeDataString = '';
			var valueDescription = 'other:';
			switch (taskType)
			{
				case 2://sms
					typeDataString = 'Call';
					break;
				case 3: //call
					typeDataString = 'SMS';
					break;
				case 4: //email
					typeDataString = 'Email';
					break;
				case 5://address
					typeDataString = 'Location';
					valueDescription = 'location:';
					break;
				case 6://website
					typeDataString = 'URL';
					valueDescription = 'url:';
					break;
				case 0:
				case 1:
				case 7:
					break;
			};

			var newTaskTypeDataValue = taskTypeData;

			taskTypeData ='';
			taskTypeData += '---- Task Type: ' + typeDataString + ' ---- \n';
			taskTypeData += 'contact: ' + newTaskTypeDataValue + ' \n';
			taskTypeData += valueDescription + ' ' + newTaskTypeDataValue + ' \n';
			taskTypeData += '---- End Task Type ----';
		}

		var taskTypeAction = '';
		var taskTypeDataArray = taskTypeData.split('\n');

		var taskTypeDataLabel = '';
			taskTypeDataLabel = taskTypeDataArray[1].replace('contact: ', '');

		var taskTypeDataOptionsHTML = ''


		taskTypeDataOptionsHTML += '<label>' + taskTypeDataLabel + '</label>';
		taskTypeDataOptionsHTML += '<hr style="margin: 6px auto"/>';

		for (var i = 0; i < taskTypeDataArray.length; i++)
		{
			if (taskTypeDataArray[i].length != 0 && taskTypeDataArray[i].indexOf('---- Task Type:') == -1 && taskTypeDataArray[i].indexOf('contact:')== -1 && taskTypeDataArray[i].indexOf('---- End Task Type ----')== -1)
			{
				var actionElArray = [];

				switch(taskType)
				{
					case 4: //email
						actionElArray = taskTypeDataArray[i].split(' ');
						taskTypeAction = 'parent.location=\'mailto:' + actionElArray[1] + '\'';
						break;
					case 5: //location
						taskTypeAction = 'window.open(\'http://maps.google.com/maps?q=' + taskTypeDataArray[i].replace('location: ', '') + '\')';
						break;
					case 6: //URL
                        var url = taskTypeDataArray[i].replace('url: ', '');
                        if (url.indexOf('http://') == -1 && url.indexOf('https://') == -1) {
                            url = 'http://' + url;
                        }
                        taskTypeAction = 'window.open(\'' + url + '\')';
						break;
					default:
						break;
				}

				taskTypeDataOptionsHTML += '<div class="picker_option" onclick="' + taskTypeAction + '"> ' +taskTypeDataArray[i] + '</div>';
			}
		}

		taskTypeDataOptionsHTML += '<hr style="margin: 6px auto"/>';
		taskTypeDataOptionsHTML += '<div class="picker_option" onclick="displayTaskTypeDataInput(event, \'' + taskId + '\')">' + taskTypeDataPlaceholder+ '</div>';
	}

	var html  = '';
				//task type
		html += '<div id="task_editor_task_type_wrap_' + taskId + '" class="' + taskTypeClass + '">';
		html += '	<div id="task_editor_task_type_icon_' + taskId + '" class="icon' + typeImgClass + '"></div>';
		html += '	<div id="task_editor_task_type_' + taskId + '" class="dropdown_toggle label" onclick="showTaskTypeEditor(\'' + taskId + '\')">' + taskTypeLabel + '</div>';
		html += '	<div id="task_type_editor_' + taskId + '" class="task_type_editor property_flyout"></div>';
		html += '</div>';

		html += '<div id="task_editor_type_details_' + taskId + '" class="' + taskTypeDetailsClass + '">';
		html += '	<div id="task_type_data_toggle_' + taskId +'" class="dropdown_toggle task_type_data_toggle" onclick="showTaskActionOptions(\'' + taskId + '\')">';
		html += '		<div id="task_type_toggle_icon_' + taskId + '" class="task_editor_icon task_type' + typeImgClass + '" ></div>';
		html += '		<span id="task_type_data_toggle_text_' + taskId + '" class="toggle_text">' + taskTypeDataLabel + '</span>';
		html += '		<div id="task_actions_flyout_' + taskId + '" class="property_flyout task_actions_flyout">' + taskTypeDataOptionsHTML + '</div>';
		html += '	</div>';
		html += '	<input id="task_type_data_' + taskId + '" class="' + taskTypeDataClass + '" type="text" placeholder="' + taskTypeDataPlaceholder + '" onkeydown="if (event.keyCode == 13) this.blur()" onblur="updateTaskTypeData(\'' + taskId + '\')" value="' + taskTypeDataLabel.trim() + '">';
		html += '</div>';

	return html;
};


// function getConvertTaskHtml(currentTaskType, taskId)
// {
// 	var html = '';
//
// 	var convertToChecklistBtnHtml = '<div class="button half" onclick="updateTaskType(event, \'' + taskId + '\',\'7\' )">Convert to Checklist</div>';
// 	var convertToProjectBtnHtml = '	 <div class="button half" onclick="updateTaskType(event, \'' + taskId + '\',\'1\' )" >Convert to Project</div>';
// 	var convertToNormalBtnHtml = '	 <div class="button half" onclick="updateTaskType(event, \'' + taskId + '\',\'0\' )" >Convert to Task</div>';
//
// 	switch (currentTaskType)
// 	{
// 		case 1:/*project*/
// 			html += convertToChecklistBtnHtml + convertToNormalBtnHtml;
// 			break;
// 		case 7:/*checklist*/
// 			html += convertToNormalBtnHtml + convertToProjectBtnHtml;
// 			break;
// 		case 0:/*normal*/
// 		default:
// 			html += convertToChecklistBtnHtml + convertToProjectBtnHtml;
// 			break;
// 	}
//
// 	return html;
// };
//

function loadTaskEditorContent(taskId)
{
	var doc = document;
	var editorEl = doc.getElementById('task_editor_' + taskId);
	var taskEl = doc.getElementById(taskId);
	var startDate = taskEl.getAttribute('startdate');
	var dueDate = taskEl.getAttribute('duedate');
	var hasStar = taskEl.getAttribute('starred');
	var starClass = '';
	var priority = parseInt(taskEl.getAttribute('priority'), 10);
	var priorityLabel = taskSectionsStrings.none;
	var context = taskEl.getAttribute('contextname');
	var listName = htmlEntities(taskEl.getAttribute('listname'));
	var locationType = parseInt(taskEl.getAttribute('locationalerttype'), 10);
	var locationTypeLabel = labels.none;
	var locationDisplay = '';
	var locationAddress = '';
	var assigneeLabel = taskEl.getAttribute('assigneename');
	var alertsCount = taskEl.getAttribute('alertcount');
	var hasTime = parseInt(taskEl.getAttribute('hasduetime'), 10);
	var dueTimeLabel = '';
	var dueTimeOnClick = '';
	var tags = taskEl.getAttribute('tags');
	var tagsLabel = '';
	var taskType = parseInt(taskEl.getAttribute('tasktype'), 10);
	var taskTypeLabel = titleForTaskType(taskType);
	var taskTypeDataLabel = '';
	var taskTypeDetailsDisplay = '';
	var taskTypeData = taskEl.getAttribute('tasktypedata');
	var taskTypeDataPlaceholder = '';
	var taskNote = doc.getElementById('task_original_note_' + taskId).value;

	var commentsCount = parseInt(taskEl.getAttribute('commentscount'), 10);
    var isSubtask = taskEl.getAttribute('isSubtask') == "true";

	//prepare values

	//star
	if(hasStar != 1)
		starClass = ' off';

	//priority
	switch (priority)
	{
		case 1:
            priority = "high";
            priorityLabel = taskSectionsStrings.high;
            break;
        case 5:
            priority = "med";
            priorityLabel = taskSectionsStrings.medium;
            break;
        case 9:
            priority = "low";
            priorityLabel = taskSectionsStrings.low;
            break;
        case 0:
            priority = "none";
            break;
        default:
            priority = "none";
	}

	//location alert
	switch (locationType)
	{
		case 0:
			locationTypeLabel = alertStrings.none;
			break;
		case 1:
			locationTypeLabel = alertStrings.whenIArrive;
			break;
		case 2:
			locationTypeLabel = alertStrings.whenILeave;
			break;
		default:
			locationTypeLabel = alertStrings.none;
	}

	if (locationType == 1 || locationType == 2)
	{
		locationDisplay = 'display:block';
		locationAddress = taskEl.getAttribute('locationalertaddress');
	}

	//due time
	if (hasTime == 1)
		dueTimeLabel = displayHumanReadableTime(dueDate);
	else
		dueTimeLabel = labels.none;

	if (dueDate != '0')
		dueTimeOnClick = 'displayDueTimeEditor(\'' + taskId + '\')';

	//tags
	if (tags.length > 0)
		tagsLabel = tags;
	else
		tagsLabel = labels.none;

	//task type
	var typeImgClass = ''

	switch (taskType)
	{
		case 2:
			taskTypeDetailsDisplay = 'display:block';
			taskTypeDataPlaceholder = taskStrings.enterPhoneNumber;
			typeImgClass = 'call';
			break;
		case 3:
			taskTypeDetailsDisplay = 'display:block';
			taskTypeDataPlaceholder = taskStrings.enterPhoneNumber;
			typeImgClass = 'sms';
			break;
		case 4:
			taskTypeDetailsDisplay = 'display:block';
			taskTypeDataPlaceholder = taskStrings.enterEmailAddress;
			typeImgClass = 'email';
			break;
		case 5:
			taskTypeDetailsDisplay = 'display:block';
			taskTypeDataPlaceholder = taskStrings.enterStreetAddress;
			typeImgClass = 'location';
			break;
		case 6:
			taskTypeDetailsDisplay = 'display:block';
			taskTypeDataPlaceholder = taskStrings.enterWebsiteAddress;
			typeImgClass = 'website';
			break;
		case 0:
		case 1:
		case 7:
			break;
	};
    if (taskTypeData !== '') {
        var taskTypeDataArray = taskTypeData.split('\n');
        taskTypeDataLabel = taskTypeDataArray[1].replace('contact: ', '');
    }

	//task type data
    var newTypeDataInputDisplay = '';
    var taskTypeDataOptionsHTML = '';
    var flyoutData = getTaskDataFlyoutContent(taskId);
    if (flyoutData === false) {
        taskTypeDataOptionsHTML = '';
        newTypeDataInputDisplay = 'display:inline-block;';
    } else {
        taskTypeDataOptionsHTML = flyoutData;
        newTypeDataInputDisplay = '';
    }


	var html = '';
		html += '	<div class="close-task-editor" onclick="hideTaskEditor(\'close-task-editor\', \'' + taskId + '\')"><i class="fa fa-times"></i></div>';
					//name
		html += '	<div class="labeled_control task_name_editor">';
		html += '		<div class="property_label">' + labels.task_name + '</div>';
    html += '		<input type="text" name="" value="' + htmlEntities(taskEl.getAttribute('name')) + '" class="control task_name" id="task_editor_task_name_' + taskId + '" onblur="updateTaskName(\'' + taskId + '\')" onkeydown="if (event.keyCode == 13) {this.blur(); return false;}">';
    html += '	</div>';

		html += '	<div class="breath-10"></div>';
//					//start date
//		html += '	<div class="labeled_control due_date_editor">';
//		html += '		<div class="property_label">Start Date</div>';
//		html += '		<span class="property_wrapper task_date_wrapper" id="task_editor_start_date_' + taskId + '">';
//		html += '			<div onclick="displayTaskStartDatePicker(\'' + taskId + '\', \'' + startDate + '\')" id="task_editor_start_date_toggle_' + taskId + '" class="task_editor_property_label ">' + displayHumanReadableDate(startDate) + '</div>';
//		html += ' 			<div id="start_date_editor_' + taskId + '" class="property_flyout datepicker_wrapper">';
//		html += ' 				<div class="task_datepicker" id="start_datepicker_' + taskId + '">';
//		html += ' 				</div>';
//		html += ' 			</div>';
//		html += ' 		</span>';
//		html += '	</div>';
					//due date
		html += '	<div class="labeled_control due_date_editor">';
		html += '		<div class="property_label">' + taskStrings.dueDate + '</div>';
		html += '		<span class="property_wrapper task_date_wrapper" id="task_editor_due_date_' + taskId + '">';
		html += '			<div onclick="displayTaskDueDatePicker(\'' + taskId + '\', \'' + dueDate + '\')" id="task_editor_due_date_toggle_' + taskId + '" class="task_editor_property_label ">' + displayHumanReadableDate(dueDate, false, true, true) + '</div>';
		html += ' 			<div id="due_date_editor_' + taskId + '" class="property_flyout datepicker_wrapper">';
		html += ' 				<div class="task_datepicker" id="datepicker_' + taskId + '">';
		html += ' 				</div>';
		html += ' 			</div>';
		html += ' 		</span>';
		html += '	</div>';
					//due time
		html += '	<div class="labeled_control due_time_editor">';
		html += '		<div class="property_label">' + labels.due_time + '</div>';
		html += '		<span class="property_wrapper repeat_wrapper" id="task_repeat_' + taskId + '">';
		html += '			<div onclick="' + dueTimeOnClick + '" id="task_editor_due_time_toggle_' + taskId + '" class="task_editor_property_label ">' + dueTimeLabel + '</div>';
		html += ' 			<div id="due_time_editor_flyout_' + taskId + '" class="property_flyout due_time_editor_flyout">';
		html += ' 				<div class="task_due_time_editor" id="task_due_time_editor_' + taskId + '">';
		html += ' 				</div>';
		html += ' 			</div>';
		html += ' 		</span>';
		html += '	</div>';

		html += '	<div class="breath-10"></div>';
					//start date
		html += '	<div class="labeled_control start_date_editor">';
		html += '		<div class="property_label">' + labels.start_date  + '</div>';
		html += '		<span class="property_wrapper task_date_wrapper" id="task_editor_start_date_' + taskId + '">';
		html += '			<div onclick="displayTaskStartDatePicker(\'' + taskId + '\', \'' + startDate + '\')" id="task_editor_start_date_toggle_' + taskId + '" class="task_editor_property_label ">' + displayHumanReadableDate(startDate, false, true, true) + '</div>';
		html += ' 			<div id="start_date_editor_' + taskId + '" class="property_flyout datepicker_wrapper">';
		html += ' 				<div class="task_datepicker" id="start_datepicker_' + taskId + '">';
		html += ' 				</div>';
		html += ' 			</div>';
		html += ' 		</span>';
		html += '	</div>';

		html += '	<div class="breath-10"></div>';

        var repeatType = parseInt(taskEl.getAttribute('repeat'));
        var repeatString = taskEl.getAttribute('advrepeat');

        var repeatLabel = localizedStringForTaskRecurrenceType(repeatType, repeatString);
        //repeat
		html += '	<div class="labeled_control repeat_editor">';
		html += '		<div class="property_label">' + labels.repeat  + '</div>';
		html += '		<span class="property_wrapper repeat_wrapper" id="task_repeat_' + taskId + '">';
		html += '			<div class="dropdown_toggle days_of_week" id="task_editor_repeat_toggle_' + taskId + '" onclick="displayRepeatPicker(event, \'' + taskId + '\')" >' + repeatLabel + '</div>';
		html += '			<div class="property_flyout task_repeat_editor_flyout" id="repeat_editor_' + taskId + '"></div>';
		html += ' 		</span>';
		html += '	</div>';

        var fromDueDateLabel = localizedFromDueDateOrCompletionStringForType(repeatType);
        var style = 'display:none;';
        if(repeatType != 0 && repeatType != 100)
            style = 'display:block;';

		html += '	<div class="labeled_control form_repeat_editor" id="repeat_from_wrapper" style="' + style + '">';
		html += '		<div class="property_label"></div>';
		html += '		<span class="property_wrapper repeat_wrapper">';
		html += '			<div class="dropdown_toggle" id="task_editor_repeat_from_toggle_' + taskId + '" onclick="displayRepeatFromPicker(event, \'' + taskId + '\')" >' + fromDueDateLabel + '</div>';
		html += '			<div class="property_flyout task_repeat_editor_flyout" id="repeat_from_editor_' + taskId + '"></div>';
		html += ' 		</span>';
		html += '	</div>';

        style = 'display:none;';
        var advancedHtml = htmlForAdvancedPicker(taskId, repeatType, repeatString);
        if(advancedHtml.length > 0)
        {
            style = 'display:block;';
        }
        html += '   <div class="labeled_control advanced_repeat_editor" id="advanced_repeat_wrapper_' + taskId + '" style="' + style + '">';
        html += advancedHtml;
        html += '   </div>';



		html += '	<div class="breath-10"></div>';
					//star
		html += '	<div class="labeled_control ">';
		html += '		<div class="property_label">' + labels.star  + '</div>';
		html += '		<div id="task_editor_star_' + taskId + '" class="task_icon task_editor_star ' + starClass + '" onclick="updateTaskStar(\'' + taskId + '\')"></div>';
		html += '	</div>';
					//priority
		html += '	<div class="labeled_control priority_editor">';
		html += '		<div class="property_label">' + taskStrings.priority  + '</div>';
		html += '		<span class="property_wrapper priority_wrapper">';
		html += '			<div class="priority_option task_editor_priority_toggle" id="task_editor_priority_toggle_' + taskId + '" onclick="displayPriorityPicker(\'' + taskId + '\')" >';
		html += '				<div id="task_priority_icon_' + taskId + '" class="task_editor_icon task_priority ' + priority + '" ></div>';
		html += '				<div id="task_priority_label_' + taskId + '" class=" dropdown_toggle task_editor_property_label  task_priority_label">' + priorityLabel + '</div>';
		html += '			</div>';
		html += '			<div class="property_flyout task_priority_picker" id="priority_editor_' + taskId + '"></div>';
		html += '		</span>';
		html += '	</div>';

		html += '	<div class="breath-10"></div>';
					//assignment
		html += '	<div class="labeled_control assigment_editor">';
		html += '		<div class="property_label">' + labels.assignment  + '</div>';
		html += '		<span class="property_wrapper assignment_wrapper" id="task_assigment_' + taskId + '">';
		html += '			<div class="priority_option" id="task_editor_priority_toggle_' + taskId + '" onclick="displayPeoplePickerModal(\'' + taskId + '\')" >';
		//html += '				<div id="task_priority_icon_' + taskId + '" class="task_editor_icon task_priority ' + priority + '" ></div>';
		html += '				<div id="task_assignee_label_' + taskId + '" class="dropdown_toggle  task_editor_property_label task_assignee_label">' + assigneeLabel + '</div>';
		html += '			</div>';
		html += '			<div class="property_flyout task_assignee_picker" id="assignee_editor_' + taskId + '">assignees goes here</div>';
		html += ' 		</span>';
		html += '	</div>';

					//list
        if(isSubtask == false)
        {
            html += '	<div class="labeled_control list_editor">';
            html += '		<div class="property_label">' + taskStrings.list + '</div>';
            html += '		<span class="property_wrapper task_list_editor_wrapper" id="task_list_wrapper_' + taskId + '" onclick="showListPicker(\'' + taskId + '\')">';
            html += '			<div id="task_list_label_' + taskId + '" class="dropdown_toggle  task_editor_property_label list_label">' + listName + '</div>';
            html += '			<div class="property_flyout task_list_picker" id="list_picker_' + taskId + '"></div>';
            html += ' 		</span>';
            html += '	</div>';
        }

					//context
		html += '	<div class="labeled_control context_editor">';
		html += '		<div class="property_label">' + controlStrings.context + '</div>';
		html += '		<span onclick="showContextPicker(event, \'' + taskId + '\')" class="property_wrapper context_wrapper">';
		html += '			<div class="dropdown_toggle  task_editor_property_label context_label" id="task_editor_context_label_' + taskId + '">' + context + '</div>';
		html += '			<div class="property_flyout context_picker_flyout" id="context_editor_' + taskId + '"></div>';
		html += '		</span>';
		html += '	</div>';

		html += '	<div class="breath-10"></div>';
					//location
		html += '	<div class="labeled_control location_editor">';
		html += '		<div class="property_label">' + labels.location + '</div>';
		html += '		<span class="property_wrapper assignment_wrapper" id="task_assigment_' + taskId + '">';
		html += '			<div class="dropdown_toggle task_editor_property_label  task_location_type" id="task_location_type_' + taskId + '" onclick="displayLocationAlertOptions(\'' + taskId + '\')" value="' + locationType + '">' + locationTypeLabel + '</div>';
		html += '			<div class="property_flyout location_type_editor_flyout" id="location_type_editor_' + taskId + '"></div>';
		html += '			<div class="property_details location_address" id="location_details_' + taskId + '" style="' + locationDisplay + '">';
		html += '				<input class="task_location_address" id="task_location_address_' + taskId + '" type="text" value="' + locationAddress + '" onblur="addTaskLocationAlert(\'' + taskId + '\')" onkeydown="if (event.keyCode == 13) this.blur()" placeholder="' + labels.type_an_address + '"/>';
		html += '			</div>';
		html += ' 		</span>';
		html += '	</div>';
					//alerts
		html += '	<div class="labeled_control alerts_editor">';
		html += '		<div class="property_label">' + labels.alerts  + '</div>';
		html += '		<span class="property_wrapper alert_editor_wrapper" id="task_editor_alerts_' + taskId + '">';
		html += '			<div class="property_details task_alerts" id="task_alerts_in_editor_' + taskId + '" style="' + locationDisplay + '">';
		html += '			</div>';
		html += '			<div class="task_alert_type_wrap" id="new_task_alert_type_wrap_' + taskId + '" >';
		html += '				<div class="dropdown_toggle  task_editor_property_label task_alert" id="task_alert_type_options_toggle_' + taskId + '" onclick="displayAlertOptions(\'' + taskId + '\')" value="">' + labels.none + '</div>';
		html += '				<div class="property_flyout alert_type_options_flyout" id="alert_type_options_flyout_' + taskId + '"></div>';
		html += '			</div>';
		html += ' 		</span>';
		html += '	</div>';

		html += '	<div class="breath-10"></div>';
					//type
		html += '	<div class="labeled_control types_editor">';
		html += '		<div class="property_label">' + subStrings.type + '</div>';
		html += '		<span class="property_wrapper assignment_wrapper" id="task_editor_task_type_' + taskId + '">';
		html += '			<div class="dropdown_toggle  task_editor_property_label task_type_label"  onclick="showTaskTypeEditor(\'' + taskId + '\')" id="task_editor_task_type_label_' + taskId + '">' + taskTypeLabel+ '</div>';
		html += '			<div class="property_flyout task_type_editor" id="task_type_editor_' + taskId + '"></div>';
		html += '			<div class="property_details task_type_details" id="task_type_details_' + taskId + '" style="' + taskTypeDetailsDisplay + '">';
		html += '				<div class="property_wrapper task_type_data_actions_wrap">';
		html += '					<div id="task_type_data_toggle_' + taskId +'" class="dropdown_toggle task_type_data_toggle" onclick="showTaskActionOptions(\'' + taskId + '\')">';
		html += '						<div id="task_type_toggle_icon_' + taskId + '" class="task_editor_icon task_type ' + typeImgClass + '"></div>';
		html += '					</div>';
		html += '				    <span id="task_type_data_toggle_text_' + taskId + '" class="toggle_text">' + taskTypeDataLabel + '</span>';
		html += '					<div class="property_flyout task_actions_flyout" id="task_actions_flyout_' + taskId + '">' + taskTypeDataOptionsHTML + '</div>';
		html += '				</div>';
//		html += '				<input class="task_type_data" id="task_type_data_' + taskId + '" type="text" value="' + taskTypeData + '" onblur="updateTaskTypeData(\'' + taskId + '\')" onkeydown="if (event.keyCode == 13) this.blur()" placeholder="' + taskTypeDataPlaceholder + '" style="' + newTypeDataInputDisplay+ '"/>';
		html += '				<input class="task_type_data" id="task_type_data_' + taskId + '" type="text" value="' + taskTypeDataLabel.trim() + '" onblur="updateTaskTypeData(\'' + taskId + '\', true)" onkeydown="if(event.keyCode == 13){this.blur();}else{this.style.border = \'none\'}" placeholder="' + taskTypeDataPlaceholder + '" style="' + newTypeDataInputDisplay+ '"/>';
		html += '			</div>';
		html += ' 		</span>';
		html += '	</div>';

		html += '	<div class="breath-10"></div>';
					//tags
		html += '	<div class="labeled_control tags_editor">';
		html += '		<div class="property_label">' + taskStrings.tags + '</div>';
		html += '		<span onclick="showTagsEditor(event, \'' + taskId + '\')" class="property_wrapper tags_wrapper">';
		html += '			<div class="dropdown_toggle  task_editor_property_label tags_label" id="task_editor_tags_label_' + taskId + '">' + tagsLabel + '</div>';
		html += '			<div class="property_flyout tags_picker_flyout" id="tags_editor_' + taskId + '"></div>';
		html += '		</span>';
		html += '	</div>';

		html += '	<div class="breath-10"></div>';
		html += '	<hr />';
		html += '	<div class="breath-10"></div>';
					//notes
		html += '	<div class="labeled_control note_editor">';
		html += '		<div class="property_label" style="text-align:left;padding-left:10px">';
		html += '			<span id="note_label">' + labels.note + '</span>';
		html += '			<div class="task_icon note_zoom" id="note_zoom_icon_' + taskId + '" onclick="showNoteEditor(\'' + taskId + '\')"></div>';
		html += '		</div>';
		html += '		<textarea class="task_editor_task_note" id="task_editor_task_note_' + taskId + '" onblur="updateTaskNote(\'' + taskId + '\')">' + taskNote + '</textarea>';
		html += '	</div>';
		html += '	<div class="breath-10"></div>';
					//comments
		html += '	<div class="labeled_control comments_editor">';
		html += '		<div class="property_label" style="text-align:left;padding-left:10px">' + taskStrings.comments + '</div>';
		html += '		<div class="comments_wrap" id="comments_' + taskId + '"></div>';

		html += '		<div class="container add_comment">';
		//html += ' 			<img title="pigeon" src="https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif" class="comment_author_pic">';
		html += ' 			<div class="container comment_content">';
		html += '				<textarea class="new_comment_texarea" id="new_comment_' + taskId + '" onkeydown="if (event.keyCode == 13) this.onchange(event)" onchange=" postTaskComment(event, \'' + taskId + '\')" placeholder="'+labels.enter_a_new_comment+'"></textarea>';
		html += ' 			</div>';
		html += ' 		</div>';


		html += '	</div>';


		//html += '	<div class="control">' + displayHumanReadableDate(taskEl.getAttribute('duedate')) + '</div>';
		html += '</div>';


	editorEl.innerHTML = html;

	//load task alerts
	if (alertsCount > 0)
		loadTaskAlertsInEditor(taskId);

	if (commentsCount > 0)
		loadTaskCommentsInTaskEditor(taskId);
};


function loadTaskAlertsInEditor(taskId)
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
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                	var doc = document;
                	var notifications = response.notifications;
                	var alertsEl = doc.getElementById('task_alerts_in_editor_' + taskId);
                	var html = '';

                	//sort notifications
                    notifications.sort(function(a,b) { return a.triggerdate - b.triggerdate;});


                	for (var i = 0; i < notifications.length; i++)
                	{
	                	html += getHtmlForAlertInTaskEditor(notifications[i]);

                	}
                	alertsEl.innerHTML = html;
                	alertsEl.style.display = 'block';

                }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }
                        else
                            displayGlobalErrorMessage("Error from server: " + response.error);
                    }
                    else
                        displayGlobalErrorMessage(labels.failed_to_retrieve_alerts_for_task);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

 	var params = "method=getNotificationsForTask&taskid=" + taskId;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function getHtmlForAlertInTaskEditor(notification)
{
	var html = '';
	var id = notification.notificationid;
	var soundLabel = '';
	var offsetLabel = ''

	switch (notification.sound_name)
	{
		case 'none':
			soundLabel = labels.none;
			break;
		case 'bells':
			soundLabel = alertStrings.bells;
			break;
		case 'data':
			soundLabel = alertStrings.data;
			break;
		case 'flute':
			soundLabel = alertStrings.flute;
			break;
		case 'morse':
			soundLabel = alertStrings.morse;
			break;
		default:
			soundLabel = labels.none;
	}

	if (useNewTaskEditor)
		html += '<div id="task_alert_in_editor_icon_' + id + '" class="icon"></div>';

	if(typeof(notification.triggeroffset) == 'undefined'&& notification.triggerdate.length > 0)
	{
		offsetLabel = labels.custom;

		html += '	<div id="task_alert_in_editor_' + id + '" class="task_alert_in_editor" taskid="' + notification.taskid + '" triggerdate="' + notification.triggerdate + '">';
		html += '		<div class="task_alert_type_wrap" id="task_alert_type_wrap_' + id + '" >';
		html += '			<div class="dropdown_toggle task_alert" id="task_update_alert_type_options_toggle_' + id + '" onclick="displayUpdateAlertTypeOptions(\'' + id + '\')" value="">' + offsetLabel + '</div>';
		html += '			<div class="property_flyout alert_type_options_flyout" id="update_alert_type_options_flyout_' + id + '"></div>';
		html += '		</div>';
		html += '		<div class="task_alert_sound_wrap" id="update_task_alert_sound_wrap_' + id + '" style="display:inline-block">';
		html += '			<div class="dropdown_toggle task_alert" id="task_alert_sound_options_toggle_' + id + '" onclick="displayUpdateAlertSoundOptions(\'' + id + '\')" value="">' + soundLabel + '</div>';
		html += '			<div class="property_flyout alert_sound_options_flyout" id="update_alert_sound_options_flyout_' + id + '"></div>';
		html += '		</div>';
		html += '		<div class="task_alert_date" id="task_alert_date_' + id + '" >';
		html += '			<div class="task_alert_trigger_date_wrap">';
		html += '				<div class="task_alert_trigger_date_label" id="task_alert_trigger_date_label_' + id + '" onclick="displayTaskAlertDueDatePicker(\'' + id + '\', ' + notification.triggerdate + ')">' + displayHumanReadableDate(notification.triggerdate, false, true, true) + '</div>';
		html += '				<div id="due_date_editor_' + id + '" class="property_flyout task_alert_datepicker_wrapper" style="display:none">';
		html += '					<div id="datepicker_' + id + '" class="task_datepicker"> </div>';
		html += '				</div>';
		html += '			</div>';
		html += '			<div class="task_alert_trigger_time_wrap">';
		html += '				<div class="task_alert_trigger_time_label" id="task_alert_trigger_time_label_' + id + '" onclick="displayAlertDueTimeEditor(\'' + id + '\')">' + displayHumanReadableTime(notification.triggerdate) + '</div>';
		html += '				<div id="due_time_editor_flyout_' + id +'" class="property_flyout alert_due_time_editor_flyout">';
		html += '					<div id="task_due_time_editor_' + id + '" class="task_due_time_editor"> </div>';
		html += '				</div>';
		html += '			</div>';
		html += '		</div>';
		html += '	</div>';
	}
	else
	{

		if (notification.triggeroffset == 1)
		{
			offsetLabel = alertStrings.zeroMinutesBefore;
		}
		else
		{
			switch (notification.triggeroffset/60)
			{

				case 5:
					offsetLabel = alertStrings.fiveMinutesBefore;
					break;
				case 15:
					offsetLabel = alertStrings.fifteenMinutesBefore;
					break;
				case 30:
					offsetLabel	= alertStrings.thirtyMinutesBefore;
					break;
				case 60:
					offsetLabel = alertStrings.oneHourBefore;
					break;
				case 120:
					offsetLabel = alertStrings.twoHoursBefore;
					break;
				case 1440:
					offsetLabel = alertStrings.oneDayBefore;
					break;
				case 2880:
					offsetLabel = alertStrings.twoDaysBefore;
					break;
				default:
					offsetLabel = labels.unknown_alert;
			}
		}

		html += '	<div id="task_alert_in_editor_' + id + '" class="task_alert_in_editor" taskid="' + notification.taskid + '" offset="' + notification.triggeroffset + '">';
		html += '		<div class="task_alert_type_wrap" id="task_alert_type_wrap_' + id + '" >';
		html += '			<div class="dropdown_toggle task_alert" id="task_update_alert_type_options_toggle_' + id + '" onclick="displayUpdateAlertTypeOptions(\'' + id + '\')" value="">' + offsetLabel + '</div>';
		html += '			<div class="property_flyout alert_type_options_flyout" id="update_alert_type_options_flyout_' + id + '"></div>';
		html += '		</div>';
		html += '		<div class="task_alert_sound_wrap" id="update_task_alert_sound_wrap_' + id + '" style="display:inline-block">';
		html += '			<div class="dropdown_toggle task_alert" id="task_alert_sound_options_toggle_' + id + '" onclick="displayUpdateAlertSoundOptions(\'' + id + '\')" value="">' + soundLabel + '</div>';
		html += '			<div class="property_flyout alert_sound_options_flyout" id="update_alert_sound_options_flyout_' + id + '"></div>';
		html += '		</div>';
		html += '	</div>';
	}

	return html;
};


function displayMultiEditTagPicker()
{
    var headerHTML = labels.add_tags;
    var bodyHTML = '<div id="multi_edit_add_tag_modal_body">'+ labels.loading_tags + '</div>';


    var footerHTML = '<div class="button disabled" id="multiEditTagOKButton">' + labels.add_tags + '</div>';
    footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.cancel + '</div>';

    loadMultiEditTagsList();
    displayModalContainer(bodyHTML, headerHTML, footerHTML);
}

function enableMultiEditAddTagsButton()
{
	var okButton = document.getElementById('multiEditTagOKButton');

    var tagSelected = false;
    var tagOptions = document.getElementsByName('tags_picker_checkboxes_multi_edit');
    for(var i = 0; i < tagOptions.length; i++)
    {
        if(tagOptions[i].checked)
        {
            tagSelected = true;
            break;
        }
    }

	if(tagSelected)
    {
        okButton.onclick = function(){multi_edit_add_tag()};
        okButton.className = 'button';
    }
    else
    {
        okButton.onclick = null;
        okButton.className = 'button disabled';
    }
}


function loadMultiEditTagsList()
{

    //get tags from server
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
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
            catch(e){
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }

            if(ajaxRequest.responseText != "")
            {

            	var responseJSON = JSON.parse(ajaxRequest.responseText);

            	if(responseJSON.success == true)
            	{
	                var tagsJSON = responseJSON.tags;
	               	var innerHTML = htmlForTagPicker(tagsJSON, 'multi_edit');
                    document.getElementById('multi_edit_add_tag_modal_body').innerHTML = innerHTML;
                    updateTagStringForTagPicker(null, 'multi_edit');

                    document.getElementById('tags_picker_text_field_multi_edit').focus();

            	}
            	else
            		displayGlobalErrorMessage(labels.failed_to_retrieve_tags_for_multiedit_control +': ' + ajaxRequest.responseText);

            }
            else
            {
                displayGlobalErrorMessage(labels.unable_to_retrieve_tags_from_server_for_multiedit_control );
            }
        }
    }

    var params = 'method=getControlContent&type=tag';

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");


    ajaxRequest.send(params);
};




// ! Comments
function loadTaskCommentsInTaskEditor(taskId)
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
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success && response.comments)
                {
                    //var html = '<input type="hidden" id="object_comment_count_' + taskId + '" value="' + response.comments.length + '" />';
                    var html = '';
                    for(var i = 0; i < response.comments.length; i++)
                    {
                    	//var comment = response.comments[i];

                        html += htmlForComment(response.comments[i]);

                    }
					document.getElementById('comments_' + taskId).innerHTML = html;
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error=="authentication")
                        {
                            //make the user log in again
                            history.go(0);
                            return;
                        }

                        displayGlobalErrorMessage(response.error);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_load_tasks_comments+'.');
                    }

                }

            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	params = "method=getCommentsForObject&itemid=" + taskId + "&itemtype=7";
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

// Share list functions

function showToolbarEmailShareFlyout()
{
    var flyout = document.getElementById('toolbar_email_share_flyout');

    var html = '<textarea id="toolbar_email_textarea" style="height:100px;width:300px;margin:10px 10px 10px 10px;" placeholder="'+labels.enter_one_email_address_per_line+'" onkeyup="shouldEnableToolbarSendEmailButton(this)"></textarea>';
    html += '<div style="margin: 10px 0 10px 15px;">';
    html += labels.set_as + ' <select id="toolbar_roleselect" style="width:150px;height:20px;" >';
    html += '<option selected="selected" value="1">'+labels.members+'</option>';
    html += '<option value="2">'+labels.owners+'</option>';
    html += '</select> '+labels.of_the_list;
    html += '</div>';

    html += '<div id="toolbar_send_email_button" class="toolbar_button" style="float:right;margin: 5px 5px 5px 0;height:24px;" onclick="toolbarEmailList()">'+labels.invite+'</div>';

    flyout.innerHTML = html;
    flyout.style.display = 'block';
    document.getElementById('toolbar_email_share_background').style.width = '100%';
    document.getElementById('toolbar_email_share_background').style.height = '100%';

    document.getElementById('toolbar_email_textarea').focus();
    shouldEnableToolbarSendEmailButton(document.getElementById('toolbar_email_textarea'));

}
function hideToolbarEmailShareFlyout()
{
    document.getElementById('toolbar_email_share_flyout').style.display = 'none';
    document.getElementById('toolbar_email_share_background').style.width = '0px';
    document.getElementById('toolbar_email_share_background').style.height = '0px';
}
function shouldEnableToolbarSendEmailButton(inputEl)
{
    var enableButton = inputEl.value.length > 0 ? true : false;
	var button = document.getElementById('toolbar_send_email_button')

	if (enableButton)
	{
		button.setAttribute('class', 'toolbar_button');
		button.onclick = function(){toolbarEmailList();};
	}
	else
	{
		button.setAttribute('class', 'toolbar_button disabled');
		button.onclick = null;
	}

}

function displayShareListPremiumDialog()
{
    var header = labels.premium_feature;
    var body = labels.sharing_lists_is_a_premium_feature;
    var footerHTML =    '<a class="button" style="margin:5px 5px 5px 0px; float:right;" href="?appSettings=show&option=subscription">'+labels.go_premium+'</a>';
        footerHTML +=   '<div class="button" style="margin:5px 5px 5px 0px; float:right;" onclick="hideModalContainer()">'+labels.later+'</div>';

    displayModalContainer(body, header, footerHTML);
}

function toolbarEmailList()
{
    var listid = document.getElementById('currentListId').value;
    var role = document.getElementById('toolbar_roleselect').value;
    var email = document.getElementById('toolbar_email_textarea').value;


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
                    hideToolbarEmailShareFlyout();
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
                        else if(response.error == "premium")
                        {
                            displayShareListPremiumDialog();
                        }
                        else
                        {
                            displayGlobalErrorMessage(response.error);
                        }
                    }
                    else
                    {
                       displayGlobalErrorMessage(labels.unable_to_send_invitations);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }


    var params = "method=emailInvites&listid=" + listid + "&email=" + encodeURIComponent(email) + "&role=" + role;

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");


    ajaxRequest.send(params);

}

// !Auxiliary Functions
function isTaskSubtask(taskId)
{
    return (document.getElementById(taskId).getAttribute("isSubtask") == "true");
}

function isTaskTaskito(taskId)
{
	var doc = document;
	var taskEl = doc.getElementById(taskId);
	var parentId = taskEl.getAttribute('parentid');
	var parentEl = null;
	var isTaskito = false

	if (parentId.length > 0)
	{
		parentEl = doc.getElementById(parentId);

		if (parentEl != null && parentEl.getAttribute('taskype') == '7'/*checklist*/)
			isTaskito = true;
	}

	return isTaskito;
};

function parentIdForTask(taskId)
{
    if(isTaskSubtask)
    {
        return(document.getElementById("project_subtask_" + taskId).getAttribute("parentid"));
    }
    return null;
}

function replaceURLWithHTMLLinks(text) {
    var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
    return text.replace(exp,"<a target=\"_blank\" href='$1'>$1</a>");
}


function replaceEmailAddressesWithHTMLLinks(text)
{
	var exp = /(\S+@\S+\.\S+)/ig;
	return text.replace(exp, "<a target=\"_blank\" href='mailto:$1'>$1</a>");
}

function taskListRowsClass(taskId) {
    if (typeof taskId !== 'undefined' && jQuery('#' + taskId).hasClass('task_wrap')) {
        shouldDisplaySecondRow(taskId);
    } else {
        jQuery('.tasks_container_wrap .task_wrap').each(function () {
            shouldDisplaySecondRow(jQuery(this).attr('id'));
        });
    }

}
function addClassTaskMultiline(taskId) {
    var el = jQuery('#' + taskId + ' .task_right_properties');
    var second_row = jQuery('.task_bottom_properties', el);
    var multiline = false;
    jQuery('>*', second_row).each(function () {
        if (jQuery(this).is(':visible')) {
            multiline = true;
            el.addClass('display-bottom-properties');
            return false;
        }
    });
    if (!multiline) {
        el.removeClass('display-bottom-properties');
    }
    return multiline;
}

function getTaskDataFlyoutContent(taskId) {
    var doc = document;
    var taskEl = doc.getElementById(taskId);
    var taskTypeData = taskEl.getAttribute('tasktypedata');
    var taskTypeDataLabel = '';
    var taskType = parseInt(taskEl.getAttribute('tasktype'), 10);
    var taskTypeDataPlaceholder = '';
    switch (taskType) {
        case 2:
            taskTypeDataPlaceholder = taskStrings.enterPhoneNumber;
            break;
        case 3:
            taskTypeDataPlaceholder = taskStrings.enterPhoneNumber;
            break;
        case 4:
            taskTypeDataPlaceholder = taskStrings.enterEmailAddress;
            break;
        case 5:
            taskTypeDataPlaceholder = taskStrings.enterStreetAddress;
            break;
        case 6:
            taskTypeDataPlaceholder = taskStrings.enterWebsiteAddress;
            break;
        case 0:
        case 1:
        case 7:
            break;
    }
    if (taskTypeData !== '') {
        if (taskTypeData.indexOf('Task Type') == -1) //new format is NOT found format old data into new data
        {
            var typeDataString = '';
            var valueDescription = 'other:';
            switch (taskType) {
                case 2://sms
                    typeDataString = 'Call';
                    break;
                case 3: //call
                    typeDataString = 'SMS';
                    break;
                case 4: //email
                    typeDataString = 'Email';
                    break;
                case 5://address
                    typeDataString = 'Location';
                    valueDescription = 'location:';
                    break;
                case 6://website
                    typeDataString = 'URL';
                    valueDescription = 'url:';
                    break;
                case 0:
                case 1:
                case 7:
                    break;
            }

            var newTaskTypeDataValue = taskTypeData;

            taskTypeData = '';
            taskTypeData += '---- Task Type: ' + typeDataString + ' ---- \n';
            taskTypeData += 'contact: ' + newTaskTypeDataValue + ' \n';
            taskTypeData += valueDescription + ' ' + newTaskTypeDataValue + ' \n';
            taskTypeData += '---- End Task Type ----';
        }

        var taskTypeAction = '';
        var taskTypeDataArray = taskTypeData.split('\n');
        taskTypeDataLabel = taskTypeDataArray[1].replace('contact: ', '');
        var taskTypeDataOptionsHTML = ''


        taskTypeDataOptionsHTML += '<label>' + taskTypeDataLabel + '</label>';
        taskTypeDataOptionsHTML += '<hr style="margin: 6px auto"/>';

        for (var i = 0; i < taskTypeDataArray.length; i++) {
            if (taskTypeDataArray[i].indexOf('---- Task Type:') == -1 && taskTypeDataArray[i].indexOf('contact:') == -1 && taskTypeDataArray[i].indexOf('---- End Task Type ----') == -1) {
                var actionElArray = [];

                switch (taskType) {
                    case 4: //email
                        actionElArray = taskTypeDataArray[i].split(' ');
                        taskTypeAction = 'parent.location=\'mailto:' + actionElArray[1] + '\'';
                        break;
                    case 5: //location
                        if (taskTypeDataArray[i].replace('location: ', '').length > 1) {
                            taskTypeAction = 'window.open(\'http://maps.google.com/maps?q=' + taskTypeDataArray[i].replace('location: ', '') + '\')';
                        }
                        break;
                    case 6: //URL
                        var url = taskTypeDataArray[i].replace('url: ', '');
                        if (url.indexOf('http://') == -1 && url.indexOf('https://') == -1) {
                            url = 'http://' + url;
                        }
                        taskTypeAction = 'window.open(\'' + url + '\')';
                        break;
                    default:
                        break;
                }

                taskTypeDataOptionsHTML += '<div class="picker_option" onclick="' + taskTypeAction + '"> ' + taskTypeDataArray[i] + '</div>';
            }
        }

        taskTypeDataOptionsHTML += '<hr style="margin: 6px auto"/>';
        taskTypeDataOptionsHTML += '<div class="picker_option" onclick="displayTaskTypeDataInput(event, \'' + taskId + '\')">' + taskTypeDataPlaceholder + '</div>';
        return taskTypeDataOptionsHTML;
    }
    else //if there is no metadata, display input field
    {
        return false;
    }
}
function liveTaskSort(sectionCounts, skipNewSection) {
    if (pageSortOrder === 0) {
        sortTaskByTime(sectionCounts, skipNewSection);
    } else if (pageSortOrder === 1) {
        sortTaskByPriority(sectionCounts, skipNewSection);
    } else {
        showNotEmptySections(false, window.preocessed_sections);
    }
}
function sortTaskByTime(sectionCounts, skipNewSection) {

	sectionCounts = typeof sectionCounts !== 'undefined' ? sectionCounts : false;
    skipNewSection = typeof skipNewSection !== 'undefined' ? skipNewSection : false;

    if(parseInt(showCompletedTasks)){
        showNotEmptySections(false, 2);
		return false;
    }
    
	var taskSections = {
		overdue: 'overdue_tasks_container_wrap',
		new: 'new__tasks_container_wrap',
		today: 'today_tasks_container_wrap',
		tomorrow: 'tomorrow_tasks_container_wrap',
		future: 'future_tasks_container_wrap',
		nextsevendays: 'nextsevendays_tasks_container_wrap',
		noduedate: 'noduedate_tasks_container_wrap',
		completed: 'completed_tasks_container_wrap'
	};
	if (sectionCounts && Object.keys(taskSections).length !== sectionCounts) {
		return false;
	}

	var now = Date.now();
	var today = new Date();
	today.setHours(0);
	today.setMinutes(0);
	today.setSeconds(0);
	today.setMilliseconds(0);
	today.setDate(today.getDate() + 1);

	var yesterday = new Date(today);
	yesterday.setDate(yesterday.getDate() - 1);
	var tomorrow = new Date(today);
	tomorrow.setDate(tomorrow.getDate() + 1);
	var after_tomorrow = new Date(today);
	after_tomorrow.setDate(after_tomorrow.getDate() + 2);

	var seven_days = new Date(today);
	seven_days.setDate(seven_days.getDate() + 7);

	//now = now;
	today = today.getTime();
	tomorrow = tomorrow.getTime();
	yesterday = yesterday.getTime();
	after_tomorrow = after_tomorrow.getTime();
	seven_days = seven_days.getTime();


	var tasks = jQuery('.section_tasks_container > .task_wrap').each(function () {
		var task = jQuery(this);
        var section_container_id = task.parents('.tasks_container_wrap');
        if(skipNewSection && section_container_id.attr('id') === 'new__tasks_container_wrap'){
            return;
        }
		var start_date = parseInt(task.attr('startdate')) * 1000;
		var due_date = parseInt(task.attr('duedate')) * 1000;
		var child_due_date = parseInt(task.attr('childduedate')) * 1000;
		var has_due_time = parseInt(task.attr('hasduetime'));
		var child_has_due_time = parseInt(task.attr('childduedatehastime'));
		var task_section = jQuery(task).parents('.tasks_container_wrap')
		var task_section_id = task_section.attr('id');
		var task_container_class = 'section_tasks_container';
		
		if(due_date === 0 && child_due_date > 0){
			due_date = child_due_date;
			has_due_time = child_has_due_time;
		}
		//overdue
		if (due_date > 0 && ((has_due_time === 1 && due_date <= now) || (has_due_time === 0 && due_date < yesterday))) {
			if (task_section_id === taskSections.overdue) {
				return;
			}
			moveTaskAndSubtask(taskSections.overdue, task_container_class, task);
		} else
		//today
		if (due_date > 0 && due_date >= yesterday && due_date < today) {

			if (task_section_id === taskSections.today) {
				return;
			}
			moveTaskAndSubtask(taskSections.today, task_container_class, task);
		} else
		//tomorrow
		if (/*(start_date === 0 || start_date > today) && */(due_date > 0 && due_date <= tomorrow)) {
            if (task_section_id === taskSections.tomorrow) {
				return;
			}
			moveTaskAndSubtask(taskSections.tomorrow, task_container_class, task);
		} else
		//future
		if (/*(start_date === 0 || start_date > seven_days) && */(due_date > 0 && due_date > seven_days)) {
            if (task_section_id === taskSections.future) {
				return;
			}
			moveTaskAndSubtask(taskSections.future, task_container_class, task);
		} else
		//seven days
		if (/*(start_date === 0 || start_date > after_tomorrow) &&*/ (due_date > 0 && due_date <= seven_days)) {
            if (task_section_id === taskSections.nextsevendays) {
				return;
			}
			moveTaskAndSubtask(taskSections.nextsevendays, task_container_class, task);
		}
		else if (start_date === 0 && due_date === 0) {
			if (task_section_id === taskSections.noduedate) {
				return;
			}
			moveTaskAndSubtask(taskSections.noduedate, task_container_class, task);
		}
	});
	jQuery.each(taskSections, function (index, value) {
		var section = jQuery('#' + value);
		var task_container = jQuery('.section_tasks_container', section);
		if (jQuery('.section_tasks_container > .task_wrap', section).size()) {
            sortingTasksExtension(section.find('.section_tasks_container > .task_wrap'), task_container);
			if (jQuery('.subtasks_wrapper', task_container).size()) {
				jQuery('.section_tasks_container > .task_wrap', section).each(function () {
					var taskId = jQuery(this).attr('id');
					if (jQuery('#subtasks_wrapper_' + taskId).size()) {
						jQuery(this).after(jQuery('#subtasks_wrapper_' + taskId));
                        if(jQuery('#subtasks_' + taskId + ' > .task_wrap').size()){
                            sortingTasksExtension(jQuery('#subtasks_' + taskId + ' > .task_wrap'), jQuery('#subtasks_' + taskId));
                            jQuery('#subtasks_' + taskId + ' > .task_wrap').each(function () {
                                var subtask_item = jQuery(this);
                                var subtaskId = subtask_item.attr('id');
                                jQuery(subtask_item).after(jQuery('#subtasks_wrapper_' + subtaskId));
                            });
                        }
					}
				});
			}
			section.removeClass('hidden');
		} else {
			section.addClass('hidden');
		}
	});
}

function sortingTasksExtension(tasks, task_container) {
    tasks.sort(function (a, b) {
        var dateA = parseInt(jQuery(a).attr('duedate'));
        var dateB = parseInt(jQuery(b).attr('duedate'));
        var priorityA = (parseInt(jQuery(a).attr('priority')) === 0) ? 10 : parseInt(jQuery(a).attr('priority'));
        var priorityB = (parseInt(jQuery(b).attr('priority')) === 0) ? 10 : parseInt(jQuery(b).attr('priority'));
        var orderA = parseInt(jQuery(a).attr('taskorder'));
        var orderB = parseInt(jQuery(b).attr('taskorder'));
        if (dateA === dateB) {
            if (priorityA === priorityB || (priorityA === 0 && priorityB === 0)) {
                return (orderA < orderB ) ? -1 : (orderA > orderB ) ? 1 : 0;
            } else {
                return (priorityA < priorityB ) ? -1 : (priorityA > priorityB ) ? 1 : 0;
            }
        }
        else {
            return (dateA < dateB ) ? -1 : (dateA > dateB ) ? 1 : 0;
        }
    }).appendTo(task_container);
}

function moveTaskAndSubtask(section, task_container_class, task) {
	jQuery('#' + section + ' .' + task_container_class).append(task);
	var section = jQuery('#' + section);
	var task_container = jQuery('.section_tasks_container', section);

	var taskId = task.attr('id');
	if (jQuery('#subtasks_wrapper_' + taskId).size()) {
		setTimeout(function(){
			task.after(jQuery('#subtasks_wrapper_' + taskId))
		},70);
	}
}
function showNotEmptySections(sectionId, sections_count) {
    if(taskSections.length !== sections_count){
        return false;
    }
    sectionId = typeof sectionId !== 'undefined' ? sectionId : false;
    if (sectionId) {
        var section_wrapper = jQuery('#' + sectionId + '_wrapper');
        if (jQuery('.task_wrap', section_wrapper).size() > 0) {
            section_wrapper.removeClass('hidden');
        }
    }else{
        jQuery('.tasks_container_wrap').each(function () {
            if (jQuery('.task_wrap', this).size() > 0) {
                jQuery(this).removeClass('hidden');
            }
        });
    }
}

function updateTaskSortOrder(taskId) {
    var task_wrapper = jQuery('#' + taskId);
    if (task_wrapper.parents('.task_subtasks_container').size()) {
        var section_wrapper = task_wrapper.parents('.task_subtasks_container');
    } else {
        var section_wrapper = task_wrapper.parents('.section_tasks_container');
    }
    var all_tasks_in_section = jQuery('> .task_wrap', section_wrapper);
    var tmp_taskid = '';

    var order_iterator = 0;
    var tasks_to_update = {};

    all_tasks_in_section.each(function () {
        tmp_taskid = jQuery(this).attr('id');
        tasks_to_update[tmp_taskid] = {sort_order: order_iterator};
        jQuery(this).attr('taskorder', ++order_iterator);
    });
    saveTasksOrdering(tasks_to_update);
}
function saveTasksOrdering(tasks) {
    var data = {};
    data.method = 'groupUpdateTask';
    data.tasks = tasks;
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/',
        data: serialize(data),
        success: function (json) {
        }
    });
}
function sortTaskByPriority(sectionId, sections_count) {

    sectionCounts = typeof sectionCounts !== 'undefined' ? sectionCounts : false;
    skipNewSection = typeof skipNewSection !== 'undefined' ? skipNewSection : false;

    if(parseInt(showCompletedTasks)){
        showNotEmptySections(false, 2);
        return false;
    }

    var taskSections = {
        overdue: 'overdue_tasks_container_wrap',
        new: 'new__tasks_container_wrap',
        high: 'high_tasks_container_wrap',
        medium: 'medium_tasks_container_wrap',
        low: 'low_tasks_container_wrap',
        none: 'none_tasks_container_wrap',
        completed: 'completed_tasks_container_wrap'
    };
    if (sectionCounts && Object.keys(taskSections).length !== sectionCounts) {
        return false;
    }

    var tasks = jQuery('.section_tasks_container > .task_wrap').each(function () {
        var task = jQuery(this);
        var section_container_id = task.parents('.tasks_container_wrap');
        if(skipNewSection && section_container_id.attr('id') === 'new__tasks_container_wrap'){
            return;
        }
        var task_priority = parseInt(task.attr('priority'));
        var task_section = jQuery(task).parents('.tasks_container_wrap');
        var task_section_id = task_section.attr('id');
        var task_container_class = 'section_tasks_container';

        //None Priority
        if (task_priority === 0) {
            moveTaskAndSubtask(taskSections.none, task_container_class, task);
        } else
        //Low Priority
        if (task_priority === 9) {
            moveTaskAndSubtask(taskSections.low, task_container_class, task);
        }
        else
        //Medium Priority
        if (task_priority === 5) {
            moveTaskAndSubtask(taskSections.medium, task_container_class, task);
        }
        else
        //High Priority
        if (task_priority === 1) {
            moveTaskAndSubtask(taskSections.high, task_container_class, task);
        }
    });
    jQuery.each(taskSections, function (index, value) {
        var section = jQuery('#' + value);
        var task_container = jQuery('.section_tasks_container', section);
        if (jQuery('.section_tasks_container > .task_wrap', section).size()) {
            sortingTasksExtension(section.find('.section_tasks_container > .task_wrap'), task_container);
            if (jQuery('.subtasks_wrapper', task_container).size()) {
                jQuery('.section_tasks_container > .task_wrap', section).each(function () {
                    var taskId = jQuery(this).attr('id');
                    if (jQuery('#subtasks_wrapper_' + taskId).size()) {
                        jQuery(this).after(jQuery('#subtasks_wrapper_' + taskId));
                        if(jQuery('#subtasks_' + taskId + ' > .task_wrap').size()){
                            sortingTasksExtension(jQuery('#subtasks_' + taskId + ' > .task_wrap'), jQuery('#subtasks_' + taskId));
                            jQuery('#subtasks_' + taskId + ' > .task_wrap').each(function () {
                                var subtask_item = jQuery(this);
                                var subtaskId = subtask_item.attr('id');
                                jQuery(subtask_item).after(jQuery('#subtasks_wrapper_' + subtaskId));
                            });
                        }
                    }
                });
            }
            section.removeClass('hidden');
        } else {
            section.addClass('hidden');
        }
    });

}


jQuery(document).ready(function () {
    jQuery('.create_task_wrapper .task-types input[type="radio"]').on('change', function (e) {
        var container = jQuery(this).parents('.task-types');
        jQuery('span', container).removeClass('active');
        jQuery(this).parents('span').addClass('active');
    });
    jQuery('#task_sections_wrapper').mouseup(function (e) {
        var task_wrap_container = jQuery(".task_wrap");
        if (task_wrap_container.has(e.target).length === 0) {
            unselectAllSelectedTasks();
        }
    });
    jQuery(document).keyup(function (e) {
        //key code DELETE
        if (e.keyCode == 46) {
            if (!jQuery('input').is(':focus') && !jQuery('.property_flyout').is(':visible') && !jQuery('#modal_container').is(':visible') && selectedTasks.length) {
                displayMultiEditDeleteDialog();
            }
        }
        if (e.keyCode == 13) {
            //key code Enter
            if (selectedTasks.length && jQuery('#multiEditListOkButton').is(':visible')) {
                multi_edit_delete();
            }
        }
    });
});
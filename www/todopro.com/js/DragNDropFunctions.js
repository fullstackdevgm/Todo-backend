// ! Context
function contextCatchDrop(event, contextId)
{
	var taskId = event.dataTransfer.getData("Text");
	event.preventDefault();
	
	resetContextEl(contextId);
		
	//check for multiple task selection
	var taskIds = selectedTasks;
	if(taskIds.indexOf(taskId) > -1)
	{
		for (var i = 0; i < taskIds.length; i++)
		{
			updateContextForTask(null, taskIds[i], contextId, true);
		}
	}	
	else
	{
		updateContextForTask(null, taskId, contextId, true);
	}
	
	contextDragLeave(event, contextId);
};

function contextDragEnter(event, contextId)
{
	contextId = contextId == '0' ? 'no_context' : contextId;
	event.preventDefault();
	
	var doc = document;
	var contextEl = doc.getElementById(contextId);
		contextEl.setAttribute('class', 'group_option drag_over');
	
	return false;
};

function contextDragLeave(event, contextId)
{
	event.preventDefault();
	
	resetContextEl(contextId);
	
	return false;
};

function resetContextEl(contextId)
{
	contextId = contextId == '0' ? 'no_context' : contextId;
	
	var doc = document;
	var contextEl = doc.getElementById(contextId);
		contextEl.setAttribute('class', 'group_option');
};

// ! Tags
function tagCatchDrop(event, tagId, tagName)
{
	event.preventDefault();
	
	resetTagEl(tagId);
	
	var taskId = event.dataTransfer.getData("Text");
	
	//check for multiple task selection
	var taskIds = selectedTasks;
	if(taskIds.indexOf(taskId) > -1)
	{
		
		for (var i = 0; i < taskIds.length; i++)
		{
			addTagToTask(taskIds[i], tagName, true);
		}
	}	
	else
	{
		addTagToTask(taskId, tagName, true);
	}
	
	tagDragLeave(event, tagId);
};

function tagDragEnter(event, tagId)
{
	event.preventDefault();
	
	var doc = document;
	var tagEl = doc.getElementById(tagId);
		tagEl.setAttribute('class', 'group_option drag_over');
	
	return false;
};

function tagDragLeave(event, tagId)
{
	event.preventDefault();
	
	resetContextEl(tagId);
	
	return false;
};

function resetTagEl(tagId)
{
	var doc = document;
	var tagEl = doc.getElementById(tagId);
		tagEl.setAttribute('class', 'group_option');
};

// ! Lists
function listCatchDrop(event, listId, listColor)
{
	if (canTaskBeDroppedInCustomList())
	{
		if(event)
		event.preventDefault();
	
		resetListEl(listId);
		
		var taskId = event.dataTransfer.getData("Text");
		
		//check for multiple task selection
		var taskIds = selectedTasks;
		if(taskIds.indexOf(taskId) > -1)
		{
			
			for (var i = 0; i < taskIds.length; i++)
			{
				updateListForTask(null, taskIds[i], listId, listColor)
			}
			unselectAllSelectedTasks();
		}	
		else
		{
			updateListForTask(null, taskId, listId, listColor)
		}
	}
	
	listDragLeave(event, listId);
};

function listDragEnter(event, listId)
{
	if (canTaskBeDroppedInCustomList())
	{
		if(event)
		{
			event.preventDefault();
			stopEventPropogation(event);	
		}
		var doc = document;
		var listEl = doc.getElementById(listId);
			listEl.classList.add('drag_over');
	}

	return false;
	
};

function listDragLeave(event, listId)
{
	if(event)
	{
		event.preventDefault();
		stopEventPropogation(event);	
	}
	
	resetListEl(listId);
	
	return false;
};

function resetListEl(listId)
{
	var doc = document;
	var listEl = doc.getElementById(listId);
		listEl.classList.remove('drag_over');
};


// !Star list
function starListCatchDrop(event)
{
	if (canTaskBeDroppedInStarredList())
	{
	
		event.preventDefault();
		
		resetStarListEl();
		
		var taskId = event.dataTransfer.getData("Text");
		
		//check for multiple task selection
		var taskIds = selectedTasks;
		if(taskIds.indexOf(taskId) > -1)
		{
			
			for (var i = 0; i < taskIds.length; i++)
			{
				updateTaskStar(taskIds[i], true);
			}
		}	
		else
		{
			updateTaskStar(taskId, true);
		}
	}
	
	starListDragLeave(event);
};

function starListDragEnter(event)
{
	if (canTaskBeDroppedInStarredList())
	{
		event.preventDefault();
		
		var doc = document;
		var listEl = doc.getElementById('starred_list');
			listEl.setAttribute('class', 'group_option drag_over');
	}
	
	return false;
};

function starListDragLeave(event)
{
	event.preventDefault();
	
	resetStarListEl();
	
	return false;
};

function resetStarListEl()
{
	var doc = document;
	var listEl = doc.getElementById('starred_list');
		listEl.setAttribute('class', 'group_option');
};

function canTaskBeDroppedInCustomList()
{
	var doc = document;
	var result = true;
	var taskId = draggedTaskId;
	
	if (selectedTasks.length > 0 && selectedTasks.indexOf(draggedTaskId) > -1)
	{
		for (var i = 0; i < selectedTasks.length; i++)
		{
			var taskEl = doc.getElementById(taskId);	
			var parentId = taskEl.getAttribute('parentid');
	
			if (parentId) 
			{
				result = false;
			}
		}
	}
	else 
	{
		var taskEl = doc.getElementById(taskId);	
		var parentId = taskEl.getAttribute('parentid');

		if (parentId) 
		{
			result = false;
		}
	}
	

				
	return result;
};

function canTaskBeDroppedInStarredList()
{
	var doc = document;
	var result = true;
	var taskId = draggedTaskId;

	var taskEl = doc.getElementById(taskId);	
	var parentId = taskEl.getAttribute('parentid');
	
	if (parentId) 
	{
		var parentType = parseInt(doc.getElementById(parentId).getAttribute('tasktype'), 10);
		
		if (parentType == 7) //checklist
			result = false;
	}
				
	return result;
};


// !Calendar dates
function dateDragEnter(event)
{
	if (canTaskBeDroppedInDate())
	{
		event.preventDefault();
		
		var doc = document;
		var dateEl = doc.getElementById('starred_list');
			dateEl.setAttribute('class', 'group_option drag_over');
	}
	
	return false;
};
function dateCatchDrop(event, unixDate)
{
	if (canTaskBeDroppedInDate())
	{
		var doc = document;
		var taskId = event.dataTransfer.getData("Text");
		event.preventDefault();
		
		///resetContextEl(contextId);
			
		//check for multiple task selection
		var taskIds = selectedTasks;
		if(taskIds.indexOf(taskId) > -1)
		{
			
			
			for (var i = 0; i < taskIds.length; i++)
			{
				var taskEl = doc.getElementById(taskIds[i]);
				var hasDueTime = parseInt(taskEl.getAttribute('hasduetime'), 10);
				
				updateTaskDueDate(taskIds[i], unixDate, hasDueTime, true)
			}
		}	
		else
		{
			var taskEl = doc.getElementById(taskId);
			var hasDueTime = parseInt(taskEl.getAttribute('hasduetime'), 10);
			
			updateTaskDueDate(draggedTaskId, unixDate, hasDueTime, true);
		}
	
	//contextDragLeave(event, contextId);
	}
};

function canTaskBeDroppedInDate()
{
	var doc = document;
	var result = true;
	var taskId = draggedTaskId;

	var taskEl = doc.getElementById(taskId);	
	var parentId = taskEl.getAttribute('parentid');
	
	if (parentId) 
	{
		var parentType = parseInt(doc.getElementById(parentId).getAttribute('tasktype'), 10);
		
		if (parentType == 7) //checklist
			result = false;
	}
				
	return result;
};


// ! Section headers
function sectionHeaderDragEnter(event, sectionId)
{
	event.preventDefault();
		
    if(canTaskBeDroppedInSectionHeader(draggedTaskId, sectionId))
    {
        var doc = document;
        var sectionHeaderEl = doc.getElementById('section_header_wrap_' + sectionId);
        sectionHeaderEl.setAttribute('class', 'section_header_wrap drag_over');
    }

	return false;
};

function sectionHeaderDragLeave(event, sectionId)
{
	event.preventDefault();
	
	var doc = document;
	var sectionHeaderEl = doc.getElementById('section_header_wrap_' + sectionId);
		sectionHeaderEl.setAttribute('class', 'section_header_wrap');
		
	return false;	 
};

function canTaskBeDroppedInSectionHeader(draggedTaskId, sectionId)
{
    if(draggedTaskId == null || sectionId == null || (curSearchString != null && curSearchString.length > 0) || sectionId == 'new_')
        return false;

    return true;
}

function sectionHeaderCatchDrop(event, sectionId)
{
    if(canTaskBeDroppedInSectionHeader(draggedTaskId, sectionId))
    {
        
        
        //check for multiple task selection
		var taskIds = selectedTasks;
		if(taskIds.indexOf(draggedTaskId) > -1)
		{
			for (var i = taskIds.length -1; i >= 0; i--)
			{
				moveTaskToSection(event, taskIds[i], sectionId);
				//updateTaskDueDate(taskIds[i], unixDate, null, true)
			}
		}	
		else
		{
			moveTaskToSection(event, draggedTaskId, sectionId);
			//updateTaskDueDate(draggedTaskId, unixDate, null, true);
		}
	}
	
	sectionHeaderDragLeave(event, sectionId);
};

function moveTaskToSection(event, taskId, sectionId)
{
/* 	console.log('moving ' + document.getElementById('task_name_' + taskId).innerHTML); */
	
	var sectionEl = document.getElementById(sectionId + '_tasks_container');
    var targetTask = null;
    var child = sectionEl.firstChild;
    if(child != null && child.className == 'task_wrap')
    {
        targetTask = child;
    }
	//If the dragged task is a subtask, first item of business is to move it out of its parent
        var draggedTask = document.getElementById(taskId);
/*
        if(targetTask == draggedTask)
        {
            sectionHeaderDragLeave(event, sectionId);
            return;
        }
*/
        
        var draggedParentId = draggedTask.getAttribute('parentid');
        
            
        var draggedSubtasks = null;
        
        if(draggedParentId)
        {
            var draggedParentType = parseInt(document.getElementById(draggedParentId).getAttribute('tasktype') , 10);
            
            moveTaskOutOfParentToSection(taskId, draggedParentId, sectionEl);
            
            //update values based on current task sorting
            if (draggedParentType == 7)
                taskId = newlyCreatedTaskId;
        }
        else
        {
            draggedSubtasks = document.getElementById('subtasks_wrapper_' + taskId);
            
            //move dragged task UI below target task
            draggedTask.parentNode.removeChild(draggedSubtasks);
            draggedTask.parentNode.removeChild(draggedTask);

            sectionEl.insertBefore(draggedSubtasks, sectionEl.firstChild);
            sectionEl.insertBefore(draggedTask, sectionEl.firstChild);
            
        }
        
        
        if(sectionId == 'completed')
        {
            if(draggedTask.getAttribute('iscompleted') == 'false')
            {
                var completionTimestamp = Math.round(+new Date()/1000);
                if(targetTask != null)
                {
                    completionTimestamp = targetTask.getAttribute('completiondate');
                }
                completeTask(null, taskId, completionTimestamp);
            }
        }
        else
        {
            if(draggedTask.getAttribute('iscompleted') == 'true')
            {
                uncompleteTask(null, taskId);
            }
            var elements_after = jQuery('#' + taskId).nextAll('.task_wrap');
            var next_element = elements_after[0];
            var next_task_priority = undefined;
            if (typeof next_element != 'undefined') {
                next_task_priority = parseInt(jQuery(next_element).attr('priority'));
            }
            updatePriorityForTask(newTaskPriority(next_task_priority, next_task_priority), taskId, true);
            switch(pageSortOrder)
            {
                case 0: //due date sorting
                {
                    var duedate;
                    var hastime;
                    
/*
                    if(targetTask != null)
                    {
                        duedate = targetTask.getAttribute('duedate');
                        hastime = targetTask.getAttribute('hasduetime');
                    }
                    else
                    {
*/
                        hastime = 0;
                        if(sectionId == 'overdue')
                        {
                            var overdue = new Date();
                            overdue.setDate(overdue.getDate() - 1);
                            duedate = overdue.getTime() / 1000;
                        }
                        else if(sectionId == 'today')
                        {
                            var today = new Date();
                            duedate = today.getTime() / 1000;
                        }
                        else if(sectionId == 'tomorrow')
                        {
                            var tomorrow = new Date();
                            tomorrow.setDate(tomorrow.getDate() + 1);
                            duedate = tomorrow.getTime() / 1000;
                        }
                        else if(sectionId == 'nextsevendays')
                        {
                            var nextsevendays = new Date();
                            nextsevendays.setDate(nextsevendays.getDate() + 2);
                            duedate = nextsevendays.getTime() / 1000;
                        }
                        else if(sectionId == 'future')
                        {
                            var future = new Date();
                            future.setDate(future.getDate() + 8);
                            duedate = future.getTime() / 1000;
                        }
                        else
                        {
                            duedate = 0;
                        }
                    
/*                     } */
                    
                    
                    updateTaskDueDate(taskId, duedate, 0, true);/*

                    console.log('sectionId: ' +sectionId);
                    console.log('updated : ' + document.getElementById('task_name_' +taskId).innerHTML + ' to ' + displayHumanReadableDate(duedate));
*/
                    
                    break;
                }
                case 1:
                {
                    var priority = 0;
                    if(targetTask != null)
                    {
                        priority = targetTask.getAttribute('priority');
                    }
                    else
                    {
                        if(sectionId == 'high')
                        {
                            priority = 1;
                        }
                        else if(sectionId == 'medium')
                        {
                            priority = 5;
                        }
                        else if(sectionId == 'low')
                        {
                            priority = 9;
                        }
                        else
                        {
                            priority = 0;
                        }
                    }
                    updatePriorityForTask(priority, taskId, true);
                    
                    break;
                }
                default:
                    break;
            }
        }
    updateTaskSortOrder(taskId);
};
// ! Tasks

var draggedTaskId = null;
var newlyCreatedTaskId = null;

function taskDragStart(event, taskId)
{
	//event.preventDefault();
	
	//set the type of operation that is allowed: none, copy, copyLink, copyMove, link, linkMove, move, all and uninitialized
	//event.dataTransfer.effectAllowed = 'none';

	draggedTaskId = taskId;
	//specify the data that will be used in the drag event
	event.dataTransfer.setData("Text", event.target.parentNode.getAttribute(('id')));

	//configure drag image
	var dragImgEl = document.getElementById('task_drag_image');
	
	if (selectedTasks.indexOf(taskId) > -1 && selectedTasks.length > 1)
		dragImgEl.setAttribute('class', 'task_drag_image multi');
	else
		dragImgEl.setAttribute('class', 'task_drag_image single');	
	
	//event.dataTransfer.setDragImage(event.target.parentNode, event.layerX, event.layerY);

	if (event.dataTransfer.setDragImage)
		event.dataTransfer.setDragImage(dragImgEl, dragImgEl.offsetWidth / 2, dragImgEl.offsetHeight / 2);
		
	return true;
};

function insideTaskDragEnter(event)
{
	var targetTaskId = event.target.getAttribute('id').replace('insert_inside_target_', '');
	var targetEl = event.target;

	if (canTaskBeDroppedInsideTarget(draggedTaskId, targetTaskId))	
		targetEl.setAttribute('class', 'drop_target inside active');

	return false;
};

function insideTaskDragLeave(event)
{
	event.preventDefault();
	
	var draggedTaskId = event.dataTransfer.getData("Text");
	var targetTaskId = event.target.getAttribute('id').replace('insert_inside_target_', '');
	var targetEl = event.target;
			
	targetEl.setAttribute('class', 'drop_target inside');

	//console.log('leaving inside task');
};

function catchTaskDropInside(event)
{
	event.preventDefault();
	
	//check for multiple task selection
	var taskIds = selectedTasks;
	var targetTaskId = null;
	
	if(taskIds.length > 0)
	{
		for (var i = 0; i < taskIds.length; i++)
		{
			targetTaskId = event.target.getAttribute('id').replace('insert_inside_target_', '');
	
			if(canTaskBeDroppedInsideTarget(taskIds[i], targetTaskId))
			{
				moveTaskToParent(taskIds[i], targetTaskId, targetTaskId);	//&&&	
			}
		}
	}	
	else
	{
		targetTaskId = event.target.getAttribute('id').replace('insert_inside_target_', '');
	
		if(canTaskBeDroppedInsideTarget(draggedTaskId, targetTaskId))
		{
			moveTaskToParent(draggedTaskId, targetTaskId, targetTaskId);		
		}
	}

	insideTaskDragLeave(event);	
};

function canTaskBeDroppedInsideTarget(draggedTaskId, targetTaskId)
{
	if (draggedTaskId == null || (curSearchString != null && curSearchString.length > 0))
		return false;
		
	var answer = false;
	var doc = document;
	
	var draggedTask = doc.getElementById(draggedTaskId);
	var draggedTaskType = parseInt(draggedTask.getAttribute('tasktype'), 10);
	var targetTask = doc.getElementById(targetTaskId);
	var targetTaskType = parseInt(targetTask.getAttribute('tasktype'), 10);
	
	if (targetTaskType == 1 || targetTaskType == 7)	 //any task over a project or a checklist
		answer = true;
		
	if (draggedTaskId == targetTaskId) //task dragged over itself
		answer = false;
	
	if (targetTaskType == 7 && draggedTaskType == 7) //checklist over checklist
		answer = false;	
		
	if (targetTaskType == 1 && draggedTaskType == 1) //project over project
		answer = false;
			
	if (targetTaskType == 7 && draggedTaskType == 1) //project over checklist
		answer = false;
						
	return answer;	
};

function belowTaskDragEnter(event)
{
	var doc = document;
	var targetTaskId = event.target.getAttribute('id').replace('insert_below_target_', '');
	var targetEl = event.target;
	
	if (canTaskBeDroppedBelowTarget(draggedTaskId, targetTaskId))	
		targetEl.setAttribute('class', 'drop_target below active');

	return false;
};
function aboveTaskDragEnter(event)
{
    var doc = document;
    var targetTaskId = event.target.getAttribute('id').replace('insert_above_target_', '');
    var targetEl = event.target;

    if (canTaskBeDroppedAboveTarget(draggedTaskId, targetTaskId))
        targetEl.setAttribute('class', 'drop_target above active');

    return false;
};

function belowTaskDragLeave(event)
{
	//var draggedTaskId = event.dataTransfer.getData("Text");
	var targetTaskId = event.target.getAttribute('id').replace('insert_below_target_', '');
	var targetEl = event.target;
	
	if (draggedTaskId == targetTaskId)
		return false;
		
	targetEl.setAttribute('class', 'drop_target below');

	return false;
};
function aboveTaskDragLeave(event)
{
    var targetTaskId = event.target.getAttribute('id').replace('insert_above_target_', '');
    var targetEl = event.target;

    if (draggedTaskId == targetTaskId)
        return false;

    targetEl.setAttribute('class', 'drop_target above');

    return false;
};


function catchTaskDropBelow(event)
{
	event.preventDefault();

	var targetTaskId = event.target.getAttribute('id').replace('insert_below_target_', '');
	updateDraggedTaskWithTaskBelow(targetTaskId);
	
	belowTaskDragLeave(event);	
};
function catchTaskDropAbove(event)
{
    event.preventDefault();

    var targetTaskId = event.target.getAttribute('id').replace('insert_above_target_', '');
    updateDraggedTaskWithTaskAbove(targetTaskId);

    aboveTaskDragLeave(event);
};

function updateDraggedTaskWithTaskBelow(targetTaskId)
{
    var tasks_to_move = [];
    if (selectedTasks.indexOf(draggedTaskId) > -1 && selectedTasks.length > 1) {
        jQuery('#task_sections_wrapper .task_wrap').each(function () {
            var current_id = jQuery(this).attr('id');
            if (jQuery.inArray(current_id, selectedTasks) >= 0) {
                tasks_to_move.push(current_id);
            }
        });
    } else {
        tasks_to_move.push(draggedTaskId);
    }
    window.dragged_tasks_count = tasks_to_move.length;
    for (var i = tasks_to_move.length - 1 ; i >= 0; i--) {
        var current_id = tasks_to_move[i];
	if(canTaskBeDroppedBelowTarget(current_id , targetTaskId))
	{
		var doc = document;
		var draggedTask = doc.getElementById(current_id);
		var draggedParentId = draggedTask.getAttribute('parentid');
		
		if (draggedParentId)
			var draggedParentType = parseInt(doc.getElementById(draggedParentId).getAttribute('tasktype') , 10);
		
		if (draggedParentType != 7) //checklist
			var draggedTaskSubtasks = doc.getElementById('subtasks_wrapper_' + current_id );
		
		var targetTask = doc.getElementById(targetTaskId);
		var targetParentId = targetTask.getAttribute('parentid');
		
		if (targetParentId)
		{
			var targetParentTask = doc.getElementById(targetParentId);
			var targetParentType = parseInt(targetParentTask.getAttribute('tasktype'), 10);
		}
		if (targetParentType != 7) //checklist
				var targetTaskSubtasks = doc.getElementById('subtasks_wrapper_'+ targetTaskId);
				
				
				
		if(targetParentId == draggedParentId && (draggedParentType == 7 || draggedParentType == 1)) //moving taskito inside its own parent checklist or project
		{
			moveTaskito(targetTaskId);
		}
		else
		{		
			// move task to project or checklist (if needed)
			if ((targetParentId && draggedParentId) && targetParentId !== draggedParentId) //move tasks between projects or checklists
			{
				moveTaskToParent(current_id , targetParentId, targetTaskId);//this call moves the task out of the original parent into the target parent
			}
			else if (draggedParentId) //move task out of project or checklist
			{
				moveTaskOutOfParent(current_id , draggedParentId, targetTaskId);//this call moves the task outside the task and below the target task
			}
			else if (targetParentId)
			{
				
				moveTaskToParent(current_id , targetParentId, targetTaskId)
			}
			else //move task below another task, project or checklist that does not have a parent
			{
				//move dragged task UI below target task
				draggedTask.parentNode.removeChild(draggedTaskSubtasks);
				draggedTask.parentNode.removeChild(draggedTask);
		
				targetTaskSubtasks.parentNode.insertBefore(draggedTaskSubtasks, targetTaskSubtasks.nextSibling);
				targetTaskSubtasks.parentNode.insertBefore(draggedTask, targetTaskSubtasks.nextSibling);
			}

			//update values based on current task sorting
			if (draggedParentType == 7)
					draggedTaskId = newlyCreatedTaskId;
				
            if(targetTask.getAttribute('iscompleted') == 'true')
            {
                if(draggedTask.getAttribute('iscompleted') != 'true')
                {
                    var completionTimestamp = targetTask.getAttribute('completiondate');
                    completeTask(null, current_id , completionTimestamp);
                }
            }
            else
            {
                if(draggedTask.getAttribute('iscompleted') == 'true')
                {
                    uncompleteTask(null, current_id );
                }
            }
        }
        if (draggedParentType !== 7) {
            var elements_after = jQuery('#' + current_id ).nextAll('.task_wrap');
            var next_element = elements_after[0];
            var next_task_priority = undefined;
            if (typeof next_element != 'undefined') {
                next_task_priority = parseInt(jQuery(next_element).attr('priority'));
            }
            var elements_before = jQuery('#' + current_id ).prevAll('.task_wrap');
            var prev_element = elements_before[0];
            var prev_task_priority = undefined;
            if (typeof prev_element != 'undefined') {
                prev_task_priority = parseInt(jQuery(prev_element).attr('priority'));
            }
            switch (pageSortOrder)
            {
                case 0: //due date sorting
                    updatePriorityForTask(newTaskPriority(prev_task_priority, next_task_priority), current_id , true,
                        function (current_id) {
                            updateTaskDueDate(current_id , targetTask.getAttribute('duedate'), targetTask.getAttribute('hasduetime'), true);
                        }
                    );
                    break;
                case 1: //priority sorting
                    updatePriorityForTask(newTaskPriority(prev_task_priority, next_task_priority), current_id , true);
                    break;
                case 2: //alphabetical sorting
                default:
                    break;
            }
        }
        updateTaskSortOrder(targetTaskId);
	}    
    }
}

function updateDraggedTaskWithTaskAbove(targetTaskId)
{
    var tasks_to_move = [];
    if (selectedTasks.indexOf(draggedTaskId) > -1 && selectedTasks.length > 1) {
        jQuery('#task_sections_wrapper .task_wrap').each(function () {
            var current_id = jQuery(this).attr('id');
            if (jQuery.inArray(current_id, selectedTasks) >= 0) {
                tasks_to_move.push(current_id);
            }
        });
    } else {
        tasks_to_move.push(draggedTaskId);
    }
    window.dragged_tasks_count = tasks_to_move.length;

    for (var i = 0; i < tasks_to_move.length; i++) {
        var current_id = tasks_to_move[i];
        if (canTaskBeDroppedAboveTarget(current_id, targetTaskId)) {
            var doc = document;
            var draggedTask = doc.getElementById(current_id);
            var draggedParentId = draggedTask.getAttribute('parentid');

            if (draggedParentId)
                var draggedParentType = parseInt(doc.getElementById(draggedParentId).getAttribute('tasktype'), 10);

            if (draggedParentType != 7) //checklist
                var draggedTaskSubtasks = doc.getElementById('subtasks_wrapper_' + current_id);

            var targetTask = doc.getElementById(targetTaskId);
            var targetParentId = targetTask.getAttribute('parentid');

            if (targetParentId) {
                var targetParentTask = doc.getElementById(targetParentId);
                var targetParentType = parseInt(targetParentTask.getAttribute('tasktype'), 10);
            }
            if (targetParentType != 7) //checklist
                var targetTaskSubtasks = doc.getElementById('subtasks_wrapper_' + targetTaskId);


            if (targetParentId == draggedParentId && (draggedParentType == 7 || draggedParentType == 1)) //moving taskito inside its own parent checklist or project
            {
                moveTaskito(targetTaskId, true);
            }
            else {
                // move task to project or checklist (if needed)
                if ((targetParentId && draggedParentId) && targetParentId !== draggedParentId) //move tasks between projects or checklists
                {
                    moveTaskToParent(current_id, targetParentId, targetTaskId);//this call moves the task out of the original parent into the target parent
                }
                else if (draggedParentId) //move task out of project or checklist
                {
                    moveTaskOutOfParent(current_id, draggedParentId, targetTaskId);//this call moves the task outside the task and above the target task
                }
                else if (targetParentId) {

                    moveTaskToParent(current_id, targetParentId, targetTaskId)
                }
                else //move task above another task, project or checklist that does not have a parent
                {
                    //move dragged task UI above target task
                    draggedTask.parentNode.removeChild(draggedTaskSubtasks);
                    draggedTask.parentNode.removeChild(draggedTask);

                    targetTask.parentNode.insertBefore(draggedTaskSubtasks, targetTask);
                    targetTask.parentNode.insertBefore(draggedTask, targetTask);
                }

                //update values based on current task sorting
                if (draggedParentType == 7)
                    current_id = newlyCreatedTaskId;

                if (targetTask.getAttribute('iscompleted') == 'true') {
                    if (draggedTask.getAttribute('iscompleted') != 'true') {
                        var completionTimestamp = targetTask.getAttribute('completiondate');
                        completeTask(null, current_id, completionTimestamp);
                    }
                }
                else {
                    if (draggedTask.getAttribute('iscompleted') == 'true') {
                        uncompleteTask(null, current_id);
                    }
                }
            }
            if (draggedParentType !== 7) {
                var elements_after = jQuery('#' + current_id).nextAll('.task_wrap');
                var next_element = elements_after[0];
                var next_task_priority = undefined;
                if (typeof next_element != 'undefined') {
                    next_task_priority = parseInt(jQuery(next_element).attr('priority'));
                }
                var elements_before = jQuery('#' + current_id).prevAll('.task_wrap');
                var prev_element = elements_before[0];
                var prev_task_priority = undefined;
                if (typeof prev_element != 'undefined') {
                    prev_task_priority = parseInt(jQuery(prev_element).attr('priority'));
                }
                switch (pageSortOrder) {
                    case 0: //due date sorting
                        updatePriorityForTask(newTaskPriority(prev_task_priority, next_task_priority), current_id, true,
                            function (current_id) {
                                updateTaskDueDate(current_id, targetTask.getAttribute('duedate'), targetTask.getAttribute('hasduetime'), true);
                            }
                        );
                        break;
                    case 1: //priority sorting
                        updatePriorityForTask(newTaskPriority(prev_task_priority, next_task_priority), current_id, true);
                        break;
                    case 2: //alphabetical sorting
                    default:
                        break;
                }
            }
            updateTaskSortOrder(targetTaskId);
        }
    }
}

function moveTaskito(targetId, before)
{
	var doc = document;
	var draggedTaskito = doc.getElementById(draggedTaskId);
	var targetTaskito = doc.getElementById(targetId);
	var draggedTaskParentChecklist = doc.getElementById(draggedTaskito.getAttribute('parentid'));
	var draggedTaskParentSubtasksEl = doc.getElementById('subtasks_' + draggedTaskito.getAttribute('parentid'));
	var targetTaskParentSubtasksEl = doc.getElementById('subtasks_' + targetTaskito.getAttribute('parentid'));

    if (typeof before !== 'undefined' && before == true) {
        targetTaskParentSubtasksEl.insertBefore(draggedTaskito, targetTaskito);
        jQuery('#'+draggedTaskId).after(jQuery('#subtasks_wrapper_' + draggedTaskId));
    } else {
        targetTaskParentSubtasksEl.insertBefore(draggedTaskito, targetTaskito.nextSibling);
        jQuery('#'+draggedTaskId).after(jQuery('#subtasks_wrapper_' + draggedTaskId));
    }
	//draggedTaskParentSubtasksEl.removeChild(draggedTaskito);
	
	
	
};

function canTaskBeDroppedBelowTarget(draggedTaskId, targetTaskId)
{
	if (draggedTaskId == null || pageSortOrder == 2 || (curSearchString != null && curSearchString.length > 0))
		return;
		
	var answer = true;	
	var doc = document;
	
	///var draggedTaskId = event.dataTransfer.getData("Text");
	
	var draggedTask = doc.getElementById(draggedTaskId);
	var draggedTaskType = parseInt(draggedTask.getAttribute('tasktype'), 10);
	var targetTask = doc.getElementById(targetTaskId);
	var targetTaskType = parseInt(targetTask.getAttribute('tasktype'), 10);
	var targetParentId = targetTask.getAttribute('parentid');
	
	
	if (targetParentId)
	{
		var targetParentEl = doc.getElementById(targetParentId);
		var targetParentTaskType = parseInt(targetParentEl.getAttribute('tasktype'), 10);
	
		if (targetParentTaskType == 1) //project root 
		{
			if (draggedTaskType == 1) //project below any task
				answer = false;		
		}	
		
		if (targetParentTaskType == 7) //checklist root
		{
			if (draggedTaskType == 7 || draggedTaskType == 1) //checklist or project below root checklist child
				answer = false;
		}
	}

	if (draggedTaskId == targetTaskId) //task below itself
		answer = false;
		
	if (targetTaskType == 1  &&  targetTask.getAttribute('hassubtasksopen') == 'true')	// any task type dragged below an opened project or checklist
		answer =  false;	
		
	return answer;
};
function canTaskBeDroppedAboveTarget(draggedTaskId, targetTaskId)
{
    if (draggedTaskId == null || pageSortOrder == 2 || (curSearchString != null && curSearchString.length > 0))
        return;

    var answer = true;
    var doc = document;

    ///var draggedTaskId = event.dataTransfer.getData("Text");

    var draggedTask = doc.getElementById(draggedTaskId);
    var draggedTaskType = parseInt(draggedTask.getAttribute('tasktype'), 10);
    var targetTask = doc.getElementById(targetTaskId);
    var targetTaskType = parseInt(targetTask.getAttribute('tasktype'), 10);
    var targetParentId = targetTask.getAttribute('parentid');


    if (targetParentId)
    {
        var targetParentEl = doc.getElementById(targetParentId);
        var targetParentTaskType = parseInt(targetParentEl.getAttribute('tasktype'), 10);

        if (targetParentTaskType == 1) //project root
        {
            if (draggedTaskType == 1) //project above any task
                answer = false;
        }

        if (targetParentTaskType == 7) //checklist root
        {
            if (draggedTaskType == 7 || draggedTaskType == 1) //checklist or project above root checklist child
                answer = false;
        }
    }

    if (draggedTaskId == targetTaskId) //task above itself
        answer = false;

    if (targetTaskType == 1  &&  targetTask.getAttribute('hassubtasksopen') == 'true')	// any task type dragged above an opened project or checklist
        answer =  false;

    return answer;
};


// ! Other

function moveTaskToParent(taskId, parentId, targetTaskId)
{
	var doc = document;
	var draggedTask = doc.getElementById(taskId);
	var draggedTaskParentId = draggedTask.getAttribute('parentid');
	
	if (draggedTaskParentId)
	{
		var draggedTaskParent = doc.getElementById(draggedTask.getAttribute('parentid'));
		var draggedParentType = parseInt(draggedTaskParent.getAttribute('tasktype'), 10);
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

                if(response.success)
                {
                	
    	           	//move dragged task into parent task    	           	
    	           	var draggedTaskSubtasks = doc.getElementById('subtasks_wrapper_' + taskId);
    	           	
    	           	var parentTask = doc.getElementById(parentId);
    	           	var parentType = parseInt(parentTask.getAttribute('tasktype'), 10);
    	           	
    	           	if (parentType != 7)
    	           		var targetTaskSubtasks = doc.getElementById('subtasks_wrapper_' + targetTaskId);
    	           		
    	           	var subtasksOpened = parentTask.getAttribute('hassubtasksopen');
    	           	
    	           	if(draggedTaskSubtasks)
    	           		draggedTask.parentNode.removeChild(draggedTaskSubtasks);
    	           	
    	           	draggedTask.parentNode.removeChild(draggedTask);
			
    	           	if (draggedParentType == 7 && parentType != 7)
    	           	{
	    	           	//insert new task in UI
	    	           	var newTask = response.task;
	    	           	var newTaskHtml = getTaskHTML(newTask);
	    	           	var newTaskEl = createFragment(newTaskHtml);
	    	           	
	    	           	if (parentType != 7)
	    	           		targetTaskSubtasks.parentNode.insertBefore(newTaskEl, targetTaskSubtasks.nextSibling);
	    	           	
	    	           	newlyCreatedTaskId = newTask.taskid;
    	           	}
    	           	else if (subtasksOpened == 'true')
    	           		loadSubtasksForTaskId(parentId);
    	           	   	 
    	           	// update parent badge count for target parent
    	           	var parentEl = doc.getElementById(parentId);
                	var currentActiveSubtasks = parseInt(parentEl.getAttribute('activesubtasks'), 10);
                	var newCount = currentActiveSubtasks + 1;
                	
                	
                	parentEl.setAttribute('activesubtasks', newCount);
                	doc.getElementById('active_badge_count_' + parentId).innerHTML = newCount;
               		doc.getElementById('active_badge_count_' + parentId).style.display = 'inline-block';
               		
               		
               		// update parent badge count for dragged task's parent
               		if (draggedTaskParentId)
               		{
	               		
	               		var currentActiveSubtasks = parseInt(draggedTaskParent.getAttribute('activesubtasks'), 10);
	               		var newCount = currentActiveSubtasks - 1;
	               		draggedTaskParent.setAttribute('activesubtasks', newCount);
	               		doc.getElementById('active_badge_count_' + draggedTaskParentId).innerHTML = newCount;
	               		
	               		if (newCount == 0)
	               			doc.getElementById('active_badge_count_' + draggedTaskParentId).setAttribute('style', '');
               		}

					updateTaskSortOrder(taskId);

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
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                    
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_response_in_movetasktoparent + ': ' + e);
            }
            
		}
	}

	var params = '';
	
	if (draggedParentType == 7)
		params += 'method=moveTaskitoToParent&taskitoId=' + taskId + '&parentId=' + parentId;
	else
		params += 'method=moveTaskToParent&taskId=' + taskId + '&parentId=' + parentId;
    
	ajaxRequest.open("POST", ".", false);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function moveTaskOutOfParent(draggedTaskId, draggedParentId, targetTaskId)
{
	var doc = document;
	var draggedParent = doc.getElementById(draggedParentId );
    var draggedParentType = parseInt(draggedParent.getAttribute('tasktype'), 10);
	
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
            	var doc = document;
                var response = JSON.parse(ajaxRequest.responseText);

                if(response.success)
                {
	                //move dragged task out of parent task and place it below target task
    	           	var draggedTask = doc.getElementById(draggedTaskId);
    	           
    	           	if (draggedParentType != 7) //checklist
    	           		var draggedTaskSubtasks = doc.getElementById('subtasks_wrapper_' + draggedTaskId);
    	           
    	           	var targetTask = doc.getElementById(targetTaskId);
    	           	var targetParentId = targetTask.getAttribute('parentid');
    	           	
    	           	if (targetParentId)
    	           	{
    	           		var targetParent = doc.getElementById(targetParentId);
    	           		var targetParentType = targetParent.getAttribute('tasktype');
    	           	}
    	           		
    	           	if (targetParentType != 7) //checklist
    	           		var targetTaskSubtasks = doc.getElementById('subtasks_wrapper_' + targetTaskId);
    	           	
    	           	
    	           	var subtasksOpened = draggedParent.getAttribute('hassubtasksopen');
    	           	
    	           	if (draggedParentType != 7) //checklist
    	           		draggedTask.parentNode.removeChild(draggedTaskSubtasks);
    	           	
    	           	draggedTask.parentNode.removeChild(draggedTask);
    	           	
    	           	if (draggedParentType != 7) //checklist		
    	           	{	
    	           		targetTaskSubtasks.parentNode.insertBefore(draggedTaskSubtasks, targetTaskSubtasks.nextSibling);
    	           		targetTaskSubtasks.parentNode.insertBefore(draggedTask, targetTaskSubtasks.nextSibling);
                        
                        draggedTask.setAttribute('isSubtask', 'false');
                        draggedTask.removeAttribute('parentid');
                        var repeatVal = parseInt(draggedTask.getAttribute('repeat'), 10);
                        if(repeatVal == 9 || repeatVal == 109)
                        {
                            //if the value was repeat with parent, remove it
                            draggedTask.setAttribute('repeat', '0');
                        }
                        
                        var subtaskInput = document.getElementById('project_subtask_' + draggedTaskId);
                        if(subtaskInput)
                        {
                            subtaskInput.parentNode.removeChild(subtaskInput);
                        }
    	           	}
    	           	else
    	           	{
    	           		//insert new task in UI
	    	           	var newTask = response.task;
	    	           	var newTaskHtml = getTaskHTML(newTask);
	    	           	var newTaskEl = createFragment(newTaskHtml);
	    	           	
	    	           	targetTaskSubtasks.parentNode.insertBefore(newTaskEl, targetTaskSubtasks.nextSibling);
	    	           	newlyCreatedTaskId = newTask.taskid;
    	           	}
    	           	
    	           	if (subtasksOpened == 'true')
    	           		loadSubtasksForTaskId(draggedParentId);
    	           	   	 
    	           	// update parent badge count for target parent
                	var currentActiveSubtasks = parseInt(draggedParent.getAttribute('activesubtasks'), 10);
                	var newCount = currentActiveSubtasks + 1;
                	
                	if (targetParentId)
                	{
	                	targetParent.setAttribute('activesubtasks', newCount);
	                	doc.getElementById('active_badge_count_' + targetParentId).innerHTML = newCount;
	               		doc.getElementById('active_badge_count_' + targetParentId).style.display = 'inline-block';
	               	}	
	               		
               		// update parent badge count for dragged task's parent
               		if (draggedParentId)
               		{
	               		var currentActiveSubtasks = parseInt(draggedParent.getAttribute('activesubtasks'), 10);
	               		var newCount = currentActiveSubtasks - 1;
	               		draggedParent.setAttribute('activesubtasks', newCount);
	               		doc.getElementById('active_badge_count_' + draggedParentId).innerHTML = newCount;
	               		
	               		if (newCount == 0)
	               			doc.getElementById('active_badge_count_' + draggedParentId).setAttribute('style', '');
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
                            displayGlobalErrorMessage(labels.error_from_server + ':' + response.error);
                    }
                    
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
            
		}
	}

	var params = '';
	
	if (draggedParentType == 1)
		params = 'method=updateTask&taskId=' + draggedTaskId + '&moveFromProject=true';
	else if (draggedParentType == 7)
		params = 'method=moveTaskitoFromParent&taskitoId=' + draggedTaskId;
		    
	ajaxRequest.open("POST", ".", false);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
	//method=updateTask &moveFromProject=true
};

function moveTaskOutOfParentToSection(draggedTaskId, draggedParentId, sectionEl)
{
    var draggedParent = document.getElementById(draggedParentId );
    var draggedParentType = parseInt(draggedParent.getAttribute('tasktype'), 10);

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
	                //move dragged task out of parent task
    	           	var draggedTask = document.getElementById(draggedTaskId);
    	           
                   var subtasksOpened = draggedParent.getAttribute('hassubtasksopen');
                   
                    var draggedTaskSubtasks = null;
    	           	if (draggedParentType != 7) //checklist
                    {
                        draggedTaskSubtasks = document.getElementById('subtasks_wrapper_' + draggedTaskId);
                        draggedTask.parentNode.removeChild(draggedTaskSubtasks);
                    }

    	           	draggedTask.parentNode.removeChild(draggedTask);
    	           	
    	           	if (draggedParentType != 7) //checklist		
    	           	{	
    	           		sectionEl.insertBefore(draggedTaskSubtasks, sectionEl.firstChild);
    	           		sectionEl.insertBefore(draggedTask, sectionEl.firstChild);
                        
                        draggedTask.setAttribute('isSubtask', 'false');
                        draggedTask.removeAttribute('parentid');
                        var repeatVal = parseInt(draggedTask.getAttribute('repeat'), 10);
                        if(repeatVal == 9 || repeatVal == 109)
                        {
                            //if the value was repeat with parent, remove it
                            draggedTask.setAttribute('repeat', '0');
                        }
                        
                        var subtaskInput = document.getElementById('project_subtask_' + draggedTaskId);
                        if(subtaskInput)
                        {
                            subtaskInput.parentNode.removeChild(subtaskInput);
                        }
    	           	}
    	           	else
    	           	{
    	           		//insert new task in UI
	    	           	var newTask = response.task;
	    	           	var newTaskHtml = getTaskHTML(newTask);
	    	           	var newTaskEl = createFragment(newTaskHtml);
	    	           	
	    	           	sectionEl.insertBefore(newTaskEl, sectionEl.firstChild);
	    	           	newlyCreatedTaskId = newTask.taskid;
    	           	}
    	           	
    	           	if (subtasksOpened == 'true')
    	           		loadSubtasksForTaskId(draggedParentId);
	               		
               		// update parent badge count for dragged task's parent
               		if (draggedParentId)
               		{
	               		var currentActiveSubtasks = parseInt(draggedParent.getAttribute('activesubtasks'), 10);
	               		var newCount = currentActiveSubtasks - 1;
	               		draggedParent.setAttribute('activesubtasks', newCount);
	               		document.getElementById('active_badge_count_' + draggedParentId).innerHTML = newCount;
	               		
	               		if (newCount == 0)
	               			document.getElementById('active_badge_count_' + draggedParentId).setAttribute('style', '');
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
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                        else
                            displayGlobalErrorMessage(labels.unknown_error_moving_task_from_parent);
                    }
                    
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.error_moving_task_from_parent + ': ' + e);
            }
            
		}
	}

	var params = '';
	
	if (draggedParentType == 1)
		params = 'method=updateTask&taskId=' + draggedTaskId + '&moveFromProject=true';
	else if (draggedParentType == 7)
		params = 'method=moveTaskitoFromParent&taskitoId=' + draggedTaskId;
		    
	ajaxRequest.open("POST", ".", false);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
	//method=updateTask &moveFromProject=true
};

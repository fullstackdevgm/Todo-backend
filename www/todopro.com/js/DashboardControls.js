//console.log('Loading DashboardControls.js');
// ! Onload

window.globals = window.globals || {};
window.globals.context = window.globals.context || {};

loadListsControl();
loadContextsControl();
loadTagsControl();

// ! Tags
//loadTagsControl function
//purpose: loads all the tags available to the user in the dashboard_tags_control div
function loadTagsControl()
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
            catch(e){}

            if(ajaxRequest.responseText != "")
            {

            	var responseJSON = JSON.parse(ajaxRequest.responseText);

            	if(responseJSON.success == true)
            	{
            		var pageContextId = document.getElementById('currentContextId').value;
                    if(typeof(pageContextId) == 'undefined')
                        pageContextId = 'all';
            		var pageListId = document.getElementById('currentListId').value;
                    if(typeof(pageListId) == 'undefined')
                        pageListId = 'all';
                    var pageTagIdString = document.getElementById('currentTagIds').value;
                    if(typeof(pageTagIdString) == 'undefined')
                        pageTagIdString = 'all';

            		var tagsControlEl = document.getElementById('dashboard_tags_control');
					var tagsControlHTML = '';

          var tagsJSON = responseJSON.tags;

          var filterByAnd = false;
          if(response.filterByAnd == true)
              filterByAnd = true;

       	 //set up control header
       	 tagsControlHTML += '	<li><div class="group_title">' + taskStrings.tags + '</div></li>';

          var selectedTagArray = pageTagIdString.split(',');

					//all tags
       	 //var allHref = getHrefLinkForTag('all', pageTagIdString, pageListId, pageContextId, filterByAnd);

          var tagFilter = buildTagFilter('all', pageTagIdString, filterByAnd);
       	 var allHref = 'javascript:void(0)" onclick="SetCookieAndLoad( \'TodoOnlineTagId\', \'' + tagFilter + '\')';

          var allSelectedClass = '';
          if(pageTagIdString == 'all') allSelectedClass = ' selected_option';
          tagsControlHTML += '<li  class="group_option ' + allSelectedClass + '">';
          tagsControlHTML += '	<span class="option_left_icon ' + allSelectedClass + '">';
					tagsControlHTML += '	</span>';
					tagsControlHTML += '	<a class="" href="'+ allHref +'">';
                    tagsControlHTML += '		<div class="option_name">' + labels.all_tags;
					tagsControlHTML += '		</div>';
					tagsControlHTML += '	</a>';
					tagsControlHTML += '</li>';

					//no tags
					// var noTagHref = getHrefLinkForTag('notag', pageTagIdString, pageListId, pageContextId, filterByAnd);
          tagFilter = buildTagFilter('notag', pageTagIdString, filterByAnd);
       	 var noTagOnclick = 'SetCookieAndLoad( \'TodoOnlineTagId\', \'' + tagFilter + '\')';

          var notagSelectedClass = '';
          if(pageTagIdString.indexOf('notag') >= 0)
              notagSelectedClass = ' selected_option';

          tagsControlHTML += '<li  id="no_tags" class="group_option ' + notagSelectedClass + '" >';
          tagsControlHTML += '	<div style="background:rgba(255,255,255,0)"  class="drop_target" ondragenter="tagDragEnter(event, \'no_tags\')" ondragover="return false" ondragleave="tagDragLeave(event, \'no_tags\')" ondrop="tagCatchDrop(event, \'no_tags\', \'\')" onclick="'+ noTagOnclick + '"></div>';
          tagsControlHTML += '	<span class="option_left_icon ' + notagSelectedClass + '">';
					tagsControlHTML += '	</span>';
					tagsControlHTML += '	<a href="javascript:;" >';
					tagsControlHTML += '		<div class="option_name">' + controlStrings.noTags;
					tagsControlHTML += '		</div>';
					tagsControlHTML += '	</a>';
					tagsControlHTML += '</li>';

					//custom tags
					for (var i = 0; i < tagsJSON.length; i ++)
					{
                        var tagId = tagsJSON[i].tagid;
                        var tagName = tagsJSON[i].name;
                        var tagIndex = pageTagIdString.indexOf(tagId);

                        //var href = getHrefLinkForTag(tagId, pageTagIdString, pageListId, pageContextId, filterByAnd);
                        tagFilter = buildTagFilter(tagId, pageTagIdString, filterByAnd);
                        var onclick = 'onclick="SetCookieAndLoad( \'TodoOnlineTagId\', \'' + tagFilter + '\')"';

                        var selectClass = '';
                        if(pageTagIdString.indexOf(tagId) >= 0)
                            selectClass = ' selected_option';



                        tagsControlHTML += '<li id="' + tagId + '" class="group_option ' + selectClass + '" >';
						tagsControlHTML += '	<div style="background:rgba(255,255,255,0)"  class="drop_target" ondragenter="tagDragEnter(event, \'' + tagId + '\')" ondragover="return false" ondragleave="tagDragLeave(event, \'' + tagId + '\')" ondrop="tagCatchDrop(event, \'' + tagId + '\', \'' + tagName + '\')" ' + onclick + '></div>';
						tagsControlHTML += '	<span class="control_edit_icon" id="edit_tag_link_' + tagId + '">';
						tagsControlHTML += '		<i class="fa fa-cogs"></i>';
						tagsControlHTML += '	</span>';
						tagsControlHTML += '	<span class="option_left_icon tag_icon ' + selectClass + '" ></span>';
						tagsControlHTML += '	<span class="option_name" >' + tagName;
						tagsControlHTML += '	</span>';
						tagsControlHTML += '</li>';
						tagsControlHTML += '<div class="list_option_settings_menu_wrap">';
                        tagsControlHTML += '	<div id="tag_edit_flyout_' + tagId + '" class="property_flyout"></div>';
                        tagsControlHTML += '	<div id="tag_edit_background_' + tagId + '" class="task_repeat_background" onclick="hideTagEditFlyout(event,\'' + tagId + '\')"></div>';
                        tagsControlHTML += '</div>';
					}

					tagsControlEl.innerHTML = tagsControlHTML;
                    jQuery('.list_option_settings_menu_wrap').appendTo('#controls');

                    //Now bind the onclick actions using closures to avoid issues with special characters in the tag name
                    for(var i = 0; i < tagsJSON.length; i++)
                    {
                        var name = tagsJSON[i].name;
                        var tagId = tagsJSON[i].tagid;

                        var el = document.getElementById('edit_tag_link_' + tagId);
                        var event = (function(id,n){return function(event){displayEditTagFlyout(event,id,n);}}(tagId, name));
                        el.bindEvent('click', event, false);
                    }

            	}
            	else
            		displayGlobalErrorMessage(labels.failed_to_retrieve_tags + ': ' + ajaxRequest.responseText);

            }
        }
    }

    params = 'method=getControlContent&type=tag';

    ajaxRequest.open("POST", ".", false);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);
};

function buildTagFilter(tagId, currentTagString, tagFilterIsAnd)
{
    var tagFilter = '';
    //var href = '?list=' + pageListId + '&context=' + pageContextId + '&tag=';

    if(tagId == 'all')
    {
        tagFilter += 'all';
    }
    else
    {
        //If we're filtering by 'And', notag cannot be selected with other tags.
        //Otherwise, treat it like a normal tag (allow it to be joined with other tags)
        if(tagId == 'notag' && tagFilterIsAnd)
        {
            tagFilter += 'notag';
        }
        else
        {
            var tagArray = currentTagString.split(',');
            var tagIndex = tagArray.indexOf(tagId)
            if(tagIndex >= 0)
            {
                tagArray.splice(tagIndex, 1);
                var newString = tagArray.join(',');
                if(newString.length == 0)
                    newString = 'all';
                tagFilter += newString;
            }
            else
            {
                var allIndex = tagArray.indexOf('all');
                if(allIndex >= 0)
                    tagArray.splice(allIndex, 1);

                if(tagFilterIsAnd)
                {
                    var noTagIndex = tagArray.indexOf('notag');
                    if(noTagIndex >= 0)
                        tagArray.splice(noTagIndex, 1);
                }

                tagArray.push(tagId);

                var newString = tagArray.join(',');
                tagFilter += newString;
            }
        }
    }


    return tagFilter;
}

function displayCreateTagModal()
{
    var bodyHTML = '';
    var headerHTML = controlStrings.createTag;
    var footerHTML = '';

    bodyHTML += ' 	<div class="breath-4"></div>';
    bodyHTML += ' 	<div><input id="new_tag_name" type="text" class="centered_text_field" placeholder="' + labels.enter_a_tag_name + '" onkeyup="shouldEnableCreateTagOkButton(event, this)"/></div>';

    footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.cancel + '</div>';
    footerHTML += '<div id="create_context_ok_button" class="button disabled" >' + labels.ok + '</div>';

    displayModalContainer(bodyHTML, headerHTML, footerHTML);
    document.getElementById('new_tag_name').focus();
};

function createTag()
{
    var doc = document;
    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
    if(!ajaxRequest)
        return false;

    var tagName = doc.getElementById('new_tag_name').value;

    if(tagName.length == 0)
        return;

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
                    loadTagsControl();
                    cancelMultiEditListSelection();
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
                            displayGlobalErrorMessage(response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.exception_while_adding_tag + ': ' + e);
            }
        }
    }

    var params = "method=addTag&tagName=" + encodeURIComponent(tagName);
    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);
};

function shouldEnableCreateTagOkButton(event, inputEl)
{
    var enableButton = inputEl.value.length > 0 ? true : false;
    var button = document.getElementById('create_context_ok_button')

    if (enableButton)
    {
        button.setAttribute('class', 'button');
        button.onclick = function(){createTag();};
    }
    else
    {
        button.setAttribute('class', 'button disabled');
        button.onclick = null;
    }

    if (event.keyCode == 13 && enableButton)
        createTag();
};


// ! Contexts

function loadContextsControl()
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
            catch(e){}

            if(ajaxRequest.responseText != "")
            {
            	var responseJSON = JSON.parse(ajaxRequest.responseText);

            	if(responseJSON.success == true)
            	{
            		var pageContextId = document.getElementById('currentContextId').value;
                    var pageListId = document.getElementById('currentListId').value;
                    var pageTagIdString = document.getElementById('currentTagIds').value;

                    if(typeof(pageContextId) == 'undefined')
                        pageContextId = 'all';
                    if(typeof(pageListId) == 'undefined')
                        pageListId = 'all';
                    if(typeof(pageTagIdString) == 'undefined')
                        pageTagIdString = 'all';


            		var allSelectedClass = '';
            		var noContextSelectedClass = '';
            		var contextsControlEl = document.getElementById('dashboard_contexts_control');
					var contextsControlHTML = '';
	                var contextsJSON = responseJSON.contexts;

       	//set up control header
       	            contextsControlHTML += '<li><div class="group_title" draggable="true" >' + controlStrings.contexts + '</div></li>';

					//all contexts
                    if (pageContextId == 'all') {
                        allSelectedClass = ' selected_option';
                    }

					contextsControlHTML += '<li class="group_option ' + allSelectedClass + '" >';
					contextsControlHTML += '	<span class="new-sprite sprite-context-normal-wht ' + allSelectedClass + '" onclick="SetCookieAndLoad( \'TodoOnlineContextId\', \'all\')"></span>';
					contextsControlHTML += '	<span class="option_name" onclick="SetCookieAndLoad( \'TodoOnlineContextId\', \'all\')">' + controlStrings.all + '</span>';
					contextsControlHTML += '</li>';

					//no contexts
					if (pageContextId == 'nocontext')
					{
               			noContextSelectedClass = ' selected_option';
               		}

               		contextsControlHTML += '<li id="no_context" class="group_option ' + noContextSelectedClass + '" >';
					contextsControlHTML += '	<div style="background:rgba(255,255,255,0)"  class="drop_target" ondragenter="contextDragEnter(event, \'0\')" ondragover="return false" ondragleave="contextDragLeave(event, \'0\')" ondrop="contextCatchDrop(event, \'0\')" onclick="SetCookieAndLoad( \'TodoOnlineContextId\', \'nocontext\')"></div>';
					contextsControlHTML += '	<span class="new-sprite sprite-context-normal-wht ' + noContextSelectedClass + '">';
					contextsControlHTML += '	</span>';
					contextsControlHTML += '	<span class="option_name" >';
					contextsControlHTML += 			controlStrings.noContext;
					contextsControlHTML += '	</span>';
					contextsControlHTML += '</li>';

					//custom contexts
					for (var i = 0; i < contextsJSON.length; i ++)
					{
						var contextId = contextsJSON[i].id;
						var contextName = contextsJSON[i].name;
						var selectedContextClass= '';
						if (pageContextId == contextId)
						{
							selectedContextClass= ' selected_option';
						}

						contextsControlHTML += '<li id="' + contextId + '" class="group_option' + selectedContextClass + '" >';
						contextsControlHTML += '	<div style="background:rgba(255,255,255,0)"  class="drop_target" ondragenter="contextDragEnter(event, \'' + contextId + '\')" ondragover="return false" ondragleave="contextDragLeave(event, \'' + contextId + '\')" ondrop="contextCatchDrop(event, \'' + contextId + '\')" onclick="SetCookieAndLoad( \'TodoOnlineContextId\', \'' + contextId + '\')"></div>';
						contextsControlHTML += '	<span class="control_edit_icon" id="edit_context_link_' + contextId + '">';
						contextsControlHTML += '		<i class="fa fa-cogs"></i>';
						contextsControlHTML += '	</span>';
						contextsControlHTML += '	<span class="new-sprite sprite-context-normal-wht ' + selectedContextClass + '" ></span>';
						contextsControlHTML += '	<span class="option_name" >' + contextsJSON[i].name;
						contextsControlHTML += '	</span>';
						contextsControlHTML += '</li>';
						contextsControlHTML += '<div class="list_option_settings_menu_wrap">';
                        contextsControlHTML += '	<div id="context_edit_flyout_' + contextId + '" class="property_flyout"></div>';
                        contextsControlHTML += '	<div id="context_edit_background_' + contextId + '" class="task_repeat_background" onclick="hideContextEditFlyout(event,\'' + contextId + '\')"></div>';
                        contextsControlHTML += '</div>';
					}

					//create context
					contextsControlHTML += '<li class="group_option dashboard_create_list">';
					contextsControlHTML += '	<span class="option_left_icon">';
					contextsControlHTML += '	</span>';
					contextsControlHTML += '	<a class="" href="javascript:void(0)" onclick="displayCreateContextModal()" >'; //displayModalWindow(\'addContextModal\', setFocusOnContextName)">';
					contextsControlHTML += '		<span class="option_name" style="width: inherit">' + controlStrings.createContext + " +";
					contextsControlHTML += '		</span>';
					contextsControlHTML += '	</a>';
					contextsControlHTML += '</li>';

					contextsControlEl.innerHTML = contextsControlHTML;
                    jQuery('.list_option_settings_menu_wrap').appendTo('#controls');

                    //Now bind the onclick actions using closures to avoid issues with special characters in the context name
                    for(var i = 0; i < contextsJSON.length; i++)
                    {
                        var name = contextsJSON[i].name;
                        var id = contextsJSON[i].id;

                        var el = document.getElementById('edit_context_link_' + id);
                        var event = (function(id,n){return function(event){displayEditContextFlyout(event,id,n);}}(id, name));
                        el.bindEvent('click', event, false);
                    }

            	}
            	else
            		displayGlobalErrorMessage(labels.failed_to_retrieve_contexts_for_dashboard_control + ': ' + ajaxRequest.responseText);

            }
        }
    }

    params = 'method=getControlContent&type=context';

    ajaxRequest.open("POST", ".", false);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);
};

function displayCreateContextModal()
{
    window.context_creating = false;

	var bodyHTML = '';
	var headerHTML = controlStrings.createContext;
	var footerHTML = '';

	bodyHTML += ' 	<div class="breath-4"></div>';
	bodyHTML += ' 	<div><input id="new_context_name" type="text" class="centered_text_field" placeholder="'+labels.enter_a_context_name+'" onkeyup="shouldEnableCreateContextOkButton(event, this)" oninput="shouldEnableCreateContextOkButton(event, this)"/></div>';

	footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.cancel + '</div>';
	footerHTML += '<div id="create_context_ok_button" class="button disabled" >' + labels.ok + '</div>';

	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	document.getElementById('new_context_name').focus();
};

function createContext()
{
	var doc = document;
	var name = doc.getElementById('new_context_name').value.trim();
    window.context_creating = true;

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

                if(response.success)
                {
                	loadContextsControl();
                	cancelMultiEditListSelection();
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
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=addContext&contextName=" + encodeURIComponent(name);

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function shouldEnableCreateContextOkButton(event, inputEl)
{
    var context_name = inputEl.value.trim();
	var enableButton = context_name.length > 0 ? true : false;
	var button = document.getElementById('create_context_ok_button');

	if (enableButton && !window.context_creating)
	{
		button.setAttribute('class', 'button');
		button.onclick = function () {
            button.setAttribute('class', 'button disabled');
            button.onclick = null;
            createContext();
        };
	}
	else
	{
		button.setAttribute('class', 'button disabled');
		button.onclick = null;
	}

	if (event.keyCode == 13 && enableButton && !window.context_creating) {
        window.context_creating = true;
        button.setAttribute('class', 'button disabled');
        button.onclick = null;
        createContext();
    }
};

// ! Lists
function loadListsControl()
{
	//get lists from server
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

            	if(responseJSON.success == true)
            	{

                    // sort lists
                    responseJSON.lists.sort(function listSort(a,b){
                        var diff = a.sortOrder - b.sortOrder;
                        return diff != 0 ? diff : a.name.toLowerCase().charCodeAt(0) - b.name.toLowerCase().charCodeAt(0);
                    });
                    var zero_ordering = [];
                    var sorted_ordering = [];
                    responseJSON.lists.forEach(function (item, index, object){
                        if(parseInt(item.sortOrder) === 0 && item.name !== 'Inbox'){
                            zero_ordering.push(item);
                        }else{
                            sorted_ordering.push(item);
                        }
                    });
                    responseJSON.lists = sorted_ordering.concat(zero_ordering);

            		var pageContextId = document.getElementById('currentContextId').value;
            		var pageListId = document.getElementById('currentListId').value;
                    var pageTagIdString = document.getElementById('currentTagIds').value;

                    var pageListNameEl = document.getElementById('currentListName');
                    var controlItemIcon = '';


                    if(typeof(pageContextId) == 'undefined')
                        pageContextId = 'all';
                    if(typeof(pageListId) == 'undefined')
                        pageListId = 'all';
                    if(typeof(pageTagIdString) == 'undefined')
                        pageTagIdString = 'all';

            		var allSelectedClass = '';
            		var todaySelectedClass = '';
            		var focusSelectedClass = '';
            		var starredSelectedClass = '';
            		var inboxSelectedClass = '';

            		var listsControlEl = document.getElementById('dashboard_lists_control');
					var listsControlHTML = '';
	                var listsJSON = responseJSON.lists;

	               	//set up control header
									//        	listsControlHTML += '	<li><div class="group_title">' + controlStrings.lists + '</div></li>';

                //all list
					var allColors = responseJSON.default_lists_color.all;
                    var tasks_label = labels.tasks;
					var allStyle = '';
                    if (pageListId == 'all') {
                        allSelectedClass = ' selected_option';
                        allStyle = 'background: rgb(' + allColors + ')';
                        pageListNameEl.value = controlStrings.all;
                        window.globals.context.color = allColors;
                        window.globals.context.listName = controlStrings.all;
                        window.globals.context.listIconStyle = 'new-sprite';
                        if (isDark(window.globals.context.color)) {
                            allSelectedClass += ' bg-light';
                            window.globals.context.listIconStyle += ' sprite-list-all-blk';
                        } else {
                            allSelectedClass += ' bg-dark';
                            window.globals.context.listIconStyle += ' sprite-list-all-wht';
                        }
                        controlItemIcon = window.globals.context.listIconStyle;
                    } else {
                        controlItemIcon = 'new-sprite sprite-list-all-wht';
                    }

                    var taskCountDisplay = responseJSON.allcount > 0 ? 'display:inline-block' : '';
                    var taskCountClass = responseJSON.overdueallcount > 0 ? 'activesplit' :'active';
                    var overdueCountDisplay = responseJSON.overdueallcount > 0 ? 'display:inline-block' :'';

                    if (responseJSON.allcount == 1) {
                        tasks_label = labels.task;
                    } else {
                        tasks_label = labels.tasks;
                    }
                    listsControlHTML += '<li class="group_option all-tasks-list' + allSelectedClass + '" style="' + allStyle + '">';
                    listsControlHTML += '<div class="drop_target" onclick="SetCookieAndLoad( \'TodoOnlineListId\', \'all\')"></div>';
                    listsControlHTML += '<div class="group-color" style="background: rgb(' + allColors + ')"></div>';
                    listsControlHTML += '	<span class="control_edit_icon" id="control_edit_icon_all">';
                    listsControlHTML += '		<i class="fa fa-cogs"></i> <br/>';
                    listsControlHTML += '	</span>';
                    listsControlHTML += '	<span class="'+ controlItemIcon + ' ' + allSelectedClass + '">';
                    listsControlHTML += '	</span>';
                    listsControlHTML += '	<span class="option_name" >';
                    listsControlHTML += 		controlStrings.all;
                    listsControlHTML += '	</span>';
                    listsControlHTML += '	<span class="badge_count_wrap">';
                    listsControlHTML += '	    <span class="' + taskCountClass + '"  style="' + taskCountDisplay + '">'+ responseJSON.allcount + ' ' + tasks_label + '</span>';
                    if( responseJSON.overdueallcount > 0 ){
                        listsControlHTML += '	    <span class="overdue" style="' + overdueCountDisplay + '">'+ responseJSON.overdueallcount + ' ' + labels.overdue + '</span>';
                    }
                    listsControlHTML += '	</span>';
                    listsControlHTML += '</li>';
                    listsControlHTML += '<div class="list_option_settings_menu_wrap">';
                    listsControlHTML += '	<div id="list_edit_flyout_all" class="property_flyout"></div>';
                    listsControlHTML += '	<div id="list_edit_background_all" class="task_repeat_background" onclick="hideListEditFlyout(event,\'all\')"></div>';
                    listsControlHTML += '</div>';

                //focus list
                    var focusColors = responseJSON.default_lists_color.focus;
                    var focusSelectedStyle = '';
                    if (pageListId == 'focus') {
                        focusSelectedClass = ' selected_option';
                        focusSelectedStyle = 'background: rgb(' + focusColors + ');';
                        pageListNameEl.value = controlStrings.focus;
                        window.globals.context.color = focusColors;
                        window.globals.context.listName = controlStrings.focus;
                        window.globals.context.listIconStyle = 'new-sprite ';
                        if (isDark(window.globals.context.color)) {
                            focusSelectedClass += ' bg-light';
                            window.globals.context.listIconStyle += ' sprite-list-focus-blk';
                        } else {
                            focusSelectedClass += ' bg-dark';
                            window.globals.context.listIconStyle += ' sprite-list-focus-wht';
                        }
                        controlItemIcon = window.globals.context.listIconStyle;
                    } else {
                        controlItemIcon = 'new-sprite sprite-list-focus-wht';
                    }

                    taskCountDisplay = responseJSON.focuscount > 0 ? 'display:inline-block' : '';
                    taskCountClass = responseJSON.overduefocuscount > 0 ? 'activesplit' :'active';
                    overdueCountDisplay = responseJSON.overduefocuscount > 0 ? 'display:inline-block' :'';

                    if (responseJSON.focuscount == 1) {
                        tasks_label = labels.task;
                    } else {
                        tasks_label = labels.tasks;
                    }
					listsControlHTML += '<li class="group_option ' + focusSelectedClass + '" style="' + focusSelectedStyle + '">';
					listsControlHTML += '<div class="drop_target" onclick="SetCookieAndLoad( \'TodoOnlineListId\', \'focus\')"></div>';
					listsControlHTML += '<div class="group-color" style="background: rgb(' + focusColors + ')"></div>';
                    listsControlHTML += '	<span class="control_edit_icon" id="control_edit_icon_focus">';
                    listsControlHTML += '		<i class="fa fa-cogs"></i> <br/>';
                    listsControlHTML += '	</span>';
					listsControlHTML += '	<span class="'+ controlItemIcon + ' ' + focusSelectedClass + '">';
					listsControlHTML += '	</span>';
					listsControlHTML += '	<span class="option_name" >';
					listsControlHTML += 		controlStrings.focus;
					listsControlHTML += '	</span>';
                    listsControlHTML += '	<span class="badge_count_wrap">';
					listsControlHTML += '	    <span class="' + taskCountClass + '"  style="' + taskCountDisplay + '">'+ responseJSON.focuscount + ' ' + tasks_label + '</span>';
					if( responseJSON.overduefocuscount > 0) {
                        listsControlHTML += '	    <span class="overdue" style="' + overdueCountDisplay + '">' + responseJSON.overduefocuscount + ' ' + labels.overdue + '</span>';
                    }
                    listsControlHTML += '	</span>';
					listsControlHTML += '</li>';
                    listsControlHTML += '<div class="list_option_settings_menu_wrap">';
                    listsControlHTML += '	<div id="list_edit_flyout_focus" class="property_flyout"></div>';
                    listsControlHTML += '	<div id="list_edit_background_focus" class="task_repeat_background" onclick="hideListEditFlyout(event,\'focus\')"></div>';
                    listsControlHTML += '</div>';



                //starred list
					var starredColors = responseJSON.default_lists_color.starred;
					var starredSelectedStyle = '';
					if (pageListId == 'starred') {
                        starredSelectedClass = ' selected_option';
                        starredSelectedStyle = 'background: rgb(' + starredColors + ');';
                        pageListNameEl.value = controlStrings.starred;
                        window.globals.context.color = starredColors;
                        window.globals.context.listName = controlStrings.starred;
                        window.globals.context.listIconStyle = 'new-sprite new-list-sprite-star-button ';
                        if (isDark(window.globals.context.color)) {
                            starredSelectedClass += ' bg-light';
                        } else {
                            starredSelectedClass += ' bg-dark';
                        }
                        controlItemIcon = window.globals.context.listIconStyle
                    } else {
                        controlItemIcon = 'new-sprite new-list-sprite-star-button';
                    }

                    taskCountDisplay = responseJSON.starredcount > 0 ? 'display:inline-block' : '';
                    taskCountClass = responseJSON.overduestarredcount > 0 ? 'activesplit' :'active';
                    overdueCountDisplay = responseJSON.overduestarredcount > 0 ? 'display:inline-block' :'';

                    if (responseJSON.starredcount == 1) {
                        tasks_label = labels.task;
                    } else {
                        tasks_label = labels.tasks;
                    }
					listsControlHTML += '<li id="starred_list" class="group_option ' + starredSelectedClass + '" style="' + starredSelectedStyle + '" >';
                    listsControlHTML += '<div class="group-color" style="background: rgb(' + starredColors + ')"></div>'
					listsControlHTML += '	<div style="background:rgba(255,255,255,0)" onclick="SetCookieAndLoad( \'TodoOnlineListId\', \'starred\')" class="drop_target" ondragenter="starListDragEnter(event)" ondragover="stopEventPropogation(event);return false" ondragleave="starListDragLeave(event)" ondrop="starListCatchDrop(event)" ></div>';
					listsControlHTML += '	<span class="' + controlItemIcon + ' ' + starredSelectedClass + '">';
					listsControlHTML += '	</span>';
                    listsControlHTML += '	<span class="control_edit_icon" id="control_edit_icon_starred">';
                    listsControlHTML += '		<i class="fa fa-cogs"></i> <br/>';
                    listsControlHTML += '	</span>';
					listsControlHTML += '	<span class="option_name" >';
					listsControlHTML += 		controlStrings.starred;
					listsControlHTML += '	</span>';
                    listsControlHTML += '	<span class="badge_count_wrap">';
                    listsControlHTML += '	    <span class="' + taskCountClass + '"  style="' + taskCountDisplay + '">'+ responseJSON.starredcount + ' ' + tasks_label + '</span>';
					if( responseJSON.overduestarredcount > 0 ){
						listsControlHTML += '	    <span class="overdue" style="' + overdueCountDisplay + '">'+ responseJSON.overduestarredcount + ' ' + labels.overdue + '</span>';
					}
                    listsControlHTML += '	</span>';
					listsControlHTML += '</li>';
                    listsControlHTML += '<div class="list_option_settings_menu_wrap">';
                    listsControlHTML += '	<div id="list_edit_flyout_starred" class="property_flyout"></div>';
                    listsControlHTML += '	<div id="list_edit_background_starred" class="task_repeat_background" onclick="hideListEditFlyout(event,\'starred\')"></div>';
                    listsControlHTML += '</div>';






                //custom lists
					for (var i = 0; i < listsJSON.length; i ++)
					{
						var listId = listsJSON[i].listid;
						var listName = htmlEntities(listsJSON[i].name);
						var listColor = listsJSON[i].color;
                        var taskCount = listsJSON[i].taskcount;
                        taskCountDisplay = taskCount > 0 ? 'display:inline-block' : '';
                        taskCountClass = listsJSON[i].overduecount > 0 ? 'activesplit' :'active';
                        overdueCountDisplay = listsJSON[i].overduecount > 0 ? 'display:inline-block' :'';


                        var inboxBackgroundSides = '';
                        var inboxBackgroundClass = '';
                        var iconClass= 'sprite-list-normal';
                        var selectedlistClass = '';
                        var selectedListStyles = '';
                        var isInbox = false;
                        if(listsJSON[i].inbox)
                            isInbox = true;

						if (isInbox) {
                            listName = controlStrings.inbox;
                            iconClass = 'sprite-list-inbox';
                            inboxBackgroundSides = '<span class="inbox_bg_left"></span><span class="inbox_bg_right"></span>';
                            inboxBackgroundClass = 'inbox ';
                        }
                        if (pageListId == listId) {
                            selectedlistClass = ' selected_option';
                            selectedListStyles = 'background: rgb(' + listColor + ')';
                            pageListNameEl.value = listName;
                            window.globals.context.color = listColor;
                            window.globals.context.listName = listName;
                            window.globals.context.listIconStyle = 'new-list-sprite sprite-list-normal';

                            if (isDark(window.globals.context.color)) {
                                selectedlistClass += ' bg-light';
                            } else {
                                selectedlistClass += ' bg-dark';
                            }
                        }

                        var sharedClass = '';
						if (listsJSON[i].shared)
						{
							sharedClass = 'shared';
						}

                        var iconTypeClass = 'new-sprite';
                        if (listsJSON[i].iconName) {
                            iconClass = 'new-list-sprite-' + listsJSON[i].iconName;
                            iconTypeClass = 'new-list-sprite';

                            if (pageListId == listId) {
                                window.globals.context.listIconStyle = 'new-list-sprite ' + iconClass;
                            }
                        }
                        if (taskCount == 1) {
                            tasks_label = labels.task;
                        } else {
                            tasks_label = labels.tasks;
                        }
						listsControlHTML += '<li id="' + listId + '" class="group_option ' + selectedlistClass + '" style="' + selectedListStyles + '" >';
						listsControlHTML += '<div class="group-color" style="background: rgb(' + listColor + ');"></div>'
						listsControlHTML += '	<div style="background:rgba(255,255,255,0)"  class="drop_target" ondragenter="listDragEnter(event, \'' + listId + '\')" ondragleave="listDragLeave(event, \'' + listId + '\')" ondragover="stopEventPropogation(event);return false" ondrop="listCatchDrop(event, \'' + listId + '\', \'' + listColor + '\')" onclick="SetCookieAndLoad( \'TodoOnlineListId\', \'' + listId + '\')"></div>';
						listsControlHTML += '	<span class="control_edit_icon" id="control_edit_icon_' + listId + '">';
						listsControlHTML += '		<i class="fa fa-cogs"></i> <br/>';
						listsControlHTML += '	</span>';
                        listsControlHTML += '   <span class="shared_edit_icon ' + sharedClass + '">';
                        listsControlHTML += '       <i class="fa fa-rss"></i> <br/>';
                        listsControlHTML += '   </span>';
						listsControlHTML += '	<span class="' + iconTypeClass + ' ' + iconClass + ' ' + selectedlistClass + '" ></span>';
						// listsControlHTML += '	<span class="' + inboxBackgroundClass + 'list_background_color" style="background-color:rgba(' + listColor + ', 1);" >' + inboxBackgroundSides  +'</span>';
						listsControlHTML += '	<span class="option_name" style="width:63.8%">';
						listsControlHTML += 		listName;
						listsControlHTML += '	</span>';
						listsControlHTML += '	<span class="badge_count_wrap">';
						listsControlHTML += '	    <span class="' + taskCountClass + '"  style="' + taskCountDisplay + '">'+ taskCount + ' ' + tasks_label + '</span>';
						if( listsJSON[i].overduecount > 0 ) {
							listsControlHTML += '	    <span class="overdue" style="' + overdueCountDisplay + '">'+ listsJSON[i].overduecount + ' ' + labels.overdue + '</span>';
						}
						listsControlHTML += '	</span>';
						listsControlHTML += '</li>';
						listsControlHTML += '<div class="list_option_settings_menu_wrap">';
                        listsControlHTML += '	<div id="list_edit_flyout_' + listId + '" class="property_flyout"></div>';
                        listsControlHTML += '	<div id="list_edit_background_' + listId + '" class="task_repeat_background" onclick="hideListEditFlyout(event,\'' + listId + '\')"></div>';
                        listsControlHTML += '</div>';
					}


                //create list
					listsControlHTML += '<li  class="group_option dashboard_create_list">';
					listsControlHTML += '	<span class="option_left_icon">';
					listsControlHTML += '	</span>';
					listsControlHTML += '	<a href="javascript:void(0)" onclick="displayCreateListModal()" >'; //displayModalWindow(\'addListModal\', setFocusOnListName)">';
					listsControlHTML += '		<span class="option_name">' + controlStrings.createList + ' +';
					listsControlHTML += '		</span>';
					listsControlHTML += '	</a>';
					listsControlHTML += '</li>';

                //render lists
					listsControlEl.innerHTML = listsControlHTML;

                //bind events
                    for(var i = 0; i < listsJSON.length; i++)
                    {
                        var list = listsJSON[i];
                        var listId = list.listid;
                        var name = list.name;
                        var color = list.color;
                        var description = '';
                        if(list.description)
                            description = list.description;
                        var isInbox = false;
                        if(list.inbox)
                            isInbox = true;

                        var el = document.getElementById('control_edit_icon_' + listId);
                        var event = (function(l,n,c,d,i,r){return function(event){displayEditListFlyout(event, l,n,c,d,i,r);}}(listId,name,color,description,isInbox));
                        el.bindEvent('click',event, false);
                    }

                    var event = (function(l,n,c,d,i,r){return function(event){displayEditListFlyout(event, l,n,c,d,i,r);}}('all',controlStrings.all,allColors,'',true));
                    document.getElementById('control_edit_icon_all').bindEvent('click',event, true);
                    var event = (function(l,n,c,d,i,r){return function(event){displayEditListFlyout(event, l,n,c,d,i,r);}}('focus',controlStrings.focus,focusColors,'',true));
                    document.getElementById('control_edit_icon_focus').bindEvent('click',event, true);
                    var event = (function(l,n,c,d,i,r){return function(event){displayEditListFlyout(event, l,n,c,d,i,r);}}('starred',controlStrings.starred,starredColors,'',true));
                    document.getElementById('control_edit_icon_starred').bindEvent('click',event, true);
					//document.getElementById('list_name_wrap').innerHTML = pageListNameEl.value;
                    jQuery('.list_option_settings_menu_wrap').appendTo('#controls');
            	}
            	else
            		displayGlobalErrorMessage('Failed to retrieve lists for dashboard control: ' + ajaxRequest.responseText);
            }
        }
    }

    params = 'method=getControlContent&type=list&counts=true';

    ajaxRequest.open("POST", ".", false);
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);

};

function displayCreateListModal()
{
    window.list_creating = false;

	var bodyHTML = '';
	var headerHTML = controlStrings.createList;
	var footerHTML = '';

	bodyHTML += ' 	<div class="breath-4"></div>';
	bodyHTML += ' 	<div><input id="new_list_name" type="text" class="centered_text_field" placeholder="' + labels.enter_a_list_name + '" onkeyup="shouldEnableCreateListOkButton(event, this)" oninput="shouldEnableCreateListOkButton(event, this)"/></div>';

	footerHTML += '<div class="button" onclick="cancelMultiEditListSelection()">' + labels.cancel + '</div>';
	footerHTML += '<div id="create_list_ok_button" class="button disabled" >' + labels.ok + '</div>';

	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	document.getElementById('new_list_name').focus();
};

function createList()
{
	var doc = document;
	var name = doc.getElementById('new_list_name').value.trim();
    window.list_creating = true;
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

                if(response.success)
                {
                	loadListsControl();
                	cancelMultiEditListSelection();
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
                            displayGlobalErrorMessage(labels.error_from_server + ' ' +response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=addList&listName=" + encodeURIComponent(name);

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function shouldEnableCreateListOkButton(event, inputEl)
{
	var enableButton = inputEl.value.trim().length > 0 ? true : false;
	var button = document.getElementById('create_list_ok_button')

	if (enableButton && !window.list_creating)
	{
		button.setAttribute('class', 'button');
        button.onclick = function () {
            button.setAttribute('class', 'button disabled');
            button.onclick = null;
            createList();
        };
	}
	else
	{
		button.setAttribute('class', 'button disabled');
		button.onclick = null;
	}

    if (event.keyCode == 13 && enableButton && !window.list_creating) {
        window.list_creating = true;
        button.setAttribute('class', 'button disabled');
        button.onclick = null;
        createList();
    }
};

function loadCalendarControl()
{
	var doc = document;
	var calendarEl = doc.getElementById('dashboard_calendar');

	calendarEl.innerHTML =  labels.calendar_goes_here;
}

// ! Auxiliary
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (searchElement /*, fromIndex */ ) {
        "use strict";
        if (this == null) {
            throw new TypeError();
        }
        var t = Object(this);
        var len = t.length >>> 0;
        if (len === 0) {
            return -1;
        }
        var n = 0;
        if (arguments.length > 0) {
            n = Number(arguments[1]);
            if (n != n) { // shortcut for verifying if it's NaN
                n = 0;
            } else if (n != 0 && n != Infinity && n != -Infinity) {
                n = (n > 0 || -1) * Math.floor(Math.abs(n));
            }
        }
        if (n >= len) {
            return -1;
        }
        var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
        for (; k < len; k++) {
            if (k in t && t[k] === searchElement) {
                return k;
            }
        }
        return -1;
    }
};
//console.log('Finished loading DashboardControls.js');

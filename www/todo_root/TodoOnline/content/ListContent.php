<div id="CONTENT_TOOLBAR">
	<?php
        
		if(isset($_COOKIE['TodoOnlineListId']))
		{
			$selectedlistid = $_COOKIE['TodoOnlineListId'];
		}
		else
		{
			$selectedlistid = 'all';
		}	
		
		$goBack = new PBButton;
		$goBack->setLabel('< Dashboard');
		
		$goBack->setUrl('?list='.$selectedlistid);
		
		$toolbarButtons = array($goBack);
		include_once('TodoOnline/content/ContentToolbarButtons.php');
	?>
</div>

<?php  include_once('TodoOnline/ajax_config.html'); ?>

<br/><br/><br/>
<div class="list_comments_container">

<?php
	
	if(isset($_COOKIE['TodoOnlineListId']))
	{
		$selectedlistid = $_COOKIE['TodoOnlineListId'];
	}
	else
	{
		$selectedlistid = 'all';
	}	
	
	$userid = $session->getUserId();
	$list = TDOList::getListForListid($selectedlistid);
	if(!empty($list))
	{
	    if(TDOList::userCanViewList($selectedlistid, $userid))
	    {
	        echo '<h1>'.$list->name().'</h1>';
	        $listDescription = $list->description();
	        if(!empty($listDescription))
	            echo '<h4>'.$listDescription.'</h4>';
	        
	        
	        $listid = $list->listId();
	        echo "<br>";
	        echo "<table>";
	        echo "<tr><td>listid:</td><td>" . $listid . "</td><td/><td/></tr>";
	        echo "<tr><td>Creator:</td><td>" . TDOUser::displayNameForUserId($list->getCreator()) . "</td><td/><td/></tr>";
	
	        echo "</table>";
	
	        echo '<br /><br />';
	        echo '<h3>'. _('Comments').'</h3>';
			echo '<textarea id="comments_clone_textarea" style="position:absolute;" tabindex="-1"></textarea>';
	
			echo '<div class="comments_container">';
	
			echo '<ul id="comments_'.$listid.'" style="list-style-type: none;">';
	
			pagedCommentContent($listid);
			echo '</ul>';		
			
			if(TDOList::userCanEditList($selectedlistid, $userid))
			{
	            echo '<input type="hidden" id="listid" value="'.$listid.'">';
	            echo '<input type="hidden" id="userID" value="'.$userid.'">';
	            echo '<input type="hidden" id="listName" value="'.$list->name().'">';					
				echo '<textarea id="NEW_COMMENT_list_comments" style="height:13px;" placeholder="' . _('Write a comment...') . '" onkeydown="shouldSubmitComment(event, this, postComment, \''.$listid.'\')" ></textarea>';
			}
	
			echo '</div>';		
	        
	        //echo '<br /><br/>';
	        //echo '<h3>Recent Activity</h3>';	
	        //echo '<br />';
			
			//$loglistid = $selectedlistid;		
			//$logOffset = 0;
			//$logLimit = 10;
			
			//echo '<div class="changelog_container">
			//		<ul id="changelog_container_ul">';				
			//echo '	</ul>
			//			<div id="more_button_container" class="more_button_container">
			//			<a href="javascript:void(0)" onclick="getMoreChangeLog()">
			//				<div class="more_button">More Events</div>
			//			</a>
			//		</div>
	  		//	  </div>';
			
	    }
	    else
	    {
            echo _('You are not allowed to view this page because you&#39;re not a member of this list') . ' < br>';
	    }
	}
	else
	{
        echo _('Missing information about this list') . '<br>';
	}
?>
</div>
<div class="list_changelog_container">
	<div class="list_section_header">
			<h2><?php _e('Ticker'); ?></h2>
			<div id="changelog_assign_filter_container" class="task_assign_filter_container" style="float:right;">
			<!-- this is filled out by Javascript on load (see bottom of the page)-->
			</div>	
	</div>
	
	<?php
		$userId = $session->getUserID();
		$logOffset = 0;
		$logLimit = 10;
		
		echo '
		
			<ul id="changelog_container_ul">
			
			</ul>
			<input type="hidden" id="userID" value="'.$userId.'"/>
			<div id="more_button_container" class="more_button_container">
				<a href="javascript:void(0)" onclick="getMoreChangeLog()">
					<div class="more_button" id="show_more_changelog_div">' . _('Show More') . '</div>
				</a>
			</div>
			';		
	?>
</div>	
<script type="text/javascript" src="<?php echo TP_JS_PATH_COMMENT_FUNCTIONS; ?>" ></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_TASK_FUNCTIONS; ?>" ></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_CHANGELOG_FUNCTIONS; ?>"></script>


<script type="text/javascript">
	
	var listid = "";
	var changelogOffset = 0;
	var changelogLimit = 10;
	
	var changeLogFilterUser = false;
	var prevChangeLogFilterUser = changeLogFilterUser;
	
//	function outputChangeLogFilterButtons()
//	{
//		var buttonHTML = "";
//		
//		if(changeLogFilterUser == true)
//		{
//			buttonHTML = '<div class="filter_left_button " onclick="outputChangeLogFilterButtons()">All</div><div class="filter_left_button" >|</div><div class="filter_right_button filter_selected">Not Me</div>'; 						
//		}
//		else
//		{
//			buttonHTML = '<div class="filter_left_button filter_selected" onclick="">All</div><div class="filter_left_button" >|</div><div class="filter_right_button " onclick="outputChangeLogFilterButtons()">Not Me</div>';	
//		}
//		
//		document.getElementById('changelog_assign_filter_container').innerHTML = buttonHTML;
//		
//		changeLogFilterUser = !changeLogFilterUser;
//	
//		// because we changes the changeLogFilterShowAll, this will reload
//		getMoreChangeLog();
//	}
//
//
//	function getMoreChangeLog()
//	{
//		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
//		if(!ajaxRequest)
//			return false;
//		
//		var listid = document.getElementById('listid').value;
//		var userId = document.getElementById('userId').value;
//		
//		// Create a function that will receive data sent from the server
//		ajaxRequest.onreadystatechange = function()
//		{
//			if(ajaxRequest.readyState == 4)
//			{
//                try
//                {
//                    //first make sure there wasn't an authentication error
//                    var response = JSON.parse(ajaxRequest.responseText);
//                    if(response.success == false && response.error=="authentication")
//                    {
//                        //make the user log in 
//                        history.go(0);
//                        return;
//                    }
//                }
//                catch(e)
//                {
//                }
//                if(ajaxRequest.responseText != "")
//                {
//                    innerHTMLText = document.getElementById('changelog_container_ul').innerHTML + ajaxRequest.responseText;
//                    document.getElementById('changelog_container_ul').innerHTML = innerHTMLText;
//  
//                    changelogOffset += changelogLimit;
//                    
//                }
//                else
//                {
//                    alert("Unable to fetch more ticker events");
//                }
//                
//			}
//		}
//		// if the filter changes, reset the content and variables
//		if(changeLogFilterUser != prevChangeLogFilterUser)
//		{
//			document.getElementById('changelog_container_ul').innerHTML = ""
//			changelogOffset = 0;
//			prevChangeLogFilterUser = changeLogFilterUser;
//		}
//	
//		var params = "method=getPagedChangeLog&listid=" + listid + "&userid=" + userId + "&offset=" + changelogOffset + "&limit=" + changelogLimit;
//		
//		if(changeLogFilterUser == true)
//			params = params + "&filterUser=true";
//		
//		ajaxRequest.open("POST", ".", true);
//		
//		//Send the proper header information along with the request
//		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
//		ajaxRequest.send(params);
//	}
	
	
	
	function postComment()
	{
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
		var listid = document.getElementById('listid').value;
		var userID = document.getElementById('userID').value;
		var commentText = document.getElementById('NEW_COMMENT_list_comments').value;
		var listName = document.getElementById('listName').value;
		//replace new line char with <br/>
		var newLinePattern = new RegExp('\\n','g');
		commentText = commentText.replace(newLinePattern, '<br/>');
	
		// Create a function that will receive data sent from the server
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
                        //make the user log in 
                        history.go(0);
                        return;
                    }
                }
                catch(e)
                {
                }				
					
                if(ajaxRequest.responseText != "")
                {
                    var html = document.getElementById('comments_' + listid).innerHTML;
                    document.getElementById('comments_' + listid).innerHTML = document.getElementById('comments_' + listid).innerHTML + ajaxRequest.responseText;
                    document.getElementById('NEW_COMMENT_list_comments').value = "";
                }
                else
                {
                    alert("Failed to post comment.");
                }

			}
		}
		
		var params = "method=postComment&listid=" + listid + "&itemid=" + listid + "&itemtype=1&itemname=" + listName + "&comment=" + commentText;
		ajaxRequest.open("POST", ".", true);
	
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params);
	}
	
	function removeComment(commentId)
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
	                var response = JSON.parse(ajaxRequest.responseText);
	                
	                if(response.success == true)
	                {
						var element = document.getElementById('single_comment_' + commentId);
						element.parentNode.removeChild(element);
						
	                    //history.go(0);
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
                            alert("Failed to remove comment.");
                        }
                    }
	            }
	            catch(e)
	            {
	                alert("Unknown Response");
	            }
			}
		}
		
		var params = "method=removeComment&commentid=" + commentId;
		ajaxRequest.open("POST", ".", true);
	
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params);
	}
	
	
	//load the first batch of event logs
	//getMoreChangeLog(); 
	outputChangeLogFilterButtons();
</script>
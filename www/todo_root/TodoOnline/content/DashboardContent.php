<!--tasks -->
<textarea id="comments_clone_textarea" style="position:absolute;"></textarea>
<textarea id="note_clone_textarea" style="position:absolute;"></textarea>

<div class="db_tasks_container">
	<input type="hidden" id="popupDateTimeStamp" value=""/>
	<div class="dashboard_section_header"> 	
		<h2><?php _e('Tasks'); ?></h2>
		<div id="task_assign_filter_container_id" class="task_assign_filter_container">
			<!-- populated by JS -->
  		</div>
  		<div id="task_view_type_control" class="task_view_type_control">
  			<!-- populated by JS -->
		</div>
	</div>
	<div class="tasks_container">
		<ul id="pending_tasks_container">
			<!--populated by JS -->
		</ul>
		
		<div id="more_button_container" class="more_button_container">
			<a href="javascript:void(0)" onclick="getMoreTasks()">
				<div class="more_button" id="show_more_tasks_div"><?php _e('Show More'); ?></div>
			</a>
		</div>
	</div>
</div>
<?php
//	//create modal view for people picker
//	$submitModalButton = new PBButton;
//	$submitModalButton->setLabel("Save");
//	$submitModalButton->setOnClick("saveAssignmentChanges()");
//	$modalWindow = array(	"id"=>"modal_people_picker",
//			"title"=>"Assign Task",
//			"body"=>"<div id=\"people_picker_body\"></div>",
//			"action_button"=>$submitModalButton, 
//			"cancel_button_label"=>"Cancel"
//			);
//    //We're not doing modal windows in php any more. Implement it in javascript.
//	include('TodoOnline/content/???.php');
//?>
//
//<!--changelog-->
//<div class="db_changelog_container">
//	<div class="dashboard_section_header"> 
//		<h2 ><?php _e('Ticker'); ?></h2>
//		<div id="changelog_assign_filter_container" class="task_assign_filter_container" style="float:right;">
//			<!-- this is filled out by Javascript on load (see bottom of the page)-->
//		</div>	
//	</div>	
//	<?php
//		if(isset($loadContent))
//		{
//	        if(isset($_GET['request_ids']))
//	        {
//	            include_once('TodoOnline/content/RequestContent.php');
//	            $html = "<div class=\"modal_window\">$html</div>";
//	            $modalWindow = array(	"id"=>"requestModal",
//	            						"title"=>"Plunkboard Requests",
//	            						"body"=>$html,
//	            						"cancel_button_label"=>"Done");
//	
//    //We're not doing modal windows in php any more. Implement it in javascript.
//	include('TodoOnline/content/???.php');  
//	            echo "<script type=\"text/javascript\">displayModalWindow('requestModal');</script>";
//	        }
//	    
//			//$userid = $session->getUserId();
//			$userId = $session->getUserID();
//			$logOffset = 0;
//			$logLimit = 10;
//			
//			echo '
//			
//				<ul id="changelog_container_ul">
//				
//				</ul>
//				<input type="hidden" id="userID" value="'.$userId.'"/>
//				<div id="more_button_container" class="more_button_container">
//					<a href="javascript:void(0)" onclick="getMoreChangeLog()">
//						<div class="more_button" id="show_more_changelog_div">Show More</div>
//					</a>
//				</div>
//				';	
//		}	
//	?>

</div>
<?php
//    include_once('TodoOnline/ajax_config.html');
//?>
//
//<script type="text/javascript" src="https://s3.amazonaws.com/static.plunkboard.com/scripts/calendar/tcal.js"></script>
//<script type="text/javascript" src="<?php echo TP_JS_PATH_COMMENT_FUNCTIONS; ?>" ></script>
//<script type="text/javascript" src="<?php echo TP_JS_PATH_TASK_FUNCTIONS; ?>" ></script>
//<script type="text/javascript" src="<?php echo TP_JS_PATH_CHANGELOG_FUNCTIONS; ?>"></script>
//
//<script type="text/javascript">
////load first batch of tasks
//</script>
//
//
//<script>
//
//function removeInvite(invitationid, requestid, method)
//{
//    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
//    if(!ajaxRequest)
//    {
//        return false;  
//    }
//    // Create a function that will receive data sent from the server
//    ajaxRequest.onreadystatechange = function()
//    {
//        if(ajaxRequest.readyState == 4)
//        {
//            var responseText = ajaxRequest.responseText;
//            try
//            {
//                //first make sure there wasn't an authentication error
//                var response = JSON.parse(ajaxRequest.responseText);
//                if(response.success || response.error == "authentication")
//                {
//                    history.go(0);
//                }
//                else
//                {
//                    if(response.error == "removed")
//                    {
//                        alert("Sorry, this invitation has been removed.");   
//                        history.go(0);                             
//                    }
//                    else
//                    {
//                        alert("Unable to delete request.");
//                    }
//                }
//            }
//            catch(e)
//            {
//                alert("Unknown response");
//            }
//        }
//    }
//
//
//    var params = "invitationid=" + invitationid + "&requestid=" + requestid + "&method=" + method;    
//
//    ajaxRequest.open("POST", ".", true);
//
//    //Send the proper header information along with the request
//    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
//    ajaxRequest.send(params);
//
//    
//}
//
//
//var offset = 0;
//var compOffset = 0;
//var limit = 10;
//var changeLogFilterUser = false;
//var prevChangeLogFilterUser = changeLogFilterUser;
//
////function getMoreChangeLog()
////{
////	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
////	if(!ajaxRequest)
////		return false;
////	
////	var userId = document.getElementById('userID').value;
////	
////    if(document.getElementById('show_more_changelog_div'))
////        document.getElementById('show_more_changelog_div').innerHTML = "<img src='https://s3.amazonaws.com/static.plunkboard.com/gifs/ajax-loader.gif'>";
////    
////	// Create a function that will receive data sent from the server
////	ajaxRequest.onreadystatechange = function()
////	{
////		if(ajaxRequest.readyState == 4)
////		{
////            if(document.getElementById('show_more_changelog_div'))      
////                document.getElementById("show_more_changelog_div").innerHTML = "Show More";
////            try
////            {
////                //first make sure there wasn't an authentication error
////                var response = JSON.parse(ajaxRequest.responseText);
////                if(response.success == false && response.error=="authentication")
////                {
////                    //make the user log in 
////                    history.go(0);
////                    return;
////                }
////            }
////            catch(e)
////            {
////            }	            
////            
////			if(ajaxRequest.responseText != "")
////			{
////				innerHTMLText = document.getElementById('changelog_container_ul').innerHTML + ajaxRequest.responseText;
////				document.getElementById('changelog_container_ul').innerHTML = innerHTMLText;
////				
////				offset = offset+limit;
////			}
////			else
////			{
////				alert("Unable to fetch more change log");
////			}
////		}
////	}
////	
////	// if the filter changes, reset the content and variables
////	if(changeLogFilterUser != prevChangeLogFilterUser)
////	{
////		document.getElementById('changelog_container_ul').innerHTML = ""
////		offset = 0;
////		compOffset = 10;
////		prevChangeLogFilterUser = changeLogFilterUser;
////	}
////
////	var params = "method=getPagedChangeLog&userid=" + userId + "&showAllLists=true&offset=" + offset + "&limit=" + limit;
////	
////	if(changeLogFilterUser == true)
////		params = params + "&filterUser=true";
////	
////	ajaxRequest.open("POST", ".", true);
////	
////	//Send the proper header information along with the request
////	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
////	ajaxRequest.send(params);
////}
//
function outputChangeLogFilterButtons()
{
	var buttonHTML = "";
	
	if(changeLogFilterUser == true)
	{
		buttonHTML = '<div class="filter_left_button " onclick="outputChangeLogFilterButtons()"><?php _e('All'); ?></div><div class="filter_left_button" >|</div><div class="filter_right_button filter_selected"><?php _e('Not Me'); ?></div>';
	}
	else
	{
		buttonHTML = '<div class="filter_left_button filter_selected" onclick=""><?php _e('All'); ?></div><div class="filter_left_button" >|</div><div class="filter_right_button " onclick="outputChangeLogFilterButtons()"><?php _e('Not Me'); ?></div>';
	}
	
	document.getElementById('changelog_assign_filter_container').innerHTML = buttonHTML;
	
	changeLogFilterUser = !changeLogFilterUser;

	// because we changes the changeLogFilterShowAll, this will reload
	getMoreChangeLog();
}
//
//
//
////load the first batch of event logs
////getMoreChangeLog();
//// event logs initially get loaded now by outputChangeLogFilters Button
outputChangeLogFilterButtons();
////collapseTaskDetails();
//</script>

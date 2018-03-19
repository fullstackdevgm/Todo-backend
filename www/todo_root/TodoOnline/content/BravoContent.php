
<style>
	.taskSection {
		margin: 10px;
		border: 1px solid gray;
		padding: 5px;
		background: pink;
		display: block;
	}
	.sectionHeader {
		font-size: 1.3em;
		padding: 4px;
		color: blue;
	}
</style>
<ul>
	<span id="todaySectionn" class="taskSection">
	<div class="sectionHeader">Today</div>
	<li>Funny things A</li>
	<li>Funny things B</li>
	<li>Funny things C</li>
	<li>Funny things D</li>
	<li>Funny things E</li>
	<li>Funny things F</li>
	<li>Funny things G</li>
	<li>Funny things H</li>
	<li>Funny things I</li>
	<li>Funny things J</li>
	</span>
	<span id="tomorrowSection" class="taskSection">
	<div class="sectionHeader">Tomorrow</div>
	<li>Potato A</li>
	<li>Potato B</li>
	<li>Potato C</li>
	<li>Potato D</li>
	<li>Potato E</li>
	<li>Potato F</li>
	<li>Potato G</li>
	<li>Potato H</li>
	<li>Potato I</li>
	<li>Potato J</li>
	</span>
</ul>
<script>

	window.onload = function(){
		buildHTMLForTasks("fadsfads");
		var all = document.getElementsByTagName("li");
		
        for (var i = 0; i < all.length; i++) 
        {
            //all[i].onclick = function() {alert(this.innerHTML);};
            all[i].setAttribute('onclick', 'test(e)');
        }
	};
	
	
	function test(e){
		var childrenEls = e.parentNode.childNodes();
		
		console.log(childrenEls.length);
	};
	
	function buildHTMLForTasks(jsonString)
	{
		var tasksJSON = [];
		var task = {};
		var HTML = '';
		
		//create JSON
		for (var i = 1; i <= 10; i++)
		{
			task.name = "task " + i;
			task.notes = "notes for task " + i + " blah blah blah blah";
			tasksJSON.push(task);
			task = {};
		}
		
		tasksJSON = JSON.stringify(tasksJSON);
		tasksJSON = JSON.parse(tasksJSON);
		
		for (var i = 0; i < tasksJSON.length; i++)
		{
			HTML += tasksJSON[i].name + ": " + tasksJSON[i].notes + "<br/>";
		}
		
		document.write(HTML);
	};
</script>
<!--

<style>
	
	


	.test {
		background: lightgray;
		width: 100px;
		border: 1px solid gray;
		border-radius: 8px;
		line-height: 30px;
		text-align: center;
	}
</style>
<div id="datePickerBackground" class="date_picker_background"></div>
<div id="datePicker"></div>

<script>
	drawCalendarStructure('datePicker');
	setupCalendar('datePicker', 2013, 1, 1, 1, 1, "am", true);
</script>


<div class="test" onclick="displayCalendar('datePicker', 'datePickerBackground', event)">One</div>
<br/><br/><br/>
<div class="test" onclick="displayCalendar('datePicker', 'datePickerBackground', event)">Two</div>
<!--
<div id="datePicker">
	<div id="dateStringDisplay">
		<input type="text" id="datePickerStringDisplay" />
	</div>
	<div id="calendar">
		<div id="monthYearControl">
			<span id="prevMonth"><</span>
			<span id="curMonth">June 2012</span>
			<span id="nextMonth">></span>
		</div>
		<div id="daysOfWeekWrapper">
			<ul id="daysOfWeek">
				<li>Su</li>
				<li>Mo</li>
				<li>Tu</li>
				<li>We</li>
				<li>Th</li>
				<li>Fr</li>
				<li>Sa</li>
			</ul>
		</div>
		<div id="daysOfMonth">
			<ul id="week1">
				<li class="emptyDay"></li>
				<li class="emptyDay"></li>
				<li class="emptyDay"></li>
				<li class="emptyDay"></li>
				<li class="emptyDay"></li>
				<li>1</li>
				<li>2</li>
			</ul>
			<ul id="week2">
				<li>3</li>
				<li>4</li>
				<li>5</li>
				<li>6</li>
				<li>7</li>
				<li>8</li>
				<li>9</li>
			</ul>
			<ul id="week3">
				<li>10</li>
				<li>11</li>
				<li>12</li>
				<li>13</li>
				<li>14</li>
				<li>15</li>
				<li>16</li>
			</ul>
			<ul id="week4">
				<li>17</li>
				<li>18</li>
				<li>19</li>
				<li>20</li>
				<li>21</li>
				<li>22</li>
				<li>23</li>
			</ul>
			<ul id="week5">
				<li>24</li>
				<li>25</li>
				<li>26</li>
				<li>27</li>
				<li>28</li>
				<li>29</li>
				<li>30</li>
			</ul>
		</div>
	</div>
</div>

-->
<!--
<h1>Drag and Drop</h1>
 
<style>
	li {
		height: 30px;
		border: 1px solid lightgray;
	}
	
	#output {
		height: 200px;
		width: 200px;
		border: 1px black solid;
	}
	
	#tracker {
		background: green;
	    height: 10px;
	    position: absolute;
	    top: 0;
	    width: 10px;
	}
</style>

<script>

	

	function taskStartDrag(e) 
	{
		e.dataTransfer.setData('Text','');
	}
	function taskDragOver(e)
	{
		if(this != e.target)
			e.target.setAttribute("style", "border-bottom: 1px solid red;");
	}
	
	function taskDragLeave(e)
	{
		e.target.removeAttribute("style");
	}
</script>	
<ul>
	<li draggable="true" ondragstart="taskStartDrag(event)" ondragover="taskDragOver(event)" ondragleave="taskDragLeave(event)">Task A</li>
	<li draggable="true" ondragstart="taskStartDrag(event)" ondragover="taskDragOver(event)" ondragleave="taskDragLeave(event)">Task B</li>
	<li draggable="true" ondragstart="taskStartDrag(event)" ondragover="taskDragOver(event)" ondragleave="taskDragLeave(event)">Task C</li>
	<li draggable="true" ondragstart="taskStartDrag(event)" ondragover="taskDragOver(event)" ondragleave="taskDragLeave(event)">Task D</li>
</ul>





<script type="text/javascript" src="<?php echo TP_JS_PATH_TASK_FUNCTIONS; ?>" ></script>
<br/>
<h1>Changelog popups</h1>
<br/>

<script>
	function showPopup(id) 
	{
		//var sourceDiv = e.target;
		var detailPopup = document.getElementById('changelog_event_popup');
		
		
		if(detailPopup.style.display == "none")
		{
			detailPopup.style.display = "block";
			detailPopup.focus();
		}
		else
			hidePopup('cheese');
	}
	
	function hidePopup(id)
	{
		var popupId = 'changelog_event_popup';
		document.getElementById(popupId).style.display = "none";
	}
</script>
<style>
	.changelog_popup {
		min-height: 60px;
		width: 230px;
		border: 1px solid rgb(140,140,140);
		z-index: 100;
		background: white;
		margin-left: -260px;
		float:left;
		margin-top: 5px;
		outline: none;
		padding:10px;
	}
	
	.popup_right_arrow {
		float: right;
	    margin-right: -19px;
	    margin-top: 1px;
	    z-index: 100;
	    background: url('https://s3.amazonaws.com/static.plunkboard.com/images/changelog/popup_arrow.png');
	    background-repeat: no-repeat;
	    width:10px;
	    height: 16px;
	}
</style>

<div class="list_changelog_container" style="margin-left:500px;">
	<div class="list_section_header">
			<h2>Ticker</h2>
			<div style="float:right;" class="task_assign_filter_container" id="changelog_assign_filter_container"><div onclick="" class="filter_left_button filter_selected">All</div><div class="filter_left_button">|</div><div onclick="outputChangeLogFilterButtons()" class="filter_right_button ">Not Me</div></div>	
			</div>
	
		
			<ul id="changelog_container_ul">
			<li>
			<div id="changelog_event_popup" class="changelog_popup" onblur="hidePopup('id')" style="display:none;"; >
				[details go here]
				<div class="popup_right_arrow">
				</div>
			</div>
			
			<div onclick="showPopup(event)" class="changelog_event" >
				<div class="changelog_pic_container">
					<img src="https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif" class="changelog_pic">
				</div>
				<div class="changelog_description_container">
					<img width="10" height="10" src="https://s3.amazonaws.com/static.plunkboard.com/images/changelog/changelog-type-task.png"> <span class="change_log_username">pigeon</span> deleted Green task 3 from Green List
					<div class="change_log_timestamp">
						6 hours ago  
					</div>
				</div>
			</div>
		</li>		
		</ul>			
</div>
<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
<br/>
<br/>
<h1>key codes</h1>
<script>

var currentKey = new String();
var lastKey = new String();

function returnKeyCode(textarea, e)
{
	currentKey = e.keyCode;
	
	if(currentKey == "13" && lastKey=="16")
	{
		e.preventDefault();
		textarea.value += "\n";
		lastKey = "16";
		currentKey="";
	}
	else if(currentKey == "13" && lastKey != "16")
	{
		e.preventDefault();
		alert('submit');
	} else
		lastKey = currentKey;
}
</script>
<textarea cols="50" rows="20" onkeydown="returnKeyCode(this, event)"></textarea>
<br/><br/>
<br/>
<h1>Link inside textarea</h1>
<br/><br/>
<style>
	.editDiv {
		width: 400px;
		height: 200px;
		padding: 0;
		margin: 0;
		outline: 1px lightgray solid;
	}
	#editableDivContainer {
		background: gray;
		padding: 10px;
	}
</style>
<script>
	function makeDivEditable (editableDiv)
	{
		var container = document.getElementById('editableDivContainer');
		var content = editableDiv.innerHTML;
		
		var html = '<textarea id="editTextarea" class="editDiv" onblur="makeTextareaDiv(this, event)">' + content + '</textarea>';
				
		container.innerHTML = html;
		document.getElementById('editTextarea').focus();
	}
	function makeTextareaDiv(targetTextarea, e)
	{
		alert(e.keyCode);
		if (e.target.tagName != "a")
		{
			var container = document.getElementById('editableDivContainer');
			var content = targetTextarea.value;
			
			var html = '<div class="editDiv" onclick="">'+linkify(content)+'</div>';
			
			container.innerHTML = html;
		}
	}
	
	function linkify(text)
	{
		var linkifiedText = new String();
		
		var pattern = /(http:|https:)([a-zA-Z0-9.\/&?_=!*,\(\)+-]+)/i;
		var replace = "<a href=\"$1$2\">$1$2</a>";
		linkifiedText = text.replace(pattern , replace);
		
		
		return linkifiedText;
	}
</script>
<div id="editableDivContainer" >
	<div class="editDiv"  onclick="makeDivEditable(this)">contents of the div</div>
</div>


<br/>
<h1>People picker</h1>
<br/><br/>

<form>
<div style="width:300px;">
	<ul>
		<li>
			<label for="first_option">
			<input class="picker_radio" type="radio" id="first_option" value="first_option" name="users"/>
			<div class="people_picker_option">
				<div class="picker_img_container"><img src="https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/371128_17821023_475749863_q.jpg" /></div>
				<div class="picker_label_container">First option</div>
			</div>
			</label>
		</li>
		<li>
			<label for="second_option">
			<input class="picker_radio" type="radio" id="second_option" value="second_option" name="users"/>
			<div class="people_picker_option">
				<div class="picker_img_container"><img src="https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/275335_511618673_1213131303_q.jpg" /></div>
				<div class="picker_label_container">Second option</div>
			</div>
			</label>
		</li>
		<li>
			<label for="third_option">
			<input class="picker_radio" type="radio" id="third_option" value="third_option" name="users"/>
				<div class="people_picker_option">
					<div class="picker_img_container"><img src="https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/161145_1559400058_1632839991_q.jpg" /></div>
					<div class="picker_label_container">Third option</div>
				</div>
			</label>
		</li>
	</ul>
</div>
</form>

<br/><br/>
<h1>Hide sibbling</h1>
<style>
#toggle:hover + #sibbling{
	visibility: hidden;
}
</style>
<div id="toggle">Hover over me</div>
<div id="sibbling">Sibbling</div>

<br/><br/><br/>
<h1>click to edit</h1>
<br/><br/>

<input style="padding:4px;" class="editable_input_field" id="click2edit" value="click here to edit this string" onkeydown="if (event.keyCode == 13) alert('updating task…')" />

<br/><br/><br/>
<h1>Toolbar buttons</h1>
<div id="CONTENT_TOOLBAR">

-->
	<?php
				
		/*
		$goBack = new PBButton;
		$goBack->setLabel('< Dashboard');
		$goBack->setUrl('.');
		
		$testButton = new PBButton;
	
		$testButton->setLabel("Multiple Options");
		
		
		$child_1 = new PBButton;
		$child_2 = new PBButton;
		$divisor = new PBButton;
		$java = new PBButton;
		
		$child_1->setLabel("Disabled option");
		$child_1->setIsDisabled("true");
		
		$child_2->setLabel("Selected option");
		$child_2->setIsSelected("true");
		
		$java->setLabel('Javascript option');
		$java->setOnClick ("alert('this is javascript')");
		
		$divisor->setIsDivisor("YES");
		
		$childrenButtons = array($child_1, $child_2, $divisor, $java);
		
		$testButton->setChildren($childrenButtons);
	
	
	
		$toolbarButtons = array($goBack, $testButton);
		include_once('TodoOnline/content/ContentToolbarButtons.php');
		*/
	?>

<!--</div>


<br/><br/><br/>
<h1>Profiles</h1>
	
	<?php 
		/*
		$button_1 = new PBButton;
			$button_1->setLabel("Add friend");
			
			$profile_1 = array("picture_url"=>"https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/275335_511618673_1213131303_q.jpg", "title" => "Brian Bravo", "caption"=>"Works at Appigo", "button"=>$button_1, "url"=>"https://www.facebook.com/profile.php?id=511618673");
			
			$profile_2 = array("picture_url"=>"https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/371128_17821023_475749863_q.jpg", "title"=>"Valorie Bravo", "caption"=>"Best Valentine ever!", "button"=>$button_1);
			
			$profiles = array($profile_1, $profile_2);
		
		include_once('TodoOnline/content/ContentProfiles.php');
		*/
	?>
	
<br/><br/>

<h1>On blur for a div</h1>
<div onblur="alert('You clicked away!')" id="someDiv" name="toggle" tag="this is a tag" style="width:100px;height:100px;background:gray;border:1px solid black;">Click here</div>
<div name="buttonFlyout" style="height:200px;width:100px;border:1px solid black;display:none;">This is the flyout</div>
	
<script>
	var x = document.getElementById("someDiv");
	
	var isFlyoutVisible = new Boolean();
	isFlyoutVisible = false;
	
	
	function toggleFlyout(element)
	{		
		//add onblur event
		
		var nextS=element.nextSibling;
		while(nextS.nodeType!=1) //this will find the next non-blank-space sibbling
		{
			nextS=nextS.nextSibling;
		}
		
		if (isFlyoutVisible)
		{
			nextS.style.display ="block";
			isFlyoutVisible = false;
		}
		else
		{
			nextS.style.display ="none";
			isFlyoutVisible = true;
		}
	}
	
	function blurMe()
	{
		alert('blurred');
	}
	
	
</script>
	
	
	
<br/><br/><br/>
<h1>Comment box</h1>	
	
	
	<style>
	
	</style>
	
	
<?php 
	/*
	$comment_1= array(	"picture_url"=> "https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/275335_511618673_1213131303_q.jpg",
						"author"=>"Brian Bravo",
						"author_link"=>"https://www.facebook.com/profile.php?id=511618673",
						"comment"=> "Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit",
						"delete_action" => "alert('deleting first comment')",
						"timestamp"=>"21 mins ago",
						"like_action"=>"alert('you liked the first comment')");
									
	$comment_3= array(	"picture_url"=> "https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/275335_511618673_1213131303_q.jpg",
						"author"=>"Brian Bravo",
						"comment"=> "Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit",
						"delete_action" => "alert('deleting the second comment')",
						"timestamp"=>"21 mins ago",
						"like_action"=>"alert('you liked the second comment')");
		
	$myComments = array($comment_1, $comment_3);
		
	$comments_group = array(	"id"=>"xyz",
								"comments"=>$myComments,
								"submit_method" => "alert('xyz!')");
								
	include('TodoOnline/content/CommentsSetup.php');
	
	print '<br/><br/>';
	$comment_1= array(	"picture_url"=> "https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/275335_511618673_1213131303_q.jpg",
						"author"=>"Brian Bravo",
						"author_link"=>"https://www.facebook.com/profile.php?id=511618673",
						"comment"=> "Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit",
						//"delete_action" => "alert('deleting the comment')",
						"timestamp"=>"21 mins ago",
						"like_action"=>"alert('you liked this comment')");
									
	$comment_3= array(	"picture_url"=> "https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/275335_511618673_1213131303_q.jpg",
						"author"=>"Brian Bravo",
						"comment"=> "Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit",
						//"delete_action" => "alert('deleting the comment')",
						"timestamp"=>"21 mins ago",
						"like_action"=>"alert('you liked this comment')");
		
	$myComments = array($comment_1, $comment_3);
		
	$comments_group = array(	"id"=>"abc",
								"comments"=>$myComments);
								
	include('TodoOnline/content/CommentsSetup.php');
	

	print '<br/><br/><br/>';
		*/
?>
	
<br/><br/>
	
<h1>Modal Windows</h1>
<?php
	/*
	$action_button = new PBButton;
  	$action_button->setLabel("Submit");
 	$action_button->setOnClick("alert('hello :]')");
  		
  	$modalWindow = array(	"id" => "sampleWindow",
  							"title"=>"This is a modal window",
  							"body"=>"This is the body of the modal window. </br>This is a new line with some <span style=\"font-weight:bold;\">html</span> in it.",
  							"action_button"=>$action_button, 
  							"cancel_button_label"=>"Cancel");
	
    //We're not doing modal windows in php any more. Implement it in javascript.
	include('TodoOnline/content/???.php');
	
	$delete_button = new PBButton;
  	$delete_button->setLabel("Delete");
 	$delete_button->setOnClick("alert('deleting list')");
 	
	$modalWindow = array(	"id" => "deleteWindow",
  							"title"=>"Delete list",
  							"body"=>"Are you sure you want to delete this list?",
  							"action_button"=>$delete_button, 
  							"cancel_button_label"=>"Cancel this please");
    //We're not doing modal windows in php any more. Implement it in javascript.
	include('TodoOnline/content/???.php');	
  	
  	
  	$random_button = new PBButton;
  	$random_button->setLabel("Yes");
 	$random_button->setOnClick("alert('Awesome!')");
 	
	$modalWindow = array(	"id" => "autofocusModal",
  							"title"=>"Autofocus Modal Window",
  							"body"=>'Did the textfield get autofocus?<br/><br/><form name="autofocusForm"><input type="text" id="autofocusTextfield" /></form><script>function focusOnTextfield() { document.getElementById("autofocusTextfield").focus();}</script>',
  							"action_button"=>$random_button, 
  							"cancel_button_label"=>"No");					
    //We're not doing modal windows in php any more. Implement it in javascript.
	include('TodoOnline/content/???.php');				
	*/
?>

<br/><br/>
<input type="submit" onclick="displayModalWindow('sampleWindow')" value="sample Window"/>
<br/><br/>
<input type="submit" onclick="displayModalWindow('deleteWindow')" value="delete List"/>
<br/><br/>
<input type="submit" onclick="displayModalWindow('autofocusModal', focusOnTextfield)" value="autofocus"/>


<br/><br/><br/>



<h1> Changelog UI</h1>
<br/><br/>
<style>

.changelog_container {
	max-width: 600px;
}
.changelog_event {
	border-bottom: 1px solid lightgray;
	overflow: hidden;
	margin-bottom: 2px;
	padding-bottom: 4px;
}
.changelog_pic_container {
	float:left;
}
.changelog_description_container {
	float:left;
	min-width: 150px;
	min-height: 35px;
	padding: 3px 0;
	position: relative;
}

.change_log_timestamp {
	position: absolute;
	left: 0;
	bottom: 0;
	color:gray;
}
</style>

<div class="changelog_container">
	<ul>
		<li>
			<div class="changelog_event">
				<div class="changelog_pic_container">
					<img class="small_profile_img comment_pic" src="https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/275335_511618673_1213131303_q.jpg">
				</div>
				<div class="changelog_description_container">
					Brian Bravo created a list: Ethan's Party
					<div class="change_log_timestamp">
						Yesterday, 5:34 pm
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="changelog_event">
				<div class="changelog_pic_container">
					<img class="small_profile_img comment_pic" src="https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/275335_511618673_1213131303_q.jpg">
				</div>
				<div class="changelog_description_container">
					Brian Bravo shared "Ethan's Party" list with Valorie Bravo
					<div class="change_log_timestamp">
						Today, 8:24 am
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="changelog_event">
				<div class="changelog_pic_container">
					<img class="small_profile_img comment_pic" src="https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/371128_17821023_475749863_q.jpg">
				</div>
				<div class="changelog_description_container">
					Valorie Bravo added 7 tasks to "Ethan's Party"
					<div class="change_log_timestamp">
						Today, 10:47 am
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="changelog_event">
				<div class="changelog_pic_container">
					<img class="small_profile_img comment_pic" src="https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/275335_511618673_1213131303_q.jpg">
				</div>
				<div class="changelog_description_container">
					Brian Bravo completed "Get party supplies"
					<div class="change_log_timestamp">
						21 mins ago
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="changelog_event">
				<div class="changelog_pic_container">
					<img class="small_profile_img comment_pic" src="https://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/371128_17821023_475749863_q.jpg">
				</div>
				<div class="changelog_description_container">
					Valorie Bravo added 2 more tasks to "Ethan's Party"
					<div class="change_log_timestamp">
						1 mins ago
					</div>
				</div>
			</div>
		</li>
	</ul>
	
</div>
<br/><br/><br/>

<h1>More Button</h1>
<br/><br/>
<style>
	.more_button_container {
		width: 100%;
		border:1px gray rgb(216,223,234);
		color:rgb(82,110,166);
		background: rgb(237, 239, 244);
		padding:4px;
		text-align: center;
	}
	.more_button_container:hover {
		cursor: pointer;
		background: rgb(216, 223, 234);
	}
	
	.more_button_container a:link {text-decoration: none;color:rgb(82,110,166);}
	.more_button_container a:visited {text-decoration: none;color:rgb(82,110,166);}
	.more_button_container a:hover {text-decoration: underline;color:rgb(82,110,166);}
	.more_button_container a:active {text-decoration: none;color:rgb(82,110,166);}
	
	.more_button_img {
		visibility: hidden;
		margin-left: 10px;
	}
</style>
<script>
	function fetchMoreData () {
		document.getElementById("more_button_img").style.visibility = "visible";
		
		setTimeout('document.getElementById("more_button_img").style.visibility ="hidden"', 3000);
	}
</script>
	<div class="more_button_container">
		<a href="javascript:void(0)" onclick="fetchMoreData()">
			<div class="more_button">
			More<span><img class="more_button_img" id="more_button_img" src="https://s-static.ak.facebook.com/rsrc.php/v1/yb/r/GsNJNwuI-UM.gif" /></span>
			</div>
		</a>
	</div>



<br/><br/><Br/><br/><br/><br/><br/><br/><Br/><br/><br/><br/><br/><br/><Br/><br/><br/><br/>

-->


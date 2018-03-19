<div id="CONTENT_TOOLBAR">

<?php
	$goBack = new PBButton;
	$goBack->setLabel('< Dashboard');
	$goBack->setUrl('.');
	
	$toolbarButtons = array($goBack);
	include_once('TodoOnline/content/ContentToolbarButtons.php');
?>

</div>
<?php
	
	$firstNameStatus = "(not set)";
	$lastNameStatus = "(not set)";
	$usernameStatus = "(not set)";
	$passwordStatus = "(not set)";
	$fakePass = "•••••••••••••";
    $selectedTimezone = '';
	
	if($session->isLoggedIn())
	{
		$user = TDOUser::getUserForUserId($session->getUserId());
		
		if(!empty($user))
		{
			$firstName = htmlspecialchars($user->firstName());
			$lastName = htmlspecialchars($user->lastName());
			
			if(!empty($firstName))
				$firstNameStatus = "";
			if(!empty($lastName))
				$lastNameStatus = "";

			$username = htmlspecialchars($user->username());
			
			if(!empty($username))
				$usernameStatus = "";
			
			$password = $user->password();
			if(!empty($password))
				$passwordStatus = "";
			else
			{
				$fakePass = "";
			}
            
            $userSettings = TDOUserSettings::getUserSettingsForUserid($user->userId());
            if($userSettings)
            {
                $userTimezone = $userSettings->timezone();
                if(!empty($userTimezone))
                {
                    $selectedTimezone = $userTimezone;
                }
                $filterSetting = $userSettings->tagFilterWithAnd();
                
                $showUndueTaskSetting = $userSettings->focusShowUndueTasks();
                $showStarredTaskSetting = $userSettings->focusShowStarredTasks();
                $hideDueAfterSetting = $userSettings->focusHideTaskDate();
                $hidePrioritySetting = $userSettings->focusHideTaskPriority();
                $focusListFilterString = $userSettings->focusListFilterString();
                $completedTasksSetting = $userSettings->focusShowCompletedDate();
                
                $filters = explode("," , $focusListFilterString);
                $filterCount = 0;
                foreach($filters as $filter)
                {
                    if(strlen($filter) > 0 && $filter != "none" && $filter != NULL)
                    {
                        $filterCount++;
                    }
                }
                $focusListFilterSummary = _('Show All');
                if($filterCount > 0)
                    $focusListFilterSummary = $filterCount.' '._('Hidden');
            }
            else
                error_log("Unable to get user settings for user: ".$user->userId());
		}

	}
    function formatOffset($offset) 
    {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int) abs($hours);
        $minutes = (int) abs($remainder / 60);

        if ($hour == 0 AND $minutes == 0) 
        {
            $sign = ' ';
        }
        return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) .':'. str_pad($minutes,2, '0');

    }
?>


<script type="text/javascript">

var currentTimezoneValue = "<?php echo $selectedTimezone; ?>";

var hideDueAfterOptions = ["<?php _e('No Filter'); ?>", "<?php _e('Today'); ?>", "<?php _e('Tomorrow'); ?>", "<?php _e('Next Three Days'); ?>", "<?php _e('One Week'); ?>", "<?php _e('Two Weeks'); ?>", "<?php _e('One Month'); ?>", "<?php _e('Two Months'); ?>"];
var selectedDueAfterOption = "<?php echo $hideDueAfterSetting; ?>";

var hidePriorityOptions = [{value:0, title:"<?php _e('None'); ?>"},{value:9, title:"<?php _e('Low'); ?>"},{value:5, title:"<?php _e('Medium'); ?>"},{value:1, title:"<?php _e('High'); ?>"}];

var selectedPriorityOption = "<?php echo $hidePrioritySetting; ?>";
var selectedPriorityIndex = 0;

var focusListFilterString = "<?php echo $focusListFilterString; ?>";

var completedTasksOptions = ["<?php _e('None'); ?>", "<?php _e('One Day'); ?>", "<?php _e('Two Days'); ?>", "<?php _e('Three Days'); ?>", "<?php _e('One Week'); ?>", "<?php _e('Two Weeks'); ?>", "<?php _e('One Month'); ?>", "<?php _e('One Year'); ?>"];
var selectedCompletedTasksOption = "<?php echo $completedTasksSetting; ?>";

</script>

<br>
<h1><?php _e('General Settings'); ?></h1>

<!--
<div class="setting_options_container">
    <ul style="list-style-type: none;">
        <!--tag filter setting
            <li id="tagFilterSettingValueBox" class="setting_option">
                <script> document.getElementById("tagFilterSettingValueBox").style.background ="white"; </script>
                <span class="setting_label_wide">Tag Filter Setting</span>
                <span class="setting_value_narrow"></span>
                <span class="setting_radio_button">
                    <input type="radio" <?php// if(!$filterSetting) echo 'checked="true"'; ?> name="tagFilterRadio" id="tagFilterRadioOr" onchange="toggleTagFilterSetting(0)"> Or
                    &nbsp
                    <input type="radio" <?php// if($filterSetting) echo 'checked="true"'; ?> name="tagFilterRadio" id="tagFilterRadioAnd" onchange="toggleTagFilterSetting(1)"> And
                </span>

                <span id="tagFilterUpdateStatus" style="float: right"></span>
            </li>
    </ul>
</div>
-->
<br>
<h2><?php _e('Focus List Settings'); ?></h2>
<!--
<div class="setting_options_container">
    <ul style="list-style-type: none;">
        <!--show tasks with no due date setting-
            <li id="focusShowUndueTasksSettingValueBox" class="setting_option">
                <script> document.getElementById("focusShowUndueTasksSettingValueBox").style.background ="white"; </script>
                    <span class="setting_label_wide">Show Tasks With No Due Date</span>
                    <span class="setting_value_narrow"></span>
                    <span class="setting_checkbox">
                        <input type="checkbox" <?php if($showUndueTaskSetting) echo 'checked="true"';  ?> id="focusShowUndueTasksSettingCheckbox" onchange="toggleFocusShowUndueTasksSetting()">
                    </span>

                <span id="focusShowUndueTasksUpdateStatus" style="float: right"></span>
            </li>
            
        <!--show starred tasks setting
            <li id="focusShowStarredTasksSettingValueBox" class="setting_option">
                <script> document.getElementById("focusShowStarredTasksSettingValueBox").style.background ="white"; </script>
                    <span class="setting_label_wide">Show Starred Tasks</span>
                    <span class="setting_value_narrow"></span>
                    <span class="setting_checkbox">
                        <input type="checkbox" <?php if($showStarredTaskSetting) echo 'checked="true"';  ?> id="focusShowStarredTasksSettingCheckbox" onchange="toggleFocusShowStarredTasksSetting()">
                    </span>

                <span id="focusShowStarredTasksUpdateStatus" style="float: right"></span>
            </li>
                  
        <!--show completed tasks setting
            <li id="focusCompletedTasksSettingValueBox" class="setting_option">
                <script> document.getElementById("focusCompletedTasksSettingValueBox").style.background ="white"; </script>
                    <span class="setting_label_wide">Show Completed Tasks:</span>
                    <span class="setting_value_narrow"></span>
                    <span class="setting_checkbox">
                        <select id="focusCompletedTasksSelectBox" onchange="toggleFocusCompletedTasksSetting()">
                            <script>
                                var selectHTML = "";
                                for(var i=0; i < completedTasksOptions.length; i++)
                                {
                                    var option = completedTasksOptions[i];
                                    selectHTML += "<option value=" + i;
                                    if(i == selectedCompletedTasksOption)
                                    {
                                        selectHTML += " selected=\"true\"";
                                    }
                                    selectHTML += ">" + option + "</option>";
                                }
                                document.write(selectHTML);
                            </script>
                        </select>
                    </span>

                <span id="focusCompletedTasksSettingUpdateStatus" style="float: right"></span>
            </li>

                        
        <!--hide tasks after date setting
            <li id="focusHideTasksDateSettingValueBox" class="setting_option">
                <script> document.getElementById("focusHideTasksDateSettingValueBox").style.background ="white"; </script>
                    <span class="setting_label_wide">Hide Tasks Due After:</span>
                    <span class="setting_value_narrow"></span>
                    <span class="setting_checkbox">
                        <select id="focusHideDueAfterSelectBox" onchange="toggleFocusHideTaskDateSetting()">
                            <script>
                                var selectHTML = "";
                                for(var i=0; i < hideDueAfterOptions.length; i++)
                                {
                                    var option = hideDueAfterOptions[i];
                                    selectHTML += "<option value=" + i;
                                    if(i == selectedDueAfterOption)
                                    {
                                        selectHTML += " selected=\"true\"";
                                    }
                                    selectHTML += ">" + option + "</option>";
                                }
                                document.write(selectHTML);
                            </script>
                        </select>
                    </span>

                <span id="focusHideTasksDateSettingUpdateStatus" style="float: right"></span>
            </li>
            
        <!--hide tasks with priority setting
            <li id="focusHideTasksPrioritySettingValueBox" class="setting_option">
                <script> document.getElementById("focusHideTasksPrioritySettingValueBox").style.background ="white"; </script>
                    <span class="setting_label_wide">Hide Tasks With Priority Less Than:</span>
                    <span class="setting_value_narrow"></span>
                    <span class="setting_checkbox">
                        <select id="focusHidePrioritySelectBox" onchange="toggleFocusHidePrioritySetting()">
                            <script>
                                var selectHTML = "";
                                for(var i=0; i < 4; i++)
                                {
                                    var option = hidePriorityOptions[i];
                                    selectHTML += "<option value=" + option.value;
                                    if(option.value == selectedPriorityOption)
                                    {
                                        selectedPriorityIndex = i;
                                        selectHTML += " selected=\"true\"";
                                    }
                                    selectHTML += ">" + option.title + "</option>";
                                }
                                document.write(selectHTML);
                            </script>
                        </select>
                    </span>

                <span id="focusHideTasksPrioritySettingUpdateStatus" style="float: right"></span>
            </li>
        <!--focus list filter settings
            <a class="setting_option_link" href="javascript:void(0)" onclick="showFocusListFilterBox(this)">
                <li id="focusListFilterSettingsValueBox" class="setting_option">
                    <span class="setting_label_wide">List Filter</span>
                    <span class="setting_value_narrow" id="focusListFilterStringSummary"><?php echo $focusListFilterSummary; ?></span>
                    <span id="focusListFilterEditLink" class="setting_edit_link">Edit</span>
                </li>
            </a>	
            <div id="focusListFilterSettingsDetailBox" class="setting_option_details">
                <table cellspacing="0" cellpadding="0" id="focusListFilterPicker"></table>
                <table cellspacing="0" cellpadding="0">
                    <tr>
                        <td>
                            <input  type="submit" id="focusListFilterSettingsSubmit" onclick="saveFocusListFilterSettings()" value="Save" />
                            <input  type="submit" onclick="cancelFocusListFilterUpdate('focusListFilterSettingsValueBox', 'focusListFilterSettingsDetailBox')" value="Cancel" />
                        </td>
                    </tr> 
                </table> 
                <table cellspacing="0" cellpadding="0"> 
                </table>
                
            </div>

                                                    
            
    </ul>
</div>
-->

<br><br><br>
<h1><?php _e('Account Settings'); ?></h1>
	
<!--
<div class="setting_options_container">
	<ul style="list-style-type: none;">
		<!--first name & lastname
		<a  class="setting_option_link" href="javascript:void(0)" onclick="displaySettingDetails('firstLastNameSettingDetailsBox', this)">
			<li id="firstLastNameSettingValueBox" class="setting_option">
				<span class="setting_label">Name</span>
				<span class="setting_value" id="firstLastNameValues">
				<?php 
					echo $firstName . " " . $lastName;
				?>
				</span>
				<span id="firstLastNameEditLink" class="setting_edit_link">Edit</span>
			</li>
		</a>	
		<div id="firstLastNameSettingDetailsBox" class="setting_option_details">
			<table cellspacing="0" cellpadding="0"> 
 				<tr>
 					<td align="right" width="60">First:</td>
					<?php 
						echo '<td><input type="text" onkeyup="validateFirstLastName()" name="firstName" id="firstName" value="'.$firstName.'" autocomplete="off"/></td>';
					?>
 					<td id="firstNameStatus" class="update_status"></td>
 				</tr> 
 				<tr>
 					<td align="right" width="60">Last:</td>
					<?php 
						echo '<td><input type="text" onkeyup="validateFirstLastName()" onpaste="validateFirstLastName()" oncut="validateFirstLastName()" name="lastName" id="lastName" value="'.$lastName.'" autocomplete="off"/></td>';
					?>
 					<td id="lastNameStatus" class="update_status"></td>
 				</tr> 
 				<tr>
 					<td></td>
 					<td colspan="2">
 					<input id="nameSubmit" type="button" onclick="saveFirstLastNames()" value="Save" disabled="disabled" />
 					<input  type="submit" onclick="cancelFirstLastNameUpdate('firstLastNameSettingValueBox', 'firstLastNameSettingDetailsBox')" value="Cancel" />
 					</td>
 				</tr> 
 			</table>
		</div>
		
		<!--username--
		<a class="setting_option_link" href="javascript:void(0)" onclick="displaySettingDetails('usernameSettingDetailsBox', this)">
			<li id="usernameSettingValueBox" class="setting_option">
				<span class="setting_label">Username</span>
				<span class="setting_value" id="usernameValue">
				<?php 
					echo $username;
				?>
				</span>
				<span id="usernameEditLink" class="setting_edit_link">Edit</span>
			</li>
		</a>	
		<div id="usernameSettingDetailsBox" class="setting_option_details">
			<table cellspacing="0" cellpadding="0"> 
 				 <tr>
 				 	<td align="right" width="60">username:</td>
 				 	<td>
					<?php 
						echo '<input type="text" onkeyup="validateUsername()" name="username" id="username" value="'.$username.'" autocomplete="off"/>';
					?>
 				 	</td>
 				 	<td id="usernameStatus" class="update_status"><?php echo $usernameStatus; ?></td>
 				 </tr> 
 				<tr>
 					<td></td>
 					<td>
 						<input id="usernameSubmit" type="button" onclick="saveUsername()" value="Save" disabled="disabled" />
 						<input  type="submit" onclick="cancelUsernameUpdate('usernameSettingValueBox', 'usernameSettingDetailsBox')" value="Cancel" />
 					</td>
 				</tr> 
 			</table> 
		</div>
		
		<!--password--
		<a class="setting_option_link" href="javascript:void(0)" onclick="displaySettingDetails('passwordSettingDetailsBox', this)">
			<li id="passwordSettingValueBox" class="setting_option">
				<span class="setting_label">Password</span>
				<span class="setting_value" id="usernameValue"><?php echo $fakePass;?></span>
				<span id="passwordEditLink" class="setting_edit_link">Edit</span>
			</li>
		</a>	
		<div id="passwordSettingDetailsBox" class="setting_option_details">
			<table cellspacing="0" cellpadding="0"> 
 				<tr>
 					<td align="right" width="60">Password:</td>
 					<td><input type="password" onkeyup="validatePassword()" name="password" id="password" value="<?php echo $fakePass;?>"></td>
 					<td id="passwordStatus" class="update_status"><?php echo $passwordStatus;?></td>
 				</tr> 
 				<tr>
 					<td align="right" width="60">Verify:</td>
 					<td><input type="password" onkeyup="validatePassword()" name="verifyPassword" id="verifyPassword" value="'.$fakePass.'"></td>
 				</tr> 
 				<tr>
 					<td></td>
 					<td>
 						<input id="passwordSubmit" type="button" onclick="savePassword()" value="Save" disabled="disabled" />
 						<input  type="submit" onclick="cancelPasswordUpdate('passwordSettingValueBox', 'passwordSettingDetailsBox')" value="Cancel" />
 					</td>
 				</tr> 
 			</table>
		</div>
        
        <!--timezone--
		<a class="setting_option_link" href="javascript:void(0)" onclick="displaySettingDetails('timezoneSettingDetailsBox', this)">
			<li id="timezoneSettingValueBox" class="setting_option">
				<span class="setting_label">Time Zone</span>
				<span class="setting_value" id="timezoneValue"></span>
                <script>document.getElementById('timezoneValue').innerHTML = currentTimezoneValue</script>
				<span id="timezoneEditLink" class="setting_edit_link">Edit</span>
			</li>
		</a>	
		<div id="timezoneSettingDetailsBox" class="setting_option_details">
			<table cellspacing="0" cellpadding="0"> 
 				<tr>
                <td>
                <?php
                    $utc = new DateTimeZone('UTC');
                    $dt = new DateTime('now', $utc);

                    echo '<select id="timezoneSelect">';
                    foreach(DateTimeZone::listIdentifiers() as $tz) 
                    {
                        $current_tz = new DateTimeZone($tz);
                        $offset =  $current_tz->getOffset($dt);
                        $transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
                        $abbr = $transition[0]['abbr'];
                        $selectedString = '';
                        if($tz == $selectedTimezone)
                        {
                            $selectedString = 'selected="selected"';
                        }
                        echo '<option id="'.$tz.'" value="' ,$tz, '" '.$selectedString.'>' ,$tz, ' [' ,$abbr, ' ', formatOffset($offset), ']</option>';
                    }
                    echo '</select>';
                ?>
                </td>
 				</tr> 
 				<tr>
 					<td>
 						<input id="timezoneSubmit" type="button" onclick="saveTimezone()" value="Save" />
 						<input  type="submit" onclick="cancelTimezoneUpdate('timezoneSettingValueBox', 'timezoneSettingDetailsBox')" value="Cancel" />
 					</td>
 				</tr> 
 			</table>
		</div> 
        
               
	</ul>
</div>
-->
    
<?php

    //NCB - taking this out of the UI until we have a facebook app
//    if($session->isFB() == false)
//    {
//        echo '<br/>';
//        echo '<h3>Facebook</h3>';
//        $fbId = TDOUser::facebookIdForUserId($session->getUserId());
//        if(empty($fbId))
//        {
//            TDOSession::saveCurrentURL();
//            echo '<table cellspacing="5">';
//            echo '<tr><td>&nbsp;&nbsp;&nbsp;<a href="?method=linkFacebook">Link to my Facebook Account</a></td></tr>';
//            echo '</table>';
//        }
//        else
//        {
//            echo '<table cellspacing="5">';
//            echo '<tr><td><img src="https://graph.facebook.com/'.$fbId.'/picture"></td>';
//
//            $userData = json_decode(file_get_contents("https://graph.facebook.com/$fbId"), true);
//            if($userData)
//            {
//                $firstName = '';
//                $lastName = '';
//                if(isset($userData['first_name']))
//                    $firstName = $userData['first_name'];
//                if(isset($userData['last_name']))
//                    $lastName = $userData['last_name'];
//                echo "<td>$firstName</td><td>$lastName</td>";
//            }
//
//            //If the user has other login credentials, give him the option to unlink his facebook account
//            $uName = $user->username();
//            $pass = $user->password();
//            if(!empty($uName) && !empty($pass))
//            {
//                echo '<td><a href="javascript:unlinkFacebook()">Unlink</a></td></tr>';
//            }
//            echo '</table>';
//        }
//    }
?>
	<br/><br/>
	<h3><?php _e('Your Recent Activity in Todo Cloud'); ?></h3>
	<br/>
<?php
	$userId = $session->getUserID();
	$logOffset = 0;
	$logLimit = 10;
	
	echo '	<div class="changelog_container">
				<!--<input type="hidden" id="moreButton" value="false" />
				<input type="hidden" id="totalEvents" value="" />-->
				<ul id="changelog_container_ul">
				
				</ul>
				<input type="hidden" id="userID" value="'.$userId.'"/>
				<div id="more_button_container" class="more_button_container">
					<a href="javascript:void(0)" onclick="getMoreChangeLog()">
						<div class="more_button">'._('More Events').'</div>
					</a>
				</div>
		  	</div>';
	
?>
	
<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>" ></script>

<script type="text/javascript">

	/*
var username = "";
	var password = "";

	var currentFirstNameValue = document.getElementById('firstName').value;
	var currentLastNameValue = document.getElementById('lastName').value;
	var currentUsernameValue = document.getElementById('username').value;
*/

	/*
function displaySettingDetails(detailsId, el) 
	{
		document.getElementById(detailsId).style.display = "block";
		el.style.display = "none";
	}
	
	function hideSettingDetails(valuesId, detailsId)
	{
		document.getElementById(valuesId).parentNode.style.display = "block";
		document.getElementById(detailsId).style.display = "none";
	}
*/
	
	/*
function cancelFirstLastNameUpdate(valuesId, detailsId)
	{
		document.getElementById('firstName').value = currentFirstNameValue;
		document.getElementById('lastName').value = currentLastNameValue;
		document.getElementById('nameSubmit').setAttribute("disabled","disabled");
		document.getElementById('firstNameStatus').innerHTML = "";
		document.getElementById('lastNameStatus').innerHTML = "";
		
		hideSettingDetails(valuesId, detailsId);
	}
	
	function cancelUsernameUpdate(valuesId, detailsId)
	{
		document.getElementById('username').value = currentUsernameValue;
		document.getElementById('usernameSubmit').setAttribute("disabled","disabled");
		document.getElementById('usernameStatus').innerHTML = "";
		
		hideSettingDetails(valuesId, detailsId);
	}
	
	function cancelPasswordUpdate(valuesId, detailsId)
	{
		document.getElementById('passwordSubmit').setAttribute("disabled","disabled");
		document.getElementById('passwordStatus').innerHTML = "";
		
		hideSettingDetails(valuesId, detailsId);
	}
    
	function cancelTimezoneUpdate(valuesId, detailsId)
	{
		var selector = document.getElementById('timezoneSelect');
        selector.options[selector.options.selectedIndex].selected = false;
        document.getElementById(currentTimezoneValue).selected = true;
        
		hideSettingDetails(valuesId, detailsId);
	}
    
    function cancelFocusListFilterUpdate(valuesId, detailsId)
    {
        hideSettingDetails(valuesId, detailsId);
    }
*/
    
	
<?php    include_once('TodoOnline/ajax_config.html'); ?>
	
	/*
function validateFirstLastName()
	{
		var firstName = document.getElementById('firstName').value;
		var lastName = document.getElementById('lastName').value;
		
		if(firstName.length > 0)
			document.getElementById('firstNameStatus').innerHTML = "";
		else
			document.getElementById('firstNameStatus').innerHTML = "too short";
			
		if(lastName.length > 0)
			document.getElementById('lastNameStatus').innerHTML = "";
		else
			document.getElementById('lastNameStatus').innerHTML = "too short";
				
		if(firstName.length > 0 && lastName.length > 0)
			document.getElementById('nameSubmit').removeAttribute("disabled");
		else
			document.getElementById('nameSubmit').setAttribute("disabled","disabled");
	}
	
	function validateUsername()
	{
		var invalid = " "; // Invalid character is a space
		var minLength = 4; // Minimum length
		var validated = true;
		
		var username = document.getElementById('username').value;
		
		if(username.length < minLength)
		{
			document.getElementById('usernameStatus').innerHTML = "too short";
			validated = false;
		}
		else if (username.indexOf(invalid) > -1)
		{
			document.getElementById('usernameStatus').innerHTML = "spaces not allowed";
			validated = false;
		}	
		else
		{
			var atpos=username.indexOf("@");
			var dotpos=username.lastIndexOf(".");
			if (atpos<1 || dotpos<atpos+2 || dotpos+2>=username.length)
			{
				document.getElementById('usernameStatus').innerHTML = "not an email address";
				validated = false;
			}
			else
				document.getElementById('usernameStatus').innerHTML = "";
		}
			
		
		if(validated)
			document.getElementById('usernameSubmit').removeAttribute("disabled");
		else
			document.getElementById('usernameSubmit').setAttribute("disabled","disabled");
	}
	
	function validatePassword()
	{
		var invalid = " "; // Invalid character is a space
		var minLength = 5; // Minimum length
		var validated = true;
		
		var passOne = document.getElementById('password').value;
		var passTwo = document.getElementById('verifyPassword').value;
		
		if(passOne.length < minLength)
		{
			document.getElementById('passwordStatus').innerHTML = "too short";
			validated = false;
		}
		else if (passOne.indexOf(invalid) > -1)
		{
			document.getElementById('passwordStatus').innerHTML = "spaces not allowed";
			validated = false;
		}	
		else if (passOne != passTwo)
		{
			document.getElementById('passwordStatus').innerHTML = "don't match";
			validated = false;
		}	
		else
		{
			document.getElementById('passwordStatus').innerHTML = "";
		}	
		
		
		if(validated)
		{
			document.getElementById('passwordSubmit').removeAttribute("disabled");
		}
		else
			document.getElementById('passwordSubmit').setAttribute("disabled","disabled");
	}
	
	function saveFirstLastNames()
	{
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
		var firstName = document.getElementById('firstName').value;
		var lastName = document.getElementById('lastName').value;
		
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
                        //update values and UI	
                        var valueBox = document.getElementById('firstLastNameSettingValueBox');
                        document.getElementById('firstLastNameEditLink').innerHTML = "<b>UPDATED</b>";
                        document.getElementById('nameSubmit').setAttribute("disabled","disabled");
                        document.getElementById('firstLastNameValues').innerHTML = TDOEncodeEntities(firstName + " " + lastName);
                        
                        currentFirstNameValue  = firstName;
                        currentLastNameValue = lastName;
                        
                        document.getElementById('firstName').value = firstName;
                        document.getElementById('lastName').value = lastName;
                        
                        //hide details & show value
                        document.getElementById('firstLastNameSettingDetailsBox').style.display = "none";
                        valueBox.parentNode.style.display = "block";
                        
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("firstLastNameEditLink").innerHTML = "Edit"',1000);
                        setTimeout('document.getElementById("firstLastNameSettingValueBox").style.background ="white"', 1000);
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
                            document.getElementById('firstNameStatus').innerHTML = "(not saved)";
                            document.getElementById('lastNameStatus').innerHTML = "(not saved)";
                            alert("Invalidusername.\nPlease re-enter your user name using a valid email address.");
                        }
                    }
                }
                catch(e)
                {
                    alert("unknown response");
                }
			}
		}
		
		var queryString = "?method=updateUser&firstname=" + firstName + "&lastname="+lastName;
		ajaxRequest.open("GET", "." + queryString, true);
		ajaxRequest.send(null); 
	}
	
	function saveUsername()
	{
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
		var username = document.getElementById('username').value;
		
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
                        //update values and UI	
                        var valueBox = document.getElementById('usernameSettingValueBox');
                        document.getElementById('usernameEditLink').innerHTML = "<b>UPDATED</b>";
                        document.getElementById('usernameSubmit').setAttribute("disabled","disabled");
                        document.getElementById('usernameValue').innerHTML = TDOEncodeEntities(username);
                        document.getElementById('username').value = username;
                        
                        currentUsernameValue = username;
                        
                        //hide details & show value
                        document.getElementById('usernameSettingDetailsBox').style.display = "none";
                        valueBox.parentNode.style.display = "block";
                        
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("usernameEditLink").innerHTML = "Edit"',1000);
                        setTimeout('document.getElementById("usernameSettingValueBox").style.background ="white"', 1000);	
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
                            document.getElementById('usernameStatus').innerHTML = "(not saved)";
                            alert("Invalidusername.\nPlease re-enter your user name using a valid email address.");
                        }
                    }
                }
                catch(e)
                {
                    alert("unknown response");
                }
			}
		}
		
		var queryString = "?method=updateUser&username=" +username;
		ajaxRequest.open("GET", "." + queryString, true);
		ajaxRequest.send(null); 
		
	}
	
	function savePassword()
	{
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
	
		var passOne = document.getElementById('password').value;
		
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
                        //update values and UI	
                        var valueBox = document.getElementById('passwordSettingValueBox');
                        document.getElementById('passwordEditLink').innerHTML = "<b>UPDATED</b>";
                        document.getElementById('passwordSubmit').setAttribute("disabled","disabled");
                        //document.getElementById('passwordValue').innerHTML = username;
                        
                        //hide details & show value
                        document.getElementById('passwordSettingDetailsBox').style.display = "none";
                        valueBox.parentNode.style.display = "block";
                        
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("passwordEditLink").innerHTML = "Edit"',1000);
                        setTimeout('document.getElementById("passwordSettingValueBox").style.background ="white"', 1000);
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
                            document.getElementById('passwordStatus').innerHTML = "(not saved)";
                            alert("Invalid username.\nPlease re-enter your user name using a valid email address.");
                        }
                    }
                }
                catch(e)
                {
                    alert("unknown response");
                }
			}
		}
			
		var queryString = "?method=updateUser&password=" + passOne;
		ajaxRequest.open("GET", "." + queryString, true);
		ajaxRequest.send(null); 
	}

	function saveTimezone()
	{
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
        var selector = document.getElementById('timezoneSelect');
		var timezone = selector.options[selector.selectedIndex].value;

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
                        //update values and UI	
                        var valueBox = document.getElementById('timezoneSettingValueBox');
                        document.getElementById('timezoneEditLink').innerHTML = "<b>UPDATED</b>";
                        document.getElementById('timezoneValue').innerHTML = timezone;
                        
                        currentTimezoneValue = timezone;
                        
                        //hide details & show value
                        document.getElementById('timezoneSettingDetailsBox').style.display = "none";
                        valueBox.parentNode.style.display = "block";
                        
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("timezoneEditLink").innerHTML = "Edit"',1000);
                        setTimeout('document.getElementById("timezoneSettingValueBox").style.background ="white"', 1000);	
                    }
                    else
                    {
                        if(response.error == "authentication")
                            history.go(0);
                        else
                        {
                            if(response.error)
                                alert(error);
                            else 
                                alert("Unable to save timezone change");
                        }
                    }
                }
                catch(e)
                {   
                    alert("unknown response from server");
                }
			}
		}
		var params= "method=setUserTimezone&timezone_id=" + timezone;
		ajaxRequest.open("POST", ".", true);
	    
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params); 	
	}
*/

    /*
function toggleTagFilterSetting(setting)
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
                    if(response.success)
                    {
                        
                        //update values and UI	
                        var valueBox = document.getElementById('tagFilterSettingValueBox');
                        //hide details & show value
                        document.getElementById('tagFilterUpdateStatus').innerHTML = "<b>UPDATED&nbsp;&nbsp;</b>";
                        valueBox.parentNode.style.display = "block";
                        
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("tagFilterSettingValueBox").style.background ="white"', 1000);
                        setTimeout('document.getElementById("tagFilterUpdateStatus").innerHTML = ""', 1000);
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
                            //if it didn't save, revert the checkbox
                            if(setting)
                                document.getElementById('tagFilterRadioOr').checked = true;
                            else
                                document.getElementById('tagFilterRadioAnd').checked = true;
                            alert("Unable to save setting");
                        }
                    }
                }
                catch(e)
                {
                    
                    if(setting)
                        document.getElementById('tagFilterRadioOr').checked = true;
                    else
                        document.getElementById('tagFilterRadioAnd').checked = true;
                    alert("Unknown response from server");
                }
			}
		}
		
		var params = "method=changeTagFilterSetting&setting=" + setting;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params);    

    }
*/

    /*
function toggleFocusShowUndueTasksSetting()
    {
            var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var checkbox = document.getElementById("focusShowUndueTasksSettingCheckbox");
        var setting = 0;
        if(checkbox.checked)
            setting = 1;

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
                        
                        //update values and UI	
                        var valueBox = document.getElementById('focusShowUndueTasksSettingValueBox');
                        //hide details & show value
                        document.getElementById('focusShowUndueTasksUpdateStatus').innerHTML = "<b>UPDATED&nbsp;&nbsp;</b>";
                        valueBox.parentNode.style.display = "block";
                        
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("focusShowUndueTasksSettingValueBox").style.background ="white"', 1000);
                        setTimeout('document.getElementById("focusShowUndueTasksUpdateStatus").innerHTML = ""', 1000);
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
                            //if it didn't save, revert the checkbox
                            checkbox.checked = !checkbox.checked;
                            alert("Unable to save setting");
                        }
                    }
                }
                catch(e)
                {
                    checkbox.checked = !checkbox.checked;
                    alert("Unknown response from server");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_show_undue_tasks=" + setting;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }

    function toggleFocusShowStarredTasksSetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var checkbox = document.getElementById("focusShowStarredTasksSettingCheckbox");
        var setting = 0;
        if(checkbox.checked)
            setting = 1;

		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try 
                {
                    var response = JSON.parse(ajaxRequest.responseText);
//                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                        //update values and UI	
                        var valueBox = document.getElementById('focusShowStarredTasksSettingValueBox');
                        //hide details & show value
                        document.getElementById('focusShowStarredTasksUpdateStatus').innerHTML = "<b>UPDATED&nbsp;&nbsp;</b>";
                        valueBox.parentNode.style.display = "block";
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("focusShowStarredTasksSettingValueBox").style.background ="white"', 1000);
                        setTimeout('document.getElementById("focusShowStarredTasksUpdateStatus").innerHTML = ""', 1000);
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
                            //if it didn't save, revert the checkbox
                            checkbox.checked = !checkbox.checked;
                            alert("Unable to save setting");
                        }
                    }
                }
                catch(e)
                {
                    checkbox.checked = !checkbox.checked;
                    alert("Unknown response from server");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_show_starred_tasks=" + setting;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }
    
    function toggleFocusCompletedTasksSetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var selectBox = document.getElementById("focusCompletedTasksSelectBox");
        var option = selectBox.options[selectBox.selectedIndex].value;
    
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try 
                {
                    var response = JSON.parse(ajaxRequest.responseText);
//                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                        //update values and UI
                        var valueBox = document.getElementById('focusCompletedTasksSettingValueBox');
                        //hide details & show value
                        document.getElementById('focusCompletedTasksSettingUpdateStatus').innerHTML = "<b>UPDATED&nbsp;&nbsp;</b>";
                        valueBox.parentNode.style.display = "block";
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("focusCompletedTasksSettingValueBox").style.background ="white"', 1000);
                        setTimeout('document.getElementById("focusCompletedTasksSettingUpdateStatus").innerHTML = ""', 1000);
                        
                        selectedCompletedTasksOption = option;
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
                            //if it didn't save, revert the checkbox
                             selectBox.selectedIndex = selectedCompletedTasksOption;
                            alert("Unable to save setting");
                        }
                    }
                }
                catch(e)
                {
                    selectBox.selectedIndex = selectedCompletedTasksOption;
                    alert("Unknown response from server");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_show_completed_date=" + option;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }
    
    
	function toggleFocusHideTaskDateSetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var selectBox = document.getElementById("focusHideDueAfterSelectBox");
        var option = selectBox.options[selectBox.selectedIndex].value;
    
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try 
                {
                    var response = JSON.parse(ajaxRequest.responseText);
//                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                        //update values and UI
                        var valueBox = document.getElementById('focusHideTasksDateSettingValueBox');
                        //hide details & show value
                        document.getElementById('focusHideTasksDateSettingUpdateStatus').innerHTML = "<b>UPDATED&nbsp;&nbsp;</b>";
                        valueBox.parentNode.style.display = "block";
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("focusHideTasksDateSettingValueBox").style.background ="white"', 1000);
                        setTimeout('document.getElementById("focusHideTasksDateSettingUpdateStatus").innerHTML = ""', 1000);
                        
                        selectedDueAfterOption = option;
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
                            //if it didn't save, revert the checkbox
                             selectBox.selectedIndex = selectedDueAfterOption;
                            alert("Unable to save setting");
                        }
                    }
                }
                catch(e)
                {
                    selectBox.selectedIndex = selectedDueAfterOption;
                    alert("Unknown response from server");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_hide_task_date=" + option;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }
    
    
    function toggleFocusHidePrioritySetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
            
        var selectBox = document.getElementById("focusHidePrioritySelectBox");
        var index = selectBox.selectedIndex;
        var option = selectBox.options[index].value;
    
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try 
                {
                    var response = JSON.parse(ajaxRequest.responseText);
//                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                        //update values and UI
                        var valueBox = document.getElementById('focusHideTasksPrioritySettingValueBox');
                        //hide details & show value
                        document.getElementById('focusHideTasksPrioritySettingUpdateStatus').innerHTML = "<b>UPDATED&nbsp;&nbsp;</b>";
                        valueBox.parentNode.style.display = "block";
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("focusHideTasksPrioritySettingValueBox").style.background ="white"', 1000);
                        setTimeout('document.getElementById("focusHideTasksPrioritySettingUpdateStatus").innerHTML = ""', 1000);
                        
                        selectedPriorityIndex = index;
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
                            //if it didn't save, revert the checkbox
                             selectBox.selectedIndex = selectedPriorityIndex;
                            alert("Unable to save setting");
                        }
                    }
                }
                catch(e)
                {
                    selectBox.selectedIndex = selectedPriorityIndex;
                    alert("Unknown response from server");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_hide_task_priority=" + option;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params); 
    }
    
    function showFocusListFilterBox(el)
    {
        var listPicker = document.getElementById('focusListFilterPicker');
        
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
                                    var listName = listsJSON[i].name;
                                    var listId = listsJSON[i].id;
                                    var selectedAttribute = ''
                                    
                                    if (focusListFilterString.indexOf(listId) == -1)
                                        selectedAttribute = ' checked="true"';
                                    listPickerHTML += '<tr>';
                                    listPickerHTML += '<td><input class="focusListFilterOptions" type="checkbox" id="list_option_' + listId + '" ' + 'value="' + listId + '" ' + selectedAttribute + '/></td>';
                                    listPickerHTML += '<td><label for="list_option_' + listId + '">'+ listName + '</label></td>';
                                    listPickerHTML += '</tr>';
                                }
                                
                                listPicker.innerHTML = listPickerHTML;
                                displaySettingDetails("focusListFilterSettingsDetailBox", el);
                            }
                        }
                        else
                            displayGlobalErrorMessage("Failed to retrieve lists for list picker: " + ajaxRequest.responseText);
                        
                    }     
                }
                catch(e){}
             }
        }
    
        var params = "method=getControlContent&type=list";
        
        ajaxRequest.open("POST", ".", true);
        
        //Send the proper header information along with the request
        ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        ajaxRequest.send(params);	

    }
    
    function getFocusListFilterStringSummary()
    {
        var filterOptions = document.getElementsByClassName("focusListFilterOptions");
        
        var filterCount = 0;
        for(var i = 0; i < filterOptions.length; i++)
        {
            var filterOption = filterOptions[i];
            if(!filterOption.checked)
            {
                filterCount++;
            }
        }
        
        if(filterCount == 0)
            return "Show All";
            
        else
            return "" + filterCount + " Hidden";
    }
    
    function getFocusListFilterString()
    {
        var filterOptions = document.getElementsByClassName("focusListFilterOptions");
        
        var filterString = "";
        for(var i = 0; i < filterOptions.length; i++)
        {
            var filterOption = filterOptions[i];
            if(!filterOption.checked)
            {
                if(filterString.length > 0)
                    filterString += ",";
                    
                filterString += filterOption.value;
            }
        }
        
        if(filterString.length == 0)
            return "none";
            
        else
            return filterString;
    }
    
    function saveFocusListFilterSettings()
    {
        var newListFilterString = getFocusListFilterString();
        var newListFilterSummary = getFocusListFilterStringSummary();
        
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
//                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                        //update values and UI
                        var valueBox = document.getElementById('focusListFilterSettingsValueBox');
                        //hide details & show value
                        document.getElementById('focusListFilterEditLink').innerHTML = "<b>UPDATED</b>";
                        document.getElementById('focusListFilterStringSummary').innerHTML = newListFilterSummary;
                        focusListFilterString = newListFilterString;
                        
                        hideSettingDetails('focusListFilterSettingsValueBox', 'focusListFilterSettingsDetailBox');
                        //transition into normal UI
                        valueBox.style.background ="#C1EEB6";
                        setTimeout('document.getElementById("focusListFilterSettingsValueBox").style.background ="white"', 1000);
                        setTimeout('document.getElementById("focusListFilterEditLink").innerHTML = "Edit"', 1000);
                        
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
                            //if it didn't save, revert the checkbox
                             cancelFocusListFilterUpdate('focusListFilterSettingsValueBox', 'focusListFilterSettingsDetailBox');
                            alert("Unable to save setting");
                        }
                    }
                }
                catch(e)
                {
                    cancelFocusListFilterUpdate('focusListFilterSettingsValueBox', 'focusListFilterSettingsDetailBox');
                    alert("Unknown response from server");
                }
			}
		}
		
		var params = "method=updateUserSettings&focus_list_filter_string=" + newListFilterString;
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		
		ajaxRequest.send(params);         
        
    }
*/
    
  /*
  
	function unlinkFacebook()
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
	                    history.go(0);
	                }
	                else
	                {
	                    if(response.error)
	                    {
                            if(response.error == "authentication")
                                history.go(0);
                            else
                                alert(error);
	                    }
	                    else
	                        alert("Unknown Failure");
	                }
	            }
	            catch(e)
	            {
	                alert("Unknown Response");
	            }
			}
		}
	
		var params = "method=unlinkFacebook";
		ajaxRequest.open("POST", ".", true);
	    
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params); 
		
	}
*/
	
	
//	var offset = 0;
//	var compOffset = 10;
//	var limit = 10;
//	
//	function getMoreChangeLog()
//	{
//		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
//		if(!ajaxRequest)
//			return false;
//		
//		var userId = document.getElementById('userID').value;
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
//                        //make the user log in again
//                        history.go(0);
//                        return;
//                    }
//                }
//                catch(e)
//                {
//                }
//                	               
//				if(ajaxRequest.responseText != "")
//				{
//					innerHTMLText = document.getElementById('changelog_container_ul').innerHTML + ajaxRequest.responseText;
//					document.getElementById('changelog_container_ul').innerHTML = innerHTMLText;
//					
//					offset = offset+limit;
//				}
//				else
//				{
//					alert("Unable to fetch more change log");
//				}
//			}
//		}
//		
//		var params = "method=getPagedChangeLog&userid=" + userId + "&offset=" + offset + "&limit=" + limit;
//		ajaxRequest.open("POST", ".", true);
//		
//		//Send the proper header information along with the request
//		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
//		
//		
//		ajaxRequest.send(params);
//	}
//	
//	
//	//load the first batch of event logs
//	getMoreChangeLog();
</script>
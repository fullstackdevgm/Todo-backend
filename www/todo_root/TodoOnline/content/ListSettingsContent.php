<link href="<?php echo TP_CSS_PATH_LIST_SETTINGS; ?>" type="text/css" rel="stylesheet">

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
	
	/*
$goBack = new PBButton;
	$goBack->setLabel('< Dashboard');
	$goBack->setUrl('?list='.$selectedlistid);	
	$toolbarButtons = array($goBack);
	include_once('TodoOnline/content/ContentToolbarButtons.php');
*/
?>

</div>
<?php
	
    $emailVerified = false;
	if($session->isLoggedIn() && isset($_COOKIE['TodoOnlineListId']))
	{
        $userid = $session->getUserId();
        $user = TDOUser::getUserForUserId($userid);
        if($user)
        {
            $emailVerified = $user->emailVerified();
        }
        
		$listid = $_COOKIE['TodoOnlineListId'];
        echo "<input type=\"hidden\" id=\"list_id\" value=\"$listid\">";
        $list = TDOList::getListForListid($listid);
        $listSettings = TDOListSettings::getListSettingsForUser($listid, $userid);
		if($list && $listSettings)
        {
            $listName = $list->name();
            $listDescription = $list->description();
            $caldavColor = $listSettings->color();
            $filterSync = $listSettings->filterSyncedTasks();
            $notificationSettings = $listSettings->changeNotificationSettings();
        }
        else
        {
            echo _('Missing list') . "<br>";
            return;
        }
	}
    include_once('TodoOnline/ajax_config.html');
?>


<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>" ></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>"></script>

<script type="text/javascript">

var currentListNameValue = "<?php echo htmlspecialchars($listName); ?>";
var currentDescriptionValue = "<?php echo htmlspecialchars($listDescription); ?>";
var initialFilterSyncVal = "<?php echo $filterSync; ?>";

var currentNotificationSettings = new Array();

<?php
    foreach($notificationSettings as $notificationType=>$value)
    {
        echo 'currentNotificationSettings.push({"key":"'.$notificationType.'", "value":'.$value.', "displayname":"'._(TDOListSettings::displayNameForNotificationType($notificationType)).'"}); ';
    }
   
?>


    
function toggleTaskSyncSetting()
{

	var doc = document;
	
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;
	var listid = doc.getElementById('list_id').value;
    var shouldFilter = doc.getElementById('taskSyncCheckbox').checked;
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
                    var valueBox = doc.getElementById('taskSyncSettingValueBox');
                    //hide details & show value
                    doc.getElementById('taskSyncUpdateStatus').innerHTML = "<b><?php _e('UPDATED'); ?>&nbsp;&nbsp;</b>";
                    valueBox.parentNode.style.display = "block";
                    
                    //transition into normal UI
                    valueBox.style.background ="#C1EEB6";
                    setTimeout('doc.getElementById("taskSyncSettingValueBox").style.background ="white"', 1000);
                    setTimeout('doc.getElementById("taskSyncUpdateStatus").innerHTML = ""', 1000);	
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
                        doc.getElementById('taskSyncCheckbox').checked = !shouldFilter;
                        if(response.error)
                            alert(response.error);
                        else
                            alert("<?php _e('Unable to save setting'); ?>");
                    }
                }
            }
            catch(e)
            {
                
                doc.getElementById('taskSyncCheckbox').checked = !shouldFilter;
                alert("<?php _e('Unknown response from server'); ?>");
            }
		}
	}
	
	var params = "method=updateListSettings&listid=" + listid + "&filter_sync=" + shouldFilter;
	ajaxRequest.open("POST", "." , true);
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);    

};

function saveNotificationSettings()
{
	var doc = document;
	
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;
	var listid = doc.getElementById('list_id').value;

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
                    setCurrentNotificationSettingsToCheckboxes();
                    setNotificationSettingStringToCurrentSettings();
                    
                    showSuccessSettingUpdate('notifications_setting', 'notifications_edit', true);
                    doc.getElementById('notifications_config').removeAttribute('style');

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
                        setCheckboxesToCurrentNotificationSettings();
                        alert("<?php _e('Unable to save setting'); ?>");
                    }
                }
            }
            catch(e)
            {
                setCheckboxesToCurrentNotificationSettings();
                alert("<?php _e('Unknown response from server'); ?> " + e);
            }
		}
	}
	
	var params = "method=updateListSettings";
//    if(doc.getElementById("applyNotificationSettingsBox").checked == false)
//    {
        params = params.concat("&listid=" + listid);
//    }
    
    for(var i=0; i < currentNotificationSettings.length; i++)
    {
        var setting = currentNotificationSettings[i];
        params = params.concat("&notifications[" + setting.key + "]=");
        if(doc.getElementById(setting.key).checked)
            params = params.concat("1");
        else
            params = params.concat("0");
    }

	ajaxRequest.open("POST", "." , true);
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);    
    
};




function setNotificationSettingStringToCurrentSettings()
{   
    var html = "";
    for(var i=0; i < currentNotificationSettings.length; i++)
    {
        var setting = currentNotificationSettings[i];
        if(setting.value == 1)
        {
            if(html.length > 0)
                html = html.concat(", ");
            html = html.concat(setting.displayname);
        }
    }
    if(html.length == 0)
        html = "None";
    
    document.getElementById("notificationStringValue").innerHTML = html;        
};


function setCheckboxesToCurrentNotificationSettings()
{
	var doc = document;
    for(var i=0; i < currentNotificationSettings.length; i++)
    {
        var setting = currentNotificationSettings[i];
        if(setting.value == 1)
        {
             doc.getElementById(setting.key).checked = true;
        }
        else
        {
             doc.getElementById(setting.key).checked = false;
        }
    }	
};


</script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_LIST_FUNCTIONS; ?>"></script>

<h3><?php _e('List Settings'); ?></h3>
	
<div class="setting_options_container">
    
		<!--listName-->
		<div id="list_name_setting" class="setting">
			<input id="origListName" type="hidden" value="<?php echo htmlspecialchars($listName); ?>" />
			<span class="setting_name"><?php _e('Name'); ?></span>
			<span class="setting_details" id="listNameLabel"><?php echo htmlspecialchars($listName); ?></span>

			<span id="list_name_edit" class="setting_edit" onclick="displaySettingDetails('list_name_config', 'list_name_edit')"><?php _e('Edit'); ?></span>
		
			<div id="list_name_config" class="setting_details setting_config">
				<div class="labeled_control">
					<label><?php _e('List Name'); ?></label>
					<input type="text" onkeyup="validateListName()" name="Name" id="listName" autocomplete="off" value="<?php echo htmlspecialchars($listName); ?>"/>
					<span id="list_name_status" class="setting_status"></span>
				</div>
				<div class="save_cancel_button_wrap">
					<div class="button disabled" id="listNameSubmit" ><?php _e('Save'); ?></div>
	 				<div class="button" onclick="cancelListNameUpdate()"><?php _e('Cancel'); ?></div>
				</div>
			</div>
		</div>
        <!--description-->
		<div id="description_setting" class="setting">
			<textarea id="origDescription" class="no_display" tab="-1"><?php echo htmlspecialchars($listDescription); ?></textarea>
			<span class="setting_name"><?php _e('Description'); ?></span>
			<span class="setting_details" id="descriptionValue"><?php echo htmlspecialchars($listDescription); ?></span>
			<span id="description_edit" class="setting_edit" onclick="displaySettingDetails('description_config', 'description_edit')"><?php _e('Edit'); ?></span>
		
			<div id="description_config" class="setting_details setting_config">
				<div class="labeled_control">
					<label></label>
					<textarea rows="3" cols="40" onkeyup="validateDescription()" name="Description" id="description" autocomplete="off"><?php echo htmlspecialchars($listDescription); ?></textarea>
					<span id="description_status" class="setting_status"></span>
				</div>
				<div class="save_cancel_button_wrap"> 	 	
                     <div class="button disabled" id="descriptionSubmit" ><?php _e('Save'); ?></div>
                     <div class="button" onclick="cancelDescriptionUpdate()"><?php _e('Cancel'); ?></div>
				</div>
			</div>		
		</div>
		<!--color-->
		<div id="list_color_setting" class="setting">
			<span class="setting_name"><?php _e('Color'); ?></span>
			<span class="setting_details" id="colorValue"><div id="selectedListColor" class="listColor"></div></span>
			<span id="list_color_edit" class="setting_edit" onclick="displaySettingDetails('list_color_config', 'list_color_edit')"><?php _e('Edit'); ?></span>
		
			<div id="list_color_config" class="setting_details setting_config">
				<center>
					<div id="colorPickerWrapper">
						<div class="colorPickerRow">
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,0,0);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(255,210,125);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(214,249,127);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(120,251,214);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(119,215,254);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(213,136,253);"></div>
						</div>					   								    
						<div class="colorPickerRow">							    
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(33,33,33);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(255,125,122);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(255,250,127);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(121,248,126);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(129,253,255);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(120,134,253);"></div>
						</div>					   								    
						<div class="colorPickerRow">							    
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(66,66,66);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(255,145,27);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(146,247,42);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,248,150);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,154,253);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(145,71,252);"></div>
						</div>					   								    
						<div class="colorPickerRow">							    
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(95,94,95);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(255,35,4);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(255,248,45);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,246,40);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,253,255);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,68,252);"></div>
						</div>					   								    
						<div class="colorPickerRow">							    
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(121,121,121);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(147,81,10);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(72,133,11);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,142,83);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,86,145);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(81,36,145);"></div>
						</div>					   								    
						<div class="colorPickerRow">							    
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(145,145,145);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(147,15,4);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(147,142,21);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,141,18);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,145,146);"></div>
							<div class="listColor" onclick="updateListColor(event)" style="background:rgb(0,35,145);"></div>
						</div>
					</div>
				</center>
			</div>
		</div>	
        <!--image-->
		<!--
		<a class="setting_option_link" href="javascript:void(0)" onclick="displaySettingDetails('imageSettingDetailsBox', this)">
			<li id="imageSettingValueBox" class="setting_option">
				<span class="setting_label">Image</span>
				<span class="setting_value" id="imageHTMLValue"></span>
				<script>
                    if(imageSrc.length > 0)
                        document.getElementById('imageHTMLValue').innerHTML = imageSrc;
                    else
                        document.getElementById('imageHTMLValue').innerHTML = 'None';
				</script>
				<span id="imageEditLink" class="setting_edit_link">Edit</span>
			</li>
		</a>	
		<div id="imageSettingDetailsBox" class="setting_option_details">
        <table cellspacing="0" cellpadding="0">
        <tr><td><div id="img_prev"></div></td><td><div id="img_rmv"></div></td></tr>
        </table>
        
       <table cellspacing="0" cellpadding="0">
            <form action="?method=uploadListImage" id="imageForm" method="post" enctype="multipart/form-data">
            <tr><td><input type="file" name="imageFile" id="imageFile" onchange="validateImageSelection()"  accept="image/jpeg, image/png, image/gif" /></td>
            <input type="hidden" name="listid" id="img_bid">
            <script>document.getElementById("img_bid").value=document.getElementById("list_id").value</script>
            <td></td><td></td><td></td><td align="right"><input type="button" onclick="submitImageUpload()" id="imageSubmit" value="Save" disabled="disabled"/></form>
            <input  type="button" onclick="cancelImageUpdate('imageSettingValueBox', 'imageSettingDetailsBox')" value="Cancel" /></td></tr>
            </form>
        </table> 
        
		</div>
        -->
</div>

			<script>
				//document.getElementById('listNameValue').innerHTML = currentListNameValue;
			</script>
			<script>
					//document.getElementById('listName').value = TDODeencodeEntities(currentListNameValue);
				</script>
			
<br/>
<h3><?php _e('Personal Settings'); ?></h3>
<div class="setting_options_container">
    
		<!--sync task assignment

			<li id="taskSyncSettingValueBox" class="setting_option">
                <script> document.getElementById("taskSyncSettingValueBox").style.background ="white"; </script>
				<span class="setting_label_wide">Sync Only My Assigned Tasks</span>
				<span class="setting_value_narrow"></span>
                <span class="setting_checkbox"><input type="checkbox" id="taskSyncCheckbox" onchange="toggleTaskSyncSetting()"></span>
                <script>
                    if(initialFilterSyncVal == 1)
                        document.getElementById("taskSyncCheckbox").checked = true;
                    else
                        document.getElementById("taskSyncCheckbox").checked = false;
                </script>
                <span id="taskSyncUpdateStatus" style="float: right"></span>
			</li>
        -->
            
        <!--notification settings-->
		<div id="notifications_setting" class="setting">
			<span class="setting_name"><?php _e('Email Notifications'); ?></span>
            
            <?php 
            
            if($emailVerified)
            {
                echo '<span class="setting_details" id="notificationStringValue"></span>';
                
                echo '<span id="notifications_edit" class="setting_edit" onclick="displaySettingDetails(\'notifications_config\', \'notifications_edit\')">' . _('Edit') . '</span>';
            
                echo '<div id="notifications_config" class="setting_details setting_config">';
                echo '<span>' . _('Notify me when there are changes to:') . '</span><br><br>';
 
                        foreach($notificationSettings as $notificationType=>$value)
                        {
                            echo '<label for="'.$notificationType.'">';
                            echo _(TDOListSettings::displayNameForNotificationType($notificationType));
                            echo ' <input type="checkbox" id="'.$notificationType.'" name="notifications">';
                            echo '</label>';
                        }
                        
                        echo '<script type="text/javascript">setCheckboxesToCurrentNotificationSettings();</script>';
                    echo '<div class="save_cancel_button_wrap">';
                    echo '  <div class="button" id="notificationSettingsSubmit" onclick="saveNotificationSettings()">' . _('Save') . '</div>';
                    echo '  <div class="button" onclick="cancelNotificationsUpdate()">' . _('Cancel') . '</div>';
                    echo '</div>';
                echo '</div>';
            }
            else
            {
                echo '<span class="setting_details">';
                echo _('This feature requires verification of your email address');
                echo '</span>';
                
                echo '<div class="button" onclick="verifyUserEmail()">' . _('Verify') . '</div>';
            }
            
            ?>
            
		</div>
</div>	

<script>
	setupListSettings();
    setNotificationSettingStringToCurrentSettings();
</script>























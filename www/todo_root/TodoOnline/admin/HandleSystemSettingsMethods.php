<?php

include_once('TodoOnline/base_sdk.php');


    if($method == "updateSystemSetting")
    {
		
		if (!isset($_POST['name']))
		{
			error_log("HandleSystemSettingsMethods::updateSystemSetting called and missing a required parameter: name");
			echo '{"success":false}';
			return;
		}
		
		if (!isset($_POST['value']))
		{
			error_log("HandleSystemSettingsMethods::updateSystemSetting called and missing a required parameter: value");
			echo '{"success":false}';
			return;
		}
		
		$name = $_POST['name'];
		$value = $_POST['value'];
		
		if (!TDOUtil::setStringSystemSetting($name, $value))
		{
			error_log("HandleSystemSettingsMethods::updateSystemSetting failed to set the system setting.");
			echo '{"success":false}';
			return;
		}
		
		echo '{"success":true}';
		return;
    }
    

?>

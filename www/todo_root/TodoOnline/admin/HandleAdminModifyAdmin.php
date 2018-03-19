<?php

include_once('TodoOnline/base_sdk.php');
include_once('Facebook/config.php');
include_once('Facebook/facebook.php');

include_once('TodoOnline/php/SessionHandler.php');
	
if($session->isLoggedIn())
	$userAdminLevel = TDOUser::adminLevel($session->getUserId());
    
    
if(isset($_SERVER['HTTP_REFERER']))
{
	$referrer = $_SERVER['HTTP_REFERER'];
}
else
{
	$referrer = ".";
}
	
    if(isset($_POST["userid"] ))
    {
        $uid = $_POST["userid"];
        
        if(isset($_POST["level"] ))
        {
			$newLevel = $_POST["level"];
			$result = TDOUser::setAdminLevel($uid, $newLevel);
        }
    } 

    if($result == true)
        header("Location:".$referrer);
    else
        echo "Failed to change user privileges<br>";
    
?>

<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/php/SessionHandler.php');

if(TDOSession::getInstance()->isLoggedIn())
{
	if(TDOSession::getInstance()->setDefaultTimezone() == false)
	{
		//guess the user's time zone from their browser time
		if(!isset($_GET['method']) && !isset($_POST['method']))
		{
				include_once('TodoOnline/content/InferUserTimezone.php');
				exit();
		}
	}
}

include_once('TodoOnline/php/APIMethodHandler.php');
?>

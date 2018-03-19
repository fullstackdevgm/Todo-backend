<?php
	include_once('TodoOnline/base_sdk.php');
    
	// admin does not support Facebook

	TDOSession::setIsAdmin();
	
	include_once('TodoOnline/php/SessionHandler.php');
	
	if(isset($_GET['method']) || isset($_POST['method']))
	{
		include_once('TodoOnline/admin/AdminMethodHandler.php');
	}
	else
	{
		include_once('TodoOnline/admin/AdminPageLoader.php');
	}

?>



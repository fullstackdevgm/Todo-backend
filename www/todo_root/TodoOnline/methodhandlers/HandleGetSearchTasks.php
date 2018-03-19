<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');
	include_once('TodoOnline/content/TaskContentFunctions.php');	
	
    if(!isset($_POST['searchstring']))
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('getSearchTasks called missing parameter: searchstring'),
        ));
        return;
    }
    
    $searchString = $_POST['searchstring'];
    searchContentForSearchString($session->getUserId(), $searchString)
	
?>
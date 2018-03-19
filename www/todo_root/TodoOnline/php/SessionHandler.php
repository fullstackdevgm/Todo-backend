<?php

include_once('TodoOnline/base_sdk.php');

// Check for a POST/PUT from a REST client and if so, convert the JSON POST
// data to PHP $_POST variable so our existing API can work with minimal
// changes.
TDORESTAdapter::adaptJSONPostIfNeeded();

include_once('Facebook/config.php');
include_once('Facebook/facebook.php');

//Create facebook application instance.
$facebook = new Facebook(array(
							   'appId'  => $fb_app_id,
							   'secret' => $fb_secret,
							   'cookie' => true,
							   ));
	$session = TDOSession::getInstance($facebook);

?>

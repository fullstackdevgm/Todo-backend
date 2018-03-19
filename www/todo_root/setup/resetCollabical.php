<?php

// include files
include_once('AWS/sdk.class.php');
include_once('Collabical/config.php');


// Instantiate
$sdb = new AmazonSDB();

$sdb->delete_domain('ac_user_accounts');
$sdb->delete_domain('ac_user_sessions');
$sdb->delete_domain('ac_boards');
$sdb->delete_domain('ac_events');
$sdb->delete_domain('ac_invitations');
	
?>

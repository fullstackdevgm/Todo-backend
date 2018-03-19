<?php

include_once('TodoOnline/base_sdk.php');
include_once('TDONotificationController.php');

$q = new TDONotificationController('TEST');
$q->queueNotifications();

?>

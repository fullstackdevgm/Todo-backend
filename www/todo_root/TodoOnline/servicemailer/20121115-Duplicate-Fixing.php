#!/usr/bin/php -q
<?php

// Include Classes
include_once('TodoOnline/base_sdk.php');

if ($argc != 3)
{
	echo "Missing parameters!\n";
	exit(1);
}

$emailAddress = $argv[1];
$firstName = $argv[2];

$subject = "Todo Cloud: Maintenance affecting your user account.";

$htmlBody = "<p>Dear $firstName,</p>\n";
$textBody = "Dear $firstName,\n\n";


$htmlBody .= "<p>Todo Cloud has identified your account as needing a one-time maintenance to improve the performance of task synchronization.</p>\n";
$textBody .= "Todo Cloud has identified your account as needing a one-time maintenance to improve the performance of task synchronization.\n\n";

$htmlBody .= "<p>We have recently fixed a problem where some of your tasks could have been duplicated multiple times on the Todo Cloud service (www.todo-cloud.com) if your iOS device or Mac experienced a synchronization error. You may or may not have noticed any duplicate tasks in your Todo app, but may have seen them when you logged in to Todo Cloud in a web browser.</p>\n";
$textBody .= "We have recently fixed a problem where some of your tasks could have been duplicated multiple times on the Todo Cloud service (www.todo-cloud.com) if your iOS device or Mac experienced a synchronization error. You may or may not have noticed any duplicate tasks in your Todo app, but may have seen them when you logged in to Todo Cloud in a web browser.\n\n";

$htmlBody .= "<p>Because the duplication of tasks is affecting the performance of the entire service for other users, your account may be temporarily unavailable during this maintenance. We anticipate the maintenance for all affected accounts may take a few days once the maintenance begins.</p>\n";
$textBody .= "Because the duplication of tasks is affecting the performance of the entire service for other users, your account may be temporarily unavailable during this maintenance. We anticipate the maintenance for all affected accounts may take a few days once the maintenance begins.\n\n";

$htmlBody .= "<p>During your account maintenance period, if you attempt to synchronize tasks with your Todo Cloud account, you will see on your iOS device and Mac that your account is being upgraded. If you try logging in to your account in Todo Cloud Web, you will see a message that your account is being maintained. If you see this message, please wait and try synchronizing later.</p>\n";
$textBody .= "During your account maintenance period, if you attempt to synchronize tasks with your Todo Cloud account, you will see on your iOS device and Mac that your account is being upgraded. If you try logging in to your account in Todo Cloud Web, you will see a message that your account is being maintained. If you see this message, please wait and try synchronizing later.\n";

$htmlBody .= "<p>You can continue using your tasks during the account maintenance using your iOS device or Mac in Todo or Todo Cloud. After the maintenance is finished your changes will be updated on your Todo Cloud account when you synchronize.</p>\n";
$textBody .= "You can continue using your tasks during the account maintenance using your iOS device or Mac in Todo or Todo Cloud. After the maintenance is finished your changes will be updated on your Todo Cloud accoun when you synchronize.\n\n";

$htmlBody .= "<p>When this maintenance has been performed, we will also update the Todo Cloud Status page on our Help Center site: http://help.appigo.com/entries/22336756-todo-pro-status</p>\n";
$textBody .= "When this maintenance has been performed, we will also update the Todo Cloud Status page on our Help Center site: http://help.appigo.com/entries/22336756-todo-pro-status\n\n";

$htmlBody .= "<p>Your experience using Todo Cloud is very important to us and we want to make sure it works well for you. If you continue experiencing any problems after your account has been maintained, please contact us at your earliest convenience here: http://help.appigo.com/anonymous_requests/new</p>\n";
$textBody .= "Your experience using Todo Cloud is very important to us and we want to make sure it works well for you. If you continue experiencing any problems after your account has been maintained, please contact us at your earliest convenience here: http://help.appigo.com/anonymous_requests/new\n\n";

$htmlBody .= "<p>Best regards,</p>\n";
$textBody .= "Best regards,\n\n";

$htmlBody .= "<p><strong>The Appigo Team</strong></p>\n";
$textBody .= "The Appigo Team\n";

$result = TDOMailer::sendHTMLAndTextEmail($emailAddress, $subject, EMAIL_FROM_NAME, EMAIL_FROM_ADDR, $htmlBody, $textBody);
	
?>

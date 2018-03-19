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

$subject = "Todo Online Update (Todo Online is now Todo Cloud)";

$htmlBody = "<p>$firstName,</p>\n";
$textBody = "$firstName,\n\n";


$htmlBody .= "<p>Thank you for using Appigo's Todo Online task management service. You are receiving this email because you previously created a Todo Online account.</p>\n";
$textBody .= "Thank you for using Appigo's Todo Online task management service. You are receiving this email because you previously created a Todo Online account.\n\n";

$htmlBody .= "<p>Last week we upgraded Todo Online to version 2.0. At the same time, we changed the name of Todo Online. It is now known as &quot;Todo Cloud.&quot; Todo Cloud includes many new features, including task list sharing, assignment of tasks, task comments, and a much faster task synchronization between devices. We're excited to provide these new features to you, and the service is working very well.</p>\n";
$textBody .= "Last week we upgraded Todo Online to version 2.0. At the same time, we changed the name of Todo Online. It is now known as \"Todo Cloud.\" Todo Cloud includes many new features, including task list sharing, assignment of tasks, task comments, and a much faster task synchronization between devices. We're excited to provide these new features to you, and the service is working very well.\n\n";

$htmlBody .= "<p>During the first two days, there was a large influx of traffic to Todo Cloud, and unfortunately, the service wasn't able to handle everything all at once. We sincerely apologize for this service interruption. We know how important your tasks are, and we've taken proactive steps to resolve the issues we saw during the launch day.</p>\n";
$textBody .= "During the first two days, there was a large influx of traffic to Todo Cloud, and unfortunately, the service wasn't able to handle everything all at once. We sincerely apologize for this service interruption. We know how important your tasks are, and we've taken proactive steps to resolve the issues we saw during the launch day.\n\n";

$htmlBody .= "<p>Todo Cloud is now available at www.todo-cloud.com and you can log in using your old username (email) and password from Todo Online. When you log in for the first time, your tasks will be upgraded automatically to work in the new system. If you upgraded during the first day and are still experiencing problems, please contact us at support@appigo.com, and we will help you find a solution.</p>\n";
$textBody .= "Todo Cloud is now available at www.todo-cloud.com and you can log in using your old username (email) and password from Todo Online. When you log in for the first time, your tasks will be upgraded automatically to work in the new system. If you upgraded during the first day and are still experiencing problems, please contact us at support@appigo.com, and we will help you find a solution.\n";

$htmlBody .= "<p>If you synchronized your tasks with Todo Online on your Mac, iPhone, iPad, or iPod touch, please upgrade to the latest versions available on the App Store and they will automatically connect with Todo Cloud. We have also provided free versions of the Todo Cloud apps available on the App Store and on the Mac App Store.</p>\n";
$textBody .= "If you synchronized your tasks with Todo Online on your Mac, iPhone, iPad, or iPod touch, please upgrade to the latest versions available on the App Store and they will automatically connect with Todo Cloud. We have also provided free versions of the Todo Cloud apps available on the App Store and on the Mac App Store.\n\n";

$htmlBody .= "<p>We are also working on a version of Todo Cloud for Android and will be announcing a beta program for that soon. Please follow us on Twitter (www.twitter.com/appigo) to learn when that becomes available.</p>\n";
$textBody .= "We are also working on a version of Todo Cloud for Android and will be announcing a beta program for that soon. Please follow us on Twitter (www.twitter.com/appigo) to learn when that becomes available.\n\n";

$htmlBody .= "<p>Should you have any questions, please take a look at our Help Center available at: http://support.appigo.com  If you have any other questions, please do not hesitate to contact us at support@appigo.com.</p>\n";
$textBody .= "Should you have any questions, please take a look at our Help Center available at: http://support.appigo.com  If you have any other questions, please do not hesitate to contact us at support@appigo.com.\n\n";

$htmlBody .= "<p>Thank you for your continued support of our apps and services,</p>\n";
$textBody .= "Thank you for your continued support of our apps and services,\n\n";

$htmlBody .= "<p><strong>The Appigo Team</strong></p>\n";
$textBody .= "The Appigo Team\n";

$result = TDOMailer::sendHTMLAndTextEmail($emailAddress, $subject, EMAIL_FROM_NAME, EMAIL_FROM_ADDR, $htmlBody, $textBody);
	
?>

<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	include_once('TodoOnline/DBConstants.php');
	
	define('AWS_REGION', 'us-east-1');
	define('AWS_ACCOUNT', '398938165940');
	define('AWS_TOPIC', 'appigo-ses-bounce-notifications');
	
	
	// General idea of code came from: http://www.async.fi/2011/05/verifying-amazon-sns-messages-with-php/
	
	
	// http://codepad.org/NGlABcAC
	function get_domain_from_url( $url, $max_node_count = 0 )
	{
		$return_value='';
		$max_node_count=(int)$max_node_count;
		$url_parts=parse_url((string)$url);
		if(is_array($url_parts)&&isset($url_parts['host'])&&strlen((string)$url_parts['host'])>0) {
			$return_value=(string)$url_parts['host'];
			if($max_node_count>0) {
				$host_parts=explode('.',$return_value);
				$return_parts=array();
				for($i=$max_node_count;$i>0;$i--) {
					$current_node=array_pop($host_parts);
					if(is_string($current_node)&&$current_node!=='') {
						$return_parts[]=$current_node;
					} else {
						break;
					}
				}
				if(count($return_parts)>0) {
					$return_value=implode('.',array_reverse($return_parts));
				} else {
					$return_value='';
				}
			}
		}
		return$return_value;
	}
	
	// http://stackoverflow.com/questions/619610/whats-the-most-efficient-test-of-whether-a-php-string-ends-with-another-string
	function endswith($string, $test)
	{
		$strlen = strlen($string);
		$testlen = strlen($test);
		if ($testlen > $strlen) return false;
		return substr_compare($string, $test, -$testlen) === 0;
	}
	
	// http://media.async.fi/media/2011/05/sns-verify.php_1.txt
	function verify_sns($message, $region, $account, $topics)
	{
		$msg = json_decode($message);
		
		// Check that region, account and topic match
		$topicarn = explode(':', $msg->TopicArn);
		if($topicarn[3] != $region || $topicarn[4] != $account || !in_array($topicarn[5], $topics)) {
			return false;
		}
		$_region = $topicarn[3]; $_account = $topicarn[4]; $_topic = $topicarn[5];
		
		// Check that the domain in message ends with '.amazonaws.com'
		if(!endswith(get_domain_from_url($msg->SigningCertURL), '.amazonaws.com')) {
			return false;
		}
		
		// Load certificate and extract public key from it
		$cert = file_get_contents($msg->SigningCertURL);
		$pubkey = openssl_get_publickey($cert);
		if(!$pubkey) {
			return false;
		}
		
		// Generate a message string for comparison in Amazon-specified format
		$text = "";
		if($msg->Type == 'Notification') {
			$text .= "Message\n";
			$text .= $msg->Message . "\n";
			$text .= "MessageId\n";
			$text .= $msg->MessageId . "\n";
//			if (!empty($msg->Subject))
//			{
//				$text .= "Subject\n";
//				$text .= $msg->Subject . "\n";
//			}
			$text .= "Timestamp\n";
			$text .= $msg->Timestamp . "\n";
			$text .= "TopicArn\n";
			$text .= $msg->TopicArn . "\n";
			$text .= "Type\n";
			$text .= $msg->Type . "\n";
		} elseif($msg->Type == 'SubscriptionConfirmation') {
			$text .= "Message\n";
			$text .= $msg->Message . "\n";
			$text .= "MessageId\n";
			$text .= $msg->MessageId . "\n";
			$text .= "SubscribeURL\n";
			$text .= $msg->SubscribeURL . "\n";
			$text .= "Timestamp\n";
			$text .= $msg->Timestamp . "\n";
			$text .= "Token\n";
			$text .= $msg->Token . "\n";
			$text .= "TopicArn\n";
			$text .= $msg->TopicArn . "\n";
			$text .= "Type\n";
			$text .= $msg->Type . "\n";
		} else {
			return false;
		}
		
		// Get a raw binary message signature
		$signature = base64_decode($msg->Signature);
		
		// ..and finally, verify the message
		if(openssl_verify($text, $signature, $pubkey, OPENSSL_ALGO_SHA1)) {
			return true;
		}
		
		return false;
	}
	
	// Use the following two lines to retrieve the confirmation URL needed to
	// "Subscribe" to the Amazon SNS topic. Instead of implementing something
	// here, just extract the URL and load it in a browser.
	//$postContents = file_get_contents("php://input");
	//error_log("POST: $postContents");
	
	// Extracted subscription confirmation URL
	// https://sns.us-east-1.amazonaws.com/?Action=ConfirmSubscription&TopicArn=arn:aws:sns:us-east-1:398938165940:appigo-ses-bounce-notifications&Token=2336412f37fb687f5d51e6e241d09c81df3ba3d314f6d86cfb745e0c034e7c8fd720018646fca9059822f17a71201ea0c3de18ecd8084940623be0d965bdda7e262bd2c802789358bfaa74677e0cbcfda411c72d43cf455ea1679d6a5ee5e41ead0cd43ad76368372116d02202d3f93c30421acc1c0b4a189c21f3bf5a3654e983ab6a3953d4b2c4f8d980f16f24fbd0
	
	// Response from the subscription confirmation:
	/*
		<ConfirmSubscriptionResponse>
			<ConfirmSubscriptionResult>
				<SubscriptionArn>arn:aws:sns:us-east-1:398938165940:appigo-ses-bounce-notifications:caccd413-0e7a-48fd-97db-9d1b192ec3a3</SubscriptionArn>
			</ConfirmSubscriptionResult>
			<ResponseMetadata>
				<RequestId>e74ab102-4452-5993-99e6-f80d3a702dc3</RequestId>
			</ResponseMetadata>
		</ConfirmSubscriptionResponse>
	 */
	 
	if ($_SERVER['REQUEST_METHOD'] != 'POST')
	{
		error_log('HandleEmailBounceNotificationMethods NOT called with POST, exiting.');
		exit();
	}
	
	$postData = file_get_contents('php://input');
//	error_log('POST DATA: ' . $postData);
	
	//
	// Verify that the post actually came from SNS
	//
	if (verify_sns($postData, AWS_REGION, AWS_ACCOUNT, array(AWS_TOPIC)) == false)
	{
		error_log('HandleEmailBounceNotificationMethods received data that could NOT be verified.');
		exit();
	}
	
	$msg = json_decode($postData);
	
	if ($msg->Type == 'SubscriptionConfirmation')
	{
		error_log('HandleEmailBounceNotificationMethods Subscription Confirmation URL: ' . $msg->SubscribeURL);
		$htmlMsg = '<body>' . $msg->SubscribeURL . '</body>';
		$textMsg = $msg->SubscribeURL;
		TDOMailer::sendHTMLAndTextEmail('admin@appigo.com', 'Amazon SNS Subscription Confirmation URL', 'Todo Cloud', 'no-reply@todo-cloud.com', $htmlMsg, $textMsg);
	}
	else if ($msg->Type == 'Notification')
	{
		$bounceMsg = json_decode($msg->Message);
		if ($bounceMsg->notificationType == 'Bounce')
		{
			$bouncedEmails = $bounceMsg->mail->destination;
			foreach($bouncedEmails as $bouncedEmail)
			{
				// Check to see if this is a "Permanent" or "Transient" bounce. If
				// it's a "Transient" bounce, it's likely an "Out of Office"
				// notification.
				if ($bounceMsg->bounce->bounceType == "Permanent")
				{
					// The email(s) specified here are permanently bouncing and
					// should be added to our bounce list.
					error_log('HandleEmailBounceNotificationMethods received a permanent bounce: ' . $bouncedEmail);
					TDOMailer::recordBounceEmail($bouncedEmail, BOUNCE_PERMANENT);
				}
				else if ($bounceMsg->bounce->bounceType == "Transient")
				{
					// The email(s) specified are temporarily unavailable. Not sure
					// what to do with them.
					error_log('HandleEmailBounceNotificationMethods received a transient bounce: ' . $bouncedEmail);
					TDOMailer::recordBounceEmail($bouncedEmail, BOUNCE_TRANSIENT);
				}
				else if ($bounceMsg->bounce->bounceType == "Undetermined")
				{
					// Amazon SES was unable to determine a specific bounce
					// reason.
					error_log('HandleEmailBouncedNotificationMethods received an undetermined bounce: ' . $bouncedEmail);
					TDOMailer::recordBounceEmail($bouncedEmail, BOUNCE_UNDETERMINED);
				}
				else
				{
					// We're not expecting any other types of bounces, so record
					// them as unknown.
					error_log('HandleEmailBouncedNotificationMethods received an unknown bounce type: ' . $bouncedEmail . ', ' . $bounceMsg->bounce->bounceType);
					TDOMailer::recordBounceEmail($bouncedEmail, BOUNCE_UNKNOWN);
				}
			}
		}
		else if ($bounceMsg->notificationType == 'Complaint')
		{
			$bouncedEmails = $bounceMsg->mail->destination;
			foreach($bouncedEmails as $bouncedEmail)
			{
				error_log('HandleEmailBounceNotificationMethods received a complaint bounce: ' . $bouncedEmail);
				TDOMailer::recordBounceEmail($bouncedEmail, BOUNCE_COMPLAINT);
			}
		}
		else
		{
			error_log('HandleEmailBounceNotificationMethods received unexpected notification: ' . $bounceMsg->notificationType);
			exit();
		}
	}
	else
	{
		error_log('HandleEmailBounceNotificationMethods received unexpected type: ' . $msg->Type);
	}
	
	
	/*
	 
	 Sample Subscription Confirmation:
	 
	 {
		"Type" : "SubscriptionConfirmation",
		"MessageId" : "7eaa86e6-6c43-4eb1-9982-db8e9a8335af",
		"Token" : "2336412f37fb687f5d51e6e241d09c81df3ba3d314f6d86cfb745e0c034e7c8fd720018646fca9059822f17a71201ea0c3de18ecd8084940623be0d965bdda7e262bd2c802789358bfaa74677e0cbcfda411c72d43cf455ea1679d6a5ee5e41ead0cd43ad76368372116d02202d3f93c30421acc1c0b4a189c21f3bf5a3654e983ab6a3953d4b2c4f8d980f16f24fbd0",
		"TopicArn" : "arn:aws:sns:us-east-1:398938165940:appigo-ses-bounce-notifications",
		"Message" : "You have chosen to subscribe to the topic arn:aws:sns:us-east-1:398938165940:appigo-ses-bounce-notifications.\\nTo confirm the subscription, visit the SubscribeURL included in this message.",
		"SubscribeURL" : "https://sns.us-east-1.amazonaws.com/?Action=ConfirmSubscription&TopicArn=arn:aws:sns:us-east-1:398938165940:appigo-ses-bounce-notifications&Token=2336412f37fb687f5d51e6e241d09c81df3ba3d314f6d86cfb745e0c034e7c8fd720018646fca9059822f17a71201ea0c3de18ecd8084940623be0d965bdda7e262bd2c802789358bfaa74677e0cbcfda411c72d43cf455ea1679d6a5ee5e41ead0cd43ad76368372116d02202d3f93c30421acc1c0b4a189c21f3bf5a3654e983ab6a3953d4b2c4f8d980f16f24fbd0",
		"Timestamp" : "2012-11-03T20:15:06.094Z",
		"SignatureVersion" : "1",
		"Signature" : "f8ks1mnoNxj2ScJmu3R4VrlC781JorVKIOW7oZYd9uNSY+Ov+l1gdbIvEaUNFPWq/9DYZZnFVbykYfxfOWvEbCzRd9mNiAqvhpi57oHKhCOHnclvpWrpiedPtt85HZIuvwH7Nw6HTYNHt++wDhKTY8FPfX8ExV7M/U604EGrOSM=",
		"SigningCertURL" : "https://sns.us-east-1.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem"
	 }
	 
	 Sample Bounce Notification:
	 
	 {
		"Type" : "Notification",
		"MessageId" : "dc2d8930-5b9c-4709-8cd2-3e39dc2341ce",
		"TopicArn" : "arn:aws:sns:us-east-1:398938165940:appigo-ses-bounce-notifications",
		"Message" :
		{
			"notificationType":"Bounce",
			"bounce":
			{
				"reportingMTA":"dns; a193-107.smtp-out.amazonses.com",
				"bounceType":"Permanent",
				"bouncedRecipients":
					[
						{
							"emailAddress":"john@example.com",
							"status":"5.0.0",
							"diagnosticCode":"smtp; 5.1.0 - Unknown address error 550-'5.7.1 <john@example.com>: Recipient address rejected: User unknown' (delivery attempts: 0)",
							"action":"failed"
						}
					],
				"bounceSubType":"General",
				"timestamp":"2012-11-03T20:24:55.000Z",
				"feedbackId":"0000013ac7f2b074-8b8d091f-25f4-11e2-a138-6f77ccfad686-000000"
			},
			"mail":
			{
				"timestamp":"2012-11-03T20:24:56.000Z",
				"source":"no-reply@todo-cloud.com",
				"messageId":"0000013ac7f29a6b-c0cc3eaa-fbf7-4a4b-8e5b-71cd3cfdee1d-000000",
				"destination":["john@example.com"]
			}
		}",
		"Timestamp" : "2012-11-03T20:25:02.113Z",
		"SignatureVersion" : "1",
		"Signature" : "i711b/WqbOA6VZMxF7J7k0Ewlf+t7DBvg03jxKL/uKe+zePDEGSObjuMgcMpicQvVUCXSzkq5FH+qmEr5bKQm7mnIWw4tpUs1ATYNLGM46Pt07wFSFj01xuDpe3gUVF5SNpaH3Uhm+Dn9Lz5PbTTBOAoFz/g2ifMhadhpxdv8qA=",
		"SigningCertURL" : "https://sns.us-east-1.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem",
		"UnsubscribeURL" : "https://sns.us-east-1.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-east-1:398938165940:appigo-ses-bounce-notifications:caccd413-0e7a-48fd-97db-9d1b192ec3a3"
	 }
	 
	 Sample Complaint Notification:
	 
	 {
		"Type" : "Notification",
		"MessageId" : "f156fea4-66d7-4e9f-85c4-c8955a5aaf55",
		"TopicArn" : "arn:aws:sns:us-east-1:398938165940:appigo-ses-bounce-notifications",
		"Message" :
		{
			"notificationType":"Complaint",
			"complaint":
			{
				"userAgent":"Amazon SES Mailbox Simulator",
				"complainedRecipients":[{"emailAddress":"complaint@simulator.amazonses.com"}],
				"complaintFeedbackType":"abuse",
				"timestamp":"2012-11-03T21:57:02.000Z",
				"feedbackId":"0000013ac846ee31-6638fec7-2601-11e2-a6d5-19d2f361820c-000000"
			},
			"mail":
			{
				"timestamp":"2012-11-03T21:57:01.000Z",
				"source":"no-reply@todo-cloud.com",
				"messageId":"0000013ac846eac2-c9f2bf69-20d0-4769-bf63-d117d6892a1b-000000",
				"destination":["complaint@simulator.amazonses.com"]
			}
		},
		"Timestamp" : "2012-11-03T21:57:02.807Z",
		"SignatureVersion" : "1",
		"Signature" : "S6BYUIk3FbUo4fg0OCu0ufw39qlxFaySJUf/aTwhcrRIhgCF3Joip0LSpJC1hew07H9B7ATs0+LKrYM8J3GuVqX9BzUfWzEl9AiDvL98/zAxAsU0lZEte20YnhW2SrJOTPLyN1zmJD4alGSjicvpRPM+54K48uQIkWcdQ+F4iN8=",
		"SigningCertURL" : "https://sns.us-east-1.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem",
		"UnsubscribeURL" : "https://sns.us-east-1.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-east-1:398938165940:appigo-ses-bounce-notifications:caccd413-0e7a-48fd-97db-9d1b192ec3a3"
	 }
	 
	 */
	
?>

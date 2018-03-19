<?php
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');
	
	
	define("ZDAPIKEY", "jKP8X3pwzNj0te8sCqGBATopnMRnjVvGg5tffYxm");
	define("ZDUSER", "admin@appigo.com");
	define("ZDURL", "https://appigo.zendesk.com/api/v2");
	
	
	function curlWrap($url, $json, $action)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_URL, ZDURL.$url);
		curl_setopt($ch, CURLOPT_USERPWD, ZDUSER."/token:".ZDAPIKEY);
		switch($action){
			case "POST":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
				break;
			case "GET":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
				break;
			case "PUT":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
				break;
			case "DELETE":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				break;
			default:
				break;
		}
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		curl_close($ch);
		$decoded = json_decode($output);
		return $decoded;
	}
	
	
	//check for valid parameters
	if(!isset($_POST['fromName']))
	{
		error_log("HandleSendFeedback.php called and missing a required parameter: fromName");
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('name'),
        ));
		return;
	}
	if(!isset($_POST['subject']))
	{
		error_log("HandleSendFeedback.php called and missing a required parameter: subject");
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('subject'),
        ));
		return;
	}
	if(!isset($_POST['message']))
	{
		error_log("HandleSendFeedback.php called and missing a required parameter: message");
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('message'),
        ));
		return;
	}
	
	$fromName = $_POST['fromName'];
	$subject = $_POST['subject'];
	$message = $_POST['message'];
    
    $userid = $session->getUserId();
    
    if(!empty($userid))
    {
		$displayName = TDOUser::displayNameForUserId($userid);
		$emailAddress = TDOUser::usernameForUserId($userid);

        $message .= "\r\n\r\n";
        $message .= "From User: " . $displayName . "\r\n";
        $message .= "Email:     " . $emailAddress . "\r\n";
        $message .= "User ID:   " . $userid . "\r\n";
        $message .= "Date:      " . date("Y/m/d H:i:s", mktime()) . "\r\n";
        $headers = 'From: ' . $displayName . ' <' . $emailAddress . '>' . "\r\n" . 'Reply-To: ' . $emailAddress . "\r\n" . 'X-Mailer: PHP/' . phpversion();

		/*
		$jsonArray = array('ticket' => array('subject' => $subject, 'description' => $message, 'requester' => array('name' => $displayName, 'email' => $emailAddress)));
		$jsonCreate = json_encode($jsonArray, JSON_FORCE_OBJECT);
        
		// send feedback via Zendesk API call
		$data = curlWrap("/tickets.json", $jsonCreate, "POST");
		*/
		$res = mail("Todo Cloud Support <support@appigo.com>", $subject , $message, $headers);
		if ($res)
		{
			echo '{"success":true}';
			return;
		}
		else
		{
			//echo "{\"success\":false, \"error\":\"no ticket created\", \"details\":\"$data\"}";
			echo "{\"success\":false, \"error\":\"no message sent\"}";
			return;
		}
		
    }

    echo "{'success':false}";
?>
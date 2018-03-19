#!/usr/bin/php -q
<?php

require_once 'Mail/mimeDecode.php';

define ("TODO_ONLINE_CREATE_TASK_URL", 'https://www.todo-cloud.com/?method=createTaskFromEmail');
define ("CREATE_EMAIL_TASK_SECRET", '86104B2D-DC10-4538-9A0E-61E974565D5E');

define ("TODO_ONLINE_CREATE_COMMENT_URL", 'https://www.todo-cloud.com/?method=createCommentFromEmail');
define ("REPLY_EMAIL_TASK_SECRET", 'F69754A3-9C48-4443-980F-766282FB467A');
define ("REPLY_EMAIL_ADDRESS_PREFIX", "comment+");

$logFile = "/var/spool/filter/filter.log";
$log = fopen($logFile, 'a');

function tdoParsePlainTextBody($decodedStructure, $log)
{
	if (empty($decodedStructure))
	{
		fwrite($log, "tdoParsePlainTextBody() called with empty decodedStructure.\n");
		return false;
	}

	// Check to see if there is a body present and if so,
	// that's the body we will use.

	if (isset($decodedStructure->body))
    {
        $body = $decodedStructure->body;
    
        //Bug 7263 - If we get a charset other then UTF-8, we need to convert the text encoding
        if(isset($decodedStructure->ctype_parameters))
        {
            if(isset($decodedStructure->ctype_parameters['charset']))
            {
                $encoding = $decodedStructure->ctype_parameters['charset'];
                if(strcasecmp($encoding, 'UTF-8') != 0)
                    $body = mb_convert_encoding($body, 'UTF-8', $encoding);
            }
        }
        
        return $body;
    }

	// This must be a multi-part message, so we've got to
	// look for a plain text part to return.

	if (!isset($decodedStructure->parts))
	{
		fwrite($log, "tdoParsePlainTextBody() could not find any body or parts from the email to use as the task's note.\n");
		return false;
	}

	$plainText = NULL;
	$htmlText = NULL;

	foreach ($decodedStructure->parts as $part)
	{
        //Bug 7263 - If we get a charset other then UTF-8, we need to convert the text encoding
        $encoding = NULL;
        if(isset($part->ctype_parameters))
        {
            if(isset($part->ctype_parameters['charset']) && strcasecmp($part->ctype_parameters['charset'], 'UTF-8') != 0)
            {
                $encoding = $part->ctype_parameters['charset'];
            }
        }
        
        if(isset($part->ctype_primary))
        {
        
            if ($part->ctype_primary == "text" )
            {
                if ($part->ctype_secondary == "plain")
                {
                    $plainText = trim($part->body);
                    if(!empty($encoding))
                        $plainText = mb_convert_encoding($plainText, 'UTF-8', $encoding);
                }
                else if ($part->ctype_secondary == "html")
                {
                    $htmlText = trim($part->body);
                    if(!empty($encoding))
                        $htmlText = mb_convert_encoding($htmlText, 'UTF-8', $encoding);
                }
            }
            else if($part->ctype_primary == "multipart")
            {
                if(isset($part->parts))
                {
                    $subParts = $part->parts;
                    foreach($subParts as $subPart)
                    {
                        if ($subPart->ctype_primary == "text" )
                        {
                            if ($subPart->ctype_secondary == "plain")
                            {
                                $plainText = trim($subPart->body);
                                if(!empty($encoding))
                                    $plainText = mb_convert_encoding($plainText, 'UTF-8', $encoding);
                            }
                            else if ($subPart->ctype_secondary == "html")
                            {
                                $htmlText = trim($subPart->body);
                                if(!empty($encoding))
                                    $htmlText = mb_convert_encoding($htmlText, 'UTF-8', $encoding);
                            }
                        }
                    }
                }
            }
        }
	}

	if (!empty($plainText))
    {
        return $plainText;
    }

	// If the function makes it this far, the only message available is in HTML only.
	// Instead of returning HTML, remove all TAGS and convert to plain text.
	if (!empty($htmlText))
	{
		$strippedText = html_entity_decode(str_replace("&nbsp;", " ", strip_tags($htmlText)));
        return $strippedText;
	}
}

function createTaskFromEmail($sender, $recipient, $subject, $body)
{
	global $log;

	if (empty($sender))
	{
		fwrite($log, "createTaskFromEmail() called with empty sender\n");
		return false;
	}
	if (empty($recipient))
	{
		fwrite($log, "createTaskFromEmail() called with empty recipient\n");
		return false;
	}

	// We use a "fancy" way of preventing anyone but US from
	// calling this method successfully on the Todo Online server
	// by using a secret MD5 hash
	$preHash = CREATE_EMAIL_TASK_SECRET . $sender . $recipient . CREATE_EMAIL_TASK_SECRET . "47";
	$apiKey = md5($preHash);

	$postFields = array(
		"sender" => $sender,
		"recipient" => $recipient,
		"apikey" => $apiKey
		);
	if (!empty($subject))
		$postFields['subject'] = $subject;

	if (!empty($body))
		$postFields['body'] = $body;

	$fieldsString = '';
	foreach ($postFields as $key=>$value)
	{
		$fieldsString .= $key . '=' . urlencode($value) . '&';
	}
	$fieldsString = rtrim($fieldsString, '&');

	$curlHandle = curl_init();
	if (!$curlHandle)
	{
		fwrite($log, "Error initializing CURL!\n");
		return false;
	}

	curl_setopt($curlHandle, CURLOPT_URL, TODO_ONLINE_CREATE_TASK_URL);
	curl_setopt($curlHandle, CURLOPT_POST, true);
	curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $fieldsString);

	$result = curl_exec($curlHandle);
	if ($result === false)
	{
		fwrite($log, "Curl Error: " . curl_error($curlHandle) . "\n");
	}

	curl_close($curlHandle);

	return $result;
}

function createCommentFromEmail($sender, $recipient, $subject, $body)
{
    global $log;

	if (empty($sender))
	{
		fwrite($log, "createCommentFromEmail() called with empty sender\n");
		return false;
	}
	if (empty($recipient))
	{
		fwrite($log, "createCommentFromEmail() called with empty recipient\n");
		return false;
	}

	// We use a "fancy" way of preventing anyone but US from
	// calling this method successfully on the Todo Online server
	// by using a secret MD5 hash
	$preHash = REPLY_EMAIL_TASK_SECRET . $sender . $recipient . REPLY_EMAIL_TASK_SECRET . "47";
	$apiKey = md5($preHash);

	$postFields = array(
		"sender" => $sender,
		"recipient" => $recipient,
		"apikey" => $apiKey
		);

	if (!empty($body))
		$postFields['body'] = $body;

	$fieldsString = '';
	foreach ($postFields as $key=>$value)
	{
		$fieldsString .= $key . '=' . urlencode($value) . '&';
	}
	$fieldsString = rtrim($fieldsString, '&');

	$curlHandle = curl_init();
	if (!$curlHandle)
	{
		fwrite($log, "Error initializing CURL!\n");
		return false;
	}

	curl_setopt($curlHandle, CURLOPT_URL, TODO_ONLINE_CREATE_COMMENT_URL);
	curl_setopt($curlHandle, CURLOPT_POST, true);
	curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $fieldsString);

	$result = curl_exec($curlHandle);
	if ($result === false)
	{
		fwrite($log, "Curl Error: " . curl_error($curlHandle) . "\n");
	}

	curl_close($curlHandle);

	return $result;    
}

function parseReplyFromEmail($wholeEmail)
{
    global $log;
 
    $reply = "";
    
    $wholeEmail = trim($wholeEmail);
    if(!empty($wholeEmail))
    {
        $lines = preg_split('/\n|\r\n?/', $wholeEmail);
        
        //Append lines to the reply until we reach a line that looks like
        //it's part of the quoted original email. We're attempting to do this
        //a lot like facebook does it.
        
        foreach($lines as $line)
        {
            $trimmedLine = trim($line);
            
            //A lot of clients put > before all lines in the reply
            if(strpos($trimmedLine, ">") === 0)
                break;
            
            //This should handle some versions of Outlook (that show --- Original Message ---)
            if(strpos($trimmedLine, "---") === 0)
                break;
            
            //This should handle Yahoo mail
            if(strpos($trimmedLine, "____") === 0)
                break;
            
            //Some clients put | before all lines in the reply
            if(strpos($trimmedLine, "|") === 0)
                break;
            
            //This should handle Hotmail
            if(stripos($trimmedLine, "Date:") === 0)
                break;

            //This should handle some versions of Outlook
            if(stripos($trimmedLine, "From:") === 0)
                break;
            
            //This should handle Mail, Gmail, and Thunderbird (they show On <date>, <sender> wrote:)
            if(stripos($trimmedLine, "wrote:") === strlen($trimmedLine) - strlen("wrote:"))
                break;
            
            //In Facebook, they base this heavily on finding dates in the line. Try to
            //imitate that.
            if(lineContainsDateRejectedByFacebook($trimmedLine))
                break;
            
            if(strlen($reply) > 0)
                $reply .= "\n";
            
            $reply .= $line;
        }
    }
    return $reply;
}

function lineContainsDateRejectedByFacebook($line)
{
    //If the line starts with any dates with slashes, it's rejected
    $result = NULL;
    $formatsToCheck = array("d/m/y", "m/d/y", "y/d/m", "y/m/d", "d/m/Y", "m/d/Y", "Y/d/m", "Y/m/d");
    
    foreach($formatsToCheck as $format)
    {
        $result = date_parse_from_format($format, $line);
        if(dateWasParsedSuccessfully($result))
            return true;
    }
    
    //If the line contains a date of the format Dec 13, 2012 anywhere in it,
    //Facebook rejects it. To check that, iterate through each sequence of 3 words
    $words = preg_split('/\s+/', $line);
    
    $index = 0;
    while($index + 2 < count($words))
    {
        $w1 = $words[$index];
        $w2 = $words[$index + 1];
        $w3 = $words[$index + 2];
        
        $sequence = "$w1 $w2 $w3";
        
        $formatsToCheck = array("M d Y", "M d, Y");
        foreach($formatsToCheck as $format)
        {
            $result = date_parse_from_format($format, $sequence);
            if(dateWasParsedSuccessfully($result))
                return true;
        }
        
        $index++;
    }
    
    
    return false;
    
}
function dateWasParsedSuccessfully($result)
{
    if($result == NULL)
        return false;
    
    if(!isset($result['errors']))
        return true;
    
    $array = $result['errors'];
    if(count($array) == 0)
        return true;
    
    //The only error we permit is Trailing data
    foreach($array as $error)
    {
        if($error != "Trailing data")
            return false;
    }
    
    return true;
    
}

$sender = $argv[1];
$recipient = $argv[2];

// Read the incoming email from STDIN
$sock = fopen("php://stdin", 'r');
$message = '';

// Read the entire email into a variable
while (!feof($sock))
{
	$mailData = fread($sock, 1024);
	$message .= $mailData;
}

// Close the read socket
fclose($sock);

$parseParams = array(
	"include_bodies" => true,
	"decode_bodies" => true,
	"decode_headers" => true,
	);

$structure = NULL;
try
{
    $subject = NULL;
    
    //Bug 7263 - Mail_mimeDecode will not decode the subject to UTF-8, so we need to do that ourselves
    //using iconv_mime_decode_headers to be able to get the subject if it's in a strange encoding
    $decodedHeaders = iconv_mime_decode_headers($message, 0, 'UTF-8');
    if(!empty($decodedHeaders) && isset($decodedHeaders['Subject']))
    {
        $subject = $decodedHeaders['Subject'];
    }
    
	$decoder = new Mail_mimeDecode($message);
	$structure = $decoder->decode($parseParams);
    
    //Only look for the subject if for some reason we couldn't find it using iconv_mime_decode_headers
	if ($subject == NULL && isset($structure->headers) && isset($structure->headers['subject']))
	{
		$subject = $structure->headers['subject'];
	}

	// Now grab the plain text portion of the email so that we
	// can use it as the task's note.

    $plainTextBody = tdoParsePlainTextBody($structure, $log);

	////fwrite($log, "==== " . strftime("%d %b %Y, %H:%M:%S", time()) . " =========\n");
	//fwrite($log, "  Subject: " . $subject . "\n");
	////fwrite($log, "   Sender: " . $sender . "\n");
	////fwrite($log, "Recipient: " . $recipient . "\n");
	//if (!empty($plainTextBody))
	//{
	//	fwrite($log, "----- MESSAGE ------\n" . $plainTextBody . "\n-----------\n");
	//}

    if(strlen($recipient) > strlen(REPLY_EMAIL_ADDRESS_PREFIX) && strpos($recipient, REPLY_EMAIL_ADDRESS_PREFIX) === 0)
    {
        $plainTextBody = trim(parseReplyFromEmail($plainTextBody));
        ////fwrite($log, "Creating comment from email\n");
        $result = createCommentFromEmail($sender, $recipient, $subject, $plainTextBody);
        if ($result == false)
        {
            fwrite($log, "Failed to create comment for email sent by $sender addressed to $recipient\n");
        }
    }
    else
    {
        ////fwrite($log, "Creating task from email\n");
        
        // Send the info to the server!
        $result = createTaskFromEmail($sender, $recipient, $subject, $plainTextBody);
        if ($result == false)
        {
            fwrite($log, "Failed to create task for email sent by $sender addressed to $recipient\n");
        }
    }
}
catch (Exception $e)
{
	fwrite($log, "\n\n===== EXCEPTION: =====\n " . $e->getMessage() . "\n=====================\n\n");
}

fclose($log);

?>

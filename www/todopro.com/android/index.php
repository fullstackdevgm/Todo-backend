<?php
	include_once('TodoOnline/base_sdk.php');
	
	$requestURI = $_SERVER['REQUEST_URI'];
	$externalFile = NULL;
	$pageTitle = NULL;
	
	$baseURL = '../';
	$pathPrefix = '../';
	if (strlen(SITE_BASE_S3_URL) > 0)
	{
		$baseURL = SITE_BASE_S3_URL;
		$pathPrefix = '';
	}
	
	if (strstr($requestURI, 'android-confirm'))
	{
		$externalFile = 'html/android-confirm.html';
	}
	else if (strstr($requestURI, 'android-thankyou'))
	{
		$externalFile = 'html/android-thankyou.html';
	}
	else if (strstr($requestURI, 'android-unsubscribe'))
	{
		$externalFile = 'html/android-unsubscribe.html';
	}
	else
	{
		$externalFile = 'html/android.html';
	}

	$file = fopen($baseURL . $externalFile, "r");
	if (!$file)
	{
		echo "<p>Unable to load file.</p>\n";
		exit;
	}

	while (!feof($file))
	{
		$line = fgets($file, 1024);
		echo $line;
	}

	fclose($file);
?>

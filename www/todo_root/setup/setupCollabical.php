<?php

// include files
include_once('AWS/sdk.class.php');
include_once('Collabical/config.php');


// Instantiate
$sdb = new AmazonSDB();

echo ("Creating user account domain...");
$response = $sdb->create_domain(PB_DOMAIN_USER_ACCOUNTS);
if($response->isOK() == True)
{
	echo (" success!<br/>");
} 
else
{
	echo " Failed! <br/>";
	exit;
}

echo ("Creating user session domain...");
$response = $sdb->create_domain(PB_DOMAIN_USER_SESSIONS);
if($response->isOK() == True)
{
	echo (" success! <br/>");
} 
else
{
	echo " Failed! <br/>";
	exit;
}

echo ("Creating boards domain...");
$response = $sdb->create_domain(PB_DOMAIN_BOARDS);
if($response->isOK() == True)
{
	echo (" success!<br/>");
} 
else
{
	echo " Failed! <br/>";
	exit;
}

echo ("Creating events domain...");
$response = $sdb->create_domain(PB_DOMAIN_EVENTS);
if($response->isOK() == True)
{
	echo (" success!<br/>");
} 
else
{
	echo " Failed! <br/>";
	exit;
}
    
echo ("Creating invitations domain...");
$response = $sdb->create_domain(PB_DOMAIN_INVITATIONS);
if($response->isOK() == True)
{
    echo (" success!<br/>");
} 
else
{
    echo " Failed! <br/>";
    exit;
}
	
?>

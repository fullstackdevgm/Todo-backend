#!/usr/bin/php -q
<?php

// Include Classes
require_once "System/Daemon.php";
include_once('MCDaemonConfig.php');
include_once('MCExpirationController.php');
 
// Allowed arguments & their defaults 
$runmode = array("no-daemon" => false, "help" => false, "write-initd" => false);
 
// Scan command line attributes for allowed arguments
foreach ($argv as $k=>$arg)
{
    if (substr($arg, 0, 2) == "--" && isset($runmode[substr($arg, 2)]))
    {
        $runmode[substr($arg, 2)] = true;
    }
}
 
// Help mode. Shows allowed argumentents and quit directly
if ($runmode["help"] == true)
{
    echo "Usage: " . $argv[0] . " [runmode]\n";
    echo "Available runmodes:\n"; 
    foreach ($runmode as $runmod=>$val)
    {
        echo " --" . $runmod . "\n";
    }
    die();
}
  

 
// Setup
$options = array(
	"appName" => "mcexpirationd",
	"appDir" => dirname(__FILE__),
	"appDescription" => "Message Center Daemon",
    "authorName" => "Appigo, Inc.",
	"authorEmail" => "admin@todo-cloud.com",
	"sysMaxExecutionTime" => "0",
	"sysMaxInputTime" => "0",
	"sysMemoryLimit" => "1024M",
	"appRunAsGID" => DAEMON_GID,
	"appRunAsUID" => DAEMON_UID
);
 
System_Daemon::setOptions($options);
 
// Overrule the signal handler with any function
System_Daemon::setSigHandler(SIGCONT, array("System_Daemon", "defaultSigHandler"));
 
 
// This program can also be run in the forground with runmode --no-daemon
if (!$runmode["no-daemon"])
{
	System_Daemon::start();
}
 
// With the runmode --write-initd, this program can automatically write a 
// system startup file called: 'init.d'
// This will make sure your daemon will be started on reboot 
if (!$runmode["write-initd"])
{
    System_Daemon::log(System_Daemon::LOG_INFO, "not writing " . "an init.d script this time");
}
else
{
	if (($initd_location = System_Daemon::writeAutoRun()) === false)
	{
		System_Daemon::log(System_Daemon::LOG_NOTICE, "unable to write " . "init.d script");
	}
	else
	{
		System_Daemon::log(System_Daemon::LOG_INFO, "sucessfully written " . "startup script: " . $initd_location);
	}
}
	
$expirationDaemon = new MCExpirationController('A');
  
while (!System_Daemon::isDying())
{
	if($expirationDaemon->processExpiredMessages() == true)
    {
        System_Daemon::iterate(EXPIRATION_AUTORENEW_SLEEP_INTERVAL);
    }
    else
    {
        //If the processing failed for some reason, try again sooner
        System_Daemon::iterate(EXPIRATION_AUTORENEW_SLEEP_INTERVAL_ERROR);
    }
}

 
 
System_Daemon::stop();
?>

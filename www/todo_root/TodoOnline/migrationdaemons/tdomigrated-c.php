#!/usr/bin/php -q
<?php

// Include Classes
require_once "System/Daemon.php";
include_once('TDOMigrationDaemonConfig.php');
include_once('TDOMigrationController.php');
 
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
	"appName" => "tdomigrated-c",
	"appDir" => dirname(__FILE__),
	"appDescription" => "Todo Cloud User Migration Daemon",
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

$q = new TDOMigrationController('cf687068-8795-4d78-b2bf-427930d557fe');
  
while (!System_Daemon::isDying())
{
	// If we processed less than the loop limit, we know that we've covered
	// everything for the next X minutes, so sleep for that amount of time.
	// If we did process at least as many as the limit, don't really sleep,
	// but get right back to work.
	// CRG - changed this, if we hit 0, slow sleep, otherwise work
	if ($q->queueMigrations() == 0)
		System_Daemon::iterate(QUEUE_PROCESS_SLOW_SLEEP); 
	else
		System_Daemon::iterate(QUEUE_PROCESS_FAST_SLEEP);
}

 
 
System_Daemon::stop();
?>

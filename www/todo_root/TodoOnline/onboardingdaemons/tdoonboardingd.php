#!/usr/bin/php -q
<?php

// Include Classes
require_once "System/Daemon.php";
include_once('TDODaemonConfig.php');
include_once('TDOOnboardingController.php');
 
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

$log_location = '/var/log/tdoonboardingd.log';

if (filesize($log_location) > LOG_SIZE_LIMIT) {
    $log_file_pattern = '/var/log/tdoonboardingd*.log';
    $find_files = glob($log_file_pattern);
    $new_file_path = str_replace('*', '-' . sizeof($find_files), $log_file_pattern);
    rename($log_location, $new_file_path);
}
 
// Setup
$options = array(
	"appName" => "tdoonboardingd",
	"appDir" => dirname(__FILE__),
	"appDescription" => "Todo Cloud Onboarding Daemon",
    "authorName" => "Appigo, Inc.",
	"authorEmail" => "admin@todo-cloud.com",
	"sysMaxExecutionTime" => "0",
	"sysMaxInputTime" => "0",
	"sysMemoryLimit" => "1024M",
	"appRunAsGID" => DAEMON_GID,
	"appRunAsUID" => DAEMON_UID,
    "logLocation" => $log_location,
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
		System_Daemon::log(System_Daemon::LOG_INFO, "successfully written " . "startup script: " . $initd_location);
	}
}
	
$onboardingDaemon = new TDOOnboardingController('A');
	
while (!System_Daemon::isDying())
{
	$onboardingDaemon->processOnboardingEmails();
	
	$daemonSleepIntervalString = TDOUtil::getStringSystemSetting("SYSTEM_SETTING_ONBOARDING_DAEMON_SLEEP_INTERVAL", DEFAULT_SYSTEM_SETTING_ONBOARDING_DAEMON_SLEEP_INTERVAL);
	
	$nowDate = new DateTime("now", new DateTimeZone("UTC"));
	$futureDate = new DateTime("now", new DateTimeZone("UTC"));
	$futureDate->add(new DateInterval($daemonSleepIntervalString));
	
	$intervalInSeconds = $futureDate->getTimestamp() - $nowDate->getTimestamp();
	
	System_Daemon::iterate($intervalInSeconds);
}

 
 
System_Daemon::stop();
?>

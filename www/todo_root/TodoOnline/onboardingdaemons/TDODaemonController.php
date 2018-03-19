<?php

include_once('TDODaemonConfig.php');
include_once('TDODaemonLogger.php');

class TDODaemonController
{
	// Each daemon will be uniquely identified with a GUID.  This will help in identifying
	// multiple daemons 
	protected $guid;

	private $logger;
	
	function __construct($daemonID = '')
	{
		if (isset($daemonID))
			$this->guid = $daemonID;
/*
		print("argc: " . $argc . "\n");
		if ($argc > 1)
			$this->guid = $argv[1];
		else
		{
			print("You must specify a daemon ID\n");
			exit(1);
		}
*/
		//$this->guid = "DefaultQueueDaemon";

		// Set up a logger that will be the name of the class (subclass)
		$logger_name = get_class($this);
		if (isset($this->guid))
			$logger_name .= "-" . $this->guid;

		$this->logger = new TDODaemonLogger($logger_name);
		$this->log("==== DAEMON STARTED ====");

	}
	
	function log($message)
	{
		$this->logger->log($message);
	}

	function __toString()
	{
		$properties = (array) $this;
		return implode(",", $properties);
	}

	
}

?>

<?php

include_once('TDOMigrationDaemonConfig.php');
include_once('TodoOnline/base_sdk.php');
include_once('TDOMigrationDaemonLogger.php');

class TDOMigrationController
{			
	// Each daemon will be uniquely identified with a GUID.  This will help in identifying
	// multiple daemons 
	protected $guid;
	private $logger;
	
	function __construct($daemonID = '')
	{
		if (isset($daemonID))
			$this->guid = $daemonID;
        else
            $this->guid = "EC3A89BD-C176-4656-B669-C80036C72D4D";
        
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
    
    
	public function queueMigrations()
	{
		$this->log("-----------------------------------------------");

        
        $result = TDOLegacy::markUserRecordForMigration($this->guid);
        if(!$result)
        {
            $this->log("There was an error marking records, NULL was returned, returning FALSE");
            return false;
        }
        
        if(!empty($result['error']))
        {
            $error = $result['error'];
            
            $this->log("There was an error marking records: ".$error['msg'].", returning FALSE");
            return false;
        }
        
        $recordsMarked = $result['records_marked_count'];
        if($recordsMarked == 0)
        {
            $this->log("No users found for migration, returning FALSE");
            return false;
        }
        
        $this->log($recordsMarked . " - Marked ");
        $totalMarkedRecords = $recordsMarked;


        $result = TDOLegacy::processMarkedRecords($this->guid);
        
        if(!empty($result['error']))
        {
            $error = $result['error'];
            $this->log("Error migrating user: ".$error['id'].": ".$error['msg']);
            return false;
        }
        
        if(!empty($result['userid']))
        {
            $this->log("Migrated user with Userid: ".$result['userid']);
        }
        
        if(!empty($result['lists_migrated']))
        {
            $migratedArray = $result['lists_migrated'];
            $this->log("Migrated ".count($migratedArray)." lists");
        }

        if(!empty($result['contexts_migrated']))
        {
            $migratedArray = $result['contexts_migrated'];
            $this->log("Migrated ".count($migratedArray)." contexts");
        }

        if(!empty($result['tasks_migrated']))
        {
            $migratedArray = $result['tasks_migrated'];
            $this->log("Migrated ".count($migratedArray)." tasks");
        }

        if(!empty($result['notifications_migrated']))
        {
            $migratedArray = $result['notifications_migrated'];
            $this->log("Migrated ".count($migratedArray)." notifications");
        }

		return 1;
	}
}


?>


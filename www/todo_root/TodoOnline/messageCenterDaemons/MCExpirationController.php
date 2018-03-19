<?php

include_once('MCDaemonConfig.php');
include_once('TodoOnline/messageCenter/mc_base_sdk.php');
include_once('MCDaemonLogger.php');
include_once('MCDaemonController.php');
	

class MCExpirationController extends MCDaemonController
{			

	function __construct($daemonID = '')
	{
		parent::__construct($daemonID);
	}
		
    
    //Search for expired subscriptions and remove them from their lookup tables
    public function processExpiredMessages()
    {
//        error_log("*** Processing Expired Messages ***");
        $this->log("*** Processing Expired Messages ***");
        
        $dynamoDBClient = DynamoDBUtil::getDynamoDBClient();
        
        $currentTime = time();
        $messages = MCMessageExpirationMethods::getExpiredMessages($currentTime, $dynamoDBClient);
        
        if($messages === false)
        {
//            error_log("Failed to get expired messages");
            $this->log("Failed to get expired messages");
            return false;
        }
        
        //Loop through all expired messages and remove them from their lookup tables
//        error_log("Found ".count($messages)." expired messages to process....");
        $this->log("Found ".count($messages)." expired messages to process....");
        
        foreach($messages as $message)
        {
            if($message->removeMessage(NULL, $dynamoDBClient) == false)
            {
//                error_log("Failed to remove lookup table entries for message: ".$message->subject());
                $this->log("Failed to remove lookup table entries for message: ".$message->subject());
            }
        }
        
//        error_log("Done processing messages");
        $this->log("Done processing messages");
        
        return true;
    }
}


?>


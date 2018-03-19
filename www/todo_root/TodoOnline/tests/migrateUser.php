<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/classes/TDOLegacy.php');    
    

    function showUsage()
    {
        echo "\nUsage: migrateUser username=<username> password=<password>\n  or\n  migrateUser id=<daemonid> processQueue=true\n\n";
    }
    
    // $_GET['a'] to '1' and $_GET['b'] to array('2', '3').
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    
    if(!empty($_GET['help']))
    {
        showUsage();
        return;
    }

    if( (empty($_GET['username'])) && (empty($_GET['password'])) && (empty($_GET['processQueue'])) && (empty($_GET['id'])) )
    {
        showUsage();
        return;
    }

    
    if( (!empty($_GET['username'])) && (!empty($_GET['password'])) )
    {
        $result = TDOLegacy::startMigrationForLegacyUser($_GET['username'], $_GET['password']);
        
        if(!empty($result['error']))
        {
            // we had an error, dump it
            var_dump($result);
            return;
        }
        
        if(empty($result['userid']))
        {
            echo "There was no error but we also didn't get a userid.\n";
            var_dump($result);
        }

        if(empty($result['subscription_time_added']))
        {
            echo "There was no error but we also didn't get a subscription_time_added.\n";
            var_dump($result);
        }

        echo "Started migration process for user: ".$result['userid']."\n";
        echo "User received a bonus on their subscription time of: ".$result['subscription_time_added']."\n";
    }
    else
    {
        if(!empty($_GET['id']))
        {
            switch($_GET['id'])
            {
                case 'a':
                    $daemonID = 'EC3A89BD-C176-4656-B669-C80036C72D4D';
                    break;
                case 'b':
                    $daemonID = 'db6a81c2-c742-48eb-b21c-843243507683';
                    break;
                case 'c':
                    $daemonID = 'cf687068-8795-4d78-b2bf-427930d557fe';
                    break;
                case 'd':
                    $daemonID = '5317fefd-e117-45a0-9ae8-219f47d3409e';
                    break;
                case 'e':
                    $daemonID = '23bc6776-56bf-4833-8345-56710728888c';
                    break;
                case 'f':
                    $daemonID = 'b27f664c-fac4-4638-ba43-3c3c17e799ec';
                    break;
                case 'g':
                    $daemonID = '5bf46472-34ce-418c-8879-7c2bb503e922';
                    break;
                case 'h':
                    $daemonID = '15edd281-8d36-4731-b925-76683c4a1823';
                    break;
            }
        }
        else
        {
            $daemonID = 'EC3A89BD-C176-4656-B669-C80036C72D4D';
        }
        
        // set the error log to log out to a log
        ini_set('error_log','/var/log/tdoMigrationDaemon/migrationd-'.$daemonID.'.log');
        
        
        error_log("Processing the queue as: " . $daemonID);
        
        $response = TDOLegacy::markUserRecordForMigration($daemonID);
        if(!empty($response['error']))
        {
            $error = $response['error'];
            error_log("Error marking record for processing user: ".$error['id'].": ".$error['msg']);
            return;
        }
        
        $markedRecordCount = $response['records_marked_count'];
        
        error_log("Marked ".$markedRecordCount." records for migration");
        
        $response = TDOLegacy::processMarkedRecords($daemonID);
        
        if(!empty($response['error']))
        {
            $error = $response['error'];
            error_log("Error migrating user: ".$error['id'].": ".$error['msg']);
            
            TDOLegacy::markUserRecordForFailedMigration($daemonID);
            return;
        }
        
        $processedUser = false;
        if(!empty($response['userid']))
        {
            error_log("Migrated user with Userid: ".$response['userid']);
            $processedUser = true;
        }
        
        if(!empty($response['lists_migrated']))
        {
            $migratedArray = $response['lists_migrated'];
            error_log("Migrated ".count($migratedArray)." lists");
            $processedUser = true;
        }
        
        if(!empty($response['contexts_migrated']))
        {
            $migratedArray = $response['contexts_migrated'];
            error_log("Migrated ".count($migratedArray)." contexts");
            $processedUser = true;
        }
        
        if(!empty($response['tasks_migrated']))
        {
            $migratedArray = $response['tasks_migrated'];
            error_log("Migrated ".count($migratedArray)." tasks");
            $processedUser = true;
        }
        
        if(!empty($response['notifications_migrated']))
        {
            $migratedArray = $response['notifications_migrated'];
            error_log("Migrated ".count($migratedArray)." notifications");
            $processedUser = true;
        }
        
        if($processedUser == false)
        {
            error_log("No user migrated, sleeping for 5 seconds...");
            Sleep(5);
        }
        
        
//        if(!empty($response['userid']))
//        {
//            echo "Create new user with Userid: ".$response['userid']."\n";
//        }
//        
//        if(!empty($response['lists_migrated']))
//        {
//            $listsMigrated = $response['lists_migrated'];
//            echo "Migrated ".count($listsMigrated)." lists\n";
//            foreach($listsMigrated as $list)
//            {
//                echo "        ".$list['title']."\n";
//            }
//        }
//        
//        if(!empty($response['contexts_migrated']))
//        {
//            $contextsMigrated = $response['contexts_migrated'];
//            echo "Migrated ".count($contextsMigrated)." contexts\n";
//            foreach($contextsMigrated as $context)
//            {
//                echo "        ".$context['title']."\n";
//            }
//        }
//
//        if(!empty($response['tasks_migrated']))
//        {
//            $tasksMigrated = $response['tasks_migrated'];
//            echo "Migrated ".count($tasksMigrated)." tasks\n";
//            foreach($tasksMigrated as $task)
//            {
//                echo "        ".$task['title']."\n";
//            }
//        }
//
//        if(!empty($response['notifications_migrated']))
//        {
//            $notificationsMigrated = $response['notifications_migrated'];
//            echo "Migrated ".count($notificationsMigrated)." notifications\n";
//            foreach($notificationsMigrated as $notification)
//            {
//                echo "        ".$notification['soundname']."\n";
//            }
//        }
    }
    
    
?>


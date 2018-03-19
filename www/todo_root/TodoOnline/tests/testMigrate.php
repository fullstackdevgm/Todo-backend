<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/classes/TDOLegacy.php');    
    

    function showUsage()
    {
        echo "\nUsage: testMigrate.php username=<username> password=<password>\n  or\n       testMigrate.php userid=<userid>\n\n";
    }
    
    // $_GET['a'] to '1' and $_GET['b'] to array('2', '3').
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    
    if(!empty($_GET['--help']))
    {
        showUsage();
        return;
    }
    
    if( (empty($_GET['username'])) || (empty($_GET['password'])) )
    {
        showUsage();
        return;
    }
    
    $tdoLegacy = new TDOLegacy();
    
    $response = $tdoLegacy->authUser($_GET['username'], $_GET['password']);

    if(!empty($response['error']))
    {
        // we had an error, dump it
        var_dump($response);
        return;
    }

    if(empty($response['user']))
    {
        echo "We didn't get the response we expected, but got this....\n";
        var_dump($response);
    }
    
    $user = $response['user'];
    
    if(empty($user['userid']))
    {
        echo "No userid means we can't sync!\n";
        return;
    }

    $userId = $user['userid'];
    
    $response = $tdoLegacy->startSyncSession($userId, ACS_MIGRATION_DEVICE);

    if(!empty($response['error']))
    {
        // we had an error, dump it
        var_dump($response);
        return;
    }
    
    if(empty($response['session']))
    {
        echo "We didn't get the response we expected, but got this....\n";
        var_dump($response);
    }
    
    $session = $response['session'];
    
    if(empty($session['sessionid']))
    {
        echo "No sessionid means we can't sync!";
        return;
    }
    
    $sessionId = $session['sessionid'];

    if(empty($session['sharedSecret']))
    {
        echo "No sharedSecret means we can't sync, ending syncSession!";
        $tdoLegacy->endSyncSession($sessionId);
        return;
    }
    
    $sharedSecret = $session['sharedSecret'];
    
    $response = $tdoLegacy->getLists($sessionId, $sharedSecret);
    
    if(!empty($response['error']))
    {
        // we had an error, dump it
        var_dump($response);
        return;
    }
    
    if(empty($response['lists']))
    {
        echo "getLists: We didn't get the response we expected, but got this....\n";
        var_dump($response);
    }

    $lists = $response['lists'];
    

    echo "\n";
    echo "Lists\n";
    echo "-----------------------------------------\n";
    if(!empty($lists['list']))
    {
        $listArray = $lists['list'];
        
        foreach($listArray as $list)
        {
            $title = $list['title'];
            $color = $list['color'];
            $listid = $list['listid'];
            
            echo $title . "    - id: " . $listid . "\n";
        }
    }
    else
        echo "No Lists found\n";
    
    $response = $tdoLegacy->getContexts($sessionId, $sharedSecret);
    
    if(!empty($response['error']))
    {
        // we had an error, dump it
        var_dump($response);
        return;
    }
    
    if(empty($response['contexts']))
    {
        echo "getContexts: We didn't get the response we expected, but got this....\n";
        var_dump($response);
    }
    
    $contexts = $response['contexts'];
    
    echo "\n";
    echo "Contexts\n";
    echo "-----------------------------------------\n";
    if(!empty($contexts['context']))
    {
        $contextArray = $contexts['context'];
        
        foreach($contextArray as $context)
        {
            $title = $context['title'];
            $contextid = $context['contextid'];
            
            echo $title . "    - id: " . $contextid . "\n";
        }
    }
    else
        echo "No Contexts Found\n";
    
    

    echo "\n";
    echo "Tasks\n";
    echo "-----------------------------------------\n";
    $hasMore = true;
    $serverOffset = NULL;
    
    while($hasMore)
    {
        $response = $tdoLegacy->getTasks($sessionId, $sharedSecret, $serverOffset);
        
        if(!empty($response['error']))
        {
            // we had an error, dump it
            var_dump($response);
            return;
        }
        
        if(empty($response['tasks']))
        {
            echo "getTasks: We didn't get the response we expected, but got this....\n";
            $hasMore = false;
            var_dump($response);
            continue;
        }
        
        $tasks = $response['tasks'];
        
        if(empty($tasks['task']))
        {
            $hasMore = false;            
            continue;
        }
        
        if(isset($tasks['offset']))
            $serverOffset = $tasks['offset'];

        $numReturned = $tasks['numreturned'];

        if($numReturned < 500)
            $hasMore = false;

        
        $taskArray = $tasks['task'];
        // Moki really bunged stuff up here so check if there is a title key
        // and if there is, put the values inside an array so processing is the
        // same for one item or multiple items
        if(array_key_exists('title', $taskArray) == true)
        {
            $taskArray = array();
            $taskArray[] = $tasks['task'];
        }        
        
        
        foreach($taskArray as $task)
        {
            //var_dump($task);
            $title = $task['title'];
            echo "Task Title: ". $title . "\n";
            
            if(!empty($task['listid']))
            {
                echo "  - list: ".$task['listid'];
            }
            
            if(!empty($task['duedate']))
            {
                $uniDate = strtotime($task['duedate']);
                echo "  - due: ".$uniDate;
            }
            
            if(!empty($task['lastupdated']))
            {
                $uniDate = strtotime($task['lastupdated']);
                echo "  - updated: ".$uniDate;
            }
            
            if(!empty($task['type']))
            {
                $uniDate = strtotime($task['lastupdated']);
                echo "  - updated: ".$uniDate;
            }

            if(!empty($task['type_data']))
            {
                $uniDate = strtotime($task['lastupdated']);
                echo "  - updated: ".$uniDate;
            }
            
            
            
            
            
            
            echo "\n";
        }
    }
    
    
    echo "\n";
    echo "Notifications\n";
    echo "-----------------------------------------\n";
    $hasMore = true;
    $serverOffset = NULL;
    
    while($hasMore)
    {
        $response = $tdoLegacy->getNotifications($sessionId, $sharedSecret, $serverOffset);
        
        if(!empty($response['error']))
        {
            // we had an error, dump it
            var_dump($response);
            return;
        }

        if(empty($response['notifications']))
        {
            echo "We didn't get the response we expected, but got this....\n";
            $hasMore = false;
            var_dump($response);
            continue;
        }

        $notifications = $response['notifications'];
        
        if(empty($notifications['notification']))
        {
            $hasMore = false;            
            continue;
        }
        
        $notificationArray = $notifications['notification'];
        if(isset($notifications['offset']))
            $serverOffset = $notifications['offset'];
        
        $numReturned = $notifications['numreturned'];
        
        if($numReturned < 500)
            $hasMore = false;
        
        foreach($notificationArray as $notification)
        {
            //var_dump($notification);
            $notificationid = $notification['uid'];
            $triggerDate = $notification['triggerdate'];
            $soundName = $notification['soundname'];
            echo "Id: " . $notificationid . " - ". $triggerDate." - ".$soundName."\n";
        }
    }
    
    
    
    $response = $tdoLegacy->endSyncSession($sessionId);
    
    if(!empty($response['error']))
    {
        // we had an error, dump it
        var_dump($response);
        return;
    }

    echo "Test was successful!";
    
    
?>


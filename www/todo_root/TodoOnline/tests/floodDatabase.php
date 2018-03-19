<?php

include_once('TodoOnline/base_sdk.php');
    
    echo "Adding a lot of users";
    
    for($i=1; $i < 50000; $i++)
    {
        $user = new TDOUser();
        
        $userGUID = TDOUtil::uuid();
        $user->setUsername("User-".$userGUID);
        $user->setPassword("secret");
        $user->setFirstName("Human".$userGUID);
        $user->setLastName("Being".$userGUID);
        
        if($user->addUser() == false)
        {
            echo "\nFailed to add user, bailing!";
            break;
        }
        else
        {
            echo "\nAdded User ".$i.": ".$user->username().", now adding tasks\n";
        
            for($t=1; $t < 300; $t++)
            {
                $taskName = "Task ".$t." for ".$userGUID;
				$listid = TDOList::getUserInboxId($user->userId(), true);
                
                $newTask = new TDOTask();
                  
                $newTask->setListid($listid);
                $newTask->setName($taskName);
                  
                if($newTask->addObject() == true)
                {
                    echo ".";
                    TDOChangeLog::addChangeLog($listid, "admin", $newTask->taskId(), $newTask->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
                }
                else
                {
                    echo "\nAdding tasks for user failed, bailing";
                    break;
                }
            }
        }
    }
    
//    $users = TDOUser::getAllUsers();
//    if($users)
//    {
//        $userCount = count($users);
//        echo "\nDumping All Users (".$userCount.")\n";
//
//        foreach($users as $user)
//        {
//            $uid = $user->userId();
//            $userName = $user->username();
//            $firstName = $user->firstName();
//            $lastName = $user->lastName();
//
//            echo "Username:".$userName." UID:".$uid." \n";
//        }
//    }
//    else
//        echo "No Users Found";

    
?>


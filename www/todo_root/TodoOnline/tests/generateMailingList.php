<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/classes/TDOLegacy.php');    
    

    $allUsers = TDOUser::getAllUsers();
    
    if($allUsers)
    {
        echo count($allUsers) ." users on Todo Cloud:\n\n";
        
        foreach($allUsers as $user)
        {
            echo $user->username() . "\n";
        }
    }
    else
        echo "There are no users!\n";
    
?>


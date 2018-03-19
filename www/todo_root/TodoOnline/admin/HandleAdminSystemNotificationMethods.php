<?php

include_once('TodoOnline/base_sdk.php');

    if($method == "addSystemNotification")
    {
        if(!isset($_POST['message']))
        {
            error_log("addSystemNotification called missing required parameter message");
            echo '{"success":false, "error":"missing required parameter message"}';
            return;
        }
        
        $message = $_POST['message'];
        
        $notification = new TDOSystemNotification();
        $notification->setMessage($message);
        $notification->setTimestamp(time());
        
        if(isset($_POST['url']))
            $notification->setLearnMoreUrl($_POST['url']);
        
        if($notification->addSystemNotification())
        {
            $response = array();
            $response['success'] = true;
            $response['notification'] = $notification->getPropertiesArray();
            echo json_encode($response);
        }
        else
        {
            echo '{"success":false, "error":"failed to add notification"}';
        }
        
    }
    else if($method == "removeSystemNotification")
    {
        if(!isset($_POST['notificationid']))
        {
            error_log("removeSystemNotification called missing required parameter notificationid");
            echo '{"success":false, "error":"missing required parameter notificationid"}';
            return;
        }
        
        $notificationid = $_POST['notificationid'];
        
        if(TDOSystemNotification::deleteSystemNotification($notificationid))
        {
            echo '{"success":true}';
        }
        else
        {
            echo '{"success":false}';
        }
    }
    else if($method == "getCurrentSystemNotification")
    {
        $notification = TDOSystemNotification::getCurrentSystemNotification();
        if($notification === false)
        {
            echo '{"success":false}';
        }
        else
        {
            $response = array();
            $response['success'] = true;
            
            if($notification != NULL)
            {
                $response['notification'] = $notification->getPropertiesArray();
            }
            echo json_encode($response);
        }
    }
?>

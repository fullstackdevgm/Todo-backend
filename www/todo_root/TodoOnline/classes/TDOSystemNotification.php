<?php

include_once('TodoOnline/base_sdk.php');
	
class TDOSystemNotification extends TDODBObject
{
	
    public function addSystemNotification($link=NULL)
    {
        if($this->message() == NULL)
        {
            error_log("Attempting to add a system notification with no message attached");
            return false;
        }
        
        if(empty($link))
        {
            $closeDBLink = true;
            
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOSystemNotification unable to get DB link");
                return false;
            }
        }
        else
        {
            $closeDBLink = false;
        }
        
        if($this->notificationId() == NULL)
            $this->setNotificationId(TDOUtil::uuid());
        
        if($this->timestamp() == 0)
            $this->setTimestamp(time());
    
        $notificationId = mysql_real_escape_string($this->notificationId(), $link);
        $timestamp = intval($this->timestamp());
        $deleted = intval($this->deleted());
        $message = mysql_real_escape_string($this->message(), $link);
        
        $learnMoreUrl = NULL;
        if($this->learnMoreUrl() != NULL)
            $learnMoreUrl = mysql_real_escape_string($this->learnMoreUrl(), $link);
        
        
        //If this is a non-deleted notification, we first must delete all old notifications, because
        //we are only supporting one at a time
        if($deleted == 0)
        {
            $sql = "UPDATE tdo_system_notifications SET deleted=1 WHERE deleted=0";
            
            if(!mysql_query($sql, $link))
            {
                error_log("addSystemNotification failed to delete old notification with error: ".mysql_error());
                
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                
                return false;
            }
        }
        
        $sql = "INSERT INTO tdo_system_notifications (notificationid, message, timestamp, deleted, learn_more_url) VALUES ('$notificationId', '$message', $timestamp, $deleted, '$learnMoreUrl')";
        
        if(!mysql_query($sql, $link))
        {
            error_log("addSystemNotification failed with error: ".mysql_error());
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            
            return false;
        }
            
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        
        return true;
        
    }
    
    public static function deleteSystemNotification($notificationId, $link=NULL)
    {
        if(empty($notificationId))
            return false;
        
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            
            if(empty($link))
            {
                error_log("TDOSystemNotification failed to get db link");
                return false;
            }
        }
        else
        {
            $closeDBLink = false;
        }
    
        $notificationId = mysql_real_escape_string($notificationId, $link);
        
        $sql = "UPDATE tdo_system_notifications SET deleted=1 WHERE notificationid='$notificationId'";
        
        if(!mysql_query($sql, $link))
        {
            error_log("deleteSystemNotification failed with error: ".mysql_error());
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            
            return false;
        }
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        
        return true;
    }
    
    public static function getCurrentSystemNotification($link=NULL)
    {
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            
            if(empty($link))
            {
                error_log("TDOSystemNotification failed to get db link");
                return false;
            }
        }
        else
        {
            $closeDBLink = false;
        }
        
        $sql = "SELECT * FROM tdo_system_notifications WHERE deleted=0";
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            $row = mysql_fetch_array($result);
            if($row)
            {
                $notification = TDOSystemNotification::systemNotificationFromRow($row);
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                
                return $notification;
            }
            else
            {
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                    
                return NULL;
            }
        }
        else
            error_log("getCurrentSystemNotification failed with error: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        
        return false;
    }
    
    public static function systemNotificationFromRow($row)
    {
        if(empty($row))
            return NULL;
        
        $notification = new TDOSystemNotification();
        
        if(isset($row['notificationid']))
            $notification->setNotificationId($row['notificationid']);
        if(isset($row['message']))
            $notification->setMessage($row['message']);
        if(isset($row['timestamp']))
            $notification->setTimestamp($row['timestamp']);
        if(isset($row['deleted']))
            $notification->setDeleted($row['deleted']);
        if(isset($row['learn_more_url']))
            $notification->setLearnMoreUrl($row['learn_more_url']);
        
        return $notification;
    }
    
    
    public function notificationId()
    {
        if(empty($this->_publicPropertyArray['notificationid']))
            return NULL;
        else
            return $this->_publicPropertyArray['notificationid'];
    }
    
    public function setNotificationId($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['notificationid']);
        else
            $this->_publicPropertyArray['notificationid'] = $val;
    }

    public function message()
    {
        if(empty($this->_publicPropertyArray['message']))
            return NULL;
        else
            return $this->_publicPropertyArray['message'];
    }
    public function setMessage($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['message']);
        else
            $this->_publicPropertyArray['message'] = $val;
    }

    public function learnMoreUrl()
    {
        if(empty($this->_publicPropertyArray['learn_more_url']))
            return NULL;
        else
            return $this->_publicPropertyArray['learn_more_url'];
    }
    public function setLearnMoreUrl($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['learn_more_url']);
        else
            $this->_publicPropertyArray['learn_more_url'] = $val;
    }
}
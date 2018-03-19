<?php
//  TDOGiftCode - used to handle gift codes that users can buy for each other
    
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');

class TDOGiftCode extends TDODBObject 
{
    public function __construct()
    {
        parent::__construct();
        
        $this->set_to_default();
    }
    
    public function set_to_default()
    {
        parent::set_to_default();
        
    }
    
    // ------------------------
    // Property Methods
    // ------------------------

    public function giftCode()
    {
        if(empty($this->_publicPropertyArray['giftcode']))
            return NULL;
        else
            return $this->_publicPropertyArray['giftcode'];
    }
    
    public function setGiftCode($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['giftcode']);
        else
            $this->_publicPropertyArray['giftcode'] = $val;
    }

    public function subscriptionDuration()
    {
        if(empty($this->_publicPropertyArray['duration']))
            return 0;
        else
            return $this->_publicPropertyArray['duration'];
    }
    
    public function setSubscriptionDuration($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['duration']);
        else
            $this->_publicPropertyArray['duration'] = $val;
    }    
    
    public function stripeGiftPaymentId()
    {
        if(empty($this->_publicPropertyArray['payment_id']))
            return NULL;
        else
            return $this->_publicPropertyArray['payment_id'];
    }
    
    public function setStripeGiftPaymentId($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['payment_id']);
        else
            $this->_publicPropertyArray['payment_id'] = $val;
    }
    
    public function purchaserUserId()
    {
        if(empty($this->_publicPropertyArray['purchaser']))
            return NULL;
        else
            return $this->_publicPropertyArray['purchaser'];        
    }
    public function setPurchaserUserId($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['purchaser']);
        else
            $this->_publicPropertyArray['purchaser'] = $val;
    }

    public function purchaseTimestamp()
    {
        if(empty($this->_publicPropertyArray['purchase_date']))
            return 0;
        else
            return $this->_publicPropertyArray['purchase_date'];        
    }
    public function setPurchaseTimestamp($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['purchase_date']);
        else
            $this->_publicPropertyArray['purchase_date'] = $val;
    }

    public function senderName()
    {
        if(empty($this->_publicPropertyArray['sender']))
            return NULL;
        else
            return $this->_publicPropertyArray['sender'];        
    }
    public function setSenderName($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['sender']);
        else
            $this->_publicPropertyArray['sender'] = $val;
    }

    public function recipientName()
    {
        if(empty($this->_publicPropertyArray['recipient']))
            return NULL;
        else
            return $this->_publicPropertyArray['recipient'];        
    }
    public function setRecipientName($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['recipient']);
        else
            $this->_publicPropertyArray['recipient'] = $val;
    }

    public function recipientEmail()
    {
        if(empty($this->_publicPropertyArray['recipient_email']))
            return NULL;
        else
            return $this->_publicPropertyArray['recipient_email'];        
    }
    public function setRecipientEmail($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['recipient_email']);
        else
            $this->_publicPropertyArray['recipient_email'] = $val;
    }

    public function consumptionDate()
    {
        if(empty($this->_publicPropertyArray['consumption_timestamp']))
            return 0;
        else
            return $this->_publicPropertyArray['consumption_timestamp'];        
    }
    public function setConsumptionDate($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['consumption_timestamp']);
        else
            $this->_publicPropertyArray['consumption_timestamp'] = $val;
    }

    public function consumerUserId()
    {
        if(empty($this->_publicPropertyArray['consumer_id']))
            return NULL;
        else
            return $this->_publicPropertyArray['consumer_id'];        
    }
    public function setConsumerUserId($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['consumer_id']);
        else
            $this->_publicPropertyArray['consumer_id'] = $val;
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
    
    public function addGiftCode()
    {
        if($this->giftCode() == NULL)
            $this->setGiftCode(TDOUtil::uuid());
        
    
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOGiftCode failed to get DB link");
            return false;
        }
        
        $giftCode = "'".mysql_real_escape_string($this->giftCode(), $link)."'";
        
        if($this->stripeGiftPaymentId() == NULL)
            $paymentId = "NULL";
        else
            $paymentId = "'".mysql_real_escape_string($this->stripeGiftPaymentId(), $link)."'";
        
        $subscriptionDuration = intval($this->subscriptionDuration());
        
        if($this->purchaserUserId() == NULL)
            $purchaserId = "NULL";
        else
            $purchaserId = "'".mysql_real_escape_string($this->purchaserUserId(), $link)."'";
        
        $purchaseTimestamp = intval($this->purchaseTimestamp());
        
        if($this->senderName() == NULL)
            $sender = "NULL";
        else
            $sender = "'".mysql_real_escape_string($this->senderName(), $link)."'";
        
        if($this->recipientName() == NULL)
            $recipient = "NULL";
        else
            $recipient = "'".mysql_real_escape_string($this->recipientName(), $link)."'";
        
        if($this->recipientEmail() == NULL)
            $email = "NULL";
        else
            $email = "'".mysql_real_escape_string($this->recipientEmail(), $link)."'";
        
        $consumptionDate = intval($this->consumptionDate());
        
        if($this->consumerUserId() == NULL)
            $consumer = "NULL";
        else
            $consumer = "'".mysql_real_escape_string($this->consumerUserId(), $link)."'";
        
        if($this->message() == NULL)
            $message = "NULL";
        else
            $message = "'".mysql_real_escape_string($this->message(), $link)."'";
        
        $sql = "INSERT INTO tdo_gift_codes (gift_code, stripe_gift_payment_id, subscription_duration, purchaser_userid, purchase_timestamp, sender_name, recipient_name, recipient_email, consumption_date, consumer_userid, message) VALUES ($giftCode, $paymentId, $subscriptionDuration, $purchaserId, $purchaseTimestamp, $sender, $recipient, $email, $consumptionDate, $consumer, $message)";
        
        if(mysql_query($sql, $link))
        {
            TDOUtil::closeDBLink($link);
            return true;
        }
        else
            error_log("addGiftCode failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
        
    }
    
    public function updateGiftCode($link=NULL)
    {
        if($this->giftCode() == NULL)
            $this->setGiftCode(TDOUtil::uuid());
    
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOGiftCode failed to get DB link");
                return false;
            }
        }
        else
            $closeDBLink = false;
        
        if($this->stripeGiftPaymentId() == NULL)
            $updateString = "stripe_gift_payment_id=NULL";
        else
            $updateString = "stripe_gift_payment_id='".mysql_real_escape_string($this->stripeGiftPaymentId(), $link)."'";
        
        $updateString .= ",subscription_duration=".intval($this->subscriptionDuration());
        
        if($this->purchaserUserId() == NULL)
            $updateString .= ",purchaser_userid=NULL";
        else
            $updateString .= ",purchaser_userid='".mysql_real_escape_string($this->purchaserUserId(), $link)."'";
        
        $updateString .= ",purchase_timestamp=".intval($this->purchaseTimestamp());
        
        if($this->senderName() == NULL)
            $updateString .= ",sender_name=NULL";
        else
            $updateString .= ",sender_name='".mysql_real_escape_string($this->senderName(), $link)."'";
        
        if($this->recipientName() == NULL)
            $updateString .= ",recipient_name=NULL";
        else
            $updateString .= ",recipient_name='".mysql_real_escape_string($this->recipientName(), $link)."'";
        
        if($this->recipientEmail() == NULL)
            $updateString .= ",recipient_email=NULL";
        else
            $updateString .= ",recipient_email='".mysql_real_escape_string($this->recipientEmail(), $link)."'";
        
        $updateString .= ",consumption_date=".intval($this->consumptionDate());
        
        if($this->consumerUserId() == NULL)
            $updateString .= ",consumer_userid=NULL";
        else
            $updateString .= ",consumer_userid='".mysql_real_escape_string($this->consumerUserId(), $link)."'";
        
        if($this->message() == NULL)
            $updateString .= ",message=NULL";
        else
            $updateString .= ",message='".mysql_real_escape_string($this->message(), $link)."'";
    
        $sql = "UPDATE tdo_gift_codes SET $updateString WHERE gift_code='".mysql_real_escape_string($this->giftCode(), $link)."'";
        
        if(mysql_query($sql, $link))
        {
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return true;
        }
        else
            error_log("updateGiftCode failed with error: ".mysql_error());
        
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;        
    
    }
    
    public static function giftCodeFromRow($row)
    {
        if(empty($row))
            return NULL;
        
        $giftCode = new TDOGiftCode();
        
        if(isset($row['gift_code']))
            $giftCode->setGiftCode($row['gift_code']);
        if(isset($row['stripe_gift_payment_id']))
            $giftCode->setStripeGiftPaymentId($row['stripe_gift_payment_id']);
        if(isset($row['subscription_duration']))
            $giftCode->setSubscriptionDuration($row['subscription_duration']);
        if(isset($row['purchaser_userid']))
            $giftCode->setPurchaserUserId($row['purchaser_userid']);
        if(isset($row['purchase_timestamp']))
            $giftCode->setPurchaseTimestamp($row['purchase_timestamp']);
        if(isset($row['sender_name']))
            $giftCode->setSenderName($row['sender_name']);
        if(isset($row['recipient_name']));
            $giftCode->setRecipientName($row['recipient_name']);
        if(isset($row['recipient_email']))
            $giftCode->setRecipientEmail($row['recipient_email']);
        if(isset($row['consumption_date']))
            $giftCode->setConsumptionDate($row['consumption_date']);
        if(isset($row['consumer_userid']))
            $giftCode->setConsumerUserId($row['consumer_userid']);
        if(isset($row['message']))
            $giftCode->setMessage($row['message']);
        
        return $giftCode;
    }
    
    public static function giftCodeForCode($giftCode)
    {
        if(empty($giftCode))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOGiftCode failed to get DB link");
            return false;
        }        
        
        $sql = "SELECT * FROM tdo_gift_codes WHERE gift_code='".mysql_real_escape_string($giftCode, $link)."'";
        
        $result = mysql_query($sql, $link);
        
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                $giftCode = TDOGiftCode::giftCodeFromRow($row);
                TDOUtil::closeDBLink($link);
                return $giftCode;
            }
            else
            {
                TDOUtil::closeDBLink($link);
                return NULL;
            }
        }
        else
            error_log("giftCodeForCode failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function giftCodesForUser($userId, $usedOnly=false, $unusedOnly=false)
    {
        if(empty($userId))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOGiftCode failed to get DB link");
            return false;
        }
        
        $sql = "SELECT * from tdo_gift_codes WHERE purchaser_userid='".mysql_real_escape_string($userId, $link)."'";
        if($usedOnly)
        {
            $sql .= " AND consumption_date != 0";
        }
        else if($unusedOnly)
        {
            $sql .= " AND consumption_date = 0";
        }
        
        $sql .= " ORDER BY purchase_timestamp DESC";
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            $codes = array();
            while($row = mysql_fetch_array($result))
            {
                $giftCode = TDOGiftCode::giftCodeFromRow($row);
                $codes[] = $giftCode;
            }
            
            return $codes;
        }
        else
            error_log("giftCodesForUser failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function giftCodesConsumedByUser($userId)
    {
        if(empty($userId))
            return false;
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOGiftCode failed to get DB link");
            return false;
        }
        
        $sql = "SELECT * from tdo_gift_codes WHERE consumer_userid='".mysql_real_escape_string($userId, $link)."'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $codes = array();
            while($row = mysql_fetch_array($result))
            {
                $giftCode = TDOGiftCode::giftCodeFromRow($row);
                $codes[] = $giftCode;
            }
            
            return $codes;
        }
        else
            error_log("giftCodesConsumedByUser failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;        
    }
    
    public static function allGiftCodesInSystem($unusedOnly=false, $usedOnly=false, $offset = 0, $limit = 50)
    {
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOGiftCode failed to get DB link");
            return false;
        }
        
        $limit = intval($limit);
        if ($limit > PROMO_CODE_LIST_MAXIMUM_LIMIT)
            $limit = PROMO_CODE_LIST_MAXIMUM_LIMIT;
        
        $offset = intval($offset);
			if ($offset < 0)
				$offset = 0;
        
        $sql = "SELECT * FROM tdo_gift_codes";
        
        if($unusedOnly)
            $sql .= " WHERE consumption_date=0";
        else if($usedOnly)
            $sql .= " WHERE consumption_date != 0";
        
        $sql .= " ORDER BY purchase_timestamp DESC LIMIT $limit OFFSET $offset";
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            $giftCodes = array();
            while($row = mysql_fetch_array($result))
            {
                $giftCode = TDOGiftCode::giftCodeFromRow($row);
                $giftCodes[] = $giftCode;
            }
            TDOUtil::closeDBLink($link);
            return $giftCodes;
        }
        else
            error_log("TDOGiftCode::allGiftCodesInSystem failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
    
    }
    
    public static function countGiftCodesInSystem($unusedOnly=false, $usedOnly=false)
    {
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOGiftCode failed to get DB link");
            return false;
        }
        $sql = "SELECT count(gift_code) FROM tdo_gift_codes";
        if($unusedOnly)
            $sql .= " WHERE consumption_date=0";
        else if($usedOnly)
            $sql .= " WHERE consumption_date != 0";
        
        $result = mysql_query($sql, $link);
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                if(isset($row[0]))
                {
                    $count = $row[0];
                    TDOUtil::closeDBLink($link);
                    return $count;
                }
            }
        }
        else
            error_log("TDOGiftCode::countGiftCodesInSystem failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function giftCodeInfoForUser($userId)
    {
        $unusedGiftCodes = TDOGiftCode::giftCodesForUser($userId, false, true);
        if($unusedGiftCodes === false)
            return false;
        
        $usedGiftCodes = TDOGiftCode::giftCodesForUser($userId, true, false);
        if($usedGiftCodes === false)
            return false;
        
        $unusedGiftCodesJSON = array();
        foreach($unusedGiftCodes as $giftCode)
        {
            $giftCodeJSON = $giftCode->getPropertiesArray(true);
            $unusedGiftCodesJSON[] = $giftCodeJSON;
        }
        
        $usedGiftCodeJSON = array();
        foreach($usedGiftCodes as $giftCode)
        {
            $giftCodeJSON = $giftCode->getPropertiesArray(true);
            $usedGiftCodeJSON[] = $giftCodeJSON;
        }
        
        $infoArray = array();
        $infoArray['unused_codes'] = $unusedGiftCodesJSON;
        $infoArray['used_codes'] = $usedGiftCodeJSON;
        
        return $infoArray;
    }
    
    public function getPropertiesArray($getDisplayInfo=false)
    {
        $propertiesArray = parent::getPropertiesArray();
        if($getDisplayInfo)
        {
            if($this->purchaserUserId() != NULL)
                $propertiesArray['purchaser_displayname'] = TDOUser::displayNameForUserId($this->purchaserUserId());
            if($this->consumerUserId() != NULL)
                $propertiesArray['consumer_displayname'] = TDOUser::displayNameForUserId($this->consumerUserId());
            $propertiesArray['giftcode_link'] = TDOGiftCode::giftCodeLinkForCode($this->giftCode());
        }
        
        return $propertiesArray;
    }
    
    public static function giftCodesForStripePaymentId($stripePaymentId)
    {
        if(empty($stripePaymentId))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOGiftCode failed to get DB link");
            return false;
        }
        
        $sql = "SELECT * from tdo_gift_codes WHERE stripe_gift_payment_id='".mysql_real_escape_string($stripePaymentId, $link)."'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $codes = array();
            while($row = mysql_fetch_array($result))
            {
                $giftCode = TDOGiftCode::giftCodeFromRow($row);
                $codes[] = $giftCode;
            }
            
            return $codes;
        }
        else
            error_log("giftCodesForStripePaymentId failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;        
    }

    public static function giftCodeLinkForCode($code)
    {
        $giftLink = SITE_PROTOCOL . SITE_BASE_URL . "?applygiftcode=true&giftcode=" . $code;
        return $giftLink;
    }

    public static function applyGiftCodeToSubscription($giftCode, $userID, $subscriptionID)
    {
        if (empty($giftCode) || empty($userID) || empty($subscriptionID))
        {
            return false;
        }

        $subscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
        if (empty($subscription))
        {
            error_log("TDOGiftCode::applyGiftCodeToSubscription could not get subscription for subscription id: $subscriptionID");
            return false;
        }
		
		// If the subscription is part of a team, do not allow the gift code to
		// be applied.
		$teamID = $subscription->getTeamID();
		if (!empty($teamID))
		{
            error_log("TDOGiftCode::applyGiftCodeToSubscription cannot be applied to a subscription that is part of a team, subscriptionID = $subscriptionID");
            return false;
		}
        
        //If the gift code has already been used, return false
        if($giftCode->consumptionDate() != 0 || $giftCode->consumerUserId() != NULL)
        {
            error_log("TDOGiftCode::applyGiftCodeToSubscription called with used gift code");
            return false;
        }
        
        $duration = $giftCode->subscriptionDuration();
        $extensionInterval = new DateInterval("P" . $duration . "M");
        
        $expirationTimestamp = $subscription->getExpirationDate();
        $expirationDate = new DateTime('@' . $expirationTimestamp, new DateTimeZone("UTC"));
        $nowDate = new DateTime();
        
        // Make sure that the gift code at least starts from today and not
        // way back if the user's expiration date is far in the past.
        if ($nowDate > $expirationDate)
            $expirationDate = $nowDate;
        
        $newExpirationDate = $expirationDate->add($extensionInterval);
        $newExpirationTimestamp = $newExpirationDate->getTimestamp();
        
        $subscriptionType = $subscription->getSubscriptionType();
        
        //Apply the subscription and mark the gift code as used in a transaction, so that if one fails, we fail the whole operation
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOGiftCode failed to get db link");
            return false;
        }
        
        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("TDOGiftCode failed to begin database transaction");
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        // Adjust the subscription to the new expiration date
        if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $subscriptionType, SUBSCRIPTION_LEVEL_GIFT, $link))
        {
            error_log("TDOGiftCode::applyGiftCodeToSubscription($giftCode, $subscriptionID) failed to update the user's subscription with the new expiration date");
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        //Update the gift code to show that it has been used
        $giftCode->setConsumptionDate(time());
        $giftCode->setConsumerUserId($userID);
        
        if($giftCode->updateGiftCode($link) == false)
        {
            error_log("TDOGiftCode::applyGiftCodeToSubscription($giftCode, $subscriptionID) applied the gift code to the user's subscription, but failed to delete the gift code. Rolling back transaction.");
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        if(!mysql_query("COMMIT", $link))
        {
            error_log("TDOGiftCode failed to commit transaction");
            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return false;
        }
        
        TDOUtil::closeDBLink($link);
        return true;
    }

}

?>
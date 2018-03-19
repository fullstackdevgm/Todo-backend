<?php
//  TDOStripeGiftPayment - used to track purchases of gift codes
    
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');

class TDOStripeGiftPayment extends TDODBObject
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

    public function userId()
    {
        if(empty($this->_publicPropertyArray['userid']))
            return NULL;
        else
            return $this->_publicPropertyArray['userid'];
    }
    
    public function setUserId($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['userid']);
        else
            $this->_publicPropertyArray['userid'] = $val;
    }

    public function stripeUserId()
    {
        if(empty($this->_publicPropertyArray['stripe_id']))
            return NULL;
        else
            return $this->_publicPropertyArray['stripe_id'];
    }
    
    public function setStripeUserId($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['stripe_id']);
        else
            $this->_publicPropertyArray['stripe_id'] = $val;
    }
 
    public function stripeChargeId()
    {
        if(empty($this->_publicPropertyArray['charge_id']))
            return NULL;
        else
            return $this->_publicPropertyArray['charge_id'];
    }
    
    public function setStripeChargeId($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['charge_id']);
        else
            $this->_publicPropertyArray['charge_id'] = $val;
    }
    
    public function cardType()
    {
        if(empty($this->_publicPropertyArray['card_type']))
            return NULL;
        else
            return $this->_publicPropertyArray['card_type'];
    }
    
    public function setCardType($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['card_type']);
        else
            $this->_publicPropertyArray['card_type'] = $val;
    }
    
    public function lastFour()
    {
        if(empty($this->_publicPropertyArray['last_four']))
            return NULL;
        else
            return $this->_publicPropertyArray['last_four'];
    }
    
    public function setLastFour($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['last_four']);
        else
            $this->_publicPropertyArray['last_four'] = $val;
    }

    public function amount()
    {
        if(empty($this->_publicPropertyArray['amount']))
            return 0;
        else
            return $this->_publicPropertyArray['amount'];
    }
    
    public function setAmount($val)
    {
        if(empty($val))
            unset($this->_publicPropertyArray['amount']);
        else
            $this->_publicPropertyArray['amount'] = $val;
    }
    
    public function addStripeGiftPayment()
    {
        if($this->stripeGiftPaymentId() == NULL)
            $this->setStripeGiftPaymentId(TDOUtil::uuid());
        
        if($this->stripeUserId() == NULL || $this->stripeChargeId() == NULL || $this->cardType() == NULL || $this->lastFour() == NULL)
        {
            error_log("Attempting to add stripe gift payment with missing information");
            return false;
        }
    
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOGiftCode failed to get DB link");
            return false;
        }
        
        $paymentId = "'".mysql_real_escape_string($this->stripeGiftPaymentId(), $link)."'";
        if($this->userId() == NULL)
            $userId = "NULL";
        else
            $userId = "'".mysql_real_escape_string($this->userId(), $link)."'";
        
        $stripeUserId = "'". mysql_real_escape_string($this->stripeUserId(), $link)."'";
        $stripeChargeId = "'". mysql_real_escape_string($this->stripeChargeId(), $link)."'";
        $cardType = "'". mysql_real_escape_string($this->cardType(), $link)."'";
        $lastFour = "'". mysql_real_escape_string($this->lastFour(), $link)."'";
        $amount = floatval($this->amount());
        $timestamp = intval($this->timestamp());
        
        
        $sql = "INSERT INTO tdo_stripe_gift_payment_history (stripe_gift_payment_id, userid, stripe_userid, stripe_chargeid, card_type, last4, amount, timestamp) VALUES ($paymentId, $userId, $stripeUserId, $stripeChargeId, $cardType, $lastFour, $amount, $timestamp)";
        
        if(mysql_query($sql, $link))
        {
            TDOUtil::closeDBLink($link);
            return true;
        }
        else
            error_log("addStripeGiftPayment failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
        
    }
    
    public static function stripeGiftPaymentFromRow($row)
    {
        if(empty($row))
            return NULL;
        
        $payment = new TDOStripeGiftPayment();
        
        if(isset($row['stripe_gift_payment_id']))
            $payment->setStripeGiftPaymentId($row['stripe_gift_payment_id']);
        if(isset($row['userid']))
            $payment->setUserId($row['userid']);
        if(isset($row['stripe_userid']))
            $payment->setStripeUserId($row['stripe_userid']);
        if(isset($row['stripe_chargeid']))
            $payment->setStripeChargeId($row['stripe_chargeid']);
        if(isset($row['card_type']))
            $payment->setCardType($row['card_type']);
        if(isset($row['last4']));
            $payment->setLastFour($row['last4']);
        if(isset($row['amount']))
            $payment->setAmount($row['amount']);
        if(isset($row['timestamp']))
            $payment->setTimestamp($row['timestamp']);
        
        return $payment;
    }
    
    public static function stripeGiftPaymentForPaymentId($paymentId)
    {
        if(empty($paymentId))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOStripeGiftPayment failed to get DB link");
            return false;
        }        
        
        $sql = "SELECT * FROM tdo_stripe_gift_payment_history WHERE stripe_gift_payment_id='".mysql_real_escape_string($paymentId, $link)."'";
        
        $result = mysql_query($sql, $link);
        
        if($result)
        {
            if($row = mysql_fetch_array($result))
            {
                $payment = TDOStripeGiftPayment::stripeGiftPaymentFromRow($row);
                TDOUtil::closeDBLink($link);
                return $payment;
            }
            else
            {
                TDOUtil::closeDBLink($link);
                return NULL;
            }
        }
        else
            error_log("stripeGiftPaymentForPaymentId failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
    }
    
    public static function stripeGiftPaymentsForUser($userId)
    {
        if(empty($userId))
            return false;
        
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            error_log("TDOStripeGiftPayment failed to get DB link");
            return false;
        }
        
        $sql = "SELECT * from tdo_stripe_gift_payment_history WHERE userid='".mysql_real_escape_string($userId, $link)."' ORDER BY timestamp DESC";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $payments = array();
            while($row = mysql_fetch_array($result))
            {
                $payment = TDOStripeGiftPayment::stripeGiftPaymentFromRow($row);
                $payments[] = $payment;
            }
            
            return $payments;
        }
        else
            error_log("stripeGiftPaymentsForUser failed with error: ".mysql_error());
        
        TDOUtil::closeDBLink($link);
        return false;
    }
}

?>
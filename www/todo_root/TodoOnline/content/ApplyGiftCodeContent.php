
<script type="text/javascript" src="<?php echo TP_JS_PATH_APPLY_GIFT_CODE_FUNCTIONS; ?>"></script>

<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/php/SessionHandler.php');
include_once('TodoOnline/DBConstants.php');


if($session->isLoggedIn()) 
{
	$userID = $session->getUserId();
	$errorMessage = NULL;
    if(isset($_GET['giftcode']))
    {
		$giftCodeId = $_GET['giftcode'];
        
        $giftCode = TDOGiftCode::giftCodeForCode($giftCodeId);
        if(!empty($giftCode))
        {
            if($giftCode->consumptionDate() == 0 && $giftCode->consumerUserId() == NULL)
            {
                $subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userID);
                if ($subscriptionID)
                {
                    $originalSubscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
                    if($originalSubscription)
                    {
                        //Prevent gift code from being applied to account that is already 2 years or more from expiring
                        $twoYearsFromNow = mktime(0, 0, 0, date("n"), date("j"), (date("Y") + 2));
                        
                        if($originalSubscription->getExpirationDate() < $twoYearsFromNow)
                        {
                            echo "<script type=\"text/javascript\">displayRedeemGiftCodeModal('".$giftCodeId."');</script>";
                        }
                        else
                        {
                            $errorMessage = _('The gift code cannot be applied to your account because <br/>your current subscription does not expire within the next two years.<br/>');
                        }
                    }
                    else
                    {
                        $errorMessage = _('Your account cannot accept a gift code, please contact the support team.') . '<br/>';
                    }
                }
                else
                {
                    $errorMessage = _('Your account cannot accept a gift code, please contact the support team.') . '<br/>';
                }
            }
            else
            {
                $errorMessage = _('This gift code has already been used.') . '<br/>';
            }
		}
		else
		{
			$errorMessage = _('This is not a valid gift code.') . '<br/>';
		}
	}
    else
    {
        $errorMessage = _('The url you have visited is invalid.') . '<br/>';
    }

    if($errorMessage != NULL): ?>
        <script type="text/javascript">displayGiftCodeProcessedModal('<?php echo $errorMessage; ?>', true);</script>
    <?php
    endif;

} 


?>


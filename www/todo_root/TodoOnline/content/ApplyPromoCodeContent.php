<script type="text/javascript">

function displayApplyPromoCodeModal(message)
{
    var header = <?php _e('Premium Account Promo Code'); ?>;
    var body = message;
    var footer = '<div class="button" onclick="top.location=\'.\'"><?php _e('OK'); ?></div>';
    
    displayModalContainer(body, header, footer);
}

</script>

<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/php/SessionHandler.php');
include_once('TodoOnline/DBConstants.php');


if($session->isLoggedIn()) 
{
	$userID = $session->getUserId();
	$message = '';
    if(isset($_GET['promocode']))
    {
		$promoCode = $_GET['promocode'];
		
		$promoCodeInfo = TDOPromoCode::getPromoCodeInfo($promoCode);
		if (isset($promoCodeInfo['success']))
		{
            if(TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userID) == false)
            {
                $promoCodeInfo = $promoCodeInfo['promo_code_info'];
                $subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userID);
                
                if ($subscriptionID)
                {
                    $result = TDOPromoCode::applyPromoCodeToSubscription($promoCode, $userID, $subscriptionID);
                    if (isset($result['success']))
                    {
                        $subscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
                        $expirationTimestamp = $subscription->getExpirationDate();
                        $expirationDateString = date("D j M Y", $expirationTimestamp);

                        $message = sprintf(_('Thank you for redeeming your promo code!<br/><br/>Your Todo Cloud Premium Account is now valid through:<br/><br/>%s.<br/>'), $expirationDateString);
                    }
                    else
                    {
                        $message = _('Could not apply the promo code to your account.') . '<br/>';
                    }
                }
                else
                {
                    $message = _('Your account cannot accept a promo code, please contact the support team.') . '<br/>';
                }
            }
            else
            {
                $message = _('This promo code cannot be used in conjunction with your renewing In-App Purchase subscription.<br/>The promo code is still valid and may be redeemed when your auto-renewing in app purchase has expired.<br/>');
            }
		}
		else
		{
			$message = _('This is not a valid promo code.') . '<br/>';
		}
	}
    if($errorMessage !== ''): ?>
        <script type="text/javascript">displayApplyPromoCodeModal('<?php echo $message; ?>');</script>
    <?php
    endif;
}


?>


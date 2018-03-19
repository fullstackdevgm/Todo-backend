<link rel="stylesheet" type="text/css" href="<?php echo TP_CSS_PATH_APP_SETTINGS; ?>" />
<div class="setting_options_container">
	<div id="settings_inner_content"></div>
</div>
<!--	<!--<h3>Purchased Subscriptions</h3>
<!--	
<!--	<div id="owned_subscriptions_wrapper"></div>
<!--	
<!--	<div id="add_subscription_button_wrapper"></div>
<!--	
<!--	<div id="purchase_pricing_container" style="display:none;">
<!--		<div id="subtotal_wrapper">
<!--			<span id="subtotal_label">Subtotal : </span>
<!--			<span id="subtotal_amount">0.00</span>
<!--			<span></span>
<!--		</div>
<!--		<div id="discount_wrapper">
<!--			<span id="discount_label">Discount : </span>
<!--			<span id="discount_amount">0.00</span>
<!--			<span id="discount_percent">(0%)</span>
<!--		</div>
<!--		<div id="total_price_wrapper">	
<!--			<span id="total_label">Total : </span>
<!--			<span id="total_amount">0.00</span>
<!--			<span></span>
<!--		</div>
<!--	</div>
<!--	
<!--	<div id="pricing_payment_info_wrapper" style="display:none;">
<!--		<div id="discounts_table">
<!--			<h3>Team Discounts</h3>
<!--			<div>
<!--				<span class="label">5 - 9 subscriptions: </span>
<!--				<span class="value">5%</span>
<!--			</div>
<!--			<div>
<!--				<span class="label">10 - 19 subscriptions: </span>
<!--				<span class="value">10%</span>
<!--			</div>
<!--			<div>
<!--				<span class="label">20+ subscriptions: </span>
<!--				<span class="value">15%</span>
<!--			</div>
<!--		</div>	
<!--		<div id="payment_info_container">
<!--				<data id="pricing_table_wrapper" style="display:none;"></data>
<!--				<h3>Payment Information</h3>
<!--				<div class="payment_info_option">
<!--					<label>Name on Card</label>
<!--					<input id="name_on_card" type="text" />
<!--				</div>
<!--				<div class="payment_info_option">
<!--					<label>Credit Card Number</label>
<!--					<input id="cc_number" type="text" />
<!--					<img src="<?php echo TP_IMG_PATH_CC_LOGOS; ?>" />
<!--				</div>
<!--				<div class="payment_info_option">
<!--					<label>Expiration Date</label>
<!--					<select id="exp_date_month" ></select>
<!--					<select id="exp_date_year" ></select>
<!--				</div>
<!--				<div class="payment_info_option">
<!--					<label id="security_code_label">Security code</label>
<!--					<input id="cvc" type="text" />
<!--					<span id="cvc_help_wrapper" > <!--onmouseover="displayHelpElementWithId('cvc_help', 'inline-block')" onmouseout="hideHelpElementWithId('cvc_help')">-->
<!--						<img id="cvc_question_mark" src="<?php echo TP_IMG_PATH_CC_QUESTION; ?>" />
<!--						<div id="cvc_help" style="opacity:0.0;">Look at the back of your card O_o</div>
<!--					</span>
<!--				</div>
<!--				<div class="payment_info_option purchace_cancel_buttons_wrapper">
<!--					<div id="cancel_renew_subscription_button" class="button" onclick="hideSubscriptionPurchaseMode()">Cancel</div>	
<!--					<div id="purchase_subscription_button" class="button disabled" >Purchase</div>	
<!--				</div>
<!--				<span id="lock_img_wrapper">
<!--					<img id="lock_img" src="<?php echo TP_IMG_PATH_CC_QUESTION; ?>" />
<!--					<div id="lock_help">Yes, this is a secure transaction</div>
<!--				</span>
<!--		</div>	
<!--	</div>	
<!--	<div id="main_subscription_buttons_wrapper">	
<!--		<span id="renew_subscription_button" class="button" onclick="displaySubscriptionPurchaseMode()">Add/Renew Subscription</span>
<!--	</div>	
<!--	
<!--	<h3 id="member_subscription_header" style="display:none;" >Member Subscription</h3>
<!--	<div id="member_subscription_wrapper"></div>
<!--	<div id="billing_info_wrapper" style="display:none;">
<!--		<h3>Billing Information</h3>
<!--		<div id="cc_setting_option" class="setting_option" onclick="displaySettingDetails_('cc_setting')">
<!--			<span class="setting_label">Credit Card</span>
<!--			<span id="cc_setting_value" class="setting_value"></span>
<!--			<span id="cc_setting_details" class="setting_value" style="display:none;">
<!--				<ul class="simple_list no_margins">
<!--					<li>
<!--						<span class="fixed_width">Name on Card </span><input id="update_name_on_card" type="text" value="" />
<!--					</li>
<!--					<li>
<!--						<span class="fixed_width">Credit Card Number</span><input id="update_cc_number" type="text" value="" />
<!--					</li>
<!--					<li>
<!--						<span class="fixed_width">Expiration Date </span><select id="update_exp_date_month"></select><select id="update_exp_date_year"></select>
<!--					</li>
<!--					<li>
<!--						<span class="fixed_width">Security Code </span><input id="update_cc_cvc" type="text" value="" />
<!--						<span id="cvc_help_wrapper"> <!--onmouseover="displayHelpElementWithId('cvc_help', 'inline-block')" onmouseout="hideHelpElementWithId('cvc_help')">-->
<!--							<img src="<?php echo TP_IMG_PATH_CC_QUESTION; ?>" id="cvc_question_mark">
<!--							<div style="opacity:0.0;" id="cvc_help">Look at the back of your card O_o</div>
<!--						</span>
<!--					</li>
<!--				</ul>
<!--				<div id="cc_button_wrapper" class="buttons_wrapper">
<!--					<span class="button" onclick="hideSettingsDetails_('cc_setting', event)">Cancel</span>
<!--					<span class="button" onclick="updateCreditCardInfoSetting()">Update</span>
<!--				</div>
<!--			</span>
<!--		</div>
<!--		<!--
<!--		<div class="info_container">
<!--		
<!--			<div class="setting_option">
<!--							</div>	
<!--		</div>
<!--		
<!--		-->
<!--	</div>
<!--
<!--	
<!--</div>-->
<?php
	//set up assign subscription modal
	/*$submitModalButton = new PBButton;
	$submitModalButton->setLabel("Assign");
	$submitModalButton->setOnClick("assignTeamSubscription()");
	$submitModalButton->setId("assign_subscription_button");
	
	$modalBody  = '	<div id="primary_assign_options">';
	$modalBody .= ' 	<label for="me_option" ><input id="me_option" type="radio" name="primary_assign_option" checked onclick="assignOptionSelected(\'me\')" />To me</label>';
	$modalBody .= ' 	<label for="friend_option" ><input id="friend_option" type="radio" name="primary_assign_option" onclick="assignOptionSelected(\'friend\')" />To someone else</label>';
	$modalBody .= '	</div>';
	$modalBody .= '	<div id="secondary_assign_options">';
	$modalBody .= '		<div id="fb_friend_option" onclick="friendOptionSelected(\'fb\')">Facebook friend</div>';
	$modalBody .= '		<div id="other_friend_option" onclick="friendOptionSelected(\'other\')">Other</div>';
	$modalBody .= '	</div>';
	$modalBody .= '	<div id="secondary_assign_options_body">';
	$modalBody .= '	</div>';
	
	$modalWindow = array(	"id"=>"assign_subscription_modal",
			"title"=>"Assign Subscription",
			"body"=> $modalBody,
			"action_button"=>$submitModalButton, 
			"cancel_button_label"=>"Cancel"
			);
			
    //We're not doing modal windows in php any more. Implement it in javascript.
	include('TodoOnline/content/???.php');
	
	//set up payment confirmation modal
	$submitModalButton = new PBButton;
	$submitModalButton->setLabel("Confirm");
	$submitModalButton->setOnClick("confirmPurchase()");
	$submitModalButton->setId("confirm_purchase_button");
	
	$modalBody  = '';
	
	$modalWindow = array(	"id"=>"confirm_purchase_modal",
							"title"=>"Confirm Purchase",
							"body"=> $modalBody,
							"action_button"=>$submitModalButton, 
							"cancel_button_label"=>"Cancel"
							);
			
    //We're not doing modal windows in php any more. Implement it in javascript.
	include('TodoOnline/content/???.php');
	*/
?>


<script type="text/javascript" src="https://js.stripe.com/v1/"></script>
<script type="text/javascript">
     Stripe.setPublishableKey(<?php echo "'".APPIGO_STRIPE_PUBLIC_KEY."'";?>);
</script>

<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>
<!--<script type="text/javascript" src="<?php echo TP_JS_PATH_DATE_PICKER; ?>" ></script>-->
<script>
buildSubscriptionHtmlLayout();
loadPremiumAccountInfo ();
loadSubscriptionSettingContent();
</script>
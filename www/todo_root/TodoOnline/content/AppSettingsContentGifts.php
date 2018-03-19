<style>
	h2 {color: black;margin:0 20px 30px;font-weight: bold}

	.content_block {width:660px;margin:0 auto 30px;background:white;border:1px solid gray;border-radius:8px;padding:20px 10px}
	.labeled_input {margin-bottom: 6px}
	.err_msg {margin-left: 80px;color:red;font-weight: bold;font-size: .9rem;display: none}
	.err_msg.resend{margin-left:60px}
	
	.action_link {font-size: .9rem;visibility: hidden;cursor: pointer;font-weight: bold}
	.action_link:hover {text-decoration: underline}
	.setting.purchase:hover .action_link{visibility: visible}
	.gift_order_details:hover .action_link{visibility: visible}
	
	.gifts_history {width: 640px; display:none;box-sizing: border-box}
	.gift_history {width:100px}
	.gift_history.date {width:130px;text-align: center}
	.gift_history.resend {width: 50px}
	.gift_history.gift{width: 50px}
	.gift_history.view_gift {width: 40px}

    .setting_options_container {
        overflow: visible;
        overflow-x: visible;
    }

    #gifts_content .cvc_help_image_wrap {
        width: 345px;
        margin: 0 0 20px 0;
    }

    #gifts_content .cvc_help_item {
        margin: 0;
    }
    
    .setting_options_container .setting.purchase{
        min-width: inherit;
    }
    .setting_options_container .content_block{
        box-sizing: border-box;
        width: 640px;
    }
    .gift_promo_code.banner {
        cursor: pointer;
        background-repeat: no-repeat;
        max-width: 640px;
        height: 280px;
        border: 1px solid gray;
        border-radius: 9px;
        margin: 30px auto;
        background-image: url('<?php echo TP_IMG_GIFT_CODE_SHOWCASE_ZH_CN; ?>');
    }

    .gift_promo_code.banner .info {
        max-width: 280px;
        text-align: center;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-family: Roboto, sans-serif;
        color: #333;
    }

    .gift_promo_code.banner .info h2 {
        font-size: 42px;
        font-weight: 100;
        line-height: 1;
        font-family: Roboto, sans-serif;
        margin: 0;
    }

    .gift_promo_code.banner .info p {
        font-size: 19px;
        color: #333;
        line-height: 1.3;
        font-weight: 300;
    }

    .gift_promo_code.banner .info .btn-default {
        width: 60px;
        min-width: 60px;
        margin: 20px 0;
    }

    .gift_promo_code.banner .info p {
        margin: 15px 0 0 0;
    }

    .gift_promo_code.banner .info p.small {
        margin: 0;
        font-size: 12px;
        width: 110px;
    }

	.gift_promo_col {display: inline-block;width: 49%;vertical-align: top}
	.gift_promo_col label {text-align: right;display: inline-block;min-width: 47px}
	.gift_promo_col input[type="text"]{width: 140px;min-width: 120px}
	.gift_promo_col textarea {height:130px;width:220px}
	.gift_promo_col .char_count {font-size: .9rem;margin-top: 4px;text-align: right;padding-right: 4px}
	
	.view_gift .button {width: 120px}
	
	
	.gift_order_details {width: 90%;margin:0 auto}
	.gift_order_details.total {margin-top: 20px}
	.gift_order_details.add_more {margin:20px auto}
	
	.gift_order_detail {display: inline-block;text-align: center;margin-bottom: 6px}
	.gift_order_detail.header {font-weight: bold;background: none;margin-bottom: 20px}
	.gift_order_detail.price.header:before {content: ''}
    .gift_order_detail.description {width: 390px;vertical-align: text-top;text-align: left}
    .gift_order_detail.price {width: 150px}		
    .gift_order_detail.price:before {content: '$'}
    .gift_order_detail.description.total {text-align: right;font-weight: bold}
	
	.credit_card {width:400px;margin:0 auto}
	.purchase.button {width:120px;margin: 20px auto;display: block}
	
	.add_code.button {	color: white;
						background-image: -webkit-linear-gradient(top, rgb(113,131,255) 0%, rgb(31,32,152) 100%); 
						background-image:    -moz-linear-gradient(top, rgb(113,131,255) 0%, rgb(31,32,152) 100%);
						background-image:     -ms-linear-gradient(top, rgb(113,131,255) 0%, rgb(31,32,152) 100%);
						background-image:      -o-linear-gradient(top, rgb(113,131,255) 0%, rgb(31,32,152) 100%);}

	@media only screen and (-webkit-min-device-pixel-ratio: 2), only screen and (min-device-pixel-ratio: 2) {
        .gift_promo_code.banner {
            cursor: pointer;
            background-repeat: no-repeat;
            width: 640px;
            height: 280px;
            border: 1px solid gray;
            border-radius: 8px;
            margin: 30px auto;
            background-image: url('<?php echo TP_IMG_GIFT_CODE_SHOWCASE_2X_ZH_CN; ?>');
        }
    }
    @media only screen and (max-width: 880px) {
        .gift_promo_code.banner {margin:30px 10px}
    }
</style>

<link href="<?php echo TP_CSS_PATH_APP_SETTINGS; ?>" type="text/css" rel="stylesheet" />
	<div class="setting_options_container" id="gifts_content">
		<div class="banner gift_promo_code" onclick="showGiftPromoCodeModal()">
            <div class="info">
                <h2><?php printf('%sTodo%s Cloud','<strong>', '</strong>'); ?></h2>
                <p><?php printf(_('Give someone %sthe gift of productivity.'), '<br>'); ?></p>

                <a href="#" class="btn-default btn-info"><?php _e('Buy Gift'); ?></a>

                <p class="small">(<?php _e('premium account one year gift code'); ?>)</p>
            </div>
        </div>
	</div>
    <div class="setting_options_container">
        <div class="content_block gifts_history" id="gifts_history"></div>
    </div>

<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>
<script type="text/javascript" src="https://js.stripe.com/v1/"></script>

<script>
    Stripe.setPublishableKey(<?php echo "'".APPIGO_STRIPE_PUBLIC_KEY."'";?>);
			
	var userSession = {};
	var useSessionStorage = window.sessionStorage ? true : false;
	
	var cardOnFile;
	var promoCodeValue = 19.99;
	var yearlySubscriptionType = 'year'; 
	var giftCodes;
	var didDisplayPaymentUI = false;
	
	createShoppingCart();
	loadGiftCodePageContent();
	
	function loadGiftCodePageContent()
	{
		var giftCodes = getGiftCodesFromShoppingCart();

        if (giftCodes != null && giftCodes.length != 0) {
            showPromoCodeCheckoutUI();
        }
        loadGiftHistory();
	};
	
	function createShoppingCart()
	{
		var shoppingCart = getShoppingCart();
		
		if (shoppingCart == null)
		{
			shoppingCart = {};
			if (useSessionStorage)
				sessionStorage.shoppingCart = JSON.stringify(shoppingCart);
			else
				userSession.shoppingCart = shoppingCart;	
		}
	};
	
	function clearShoppingCart()
	{
		var shoppingCart = {};
		
		setShoppingCart(shoppingCart);		
	}
	
	function getShoppingCart()
	{
		var shoppingCart = null;
		
		if (useSessionStorage && typeof(sessionStorage.shoppingCart) != 'undefined')
		 	shoppingCart = JSON.parse(sessionStorage.shoppingCart);
		else if (typeof(userSession.shoppingCart) != 'undefined')
			shoppingCart = userSession.shoppingCart;
			
		return shoppingCart;
	};
	
	function setShoppingCart(shoppingCart)
	{
		if (useSessionStorage)
			sessionStorage.shoppingCart = JSON.stringify(shoppingCart);
		else
			userSession.shoppingCart = shoppingCart;
	}
	
	function getGiftCodesFromShoppingCart()
	{
		var giftCodes = null;
		var shoppingCart = getShoppingCart();
		
		if (typeof(shoppingCart.giftCodes) != 'undefined')
			giftCodes = shoppingCart.giftCodes;
			
		return giftCodes;		
	};
	
	function setGiftCodesInShoppingCart(giftCodes)
	{
		var shoppingCart = getShoppingCart();
		
		if (shoppingCart == null)
			createShoppingCart();
		
		shoppingCart.giftCodes = giftCodes;
		
		setShoppingCart(shoppingCart);
	};
	
	function addGiftCodeToShoppingCart(giftCode)
	{
		var giftCodes = getGiftCodesFromShoppingCart();
		
		if (giftCodes == null)
			giftCodes = [];

		giftCodes.push(giftCode);
		
		setGiftCodesInShoppingCart(giftCodes);
	};
	
	function showGiftPromoCodeModal()
	{
		var doc = document;
        var headerHTML = '<?php _e('Gift Todo Cloud'); ?>';
		var bodyHTML = '';
		var footerHTML = '';
		var username = doc.getElementById('userName').value;
		var names = username.split(' ');
		var firstName = names[0];	
		
		bodyHTML += '<div style="width:480px" >';
		bodyHTML += '	<p style="margin-top:0"><?php _e('Buy a one year Todo Cloud Premium Account Gift Code ($19.99 USD)'); ?></p>';
		bodyHTML += '	<hr style="margin:16px auto"/> ';
		bodyHTML += ' 	<div class="gift_promo_col">';
		bodyHTML += '		<div class="labeled_input">';
		bodyHTML += '			<label><?php _e('From'); ?> </label>';
		bodyHTML += '			<input id="from_name" type="text" placeholder="<?php _e('your name'); ?>"  value="' + firstName + '"/>';
		bodyHTML += '		</div>';
		bodyHTML += '		<div class="labeled_input">';
		bodyHTML += '			<label><?php _e('To'); ?> </label>';
		bodyHTML += '			<input id="to_name" type="text" placeholder="<?php echo str_replace('&lsqou;', '\\' . '\'',_('your friend&lsqou;s name')); ?>"  />';
		bodyHTML += '		</div>';
		bodyHTML += '		<div class="labeled_input">';
		bodyHTML += '			<label style="font-weight:bold;margin-top:14px"><?php _e('Delivery method'); ?></label>';
		bodyHTML += '		</div>';
		bodyHTML += '		<div class="labeled_input" >';
		bodyHTML += '			<input id="delivery_email" type="radio" name="delivery_method" value="true" checked/>';
		bodyHTML += '			<label for="delivery_email" ><?php _e('Email to'); ?> </label>';
		bodyHTML += '			<input id="to_email" type="text" placeholder="<?php echo str_replace('&lsqou;', '\\' . '\'',_('your friend&lsqou;s name')); ?>"  style="width:120px"/>';
		bodyHTML += '			<div id="to_email_err" class="err_msg"></div>';
		bodyHTML += '		</div>';
		bodyHTML += '		<div class="labeled_input">';
		bodyHTML += '			<input id="delivery_manual" type="radio" value="false" name="delivery_method"/>';
		bodyHTML += '			<label for="delivery_manual"><?php _e('I&#39;ll send it myself'); ?></label>';
		bodyHTML += '		</div>';
		bodyHTML += '	</div>';
		bodyHTML += ' 	<div class="gift_promo_col">';
		bodyHTML += '		<div><?php _e('Message (optional - 255 characters):'); ?></div>';
		bodyHTML += '		<textarea id="to_message" placeholder="<?php _e('your message'); ?>"  maxlength="255"></textarea>';
		bodyHTML += '		<div id="msg_char_count" class="char_count">&nbsp;</div>';
		bodyHTML += '	</div>';
		bodyHTML += '</div>';
		
		footerHTML += '<div class="action_link" style="float:left"><a href="http://help.appigo.com/entries/22705872-faq-todo-pro-gift-codes" target="_blank" style="visibility:visible"><?php _e('Gift Code FAQ'); ?></a></div>';
		footerHTML += '<div class="button" onclick="hideModalContainer()"><?php _e('Cancel'); ?></div>';
		footerHTML += '<div class="button disabled" id="addItemToCartBtn"><?php _e('Continue'); ?></div>';
		
		displayModalContainer(bodyHTML, headerHTML, footerHTML);
		doc.getElementById('modal_overlay').onclick = null;
		
		doc.getElementById('to_name').focus();
		doc.getElementById('to_name').bindEvent('keyup', shouldEnableAddPromoCodeButton, false);
		doc.getElementById('from_name').bindEvent('keyup', shouldEnableAddPromoCodeButton, false);
		doc.getElementById('to_message').bindEvent('keyup', function(event){shouldLimitCharsInMessage(event)}, false);
		doc.getElementById('to_email').bindEvent('keyup', shouldEnableAddPromoCodeButton, false);
		doc.getElementById('delivery_email').bindEvent('click', shouldEnableAddPromoCodeButton, false);
		doc.getElementById('delivery_email').bindEvent('click', shouldEnableMessageTextarea, false);
		doc.getElementById('delivery_manual').bindEvent('click', shouldEnableAddPromoCodeButton, false);
		doc.getElementById('delivery_manual').bindEvent('click', shouldEnableMessageTextarea, false);
		doc.getElementById('to_email').bindEvent('keyup', checkForPlusCharacter, false);
	};

	function showPromoCodeCheckoutUI()
	{
		hideModalContainer();
		
		var doc = document;
		var container = doc.getElementById('gifts_content');
		var html = '';
			html += '<div id="shopping_cart_items" class="content_block"></div>';
			html += '<div id="payment_info" class="content_block"></div>';
		
		container.innerHTML =  html;
		
		loadShoppingCartContent();
		loadPaymentUI();
	};
	
	function loadShoppingCartContent()
	{
		var giftCodes = getGiftCodesFromShoppingCart();
		var container = document.getElementById('shopping_cart_items');
		var html = '';
		
			html += '	<h2><?php _e('Order Details'); ?></h2>';
			html += '		<div class="gift_order_details">';
			html += '			<div class="gift_order_detail description header"><?php _e('Description'); ?></div>';
			html += '			<div class="gift_order_detail price header"><?php _e('Price'); ?></div>';
			html += '			<div class="gift_order_detail remove"></div>';
			html += '		</div>';
			
			//items
			for (var i = 0; i < giftCodes.length; i ++)
			{
				html += '		<div class="gift_order_details">';
				html += '			<div class="gift_order_detail description ellipsized"><?php _e('One year premium account gift code for');?> ' + giftCodes[i].recipient_name + '</div>';
				html += '			<div class="gift_order_detail price">19.99</div>';
				html += '			<div class="gift_order_detail remove"><div class="action_link" onclick="removeItemFromCart(\'' + giftCodes[i].guid + '\')"><?php _e('remove'); ?></div></div>';
				html += '		</div>';
			}
			
            //Bug 7240 - round this to two decimals
            var total = giftCodes.length * promoCodeValue;
            total = Math.round(total*100)/100;
        
				    //total
			html += '		<div class="gift_order_details total">';
			html += '			<div class="gift_order_detail description total"><?php _e('TOTAL'); ?></div>';
			html += '			<div class="gift_order_detail price total">' + currencyFormatted(total) + '</div>';
			html += '		</div>';
					//add more button
			html += '		<div class="gift_order_details add_more">';
			html += '			<div class="gift_order_detail description">';
			html += '				<div class="button add_code" onclick="showGiftPromoCodeModal()"><?php _e('Add another gift code'); ?></div>';
			html += '			</div>';
			html += '		</div>';
		
		container.innerHTML = html;
	};
	
	function loadNewCardUI()
	{
		cardOnFile = null;
		
		var doc = document;
		var purchaseBtn = doc.getElementById('purchase_button');
		doc.getElementById('credit_card').innerHTML = getNewCardHtml();
		
		loadExpirationMonthOptions('exp_date_month');
		loadExpirationYearOptions('exp_date_year');
		doc.getElementById('cc_number').setAttribute('onkeyup', 'shouldEnablePurchaseButton()');
		doc.getElementById('name_on_card').setAttribute('onkeyup', 'shouldEnablePurchaseButton()');
		doc.getElementById('cvc').setAttribute('onkeyup', 'shouldEnablePurchaseButton,()');
		
		purchaseBtn.setAttribute('class', 'button disabled purchase');
		purchaseBtn.setAttribute('onclick','');
	};
	
	function loadPaymentUI()
	{
		var doc = document;
		var container = doc.getElementById('payment_info');
		var html = '';
					//payment info
			html += '		<h2><?php _e('Payment Information'); ?></h2>';
			html += '		<div id="credit_card" class="credit_card">';
			html += '			<center><?php _e('Retrieving information...'); ?><br/><br/><span class="progress_indicator" style="display:inline-block"></span></center>;'
			html += '		</div>';
			html += '		<div class="button disabled purchase" id="purchase_button"><?php _e('Purchase'); ?></div>';
		
		container.innerHTML = html;
			
		var paymentEl = doc.getElementById('credit_card');
		
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
	            try 
	            {	
		            var response = JSON.parse(ajaxRequest.responseText);
	        
	                if(response.success && typeof(response.billing_info) != 'undefined')
	                {
	                	var purchaseBtn = doc.getElementById('purchase_button');
	                	
	                	cardOnFile = response.billing_info;
	                	
	                	paymentEl.innerHTML = getPreviousCardHtml();
			
						doc.getElementById('new_card_tab').bindEvent('click', loadNewCardUI, false);
						
						purchaseBtn.setAttribute('class', 'button purchase');
						purchaseBtn.setAttribute('onclick', 'purchaseItems()');
	                }
	                else
	                {
		                paymentEl.innerHTML = getNewCardHtml();
		                
		                loadExpirationMonthOptions('exp_date_month');
		                loadExpirationYearOptions('exp_date_year');
		                
		                doc.getElementById('cc_number').setAttribute('onkeyup', 'shouldEnablePurchaseButton()');
		                doc.getElementById('name_on_card').setAttribute('onkeyup', 'shouldEnablePurchaseButton()');
		                doc.getElementById('cvc').setAttribute('onkeyup', 'shouldEnablePurchaseButton()');
	                }
	            }
	            catch(e)
	            {
	                displayGlobalErrorMessage("<?php _e('Unknown response from server: '); ?>" + e);
	            }
			}
		}
		
		var params = 'method=getBillingInfoForCurrentUser';
		ajaxRequest.open("POST", "." , false);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);	
	};
	
	function loadGiftHistory()
	{
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
	            try 
	            {	
	            	var doc = document;
	            		
			        var response = JSON.parse(ajaxRequest.responseText);
	        
	                if(response.success)
	                {
	                    var items = response.gift_codes;
	                    var html = '';
	            			html += '<h2 style="color:black"><?php _e('Gift Code History'); ?></h2>';

	                    giftCodes = items;
	                    
	                    var isRedeemed = false;
	                    
	                    if (items.length > 0)
	                    {
		            		html += '<div class="setting purchase header">';
		            		html += '    <div class="gift_history to ellipsized"><?php _e('To'); ?></div>';
		            		html += '    <div class="gift_history gift ellipsized"><?php _e('Gift'); ?></div>';
		            		html += '    <div class="gift_history date ellipsized"><?php _e('Purchase Date'); ?></div>';
		            		html += '    <div class="gift_history date ellipsized"><?php _e('Redeemed'); ?></div>';
		            		html += '    <div class="gift_history resend"></div>';
		            		html += '    <div class="gift_history view_gift"></div>';
		            		html += '</div>';

							for (var i = 0; i < items.length; i ++)	                    	
	                    	{
	                    		var item = items[i];
	                    	
	                    		var status = '';
	                    		
	                    		if (typeof(item.consumption_timestamp) == 'undefined' || item.consumption_timestamp == 0)
	                    		{
	                    			status = '-';
	                    			isRedeemed = false;
	                    		}
	                    		else
	                    		{
	                    			status = '<span title="' + displayHumanReadableDate(item.consumption_timestamp, false, true) + '" >' + displayHumanReadableDate(item.consumption_timestamp, false, false) + '</span>';
	                    			isRedeemed = true;
	                    		}
	                    		
			                    html += ' <div class="setting purchase ">';
								if (item.recipient)
								{
									html += '     <div class="gift_history to ellipsized">' + item.recipient + '</div>';
								}
								else
								{
									html += '     <div class="gift_history to ellipsized">-</div>';
								}
								if (item.duration == "12")
								{
									html += '     <div class="gift_history gift ellipsized"><?php _e('1 year'); ?></div>';
								}
								else
								{
									html += '     <div class="gift_history gift ellipsized">' + item.duration + ' <?php _e('mo.'); ?></div>';
								}
					            html += '     <div class="gift_history date" title="' + displayHumanReadableDate(item.purchase_date, false, true) + '" >' + displayHumanReadableDate(item.purchase_date, false, false) + '</div>';
					            html += '     <div class="gift_history date">' + status + '</div>';
					           
					            if (isRedeemed == false)
					            {
					            	html += '     <div class="gift_history resend"><div class="action_link" onclick="showResendGiftUI(\'' + i + '\')"><?php _e('Resend'); ?></div></div>';
					            	html += '     <div class="gift_history view_gift"><div class="action_link" onclick="showPromoCode(\'' + item.giftcode_link + '\')"><?php _e('View'); ?></div></div>';
					            }
					            
					            html += ' </div>';
					        }
					        
                            doc.getElementById('gifts_history').setAttribute('style', 'display:block');
					        doc.getElementById('gifts_history').innerHTML = html;
	                    }
	                    else
	                    {
		                    doc.getElementById('gifts_history').setAttribute('style', 'display:none');
	                    }
	                }
	                else
	                {
	                    if(response.error == "authentication")
	                    {
	                        //make the user log in
	                        history.go(0);
	                    }
	                    else
	                    {
	                        if(response.error)
	                            displayGlobalErrorMessage(response.error);
	                        else
	                            displayGlobalErrorMessage("<?php _e('Unable to retrieve gift history'); ?>");
	                    }
	                }
	            }
	            catch(e)
	            {
	                displayGlobalErrorMessage("<?php _e('Unknown response from server: '); ?>" + e);
	            }
			}
		}
		
		var params = 'method=getGiftCodesForCurrentUser';
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);	
	};
	
	function addPromoCodeToCart()
	{
		var doc = document;
				
		var giftCode = {};
			giftCode.recipient_name= doc.getElementById('to_name').value;
        
            if(!doc.getElementById('delivery_manual').checked)
                giftCode.recipient_email = doc.getElementById('to_email').value;
			giftCode.sender_name = doc.getElementById('from_name').value;
			giftCode.message = doc.getElementById('to_message').value;
			giftCode.subscription_type = yearlySubscriptionType;
			giftCode.guid = guid();
			
		addGiftCodeToShoppingCart(giftCode);	
		
		if (!didDisplayPaymentUI)
		{
			showPromoCodeCheckoutUI();
			didDisplayPaymentUI = true;
		}
		
		loadShoppingCartContent();
		shouldEnablePurchaseButton();
		hideModalContainer();
	};
	
	function removeItemFromCart(guid)
	{
		var giftCodes = getGiftCodesFromShoppingCart();
		
		for (var i = 0; i < giftCodes.length; i++)
		{
			if (giftCodes[i].guid == guid)
			{
				giftCodes.splice(i, 1);
				break;
			}
		}
		
		setGiftCodesInShoppingCart(giftCodes);
		
		//shoppingCart.splice(itemIndex, 1);
		loadShoppingCartContent();
		shouldEnablePurchaseButton();
	};
	
	function shouldEnableAddPromoCodeButton()
	{
		var doc = document;
		var toName = doc.getElementById('to_name').value.trim();
		var fromName = doc.getElementById('from_name').value.trim();
		var addButton = doc.getElementById('addItemToCartBtn')
		var toEmail = doc.getElementById('to_email').value.trim();
		var radioBtns = doc.getElementsByName('delivery_method');
		var sendEmail = '';
		
		for (var i = 0; i < radioBtns.length; i++)
		{
			if (radioBtns[i].checked)
				sendEmail = radioBtns[i].value;
		}
		
		var shouldEnable = false;
		
		if (toName.length != 0 && fromName.length != 0)
		{
			if (sendEmail == 'true' && isValidEmailAddress(toEmail) && toEmail.indexOf('+') == -1)
			{
				shouldEnable = true;
			}
			else if (sendEmail == 'false')
			{
				shouldEnable = true;
			}
			else
			{
				shouldEnable = false;
			}
		}

		if (shouldEnable)
		{
			addButton.setAttribute('class', 'button');
			addButton.bindEvent('click', addPromoCodeToCart, false);
		}
		else
		{
			addButton.setAttribute('class', 'button disabled');
			addButton.unbindEvent('click', addPromoCodeToCart, false);
		}
	};
	
	function shouldLimitCharsInMessage(event)
	{
		var doc = document;
		var msgEl = doc.getElementById('to_message');
		var msg = msgEl.value;
		var countEl = doc.getElementById('msg_char_count');	
		var charLimit = 255;
		
			
		if (msg.length > charLimit)
		{
			event.preventDefault();
			msgEl.value = msg.substring(0, charLimit + 1);
		}	
		else
		{
			countEl.innerHTML = (charLimit - msg.length) + ' characters left';
		}		
	};
	
	function shouldEnablePurchaseButton()
	{
		var enable = false;
		var doc = document;
		var giftCodes = getGiftCodesFromShoppingCart();
		var purchaseBtn = doc.getElementById('purchase_button');
		
		if (giftCodes.length > 0)
		{	
			if(cardOnFile)
			{
				enable = true;
			}
			else
			{
				var ccNumber = doc.getElementById('cc_number').value;
				var ccName = doc.getElementById('name_on_card').value;
				var ccCvc = doc.getElementById('cvc').value;
				
				if (ccNumber.length > 0 && ccName.length > 0 && ccCvc.length > 2)
				{
					enable = true;
				}
			}
		}
		
		if (enable)
		{
			purchaseBtn.setAttribute('class', 'button purchase');
			purchaseBtn.setAttribute('onclick', 'purchaseItems()');
		}
		else
		{
			purchaseBtn.setAttribute('class', 'button disabled purchase');
			purchaseBtn.setAttribute('onclick', '');
		}
	};
	
	function purchaseItems()
	{
		var doc = document;
		var purchaseBtn = doc.getElementById('purchase_button');
			purchaseBtn.innerHTML = labels.processing+'... <span class="progress_indicator" style="display:inline-block"></span>';
			purchaseBtn.setAttribute('class', 'button purchase');
			purchaseBtn.setAttribute('onclick', '');
			
	    
	    displayModalOverlay();
	    doc.getElementById('modal_overlay').onclick = null;    
	        
		if(cardOnFile)
		{
			processGiftsPurchase(200, null);	
		}
		else
		{
	        doc.getElementById('modal_overlay').onclick = null;
	    
			var objectForStripe = {
				name : doc.getElementById('name_on_card').value,
		        number: doc.getElementById('cc_number').value,
		        cvc: doc.getElementById('cvc').value,
		        exp_month: doc.getElementById('exp_date_month').value,
		        exp_year: doc.getElementById('exp_date_year').value};
		        
			Stripe.createToken(objectForStripe, processGiftsPurchase);
		}	
	};
	
	function processGiftsPurchase(status, response)
	{
		var doc = document;
		var giftCodes = getGiftCodesFromShoppingCart();
		
		//clear all error messages
		if (!cardOnFile)
		{
			doc.getElementById('cc_number_error').setAttribute('style', '');
			doc.getElementById('exp_month_error').setAttribute('style', '');
			doc.getElementById('cvc_error').setAttribute('style', '');
			doc.getElementById('card_error').setAttribute('style', '');
		}
			
		if (status == 200)
		{
            //Bug 7240 - round this to two decimals
			var totalCharge = giftCodes.length * promoCodeValue;
            totalCharge = Math.round(totalCharge*100)/100;
			
			if (!cardOnFile)
			{
				var nameOnCard = doc.getElementById('name_on_card').value;
				var cardNumber = doc.getElementById('cc_number').value.substring(12,16);
				var expDate = doc.getElementById('exp_date_month').value + '/' + doc.getElementById('exp_date_year').value;
				var stripeToken = response.id;
			}
		
			var ajaxRequest = getAjaxRequest(); 
			if(!ajaxRequest)
				return false;
		
			ajaxRequest.onreadystatechange = function()
			{
				if(ajaxRequest.readyState == 4)
				{
		            try
		            {
		            	var purchaseBtn = doc.getElementById('purchase_button');
		            	var responseJSON = JSON.parse(ajaxRequest.responseText);
		                
		                if(responseJSON.success)
		                {
		                	clearShoppingCart();
		                	
		                	var bodyHTML = '';
							    bodyHTML += '<?php _e('Thank you for your purchase. We&#39;ve emailed you a receipt.'); ?>';
							   
							var headerHTML = '<?php _e('Thank You!'); ?>';
							
							var footerHTML = '';
								footerHTML += '<div class="action_link" style="float:left"><a href="http://help.appigo.com/entries/22705872-faq-todo-pro-gift-codes" target="_blank" style="visibility:visible"><?php _e('Gift Code FAQ'); ?></a></div>';
							    footerHTML += '<div class="button" id="success_gift_purchased_ok_button" ><?php _e('Ok'); ?></div>';
							    
							displayModalContainer(bodyHTML, headerHTML, footerHTML);  
							doc.getElementById('modal_overlay').onclick = null;
							
							doc.getElementById('success_gift_purchased_ok_button').bindEvent('click', function(){	
								history.go(0);
							}, false);
							
		                }
		                else
		                {
		                    if(responseJSON.error == "authentication")
		                        history.go(0);
		                    else
		                    {
		                        if(responseJSON.error)
		                            displayGlobalErrorMessage(responseJSON.error);
		                        else
		                        	displayGlobalErrorMessage(responseJSON.errdesc);
                                
                                hideModalOverlay();
		                    }
		                }
		                
		                purchaseBtn.innerHTML = '<?php _e('Purchase'); ?>';
		                purchaseBtn.setAttribute('class', 'button purchase');
		                purchaseBtn.setAttribute('onclick', 'purchaseItems()');
		            }
		            catch(err)
		            {   
		                displayGlobalErrorMessage("<?php _e('unknown response from server: '); ?>" + err);
		                hideModalOverlay();
		            }
				}
			}

			var params = '';

				params = 'method=purchaseGiftCodes&totalCharge=' + totalCharge + '&giftCodes=' + JSON.stringify(giftCodes);
				
			if (cardOnFile)
				params += '&last4=' + cardOnFile.last4;
			else 	
				params += '&stripeToken=' + stripeToken;
			
			ajaxRequest.open("POST", ".", true);
		    
		    //Send the proper header information along with the request
		    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			ajaxRequest.send(params);
		}
		else
		{
			hideModalOverlay();
	//		console.log('unlocking UI - epic fail due to error in stripe returned status');
			var error = response.error;
			var errorCode = error.code;
			var statusEl = null;
			var purchaseBtn = doc.getElementById('purchase_button');
			
			switch (errorCode)
			{
				case 'invalid_number':
				case 'incorrect_number':
					statusEl = doc.getElementById('cc_number_error');
					break;
				case 'invalid_expiry_month':
					statusEl = doc.getElementById('exp_month_error');
					break;	
				case 'invalid_cvc':
					statusEl = doc.getElementById('cvc_error');
					break;
				default:
					statusEl = doc.getElementById('card_error');
					break;
			}

            if (response.error && response.error.type == 'card_error') {
                statusEl.innerHTML = stripeErrorMessages[response.error.code];
            } else {
                statusEl.innerHTML = error.message;
            }
			statusEl.style.display = 'block';
			
			purchaseBtn.innerHTML = '<?php _e('Purchase'); ?>';
		    purchaseBtn.setAttribute('class', 'button purchase');
		    purchaseBtn.setAttribute('onclick', '');
		}
	};
	
	function showPromoCode(promoCodeLink)
	{
		var bodyHTML = '';
		    bodyHTML += '<p style="width:400px"><?php _e('Link to redeem the gift code:'); ?></p>';
		    bodyHTML += '<p style="width:400px;word-wrap:break-word">' + promoCodeLink + '</p>';
		    bodyHTML += '<hr style="margin:20px auto"/>';
		    bodyHTML += '<p style="width:400px"><?php printf(_('Questions about redeeming a gift code? View the %sGift Code FAQs%s available in our Help Center.'), '<a href="http://help.appigo.com/entries/22705872-faq-todo-pro-gift-codes" target="_blank" style="font-weight:bold;color:black;text-decoration:underline">', '</a>'); ?></p>';
		   
		var headerHTML = '<?php _e('Gift Code'); ?>';
		
		var footerHTML = '';
		    footerHTML += '<div class="button" onclick="hideModalContainer()"><?php _e('Done'); ?></div>';
		    
		displayModalContainer(bodyHTML, headerHTML, footerHTML);  	
	};
	
	function shouldEnableMessageTextarea()
	{
		var doc = document;
		var deliverEmail = doc.getElementById('delivery_email');
		var msgTextarea = doc.getElementById('to_message');
		var to_email = doc.getElementById('to_email');

		if (deliverEmail.checked)
		{
			msgTextarea.removeAttribute('disabled');
            to_email.removeAttribute('disabled');
		}
		else
		{
			msgTextarea.setAttribute('disabled', 'disabled');
            to_email.setAttribute('disabled', 'disabled');
		}
	};
	
	function showResendGiftUI(codeIndex)
	{
		var doc = document;
		var giftCode = giftCodes[codeIndex];
		var message = typeof(giftCode.message) == 'undefined' ? '' : giftCode.message;
		var toEmail = typeof(giftCode.recipient_email) == 'undefined' ? '' : giftCode.recipient_email;
				
		var bodyHTML = '';
		    bodyHTML += '<div style="width:480px" >';
			bodyHTML += ' 	<div class="gift_promo_col">';
			bodyHTML += '		<div class="labeled_input">';
			bodyHTML += '			<label><?php _e('from'); ?> </label>';
			bodyHTML += '			<input id="from_name" type="text" placeholder="<?php _e('your name'); ?>"  value="' + giftCode.sender + '"/>';
			bodyHTML += '		</div>';
			bodyHTML += '		<div class="labeled_input">';
			bodyHTML += '			<label><?php _e('to'); ?> </label>';
			bodyHTML += '			<input id="to_name" type="text" placeholder="<?php echo str_replace('&lsqou;', '\\' . '\'',_('your friend&lsqou;s name')); ?>"  value="' + giftCode.recipient + '"/>';
			bodyHTML += '		</div>';
			bodyHTML += '		<div class="labeled_input" >';
			bodyHTML += '			<label><?php _e('Email to'); ?> </label>';
			bodyHTML += '			<input id="to_email" type="text" placeholder="<?php echo str_replace('&lsqou;', '\\' . '\'',_('your friend&lsqou;s name')); ?>"  value="' + toEmail + '" />';
			bodyHTML += '			<div id="to_email_err" class="err_msg resend"></div>';
			bodyHTML += '		</div>';
			bodyHTML += '	</div>';
			bodyHTML += ' 	<div class="gift_promo_col">';
			bodyHTML += '		<div><?php _e('message (optional - 255 characters):'); ?></div>';
			bodyHTML += '		<textarea id="to_message" placeholder="<?php _e('your message'); ?>"  maxlength="255">' + message + '</textarea>';
			bodyHTML += '		<div id="msg_char_count" class="char_count">&nbsp;</div>';
			bodyHTML += '	</div>';
			bodyHTML += '</div>';
		   
		var headerHTML = '<?php _e('Resend Gift Code'); ?>';
		
		var footerHTML = '';
			footerHTML += '<div class="action_link" style="float:left"><a href="http://help.appigo.com/entries/22705872-faq-todo-pro-gift-codes" target="_blank" style="visibility:visible"><?php _e('Gift Code FAQ'); ?></a></div>';
			footerHTML += '<div class="button" onclick="hideModalContainer()"><?php _e('Cancel'); ?></div>';
		    footerHTML += '<div class="button disabled" id="resendCodeBtn"><?php _e('Resend'); ?></div>';
		    
		displayModalContainer(bodyHTML, headerHTML, footerHTML); 
		
		doc.getElementById('to_email').focus();
		
		doc.getElementById('modal_overlay').onclick = null;
		doc.getElementById('to_name').bindEvent('keyup', function(){shouldEnableResendCodeButton(codeIndex)}, false);
		doc.getElementById('from_name').bindEvent('keyup', function(){shouldEnableResendCodeButton(codeIndex)}, false);
		doc.getElementById('to_message').bindEvent('keyup', function(event){shouldLimitCharsInMessage(event)}, false);
		doc.getElementById('to_email').bindEvent('keyup', function(){shouldEnableResendCodeButton(codeIndex)}, false);
		
		doc.getElementById('to_email').bindEvent('keyup', checkForPlusCharacter, false);
	};
	
	function shouldEnableResendCodeButton(codeIndex)
	{
		var doc = document;
		var resendButton = doc.getElementById('resendCodeBtn');
		
		var toName = doc.getElementById('to_name').value;
		var fromName = doc.getElementById('from_name').value;
		var toEmail = doc.getElementById('to_email').value;
		
		
		if (toName.length != 0 && fromName.length != 0 && isValidEmailAddress(toEmail))
		{
			resendButton.setAttribute('class', 'button');
			resendButton.setAttribute('onclick', 'resendGiftCode(\'' + codeIndex + '\')');
		}
		else
		{
			resendButton.setAttribute('class', 'button disabled');
			resendButton.setAttribute('onclick', '');
		}
	};
	
	function resendGiftCode(codeIndex)
	{
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
	            try 
	            {	
			        var response = JSON.parse(ajaxRequest.responseText);
	        
	                if(response.success)
	                {
	                    var bodyHTML = '';
						    bodyHTML += '<div style="width:480px" >';
							bodyHTML += '	<p style="margin-top:0"><?php _e('Your gift code was resent.'); ?></p>';
							bodyHTML += '</div>';
						   
						var headerHTML = '<?php _e('Thank You!'); ?>';
						
						var footerHTML = '';
							footerHTML += '<div class="action_link" style="float:left"><a href="http://help.appigo.com/entries/22705872-faq-todo-pro-gift-codes" target="_blank" style="visibility:visible"><?php _e('Gift Code FAQ'); ?></a></div>';
							footerHTML += '<div class="button" onclick="hideModalContainer()"><?php _e('Ok'); ?></div>';
						    
						displayModalContainer(bodyHTML, headerHTML, footerHTML); 
	                }
	                else
	                {
	                    if(response.error == "authentication")
	                    {
	                        //make the user log in
	                        history.go(0);
	                    }
	                    else
	                    {
	                        if(response.error)
	                            displayGlobalErrorMessage(response.error);
	                        else
	                            displayGlobalErrorMessage("<?php _e('Unable to resend gift code'); ?>");
	                    }
	                }
	            }
	            catch(e)
	            {
	                displayGlobalErrorMessage("<?php _e('Unknown response from server: '); ?>" + e);
	            }
			}
		}
		
		var doc = document;
		var giftCode = giftCodes[codeIndex].giftcode;
		var toName = doc.getElementById('to_name').value;
		var toEmail = doc.getElementById('to_email').value;
		var fromName = doc.getElementById('from_name').value;
		var message = doc.getElementById('to_message').value;
		
		var params = 'method=resendGiftCodeEmail&gift_code=' + giftCode + '&recipient_name=' + toName + '&recipient_email=' + toEmail + '&sender_name=' + fromName;
		
		if(message.length != 0)
			params += '&message=' + message;
			
		ajaxRequest.open("POST", "." , true);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);
	};
	
	function checkForPlusCharacter()
	{
		var doc = document;
		var email = doc.getElementById('to_email').value;
		var emailErr = doc.getElementById('to_email_err');
		
		if (email.indexOf('+') > -1)
		{
			emailErr.innerHTML = 'invalid email address';
			emailErr.setAttribute('style', 'display:block');
		}
		else
		{
			emailErr.innerHTML = '';
			emailErr.setAttribute('style', '');
		}
	};
</script>
















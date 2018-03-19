window.bindEvent('load', function(){
	calculateMainContainersSize();
    if( window.getUnreadMessagesCount ) getUnreadMessagesCount();

}, false);

window.bindEvent('resize', calculateMainContainersSize,	false);

/*----------------------------------*/
/*---------- Subscriptions ---------*/
/*----------------------------------*/

// !Subscriptions
var cardOnFile = null;
var switchToMonthly = false;
var subscriptionId = null;
var billingInfo;
var iapAutorenewing = false;

function buildSubscriptionHtmlLayout(){
	var doc = document;
	var html = '';
		
		html += '<div style="width:430px;float: left;margin-left:20px;margin-bottom:20px">';
		html += '	<div id="subs_summary" class="container subs_summary"></div>';
        html += '   <div id="subs_purchase_history" class="container subs_summary" style="display:none;"></div>';
        html += '   <div id="subs_account_type_info" class="container subs_summary" style="display:none;"></div>';
    
		html += '	<div id="subs_billing" class="container subs_billing"></div>';
		html += '	<input id="subscription_id" type="hidden" value="" />';
    	html += '	<input id="subscription_type" type="hidden" value="" />';
    	html += '</div>';
    	html += '<div class="subs_marketing_wrap">';
    	html += '	<div id="subs_marketing" class="container subs_marketing"></div>';
    	html += '	<div class="subs_marketing_dropshadow "></div>';
		html += '</div>';
	doc.getElementById('settings_inner_content').innerHTML = html;
};

function loadPremiumAccountInfo ()
{
	var features = [
		{imgClass:'share_icon', title: labels.share_lists, description: labels.delegate_tasks_to_friends},
		{imgClass:'siri_icon', title: labels.use_siri_for_tasks, description: labels.experience_hands_free_task},
		{imgClass:'informed_icon', title: labels.stay_informed, description: labels.know_immediately_when_someone},
		{imgClass:'tasks_icon', title: labels.make_advanced_projects, description: labels.use_checklists_inside_of},
		{imgClass:'updated_icon', title: labels.keep_tasks_updated, description: labels.tasks_are_updated_at_lightning}
	];
	var html = '';
		html += '<style>.premium_icon{margin: 0 20px;width:48px;height:48px;float:left}.share_icon{background-position:-96px -278px}.siri_icon{background-position:1px -324px}.informed_icon{background-position:-146px -278px}.tasks_icon{background-position:0px -278px}.updated_icon{background-position:-48px -278px}</style>';
		html += '<h2>'+labels.what_you_get_with_a_premium_account+'</h2>';
	
	for (var i = 0; i < features.length; i++)
	{
		var feature = features[i];
		
		html += '<div class="premium_feature" style="margin-bottom:24px">';
		html += '	<div class="premium_icon ' + feature.imgClass + '"></div>';
		html += '	<div class="content">';
		html += '		<h2 style="color:black;font-weight:bold">' + feature.title + '</h2>';
		html += '		<p>' + feature.description + '</p>';
		html += '	</div>';
		html += '</div>';
	}
	
	document.getElementById('subs_marketing').innerHTML = html;
};


function loadSubscriptionSettingContent()
{
	var doc = document;
    
    doc.getElementById('subs_billing').innerHTML = '';
    doc.getElementById('subs_purchase_history').style.display = 'none';
    doc.getElementById('subs_account_type_info').style.display = 'none';
    doc.getElementById('subs_summary').style.display = 'block';
    
    //We already have current info loaded, so just display it
    if(document.getElementById('subs_summary').innerHTML.length > 0)
    {
        return;
    }
	
	doc.getElementById('subs_summary').innerHTML = 	'<br/><br/><br/><br/><br/><center><div class="progress_indicator" style="display:block"></div><br/>'+labels.gathering_your_account_information;

	
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
                	var info = response.subscriptioninfo;
                	var subType = info.subscription_type;
                	var prices = info.pricing_table;
                	billingInfo = info.billing_info;
                    iapAutorenewing = info.iap_autorenewing_account;
                    
                    var downgradeHtml = '';
                    if(billingInfo)
                    {
                        if(iapAutorenewing)
                            downgradeHtml = '<label class="indent-50">- <a href="javascript:confirmRemoveBillingInfo(\'' + info.expiration_date + '\')">' + labels.remove_billing_information +'</a></label>';
                        else
                            downgradeHtml = '<label class="indent-50">- <a href="javascript:confirmDowngradRequest(\'' + info.expiration_date + '\')">' + labels.downgrade_to_a_free_account + '</a></label>';
                    }

                	var yourNextBillingString = '';
                	var yourAccountString = '';
                	
                	switchToMonthly = info.switch_to_monthly && !iapAutorenewing;
                	
                	if(billingInfo)
                	{
                		cardOnFile = {};
	                	cardOnFile.name = billingInfo.name;
	                	cardOnFile.exp_month = billingInfo.exp_month;
	                	cardOnFile.exp_year = billingInfo.exp_year;
	                	cardOnFile.type = billingInfo.type;
	                	cardOnFile.last4 = billingInfo.last4;       
	                	
                        if(!iapAutorenewing)
                        {
                            if (subType == "year")
                                yourAccountString = labels.your_premium_account_is_billed_yearly + ' ';
                            else if (subType == "month")
                                yourAccountString = labels.your_premium_account_is_billed_monthly + ' ';
                        }
                	}
                    else
                        cardOnFile = null;
                	
                	if (info.expired)
                		yourNextBillingString = labels.your_premium_account_expired_on + ' <b>' + displayHumanReadableDate(info.expiration_date, false, true) + '</b>';
                	else
                    {
                        if(iapAutorenewing)
                        {
                            if(billingInfo)
                                yourNextBillingString = labels.your_premium_account_will_be_renewed +' <b>' + displayHumanReadableDate(info.expiration_date, false, true) + '</b> '+labels.if_you_have_canceled_your_in_app_purchase;
                            else
                                yourNextBillingString = labels.your_premium_account_will_be_renewed_through_app_s +' <b>' + displayHumanReadableDate(info.expiration_date, false, true) + '</b>.';
                            
                        }
                        else
                        {
                            if(billingInfo)
                                yourNextBillingString = labels.your_premium_account_will_renew_on +' <b>' + displayHumanReadableDate(info.expiration_date, false, true) + '</b>';
                            else
                                yourNextBillingString = labels.your_premium_account_will_expire_on + ' <b>' + displayHumanReadableDate(info.expiration_date, false, true) + '</b>';
                        }
    				}	
    				var updateButtonHtml = '';
    				
                    var updateButtonTitle = labels.upgrade_renew_your_account;
                    if(iapAutorenewing)
                        updateButtonTitle = labels.switch_billing_methods;
                    
                    if(!iapAutorenewing || !billingInfo)
                    {
                        if (info.eligible)
                            updateButtonHtml += '<label class="indent-50">- <a href="javascript:loadAccountTypeInfo(\'' + subType + '\', \'' + info.new_month_expiration_date + '\', \'' + info.new_year_expiration_date + '\', \'' + prices.month + '\', \'' + prices.year + '\')">' + updateButtonTitle + '</a></label>';
                        else
                            updateButtonHtml += '<label class="indent-50">- ' + labels.youll_be_able_to_manually + ' <b>' + displayHumanReadableDate(info.eligibility_date, false, true) + '</b></label>';
    				}
                    
    				var html = '';
    					html += '<div class="breath-30"></div>';
    					html += '<h2>' + labels.your_premium_account + '</h2>';
    					html += '<p>' + yourAccountString + yourNextBillingString + '</p>';
    				 	html += updateButtonHtml;
    					html += downgradeHtml;
    					//html += updateBillingHtml;
    					html += '<label class="indent-50" >- <a href="javascript:loadPurchaseHistory()">' + labels.view_purchase_history + '</a></label>';
    					
    				setTimeout(function(){
	    				doc.getElementById('subs_summary').innerHTML = html;
	    				doc.getElementById('subscription_id').value = info.subscription_id;
	    				doc.getElementById('subscription_type').value = subType;
    				}, 1000);	
    				
                		
                }
                else if(response.error == "authentication")
                {
                     history.go(0); //make the user log in again
                }
                else
                {
                     displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	var params = "method=getSubscriptionInfo&userid=" + doc.getElementById('userId').value;
	
	ajaxRequest.open("POST", "." , true);
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);  
};

function showIAPSwitchToStripeDialog()
{
    var header = labels.switch_billing_methods ;
    var body = labels.to_switch_to_payment_by_credit+'<br>';
    body += labels.then_be_sure_to_cancel_your_in_app+'<br><br>';
    body += labels.your_credit_card_will_not_be_billed+'<br>';
    body += labels.is_canceled_and_your_premium;

    var footer = '<div class="button" onclick="hideModalContainer()">' + labels.ok + '</div>';
    
    displayModalContainer(body, header, footer);
}

function loadAccountTypeInfo(currentType, newMonthExpDate, newYearExpDate, monthPrice, yearPrice)
{
    //If the user has an IAP auto-renewing account, show a dialog explaining what will
    //happen when they enter their purchase information
    if(iapAutorenewing)
    {
        showIAPSwitchToStripeDialog();
    }

	//Monthly/Yearly options
	currentType = currentType || 'year';
	
	var monthlyActive = '';
	var yearlyActive = '';
	var total = 0.0;
	
	currentType == 'year' ? yearlyActive = ' active' : monthlyActive = ' active';
	
	if(switchToMonthly && currentType == 'month')
	{
	}
	else
	{
		total = (currentType == 'year') ? yearPrice : monthPrice;
	}
	 
	var doc = document;
	
	var monthlyHtml = '';
		monthlyHtml += '<label class="time">' + labels.monthly + '</label>';
		monthlyHtml += '<label class="price">$ ' + monthPrice + '</label>';
	if (newMonthExpDate)	
		monthlyHtml += '<label class="date">' + labels.new_expiration_date +' <br/>' + displayHumanReadableDate(newMonthExpDate, false, true) + '</label>';
		
	var yearlyHtml = '';
		yearlyHtml += '<label class="time">'+labels.yearly+'</label>';
		yearlyHtml += '<label class="price">$ ' + yearPrice + '</label>';
		yearlyHtml += '<label class="savings">' + labels.saveb + ' ' + (12*monthPrice - yearPrice).toFixed(2) + '!</label>';
	
	if (newYearExpDate)	
		yearlyHtml += '<label class="date">'+labels.new_expiration_date+' <br/>' + displayHumanReadableDate(newYearExpDate, false, true) + '</label>';
			
	var html = '';
		html += '<div class="breath-20"></div>';
        html += '<h2>' + labels.upgrade_your_account + '</h2>';
		html += '<div class="acc_type_wrapper">';
		html += '	<div class="acc_type_info_wrapper left">';
    	html += '		<div id="acc_type_info_left" class="acc_type_info ' + monthlyActive + '">' + monthlyHtml + '</div>';
    	html += '	</div>';
    	html += '	<div class="acc_type_info_wrapper right">';
     	html += '		<div id="acc_type_info_right" class="acc_type_info ' + yearlyActive + '">' + yearlyHtml + '</div>';
     	html += '	</div>';
     	html += '</div>';
   	
    doc.getElementById('subs_summary').style.display = 'none';
    doc.getElementById('subs_purchase_history').style.display = 'none';
    doc.getElementById('subs_account_type_info').innerHTML = html;
    doc.getElementById('subs_account_type_info').style.display = 'block';
    
    doc.getElementById('acc_type_info_left').onclick = function(){toggleAccountTypeInfo('monthly', monthPrice);};
    doc.getElementById('acc_type_info_right').onclick = function(){toggleAccountTypeInfo('yearly', yearPrice);};
    
    /*Billing info*/
    html = '';
    cardUIHtml = cardOnFile ? getPreviousCardHtml() : getNewCardHtml();
    
    html += '	<div class="breath-20"></div>';
	html += '	<div id="payment_info_container">';
	html += 	cardUIHtml;
	html += '	</div>';
    html += '	<div class="purchase_confirmation">';
    html += '		<p id="purchase_msg"></p>';
    html += '		<div class="purchase_summary">';
    html += '			<div class="total">';
    html += '				<span>' + labels.total + '</span>';
    html += '				<span id="total_amount">' + total + '</span>';
    html += '			</div>';
    html += '		</div>';
    html += '		<div class="terms_service_check">';
    html += '			<input id="terms_of_service_check" type="checkbox" />';
    html += '			'+labels.i_have_read_and_i_agree;
    html += '		</div>';
    html += '		<div id="cancel_button" class="button" onclick="loadSubscriptionSettingContent()">'+labels.cancel+'</div>	';
    html += '		<div id="purchase_button" class="button disabled" >' + labels.purchase + '</div>';

   // html += '		<span id="lock_img_wrapper">';
   // html += '		    <img id="lock_img" src="https://s3.amazonaws.com/static.plunkboard.com/images/settings/lock.png" />';
   // html += '		    <div id="lock_help">Yes, this is a secure transaction</div>';
   // html += '		</span>';
    html += '	</div>';

    doc.getElementById('subs_billing').innerHTML = html;
    
    if (cardOnFile)
    {
    	doc.getElementById('new_card_tab').addEventListener('click', confirmNewCardEntry, false);
    }
    else
    {
    	loadExpirationMonthOptions('exp_date_month');
    	loadExpirationYearOptions('exp_date_year');
	}
	
	doc.getElementById('terms_of_service_check').addEventListener('click', shouldEnablePurchaseButton, false);
};

function getNewCardHtml()
{
	var html = '';
		html += '		<div class="card_info_wrap">';
		html += '			<div class="payment_info_option">';
	    html += '			    <label>'+labels.credit_card_number+'</label>';
	    html += '			    <input id="cc_number" type="text" />';
	    html += '				<div class="input_status" id="cc_number_error"></div>';
	    html += '			    <div class="payment_image cards"></div>';
	    html += '			</div>';
	    html += '			<div class="payment_info_option">';
	    html += '			    <label>'+labels.name_on_card +'</label>';
	    html += '			    <input id="name_on_card" type="text" onkeydown="limitNameLenght(event)"/>';
	    html += '			</div>';
	    html += '			<div class="payment_info_option">';
	    html += '			    <label>' + labels.expiration_date +'</label>';
	    html += '			    <select id="exp_date_month" ></select>';
	    html += '				<div class="input_status exp_month" id="exp_month_error"></div>';
	    html += '			    <select id="exp_date_year" ></select>';
	    html += '			</div>';
	    html += '			<div class="payment_info_option">';
        html += '			    <label id="security_code_label">' + labels.security_code + '</label>';
	    html += '			    <input id="cvc" type="text" maxlength="4" />';
	    html += '				<div class="input_status cvc" id="cvc_error"></div>';
	    html += '			    <span id="cvc_help_wrapper" class="cvc_help_wrapper">';// <!--onmouseover="displayHelpElementWithId('cvc_help', 'inline-block')" onmouseout="hideHelpElementWithId('cvc_help')">-->
	    html += '			    	<div class="payment_image cvc_help" id="cvc_help_toggle"> </div>';
	    html += '					<div class="cvc_help_image_wrap">';
	    html += '						<div class="cvc_help_item">';
	    html += '							<h3>' + labels.cards_names + '</h3>';
	    html += '			    			<div class="cvc_cc_back_image" ></div>';
	    html += '							<p>' + labels.the_last_3_digits + '</p>';
	    html += '						</div>';
	    html += '						<div class="cvc_help_item">';
	    html += '							<h3>' + labels.american_express + '</h3>';
	    html += '			    			<div class="cvc_cc_front_image" ></div>';
	    html += '							<p>' + labels.the_4_digits_printed + '</p>';
	    html += '						</div>';
	    html += '					</div>';
	    html += '			    </span>';
	    html += '			</div>';
	    html += '			<div class="input_status card" id="card_error"></div>';
	    html += '		</div>';
	    
	return html;    
};
function getPreviousCardHtml()
{
	var html = '';
		html += '		<div class="tab active">' + labels.previous_card + '</div>';
	    html += '		<div class="tab" id="new_card_tab">' + labels.new_card + '</div>';
	    html += '		<div class="card_info_wrap">'; 
	    html += '			<div class="payment_info_option">';
	    html += '			    <label>' + labels.credit_card_number + '</label>';
	    html += '			    <span>************' + cardOnFile.last4 + '</span>';
	    html += '			</div>';
	    html += '			<div class="payment_info_option">';
	    html += '			    <label>'+labels.name_on_card+'</label>';
	    html += '			    <span>' + cardOnFile.name + '</span>';
	    html += '			</div>';
	    html += '			<div class="payment_info_option">';
	    html += '			    <label>'+labels.expiration_date+'</label>';
	    html += '			    <span>' + addLeadingZero(cardOnFile.exp_month) + '/' + cardOnFile.exp_year + '</span>';
	    html += '			</div>';
	    html += '		</div>';
	    
	return html;    
};

function confirmNewCardEntry()
{
	var bodyHTML = '';
		
		bodyHTML += labels.adding_a_new_credit_card;
		
		var headerHTML = labels.new_credit_card;
		var footerHTML ='';
			footerHTML += '<div class="button" id="confirm_new_card_button" >' + labels.ok + '</div>';
			footerHTML += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
			
		
		displayModalContainer(bodyHTML, headerHTML, footerHTML);
		document.getElementById('confirm_new_card_button').addEventListener('click', displayNewCardUI, false);
};

function displayNewCardUI()
{
    cardOnFile = null;
	var doc = document;
	
	doc.getElementById('payment_info_container').innerHTML = getNewCardHtml();
	doc.getElementById('cc_number').focus();
	loadExpirationMonthOptions('exp_date_month');
    loadExpirationYearOptions('exp_date_year');
	
	hideModalContainer();
};

function limitNameLenght(event)
{
	var keyCode = 'keyCode' in event ? event.keyCode : event.charCode;
	var name = document.getElementById('name_on_card').value;
	var maxNameLength = 98; //this will limit it to 99 since the event is being called on keydown
	
	if (name.length > maxNameLength && keyCode != 8/*backspace*/ && keyCode != 9/*tab*/ && keyCode != 46 /*delete*/ && keyCode !=39/*right arrow*/ && keyCode != 37/*left arrow*/)
		event.preventDefault();
};

function toggleAccountTypeInfo(newSelection, newTotal)
{
	var doc = document;
	var purchaseButton = doc.getElementById('');
	
	if (newSelection == 'monthly')
	{
		doc.getElementById('acc_type_info_left').setAttribute('class', 'active acc_type_info');
		doc.getElementById('acc_type_info_right').setAttribute('class', 'acc_type_info');
		doc.getElementById('subscription_type').value = 'month';
	}
	else
	{
		doc.getElementById('acc_type_info_right').setAttribute('class', 'active acc_type_info');
		doc.getElementById('acc_type_info_left').setAttribute('class', 'acc_type_info');
		doc.getElementById('subscription_type').value = 'year';
	}
	
	if (switchToMonthly && newSelection == 'monthly')
	{
		newTotal = '0.00';
		doc.getElementById('purchase_msg').innerHTML = '<i>' + labels.switching_to_monthly_billing_does_not_charge + '</i>';
		doc.getElementById('purchase_button').innerHTML = labels.switch_to_monthly_billing;
	}
	else
	{
		doc.getElementById('purchase_msg').innerHTML = '';
		doc.getElementById('purchase_button').innerHTML = labels.purchase;
	}
		
	doc.getElementById('total_amount').innerHTML = newTotal;
};

function shouldEnablePurchaseButton()
{
	var doc = document;
	
	var agreesWithTermsOfService = doc.getElementById('terms_of_service_check').checked;
	var button = doc.getElementById('purchase_button');
	
	if (/*validCardOnFile && */agreesWithTermsOfService)
	{
        if(switchToMonthly)
            button.addEventListener('click', switchToMonthlyBilling, false);
        else	
            button.addEventListener('click', purchaseProAccount, false);
		
		button.setAttribute('class', 'button');
	}
	else
	{
		button.removeEventListener('click', switchToMonthlyBilling, false);
		button.removeEventListener('click', purchaseProAccount, false);
        button.removeEventListener('click', switchBillingMethods, false);
		
		button.setAttribute('class', 'button disabled');
	}
};

function loadPurchaseHistory()
{
	var doc = document;
		
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
                	 var purchases = response.purchases;
                	 var count = purchases.length;
                	 var historyHtml = '';
                	
                	 
                	 if (count > 0)
                	 {
                	 	historyHtml += '<div class="setting purchase header">';
	                	historyHtml += '	<div class="p-date ellipsized">' + labels.purchase_date + '</div>';
	                	historyHtml += '	<div class="p-type ellipsized">' + labels.account_type + '</div>';
	                	historyHtml += '	<div class="p-details ellipsized">' + labels.description + '</div>';
	                	historyHtml += '</div>';
		                	
	                	for (var i = 0; i < count; i++)
	                	{
	                		purchase = purchases[i];
							var typeString = labels.promo;
							if (purchase.subscriptionType == 'month')
								typeString = labels.month;
							else if (purchase.subscriptionType == 'year')
								typeString = labels.year;
                            else if (purchase.subscriptionType == 'gift')
                                typeString = labels.gift;
							else if (purchase.subscriptionType == 'referral')
								typeString = labels.referral;
                            
                	 		historyHtml += '<div class="setting purchase">';
                	 		historyHtml += '	<div class="p-date ellipsized">' + displayHumanReadableDate(purchase.timestamp, false, true) + '</div>';
                	 		historyHtml += '	<div class="p-type ellipsized">' + typeString + '</div>';
                	 		historyHtml += '	<div class="p-details ellipsized">' + purchase.description + '</div>';
                	 		historyHtml += '</div>';
                	 		} 
                	 }	
                	 else
                	 {
	                	 historyHtml += labels.you_have_no_purchases ;
                	 }
                	 
                	 var html = '';
                	 	 html += '<div class="breath-30"></div>';
                	 	 html += '<h2>' + labels.purchase_history + '</h2>';
                	 	 html += historyHtml;
                	 	 html += '	<div class="breath-20"></div>';
                	 	 html += '	<div class="save_cancel_button_wrap" style="text-align:right;margin-right:10px">';
                	 	 html += '		<div class="button" onclick="loadSubscriptionSettingContent()">'+ labels.done+'</div>';
                	 	 html += '	</div>';
                	 	 
                	doc.getElementById('subs_summary').style.display = 'none';
                    doc.getElementById('subs_account_type_info').style.display = 'none';
                    doc.getElementById('subs_purchase_history').innerHTML = html;
                    doc.getElementById('subs_purchase_history').style.display = 'block';
                }
                else if(response.error == "authentication")
                {
                     history.go(0); //make the user log in again
                }
                else
                {
                     displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	var params = 'method=getPurchaseHistory';
	
	ajaxRequest.open("POST", "." , true);
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);  	
};

function confirmDowngradRequest(expirationDate)
{
	var bodyHTML = '<p style="width:300px;">'+labels.downgrading_to_a_free_account_will + ' ' + displayHumanReadableDate(expirationDate, false, true) + ' </p><p>' + labels.your_account_will_be_not_be_renewed + '</p>';
	var headerHTML = labels.downgrade_premium_account;
	var footerHTML = '';
	
	footerHTML += '<div class="button" id="downgradePremiumButton" onclick="downgradePremiumAccount(false)">'+labels.downgrade+'</div>';
	footerHTML += '<div class="button" id="cancelDownloadPremiumButton" onclick="hideModalContainer()">'+ labels.cancel +'</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
    document.getElementById('modal_overlay').onclick = null;

};

//This option is present if the user has an IAP autorenewing account but has set
//up Stripe billing info to be charged once the autorenewal cancels.
function confirmRemoveBillingInfo(expirationDate)
{
    var bodyHTML = '<p style="width:500px;">' + labels.removing_your_billing_information_will + displayHumanReadableDate(expirationDate, false, true) + labels.if_your_in_app_purchase + ' </p>';
    
    var headerHTML = labels.remove_billing_information;
    
    var footerHTML = '<div class="button" id="removeBillingInfoButton" onclick="downgradePremiumAccount(true)">'+labels.remove+'</div>';
    footerHTML += '<div class="button" id="cancelRemoveBillingInfoButton" onclick="hideModalContainer()">'+labels.cancel+'</div>';
    
    displayModalContainer(bodyHTML, headerHTML, footerHTML);
    document.getElementById('modal_overlay').onclick = null;
}

function downgradePremiumAccount(isRemoveBillingInfo)
{
	var doc = document;
	
    var cancelButton;
    if(isRemoveBillingInfo)
        cancelButton = document.getElementById('cancelRemoveBillingInfoButton');
    else
        cancelButton = document.getElementById('cancelDownloadPremiumButton');
    
    cancelButton.setAttribute('onclick', '');
    cancelButton.setAttribute('class', 'button disabled');
    
    var downgradeButton;
    
    if(isRemoveBillingInfo)
        downgradeButton = document.getElementById('removeBillingInfoButton');
    else
        downgradeButton = document.getElementById('downgradePremiumButton');
    
    downgradeButton.innerHTML += ' <div class="progress_indicator" style="display:inline-block"></div>';
    downgradeButton.setAttribute('onclick', '');
    
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
                    document.getElementById('subs_summary').innerHTML = '';
                    loadSubscriptionSettingContent();
                
                    var bodyHTML;
                    if(isRemoveBillingInfo)
                        bodyHTML = '<p>' + labels.your_billing_info_was_successfully_removed + '</p>';
                    else
                        bodyHTML = '<p>' + labels.your_account_was_successfully_downgraded + '</p>';
                    
					var headerHTML = labels.success_e;
					var footerHTML = '';
					
					footerHTML += '<div class="button" onclick="hideModalContainer();">' + labels.ok + '</div>';
					
					displayModalContainer(bodyHTML, headerHTML, footerHTML);
                }
                else if(response.error == "authentication")
                {
                     history.go(0); //make the user log in again
                }
                else
                {
                     displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	var params = 'method=downgradeToFreeAccount';
	
	ajaxRequest.open("POST", "." , true);
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params); 
};


/*----------------------------------*/
/*--------! Auxiliary Functions -----*/
/*----------------------------------*/

function showSuccessSettingUpdate(elementId, editId, restoreEditString)
{
	var doc = document;
	var settingEl = doc.getElementById(elementId);
	var statusEl = doc.getElementById(editId);
	
	statusEl.style.visibility = 'visible';
	statusEl.style.display = 'inline-block';
	statusEl.style.opacity = '0.0';
	statusEl.innerHTML = settingStrings.updated;
	statusEl.style.opacity = '1.0';
    
    settingEl.style.background = 'rgb(221, 244, 253)';
    
    setTimeout(function(){
    	
    	settingEl.style.background = 'transparent';
    	statusEl.style.opacity = '0.0';
    	statusEl.innerHTML = '';
    	if(restoreEditString)
    	{
    		statusEl.innerHTML = labels.edit;
    		statusEl.setAttribute('style', '');
    	}	
    }, 1500);
};


function toggleTagFilterSetting(setting)
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
                	showSuccessSettingUpdate('tags_filter_setting', 'tags_filter_edit');                    
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        //if it didn't save, revert the checkbox
                        if(setting)
                            document.getElementById('tagFilterRadioOr').checked = true;
                        else
                            document.getElementById('tagFilterRadioAnd').checked = true;
                        displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                }
            }
            catch(e)
            {
                if(setting)
                    document.getElementById('tagFilterRadioOr').checked = true;
                else
                    document.getElementById('tagFilterRadioAnd').checked = true;
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	var params = "method=changeTagFilterSetting&setting=" + setting;
	
	ajaxRequest.open("POST", "." , true);
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);    

}

function toggleOverdueSectionSetting(setting)
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
                	showSuccessSettingUpdate('overdue_section_setting', 'overdue_section_edit');                    
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        //if it didn't save, revert the checkbox
                        if(setting)
                            document.getElementById('overdueSectionRadioOff').checked = true;
                        else
                            document.getElementById('overdueSectionRadioOn').checked = true;
                        displayGlobalErrorMessage(labels.unable_to_save_setting);
                    }
                }
            }
            catch(e)
            {
                if(setting)
                    document.getElementById('overdueSectionRadioOff').checked = true;
                else
                    document.getElementById('overdueSectionRadioOn').checked = true;
                displayGlobalErrorMessage(labels.unable_to_save_setting_e + ' ' + e);
            }
		}
	}
	
	var params = "method=updateUserSettings&show_overdue_section=" + setting;
	
	ajaxRequest.open("POST", "." , true);
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);  
    
}

function updateTaskSortSetting()
{
    var selectBox = document.getElementById('taskSortSettingSelect');
    var selectVal = selectBox.selectedIndex;

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
                    currentTaskSortSetting = selectVal;
                
                    showSuccessSettingUpdate('task_sort_setting', 'task_sort_edit');   
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        //if it didn't save, revert the checkbox
                        selectBox.selectedIndex = currentTaskSortSetting;
                        displayGlobalErrorMessage(labels.unable_to_save_setting);
                    }
                }
            }
            catch(e)
            {
                selectBox.selectedIndex = currentTaskSortSetting;
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	var params = "method=updateUserSettings&task_sort_order=" + selectVal;
	
	ajaxRequest.open("POST", "." , true);
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);     
}


function updateStartDateSetting()
{
    var selectBox = document.getElementById('startDateSettingSelect');
    var selectVal = selectBox.selectedIndex;
	
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
                    currentStartDateSetting = selectVal;
					
                    showSuccessSettingUpdate('start_date_setting', 'start_date_edit');
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        //if it didn't save, revert the checkbox
                        selectBox.selectedIndex = currentStartDateSetting;
                        displayGlobalErrorMessage(labels.unable_to_save_setting);
                    }
                }
            }
            catch(e)
            {
                selectBox.selectedIndex = currentStartDateSetting;
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	var params = "method=updateUserSettings&start_date_filter=" + selectVal;
	
	ajaxRequest.open("POST", "." , true);
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}


function updateDefaultDueDateSetting()
{
    var selectBox = document.getElementById('taskDefaultDueDateSettingSelect');
    var selectVal = selectBox.selectedIndex;

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
                    currentDefaultDueDateSetting = selectVal;
                
                    showSuccessSettingUpdate('task_default_duedate_setting', 'task_default_duedate_edit');   
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        //if it didn't save, revert the checkbox
                        selectBox.selectedIndex = currentDefaultDueDateSetting;
                        displayGlobalErrorMessage(labels.unable_to_save_setting);
                    }
                }
            }
            catch(e)
            {
                selectBox.selectedIndex = currentDefaultDueDateSetting;
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	var params = "method=updateUserSettings&default_due_date=" + selectVal;
	
	ajaxRequest.open("POST", "." , true);
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);      
}

function displaySettingDetails(detailsId, editId) 
{
	var doc = document;
	
	doc.getElementById(detailsId).style.display = "block";
	doc.getElementById(editId).style.display = 'none';
}


function confirmNewTaskEmailRequest()
{
	var bodyHTML = '';
		bodyHTML += '<p>'+labels.your_previous_email_address_will_be_replaced+'</p>';
		bodyHTML += '<p>'+labels.any_emails_sent_to_the_previous_address_will_not+'</p>';
	var headerHTML = labels.new_task_email_address;
	var footerHTML = '';
	
	
	footerHTML += '<div class="button" onclick="regenerateTaskCreationEmail()">' + labels.ok + '</div>';
	footerHTML += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	loadMultiEditListOptions();
};

function regenerateTaskCreationEmail()
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
                	var doc = document;
                	
                	hideModalContainer();
                	
					newEmail = response.task_creation_email;
					
					doc.getElementById('taskEmailLabel').innerHTML = newEmail + "@newtask.todo-cloud.com";
					
					doc.getElementById('task_email_config').setAttribute('style', '');
                    showSuccessSettingUpdate('task_email_setting', 'task_email_edit', true);
                }
                else
                {
                    if(response.error == "no subscription")
                    {
						displayGlobalErrorMessage(labels.this_feature_requires_a_todo_cloud_subscription);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	
	var params = "method=generateNewTaskCreationEmail";
	
	ajaxRequest.open("POST", "." , true);
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function clearTaskCreationEmail(el)
{
	// TODO: Implement clearTaskCreationEmail to call "deleteTaskCreationEmail"
}


/////****! account

function validateFirstLastName()
	{
		var doc = document;
		var submitButton = doc.getElementById('nameSubmit');
		
		var firstName = doc.getElementById('firstName').value.trim();
		var lastName = doc.getElementById('lastName').value.trim();
		
		if(firstName.length > 0)
			doc.getElementById('first_name_status').innerHTML = "";
		else
			doc.getElementById('first_name_status').innerHTML = labels.too_short;
			
		if(lastName.length > 0)
			doc.getElementById('last_name_status').innerHTML = "";
		else
			doc.getElementById('last_name_status').innerHTML = labels.too_short;

        var pattern = /^[A-Za-z\ ]+$/i;
        if (firstName.length > 0 && lastName.length > 0 && pattern.test(firstName) && pattern.test(lastName))
		{
			submitButton.setAttribute('class', 'button');
			submitButton.addEventListener('click',saveFirstLastNames , false);
		}
		else
		{
			submitButton.setAttribute('class', 'button disabled');
			submitButton.removeEventListener('click',saveFirstLastNames , false);
		}
	};
	
	function validateUsername()
	{
		var doc = document;
		var saveButton = doc.getElementById('usernameSubmit');
		var invalid = " "; // Invalid character is a space
		var minLength = 4; // Minimum length
		var validated = true;
		
		var username = doc.getElementById('username').value;
		
		if(username.length < minLength)
		{
			doc.getElementById('username_status').innerHTML = labels.too_short;
			validated = false;
		}
		else if (username.indexOf(invalid) > -1)
		{
			doc.getElementById('username_status').innerHTML = labels.spaces_not_allowed;
			validated = false;
		}	
		else
		{
			var atpos=username.indexOf("@");
			var dotpos=username.lastIndexOf(".");
			if (atpos<1 || dotpos<atpos+2 || dotpos+2>=username.length)
			{
				doc.getElementById('username_status').innerHTML = labels.not_an_email_address;
				validated = false;
			}
			else
				doc.getElementById('username_status').innerHTML = "";
		}
			
		
		if(validated)
		{
			saveButton.setAttribute('class', 'button');
			saveButton.addEventListener('click',saveUsername , false);
		}
		else
		{
			saveButton.setAttribute('class', 'button disabled');
			saveButton.removeEventListener('click',saveUsername , false);
		}
	};
	
	function validatePassword()
	{
		var doc = document;
		var saveButton = doc.getElementById('passwordSubmit');
		
		var invalid = " "; // Invalid character is a space
		var minLength = 6; // Minimum length
		var validated = true;
		
		var passOne = doc.getElementById('password').value;
		var passTwo = doc.getElementById('verifyPassword').value;
		
		if(passOne.length < minLength)
		{
			doc.getElementById('password_status').innerHTML = labels.too_short;
			validated = false;
		}
		else if (passOne.indexOf(invalid) > -1)
		{
			doc.getElementById('password_status').innerHTML = labels.spaces_not_allowed;
			validated = false;
		}	
		else if (passOne != passTwo)
		{
			doc.getElementById('password_status').innerHTML = labels.dont_match;
			validated = false;
		}	
		else
		{
			doc.getElementById('password_status').innerHTML = "";
		}	
		
		
		if(validated)
		{
			saveButton.setAttribute('class', 'button');
			saveButton.addEventListener('click', savePassword, false);
		}
		else
		{
			saveButton.setAttribute('class', 'button disabled');
			saveButton.removeEventListener('click', savePassword, false);
		}
	};
	
	function saveFirstLastNames()
	{
		var doc = document;
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
		var firstName = doc.getElementById('firstName').value.trim();
		var lastName = doc.getElementById('lastName').value.trim();
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try
                {
                    var response = JSON.parse(ajaxRequest.responseText);
                    if(response.success == true)
                    {	
                        //update values and UI	
                        doc.getElementById('firstNameLabel').innerHTML = firstName;
                        doc.getElementById('lastNameLabel').innerHTML = lastName;
                        
                        doc.getElementById('origFirstName').value  = firstName;
                        doc.getElementById('origLastName').value  = lastName;
                                                
                        doc.getElementById('first_last_name_config').setAttribute('style', '');
                        showSuccessSettingUpdate('first_last_name_setting', 'first_last_name_edit', true);
                        
                        var submitButton = doc.getElementById('nameSubmit');
							submitButton.setAttribute('class', 'button disabled');
							submitButton.removeEventListener('click',saveFirstLastNames , false);
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            doc.getElementById('firstNameStatus').innerHTML = "(" + labels.not_saved + ")";
                            doc.getElementById('lastNameStatus').innerHTML = "(" + labels.not_saved + ")";
                            displayGlobalErrorMessage(labels.please_re_enter_your_user_name_using);
                        }
                    }
                }
                catch(e)
                {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }
			}
		}
        
        var params = "method=updateUser&firstname=" + firstName + "&lastname="+lastName;
		
		ajaxRequest.open("POST", ".", true);
	    
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);
	};
	
	function saveUsername()
	{
		var doc = document;
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
		var username = doc.getElementById('username').value;
		
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try
                {
                    var response = JSON.parse(ajaxRequest.responseText);
                    if(response.success == true)
                    {
                        //update values and UI	
                        doc.getElementById('usernameLabel').innerHTML = username;
                        doc.getElementById('origUsername').value = username;
                        
                        doc.getElementById('username_config').setAttribute('style', '');
                        showSuccessSettingUpdate('username_setting', 'username_edit', true);
                        
                        var saveButton = doc.getElementById('usernameSubmit');
							saveButton.setAttribute('class', 'button disabled');
							saveButton.removeEventListener('click', saveUsername, false);
                            
                        if(response.email)
                        {
                            showVerificationEmailSentModal(response.email);
                        }
                        else
                        {
                            doc.getElementById('verify_email_button').style.display = 'inline-block';
                        }
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            if(response.error)
                            {
                                displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                            }
                            else
                            {
//                            doc.getElementById('usernameStatus').innerHTML = "(not saved)";
                                displayGlobalErrorMessage(labels.please_re_enter_your_user_name_using);
                            }
                        }
                    }
                }
                catch(e)
                {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }
			}
		}
		
        var params = 'method=updateUser&username=' + encodeURIComponent(username);
		
		ajaxRequest.open("POST", ".", true);
	    
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);
		
	};
	
	function savePassword()
	{
		var doc = document;
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
	
		var passOne = doc.getElementById('password').value;
		
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
                try
                {
                    var response = JSON.parse(ajaxRequest.responseText);
                    if(response.success == true)
                    {
                        //hide details & show value
                        doc.getElementById('password_config').setAttribute('style', '');
                        showSuccessSettingUpdate('password_setting', 'password_edit', true);
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            if(response.error)
                            {
                                displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                            }
                            else
                            {
                                displayGlobalErrorMessage(labels.please_re_enter_your_password);
                            }
                            doc.getElementById('password_status').innerHTML = labels.not_saved;
                            
                        }
                    }
                }
                catch(e)
                {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }
			}
		}
        
        var params = 'method=updateUser&password=' + passOne;
		
		ajaxRequest.open("POST", ".", true);
	    
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);
    
    };


    function toggleEmailOptOutSetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
        if(!ajaxRequest)
            return false;
        
        var checkbox = document.getElementById("emailOptOutSettingCheckbox");
        var setting = 1;
        if(checkbox.checked)
            setting = 0;
        
        // Create a function that will receive data sent from the server
        ajaxRequest.onreadystatechange = function()
        {
            if(ajaxRequest.readyState == 4)
            {
                try 
                {
                    var response = JSON.parse(ajaxRequest.responseText);
                    //                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                        showSuccessSettingUpdate('show_email_opt_out_setting', 'show_email_opt_out_edit');
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            //if it didn't save, revert the checkbox
                            checkbox.checked = !checkbox.checked;
                            displayGlobalErrorMessage(labels.unable_to_save_setting);
                        }
                    }
                }
                catch(e)
                {
                    checkbox.checked = !checkbox.checked;
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }
            }
        }
        
        var params = 'method=updateUser&email_opt_out=' + setting;
        
        ajaxRequest.open("POST", "." , true);
        //Send the proper header information along with the request
        ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        
        ajaxRequest.send(params); 
    }
    function toggleGAOptOutSetting()
    {
        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
        if(!ajaxRequest)
            return false;

        var checkbox = document.getElementById("gaOptOutSettingCheckbox");
        var setting = 0;
        if(checkbox.checked)
            setting = 1;

        // Create a function that will receive data sent from the server
        ajaxRequest.onreadystatechange = function()
        {
            if(ajaxRequest.readyState == 4)
            {
                try
                {
                    var response = JSON.parse(ajaxRequest.responseText);
                    //                    alert(ajaxRequest.responseText);
                    if(response.success)
                    {
                        showSuccessSettingUpdate('enable_google_analytics_tracking_setting', 'enable_google_analytics_tracking_edit');
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            //if it didn't save, revert the checkbox
                            checkbox.checked = !checkbox.checked;
                            displayGlobalErrorMessage(labels.unable_to_save_setting);
                        }
                    }
                }
                catch(e)
                {
                    checkbox.checked = !checkbox.checked;
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }
            }
        }

        var params = 'method=updateUserSettings&google_analytics_tracking=' + setting;

        ajaxRequest.open("POST", "." , true);
        //Send the proper header information along with the request
        ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        ajaxRequest.send(params);
    }


	function saveTimezone()
	{
		var doc = document;
		
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
        var selector = doc.getElementById('timezoneSelect');
		var timezone = selector.options[selector.selectedIndex].value;

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

                        //currentTimezoneValue = timezone;
                        showSuccessSettingUpdate('timezone_setting', 'timezone_edit', false);
                    }
                    else
                    {
                        if(response.error == "authentication")
                            history.go(0);
                        else
                        {
                            if(response.error)
                                displayGlobalErrorMessage(error);
                            else 
                                displayGlobalErrorMessage(labels.unable_to_save_timezone_change);
                        }
                    }
                }
                catch(e)
                {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }
			}
		}
		var params= "method=setUserTimezone&timezone_id=" + timezone;
		ajaxRequest.open("POST", ".", true);
	    
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params); 	
	};
    function saveLanguage() {
        var doc = document;

        var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
        if (!ajaxRequest)
            return false;

        var selector = doc.getElementById('languageSelect');
        var language_id = selector.options[selector.selectedIndex].value;

        // Create a function that will receive data sent from the server
        ajaxRequest.onreadystatechange = function () {
            if (ajaxRequest.readyState == 4) {
                try {
                    var response = JSON.parse(ajaxRequest.responseText);
                    if (response.success) {
                        showSuccessSettingUpdate('language_setting', 'language_edit', false);
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    }
                    else {
                        if (response.error == "authentication")
                            history.go(0);
                        else {
                            if (response.error)
                                displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                            else
                                displayGlobalErrorMessage(labels.unable_to_save_language_change);
                        }
                    }
                }
                catch (e) {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }
            }
        }
        var params = "method=setUserLanguage&language_id=" + language_id;
        ajaxRequest.open("POST", ".", true);

        //Send the proper header information along with the request
        ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        ajaxRequest.send(params);
    };
	function cancelFirstLastNameUpdate()
	{
		var doc = document;
        var saveButton = doc.getElementById('nameSubmit');
			saveButton.setAttribute('class', 'button disabled');
			saveButton.removeEventListener('click', saveUsername, false);
        
		doc.getElementById('firstName').value = doc.getElementById('origFirstName').value;
		doc.getElementById('lastName').value = doc.getElementById('origLastName').value;
		
		doc.getElementById('first_name_status').innerHTML = "";
		doc.getElementById('last_name_status').innerHTML = "";
				
		doc.getElementById('first_last_name_config').setAttribute('style', '');
        doc.getElementById('first_last_name_edit').setAttribute('style', '');
   	};
	
	function cancelUsernameUpdate()
	{
		var doc = document;
		var saveButton = doc.getElementById('usernameSubmit');
			saveButton.setAttribute('class', 'button disabled');
			saveButton.removeEventListener('click', saveUsername, false);
							
		doc.getElementById('username').value = doc.getElementById('origUsername').value;
		doc.getElementById('username_status').innerHTML = "";
		
		doc.getElementById('username_config').setAttribute('style', '');
		doc.getElementById('username_edit').setAttribute('style', '');
	};
	
	function cancelPasswordUpdate()
	{	
		var doc = document;
		var saveButton = doc.getElementById('passwordSubmit');
			saveButton.setAttribute('class', 'button disabled');
			saveButton.removeEventListener('click', savePassword, false);
			
		doc.getElementById('password_status').innerHTML = "";
		doc.getElementById('password').value = '';
		doc.getElementById('verifyPassword').value = '';
		
		doc.getElementById('password_config').setAttribute('style', '');
		doc.getElementById('password_edit').setAttribute('style', '');
	};
    
    function cancelListFilterUpdate(filterName)
    {
    	var doc = document;
    	
        doc.getElementById(filterName + '_list_filter_config').setAttribute('style', '');
        doc.getElementById(filterName + '_list_filter_edit').setAttribute('style', '');
    };

    function cancelTaskEmailUpdate()
    {
    	var doc = document;
    	
	    doc.getElementById('task_email_config').setAttribute('style', '');	
	    doc.getElementById('task_email_edit').setAttribute('style', '');    
    };
    
    function cancelUnlinkFBAccount()
    {
	  	var doc = document;
	    
	    doc.getElementById('social_config').setAttribute('style', '');	
	    doc.getElementById('social_edit').setAttribute('style', ''); 
    };
    
//!facebook
  
	function unlinkFacebook()
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
	                if(response.success == true)
	                {			
	                    history.go(0);
	                }
	                else
	                {
	                    if(response.error)
	                    {
                            if(response.error == "authentication")
                                history.go(0);
                            else
                                displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                        }
	                    else
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
	            }
	            catch(e)
	            {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
	            }
			}
		}
	
		var params = "method=unlinkFacebook";
		ajaxRequest.open("POST", ".", true);
	    
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params); 
		
	}

var assignTeamSubscriptionToMe = false;

function loadOwnedSubscriptionsContent()
{
	var doc = document;
	
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
                var subsHTML = '';
                var memberSubHTML = '';
                
                if(response.success == true)
                {			
                	var container = doc.getElementById('owned_subscriptions_wrapper');
                	var memberSubContainer = doc.getElementById('member_subscription_wrapper');
                	var subsJSON = response.ownedsubscriptions;
                	var memberSubJSON = response.membersubscription;
                	
                	for (var i = 0; i < subsJSON.length; i++)
                	{
                		var subJSON = subsJSON[i];
                		var ownerLabel = '';
                		var expirationLabel = '';
                		var actionButtonHTML = '';
                		var invitationEmail = subJSON.invitationemail;
                		
                		var subId = subJSON.subscriptionid;
                		
                		//ownerLabel
                		if (typeof(invitationEmail) != 'undefined')
                		{
                			ownerLabel += 'Pending invitation for ' + subJSON.invitationemail;
                			ownerLabel += '<span> (' + subStrings.invitationSent + ': ' + displayHumanReadableDate(subJSON.invitationtimestamp, false) + ')</span>';
                		
                			//cancel invitation button
                			actionButtonHTML = '<div class="button" onclick="deleteTeamSubscriptionInvitation(\'' + subJSON.invitationid + '\')">' + subStrings.deleteInvitation + '</div>';
                			
                			//resend invitation button
                			actionButtonHTML += '<div class="button" onclick="sendTeamSubscriptionInvitation(\'' + subJSON.subscriptionId + '\' , \'' + invitationEmail + '\')">' + subStrings.resendInvitation + '</div>';
                		}
                		else if(typeof(subJSON.memberuserid) != 'undefined')
                		{
                			ownerLabel = subJSON.membername;
                			actionButtonHTML = '<div class="button" onclick="unassignTeamSubscription(\'' + subJSON.subscriptionid + '\' , \'' + subJSON.memberuserid + '\')">' + subStrings.unassign + '</div>';
                		}
                		else
                		{
                			ownerLabel = subStrings.unused;
                			actionButtonHTML = '<div class="button" onclick="displayAssignOptions(\'' + subJSON.subscriptionid + '\')">' + subStrings.assign + '</div>';
                		}
                			
                		//expiration label
                		if (typeof(subJSON.susbscriptionexpiratiodate) != 'undefined')
                			expirationLabel = subStrings.expires + ': ' + displayHumanReadableDate(subJSON.susbscriptionexpiratiodate, false, true);	
                		 
                		//if (typeof(subJSON.invitationtimestamp) != 'undefined')
                		//expirationLabel += ' | ' + subStrings.invitationSent + ': ' + 	displayHumanReadableDate(subJSON.invitationtimestamp, false);	                		
                		
                		subsHTML += '<div class="subscription_container" name="subscription_containers" id="subscription_container_' + subId  + '" >';
                		subsHTML += '	<data id="subscription_purchase_info_' + subId + '"></data>';
                		subsHTML += '	<input id="subscription_checkbox_' + subId + '"  type="checkbox" name="subscriptions_checkboxes" value="\'' + subId + '\'" style="display:none;" onclick="selectSubscription(this, \'' + subId + '\')" />';
                		subsHTML += '	<img src="' + subJSON.memberpicurl + '" />';
                		
                		subsHTML += ' 	<span class="labels_wrapper">';
                		subsHTML += '		<div class="sub_owner_label" id="sub_owner_label_' + subId + '" >';
                		subsHTML += 		ownerLabel;
                		subsHTML += '		</div>';

                		subsHTML += '		<input type="hidden" id="original_sub_expire_label_' + subId + '" value="' + expirationLabel + '" />';	
                		subsHTML += '		<div class="sub_expire_label" id="sub_type_label_' + subId + '" >';
                		subsHTML += 			expirationLabel;
                		subsHTML += '		</div>';
                		subsHTML += '	</span>';
                		
                		//action button
                		subsHTML += '	<span class="sub_action_button" id="sub_action_button_' + subId + '" name="action_buttons">';
                		subsHTML += 		actionButtonHTML;
                		subsHTML += '	</span>';
                		subsHTML += '	<span class="sub_price_label" id="sub_price_label_' + subId + '" name="sub_price_labels"></span>';
                		subsHTML += '</div>';
                   	}
                   	
                   	container.innerHTML = subsHTML;
 	
                   	//member subscription
                   	if (typeof(memberSubJSON) != 'undefined')
                   	{
                   		doc.getElementById('member_subscription_header').style.display = 'block';
                   		
                   		memberSubHTML += '<div class="subscription_container">';
                		memberSubHTML += '	<img src="' + memberSubJSON.onwerpicurl + '" />';
                		memberSubHTML
                		memberSubHTML += ' 	<span class="labels_wrapper">';
                		memberSubHTML += '		<div class="sub_owner_label" id="sub_owner_label_' + subId + '" >';
                		memberSubHTML += 		subStrings.owner + ': ' + memberSubJSON.ownername;
                		memberSubHTML += '		</div>';
                		memberSubHTML += '		<div class="sub_expire_label" id="sub_type_label_"' + subId + '" >';
                		memberSubHTML += 		subStrings.expires + ': ' + displayHumanReadableDate(memberSubJSON.expirationdate, false, true);
                		memberSubHTML += '		</div>';
                		memberSubHTML += '	</span>';
                   	}
                 	
                   	memberSubContainer.innerHTML = memberSubHTML;
                   	
                   	
                   	//load Billing Information
                   	
               		if(response.userpaymentinfo != false)
               		{
               			doc.getElementById('billing_info_wrapper').style.display = 'block';
               			
               			var userPayInfo = response.userpaymentinfo;
               			
               			//build cc setting values
               			var ccHTML = '';
               			ccHTML += '	<ul class="simple_list no_margins">';
               			ccHTML += '		<li>';
						ccHTML += '			<span class="fixed_width">' + labels.name_on_card + ' </span><span id="prev_name_on_card"></span>';
						ccHTML += '		</li>';
						ccHTML += '		<li>';
						ccHTML += '			<span class="fixed_width">' + labels.credit_card_number +' </span><span id="prev_cc_number"></span>';
						ccHTML += '		</li>';
						ccHTML += '		<li>';
						ccHTML += '			<span class="fixed_width">' + labels.expiration_date + ' </span><span id="prev_exp_date"></span>';
						ccHTML += '		</li>';
						ccHTML += '	</ul>';
               			
               			
               			
               			
               			doc.getElementById('cc_setting_value').innerHTML = ccHTML;//userPayInfo.type;
               			doc.getElementById('prev_name_on_card').innerHTML = userPayInfo.name;
               			doc.getElementById('update_name_on_card').value = userPayInfo.name;
               			
               			doc.getElementById('prev_cc_number').innerHTML = '************' + userPayInfo.last4;
               			doc.getElementById('prev_exp_date').innerHTML = addLeadingZero(userPayInfo.exp_month) + '/' + userPayInfo.exp_year;
               			
               			loadExpirationMonthOptions('update_exp_date_month');
						loadExpirationYearOptions('update_exp_date_year');
						
    					selectOptionInDropdownMenu('update_exp_date_month', addLeadingZero(userPayInfo.exp_month));
    					selectOptionInDropdownMenu('update_exp_date_year', userPayInfo.exp_year);
               		}

                }
                else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                            history.go(0);
                        else
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                    else
                        displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

	var params = "method=getSettingsValues&type=subscription";
	ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
	ajaxRequest.send(params); 
};

function addSubscription()
{
	var doc = document;
	var ajaxRequest = getAjaxRequest(); 
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    var newSubId = response.subscriptionid;
                    	
					var subsContainer = doc.getElementById('owned_subscriptions_wrapper');
                    var newSubHTML = '';
                    newSubHTML += '	<div id="subscription_container_' + newSubId + '" name="subscription_containers" class="subscription_container" style="color:gray;">';
                    newSubHTML += '		<data id="subscription_purchase_info_' + newSubId + '"></data>';
                    newSubHTML += '		<input id="subscription_checkbox_' + newSubId + '" type="checkbox" onclick="selectSubscription(this, \'' + newSubId + '\')" style="display: inline-block;" value="' + newSubId + '" name="subscriptions_checkboxes">';
                    newSubHTML += '		<img src="https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif">';
                    newSubHTML += '		 	<span class="labels_wrapper">';
                    newSubHTML += '		 		<div id="sub_owner_label_' + newSubId + '" class="sub_owner_label">' + subStrings.unused + '</div>';
                    newSubHTML += '					<input type="hidden" value="Expires: yesterday" id="original_sub_expire_label_' + newSubId + '">';
                    newSubHTML += '			 		<div id="sub_type_label_' + newSubId + '" class="sub_expire_label">Expires: yesterday		</div>';
                    newSubHTML += '			</span>	';
                    newSubHTML += '			<span name="action_buttons" id="sub_action_button_' + newSubId + '" class="sub_action_button" style="display: none;">';
                    newSubHTML += '				<div onclick="displayAssignOptions()" class="button">' + subStrings.assign + '</div>	';
                    newSubHTML += '			</span>	';
                    newSubHTML += '			<span name="sub_price_labels" id="sub_price_label_' + newSubId + '" class="sub_price_label"></span>';
                    newSubHTML += '	</div>';
							 
                    subsContainer.innerHTML += newSubHTML;
	            }
                else
                {
                    if(response.error == "authentication")
                        history.go(0);
                    else
                    {
                        if(response.error)
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	var params= 'method=addAdditionalTeamSubscription';
	
	ajaxRequest.open("POST", ".", false);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
	ajaxRequest.send(params); 
};

function routeAssignSubscription(subscriptionId)
{
	var userId = document.getElementById('userId').value;
		
	if (assignTeamSubscriptionToMe)
		assignTeamSubscriptionIdToMemberId(subscriptionId, userId);
	else
		sendTeamSubscriptionInvitation(subscriptionId);
};

function assignTeamSubscriptionIdToMemberId(subscriptionId, memberId)
{
	var ajaxRequest = getAjaxRequest(); 
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    loadOwnedSubscriptionsContent();	
                    hideModalWindow();
                }
                else
                {
                    if(response.error == "authentication")
                        history.go(0);
                    else
                    {
                        if(response.error)
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	var params= 'method=assignTeamSubscription&subscriptionid=' + subscriptionId + '&memberuid=' + memberId;
	
	ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
	ajaxRequest.send(params); 

};

function unassignTeamSubscription(subscriptionId, memberId)
{
	var ajaxRequest = getAjaxRequest(); 
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    loadOwnedSubscriptionsContent();	
                }
                else
                {
                    if(response.error == "authentication")
                        history.go(0);
                    else
                    {
                        if(response.error)
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	var params= 'method=unassignTeamSubscription&subscriptionid=' + subscriptionId + '&memberuid=' + memberId;
	
	ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
	ajaxRequest.send(params); 
};

function displayAssignOptions(subscriptionId)
{
	displayModalWindow('assign_subscription_modal');
	assignOptionSelected('me');
	friendOptionSelected('other');
	
	document.getElementById('assign_subscription_button').onclick = function(){routeAssignSubscription(subscriptionId)};	
};

function assignOptionSelected(type)
{
	var doc = document;
	
	var secondaryOptions = doc.getElementById('secondary_assign_options');
	var secondaryOptionsBody = doc.getElementById('secondary_assign_options_body');
	var assignTeamSubscriptionButton = doc.getElementById('assign_subscription_button');
	var userId = doc.getElementById('userId').value;
	
	if(type == 'me')
	{
		assignTeamSubscriptionToMe = true;
		
		secondaryOptions.style.display = 'none';
		secondaryOptionsBody.style.display = 'none';
		
		assignTeamSubscriptionButton.onClick = function(){};
	}
	else
	{
		assignTeamSubscriptionToMe = false;
		
		secondaryOptions.style.display = 'block';
		secondaryOptionsBody.style.display = 'block';
		
		assignTeamSubscriptionButton.onClick = function(){};
	}
};

function friendOptionSelected(type)
{
	var doc = document;
	
	var secondaryOptionsBody = doc.getElementById('secondary_assign_options_body');
	var fbOption = doc.getElementById('fb_friend_option');
	var otherOption = doc.getElementById('other_friend_option');
	
	var optionsHTML = '';
	
	if(type == "fb")
	{
		//set up UI
		fbOption.style.border = '1px solid black';
		fbOption.style.color = 'black';
		
		otherOption.style.border ='';
		otherOption.style.borderTop = '1px solid lightgray';
		otherOption.style.borderRight = '1px solid lightgray';
		otherOption.style.color = 'lightgray';
		
		//build option HTML
		optionsHTML += '[fb friends search goes here]';
	}
	else
	{
		//set up UI
		otherOption.style.border = '1px solid black';
		otherOption.style.color = 'black';
		fbOption.style.border ='';
		fbOption.style.borderTop = '1px solid lightgray';
		fbOption.style.borderLeft = '1px solid lightgray';
		fbOption.style.color = 'lightgray';
		
		//build option HTML
		optionsHTML += '<div class="option_wrapper">';
		optionsHTML += '    <span class="label" >'+labels.email+':</span>';
		optionsHTML += '    <input id="friend_email" type="text" placeholder="'+labels.your_friend_s_email_address+'">';
		optionsHTML += '</div>';
		optionsHTML += '<div class="option_wrapper">';
		optionsHTML += '    <span class="label" >'+labels.message+':</span>';
		optionsHTML += '    <textarea id="friend_message" placeholder="' + labels.optional_message + '"></textarea>';
		optionsHTML += '</div>';
	}
	
	secondaryOptionsBody.innerHTML = optionsHTML;
};

function sendTeamSubscriptionInvitation(subscriptionId, email)
{
	if (typeof(email)=='undefined')
		email = document.getElementById('friend_email').value;
	
	var ajaxRequest = getAjaxRequest(); 
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    loadOwnedSubscriptionsContent();	
                    hideModalWindow();
                }
                else
                {
                    if(response.error == "authentication")
                        history.go(0);
                    else
                    {
                        if(response.error)
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	var params= 'method=createTeamSubscriptionInvitation&subscriptionid=' + subscriptionId + '&email=' + email;
	
	ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
	ajaxRequest.send(params); 
};

function deleteTeamSubscriptionInvitation(invitationId)
{
	var ajaxRequest = getAjaxRequest(); 
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    loadOwnedSubscriptionsContent();	
                }
                else
                {
                    if(response.error == "authentication")
                        history.go(0);
                    else
                    {
                        if(response.error)
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}
	var params= 'method=deleteTeamSubscriptionInvitation&invitationid=' + invitationId;
	
	ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
	ajaxRequest.send(params);
};

/*
function displaySubscriptionPurchaseMode()
{
	var doc = document;
	var renewSubscriptionButton = doc.getElementById('renew_subscription_button');
	var addSubscriptionButtonWrapper = doc.getElementById('add_subscription_button_wrapper');
	var purchaseInfo = doc.getElementById('purchase_pricing_container');
	var checkboxes = doc.getElementsByName('subscriptions_checkboxes');
	var actionButtons = doc.getElementsByName('action_buttons');
	var subcriptionContainers = doc.getElementsByName('subscription_containers');
	var pricingPaymentInfoWrapper = doc.getElementById('pricing_payment_info_wrapper');
	//var ownedSubsContainer = doc.getElementById('owned_subscriptions_wrapper');
	
	//get subscriptions purchase info
	getSubscriptionPurchaseInfo();
	
	//hide elements
	for (var i = 0; i < actionButtons.length; i++)
		actionButtons[i].style.display = 'none';
		
	renewSubscriptionButton.style.display = 'none';	
	//addSubscriptionButtonWrapper.style.display = 'block';
		
	//display new elements
	for (var i = 0; i < checkboxes.length; i++)
		checkboxes[i].style.display = 'inline-block';
	
	purchaseInfo.style.display = 'block';
	pricingPaymentInfoWrapper.style.display = 'block';
	
	//update UI colors
	for (var i = 0; i < subcriptionContainers.length; i++)
		subcriptionContainers[i].style.color = 'gray';
		
	//add 'add a subscription' option
	
	var addSubHtml = '';
	
	addSubHtml += ' <div id="subscription_container_new_sub" name="subscription_containers" class="subscription_container" onclick="addSubscription()" style="cursor:pointer;">';	
	addSubHtml += ' 		<data id="subscription_purchase_info_new_sub"></data>	';
	addSubHtml += '			<input type="checkbox"  style="visibility:hidden;">';
	addSubHtml += ' 		<img alt="add subs img" src="https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif" /> ';	
	addSubHtml += ' 		<span class="labels_wrapper">';		
	addSubHtml += ' 			<div id="sub_owner_label_new_sub" class="sub_owner_label">Add a subscription';		
	addSubHtml += ' 			</div>';
	addSubHtml += ' 		</span>';
	addSubHtml += ' 	</div>';
	
	
	addSubscriptionButtonWrapper.innerHTML += addSubHtml;	
};

function hideSubscriptionPurchaseMode()
{
	history.go(0);
	/*
loadOwnedSubscriptionsContent();
	document.getElementById('purchase_pricing_container').style.display = 'none';
	document.getElementById('renew_subscription_button').style.display = 'inline-block';
	document.getElementById('payment_info_container')
*/
	
	/*
	var renewSubscriptionButton = document.getElementById('renew_subscription_button');
	//var addSubscriptionButton = document.getElementById('add_subscription_button');
	var purchaseInfo = document.getElementById('purchase_pricing_container');
	var checkboxes = document.getElementsByName('subscriptions_checkboxes');
	var actionButtons = document.getElementsByName('action_buttons');
	var subcriptionContainers = document.getElementsByName('subscription_containers');
	var priceLabels = document.getElementsByName('sub_price_labels');

	//hide elements
	for (var i = 0; i < checkboxes.length; i++)
	{
		checkboxes[i].style.display = 'none';
		checkboxes[i].checked = false;
	}
		
	purchaseInfo.style.display = 'none';
	
	//display elements
	for (var i = 0; i < actionButtons.length; i++)
		actionButtons[i].style.display = 'inline-block';
		
	renewSubscriptionButton.style.display = 'inline-block';	
	//addSubscriptionButton.style.display = 'inline-block';
		
	//update UI colors
	for (var i = 0; i < subcriptionContainers.length; i++)
		subcriptionContainers[i].style.color = '#333333';	
		
	//clear UI values	
	for (var i = 0; i < priceLabels.length; i++)
		priceLabels[i].innerHTML = '';
	
	for (var i = 0; i < selectedSubscriptions.length; i++)
		document.getElementById('sub_type_label_' + selectedSubscriptions[i]).innerHTML = document.getElementById('original_sub_expire_label_' + selectedSubscriptions[i]).value;
	
*/
	/*		
};

var selectedSubscriptions = [];
var pricingTable = {};
var currentSubPrice = {};


function selectSubscription(el, subscriptionId)
{
	var doc = document;
	var pricingTable = doc.getElementById('pricing_table');
	var unitPrice = pricingTable.getAttribute('price_ceiling_1');
	
	//update individual subscription UI
	if (el.checked)
	{
		if (selectedSubscriptions.indexOf(subscriptionId) + -1)
			selectedSubscriptions.push(subscriptionId);
			
		doc.getElementById('subscription_container_' + subscriptionId).style.color = '#333333';
		doc.getElementById('sub_price_label_' + subscriptionId).innerHTML = unitPrice;
		doc.getElementById('sub_type_label_' + subscriptionId).innerHTML = subStrings.expires + ': ' + doc.getElementById('subscription_purchase_info_' + subscriptionId).getAttribute('new_exp_date_label');
		el.setAttribute('checked', 'checked');	
	}
		
	else
	{
		selectedSubscriptions.pop(subscriptionId);	
		doc.getElementById('subscription_container_' + subscriptionId).style.color = 'gray';
		doc.getElementById('sub_price_label_' + subscriptionId).innerHTML = '';
		doc.getElementById('sub_type_label_' + subscriptionId).innerHTML = doc.getElementById('original_sub_expire_label_' + subscriptionId).value;
		el.removeAttribute('checked');
	}	
	
	//enable/disable 'purchase' button
	var purchaseButton = doc.getElementById('purchase_subscription_button');
	if (selectedSubscriptions.length > 0)
	{
		purchaseButton.setAttribute('onclick', 'purchaseTeamSubscription()');
		purchaseButton.setAttribute('class', 'button');
	}
	else
	{
		purchaseButton.setAttribute('class', 'button disabled');
		purchaseButton.removeAttribute('onclick');	
	}

	//display subtotal, discount and total price in UI
	calculateSubtotalDiscountAndTotalCharge();
};

function calculateSubtotalDiscountAndTotalCharge()
{
	var doc = document;
	
	var rate = currentSubPrice;
	var subs = selectedSubscriptions;
	var subsCount = subs.length;
	var pricingTable = doc.getElementById('pricing_table');
	var priceCeiling = {};
	
	if (subsCount >= 20)
		priceCeiling = 20;
	else if (subsCount >= 10)
		priceCeiling = 10;
	else if (subsCount >= 5)
		priceCeiling = 5;
	else
		priceCeiling = 1;
			
	var baseUnitPrice = pricingTable.getAttribute('price_ceiling_1');		
	var unitPrice = pricingTable.getAttribute('price_ceiling_' + priceCeiling);
	var subtotal = (subsCount * baseUnitPrice).toFixed(2);
	var totalPrice = (subsCount * unitPrice).toFixed(2);
	var discount = (totalPrice - subtotal).toFixed(2);
	var discountPercent = (subtotal != "0.00") ? (discount/subtotal).toFixed(2) * 100 : 0;
		
	doc.getElementById('subtotal_amount').innerHTML = subtotal;
	doc.getElementById('discount_amount').innerHTML = discount;
	doc.getElementById('total_amount').innerHTML = totalPrice;
	doc.getElementById('discount_percent').innerHTML = '(' + discountPercent + '%)';
};

function getSubscriptionPurchaseInfo()
{
	var doc = document;
	
	var userId = doc.getElementById('userId').value;
	
	var ajaxRequest = getAjaxRequest(); 
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    var newSubsInfo = response.newSubscriptionExpirations;
                    var pricingTable = response.teamPricingTable;
					var personalPricingTable = response.personalPricingTable;
                    var htmlContent = '';
                    var bracketCeilings = [1,5,10,20];
                    var pricingTableHtml = '';
                    
                    //store pricing table in page
                    for (var i = 0 ; i < bracketCeilings.length; i++)
                    {
                    	pricingTableHtml += ' price_ceiling_'+ bracketCeilings[i] +'= "' + pricingTable[bracketCeilings[i]] +'"';
                    }
                    
                    htmlContent += '<data id="pricing_table"' + pricingTableHtml + '/>';
                    
                    doc.getElementById('pricing_table_wrapper').innerHTML = htmlContent;
                    
                    //prep subscriptions availability
                    for (var i = 0; i < newSubsInfo.length; i++)
                    {
                    	var subInfo = newSubsInfo[i];
                    	var expirationDate = subInfo.new_expiration_date;
                    	var subId = subInfo.subscriptionid;
                    	var subPurchaseInfoEl = doc.getElementById('subscription_purchase_info_' + subId);
                    	var expirationDateLabel = subStrings.notEligible;
                    	
                    	if (expirationDate != 0)
                    	{
                    		expirationDateLabel = displayHumanReadableDate(expirationDate, false, true);
                    		subPurchaseInfoEl.setAttribute('is_eligible', true);
                    	}
                    	else
                    	{
                    		subPurchaseInfoEl.setAttribute('is_eligible', false);
                    		doc.getElementById('subscription_checkbox_' + subId).style.visibility = 'hidden';
                    		doc.getElementById('sub_type_label_' + subId).innerHTML += ' ('+ subStrings.notEligible + ')';
                    	}	
                    	
                    	subPurchaseInfoEl.setAttribute('new_exp_date_label', expirationDateLabel);
                    }
                    	
                }
                else
                {
                    if(response.error == "authentication")
                        history.go(0);
                    else
                    {
                        if(response.error)
                            displayGlobalErrorMessage(response.error);
                    }
                }
            }
            catch(err)
            {   
                displayGlobalErrorMessage("unknown response from server: " + err);
            }
		}
		
	}
	var params= 'method=getSubscriptionPurchaseInfo&userid=' + userId;
	
	ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
	ajaxRequest.send(params);

};
*/

function purchaseProAccount()
{
	var doc = document;
	
	//displayModalOverlay();
		
	
	if(cardOnFile)
	{
		processPurchaseWithCardOnFile();		
	}
	else
	{
        var overlayMessage = '<div class="progress_indicator" style="display:inline-block"></div> processing...';
        displayModalOverlay(null, overlayMessage);
    
		var objectForStripe = {
			name : doc.getElementById('name_on_card').value,
	        number: doc.getElementById('cc_number').value,
	        cvc: doc.getElementById('cvc').value,
	        exp_month: doc.getElementById('exp_date_month').value,
	        exp_year: doc.getElementById('exp_date_year').value};
	        
		Stripe.createToken( objectForStripe, processPurchaseResponseFromStripe);
	}
};

function switchToMonthlyBilling()
{
	var doc = document;
	
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
                    document.getElementById('subs_summary').innerHTML = '';
                    loadSubscriptionSettingContent();
                
//	                console.log('unlocking UI - success');
                    var bodyHTML = labels.your_account_has_been_successfully_updated;
					   
					var headerHTML = labels.success_e;
					
					var footerHTML = '';
					    footerHTML += '<div class="button" id="success_account_update_ok_button">' + labels.ok + '</div>';
					    
					displayModalContainer(bodyHTML, headerHTML, footerHTML);  
					doc.getElementById('success_account_update_ok_button').addEventListener('click', function(){
						hideModalContainer();
					}, false);
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //log in again
                        history.go(0);
                    }
                    else
                    {
                        if(response.error)
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
		}
	}

		var params = 'method=switchAccountToMonthly&subscriptionID=' + doc.getElementById('subscription_id').value;
		
		ajaxRequest.open("POST", ".", true);
	    
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);

};

function processPurchaseResponseFromStripe(status, response)
{
	var doc = document;
	//clear all error messages
	doc.getElementById('cc_number_error').setAttribute('style', '');
	doc.getElementById('exp_month_error').setAttribute('style', '');
	doc.getElementById('cvc_error').setAttribute('style', '');
	doc.getElementById('card_error').setAttribute('style', '');
	if (status == 200)
	{
		var totalCharge = doc.getElementById('total_amount').innerHTML;
		var nameOnCard = doc.getElementById('name_on_card').value;
		var cardNumber = doc.getElementById('cc_number').value.substring(12,16);
		var expDate = doc.getElementById('exp_date_month').value + '/' + doc.getElementById('exp_date_year').value;
		var stripeToken = response.id;
		
		var subTypeString = doc.getElementById('subscription_type').value == 'year' ? 'yearly' : 'monthly';
		var bodyHTML = '';
		
		bodyHTML += '<input type="hidden" name="totalCharge" value="' + doc.getElementById('total_amount').innerHTML + '" />';
		//bodyHTML += '<input type="hidden" name="method" value="purchaseTeamSubscription" />';
		bodyHTML += '<input type="hidden" id="stripeToken_for_user" value="' + stripeToken + '" />';
		bodyHTML += '<div id="purchase_confirmation_prompt">';
		bodyHTML += '	<p>' + labels.please_verify_the_following_information + ':</p>';
		bodyHTML += '	<h5>'+labels.purchase+' </h5>';
		bodyHTML += '	<ul class="simple_list">';
		bodyHTML += '		<li><span class="fixed_width">'+settingStrings.premiunAccount+' (' + labels[subTypeString] + ') - $'+ totalCharge + '</span></li>';
		bodyHTML += ' 	</ul>';
		bodyHTML += '	<div class="breath-10"></div>';
		bodyHTML += '	<h5>' + labels.billing_information + ':</h5>';
		bodyHTML += '	<ul class="simple_list">';
		bodyHTML += '		<li><span class="fixed_width">' + labels.name_on_card + ': </span><span>' + nameOnCard + '</span></li>';
		bodyHTML += '		<li><span class="fixed_width">' + labels.credit_card_number + ': </span><span>************' + cardNumber + '</span></li>';
		bodyHTML += '		<li><span class="fixed_width">' + labels.expiration_date + ': </span><span>' + expDate + '</span></li>';
		bodyHTML += '	</ul>';
		bodyHTML += '</div>';
		
		var headerHTML = labels.confirm_purchase;
		var footerHTML ='';
			footerHTML += '<div class="button" id="cancelConfirmPurchaseButton" onclick="hideModalContainer()">' + labels.cancel + '</div>';
			footerHTML += '<div class="button" id="confirmPurchaseButton" onclick="confirmPurchase()">' + labels.confirm + '</div>';
		
		displayModalContainer(bodyHTML, headerHTML, footerHTML);
		doc.getElementById('modal_overlay').onclick = null;
		doc.getElementById('modal_overlay_message').innerHTML = '';
	}
	else
	{
		hideModalOverlay();
//		console.log('unlocking UI - epic fail due to error in stripe returned status');
		var error = response.error;
		var errorCode = error.code;
		var statusEl = null;
		
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
		//displayGlobalErrorMessage('Process failed: ' + status + ' - ' + error.message);
	}
	 	
};

function processPurchaseWithCardOnFile()
{
	var doc = document;
	var totalCharge = doc.getElementById('total_amount').innerHTML;
	var subTypeString = doc.getElementById('subscription_type').value == 'year' ? 'yearly' : 'monthly';
	
	var bodyHTML = '';
	    bodyHTML += '<div id="purchase_confirmation_prompt">';
	    bodyHTML += '	<p>' + labels.please_verify_the_following_information + ':</p>';
	    bodyHTML += '	<h5>' + labels.purchase + '</h5>';
	    bodyHTML += '	<ul class="simple_list">';
		bodyHTML += '		<li><span class="fixed_width">'+settingStrings.premiunAccount+' (' + subTypeString + ') - $'+ totalCharge + '</span></li>';
		bodyHTML += ' 	</ul>';
	    bodyHTML += '	<h5>' + labels.billing_information + ':</h5>';
	    bodyHTML += '	<ul class="simple_list">';
	    bodyHTML += '		<li><span class="fixed_width">' + labels.name_on_card + ': </span><span>' + cardOnFile.name + '</span></li>';
	    bodyHTML += '		<li><span class="fixed_width">' + labels.credit_card_number + ': </span><span>************' + cardOnFile.last4 + '</span></li>';
	    bodyHTML += '		<li><span class="fixed_width">' + labels.expiration_date + ': </span><span>' + addLeadingZero(cardOnFile.exp_month) + '/' + cardOnFile.exp_year + '</span></li>';
	    bodyHTML += '	</ul>';
	    bodyHTML += '</div>';
	   
	var headerHTML = labels.confirm_purchase;
	
	var footerHTML = '';
	    footerHTML += '<div class="button" id="cancelConfirmPurchaseButton" onclick="hideModalContainer()">' + labels.cancel + '</div>';
	    footerHTML += '<div class="button" id="confirmPurchaseButton" onclick="confirmPurchase()">' + labels.confirm + '</div>';
	    
	displayModalContainer(bodyHTML, headerHTML, footerHTML);  
	doc.getElementById('modal_overlay').onclick = null;   
};

function confirmPurchase() 
{
    if(iapAutorenewing)
    {
        confirmSwitchBillingMethods();
        return;
    }

	//var subs = selectedSubscriptions;
	var doc = document;
	
	var confirmButton = doc.getElementById('confirmPurchaseButton');
		confirmButton.setAttribute('onclick', '');
		confirmButton.innerHTML += ' <div class="progress_indicator" style="display:inline-block"></div>';
	
	var subscriptionType = doc.getElementById('subscription_type').value;
    
    //Bug 6993 - if this has not been set, default to monthly
    if(subscriptionType != 'year')
        subscriptionType = 'month';
    
	var subscriptionId = doc.getElementById('subscription_id').value;
	
	var totalCharge = doc.getElementById('total_amount').innerHTML;
	var stripeToken = cardOnFile ? '' : doc.getElementById('stripeToken_for_user').value;
	
    //Bug 6995 - disable the cancel button once the user has confirmed purchase
    var cancelButton = document.getElementById('cancelConfirmPurchaseButton');
    	cancelButton.setAttribute('class', 'button disabled');
    	cancelButton.setAttribute('onclick', '');
    
    
	var ajaxRequest = getAjaxRequest(); 
		if(!ajaxRequest)
			return false;
	
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
	            try
	            {
	                var responseJSON = JSON.parse(ajaxRequest.responseText);
	                if(responseJSON.success)
	                {
                        document.getElementById('subs_summary').innerHTML = '';
                        loadSubscriptionSettingContent();
                    
//	                    console.log('unlocking UI - success');
	                    var bodyHTML = '';
						    bodyHTML += labels.your_account_has_been_successfully_updated ;
						   
						var headerHTML = labels.success_e;
						
						var footerHTML = '';
						    footerHTML += '<div class="button" id="success_account_update_ok_button">' + labels.ok + '</div>';
						    
						displayModalContainer(bodyHTML, headerHTML, footerHTML);  
						doc.getElementById('success_account_update_ok_button').addEventListener('click', function(){
							hideModalContainer();
						}, false);
	                }
	                else
	                {
	                    if(responseJSON.error == "authentication")
	                        history.go(0);
	                    else
	                    {
	                        if(responseJSON.error)
                                displayGlobalErrorMessage(labels.error_from_server + ' ' + responseJSON.error);
	                    }
                        displayGlobalErrorMessage(labels.unable_to_complete_purchase_for_unknown_reason);
	                    hideModalContainer();
	                    hideModalOverlay();
//	                    console.log('unlocking UI - epic fail: ' + responseJSON);
	                }
	            }
	            catch(e)
	            {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                    hideModalContainer();
	                hideModalOverlay();
//	                console.log('unlocking UI - super epic fail');
	            }
			}
		}
		/*
var subsIds = '';
		
		for (var i = 0; i < subs.length; i++)
		{
			subsIds += '&subscriptionIDs[]=' + subs[i];
		}
*/
		var params = '';
		
/*
		if(switchToMonthly && subscriptionType == 'month')
			params = 'method=switchAccountToMonthly&subscriptionID=' + subscriptionId;
		else
		{
*/
			params = 'method=purchasePremiumAccount&subscriptionType=' + subscriptionType + '&subscriptionID=' + subscriptionId + '&totalCharge=' + totalCharge;
			
			if (stripeToken.length > 0)
				params += '&stripeToken=' + stripeToken;
			else 
				params += '&last4=' + cardOnFile.last4;	
/* 		} */
		
		ajaxRequest.open("POST", ".", true);
	    
	    //Send the proper header information along with the request
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);

};

function confirmSwitchBillingMethods()
{
	var doc = document;
	
	var confirmButton = doc.getElementById('confirmPurchaseButton');
    confirmButton.setAttribute('onclick', '');
    confirmButton.innerHTML += ' <div class="progress_indicator" style="display:inline-block"></div>';
	
	var subscriptionType = doc.getElementById('subscription_type').value;
    
    //Bug 6993 - if this has not been set, default to monthly
    if(subscriptionType != 'year')
        subscriptionType = 'month';
    
	var subscriptionId = doc.getElementById('subscription_id').value;
	
	var totalCharge = doc.getElementById('total_amount').innerHTML;
	var stripeToken = doc.getElementById('stripeToken_for_user').value;
    
    if(!stripeToken || stripeToken.length == 0)
    {
        displayGlobalErrorMessage(labels.you_must_enter_card_valid_card);
        return;
    }
	
    //Bug 6995 - disable the cancel button once the user has confirmed purchase
    var cancelButton = document.getElementById('cancelConfirmPurchaseButton');
    cancelButton.setAttribute('class', 'button disabled');
    cancelButton.setAttribute('onclick', '');
    
	var ajaxRequest = getAjaxRequest();
    if(!ajaxRequest)
        return false;
	
    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            try
            {
                var responseJSON = JSON.parse(ajaxRequest.responseText);
                if(responseJSON.success)
                {
                    document.getElementById('subs_summary').innerHTML = '';
                    loadSubscriptionSettingContent();
                    
                    var bodyHTML = '';
                    bodyHTML += labels.your_credit_card_will_be_billed_once_you_cancel_your;
                    
                    var headerHTML = labels.success_e;
                    
                    var footerHTML = '';
                    footerHTML += '<div class="button" id="success_account_update_ok_button">' + labels.ok + '</div>';
                    
                    displayModalContainer(bodyHTML, headerHTML, footerHTML);
                    doc.getElementById('success_account_update_ok_button').addEventListener('click', function(){
                                                                                            hideModalContainer();
                                                                                            }, false);
                }
                else
                {
                    if(responseJSON.error == "authentication")
                        history.go(0);
                    else
                    {
                        if(responseJSON.error)
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + responseJSON.error);
                    }
                    displayGlobalErrorMessage(labels.unable_to_complete_purchase_for_unknown_reason);
                    hideModalContainer();
                    hideModalOverlay();
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                hideModalContainer();
                hideModalOverlay();
            }
        }
    }
    var params = '';
    
    params = 'method=switchBillingMethodsFromIAP&subscriptionType=' + subscriptionType + '&totalCharge=' + totalCharge + '&stripeToken=' + stripeToken;
    
    ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);
}

function loadExpirationYearOptions(menuId)
{
	var selectContainer = document.getElementById(menuId);
	var optionsHTML = '';
	var year = (new Date().getFullYear());
	
	for (var i = 0; i < 21; i++)
	{
		optionsHTML += '<option value="' + (year + i) + '">' + (year + i) + '</option>';
	}	
	selectContainer.innerHTML = optionsHTML;
	
};

function loadExpirationMonthOptions(menuId)
{
	var selectContainer = document.getElementById(menuId);
	var optionsHTML = '';
	
	optionsHTML += '<option value="01">01 - ' + monthStrings.january + '</option>';
	optionsHTML += '<option value="02">02 - ' + monthStrings.february + '</option>';
	optionsHTML += '<option value="03">03 - ' + monthStrings.march	+ '</option>';
	optionsHTML += '<option value="04">04 - ' + monthStrings.april	+ '</option>';
	optionsHTML += '<option value="05">05 - ' + monthStrings.may + '</option>';
	optionsHTML += '<option value="06">06 - ' + monthStrings.june + '</option>';
	optionsHTML += '<option value="07">07 - ' + monthStrings.july + '</option>';
	optionsHTML += '<option value="08">08 - ' + monthStrings.august + '</option>';
	optionsHTML += '<option value="09">09 - ' + monthStrings.september + '</option>';
	optionsHTML += '<option value="10">10 - ' + monthStrings.october + '</option>';
	optionsHTML += '<option value="11">11 - ' + monthStrings.november + '</option>';
	optionsHTML += '<option value="12">12 - ' + monthStrings.december + '</option>';
	
	selectContainer.innerHTML = optionsHTML;
};

function selectOptionInDropdownMenu(menuId, selectedValue)
{
	document.getElementById(menuId).value = selectedValue;
};

/*
function displayHelpElementWithId(elementId, displayStyle)
{
	el = document.getElementById(elementId);
	
	el.style.display = displayStyle;
	
	setTimeout(function(){el.style.opacity = '1.0';},50);
	
	return false;
};

function hideHelpElementWithId(elementId)
{
	document.getElementById(elementId).style.opacity = '0.0';
	setTimeout(function(){el.style.display = displayStyle = 'none';},1000);
	
	return false;
};

*/
var updateCCObject = {};

function updateCreditCardInfoSetting()
{
	var doc = document;
	var ccObject = {
		name : doc.getElementById('update_name_on_card').value,
        number: doc.getElementById('update_cc_number').value,
        cvc: doc.getElementById('update_cc_cvc').value,
        exp_month: doc.getElementById('update_exp_date_month').value,
        exp_year: doc.getElementById('update_exp_date_year').value
     };
     
     
//	console.log('locking UI');
	
	updateCCObject = {
		name : doc.getElementById('update_name_on_card').value,
        number: doc.getElementById('update_cc_number').value.substring(12,16),
        exp_date: doc.getElementById('update_exp_date_month').value + '/' + doc.getElementById('update_exp_date_year').value 
     };
     
	Stripe.createToken(ccObject, processUpdateCreditCardInfoResponseFromStripe);
};



function processUpdateCreditCardInfoResponseFromStripe(status, response)
{
	if (status == 200)
	{
		var doc = document;
		var stripeToken = response.id;
				
		var ajaxRequest = getAjaxRequest(); 
		if(!ajaxRequest)
			return false;
	
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
	            try
	            {
	                var responseJSON = JSON.parse(ajaxRequest.responseText);
	                if(responseJSON.success)
	                {
	                	var ccObject = updateCCObject;
	                	
	                	doc.getElementById('prev_name_on_card').innerHTML = ccObject.name;
	                	doc.getElementById('prev_cc_number').innerHTML = ccObject.number;
						doc.getElementById('prev_exp_date').innerHTML = ccObject.exp_date;
	                		                	
	                	
						hideSettingsDetailsWithUpdate('cc_setting', responseJSON.success);
	                }
	                else
	                {
	                    if(responseJSON.error == "authentication")
	                        history.go(0);
	                    else
	                    {
	                        if(responseJSON.error)
                                displayGlobalErrorMessage(labels.error_from_server + ' ' + responseJSON.error);
	                    }
	                }
	            }
	            catch(e)
	            {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
	            }
			}
		}

		var params = 'method=updatePaymentCardInfo&stripeToken=' + stripeToken;
	
		ajaxRequest.open("POST", ".", true);
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);
		
	}
	else
	{
		hideModalOverlay();
		
//		console.log('unlocking UI - epic fail due to error in stripe returned status');
		
		var error = response.error;
		displayGlobalErrorMessage(labels.process_failed + ': ' + status + ' - ' + error.message);
	}

};

/* !Invitations */

function displaySendInvitationsModal(listid)
{
	var doc = document;
	
	var bodyHTML = '<textarea placeholder="' + labels.enter_one_email_per_line + '" id="invitation_emails" style="height:100px;width:300px;"></textarea>';
	var headerHTML = labels.send_email_invitations;
	var footerHTML = '';
	
	
	footerHTML += '<div class="button disabled" id="sendInvitationsOkButton">' + labels.ok + '</div>';
	footerHTML += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	
	doc.getElementById('invitation_emails').addEventListener('keyup', shouldEnableSendInvitationButton, false);
    doc.getElementById('invitation_emails').focus();
};

function shouldEnableSendInvitationButton()
{
	var doc = document;
	var sendButton = doc.getElementById('sendInvitationsOkButton');
	
	if (doc.getElementById('invitation_emails').value.length > 0)
	{
		sendButton.setAttribute('class', 'button');
		sendButton.addEventListener('click', emailInviteOnlyList, false);
	}
	else
	{
		sendButton.setAttribute('class', 'button disabled');
		sendButton.removeEventListener('click', emailInviteOnlyList, false);
	}
}

function emailInviteOnlyList()
{
    var email = document.getElementById('invitation_emails').value;

    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
    if(!ajaxRequest)
    {
        return false;
    }
    // Create a function that will receive data sent from the server
    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                    history.go(0);
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                        }
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_send_invitations);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }
    
    
    var params = "method=emailInvites&email=" + email;
    
    ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);
    
    
}

//Delete server data functions
function showDeleteServerDataModal()
{
    var header = labels.confirm_password;
    
    var body = '<div id="delete_data_body">';
    body += '<div>' + labels.please_reenter_your_password + '</div>';
    body += '<input type="password" id="confirm_password_text_field" onkeyup="shouldEnableConfirmPasswordButton(event, this)"/>';
    body += '</div>';
    
    var footer = '<div id="delete_data_footer">';
    footer += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    footer += '<div class="button disabled" id="confirm_password_button">' + labels.confirm + '</div>';
    footer += '</div>';

    displayModalContainer(body, header, footer);
    document.getElementById('confirm_password_text_field').focus();

}

function displaySecondConfirmationForDeleteServerModal()
{
    var headerText = labels.delete_data_q;
    var bodyText = labels.this_will_permanently_delete_all;
    var footerButtons = '<div class="button" onclick="deleteServerData()">' + labels.delete + '</div>';
    footerButtons += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    
    //put this in a try catch in case the user dismissed the modal before the request completed
    try
    {
        document.getElementById('modal_header').innerHTML = headerText;
        document.getElementById('delete_data_body').innerHTML = bodyText;
        document.getElementById('delete_data_footer').innerHTML = footerButtons;
    }
    catch(e){}
}

function displayLoadingGifInDeleteServerModal()
{
    var footerButtons = '';
    //var bodyText = '<img src="https://s3.amazonaws.com/static.plunkboard.com/images/loading.gif"/> Deleting Data...';
    var bodyText = '<div class="progress_indicator" style="display:inline-block"></div> ' + labels.deleting_data;
    
    //put this in a try catch in case the user dismissed the modal before the request completed
    try
    {
        document.getElementById('delete_data_body').innerHTML = bodyText;
        document.getElementById('delete_data_footer').innerHTML = footerButtons;
    }
    catch(e){}    
    
}

function shouldEnableConfirmPasswordButton(event, el)
{
    var pswd = el.value;
    pswd = trim(pswd);

    var button = document.getElementById('confirm_password_button');
    if(pswd.length == 0)
    {
		button.setAttribute('class', 'button disabled');
		button.onclick = null;	
    }
    else
    {
        button.setAttribute('class', 'button');
		button.onclick = function(){verifyUserPassword();};
        
        if(event.keyCode == 13)
        {
            verifyUserPassword();
        }
    }
}

function verifyUserPassword()
{
    var pswd = document.getElementById('confirm_password_text_field').value;
    pswd = trim(pswd);
    
    if(pswd.length == 0)
        return;

    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
    if(!ajaxRequest)
    {
        return false;
    }
    // Create a function that will receive data sent from the server
    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                    displaySecondConfirmationForDeleteServerModal();
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                        }
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.failed_to_confirm_password+'.');
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.failed_to_confirm_password + ': ' + e);
            }
        }
    }
    
    
    var params = "method=verifyUserPassword&password=" + encodeURIComponent(pswd);
    
    ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);    
}

function deleteServerData()
{
    displayLoadingGifInDeleteServerModal();

    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
    if(!ajaxRequest)
    {
        return false;
    }
    // Create a function that will receive data sent from the server
    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            try
            {
                hideModalContainer();
                
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success == true)
                {
                    //There's not really anything to do once it succeeds
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                        {
                            //make the user log in again
                            history.go(0);
                        }
                        else
                        {
                            displayGlobalErrorMessage(labels.error_from_server + ' ' + response.error);
                        }
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.failed_to_delete_data  + '.');
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.failed_to_delete_data + ': ' + e);
            }
        }
    }
    
    
    var params = "method=wipeUserData";
    
    ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);       
}

function showListFilterBox(el, filterName)
{
    var doc = document;
    var listPicker = doc.getElementById(filterName + 'ListFilterPicker');
    
    //request list names from server
    var ajaxRequest = getAjaxRequest();  
    if(!ajaxRequest)
        return false;
    
    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            try
            {
                if(ajaxRequest.responseText != "")
                {
                    var responseJSON = JSON.parse(ajaxRequest.responseText);
                    
                    if(responseJSON.success == false && responseJSON.error=="authentication")
                    {
                        history.go(0);
                        return;
                    }
                    
                    if (responseJSON.success == true)
                    {
                        var listsJSON = responseJSON.lists;
                        var listsCount = listsJSON.length;
                    
                        if (listsCount > 0)
                        {
                            var listPickerHTML = '';
                            
                            for (var i = 0; i < listsCount; i++)
                            {
                                var listName = listsJSON[i].name;
                                if(listName === 'Inbox' && i===0){
                                    listName = controlStrings.inbox;
                                }
                                var listId = listsJSON[i].listid;
                                var selectedAttribute = '';
                                
                                doc.getElementById(filterName + '_list_filter_edit').style.visibility = 'hidden';
                                
                                var filterString;
                                if(filterName == 'all')
                                    filterString = allListFilterString;
                                else
                                    filterString = focusListFilterString;
                                
                                if (filterString.indexOf(listId) == -1)
                                    selectedAttribute = ' checked="true"';
                                
                                listPickerHTML += '<tr>';
                                listPickerHTML += '<td><input name="' + filterName + 'ListFilterOptions" type="checkbox" id="list_option_' + listId + '" ' + 'value="' + listId + '" ' + selectedAttribute + '/></td>';
                                listPickerHTML += '<td><label for="list_option_' + listId + '">'+ listName + '</label></td>';
                                listPickerHTML += '</tr>';
                            }
                            
                            listPicker.innerHTML = listPickerHTML;
                            doc.getElementById(filterName + '_list_filter_config').style.display = 'block';
                            //displaySettingDetails("focusListFilterSettingsDetailBox", el);
                        }
                    }
                    else
                        displayGlobalErrorMessage(labels.failed_to_retrieve_lists + ' ' + ajaxRequest.responseText);
                    
                }     
            }
            catch(e){
                displayGlobalErrorMessage(labels.error_from_server+ ' ' + e);
            }
         }
    }

    var params = "method=getControlContent&type=list";
    
    ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);	

}

function getListFilterStringSummary(filterName)
{
    var filterOptions = document.getElementsByName(filterName + "ListFilterOptions");
    
    var filterCount = 0;
    for(var i = 0; i < filterOptions.length; i++)
    {
        var filterOption = filterOptions[i];
        if(!filterOption.checked)
        {
            filterCount++;
        }
    }
    
    if(filterCount == 0)
        return labels.show_all;
        
    else
        return "" + filterCount + ' ' + labels.hidden;
}

function getListFilterString(filterName)
{
    var filterOptions = document.getElementsByName(filterName + "ListFilterOptions");
    
    var filterString = "";
    for(var i = 0; i < filterOptions.length; i++)
    {
        var filterOption = filterOptions[i];
        if(!filterOption.checked)
        {
            if(filterString.length > 0)
                filterString += ",";
                
            filterString += filterOption.value;
        }
    }
    
    if(filterString.length == 0)
        return "none";
        
    else
        return filterString;
}

function saveListFilterSetting(filterName)
{
    var newListFilterString = getListFilterString(filterName);
    var newListFilterSummary = getListFilterStringSummary(filterName);
    
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
//                    alert(ajaxRequest.responseText);
                if(response.success)
                {
                    document.getElementById(filterName + '_list_filter_config').removeAttribute('style');
                    showSuccessSettingUpdate(filterName + '_list_filter_setting', filterName + '_list_filter_edit', true);
                    document.getElementById(filterName + 'ListFilterStringSummary').innerHTML = newListFilterSummary;
                    
                    if(filterName == 'focus')
                        focusListFilterString = newListFilterString;
                    else
                        allListFilterString = newListFilterString;
                                            
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        //if it didn't save, revert the checkbox
                         cancelListFilterUpdate('focus');
                        displayGlobalErrorMessage(labels.unable_to_save_setting);
                    }
                }
            }
            catch(e)
            {
                cancelFocusListFilterUpdate('focus');
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }
    
    var params = "method=updateUserSettings&" + filterName + "_list_filter_string=" + newListFilterString;
    ajaxRequest.open("POST", "." , true);
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);         
    
}

/**** PROFILE IMAGE FUNCTIONS ******/

//vars s3baseUrl, imageGuid, and imageTimestamp are declared in AppSettingsContentAccount.php

function populateProfileImageDivs()
{
    if(imageGuid.length == 0 || imageTimestamp == 0)
    {
        document.getElementById('profile_image_details').innerHTML = getHTMLForImageUploader();
        document.getElementById('profile_image_edit').style.display = 'none';
        document.getElementById('profile_image_remove').style.display = 'none';
        
    }
    else
    {
        var imageURL = s3baseUrl + imageGuid + '?lastmod=' + imageTimestamp;
        document.getElementById('profile_image_details').innerHTML = '<img src="' + imageURL + '" width="50" height="50"/>';
        document.getElementById('profile_image_edit').style.display = 'inline-block';
        document.getElementById('profile_image_remove').style.display = 'inline-block';
    }
}

function getHTMLForImageUploader()
{
    var html = '';
    html += '<iframe name="upload_iframe" id="upload_iframe" style="display:none;"></iframe>';
    html += '<form name="pictureForm" method="post" autocomplete="off" enctype="multipart/form-data">';
    html += '<span>' + labels.upload_picture + ': </span>';
    html += '<div class="button" id="picture-btn" style="display:inline-block;">' + labels.choose_file + '</div>';
    html += '<input type="file" name="picture" id="picture" class="hidden" onchange="return ajaxFileUpload(this);" accept="image/jpeg, image/png, image/gif" />';
    html += '<div id="picture_error"></div>';
    html += '<div>';
    html += '<span id="picture_preview" ></span>';
    html += '<span id="picture_save_button" class="button" style="display:none;" onclick="saveProfileImage()">'+labels.save+'</span>';
    html += '<span id="picture_cancel_button" class="button" style="display:none;" onclick="cancelEditProfileImage()">' + labels.cancel + '</span>';
    html += '</div>';
    html += '</form>';
    
    return html;
}

function editProfileImageSetting()
{
    var html = getHTMLForImageUploader();
    document.getElementById('profile_image_config').innerHTML = html;
    document.getElementById('profile_image_config').style.display = 'block';
    document.getElementById('profile_image_edit').style.display = 'none';
    document.getElementById('profile_image_remove').style.display = 'none';
    document.getElementById('profile_image_details').innerHTML = '';
    
    document.getElementById('picture_cancel_button').style.display = 'inline-block';
    document.getElementById('picture_save_button').style.display = 'inline-block';
    document.getElementById('picture_save_button').setAttribute('class', 'button disabled');
    document.getElementById('picture_save_button').setAttribute('onclick', '');
}

function cancelEditProfileImage()
{
    document.getElementById('profile_image_config').innerHTML = '';
    document.getElementById('profile_image_config').style.display = 'none';
    
    populateProfileImageDivs();
}

function ajaxFileUpload(upload_field)
{
    // Checking file type
    var re_text = /\.png|\.jpg|\.gif|\.jpeg/i;
    var filename = upload_field.value;
    if (filename.search(re_text) == -1)
    {
        displayGlobalErrorMessage(labels.file_should_be_either);
        upload_field.form.reset();
        return false;
    }
//    document.getElementById('picture_preview').innerHTML = '<img src="https://s3.amazonaws.com/static.plunkboard.com/images/loading.gif" />';//'<div><img src="images/progressbar.gif" border="0" /></div>';
    document.getElementById('picture_preview').innerHTML = '<div class="progress_indicator" style="display:inline-block"></div>';

    document.getElementById('picture_error').innerHTML = '';
    document.getElementById('picture_save_button').style.display = 'inline-block';
    document.getElementById('picture_save_button').setAttribute('class', 'button disabled');
    document.getElementById('picture_save_button').setAttribute('onclick', '');
    document.getElementById('picture_cancel_button').style.display = 'inline-block';
    
    upload_field.form.action = '?method=uploadProfileImage';
    upload_field.form.target = 'upload_iframe';
    upload_field.form.submit();
    upload_field.form.action = '';
    upload_field.form.target = '';
    return true;
}

function saveProfileImage()
{
    document.getElementById('profile_image_config').style.display = 'none';
//    document.getElementById('profile_image_details').innerHTML = '<img src="https://s3.amazonaws.com/static.plunkboard.com/images/loading.gif" /> Saving Photo';
    document.getElementById('profile_image_details').innerHTML = '<div class="progress_indicator" style="display:inline-block"></div> ' + labels.saving_photo;

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
                    imageTimestamp = Math.round(new Date().getTime() / 1000);
                    if(response.imageguid)
                        imageGuid = response.imageguid;
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_save_photo);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
            
            cancelEditProfileImage();
        }
    }
    
    var params = "method=saveUploadedProfileImage";
    ajaxRequest.open("POST", "." , true);
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);       
}

function confirmRemoveProfileImage()
{
    var header = labels.remove_photo_q;
    
    var body = labels.are_you_sure_you_want_to_remove_this_photo;
    
    var footer = '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
    footer += '<div class="button" onclick="deleteProfileImage()">' + labels.remove + '</div>';
    
    displayModalContainer(body,header,footer);
}

function deleteProfileImage()
{
    hideModalContainer();

    document.getElementById('profile_image_config').style.display = 'none';
    document.getElementById('profile_image_details').innerHTML = '<div class="progress_indicator" style="display:inline-block"></div> ' + labels.deleting_photo;

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
                    imageTimestamp = 0;
                    imageGuid = '';
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_remove_photo+ '.');
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_remove_photo + ': ' + e);
            }
            
            populateProfileImageDivs();
        }
    }
    
    var params = "method=removeProfileImage";
    ajaxRequest.open("POST", "." , true);
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);       
};



/* re-migration functions */
function shouldDisplayMigrationButton()
{
	var doc = document;
	
	/*something will happen to determine whether or not we display the re-migrate button*/
	
	doc.getElementById('migrate_date').style.display = 'block';	
};

function showMigrationConfirmationDialog()
{
	var doc = document;
	var headerHTML = labels.migrate_again_q;
	var bodyHTML  = '<p style="width:400px;line-height:1.6rem;margin:0">'+labels.this_will_read_your_tasks_from+'</p>';
		bodyHTML += '<div class="labeled_control"style="margin: 20px auto 10px"><label style="width:190px;padding-right:6px">' + labels.old_todo_online_password +':</label><input id="migrate_password" type="password" /></div>';
		
	var footerHTML = '<div class="button" onclick="remigrateAccount()">Proceed</div><div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
};

function remigrateAccount()
{
	var doc = document;
	var password = doc.getElementById('migrate_password').value;
	
	hideModalContainer();

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
                	var headerHTML = labels.success_e;
                	var bodyHTML =  '<p style="margin:0">' + labels.your_account_will_be_updated_shortly + '</p>';
                	var footerHTML = '<div class="button" onclick="history.go(0)">' + labels.ok + '</div>';
                	
                	displayModalContainer(bodyHTML, headerHTML, footerHTML);
                }
                else
                {
                    if(response.error == "authentication")
                    {
                        //make the user log in again
                        history.go(0);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.please_use_your_old_todo_online_password_and_try_again );
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_remigrate + ': ' + e);
            }
            
        }
    }
    
    var params = 'method=reMigrateUserData&password=' + password;
    ajaxRequest.open("POST", "." , true);
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);   
};

/**
 * This part of code is related to 2.4.0
 */
jQuery(document).ready(function () {
    var current_hash = location.hash; // get hash of current page

    /**
     * Activate tab with needed hash
     */
    if (current_hash) {
        if (current_hash == '#invited-members-btn') {
            displayInviteMemberModal();
        }
        if (current_hash == '#invited-members-of') {
            if (jQuery('.item-list .item').size()) {
                setTimeout(function(){
                    jQuery('.item-list .item:nth-child(2) .view-actions.fa-caret-down').click();
                }, 800)
            }
        }
        if (current_hash == '#invited-members-of' || current_hash == '#invited-members-btn') {
            current_hash = 'invited-members';
            var new_history = location.origin + '/' + location.search + '#' + current_hash;
            if (window.history && history.pushState) {
                history.pushState("", "", new_history);
            } else {
                location.hash = hash;
            }
        }
        if (jQuery(current_hash).size() && jQuery('ul.tabs li[data-tab="' + current_hash.substr(1) + '"]:not(.disabled)').size()) {
            jQuery('ul.tabs li, .tab-content').removeClass('current');
            jQuery('ul.tabs li[data-tab="' + current_hash.substr(1) + '"]').addClass('current');
            jQuery(current_hash).addClass('current');
        }
    }

    jQuery(document).on('click', '.btn-invite-member', function () {
        displayInviteMemberModal();
    });
    /**
     * Tabs functionality
     */
    jQuery('ul.tabs li:not(.disabled)').on('click', function (e) {
		e.stopImmediatePropagation();
		if (jQuery(this).hasClass('current')) {
			return false;
		}
        var tab_id = jQuery(this).attr('data-tab');
        var hash = '#' + tab_id;
        var new_history = location.origin + '/' + location.search + hash;
        if (hash !== current_hash) {
            jQuery('ul.tabs li').removeClass('current');
            jQuery('.tab-content').removeClass('current');
			current_hash = hash;
            jQuery(this).addClass('current');

            //Tab saved in browser history
            jQuery("#" + tab_id).addClass('current');
            if (window.history && history.pushState) {
                history.pushState("", "", new_history);
            } else {
                location.hash = hash;
            }
        }
    });
    jQuery('#profile_image_setting').on('click', '#picture-btn', function () {
        jQuery('#picture').click();
    });
});
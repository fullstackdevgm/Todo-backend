
function dateStringFromUnixTimestamp(timestamp)
{
	var date = new Date(timestamp * 1000); // convert to milliseconds
	var months = monthsStrings;
	var year = date.getFullYear();
	var month = months[date.getMonth()];
	var day = date.getDate();
	var dateString = day + " " + month + " " + year;
	return dateString;
};

function switchToCreateTeamMode()
{
	window.location = "?appSettings=show&option=teaming&action=createTeam";
};

function updateModifyTeamPricing(numOfPaidLicenses, numOfCurrentLicenses, expirationDate, billingFrequency)
{
	updateCreateTeamPricing();
        if (jQuery('.createTeam').size() == 0) {
        updateChangeDisplayInfo();
    }
};

function updateCreateTeamPricing()
{
	var doc = document;
	var inputElement = doc.getElementById('num_of_members');
	var stringValue = inputElement.value;
	if (stringValue.length == 0)
	{
        inputElement.value = "";
		return setTeamPricingForNumberOfMembers(0);
	}
	else
	{
		// Make sure that the value entered is a numeric value
		var numValue = Math.abs(parseInt(stringValue, 10));
		if (isNaN(numValue))
		{
			inputElement.value = "";
			return setTeamPricingForNumberOfMembers(0);
		}
        inputElement.value = numValue;
		return setTeamPricingForNumberOfMembers(numValue);
	}
};

function setTeamPricingForNumberOfMembers(numOfMembers)
{
	var normalPricingElement = document.getElementById('team_create_pricing_options_normal');
	var customPricingElement = document.getElementById('team_create_pricing_options_custom');
    var usedLicenseCount = parseInt(jQuery('.current-count').text());

    var team_tos_agree = document.getElementById('team_tos_agree');
    var purchaseButton = document.getElementById('team_purchase_button');
    var enable_button = true;
    if (team_tos_agree.checked == false) {
        enable_button = false;
    }

	if (numOfMembers > 500)
	{
		normalPricingElement.className = "team_item_hidden";
		customPricingElement.className = "team_price_warning";
        enable_button = false;
	}
	else
	{
		normalPricingElement.className = "";
		customPricingElement.className = "team_item_hidden";
		
		var monthlyLabel = document.getElementById('monthly_team_price');
		var yearlyLabel = document.getElementById('yearly_team_price');
		var yearlySavingsLabel = document.getElementById('yearly_savings');
		
		var monthlyPrice = pricingWithBasePriceAndLicenseCount(monthlyTeamPrice, numOfMembers);
		var yearlyPrice = pricingWithBasePriceAndLicenseCount(yearlyTeamPrice, numOfMembers);
		var yearlySavings = ((monthlyPrice * 12) - yearlyPrice);
		yearlySavings = yearlySavings.toFixed(2);
		
		var monthlyString = "$" + monthlyPrice;
		var yearlyString = "$" + yearlyPrice;
		var yearlySavingsString = "$" + yearlySavings;
		
		if ("innerText" in monthlyLabel)
		{
			monthlyLabel.innerText = monthlyString;
			yearlyLabel.innerText = yearlyString;
			yearlySavingsLabel.innerText = yearlySavingsString;
		}
		else
		{
			monthlyLabel.textContent = monthlyString;
			yearlyLabel.textContent = yearlyString;
			yearlySavingsLabel.textContent = yearlySavingsString;
		}
        if(usedLicenseCount > numOfMembers){
            enable_button = false;
        }
	}
    if (enable_button) {
        purchaseButton.classList.remove('disabled');
    } else {
        purchaseButton.classList.add('disabled');
    }
};

function pricingWithBasePriceAndLicenseCount(basePrice, numOfLicenses)
{
	var subtotalPrice = basePrice * numOfLicenses;
	var discountFactor = 1.0; //discountFactorForLicenseCount(numOfLicenses)
	var totalPrice = subtotalPrice * discountFactor;
	
	totalPrice = Math.round(totalPrice * Math.pow(10, 2)) / Math.pow(10, 2);
	totalPrice = totalPrice.toFixed(2);
	
	return totalPrice;
};

//function discountFactorForLicenseCount(numOfLicenses)
//{
//	if (numOfLicenses >= 20)
//		return 0.8;		// 20% discount
//	
//	if (numOfLicenses >= 10)
//		return 0.9;		// 10% dicsount
//	
//	if (numOfLicenses >= 5)
//		return 0.95;	// 5% discound
//	
//	return 1.0;			// No discount for teams less than 5 users
//};

function updateBillingFrequencySelection(element)
{
    var radio = jQuery('#team_create_pricing_options_normal .team_pricing_option input[name="billing_frequency"]');
    var isFrequencyChange = parseInt(element.data('change-frequency'));
    radio.each(function(){
        var wrapper = jQuery(this).parents('.team_pricing_option');
        wrapper.toggleClass('selected');
    });
	if (isFrequencyChange)
	{
		updateChangeDisplayInfo();
	}
};


function updateChangeDisplayInfo()
{
	var doc = document;
	
	var origLicenseCount = doc.getElementById('orig_current_license_count').value;
	var origNewLicenseCount = doc.getElementById('orig_new_license_count').value;
	var origBillingFrequency = doc.getElementById('orig_billing_frequency').value;
	var origExpirationDate = doc.getElementById('orig_expiration_date').value;
		
	var numString = doc.getElementById('num_of_members').value;
	var numString = numString.trim();
	var numOfMembers = parseInt(numString, 10);
	if (isNaN(numOfMembers))
	{
		numOfMembers = 0;
	}
	
	var billingFrequency = 0; // unknown
	if (doc.getElementById('monthly_radio').checked)
		billingFrequency = 1; // monthly
	else
		billingFrequency = 2; // yearly
	
	var membersToAdd = 0;
	var membersToRemove = 0;
	var newExpirationDate = origExpirationDate;
	
	if (numOfMembers < origNewLicenseCount)
	{
		membersToRemove = origNewLicenseCount - numOfMembers;
	}
	else if (numOfMembers > origNewLicenseCount)
	{
		membersToAdd = numOfMembers - origNewLicenseCount;
	}
	
	doc.getElementById('team_change_members_to_add').innerHTML = membersToAdd;
	doc.getElementById('team_change_members_to_remove').innerHTML = membersToRemove;
	
	// Utility date variables
	var now = Math.round(new Date().getTime() / 1000);
	var oneDayInSeconds = 60 * 60 * 24;
	var oneYearInSeconds = oneDayInSeconds * 365;
	var oneMonthInSeconds = oneDayInSeconds * 31;
	var secondsLeft = origExpirationDate - now;
	var monthsLeft = Math.round(secondsLeft / oneMonthInSeconds);
	
	var currentAccountCredit = 0.0;
	var bulkDiscount = 0.0;
	var totalCharge = 0.0;
	
	if (origExpirationDate < now)
	{
		// Set the origNewLicenseCount to 0 so the team is charged again to
		// renew for their selection. Also set the newExpirationDate accordingly.
		origLicenseCount = 0;
		origNewLicenseCount = 0;
		if (billingFrequency == 1)
		{
			newExpirationDate = now + monthlySubscriptionTimeInSeconds;
			monthsLeft = 1;
		}
		else
		{
			newExpirationDate = now + yearlySubscriptionTimeInSeconds;
			monthsLeft = 12;
		}
	}
	
	
	// Scenario 0: Nothing has changed
	if ((billingFrequency == origBillingFrequency) && (numOfMembers == origNewLicenseCount))
	{
		// Nothing to do. Intentionally blank.
	}
	else if (billingFrequency == origBillingFrequency)
	{
		if (numOfMembers < origLicenseCount)
		{
			//
			// Scenario 1: Reduce the number of team members only
			//
			
			// Nothing to do
		}
		else if (numOfMembers > origLicenseCount)
		{
			// If there are 0 months left in the subscription, there were 15 or
			// fewer days left in the current subscription. Let all new members
			// join in free and they will start with the new billing when the
			// next cycle rolls around.
			if (monthsLeft > 0)
			{
				// We need to charge for the new users
				var discountRate = 0.00;
//				if (numOfMembers >= 20)
//					discountRate = 0.2;
//				else if (numOfMembers >= 10)
//					discountRate = 0.1;
//				else if (numOfMembers >= 5)
//					discountRate = 0.05;
				var baseCost = monthlyTeamPrice;
				if (billingFrequency == 2)
				{
					// Prorate based on the number of months left.
					baseCost = yearlyTeamPrice;
					var monthlyCost = baseCost / 12;
					baseCost * monthsLeft;
				}
				
				var numOfNewPaidMembers = numOfMembers - origLicenseCount;
				var subtotal = numOfNewPaidMembers * baseCost;
				bulkDiscount = subtotal * discountRate;
				totalCharge = subtotal - bulkDiscount;
			}
		}
	}
	else if ((origBillingFrequency == 1) && (billingFrequency == 2))
	{
		// Changing billing from MONTHLY to YEARLY
		
		// New expiration date is ALWAYS ONE year from NOW
		newExpirationDate = now + yearlySubscriptionTimeInSeconds;
		
		var baseCost = numOfMembers * yearlyTeamPrice;
		var discountRate = 0.00;
//		if (numOfMembers >= 20)
//			discountRate = 0.2;
//		else if (numOfMembers >= 10)
//			discountRate = 0.1;
//		else if (numOfMembers >= 5)
//			discountRate = 0.05;
		bulkDiscount = baseCost * discountRate;
		var subtotal = baseCost - bulkDiscount;
		
		
		// Remaining expiration is greater than 14 days, so deduct a credit
		// based on the number of months.
		if (monthsLeft > 0)
		{
			discountRate = 0.0;
//			if (origLicenseCount >= 20)
//				discountRate = 0.2;
//			else if (origLicenseCount >= 10)
//				discountRate = 0.1;
//			else if (origLicenseCount >= 5)
//				discountRate = 0.05;
			var origCost = origLicenseCount * monthlyTeamPrice;
			var origBulkDiscount = origCost * discountRate;
			origCost = origCost - origBulkDiscount;
			
			currentAccountCredit = origCost * monthsLeft;
		}
		
		totalCharge = subtotal - currentAccountCredit;
	}
	else if ((origBillingFrequency == 2) && (billingFrequency == 1))
	{
		if ((origExpirationDate < now) || (monthsLeft == 0))
		{
			// Have already expired. Need to charge anew for whatever the user
			// has selected.
			newExpirationDate = now + monthlySubscriptionTimeInSeconds;
			var baseCost = numOfMembers * monthlyTeamPrice;
			var discountRate = 0.00;
//			if (numOfMembers >= 20)
//				discountRate = 0.2;
//			else if (numOfMembers >= 10)
//				discountRate = 0.1;
//			else if (numOfMembers >= 5)
//				discountRate = 0.05;
			bulkDiscount = baseCost * discountRate;
			totalCharge = baseCost - bulkDiscount;
		}
		else
		{
			if (numOfMembers <= origLicenseCount)
			{
				// Nothing changes other than the billing frequency. When the
				// next billing date occurs, they will be billed monthly.
				
				// No need to charge anything
			}
			else
			{
				// The expiration date needs to be adjusted to account for more
				// people being added into the team subscription.
				var origPayment = yearlyTeamPrice * origLicenseCount;
				var origPaymentPerMonth = origPayment / 12;
				// Now determine how many
				var origCredit = origPaymentPerMonth * monthsLeft;
				var newMonthlyCost = numOfMembers * monthlyTeamPrice;
				
				if (origCredit > newMonthlyCost)
				{
					// Determine how many months are left in the subscription
					// and adjust the new expiration date accordingly.
					var monthsAvailable = Math.round(origCredit / newMonthlyCost);
					newExpirationDate = now + (monthsAvailable * monthlySubscriptionTimeInSeconds);
					
					// No need to charge anything
				}
				else
				{
					// In this case, the original credit does NOT cover the new
					// cost of the total number of members being added and the
					// user will need to pay to make up the difference.
					
					// Set the new expiration date to one month from now.
					newExpirationDate = now + monthlySubscriptionTimeInSeconds;
					
					var unpaidAmount = newMonthlyCost - origCredit;
					var discountRate = 0.0;
//					if (numOfMembers >= 20)
//						discountRate = 0.2;
//					else if (numOfMembers >= 10)
//						discountRate = 0.1;
//					else if (numOfMembers >= 5)
//						discountRate = 0.05;
					bulkDiscount = unpaidAmount * discountRate;
					totalCharge = unpaidAmount - bulkDiscount;
				}
			}
		}
	}
	
//	var numOfUsersToAdd = 0;
//	if (numOfNewPaidMembers > origLicenseCount)
//		numOfUsersToAdd = numOfNewPaidMembers - origLicenseCount;
//	var numOfUsersToRemove = 0;
//	if (numOfNewPaidMembers < origLicenseCount)
//		numOfUsersToRemove = origLicenseCount - numOfNewPaidMembers;
	
	if ((totalCharge < 0) || isTeamTrialPeriod)
	{
		bulkDiscount = 0.00;
		currentAccountCredit = 0.00;
		totalCharge = 0.00;
	}
	
	totalCharge = totalCharge.toFixed(2);


	
	// Show the user the new expiration date and the total charge needed to
	// make the change.
	var newExpirationDateString = dateStringFromUnixTimestamp(newExpirationDate);
	doc.getElementById('team_change_expiration_date').innerHTML = newExpirationDateString;
	
	var amountDueString = "$" + totalCharge + " USD";
	doc.getElementById('team_change_amount_due').innerHTML = amountDueString;
	
};


function updateBizZipLength(event, inputElement)
{
	var bizZip = document.getElementById('biz_zip');
	
	if (inputElement.value == 'US')
	{
		bizZip.setAttribute('maxlength', '5');
		
		var bizZipString = bizZip.value;
		bizZipString = bizZipString.trim();
		
		if (bizZipString.length > 5)
		{
			// Scale it back to fit into the United States constraints of 5 digits
			bizZipString = bizZipString.substring(0, 5);
			
			var bizZipNumber = parseInt(bizZipString);
			if (isNaN(bizZipNumber))
			{
				bizZip.value = "";
			}
			else
			{
				bizZip.value = bizZipNumber;
			}
		}
	}
	else
	{
		bizZip.setAttribute('maxlength', '127');
	}
};


function zipCodeCharsEntered(event)
{
	// If the country is not the US, don't worry about doing anything here
	var bizCountry = document.getElementById('biz_country');
	if (bizCountry.value != 'US')
		return;
	
	var theEvent = event || window.event;
	var key = theEvent.keyCode || theEvent.which;
	
	if (key < 32)
		return;
	
	key = String.fromCharCode(key);
	var regex = /[0-9]/;
	if (!regex.test(key))
	{
		theEvent.returnValue = false;
		if (theEvent.preventDefault)
			theEvent.preventDefault();
	}
};


function displayCreateTeamModal()
{
	var bodyHTML = '';
	var headerHTML = labels.create_a_team;
	var footerHTML = '';
	
	bodyHTML += '<div class="breath-4"></div>';
	bodyHTML += '<div><input id="teamNameInput" type="text" class="centered_text_field" placeholder="' + labels.enter_a_team_name + '" onkeyup="shouldEnableCreateTeamButton(event, this)"/></div>';
	
	footerHTML += '<div class="button" onclick="hideModalContainer();">' + labels.cancel + '</div>';
	footerHTML += '<div id="createTeamButton" class="button disabled">'+labels.create+'</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML, null, true);
	document.getElementById('teamNameInput').focus();
	document.getElementById('teamNameInput').select();
};


function deleteTeamModal(teamID, deleteTeamAllowed)
{
	var bodyHTML = '';
	var headerHTML = labels.delete_this_team ;
	var footerHTML = '';
	
	bodyHTML += '<div style="width:480px;">';
	
	if (deleteTeamAllowed)
	{
        bodyHTML += '<div>' + labels.are_you_sure_you_want_to_delete + '</div>';
		footerHTML += '<div class="button" id="cancelDeleteTeamButton" onclick="hideModalContainer();">' + labels.cancel + '</div>';
		footerHTML += '<div id="deleteTeamButton" class="button" onclick="completeDeleteTeam(\'' + teamID + '\');">' + labels.delete + '</div>';
	}
	else
	{
        bodyHTML += '<div>' + labels.this_team_cannot_be_deleted + '</div>';
		footerHTML += '<div class="button" onclick="hideModalContainer();">' + labels.cancel + '</div>';
	}
	
	bodyHTML += '</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML, null, true);
    document.getElementById('modal_overlay').onclick = null;
};


function completeDeleteTeam(teamID)
{
	var doc = document;
	
	var cancelButton = doc.getElementById('cancelDeleteTeamButton');
	cancelButton.setAttribute('onclick', '');
	cancelButton.setAttribute('class', 'button disabled');
	
	var confirmButton = doc.getElementById('deleteTeamButton');
	confirmButton.setAttribute('onclick', ''); // Prevent the button from being pressed twice
	confirmButton.innerHTML = ' <div class="progress_indicator" style="display:inline-block;"></div>';
	
	var ajaxRequest = getAjaxRequest();
	if (!ajaxRequest)
	{
		displayGlobalErrorMessage(labels.unable_to_delete_team);
		hideModalContainer();
		hideModalOverlay();
		return;
	}
	
	ajaxRequest.onreadystatechange = function()
	{
		if (ajaxRequest.readyState == 4)
		{
			try
			{
				var responseJSON = JSON.parse(ajaxRequest.responseText);
				if (responseJSON.success)
				{
					window.location = "?appSettings=show&option=teaming";
					hideModalContainer();
				}
				else
				{
					displayGlobalErrorMessage(responseJSON.error);
					hideModalContainer();
					hideModalOverlay();
					return;
				}
			}
			catch(e)
			{
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
				hideModalContainer();
				hideModalOverlay();
				return;
			}
		}
	}
	
	var params = "method=deleteTeamAccount";
	params += "&teamID=" + encodeURIComponent(teamID);
	
	ajaxRequest.open("POST", ".", true);
	ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function updatePurchaseButtonEnablement(event, inputElement, insideChangeTeam)
{
    var usedLicenseCount = parseInt(jQuery('.current-count').text());

	var validateFunction;
	if (insideChangeTeam)
		validateFunction = validateChangeTeamAndShowPurchaseModal;
	else
		validateFunction = validateTeamCreateAndShowPurchaseModal;
	
	var purchaseButton = document.getElementById('team_purchase_button');
    var enable_button = true;
    var num_of_members_el = jQuery('#num_of_members');
    if (num_of_members_el.size() && (parseInt(num_of_members_el.val()) <= 0 || parseInt(num_of_members_el.val()) > 500 || parseInt(num_of_members_el.val()) < usedLicenseCount)) {
        enable_button = false;
    }
    if (inputElement.checked == false) {
        enable_button = false;
    }
    if (enable_button)
	{
        purchaseButton.classList.remove('disabled');
		if (purchaseButton.addEventListener)
		{
			// All browsers and IE 9
			purchaseButton.addEventListener("click", validateFunction, false);
		}
		else if (purchaseButton.attachEvent)
		{
			// Before IE 9
			purchaseButton.attachEvent("click", validateFunction);
		}
	}
	else
	{
        purchaseButton.classList.add('disabled');
        if (purchaseButton.removeEventListener)
		{
			// All browsers including IE 9
			purchaseButton.removeEventListener("click", validateFunction);
		}
		else if (purchaseButton.detachEvent)
		{
			// Before IE 9
			purchaseButton.detachEvent("click", validateFunction);
		}
	}
}


function enableUpdateBillingInfoButton(event, inputElement)
{
    var purchaseButton = document.getElementById('team_purchase_button');
    var enable_button = true;
    if (jQuery('#num_of_members').size() && (parseInt(jQuery('#num_of_members').val()) <= 0 || parseInt(jQuery('#num_of_members').val()) > 500)) {
        enable_button = false;
    }
    if (inputElement.checked == false) {
        enable_button = false;
    }
    if (enable_button) {
        purchaseButton.classList.remove('disabled');
		if (purchaseButton.addEventListener)
		{
			// All browsers and IE 9
			purchaseButton.addEventListener("click", updateBillingInfo, false);
		}
		else if (purchaseButton.attachEvent)
		{
			// Before IE 9
			purchaseButton.attachEvent("click", updateBillingInfo);
		}
	}
	else
	{
        purchaseButton.classList.add('disabled');
		if (purchaseButton.removeEventListener)
		{
			// All browsers including IE 9
			purchaseButton.removeEventListener("click", updateBillingInfo);
		}
		else if (purchaseButton.detachEvent)
		{
			// Before IE 9
			purchaseButton.detachEvent("click", updateBillingInfo);
		}
	}
}


function updateBillingInfo()
{
    var purchaseButton = document.getElementById('team_purchase_button');
    if(purchaseButton.classList.contains('disabled')){
        return false;
    }
	var everythingIsValid = true;
	
	// Validate Credit Card #
	var bizCardNumber = getValueOrError('ccard_number', 'ccard_number_label');
	if (!bizCardNumber)
		everythingIsValid = false;
	
	// Validate Name on Card
	var bizCardName = getValueOrError('ccard_name', 'ccard_name_label');
	if (!bizCardName)
		everythingIsValid = false;
	
	// Validate CVC
	var bizCardCVC = getValueOrError('ccard_cvc', 'ccard_cvc_label');
	if (!bizCardCVC)
		everythingIsValid = false;
	
	// Validate Expiry Month
	var bizCardMonth = getValueOrError('ccard_month', 'ccard_month_label');
	if (!bizCardMonth)
		everythingIsValid = false;
	
	// Validate Expiry Year
	var bizCardYear = getValueOrError('ccard_year', 'ccard_year_label');
	if (!bizCardYear)
		everythingIsValid = false;
	
	// TOS MUST be accepted (shouldn't be able to get here without that)
    if (jQuery('#num_of_members').size() && (parseInt(jQuery('#num_of_members').val()) <= 0 || parseInt(jQuery('#num_of_members').val()) > 500)) {
        everythingIsValid = false;
    }
	var teamValidationError = document.getElementById('team_validate_error');
	
	if (!everythingIsValid)
	{
		// Something required wasn't filled in
		teamValidationError.innerHTML = labels.please_enter_the_missing_information ;
		teamValidationError.className = "team_validation_error_message width_full";
		return;
	}
	
	// Everything was filled in! Time to review and purchase.
	teamValidationError.className = "team_validation_error_message width_full team_item_hidden";
	
	// We need to charge the user by creating a Stripe Token
	var cardName = document.getElementById('ccard_name').value.trim();
	var cardNumber = document.getElementById('ccard_number').value.trim();
	var cardCVC = document.getElementById('ccard_cvc').value.trim();
	var cardExpMonth = document.getElementById('ccard_month').value.trim();
	var cardExpYear = document.getElementById('ccard_year').value.trim();
	
	var objectForStripe = {
	name: cardName,
	number: cardNumber,
	cvc: cardCVC,
	exp_month: cardExpMonth,
	exp_year: cardExpYear
	};
	
	purchaseButton.innerHTML = ' <div class="progress_indicator" style="display:inline-block;"></div>';
	
	Stripe.createToken(objectForStripe, processUpdateBillingInfoResponse);
};


function processUpdateBillingInfoResponse(status, response)
{
	var purchaseButton = document.getElementById('team_purchase_button');
	
	if (status == 200)
	{
		// Read all the saved values from the doc
		var teamID = document.getElementById('teamID').value;
		var stripeToken = response.id;
		var stripeTokenElement = document.getElementById('stripe_token');
		stripeTokenElement.value = stripeToken;
		
		var ajaxRequest = getAjaxRequest();
		if (!ajaxRequest)
			return false;
		
		ajaxRequest.onreadystatechange = function()
		{
			if (ajaxRequest.readyState == 4)
			{
				try
				{
					var response = JSON.parse(ajaxRequest.responseText);
					
					if (response.success)
					{
						hideModalContainer();
						
						window.location = "?appSettings=show&option=teaming&action=teaming_billing";
					}
					else
					{
						if (response.error)
							displayGlobalErrorMessage(response.error);
						purchaseButton.innerHTML = labels.update_billing_info;
					}
				}
				catch (e)
				{
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
					purchaseButton.innerHTML = labels.update_billing_info;
				}
			}
		}
		
		var params = "method=updateTeamBillingInfo&teamID=" + encodeURIComponent(teamID) + "&stripeToken=" + encodeURIComponent(stripeToken);
		
		ajaxRequest.open("POST", ".", true);
		
		// Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);
	}
	else
	{
		purchaseButton.innerHTML = labels.update_billing_info;
		hideModalOverlay();
		
		var error = response.error;
		var errorCode = error.code;
		var fieldLabel = null;
		switch (errorCode)
		{
			case 'invalid_number':
			case 'incorrect_number':
				fieldLabel = 'ccard_number_label';
				break;
			case 'invalid_expiry_month':
				fieldLabel = 'ccard_month_label';
				break;
			case 'invalid_expiry_year':
				fieldLabel = 'ccard_year_label';
				break;
			case 'invalid_cvc':
				fieldLabel = 'ccard_cvc_label';
				break;
			default:
				break;
		}
		
		if (fieldLabel)
		{
			indicateErrorForLabel(fieldLabel);
		}
		
		var teamValidationError = document.getElementById('team_validate_error');
		teamValidationError.className = "team_validation_error_message width_full";
        if (response.error && response.error.type == 'card_error') {
            teamValidationError.innerHTML = stripeErrorMessages[response.error.code];
        } else {
            teamValidationError.innerHTML = error.message;
        }
	}
};


function validateTeamCreateAndShowPurchaseModal()
{
    var purchaseButton = document.getElementById('team_purchase_button');
    if(purchaseButton.classList.contains('disabled')){
        return false;
    }
	var everythingIsValid = true;
	
	// Check Team Name
	var teamName = getValueOrError('teamname', 'teamname_label');
	if (!teamName)
		everythingIsValid = false;
	
	// Check # of team members
	var numString = document.getElementById('num_of_members').value;
	var numString = numString.trim();
	var numOfTeamMembers = parseInt(numString, 10);
	if (numString.length == 0 || numOfTeamMembers <= 0)
	{
		everythingIsValid = false;
		indicateErrorForLabel('num_of_members_label');
	}
	else
	{
		removeErrorForLabel('num_of_members_label');
	}
	
	//// OPTIONAL ////
	// Check Business Name
    var bizName = getValueOrError('biz_name', 'biz_name_label');
    if (!bizName)
        everythingIsValid = false;
	
	// Check business phone number
	var bizPhone = getValueOfElement('biz_phone');
	
	// Validate Country
	var bizCountry = getValueOrError('biz_country', 'biz_country_label');
	if (!bizCountry)
		everythingIsValid = false;
	
	// Validate Credit Card #
	var bizCardNumber = getValueOrError('ccard_number', 'ccard_number_label');
	if (!bizCardNumber)
		everythingIsValid = false;
	
	// Validate Name on Card
	var bizCardName = getValueOrError('ccard_name', 'ccard_name_label');
	if (!bizCardName)
		everythingIsValid = false;
	
	// Validate CVC
	var bizCardCVC = getValueOrError('ccard_cvc', 'ccard_cvc_label');
	if (!bizCardCVC)
		everythingIsValid = false;
	
	// Validate Expiry Month
	var bizCardMonth = getValueOrError('ccard_month', 'ccard_month_label');
	if (!bizCardMonth)
		everythingIsValid = false;
	
	// Validate Expiry Year
	var bizCardYear = getValueOrError('ccard_year', 'ccard_year_label');
	if (!bizCardYear)
		everythingIsValid = false;

    if (jQuery('#num_of_members').size() && (parseInt(jQuery('#num_of_members').val()) <= 0 || parseInt(jQuery('#num_of_members').val()) > 500)) {
        everythingIsValid = false;
    }

	// Validate discovery answer
	var discoveryAnswer = getValueOfElement('discovery_answer');
	if (!discoveryAnswer || discoveryAnswer == '-not-specified-')
	{
		indicateErrorForLabel('discovery_answer_label');
		everythingIsValid = false;
	}
	else
	{
		removeErrorForLabel('discovery_answer_label');
	}
	
	// TOS MUST be accepted (shouldn't be able to get here without that)
	
	var teamValidationError = document.getElementById('team_validate_error');
	
	if (!everythingIsValid)
	{
		// Something required wasn't filled in
		teamValidationError.innerHTML = labels.please_enter_the_missing_information ;
		teamValidationError.className = "team_validation_error_message width_full";
	}
	else
	{
		// Everything was filled in! Time to review and purchase.
		teamValidationError.className = "team_validation_error_message width_full team_item_hidden";
		
		showPurchaseConfirmationModal();
	}
};


function validateChangeTeamAndShowPurchaseModal()
{
    var purchaseButton = document.getElementById('team_purchase_button');
    if(purchaseButton.classList.contains('disabled')){
        return false;
    }
	var everythingIsValid = true;
	var new_payment_method = jQuery('.new-payment-method').hasClass('active');
	// Check # of team members
	var numString = document.getElementById('num_of_members').value;
	var numString = numString.trim();
	var numOfTeamMembers = parseInt(numString, 10);
	if (numString.length == 0 || numOfTeamMembers <= 0)
	{
		everythingIsValid = false;
		indicateErrorForLabel('num_of_members_label');
	}
	else
	{
		removeErrorForLabel('num_of_members_label');
	}
	
	// Only validate credit card info if there's a charge
	if (new_payment_method)
	{
		// Validate Credit Card #
		var bizCardNumber = getValueOrError('ccard_number', 'ccard_number_label');
		if (!bizCardNumber)
			everythingIsValid = false;
		
		// Validate Name on Card
		var bizCardName = getValueOrError('ccard_name', 'ccard_name_label');
		if (!bizCardName)
			everythingIsValid = false;
		
		// Validate CVC
		var bizCardCVC = getValueOrError('ccard_cvc', 'ccard_cvc_label');
		if (!bizCardCVC)
			everythingIsValid = false;
		
		// Validate Expiry Month
		var bizCardMonth = getValueOrError('ccard_month', 'ccard_month_label');
		if (!bizCardMonth)
			everythingIsValid = false;
		
		// Validate Expiry Year
		var bizCardYear = getValueOrError('ccard_year', 'ccard_year_label');
		if (!bizCardYear)
			everythingIsValid = false;
	}
    if (jQuery('#num_of_members').size() && (parseInt(jQuery('#num_of_members').val()) <= 0 || parseInt(jQuery('#num_of_members').val()) > 500)) {
        everythingIsValid = false;
    }
	// TOS MUST be accepted (shouldn't be able to get here without that)
	
	var teamValidationError = document.getElementById('team_validate_error');
	
	if (!everythingIsValid)
	{
		// Something required wasn't filled in
		teamValidationError.innerHTML = labels.please_enter_the_missing_information;
		teamValidationError.className = "team_validation_error_message width_full";
		return;
	}

	// Everything was filled in! Time to review and purchase.
	teamValidationError.className = "team_validation_error_message width_full team_item_hidden";
		
	
	purchaseButton.innerHTML = ' <div class="progress_indicator" style="display:inline-block;"></div>';
	
	// Make a call to the server so we can display the validated values from
	// the server on pricing and then call Stripe if needed.
	
	var ajaxRequest = getAjaxRequest();
	if (!ajaxRequest)
	{
		teamValidationError.className = "team_validation_error_message width_full";
		teamValidationError.innerHTML = labels.an_error_occurred_confirming_the_change;
		return;
	}
	
	ajaxRequest.onreadystatechange = function()
	{
		if (ajaxRequest.readyState == 4)
		{
			try
			{
				var responseJSON = JSON.parse(ajaxRequest.responseText);
				if (responseJSON.success)
				{
					// Receive:
					//	responseJSON.changeInfo.newExpirationDate
					//	responseJSON.changeInfo.newNumOfMembers
					//	responseJSON.changeInfo.billingFrequency
					//	responseJSON.changeInfo.bulkDiscount
					//	responseJSON.changeInfo.discountPercentage
					//	responseJSON.changeInfo.currentAccountCredit
					//	responseJSON.changeInfo.totalCharge
					
					// Save these values off to hidden HTML input elements to
					// be used in the next function.
					
					document.getElementById('team_change_new_expiration_date').value = dateStringFromUnixTimestamp(responseJSON.changeInfo.newExpirationDate);
					document.getElementById('team_change_new_num_of_members').value = responseJSON.changeInfo.newNumOfMembers;
					document.getElementById('team_change_billing_frequency').value = responseJSON.changeInfo.billingFrequency;
					//document.getElementById('team_change_bulk_discount').value = responseJSON.changeInfo.bulkDiscount;
					//document.getElementById('team_change_discount_percentage').value = responseJSON.changeInfo.discountPercentage;
					//document.getElementById('team_change_current_account_credit').value = responseJSON.changeInfo.currentAccountCredit;
					document.getElementById('team_change_total_charge').value = responseJSON.changeInfo.totalCharge;
                    if(new_payment_method) {
                            // We need to charge the user by creating a Stripe Token
                            var cardName = document.getElementById('ccard_name').value.trim();
                            var cardNumber = document.getElementById('ccard_number').value.trim();
                            var cardCVC = document.getElementById('ccard_cvc').value.trim();
                            var cardExpMonth = document.getElementById('ccard_month').value.trim();
                            var cardExpYear = document.getElementById('ccard_year').value.trim();

                            var objectForStripe = {
                                name: cardName,
                                number: cardNumber,
                                cvc: cardCVC,
                                exp_month: cardExpMonth,
                                exp_year: cardExpYear
                            };
                            Stripe.createToken(objectForStripe, processCreateTokenResponseForTeamChange);
                    } else {
                        // Continue on without using Stripe
                        processCreateTokenResponseForTeamChange(200, false);
                    }
                }
				else
				{
					if (responseJSON.error == "authentication")
					{
						history.go(0);
					}
					else
					{
						if (responseJSON.error)
						{
							purchaseButton.innerHTML = labels.review_changes;
							teamValidationError.className = "team_validation_error_message width_full";
							teamValidationError.innerHTML = responseJSON.error;
							return;
						}
					}
					
					purchaseButton.innerHTML = labels.review_changes;
					teamValidationError.className = "team_validation_error_message width_full";
					teamValidationError.innerHTML = labels.unable_to_process_changes;
					return;
				}
			}
			catch (e)
			{
                purchaseButton.innerHTML = labels.review_changes;
                teamValidationError.className = "team_validation_error_message width_full";
                teamValidationError.innerHTML = labels.unknown_error + ' ' + e;
				return;
			}
		}
	}
	
	// Send:
	//	teamID
	//	billingFreqency
	//	numOfTeamMembers
	//
	// Receive:
	//	newExpirationDate
	//	membersToAdd
	//	membersToRemove
	//	newNumOfMembers
	//	bulkDiscount
	//	discountPercentage
	//	currentAccountCredit
	//	totalCharge
	
	var teamID = document.getElementById('teamID').value;
	var billingFrequency = 0; // unknown
	if (document.getElementById('monthly_radio').checked)
		billingFrequency = 1; // monthly
	else
		billingFrequency = 2; // yearly
	
	var params = "method=getTeamChangePricingInfo&teamID=" + encodeURIComponent(teamID) + "&billingFrequency=" + billingFrequency + "&numOfTeamMembers=" + numOfTeamMembers;
	
	ajaxRequest.open("POST", ".", true);
	ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


// This function can be called with or without Stripe. In the case that no
// charge is needed, this function won't be called by a Stripe callback. The
// purpose of this function is to show a modal to the team admin so they can
// review the changes before making the change (with or without purchase as
// needed).
function processCreateTokenResponseForTeamChange(status, response)
{
	var purchaseButton = document.getElementById('team_purchase_button');
	purchaseButton.innerHTML = labels.review_changes;
	
	if (status == 200)
	{
        var new_payment_method = jQuery('.new-payment-method').hasClass('active');

        // Read all the saved values from the doc
		var teamID = document.getElementById('teamID').value;
		var newExpirationDate = document.getElementById('team_change_new_expiration_date').value;
		var newNumOfMembers = document.getElementById('team_change_new_num_of_members').value;
		var billingFrequency = document.getElementById('team_change_billing_frequency').value;
		var totalCharge = document.getElementById('team_change_total_charge').value;

		// If a charge is needed, this
        var stripeTokenElement = document.getElementById('stripe_token');
        if (new_payment_method) {
            if (response && response.id) {
                var stripeToken = response.id;
                stripeTokenElement.value = stripeToken;
            }
        } else {
            stripeTokenElement.value = '';
        }
		
		var bodyHTML = '';
        var headerHTML = labels.modify_your_team;
		var footerHTML = '';
		
		bodyHTML += '<div class="breath-4"></div>';
		bodyHTML += '<div class="team_modal_section">';
        bodyHTML += '  <div class="team_modal_info_label">' + labels.change_summary + '</div>';
		bodyHTML += '  <div class="team_modal_invoice_section">';
		
		// Expiration Date
        bodyHTML += '    <div class="team_modal_invoice_item_label">' + labels.new_expiration_renewal_date + '</div>';
		bodyHTML += '    <div class="team_modal_invoice_item_value">' + newExpirationDate + '</div>';
		
		// Number of Team Members
        bodyHTML += '    <div class="team_modal_invoice_item_label">' + labels.number_of_team_members + '</div>';
		bodyHTML += '    <div class="team_modal_invoice_item_value">' + newNumOfMembers + '</div>';
		
		// Billing Freqency
		bodyHTML += '    <div class="team_modal_invoice_item_label">'+labels.billing_type+'</div>';
		if (billingFrequency == 1)
			bodyHTML += '    <div class="team_modal_invoice_item_value">'+labels.monthly+'</div>';
		else
			bodyHTML += '    <div class="team_modal_invoice_item_value">'+labels.yearly +'</div>';
		
		if (totalCharge > 0)
		{
			// Total Charge
			bodyHTML += '    <div class="team_modal_invoice_item_label team_modal_invoice_total" style="font-weight:200;">'+labels.total+'</div>';
			bodyHTML += '    <div class="team_modal_invoice_item_value team_modal_invoice_total" style="font-weight:200;">$' + totalCharge + '</div>';
			bodyHTML += '    <div class="team_modal_invoice_item_supplement">('+labels.in_usd+')</div>';
		}
		
		bodyHTML += '  </div>'; // end team_modal_invoice_section
		bodyHTML += '</div>';	// end team_modal_section
		
		footerHTML += '<div class="button" id="cancelCompleteTeamChange" onclick="hideModalContainer();">' + labels.cancel + '</div>';
		footerHTML += '<div id="changeTeamButton" class="button" onclick="completeTeamChange(\'' + teamID + '\', \'' + newNumOfMembers + '\', \'' + billingFrequency + '\');">';
		
		if (totalCharge > 0)
		{
			footerHTML += labels.purchase;
		}
		else
		{
			footerHTML += labels.continue;
		}
		footerHTML += '</div>';
		
		displayModalContainer(bodyHTML, headerHTML, footerHTML, null, true);
	}
	else
	{
		hideModalOverlay();
		
		var error = response.error;
		var errorCode = error.code;
		var fieldLabel = null;
		switch (errorCode)
		{
			case 'invalid_number':
			case 'incorrect_number':
				fieldLabel = 'ccard_number_label';
				break;
			case 'invalid_expiry_month':
				fieldLabel = 'ccard_month_label';
				break;
			case 'invalid_expiry_year':
				fieldLabel = 'ccard_year_label';
				break;
			case 'invalid_cvc':
				fieldLabel = 'ccard_cvc_label';
				break;
			default:
				break;
		}
		
		if (fieldLabel)
		{
			indicateErrorForLabel(fieldLabel);
		}
		
		var teamValidationError = document.getElementById('team_validate_error');
		teamValidationError.className = "team_validation_error_message width_full";
        if (response.error && response.error.type == 'card_error') {
            teamValidationError.innerHTML = stripeErrorMessages[response.error.code];
        } else {
            teamValidationError.innerHTML = error.message;
        }
	}
};


function showPurchaseConfirmationModal()
{
	// 1. Pop up the modal
	// 2. Show a progress thing
	// 3. Immediately attempt to validate the credit card information (asynchronously)
	// 4. If valid, get all the pricing info from the server (so the server can validate and
	//    calculate any needed tax (for Utah).
	// 5. Stop the modal
	// 6. Display the pricing summary and enable the "Purchase" button
	
	var overlayMessage = '<div class="progress_indicator" style="display:inline-block"></div>' + labels.please_wait;
	displayModalOverlay(null, overlayMessage);
	
	/*
	 var objectForStripe = {
	 name : doc.getElementById('name_on_card').value,
	 number: doc.getElementById('cc_number').value,
	 cvc: doc.getElementById('cvc').value,
	 exp_month: doc.getElementById('exp_date_month').value,
	 exp_year: doc.getElementById('exp_date_year').value};
	 
	 Stripe.createToken( objectForStripe, processPurchaseResponseFromStripe);
	 */
	
	var cardName = document.getElementById('ccard_name').value.trim();
	var cardNumber = document.getElementById('ccard_number').value.trim();
	var cardCVC = document.getElementById('ccard_cvc').value.trim();
	var cardExpMonth = document.getElementById('ccard_month').value.trim();
	var cardExpYear = document.getElementById('ccard_year').value.trim();
	
	var objectForStripe = {
	name: cardName,
	number: cardNumber,
	cvc: cardCVC,
	exp_month: cardExpMonth,
	exp_year: cardExpYear
	};
	Stripe.createToken(objectForStripe, processCreateTokenResponseFromStripe);
	
	//	displayModalContainer(bodyHTML, headerHTML, footerHTML);
};


function processCreateTokenResponseFromStripe(status, response)
{
	if (status == 200)
	{
		// The credit card is valid and we now have a Stripe Token.
		// Store the Stripe token off so we have it and get correct
		// pricing from the server.
		
		var stripeToken = response.id;
		//alert("Stripe Token: " + stripeToken);
		var stripeTokenElement = document.getElementById('stripe_token');
		stripeTokenElement.value = stripeToken;
		
		var teamName = document.getElementById('teamname').value.trim();
		
		
		
		var billingFrequency = null;
		if (document.getElementById('yearly_radio').checked)
		{
			billingFrequency = 'yearly';
		}
		else
		{
			billingFrequency = 'monthly';
		}
		
		var numOfMembers = document.getElementById('num_of_members').value;
		
		startTeamTrial(numOfMembers, billingFrequency);
	}
	else
	{
		hideModalOverlay();
		
		var error = response.error;
		var errorCode = error.code;
		var fieldLabel = null;
		switch (errorCode)
		{
			case 'invalid_number':
			case 'incorrect_number':
				fieldLabel = 'ccard_number_label';
				break;
			case 'invalid_expiry_month':
				fieldLabel = 'ccard_month_label';
				break;
			case 'invalid_expiry_year':
				fieldLabel = 'ccard_year_label';
				break;
			case 'invalid_cvc':
				fieldLabel = 'ccard_cvc_label';
				break;
			default:
				break;
		}
		
		if (fieldLabel)
		{
			indicateErrorForLabel(fieldLabel);
		}
		
		var teamValidationError = document.getElementById('team_validate_error');
		teamValidationError.className = "team_validation_error_message width_full";
        if (response.error && response.error.type == 'card_error') {
            teamValidationError.innerHTML = stripeErrorMessages[response.error.code];
        } else {
            teamValidationError.innerHTML = error.message;
        }
	}
};


function processCreateTokenResponseFromStripeOLD(status, response)
{
	if (status == 200)
	{
		// The credit card is valid and we now have a Stripe Token.
		// Store the Stripe token off so we have it and get correct
		// pricing from the server.
		
		var stripeToken = response.id;
		//alert("Stripe Token: " + stripeToken);
		var stripeTokenElement = document.getElementById('stripe_token');
		stripeTokenElement.value = stripeToken;
		
		var ajaxRequest = getAjaxRequest();
		if (!ajaxRequest)
		{
			var teamValidationError = document.getElementById('team_validate_error');
			teamValidationError.className = "team_validation_error_message width_full";
			teamValidationError.innerHTML = labels.an_error_occurred_confirming_the_team_pricing;
			return;
		}
		
		ajaxRequest.onreadystatechange = function()
		{
			if (ajaxRequest.readyState == 4)
			{
				try
				{
					var responseJSON = JSON.parse(ajaxRequest.responseText);
					if (responseJSON.success)
					{
						//responseJSON.pricingInfo.billingFrequency
						//						//responseJSON.pricingInfo.unitPrice
						//responseJSON.pricingInfo.unitCombinedPrice
						//responseJSON.pricingInfo.numOfSubscriptions
						//responseJSON.pricingInfo.discountPercentage
						//responseJSON.pricingInfo.discountAmount
						//responseJSON.pricingInfo.subtotal
						//responseJSON.pricingInfo.tax
						//responseJSON.pricingInfo.taxRate
						//responseJSON.pricingInfo.taxZipCode
						//responseJSON.pricingInfo.taxCityName
						//responseJSON.pricingInfo.totalPrice
						
						//response.card.last4
						
						var teamName = document.getElementById('teamname').value.trim();
						
						var bodyHTML = '';
						var headerHTML = settingStrings.teaming_create;
						var footerHTML = '';
						
						bodyHTML += '<div class="breath-4"></div>';
						bodyHTML += '<div class="team_modal_section">';
						bodyHTML += '  <div class="team_modal_info_label">'+labels.team_name+':</div><div class="team_modal_info_value">' + teamName + '</div>';
						bodyHTML += '  <div class="team_modal_invoice_section">';
						if (responseJSON.pricingInfo.billingFrequency == "yearly")
						{
							bodyHTML += '    <div class="team_modal_invoice_item_label">'+labels.todo_cloud_yearly_premium_subscription +'</div>';
						}
						else
						{
							bodyHTML += '    <div class="team_modal_invoice_item_label">' + labels.todo_cloud_monthly_premium_subscription + '</div>';
						}
						bodyHTML += '    <div class="team_modal_invoice_item_value">$' + responseJSON.pricingInfo.unitCombinedPrice + '</div>';
                        bodyHTML += '    <div class="team_modal_invoice_item_supplement">' + sprintf(labels.members_at_USD_each, responseJSON.pricingInfo.numOfSubscriptions, responseJSON.pricingInfo.unitPrice) + '</div>';
						
						// Bulk Discount
						// For now Bulk Discount is disabled
						//bodyHTML += '    <div class="team_modal_invoice_item_label">Bulk Discount</div>';
						//bodyHTML += '    <div class="team_modal_invoice_item_value" style="color:#FF0000;">($' + responseJSON.pricingInfo.discountAmount + ')</div>';
						//bodyHTML += '    <div class="team_modal_invoice_item_supplement">(' + responseJSON.pricingInfo.discountPercentage + '%)</div>';
						
						// Subtotal
//						bodyHTML += '    <div class="team_modal_invoice_item_label team_modal_invoice_subtotal">Subtotal</div>';
//						bodyHTML += '    <div class="team_modal_invoice_item_value team_modal_invoice_subtotal">$' + responseJSON.pricingInfo.subtotal + '</div>';
						
						// Tax
//						bodyHTML += '    <div class="team_modal_invoice_item_label">Tax</div>';
//						bodyHTML += '    <div class="team_modal_invoice_item_value">$' + responseJSON.pricingInfo.tax + '</div>';
//						if (responseJSON.pricingInfo.tax > 0)
//						{
//							bodyHTML += '    <div class="team_modal_invoice_item_supplement">(' + responseJSON.pricingInfo.taxZipCode + ' - ' + responseJSON.pricingInfo.taxCityName + ' - ' + responseJSON.pricingInfo.taxRate + '%)</div>';
//						}
						
						// Total Price
						bodyHTML += '    <div class="team_modal_invoice_item_label team_modal_invoice_total" style="font-weight:200;">'+labels.total+'</div>';
						bodyHTML += '    <div class="team_modal_invoice_item_value team_modal_invoice_total" style="font-weight:200;">$' + responseJSON.pricingInfo.totalPrice + '</div>';
						bodyHTML += '    <div class="team_modal_invoice_item_supplement">'+labels.in_usd+'</div>';
						
						
						bodyHTML += '  </div>'; // end team_modal_invoice_section
						bodyHTML += '</div>';	// end team_modal_section
						
						footerHTML += '<div class="button" id="cancelCompleteTeamPurchase" onclick="hideModalContainer();">' + labels.cancel + '</div>';
						footerHTML += '<div id="createTeamButton" class="button" onclick="completeTeamPurchase(\'' + responseJSON.pricingInfo.numOfSubscriptions + '\', \'' + responseJSON.pricingInfo.billingFrequency + '\', \'' + responseJSON.pricingInfo.totalPrice + '\');">'+labels.purchase+'</div>';
						
						
						
						displayModalContainer(bodyHTML, headerHTML, footerHTML, null, true);
					}
					else
					{
						if (responseJSON.error == "authentication")
						{
							history.go(0);
						}
						else
						{
							if (responseJSON.error)
							{
								displayGlobalErrorMessage(responseJSON.error);
							}
						}
						
						displayGlobalErrorMessage(labels.unable_to_calculate_total_purchase_price);
						hideModalContainer();
						hideModalOverlay();
						return;
					}
				}
				catch (e)
				{
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
					hideModalContainer();
					hideModalOverlay();
					return;
				}
			}
		}
		
		var billingFrequency = null;
		if (document.getElementById('yearly_radio').checked)
		{
			billingFrequency = 'yearly';
		}
		else
		{
			billingFrequency = 'monthly';
		}
		
		var numOfMembers = document.getElementById('num_of_members').value;
		
		var zipCode = document.getElementById('biz_zip').value.trim();
		
		var params = "method=getTeamPricingInfo&billingFrequency=" + encodeURIComponent(billingFrequency) + "&numberOfSubscriptions=" + encodeURIComponent(numOfMembers);
		
		if (zipCode && zipCode.length > 0)
			params = params + "&zipCode=" + encodeURIComponent(zipCode);
		
		ajaxRequest.open("POST", ".", true);
		ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);
	}
	else
	{
		hideModalOverlay();
		
		var error = response.error;
		var errorCode = error.code;
		var fieldLabel = null;
		switch (errorCode)
		{
			case 'invalid_number':
			case 'incorrect_number':
				fieldLabel = 'ccard_number_label';
				break;
			case 'invalid_expiry_month':
				fieldLabel = 'ccard_month_label';
				break;
			case 'invalid_expiry_year':
				fieldLabel = 'ccard_year_label';
				break;
			case 'invalid_cvc':
				fieldLabel = 'ccard_cvc_label';
				break;
			default:
				break;
		}
		
		if (fieldLabel)
		{
			indicateErrorForLabel(fieldLabel);
		}
		
		var teamValidationError = document.getElementById('team_validate_error');
        teamValidationError.className = "team_validation_error_message width_full";
        if (response.error && response.error.type == 'card_error') {
            teamValidationError.innerHTML = stripeErrorMessages[response.error.code];
        } else {
            teamValidationError.innerHTML = error.message;
        }
	}
};


function startTeamTrial(numOfSubscriptions, billingFrequency)
{
	var doc = document;
	
	var stripeToken = getValueOfElement('stripe_token');
	
	//alert("Subscriptions: " + numOfSubscriptions + ", Billing Frequency: " + billingFrequency + ", ZIP Code: " + zipCode + ", Total Price: " + totalPrice + ", Stripe Token: " + stripeToken);
	
	var ajaxRequest = getAjaxRequest();
	if (!ajaxRequest)
	{
		displayGlobalErrorMessage(labels.unable_to_create_new_team);
		hideModalContainer();
		hideModalOverlay();
		return;
	}
	
	ajaxRequest.onreadystatechange = function()
	{
		if (ajaxRequest.readyState == 4)
		{
			try
			{
				var responseJSON = JSON.parse(ajaxRequest.responseText);
				if (responseJSON.success)
				{
					window.location = "?appSettings=show&option=teaming";
					hideModalContainer();
				}
				else
				{
					displayGlobalErrorMessage(responseJSON.error);
					hideModalContainer();
					hideModalOverlay();
					return;
				}
			}
			catch(e)
			{
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
				hideModalContainer();
				hideModalOverlay();
				return;
			}
		}
	}
	
	var params = "method=createTeamAccountWithTrial";
	params += "&stripeToken=" + encodeURIComponent(stripeToken);
	params += "&numOfSubscriptions=" + encodeURIComponent(numOfSubscriptions);
	params += "&billingFrequency=" + encodeURIComponent(billingFrequency);
	
	params += "&teamName=" + encodeURIComponent(getValueOfElement('teamname'));
	params += "&bizName=" + encodeURIComponent(getValueOfElement('biz_name'));
	params += "&bizPhone=" + encodeURIComponent(getValueOfElement('biz_phone'));
	params += "&bizCountry=" + encodeURIComponent(getValueOfElement('biz_country'));
	params += "&discoveryAnswer=" + encodeURIComponent(getValueOfElement('discovery_answer'));

	// Vladimir, here's where we need to pass in the discoveryAnswer. To get the
	// valid drop-down options for this, please call:
	//     TDOTeamAccount::getPossibleDiscoveryAnswers().
	//params += "&discoveryAnswer=" + encodeURIComponent(getValueOfElement('discovery_answer'));
	
	ajaxRequest.open("POST", ".", true);
	ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function completeTeamPurchase(numOfSubscriptions, billingFrequency, totalPrice)
{
	var doc = document;
	
	var cancelButton = doc.getElementById('cancelCompleteTeamPurchase');
	cancelButton.setAttribute('onclick', '');
	cancelButton.setAttribute('class', 'button disabled');
	
	var confirmButton = doc.getElementById('createTeamButton');
	confirmButton.setAttribute('onclick', ''); // Prevent the button from being pressed twice
	confirmButton.innerHTML = ' <div class="progress_indicator" style="display:inline-block;"></div>';
	
	var stripeToken = getValueOfElement('stripe_token');
	
	//alert("Subscriptions: " + numOfSubscriptions + ", Billing Frequency: " + billingFrequency + ", ZIP Code: " + zipCode + ", Total Price: " + totalPrice + ", Stripe Token: " + stripeToken);
	
	var ajaxRequest = getAjaxRequest();
	if (!ajaxRequest)
	{
		displayGlobalErrorMessage(labels.unable_to_complete_purchase);
		hideModalContainer();
		hideModalOverlay();
		return;
	}
	
	ajaxRequest.onreadystatechange = function()
	{
		if (ajaxRequest.readyState == 4)
		{
			try
			{
				var responseJSON = JSON.parse(ajaxRequest.responseText);
				if (responseJSON.success)
				{
					window.location = "?appSettings=show&option=teaming";
					hideModalContainer();
				}
				else
				{
					displayGlobalErrorMessage(responseJSON.error);
					hideModalContainer();
					hideModalOverlay();
					return;
				}
			}
			catch(e)
			{
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
				hideModalContainer();
				hideModalOverlay();
				return;
			}
		}
	}
	
	var params = "method=purchaseTeamAccount";
	params += "&stripeToken=" + encodeURIComponent(stripeToken);
	params += "&numOfSubscriptions=" + encodeURIComponent(numOfSubscriptions);
	params += "&billingFrequency=" + encodeURIComponent(billingFrequency);
	params += "&totalPrice=" + encodeURIComponent(totalPrice);
	
	params += "&teamName=" + encodeURIComponent(getValueOfElement('teamname'));
	params += "&bizName=" + encodeURIComponent(getValueOfElement('biz_name'));
	params += "&bizPhone=" + encodeURIComponent(getValueOfElement('biz_phone'));
	params += "&bizAddr1=" + encodeURIComponent(getValueOfElement('biz_address1'));
	params += "&bizAddr2=" + encodeURIComponent(getValueOfElement('biz_address2'));
	params += "&bizCity=" + encodeURIComponent(getValueOfElement('biz_city'));
	params += "&bizState=" + encodeURIComponent(getValueOfElement('biz_state'));
	params += "&bizCountry=" + encodeURIComponent(getValueOfElement('biz_country'));
	params += "&zipCode=" + encodeURIComponent(getValueOfElement('biz_zip'));
	
	ajaxRequest.open("POST", ".", true);
	ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function completeTeamChange(teamID, numOfMembers, billingFrequency)
{
	var doc = document;
	
	var cancelButton = doc.getElementById('cancelCompleteTeamChange');
	cancelButton.setAttribute('onclick', '');
	cancelButton.setAttribute('class', 'button disabled');
	
	var confirmButton = doc.getElementById('changeTeamButton');
	confirmButton.setAttribute('onclick', ''); // Prevent the button from being pressed twice
	confirmButton.innerHTML = ' <div class="progress_indicator" style="display:inline-block;"></div>';
	
	var stripeToken = getValueOfElement('stripe_token');
		
	var ajaxRequest = getAjaxRequest();
	if (!ajaxRequest)
	{
		displayGlobalErrorMessage(labels.unable_to_complete_the_team_account_change );
		hideModalContainer();
		hideModalOverlay();
		return;
	}
	
	ajaxRequest.onreadystatechange = function()
	{
		if (ajaxRequest.readyState == 4)
		{
			try
			{
				var responseJSON = JSON.parse(ajaxRequest.responseText);
				if (responseJSON.success)
				{
					window.location = "?appSettings=show&option=teaming&action=teaming_billing";
					hideModalContainer();
				}
				else
				{
					displayGlobalErrorMessage(responseJSON.error);
					hideModalContainer();
					hideModalOverlay();
					return;
				}
			}
			catch(e)
			{
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
				hideModalContainer();
				hideModalOverlay();
				return;
			}
		}
	}
	
	var params = "method=changeTeamAccount&teamID=" + encodeURIComponent(teamID);
	if (stripeToken)
		params += "&stripeToken=" + encodeURIComponent(stripeToken);
	params += "&numOfMembers=" + numOfMembers;
	params += "&billingFrequency=" + billingFrequency;
	
	ajaxRequest.open("POST", ".", true);
	ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};



function getValueOfElement(inputFieldName)
{
	var stringValue = document.getElementById(inputFieldName).value;
	stringValue = stringValue.trim();
	return stringValue;
};


function getValueOrError(inputFieldName, inputLabelName)
{
	var stringValue = getValueOfElement(inputFieldName);
	if (stringValue.length == 0)
	{
		indicateErrorForLabel(inputLabelName);
		return null; // indicates an error
	}
	else
	{
		removeErrorForLabel(inputLabelName);
	}
	
	return stringValue;
};


function indicateErrorForLabel(elementName)
{
	var label = document.getElementById(elementName);
	if (!label)
		return;
	
	if (label.className.indexOf('team_label_error') >= 0)
	{
		// the error class is already present
		return;
	}
	
	label.className += " team_label_error";
};


function removeErrorForLabel(elementName)
{
	var label = document.getElementById(elementName);
	if (!label)
		return;
	
	var indexOfErrorString = label.className.indexOf('team_label_error');
	if (indexOfErrorString < 0)
	{
		// The error class doesn't exist
		return;
	}
	
	var newClassName = label.className.substring(0, indexOfErrorString);
	label.className = newClassName;
};


function shouldEnableCreateTeamButton(event, inputElement)
{
	var enableButton = inputElement.value.length > 0 ? true : false;
	var button = document.getElementById('createTeamButton');
	if (enableButton)
	{
		button.setAttribute('class', 'button');
		button.onclick = function(){createTeam();};
	}
	else
	{
		button.setAttribute('class', 'button disabled');
		button.onclick = null;
	}
	
	// Enable the <return> key to create the team
	if (event.keyCode == 13 && enableButton)
		createTeam();
};

function createTeam()
{
	var teamName = document.getElementById('teamNameInput').value;
	
	var ajaxRequest = getAjaxRequest();
	if (!ajaxRequest)
		return false;
	
	ajaxRequest.onreadystatechange = function()
	{
		if (ajaxRequest.readyState == 4)
		{
			try
			{
				var response = JSON.parse(ajaxRequest.responseText);
				
				if (response.success)
				{
					window.location = "?appSettings=show&option=teaming";
					hideModalContainer();
				}
				else
				{
					if (response.error)
						displayGlobalErrorMessage(response.error);
				}
			}
            catch (e)
			{
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
			}
		}
	}
	
	var params = "method=createTeam&teamName=" + encodeURIComponent(teamName);
	
	ajaxRequest.open("POST", ".", true);
	
	// Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function validateChangeTeamName()
{
	var doc = document;
	var submitButton = doc.getElementById('teamNameChangeSubmit');
	
	var firstName = doc.getElementById('teamName').value;
	
	if(firstName.length > 0)
		doc.getElementById('team_name_status').innerHTML = "";
	else
		doc.getElementById('team_name_status').innerHTML = labels.too_short;
	
	if(firstName.length > 0)
	{
		submitButton.setAttribute('class', 'button');
		submitButton.addEventListener('click', saveTeamNameChange, false);
	}
	else
	{
		submitButton.setAttribute('class', 'button disabled');
		submitButton.removeEventListener('click', saveTeamNameChange, false);
	}
};

//TODO: we need remove this function
function saveTeamNameChange()
{
	var doc = document;
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;
	
	var teamID = doc.getElementById('teamID').value;
	var teamName = doc.getElementById('teamName').value;
	
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
					doc.getElementById('teamNameLabel').innerHTML = teamName;
					
					doc.getElementById('origTeamName').value  = teamName;
					
					doc.getElementById('team_name_config').setAttribute('style', '');
					showSuccessSettingUpdate('team_name_setting', 'team_name_edit', true);
					
					var submitButton = doc.getElementById('teamNameChangeSubmit');
					submitButton.setAttribute('class', 'button disabled');
					submitButton.removeEventListener('click', saveTeamNameChange, false);
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
						doc.getElementById('teamNameStatus').innerHTML = labels.not_saved ;
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
	
	var params = "method=updateTeamName&teamid=" + encodeURIComponent(teamID) + "&teamName=" + encodeURIComponent(teamName);
	
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

//TODO: we need remove this function
function cancelTeamNameUpdate()
{
	var doc = document;
	var saveButton = doc.getElementById('teamNameChangeSubmit');
	saveButton.setAttribute('class', 'button disabled');
	saveButton.removeEventListener('click', saveTeamNameChange, false);
	
	doc.getElementById('teamName').value = doc.getElementById('origTeamName').value;
	
	doc.getElementById('team_name_status').innerHTML = "";
	
	doc.getElementById('team_name_config').setAttribute('style', '');
	doc.getElementById('team_name_edit').setAttribute('style', '');
};


function validateChangeTeamInfo()
{
	var doc = document;
	var submitButton = doc.getElementById('teamInfoChangeSubmit');
	
	submitButton.setAttribute('class', 'button');
	submitButton.addEventListener('click', saveTeamInfoChange, false);
};

//TODO: we need remove this function
function saveTeamInfoChange()
{
	var doc = document;
	var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
	if(!ajaxRequest)
		return false;
	
	var teamID = doc.getElementById('teamID').value;
	var bizName = doc.getElementById('bizName').value;
	var bizPhone = doc.getElementById('bizPhone').value;
	var bizAddr1 = doc.getElementById('bizAddr1').value;
	var bizAddr2 = doc.getElementById('bizAddr2').value;
	var bizCity = doc.getElementById('bizCity').value;
	var bizState = doc.getElementById('bizState').value;
	var bizCountry = doc.getElementById('bizCountry').value;
	var bizPostalCode = doc.getElementById('bizPostalCode').value;
	
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
					doc.getElementById('bizNameLabel').innerHTML = bizName;
					doc.getElementById('bizPhoneLabel').innerHTML = bizPhone;
					doc.getElementById('bizAddr1Label').innerHTML = bizAddr1;
					doc.getElementById('bizAddr2Label').innerHTML = bizAddr2;
					doc.getElementById('bizCityLabel').innerHTML = bizCity;
					doc.getElementById('bizStateLabel').innerHTML = bizState;
					doc.getElementById('bizCountryLabel').innerHTML = bizCountry;
					doc.getElementById('bizPostalCodeLabel').innerHTML = bizPostalCode;
					
					doc.getElementById('origBizName').value  = bizName;
					doc.getElementById('origBizPhone').value  = bizPhone;
					doc.getElementById('origBizAddr1').value  = bizAddr1;
					doc.getElementById('origBizAddr2').value  = bizAddr2;
					doc.getElementById('origBizCity').value  = bizCity;
					doc.getElementById('origBizState').value  = bizState;
					doc.getElementById('origBizCountry').value  = bizCountry;
					doc.getElementById('origBizPostalCode').value  = bizPostalCode;
					
					doc.getElementById('team_info_config').setAttribute('style', '');
					showSuccessSettingUpdate('team_info_setting', 'team_info_edit', true);
					
					var submitButton = doc.getElementById('teamInfoChangeSubmit');
					submitButton.setAttribute('class', 'button disabled');
					submitButton.removeEventListener('click', saveTeamInfoChange, false);
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
						doc.getElementById('teamInfoStatus').innerHTML = labels.not_saved ;
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
	
	var params = "method=updateTeamInfo&teamid=" + encodeURIComponent(teamID) + "&bizName=" + encodeURIComponent(bizName) + "&bizPhone=" + encodeURIComponent(bizPhone) + "&bizAddr1=" + encodeURIComponent(bizAddr1) + "&bizAddr2=" + encodeURIComponent(bizAddr2) + "&bizCity=" + encodeURIComponent(bizCity) + "&bizState=" + encodeURIComponent(bizState) + "&bizCountry=" + encodeURIComponent(bizCountry) + "&bizPostalCode=" + encodeURIComponent(bizPostalCode);
	
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

//TODO: we need remove this function
function cancelTeamInfoUpdate()
{
	var doc = document;
	var saveButton = doc.getElementById('teamInfoChangeSubmit');
	saveButton.setAttribute('class', 'button disabled');
	saveButton.removeEventListener('click', saveTeamInfoChange, false);
	
	doc.getElementById('bizName').value = doc.getElementById('origBizName').value;
	doc.getElementById('bizPhone').value = doc.getElementById('origBizPhone').value;
	doc.getElementById('bizAddr1').value = doc.getElementById('origBizAddr1').value;
	doc.getElementById('bizAddr2').value = doc.getElementById('origBizAddr2').value;
	doc.getElementById('bizCity').value = doc.getElementById('origBizCity').value;
	doc.getElementById('bizState').value = doc.getElementById('origBizState').value;
	doc.getElementById('bizCountry').value = doc.getElementById('origBizCountry').value;
	doc.getElementById('bizPostalCode').value = doc.getElementById('origBizPostalCode').value;
	
	doc.getElementById('team_info_status').innerHTML = "";
	
	doc.getElementById('team_info_config').setAttribute('style', '');
	doc.getElementById('team_info_edit').setAttribute('style', '');
};


function displayRemoveMemberModal(element) {
    var modal_content_source = jQuery('.delete-user-wrapper');
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    jQuery('.modal-content form input[name="userID"]', modal_content_source).val(element.data('user-id'));
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();
    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
};
function displayRemoveMeModal(element) {
    var modal_content_source = jQuery('.delete-me-wrapper');
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    jQuery('.modal-content form input[name="userID"]', modal_content_source).val(element.data('user-id'));
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();
    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
};


function displayCancelRenewalModal()
{
    var modal_content_source = jQuery('.cancel-subscription-wrapper');
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();
    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
};


function validateTeamInviteEmail(inputID)
{
	var doc = document;
	var inputElement = doc.getElementById(inputID);
	var potentialEmail = inputElement.value.trim();
	if (potentialEmail.length == 0 || isValidEmailAddress(potentialEmail))
	{
		inputElement.setAttribute('style', 'color:#000000');
	}
	else
	{
		inputElement.setAttribute('style', 'color:#ff0000');
	}
};

function displayInviteMemberModal(element) {
    jQuery('.btn-invite .reg-count').text('1');
    var modal_content_source = jQuery('.invite-members-modal-wrapper');
    if (typeof element != 'undefined' && element.data('invitation')) {
        jQuery('.modal-content form input[name="invitationID"]', modal_content_source).val(element.data('invitation'));
    }
    var btn_submit = jQuery('.btn-invite',modal_content_source);
    var user_lines = jQuery('input[type="email"]', modal_content_source).size();
    var available_licenses_count = parseInt(jQuery('.form-wrapper', modal_content_source).data('available-license-count'));
    if (available_licenses_count < user_lines) {
        btn_submit.attr('data-new-licenses-count', user_lines - available_licenses_count);
        jQuery('>span', btn_submit).hide();
        jQuery('span.need-more-licenses', btn_submit).show();
    }

    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
}

function inviteTeamMember(memberLicenseNumber, memberType)
{
	var teamID = document.getElementById('teamID').value;
	var inputElement;
	if (memberType == "admin")
		inputElement = document.getElementById("team_unused_admin");
	else
		inputElement = document.getElementById("team_unused_license_" + memberLicenseNumber);
	var potentialEmail = inputElement.value.trim();
	if (!isValidEmailAddress(potentialEmail))
	{
		return; // do nothing because the specified text is not a valid email address
	}
	var emailAddress = potentialEmail;
	
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
					if (memberType == "admin")
					{
						location.reload();
					}
					else
					{
						document.getElementById("team_member_slot_" + memberLicenseNumber).innerHTML = "<span>" + memberLicenseNumber + ". "+labels.invitation_c+": " + response.email + "</span>"
						+ "<span class=\"team_member_setting_edit\" onclick=\"displayDeleteInvitationModal('" + memberLicenseNumber + "', '" + response.invitationid + "', '" + memberType + "')\">"+labels.delete+"</span>"
						+ "<span class=\"team_member_setting_edit\" onclick=\"displayResendInvitationModal('" + memberLicenseNumber + "', '" + response.invitationid + "', '" + memberType + "')\">"+labels.resend+"</span>";
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
                        displayGlobalErrorMessage(labels.could_not_invite_this_member);
					}
				}
			}
			catch(e)
			{
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
			}
		}
	}
	
	var params = "method=inviteTeamMember&teamid=" + encodeURIComponent(teamID) + "&email=" + encodeURIComponent(emailAddress) + "&memberType=" + encodeURIComponent(memberType);
	
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function displayDeleteInvitationModal(element) {
    var modal_content_source = jQuery('.delete-invitation-wrapper');
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    jQuery('.modal-content form input[name="invitationID"]', modal_content_source).val(element.data('invitation'));
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
}

function displayResendInvitationModal(invitationId) {
    var modal_content_source = jQuery('.resend-invitation-wrapper');
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    jQuery('.modal-content form input[name="invitationID"]', modal_content_source).val(invitationId);
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
}


function removeTeamMember() {
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-delete-administrator');
    var userId = jQuery('input[name="userID"]', form).val();
    var active_item = jQuery('.item button.btn-default[data-user-id="' + userId + '"]').parents('.item');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                location.reload();
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_remove_the_team_member);
            hideModalContainer();
        }
    });

    return false;
}


function cancelTeamRenewal()
{
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-cancel-subscription');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                window.location = "?appSettings=show&option=teaming&action=teaming_billing";
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_cancel_autorenewal);
            hideModalContainer();
        }
    });

    return false;
};


function deleteTeamInvitation() {
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var invitationId = jQuery('input[name="invitationID"]', form).val();
    var active_item = jQuery('.item .btn-delete-invitation-modal[data-invitation="' + invitationId + '"]').parents('.item');

    jQuery('#modal_container .progress-button').progressInitialize();

    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        success: function (json) {
            if (json.success) {
                active_item.remove();
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            displayGlobalErrorMessage(labels.could_not_delete_this_invitation);
            hideModalContainer();
        }
    });

    return false;
}

function resendTeamInvitation(memberLicenseNumber, invitationID, memberType)
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
					hideModalContainer();
					displayModalWithMessage(labels.invitation_resent, labels.the_invitation_was_sent_successfully);
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
                        displayGlobalErrorMessage(labels.could_not_delete_this_invitation);
					}
				}
			}
			catch(e)
			{
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
			}
		}
	}

	var params = "method=resendTeamInvitation&invitationID=" + encodeURIComponent(invitationID) + "&membershipType=" + encodeURIComponent(memberType);

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function cancelTeamModal() {
    hideModalContainer();
};


function displayModalWithMessage(title, message)
{
	var headerHTML = '';
	if (title != null)
		headerHTML += title;
	var bodyHTML = '';
	var footerHTML = '';
	
	bodyHTML += '<div class="breath-4"></div>';
	bodyHTML += '<div><p>' + message + '</p></div>';
	
	footerHTML += '<div class="button" onclick="hideModalContainer();">' + labels.ok + '</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML, null, true);
};



function viewTeam(teamID) {
    window.location = "?appSettings=show&option=teaming";
}

function resendPurchaseReceipt(teamID, timestamp){
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
                    displayModalWithMessage(labels.purchase_receipt_resent, labels.the_purchase_receipt_was_sent_successfully);
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
                        displayGlobalErrorMessage(labels.could_not_resend_purchase_receipt);
					}
				}
			}
			catch(e)
			{
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
			}
		}
	}

	var params = "method=resendPurchaseReceipt&teamID=" + encodeURIComponent(teamID) + "&timestamp=" + encodeURIComponent(timestamp);

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
}

function displayCreateSharedListModal() {
    var modal_content_source = jQuery('.create-shared-list-wrapper');
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
}
function createSharedList() {
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-create-list');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                window.location = url + '/?appSettings=show&option=teaming&action=teaming_list_members&list_id=' + json.list_id;
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_create_shared_list);
            hideModalContainer();
        }
    });

    return false;
}
function displayRenameSharedListModal(element) {
    var modal_content_source = jQuery('.rename-shared-list-wrapper');

    jQuery('input[name="listid"]', modal_content_source).val(element.data('list-id'));
    jQuery('.current-name', modal_content_source).html(element.data('list-name'));
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
    jQuery('#modal_container input[name="listname"]').val(element.data('list-name'));
}
function updateSharedList() {
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-update-list');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                location.reload();
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_update_shared_list);
            hideModalContainer();
        }
    });

    return false;
}
function displayDeleteSharedListModal(element) {
    var modal_content_source = jQuery('.delete-shared-list-wrapper');

    jQuery('input[name="listid"]', modal_content_source).val(element.data('list-id'));
    jQuery('.current-name', modal_content_source).html(element.data('list-name'));
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
    jQuery('#modal_container .btn-manage-members').attr('href', '/?appSettings=show&option=teaming&action=teaming_list_members&list_id=' + element.data('list-id'));
}
function deleteSharedList() {
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-delete-list');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                location.reload();
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_delete_shared_list);
            hideModalContainer();
        }
    });

    return false;
}
function displayAddMembersToSharedList(element) {
    var modal_content_source = jQuery('.add-members-to-list-wrapper');

    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
}
function addMembersToSharedList(){
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-add-members-to-shared-list');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                location.reload();
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_add_members_to_shared_list);
            hideModalContainer();
        }
    });

    return false;
}
function displayRemoveMemberFromSharedList(element) {
    var modal_content_source = jQuery('.remove-member-from-list-wrapper');

    jQuery('input[name="userid"]', modal_content_source).val(element.data('user-id'));
    jQuery('.user-name', modal_content_source).html(element.data('user-name'));
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
}
function removeMemberFromSharedList() {
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-remove-shared-list-member');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                location.reload();
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_remove_members_from_shared_list);
            hideModalContainer();
        }
    });

    return false;
}
function displayChangeMemberRole(element) {
    var modal_content_source = jQuery('.change-member-role-wrapper');

    jQuery('input[name="userid"]', modal_content_source).val(element.data('user-id'));
    jQuery('input[name="roleid"]', modal_content_source).val(element.data('role-id'));
    jQuery('.role-name', modal_content_source).html(element.data('role-name'));
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
}
function changeMemberRole() {
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-change-member-role');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                location.reload();
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_change_member_role);
            hideModalContainer();
        }
    });

    return false;
}
function validateChangeTeamLicenses(btn) {
    var form_wrapper = jQuery('#modal_container .form-wrapper.todo-for-business');
    var form = jQuery('form.active', form_wrapper);
    var summary_form = jQuery('.confirm-purchase', form_wrapper);

    if(!btn.hasClass('trial')) {
        //validate fields
        var everythingIsValid = true;
        var billing_wrapper = jQuery('#billing_section');
        // Validate Credit Card #
        var bizCardNumber = getValueOrError('ccard_number', 'ccard_number_label');
        if (!bizCardNumber)
            everythingIsValid = false;

        // Validate Name on Card
        var bizCardName = getValueOrError('ccard_name', 'ccard_name_label');
        if (!bizCardName)
            everythingIsValid = false;

        // Validate CVC
        var bizCardCVC = getValueOrError('ccard_cvc', 'ccard_cvc_label');
        if (!bizCardCVC)
            everythingIsValid = false;

        // Validate Expiry Month
        var bizCardMonth = getValueOrError('ccard_month', 'ccard_month_label');
        if (!bizCardMonth)
            everythingIsValid = false;

        // Validate Expiry Year
        var bizCardYear = getValueOrError('ccard_year', 'ccard_year_label');
        if (!bizCardYear)
            everythingIsValid = false;

        // TOS MUST be accepted (shouldn't be able to get here without that)

        var teamValidationError = jQuery('.team_validation_error_message');

        if (!everythingIsValid) {
            // Something required wasn't filled in
            teamValidationError.html(labels.please_enter_the_missing);
            return;
        }
    // Everything was filled in! Time to review and purchase.
    teamValidationError.html('');
    }

    // Make a call to the server so we can display the validated values from
    // the server on pricing and then call Stripe if needed.


    var url = window.location.origin;
    var method = 'POST';
    var show_progress = true;
    var progress_interval = '';

    jQuery.ajax({
        type: method,
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            if (show_progress) {
                btn.progressSet(20);
                progress_interval = setInterval(function () {
                    btn.progressIncrement();
                }, 1000);
            }
        },
        success: function (json) {
            if (show_progress) {
                clearInterval(progress_interval);
                btn.progressFinish();
            }
            if (json.success) {
                btn.removeClass('finished btn-invite review-changes').addClass('btn-make-purchase');
                jQuery('>span', btn).hide();
                jQuery('span.make-purchase', btn).show();
                form.removeClass('active').slideUp(100);
                jQuery('.confirm-purchase', form_wrapper).addClass('active').slideDown(100);

                // Save these values off to hidden HTML input elements to
                // be used in the next function.

                jQuery('.num-of-members', summary_form).html(json.changeInfo.newNumOfMembers);
                var billing_type_label = 'Monthly';
                if (json.changeInfo.billingFrequency == 2) {
                    billing_type_label = 'Yearly';
                }
                jQuery('.billing-type', summary_form).html(billing_type_label);
                jQuery('.total-price', summary_form).html('$' + json.changeInfo.totalCharge);
                if (json.changeInfo.totalCharge > 0) {
                    // We need to charge the user by creating a Stripe Token
                    var objectForStripe = {
                        name: jQuery('#ccard_name', form).val(),
                        number: jQuery('#ccard_number', form).val(),
                        cvc: jQuery('#ccard_cvc', form).val(),
                        exp_month: jQuery('#ccard_month', form).val(),
                        exp_year: jQuery('#ccard_year', form).val()
                    };
                    Stripe.createToken(objectForStripe, fillUpPurchaseRequestForm);
                } else if (json.changeInfo.totalCharge === false) {
                    fillUpPurchaseRequestForm(200, {id: ' '});
                }
                else {
                    // Continue on without using Stripe
                    fillUpPurchaseRequestForm(200, false);
                }
            } else {
                var error = json.error;
                var errorCode = error.code;
                var fieldLabel = null;
                switch (errorCode) {
                    case 'invalid_number':
                    case 'incorrect_number':
                        fieldLabel = 'ccard_number_label';
                        break;
                    case 'invalid_expiry_month':
                        fieldLabel = 'ccard_month_label';
                        break;
                    case 'invalid_expiry_year':
                        fieldLabel = 'ccard_year_label';
                        break;
                    case 'invalid_cvc':
                        fieldLabel = 'ccard_cvc_label';
                        break;
                    default:
                        break;
                }

                if (fieldLabel) {
                    indicateErrorForLabel(fieldLabel);
                }
                teamValidationError.html(error.message);
            }
        },
        error: function () {
            displayGlobalErrorMessage(labels.unable_to_process_changes);
            if (show_progress) {
                clearInterval(progress_interval);
                btn.progressFinish();
            }
        }
    });
    return false;
}
function sendInvitationToUsers(btn, form) {
    var url = window.location.origin;
    var method = 'POST';
    var show_progress = true;
    var progress_interval = '';

    jQuery.ajax({
        type: method,
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            if (show_progress) {
                btn.progressSet(20);
                progress_interval = setInterval(function () {
                    btn.progressIncrement();
                }, 1000);
            }
        },
        success: function (json) {
            if (show_progress) {
                clearInterval(progress_interval);
                btn.progressFinish();
            }
            if (json.success) {
                window.location = "/?appSettings=show&option=teaming&action=teaming_members#invited-members";
                location.reload();
            } else {
                displayGlobalErrorMessage(json.error);
            }
        },
        error: function () {
            displayGlobalErrorMessage(labels.unable_to_process_changes);

            if (show_progress) {
                clearInterval(progress_interval);
                btn.progressFinish();
            }
        }
    });
}
function addLicensesForNewMembers(btn) {
    var form_wrapper = jQuery('#modal_container .form-wrapper.todo-for-business');
    var form = jQuery('form.active', form_wrapper);

    var doc = document;

    var url = window.location.origin;
    var method = 'POST';
    var show_progress = true;
    var progress_interval = '';

    jQuery.ajax({
        type: method,
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            if (show_progress) {
                btn.progressSet(20);
                progress_interval = setInterval(function () {
                    btn.progressIncrement();
                }, 800);
            }
        },
        success: function (json) {
            if (show_progress) {
                clearInterval(progress_interval);
                btn.progressFinish();
                if (json.success) {
                    form.removeClass('active');
                    sendInvitationToUsers(btn, jQuery('#modal_container .primary'));
                }
            }
        },
        error: function () {
            displayGlobalErrorMessage(labels.unable_to_process_changes);
            if (show_progress) {
                clearInterval(progress_interval);
                btn.progressFinish();
            }
        }
    });
    return false;

}
function showLicenseChangesForNewMembers(btn, form, need_new_licenses) {
    var billing_frequency = parseInt(jQuery('#orig_billing_frequency').val());
    var base_cost = monthlyTeamPrice;
    var months_left = 1;
    var add_licenses_wrapper = jQuery('#modal_container .more-licenses-wrapper');

    if (billing_frequency == 2) {
        months_left = 12;
        base_cost = yearlyTeamPrice;
    }
    btn.addClass('review-changes')
    jQuery('>span', btn).hide();
    jQuery('span.licenses', btn).show();

    form.removeClass('active').slideUp(100);
    add_licenses_wrapper.slideDown(100).addClass('active');

    jQuery('.licenses-to-add-count span', add_licenses_wrapper).text(need_new_licenses);
    jQuery('.to-be-charged-count', add_licenses_wrapper).text(need_new_licenses * base_cost);
    jQuery('input[name="numOfTeamMembers"]', add_licenses_wrapper).val(need_new_licenses + parseInt(jQuery('#orig_current_license_count').val()));

    return false;
}
function fillUpPurchaseRequestForm(status, response){
    var form = jQuery('#modal_container .confirm-purchase');
    if (status == 200) {
        jQuery('input[name="numOfMembers"]', form).val(parseInt(jQuery('#modal_container .more-licenses-wrapper input[name="numOfTeamMembers"]').val()));
        if (response && response.id) {
            jQuery('input[name="stripeToken"]', form).val(response.id);
        } else {
            jQuery('input[name="stripeToken"]', form).remove();
        }
    }
    else {
        var error = response.error;
        var errorCode = error.code;
        var fieldLabel = null;
        switch (errorCode) {
            case 'invalid_number':
            case 'incorrect_number':
                fieldLabel = 'ccard_number_label';
                break;
            case 'invalid_expiry_month':
                fieldLabel = 'ccard_month_label';
                break;
            case 'invalid_expiry_year':
                fieldLabel = 'ccard_year_label';
                break;
            case 'invalid_cvc':
                fieldLabel = 'ccard_cvc_label';
                break;
            default:
                break;
        }

        if (fieldLabel) {
            indicateErrorForLabel(fieldLabel);
        }

        var teamValidationError = document.getElementById('team_validate_error');
        teamValidationError.className = "team_validation_error_message width_full";
        if (response.error && response.error.type == 'card_error') {
            teamValidationError.innerHTML = stripeErrorMessages[response.error.code];
        } else {
            teamValidationError.innerHTML = error.message;
        }
    }
}
function updateButtonForSharedListMembers() {
    var form = jQuery('#modal_container .add-members-to-shared-list-form');
    var btn = jQuery('#modal_container .btn-add-members-to-shared-list');
    var select_users_count = jQuery('#modal_container .add-members-to-shared-list-form input[type="checkbox"]:checked').size()

    if (select_users_count == 0) {
        jQuery('.add-count', btn).html('');
        btn.addClass('disabled');
    } else {
        jQuery('.add-count', btn).html(select_users_count);
        btn.removeClass('disabled');
    }
    if (select_users_count > 1) {
        jQuery('.add-many', btn).removeClass('hidden');
    } else {
        jQuery('.add-many', btn).addClass('hidden');
    }
}
function submitInviteMembersFormModal() {
    jQuery('#modal_container .btn-invite').click();
}
function displayLeaveTeam() {
    var modal_content_source = jQuery('.leave-the-team-modal-content-wrapper');
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
}
function leaveThisTeam() {
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-leave-this-team');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                window.location = "?appSettings=show&option=subscription";
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_leave_this_team);
            hideModalContainer();
        }
    });

    return false;
}

function displaySlackIntegration() {
    var modal_content_source = jQuery('.slack-integration-wrapper');
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
}
function updateSlackWebhook() {
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-update-slack-webhook');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                location.reload();
            } else {
                displayGlobalErrorMessage(json.error);
            }
            hideModalContainer();
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_update_Slack_configuration);
            hideModalContainer();
        }
    });

    return false;
}

function enableUpdateSlackConfigButton() {
    var form = jQuery('#modal_container .webhook-list');
    var btn = jQuery('#modal_container .btn-update-slack-webhook');
    var enabled = true;
    jQuery('input', form).each(function () {
        var el = jQuery(this);
        if (el.hasClass('channel-field')) {
            if (!validateChannelName(el)) {
                enabled = false;
            } else {
                var url_el = jQuery('.channel-url', el.parents('.item'));
                if (url_el.val().length == 0 && !url_el.hasClass('origin-valid')) {
                    enabled = false;
                }
            }
        }
        if (el.hasClass('channel-url')) {
            if (!validateChannelUrl(el)) {
                enabled = false;
            }
        }
        if (el.hasClass('token-field')) {
            enabled = true;
        }
    });
    if (enabled) {
        btn.removeClass('disabled');
    } else {
        btn.addClass('disabled');
    }
}

function validateChannelName(el) {
    var value = el.val();
    var result = true;
    if (value.length > 0) {
        if (value.length <= 21) {
            var allowed_name = /^\#[A-Za-z0-9\-\_]/g;
            result = allowed_name.test(value);
        } else {
            return false;
        }
    }
    return result;
}
function validateChannelUrl(el) {
    var value = el.val();
    var result = true;
    if (value.length > 0) {
        var allowed_name = /((https?|ftp):\/\/|www\.)[^\s/$.?#].[^\s]*/ig;
        result = allowed_name.test(value);
    }
    return result;
}


function displayAddMyselfToTheTeamModal() {
    var modal_content_source = jQuery('.add-myself-to-the-team-modal');
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();

    displayModalContainer(modal_content, modal_header_content, modal_footer_content);
}
function addMyselfToTheTeam(){
    var form = jQuery('#modal_container form');
    var url = window.location.origin;
    var btn = jQuery('#modal_container .btn-add-myself-to-the-team');
    var progress_interval = '';
    jQuery('#modal_container .progress-button').progressInitialize();
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function () {
            btn.progressSet(20);
            progress_interval = setInterval(function () {
                btn.progressIncrement();
            }, 1000);
        },
        success: function (json) {
            clearInterval(progress_interval);
            btn.progressFinish();
            if (json.success) {
                location.reload();
            } else {
                displayGlobalErrorMessage(json.error);
            }
        },
        error: function () {
            clearInterval(progress_interval);
            btn.progressFinish();
            displayGlobalErrorMessage(labels.unable_to_join_this_team);
            hideModalContainer();
        }
    });

    return false;
}
/**
 * New Teaming v2.4.0
 */
jQuery(document).ready(function () {

    jQuery('.todo-for-business')
        .on('click', '.view-actions.fa-caret-down', function () {
            // hide all rows
            jQuery('.item-list .item .item-actions').slideUp();
            jQuery('.item-list .view-actions').removeClass('fa-caret-up').addClass('fa-caret-down');
            var btn = jQuery(this);
            var team_user_wrapper = btn.parents('.item');
            var actions_wrapper = jQuery('.item-actions', team_user_wrapper);
            actions_wrapper.slideToggle();
            btn.toggleClass('fa-caret-up').toggleClass('fa-caret-down');
        })
        .on('click', '.view-actions.fa-caret-up', function () {
            var btn = jQuery(this);
            var parent_wrapper = btn.parents('.item');
            var actions_wrapper = jQuery('.item-actions', parent_wrapper);
            actions_wrapper.slideToggle();
            btn.toggleClass('fa-caret-up').toggleClass('fa-caret-down');
        })
        .on('click', '.item .do-main-action', function () {
            var btn = jQuery(this);
            var parent_wrapper = btn.parents('.item');
            var action_el = jQuery('.item-actions .action-main > *', parent_wrapper);
            if (action_el.size()) {
                if (action_el.prop('tagName') !== 'A') {
                    action_el.click();
                } else {
                    window.location = action_el.attr('href');
                }
            }
        })

        .on('click', '.btn-resend-invitation-modal', function () {
            displayResendInvitationModal(jQuery(this).data('invitation'));
        })
        .on('click', '.btn-resend-invitation-nq', function () {
            displayResendInvitationModal(jQuery(this).data('invitation'));
        })
        .on('click', '.btn-delete-invitation-modal', function () {
            displayDeleteInvitationModal(jQuery(this));
            return false;
        })
        .on('click', '.btn-remove-user-modal', function () {
            displayRemoveMemberModal(jQuery(this));
            return false;
        })
        .on('click', '.btn-remove-me-modal', function () {
            displayRemoveMeModal(jQuery(this));
            return false;
        })
        .on('click', '.btn-create-new-shared-list', function () {
            displayCreateSharedListModal();
            return false;
        })
        .on('click', '.btn-rename-list-modal', function () {
            displayRenameSharedListModal(jQuery(this));
            return false;
        })
        .on('click', '.btn-delete-list-modal', function () {
            displayDeleteSharedListModal(jQuery(this));
            return false;
        })
        .on('click', '.btn-add-members-to-list', function () {
            displayAddMembersToSharedList(jQuery(this));
            return false;
        })
        .on('click', '.btn-remove-list-member-modal', function () {
            displayRemoveMemberFromSharedList(jQuery(this));
            return false;
        })
        .on('click', '.btn-change-member-role-modal', function () {
            displayChangeMemberRole(jQuery(this));
            return false;
        })
        .on('click', '.btn-leave-the-team-modal', function () {
            displayLeaveTeam();
            return false;
        })
        .on('click', '.btn-cancel-subscription-modal', displayCancelRenewalModal)
        .on('click', '.select-previous-credit-card', function(){
            if(jQuery('.previous-payment-method').hasClass('collapse')){
                jQuery('.previous-payment-method').toggleClass('active').toggleClass('collapse');
                jQuery('.new-payment-method').toggleClass('active').toggleClass('collapse');
                jQuery(this).toggleClass('active');
                jQuery('.select-new-peyment-method').toggleClass('active');
            }
            return false;
        })
        .on('click', '.select-new-peyment-method', function(){
            if(jQuery('.new-payment-method').hasClass('collapse')){
                jQuery('.new-payment-method').toggleClass('active').toggleClass('collapse');
                jQuery('.previous-payment-method').toggleClass('active').toggleClass('collapse');
                jQuery(this).toggleClass('active');
                jQuery('.select-previous-credit-card').toggleClass('active');
            }
            return false;

        })
        .on('change', '#team_create_pricing_options_normal input[name="billing_frequency"]', function (e) {
            updateBillingFrequencySelection(jQuery(this));
            return false;
        })
        .on('click', '.btn-config-slack-integration', displaySlackIntegration)
        .on('click', '.btn-add-myself-to-the-team', displayAddMyselfToTheTeamModal)

    ;
    jQuery('.team-name-wrapper .team-name')
        .on('click', '.change-team-name', function () {
            jQuery(this).hide();
            jQuery('.team-name > span').hide();
            jQuery('.team-name .form-wrapper').show();
            if (jQuery('.team-name .form-wrapper form input[type="text"]').val() === '') {
                jQuery('.team-name .form-wrapper form input[type="text"]').val(jQuery('.team-name > span').text());
            }
            jQuery('.team-name .form-wrapper form input[type="text"]').focus();
        })
        .on('click', '.btn-form-hide', function () {
            jQuery('.team-name > .change-team-name').show();
            jQuery('.team-name > span').show();
            jQuery('.team-name .form-wrapper').hide();
            return false;
        })
        .on('click', '.btn-form-submit', function () {
            //e.stopImmediatePropagation();
            var btn = jQuery(this);
            var form = btn.parents('form');
            var url = form.attr('action');

            if (url === '#') {
                url = window.location.origin;
            }

            jQuery.ajax({
                type: 'POST',
                dataType: 'json',
                url: url,
                data: form.serialize(),
                success: function (json) {
                    if (json.success) {
                        jQuery('.team-name > .change-team-name').show();
                        jQuery('.team-name > span').html(htmlEntities(jQuery('input[name="teamName"]', form).val())).show();
                        jQuery('.team-name .form-wrapper').hide();
                    } else {
                    }
                },
                error: function () {
                    displayGlobalErrorMessage(labels.unable_to_process_changes);
                }
            });
            return false;
        });
    jQuery('#team-administrators')
        .on('click', '.btn-invite-new-admin:not(.disabled)', function () {
            displayInviteMemberModal(jQuery(this));
        })
        .on('click', '.btn-invite-new-admin.disabled', function () {
            var modal_content_source = jQuery('.invitation-limit-modal-content-wrapper');
            var modal_header_content = jQuery('.invitation-limit-modal-header', modal_content_source).html();
            var modal_content = jQuery('.invitation-limit-modal-content', modal_content_source).html();
            var modal_footer_content = jQuery('.invitation-limit-modal-footer', modal_content_source).html();

            displayModalContainer(modal_content, modal_header_content, modal_footer_content);
        })
        .on('click', '.btn-delete-administrator-modal', function () {
            displayRemoveMemberModal(jQuery(this));
            return false;
        });
    jQuery('#team-members-content').on('click', '.btn-invite-new-member', function(){
        displayInviteMemberModal(jQuery(this));
    });
    jQuery('#modal_container')
        .on('click', '.todo-for-business-add-line-user > a', function () {
            var form_wrapper = jQuery('#modal_container .form-wrapper.todo-for-business');
            var btn_submit = jQuery('#modal_container .btn-invite');
            var user_lines = jQuery('input[type="email"]', form_wrapper).size();
            var available_licenses_count = parseInt(form_wrapper.data('available-license-count'));
                jQuery('.field-row', form_wrapper).each(function () {
                    var el = jQuery(this);
                    var row = jQuery('.field-wrap:last', el).clone();
                    jQuery('input', row).val('');
                    row.appendTo(el);
                });
                jQuery('.btn-invite .reg-count').text(++user_lines);
                if (user_lines > 1) {
                    jQuery('.btn-invite .reg-many, #modal_container .form-wrapper.todo-for-business .btn-remove-row').removeClass('hidden');
                    jQuery('.btn-invite .reg-one').addClass('hidden');
                }
            if (available_licenses_count < user_lines) {
                btn_submit.attr('data-new-licenses-count', user_lines - available_licenses_count);
                jQuery('>span', btn_submit).hide();
                jQuery('span.need-more-licenses', btn_submit).show();
            }
            return false;
        })
        .on('click', '.btn-lots-of-people', function () {
            var m_body = jQuery('#modal_body');
            jQuery('.form-invite-persone', m_body).removeClass('active primary').slideUp(100, function () {
                jQuery('.form-invite-group', m_body).addClass('active primary').slideDown(100);
            });
            jQuery('.btn-invite .reg-count').hide();
            jQuery('.todo-for-business-add-line-user').hide();
        })
        .on('click', '.btn-remove-row', function () {
            var form_wrapper = jQuery('#modal_container .form-wrapper.todo-for-business');
            var btn_submit = jQuery('#modal_container .btn-invite');
            var user_lines = jQuery('input[type="email"]', form_wrapper).size();
            var available_licenses_count = parseInt(form_wrapper.data('available-license-count'));
            if (user_lines > 1) {
                var el_index = jQuery('.field-row:last .field-wrap', form_wrapper).index(jQuery(this).parents('.field-wrap'))
                jQuery('.field-row', form_wrapper).each(function () {
                    var el = jQuery(this);
                    jQuery('.field-wrap:eq(' + el_index + ')', el).remove();
                });
                jQuery('.btn-invite .reg-count').text(--user_lines);
            }
            if (user_lines < 2) {
                jQuery('.btn-invite .reg-many, #modal_container .form-wrapper.todo-for-business .btn-remove-row').addClass('hidden');
                jQuery('.btn-invite .reg-one').removeClass('hidden');
            }
            jQuery('#modal_container .todo-for-business-add-line-user > a').removeClass('disabled');
            if (available_licenses_count >= user_lines) {
                btn_submit.attr('data-new-licenses-count', 0);
                jQuery('>span', btn_submit).hide();
                jQuery('span.invite', btn_submit).show();
            }
            return false;
        })
        .on('click', '.btn-invite:not(.review-changes)', function () {
            var btn = jQuery(this);

            var form_wrapper = jQuery('#modal_container .form-wrapper.todo-for-business');
            var form = jQuery('form.active', form_wrapper);
            if (!validateForm(form)) {
                return false;
            }
            btn.progressInitialize();

            var need_new_licenses = parseInt(btn.data('new-licenses-count'));


            var url = window.location.origin;
            var method = 'POST';
            var show_progress = true;
            var progress_interval = '';


            if (need_new_licenses > 0) {
                showLicenseChangesForNewMembers(btn, form, need_new_licenses);
            } else {
                sendInvitationToUsers(btn, form);
            }
            return false;
        })
        .on('click', '.btn-invite.review-changes', function () {
            validateChangeTeamLicenses(jQuery(this));
            return false;
        })
        .on('click', '.btn-make-purchase', function(){
            addLicensesForNewMembers(jQuery(this));
            return false;
        })
        .on('click', '.btn-delete-invitation', deleteTeamInvitation)
        .on('click', '.btn-delete-administrator', removeTeamMember)
        .on('click', '.btn-leave-this-team', leaveThisTeam)
        .on('click', '.btn-create-list', createSharedList)
        .on('click', '.btn-update-list', updateSharedList)
        .on('click', '.btn-delete-list', deleteSharedList)
        .on('click', '.btn-change-member-role', changeMemberRole)
        .on('click', '.btn-remove-shared-list-member', removeMemberFromSharedList)
        .on('change', '.add-members-to-shared-list-form input[type="checkbox"]', updateButtonForSharedListMembers)
        .on('click', '.btn-add-members-to-shared-list:not(.disabled)', addMembersToSharedList)
        .on('click', '.team-members-checkbox-select-all', function () {
            var wrapper = jQuery('.team-members-checkbox-container');
            jQuery('input[type="checkbox"]', wrapper).prop('checked', true);
            updateButtonForSharedListMembers();
        })
        .on('click', '.team-members-checkbox-deselect-all', function () {
            var wrapper = jQuery('.team-members-checkbox-container');
            jQuery('input[type="checkbox"]', wrapper).prop('checked', false);
            updateButtonForSharedListMembers();
        })
        .on('click', '.btn-cancel-subscription', cancelTeamRenewal)
        .on('submit', 'form', function(){
            var action = jQuery('input[name="jsaction"]', this).val();
            window[action]();
            return false;
        })
        .on('click', '.btn-update-slack-webhook:not(.disabled)', updateSlackWebhook)
        .on('change', '.webhook-list input', enableUpdateSlackConfigButton)
        .on('click', '.btn-add-myself-to-the-team', addMyselfToTheTeam)
    ;
    jQuery('#modal_container, .item-list')
        .on('click', '.btn-resend-invitation', function () {
        var btn = jQuery(this);
        var data = '';
        var form = jQuery('#modal_container form');
        var url = window.location.origin;
        var show_progress = true;
        if (form.size()) {
            show_progress = form.data('progress');
            data = form.serialize();
            jQuery('#modal_container .progress-button').progressInitialize();
        } else {
            data = serialize({
                method: btn.data('method'),
                invitationID: btn.data('invitation'),
                membershipType: btn.data('membershiptype')
            });
            btn.progressInitialize();
        }

        if (typeof (show_progress) === 'undefined' || show_progress === 'false') {
            show_progress = false;
        } else {
            show_progress = true;
        }
        var progress_interval = '';
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: url,
            data: data,
            beforeSend: function () {
                if (show_progress) {
                    btn.progressSet(20);
                    progress_interval = setInterval(function () {
                        btn.progressIncrement();
                    }, 1000);
                }
            },
            success: function (json) {
                if (show_progress) {
                    clearInterval(progress_interval);
                    btn.progressFinish();
                }
                if (json.success) {
                    hideModalContainer();
                    notify({success: true, message: labels.invitation_sent});
                } else {
                    displayGlobalErrorMessage(json.error);
                }
            },
            error: function () {
                displayGlobalErrorMessage(labels.unable_to_process_changes);
                if (show_progress) {
                    clearInterval(progress_interval);
                    btn.progressFinish();
                }
            }
        });

        return false;
    })

    if (jQuery('#num_of_members').size() && parseInt(jQuery('#num_of_members').val())) {
        updateModifyTeamPricing();
    }
});
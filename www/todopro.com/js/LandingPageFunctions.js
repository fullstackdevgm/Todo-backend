if (typeof(sessionStorage) != 'undefined' || typeof(sessionStorage.shoppingCart) != 'undefined')
    delete sessionStorage.shoppingCart;
else if (typeof(userSession) != 'undefined' || typeof(userSession.shoppingCart) != 'undefined')
    delete userSession.shoppingCart;

jQuery(document).ready(function(){
    jQuery('a.forgot-password').webuiPopover({
        title: labels.reset_your_password,
        content: jQuery('.reset-form-content').html(),
        animation: 'fade',
        placement: 'top'
    });
    jQuery(document).on('input', '.reset-password-form input[type="text"]', function (e) {
        var result = validateUsername(jQuery(this).val());
        if (result === true) {
            jQuery('.input_status.username-check').removeClass('visible-error').html('');
            jQuery('.forgot-password-button').removeClass('disabled');
        } else {
            jQuery('.input_status.username-check').html(result).addClass('visible-error');
            jQuery('.forgot-password-button').addClass('disabled');
        }
    }).on('submit','form.reset-password-form', function(e){
        if (!jQuery('.input_status.username-check').hasClass('visible-error') && !jQuery('.forgot-password-button').hasClass('disabled')) {
            sendResetPasswordLink(jQuery('input[type="text"]',this).val());
        }
        return false;
    });

    jQuery('.todo-for-business')
        .on('change', '#team_create_pricing_options_normal input[name="billing_frequency"]', function (e) {
            updateBillingFrequencySelection(jQuery(this));
            return false;
        })
    ;
    if (jQuery('#num_of_members').size() && parseInt(jQuery('#num_of_members').val())) {
        updateCreateTeamPricing();
    }
    jQuery('.change-language select').on('change', function (e) {
        SetCookie('interface_language', jQuery(this).val());
        location.reload();
    });

});
function startResetPasswordProcess()
{
	var doc = document;
	var modalHTML = '';

	modalHTML += '	<p style="width:310px;margin:0">'+labels.enter_your_todo_cloud_username_below +'</p>';
	modalHTML += '	<span id="email_wrapper">';
	modalHTML += '	    <input type="text" id="recover_password_email"  onkeyup="validateUsername()"/>';
	modalHTML += '	    <span id="recover_password_email_status" class="option_status"></span>';
    modalHTML += '	    <div id="send_reset_pw_link_button" class="button disabled" >' + labels.send_link + '</div>';
	modalHTML += '	</span>';

	var modalTitle = labels.reset_your_password;
	
	displayModalContainer(modalHTML, modalTitle, '');

	//modalBody.innerHTML = modalHTML;
	jQuery(document).on('click', '#send_reset_pw_link_button:not(.disabled)', sendResetPasswordLink)
	doc.getElementById('recover_password_email').focus();
};
function validateUsername(value) {

    value = value.trim();
    var message = '';
    var invalid = " "; // Invalid character is a space
    var minLength = 4; // Minimum length
    var validated = true;

    if (value.length < minLength) {
        message = "Too short";
        validated = false;
    } else if (value.indexOf(invalid) > -1) {
        message = "Spaces not allowed";
        validated = false;
    } else {
        var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;

        if (!re.test(value)) {
            message = "Not an email address";
            validated = false;
        }
    }
    if (validated) {
        return true;
    }
    return message;
}
function sendResetPasswordLink(email) {
    jQuery.ajax({
        type: 'POST',
        dataType: "json",
        data: 'method=sendResetPasswordEmail&username=' + email,
        success: function (json) {
            if (json.success) {
                jQuery('a.forgot-password').webuiPopover('hide');
                var modalTitle = labels.reset_your_password;
                var modalBody = '<p style="width:310px;margin:0 0 10px">' + labels.please_check_your_mailbox + '</p>';
                modalBody += '<p style="width:310px;margin:0">' + labels.if_you_do_not_see_an_email + '</p>';
                var modalFooter = '<div id="email_sent_ok_button" class="button" onclick="hideModalContainer()" >' + labels.ok + '</div>';
                displayModalContainer(modalBody, modalTitle, modalFooter);
            }
            else {
                displayErrorInModalContainer(responseJSON.error);
            }
        },
        error: function (e) {
            displaySignInErrorMessage(labels.error_from_server + ' ' + e);
        }
    });
}

var signInAttempts = 0;
var isUpgrading = false;
var isMaintenance = false;

function signIn()
{
	
	signInAttempts++;
	var doc = document;
	
	var username = doc.getElementById('username').value.trim();
	var password = doc.getElementById('password').value.trim();

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
					if (isUpgrading)
					{
						var bodyHTML = '';
                			bodyHTML += '<div style="margin:30px auto;width: 300px;font-size:1.5em;text-align:center;line-height:1.8em">';
                			bodyHTML += migrationStrings.migrationSuccessful;
                		var footerHTML = '	<div class="button" onclick="history.go(0)">' + labels.ok + '</div>';
                			
	    				modalHTML += '</div>';

	    				displayModalContainer(bodyHTML, '', footerHTML);
					}
					else if (isMaintenance)
					{
						var bodyHTML = '';
						bodyHTML += '<div style="margin:30px auto;width: 300px;font-size:1.5em;text-align:center;line-height:1.8em">';
						bodyHTML += maintenanceStrings.maintenanceSuccessful;
                		var footerHTML = '	<div class="button" onclick="history.go(0)">' + labels.ok + '</div>';
						
	    				modalHTML += '</div>';
						
	    				displayModalContainer(bodyHTML, '', footerHTML);
					}
					else
					{
						if (window.location.hash.length > 0)
		                	window.location = '.';
		                else
		                	history.go(0);
					}
                }
                else
                {
                	if(responseJSON.upgrading)
                	{
                		if (signInAttempts > 12)
                		{
                			var modalHTML = '';

            				modalHTML += '<div style="margin:30px auto;width: 300px;font-size:1.5em;text-align:center;line-height:1.8em">';
            				modalHTML += migrationStrings.migrationTimedOut;
            				modalHTML += '</div>';

                			displayModalContainer(modalHTML);
                			setTimeout(function(){hideModalOverlay();hideModalContainer();}, 10000);
                			signInAttempts = 0;
                			isUpgrading = false;
                			return;
                		}
                		else
                		{
                			if(!isUpgrading)
                			{
                				var modalHTML = '';
                				modalHTML += '<div style="margin:30px auto;width: 300px;font-size:1.5em;text-align:center;;line-height:1.8em">';
                				modalHTML += migrationStrings.migrationStarted;
                				modalHTML += '	<br/><br/><center><div class="progress_indicator" style="display:block"></div></center>';
                				modalHTML += '</div>';

                				displayModalContainer(modalHTML);
                				doc.getElementById('modal_overlay').onclick = null;
                			}

                			isUpgrading = true;

                			setTimeout(function(){signIn();}, 5000);
                		}
                	}
					else if (responseJSON.maintenance)
					{
                		if (signInAttempts > 12)
                		{
                			var modalHTML = '';
							
            				modalHTML += '<div style="margin:30px auto;width: 300px;font-size:1.5em;text-align:center;line-height:1.8em">';
            				modalHTML += maintenanceStrings.maintenanceTimedOut;
            				modalHTML += '</div>';
							
                			displayModalContainer(modalHTML);
                			setTimeout(function(){hideModalOverlay();hideModalContainer();}, 10000);
                			signInAttempts = 0;
                			isMaintenance = false;
                			return;
                		}
                		else
                		{
                			if(!isMaintenance)
                			{
                				var modalHTML = '';
                				modalHTML += '<div style="margin:30px auto;width: 300px;font-size:1.5em;text-align:center;line-height:1.8em">';
                				modalHTML += maintenanceStrings.maintenanceStarted;
                				modalHTML += '	<br/><br/><center><div class="progress_indicator" style="display:block"></div></center>';
                				modalHTML += '</div>';
								
                				displayModalContainer(modalHTML);
                				doc.getElementById('modal_overlay').onclick = null;
                			}
							
                			isMaintenance = true;
							
                			setTimeout(function(){signIn();}, 5000);
                		}
					}
                	else
                    {
                        displaySignInErrorMessage(labels.error_from_server + ' ' + responseJSON.error);
                    }
                }
            }
            catch(err)
            {
               	displaySignInErrorMessage(labels.unknown_error + ' ' + err);
            }
		}
	}

	var params = 'method=login&username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password);
	ajaxRequest.open("POST", ".", false);
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    

	ajaxRequest.send(params);

};



function displaySignInErrorMessage(message)
{
	var doc = document;
	var errorContainer = doc.getElementById('sign_in_error_message');
	errorContainer.innerHTML = message;
    errorContainer.classList.add("visible-error");

};


/* !Sign Up Functions */

function validateFirstName()
{
	var result = false;
	var doc = document;
	var inputEl = doc.getElementById('first_name');
	var value = inputEl.value.trim();
	var errorEl = doc.getElementById('first_name_status');
	
	if (value.length > 0)
	{
		result = true;
        errorEl.classList.remove("visible-error");
	}
	else
	{
		errorEl.innerHTML = labels.you_must_include_your_first_name;
		errorEl.classList.add("visible-error");
	}
		
	return result;
};

function validateLastName()
{
	var result = false;
	var doc = document;
	var inputEl = doc.getElementById('last_name');
	var value = inputEl.value.trim();
	var errorEl = doc.getElementById('last_name_status');
	
	if (value.length > 0)
	{
		result = true;
        errorEl.classList.remove("visible-error");

    }
	else
	{
		errorEl.innerHTML = labels.you_must_include_your_last_name;
        errorEl.classList.add("visible-error");
    }
		
	return result;
};

function validateEmail()
{
	var result = false;
	var doc = document;
	var inputEl = doc.getElementById('email');
	var value = inputEl.value;
	var errorEl = doc.getElementById('email_status');
	var displayError = false;
	var errorMsg = 'unknown error';
    var re = /^(([^<>()[\]\\.,;:=+\s@\"]+(\.[^<>()[\]\\.,;:=+\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    if (value.endsWith('@appigo.com')) //allow + char for appigo.com domain
    {
        re = /^(([^<>()[\]\\.,;:=\s@\"]+(\.[^<>()[\]\\.,;:=\s@\"]+)*)|(\".+\"))@appigo\.com$/;
        if(!re.test(value))
        {
            errorMsg = labels.p_character_is_not_allowed;
            displayError = true;
        }
    }
    else
    {
        if(!re.test(value))
        {
            errorMsg = labels.invalid_email_address;
            displayError = true;
        }
    }

	if (displayError)
	{
		errorEl.innerHTML = errorMsg;
        errorEl.classList.add("visible-error");
	}
	else
	{
        errorEl.classList.remove("visible-error");
        result = true;
	}
	
	return result;
};

function validatePasswords()
{
	var result = false;
	var doc = document;
	var minLength = 6;
	var inputEl = doc.getElementById('password_1');
	var value = inputEl.value.trim();
	var errorEl = doc.getElementById('password_status');
	
	if (value.length > 0)
	{
		if (value.length >= minLength)
		{
			//console.log('test: ' + value.indexOf(' '));
			if (value.indexOf(' ') == -1)
			{
				result = true;
                errorEl.classList.remove("visible-error");
            }
			else
			{
				errorEl.innerHTML = labels.spaces_are_not_allowed;
                errorEl.classList.add("visible-error");
            }
		}
		else
		{
            errorEl.innerHTML = labels.password_must_be_at_least + ' ' + minLength + ' ' + labels.characters;
            errorEl.classList.add("visible-error");
        }
		
	}
	return result;
};
function validateConfirmPasswords()
{
	var result = false;
	var doc = document;
	var inputEl = doc.getElementById('password_1');
	var inputEl_c = doc.getElementById('verifyPassword');
	var value = inputEl.value.trim();
	var value_c = inputEl_c.value.trim();
    var errorEl_c = doc.getElementById('confirm_password_status');

    if (value !== value_c) {
        errorEl_c.innerHTML = labels.dont_match;
        errorEl_c.classList.add("visible-error");

    } else {
        result = true;
        errorEl_c.classList.remove("visible-error");
    }
	return result;
};

function signUp()
{
    if (validateFirstName() && validateLastName() && validateEmail() && validatePasswords() && validateConfirmPasswords())
	{
		var doc = document;
	
		var email = doc.getElementById('email').value.trim();
		var password_1 = doc.getElementById('password_1').value.trim();
		var firstName = doc.getElementById('first_name').value.trim();
		var lastName = doc.getElementById('last_name').value.trim();
	
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
		                if (window.location.hash.length > 0)
		                	window.location = '.';
		                else
		                	history.go(0);
	                }
	                else
	                {
	                	doc.getElementById('sign_up_status').innerHTML = responseJSON.error;
	                	doc.getElementById('sign_up_status').classList.add('visible-error');
	                	//displaySignInErrorMessage(responseJSON.error);
	                }
	            }
	            catch(err)
	            {
                    displaySignInErrorMessage(labels.error_from_server + ' ' + err);
	            }
			}
		}
		var emailoptin = doc.getElementById('emailoptin').checked ? 1 : 0;
		
		var params = 'method=createUser&firstname=' + firstName + '&lastname=' + lastName + '&username=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password_1) + '&emailoptin=' + emailoptin;
	
		ajaxRequest.open("POST", ".", false);
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    
		ajaxRequest.send(params);
	}
	else
		return false;
};


/*
 * This is copy of TeamFunctions
 */

function updateModifyTeamPricing(numOfPaidLicenses, numOfCurrentLicenses, expirationDate, billingFrequency)
{
    updateCreateTeamPricing();

    updateChangeDisplayInfo();
};
function updateCreateTeamPricing()
{
    var doc = document;
    var inputElement = doc.getElementById('num_of_members');
    var stringValue = inputElement.value;
    if (stringValue.length == 0)
    {
        inputElement.value = "";
        return setTeamPricingForNumberOfMembers(1);
    }
    else
    {
        // Make sure that the value entered is a numeric value
        var numValue = Math.abs(parseInt(stringValue, 10));
        if (isNaN(numValue))
        {
            inputElement.value = "";
            return setTeamPricingForNumberOfMembers(1);
        }
        inputElement.value = numValue;
        return setTeamPricingForNumberOfMembers(numValue);
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
        numOfMembers = 1;
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
            newExpirationDate = now + oneMonthInSeconds;
            monthsLeft = 1;
        }
        else
        {
            newExpirationDate = now + oneYearInSeconds;
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
                if (numOfMembers >= 20)
                    discountRate = 0.2;
                else if (numOfMembers >= 10)
                    discountRate = 0.1;
                else if (numOfMembers >= 5)
                    discountRate = 0.05;
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
        newExpirationDate = now + oneYearInSeconds;

        var baseCost = numOfMembers * yearlyTeamPrice;
        var discountRate = 0.00;
        if (numOfMembers >= 20)
            discountRate = 0.2;
        else if (numOfMembers >= 10)
            discountRate = 0.1;
        else if (numOfMembers >= 5)
            discountRate = 0.05;
        bulkDiscount = baseCost * discountRate;
        var subtotal = baseCost - bulkDiscount;


        // Remaining expiration is greater than 14 days, so deduct a credit
        // based on the number of months.
        if (monthsLeft > 0)
        {
            discountRate = 0.0;
            if (origLicenseCount >= 20)
                discountRate = 0.2;
            else if (origLicenseCount >= 10)
                discountRate = 0.1;
            else if (origLicenseCount >= 5)
                discountRate = 0.05;
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
            newExpirationDate = now + oneMonthInSeconds;
            var baseCost = numOfMembers * monthlyTeamPrice;
            var discountRate = 0.00;
            if (numOfMembers >= 20)
                discountRate = 0.2;
            else if (numOfMembers >= 10)
                discountRate = 0.1;
            else if (numOfMembers >= 5)
                discountRate = 0.05;
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
                    newExpirationDate = now + (monthsAvailable * oneMonthInSeconds);

                    // No need to charge anything
                }
                else
                {
                    // In this case, the original credit does NOT cover the new
                    // cost of the total number of members being added and the
                    // user will need to pay to make up the difference.

                    // Set the new expiration date to one month from now.
                    newExpirationDate = now + oneMonthInSeconds;

                    var unpaidAmount = newMonthlyCost - origCredit;
                    var discountRate = 0.0;
                    if (numOfMembers >= 20)
                        discountRate = 0.2;
                    else if (numOfMembers >= 10)
                        discountRate = 0.1;
                    else if (numOfMembers >= 5)
                        discountRate = 0.05;
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

    if (totalCharge < 0)
    {
        bulkDiscount = 0.00;
        currentAccountCredit = 0.00;
        totalCharge = 0.00;
    }

    totalCharge = totalCharge.toFixed(2);

    if (bulkDiscount > 0)
    {
        bulkDiscount = bulkDiscount.toFixed(2);
        doc.getElementById('team_change_bulk_discount').innerHTML = "-$" + bulkDiscount;
    }
    else
        doc.getElementById('team_change_bulk_discount').innerHTML = "-";

    if (currentAccountCredit > 0)
    {
        currentAccountCredit = currentAccountCredit.toFixed(2);
        doc.getElementById('team_change_account_credit').innerHTML = "-$" + currentAccountCredit;
    }
    else
        doc.getElementById('team_change_account_credit').innerHTML = "-";

    // Show the user the new expiration date and the total charge needed to
    // make the change.
    var date = new Date(newExpirationDate * 1000); // convert to milliseconds
    var months = monthsStrings;
    var year = date.getFullYear();
    var month = months[date.getMonth()];
    var day = date.getDate();
    var newExpirationDateString = day + " " + month + " " + year;
    doc.getElementById('team_change_expiration_date').innerHTML = newExpirationDateString;

    var amountDueString = "$" + totalCharge + " USD";
    doc.getElementById('team_change_amount_due').innerHTML = amountDueString;

    var billingSection = doc.getElementById('billing_section');
    if (totalCharge <= 0)
    {
        // Hide the billing section
        billingSection.setAttribute('style', 'display:none;');
    }
    else
    {
        billingSection.setAttribute('style', 'display:block');
    }

};
function setTeamPricingForNumberOfMembers(numOfMembers)
{
    var normalPricingElement = document.getElementById('team_create_pricing_options_normal');
    var customPricingElement = document.getElementById('team_create_pricing_options_custom');

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
function updatePurchaseButtonEnablement(event, inputElement, insideChangeTeam)
{
    var validateFunction;
    if (insideChangeTeam)
        validateFunction = validateChangeTeamAndShowPurchaseModal;
    else
        validateFunction = validateTeamCreateAndShowPurchaseModal;

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
};
function validateTeamCreateAndShowPurchaseModal()
{
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

    var firstName = getValueOrError('first_name', 'first_name_label');
    if (!firstName)
        everythingIsValid = false;

    var lastName = getValueOrError('last_name', 'last_name_label');
    if (!lastName)
        everythingIsValid = false;

    var email = getValueOrError('email', 'email_label');
    if (!email)
        everythingIsValid = false;

    var password_1 = getValueOrError('password_1', 'password_1_label');
    if (!password_1)
        everythingIsValid = false;
    var verifyPassword = getValueOrError('verifyPassword', 'password_2_label');
    if (!verifyPassword || verifyPassword !== password_1)
        everythingIsValid = false;

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
        teamValidationError.innerHTML = labels.please_enter_the_missing;
        teamValidationError.className = "team_validation_error_message width_full";
    }
    else
    {
        // Everything was filled in! Time to review and purchase.
        teamValidationError.className = "team_validation_error_message width_full team_item_hidden";

        showPurchaseConfirmationModal();
    }
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
function getValueOfElement(inputFieldName)
{
    var stringValue = document.getElementById(inputFieldName).value;
    stringValue = stringValue.trim();
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
        teamValidationError.innerHTML = error.message;
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
        displayGlobalErrorMessage(labels.unable_to_create_new_team );
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
            catch(err)
            {
                displayGlobalErrorMessage(labels.error_from_server + ' ' + err);
                hideModalContainer();
                hideModalOverlay();
                return;
            }
        }
    }

    var emailoptin = doc.getElementById('emailoptin').checked ? 1 : 0;

    var params = "method=createUser";

    params += "&firstname=" + encodeURIComponent(getValueOfElement('first_name'));
    params += "&lastname=" + encodeURIComponent(getValueOfElement('last_name'));
    params += "&username=" + encodeURIComponent(getValueOfElement('email'));
    params += "&password=" + encodeURIComponent(getValueOfElement('password_1'));
    params += "&emailoptin=" + emailoptin;

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

/*create account validation functions*/
/*
function validateFields()
{
	var firstName = document.getElementById('first_name').value;
	var lastName = document.getElementById('last_name').value;
	var userName = document.getElementById('email').value;


	var invalid = " "; // Invalid character is a space
	var minLength = 5; // Minimum length
	var passOne = document.getElementById('password_1').value;
	var passTwo = document.getElementById('password_2').value;
	var validated = true;

	if(firstName.length == 0)
	{
		document.getElementById('firstNameStatus').innerHTML = "required";
		validated = false;
	}
	else
	{
		document.getElementById('firstNameStatus').innerHTML = "";
	}

	if(lastName.length == 0)
	{
		document.getElementById('lastNameStatus').innerHTML = "required";
		validated = false;
	}
	else
	{
		document.getElementById('lastNameStatus').innerHTML = "";
	}

	if(userName.length == 0)
	{
		document.getElementById('userNameStatus').innerHTML = "required";
		validated = false;
	}
	else if (userName.indexOf(invalid) > -1)
	{
		document.getElementById('userNameStatus').innerHTML = "spaces not allowed";
		validated = false;
	}
	else
	{
		var atpos=userName.indexOf("@");
		var dotpos=userName.lastIndexOf(".");
		if (atpos<1 || dotpos<atpos+2 || dotpos+2>=userName.length)
		{
			document.getElementById('userNameStatus').innerHTML = "invalid address";
			validated = false;
		}
		else
			document.getElementById('userNameStatus').innerHTML = "";
	}

	if(passOne.length < minLength)
	{
		document.getElementById('passwordStatus').innerHTML = "too short";
		validated = false;
	}
	else if (passOne.indexOf(invalid) > -1)
	{
		document.getElementById('passwordStatus').innerHTML = "spaces not allowed";
		validated = false;
	}
	else if (passOne != passTwo)
	{
		document.getElementById('passwordStatus').innerHTML = "passwords don't match";
		validated = false;
	}
	else
	{
		document.getElementById('passwordStatus').innerHTML = "";
	}

	var enabledButton = '<input type=submit value="Sign Up">';
	var disabledButton = '<input type=submit value="Sign Up" disabled="disabled">';

	if(validated)
	{
		document.getElementById('passwordSubmit').innerHTML = enabledButton;
	}
	else
		document.getElementById('passwordSubmit').innerHTML = disabledButton;
}

*/










































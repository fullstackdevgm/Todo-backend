// !Global vars
var doc = document;
var recordsPerPage = 50;
var adminLevel = doc.getElementById('user_admin_level').value;

// !window.load
window.addEventListener('load', setUpUserPage, false);

// !functions
function setUpUserPage()
{
	var button = doc.getElementById('perform_search_button');

	var searchTerm = getQueryVariable("searchString");
	var userid = getQueryVariable("userid");
	if (searchTerm)
	{
		doc.getElementById('user_search_term').value = searchTerm;
		performUserSearch();
	}
	else if (userid)
	{
		showUserInfo(userid);
	}

	button.setAttribute('class', 'button');
	button.addEventListener('click', setUpUserSearch, false);

	doc.getElementById('user_search_term').addEventListener('keyup', shouldSearchOnEnter, false);
};

function shouldSearchOnEnter(event)
{
	if (event.keyCode == 13)
		setUpUserSearch();
};

function setUpUserSearch()
{
	var searchTerm = doc.getElementById('user_search_term').value;

	// This just rewrites the URL so that the page will refresh and the search
	// will be performed.
    var query = window.location.search.substring(1);
	var oldLocation = new String(window.location);

	var queryLocation = oldLocation.indexOf(query);
	var baseLoc = oldLocation.substr(0,queryLocation);
	var newLocation = baseLoc + "section=users&searchString=" + searchTerm;

	window.location = newLocation;
};

function performUserSearch()
{
	var searchTerm = doc.getElementById('user_search_term').value;

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

					displaySearchResults(searchTerm, response.users);
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
                            alert(response.error);
                    }
                }
            }
            catch(e)
            {
                alert("unknown response" + e);
            }
		}
	}



	var params = 'method=searchUsers&searchString=' + searchTerm;;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function displaySearchResults(searchTerm, users)
{
	var numberOfResults = users.length;

	var html = '';
	if (numberOfResults > 0)
	{
		html += '<table border="0">';
		html += '	<tr>';
		html += '		<td><strong>Username/Email</strong></td><td><strong>First Name&nbsp;&nbsp;&nbsp;&nbsp;</strong></td><td><strong>Last Name</strong></td>';
		html += '	</tr>';
		for (var i = 0; i < numberOfResults; i++)
		{
			var user = users[i];

			html += '	<tr>';
			html += '		<td><a href="?section=users&userid='  + user.userid + '">' + user.username + '</a>&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			html += '		<td><a href="?section=users&userid='  + user.userid + '">' + user.firstname + '</a>&nbsp;</td>';
			html += '		<td><a href="?section=users&userid='  + user.userid + '">' + user.lastname + '</a></td>';
			html += '	</tr>';
		}
		html += '</table>';
	}
	else
	{
		html += '<p>No users matched the search term.</p>'
	}

	doc.getElementById('user_search_results').innerHTML = html;
};

function getQueryVariable(variable)
{
    var query = window.location.search.substring(1);
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        if (decodeURIComponent(pair[0]) == variable) {
            return decodeURIComponent(pair[1]);
        }
    }
    //console.log('Query variable %s not found', variable);
}


function showUserInfo(userid)
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

					displayUserInfo(response);
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
                            alert(response.error);
                    }
                }
            }
            catch(e)
            {
                alert("unknown response" + e);
            }
		}
	}



	var params = 'method=getUserInfo&userid=' + userid;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function displayUserInfo(response)
{
	var html = '';

	html += '<h2>Contact Information</h2>';
	html += '<table border="0">';
	html += '	<tr><td>Username:</td><td>' + response.user.username + '&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="prepareSendPasswordReset(\'' + response.user.username + '\', \'' + response.user.userid + '\');" style="cursor: pointer; cursor: hand;">Send Password Reset Email</a></td></tr>';
	html += '	<tr><td>First Name:</td><td>' + response.user.firstname + '</td></tr>';
	html += '	<tr><td>Last Name:</td><td>' + response.user.lastname + '</td></tr>';
	html += '	<tr><td>Facebook Linked:</td><td>';
	if ( (response.user.oauth_provider) && (response.user.oauth_provider == 1) )
		html += 'Yes';
	else
		html += 'No';
	html += '</td></tr>';

	html += '	<tr><td>Number of Lists:</td><td>';
	if (response.listCount)
		html += response.listCount;
	else
		html += 'No lists found';
	html += '</td></tr>';

	html += '	<tr><td>Number of Owned Lists:</td><td>';
	if (response.ownedListCount)
		html += response.ownedListCount;
	else
		html += 'No owned lists found';
	html += '</td></tr>';

	html += '	<tr><td>Number of Shared Lists:</td><td>';
	if (response.sharedListCount)
		html += response.sharedListCount;
	else
		html += 'No shared lists found';
	html += '</td></tr>';

	html += '	<tr><td>Number of Active Tasks:</td><td>';
	if (response.activeTaskCount)
		html += response.activeTaskCount;
	else
		html += 'No active tasks found';
	html += '</td></tr>';

	html += '	<tr><td>Number of Completed Tasks:</td><td>';
	if (response.completedTaskCount)
		html += response.completedTaskCount;
	else
		html += 'No completed tasks found';
	html += '</td></tr>';


	html += '	<tr><td>Migration Status:</td><td>';
	if (response.migrationInfo)
	{
		var migrationInfo = response.migrationInfo;
        if (migrationInfo.failed == 1)
        {
			html += 'MIGRATION FAILED';
			if (migrationInfo.lastAttempt && migrationInfo.lastAttempt > 0)
				html += ' (last attempt: ' + displayHumanReadableDate(migrationInfo.lastAttempt, false, true) + ')';
        }
        else if (migrationInfo.completionDate == 0)
		{
			html += 'MIGRATION IN PROGRESS';
			if (migrationInfo.lastAttempt && migrationInfo.lastAttempt > 0)
				html += ' (last attempt: ' + displayHumanReadableDate(migrationInfo.lastAttempt, false, true) + ')';
		}
		else
		{
			html += 'Migration Completed: ' + displayHumanReadableDate(migrationInfo.completionDate, false, true);
		}
	}
	else
		html += 'Not a migrated user.';
	html += '</td></tr>';

	html += '	<tr><td>Maintenance Status:</td><td>';
	if (response.maintenanceInfo)
	{
		var maintenanceInfo = response.maintenanceInfo;
		if (maintenanceInfo.operationType > 0)
		{
			if (maintenanceInfo.operationType == 1)
			{
				html += 'Processing permanent deletion of duplicate tasks';
			}
			else if (maintenanceInfo.operationType == 2)
			{
				html += 'Processing normal deletion of duplicate tasks';
			}
			else
			{
				html += 'Unknown operation type';
			}
			if (maintenanceInfo.daemonid)
				html += ', PROCESSING NOW (daemonid = ' + maintenanceInfo.daemonid + ')';
			else
				html += ', Not yet processing';
			html += ', timestamp = ' + displayHumanReadableDate(maintenanceInfo.timestamp, false, true);
        }
		else
		{
			html += 'Unknown Type';
		}
	}
	else
		html += '--';
	html += '</td></tr>';


	// Bounced Email Information
	html += '	<tr><td>Email Bounce Info:</td><td>';
	if (response.bounceRecord)
	{
		// Show stuff about the bounce record
		response.bounceRecord.bounceType;
		response.bounceRecord.timestamp;
		response.bounceRecord.bounceCount;
		html += 'Type = ' + response.bounceRecord.bounceType + ', ' + response.bounceRecord.bounceCount + ' times, Last Bounce: ' + displayHumanReadableDate(response.bounceRecord.timestamp);
		html += ' - <a onclick="showClearBouncedEmail(\'' + response.user.userid + '\', \'' + response.bounceRecord.email + '\');" style="cursor: pointer; cursor: hand;">Clear</a></td></tr>';

		// showEditExpiration
	}
	else
	{
		html += 'None detected';
	}


	html += '</table>';

	if (response.subscriptionInfo)
	{
		var subscriptionInfo = response.subscriptionInfo;

		var subscriptionLevelString = '';
		switch(subscriptionInfo.subscription_level)
		{
			case "2":
				subscriptionLevelString = "Trial User";
				break;
			case "3":
				subscriptionLevelString = "Promo Code User";
				break;
			case "4":
				subscriptionLevelString = "Paid User";
				break;
			case "5":
				subscriptionLevelString = "Migrated User";
				break;
			case "6":
				subscriptionLevelString = "Pro User";
				break;
			default:
				subscriptionLevelString = "Unknown";
				break;
		}

		html += '<br/><h2>Premium Account Information</h2>';
		html += '<table border="0">';
		html += '	<tr><td>Expiration Date:</td><td><a onclick="showEditExpiration(\'' + response.user.userid + '\', ' + subscriptionInfo.expiration_date + ');" style="cursor: pointer; cursor: hand;">' + displayHumanReadableDate(subscriptionInfo.expiration_date, false, true) + ' Edit</a></td></tr>';
		html += '	<tr><td>Expired:</td><td>' + subscriptionInfo.expired + '</td></tr>';
		html += '	<tr><td>Account Level:</td><td>' + subscriptionLevelString + '</td></tr>';
		html += '	<tr><td>Account Type:</td><td>' + subscriptionInfo.subscription_type + '</td></tr>';
		if (subscriptionInfo.iap_autorenewing_account)
		{
			var isIAPCancelledString = "NO";
			if (subscriptionInfo.iap_autorenewing_account_cancelled)
			{
				isIAPCancelledString = "YES";
			}
			html += '   <tr><td>Auto-Renewing IAP Customer:</td><td>' + subscriptionInfo.iap_autorenewing_account_type + ', Cancelled = ' + isIAPCancelledString + ', <a onclick="attemptIAPAutorenewal(\'' + response.user.userid + '\');" style="cursor: pointer; cursor: hand;">Attempt Autorenewal (USE WITH CAUTION!)</a></td></tr>';
		}
		else
		{
			html += '   <tr><td>Not IAP Customer:</td><td><a onclick="showConvertToTestIAP(\'' + response.user.userid + '\');" style="cursor: pointer; cursor: hand;">Convert to Test IAP (TESTING ONLY - DO NOT CLICK THIS IN PRODUCTION!)</a></td></tr>';
		}
		html += '</table>';
	}
    html += '<br/><h2>Team Info:</h2>';
    if (response.teamInfo) {
        var is_team_admin = (response.administeredTeams && response.administeredTeams[0].teamid  === response.teamInfo.teamid) ? 'Yes' : 'No';
        var is_billing_admin = (response.user.userid === response.teamInfo.billingUserID) ? 'Yes' : 'No';
        html += '<table border="0">';
        html += '	<tr><td>Team Name: </td><td><a href="?section=teams&teamid=' + response.teamInfo.teamid + '" target="_blank">' + response.teamInfo.teamName + '</a></td></tr>';
        html += '	<tr><td>User is team admin: </td><td>' + is_team_admin + '</td></tr>';
        html += '	<tr><td>User is billing admin: </td><td>' + is_billing_admin + '</td></tr>';
        html += '</table>';
    } else {
        html += 'No Team found.';
    }
    html += '<br/><br/><h2>Purchase History</h2>';
	if ( (response.purchaseHistory) && (response.purchaseHistory.length > 0) )
	{
		var purchaseHistory = response.purchaseHistory;
		html += '<table border="0" cellspacing="10" cellpadding="5">';
		html += '<tr><td><strong>Date</strong>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><strong>Type</strong>&nbsp;&nbsp;&nbsp;&nbsp;</td><td colspan="2"><strong>Description</strong>&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>';

		for (var i = 0; i < purchaseHistory.length; i++)
		{
			var purchase = purchaseHistory[i];

			// Determine whether this was a payment made with Stripe by
			// the existence of "USD" in the description.
			var description = new String(purchase.description);
			usdPos = description.indexOf("USD");
			var isStripePurchase = false;
			if (usdPos > 0)
				isStripePurchase = true;

			html += '<tr>';
			html += '<td>' + displayHumanReadableDate(purchase.timestamp, false, true) + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			html += '<td>' + purchase.subscriptionType + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			html += '<td>' + description + '</td>';

			if (isStripePurchase)
			{
				html += '<td>&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="prepareSendPurchaseReceipt(\'' + response.user.username + '\', \'' + response.user.userid + '\', ' + purchase.timestamp + ', \'' + description + '\');" style="cursor: pointer; cursor: hand;">Send Purchase Receipt</a>';
				if (purchase.stripeChargeID) {
					html += '&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://dashboard.stripe.com/payments/' + purchase.stripeChargeID + '" target="_blank">View Stripe Charge</a></td>';
				} else {
					html += '</td>';
				}
			}
			else
			{
				html += '<td>&nbsp;</td>';
			}

			html += '</tr>';
		}

		html += '</table>';
	}
	else
		html += 'No purchases found.';

    html +='<br/><br/><h2>Gift Purchase History</h2>';
    if(response.giftCodeHistory)
    {
        var giftPurchaseHistory = response.giftPurchaseHistory;
        html += '<h3>Unused Gift Codes</h3>';
        if(response.giftCodeHistory.unused_codes)
        {
            html += getGiftCodeHtmlForGiftCodes(response.giftCodeHistory.unused_codes);
        }
        else
        {
            html += '<p>Error loading gift codes</p>';
        }


        html += '<h3>Used Gift Codes</h3>';
        if(response.giftCodeHistory.used_codes)
        {
            html += getGiftCodeHtmlForGiftCodes(response.giftCodeHistory.used_codes);
        }
        else
        {
            html += '<p>Error loading gift codes</p>';
        }


    }
    else
        html += 'No gift purchases found.';

    html += '<br/><br/>';

	html += '<h2>Administrative Account Activity</h2>';
	if ( (response.accountLog) && (response.accountLog.length > 0) )
	{
		var accountLog = response.accountLog;
		var accountLogAdmins;
		if (response.accountLogAdmins)
			accountLogAdmins = response.accountLogAdmins;

		html += '<table border="0" cellspacing="10" cellpadding="5">';
		html += '<tr>';
		html += '	<td><strong>Date&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '	<td><strong>Admin User&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '	<td><strong>Change Type&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '	<td><strong>Description&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '</tr>';

		for (var i = 0; i < accountLog.length; i++)
		{
			var logItem = accountLog[i];
			var ownerID = logItem.owner_userid;

			html += '<tr>';
			html += '<td>' + displayHumanReadableDate(logItem.timestamp, false, true) + '</td>';

			if (ownerID == response.user.userid)
			{
				html += '<td>-</td>';
			}
			else
			{
				var ownerDisplayName = accountLogAdmins[ownerID];

				html += '<td>' + ownerDisplayName + '</td>';
			}

			var changeType = '';
			switch (logItem.change_type)
			{
				case "1":
					changeType = "Password";
					break;
				case "2":
					changeType = "Username/Email";
					break;
				case "3":
					changeType = "First/Last Name";
					break;
				case "4":
					changeType = "Expiration Date";
					break;
				case "5":
					changeType = "Mailed Receipt";
					break;
				case "6":
					changeType = "Downgrade Account";
					break;
				case "7":
					changeType = "Mail Password Reset";
					break;
				case "8":
					changeType = "Password Reset";
					break;
				case "9":
					changeType = "Clear Bounce Email";
					break;
				case "10":
					changeType = "Re-Migrate Enabled";
					break;
				case "11":
					changeType = "Whitelisted User Freebie";
					break;
				case "16":
					changeType = "Impersonation mode";
					break;
				default:
					changeType = 'Unknown';
					break;
			}

			html += '<td>' + changeType + '</td>';
			html += '<td>' + logItem.description + '</td>';
			html += '</tr>';
		}

		html += '</table>';
	}
	else
		html += 'No log activity found.';


	html += '<h2>User Devices</h2>';
	if ( (response.userDevices) && (response.userDevices.length > 0) )
	{
        var devices = response.userDevices;


		html += '<table border="0" cellspacing="10" cellpadding="50">';
		html += '<tr>';
		html += '	<td><strong>Device Type&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '	<td><strong>OS Version&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '	<td><strong>App ID&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '	<td><strong>App Version&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '	<td><strong>Last Sync&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '	<td><strong>Last Error&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '	<td><strong>Error Message&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
		html += '</tr>';

		for (var i = 0; i < devices.length; i++)
		{
			var device = devices[i];

			html += '<tr>';
			html += '<td>' + device.devicetype + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			html += '<td>' + device.osversion + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			html += '<td>' + device.appid + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			html += '<td>' + device.appversion + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';

            if(typeof(device.timestamp) == 'undefined')
                html += '<td>never&nbsp;&nbsp;&nbsp;&nbsp;</td>';
            else
                html += '<td>' + basicDateTimeFromTimestamp(device.timestamp) + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';

            if(typeof(device.error_number) == 'undefined')
                html += '<td>none&nbsp;&nbsp;&nbsp;&nbsp;</td>';
            else
                html += '<td>' + device.error_number + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';

            if(typeof(device.error_message) == 'undefined')
                html += '<td>none&nbsp;&nbsp;&nbsp;&nbsp;</td>';
            else
                html += '<td>' + device.error_message + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';

            html += '</tr>';
		}

		html += '</table>';
	}
	else
		html += 'No devices found.';


    // only show re-migrate user if they have previoiusly migrated
	if (response.migrationInfo)
	{
		var migrationInfo = response.migrationInfo;
		if (migrationInfo.completionDate != 0)
		{
            html += '<div style="margin-top:10px;"><div class="button" onclick="showReMigrateAccountModal(\'' + response.user.userid + '\')">Re-Migrate User Account</div></div>';
		}
	}


    if(adminIsRoot)
    {
        html += '<div style="margin-top:40px;"><div class="button" onclick="showImpersonateModal(\''+response.user.userid +'\', \''+response.user.username +'\')">Login as ' + response.user.username + '</div></div>';
        html += '<div style="margin-top:40px;"><div class="button" onclick="showDeleteDataModal(\'' + response.user.userid + '\')">Delete User Data</div></div>';
        html += '<div style="margin-top:10px;"><div class="button" onclick="showDeleteAccountModal(\'' + response.user.userid + '\')">Delete User Account</div></div>';
    }

	doc.getElementById('user_search_results').innerHTML = html;
};


function prepareSendPasswordReset(username, userid)
{
	var html = '';
	html += '<table border="0" width="50%">';

	html += '	<tr><td colspan="2">Send ' + username + ' a password reset link</td></tr>';
	html += '	<tr>';
	html += '		<td>Note:</td>';
	html += '		<td><textarea id="password_reset_note"></textarea></td>';
	html += '	</tr>';
	html += '	<tr><td>&nbsp;</td><td>The note you enter above will NOT be sent to the end user and will only be available to other administrators.</td></tr>';
	html += '	<tr><td colspan="2" style="text-align:right;">';
	html += '		<input type="button" value="Cancel" onclick="cancelSendPasswordReset(\'' + userid + '\');" />';
	html += '		<input type="button" id="password_reset_send_button" value="Send" onclick="sendPasswordReset(\'' + userid + '\');" />';
	html += '	</td></tr>';

	html += '</table>';

	doc.getElementById('user_search_results').innerHTML = html;
};


function cancelSendPasswordReset(userid)
{
	showUserInfo(userid);
};


function sendPasswordReset(userid, purchaseTimestamp)
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
					showUserInfo(userid);
                }
                else
                {
					if (response.error)
						alert(response.error);
                }
            }
            catch(e)
            {
                alert("unknown response" + e);
            }
		}
	}

	var note = doc.getElementById('password_reset_note').value;

	var params = 'method=sendResetPasswordEmail&userid=' + userid + '&note=' + note;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function showEditExpiration(userid, currentExpirationTimestamp)
{
	var html = '';
	html += '<table border="0" width="50%">';

	html += '	<tr><td>New Expiration Date:</td>';
	html += '		<td>';
	html += '			<input type="text" id="new_expiration_input" value="' + basicDateFromTimestamp(currentExpirationTimestamp) + '" />';
	html += '		</td>';
	html += '	</tr>';
	html += '	<tr>';
	html += '		<td>Note:</td>';
	html += '		<td><textarea id="new_expiration_note"></textarea></td>';
	html += '	</tr>';
	html += '	<tr><td>&nbsp;</td><td>The user will be sent a confirmation email of their adjusted expiration date. The note you enter above will NOT be sent to the end user and will only be available to other administrators.</td></tr>';
	html += '	<tr><td colspan="2" style="text-align:right;">';
	html += '		<input type="button" value="Cancel" onclick="cancelEditExpirationForUser(\'' + userid + '\');" />';
	html += '		<input type="button" id="new_expiration_save_button" value="Save" onclick="saveNewExpirationForUser(\'' + userid + '\');" />';
	html += '	</td></tr>';

	html += '</table>';

	doc.getElementById('user_search_results').innerHTML = html;
};


function cancelEditExpirationForUser(userid)
{
	showUserInfo(userid);
};


function saveNewExpirationForUser(userid)
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
					showUserInfo(userid);
                }
                else
                {
					if (response.error)
						alert(response.error);
                }
            }
            catch(e)
            {
                alert("unknown response" + e);
            }
		}
	}

	var basicDateString = doc.getElementById('new_expiration_input').value;
	var changeDescription = doc.getElementById('new_expiration_note').value;


	var vars = basicDateString.split('/');
	if (vars.length != 3)
		return;

	var month = vars[0] - 1;
	var day = vars[1];
	var year = vars[2];

	var dateValue = new Date();
	dateValue.setMonth(month);
	dateValue.setDate(day);
	dateValue.setFullYear(year);

	var newExpirationTimestamp = Math.round(dateValue.getTime() / 1000);

	var params = 'method=adjustExpirationDate&userid=' + userid + '&newExpirationTimestamp=' + newExpirationTimestamp + '&note=' + changeDescription;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function showConvertToTestIAP(userid)
{
	var html = '';
	html += '<table border="0" width="50%">';

	html += '	<tr><td>&nbsp;</td><td>WARNING!!!! THIS SHOULD ***ONLY*** BE USED IN A TESTING ENVIRONMENT! This will artificially set up the user to look like they are an IAP customer.</td></tr>';
	html += '	<tr><td colspan="2" style="text-align:right;">';
	html += '		<input type="button" value="Cancel" onclick="cancelConvertToIAP(\'' + userid + '\');" />';
	html += '		<input type="button" id="convert_to_apple_iap_button" value="Convert to Apple IAP" onclick="convertUserToAppleIAP(\'' + userid + '\');" />';
	html += '		<input type="button" id="convert_to_google_iap_button" value="Convert to GooglePlay IAP" onclick="convertUserToGoogleIAP(\'' + userid + '\');" />';
	html += '	</td></tr>';

	html += '</table>';

	doc.getElementById('user_search_results').innerHTML = html;
};


function cancelConvertToIAP(userid)
{
	showUserInfo(userid);
};


function convertUserToAppleIAP(userid)
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
					showUserInfo(userid);
				}
				else
				{
					if (response.error)
						alert(response.error);
				}
			}
			catch(e)
			{
				alert("unknown response" + e);
			}
		}
	}

	var params = 'method=convertToAppleIAP&userid=' + userid;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function convertUserToGoogleIAP(userid)
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
					showUserInfo(userid);
				}
				else
				{
					if (response.error)
						alert(response.error);
				}
			}
			catch(e)
			{
				alert("unknown response" + e);
			}
		}
	}

	var params = 'method=convertToGoogleIAP&userid=' + userid;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function attemptIAPAutorenewal(userid)
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
					showUserInfo(userid);
				}
				else
				{
					if (response.error)
						alert(response.error);
				}
			}
			catch(e)
			{
				alert("unknown response" + e);
			}
		}
	}

	var params = 'method=attemptIAPAutorenewal&userid=' + userid;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function showClearBouncedEmail(userid, email)
{
	var html = '';
	html += '<table border="0" width="50%">';

	html += '	<tr><td>Clear Bounced Email?</td>';
	html += '	<td>' + email + '</td></tr>';
	html += '	<tr>';
	html += '		<td>Note:</td>';
	html += '		<td><textarea id="clear_bounce_note"></textarea></td>';
	html += '	</tr>';
	html += '	<tr><td>&nbsp;</td><td>The note you enter above will NOT be sent to the end user and will only be shown to other administrators.</td></tr>';
	html += '	<tr><td colspan="2" style="text-align:right;">';
	html += '		<input type="button" value="Cancel" onclick="cancelClearBouncedEmailForUser(\'' + userid + '\');" />';
	html += '		<input type="button" id="clear_bounce_save_button" value="Save" onclick="clearBounceEmail(\'' + userid + '\', \'' + email + '\');" />';
	html += '	</td></tr>';

	html += '</table>';

	doc.getElementById('user_search_results').innerHTML = html;
};


function cancelClearBouncedEmailForUser(userid)
{
	showUserInfo(userid);
};


function clearBounceEmail(userid, email)
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
					showUserInfo(userid);
                }
                else
                {
					if (response.error)
						alert(response.error);
                }
            }
            catch(e)
            {
                alert("unknown response" + e);
            }
		}
	}

	var changeDescription = doc.getElementById('clear_bounce_note').value;
	var params = 'method=clearBounceEmail&email=' + email + '&note=' + changeDescription;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function prepareSendPurchaseReceipt(username, userid, purchaseTimestamp, description)
{
	var html = '';
	html += '<table border="0" width="50%">';

	html += '	<tr><td colspan="2">Send ' + username + ' a receipt for: ' + description + '</td></tr>';
	html += '	<tr>';
	html += '		<td>Note:</td>';
	html += '		<td><textarea id="purchase_receipt_note"></textarea></td>';
	html += '	</tr>';
	html += '	<tr><td>&nbsp;</td><td>The note you enter above will NOT be sent to the end user and will only be available to other administrators.</td></tr>';
	html += '	<tr><td colspan="2" style="text-align:right;">';
	html += '		<input type="button" value="Cancel" onclick="cancelSendPurchaseReceipt(\'' + userid + '\');" />';
	html += '		<input type="button" id="purchase_receipt_send_button" value="Send" onclick="sendPurchaseReceipt(\'' + userid + '\',' + purchaseTimestamp + ');" />';
	html += '	</td></tr>';

	html += '</table>';

	doc.getElementById('user_search_results').innerHTML = html;
};


function cancelSendPurchaseReceipt(userid)
{
	showUserInfo(userid);
};


function sendPurchaseReceipt(userid, purchaseTimestamp)
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
					showUserInfo(userid);
                }
                else
                {
					if (response.error)
						alert(response.error);
                }
            }
            catch(e)
            {
                alert("unknown response" + e);
            }
		}
	}

	var note = doc.getElementById('purchase_receipt_note').value;

	var params = 'method=mailPurchaseReceipt&userid=' + userid + '&paymentTimestamp=' + purchaseTimestamp + '&note=' + note;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function basicDateFromTimestamp(unixTimestamp)
{
	if (unixTimestamp == 0)
		return 0;

	var dateValue = new Date(unixTimestamp * 1000);
	var month = dateValue.getMonth() + 1;
	var day = dateValue.getDate();
	var year = dateValue.getFullYear();

	var dateString = month + "/" + day + "/" + year;
	return dateString;
};

function basicDateTimeFromTimestamp(unixTimestamp)
{
	if (unixTimestamp == 0)
		return 0;

	var dateValue = new Date(unixTimestamp * 1000);
	var month = dateValue.getMonth() + 1;
	var day = dateValue.getDate();
	var year = dateValue.getFullYear();

    var hour = dateValue.getHours();
    var minutes = dateValue.getMinutes();
    var seconds = dateValue.getSeconds();

	var dateString = month + "/" + day + "/" + year + " " + hour + ":" + minutes + ":" + seconds;
	return dateString;
};


function selectLink(el)
{
	el.select();
};

function showDeleteDataModal(userid)
{
    var headerText = 'Delete data?';
    var bodyText = 'This will permanently delete all of the user\'s lists, tasks, contexts, comments, and notifications.<br> The user will also be removed from all shared lists.';
    var footerButtons = '<div class="button" onclick="deleteServerData(\'' + userid + '\')">Delete</div>';
    footerButtons += '<div class="button" onclick="hideModalContainer()">Cancel</div>';

    displayModalContainer(bodyText, headerText, footerButtons);
}

function showDeleteAccountModal(userid)
{
    var headerText = 'Delete user?';
    var bodyText = 'This will permanently delete the user\'s account, including all subscriptions, settings, and data.';
    var footerButtons = '<div class="button" onclick="deleteUserAccount(\'' + userid + '\')">Delete</div>';
    footerButtons += '<div class="button" onclick="hideModalContainer()">Cancel</div>';

    displayModalContainer(bodyText, headerText, footerButtons);
}

function showReMigrateAccountModal(userid)
{
    var headerText = 'Re-Migrate User?';
    var bodyText = 'This will re-migrate the user\'s account.';
    var footerButtons = '<div class="button" onclick="enableUserReMigration(\'' + userid + '\')">Re-Migrate</div>';
    footerButtons += '<div class="button" onclick="hideModalContainer()">Cancel</div>';

    displayModalContainer(bodyText, headerText, footerButtons);
}

function showImpersonateModal(userid, username)
{
    var headerText = 'User impersonation';
    var bodyText = 'Do you want  impersonate like '+username+' ?';
    var footerButtons = '<div class="button" onclick="impersonateAccount(\'' + userid + '\', \'' + username + '\')">Impersonate</div>';
    footerButtons += '<div class="button" onclick="hideModalContainer()">Cancel</div>';

    displayModalContainer(bodyText, headerText, footerButtons);
}

function deleteServerData(userid)
{
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
                    //once it succeeds, reload the page
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
                            displayGlobalErrorMessage(response.error);
                        }
                    }
                    else
                    {
                        displayGlobalErrorMessage("Failed to delete data.");
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage("Failed to delete data " + e);
            }
        }
    }


    var params = "method=wipeOutUserData&userid=" + userid;

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");


    ajaxRequest.send(params);
}

function deleteUserAccount(userid)
{
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
                    window.top.location = '?section=users';
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
                            displayGlobalErrorMessage(response.error);
                        }
                    }
                    else
                    {
                        displayGlobalErrorMessage("Failed to delete account.");
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage("Failed to delete account " + e);
            }
        }
    }


    var params = "method=wipeOutUserAccount&userid=" + userid;

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");


    ajaxRequest.send(params);
}


function enableUserReMigration(userid)
{
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
                    window.top.location = '?section=users';
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
                            displayGlobalErrorMessage(response.error);
                        }
                    }
                    else
                    {
                        displayGlobalErrorMessage("Failed to delete account.");
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage("Failed to delete account " + e);
            }
        }
    }


    var params = "method=enableUserReMigration&userid=" + userid;

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");


    ajaxRequest.send(params);
}

function impersonateAccount(userid, username) {
    var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
    if (!ajaxRequest) {
        return false;
    }
    // Create a function that will receive data sent from the server
    ajaxRequest.onreadystatechange = function () {
        if (ajaxRequest.readyState == 4) {
            try {
                hideModalContainer();

                var response = JSON.parse(ajaxRequest.responseText);
                if (response.success == true) {
                    window.location = window.location.origin;
                }
                else {
                    if (response.error) {
                        if (response.error == "authentication") {
                            //make the user log in again
                            history.go(0);
                        }
                        else {
                            displayGlobalErrorMessage(response.error);
                        }
                    }
                    else {
                        displayGlobalErrorMessage("Failed to impersonate account.");
                    }
                }
            }
            catch (e) {
                displayGlobalErrorMessage("Failed to impersonate account " + e);
            }
        }
    }


    var params = "method=impersonateAccount&username=" + encodeURIComponent(username) + '&userid=' + encodeURIComponent(userid);

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");


    ajaxRequest.send(params);
}

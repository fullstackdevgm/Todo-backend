// !Global vars
var doc = document;
var recordsPerPage = 50;
var adminLevel = doc.getElementById('user_admin_level').value;

// !window.load
window.addEventListener('load', setUpTeamsPage, false);

// !functions
function setUpTeamsPage()
{
	var button = doc.getElementById('perform_search_button');
	
	var searchTerm = getQueryVariable("searchString");
	var teamid = getQueryVariable("teamid");
	if (searchTerm)
	{
		doc.getElementById('team_search_term').value = searchTerm;
		performTeamSearch();
	}
	else if (teamid)
	{
		showTeamInfo(teamid);
	}
	
	button.setAttribute('class', 'button');
	button.addEventListener('click', setUpTeamSearch, false);
	
	doc.getElementById('team_search_term').addEventListener('keyup', shouldSearchOnEnter, false);
};

function shouldSearchOnEnter(event)
{
	if (event.keyCode == 13)
		setUpTeamSearch();
};

function setUpTeamSearch()
{
	var searchTerm = doc.getElementById('team_search_term').value;
	
	// This just rewrites the URL so that the page will refresh and the search
	// will be performed.
    var query = window.location.search.substring(1);
	var oldLocation = new String(window.location);
	
	var queryLocation = oldLocation.indexOf(query);
	var baseLoc = oldLocation.substr(0,queryLocation);
	var newLocation = baseLoc + "section=teams&searchString=" + searchTerm;
	
	window.location = newLocation;
};

function performTeamSearch()
{
	var searchTerm = doc.getElementById('team_search_term').value;
	
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

					displaySearchResults(searchTerm, response.teams);
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
	
	

	var params = 'method=searchTeams&searchString=' + searchTerm;;
    
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function displaySearchResults(searchTerm, teams)
{
	var numberOfResults = teams.length;
	
	var html = '';
	if (numberOfResults > 0)
	{
		html += '<table border="0">';
		html += '	<tr>';
		html += '		<td><strong>Team Name</strong></td><td><strong>Business Name&nbsp;&nbsp;&nbsp;&nbsp;</strong></td><td><strong>Num of Members</strong></td>';
		html += '	</tr>';
		for (var i = 0; i < numberOfResults; i++)
		{
			var team = teams[i];
			
			html += '	<tr>';
			html += '		<td><a href="?section=teams&teamid='  + team.teamid + '">' + team.teamName + '</a>&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			html += '		<td><a href="?section=teams&teamid='  + team.teamid + '">' + team.bizName + '</a>&nbsp;</td>';
			html += '		<td><a href="?section=teams&teamid='  + team.teamid + '">' + team.newLicenseCount + '</a></td>';
			html += '	</tr>';
		}
		html += '</table>';
	}
	else
	{
		html += '<p>No teams matched the search term.</p>'
	}
	
	doc.getElementById('team_search_results').innerHTML = html;
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


function showTeamInfo(teamid)
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
					
					displayTeamInfo(response);
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
	
	
	
	var params = 'method=getTeamInfo&teamid=' + teamid;
    
	ajaxRequest.open("POST", ".", true);
	
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function displayTeamInfo(response)
{
	var html = '';
	
	html += '<h2>Team Information</h2>';
	html += '<table border="0">';
	html += '	<tr><td>Team Name:</td><td>' + response.team.teamName + '</td></tr>';
	html += '	<tr><td>Original License Count:</td><td>' + response.team.licenseCount + '</td></tr>';
	html += '	<tr><td>New License Count:</td><td>' + response.team.newLicenseCount + '</td></tr>';
	html += '	<tr><td>Billing Admin:</td><td>' + response.team.billingDisplayName + ' (' + response.team.billingUsername + ')</td></tr>';
	html += '	<tr><td>Expiration Date:</td><td><a onclick="showEditExpiration(\'' + response.team.teamid + '\', ' + response.team.expirationDate + ');" style="cursor: pointer; cursor: hand;">' + displayHumanReadableDate(response.team.expirationDate, false, true) + ' Edit</a></td></tr>';
	html += '	<tr><td>Creation Date:</td><td>' + displayHumanReadableDate(response.team.creationDate, false, true) + '</td></tr>';
	html += '	<tr><td>Modified Date:</td><td>' + displayHumanReadableDate(response.team.modifiedDate, false, true) + '</td></tr>';
	if (response.team.billingFrequency == 1)
		html += '	<tr><td>Billing Frequency:</td><td>Monthly</td></tr>';
	else
		html += '	<tr><td>Billing Frequency:</td><td>Yearly</td></tr>';
	html += '</table>';

	
	html += '<br/><br/>';
	
	
	html += '<h2>Business Contact Information</h2>';
	html += '<table border="0">';
	html += '	<tr><td>Business Name:</td><td>' + response.team.bizName + '</td></tr>';
	html += '	<tr><td>Phone:</td><td>' + response.team.bizPhone + '</td></tr>';
	html += '	<tr><td>Address 1:</td><td>' + response.team.bizAddr1 + '</td></tr>';
	html += '	<tr><td>Address 2:</td><td>' + response.team.bizAddr2 + '</td></tr>';
	html += '	<tr><td>City:</td><td>' + response.team.bizCity + '</td></tr>';
	html += '	<tr><td>State:</td><td>' + response.team.bizState + '</td></tr>';
	html += '	<tr><td>Country:</td><td>' + response.team.bizCountryName + '</td></tr>';
	html += '	<tr><td>Postal Code:</td><td>' + response.team.bizPostalCode + '</td></tr>';
	html += '</table>';
	
	
	html += '<br/><br/>';
	
	
	html += '<h2>Purchase History</h2>';
	if ( (response.purchaseHistory) && (response.purchaseHistory.length > 0) )
	{
		var purchaseHistory = response.purchaseHistory;
		html += '<table border="0" cellspacing="10" cellpadding="10">';
		html += '<tr><td><strong>Date</strong>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><strong>Type</strong>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><strong>Description</strong>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><strong>Amount</strong></td></tr>';
		
		for (var i = 0; i < purchaseHistory.length; i++)
		{
			var purchase = purchaseHistory[i];
			
			html += '<tr>';
			html += '<td>' + displayHumanReadableDate(purchase.timestamp, false, true) + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			html += '<td>' + purchase.subscriptionType + '&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			html += '<td>' + purchase.description + '</td>';
			html += '<td>' + purchase.amount + '</td>';
			
			/*
			if (isStripePurchase)
			{
				html += '<td>&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="prepareSendPurchaseReceipt(\'' + response.user.username + '\', \'' + response.user.userid + '\', ' + purchase.timestamp + ', \'' + description + '\');" style="cursor: pointer; cursor: hand;">Send Purchase Receipt</a></td>';
			}
			else
			{
				html += '<td>&nbsp;</td>';
			}
			*/
			
			html += '</tr>';
		}
		
		html += '</table>';
	}
	else
		html += 'No purchases found.';
    
	
    html += '<br/><br/>';
	
	html += '<h2>Team Administrators</h2>';
	if ((response.admins) && (response.admins.length > 0))
	{
		var admins = response.admins;
		html += '<table border="0" cellspacing="10" cellpadding="10">';
		html += '<tr><td><strong>Name</strong>&nbsp;&nbsp;&nbsp;&nbsp;</td></td></tr>';
		
		for (var i = 0; i < admins.length; i++)
		{
			var admin = admins[i];
			
			html += '<tr>';
			html += '<td>' + admin.name + ' (' + admin.username + ')</td>';
			html += '</tr>';
		}
		
		html += '</table>';
		
	}
	else
	{
		html += 'No administrators! (BAD)';
	}
	
	
    html += '<br/><br/>';
	html += '<h2>Team Members (Consumed license slots)</h2>';
	if ((response.members) && (response.members.length > 0))
	{
		var members = response.members;
		html += '<table border="0" cellspacing="10" cellpadding="10">';
		html += '<tr><td><strong>Name</strong>&nbsp;&nbsp;&nbsp;&nbsp;</td></td></tr>';
		
		for (var i = 0; i < members.length; i++)
		{
			var member = members[i];
			
			html += '<tr>';
			html += '<td>' + member.name + ' (' + member.username + ')</td>';
			html += '</tr>';
		}
		
		html += '</table>';
		
	}
	else
	{
		html += 'No members have been assigned.';
	}

	
	/*
    if(adminIsRoot)
    {
        html += '<div style="margin-top:40px;"><div class="button" onclick="showDeleteDataModal(\'' + response.user.userid + '\')">Delete User Data</div></div>';
        html += '<div style="margin-top:10px;"><div class="button" onclick="showDeleteAccountModal(\'' + response.user.userid + '\')">Delete User Account</div></div>';
    }
	 */
    
	doc.getElementById('team_search_results').innerHTML = html;
};




function showEditExpiration(teamid, currentExpirationTimestamp)
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
	html += '	<tr><td>&nbsp;</td><td>Team administrators will be sent a confirmation email of their adjusted team expiration date. The note you enter above will NOT be sent to the end user and will only be available to other administrators.</td></tr>';
	html += '	<tr><td colspan="2" style="text-align:right;">';
	html += '		<input type="button" value="Cancel" onclick="cancelEditExpirationForTeam(\'' + teamid + '\');" />';
	html += '		<input type="button" id="new_expiration_save_button" value="Save" onclick="saveNewExpirationForTeam(\'' + teamid + '\');" />';
	html += '	</td></tr>';
	
	html += '</table>';
	
	doc.getElementById('team_search_results').innerHTML = html;
};


function cancelEditExpirationForTeam(teamid)
{
	showTeamInfo(teamid);
};


function saveNewExpirationForTeam(teamid)
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
					showTeamInfo(teamid);
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
	
	var params = 'method=adjustTeamExpirationDate&teamid=' + teamid + '&newExpirationTimestamp=' + newExpirationTimestamp + '&note=' + changeDescription;
    
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


function cancelSendPurchaseReceipt(teamid)
{
	showTeamInfo(teamid);
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
					showTeamInfo(userid);
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


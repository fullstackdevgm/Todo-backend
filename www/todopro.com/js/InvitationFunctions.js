
function displayAcceptedEmailInvitationModal(listid, message, showViewButton, showPremiumButton, isIos)
{
    var bodyHTML = '';
    var headerHTML = '';
    var footerHTML = '';
    
    if(showViewButton)
    {
        headerHTML += labels.invitation_accepted;
    }
    else
    {
        headerHTML += labels.invitation_error;
    }
    
    bodyHTML += message;
    
    if(showPremiumButton)
    {
        footerHTML += '<div class="button" onclick="cancelAcceptEmailModal()">' + labels.later + '</div>';
        footerHTML += '<a class="button" href="?appSettings=show&option=subscription">' + labels.go_premium + '</a>';
    }
    else
    {
        if(showViewButton && !isIos)
        {
            footerHTML += '<div id="view_list_button" class="button" onclick="goToAddedList(\'' + listid + '\')">' + labels.view + '</div>';
        }
        footerHTML += '<div class="button" onclick="cancelAcceptEmailModal()">' + labels.ok + '</div>';
    }

    displayModalContainer(bodyHTML, headerHTML, footerHTML);
    document.getElementById('modal_overlay').onclick = null;
};

function goToAddedList(listid)
{
    SetCookieAndLoad('TodoOnlineListId', listid);
}

function cancelAcceptEmailModal()
{
    top.location = ".";
}


//
// Team Invitation Functions
//

//function displayConvertToGiftCode(invitationID, teamName, expirationDateString, email)
//{
//    var bodyHTML = '';
//    var headerHTML = '';
//    var footerHTML = '';
//    
//    headerHTML += 'Join Todo Cloud Team - Create Gift Code';
//    
//    bodyHTML += '<div style="width:480px;">';
//    bodyHTML += '<p>You have been invited to the &quot;' + teamName + '&quot; team, but you already have a valid premium account.</p>'
//    
//    bodyHTML += '<p>Your current account is valid through <b>' + expirationDateString + '</b>.</p>';
//    bodyHTML += '<p>If you continue and join the &quot;' + teamName + '&quot; team, the time left on your existing premium account will be converted to a gift code and emailed to you (<b>' + email + '</b>).</p>';
//    bodyHTML += '<p>You can give this gift code to someone else or redeem it yourself on a different Todo Cloud account.</p>';
//    bodyHTML += '</div>';
//    
//    footerHTML += '<div class="button" id="cancelAcceptGiftCodeButton" onclick="cancelAcceptGiftCode()">Cancel</div>';
//    footerHTML += '<div class="button" id="convertToGiftCodeButton" onclick="convertToGiftCode(\'' + invitationID + '\')">Continue</div>';
//    
//    displayModalContainer(bodyHTML, headerHTML, footerHTML);
//    document.getElementById('modal_overlay').onclick = null;
//}

function displayDonateOrPromoCode(invitationID, teamName, emailAddress, monthsLeft)
{
	var bodyHTML = '';
	var headerHTML = '';
	var footerHTML = '';
	
	headerHTML += labels.join_the_team;
	
	bodyHTML += '<div style="width:480px;">';
    bodyHTML += '<p>' + labels.youre_invited_to_the + ' &quot;' + teamName + '&quot; ' + labels.team + '.</p>';

    bodyHTML += '<p>' + labels.your_subscription_has + ' <b>' + monthsLeft + '</b> ' + labels.month_s_remaining + ':</p>';
	bodyHTML += '<p style="margin-left:20px;text-indent:-20px;"><b>1.</b> '+labels.donate_your_remaining_subscription_to_the +' &quot;' + teamName + '&quot; ' + labels.team + '.</p>';
    bodyHTML += '<p style="margin-left:20px;text-indent:-20px;"><b>2.</b> ' + labels.get_a_promo_code_for + ' ' + monthsLeft + ' ' + labels.months_to_share_with_someone + ' (' + emailAddress + ').</p>';
	bodyHTML += '</div>';
	
	footerHTML += '<div class="button" id="cancelJoinTeamButton" onclick="cancelJoinTeam()" style="background-color:#aaaaaa;">' + labels.cancel + '</div>';
	footerHTML += '<div class="button" id="donateSubscriptionButton" onclick="joinTeam(\'' + invitationID + '\', \'member\', false, true)">' + labels.donate_to_team + '</div>';
	footerHTML += '<div class="button" id="convertToPromoCodeButton" onclick="joinTeam(\'' + invitationID + '\', \'member\', true, false)">' + labels.email_promo_code + '</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	document.getElementById('modal_overlay').onclick = null;
}

function displayJoinTeamModal(invitationID, teamName, membershipType, convertGift)
{
    var bodyHTML = '';
    var headerHTML = '';
    var footerHTML = '';
    
    bodyHTML += '<div style="width:480px;">';
    
    if (membershipType == "admin")
    {
        headerHTML += labels.become_a_team_administrator + ' - ' + teamName;
        bodyHTML += '<p>' + labels.youre_invited_to_a + ' "' + teamName + '" ' + labels.team + '.</p>';
    }
    else
    {
        headerHTML += labels.join_the_team;
        bodyHTML += '<p>'+labels.youre_invited_to +' "' + teamName + '" ' + labels.team + '.</p>';
    }

    bodyHTML += '<p>' + labels.click_join_to_continue + '</p>';
    bodyHTML += '</div>';
    
    footerHTML += '<div class="button" id="cancelJoinTeamButton" onclick="cancelJoinTeam()">' + labels.cancel + '</div>';
    footerHTML += '<div class="button" id="joinTeamButton" onclick="joinTeam(\'' + invitationID + '\', \'' + membershipType + '\', ' + convertGift + ', false)">' + labels.join + '</div>';
    
    displayModalContainer(bodyHTML, headerHTML, footerHTML);
    document.getElementById('modal_overlay').onclick = null;
}

function cancelJoinTeam()
{
    window.location = "?";
    hideModalContainer();
}

function convertToPromoCode(invitationID)
{
	
}

function joinTeam(invitationID, membershipType, convertToPromoCode, donateSubscriptionToTeam)
{
    // Display an activity indicator while we communicate with the server to
    // actually create and convert the user's remaining time to a gift code.
    var doc = document;
	
    var cancelButton = doc.getElementById('cancelJoinTeamButton');
    cancelButton.setAttribute('onclick', '');
    cancelButton.setAttribute('class', 'button disabled');
	
	var donateSubscriptionButton = doc.getElementById('donateSubscriptionButton');
	if (donateSubscriptionButton)
	{
		donateSubscriptionButton.setAttribute('onclick', '');
		donateSubscriptionButton.setAttribute('class', 'button disabled');
	}
	
	var convertToPromoCodeButton = doc.getElementById('convertToPromoCodeButton');
	if (convertToPromoCodeButton)
	{
		convertToPromoCodeButton.setAttribute('onclick', '');
		convertToPromoCodeButton.setAttribute('class', 'button disabled');
	}
	
	if (donateSubscriptionToTeam)
	{
		donateSubscriptionButton.innerHTML += ' <div class="progress_indicator" style="display:inline-block;"></div>';
	}
	else if (convertToPromoCode)
	{
		convertToPromoCodeButton.innerHTML += ' <div class="progress_indicator" style="display:inline-block;"></div>';
	}
	
    var ajaxRequest = getAjaxRequest();
    if (!ajaxRequest)
        return false;
	
    ajaxRequest.onreadystatechange = function()
    {
        if (ajaxRequest.readyState == 4)
        {
            try
            {
                var responseJSON = JSON.parse(ajaxRequest.responseText);
                if (responseJSON.success)
                {
                    if (membershipType == "admin")
                    {
                        window.location = "?appSettings=show&option=teaming";
                    }
                    else
                    {
                        displayJoinTeamSuccess(responseJSON.teamid, responseJSON.teamName);
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
                            displayGlobalErrorMessage(responseJSON.error);
                        }
                    }
                    
                    displayGlobalErrorMessage(labels.unable_to_join_the_team);
                    hideModalContainer();
                    hideModalOverlay();
                }
            }
            catch (e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                hideModalContainer();
                hideModalOverlay();
            }
        }
    }
    
    encodeURIComponent()
    var params = 'method=acceptTeamInvitation&invitationID=' + encodeURIComponent(invitationID) + '&membershipType=' + encodeURIComponent(membershipType);
	
	if (donateSubscriptionToTeam)
	{
		params += '&acceptType=donateToTeam';
	}
	else if (convertToPromoCode)
	{
		params += '&acceptType=convertToPromoCode';
	}
	
    ajaxRequest.open("POST", ".", true);
    
    // Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);
}

function cancelAcceptGiftCode()
{
    window.location = "?";
    hideModalContainer();
}

function displayJoinTeamSuccess(teamID, teamName)
{
    var bodyHTML = '';
    var headerHTML = '';
    var footerHTML = '';
    
    headerHTML += labels.successfully_joined + ' ' + teamName;
    
    bodyHTML += '<div style="width:480px;">';
    bodyHTML += '<p>' + labels.you_have_just_joined_the_team + ' &quot;' + teamName + '.&quot;</p>'
    
    bodyHTML += '</div>';
    
    footerHTML += '<div class="button" onclick="closeJoinTeamSuccess()">'+labels.done+'</div>';
    
    displayModalContainer(bodyHTML, headerHTML, footerHTML);
    document.getElementById('modal_overlay').onclick = null;
}

function closeJoinTeamSuccess()
{
    window.location = "?";
    hideModalContainer();
}


//function convertToGiftCode(invitationID, callback)
//{
//    // Display an activity indicator while we communicate with the server to
//    // actually create and convert the user's remaining time to a gift code.
//    //var doc = document;
//    
//    //var cancelButton = doc.getElementById('cancelAcceptGiftCodeButton');
//    //cancelButton.setAttribute('onclick', '');
//    //cancelButton.setAttribute('class', 'button disabled');
//    
//    //var confirmButton = doc.getElementById('convertToGiftCodeButton');
//    //confirmButton.setAttribute('onclick', ''); // prevent continue from being pressed twice
//    //confirmButton.innerHTML += ' <div class="progress_indicator" style="display:inline-block;"></div>';
//    
//    var ajaxRequest = getAjaxRequest();
//    if (!ajaxRequest)
//        return false;
//    
//    ajaxRequest.onreadystatechange = function()
//    {
//        if (ajaxRequest.readyState == 4)
//        {
//            try
//            {
//                var responseJSON = JSON.parse(ajaxRequest.responseText);
//                if (responseJSON.success)
//                {
//                    if(typeof callback === 'function'){
//                        callback();
//                    }
//                    return true;
//                    window.location = "?acceptTeamInvitation=true&invitationid=" +invitationID;
//                }
//                else
//                {
//                    if (responseJSON.error == "authentication")
//                    {
//                        history.go(0);
//                    }
//                    else
//                    {
//                        if (responseJSON.error)
//                        {
//                            displayGlobalErrorMessage(responseJSON.error);
//                        }
//                    }
//                    displayGlobalErrorMessage("Unable to complete purchase for unknown reason.");
//                    hideModalContainer();
//                    hideModalOverlay();
//                }
//            }
//            catch (err)
//            {
//                displayGlobalErrorMessage("Unknown response from server: " + err);
//                hideModalContainer();
//                hideModalOverlay();
//            }
//        }
//    }
//    
//    encodeURIComponent()
//    var params = 'method=convertAccountToGiftCode&invitationID=' + encodeURIComponent(invitationID);
//    
//    ajaxRequest.open("POST", ".", true);
//    
//    // Send the proper header information along with the request
//    ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
//    ajaxRequest.send(params);
//}


//
// Facebook invitation functions
//

function displayFacebookRequestsModal()
{
    var headerHTML = labels.facebook_requests;
    var bodyHTML = '<div id="facebook_requests_modal_body">' + labels.loading_requests + '</div>';
    var footerHTML = '<div class="button" onclick="cancelFacebookRequestsModal()">' + labels.done + '</div>';
    
    displayModalContainer(bodyHTML, headerHTML, footerHTML);
    getPendingFBRequests();
}

function cancelFacebookRequestsModal()
{
    loadListsControl();
    hideModalContainer();
}

function getPendingFBRequests()
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
            var responseText = ajaxRequest.responseText;
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success && response.requests)
                {

                    var html = getHTMLForFBRequests(response.requests);

                    document.getElementById("facebook_requests_modal_body").innerHTML = html;
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                        {
                            history.go(0);
                        }
                        else
                            displayGlobalErrorMessage(response.error);
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unable_to_get_pending_requests );
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unable_to_get_pending_requests_with_error + ': ' + e);
            }
        }
    }

    var params = "method=getPendingFBRequestsForUser";    

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);   
}

var rowCount = 0;

function getHTMLForFBRequests(requests)
{
    rowCount = requests.length;

    if(requests.length == 0)
        return labels.you_have_no_outstanding_requests;
        
    var html = '<table cellpadding="10">';
    for(var i = 0; i < requests.length; i++)
    {
        var request = requests[i];
        
        html += '<tr id="request_row_' + request.requestid + '">';
        
        var imgurl = "https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif";
        if(request.imgurl)
            imgurl = request.imgurl;
            
        html += '<td><img src="' + imgurl + '"></td>';
        
        if(request.invitationid && request.listname)
        {
            html += '<td>' + request.fromusername + ' ' + labels.has_invited_you_to_share_the_list + ' "' + request.listname + '" </td>';
            html += '<td><div class="button" onclick="removeInvite(\'' + request.invitationid + '\', \'' + request.requestid + '\', \'acceptInvite\')">' + labels.accept + '</div></td>';
        }
        else
        {
            html += '<td>' + labels.the_invitation_from + ' ' + request.fromusername + ' ' + labels.has_been_removed + '</td>';
            html += '<td></td>';
        }
        
        var invitationid = 'invalid';
        if(request.invitationid)
            invitationid = request.invitationid;
            
        html += '<td><div class="button" onclick="removeInvite(\'' + invitationid + '\', \'' + request.requestid + '\', \'deleteInvite\')">' + labels.remove + '</div></td>';
        
        
        html += '</tr>';
    }
    
    html += '</table>';
    
    return html;
    
}

function removeInvite(invitationid, requestid, method)
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
            var responseText = ajaxRequest.responseText;
            try
            {
                //first make sure there wasn't an authentication error
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    var row = document.getElementById('request_row_' + requestid);
                    if(row)
                    {
                        row.parentNode.removeChild(row);
                        rowCount--;
                        if(rowCount == 0)
                        {
                            document.getElementById("facebook_requests_modal_body").innerHTML = labels.you_have_no_outstanding_requests;
                        }
                    }
                }
                else
                {
                    if(response.error)
                    {
                        if(response.error == "authentication")
                        {
                            history.go(0);
                        }
//                        else if(response.error == "premium")
//                        {
//                            //The user couldn't join the list because he's reached his max lists
//                            var footerHTML = '<div class="button" onclick="hideModalContainer()">Later</div>"';
//                            footerHTML += '<a class="button" href="?appSettings=show&option=subscription"> Go Premium </a>';
//                            displayModalContainer('You have reached the allowed number of shared lists for a regular account.<br>Want unlimited shared lists? Click below to upgrade to a premium account!', 'Premium Feature', footerHTML);
//                        }
                        else
                        {
                            displayGlobalErrorMessage(response.error);
                        }
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

    //strip off the fb user id from the end of the request id
    var arr = requestid.split('_');
    requestid = arr[0];

    var params = "invitationid=" + invitationid + "&requestid=" + requestid + "&method=" + method;    

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);

    
}


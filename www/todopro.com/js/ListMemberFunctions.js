isListSharingProcessing = false;
//On page load, load the facebook api
var isFBLoaded = false;
//NCB - Taking out Facebook integration for initial release.
//  window.fbAsyncInit = function() {
//    FB.init({
//      appId      : appid, 
//      status     : true, 
//      cookie     : true, 
//      xfbml      : true, 
//      frictionlessRequests : true
//    });
//    
//    isFBLoaded = true;
//  };
//
//  (function(d){
//     var js, id = 'facebook-jssdk'; if (d.getElementById(id)) {return;}
//     js = d.createElement('script'); js.id = id; js.async = true;
//     js.src = "//connect.facebook.net/en_US/all.js";
//     d.getElementsByTagName('head')[0].appendChild(js);
//   }(document));


function sendRequestViaMultiFriendSelector(role, listName)
{
    if(isFBLoaded)
    {
        var myMessage = "Share the list \"".concat(listName, "\" with me in Todo Cloud!");
        FB.ui({method: 'apprequests',
          message: myMessage
        },
        //This is a closure used to return a function that will pass the role along with
        //the facebook response to resendCallback
        (function(r)
        {
            return function(response)
            {
                /* callback body */
                requestCallback(response, r);
            }
        })(role) /* extra value passed to callback*/
        );
    }
}

function requestCallback(response, role)
{
     if(response && response.request)
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
                        loadInvitationsSection();
                    }
                    else
                    {
                        if(response.error == "authentication")
                        {
                            history.go(0);
                        }
                        else if(response.error == "premium")
                        {
                            displayPremiumDialog();
                        }
                        else
                        {
                            displayGlobalErrorMessage(labels.creating_facebook_invitations_failed);
                        }
                    }
                }
                catch(e)
                {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }
            }
        }
        //var listid = document.getElementById('member_page_listid').value;  
        var listid = memberPageListId;
        var requestid = response.request;
        var params = "method=createFBInvites&listid=" + listid + "&role=" + role + "&requestid=" + requestid;    

        var to = response.to;
        for(var i=0;i<to.length;i++)
        {
            var user = to[i];
            params = params + "&to[]=" + user;
        }

        ajaxRequest.open("POST", ".", true);

        //Send the proper header information along with the request
        ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        ajaxRequest.send(params);
        
    }
}

function loadShareButtons()
{
    var onclick = 'showEmailShareFlyout()';
    if(shareLimitReached)
        onclick = 'displayPremiumDialog()';

    var shareViaEmailHtml = '';
    
    shareViaEmailHtml += '<div style="display:inline-block;">';
    shareViaEmailHtml += '<div class="button" onclick="' + onclick + '">' + labels.add_more_people + '</div>';
    shareViaEmailHtml += '<div class="member_page_flyout" style="position:fixed;margin-left:-190px;" id="email_share_flyout"></div>';
    shareViaEmailHtml += '<div id="email_share_flyout_background" class="property_flyout_background" onclick="hideFlyoutInModalBody(\'email_share_flyout\',\'email_share_flyout_background\')"></div>';
    shareViaEmailHtml += '</div>';

    onclick = 'displayFlyoutInModalBody(\'fb_share_flyout\', \'fb_share_flyout_background\')';
    if(shareLimitReached)
        onclick = 'displayPremiumDialog()';

    var shareViaFacebookHTML = '';
    
    //NCB - Taking out Facebook integration for initial release.
//    shareViaFacebookHTML += '<div style="display:inline-block;">';
//    shareViaFacebookHTML += '<div class="button" onclick="' + onclick + '">Share with Facebook friends</div>';
//    
//    shareViaFacebookHTML += '<div class="member_page_flyout" style="cursor:pointer; position:fixed;" id="fb_share_flyout">';
//    var listname = document.getElementById('member_page_listname').value;
//
//    shareViaFacebookHTML += '<div class="button_flyout_option" id="fb_share_owner_' + memberPageListId + '">Set friends as owners</div>';
//    shareViaFacebookHTML += '<div class="button_flyout_option" id="fb_share_member_' + memberPageListId + '">Set friends as members</div>';
//    
//    shareViaFacebookHTML += '</div>';  //end button_flyout
//    shareViaFacebookHTML += '<div id="fb_share_flyout_background" class="property_flyout_background" onclick="hideFlyoutInModalBody(\'fb_share_flyout\',\'fb_share_flyout_background\')"></div>';
//    shareViaFacebookHTML += '</div>';
    
    var html = shareViaEmailHtml + shareViaFacebookHTML;

    document.getElementById('share_buttons_container').innerHTML = html;
    
    //bind the onclick events using closures!
//    var el = document.getElementById('fb_share_owner_' + memberPageListId);
//    var event = (function(n){return function(){sendRequestViaMultiFriendSelector(2,n);}}(listname));
//    el.bindEvent('click', event, false);
//    
//    el = document.getElementById('fb_share_member_' + memberPageListId);
//    event = (function(n){return function(){sendRequestViaMultiFriendSelector(1,n);}}(listname));
//    el.bindEvent('click', event, false);    
    
}

function showEmailShareFlyout()
{
    displayFlyoutInModalBody('email_share_flyout', 'email_share_flyout_background');
    
    var flyout = document.getElementById('email_share_flyout');
    
    var html = '<textarea id="email" style="height:100px;width:300px;margin:10px 10px 10px 10px;" placeholder="' + labels.enter_one_email_address_per_line + '" onkeyup="shouldEnableSendEmailButton(this)" oninput="shouldEnableSendEmailButton(this)"></textarea>';
    html += '<div style="margin: 10px 0 10px 15px;">';
    html += labels.set_as + ' <select id="roleselect" style="width:150px;height:20px;" >';
    html += '<option selected="selected" value="1">' + labels.members + '</option>';
    html += '<option value="2">' + labels.owners + '</option>';
    html += '</select> ' + labels.of_the_list;
    html += '</div>';
    
    html += '<div id="send_email_button" class="button" style="float:right;margin: 10px 5px 5px 0" onclick="emailList()">'+labels.invite+'</div>';
    
    flyout.innerHTML = html;
    document.getElementById('email').focus();
    shouldEnableSendEmailButton(document.getElementById('email'));

}

//function showShareViaEmailModal()
//{
//    var header = 'Send Email Invitations';
//    var body = '<textarea id="email" style="height:100px;width:300px;" placeholder="Enter one email address per line…" onkeyup="shouldEnableSendEmailButton(this)"></textarea>';
//    body += '<br><br>Set as <select id="roleselect" style="width:100px;" >';
//    body += '<option selected="selected" value="1">members</option>';
//    body += '<option value="2">owners</option>';
//    body += '</select> of the list';
//    
//    var footer = '<div id="send_email_button" class="button" onclick="emailList()">Send</div>';
//    footer += '<div class="button" onclick="hideModalContainer()">Cancel</div>';
//
//	displayModalContainer(body, header, footer);
//    
//    document.getElementById('email').focus();
//    shouldEnableSendEmailButton(document.getElementById('email'));
//}

function shouldEnableSendEmailButton(inputEl)
{
    var enableButton = inputEl.value.length > 0 ? true : false;
	var button = document.getElementById('send_email_button')
	
	if (enableButton)
	{
		button.setAttribute('class', 'button');
		button.onclick = function(){emailList();};
	}
	else
	{
		button.setAttribute('class', 'button disabled');
		button.onclick = null;	
	}
	
}

var memberPageUserRole = 0;
var shareLimitReached = false;
function loadMembersSection()
{
//    var listid = document.getElementById('listId').value;

    var listid = memberPageListId;
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
            var response;
            try
            {
                 response = JSON.parse(ajaxRequest.responseText);
            }
            catch(e)
            {
                displayGlobalErrorMessage("Error: " + e);
                return;
            }
            
            if(response.success == true && response.members)
            {
                if(response.myrole)
                    memberPageUserRole = response.myrole;
                if(response.sharelimit)
                    shareLimitReached = true;

                
                //Wait to load the invitations section until after the members section is loaded, so
                //that we know what the user's current role is
                loadInvitationsSection();
                
                if(memberPageUserRole == 2)
                    loadShareButtons();
                else
                    document.getElementById('share_buttons_container').innerHTML = '';
                
                var html = '';
                var can_remove_user = (response.members.length > 1) ? true : false;
                for(var i = 0; i < response.members.length; i++)
                {
                    html += getHTMLForUserJSON(response.members[i], can_remove_user);
                }
                document.getElementById('list_members_container').innerHTML = html;
                if (can_remove_user) {
                    for(var i = 0; i < response.members.length; i++)
                    {
                        bindButtonActionsForUser(response.members[i].id);
                    }
                }
                
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
                    displayGlobalErrorMessage(labels.unknown_error_loading_list_members);
                }
            }
        }
    }


    var params = "method=getMembersAndRoles&listid=" + listid;    

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);    
}


function getButtonHTMLForUser(userid, userRole, can_remove)
{
    var html = '';
    
    var roleName = subStrings.unknown;
    if(userRole == 1)
        roleName = labels.member;
    else if(userRole == 2)
        roleName = subStrings.owner;

    var curuserid = document.getElementById('member_page_userid').value;
    var disabledClass = 'disabled';
    var onclick = '';
    if(memberPageUserRole == 2 || userid == curuserid)
    {
        disabledClass = '';
        onclick = 'displayFlyoutInModalBody(\'user_role_flyout_' + userid + '\',\'user_role_flyout_' + userid + '_background\')';
    }
    if(can_remove) {
        html += '   <div id="user_role_button_' + userid + '" onclick="' + onclick + '" class="button ' + disabledClass + '">' + roleName + '</div>';
    }else{
        html += '   <div class="button disabled">' + roleName + '</div>';
    }
    if(can_remove) {

        html += '   <div class="member_page_flyout" style="position:fixed; min-width:88px;margin-left:2px;padding: 0;" id="user_role_flyout_' + userid + '">';

        if (memberPageUserRole == 2) {
            var roles = [{'value':2, 'name':subStrings.owner}, {'value':1, 'name':labels.member}];
            for (var i = 0; i < roles.length; i++) {
                var role = roles[i];
            
                var borderedClass = '';
                if (i == roles.length - 1 && can_remove) {
                    borderedClass = 'button_flyout_option_bordered';
                }

//            var imageHTML = '<li class="button_img" style="margin-right:6px;background:none;"></li>';
                var roleOnclick = 'changeUserRole(\'' + userid + '\', \'' + role.value + '\', \'' + memberPageUserRole + '\')';
                if (role.value == userRole) {
//                imageHTML = '<li class="button_img" style="margin-right:6px;"></li>';
                    roleOnclick = 'hideFlyoutInModalBody(\'user_role_flyout_' + userid + '\',\'user_role_flyout_' + userid + '_background\')';
                }

                html += '       <div class="button_flyout_option ' + borderedClass + '" onclick="' + roleOnclick + '">';
//            html += imageHTML;
                html += role.name;
                html += '       </div>'; //end button_flyout_option
            }
        }
        html += '           <div class="button_flyout_option" style="border-top:1px;" id="remove_user_button_' + userid + '">'+labels.remove+'</div>';
        html += '   </div>'; //end user_role_flyout
    }
    html += '   <div id="user_role_flyout_' + userid + '_background" onclick="hideFlyoutInModalBody(\'user_role_flyout_' + userid + '\',\'user_role_flyout_' + userid + '_background\')" class="property_flyout_background"></div>'; //background for flyout

    return html;
}

function bindButtonActionsForUser(userid)
{
    var title = labels.remove_user_q;
    var message = labels.this_user_will_no_longer_be_able_to_view;
    var button = labels.remove;
    
    if(userid == document.getElementById('member_page_userid').value)
    {
        title = labels.leave_list_q;
        message = labels.you_will_no_longer_be_able_to_view_or;
        button = labels.leave;
    }
    
    var onconfirmAction = (function(u){return function(){changeUserRole(u, 'remove'); hideFlyoutInModalBody('sharing_confirmation_dialog','sharing_confirmation_dialog_background');}}(userid));
    var event = (function(u,t,m,a,b){return function(){hideFlyoutInModalBody('user_role_flyout_' + u, 'user_role_flyout_' + u + '_background'); displayConfirmationDialog(t, m, a, b);}}(userid,title, message, onconfirmAction, button));
    
    document.getElementById('remove_user_button_' + userid).bindEvent('click', event, false);
}


function getHTMLForUserJSON(userJSON, can_remove)
{
    if (typeof can_remove == 'undefined') {
        can_remove = false;
    }
    if (can_remove) {
        var html = '<div class="profile_container can-remove clearfix" id="user_profile_' + userJSON.id + '">';
    } else {
        var html = '<div class="profile_container clearfix" id="user_profile_' + userJSON.id + '">';
    }
    //profile picture
    html += '<div class="profile_content">';
    html += '   <a class="img_link">';
    if(userJSON.imgurl)
    {
        html += '   <img src="' + userJSON.imgurl + '" class="small_profile_img" />';
    }
    else
    {
        html += '   <img src="https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif" class="small_profile_img"/>';
    }
    html += '   </a>';
    html += '</div>'; //end profile_content

    
    //profile title and caption
    html += '<div class="profile_content">';
    
    html += '   <div class="content_title">';
    html +=     userJSON.name;
    html += '   </div>'; //end content_title
    
    html += '</div>'; //end profile_content
    
    
    //profile button
    html += '<div class="profile_content profile_button" >';

    html += '   <div class="button_container" id="button_container_' + userJSON.id + '">';
    html += getButtonHTMLForUser(userJSON.id, userJSON.role, can_remove);
    html += '   </div>'; //end button_container
    
    html +=  '</div>'; //end profile_button

    html += '</div>'; //end profile_container

    return html;
}

function loadInvitationsSection()
{
    //var listid = document.getElementById('member_page_listid').value;
    var listid = memberPageListId;
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
            var response;
            try
            {
                response = JSON.parse(ajaxRequest.responseText);
            }
            catch (e)
            {
                 displayGlobalErrorMessage("Error: " + e);
            }
            if(response.success == true && response.invitations)
            {
                var html = '';

                for(var i = 0; i < response.invitations.length; i++)
                {
                    html += getHTMLForInvitationJSON(response.invitations[i]);
                }
            
                var el = document.getElementById('list_invitations_container');
                if(el)
                {
                    el.innerHTML = html;
                    
                    if(memberPageUserRole == 2)
                    {
                        for(var i = 0; i < response.invitations.length; i++)
                        {
                            var invitation = response.invitations[i];
                            bindButtonActionsForInvitation(invitation.invitationid, invitation.fbid, invitation.invitee, invitation.membershiptype);
                        }
                    }
                }
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
                    displayGlobalErrorMessage(labels.unknown_error_loading_list_invitations);
                }
            }
            
        }
    }


    var params = "method=getInvitationsForList&listid=" + listid;    

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);    
}

function getHTMLForInvitationJSON(invitationJSON)
{
    var html = '<div class="profile_container clearfix" id="invitation_profile_' + invitationJSON.invitationid + '">';

    //profile picture
    html += '<div class="profile_content">';
    html += '   <a class="img_link">';
    if(invitationJSON.imgurl)
    {
        html += '   <img src="' + invitationJSON.imgurl + '" class="small_profile_img" />';
    }
    else
    {
        html += '   <img src="https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif" class="small_profile_img"/>';
    }
    html += '   </a>';
    html += '</div>'; //end profile_content

    
    //profile title and caption
    html += '<div class="profile_content">';
    
    html += '   <div class="content_title">';
    html +=     invitationJSON.invitee + ' (' + labels.pending + ')';
    html += '   </div>'; //end content_title
    
    html += '   <div class="content_caption">'+labels.invited_by+' ' + invitationJSON.inviter;
    if(invitationJSON.fbid)
    {
        html += ' '+labels.on_facebook;
    }
    html += ' - ' + invitationJSON.readabledate;
    html += '   </div>'; //end content_caption;
    
    html += '</div>'; //end profile_content
    
    
    //profile button
    html += '<div class="profile_content profile_button" >';

    html += '   <div class="button_container" id="button_container_' + invitationJSON.invitationid + '">';
    
    var fbid = null;
    if(invitationJSON.fbid)
        fbid = invitationJSON.fbid;
    
    html += getButtonHTMLForInvitation(invitationJSON.invitationid, invitationJSON.membershiptype, fbid);
    html += '   </div>'; //end button_container
    
    html +=  '</div>'; //end profile_button

    html += '</div>'; //end profile_container

    return html;
}

function getButtonHTMLForInvitation(invitationid, invitationrole, fbid)
{
    var html = '';
    var roleName = subStrings.unknown;
    if(invitationrole == 1)
        roleName = labels.member;
    else if(invitationrole == 2)
        roleName = subStrings.owner;
        
    var disabledClass = 'disabled';
    var onclick = '';
    if(memberPageUserRole == 2)
    {
        disabledClass = '';
        onclick = 'displayFlyoutInModalBody(\'invitation_role_flyout_' + invitationid + '\',\'invitation_role_flyout_' + invitationid + '_background\');';
    }
    html += '<div id="invitation_role_button_' + invitationid + '" onclick="' + onclick + '" class="button ' + disabledClass + '">' + roleName + '</div>';
    
    if(memberPageUserRole == 2)
    {
        html += '   <div class="member_page_flyout" style="position:fixed; min-width:88px;margin-left:2px;padding: 0;" id="invitation_role_flyout_' + invitationid + '">';
    
        var roles = [{'value':2, 'name':subStrings.owner}, {'value':1, 'name':labels.member}];
        for(var i = 0 ; i < roles.length; i++)
        {
            var role = roles[i];
            var borderedClass = '';
            
            if(i == roles.length - 1)
            {
                borderedClass = 'button_flyout_option_bordered';
            }
            var fbidString = 'null';
            if(fbid != null)
                fbidString = '\'' + fbid + '\'';
            
//            var imageHTML = '<li class="button_img" style="margin-right:6px;background:none;"></li>';
//            if(role.value == invitationrole)
//            {
//                imageHTML = '<li class="button_img" style="margin-right:6px;"></li>';
//            }
            
            html += '       <div id="invitation_role_button_' + invitationid + '_' + role.name +'" class="button_flyout_option ' + borderedClass + '" >';
//            html += imageHTML;
            html += role.name;
            html += '       </div>'; //end button_flyout_option
        }
        
        //Add the resend option
        html += '<div id="resend_invitation_button_' + invitationid + '" class="button_flyout_option button_flyout_option_bordered">';
//        html += '<li class="button_img" style="margin-right:6px;background:none;"></li>';
        html += labels.resend;
        html += '</div>';
    
        html += '<div id="remove_invitation_button_' + invitationid + '" class="button_flyout_option">';
//        html += '<li class="button_img" style="margin-right:6px;background:none;"></li>';
        html += labels.remove;
        html += '</div>';
       
    
        html += '</div>'; //end invitation_role_flyout
        html += '<div id="invitation_role_flyout_' + invitationid + '_background" onclick="hideFlyoutInModalBody(\'invitation_role_flyout_' + invitationid + '\',\'invitation_role_flyout_' + invitationid + '_background\')" class="property_flyout_background"></div>'; //background for flyout
    
    }
    
    return html;
}

function bindButtonActionsForInvitation(invitationid, fbid, invitee, invitationrole)
{
    var roles = [{'value':2, 'name':subStrings.owner}, {'value':1, 'name': labels.member}];
    for(var i = 0 ; i < roles.length; i++)
    {
        var role = roles[i];
        var buttonEl = document.getElementById('invitation_role_button_' + invitationid + '_' + role.name);
        
        var event = null;
        if(role.value == invitationrole)
        {
            event = (function(i){return function(){hideFlyoutInModalBody('invitation_role_flyout_' + i, 'invitation_role_flyout_' + i + '_background');}}(invitationid));
        }
        else
        {
            event = (function(i,r,f,n){return function(){updateInvitation(i,r,f,n);}}(invitationid, role.value, fbid, invitee));
        }
        buttonEl.bindEvent('click', event, false);
        
    }

    var message = '';
    var onconfirmAction = null;
    if(fbid != null)
    {
        message = sprintf(labels.another_facebook_request_will_be_sent_to, invitee);
        onconfirmAction = (function(i,f){return function(){resendFBRequest(i,f);hideFlyoutInModalBody('sharing_confirmation_dialog','sharing_confirmation_dialog_background');}}(invitationid, fbid));
    }
    else
    {
        message = sprintf(labels.another_email_will_be_sent_to, invitee);
        onconfirmAction = (function(i){return function(){resendEmail(i);hideFlyoutInModalBody('sharing_confirmation_dialog','sharing_confirmation_dialog_background');}}(invitationid));
    }
    
    var event = (function(i,m,a){return function(){hideFlyoutInModalBody('invitation_role_flyout_' + i, 'invitation_role_flyout_' + i + '_background'); displayConfirmationDialog(labels.resend_invitation_q , m, a, labels.resend);}}(invitationid, message, onconfirmAction));
    document.getElementById('resend_invitation_button_' + invitationid).bindEvent('click', event, false);

    message = sprintf(labels.remove_invitation_message, invitee);
    onconfirmAction = (function(i){return function(){deleteInvitation(i); hideFlyoutInModalBody('sharing_confirmation_dialog','sharing_confirmation_dialog_background');}}(invitationid));
    event = (function(i,m,a){return function(){hideFlyoutInModalBody('invitation_role_flyout_' + i, 'invitation_role_flyout_' + i + '_background'); displayConfirmationDialog(labels.remove_invitation_q, m, a, labels.remove);}}(invitationid, message, onconfirmAction));
    document.getElementById('remove_invitation_button_' + invitationid).bindEvent('click', event, false);
}
    

function changeUserRole(uid, role)
{
    //var listid = document.getElementById('member_page_listid').value;
    var listid = memberPageListId;
    var userid = document.getElementById('member_page_userid').value;
    var can_remove = document.getElementById('user_profile_'+userid).classList.contains('can-remove');
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
                    if(userid == uid)
                    {
                        if(role == 'remove')
                            SetCookieAndLoad('TodoOnlineListId', 'all');
                        else
                        {
                            //we only got here if the user demoted himself from owner, so hide the share buttons and reload the other sections
                            document.getElementById('share_buttons_container').innerHTML = '';
                            memberPageUserRole = role;
                            loadMembersSection();

                        }
                    }
                    else
                    {
                        if(role == 'remove')
                        {
                            var element = document.getElementById('user_profile_' + uid);
                            if(element)
                            {
                                element.parentNode.removeChild(element);
                            }

                        }
                        else
                        {
                            var button = document.getElementById('button_container_' + uid);
                            if(button)
                            {
                                button.innerHTML = getButtonHTMLForUser(uid, role, can_remove);
                                bindButtonActionsForUser(uid);
                            }

                        }
                    }
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
                            if(response.error == "lastowner")
                            {
                                displayGlobalErrorMessage(labels.you_may_not_remove_the_last_owner );
                            }
                            else
                            {
                                displayGlobalErrorMessage(response.error);
                            }
                        }
                    }
                    else
                    {
                        displayGlobalErrorMessage(labels.unknown_error_when_changing_user);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }

    var params = "method=changeRole&listid=" + listid + "&uid=" + uid + "&role=" + role;    

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params);

}
  
function emailList(listid, role, email)
{
    if (isListSharingProcessing === false) {
        isListSharingProcessing = true;
        //var listid = document.getElementById('member_page_listid').value;
        var listid = memberPageListId;
        var role = document.getElementById('roleselect').value;
        var email = document.getElementById('email').value;


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
                        loadInvitationsSection();
                        hideFlyoutInModalBody('email_share_flyout', 'email_share_flyout_background');
                        document.getElementById('sharing_done_button').innerHTML = 'Done';
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
                            else if(response.error == "premium")
                            {
                                displayPremiumDialog();
                            }
                            else
                            {
                                displayGlobalErrorMessage(response.error);
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
            isListSharingProcessing = false;
        }


        var params = "method=emailInvites&listid=" + listid + "&email=" + encodeURIComponent(email) + "&role=" + role;

        ajaxRequest.open("POST", ".", true);

        //Send the proper header information along with the request
        ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");


        ajaxRequest.send(params);

    }

}

function resendEmail(invitationId)
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
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    loadInvitationsSection();
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
                        displayGlobalErrorMessage(labels.unable_to_resend_invitation);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }
    var params = "method=resendInvite&invitationid=" + invitationId;    

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);
}

function deleteInvitation(invitationId)
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
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    var el = document.getElementById('invitation_profile_' + invitationId);
                    el.parentNode.removeChild(el);
                    document.getElementById('sharing_done_button').innerHTML = 'Done';
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
                        displayGlobalErrorMessage(labels.unable_to_delete_invitation);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }
    var params = "method=deleteInvite&invitationid=" + invitationId;    

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);
    
}

function updateInvitation(invitationId, role, fbid, invitee)
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
                var response = JSON.parse(ajaxRequest.responseText);
                if(response.success)
                {
                    var el = document.getElementById('button_container_' + invitationId);
                    el.innerHTML = getButtonHTMLForInvitation(invitationId, role, fbid);
                    document.getElementById('sharing_done_button').innerHTML = 'Done';
                    bindButtonActionsForInvitation(invitationId, fbid, invitee, role);
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
                        displayGlobalErrorMessage(labels.unable_to_delete_invitation );
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
            }
        }
    }
    var params = "method=updateInvite&invitationid=" + invitationId + "&role=" + role;

    ajaxRequest.open("POST", ".", true);

    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);
    
}

function resendFBRequest(invitationId, fbuserid)
{
    var listname = document.getElementById('member_page_listname').value;
    if(isFBLoaded)
    {
        var myMessage = "Share the list \"".concat(listname, "\" with me in Todo Cloud!");
        FB.ui({method: 'apprequests',
          message: myMessage,
          to: fbuserid
        },
        //This is a closure used to return a function that will pass the invite id along with
        //the facebook response to resendCallback
        (function(inviteId)
        {
            return function(response)
            {
                /* callback body */
                resendCallback(response, inviteId);
            }
        })(invitationId) /* extra value passed to callback*/
        
        );
    }
}
function resendCallback(response, invitationId)
{
    if(response && response.request && response.to)
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
                        loadInvitationsSection();
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
                            displayGlobalErrorMessage(labels.failed_to_update_facebook_request);
                        }
                    }
                }
                catch(e)
                {
                    displayGlobalErrorMessage(labels.unknown_error + ' ' + e);
                }

            }
        }
    
        var requestid = response.request;
        var sentUser = response.to[0];
        var params = "method=modifyFBInvite&invitationid=" + invitationId + "&requestid=" + requestid + "&fbuserid=" + sentUser;

        var to = response.to;
        for(var i=0;i<to.length;i++)
        {
            var user = to[i];
            params = params + "&to[]=" + user;
        }

        ajaxRequest.open("POST", ".", true);

        //Send the proper header information along with the request
        ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        ajaxRequest.send(params);

        
    }
}

function displayConfirmationDialog(title, message, onconfirmAction, confirmButtonTitle)
{
    var html = '';
    
    html += '<div class="confirmation_dialog_header">' + title + '</div>';
    html += '<div class="confirmation_dialog_body">' + message + '</div>';
    html += '<div class="modal_footer">';
    html += '<div class="button" onclick="hideFlyoutInModalBody(\'sharing_confirmation_dialog\',\'sharing_confirmation_dialog_background\')">' + labels.cancel + '</div>';
    html += '<div id="sharing_confirmation_button" class="button" >' + confirmButtonTitle + '</div>';
    html += '</div>';
    
    document.getElementById('sharing_confirmation_dialog').innerHTML = html;
    
    displayFlyoutInModalBody('sharing_confirmation_dialog', 'sharing_confirmation_dialog_background');
    centerElementInElement(document.getElementById('sharing_confirmation_dialog'), document.getElementById('modal_container'));
    document.getElementById('sharing_confirmation_button').bindEvent('click', onconfirmAction, false);
}

function displayPremiumDialog()
{
    var header = labels.premium_feature;
    var body = labels.sharing_lists_is_a_premium_feature;
    var footerHTML =    '<a class="button" href="?appSettings=show&option=subscription">' + labels.go_premium + '</a>';
        footerHTML +=   '<div class="button"  onclick="hideFlyoutInModalBody(\'sharing_confirmation_dialog\',\'sharing_confirmation_dialog_background\')">' + labels.later + '</div>';
    
    var html = '<div class="confirmation_dialog_header">' + header + '</div>';
    html += '<div class="confirmation_dialog_body">' + body + '</div>';
    html += '<div class="modal_footer">' + footerHTML + '</div>';

    document.getElementById('sharing_confirmation_dialog').innerHTML = html;
    
    displayFlyoutInModalBody('sharing_confirmation_dialog', 'sharing_confirmation_dialog_background');
    centerElementInElement(document.getElementById('sharing_confirmation_dialog'), document.getElementById('modal_container'));

}




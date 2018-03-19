
loadResetPasswordUI();
//console.log('loading ResetPasswordFunctions.js');

var passwordIsValid = false;

function compareNewPasswords()
{
	var doc = document;
	var pass1 = doc.getElementById('new_password_1').value;
	var pass2 = doc.getElementById('new_password_2').value;

	if (pass1.length > 0 && pass2.length > 0 && pass1 != pass2)
	{
		doc.getElementById('reset_pw_error_message').innerHTML = labels.passwords_dont_match;
		passwordIsValid = false;
	}
	else
	{
		doc.getElementById('reset_pw_error_message').innerHTML = '' ;
		passwordIsValid = true;
	}

};

function setNewPassword()
{
	var doc = document;

	if(passwordIsValid)
	{
		var uId = doc.getElementById('uid').value;
		var resetId = doc.getElementById('resetid').value;
		var pw = doc.getElementById('new_password_2').value;

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
		                var modalTitle = 'Success!';
                        var modalBody = '<p style="width:310px;margin:0">' + labels.your_password_was_successfully_changed + '</p>';
                        var modalFooter = '<a href="."><div class="button" >' + labels.ok + '</div>';

		               displayModalContainer(modalBody, modalTitle, modalFooter);
	                }
	                else
	                {
	                	displayResetPasswordError(responseJSON.error);
	                }
	            }
	            catch(err)
	            {
	               	displaySignInErrorMessage(labels.unknown_error + ' '+ err);
	            }
			}
		}

		var params = 'method=resetPassword&userid=' + uId + '&resetid=' + resetId + '&password=' + pw;

		ajaxRequest.open("POST", ".", false);
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);
	}
	else
		doc.getElementById('reset_pw_error_message').innerHTML = labels.Please_ake_sure_your_passwords_match;
};

function displayResetPasswordError(errMsg)
{
	document.getElementById('reset_pw_error_message').innerHTML = errMsg;
};


function loadResetPasswordUI()
{
	var bodyHTML = '';
	var titleHTML = labels.set_a_new_password ;
	var footerHTL = '<div class="button" onclick="setNewPassword()">'+labels.set_a_new_password +'</div>';
	
	bodyHTML += '	<ul class="simple_list clearfix" style="margin:0">';
    bodyHTML += '		<li class="clearfix"><label for="new_password_1">' + labels.new_password + '</label><input id="new_password_1"  onkeyup="compareNewPasswords()" type="password" /></li>';
    bodyHTML += '		<li class="clearfix"><label for="new_password_2">' + labels.confirm_password + '</label><input id="new_password_2" onkeyup="compareNewPasswords()" type="password" /></li>';
    bodyHTML += '	</ul>';
    bodyHTML += '	<div id="reset_pw_error_message"></div>';
    
    displayModalContainer(bodyHTML, titleHTML, footerHTL);
    document.getElementById('new_password_1').focus();
};
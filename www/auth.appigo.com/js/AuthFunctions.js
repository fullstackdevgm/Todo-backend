
function getAjaxRequest()
{
	var ajaxRequest;  // The variable that makes Ajax possible!
    
	try
	{
		// Opera 8.0+, Firefox, Safari
		ajaxRequest = new XMLHttpRequest();
	}
	catch (e)
	{
		// Internet Explorer Browsers
		try
		{
			ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch (e)
		{
			try
			{
				ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch (e)
			{
				// Something went wrong
				alert("Your browser broke!");
				return;
			}
		}
	}
	return ajaxRequest;
}


function submitRequestResetPassword()
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
                doc.getElementById('status_message').innerHTML = "";
                doc.getElementById('error_status_message').innerHTML = "";

                var responseJSON = JSON.parse(ajaxRequest.responseText);
                if(responseJSON.success)
                {
                    doc.getElementById('status_message').innerHTML = "Check your email and click on the link we sent you.";
   				}
                else if(responseJSON.redirectURL)
                {
                    window.location.href = responseJSON.redirectURL;
                }
                else
                {
                    doc.getElementById('error_status_message').innerHTML = responseJSON.error;
                }
            }
            catch(err)
            {
                doc.getElementById('error_status_message').innerHTML = "unknown response from server";
            }
		}
	}

	var params = 'method=sendResetPasswordEmail&username=' + doc.getElementById('username').value;

	ajaxRequest.open("POST", ".", false);
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    

	ajaxRequest.send(params);
};


function emailSignup()
{
	var doc = document;
	
	var username = doc.getElementById('username').value;
    
	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;
    
	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                doc.getElementById('status_message').innerHTML = "";
                doc.getElementById('error_status_message').innerHTML = "";
                
                var responseJSON = JSON.parse(ajaxRequest.responseText);
                if(responseJSON.success)
                {
                    doc.getElementById('status_message').innerHTML = "Check your email to verify your email address and complete your sign up";
                }
                else
                {
                    doc.getElementById('error_status_message').innerHTML = responseJSON.error;
                    //	                	doc.getElementById('sign_up_status').setAttribute('style', 'display:inline-block;margin-top: 2px;width: 146px;');
                }
            }
            catch(err)
            {
                doc.getElementById('error_status_message').innerHTML = err;
                //                    doc.getElementById('sign_up_status').setAttribute('style', 'display:inline-block;margin-top: 2px;width: 146px;');
            }
		}
	}
    
	var params = 'method=joinMailingList&email=' + encodeURIComponent(username);
	ajaxRequest.open("POST", ".", false);
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
	ajaxRequest.send(params);
    
    return true;
};


function signIn()
{
	var doc = document;
	
	var username = doc.getElementById('username').value;
	var password = doc.getElementById('password').value;

	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                doc.getElementById('status_message').innerHTML = "";
                doc.getElementById('error_status_message').innerHTML = "";
                
                var responseJSON = JSON.parse(ajaxRequest.responseText);
                if(responseJSON.success)
                {
                    window.location.href = responseJSON.redirectURL;
                }
                else
                {
                    doc.getElementById('error_status_message').innerHTML = responseJSON.error;
                    //	                	doc.getElementById('sign_up_status').setAttribute('style', 'display:inline-block;margin-top: 2px;width: 146px;');
                }
            }
            catch(err)
            {
                doc.getElementById('error_status_message').innerHTML = err;
                //                    doc.getElementById('sign_up_status').setAttribute('style', 'display:inline-block;margin-top: 2px;width: 146px;');
            }
		}
	}

	var params = 'method=login&username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password);
	ajaxRequest.open("POST", ".", false);
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	ajaxRequest.send(params);
    
    return true;
};


/* !Sign Up Functions */

function validateFirstName()
{
	var result = false;
	var doc = document;
	var inputEl = doc.getElementById('first_name');
	var value = inputEl.value;
	var errorEl = doc.getElementById('first_name_status');
	
	if (value.length > 0)
	{
		result = true;
		errorEl.setAttribute('style', '');
	}
	else
	{
		errorEl.innerHTML = '* required';
//		errorEl.style.display = 'block';
	}
		
	return result;
};

function validateLastName()
{
	var result = false;
	var doc = document;
	var inputEl = doc.getElementById('last_name');
	var value = inputEl.value;
	var errorEl = doc.getElementById('last_name_status');
	
	if (value.length > 0)
	{
		result = true;
		errorEl.setAttribute('style', '');
	}
	else
	{
		errorEl.innerHTML = '* required';
//		errorEl.style.display = 'block';
	}
		
	return result;
};

function validateEmail()
{
	var result = false;
	var doc = document;
	var inputEl = doc.getElementById('username');
	var value = inputEl.value;
	var errorEl = doc.getElementById('username_status');
	var displayError = false;
	var errorMsg = 'unknown error';
	
	var atpos = value.indexOf("@");
	var dotpos = value.lastIndexOf(".");
	if (atpos<1 || dotpos < atpos + 2 || dotpos + 2 >= value.length)
	{
		errorMsg = 'invalid email address';
		displayError = true;
	}
	
	if(value.indexOf('+') > -1)//check for + char
	{
		if (!value.endsWith('@appigo.com')) //allow + char for appigo.com domain
		{
			errorMsg = '+ character is not allowed';
			displayError = true;
		}
	}
	
	if (displayError)
	{
		errorEl.innerHTML = errorMsg;
		errorEl.style.display = 'block';
	}
	else
	{
		errorEl.innerHTML = "";
		errorEl.setAttribute('style', '');
		result = true;
	}
	
	return result;
};

function validatePasswords()
{
	var result = false;
	var doc = document;
	var minLength = 6;
	var inputEl = doc.getElementById('password');
	var inputConfirmEl = doc.getElementById('password_2');
	var value = inputEl.value;
	var valueConfirm = inputConfirmEl.value;
	var errorEl = doc.getElementById('password_status');
	
	if (value.length > 0 && valueConfirm.length > 0)
	{
		if (value.length >= 5)
		{
			//console.log('test: ' + value.indexOf(' '));
			if (value.indexOf(' ') == -1)
			{
				if (value == valueConfirm)
				{
					result = true;
					errorEl.innerHTML = '';
					errorEl.setAttribute('style', '');
				}
				else
				{
					errorEl.innerHTML = 'Passwords don\'t match';
					errorEl.style.display = 'block';
				}
			}
			else
			{
				errorEl.innerHTML = 'Spaces are not allowed';
				errorEl.style.display = 'block';
			}
			
		}
		else
		{
			errorEl.innerHTML = 'Password must be at least ' + minLength + ' characters';
			errorEl.style.display = 'block';
		}
		
	}
	
	return result;
};

function signUp()
{
	if (validatePasswords() && validateEmail() && validateFirstName() && validateLastName())
	{
		var doc = document;
	
		var email = doc.getElementById('username').value;
		var password_1 = doc.getElementById('password').value;
		var firstName = doc.getElementById('first_name').value;
		var lastName = doc.getElementById('last_name').value;
	
		var ajaxRequest = getAjaxRequest();
		if(!ajaxRequest)
			return false;
	
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
	            try
	            {
                    doc.getElementById('status_message').innerHTML = "";
                    doc.getElementById('error_status_message').innerHTML = "";
                    
	                var responseJSON = JSON.parse(ajaxRequest.responseText);
	                if(responseJSON.success)
	                {
                        window.location.href = responseJSON.redirectURL;
	                }
	                else
	                {
	                	doc.getElementById('error_status_message').innerHTML = responseJSON.error;
//	                	doc.getElementById('sign_up_status').setAttribute('style', 'display:inline-block;margin-top: 2px;width: 146px;');
	                }
	            }
	            catch(err)
	            {
                    doc.getElementById('error_status_message').innerHTML = err;
//                    doc.getElementById('sign_up_status').setAttribute('style', 'display:inline-block;margin-top: 2px;width: 146px;');
	            }
			}
		}
		var emailoptin = doc.getElementById('emailoptin').checked ? 1 : 0;
		
		var params = 'method=signup&firstname=' + firstName + '&lastname=' + lastName + '&username=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password_1) + '&emailoptin=' + emailoptin;
	
		ajaxRequest.open("POST", ".", false);
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    
		ajaxRequest.send(params);
	}
	else
		return false;
};


function submitResetPassword()
{
	if (validatePasswords())
	{
		var doc = document;
        
		var userid = doc.getElementById('userid').value;
		var resetid = doc.getElementById('resetid').value;
		var password = doc.getElementById('password').value;
        
		var ajaxRequest = getAjaxRequest();
		if(!ajaxRequest)
			return false;
        
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
	            try
	            {
                    doc.getElementById('status_message').innerHTML = "";
                    doc.getElementById('error_status_message').innerHTML = "";
                    
	                var responseJSON = JSON.parse(ajaxRequest.responseText);
	                if(responseJSON.success)
	                {
	                	doc.getElementById('status_message').innerHTML = 'Password was reset.  You can login <a href=".">here</a>';
	                }
	                else
	                {
	                	doc.getElementById('error_status_message').innerHTML = responseJSON.error;
	                }
	            }
	            catch(err)
	            {
                    doc.getElementById('error_status_message').innerHTML = err;
	            }
			}
		}

		var params = 'method=resetPassword&userid=' + encodeURIComponent(userid) + '&resetid=' + encodeURIComponent(resetid) + '&password=' + encodeURIComponent(password);
        
		ajaxRequest.open("POST", ".", false);
	    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    
		ajaxRequest.send(params);
	}
	else
		return false;
};







































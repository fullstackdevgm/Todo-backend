function loadAdminSignInForm()
{
	var doc = document;
	var html= '';
		html += '	<div class="sign_in_form" style="width:350px;">';
		html += '		<div class="labeled_control">';
		html += '			<label class="bold">Email</label>';
		html += '			<input type="text" id="email" placeholder="your_email@appigo.com" />';
		html += '		</div>';
		html += '		<div class="labeled_control">';
		html += '			<label class="bold">Password</label>';
		html += '			<input type="password" id="password" placeholder="your password" />';
		html += '		</div>';
		html += '		<div class="labeled_control" style="text-align:right;width:331px;">';
		html += '			<div class="button" id="signInButton">Sign In</div>';
		html += '		</div>';
    
    //NCB - Taking out Facebook integration for initial release.
//		html += '		<div class="labeled_control" style="text-align:right;width:331px;">';
//		html += '			<span  onclick="signInViaFacebook()" style="cursor:pointer;">Log in via Facebook</span>';
//		html += '		</div>'
		html += '	</div>';
		
	doc.write(html);	
	doc.getElementById('signInButton').addEventListener('click', signInAdmin, false);
	doc.getElementById('password').addEventListener('keyup', shouldSignIn, false);
	
};
function signInAdmin()
{
	var doc = document;
	var email = doc.getElementById('email').value;
	var password = doc.getElementById('password').value;
	
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
                
                //Bug 7229 - go back to the same page so we'll preserve the original URL
//                	window.location = '.';
                    history.go(0);
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
                        alert("Error from server: " + response.error);
                    }
                }
            }
            catch(e)
            {
                alert("Unknown response from server");
            }
        }
    }

    
    var params = "method=login&username=" + email + "&password=" + password;
    
    ajaxRequest.open("POST", "." , true);
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.send(params); 
};

function signInViaFacebook()
{
	window.location = '?fblogin=jimminycricket';
};

function shouldSignIn(event)
{
	if(event.keyCode == 13)
		signInAdmin();	
};

function updateSystemSetting(settingName)
{
	var doc = document;
    if (doc.getElementById(settingName).type == 'checkbox') {
        if (doc.getElementById(settingName).checked) {
            var settingValue = true;
        } else {
            var settingValue = false;
        }
    } else {
        var settingValue = doc.getElementById(settingName).value;
    }
	var settingButton = doc.getElementById("button_" + settingName);
	var settingStatus = doc.getElementById("status_" + settingName);
	settingStatus.style.visibility = 'hidden';
	
	settingButton.setAttribute('class', 'button disabled');
	
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
					settingButton.setAttribute('class', 'button');
					settingStatus.innerHTML = "Updated!";
					settingStatus.style.visibility = 'visible';
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
						alert("Error from server: " + response.error);
					}
				}
			}
			catch(e)
			{
				alert("Unknown response from server");
			}
		}
	}
	
	
	var params = "method=updateSystemSetting&name=" + settingName + "&value=" + encodeURIComponent(settingValue);
	
	ajaxRequest.open("POST", "." , true);
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

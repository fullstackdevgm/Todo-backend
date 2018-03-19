
//This modal is shown when the user has verified his email
function displayVerifyEmailModal(message, reload_page)
{
    var header = labels.email_verification;
    var body = message;
    var footer = '<div class="button" onclick="hideModalContainer()">' + labels.ok + '</div>';
    if (reload_page) {
        footer = '<div class="button" onclick="location.href = \'/\';">' + labels.ok + '</div>';
    }
    
    displayModalContainer(body, header, footer);
}

//This function sends an email to the user to verify his email address
function verifyUserEmail()
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
                if(response.success == true && response.email)
                {
                    showVerificationEmailSentModal(response.email);
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
                        displayGlobalErrorMessage(labels.unable_to_send_verification_email);
                    }
                }
            }
            catch(e)
            {
                displayGlobalErrorMessage(labels.unknown_error+ ' ' + e);
            }
        }
    }
    
    
    var params = "method=sendVerificationEmail";
    
    ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);    
}

//This modal is shown when a verification email has been sent
function showVerificationEmailSentModal(username)
{
    var header = labels.email_sent ;
    var body = '<p style="width:320px">' + sprintf(labels.an_email_has_been_sent_to, username) + '</p>';
    
    var footer = '<div class="button" onclick="hideModalContainer()">' + labels.ok + '</div>';
    
    displayModalContainer(body, header, footer);
}


//This modal is shown when the user first hits the page and gives them an option to accept the gift code or cancel
function displayRedeemGiftCodeModal(giftCode)
{
    var header = labels.redeem_gift_code;
    var body = labels.redeem_gift_code_text + '<br/>';
    var footer = '<div class="button" id="redeem_code_cancel_button" onclick="top.location=\'.\'">' + labels.cancel + '</div>';
    footer += '<div class="button" id="redeem_code_button" onclick="redeemGiftCode(\'' + giftCode + '\')">' + labels.redeem + '</div>';
    
    displayModalContainer(body, header, footer);

    document.getElementById('modal_overlay').onclick = null;
}


function redeemGiftCode(giftCode)
{
    //While we're processing, don't let them click the buttons again
    var cancelButton = document.getElementById('redeem_code_cancel_button');
    var redeemButton = document.getElementById('redeem_code_button');
    
    cancelButton.setAttribute('class', 'button disabled');
    cancelButton.onclick = null;
    
    redeemButton.innerHTML = labels.redeeming +' <span class="progress_indicator" style="display:inline-block;"></span>';
    redeemButton.onclick = null;
    
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
                    var message = labels.thank_you_for_redeeming_text + '<br/>';
                    if(response.expiration_date)
                    {
                        var expirationDateString = displayHumanReadableDate(response.expiration_date, false, true);
                        message += labels.your_todo_cloud_premium_text + '<br/><br/>' + expirationDateString + '<br/>';
                    }
                
                    displayGiftCodeProcessedModal(message, false);
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
                            displayGiftCodeProcessedModal(response.error + '<br/>', true);
                        }
                    }
                    else
                    {
                        displayGiftCodeProcessedModal(labels.unknown_error_redeeming_gift_code + "<br/>", true);
                    }
                }
            }
            catch(e)
            {
                displayGiftCodeProcessedModal(labels.unknown_error_redeeming_gift_code + ": " + e + '<br/>', true);
            }
        }
    }
    
    
    var params = "method=applyGiftCodeToAccount&giftcode=" + giftCode;
    
    ajaxRequest.open("POST", ".", true);
    
    //Send the proper header information along with the request
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    
    ajaxRequest.send(params);    
}

function displayGiftCodeProcessedModal(message, isError)
{
    var header = labels.gift_code_redeemed;
    if(isError)
        header = labels.gift_code_error;
    
    var body = message;
    var footer = '<div class="button" onclick="top.location=\'.\'">' + labels.ok + '</div>';
    
    displayModalContainer(body, header, footer);
    document.getElementById('modal_overlay').onclick = null;
}



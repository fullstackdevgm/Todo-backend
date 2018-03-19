// !Global vars
var recordsPerPage = 50;

function setUpGiftCodePage()
{
	loadGiftCodes(0, recordsPerPage, false);
	loadGiftCodes(0, recordsPerPage, true);
	
};

function loadGiftCodes(offset, limit, used)
{
    var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;

	ajaxRequest.onreadystatechange = function()
	{
		if(ajaxRequest.readyState == 4)
		{
            try
            {
                var response = JSON.parse(ajaxRequest.responseText);

                if(response.success)
                {
                	var doc = document;
                	
                    var giftCodes = response.gift_codes;
                    var totalCount = parseInt(response.gift_code_count, 10); //count of total records in server. Used for pagination
                    
                    var html = getGiftCodeHtmlForGiftCodes(giftCodes);
		            	
		            if(totalCount > recordsPerPage)
		            {
		            	var pages = parseInt(totalCount/recordsPerPage) + 1;
		            	var currentPage = parseInt(offset/recordsPerPage) + 1;
		            	
		            	html += '	<div class="pagination" style="text-align:right;margin-top:10px;">';
		            	
		            	for (var i = 1; i <= pages; i++)
		            	{
		            		var separator = (i == pages) ? '' : '';	
		            		var pageClass = (i == currentPage) ?  'page selected' : 'page';
		            		var pageLink = (i == currentPage) ? 'javascript:void(0);' : 'javascript:loadGiftCodes(\'' + recordsPerPage * (i - 1) + '\', \'' + recordsPerPage+ '\', \'' + used + '\')';
		            			
			            	html += '<a class="' + pageClass + '" href="' + pageLink + '"> ' + i + ' ' + separator + '</span>';
			            	
		            	}
		            	
		            	html += '	</div>';
		            }
		            	
                    if(used)
                        doc.getElementById('used_gift_codes').innerHTML = html;
                    else
                        doc.getElementById('unused_gift_codes').innerHTML = html;
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
                        else
                            alert("Error loading gift codes");
                    }
                }
            }
            catch(e)
            {
                alert("Error loading gift codes " + e);
            }
		}
	}

	var params = 'method=getAllGiftCodes';
    
    if(offset)
    	params += '&offset=' + offset;
    if(limit)
    	params += '&limit=' + limit;
    if(used)
        params += '&used=true';
    
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);

}


function getGiftCodeHtmlForGiftCodes(giftCodeArray)
{
    var html = '';

    html += '<div class="gift_codes">';
    //headers
    html += '	<div class="setting header">';
    html += '		<div class="gift_date">Purchase Date</div>';
    html += '       <div class="gift_name">Purchased By</div>';
    html += '       <div class="gift_time">Months</div>';
    html += '       <div class="gift_name">Recipient Name</div>';
    html += '       <div class="gift_name">Recipient Email</div>';
    html += '       <div class="gift_date">Consumption Date</div>';
    html += '       <div class="gift_name">Consumed By</div>';
    html += '       <div class="gift_name">Stripe Payment Id</div>';
    html += '       <div class="gift_link">Link</div>';

    html += '	</div>';
    if(giftCodeArray.length > 0)
    {   
        for(var i = 0; i < giftCodeArray.length; i++)
        {
            var giftCode = giftCodeArray[i];

            html += '	<div class="setting gift_code">';
            
            var purchaseDate = 'NO PURCHASE DATE';
            if(giftCode.purchase_date)
                purchaseDate =  displayHumanReadableDate(giftCode.purchase_date, false, true);
            
            html += '		<div class="gift_date">' + purchaseDate + '</div>';
            
            var purchaseHREF = '#';
            if(giftCode.purchaser)
                purchaseHREF = '?section=users&userid=' + giftCode.purchaser;
            
            var purchaser = 'NO PURCHASER';
            if(giftCode.purchaser_displayname)
                purchaser = giftCode.purchaser_displayname;
            
            html += '       <div class="gift_name"><a href="' + purchaseHREF + '">' + purchaser + '</a></div>';
            
            var duration = 'NO DURATION';
            if(giftCode.duration)
                duration = giftCode.duration;
            
            html += '       <div class="gift_time">' + duration + '</div>';
            
            var recipient = 'NO RECIPIENT';
            if(giftCode.recipient)
                recipient = giftCode.recipient;
            
            html += '       <div class="gift_name">' + recipient + '</div>';
            
            var email = 'NO EMAIL';
            if(giftCode.recipient_email)
                email = giftCode.recipient_email;
            
            html += '       <div class="gift_name">' + email + '</div>';
            
            var consumptionDate = 'UNUSED';
            if(giftCode.consumption_timestamp)
                consumptionDate = displayHumanReadableDate(giftCode.consumption_timestamp, false, true);
            
            html += '       <div class="gift_date">' + consumptionDate + '</div>';
            
            var consumerHREF = '#';
            if(giftCode.consumer_id)
                consumerHREF =  '?section=users&userid=' + giftCode.consumer_id;
            
            var consumer = 'UNUSED';
            if(giftCode.consumer_displayname)
                consumer = giftCode.consumer_displayname;
            
            html += '       <div class="gift_name"><a href="' + consumerHREF + '">' + consumer + '</a></div>';
            
            var paymentId = 'NO PAYMENT LOGGED';
            if(giftCode.payment_id)
                paymentId = giftCode.payment_id;
            
            html += '       <div class="gift_name">' + paymentId + '</div>';
            
            html += '       <input class="gift_grab_link" type="text" value="' + giftCode.giftcode_link + '" onclick="select(this)" oncontextmenu="onclick()"/>';
            html += '	</div>';

        }
    }
    else
    {
        html += '<p>There are no gift codes in this section.</p>';
    }
        
    html += '</div>';
    
    
    return html;
}